<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\Buckaroo\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Sales\Setup\SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @var \Magento\Quote\Setup\QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @param \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory
     * @param \Magento\Quote\Setup\QuoteSetupFactory $quoteSetupFactory
     */
    public function __construct(
        \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory,
        \Magento\Quote\Setup\QuoteSetupFactory $quoteSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.1.1', '<')) {
            $select = $setup->getConnection()->select()
                ->from(
                    $setup->getTable('sales_order_status'),
                    [
                        'status',
                    ]
                )->where(
                    'status = ?',
                    'tig_buckaroo_new'
                );
            if (count($setup->getConnection()->fetchAll($select)) == 0) {
                /**
                 * Add New status and state
                 */
                $setup->getConnection()->insert(
                    $setup->getTable('sales_order_status'),
                    [
                        'status' => 'tig_buckaroo_new',
                        'label'  => __('TIG Buckaroo New'),
                    ]
                );
                $setup->getConnection()->insert(
                    $setup->getTable('sales_order_status_state'),
                    [
                        'status'           => 'tig_buckaroo_new',
                        'state'            => 'processing',
                        'is_default'       => 0,
                        'visible_on_front' => 1,
                    ]
                );
            } else {
                // Do an update to turn on visible_on_front, since it already exists
                $bind = ['visible_on_front' => 1];
                $where = ['status = ?' => 'tig_buckaroo_new'];
                $setup->getConnection()->update($setup->getTable('sales_order_status_state'), $bind, $where);
            }

            /**
             * Add Pending status and state
             */
            $select = $setup->getConnection()->select()
                ->from(
                    $setup->getTable('sales_order_status'),
                    [
                        'status',
                    ]
                )->where(
                    'status = ?',
                    'tig_buckaroo_pending_payment'
                );
            if (count($setup->getConnection()->fetchAll($select)) == 0) {
                $setup->getConnection()->insert(
                    $setup->getTable('sales_order_status'),
                    [
                        'status' => 'tig_buckaroo_pending_payment',
                        'label'  => __('TIG Buckaroo Pending Payment'),
                    ]
                );
                $setup->getConnection()->insert(
                    $setup->getTable('sales_order_status_state'),
                    [
                        'status'           => 'tig_buckaroo_pending_payment',
                        'state'            => 'processing',
                        'is_default'       => 0,
                        'visible_on_front' => 1,
                    ]
                );
            } else {
                // Do an update to turn on visible_on_front, since it already exists
                $bind = ['visible_on_front' => 1];
                $where = ['status = ?' => 'tig_buckaroo_pending_payment'];
                $setup->getConnection()->update($setup->getTable('sales_order_status_state'), $bind, $where);
            }
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $quoteInstaller = $this->quoteSetupFactory->create(['resourceName' => 'quote_setup', 'setup' => $setup]);
        /** @noinspection PhpUndefinedMethodInspection */
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);

        if (version_compare($context->getVersion(), '0.1.3', '<')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote',
                'buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote',
                'base_buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote_address',
                'buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote_address',
                'base_buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'base_buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_invoiced',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'base_buckaroo_fee_invoiced',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_refunded',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'base_buckaroo_fee_refunded',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'invoice',
                'base_buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'invoice',
                'buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'creditmemo',
                'base_buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'creditmemo',
                'buckaroo_fee',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
        }

        if (version_compare($context->getVersion(), '0.1.4', '<')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote',
                'base_buckaroo_fee_incl_tax',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote',
                'buckaroo_fee_incl_tax',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote',
                'buckaroo_fee_base_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote',
                'buckaroo_fee_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote_address',
                'base_buckaroo_fee_incl_tax',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote_address',
                'buckaroo_fee_incl_tax',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote_address',
                'buckaroo_fee_base_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $quoteInstaller->addAttribute(
                'quote_address',
                'buckaroo_fee_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'base_buckaroo_fee_incl_tax',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_incl_tax',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_base_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
        }

        if (version_compare($context->getVersion(), '0.1.5', '<')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'invoice',
                'buckaroo_fee_base_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'invoice',
                'buckaroo_fee_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'creditmemo',
                'buckaroo_fee_base_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'creditmemo',
                'buckaroo_fee_tax_amount',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
        }

        if (version_compare($context->getVersion(), '0.1.6', '<')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_base_tax_amount_invoiced',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_tax_amount_invoiced',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );

            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_base_tax_amount_refunded',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
            /** @noinspection PhpUndefinedMethodInspection */
            $salesInstaller->addAttribute(
                'order',
                'buckaroo_fee_tax_amount_refunded',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL]
            );
        }
    }
}
