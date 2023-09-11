<?php

namespace pi\ratepay\Pi\Util\Encryption;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class OxEncryption extends EncryptionAbstract
{

    protected function insertBankdataToDatabase($aInsertSql)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();

        if(array_keys($aInsertSql) == 'update') {
            $oQueryBuilder->update($aInsertSql['update']['table']);
            foreach ($aInsertSql['update']['set'] as $aSet) {
                $oQueryBuilder->set($aSet['key'], $aSet['value']);
            }
            $oQueryBuilder
                ->where('userid = :userid')
                ->setParameter(
                    $aInsertSql['update']['setParameter']['name'],
                    $aInsertSql['update']['setParameter']['value']
                );
        } elseif (array_keys($aInsertSql) == 'insert') {
            $oQueryBuilder
                ->insert($aInsertSql['insert']['table'])
                ->values($aInsertSql['insert']['values'])
                ->setParameter(
                    $aInsertSql['insert']['setParameter']['name'],
                    $aInsertSql['insert']['setParameter']['value']
                );
        }

        $oQueryBuilder->execute();
    }

    protected function selectBankdataFromDatabase($aSelectSql)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select(
                $aSelectSql['select']['var'][0],
                $aSelectSql['select']['var'][1],
                $aSelectSql['select']['var'][2],
                $aSelectSql['select']['var'][3]
            )
            ->from($aSelectSql['select']['from'])
            ->where($aSelectSql['select']['where'])
            ->setParameter(
                $aSelectSql['select']['setParameter']['name'],
                $aSelectSql['select']['setParameter']['value']
            );
        $aSqlResult = $oQueryBuilder->execute();
        $aSqlResult = $aSqlResult->fetchAllAssociative();

        $aBankdata = [];

        foreach ($aSqlResult as $sUserId => $aDecryptedData) {
            $aBankdata = [
                'userid' => $sUserId,
                'owner' => $this->convertHexToBinary($aDecryptedData[0]),
                'accountnumber' => $this->convertHexToBinary($aDecryptedData[1]),
                'bankcode' => $this->convertHexToBinary($aDecryptedData[2]),
            ];
        }

        return $aBankdata;
    }
    
    protected function selectUserIdFromDatabase($aUserSql)
    {
        $oContainer = ContainerFactory::getInstance()->getContainer();
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $oQueryBuilderFactory = $oContainer->get(QueryBuilderFactoryInterface::class);
        $oQueryBuilder = $oQueryBuilderFactory->create();
        $oQueryBuilder
            ->select($aUserSql['select']['var'])
            ->from($aUserSql['select']['from'])
            ->where($aUserSql['select']['where'])
            ->setParameter($aUserSql['select']['setParameter']['name'], $aUserSql['select']['setParameter']['value']);
        $sUserId = $oQueryBuilder->execute();
        $sUserId = $sUserId->fetchOne();

        return $sUserId;
    }

}
