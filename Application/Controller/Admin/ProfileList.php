<?php

namespace pi\ratepay\Application\Controller\Admin;

use pi\ratepay\Application\Model\Settings;
use OxidEsales\Eshop\Core\Registry;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class ProfileList extends AdminListBase
{
    /**
     * Current class template name.
     * @var string
     */
    protected $_sThisTemplate = '@ratepay/pi_ratepay_profile_list';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sListClass = Settings::class;

    /**
     * Sets default list sorting field and executes parent method parent::Init().
     *
     * @return null
     */
    public function init() {
        $this->_sDefSort = "COUNTRY";
        $sSortCol = Registry::getRequest()->getRequestEscapedParameter('sort');

        if (!$sSortCol || $sSortCol == $this->_sDefSort) {
            $this->_blDesc = false;
        }

        parent::init();
    }
}
