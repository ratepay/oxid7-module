<?php

namespace pi\ratepay\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use pi\ratepay\Application\Model\Logs;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class LogList extends AdminListBase
{
    /**
     * Current class template name.
     * @var string
     */
    protected $_sThisTemplate = '@ratepay/pi_ratepay_log_list';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = Logs::class;


    /**
     * Sets default list sorting field and executes parent method parent::Init().
     *
     * @return null
     */
    public function init() {
        $this->_sDefSort = "DATE";
        $sSortCol = Registry::getRequest()->getRequestEscapedParameter('sort');

        if (!$sSortCol || $sSortCol == $this->_sDefSort) {
            $this->_blDesc = false;
        }

        parent::init();
    }
}
