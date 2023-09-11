<?php

namespace pi\ratepay\Application\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RatePAY Rate (installment) manager. Checks if basket is set, if user is set
 * and for RatePAY session variables.
 * @package   PayIntelligent_RatePAY
 * @extends FrontendController
 */
class Rate0Calc extends FrontendController
{

    /**
     * {@inheritdoc}
     *
     * Also adds template variable 'piTotalAmount' (brutto price, rounded).
     *
     * @return string
     * @see FrontendController::render()
     */
    public function render()
    {
        parent::render();

        $this->checkUser();

        $basket = Registry::getSession()->getBasket();
        $this->addTplParam(
            'piTotalAmount',
            number_format($basket->getPrice()->getBruttoPrice(), 2, ",", "")
        );

        return $this->_sThisTemplate;
    }

    /**
     * Checks if basket is set if not redirects to basket. Checks if user and
     * basket are set if not redirects to start page.
     */
    public function checkUser()
    {
        $myConfig = Registry::getConfig();

        if ($myConfig->getConfigParam('blPsBasketReservationEnabled')) {
            Registry::getSession()->getBasketReservations()->renewExpiration();
        }

        $oBasket = Registry::getSession()->getBasket();
        if ($myConfig->getConfigParam('blPsBasketReservationEnabled')
            && (!$oBasket || ($oBasket && !$oBasket->getProductsCount()))
        ) {
            Registry::getUtils()->redirect(
                $myConfig->getShopHomeURL() . 'cl=basket'
            );
        }

        $oUser = $this->getUser();
        if (!$oBasket
            || !$oUser
            || ($oBasket && !$oBasket->getProductsCount())
        ) {
            Registry::getUtils()->redirect(
                $myConfig->getShopHomeURL() . 'cl=start'
            );
        }
    }

    /**
     * Checks if RatePAY Rate (installment) data is stored in session.
     */
    public function check()
    {
        $myConfig = Registry::getConfig();
        $checking = true;

        // test for these variables in session
        $ratepaySessionVariables = [
            'pi_ratepay_rate0_total_amount',
            'pi_ratepay_rate0_amount',
            'pi_ratepay_rate0_interest_amount',
            'pi_ratepay_rate0_service_charge',
            'pi_ratepay_rate0_annual_percentage_rate',
            'pi_ratepay_rate0_monthly_debit_interest',
            'pi_ratepay_rate0_number_of_rates',
            'pi_ratepay_rate0_rate',
            'pi_ratepay_rate0_last_rate'
        ];

        foreach ($ratepaySessionVariables as $sessionVariable) {
            if (!Registry::getSession()->hasVariable($sessionVariable)
                || Registry::getSession()->getVariable($sessionVariable) == ''
            ) {
                $checking = false;
                break;
            }
        }

        if ($checking) {
            Registry::getUtils()->redirect(
                $myConfig->getShopHomeURL() . 'cl=RatepayOrder'
            );
        } else {
            Registry::getUtils()->redirect(
                $myConfig->getShopHomeURL()
                . 'cl=RatepayRateCalc&fnc=calculateError'
            );
        }
    }

    /**
     * [_calculateError description]
     */
    public function calculateError()
    {
        $this->addTplParam('pierror', '-461');
    }

}
