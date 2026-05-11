<?php

namespace pi\ratepay\Application\Model;

use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generate iterable list of pi_ratepay_logs model objects
 * @extends ListModel
 */
class LogsList extends ListModel
{

    /**
     * Core table name
     *
     * @var string
     */
    protected $_sCoreTable = 'pi_ratepay_logs';

    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = Logs::class;

    /**
     * Generic function for loading the list with where clause and order by
     *
     * @param string $where
     * @param array $orderBy example: array(array('column' => 'order_id', 'direction' => 'asc'))
     * @return LogsList
     */
    public function getFilteredList($where = null, $orderBy = null)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oListObject = $this->getBaseObject();
        $sFieldList = $oListObject->getSelectFields();

        $oQueryBuilder->select("$sFieldList")
            ->from($oListObject->getViewName());

        if ($where !== null) {
            $oQueryBuilder->where("$where");
        }

        if ($orderBy !== null) {
            $firstArrayItem = reset($orderBy);
            foreach ($orderBy as $orderByItem) {
                if ($firstArrayItem == $orderByItem) {
                    $oQueryBuilder->orderBy($orderByItem['column'], $orderByItem['direction']);
                } else {
                    $oQueryBuilder->addOrderBy($orderByItem['column'], $orderByItem['direction']);
                }
            }
        }
        $this->selectString($oQueryBuilder->getSQL());
        
        return $this;
    }

}
