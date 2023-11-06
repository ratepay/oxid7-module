<?php

namespace pi\ratepay\Extend\Application\Controller;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\PayPalModule\Model\Address;
use pi\ratepay\Application\Model\Settings;
use pi\ratepay\Core\PaymentBan;
use pi\ratepay\Core\Utilities;
use pi\ratepay\Extend\Application\Model\RatepayOxorder;

/**
 * {@inheritdoc}
 *
 * Additionaly checks if RatePAY constraints are met. And initiales RatePAY
 * specific template variables.
 *
 * @package   PayIntelligent_RatePAY
 * @extends Payment
 */
class RatepayPayment extends RatepayPayment_parent
{

    /**
     * Stores if the user is the first time on the payment view.
     * @var boolean
     */
    private $_firstTime = true;

    /**
     * Stores which payment method was selected by the user
     * @var string
     */
    private $_selectedPaymentMethod;

    /**
     * Stores which payment method was selected by the user
     * @var string
     */
    private $_country;

    /**
     * Validation Errors
     * @var array
     */
    private $_errors = [];


    private $_bankdata = null;

    /**
     * {@inheritdoc}
     *
     * Additionaly checks if RatePAY constraints are met, removes RatePAY
     * payment methods if check fails.
     * Also executes init of RatePAY specific template variables.
     *
     * @return array
     * @see Payment::getPaymentList()
     */
    public function getPaymentList()
    {
        $paymentList = $this->modifyPaymentList(parent::getPaymentList());
        $this->initRatepayTemplateVariables();
        return $paymentList;
    }

    /**
     * Set the current country set by customer.
     */
    public function setCountry()
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
        $sCountry = $oQueryBuilder->execute();
        $this->_country = $sCountry->fetchOne();
    }

    /**
     * Get the current country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->_country;
    }

    /**
     * OX-33 : select correct userid to check for payment ban
     *
     * If registered, using OXID
     * If guest, using email (username)
     *
     * @return string
     */
    private function getBanUserId()
    {
        if (is_null($this->getUser()->oxuser__oxregister->value) || $this->getUser(
            )->oxuser__oxregister->value == '0000-00-00 00:00:00') {
            return $this->getUser()->oxuser__oxusername->value;
        }

        return $this->getUser()->oxuser__oxid->value;
    }

    /**
     * Check if RatePAY payment methodes are set in the $paymentList.
     * Checks if RatePAY payment requirements are meet,
     * if not unsets the RatePAY payment type from $paymentList.
     *
     * @param $paymentList
     * @return array
     */
    private function modifyPaymentList($paymentList)
    {
        $this->setCountry();
        $ratePayAllowed = $this->checkRatePAY();
        $userId = $this->getBanUserId();

        foreach (Utilities::$_RATEPAY_PAYMENT_METHOD as $paymentMethod) {
            if (array_key_exists($paymentMethod, $paymentList)) {
                $ratePAYMethodCheck = $this->checkRatePAYMethodCheck($paymentMethod, $userId);
                if (!$ratePayAllowed || !$ratePAYMethodCheck) {
                    unset($paymentList[$paymentMethod]);
                }
            }
        }

        return $paymentList;
    }

    private function checkRatePAYMethodCheck($paymentMethod, $userId)
    {
        return $this->checkCurrency($paymentMethod) && $this->checkActivation($paymentMethod) && $this->checkLimit(
                $paymentMethod
            ) && $this->checkALA($paymentMethod) && $this->checkB2B($paymentMethod) && $this->checkPaymentBan(
                $paymentMethod,
                $userId
            );
    }

    /**
     * Checks if the limits are observed.
     *
     * @return boolean
     */
    private function checkLimit($paymentMethod)
    {
        $settings = $this->getRatePaySettings($paymentMethod);
        $limitMin = (int)$settings->pi_ratepay_settings__limit_min->rawValue;
        $limitMax = (int)$settings->pi_ratepay_settings__limit_max->rawValue;
        $limitMaxB2B = (int)$settings->pi_ratepay_settings__limit_max_b2b->rawValue;
        $basketAmount = Registry::getSession()->getBasket()->getPrice()->getNettoPrice();
        return ($basketAmount >= $limitMin && ($basketAmount <= $limitMax || $basketAmount <= $limitMaxB2B));
    }

    /**
     * Checks if b2b is used and allowed.
     *
     * @return boolean
     */
    private function checkB2B($paymentMethod)
    {
        $settings = $this->getRatePaySettings($paymentMethod);
        $b2b = (bool)$settings->pi_ratepay_settings__b2b->rawValue;
        $company = (!empty($this->getUser()->oxuser__oxcompany->value));
        return (!$company || $b2b);
    }

    /**
     * Checks if differing delivery address is used and allowed.
     *
     * @return boolean
     */
    private function checkALA($paymentMethod)
    {
        $settings = $this->getRatePaySettings($paymentMethod);
        $ala = (bool)$settings->pi_ratepay_settings__ala->rawValue;
        $checkAddress = $this->checkAddress();
        $checkAddressCountry = $this->checkAddressCountry();
        return ($checkAddressCountry && ($ala || $checkAddress));
    }

    /**
     * Checks if the current payment method is activated.
     *
     * @return boolean
     */
    private function checkActivation($paymentMethod)
    {
        $userCountry = $this->getCountry(); //"SELECT OXISOALPHA2 FROM oxcountry WHERE OXID = '" . $this->getUser()->oxuser__oxcountryid->value . "'");
        $settings = $this->getRatePaySettings($paymentMethod, strtolower($userCountry));
        return (bool)$settings->pi_ratepay_settings__active->rawValue;
    }

    /**
     * OX-33 : Check if the method for the user is under active ban
     *
     * @param string $paymentMethod
     * @param string $userId
     * @return bool True if no ban (valid), false if the method should be hidden
     */
    private function checkPaymentBan($paymentMethod, $userId)
    {
        /** @var PaymentBan $paymentBan */
        $paymentBan = oxNew(PaymentBan::class);
        $existingEntry = $paymentBan->loadByUserAndMethod($userId, $paymentMethod);
        if (!$existingEntry) {
            return true;
        }
        $fromDate = oxNew(\DateTimeImmutable::class, $paymentBan->pi_ratepay_payment_ban__from_date->rawValue);
        $toDate = oxNew(\DateTimeImmutable::class, $paymentBan->pi_ratepay_payment_ban__to_date->rawValue);
        $today = oxNew(\DateTime::class);
        if (
            $today->getTimestamp() >= $fromDate->getTimestamp()
            && $today->getTimestamp() < $toDate->getTimestamp()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Initialises smarty variables specific to RatePAY payment.
     *
     */
    private function initRatepayTemplateVariables()
    {
        $basket = Registry::getSession()->getBasket();
        $basketAmount = $basket->getPrice()->getBruttoPrice();
        $session = oxNew(Session::class);
        $session->setVariable('basketAmount', $basketAmount);

        $settings = oxNew(Settings::class);
        $shopId = Registry::getConfig()->getShopId();
        $shopId = $settings->setShopIdToOne($shopId);
        $session->setVariable('shopId', $shopId);
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);

        foreach (Utilities::$_RATEPAY_PAYMENT_METHOD as $paymentMethod) {
            if ($this->_firstTime) {
                $settings->loadByType(Utilities::getPaymentMethod($paymentMethod), $shopId);

                $customer = $this->getUser();
                $oQueryBuilder = $oQueryBuilderFactory->create();
                $oQueryBuilder
                    ->select('OXISOALPHA2')
                    ->from('oxcountry')
                    ->where('OXID = :oxid')
                    ->setParameter(':oxid', $customer->oxuser__oxcountryid->value);
                $sCountry = $oQueryBuilder->execute();
                $sCountry = strtolower($sCountry->fetchOne());

                $this->addTplParam($paymentMethod . '_country', $sCountry);

                if (empty($customer->oxuser__oxfon->value)
                    && empty($customer->oxuser__oxprivfon->value)
                    && empty($customer->oxuser__oxmobfon->value)
                ) {
                    $this->addTplParam($paymentMethod . '_fon_check', 'true');
                }

                if ($customer->oxuser__oxbirthdate->value == "0000-00-00") {
                    $this->addTplParam($paymentMethod . '_birthdate_check', 'true');
                }

                if (empty($customer->oxuser__oxcompany->value) xor empty($customer->oxuser__oxustid->value)) {
                    if (empty($customer->oxuser__oxcompany->value)) {
                        $this->addTplParam($paymentMethod . '_company_check', 'true');
                    } else {
                        if (empty($customer->oxuser__oxustid->value)) {
                            $this->addTplParam($paymentMethod . '_ust_check', 'true');
                        }
                    }
                }

                $session->setVariable(
                    'bankOwner',
                    $customer->oxuser__oxfname->rawValue . " " . $customer->oxuser__oxlname->rawValue
                );
                $session->setVariable('companyName', $customer->oxuser__oxcompany->rawValue);

                $this->addTplParam(
                    $paymentMethod . '_minimumAmount',
                    $settings->pi_ratepay_settings__limit_min->rawValue
                );
                $this->addTplParam(
                    $paymentMethod . '_maximumAmount',
                    $settings->pi_ratepay_settings__limit_max->rawValue
                );
                $this->addTplParam($paymentMethod . '_duedays', $settings->pi_ratepay_settings__duedate->rawValue);
                $this->addTplParam(
                    $paymentMethod . '_iban_only',
                    (bool)$settings->pi_ratepay_settings__iban_only->rawValue
                );
                $this->addTplParam($paymentMethod . '_url', $settings->pi_ratepay_settings__url->rawValue);

                $this->addTplParam(
                    $paymentMethod . '_sandbox_notification',
                    (bool)$settings->pi_ratepay_settings__sandbox->rawValue
                );

                if ($paymentMethod === 'pi_ratepay_elv') {
                    $this->addTplParam(
                        'pi_ratepay_elv_bank_account_owner',
                        $customer->oxuser__oxfname->rawValue . " " . $customer->oxuser__oxlname->rawValue
                    );
                    $this->addTplParam('pi_ratepay_elv_company_name', $customer->oxuser__oxcompany->value);
                }

                $this->setDeviceFingerPrint();
            }

            // @todo here for compatibility reasons will be removed in the future.
            if (Registry::getSession()->hasVariable($paymentMethod . '_error_id')) {
                if (Registry::getSession()->hasVariable($paymentMethod . '_errors')) {
                    $sessionErrors = Registry::getSession()->getVariable($paymentMethod . '_errors');
                } else {
                    $sessionErrors = [];
                }
                $sessionErrors[] = Registry::getSession()->getVariable($paymentMethod . '_error_id');
                Registry::getSession()->setVariable($paymentMethod . '_errors', $sessionErrors);
                Registry::getSession()->deleteVariable($paymentMethod . '_error_id');
            }

            if (Registry::getSession()->hasVariable($paymentMethod . '_errors')) {
                $this->_sPaymentError = '-600';
                $this->_sPaymentErrorText = 'A RatePAY Error occurred';

                $this->addTplParam('piRatepayErrors', Registry::getSession()->getVariable($paymentMethod . '_errors'));

                Registry::getSession()->deleteVariable($paymentMethod . '_errors');

                $settings = $this->getRatePaySettings($paymentMethod);
            }
            if (Registry::getSession()->hasVariable($paymentMethod . '_message')) {
                $this->addTplParam(
                    'customer_message',
                    Registry::getSession()->getVariable($paymentMethod . '_message')
                );
            }
        }

        if ($paymentMethod === 'pi_ratepay_elv') { // || $paymentMethod === 'pi_ratepay_rate'
            $this->setBankdata($paymentMethod);
        }

        $this->_firstTime = false;
    }

    /**
     * Get RatePAY Settings Model for rate or rechnung.
     *
     * @param string $paymentMethod
     * @return Settings
     */
    private function getRatePaySettings($paymentMethod)
    {
        $settings = oxNew(Settings::class);
        $shopId = Registry::getConfig()->getShopId();
        $shopId = $settings->setShopIdToOne($shopId);
        $settings->loadByType(Utilities::getPaymentMethod($paymentMethod), $shopId);

        return $settings;
    }

    /**
     * {@inheritdoc}
     *
     * In Additon:
     * Checks for user data which are required by RatePAY but not by oxid.
     * The data in question are contact details (phone and/or mobile number),
     * the birthdate of the user, and if it's a business or a person tax number.
     * Validates only if all data is set (tax only if it's a business).
     * @return string
     * @see Payment::validatePayment()
     */
    public function validatePayment()
    {
        if (!($paymentId = Registry::getRequest()->getRequestEscapedParameter('paymentid'))) {
            $paymentId = oxNew(Session::class)->getVariable('paymentid');
        }

        $this->_selectedPaymentMethod = $paymentId;
        $this->setCountry();

        $nextStep = parent::validatePayment();

        if ($nextStep == 'order' && in_array($paymentId, Utilities::$_RATEPAY_PAYMENT_METHOD)) {
            $isValid = [
                $this->checkFon(),
                $this->checkBirthdate(),
                $this->checkCompanyData(),
                $this->checkBankData(),
                $this->checkPrivacy(),
                $this->checkZip(),
                $this->checkAlaZip()
            ];

            foreach ($isValid as $validationValue) {
                if (!$validationValue) {
                    Registry::getSession()->setVariable($paymentId . '_errors', array_unique($this->_errors));
                    Registry::getUtils()->redirect(
                        Registry::getConfig()->getSslShopUrl() . 'index.php?cl=Payment',
                        false
                    );
                }
            }
        }

        return $nextStep;
    }

    /**
     * Checks if user aggreed
     * @return bool
     */
    private function checkPrivacy()
    {
        if ($this->_selectedPaymentMethod != "pi_ratepay_elv") {
            return true;
        }

        $privacyParameter = Registry::getRequest()->getRequestEscapedParameter(
            $this->_selectedPaymentMethod . '_privacy'
        );
        $isPrivacyChecked = isset($privacyParameter) && $privacyParameter === '1';

        if (!$isPrivacyChecked) {
            $this->_errors[] = '-461';
        }

        return $isPrivacyChecked;
    }

    /**
     *
     * @return boolean
     */
    private function checkCompany()
    {
        $user = $this->getUser();

        if (!empty($user->oxuser__oxcompany->value) || !empty($user->oxuser__oxustid->value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    private function checkCompanyData()
    {
        $user = $this->getUser();

        $companySet = !empty($user->oxuser__oxcompany->value) && !empty($user->oxuser__oxustid->value);
        $companyNotSet = empty($user->oxuser__oxcompany->value) && empty($user->oxuser__oxustid->value);

        if ($companySet || $companyNotSet) {
            return true;
        }

        $isDataChanged = false;
        $company = Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_company');
        if (!empty($company)) {
            $user->oxuser__oxcompany->value = $company;
            $isDataChanged = true;
        }

        $ustId = Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_ust');
        if (!empty($ustId)) {
            $user->oxuser__oxustid->value = $ustId;
            $isDataChanged = true;
        }

        if ($isDataChanged) {
            $user->save();
            $this->setUser($user);
        }

        if (empty($user->oxuser__oxcompany->value) && !empty($user->oxuser__oxustid->value)) {
            $this->_errors[] = '-416';
            return false;
        }

        // OX-50 VatID is optional
        return true;
    }

    /**
     *
     * @return boolean
     */
    private function checkBirthdate()
    {
        $isBirthdateValid = false;
        $user = $this->getUser();
        $birthdate = $user->oxuser__oxbirthdate->value;

        if (!empty($birthdate) && $birthdate != '0000-00-00') {
            return true;
        }

        $day = Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_birthdate_day');
        $month = Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_birthdate_month');
        $year = Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_birthdate_year');

        if ($this->checkBirthdateValues($day, $month, $year)) {
            $user->oxuser__oxbirthdate->value = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
            $user->save();
            $this->setUser($user);

            if ($this->checkAge()) {
                $isBirthdateValid = true;
            } else {
                switch ($this->_selectedPaymentMethod) {
                    case 'pi_ratepay_rechnung':
                        $this->_errors[] = '-414';
                        break;
                    case 'pi_ratepay_rate':
                        $this->_errors[] = '-415';
                        break;
                    case 'pi_ratepay_rate0':
                        $this->_errors[] = '-415';
                        break;
                    case 'pi_ratepay_elv':
                        $this->_errors[] = '-507';
                        break;
                    default:
                        break;
                }
            }
        }

        return $isBirthdateValid;
    }

    /**
     *
     * @param string $day
     * @param string $month
     * @param string $year
     * @return boolean
     */
    private function checkBirthdateValues($day, $month, $year)
    {
        $areBirthdateValuesValid = false;

        if (is_numeric($day) && is_numeric($month) && is_numeric($year)) {
            if (preg_match('/[0-9]{4}/', (string)$year) > 0) {
                if (checkdate($month, $day, $year)) {
                    $areBirthdateValuesValid = true;
                } else {
                    switch ($this->_selectedPaymentMethod) {
                        case 'pi_ratepay_rechnung':
                            $this->_errors[] = '-401';
                            break;
                        case 'pi_ratepay_rate':
                            $this->_errors[] = '-408';
                            break;
                        case 'pi_ratepay_rate0':
                            $this->_errors[] = '-408';
                            break;
                        case 'pi_ratepay_elv':
                            $this->_errors[] = '-505';
                            break;
                        default:
                            break;
                    }
                }
            } else {
                $this->_errors[] = '-419';
            }
        } else {
            switch ($this->_selectedPaymentMethod) {
                case 'pi_ratepay_rechnung':
                    $this->_errors[] = '-401';
                    break;
                case 'pi_ratepay_rate':
                    $this->_errors[] = '-408';
                    break;
                case 'pi_ratepay_rate0':
                    $this->_errors[] = '-408';
                    break;
                case 'pi_ratepay_elv':
                    $this->_errors[] = '-505';
                    break;
                default:
                    break;
            }
        }

        return $areBirthdateValuesValid;
    }

    /**
     *
     * @return boolean
     */
    private function checkZip()
    {
        $isZipValid = false;
        $user = $this->getUser();
        $country = $this->getCountry();
        if ($country == "DE" && strlen($user->oxuser__oxzip->value) == 5) {
            $isZipValid = true;
        } elseif (($country == 'AT' || $country == 'CH') && strlen($user->oxuser__oxzip) == 4) {
            $isZipValid = true;
        } elseif ($country == 'NL') {
            $isZipValid = true;
        } else {
            switch ($this->_selectedPaymentMethod) {
                case 'pi_ratepay_rechnung':
                    $this->_errors[] = '-406';
                    break;
                case 'pi_ratepay_rate':
                    $this->_errors[] = '-413';
                    break;
                case 'pi_ratepay_rate0':
                    $this->_errors[] = '-413';
                    break;
                case 'pi_ratepay_elv':
                    $this->_errors[] = '-511';
                    break;
                default;
                    break;
            }
        }
        return $isZipValid;
    }

    private function checkAlaZip()
    {
        $isAlaZipValid = true;
        $blShowShippingAddress = (bool)Registry::getSession()->getVariable('blshowshipaddress');
        if ($blShowShippingAddress == true) {
            $oContainer = ContainerFactory::getInstance()->getContainer();
            /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
            $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->select('OXISOALPHA2')
                ->from('oxcountry')
                ->where('OXID = :oxid')
                ->setParameter(':oxid', $this->getDelAddress()->oxaddress__oxcountryid->value);
            $sCountry = $oQueryBuilder->execute();
            $sCountry = $sCountry->fetchOne();

            if ($sCountry == "DE" && strlen($this->getDelAddress()->oxaddress__oxzip->value) == 5) {
            } elseif (($sCountry == 'AT' || $sCountry == 'CH') && strlen($this->getDelAddress()->oxaddress__oxzip) == 4) {
            } elseif ($sCountry == 'NL') {
            } else {
                switch ($this->_selectedPaymentMethod) {
                    case 'pi_ratepay_rechnung':
                        $this->_errors[] = '-406';
                        $isAlaZipValid = false;
                        break;
                    case 'pi_ratepay_rate':
                        $this->_errors[] = '-413';
                        $isAlaZipValid = false;
                        break;
                    case 'pi_ratepay_rate0':
                        $this->_errors[] = '-413';
                        $isAlaZipValid = false;
                        break;
                    case 'pi_ratepay_elv':
                        $this->_errors[] = '-511';
                        $isAlaZipValid = false;
                        break;
                    default;
                        break;
                }
            }
        }
        return $isAlaZipValid;
    }

    /**
     *
     * @return boolean
     */
    private function checkFon()
    {
        $isFonValid = false;
        $user = $this->getUser();
        $fon = $user->oxuser__oxfon->value;
        $mobil = $user->oxuser__oxmobfon->value;
        $phoneNumbers = [$fon, $user->oxuser__oxprivfon->value, $mobil];

        foreach ($phoneNumbers as $phoneNumber) {
            if (!empty($phoneNumber)) {
                $phoneNumber = preg_replace("/\D+/", "", $phoneNumber);
                if (strlen($phoneNumber) >= 6) {
                    return true;
                }
            }
        }

        $phoneNumbers = [
            'fon' => Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_fon'),
            'mobil' => Registry::getRequest()->getRequestEscapedParameter($this->_selectedPaymentMethod . '_mobilfon')
        ];

        $isFonValid = true;
        foreach ($phoneNumbers as $type => $phoneNumber) {
            if (!empty($phoneNumber)) {
                if ($type == 'fon') {
                    $user->oxuser__oxfon = oxNew(Field::class, $phoneNumber);
                }
                if ($type == 'mobil') {
                    $user->oxuser__oxmobfon = oxNew(Field::class, $phoneNumber);
                }
            }
        }

        if ($isFonValid) {
            $user->save();
            $this->setUser($user);
        } else {
            switch ($this->_selectedPaymentMethod) {
                case 'pi_ratepay_rechnung':
                    $this->_errors[] = '-404';
                    break;
                case 'pi_ratepay_rate':
                    $this->_errors[] = '-460';
                    break;
                case 'pi_ratepay_rate0':
                    $this->_errors[] = '-460';
                    break;
                case 'pi_ratepay_elv':
                    $this->_errors[] = '-508';
                    break;
                default:
                    break;
            }
        }

        return $isFonValid;
    }

    private function checkBankData()
    {
        $paymentMethod = $this->_selectedPaymentMethod;
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        if ($paymentMethod != 'pi_ratepay_elv' && $paymentMethod != 'pi_ratepay_rate' && $paymentMethod != 'pi_ratepay_rate0') {
            return true;
        }

        $elvUserCompany = Registry::getRequest()->getRequestEscapedParameter('rp_sepa_use_company_name');
        Registry::getSession()->setVariable('elv_use_company_name', $elvUserCompany);

        if ($paymentMethod == 'pi_ratepay_rate' && $_SESSION['pi_ratepay_rate_payment_firstday'] == 28) {
            return true;
        }
        if ($paymentMethod == 'pi_ratepay_rate0' && $_SESSION['pi_ratepay_rate0_payment_firstday'] == 28) {
            return true;
        }

        $isBankDataValid = true;
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $this->getUser()->oxuser__oxcountryid->value);
        $sUserCountry = $oQueryBuilder->execute();
        $sUserCountry = strtoupper($sUserCountry->fetchOne());

        $bankDataType = Registry::getRequest()->getRequestEscapedParameter($paymentMethod . '_bank_datatype');
        $accountNumber = $this->xTrim(
            Registry::getRequest()->getRequestEscapedParameter($paymentMethod . '_bank_account_number')
        );
        $iban = $this->xTrim(Registry::getRequest()->getRequestEscapedParameter($paymentMethod . '_bank_iban'));
        $bankCode = $this->xTrim(Registry::getRequest()->getRequestEscapedParameter($paymentMethod . '_bank_code'));

        /* bank errors
            account numberKey => -501
            iban              => -501
            bankcode          => -502
            bic               => -510
            bankcode invalid  => -509
        */

        if ($paymentMethod == 'pi_ratepay_rate' && !empty($_SESSION['pi_ratepay_rate_bank_iban'])) {
            $bankDataType = 'iban';
            $iban = $_SESSION['pi_ratepay_rate_bank_iban'];
        }
        if ($paymentMethod == 'pi_ratepay_rate0' && !empty($_SESSION['pi_ratepay_rate0_bank_iban'])) {
            $bankDataType = 'iban';
            $iban = $_SESSION['pi_ratepay_rate0_bank_iban'];
        }

        if ($bankDataType == "classic") {
            if (empty($accountNumber)) {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            } elseif (!is_numeric($accountNumber)) {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            }

            if (empty($bankCode)) {
                $isBankDataValid = false;
                $this->_errors[] = '-502';
            } elseif (!is_numeric($bankCode)) {
                $isBankDataValid = false;
                $this->_errors[] = '-509';
            } elseif (strlen($bankCode) <> 8) {
                $isBankDataValid = false;
                $this->_errors[] = '-509';
            }
        } else {
            $countryPrefix = strtoupper($iban[0] . $iban[1]);
            $numericPart = substr($iban, 2);

            if (empty($iban)) {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            } elseif (!is_numeric($numericPart) && $countryPrefix != 'NL') {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            } elseif ($countryPrefix == "DE" && strlen($iban) <> 22) {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            } elseif ($countryPrefix == "AT" && strlen($iban) <> 20) {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            } elseif ($countryPrefix == "NL" && strlen($iban) <> 18) {
                $isBankDataValid = false;
                $this->_errors[] = '-501';
            }
        }

        if ($isBankDataValid) {
            Registry::getSession()->setVariable($paymentMethod . '_bank_datatype', $bankDataType);
            if ($bankDataType == "classic") {
                Registry::getSession()->setVariable($paymentMethod . '_bank_account_number', $accountNumber);
                Registry::getSession()->setVariable($paymentMethod . '_bank_code', $bankCode);
            } else {
                Registry::getSession()->setVariable($paymentMethod . '_bank_iban', $iban);
            }
        }

        return $isBankDataValid;
    }

    /**
     * Checks if RatePAY constraints are met.
     *
     * @return boolean
     */
    private function checkRatePAY()
    {
        return !$this->checkDenied() && $this->checkAge();
    }

    /**
     * Checks if user is >= 18 years old.
     *
     * @return boolean
     */
    private function checkAge()
    {
        $dob = $this->getUser()->oxuser__oxbirthdate->value;

        // check age if birthdate is set
        if ($dob != "0000-00-00") {
            $geb = strval($dob);
            $gebtag = explode("-", $geb);

            // explode day form time (14 00:00:00)
            $birthDay = explode(" ", $gebtag[2]);

            $stampBirth = mktime(0, 0, 0, $gebtag[1], $birthDay[0], $gebtag[0]);
            $result['stampBirth'] = $stampBirth;

            // fetch the current date (minus 18 years)
            $today['day'] = date('d');
            $today['month'] = date('m');
            $today['year'] = date('Y') - 18;

            // generates current day timestamp - 18 years
            $stampToday = mktime(0, 0, 0, $today['month'], $today['day'], $today['year']);
            $result['$stampToday'] = $stampToday;

            return $stampBirth <= $stampToday;
        }

        // still return true if birthdate is not set, this case is checked in validatePayment
        return true;
    }

    /**
     * Checks if 'pi_ratepay_denied' session variable is set to 'denied'. This variable gets set in order execute.
     * Which means if a order request is denied by RatePAY no other PAYMENT_INIT should be executed for the lifetime
     * of the session.
     *
     * @return boolean
     */
    private function checkDenied()
    {
        $session = Registry::getSession();
        return $session->hasVariable('pi_ratepay_denied') && $session->getVariable('pi_ratepay_denied') == 'denied';
    }

    /**
     * Checks if currency is set to euro. No other currencies are allowed.
     *
     * @return boolean
     */
    private function checkCurrency($paymentMethod)
    {
        $settings = $this->getRatePaySettings($paymentMethod);
        return strstr($settings->pi_ratepay_settings__currencies->rawValue, $this->getActCurrency()->name);
    }

    /**
     * Checks if delivery address is the same as invoice address.
     *
     * @return boolean
     */
    private function checkAddress()
    {
        $oUser = $this->getUser();
        $oDelAddress = $this->getDelAddress();

        if ($oDelAddress != "") {
            if ($oUser->oxuser__oxfname->value != $oDelAddress->oxaddress__oxfname->rawValue) {
                return false;
            }
            if ($oUser->oxuser__oxlname->value != $oDelAddress->oxaddress__oxlname->rawValue) {
                return false;
            }
            if ($oUser->oxuser__oxstreet->value != $oDelAddress->oxaddress__oxstreet->rawValue) {
                return false;
            }
            if ($oUser->oxuser__oxstreetnr->value != $oDelAddress->oxaddress__oxstreetnr->rawValue) {
                return false;
            }
            if ($oUser->oxuser__oxzip->value != $oDelAddress->oxaddress__oxzip->rawValue) {
                return false;
            }
            if ($oUser->oxuser__oxcity->value != $oDelAddress->oxaddress__oxcity->rawValue) {
                return false;
            }
            if ($oUser->oxuser__oxcountryid->value != $oDelAddress->oxaddress__oxcountryid->value) {
                return false;
            }
            if ($oUser->oxuser__oxsal->value != $oDelAddress->oxaddress__oxsal->rawValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if delivery address country is the same as invoice address country.
     *
     * @return boolean
     */
    private function checkAddressCountry()
    {
        $oUser = $this->getUser();
        $oDelAddress = $this->getDelAddress();

        if ($oDelAddress != "") {
            if ($oUser->oxuser__oxcountryid->value != $oDelAddress->oxaddress__oxcountryid->value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Makes the address check available for later processing
     *
     * @return boolean
     */
    public function isShippingAddressSet()
    {
        return $this->checkAddress();
    }

    /**
     * Returns delivery address information from db if $this->_oDelAddress is null.
     *
     * @return \OxidEsales\Eshop\Application\Model\Address
     */
    public function getDelAddress()
    {
        if ($this->_oDelAddress === null) {
            $this->_oDelAddress = false;
            $oOrder = oxNew(RatepayOxorder::class);
            $this->_oDelAddress = $oOrder->getDelAddressInfo();
        }
        return $this->_oDelAddress;
    }

    /**
     * Saves bank data temporarily in the session and permanently in db
     *
     * @param string $paymentMethod
     */
    private function setBankdata($paymentMethod)
    {
        $session = Registry::getSession();
        $bankDatatype = $session->getVariable($paymentMethod . '_bank_datatype');

        /*$encryptService = new Pi_Util_Encryption_OxEncryption();
        if ($encryptService->isBankdataSetForUser($this->getUser()->getId())) {
            $this->_bankdata = $encryptService->loadBankdata($this->getUser()->getId());

            $accountnumber = $this->_bankdata['accountnumber'];
            $bankcode = $this->_bankdata['bankcode'];
        }*/

        if (!empty($bankDatatype)) {
            $this->addTplParam(
                $paymentMethod . '_bank_datatype',
                $session->getVariable($paymentMethod . '_bank_datatype')
            );
            if ($session->getVariable($paymentMethod . '_bank_datatype') == "classic") {
                $this->addTplParam(
                    $paymentMethod . '_bank_account_number',
                    $session->getVariable($paymentMethod . '_bank_account_number')
                );
                $this->addTplParam($paymentMethod . '_bank_code', $session->getVariable($paymentMethod . '_bank_code'));
            } else {
                $this->addTplParam($paymentMethod . '_bank_iban', $session->getVariable($paymentMethod . '_bank_iban'));
            }
        } else {
            $this->addTplParam($paymentMethod . '_bank_datatype', 'sepa');
        }
    }

    /**
     * Creates a device fingerprint token if not exists
     */
    private function setDeviceFingerPrint()
    {
        $DeviceFingerprintToken = Registry::getSession()->getVariable('pi_ratepay_dfp_token');
        $DeviceFingerprintSnippetId = Registry::getConfig()->getConfigParam('sRPDeviceFingerprintSnippetId');
        if (empty($DeviceFingerprintSnippetId)) {
            $DeviceFingerprintSnippetId = 'ratepay'; // default value, so that there is always a device fingerprint
        }

        if (empty($DeviceFingerprintToken)) {
            $timestamp = microtime();
            $sessionId = Registry::getSession()->getId();
            $token = md5($sessionId . "_" . $timestamp);

            Registry::getSession()->setVariable('pi_ratepay_dfp_token', $token);
            $this->addTplParam('pi_ratepay_dfp_token', $token);
            $this->addTplParam('pi_ratepay_dfp_snippet_id', $DeviceFingerprintSnippetId);
        } else {
            $this->addTplParam('pi_ratepay_dfp_token', $DeviceFingerprintToken);
            $this->addTplParam('pi_ratepay_dfp_snippet_id', $DeviceFingerprintSnippetId);
        }
        $blRatepayScriptSend = Registry::getSession()->getVariable('pi_ratepay_script_send');
        if (!empty($blRatepayScriptSend)) {
            $this->addTplParam('pi_ratepay_script_send', $blRatepayScriptSend);
        }

    }

    public function setDiScriptSendAjax() {
        Registry::getSession()->setVariable('pi_ratepay_script_send', 1);
    }

    private function isSaveBankDataSet()
    {
        $elvSettings = $this->getRatePaySettings($this->_selectedPaymentMethod);
        $saveBankData = $elvSettings->pi_ratepay_settings__savebankdata->rawValue;

        return $saveBankData != 0;
    }

    private function isRateElv()
    {
        $rateSettings = $this->getRatePaySettings('pi_ratepay_rate');
        return $this->_selectedPaymentMethod === 'pi_ratepay_rate'
            && Registry::getRequest()->getRequestEscapedParameter(
                'pi_rp_rate_pay_method'
            ) === 'pi_ratepay_rate_radio_elv'
            && $rateSettings->pi_ratepay_settings__activate_elv->rawValue == 1;
    }

    /**
     * Extended trim function
     *
     * @param string $string
     * @return string
     */
    public function xTrim($string)
    {
        $string = trim(strtoupper($string));
        $string = preg_replace('/^IBAN/', '', $string);
        $string = preg_replace('/[^a-zA-Z0-9]/', '', $string);
        $string = strtoupper($string);
        return $string;
    }

}
