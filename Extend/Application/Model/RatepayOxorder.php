<?php

namespace pi\ratepay\Extend\Application\Model;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Counter;

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
    protected function _executePayment(Basket $oBasket, $oUserpayment)
    {
        if ($oUserpayment->oxuserpayments__oxpaymentsid->value  == "pi_ratepay_rate"
            || $oUserpayment->oxuserpayments__oxpaymentsid->value  == "pi_ratepay_rate0"
            || $oUserpayment->oxuserpayments__oxpaymentsid->value == "pi_ratepay_rechnung"
            || $oUserpayment->oxuserpayments__oxpaymentsid->value == "pi_ratepay_elv"
        ) {
            if (!$this->oxorder__oxordernr->value) {
                $this->_setNumber();
            } else {
                oxNew(Counter::class)->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
            }
        }

        return parent::_executePayment($oBasket, $oUserpayment);
    }
}
