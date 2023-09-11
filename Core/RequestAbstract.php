<?php

namespace pi\ratepay\Core;

use OxidEsales\Eshop\Core\Base;
use pi\ratepay\Extend\Application\Model\RatepayOxorder;
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
 * Abstract class for RatePAY Request data providers
 * @extends Base
 */
abstract class RequestAbstract extends Base
{
    /**
     * Get customer number.
     * @return string|boolean
     */
    public function getCustomerNumber()
    {
        return $this->getUser()->oxuser__oxcustnr->value;
    }

    /**
     * Get fax number of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerFax()
    {
        $fax = empty($this->getUser()->oxuser__oxfax->value) ? false : $this->getUser()->oxuser__oxfax->value;

        return $fax;
    }

    /**
     * Get mobile number of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerMobilePhone()
    {
        $mobilePhone = empty($this->getUser()->oxuser__oxmobfon->value) ? false : $this->getUser()->oxuser__oxmobfon->value;

        return $mobilePhone;
    }

    /**
     * Get phone number of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerPhone()
    {
        $phone = false;

        if (!empty($this->getUser()->oxuser__oxfon->value) || !empty($this->getUser()->oxuser__oxprivfon->value)) {
            if (!empty($this->getUser()->oxuser__oxfon->value)) {
                $phone = $this->getUser()->oxuser__oxfon->value;
            } else {
                $phone = $this->getUser()->oxuser__oxprivfon->value;
            }
        }


        return $phone;
    }

    /**
     * Get complete customer address.
     * @return array
     */
    public function getCustomerAddress()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $this->getUser()->oxuser__oxcountryid->value);
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();

        $address = [
            'street'            => $this->getUser()->oxuser__oxstreet->value,
            'street-additional' => $this->getUser()->oxuser__oxaddinfo->value,
            'street-number'     => $this->getUser()->oxuser__oxstreetnr->value,
            'zip-code'      => $this->getUser()->oxuser__oxzip->value,
            'city'          => $this->getUser()->oxuser__oxcity->value,
            'country-code'  => $sCountryCode
        ];

        return $address;
    }

    /**
     * Get complete delivery address.
     * @return array
     */
    public function getDeliveryAddress()
    {
        $order = oxNew(RatepayOxorder::class);
        $deliveryAddress = $order->getDelAddressInfo();

        if (is_null($deliveryAddress)){
            return false;
        }

        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $deliveryAddress->oxaddress__oxcountryid->value);
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();

        $address = [
            'first-name'    => $deliveryAddress->oxaddress__oxfname->value,
            'last-name'     => $deliveryAddress->oxaddress__oxlname->value,
            'company'       => $deliveryAddress->oxaddress__oxcompany->value,
            'street'        => $deliveryAddress->oxaddress__oxstreet->value,
            'street-number' => $deliveryAddress->oxaddress__oxstreetnr->value,
            'zip-code'      => $deliveryAddress->oxaddress__oxzip->value,
            'city'          => $deliveryAddress->oxaddress__oxcity->value,
            'country-code'  => $sCountryCode
        ];

        return $address;
    }

    /**
     * Get company name of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerCompanyName()
    {
        $company = false;

        if ($this->getUser()->oxuser__oxcompany->value != '' && $this->getUser()->oxuser__oxustid->value != '') {
            $company = $this->getUser()->oxuser__oxcompany->value;
        }

        return $company;
    }

    /**
     * Get customers date of birth
     * @return string
     */
    public function getCustomerDateOfBirth()
    {
        return $this->getUser()->oxuser__oxbirthdate->value;
    }

    /**
     * Get customers first name
     * @return string
     */
    public function getCustomerFirstName()
    {
        return $this->getUser()->oxuser__oxfname->value;
    }

    /**
     * Get customers last name
     * @return string
     */
    public function getCustomerLastName()
    {
        return $this->getUser()->oxuser__oxlname->value;
    }

    /**
     * Get where customer lives.
     * @return string
     */
    public function getCustomerNationality()
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select('OXISOALPHA2')
            ->from('oxcountry')
            ->where('OXID = :oxid')
            ->setParameter(':oxid', $this->getUser()->oxuser__oxcountryid->value);
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();
        return $sCountryCode;
    }

    /**
     * Get vat id of customers company, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerVatId()
    {
        $vatId = false;

        if ($this->getUser()->oxuser__oxcompany->value != '' && $this->getUser()->oxuser__oxustid->value != '') {
            $vatId = $this->getUser()->oxuser__oxustid->value;
        }

        return $vatId;
    }

    /**
     * Get customers e-mail
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getUser()->oxuser__oxusername->value;
    }

    /**
     * Get customers bank-data, owner can be retrieved either in session or if not set in $this->getUser().
     * @todo bank data persistence
     * @todo validate if bankdata is in session
     * @return array
     */
    public function getCustomerBankdata($paymentType)
    {
        $bankData          = [];
        $bankDataType      = Registry::getSession()->getVariable($paymentType . '_bank_datatype');
        $bankAccountNumber = Registry::getSession()->getVariable($paymentType . '_bank_account_number');
        $bankCode          = Registry::getSession()->getVariable($paymentType . '_bank_code');
        $bankIban          = Registry::getSession()->getVariable($paymentType . '_bank_iban');

        if ($bankDataType == 'classic') {
            $bankData['bankAccountNumber'] = $bankAccountNumber;
            $bankData['bankCode']          = $bankCode;
        } else {
            $bankData['bankIban'] = $bankIban;
        }

        return $bankData;
    }

    /**
     * Get customers gender, or 'U' (unknown) if none set.
     * @return string
     */
    public function getGender()
    {
        $salutation = strtoupper($this->getUser()->oxuser__oxsal->value);
        switch ($salutation) {
            default:
                $gender = 'U';
                break;
            case 'MR':
                $gender = 'M';
                break;
            case 'MRS':
                $gender = 'F';
                break;
        }

        return $gender;
    }

    protected function getOwner($paymentType)
    {
        $elvUseCompany = Registry::getSession()->getVariable('elv_use_company_name');

        $owner = null;
        if (Registry::getSession()->hasVariable($paymentType . '_bank_owner')) {
            $owner = Registry::getSession()->getVariable($paymentType . 'elv_bank_owner');
        } else {
            if (!empty($elvUseCompany) && $elvUseCompany == 1) {
                $owner = $this->getUser()->oxuser__oxcompany->value;
            } else {
                $owner = $this->getCustomerFirstName() . ' ' . $this->getCustomerLastName();
            }
        }

        return $owner;
    }
}
