<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Core\Model\ListModel;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generate iterable list of history model objects
 * @extends oxList
 */
class HistoryList extends ListModel
{

    /**
     * Core table name
     *
     * @var string
     */
    protected $_sCoreTable = 'pi_ratepay_history';

    /**
     * List Object class name
     *
     * @var string
     */
    protected $_sObjectsInListName = History::class;

    /**
     * Generic function for loading the list with where clause
     *
     * @param string $where optional: where condition for query
     */
    public function getFilteredList($where = null)
    {
        $oListObject = $this->getBaseObject();
        $sFieldList = $oListObject->getSelectFields();
        $sQ = "select $sFieldList from " . $oListObject->getViewName();

        if ($where != null) {
            $sQ .= " where $where ";
        }
        $this->selectString($sQ);

        return $this;
    }

}
