<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\Exception\ConnectionException;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\Facts\Facts;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use pi\ratepay\Application\Model\Settings;
use RatePAY\ModelBuilder;
use RatePAY\RequestBuilder;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class ModelFactory extends Base
{

    /**
     * Assignment helper for ratepay payment activity
     *
     * @var array
     */
    protected static $_aCountry2Payment2Configs = [
        'de' => [
            'rechnung' => [
                'active' => 'blRPInvoiceActive',
                'sandbox' => 'blRPInvoiceSandbox',
                'profileid' => 'sRPInvoiceProfileId',
                'secret' => 'sRPInvoiceSecret',
            ],
            'rate' => [
                'active' => 'blRPInstallmentActive',
                'sandbox' => 'blRPInstallmentSandbox',
                'profileid' => 'sRPInstallmentProfileId',
                'secret' => 'sRPInstallmentSecret',
                'settlement' => 'sRPInstallmentSettlement',
            ],
            'rate0' => [
                'active' => 'blRPInstallment0Active',
                'sandbox' => 'blRPInstallment0Sandbox',
                'profileid' => 'sRPInstallment0ProfileId',
                'secret' => 'sRPInstallment0Secret',
                'settlement' => 'sRPInstallment0Settlement',
            ],
            'elv' => [
                'active' => 'blRPElvActive',
                'sandbox' => 'blRPElvSandbox',
                'profileid' => 'sRPElvProfileId',
                'secret' => 'sRPElvSecret',
            ],
            'invoice' => [
                'active' => 'blRPInvoiceActive',
                'sandbox' => 'blRPInvoiceSandbox',
                'profileid' => 'sRPInvoiceProfileId',
                'secret' => 'sRPInvoiceSecret',
            ],
            'installment' => [
                'active' => 'blRPInstallmentActive',
                'sandbox' => 'blRPInstallmentSandbox',
                'profileid' => 'sRPInstallmentProfileId',
                'secret' => 'sRPInstallmentSecret',
                'settlement' => 'sRPInstallmentSettlement',
            ],
            'installment0' => [
                'active' => 'blRPInstallment0Active',
                'sandbox' => 'blRPInstallment0Sandbox',
                'profileid' => 'sRPInstallment0ProfileId',
                'secret' => 'sRPInstallment0Secret',
                'settlement' => 'sRPInstallment0Settlement',
            ],
        ],
        'at' => [
            'rechnung' => [
                'active' => 'blRPAustriaInvoice',
                'sandbox' => 'blRPAustriaInvoiceSandbox',
                'profileid' => 'sRPAustriaInvoiceProfileId',
                'secret' => 'sRPAustriaInvoiceSecret',
            ],
            'rate' => [
                'active' => 'blRPAustriaInstallment',
                'sandbox' => 'blRPAustriaInstallmentSandbox',
                'profileid' => 'sRPAustriaInstallmentProfileId',
                'secret' => 'sRPAustriaInstallmentSecret',
                'settlement' => 'sRPAustriaInstallmentSettlement',
            ],
            'rate0' => [
                'active' => 'blRPAustriaInstallment0',
                'sandbox' => 'blRPAustriaInstallment0Sandbox',
                'profileid' => 'sRPAustriaInstallment0ProfileId',
                'secret' => 'sRPAustriaInstallment0Secret',
                'settlement' => 'sRPAustriaInstallment0Settlement',
            ],
            'elv' => [
                'active' => 'blRPAustriaElv',
                'sandbox' => 'blRPAustriaElvSandbox',
                'profileid' => 'sRPAustriaElvProfileId',
                'secret' => 'sRPAustriaElvSecret',
            ],
            'invoice' => [
                'active' => 'blRPAustriaInvoice',
                'sandbox' => 'blRPAustriaInvoiceSandbox',
                'profileid' => 'sRPAustriaInvoiceProfileId',
                'secret' => 'sRPAustriaInvoiceSecret',
            ],
            'installment' => [
                'active' => 'blRPAustriaInstallment',
                'sandbox' => 'blRPAustriaInstallmentSandbox',
                'profileid' => 'sRPAustriaInstallmentProfileId',
                'secret' => 'sRPAustriaInstallmentSecret',
                'settlement' => 'sRPAustriaInstallmentSettlement',
            ],
            'installment0' => [
                'active' => 'blRPAustriaInstallment0',
                'sandbox' => 'blRPAustriaInstallment0Sandbox',
                'profileid' => 'sRPAustriaInstallment0ProfileId',
                'secret' => 'sRPAustriaInstallment0Secret',
                'settlement' => 'sRPAustriaInstallment0Settlement',
            ],
        ],
        'ch' => [
            'rechnung' => [
                'active' => 'blRPSwitzerlandInvoice',
                'sandbox' => 'blRPSwitzerlandInvoiceSandbox',
                'profileid' => 'sRPSwitzerlandInvoiceProfileId',
                'secret' => 'sRPSwitzerlandInvoiceSecret',
            ],
            'invoice' => [
                'active' => 'blRPSwitzerlandInvoice',
                'sandbox' => 'blRPSwitzerlandInvoiceSandbox',
                'profileid' => 'sRPSwitzerlandInvoiceProfileId',
                'secret' => 'sRPSwitzerlandInvoiceSecret',
            ],
        ],
        'nl' => [
            'rechnung' => [
                'active' => 'blRPNetherlandInvoice',
                'sandbox' => 'blRPNetherlandInvoiceSandbox',
                'profileid' => 'sRPNetherlandInvoiceProfileId',
                'secret' => 'sRPNetherlandInvoiceSecret',
            ],
            'elv' => [
                'active' => 'blRPAustriaElv',
                'sandbox' => 'blRPNetherlandElvSandbox',
                'profileid' => 'sRPNetherlandElvProfileId',
                'secret' => 'sRPNetherlandElvSecret',
            ],
            'invoice' => [
                'active' => 'blRPNetherlandInvoice',
                'sandbox' => 'blRPNetherlandInvoiceSandbox',
                'profileid' => 'sRPNetherlandInvoiceProfileId',
                'secret' => 'sRPNetherlandInvoiceSecret',
            ],
        ],
    ];


    protected $_orderId;

    protected $_countryCode;

    protected $_securityCode;

    protected $_profileId;

    protected $_sandbox;

    protected $_paymentType;

    protected $_basket;

    protected $_order;

    protected $_transactionId;

    protected $_deviceToken;

    protected $_customerId;

    protected $_subtype;

    protected $_shopId;

    protected $_countryId;

    protected $_calculationData = [];

    protected $_orderNumber;

    /**
     *
     *
     * @return array
     */
    public static function getConfigurationParameterMap()
    {
        return self::$_aCountry2Payment2Configs;
    }

    /**
     * @param string $sCountry
     * @return bool
     */
    public static function getSettlementTypeConfigParamByCountry($sCountry)
    {
        if (isset(self::$_aCountry2Payment2Configs[strtolower($sCountry)]['installment']['settlement'])) {
            return self::$_aCountry2Payment2Configs[strtolower($sCountry)]['installment']['settlement'];
        }
        return false;
    }

    /**
     * @param mixed $subtype
     */
    public function setSubtype($subtype)
    {
        $this->_subtype = $subtype;
    }

    /**
     * @param mixed $shopId
     */
    public function setShopId($shopId)
    {
        $this->_shopId = $shopId;
    }

    /**
     * @param mixed $countryId
     */
    public function setCountryId($countryId)
    {
        $this->_countryId = $countryId;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->_customerId = $customerId;
    }


    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId)
    {
        $this->_orderId = $orderId;
    }

    /**
     * @param mixed $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->_transactionId = $transactionId;
    }

    /**
     * @param mixed $deviceToken
     */
    public function setDeviceToken($deviceToken)
    {
        $this->_deviceToken = $deviceToken;
    }

    /**
     * @param mixed $basket
     */
    public function setBasket($basket)
    {
        $this->_basket = $basket;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->_order = $order;
    }

    /**
     * @param mixed $countryCode
     */
    public function setCountryCode($countryCode)
    {
        $this->_countryCode = $countryCode;
    }

    /**
     * @param mixed $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->_securityCode = $securityCode;
    }

    /**
     * @param mixed $profileId
     */
    public function setProfileId($profileId)
    {
        $this->_profileId = $profileId;
    }

    /**
     * @param mixed $sandbox
     */
    public function setSandbox($sandbox)
    {
        $this->_sandbox = (bool)$sandbox;
    }

    /**
     * @param mixed $paymentType
     */
    public function setPaymentType($paymentType)
    {
        $this->_paymentType = $paymentType;
    }

    /**
     * @param array $calculationData
     */
    public function setCalculationData($calculationData)
    {
        $this->_calculationData = $calculationData;
    }

    /**
     * do operation
     *
     * @param $operation
     * @return bool|mixed|object
     */
    public function doOperation($operation)
    {
        switch ($operation) {
            case 'PAYMENT_INIT':
                return $this->makePaymentInit();
                break;
            case 'PAYMENT_REQUEST':
                return $this->makePaymentRequest();
                break;
            case 'PAYMENT_CONFIRM':
                return $this->makePaymentConfirm();
                break;
            case 'CONFIRMATION_DELIVER':
                return $this->makeConfirmationDeliver();
                break;
            case 'PAYMENT_CHANGE':
                return $this->makePaymentChange();
                break;
            case 'PROFILE_REQUEST':
                return $this->makeProfileRequest();
                break;
            case 'CALCULATION_REQUEST':
                return $this->makeCalculationRequest();
                break;
        }
    }

    /**
     * Get RatePAY Confirm Settings
     *
     * @return int
     */
    private function getConfirmSettings()
    {
        $moduleSettingService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettingServiceInterface::class);
        $iRPAutoPaymentConfirm =
            (int)$moduleSettingService->getBoolean('blRPAutoPaymentConfirm', 'ratepay');

        return $iRPAutoPaymentConfirm;
    }

    /**
     * make a payment confirm
     *
     * @return bool
     */
    private function makePaymentConfirm()
    {
        $util = oxNew(Utilities::class);
        $paymentMethod = $util->getPaymentMethod($this->_paymentType);

        $confirm = $this->getConfirmSettings();
        if ($confirm == 0) {
            return true;
        }

        $mbHead = $this->getHead();
        $rb = oxNew(RequestBuilder::class, $this->_sandbox);

        $paymentConfirm = $rb->callPaymentConfirm($mbHead);

        LogsService::getInstance()->logRatepayTransaction(
            $this->getOrderNumber(),
            $this->_transactionId,
            $this->_paymentType,
            'PAYMENT_CONFIRM',
            $this->_subtype,
            '',
            '',
            $paymentConfirm
        );

        if ($paymentConfirm->isSuccessful()) {
            return true;
        }
        return false;
    }

    /**
     * make calculation request
     *
     * @return object
     */
    private function makeCalculationRequest()
    {
        $mbHead = $this->getHead();

        $array['InstallmentCalculation']['Amount'] = $this->_calculationData['requestAmount'];
        if ($this->_calculationData['requestSubtype'] == 'calculation-by-rate') {
            $array['InstallmentCalculation']['CalculationRate']['Rate'] = $this->_calculationData['requestValue'];
        } else {
            $array['InstallmentCalculation']['CalculationTime']['Month'] = $this->_calculationData['requestValue'];
        }
        $array['InstallmentCalculation']['PaymentFirstday'] = $this->_calculationData['paymentFirstday'];
        $mbContentTime = oxNew(ModelBuilder::class, 'Content');
        $mbContentTime->setArray($array);
        $rb = oxNew(RequestBuilder::class, $this->_sandbox);
        $calculationRequest = $rb->callCalculationRequest($mbHead, $mbContentTime)->subtype(
            $this->_calculationData['requestSubtype']
        );
        return $calculationRequest;
    }

    /**
     * make confirmation deliver
     *
     * @return object
     */
    private function makeConfirmationDeliver()
    {
        $mbContent = oxNew(ModelBuilder::class, 'Content');

        $this->getOrderCountryId();

        $mbHead = $this->getHead();

        $shoppingBasket = [
            'ShoppingBasket' => $this->getSpecialBasket(),
        ];

        $mbContent->setArray($shoppingBasket);

        // OX-31 Add invoice number if existing
        $orderBillNr = $this->getOrderBillNr();
        if (!empty($orderBillNr)) {
            $invoicing = [
                'Invoicing' => [
                    'InvoiceId' => $orderBillNr
                ]
            ];

            $mbContent->setArray($invoicing);
        }

        $rb = oxNew(RequestBuilder::class, $this->_sandbox);
        $confirmationDeliver = $rb->callConfirmationDeliver($mbHead, $mbContent);
        LogsService::getInstance()->logRatepayTransaction(
            $this->getOrderNumber(),
            $this->_transactionId,
            $this->_paymentType,
            'CONFIRMATION_DELIVER',
            $this->_subtype,
            '',
            '',
            $confirmationDeliver
        );
        return $confirmationDeliver;
    }

    /**
     * get order country id
     */
    private function getOrderCountryId()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXBILLCOUNTRYID')
            ->from('oxorder')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $this->_orderId);
        $sCountryId = $oQueryBuilder->execute();
        $this->_countryId = $sCountryId->fetchOne();
    }

    /**
     * get order
     */
    protected function getOrderBillNr()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXBILLNR')
            ->from('oxorder')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $this->_orderId);
        $sBillNr = $oQueryBuilder->execute();
        $sBillNr = $sBillNr->fetchOne();
        return $sBillNr;
    }

    /**
     * Sets countryid by currently logged in user
     *
     * @param void
     * @return void
     */
    protected function piSetCountryIdByUser()
    {
        if (empty($this->_countryId)) { // might be set already, for example by getOrderCountryId()
            $oUser = $this->getUser();
            $this->_countryId = $oUser->oxuser__oxcountryid->value;
        }
    }

    /**
     * make payment change
     *
     * @return object|bool
     */
    private function makePaymentChange()
    {
        $this->getOrderCountryId();
        $mbHead = $this->getHead();
        $detailsViewData = oxNew(DetailsViewData::class, $this->_orderId);

        $this->basket = $detailsViewData->getPreparedOrderArticles();

        $shoppingBasket = ['ShoppingBasket' => $this->getSpecialBasket()];

        $mbContent = oxNew(ModelBuilder::class, 'Content');
        $mbContent->setArray($shoppingBasket);

        $rb = oxNew(RequestBuilder::class, $this->_sandbox);
        $paymentChange = $rb->callPaymentChange($mbHead, $mbContent)->subtype($this->_subtype);
        LogsService::getInstance()->logRatepayTransaction(
            $this->getOrderNumber(),
            $this->_transactionId,
            $this->_paymentType,
            'PAYMENT_CHANGE',
            $this->_subtype,
            '',
            '',
            $paymentChange
        );
        return $paymentChange;
    }

    /**
     * return the head for an request
     */
    private function getHead()
    {
        if ($this->_profileId && $this->_securityCode) {
            $sProfileId = $this->_profileId;
            $sSecurityCode = $this->_securityCode;
        } else {
            $this->piSetCountryIdByUser();
            $util = oxNew(Utilities::class);
            $paymentMethod = $util->getPaymentMethod($this->_paymentType);
            $paymentMethod = strtolower($paymentMethod);
            $country = $this->getCountryCodeById($this->_countryId);
            $country = strtolower($country);

            $sConfigParamProfileId = self::$_aCountry2Payment2Configs[$country][$paymentMethod]['profileid'];
            $sConfigParamSecurityCode = self::$_aCountry2Payment2Configs[$country][$paymentMethod]['secret'];
            $sConfigParamSandbox = self::$_aCountry2Payment2Configs[$country][$paymentMethod]['sandbox'];

            $moduleSettingService = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ModuleSettingServiceInterface::class);
            $sProfileId = $moduleSettingService->getString($sConfigParamProfileId, 'ratepay');
            $sSecurityCode = $moduleSettingService->getString($sConfigParamSecurityCode, 'ratepay');
            $bSandbox = $moduleSettingService->getBoolean($sConfigParamSandbox, 'ratepay');

            $this->setSandbox($bSandbox);
        }
        $oModule = oxNew('oxModule');
        $oModule->load('ratepay');
        $headArray = [
            'SystemId' => $_SERVER['SERVER_ADDR'],
            'Credential' => [
                'ProfileId' => $sProfileId,
                'Securitycode' => $sSecurityCode
            ],
            'Meta' => [
                'Systems' => [
                    'System' => [
                        'Name' => 'OXID_' . (new Facts())->getEdition(),
                        'Version' => ShopVersion::getVersion() . '_' . $oModule->getInfo('version')
                    ]
                ]
            ]
        ];
        $modelBuilder = oxNew(ModelBuilder::class);

        if (!empty($this->_transactionId)) {
            $modelBuilder->setTransactionId($this->_transactionId);
        }

        $modelBuilder->setArray($headArray);

        if (!empty($this->_orderId)) {
            $oContainer = ContainerFactory::getInstance()->getContainer();
            /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
            $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->select('OXORDERNR')
                ->from('oxorder')
                ->where('oxid = :oxid')
                ->setParameter(':oxid', $this->_orderId);
            $sOrderNr = $oQueryBuilder->execute();
            $external['External']['OrderId'] = $sOrderNr->fetchOne();
        }
        if (!empty($this->_customerId)) {
            $external['External']['MerchantConsumerId'] = $this->_customerId;
        }

        if (!empty($this->_deviceToken)) {
            $modelBuilder->setCustomerDevice(
                $modelBuilder->CustomerDevice()->setDeviceToken($this->_deviceToken)
            );
        }
        if (!empty($external)) {
            $modelBuilder->setArray($external);
        }

        return $modelBuilder;
    }

    /**
     * make profile request
     *
     * @return bool
     */
    private function makeProfileRequest()
    {
        $head = $this->getHead();

        $rb = oxNew(RequestBuilder::class, $this->_sandbox);
        $profileRequest = $rb->callProfileRequest($head);
        if ($profileRequest->isSuccessful()) {
            return $profileRequest->getResult();
        }
        return false;
    }

    /**
     * make payment init
     *
     * @return object|bool
     */
    private function makePaymentInit()
    {
        $head = $this->getHead();
        $rb = oxNew(RequestBuilder::class, $this->_sandbox);
        $paymentInit = $rb->callPaymentInit($head);
        LogsService::getInstance()->logRatepayTransaction(
            $this->getOrderNumber(),
            '',
            $this->_paymentType,
            'PAYMENT_INIT',
            '',
            $this->getUser()->oxuser__oxfname->value,
            $this->getUser()->oxuser__oxlname->value,
            $paymentInit
        );
        return $paymentInit;
    }

    /**
     * make payment request
     *
     * @return mixed
     */
    private function makePaymentRequest()
    {
        $head = $this->getHead();
        $basket = $this->getBasket();
        $util = oxNew(Utilities::class);

        $salutation = strtoupper($this->getUser()->oxuser__oxsal->value);
        switch ($salutation) {
            default:
                $gender = 'u';
                break;
            case 'MR':
                $gender = 'm';
                break;
            case 'MRS':
                $gender = 'f';
                break;
        }

        if (!empty($this->getUser()->oxuser__oxfon->value)) {
            $phone = $this->getUser()->oxuser__oxfon->value;
        } else {
            $phone = $this->getUser()->oxuser__oxmbfon->value;
        }

        $contentArr = [
            'Customer' => [
                'Gender' => $gender,
                'FirstName' => $this->getUser()->oxuser__oxfname->value,
                'LastName' => $this->getUser()->oxuser__oxlname->value,
                'DateOfBirth' => $this->getUser()->oxuser__oxbirthdate->value,
                'IpAddress' => "127.0.0.1",
                'Addresses' => [
                    [
                        'Address' => $this->getCustomerAddress()
                    ],
                    [
                        'Address' => $this->getDeliveryAddress()
                    ]
                ],
                'Contacts' => [
                    'Email' => $this->getUser()->oxuser__oxusername->value,
                    'Phone' => [
                        'DirectDial' => !empty($phone) ? $phone : '03033988560'
                    ],
                ],
            ],
            'ShoppingBasket' => $basket,
            'Payment' => [
                'Method' => strtolower($util->getPaymentMethod($this->_paymentType)),
                'Amount' => $this->_basket->getPrice()->getBruttoPrice()
            ]
        ];

        if (!empty($this->getUser()->oxuser__oxcompany->value)) {
            $contentArr['Customer']['CompanyName'] = $this->getUser()->oxuser__oxcompany->value;
            $contentArr['Customer']['VatId'] = $this->getUser()->oxuser__oxustid->value;
        }

        if ($util->getPaymentMethod($this->_paymentType) == 'ELV') {
            $contentArr['Customer']['BankAccount'] = $this->getCustomerBankdata($this->_paymentType);
        }
        if ($util->getPaymentMethod($this->_paymentType) == 'INSTALLMENT') {
            $contentArr['Payment']['InstallmentDetails'] = $this->getInstallmentData();
            $contentArr['Payment']['DebitPayType'] = 'BANK-TRANSFER';
            $contentArr['Payment']['Amount'] = Registry::getSession()->getVariable('pi_ratepay_rate_total_amount');

            $settings = oxNew(Settings::class);
            $iban = Registry::getSession()->getVariable('pi_ratepay_rate_bank_iban');
            $settings->loadByType(
                $util->getPaymentMethod('pi_ratepay_rate'),
                Registry::getSession()->getVariable('shopId')
            );
            if (!empty($iban) && $iban !== 'undefined') {
                $contentArr['Customer']['BankAccount'] = $this->getCustomerBankdata('pi_ratepay_rate');
                $contentArr['Payment']['DebitPayType'] = 'DIRECT-DEBIT';
            }
        }
        if ($util->getPaymentMethod($this->_paymentType) == 'INSTALLMENT0') {
            $contentArr['Payment']['InstallmentDetails'] = $this->getInstallment0Data();
            $contentArr['Payment']['DebitPayType'] = 'BANK-TRANSFER';
            $contentArr['Payment']['Amount'] = Registry::getSession()->getVariable('pi_ratepay_rate0_total_amount');
            $contentArr['Payment']['Method'] = 'installment'; // OX-28: For RP, installment0 is still installment

            $settings = oxNew(Settings::class);
            $iban = Registry::getSession()->getVariable('pi_ratepay_rate0_bank_iban');
            $settings->loadByType(
                $util->getPaymentMethod('pi_ratepay_rate0'),
                Registry::getSession()->getVariable('shopId')
            );
            if (!empty($iban) && $iban !== 'undefined') {
                $contentArr['Customer']['BankAccount'] = $this->getCustomerBankdata('pi_ratepay_rate0');
                $contentArr['Payment']['DebitPayType'] = 'DIRECT-DEBIT';
            }
        }

        $shippingCosts = $this->getShippingCosts();
        if (!empty($shippingCosts)) {
            $contentArr['ShoppingBasket']['Shipping'] = $shippingCosts;
        }

        $discount = $this->getDiscount();
        if (!empty($discount)) {
            $contentArr['ShoppingBasket']['Discount'] = $discount;
        }

        $mbContent = oxNew(ModelBuilder::class, 'Content');
        $mbContent->setArray($contentArr);

        $rb = oxNew(RequestBuilder::class, $this->_sandbox);

        $paymentRequest = $rb->callPaymentRequest($head, $mbContent);
        LogsService::getInstance()->logRatepayTransaction(
            $this->getOrderNumber(),
            $this->_transactionId,
            $this->_paymentType,
            'PAYMENT_REQUEST',
            '',
            $this->getUser()->oxuser__oxfname->value,
            $this->getUser()->oxuser__oxlname->value,
            $paymentRequest
        );
        return $paymentRequest;
    }

    /**
     * get shipping costs
     *
     * @return array|bool
     */
    private function getShippingCosts()
    {
        $basket = $this->_basket;
        if (method_exists($basket, 'getDeliveryCost') && $basket->getDeliveryCost()) {
            // OX-46 use BruttoPrice to place correct shipping costs with the payment request
            /** @var Price $deliveryCostsItem */
            $deliveryCostsItem = $basket->getDeliveryCost();
            $deliveryCosts = $deliveryCostsItem->getBruttoPrice();
            $deliveryVat = $deliveryCostsItem->getVat();
        } elseif (method_exists($basket, 'getDeliveryCosts') && $basket->getDeliveryCosts()) {
            $deliveryCosts = $basket->getDeliveryCosts();
            if ($basket->$deliveryCosts() > 0) {
                $deliveryVat = $basket->getDelCostVatPercent();
            } else {
                $deliveryVat = 0;
            }
        } else {
            return false;
        }

        if (empty($deliveryCosts)) {
            return false;
        }
        $shipping = [
            'Description' => 'Shipping Costs',
            'UnitPriceGross' => $deliveryCosts,
            'TaxRate' => $deliveryVat,
        ];

        return $shipping;
    }

    /**
     * get discount
     *
     * @return array|bool|int
     */
    private function getDiscount()
    {
        $discount = 0;
        $basket = $this->_basket;
        $util = oxNew(Utilities::class);

        $sDiscountTitle = '';

        if ($basket->getTotalDiscount() && $basket->getTotalDiscount()->getBruttoPrice() > 0) {
            $discount = $discount + (float)$util->getFormattedNumber($basket->getTotalDiscount()->getBruttoPrice());

            $aDiscounts = $basket->getDiscounts();
            foreach ($aDiscounts as $oDiscount) {
                $sDiscountTitle .= '_' . $oDiscount->sDiscount;
            }
        }

        if (count($basket->getVouchers())) {
            foreach ($basket->getVouchers() as $voucher) {
                $vNr = $voucher->sVoucherId;
                $sDiscountTitle .= '_' . $this->getVoucherTitle($vNr);
                $discount = $discount + (float)$util->getFormattedNumber($voucher->dVoucherdiscount);
            }
        }

        if (empty($discount) || $discount <= 0) {
            return false;
        }

        $sDiscountTitle = trim($sDiscountTitle, '_');

        $discount = [
            'Description' => $sDiscountTitle,
            'UnitPriceGross' => $basket->getTotalDiscountSum(),
            'TaxRate' => $util->getFormattedNumber("0"),
        ];

        return $discount;
    }

    /**
     * get voucher title
     *
     * @param $oxid
     * @return false|string
     */
    private function getVoucherTitle($oxid)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXVOUCHERSERIEID')
            ->from('oxvouchers')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $oxid);
        $sVoucher = $oQueryBuilder->execute();
        $sVoucher = $sVoucher->fetchOne();

        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXSERIENR')
            ->from('oxvoucherseries')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $sVoucher);
        $sOxVoucherSeries = $oQueryBuilder->execute();
        $sOxVoucherSeries = $sOxVoucherSeries->fetchOne();
        return $sOxVoucherSeries;
    }

    /**
     * get installment data
     *
     * @return array
     */
    private function getInstallmentData()
    {
        $util = oxNew(Utilities::class);
        return [
            'InstallmentNumber' => Registry::getSession()->getVariable('pi_ratepay_rate_number_of_rates'),
            'InstallmentAmount' => $util->getFormattedNumber(
                Registry::getSession()->getVariable('pi_ratepay_rate_rate'),
                '2',
                '.'
            ),
            'LastInstallmentAmount' => $util->getFormattedNumber(
                Registry::getSession()->getVariable('pi_ratepay_rate_last_rate'),
                '2',
                '.'
            ),
            'InterestRate' => $util->getFormattedNumber(
                Registry::getSession()->getVariable('pi_ratepay_rate_interest_rate'),
                '2',
                '.'
            )
        ];
    }

    /**
     * get installment 0% data
     *
     * @return array
     */
    private function getInstallment0Data()
    {
        $util = oxNew(Utilities::class);
        return [
            'InstallmentNumber' => Registry::getSession()->getVariable('pi_ratepay_rate0_number_of_rates'),
            'InstallmentAmount' => $util->getFormattedNumber(
                Registry::getSession()->getVariable('pi_ratepay_rate0_rate'),
                '2',
                '.'
            ),
            'LastInstallmentAmount' => $util->getFormattedNumber(
                Registry::getSession()->getVariable('pi_ratepay_rate0_last_rate'),
                '2',
                '.'
            ),
            'InterestRate' => $util->getFormattedNumber(
                Registry::getSession()->getVariable('pi_ratepay_rate0_interest_rate'),
                '2',
                '.'
            )
        ];
    }

    /**
     * Get customers bank-data, owner can be retrieved either in session or if not set in $this->getUser().
     *
     * @return array
     * @todo validate if bankdata is in session
     * @todo bank data persistence
     */
    private function getCustomerBankdata($paymentType)
    {
        $bankData = [];
        $bankDataType = Registry::getSession()->getVariable($paymentType . '_bank_datatype');
        $bankAccountNumber = Registry::getSession()->getVariable($paymentType . '_bank_account_number');
        $bankCode = Registry::getSession()->getVariable($paymentType . '_bank_code');
        $bankIban = Registry::getSession()->getVariable($paymentType . '_bank_iban');
        $elvUseCompany = Registry::getSession()->getVariable('elv_use_company_name');

        if ($bankDataType == 'classic') {
            $bankData['BankAccountNumber'] = $bankAccountNumber;
            $bankData['BankCode'] = $bankCode;
        } else {
            $bankData['Iban'] = $bankIban;
        }

        $owner = null;
        if (Registry::getSession()->hasVariable($paymentType . '_bank_owner')) {
            $bankData['Owner'] = Registry::getSession()->getVariable($paymentType . 'elv_bank_owner');
        } else {
            if (!empty($elvUseCompany) && $elvUseCompany == 1) {
                $bankData['Owner'] = $this->getUser()->oxuser__oxcompany->value;
            } else {
                $bankData['Owner'] = $this->getUser()->oxuser__oxfname->value . ' ' . $this->getUser(
                    )->oxuser__oxlname->value;
            }
        }

        return $bankData;
    }

    /**
     * Get complete customer address.
     *
     * @return array
     */
    private function getCustomerAddress()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $this->getUser()->oxuser__oxcountryid->value);
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();

        $address = [
            'Type' => 'billing',
            'Street' => $this->getUser()->oxuser__oxstreet->value,
            'StreetNumber' => $this->getUser()->oxuser__oxstreetnr->value,
            'ZipCode' => $this->getUser()->oxuser__oxzip->value,
            'City' => $this->getUser()->oxuser__oxcity->value,
            'CountryCode' => $sCountryCode
        ];

        return $address;
    }

    /**
     * Get complete delivery address.
     *
     * @return array
     */
    private function getDeliveryAddress()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $order = oxNew(Order::class);
        $deliveryAddress = $order->getDelAddressInfo();

        if (is_null($deliveryAddress)) {
            $address = $this->getCustomerAddress();
            $address['Type'] = 'delivery';
            $address['FirstName'] = $this->getUser()->oxuser__oxfname->value;
            $address['LastName'] = $this->getUser()->oxuser__oxlname->value;
            return $address;
        }

        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $deliveryAddress->oxaddress__oxcountryid->value);
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();

        $address = [
            'Type' => 'delivery',
            'FirstName' => $deliveryAddress->oxaddress__oxfname->value,
            'LastName' => $deliveryAddress->oxaddress__oxlname->value,
            'Street' => $deliveryAddress->oxaddress__oxstreet->value,
            'StreetNumber' => $deliveryAddress->oxaddress__oxstreetnr->value,
            'ZipCode' => $deliveryAddress->oxaddress__oxzip->value,
            'City' => $deliveryAddress->oxaddress__oxcity->value,
            'CountryCode' => $sCountryCode
        ];

        if (!empty($deliveryAddress->oxaddress__oxcompany->value)) {
            $address['Company'] = $deliveryAddress->oxaddress__oxcompany->value;
        }

        return $address;
    }

    /**
     * get special basket for deliver and change
     *
     * @return array
     */
    private function getSpecialBasket()
    {
        $shoppingBasket = [];
        $artnr = [];

        $api = $this->isNewApi();

        $blHasVoucher = false;
        foreach ($this->_basket as $article) {
            if (substr($article['artnum'], 0, 7) == 'voucher' && stripos(
                    $article['artnum'],
                    'pi-Merchant-Voucher'
                ) === false) {
                $blHasVoucher = true;
            }
        }

        foreach ($this->_basket as $article) {
            if (Registry::getRequest()->getRequestEscapedParameter(
                    $article['arthash']
                ) <= 0 && $article['title'] !== 'Credit') {
                continue;
            }
            if ($article['artnum'] == 'oxdelivery') {
                if ($api == true) {
                    $shoppingBasket['Shipping'] = [
                        'Description' => 'Shipping Costs',
                        'UnitPriceGross' => number_format(
                            $article['unitprice'] + ($article['unitprice'] / 100 * $article['vat']),
                            '2',
                            '.',
                            ''
                        ),
                        'TaxRate' => $article['vat'],
                    ];
                    continue;
                }
            }

            if (substr($article['artnum'], 0, 7) == 'voucher' || $article['artnum'] == 'discount' || stripos(
                    $article['artnum'],
                    'pi-Merchant-Voucher'
                ) !== false) {
                if ($api == true) {
                    if (empty($article['oxtitle'])) {
                        $article['oxtitle'] = $article['title'];
                    }
                    if (!empty($shoppingBasket['Discount']['UnitPriceGross'])) {
                        $article['unitprice'] = $article['unitprice'] + $shoppingBasket['Discount']['UnitPriceGross'];
                        $article['oxtitle'] = $shoppingBasket['Discount']['Description'] . '_' . $article['oxtitle'];
                    }
                    $shoppingBasket['Discount'] = [
                        'Description' => $article['oxtitle'],
                        'UnitPriceGross' => $article['unitprice'],
                        'TaxRate' => $article['vat'],
                    ];
                    continue;
                }
            }

            $item = [
                'Description' => $article['title'],
                'ArticleNumber' => $article['artnum'],
                'Quantity' => Registry::getRequest()->getRequestEscapedParameter($article['arthash']),
                'UnitPriceGross' => number_format(
                    $article['unitprice'] + ($article['unitprice'] / 100 * $article['vat']),
                    '2',
                    '.',
                    ''
                ),
                'TaxRate' => $article['vat'],
            ];
            if (!empty($article['unique_article_number'])) {
                $item['UniqueArticleNumber'] = $article['unique_article_number'];
            }
            if (!empty($article['description_addition'])) {
                $item['DescriptionAddition'] = $article['description_addition'];
            }

            if ($article['title'] == 'Credit') {
                $item['Quantity'] = 1;
            }

            if (!empty($article['bruttoprice'])) {
                $item['UnitPriceGross'] = $article['bruttoprice'];
            }

            $shoppingBasket['Items'][] = ['Item' => $item];
        }

        $oOrder = oxNew(Order::class);
        $oOrder->load($this->_orderId);
        $oCurrency = $oOrder->getOrderCurrency();
        $shoppingBasket['Currency'] = $oCurrency->name;

        return $shoppingBasket;
    }

    /**
     * check if the new api is used
     *
     * @return bool
     * @throws ConnectionException
     */
    private function isNewApi()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('RP_API')
            ->from('pi_ratepay_orders')
            ->where('TRANSACTION_ID = :transactionId')
            ->setParameter(':transactionId', $this->_transactionId);
        $sApi = $oQueryBuilder->execute();
        $sApi = $sApi->fetchOne();

        if (empty($sApi) || $sApi == null) {
            return false;
        }
        return true;
    }

    /**
     * get basket
     *
     * @return array
     */
    private function getBasket()
    {
        $shoppingBasket = [];
        $util = oxNew(Utilities::class);
        $artnr = [];

        foreach ($this->_order->getOrderArticles() as $article) {
            $item = [
                'Description' => $article->oxorderarticles__oxtitle->value,
                'ArticleNumber' => $article->oxorderarticles__oxartnum->value,
                'Quantity' => $article->oxorderarticles__oxamount->value,
                'UnitPriceGross' => $article->oxorderarticles__oxbprice->value,
                'TaxRate' => $article->oxorderarticles__oxvat->value,
                'UniqueArticleNumber' => $article->getId(),
            ];

            $aPersParams = $article->getPersParams();
            if (!empty($article->getPersParams())) {
                if (count($aPersParams) == 1 && isset($aPersParams['details'])) {
                    $sDescriptionAddition = $aPersParams['details'];
                } else {
                    $sDescriptionAddition = '';
                    foreach ($article->getPersParams() as $sKey => $sValue) {
                        $sDescriptionAddition .= $sKey . '=' . $sValue . ';';
                    }
                }
                $item['DescriptionAddition'] = rtrim($sDescriptionAddition, ';');
            }

            $shoppingBasket['Items'][] = ['Item' => $item];
        }

        //wrapping costs
        if (method_exists($this->_basket, 'getWrappingCost') && $this->_basket->getWrappingCost()) {
            $wrappingCosts = $this->_basket->getWrappingCost()->getBruttoPrice();
            $wrappingVat = $this->_basket->getWrappingCost()->getVat();
        } elseif (method_exists($this->_basket, 'getFWrappingCosts') && $this->_basket->getFWrappingCosts()) {
            $wrappingCosts = $this->_basket->getFWrappingCosts();
            if ($this->_basket->getWrappCostNet() > 0) {
                $wrappingVat = $this->_basket->getWrappCostVatPercent();
            } else {
                $wrappingVat = 0;
            }
        } else {
            $wrappingCosts = 0;
        }
        if (!empty($wrappingCosts) && $wrappingCosts > 0) {
            $item = [
                'Description' => 'Wrapping Costs',
                'ArticleNumber' => 'oxwrapping',
                'Quantity' => 1,
                'UnitPriceGross' => $util->getFormattedNumber($wrappingCosts, '2', '.'),
                'TaxRate' => $util->getFormattedNumber(ceil($wrappingVat), '2', '.'),
            ];

            $shoppingBasket['Items'][] = ['Item' => $item];
        }

        //giftcard costs
        if (method_exists($this->_basket, 'getGiftCardCost') && $this->_basket->getGiftCardCost()) {
            $giftcardCosts = $this->_basket->getGiftCardCost()->getPrice();
            $giftcardVat = $this->_basket->getGiftCardCost()->getVat();
        } elseif (method_exists($this->_basket, 'getFGiftCardCosts') && $this->_basket->getFGiftCardCosts()) {
            $giftcardCosts = $this->_basket->getFGiftCardCosts();
            if ($this->_basket->getGiftCardCostNet() > 0) {
                $giftcardVat = $this->_basket->getGiftCardCostVatPercent();
            } else {
                $giftcardVat = 0;
            }
        } else {
            $giftcardCosts = 0;
        }
        if (!empty($giftcardCosts) && $giftcardCosts > 0) {
            $item = [
                'Description' => 'Giftcard Costs',
                'ArticleNumber' => 'oxgiftcard',
                'Quantity' => 1,
                'UnitPriceGross' => $util->getFormattedNumber($giftcardCosts, '2', '.'),
                'TaxRate' => $util->getFormattedNumber(ceil($giftcardVat), '2', '.'),
            ];

            $shoppingBasket['Items'][] = ['Item' => $item];
        }

        //payment costs
        if (method_exists($this->_basket, 'getPaymentCost') && $this->_basket->getPaymentCost()) {
            $paymentCosts = $this->_basket->getPaymentCost()->getPrice();
            $paymentVat = $this->_basket->getPaymentCost()->getVat();
        } elseif (method_exists($this->_basket, 'getPaymentCosts') && $this->_basket->getPaymentCosts()) {
            $paymentCosts = $this->_basket->getPaymentCosts();
            if ($this->_basket->getPayCostNet() > 0) {
                $paymentVat = $this->_basket->getPayCostVatPercent();
            } else {
                $paymentVat = 0;
            }
        } else {
            $paymentCosts = 0;
        }

        if (!empty($paymentCosts) && $paymentCosts > 0) {
            $item = [
                'Description' => 'Payment Costs',
                'ArticleNumber' => 'oxpayment',
                'Quantity' => 1,
                'UnitPriceGross' => $util->getFormattedNumber($paymentCosts, '2', '.'),
                'TaxRate' => $util->getFormattedNumber(ceil($paymentVat), '2', '.'),
            ];

            $shoppingBasket['Items'][] = ['Item' => $item];
        }

        //trusted protection
        if (method_exists(
                $this->_basket,
                'getTrustedShopProtectionCost'
            ) && $this->_basket->getTrustedShopProtectionCost()) {
            $tsProtectionCosts = $this->_basket->getTrustedShopProtectionCost()->getBruttoPrice();
            $tsProtectionVat = $this->_basket->getTrustedShopProtectionCost()->getVat();
        } elseif (method_exists($this->_basket, 'getTsProtectionCosts') && $this->_basket->getTsProtectionCosts()) {
            $tsProtectionCosts = $this->_basket->getTsProtectionCosts();
            if ($this->_basket->getTsProtectionNet() > 0) {
                $tsProtectionVat = $this->_basket->getTsProtectionVatPercent();
            } else {
                $tsProtectionNettoPrice = $tsProtectionCosts;
                $tsProtectionVat = 0;
            }
        } else {
            $tsProtectionCosts = 0;
        }

        if (!empty($tsProtectionCosts) && $tsProtectionCosts > 0) {
            $item = [
                'Description' => 'TS Protection Cost',
                'ArticleNumber' => 'oxtsprotection',
                'Quantity' => 1,
                'UnitPriceGross' => $util->getFormattedNumber($tsProtectionCosts, '2', '.'),
                'TaxRate' => $util->getFormattedNumber(ceil($tsProtectionVat), '2', '.'),
            ];

            $shoppingBasket['Items'][] = ['Item' => $item];
        }

        // OX-18
        // Take currency in account
        $currency = $this->_basket->getBasketCurrency();
        if ($currency) {
            $shoppingBasket['Currency'] = $currency->name;
        }

        return $shoppingBasket;
    }

    /**
     * get country code
     *
     * @param $countryId
     * @return false|string
     */
    private function getCountryCodeById($sCountryId)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $sCountryId);
        $sOxIsOAlpha2 = $oQueryBuilder->execute();
        $sOxIsOAlpha2 = $sOxIsOAlpha2->fetchOne();
        return $sOxIsOAlpha2;
    }

    /**
     * @return string
     */
    protected function getOrderNumber()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);

        if (empty($this->_orderNumber)) {
            if (empty($this->_orderId)) {
                return '';
            }

            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->select('OXORDERNR')
                ->from('oxorder')
                ->where('OXID = :oxid')
                ->setParameter(':oxid', $this->_orderId);
            $sOrderNr = $oQueryBuilder->execute();
            $sOrderNr = $sOrderNr->fetchOne();

            if ($sOrderNr) {
                $this->_orderNumber = $sOrderNr;
            } else {
                $this->_orderNumber = '';
            }
        }

        return $this->_orderNumber;
    }
}
