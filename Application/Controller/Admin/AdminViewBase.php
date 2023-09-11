<?php

namespace pi\ratepay\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class AdminViewBase extends AdminController
{
    /**
     * Returns oxid
     *
     * @param void
     * @return string
     */
    protected function piGetOxid()
    {
        $sOxid = Registry::getRequest()->getRequestEscapedParameter("oxid");
        return $sOxid;
    }

    /**
     * Returns former saved id
     *
     * @param void
     * @return string
     */
    protected function piGetSavedId()
    {
        $sSavedID = Registry::getRequest()->getRequestEscapedParameter("saved_oxid");

        return $sSavedID;
    }

    /**
     * Delete former saved id from session
     *
     * @param void
     * @return void
     */
    protected function piDeleteSavedId()
    {
        $oSession = Registry::getSession();
        $oSession->deleteVariable("saved_oxid");
    }

    /**
     * Check if checkbox has been set to on for given parameter.
     *
     * @param string $parameter
     * @return int 0 for false and 1 for true
     */
    protected function isParameterCheckedOn($parameter)
    {
        $checked = 0;

        if ($parameter != null && $parameter == 'on') {
            $checked = 1;
        }

        return $checked;
    }

    /**
     * Check if checkbox has been set to on for given parameter.
     *
     * @param string $parameter
     * @return int 0 for false and 1 for true
     */
    protected function isParameterCheckedYes($parameter)
    {
        $checked = 0;
        if ($parameter != null && $parameter == 'yes') {
            $checked = 1;
        }
        return $checked;
    }

}
