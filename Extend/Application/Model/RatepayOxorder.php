<?php

namespace pi\ratepay\Extend\Application\Model;

use OxidEsales\Eshop\Core\Counter;

class RatepayOxorder extends RatepayOxorder_parent
{
    /**
     * OX-19: Fix empty ordernr during Ratepay payment
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket      basket object
     * @param object                                     $oUserpayment user payment object
     *
     * @return  integer 2 or an error code
     */
    protected function executePayment(\OxidEsales\Eshop\Application\Model\Basket $oBasket, $oUserpayment)
    {
        if ($oUserpayment->oxuserpayments__oxpaymentsid->value  == "pi_ratepay_rate"
            || $oUserpayment->oxuserpayments__oxpaymentsid->value  == "pi_ratepay_rate0"
            || $oUserpayment->oxuserpayments__oxpaymentsid->value == "pi_ratepay_rechnung"
            || $oUserpayment->oxuserpayments__oxpaymentsid->value == "pi_ratepay_elv"
        ) {
            if (!$this->oxorder__oxordernr->value) {
                $this->setNumber();
            } else {
                oxNew(Counter::class)->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
            }
        }

        return parent::executePayment($oBasket, $oUserpayment);
    }
}
