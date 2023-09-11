<?php

namespace pi\ratepay\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Application\Model\Voucher;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use pi\ratepay\Application\Model\Settings;
use pi\ratepay\Core\DetailsViewData;
use pi\ratepay\Core\History;
use pi\ratepay\Core\HistoryList;
use pi\ratepay\Core\ModelFactory;
use pi\ratepay\Core\OrderDetails;
use pi\ratepay\Core\Orders;
use pi\ratepay\Core\RateDetails;
use pi\ratepay\Core\RequestDataBackend;
use pi\ratepay\Core\Utilities;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * RatePay order admin panel
 * {@inheritdoc}
 *
 * @package   PayIntelligent_RatePAY
 * @extends AdminDetailsController
 */
class Details extends AdminDetailsController
{

    /**
     * Unique Order ID
     *
     * @var string
     */
    private $orderId = null;

    /**
     * Amount of the Goodwill
     *
     * @var double
     */
    private $piRatepayVoucher = null;

    /**
     * Database Table name used for Order details
     *
     * @var string
     */
    private $pi_ratepay_order_details;

    /**
     * Type of the Order rate/rechnung
     *
     * @var string
     */
    private $_paymentMethod;

    /**
     * shopId
     *
     * @var int
     */
    private $_shopId;

    /**
     * Order Model Object
     * An representation of the order whicht get edited.
     *
     * @var Order
     */
    private $_oEditObject = null;

    /**
     *
     * @var mixed
     */
    private $_paymentSid;

    /**
     * request data backend object, get User Data.
     *
     * @var RequestDataBackend
     */
    private $_requestDataBackend;

    /**
     * Is shop set to UTF8 Mode
     * @var bool
     */
    private $_utfMode = null;

    private $_transactionId;

    /**
     * Preparing all necessary Data for rendering and executing all calls
     * also: {@inheritdoc}
     *
     * @return string
     * @see AdminDetailsController::render()
     */
    public function render()
    {
        parent::render();

        $order = $this->getEditObject();

        $paymentSid = $this->getPaymentSid();

        if ($paymentSid && in_array($paymentSid, Utilities::$_RATEPAY_PAYMENT_METHOD)) {
            $this->initRatepayDetails($order);
            return "@ratepay/pi_ratepay_details";
        }

        return "@ratepay/pi_ratepay_no_details";
    }

    /**
     * Initialises smarty variables specific to RatePAY order.
     * @param Order $order
     */
    private function initRatepayDetails(Order $order)
    {
        $this->_paymentMethod = Utilities::getPaymentMethod($this->getPaymentSid());
        $this->_shopId = Registry::getConfig()->getShopId();
        $this->_shopId = oxNew(Settings::class)->setShopIdToOne($this->_shopId);


        $this->pi_ratepay_order_details = 'pi_ratepay_order_details';

        $this->_requestDataBackend = oxNew(RequestDataBackend::class, $this->getEditObject());

        $ratepayOrder = oxNew(Orders::class);
        $ratepayOrder->loadByOrderNumber($this->getOrderId());
        $this->_transactionId = $ratepayOrder->pi_ratepay_orders__transaction_id->rawValue;
        $transactionId = $ratepayOrder->pi_ratepay_orders__transaction_id->rawValue;
        $descriptor = $ratepayOrder->pi_ratepay_orders__descriptor->rawValue;
        $this->addTplParam('pi_transaction_id', $transactionId);
        $this->addTplParam('pi_descriptor', $descriptor);

        $this->addTplParam('pi_total_amount', $order->oxorder__oxtotalordersum->getRawValue());

        $this->addTplParam('pi_ratepay_payment_type', $this->_paymentMethod);
        $this->addTplParam('articleList', $this->getPreparedOrderArticles(true));
        $this->addTplParam('historyList', $this->getHistory($this->_aViewData["articleList"]));

        if ($this->getPaymentSid() == "pi_ratepay_rate" || $this->getPaymentSid() == "pi_ratepay_rate0") {
            $ratepayRateDetails = oxNew(RateDetails::class);
            $ratepayRateDetails->loadByOrderId($this->getOrderId());

            $pirptotalamountvalue = $ratepayRateDetails->pi_ratepay_rate_details__totalamount->rawValue;
            $pirpamountvalue = $ratepayRateDetails->pi_ratepay_rate_details__amount->rawValue;
            $pirpinterestamountvalue = $ratepayRateDetails->pi_ratepay_rate_details__interestamount->rawValue;
            $pirpservicechargevalue = $ratepayRateDetails->pi_ratepay_rate_details__servicecharge->rawValue;
            $pirpannualpercentageratevalue = $ratepayRateDetails->pi_ratepay_rate_details__annualpercentagerate->rawValue;
            $pirpdebitinterestvalue = $ratepayRateDetails->pi_ratepay_rate_details__monthlydebitinterest->rawValue;
            $pirpnumberofratesvalue = $ratepayRateDetails->pi_ratepay_rate_details__numberofrates->rawValue;
            $pirpratevalue = $ratepayRateDetails->pi_ratepay_rate_details__rate->rawValue;
            $pirplastratevalue = $ratepayRateDetails->pi_ratepay_rate_details__lastrate->rawValue;

            $pirptotalamountvalue = str_replace(".", ",", $this->getFormattedNumber($pirptotalamountvalue)) . " EUR";
            $pirpamountvalue = str_replace(".", ",", $this->getFormattedNumber($pirpamountvalue)) . " EUR";
            $pirpinterestamountvalue = str_replace(
                    ".",
                    ",",
                    $this->getFormattedNumber($pirpinterestamountvalue)
                ) . " EUR";
            $pirpservicechargevalue = str_replace(
                    ".",
                    ",",
                    $this->getFormattedNumber($pirpservicechargevalue)
                ) . " EUR";
            $pirpannualpercentageratevalue = str_replace(
                    ".",
                    ",",
                    $this->getFormattedNumber($pirpannualpercentageratevalue)
                ) . "%";
            $pirpdebitinterestvalue = str_replace(".", ",", $this->getFormattedNumber($pirpdebitinterestvalue)) . "%";
            $pirpnumberofratesvalue = str_replace(
                    ".",
                    ",",
                    $this->getFormattedNumber($pirpnumberofratesvalue)
                ) . " Monate";
            $pirpratevalue = str_replace(".", ",", $this->getFormattedNumber($pirpratevalue)) . " EUR";
            $pirplastratevalue = str_replace(".", ",", $this->getFormattedNumber($pirplastratevalue)) . " EUR";

            $this->addTplParam('pirptotalamountvalue', $pirptotalamountvalue);
            $this->addTplParam('pirpamountvalue', $pirpamountvalue);
            $this->addTplParam('pirpinterestamountvalue', $pirpinterestamountvalue);
            $this->addTplParam('pirpservicechargevalue', $pirpservicechargevalue);
            $this->addTplParam('pirpannualpercentageratevalue', $pirpannualpercentageratevalue);
            $this->addTplParam('pirpmonthlydebitinterestvalue', $pirpdebitinterestvalue);
            $this->addTplParam('pirpnumberofratesvalue', $pirpnumberofratesvalue);
            $this->addTplParam('pirpratevalue', $pirpratevalue);
            $this->addTplParam('pirplastratevalue', $pirplastratevalue);
        }
    }

    /**
     * init RatePay data, start deliver request
     */
    public function deliver()
    {
        $this->initRatepayDetails($this->getEditObject());
        $this->deliverRequest();
    }

    /**
     * init RatePay data, start paymentChangeRequest
     */
    public function cancel()
    {
        $this->initRatepayDetails($this->getEditObject());
        $this->paymentChangeRequest('cancellation');
    }

    /**
     * init RatePay data, start paymentChangeRequest
     */
    public function retoure()
    {
        $this->initRatepayDetails($this->getEditObject());
        $this->paymentChangeRequest('return');
    }

    /**
     * init RatePay data, start credit request
     *
     * @return null
     */
    public function credit()
    {
        $voucherAmount = Registry::getRequest()->getRequestEscapedParameter('voucherAmount');
        $voucherKomma = Registry::getRequest()->getRequestEscapedParameter('voucherAmountKomma');

        $this->initRatepayDetails($this->getEditObject());

        if (isset($voucherAmount) && preg_match("/^[0-9]{1,4}$/", $voucherAmount)) {
            $voucherKomma = isset($voucherKomma) && preg_match('/^[0-9]{1,2}$/', $voucherKomma) ? $voucherKomma : '00';

            $voucherAmount .= '.' . $voucherKomma;
            $voucherAmount = (double)$voucherAmount;

            if ($voucherAmount <= $this->getEditObject()->getTotalOrderSum() && $voucherAmount > 0) {
                $this->piRatepayVoucher = $voucherAmount;

                $this->creditRequest();
                return;
            }
        }

        $this->addTplParam('pierror', 'credit');
    }

    /**
     * Gets the History of the order
     *
     * @param array ArticleList
     * @return array
     */
    private function getHistory($articleList)
    {
        $ratepayHistoryList = oxNew(HistoryList::class);
        $ratepayHistoryList->getFilteredList("order_number = '" . $this->getOrderId() . "'");

        $historyList = [];

        foreach ($ratepayHistoryList as $historyItem) {
            $title = '';
            $articleNumber = '';

            foreach ($articleList as $article) {
                if ($historyItem->pi_ratepay_history__article_number->rawValue == $article['artid']) {
                    $title = $article['title'];
                    $articleNumber = $article['artnum'];
                }
            }

            array_push($historyList, [
                'article_number' => $articleNumber,
                'title' => $title,
                'quantity' => $historyItem->pi_ratepay_history__quantity->rawValue,
                'method' => $historyItem->pi_ratepay_history__method->rawValue,
                'subtype' => $historyItem->pi_ratepay_history__submethod->rawValue,
                'date' => $historyItem->pi_ratepay_history__date->rawValue
            ]);
        }

        return $historyList;
    }

    /**
     * Gets all articles with additional informations
     *
     * @param bool $blIsDisplayList
     * @return array
     */
    public function getPreparedOrderArticles($blIsDisplayList = false)
    {
        $detailsViewData = oxNew(DetailsViewData::class, $this->getOrderId());

        return $detailsViewData->getPreparedOrderArticles($blIsDisplayList);
    }

    /**
     * add new voucher for order
     *
     * @return string oxId of voucher
     */
    private function piAddVoucher()
    {
        $order = $this->getEditObject();
        $orderId = $this->getOrderId();
        $oArticles = $this->getPreparedOrderArticles();

        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('count( * )')
            ->from('oxvouchers')
            ->where('oxvouchernr LIKE :word')
            ->setParameter(':word', 'pi-Merchant-Voucher-%');
        $sVoucherCount = $oQueryBuilder->execute();
        $sVoucherCount = $sVoucherCount->fetchOne();

        $sVoucherNr = "pi-Merchant-Voucher-" . $sVoucherCount;

        $newVoucher = oxNew(Voucher::class);
        $newVoucher->assign([
            'oxvoucherserieid' => 'pi_ratepay_voucher',
            'oxorderid' => $orderId,
            'oxuserid' => $order->getFieldData("oxuserid"),
            'oxdiscount' => $this->piRatepayVoucher,
            'oxdateused' => date('Y-m-d', Registry::get("oxUtilsDate")->getTime()),
            'oxvouchernr' => $sVoucherNr
        ]);

        $newVoucher->save();
        $this->recalculateOrder($order, $oArticles, $sVoucherNr);

        $tmptotal = 0;
        foreach ($oArticles as $article) {
            if ($article['amount'] > 0) {
                $tmptotal += $article['amount'] * $article['bruttoprice'];
            }
        }

        $voucherId = $newVoucher->getId();

        $voucherDetails = oxNew(OrderDetails::class);

        $voucherDetails->assign([
            'order_number' => $orderId,
            'article_number' => $voucherId,
            'unique_article_number' => $voucherId,
            'ordered' => 1,
        ]);
        if ($tmptotal < $this->piRatepayVoucher) {
            $voucherDetails->assign([
                'shipped' => 1,
            ]);
        }

        $voucherDetails->save();

        return $voucherId;
    }

    /**
     * Do RatePay request. If the request succeeds add voucher to order and log to history.
     */
    protected function creditRequest()
    {
        $operation = "PAYMENT_CHANGE";
        $subtype = "credit";

        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('count(*)')
            ->from('oxvouchers')
            ->where('oxvouchernr LIKE :word')
            ->setParameter(':word', 'pi-Merchant-Voucher-%');
        $sNr = $oQueryBuilder->execute();
        $sNr = $sNr->fetchOne();
        $vouchertitel = "pi-Merchant-Voucher-" . $sNr;

        $articles[] = [
            'title' => 'Credit',
            'artnum' => $vouchertitel,
            'unitprice' => "-" . $this->getFormattedNumber($this->piRatepayVoucher),
            'arthash' => 1,
            'vat' => 0,
        ];

        $modelFactory = oxNew(ModelFactory::class);
        $paymentMethod = Utilities::getPaymentMethod($this->_paymentSid);
        $modelFactory->setSandbox($this->isSandbox($paymentMethod));
        $modelFactory->setPaymentType($this->getPaymentSid());
        $modelFactory->setShopId($this->_shopId);
        $modelFactory->setBasket($articles);
        $modelFactory->setTransactionId($this->_transactionId);
        $modelFactory->setOrderId($this->getOrderId());
        $modelFactory->setSubtype($subtype);
        $change = $modelFactory->doOperation($operation);

        $isSuccess = 'pierror';
        if ($change->isSuccessful()) {
            $artid = $this->piAddVoucher();
            $this->logHistory($this->getOrderId(), $artid, 1, $operation, $subtype);

            $isSuccess = 'pisuccess';
        }
        $this->addTplParam($isSuccess, $subtype);
    }

    /**
     * Excecute payment change request. If the request succeeds add voucher to order and log to history.
     * @param string $paymentChangeType 'cancel' or 'return
     */
    protected function paymentChangeRequest($paymentChangeType)
    {
        $operation = 'PAYMENT_CHANGE';
        $modelFactory = oxNew(ModelFactory::class);
        $paymentMethod = Utilities::getPaymentMethod($this->_paymentSid);
        $modelFactory->setSandbox($this->isSandbox($paymentMethod));
        $modelFactory->setPaymentType($this->getPaymentSid());
        $modelFactory->setShopId($this->_shopId);
        $articles = $this->getPreparedOrderArticles();
        $modelFactory->setBasket($articles);
        $modelFactory->setTransactionId($this->_transactionId);
        $modelFactory->setOrderId($this->getOrderId());
        $modelFactory->setSubtype($paymentChangeType);
        $change = $modelFactory->doOperation($operation);

        $isSuccess = 'pierror';
        if ($change->isSuccessful()) {
            $articles = $this->getPreparedOrderArticles();
            $articleList = [];
            $oContainer = ContainerFactory::getInstance()->getContainer();
            /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
            $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
            foreach ($articles as $article) {
                if (Registry::getRequest()->getRequestEscapedParameter($article['arthash']) > 0) {
                    $quant = Registry::getRequest()->getRequestEscapedParameter($article['arthash']);
                    $artid = $article['artid'];
                    $uniqueArticleNumber = $article['unique_article_number'];
                    if (empty($uniqueArticleNumber)) {
                        $uniqueArticleNumber = $artid;
                    }
                    if ($paymentChangeType == "cancellation") {
                        $oQueryBuilder = $oQueryBuilderFactory->create();
                        $oQueryBuilder
                            ->update($this->pi_ratepay_order_details)
                            ->set('cancelled', 'cancelled + ' . $quant)
                            ->where('order_number = :orderId')
                            ->setParameter(':orderId', $this->getOrderId())
                            ->andWhere('unique_article_number = :uniqueArticleNumber')
                            ->setParameter(':uniqueArticleNumber', $uniqueArticleNumber);
                        $oQueryBuilder->execute();
                    } else {
                        if ($paymentChangeType == "return") {
                            $oQueryBuilder = $oQueryBuilderFactory->create();
                            $oQueryBuilder
                                ->update($this->pi_ratepay_order_details)
                                ->set('returned', 'returned + ' . $quant)
                                ->where('order_number = :orderId')
                                ->setParameter(':orderId', $this->getOrderId())
                                ->andWhere('unique_article_number = :uniqueArticleNumber')
                                ->setParameter(':uniqueArticleNumber', $uniqueArticleNumber);
                            $oQueryBuilder->execute();
                        }
                    }
                    $this->logHistory($this->getOrderId(), $artid, $quant, $operation, $paymentChangeType);
                    if ($article['oxid'] != "") {
                        $articleList[$article['oxid']] = [
                            'oxamount' => $article['ordered'] - $article['cancelled'] - $article['returned'] - Registry::getRequest(
                                )->getRequestEscapedParameter($article['arthash'])
                        ];
                    } else {
                        $oOrder = $this->getEditObject();

                        if ($article['artid'] == "oxdelivery") {
                            $oOrder->oxorder__oxdelcost->setValue(0);
                        } else {
                            if ($article['artid'] == "oxpayment") {
                                $oOrder->oxorder__oxpaycost->setValue(0);
                            } else {
                                if ($article['artid'] == "oxwrapping") {
                                    $oOrder->oxorder__oxwrapcost->setValue(0);
                                } else {
                                    if ($article['artid'] == "oxgiftcard") {
                                        $oOrder->oxorder__oxgiftcardcost->setValue(0);
                                    } else {
                                        if ($article['artid'] == "oxtsprotection") {
                                            $oOrder->oxorder__oxtsprotectcosts->setValue(0);
                                        } else {
                                            if ($article['artid'] == "discount") {
                                                $oOrder->oxorder__oxdiscount->setValue(0);
                                            } else {
                                                $value = $oOrder->oxorder__oxvoucherdiscount->getRawValue(
                                                    ) + $article['totalprice'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->updateOrder($articleList, $this->isPaymentChangeFull());
            $isSuccess = 'pisuccess';
        }

        if ($this->isPaymentChangeFull()) {
            $paymentChangeType = 'full-' . $paymentChangeType;
        } else {
            $paymentChangeType = 'partial-' . $paymentChangeType;
        }

        $this->addTplParam($isSuccess, $paymentChangeType);
    }

    /**
     * Tests if all available articles are returned or cancelled.
     * @return boolean
     */
    protected function isPaymentChangeFull()
    {
        $full = true;
        $articles = $this->getPreparedOrderArticles();

        foreach ($articles as $article) {
            if (Registry::getRequest()->getRequestEscapedParameter($article['arthash']) != $article['ordered']) {
                $full = false;
            }
        }

        return $full;
    }

    protected function isSandbox($method)
    {
        $settings = oxNew(Settings::class);
        $settings->loadByType(strtolower($method), $this->_shopId);
        return ($settings->pi_ratepay_settings__sandbox->rawValue);
    }

    /**
     * Excecute payment change request. If the request succeeds add voucher to order and log to history.
     */
    protected function deliverRequest()
    {
        $operation = 'CONFIRMATION_DELIVER';
        $modelFactory = oxNew(ModelFactory::class);
        $paymentMethod = Utilities::getPaymentMethod($this->_paymentSid);
        $modelFactory->setSandbox($this->isSandbox($paymentMethod));
        $modelFactory->setPaymentType($this->getPaymentSid());
        $modelFactory->setShopId($this->_shopId);
        $articles = $this->getPreparedOrderArticles();
        $modelFactory->setBasket($articles);
        $modelFactory->setTransactionId($this->_transactionId);
        $modelFactory->setOrderId($this->getOrderId());

        $deliver = $modelFactory->doOperation($operation);

        $isSuccess = 'pierror';

        if ($deliver->isSuccessful()) {
            $articles = $this->getPreparedOrderArticles();

            $oContainer = ContainerFactory::getInstance()->getContainer();
            /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
            $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
            foreach ($articles as $article) {
                if (Registry::getRequest()->getRequestEscapedParameter($article['arthash']) > 0) {
                    $quant = Registry::getRequest()->getRequestEscapedParameter($article['arthash']);
                    $artid = $article['artid'];
                    $uniqueArticleNumber = $article['unique_article_number'];
                    if (empty($uniqueArticleNumber)) {
                        $uniqueArticleNumber = $artid;
                    }
                    $oQueryBuilder = $oQueryBuilderFactory->create();
                    $oQueryBuilder
                        ->update($this->pi_ratepay_order_details)
                        ->set('shipped', 'shipped + ' . $quant)
                        ->where('order_number = :orderId')
                        ->setParameter(':orderId', $this->getOrderId())
                        ->andWhere('unique_article_number = :uniqueArticleNumber')
                        ->setParameter(':uniqueArticleNumber', $uniqueArticleNumber);
                    $oQueryBuilder->execute();
                    $this->logHistory($this->getOrderId(), $artid, $quant, $operation, '');
                }
            }
            $isSuccess = 'pisuccess';
        }

        $this->addTplParam($isSuccess, '');
    }

    /**
     * logs ratepay backend transactions history.
     *
     * @param string $orderId oxid of the order
     * @param string $artid oxid of the article which is modified
     * @param string $quant quantity which is changed
     * @param string $operation (deliver, payment change, credit)
     * @param string $subtype (cancellation, return)
     */
    protected function logHistory($orderId, $artid, $quant, $operation, $subtype)
    {
        $ratepayHistory = oxNew(History::class);
        $ratepayHistory->assign([
            'order_number' => $orderId,
            'article_number' => $artid,
            'quantity' => $quant,
            'method' => $operation,
            'submethod' => $subtype,
            'date' => date('Y-m-d H:i:s', Registry::get("oxUtilsDate")->getTime())
        ]);
        $ratepayHistory->save();
    }

    /**
     * Updates order articles stock and recalculates order
     *
     * @return null
     */
    public function updateOrder($articleList, $fullCancellation)
    {
        $aOrderArticles = $articleList;
        $oArticles = $this->getPreparedOrderArticles();

        if (is_array($aOrderArticles) && $oOrder = $this->getEditObject()) {
            $myConfig = Registry::getConfig();
            $oOrderArticles = $oOrder->getOrderArticles();
            $blUseStock = $myConfig->getConfigParam('blUseStock');
            if ($fullCancellation) {
                $oOrder->oxorder__oxstorno = oxNew(Field::class, 1);
            }

            $oOrder->save();

            foreach ($oOrderArticles as $oOrderArticle) {
                $sItemId = $oOrderArticle->getId();
                if (isset($aOrderArticles[$sItemId])) {
                    // update stock
                    if ($blUseStock) {
                        $oOrderArticle->setNewAmount($aOrderArticles[$sItemId]['oxamount']);
                    } else {
                        $oOrderArticle->assign($aOrderArticles[$sItemId]);
                        $oOrderArticle->save();
                    }
                    if ($aOrderArticles[$sItemId]['oxamount'] == 0) {
                        $this->storno($sItemId);
                    }
                }
            }
            // recalculating order
            $this->recalculateOrder($oOrder, $oArticles);
        }
    }

    /**
     * cancels order item
     *
     * @param string $sItemId
     */
    public function storno($sItemId)
    {
        $myConfig = Registry::getConfig();
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();

        $sOrderArtId = $sItemId;
        $oArticle = oxNew(OrderArticle::class);
        $oArticle->load($sOrderArtId);

        $oArticle->oxorderarticles__oxstorno->setValue(1);

        // stock information
        if ($myConfig->getConfigParam('blUseStock')) {
            $oArticle->updateArticleStock(
                $oArticle->oxorderarticles__oxamount->value,
                $myConfig->getConfigParam('blAllowNegativeStock')
            );
        }
        $oQueryBuilder
            ->update('oxorderarticles')
            ->set('oxstorno', $oArticle->oxorderarticles__oxstorno->value)
            ->where('oxid = :oxid')
            ->setParameter(':oxid', $sOrderArtId);
        $oQueryBuilder->execute();
    }

    /**
     * Returns editable order object
     *
     * @return Order
     */
    public function getEditObject()
    {
        $orderId = $this->getOrderId();
        if ($this->_oEditObject === null && isset($orderId) && $orderId != "-1") {
            $this->_oEditObject = oxNew(Order::class);
            $this->_oEditObject->load($orderId);
        }
        return $this->_oEditObject;
    }

    protected function getOrderId()
    {
        if ($this->orderId === null) {
            $this->orderId = $this->getEditObjectId();
        }

        return $this->orderId;
    }

    /**
     * Call to order object to recalculateOrder
     *
     * @param Order $oOrder
     * @param array $aOrderArticles
     * @param string $voucherNr
     * @return void
     */
    private function recalculateOrder($oOrder, $aOrderArticles, $voucherNr = null)
    {
        // keeps old delivery cost
        $oOrder->reloadDiscount(false);
        $oOrder->reloadDelivery(false);
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);

        $totalprice = 0;
        $voucherDiscountTotal = 0;

        foreach ($aOrderArticles as $article) {
            if (substr($article['artnum'], 0, 7) == 'voucher' && ($article['ordered'] - $article['returned'] > 0)) {
                $voucherDiscountTotal += $article['totalprice'];
            }
            if ($article['artnum'] == 'discount' || substr($article['artnum'], 0, 7) == 'voucher' || stripos(
                    $article['artnum'],
                    'pi-Merchant-Voucher'
                ) !== false) {
                $totalprice -= $article['totalprice'];
            } else {
                $totalprice += $article['totalprice'];
            }
        }

        if ($voucherNr != null) {
            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->select('oxdiscount')
                ->from('oxvouchers')
                ->where('oxvouchernr = :voucherNr')
                ->setParameter(':voucherNr', $voucherNr);
            $sDiscount = $oQueryBuilder->execute();
            $sDiscount = (float)$sDiscount->fetchOne();
            $voucherDiscountTotal += $sDiscount;
            $totalprice -= $sDiscount;
        }

        if ($oOrder->oxorder__oxvoucherdiscount->getRawValue() != $voucherDiscountTotal) {
            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->update('oxorder')
                ->set('oxvoucherdiscount', $voucherDiscountTotal)
                ->where('oxid = :oxid')
                ->setParameter(':oxid', $oOrder->oxorder__oxid->getRawValue());
            $oQueryBuilder->execute();
        }

        if ($totalprice > 0) {
            $oQueryBuilder = $oQueryBuilderFactory->create();
            $oQueryBuilder
                ->update('oxorder')
                ->set('oxtotalordersum', $totalprice)
                ->where('oxid = :oxid')
                ->setParameter(':oxid', $oOrder->oxorder__oxid->getRawValue());
            $oQueryBuilder->execute();
            $oOrder->oxorder__oxtotalordersum->setValue($totalprice);
        }
    }

    /**
     * Return payment type used in order.
     * @return string
     */
    protected function getPaymentSid()
    {
        if ($this->_paymentSid === null) {
            $order = $this->getEditObject();
            $this->_paymentSid = isset($order) ? $order->getPaymentType()->oxuserpayments__oxpaymentsid->value : false;
        }
        return $this->_paymentSid;
    }

    /**
     * Get formattet number
     * @param string $str
     * @param int $decimal
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    private function getFormattedNumber($str, $decimal = 2, $dec_point = ".", $thousands_sep = "")
    {
        $util = oxNew(Utilities::class);
        return $util->getFormattedNumber($str, $decimal, $dec_point, $thousands_sep);
    }

}
