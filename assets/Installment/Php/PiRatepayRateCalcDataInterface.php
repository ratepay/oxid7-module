<?php

namespace pi\ratepay\Installment\Php;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface for methods which need a shop specific implementation.
 */
interface PiRatepayRateCalcDataInterface
{

    /**
     * Get merchant profile-id from shop.
     */
    public function getProfileId();

    /**
     * Get merchant security code from shop.
     */
    public function getSecurityCode();

    /**
     * Is RatePAY modul set to live in shop.
     */
    public function isLive();

    /**
     * Get merchant security code from shop an hash it.
     * @deprecated Modules hash security code by now, will be removed in the future.
     */
    public function getSecurityCodeHashed();

    /**
     * Get order transaction id from shop.
     */
    public function getTransactionId();

    /**
     * Get order transaction short id from shop.
     * @deprecated Here for backwards compatibility, will be removed in the future.
     */
    public function getTransactionShortId();

    /**
     * Get order id from shop.
     */
    public function getOrderId();

    /**
     * Get merchant consumer id from shop.
     */
    public function getMerchantConsumerId();

    /**
     * Get merchant classification from shop.
     */
    public function getMerchantConsumerClassification();

    /**
     * Get order amount from shop.
     */
    public function getAmount();

    /**
     * Get rate data from shop session.
     */
    public function getData();

    /**
     * Is payment first day set to allowed in shop config.
     */
    public function getPaymentFirstdayConfig();

    /**
     * Set rate data to shop session.
     */
    public function setData(
        $total_amount,
        $amount,
        $interest_rate,
        $interest_amount,
        $service_charge,
        $annual_percentage_rate,
        $monthly_debit_interest,
        $number_of_rates,
        $rate,
        $last_rate,
        $payment_firstday,
        $bank_iban
    );

    /**
     * Unset rate data from shop session.
     */
    public function unsetData();

    /**
     * Shop specific implementation to access GET-Request parameter.
     */
    public function getGetParameter($var);

    /**
     * Shop specific implementation to access POST-Request parameter.
     */
    public function getPostParameter($var);
}
