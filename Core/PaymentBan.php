<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Model class for pi_ratepay_payment_ban table
 * @extends BaseModel
 */
class PaymentBan extends BaseModel
{

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = PaymentBan::class;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('pi_ratepay_payment_ban');
    }

    /**
     * Load data by key userid_paymentMethod, not oxid
     *
     * @param string $userid
     * @param string $paymentMethod
     * @return bool
     */
    public function loadByUserAndMethod($userid, $paymentMethod)
    {
        //getting at least one field before lazy loading the object
        $this->addField('oxid', 0);
        $selectQuery = $this->buildSelectString(
            [$this->getViewName() . ".USERID" => $userid, $this->getViewName() . ".PAYMENT_METHOD" => $paymentMethod]
        );

        return $this->_isLoaded = $this->assignRecord($selectQuery);
    }

}
