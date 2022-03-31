<?php

namespace pi\ratepay\Application\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category  PayIntelligent
 * @package   PayIntelligent_RatePAY
 * @copyright (C) 2011 PayIntelligent GmbH  <http://www.payintelligent.de/>
 * @license	http://www.gnu.org/licenses/  GNU General Public License 3
 */

/**
 * RatePAY Rate (installment) manager. Checks if basket is set, if user is set
 * and for RatePAY session variables.
 * @package   PayIntelligent_RatePAY
 * @extends FrontendController
 */
class RateCalc extends FrontendController
{

    /**
     * {@inheritdoc}
     *
     * @see FrontendController::init()
     */
    public function init()
    {
        parent::init();

        $this->_sThisTemplate = 'pi_ratepay_rate_calc.tpl';
    }

    /**
     * {@inheritdoc}
     *
     * Also adds template variable 'piTotalAmount' (brutto price, rounded).
     *
     * @see FrontendController::render()
     * @return string
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
            && (!$oBasket || ( $oBasket && !$oBasket->getProductsCount() ))
        ) {
            Registry::getUtils()->redirect(
                $myConfig->getShopHomeURL() . 'cl=basket'
            );
        }

        $oUser = $this->getUser();
        if (!$oBasket
            || !$oUser
            || ( $oBasket && !$oBasket->getProductsCount() )
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
        $ratepaySessionVariables = array(
            'pi_ratepay_rate_total_amount',
            'pi_ratepay_rate_amount',
            'pi_ratepay_rate_interest_amount',
            'pi_ratepay_rate_service_charge',
            'pi_ratepay_rate_annual_percentage_rate',
            'pi_ratepay_rate_monthly_debit_interest',
            'pi_ratepay_rate_number_of_rates',
            'pi_ratepay_rate_rate',
            'pi_ratepay_rate_last_rate'
        );

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
