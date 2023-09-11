<?php

namespace pi\ratepay\Core;

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
 * Description of pi_ratepay_util
 *
 * @author enes
 */
class Utilities
{

    /**
     * Static array of RatePAY payment methods.
     * @var array
     */
    public static $_RATEPAY_PAYMENT_METHOD = [
        'pi_ratepay_rechnung',
        'pi_ratepay_rate',
        'pi_ratepay_rate0',
        'pi_ratepay_elv'
    ]; // 'pi_ratepay_vorkasse'
    public static $_RATEPAY_PAYMENT_METHOD_NAMES = [
        'invoice' => "rechnung",
        'installment' => "rate",
        'installment0' => "rate0",
        'elv' => "elv"
    ]; // 'prepayment' => "vorkasse"

    public static $_RATEPAY_PRIVACY_NOTICE_URL_DACH = 'https://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-dach';

    public static $_RATEPAY_PRIVACY_NOTICE_URL_NL =  'https://www.ratepay.com/aanvullende-voorwaarden-en-privacybeleid-nl';


    /**
     * Static array of supported countries.
     * @var array
     */
    public static $_RATEPAY_ALLOWED_COUNTRIES = ['de', 'at', 'ch', 'nl'];

    public static function getPaymentMethod($paymentType)
    {
        $paymentMethod = null;
        switch ($paymentType) {
            case 'pi_ratepay_rechnung':
                $paymentMethod = 'INVOICE';
                break;
            case 'pi_ratepay_rate':
                $paymentMethod = 'INSTALLMENT';
                break;
            case 'pi_ratepay_rate0':
                $paymentMethod = 'INSTALLMENT0';
                break;
            case 'pi_ratepay_elv':
                $paymentMethod = 'ELV';
                break;
            default:
                break;
        }

        return $paymentMethod;
    }

    public static function getCountry($sCountryId)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $sCountryId);
        $sOXISOALPHA2 = $oQueryBuilder->execute();
        $sOXISOALPHA2 = $sOXISOALPHA2->fetchOne();
        return strtolower($sOXISOALPHA2);
    }

    /**
     * Get formattet number
     * @param string $str
     * @param int $decimal
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    public function getFormattedNumber($str, $decimal = 2, $dec_point = ".", $thousands_sep = "") {
        if(strstr($str, ",")) {
            $str = str_replace(".", "", $str); // replace dots (thousand seps) with blancs
            $str = str_replace(",", ".", $str); // replace ',' with '.'
        }

        return number_format($str, $decimal, $dec_point, $thousands_sep);
    }
}
