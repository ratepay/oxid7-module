<?php

namespace pi\ratepay\Application\Controller\Admin;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use pi\ratepay\Application\Model\Logs;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RatePAY Logging View
 *
 * Shows RatePAY Transaction Logs
 *
 * Also:
 * {@inheritdoc}
 *
 * @package PayIntelligent_RatePAY
 * @extends oxAdminView
 */
class LogMain extends AdminViewBase
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_sThisTemplate = '@ratepay/pi_ratepay_log_main';

    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_sModelClass = Logs::class;

    /**
     * DB Table
     *
     * @var string
     */
    protected $_sTable = 'pi_ratepay_logs';

    /**
     * Handle displaying model entry
     *
     * @param void
     * @return string
     */
    public function render()
    {
        parent::render();
        $sSavedID = $this->piGetSavedId();
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
            $this->_aViewData["oxid"] = $sOxid;
            // for reloading upper frame
            $this->_aViewData["updatelist"] = "1";
        }

        if ($blLoaded) {
            // load object
            $this->piDeleteSavedId();

            $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
            $sViewName = $tableViewNameGenerator->getViewName($this->_sModelClass);

            $oRatePayLogs = oxNew(
                $this->_sModelClass,
                $sViewName
            );
            $oRatePayLogs->load($sOxid);

            $this->_aViewData["edit"] = $oRatePayLogs;
        }

        return $this->_sThisTemplate;
    }

    /**
     * Removes all log entries from db which are older than x days.
     *
     * @param void
     * @return void
     */
    public function deleteLogs()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder->delete($this->_sTable);

        $sLogDays = Registry::getRequest()->getRequestEscapedParameter('logdays');
        $iLogDays = (int)$sLogDays;

        if ($iLogDays > 0) {
            $oQueryBuilder->where("TO_DAYS(now()) - TO_DAYS(date) > " . $iLogDays);
        }

        $oQueryBuilder->execute();
        $this->addTplParam('deleteSuccess', 'Success');
    }
}
