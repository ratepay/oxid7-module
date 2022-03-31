<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\Registry;
use pi\ratepay\Application\Model\Logs;
use pi\ratepay\Application\Model\LogsList;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category  PayIntelligent
 * @package   PayIntelligent_RatePAY
 * @copyright (C) 2011 PayIntelligent GmbH  <http://www.payintelligent.de/>
 * @license	http://www.gnu.org/licenses/  GNU General Public License 3
 */

/**
 * Logging service class
 * @extends Base
 */
class LogsService extends Base
{

    /**
     * LogsService class instance.
     *
     * @var LogsService instance
     */
    private static $_instance = null;

    /**
     * Returns object instance of LogsService
     *
     * @return LogsService
     */
    public static function getInstance()
    {
        // disable caching for test modules
        if (defined('OXID_PHP_UNIT')) {
            self::$_instance = modInstances::getMod(__CLASS__);
        }

        if (!self::$_instance instanceof LogsService) {
            self::$_instance = oxNew(LogsService::class);

            if (defined('OXID_PHP_UNIT')) {
                modInstances::addMod(__CLASS__, self::$_instance);
            }
        }
        return self::$_instance;
    }

    /**
     * Logs Ratepay requests and responses to database
     *
     * @param string $orderId
     * @param string $transactionId
     * @param string $paymentMethod
     * @param string $paymentType
     * @param string $paymentSubtype
     * @param string $name
     * @param string $surname
     * @param object $trans
     *
     * @return mixed Logs or null
     */
    public function logRatepayTransaction($orderId, $transactionId, $paymentMethod, $paymentType, $paymentSubtype, $name, $surname, $trans)
    {
        $util = oxNew(Utilities::class);
        $paymentMethod =  $util->getPaymentMethod($paymentMethod);

        $logging = $this->_getLogSettings();

        if ($logging == 1) {
            $requestXml = $trans->getRequestRaw();
            $responseXml = $trans->getResponseRaw();
            $reason = '';
            $reasonCode = '';
            $result = '';
            $resultCode = '';
            $status = '';
            $statusCode = '';
            $reference = '';

            if ($trans->isSuccessful()) {
                $result = (string) $trans->getResultMessage();
                $resultCode = (string) $trans->getResultCode();
                $reason = (string) $trans->getReasonMessage();
                $reasonCode = (string) $trans->getResultCode();
                $status = (string) $trans->getStatusMessage();
                $statusCode = (string) $trans->getStatusCode();

                if ($paymentType == 'PAYMENT_REQUEST') {
                    $reference =  (string) $trans->getDescriptor();
                }
            } else {
                $result = $trans->getReasonMessage();
                $resultCode = $result;
            }

            $logEntry = oxNew(Logs::class);

            $oUtilsDate = Registry::get("oxUtilsDate");

            $logEntry->assign(array(
                'order_number'    => $orderId,
                'transaction_id'  => $transactionId,
                'payment_method'  => $paymentMethod,
                'payment_type'    => $paymentType,
                'payment_subtype' => $paymentSubtype,
                'result'          => $result,
                'request'         => $requestXml,
                'response'        => $responseXml,
                'result_code'     => $resultCode,
                'first_name'      => $name,
                'last_name'       => $surname,
                'reason'          => $reason,
                'reason_code'     => $reasonCode,
                'status'          => $status,
                'status_code'     => $statusCode,
                'reference'       => $reference,
                'date'            => date('Y-m-d H:i:s', $oUtilsDate->getTime())
            ));

            $logEntry->save();
            return $logEntry;
        }

        return null;
    }

    /**
     * Get either a complete List of Log entries or a List of Log entries filtered by a where clause.
     *
     * @param string $where optional, defaults to null
     * @return LogsList
     */
    public function getLogsList($where = null, $order = null)
    {
        $ratepayLogsList = oxNew(LogsList::class);

        if ($where === null && $order === null)
            return $ratepayLogsList->getList();

        return $ratepayLogsList->getFilteredList($where, $order);
    }

    /**
     * Get RatePAY Log Settings
     * @return int
     */
    private function _getLogSettings()
    {
        $oConfig = Registry::getConfig();
        $iRPLogging = (int) $oConfig->getConfigParam('blRPLogging');

        return $iRPLogging;
    }

}
