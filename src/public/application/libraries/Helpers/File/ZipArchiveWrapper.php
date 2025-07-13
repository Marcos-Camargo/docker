<?php


class ZipArchiveWrapper extends ZipArchive
{

    private $fullFilePath;

    private $fileName;

    protected function loadFileInfo($fullFilePath)
    {
        $this->fullFilePath = $fullFilePath;
        $fileInfo = pathinfo($this->fullFilePath);
        $this->fileName = $fileInfo['basename'];
    }

    public function open($filename, $flags = null)
    {
        try {
            if (!empty($filename)) {
                $this->loadFileInfo($filename);
            }
            $opened = parent::open($this->fullFilePath, $flags);
            if (!$opened) {
                throw new Exception("Could not open the .zip file  '{$this->fileName}'");
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
        return $this;
    }

    public function extractTo($destination, $entries = null)
    {
        try {
            if (FileDir::createDir($destination)) {
                $extracted = parent::extractTo($destination, $entries);
                if (!$extracted) {
                    throw new Exception("Could not extract the .zip file '{$this->fileName}' on '{$destination}'");
                }
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }
}