<?php

namespace pi\ratepay\Extend\Application\Model;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Counter;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class RatepayOxorder extends RatepayOxorder_parent
{
    /**
     * OX-19: Fix empty ordernr during Ratepay payment
     *
     * @param Basket $oBasket   basket object
     * @param UserPayment $oUserpayment   user payment object
     *
     * @return  integer 2 or an error code
     */
    protected function executePayment(Basket $oBasket, $oUserpayment)
    {
        if ($oUserpayment->getFieldData('oxpaymentsid')  == "pi_ratepay_rate"
            || $oUserpayment->getFieldData('oxpaymentsid')  == "pi_ratepay_rate0"
            || $oUserpayment->getFieldData('oxpaymentsid') == "pi_ratepay_rechnung"
            || $oUserpayment->getFieldData('oxpaymentsid') == "pi_ratepay_elv"
        ) {
            if (!$this->getFieldData('oxordernr')) {
                $this->setNumber();
            } else {
                oxNew(Counter::class)->update($this->getCounterIdent(), $this->getFieldData('oxordernr'));
            }
        }

        return parent::executePayment($oBasket, $oUserpayment);
    }
}
