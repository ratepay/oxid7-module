<?php

namespace pi\ratepay\Installment\Php;

use OxidEsales\Eshop\Core\Registry;
use pi\ratepay\Application\Model\Settings;

/**
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package pi_ratepay_rate_calculator
 * Code by PayIntelligent GmbH  <http://www.payintelligent.de/>
 */

if (!function_exists('getShopBasePath')) {

    function getShopBasePath()
    {
        return dirname(__FILE__) . '/../../../../../../source/';
    }

}

/*if (!function_exists('isAdmin')) {

    function isAdmin()
    {
        return false;
    }

}*/

// get bootstrap since 4.7
require_once 'PiRatepayRateCalcDataInterface.php';

require_once getShopBasePath() . 'bootstrap.php';


    /**
 * {@inheritdoc}
 *
 * Concrete implementation for OXID
 */
class PiRatepayRateCalcData implements PiRatepayRateCalcDataInterface
{
    protected $paymentMethod = 'pi_ratepay_rate';

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getProfileId()
    {

        $settings = $this->getSettings();

        $profileId = $settings->pi_ratepay_settings__profile_id->rawValue;
        return $profileId;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getSecurityCode()
    {
        $settings = $this->getSettings();

        $securityCode = $settings->pi_ratepay_settings__security_code->rawValue;
        return $securityCode;
    }

    /**
     * {@inheritdoc}
     * @return boolean
     */
    public function isLive()
    {
        $settings = $this->getSettings();

        $sandbox = $settings->pi_ratepay_settings__sandbox->rawValue;
        if ($sandbox == 1) {
            $live = false;
        } else {
            $live = true;
        }
        return $live;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getSecurityCodeHashed()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getTransactionId()
    {
        return Registry::getSession()->getVariable('pi_ratepay_rate_trans_id');
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getTransactionShortId()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getOrderId()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getMerchantConsumerId()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getMerchantConsumerClassification()
    {
        return '';
    }

    public function getBankOwner() {
        $owner = Registry::getSession()->getVariable('bankOwner');

        return $owner;
    }

    public function getCompanyName() {
        $companyName = Registry::getSession()->getVariable('companyName');

        return $companyName;
    }

    /**
     * {@inheritdoc}
     * @return type
     */
    public function getAmount()
    {
        $basket = Registry::getSession()->getVariable('basketAmount');

        return $basket;
    }

    /**
     * {@inheritdoc}
     *
     * Return DE for German Calculator. Everything else will be English.
     * @return string
     */
    public function getLanguage()
    {
        $oxLangInstance = Registry::getLang();
        $languageAbbervation = strtoupper($oxLangInstance->getLanguageAbbr($oxLangInstance->getBaseLanguage()));
        if ($languageAbbervation == 'DEU' || $languageAbbervation == 'AUT')
            return 'DE';
        return $languageAbbervation;
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getInterestRate()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @param string $total_amount
     * @param string $amount
     * @param string $interest_amount
     * @param string $service_charge
     * @param string $annual_percentage_rate
     * @param string $monthly_debit_interest
     * @param string $number_of_rates
     * @param string $rate
     * @param string $last_rate
     */
    public function setData(
        $total_amount,
        $amount,
        $interest_rate,
        $interest_amount,
        $service_charge,
        $annual_percentage_rate,
        $monthly_debit_interest,
        $number_of_rates,
        $rate,
        $last_rate,
        $payment_firstday,
        $bank_iban
    )
    {
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_total_amount', $total_amount
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_amount', $amount
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_interest_rate', $interest_rate
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_interest_amount', $interest_amount
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_service_charge', $service_charge
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_annual_percentage_rate', $annual_percentage_rate
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_monthly_debit_interest', $monthly_debit_interest
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_number_of_rates', $number_of_rates
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_rate', $rate
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_last_rate', $last_rate
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_payment_firstday', $payment_firstday
        );
        Registry::getSession()->setVariable(
            $this->paymentMethod . '_bank_iban', $bank_iban
        );
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getData()
    {
        $array = array(
            'total_amount'           => Registry::getSession()->getVariable($this->paymentMethod . '_total_amount'),
            'amount'                 => Registry::getSession()->getVariable($this->paymentMethod . '_amount'),
            'interest_rate'          => Registry::getSession()->getVariable($this->paymentMethod . '_interest_rate'),
            'interest_amount'        => Registry::getSession()->getVariable($this->paymentMethod . '_interest_amount'),
            'service_charge'         => Registry::getSession()->getVariable($this->paymentMethod . '_service_charge'),
            'annual_percentage_rate' => Registry::getSession()->getVariable($this->paymentMethod . '_annual_percentage_rate'),
            'monthly_debit_interest' => Registry::getSession()->getVariable($this->paymentMethod . '_monthly_debit_interest'),
            'number_of_rates'        => Registry::getSession()->getVariable($this->paymentMethod . '_number_of_rates'),
            'rate'                   => Registry::getSession()->getVariable($this->paymentMethod . '_rate'),
            'last_rate'              => Registry::getSession()->getVariable($this->paymentMethod . '_last_rate'),
            'payment_firstday'       => Registry::getSession()->getVariable($this->paymentMethod . '_payment_firstday'),
            'bank_iban'              => Registry::getSession()->getVariable($this->paymentMethod . '_bank_iban')
        );
        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetData()
    {
        Registry::getSession()->deleteVariable($this->paymentMethod . '_total_amount');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_amount');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_interest_rate');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_interest_amount');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_service_charge');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_annual_percentage_rate');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_monthly_debit_interest');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_number_of_rates');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_rate');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_last_rate');
        Registry::getSession()->deleteVariable($this->paymentMethod . '_payment_firstday');
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getPaymentFirstdayConfig()
    {
        $settings = $this->getSettings();
        return $settings->pi_ratepay_settings__payment_firstday->rawValue;
    }

    /**
     * {@inheritdoc}
     * @param string $var
     * @return string
     */
    public function getGetParameter($var)
    {
        if (!is_null($_GET)) {
            return array_key_exists($var, $_GET)? $_GET[$var] : '';
        } else {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     * @param string $var
     * @return string
     */
    public function getPostParameter($var)
    {
        if (!is_null($_POST)) {
            return array_key_exists($var, $_POST)? $_POST[$var] : '';
        } else {
            return '';
        }
    }

    /**
     * Get installment settings
     * @return Settings
     */
    public function getSettings()
    {
        $type = 'installment';
        if ($this->paymentMethod == 'pi_ratepay_rate0') {
            $type = 'installment0';
        }
        $settings = oxNew(Settings::class);
        $settings->loadByType(strtolower($type), Registry::getSession()->getVariable('shopId'));

        return $settings;
    }

    /**
     * @param string $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

}
