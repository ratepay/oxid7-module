<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Registry;
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
 * Helper Class to generate RatePAY order data.
 */
class DetailsViewData extends BaseModel
{
    /**
     * oxid of order
     * @var string
     */
    private $_orderId;

    /**
     * Order values
     * @var array|null 
     */
    protected $_orderValues = null;

    /**
     * Name of order details table
     * @var string
     */
    private $pi_ratepay_order_details = 'pi_ratepay_order_details';

    /**
     * Class constructor
     * @param string $orderId oxid of order
     */
    public function __construct($orderId)
    {
        $this->_orderId = $orderId;
    }

    /**
     * Gets all articles with additional informations
     *
     * @param bool $blIsDisplayList
     * @return array
     */
    public function getPreparedOrderArticles($blIsDisplayList = false)
    {
        $articleList = $this->piGetOrderArticleList();
        $articleList = $this->piAddSpecialCosts($articleList, 'oxwrapping', 'Wrapping Cost');
        $articleList = $this->piAddSpecialCosts($articleList, 'oxgiftcard', 'Giftcard Cost');
        $articleList = $this->piAddSpecialCosts($articleList, 'oxpayment', 'Payment Costs');
        $articleList = $this->piAddSpecialCosts($articleList, 'oxdelivery', 'Delivery Costs');
        $articleList = $this->piAddSpecialCosts($articleList, 'oxtsprotection', 'TS Protection Cost');
        $articleList = $this->piAddDiscounts($articleList);
        $articleList = $this->piAddVouchers($articleList, $blIsDisplayList);
        $articleList = $this->piAddCredit($articleList);

        return $articleList;
    }

    protected function _getDescriptionAddition($sPersParam)
    {
        $sDescriptionAddition = false;
        if (!empty($sPersParam)) {
            $aPersParams = unserialize($sPersParam);
            if (is_array($aPersParams) && !empty($aPersParams)) {
                if (count($aPersParams) == 1 && isset($aPersParams['details'])) {
                    $sDescriptionAddition = $aPersParams['details'];
                } else {
                    $sDescriptionAddition = '';
                    foreach ($aPersParams as $sKey => $sValue) {
                        $sDescriptionAddition .= $sKey.'='.$sValue.';';
                    }
                }
            }
        }
        return $sDescriptionAddition;
    }

    /**
     * Initial method for generating an article list
     *
     * @return array
     */
    protected function piGetOrderArticleList()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('oo.OXCURRENCY',
                'oa.OXID',
                'oa.OXARTID',
                'oa.OXARTNUM',
                'oa.OXVAT',
                'oa.OXBPRICE',
                'oa.OXNPRICE',
                'oa.OXTITLE',
                'oa.OXNETPRICE',
                'oa.OXAMOUNT',
                'oa.OXPERSPARAM',
                'prrod.ORDERED',
                'prrod.CANCELLED',
                'prrod.RETURNED',
                'prrod.SHIPPED',
                'prrod.UNIQUE_ARTICLE_NUMBER',
                "if(oa.OXSELVARIANT != '',concat(oa.OXTITLE,', ',oa.OXSELVARIANT),oa.OXTITLE) as TITLE")
            ->from('oxorder', 'oo')
            ->innerJoin('oo', 'oxorderarticles', 'oa', 'oa.oxorderid = oo.oxid')
            ->innerJoin('oo', "(SELECT * FROM " . $this->pi_ratepay_order_details . " WHERE ORDER_NUMBER = '{$this->_orderId}') AS prrod WHERE oo.oxid = '{$this->_orderId}' AND oa.oxid = prrod.UNIQUE_ARTICLE_NUMBER", '')
            ->groupBy('prrod.oxid');
        $aRows = $oQueryBuilder->execute();
        $aRows = $aRows->fetchAllAssociative();

        foreach ($aRows as $aRow) {
            $iAmount = $aRow['ORDERED'] - $aRow['SHIPPED'] - $aRow['CANCELLED'];
            $dTotal = $aRow['ORDERED'] - $aRow['RETURNED'] - $aRow['CANCELLED'];

            $listEntry['oxid'] = $aRow['OXID'];
            $listEntry['artid'] = $aRow['OXARTID'];
            $listEntry['arthash'] = $aRow['UNIQUE_ARTICLE_NUMBER'];
            $listEntry['artnum'] = $aRow['OXARTNUM'];
            $listEntry['title'] = $aRow['TITLE'];
            $listEntry['oxtitle'] = $aRow['OXTITLE'];
            $listEntry['vat'] = $aRow['OXVAT'];
            $listEntry['unitprice'] = (float) $aRow['OXNPRICE'];
            $listEntry['amount'] = $iAmount;
            $listEntry['ordered'] = $aRow['ORDERED'];
            $listEntry['shipped'] = $aRow['SHIPPED'];
            $listEntry['returned'] = $aRow['RETURNED'];
            $listEntry['cancelled'] = $aRow['CANCELLED'];
            $listEntry['currency'] = $aRow['OXCURRENCY'];
            $listEntry['bruttoprice'] = (float) $aRow['OXBPRICE'];
            $listEntry['unique_article_number'] = $aRow['UNIQUE_ARTICLE_NUMBER'];
            $listEntry['description_addition'] = $this->_getDescriptionAddition($aRow['OXPERSPARAM']);

            if ($dTotal > 0) {
                $listEntry['totalprice'] = (float)
                    $aRow['OXBPRICE'] * (
                        $listEntry['ordered'] - 
                        $listEntry['returned'] - 
                        $listEntry['cancelled']
                    );
            } else {
                $listEntry['totalprice'] = 0;
            }

            $articleList[] = $listEntry;
        }

        return $articleList;
    }

    /**
     * Adding special costs to article list
     *
     * @param $articleList
     * @param $ident
     * @param $title
     * @return array
     */
    protected function piAddSpecialCosts($articleList, $ident, $title)
    {
        $aRow = $this->piGetOrderSpecialCostsQuery($ident);

        if (isset($aRow['PRICE']) && $aRow['PRICE'] > 0) {
            $listEntry['oxid'] = "";
            $listEntry['artid'] = $ident;
            $listEntry['arthash'] = md5($aRow['UNIQUE_ARTICLE_NUMBER']);
            $listEntry['artnum'] = $ident;
            $listEntry['title'] = $title;
            $listEntry['oxtitle'] = $title;
            $listEntry['vat'] = (float) $aRow['VAT'];
            $listEntry['unitprice'] = (float) $aRow['PRICE'];
            $listEntry['amount'] = 1 - $aRow['SHIPPED'] - $aRow['CANCELLED'];
            $listEntry['ordered'] = $aRow['ORDERED'];
            $listEntry['shipped'] = $aRow['SHIPPED'];
            $listEntry['returned'] = $aRow['RETURNED'];
            $listEntry['cancelled'] = $aRow['CANCELLED'];
            $listEntry['currency'] = $aRow['oxcurrency'];
            $listEntry['unique_article_number'] = $aRow['unique_article_number'] ?? '';
            $listEntry['description_addition'] = false;

            $blHasTotal = (
                ($aRow['ORDERED'] - $aRow['RETURNED'] - $aRow['CANCELLED']) > 0
            );

            if ($blHasTotal) {
                $dTotal =
                    (float) $aRow['PRICE'] +
                    ((float) $aRow['PRICE'] *
                        round((float) $aRow['VAT']) / 100);

                $listEntry['totalprice'] = $dTotal;
            } else {
                $listEntry['totalprice'] = 0;
            }

            $articleList[] = $listEntry;
        }

        return $articleList;
    }

    /**
     * Add discounts to article list
     * 
     * @param $articleList
     * @return array
     */
    protected function piAddDiscounts($articleList)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select(
                'oo.oxcurrency',
                'od.oxid AS ARTID',
                'od.oxtitle AS TITLE',
                'oo.oxdiscount AS PRICE',
                'prrod.ordered AS ORDERED',
                'prrod.cancelled AS CANCELLED',
                'prrod.returned AS RETURNED',
                'prrod.shipped AS SHIPPED',
                'prrod.unique_article_number'
            )
            ->from('oxorder', 'oo')
            ->from('oxdiscount', 'od')
            ->from($this->pi_ratepay_order_details, 'prrod')
            ->where('prrod.order_number = :orderid')
            ->setParameter(':orderid', $this->_orderId)
            ->andWhere('prrod.article_number = od.oxid')
            ->andWhere('oo.oxid = prrod.order_number');
        $aRow = $oQueryBuilder->execute();
        $aRow = $aRow->fetchAllAssociative();

        if (isset($aRow['PRICE']) && $aRow['PRICE'] != 0) {

            $listEntry['oxid'] = "";
            $listEntry['artid'] = $aRow['ARTID'];
            $listEntry['arthash'] = md5($aRow['unique_article_number']);
            $listEntry['artnum'] = "discount";
            $listEntry['title'] = $aRow['TITLE'];
            $listEntry['oxtitle'] = $aRow['TITLE'];
            $listEntry['vat'] = "0";
            $listEntry['unitprice'] = (float) $aRow['PRICE'];
            $listEntry['amount'] = 1 - $aRow['SHIPPED'] - $aRow['CANCELLED'];
            $listEntry['ordered'] = $aRow['ORDERED'];
            $listEntry['shipped'] = $aRow['SHIPPED'];
            $listEntry['returned'] = $aRow['RETURNED'];
            $listEntry['cancelled'] = $aRow['CANCELLED'];
            $listEntry['currency'] = $aRow['oxcurrency'];
            $listEntry['unique_article_number'] = $aRow['unique_article_number'];
            $listEntry['description_addition'] = false;

            $blHasTotal = (
                ($aRow['ORDERED'] - $aRow['RETURNED'] - $aRow['CANCELLED']) > 0
            );

            if ($blHasTotal) {
                $dTotal =
                    (float) $aRow['PRICE'] +
                    ((float) $aRow['PRICE'] *
                        round((float) $aRow['VAT']) / 100);

                $listEntry['totalprice'] = $dTotal;
            } else {
                $listEntry['totalprice'] = 0;
            }

            $articleList[] = $listEntry;
        }

        return $articleList;
    }

    /**
     * Add vouchers to article list
     *
     * @param array $articleList
     * @param bool $blIsDisplayList
     * @return array
     */
    protected function piAddVouchers($articleList, $blIsDisplayList = false)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select(
                'oo.oxcurrency',
                'ov.oxdiscount AS price',
                'ov.oxdiscount as totaldiscount',
                'prrod.article_number AS artnr',
                'ov.oxvouchernr AS title',
                'prrod.ORDERED',
                'prrod.CANCELLED',
                'prrod.RETURNED',
                'prrod.SHIPPED',
                'prrod.unique_article_number',
                'ovs.OXSERIENR as seriesTitle',
                'ovs.OXSERIEDESCRIPTION as seriesDescription'
            )
            ->from('oxorder', 'oo')
            ->from('oxvouchers', 'ov')
            ->from($this->pi_ratepay_order_details, 'prrod')
            ->from('oxvoucherseries', 'ovs')
            ->where('prrod.order_number = :ordernr')
            ->setParameter(':ordernr', $this->_orderId)
            ->andWhere('ov.oxorderid = prrod.order_number')
            ->andWhere('prrod.article_number = ov.oxid')
            ->andWhere('ovs.oxid = ov.OXVOUCHERSERIEID')
            ->andWhere('oo.oxid = prrod.order_number')
            ->andWhere("ov.oxvoucherserieid != 'pi_ratepay_voucher'");
        $aRows = $oQueryBuilder->execute();
        $aRows = $aRows->fetchAllAssociative();

        $dTotalprice = 0;

        $blIsNettoMode = false;
        $dVoucherVat = Registry::getConfig()->getConfigParam('dDefaultVAT');
        $aOrderValues = $this->piGetOrderValues();
        if (count($aOrderValues) > 0) {
            $sOrderCountryId = $aOrderValues[0]['OXBILLCOUNTRYID'];
            $oOrderCountry = oxNew('oxcountry');
            if ($oOrderCountry->load($sOrderCountryId)) {
                if ($oOrderCountry->oxcountry__oxvatstatus->value == 0) {
                    $dVoucherVat = 0;
                };
            }

            $blIsNettoMode = (bool) $aOrderValues[0]['OXISNETTOMODE'];
        }

        $dSum = 0;
        for ($i = 0; $i < count($aRows); $i++) {
            $aRow = $aRows[$i];
            if ($aRow['price'] != 0) {
                $dPrice = $blIsNettoMode ? (float)$aRow['price'] : (float)$aRow['price'] / ((100+$dVoucherVat)/100);

                $listEntry['oxid'] = "";
                $listEntry['artid'] = $aRow['artnr'];
                $listEntry['arthash'] = md5($aRow['artnr']);
                $listEntry['artnum'] = 'voucher_' . $aRow['title'];
                $listEntry['title'] = $aRow['seriesTitle'];
                $listEntry['oxtitle'] = $aRow['seriesTitle'];
                $listEntry['vat'] = $dVoucherVat;
                $listEntry['unitprice'] = $dPrice;
                $listEntry['amount'] = 1 - $aRow['SHIPPED'] - $aRow['CANCELLED'];
                $listEntry['ordered'] = $aRow['ORDERED'];
                $listEntry['shipped'] = $aRow['SHIPPED'];
                $listEntry['returned'] = $aRow['RETURNED'];
                $listEntry['cancelled'] = $aRow['CANCELLED'];
                $listEntry['currency'] = $aRow['oxcurrency'];
                $listEntry['unique_article_number'] = $aRow['unique_article_number'];
                $listEntry['description_addition'] = false;

                $blHasTotal = (
                    ($aRow['ORDERED'] - $aRow['RETURNED'] - $aRow['CANCELLED']) > 0
                );

                if ($blHasTotal) {
                    $dTotal =
                        $dPrice +
                        ($dPrice *
                            round((float)$dVoucherVat) / 100);

                    $listEntry['totalprice'] = $dTotal;
                } else {
                    $listEntry['totalprice'] = 0;
                }

                $dSum += $dPrice;
                $dTotalprice += $listEntry['totalprice'];

                if ($blIsNettoMode && $blIsDisplayList === false && $blHasTotal && count($aRows) == ($i + 1) && $dSum != (float)$aRow['totaldiscount']) { // is last voucher
                    // compensation for rounding discrepancies
                    $dDiff = (float)$dTotalprice - $dSum;
                    $listEntry['unitprice'] += $dDiff;
                    $listEntry['totalprice'] += $dDiff;
                }

                $articleList[] = $listEntry;
            }
        }

        return $articleList;
    }

    /**
     * Add credit to articlelist
     *
     * @param $articleList
     * @return array
     */
    protected function piAddCredit($articleList)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select(
                'oo.oxcurrency',
                'ov.oxdiscount AS price',
                'prrod.article_number AS artnr',
                'ov.oxvouchernr AS title',
                'prrod.ORDERED',
                'prrod.CANCELLED',
                'prrod.RETURNED',
                'prrod.unique_article_number',
                'prrod.SHIPPED'
            )
            ->from('oxorder', 'oo')
            ->from('oxvouchers', 'ov')
            ->from($this->pi_ratepay_order_details, 'prrod')
            ->where('prrod.order_number = :ordernr')
            ->setParameter(':ordernr', $this->_orderId)
            ->andWhere('ov.oxorderid = prrod.order_number')
            ->andWhere("ov.oxvoucherserieid = 'pi_ratepay_voucher'")
            ->andWhere('prrod.article_number = ov.oxid')
            ->andWhere('oo.oxid = prrod.order_number');
        $aRows = $oQueryBuilder->execute();
        $aRows = $aRows->fetchAllAssociative();

        $creditVat = isset($aRow['VAT']) ? (float) $aRow['VAT'] : 0;

        foreach ($aRows as $aRow) {
            if ($aRow['price'] != 0) {
                $listEntry['oxid'] = "";
                $listEntry['artid'] = $aRow['artnr'];
                $listEntry['arthash'] = md5($aRow['artnr']);
                $listEntry['artnum'] = 'voucher_' . $aRow['title'];
                $listEntry['title'] = $aRow['title'];
                $listEntry['oxtitle'] = $aRow['title'];
                $listEntry['vat'] = $creditVat;
                $listEntry['unitprice'] = (float)$aRow['price'];
                $listEntry['amount'] = 1 - $aRow['SHIPPED'] - $aRow['CANCELLED'];
                $listEntry['ordered'] = $aRow['ORDERED'];
                $listEntry['shipped'] = $aRow['SHIPPED'];
                $listEntry['returned'] = $aRow['RETURNED'];
                $listEntry['cancelled'] = $aRow['CANCELLED'];
                $listEntry['currency'] = $aRow['oxcurrency'];
                $listEntry['unique_article_number'] = $aRow['unique_article_number'];
                $listEntry['description_addition'] = false;

                if (($aRow['ORDERED'] - $aRow['RETURNED'] - $aRow['CANCELLED']) > 0) {
                    $listEntry['totalprice'] = (float)$aRow['price'] + ((float)$aRow['price'] * round($creditVat) / 100);
                } else {
                    $listEntry['totalprice'] = 0;
                }

                $articleList[] = $listEntry;
            }
        }

        return $articleList;
    }

    /**
     * Returns special costs
     *
     * @param $sIdent
     * @return mixed
     */
    protected function piGetOrderSpecialCostsQuery($sIdent)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select(
                'oo.oxcurrency',
                'prrod.*'
            )
            ->from($this->pi_ratepay_order_details, 'prrod')
            ->from('oxorder', 'oo')
            ->where('prrod.order_number = :ordernr')
            ->setParameter(':ordernr', $this->_orderId)
            ->andWhere('prrod.article_number = :artnr')
            ->setParameter(':artnr', $sIdent)
            ->andWhere("oo.oxid = prrod.order_number");
        $aRow = $oQueryBuilder->execute();
        $aRow = $aRow->fetchAllAssociative();

        return $aRow[0] ?? [];
    }

    /**
     * Returns order informations
     * 
     * @return array|null
     */
    protected function piGetOrderValues()
    {
        if ($this->_orderValues === null) {
            $orderId = $this->_orderId;
            $oContainer = ContainerFactory::getInstance()->getContainer();
            /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
            $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->select('*')
                ->from('oxorder')
                ->where('OXID = :oxid')
                ->setParameter(':oxid', $orderId);
            $aOrders = $oQueryBuilder->execute();
            $this->_orderValues = $aOrders->fetchAllAssociative();
        }
        
        return $this->_orderValues;
    }
}
