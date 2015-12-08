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

namespace TIG\Buckaroo\Model\Method;

class SepaDirectDebit extends AbstractMethod
{
    const PAYMENT_METHOD_BUCKAROO_SEPA_DIRECT_DEBIT_CODE = 'tig_buckaroo_sepadirectdebit';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_BUCKAROO_SEPA_DIRECT_DEBIT_CODE;

    /**
     * @var bool
     */
    protected $_isGateway               = true;

    /**
     * @var bool
     */
    protected $_canAuthorize            = false;

    /**
     * @var bool
     */
    protected $_canCapture              = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial       = false;

    /**
     * @var bool
     */
    protected $_canRefund               = true;

    /**
     * @var bool
     */
    protected $_canVoid                 = true;

    /**
     * @var bool
     */
    protected $_canUseInternal          = true;

    /**
     * @var bool
     */
    protected $_canUseCheckout          = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * {@inheritdoc}
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('customer_bic', $data['customer_bic']);
            $this->getInfoInstance()->setAdditionalInformation('customer_iban', $data['customer_iban']);
            $this->getInfoInstance()->setAdditionalInformation('customer_account_name', $data['customer_account_name']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $this->getInfoInstance()->setAdditionalInformation('customer_bic', $data->getCustomerBic());
            $this->getInfoInstance()->setAdditionalInformation('customer_iban', $data->getCustomerIban());
            $this->getInfoInstance()->setAdditionalInformation('customer_account_name', $data->getCustomerAccountName());
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCaptureTransaction($payment)
    {
        $transactionBuilder = $this->transactionBuilderFactory->get('order');

        $services = [
            'Name'             => 'sepadirectdebit',
            'Action'           => 'Pay',
            'Version'          => 1,
            'RequestParameter' => [
                [
                    '_'    => $this->getInfoInstance()->getAdditionalInformation('customer_account_name'),
                    'Name' => 'customeraccountname',
                ],
                [
                    '_'    => $this->getInfoInstance()->getAdditionalInformation('customer_iban'),
                    'Name' => 'CustomerIBAN',
                ],
            ],
        ];

        if ($this->getInfoInstance()->getAdditionalInformation('customer_bic')) {
            $services[0]['RequestParameter'][0][] = [
                '_'    => $this->getInfoInstance()->getAdditionalInformation('customer_bic'),
                'Name' => 'CustomerBIC',
            ];
        }

        $transactionBuilder->setOrder($payment->getOrder())
                           ->setServices($services)
                           ->setMethod('TransactionRequest');

        $transaction = $transactionBuilder->build();

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeTransaction($payment)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRefundTransaction($payment)
    {
        $transactionBuilder = $this->transactionBuilderFactory->get('refund');

        $services = [
            'Service' => [
                'Name' => 'sepadirectdebit',
                'Action' => 'Pay',
                'Version' => 1,
            ]
        ];

        $transactionBuilder->setOrder($payment->getOrder())
                           ->setServices($services)
                           ->setMethod('TransactionRequest');

        $transaction = $transactionBuilder->build();

        return $transaction;
    }
}