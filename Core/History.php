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
 * Model Class for pi_ratepay_History table
 * @extends oxBase
 */
class History extends BaseModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = History::class;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('pi_ratepay_history');
    }
}
