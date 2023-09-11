<?php

namespace pi\ratepay\Extend\Application\Model;

use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\DatabaseProvider;
use pi\ratepay\Application\Model\Settings;
use pi\ratepay\Core\LogsService;
use pi\ratepay\Core\ModelFactory;
use pi\ratepay\Core\OrderDetails;
use pi\ratepay\Core\Orders;
use pi\ratepay\Core\PaymentBan;
use pi\ratepay\Core\RateDetails;
use pi\ratepay\Core\Utilities;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class RatepayPaymentGateway extends RatepayPaymentGateway_parent
{
    /**
     * Payment type
     *
     * @var string
     */
    protected $_paymentId = null;

    /**
     * Payment type config
     *
     * @var array
     */
    protected $paymentMethodIds = [
        'pi_ratepay_rechnung' => [
            'connection_timeout' => '-418',
            'denied' => '-400',
            'soft' => '-001',
        ],
        'pi_ratepay_rate' => [
            'connection_timeout' => '-418',
            'denied' => '-407',
            'soft' => '-001',
        ],
        'pi_ratepay_rate0' => [
            'connection_timeout' => '-418',
            'denied' => '-407',
            'soft' => '-001',
        ],
        'pi_ratepay_elv' => [
            'connection_timeout' => '-418',
            'denied' => '-300',
            'soft' => '-001',
        ]
    ];

    /**
     * @param string $sPaymentType
     * @return mixed
     */
    protected function isSandbox($sPaymentType)
    {
        $method = Utilities::getPaymentMethod($sPaymentType);

        $settings = oxNew(Settings::class);
        $settings->loadByType(strtolower($method), Registry::getSession()->getVariable('shopId'));
        return ($settings->pi_ratepay_settings__sandbox->rawValue);
    }

    /**
     * Check if a RatePay payment type was selected
     *
     * @param object $oOrder  User ordering object
     *
     * @return bool
     */
    protected function isRatePayPayment($oOrder)
    {
        if (in_array($oOrder->oxorder__oxpaymenttype->value, Utilities::$_RATEPAY_PAYMENT_METHOD)) {
            return true;
        }
        return false;
    }

    /**
     * Executes payment, returns true on success.
     *
     * @param double $dAmount Goods amount
     * @param object $oOrder  User ordering object
     *
     * @return bool
     */
    public function executePayment($dAmount, &$oOrder)
    {
        if($this->isRatePayPayment($oOrder) === false) {
            return parent::executePayment($dAmount, $oOrder);
        }

        try {
            $this->handleRatePayPayment($oOrder, $dAmount);
        } catch(\Exception $exc) {
            $this->_iLastErrorNo = $exc->getCode();
            $this->_sLastError = $exc->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @param  object $oOrder  User ordering object
     * @param  double $dAmount Goods amount
     * @return void
     */
    protected function handleRatePayPayment($oOrder, $dAmount)
    {
        $this->_paymentId = $oOrder->oxorder__oxpaymenttype->value;
        $isSandbox = $this->isSandbox($this->_paymentId);

        $modelFactory = oxNew(ModelFactory::class);
        $modelFactory->setPaymentType($this->_paymentId);
        $modelFactory->setSandbox($isSandbox);
        $modelFactory->setCountryId($this->getUser()->oxuser__oxcountryid->value);
        $modelFactory->setShopId(Registry::getSession()->getVariable('shopId'));

        $payInit = $modelFactory->doOperation('PAYMENT_INIT');
        if (!$payInit->isSuccessful()) {
            if ($payInit->getReasonCode() != 703 && !$isSandbox) {
                Registry::getSession()->setVariable('pi_ratepay_denied', 'denied');
            }
            Registry::getSession()->setVariable($this->_paymentId . '_error_id', $this->paymentMethodIds[$this->_paymentId]['denied']);
            Registry::getUtils()->redirect(Registry::getConfig()->getSslShopUrl() . 'index.php?cl=Payment', false);
        }

        $transactionId = (string)$payInit->getTransactionId();
        Registry::getSession()->setVariable($this->_paymentId . '_trans_id', $transactionId);

        $modelFactory->setTransactionId($transactionId);
        $modelFactory->setCustomerId($this->getUser()->oxuser__oxcustnr->value);
        $modelFactory->setDeviceToken(Registry::getSession()->getVariable('pi_ratepay_dfp_token'));
        $modelFactory->setBasket(Registry::getSession()->getBasket());
        $modelFactory->setOrder($oOrder);

        $payRequest = $modelFactory->doOperation('PAYMENT_REQUEST');
        if (!$payRequest->isSuccessful()) {
            if ((!$payRequest->getResultCode() == 150) && !$isSandbox) {
                Registry::getSession()->setVariable('pi_ratepay_denied', 'denied');
            }

            $message = $payRequest->getCustomerMessage();
            Registry::getSession()->setVariable($this->_paymentId . '_message', (string)$message);
            if ($payRequest->getResultCode() == 150 && !empty($message)) {
                Registry::getSession()->setVariable($this->_paymentId . '_error_id', $this->paymentMethodIds[$this->_paymentId]['soft']);
            } else {
                Registry::getSession()->setVariable($this->_paymentId . '_error_id', $this->paymentMethodIds[$this->_paymentId]['denied']);

            }

            // OX-33 : register a payment ban on error codes 703/720/721
            if (in_array($payRequest->getReasonCode(), [703, 720, 721])) {
                $fromDate = (oxNew(\DateTime::class))->format(DATE_ATOM);
                $toDate = (oxNew(\DateTime::class, '+2day'))->format(DATE_ATOM);

                /** @var User $user */
                $user = oxNew(User::class);
                $userId = $oOrder->oxorder__oxuserid->value;
                $user->load($userId);
                if ($user->oxuser__oxregister->value == '0000-00-00 00:00:00') {
                    $userId = $user->oxuser__oxusername->value;
                }

                /** @var PaymentBan $paymentBan */
                $paymentBan = oxNew(PaymentBan::class);
                $existingEntry = $paymentBan->loadByUserAndMethod($userId, $this->_paymentId);
                if ($existingEntry) {
                    $paymentBan->pi_ratepay_payment_ban__from_date->rawValue = $fromDate;
                    $paymentBan->pi_ratepay_payment_ban__to_date->rawValue = $toDate;
                } else {
                    $paymentBan->assign(
                        [
                            'USERID' => $userId,
                            'PAYMENT_METHOD' => $this->_paymentId,
                            'FROM_DATE' => $fromDate,
                            'TO_DATE' => $toDate
                        ]
                    );
                }
                $paymentBan->save();
            }


            // OX-19: delete order if payment failed
            $oOrder->delete();

            Registry::getUtils()->redirect(Registry::getConfig()->getSslShopUrl() . 'index.php?cl=Payment', false);
        }
        Registry::getSession()->setVariable($this->_paymentId . '_descriptor', $payRequest->getDescriptor());

        // FINALIZE

        if ($oOrder->getId() != null && $oOrder->getId() != Registry::getSession()->getVariable('pi_ratepay_shops_order_id')) {
            Registry::getSession()->setVariable('pi_ratepay_shops_order_id', $oOrder->getId());
        }
        $this->saveRatepayOrder(Registry::getSession()->getVariable('pi_ratepay_shops_order_id'), $oOrder);
        $tid = Registry::getSession()->getVariable($this->_paymentId . '_trans_id');

        $orderLogs = LogsService::getInstance()->getLogsList("transaction_id = " . DatabaseProvider::getDb(true)->quote($tid));
        foreach ($orderLogs as $log) {
            if (!is_null($oOrder->oxorder__oxordernr)) {
                $log->assign(['order_number' => $oOrder->oxorder__oxordernr]);
            } else {
                $log->assign(['order_number' => Registry::getSession()->getVariable('pi_ratepay_shops_order_id')]);
            }
            $log->save();
        }

        $modelFactory->setOrderId(Registry::getSession()->getVariable('pi_ratepay_shops_order_id'));
        $modelFactory->setTransactionId($tid);
        $modelFactory->doOperation('PAYMENT_CONFIRM');

        Registry::getSession()->deleteVariable('pi_ratepay_dfp_token');
    }

    /**
     * Saves order information to ratepay order tables in the db. Used for backend operations.
     *
     * @uses functions saveRatepayBasketItems
     * @param string $id
     * @param object $oOrder
     */
    private function saveRatepayOrder($id, $oOrder)
    {
        $transid = Registry::getSession()->getVariable($this->_paymentId . '_trans_id');
        $descriptor = Registry::getSession()->getVariable($this->_paymentId . '_descriptor');
        $userbirthdate = $this->getUser()->oxuser__oxbirthdate->value;
        $api = 'api_1.8';

        $ratepayOrder = oxNew(Orders::class);
        $ratepayOrder->loadByOrderNumber($id);

        $ratepayOrder->assign([
            'order_number' => $id,
            'transaction_id' => $transid,
            'descriptor' => $descriptor,
            'userbirthdate' => $userbirthdate,
            'rp_api' => $api
        ]);

        $ratepayOrder->save();

        if ($this->_paymentId === 'pi_ratepay_rate') {
            $totalAmount = Registry::getSession()->getVariable('pi_ratepay_rate_total_amount');
            $amount = Registry::getSession()->getVariable('pi_ratepay_rate_amount');
            $interestAmount = Registry::getSession()->getVariable('pi_ratepay_rate_interest_amount');
            $service_charge = Registry::getSession()->getVariable('pi_ratepay_rate_service_charge');
            $annualPercentageRate = Registry::getSession()->getVariable('pi_ratepay_rate_annual_percentage_rate');
            $monthlyDebitInterest = Registry::getSession()->getVariable('pi_ratepay_rate_monthly_debit_interest');
            $numberOfRates = Registry::getSession()->getVariable('pi_ratepay_rate_number_of_rates');
            $rate = Registry::getSession()->getVariable('pi_ratepay_rate_rate');
            $lastRate = Registry::getSession()->getVariable('pi_ratepay_rate_last_rate');

            $ratepayRateDetails = oxNew(RateDetails::class);
            $ratepayRateDetails->loadByOrderId($id);

            $ratepayRateDetails->assign([
                'orderid' => $id,
                'totalamount' => $totalAmount,
                'amount' => $amount,
                'interestamount' => $interestAmount,
                'servicecharge' => $service_charge,
                'annualpercentagerate' => $annualPercentageRate,
                'monthlydebitinterest' => $monthlyDebitInterest,
                'numberofrates' => $numberOfRates,
                'rate' => $rate,
                'lastrate' => $lastRate
            ]);

            $ratepayRateDetails->save();
        }

        if ($this->_paymentId === 'pi_ratepay_rate0') {
            $totalAmount = Registry::getSession()->getVariable('pi_ratepay_rate0_total_amount');
            $amount = Registry::getSession()->getVariable('pi_ratepay_rate0_amount');
            $interestAmount = Registry::getSession()->getVariable('pi_ratepay_rate0_interest_amount');
            $service_charge = Registry::getSession()->getVariable('pi_ratepay_rate0_service_charge');
            $annualPercentageRate = Registry::getSession()->getVariable('pi_ratepay_rate0_annual_percentage_rate');
            $monthlyDebitInterest = Registry::getSession()->getVariable('pi_ratepay_rate0_monthly_debit_interest');
            $numberOfRates = Registry::getSession()->getVariable('pi_ratepay_rate0_number_of_rates');
            $rate = Registry::getSession()->getVariable('pi_ratepay_rate0_rate');
            $lastRate = Registry::getSession()->getVariable('pi_ratepay_rate0_last_rate');

            $ratepayRateDetails = oxNew(RateDetails::class);
            $ratepayRateDetails->loadByOrderId($id);

            $ratepayRateDetails->assign([
                'orderid' => $id,
                'totalamount' => $totalAmount,
                'amount' => $amount,
                'interestamount' => $interestAmount,
                'servicecharge' => $service_charge,
                'annualpercentagerate' => $annualPercentageRate,
                'monthlydebitinterest' => $monthlyDebitInterest,
                'numberofrates' => $numberOfRates,
                'rate' => $rate,
                'lastrate' => $lastRate
            ]);

            $ratepayRateDetails->save();
        }

        $this->saveRatepayBasketItems($id, $oOrder);
    }

    /**
     * Save basket items information to ratepay order details tables in the db.
     *
     * @param string $id
     * @param string $oOrder
     */
    private function saveRatepayBasketItems($id, $oOrder)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->delete('pi_ratepay_order_details')
            ->where('order_number = :ordernr')
            ->setParameter(':ordernr', $id);
        $oQueryBuilder->execute();

        $oBasket = Registry::getSession()->getBasket();
        foreach ($oOrder->getOrderArticles() AS $article) {
            $articlenumber = $article->oxorderarticles__oxartid->value;
            $quantity = $article->oxorderarticles__oxamount->value;
            $this->saveToRatepayOrderDetails($id, $articlenumber, $article->getId(), $quantity);
        }

        $specialItems = ['oxwrapping', 'oxgiftcard', 'oxdelivery', 'oxpayment', 'oxtsprotection'];
        foreach ($specialItems as $articleNumber) {
            $this->checkBasketCosts($id, $articleNumber);
        }

        if ($oBasket->getVouchers()) {
            foreach ($oBasket->getVouchers() as $voucher) {
                $articlenumber = $voucher->sVoucherId;
                $quantity = 1;
                $this->saveToRatepayOrderDetails($id, $articlenumber, $articlenumber, $quantity);
            }
        }

        if ($oBasket->getDiscounts()) {
            foreach ($oBasket->getDiscounts() as $discount) {
                $this->saveToRatepayOrderDetails($id, $discount->sOXID, $discount->sOXID, 1, $discount->dDiscount * -1);
            }
        }
    }

    /**
     * Log Basket costs to RatePAY order details.
     * @param string $id
     * @param string $articleNumber
     */
    private function checkBasketCosts($id, $articleNumber)
    {
        $articlePrice = Registry::getSession()->getBasket()->getCosts($articleNumber);
        if ($articlePrice instanceof Price && $articlePrice->getBruttoPrice() > 0) {
            $this->saveToRatepayOrderDetails($id, $articleNumber, $articleNumber, 1, $articlePrice->getNettoPrice(), round($articlePrice->getVat()));
        }
    }

    /**
     * Save to order details.
     * @param string $id
     * @param string $articleNumber
     * @param string $uniqueArticleNumber
     * @param int $quantity
     */
    private function saveToRatepayOrderDetails($id, $articleNumber, $uniqueArticleNumber, $quantity, $price = 0, $vat = 0)
    {
        $ratepayOrderDetails = oxNew(OrderDetails::class);

        $ratepayOrderDetails->assign([
            'order_number' => $id,
            'article_number' => $articleNumber,
            'unique_article_number' => $uniqueArticleNumber,
            'price' => $price,
            'vat' => $vat,
            'ordered' => $quantity
        ]);

        $ratepayOrderDetails->save();
    }
}
