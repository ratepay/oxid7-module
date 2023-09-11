<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Data provider for backend operation.
 * @extends RequestAbstract
 */
class RequestDataBackend extends RequestAbstract
{
    /**
     * Order Object
     * @var Order
     */
    private $_order;

    /**
     * Class constructor
     * @param Order $order
     */
    public function __construct($order)
    {
        $this->_order = $order;
    }

    /**
     * Generate oxuser from order user.
     * @inheritdoc
     * @return User
     */
    public function getUser()
    {
        $ratepayOrder = oxNew(Orders::class);
        $ratepayOrder->loadByOrderNumber($this->_order->getId());
        $orderUser = $this->_order->getOrderUser();
        $orderUser->oxuser__oxbirthdate = clone $ratepayOrder->pi_ratepay_orders__userbirthdate;

        return $orderUser;
    }
}
