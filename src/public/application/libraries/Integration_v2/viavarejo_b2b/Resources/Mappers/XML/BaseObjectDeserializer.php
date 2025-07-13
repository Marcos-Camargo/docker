<?php

namespace Integration_v2\viavarejo_b2b\Resources\Mappers\XML;

use Exception;
use SimpleXMLWrapper;

class BaseObjectDeserializer extends \SimpleXMLDeserializer
{
    protected $flagIdAttributeName = 'IdLojista';
    protected $flagIdAttributeValue = 0;

    protected $creationDateAttributeName = 'DataCriacao';
    protected $creationDateAttributeValue = null;

    protected $limitProcessing = false;

    public function getFlagIdAttributeValue(): int
    {
        return $this->flagIdAttributeValue;
    }

    public function setCreationDateAttributeValue(string $date)
    {
        $this->creationDateAttributeValue = $date;
    }

    public function getCreationDateAttributeValue(): string
    {
        $date = explode(' ', $this->creationDateAttributeValue ?? '');
        $this->creationDateAttributeValue = !empty($this->creationDateAttributeValue) ? date('Y-m-d', strtotime(dateBrazilToDateInternational($date[0]))) . " {$date[1]}" : null;
        return $this->creationDateAttributeValue ?? 'N/A';
    }

    /**
     * @param array|SimpleXMLWrapper $simpleXML
     * @param null $structNodeList
     */
    public function deserialize($simpleXML, $structNodeList = null)
    {
        if ($this->limitProcessing) {
            $this->limitDeserializationProcessing();
        }

        if (is_array($simpleXML) && is_object($simpleXML[0])) {
            try {
                $flagId = 0;
                if (isset($simpleXML[0]->Skus[0]->{$this->flagIdAttributeName})) {
                    $flagId = $simpleXML[0]->Skus[0]->{$this->flagIdAttributeName};
                } elseif (isset($simpleXML[0]->{$this->flagIdAttributeName})) {
                    $flagId = $simpleXML[0]->{$this->flagIdAttributeName};
                }

                $this->flagIdAttributeValue = (int)$flagId;
            } catch (Exception $exception) {
                $this->flagIdAttributeValue = 0;
            }
        } else {
            if (!$this->flagIdAttributeValue && $simpleXML->getAttributeByName($this->flagIdAttributeName)) {
                $this->flagIdAttributeValue = (int)$simpleXML->getAttributeByName($this->flagIdAttributeName);
            }
            if (!$this->creationDateAttributeValue && $simpleXML->getAttributeByName($this->creationDateAttributeName)) {
                $this->creationDateAttributeValue = $simpleXML->getAttributeByName($this->creationDateAttributeName);
            }
        }
        return parent::deserialize($simpleXML, $structNodeList);
    }

    public function setLimitProcessing(bool $limitProcessing = true)
    {
        $this->limitProcessing = $limitProcessing;
        return $this;
    }

    protected function limitDeserializationProcessing()
    {
        if ($this->flagIdAttributeValue > 0) {
            $this->stopDeserializeRecursion = true;
        }
    }
}