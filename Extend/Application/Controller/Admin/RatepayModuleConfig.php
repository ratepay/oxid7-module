<?php

namespace pi\ratepay\Extend\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use pi\ratepay\Application\Model\Settings;
use pi\ratepay\Core\ModelFactory;
use pi\ratepay\Core\Utilities;

class RatepayModuleConfig extends RatepayModuleConfig_parent
{

    /**
     * Assignment helper for ratepay payment activity
     * Will be filled in constructor
     *
     * @var array
     */
    protected $_aCountry2Payment2Configs;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_aCountry2Payment2Configs = ModelFactory::getConfigurationParameterMap();
    }

    /**
     * Returns url of country code
     *
     * @param $sCountryCode
     * @return string
     */
    public function piGetFlagUrl($sCountryCode)
    {
        $oConfig = Registry::getConfig();
        $sShopUrl = $oConfig->getShopUrl();

        $sModuleAdminImgFlagsPath =
            "out/modules/ratepay/admin/img/flags/";

        $sFlagUrl =
            $sShopUrl.
            $sModuleAdminImgFlagsPath.
            $sCountryCode.
            ".png";

        return $sFlagUrl;
    }

    /**
     * Method determines this is the config controller of
     * ratepay config page
     *
     * @param void
     * @return bool
     */
    public function piIsRatepayModuleConfig()
    {
        $blIsRatepayModuleConfig =
            ($this->_sModuleId == 'ratepay');

        return $blIsRatepayModuleConfig;
    }

    /**
     * Returns if connection has been successfully established
     *
     * @param $sPaymentType
     * @return bool
     */
    public function piTestConnectionEstablished($sPaymentType, $sCountryCode)
    {
        $blValid = isset(
            $this->_aCountry2Payment2Configs[$sCountryCode][$sPaymentType]
        );
        if (!$blValid) {
            return false;
        }

        $aConfig =
            $this->_aCountry2Payment2Configs[$sCountryCode][$sPaymentType];

        $blConnected = (bool) $this->_piPerformProfileRequest($aConfig);

        return $blConnected;
    }

    /**
     * Overloading savig settings
     */
    public function saveConfVars()
    {
        parent::saveConfVars();
        $blIsRatePay = $this->piIsRatepayModuleConfig();
        if ($blIsRatePay) {
            $this->_piFetchAndSaveRatepayProfiles();
        }
    }

    /**
     * Fetching available ratepay profiles and persist them into database
     *
     * @param void
     * @return void
     */
    protected function _piFetchAndSaveRatepayProfiles()
    {
        $oConfig = Registry::getConfig();

        $aActiveCombinations = $this->_piGetActiveCombinations();

        foreach ($aActiveCombinations as $aActiveCombination) {
            $aConfigParams = $aActiveCombination['configparams'];
            $aResult = $this->_piPerformProfileRequest($aConfigParams);

            if (!$aResult) {
                $blSandbox = $oConfig->getConfigParam($aConfigParams['sandbox']);
                $iEditLanguage = Registry::getRequest()->getRequestEscapedParameter("editlanguage");
                $oUtilsView = Registry::get('oxUtilsView');
                $oLang = Registry::get('oxLang');

                $sTranslationString = 'PI_RATEPAY_PROFILE_ERROR_CREDENTIALS_INVALID_';
                $sTranslationString .= ($blSandbox) ? 'INT' : 'LIVE';
                $sMessage = $oLang->translateString($sTranslationString, $iEditLanguage);

                return $oUtilsView->addErrorToDisplay($sMessage);
            }

            $oSettings = oxNew(Settings::class);
            $oSettings->piUpdateSettings($aActiveCombination, $aResult);
        }

        $this->addTplParam('blSaveSuccess', true);
    }

    /**
     * Performing profile request and returns result
     *
     * @param $aConfigParams
     * @return mixed
     */
    protected function _piPerformProfileRequest($aConfigParams)
    {
        $oConfig = Registry::getConfig();

        $sSecurityCode = $oConfig->getConfigParam($aConfigParams['secret']);
        $sProfileId = $oConfig->getConfigParam($aConfigParams['profileid']);
        $blSandbox = $oConfig->getConfigParam($aConfigParams['sandbox']);
        $blActive = $oConfig->getConfigParam($aConfigParams['active']);

        $blValid = (
            $blActive &&
            !empty($sProfileId) &&
            !empty($sSecurityCode)
        );

        if (!$blValid) return false;
        $modelFactory = oxNew(ModelFactory::class);
        $modelFactory->setSecurityCode($sSecurityCode);
        $modelFactory->setProfileId($sProfileId);
        $modelFactory->setSandbox($blSandbox);

        $aResult = $modelFactory->doOperation('PROFILE_REQUEST');

        return $aResult;
    }


    /**
     * Returns all active combinations of ratepay payments for certain countries
     *
     * @param void
     * @return array
     */
    protected function _piGetActiveCombinations()
    {
        $oConfig = Registry::getConfig();
        $aCountries = Utilities::$_RATEPAY_ALLOWED_COUNTRIES;
        $aMethods = Utilities::$_RATEPAY_PAYMENT_METHOD_NAMES;
        $aActiveCombinations = array();

        foreach ($aCountries as $sCountry) {
            foreach ($aMethods as $sRequestMethod => $sMethod) {
                $blConfigExists =
                    isset($this->_aCountry2Payment2Configs[$sCountry][$sMethod]);
                if (!$blConfigExists) continue;

                $aConfig =
                    $this->_aCountry2Payment2Configs[$sCountry][$sMethod];
                $sActiveConfigParam = $aConfig['active'];
                $blIsActive =
                    $oConfig->getConfigParam($sActiveConfigParam);

                if (!$blIsActive) continue;

                $aActiveCombinations[] = array(
                    'country'       => $sCountry,
                    'method'          => $sMethod,
                    'configparams'  => $aConfig,
                    'requestmethod' => $sRequestMethod,
                );
            }
        }

        return $aActiveCombinations;
    }

    /**
     * Determines which settlement types are available in the connected RatePAY profile
     *
     * @param string $sSettlementTypes
     * @return array
     */
    public function piGetAvailableSettlementTypes($sSettlementTypes)
    {
        $sCountry = 'DE';
        if ($sSettlementTypes == 'sRPAustriaInstallmentSettlement') {
            $sCountry = 'AT';
        }

        $settings = oxNew(Settings::class);
        $shopId = Registry::getConfig()->getShopId();
        $shopId = $settings->setShopIdToOne($shopId);
        $settings->loadByType(Utilities::getPaymentMethod('pi_ratepay_rate'), $shopId, $sCountry);

        return $settings->getAvailableSettlementTypes();
    }
}
