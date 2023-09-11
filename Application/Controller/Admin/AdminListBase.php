<?php

namespace pi\ratepay\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminListController;
use OxidEsales\Eshop\Core\ShopVersion;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class AdminListBase extends AdminListController
{
    /**
     * (non-PHPdoc)
     * @see AdminListController::render()
     */
    public function render() {
        parent::render();
        $sVersion = ShopVersion::getVersion();
        $sNameConcat = $this->piGetConcatByVersion();

        $this->_aViewData['shopversion'] = $sVersion;
        $this->_aViewData['nameconcat'] = $sNameConcat;
        $this->_aViewData['where'] = $this->piGetEnteredValues();

        return $this->_sThisTemplate;
    }

    /**
     * returns an array of values that the user entered
     *
     * @return array
     */
    protected function piGetEnteredValues()
    {
        $aReturn = [];
        $aWhere = $this->buildWhere();
        foreach ($aWhere as $sKey => $sValue) {
            $aValues = explode(' ', $sValue);
            $sValue = $aValues[0];
            $aSplittedKey = explode(".", $sKey);
            $sValue = $this->piDecodeUrlSearchTerm($sValue);
            $aReturn[$aSplittedKey[0]][$aSplittedKey[1]] = $sValue;
        }
        return $aReturn;
    }

    /**
     * Prepares where-part to be able to find entries for call-/targeturi
     *
     * @param string $sInput
     * @return string
     */
    protected function piDecodeUrlSearchTerm($sInput)
    {
        $sOutput = substr($sInput, 1,-1);
        $sOutput = urldecode($sOutput);
        $sOutput = urldecode($sOutput);
        $sOutput = str_replace("%", "", $sOutput);

        return $sOutput;
    }

    /**
     * Returns filter concat depending on oxid version
     *
     * @return string
     */
    protected function piGetConcatByVersion()
    {
        $sConcat = "][";
        return $sConcat;
    }

}
