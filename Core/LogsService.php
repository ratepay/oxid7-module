<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Core\Base;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use pi\ratepay\Application\Model\Logs;
use pi\ratepay\Application\Model\LogsList;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

        $logging = $this->getLogSettings();

        if ($logging == 1) {
            $requestXml = $trans->getRequestRaw();
            $requestXml = preg_replace("/<securitycode>(.*)<\/securitycode>/", '<securitycode>***</securitycode>', $requestXml);
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

            $logEntry->assign([
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
            ]);

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
    private function getLogSettings()
    {
        $moduleSettingService = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ModuleSettingServiceInterface::class);
        $iRPLogging = (int) $moduleSettingService->getBoolean('blRPLogging', 'ratepay');

        return $iRPLogging;
    }

}
