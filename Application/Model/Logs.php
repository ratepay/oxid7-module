<?php

namespace pi\ratepay\Application\Model;

use OxidEsales\Eshop\Core\Model\BaseModel;

/**
 *
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Model class for pi_ratepay_logs table
 * @extends BaseModel
 */
class Logs extends BaseModel
{

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = Logs::class;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('pi_ratepay_logs');
    }

    protected function getFormattedXml($oField)
    {
        $oSimpleXml = simplexml_load_string($oField->rawValue);
        if ($oSimpleXml === false) {
            return $oField->value;
        }

        $dom = dom_import_simplexml($oSimpleXml);
        if (!$dom) {
            return $oField->value;
        }

        $dom = $dom->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    public function getFormattedRequest()
    {
        return htmlentities($this->getFormattedXml($this->pi_ratepay_logs__request));
    }

    public function getFormattedResponse()
    {
        return htmlentities($this->getFormattedXml($this->pi_ratepay_logs__response));
    }

    /**
     * Returns true in case the item represented by this object is derived from parent shop
     *
     * @return bool|false
     */
    public function blIsDerived(){
        if ($this->isDerived() == null) {
            return false;
        }
        return $this->isDerived();
    }

}
