<?php

class SimpleXMLWrapper extends SimpleXMLElement
{
    public function getAttributeByName($name)
    {
        $nodeAttributes = $this->getAttributes();
        return $nodeAttributes[$name] ?? null;
    }

    public function getAttributes()
    {
        $attrs = (array)$this->attributes();
        return $attrs['@attributes'] ?? [];
    }

    public static function loadFile(string $filePath): SimpleXMLWrapper
    {
        if (!file_exists($filePath)) {
            throw new Exception("Arquivo {$filePath} não encontrado.");
        }
        try {
            return simplexml_load_file(
                $filePath,
                SimpleXMLWrapper::class
            );
        } catch (Throwable $e) {
            throw new Exception("Arquivo {$filePath} corrompido.");
        }
    }
}