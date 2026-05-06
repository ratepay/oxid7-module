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
        return $this->getUser()->getFieldData('oxcustnr');
    }

    /**
     * Get fax number of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerFax()
    {
        $fax = empty($this->getUser()->getFieldData('oxfax')) ? false : $this->getUser()->getFieldData('oxfax');

        return $fax;
    }

    /**
     * Get mobile number of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerMobilePhone()
    {
        $mobilePhone = empty($this->getUser()->getFieldData('oxmobfon')) ? false : $this->getUser()->getFieldData('oxmobfon');

        return $mobilePhone;
    }

    /**
     * Get phone number of customer, or false if customer has none.
     * @return string|boolean
     */
    public function getCustomerPhone()
    {
        $phone = false;

        if (!empty($this->getUser()->getFieldData('oxfon')) || !empty($this->getUser()->getFieldData('oxprivfon'))) {
            if (!empty($this->getUser()->getFieldData('oxfon'))) {
                $phone = $this->getUser()->getFieldData('oxfon');
            } else {
                $phone = $this->getUser()->getFieldData('oxprivfon');
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
            ->setParameter(':oxid', $this->getUser()->getFieldData('oxcountryid'));
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();

        $address = [
            'street'            => $this->getUser()->getFieldData('oxstreet'),
            'street-additional' => $this->getUser()->getFieldData('oxaddinfo'),
            'street-number'     => $this->getUser()->getFieldData('oxstreetnr'),
            'zip-code'          => $this->getUser()->getFieldData('oxzip'),
            'city'              => $this->getUser()->getFieldData('oxcity'),
            'country-code'      => $sCountryCode,
        ];

        return $address;
    }

    /**
     * Get complete delivery address.
     * @return array|false
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
            ->setParameter(':oxid', $deliveryAddress->getFieldData('oxcountryid'));
        $sCountryCode = $oQueryBuilder->execute();
        $sCountryCode = $sCountryCode->fetchOne();

        $address = [
            'first-name'    => $deliveryAddress->getFieldData('oxfname'),
            'last-name'     => $deliveryAddress->getFieldData('oxlname'),
            'company'       => $deliveryAddress->getFieldData('oxcompany'),
            'street'        => $deliveryAddress->getFieldData('oxstreet'),
            'street-number' => $deliveryAddress->getFieldData('oxstreetnr'),
            'zip-code'      => $deliveryAddress->getFieldData('oxzip'),
            'city'          => $deliveryAddress->getFieldData('oxcity'),
            'country-code'  => $sCountryCode,
        ];

        return $address;
    }

    /**
     * Get company name of customer, or false if customer has none.
     * @return string|false
     */
    public function getCustomerCompanyName()
    {
        $company = false;

        if ($this->getUser()->getFieldData('oxcompany') != '' && $this->getUser()->getFieldData('oxustid') != '') {
            $company = $this->getUser()->getFieldData('oxcompany');
        }

        return $company;
    }

    /**
     * Get customers date of birth
     * @return string
     */
    public function getCustomerDateOfBirth()
    {
        return $this->getUser()->getFieldData('oxbirthdate');
    }

    /**
     * Get customers first name
     * @return string
     */
    public function getCustomerFirstName()
    {
        return $this->getUser()->getFieldData('oxfname');
    }

    /**
     * Get customers last name
     * @return string
     */
    public function getCustomerLastName()
    {
        return $this->getUser()->getFieldData('oxlname');
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
     * @return string|false
     */
    public function getCustomerVatId()
    {
        $vatId = false;

        if ($this->getUser()->getFieldData('oxcompany') != '' &&
            $this->getUser()->getFieldData('oxustid') != '') {
            $vatId = $this->getUser()->getFieldData('oxustid');
        }

        return $vatId;
    }

    /**
     * Get customers e-mail
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getUser()->getFieldData('oxusername');
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
        $salutation = strtoupper($this->getUser()->getFieldData('oxsal'));
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
                $owner = $this->getUser()->getFieldData('oxcompany');
            } else {
                $owner = $this->getCustomerFirstName() . ' ' . $this->getCustomerLastName();
            }
        }

        return $owner;
    }
}
