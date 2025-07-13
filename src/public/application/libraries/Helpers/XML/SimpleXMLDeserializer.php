<?php

class SimpleXMLDeserializer
{
    protected $object;
    protected $deserializedObject;

    protected $nodeCanBeList = [];
    protected $mapNodeToList = [];

    protected $stopDeserializeRecursion = false;

    public function __construct()
    {
        $this->object = new stdClass();
    }

    /**
     * @param array|SimpleXMLWrapper $simpleXML
     * @param $structNodeList
     * @return object
     */
    public function deserialize($simpleXML, $structNodeList = null)
    {
        if (is_array($simpleXML) && is_object($simpleXML[0])) {
            $this->deserializedObject = (object)$simpleXML;
            return (object)$simpleXML;
        }

        if ($simpleXML->children()) {
            $nodeName = $simpleXML->getName();
            $this->object->{$nodeName} = (object)($simpleXML->getAttributes());
            foreach ($simpleXML->children() as $node => $child) {
                if($this->stopDeserializeRecursion) {
                    break;
                }
                $nodeChild = $this->deserialize($child, $structNodeList);
                if (!empty((array)($this->object->{$nodeName}->{$node} ?? []))) {
                    $existsNode = $this->object->{$nodeName}->{$node};
                    $this->object->{$nodeName}->{$node} = is_array($this->object->{$nodeName}->{$node})
                        ? $this->object->{$nodeName}->{$node} : [$existsNode];
                    array_push($this->object->{$nodeName}->{$node}, $nodeChild);
                    $structNodeList = (object)['nodeParent' => $nodeName, 'nodeChild' => $node];
                    continue;
                }
                if (in_array($node, $this->nodeCanBeList)) {
                    $this->object->{$nodeName}->{$node} = [$nodeChild];
                    $structNodeList = (object)['nodeParent' => $nodeName, 'nodeChild' => $node];
                    continue;
                }
                $structNodeList = null;
                $this->object->{$nodeName}->{$node} = $nodeChild;
            }
            if (!empty((array)$structNodeList) && (
                    $structNodeList->nodeParent == $nodeName
                )) {
                if ((count((array)$this->object->{$structNodeList->nodeParent}) > 1)
                    && (array_key_exists($structNodeList->nodeChild, $this->mapNodeToList))) {
                    $this->object->{$structNodeList->nodeParent}->{$this->mapNodeToList[$structNodeList->nodeChild]} = $this->object->{$structNodeList->nodeParent}->{$structNodeList->nodeChild};
                    unset($this->object->{$structNodeList->nodeParent}->{$structNodeList->nodeChild});
                } else {
                    $this->object->{$structNodeList->nodeParent} = $this->object->{$structNodeList->nodeParent}->{$structNodeList->nodeChild};
                }
            }
            $this->deserializedObject = $this->object->{$nodeName};
            return $this->object->{$nodeName};
        }
        return (object)($simpleXML->getAttributes());
    }

    public function getDeserializedObject(): stdClass
    {
        return $this->deserializedObject;
    }
}