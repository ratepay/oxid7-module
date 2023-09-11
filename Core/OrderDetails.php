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
 * Model class for pi_ratepay_orderdetails table
 * @extends oxBase
 */
class OrderDetails extends BaseModel
{

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = OrderDetails::class;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('pi_ratepay_order_details');
    }

}
