<?php

namespace pi\ratepay\Pi\Util\Encryption;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * creates private key
 */
abstract class EncryptionAbstract
{
    /**
     * Service responsible for private key handling.
     * @var PrivateKey
     */
    private $_privateKeyService;

    private $_privateKey;

    protected $_tableName = 'pi_ratepay_debit_details';

    public function __construct(PrivateKey $privateKeyService = null)
    {
        $this->_privateKeyService = isset($privateKeyService)? $privateKeyService : oxNew(PrivateKey::class);
        $this->_privateKey = $this->_privateKeyService->getPrivateKey();
    }

    public function loadBankdata($sUserId)
    {
        $aSelectSql = $this->createBankdataSelectSql($sUserId);
        $aBankdata = $this->selectBankdataFromDatabase($aSelectSql);

        return $aBankdata;
    }

    public function saveBankdata($sUserId, array $aBankdata)
    {
        if ($this->isBankdataSetForUser($sUserId)) {
            $aSaveSql = $this->createBankdataUpdateSql($sUserId, $aBankdata);
        } else {
            $aSaveSql = $this->createBankdataInsertSql($sUserId, $aBankdata);
        }
        $this->insertBankdataToDatabase($aSaveSql);
    }

    private function createBankdataInsertSql($sUserId, array $aBankdata)
    {
        $aValues = [
            'userid' => ':userid'
        ];
        $sKey = $this->_privateKey;

        foreach($aBankdata as $columnName => $columnValue) {
            $aValues[$columnName] = "AES_ENCRYPT('" . $this->convertBinaryToHex($columnValue) . "', '" . $sKey . "')";
        }

        $aInsertSql['insert'] = [
            'table' => $this->_tableName,
            'values' => $aValues,
            'setParameter' => [
                'name' => ':userid',
                'value' => $sUserId
            ]
        ];

        return $aInsertSql;
    }

    private function createBankdataUpdateSql($sUserId, array $aBankdata)
    {
        $sKey = $this->_privateKey;
        $aSet = [];

        foreach($aBankdata as $columnName => $columnValue) {
            $aTmpArr = [
                'key' => $columnName,
                'value' => "AES_ENCRYPT('" . $this->convertBinaryToHex($columnValue) . "', '" . $sKey . "')",
            ];
            array_push($aSet, $aTmpArr);
        }

        $aUpdateSql['update'] = [
            'table' => $this->_tableName,
            'set' => $aSet,
            'where' => 'userid = :userid',
            'setParameter' => [
                'name' => ':userid',
                'value' => $sUserId
            ]
        ];

        return $aUpdateSql;
    }

    public function isBankdataSetForUser($sUserId)
    {
        $aUserSql['select'] = [
            'var' => 'userid',
            'from' => $this->_tableName,
            'where' => 'userid = :userid',
            'setParameter' => [
                'name' => ':userid',
                'value' => $sUserId
            ]
        ];
        $sUserIdStoredInDb = $this->selectUserIdFromDatabase($aUserSql);

        return $sUserId === $sUserIdStoredInDb;
    }

    private function createBankdataSelectSql($sUserId)
    {
        $key = $this->_privateKey;
        $aSelectSql['select'] = [
            'var' => [
                0 => 'userid',
                1 => "AES_DECRYPT(owner, '$key') as decrypt_owner",
                2 => "AES_DECRYPT(accountnumber, '$key') as decrypt_accountnumber",
                3 => "AES_DECRYPT(bankcode, '$key') as decrypt_bankcode"
            ],
            'from' => $this->_tableName,
            'where' => 'userid = :userid',
            'setParameter' => [
                'name' => ':userid',
                'value' => $sUserId
            ]
        ];

        return $aSelectSql;
    }
    
    protected function convertBinaryToHex($value)
    {
        $toHex = bin2hex($value);
        
        return $toHex;
    }
    
    protected function convertHexToBinary($value)
    {
        $toBinary = pack("H*", $value);
        
        return $toBinary;
    }

    abstract protected function insertBankdataToDatabase($insertSql);

    abstract protected function selectBankdataFromDatabase($aSelectSql);

    abstract protected function selectUserIdFromDatabase($userSql);
}
