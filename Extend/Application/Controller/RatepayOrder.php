<?php

namespace pi\ratepay\Extend\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * {@inheritdoc}
 *
 * Additionally sends RatePAY PAYMENT_REQUEST and sets RatePAY payment specific informations in db and session.
 *
 * @package PayIntelligent_RatePAY
 * @extends order
 */
class RatepayOrder extends RatepayOrder_parent
{
    /**
     * Loads basket \OxidEsales\Eshop\Core\Session::getBasket(), sets $this->oBasket->blCalcNeeded = true to
     * recalculate, sets back basket to session \OxidEsales\Eshop\Core\Session::setBasket(), executes
     * parent::init().
     */
    public function init()
    {
        parent::init();

        $moduleSettingService = $this->getModuleSettings();
        $DeviceFingerprintToken = Registry::getSession()->getVariable('pi_ratepay_dfp_token');
        $DeviceFingerprintSnippetId = $moduleSettingService->getString('sRPDeviceFingerprintSnippetId', 'ratepay');;
        if (!empty($DeviceFingerprintToken)) {
            if ($DeviceFingerprintSnippetId->length() <= 0) {
                $DeviceFingerprintSnippetId = 'C9rKgOt'; // default value, so that there is always a device fingerprint
            }
            $this->addTplParam('pi_ratepay_dfp_token', $DeviceFingerprintToken);
            $this->addTplParam('pi_ratepay_dfp_snippet_id', $DeviceFingerprintSnippetId);
        }
    }

    /**
     * Check if this is a OXID 4.6.x Shop.
     * @return bool
     */
    public function piIsFourPointSixShop()
    {
        return substr(Registry::getConfig()->getVersion(), 0, 3) === '4.6';
    }

    /**
     * Returns next order step. If ordering was sucessfull - returns string "thankyou" (possible
     * additional parameters), otherwise - returns string "payment" with additional
     * error parameters.
     *
     * @param integer $iSuccess status code
     *
     * @return  string  $sNextStep  partial parameter url for next step
     */
    protected function getNextStep($iSuccess)
    {
        $nextStep = parent::getNextStep($iSuccess);

        /**
         * OX-44 clean session payment data as the order got placed
         */
        if($nextStep == "thankyou"){
            $this->cleanSessionPaymentData();
        }

        return $nextStep;
    }

    /**
     * OX-44 clean ratepay session data
     */
    protected function cleanSessionPaymentData()
    {
        $variablesToClean = [
            'basketAmount',
            'bankOwner',
            'paymentid'
        ];
        $sessionVariables = array_keys($_SESSION);
        $sessionVariables = array_filter($sessionVariables, function($key) {
            return preg_match('/^pi_ratepay.*/', $key) == 1;
        });

        $session = (Registry::getSession());
        $variablesToClean = array_merge($variablesToClean, $sessionVariables);

        foreach ($variablesToClean as $key) {
            if($session->hasVariable($key)) {
                $session->deleteVariable($key);
            }
        }
    }

    /**
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getModuleSettings() {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettingServiceInterface::class);
    }
}

