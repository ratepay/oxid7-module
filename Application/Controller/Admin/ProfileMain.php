<?php

namespace pi\ratepay\Application\Controller\Admin;

use OxidEsales\Eshop\Core\TableViewNameGenerator;
use pi\ratepay\Application\Model\Settings;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class ProfileMain extends AdminViewBase
{
    /**
     * Current class template name.
     * @var string
     */
    protected $_sThisTemplate = '@ratepay/pi_ratepay_profile_main';

    /**
     * Name of chosen object class (default null).
     * @var string
     */
    protected $_sModelClass = Settings::class;

    /**
     * DB Table
     * @var string
     */
    protected $_sTable = 'pi_ratepay_settings';


    /**
     * Handle displaying model entry
     *
     * @param void
     * @return string
     */
    public function render() {
        parent::render();
        $sSavedID  = $this->piGetSavedId();
        $sOxid = $this->piGetOxid();

        $blNotLoaded = (
            (
                $sOxid == "-1" ||
                !isset($sOxid)
            ) &&
            isset($sSavedID)
        );
        $blLoaded = (
            $sOxid != "-1" &&
            isset($sOxid)
        );

        if ($blNotLoaded) {
            $sOxid = $sSavedID;
            $this->_aViewData["oxid"] =  $sOxid;
            // for reloading upper frame
            $this->_aViewData["updatelist"] =  "1";
        }

        if ($blLoaded) {
            // load object
            $this->piDeleteSavedId();

            $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
            $sViewName = $tableViewNameGenerator->getViewName($this->_sModelClass);

            $oProfile = oxNew(
                $this->_sModelClass,
                $sViewName
            );
            /** @var Settings $oProfile */
            $oProfile->load($sOxid);

            $this->_aViewData["edit"] =  $oProfile;
        }

        return $this->_sThisTemplate;
    }
}
