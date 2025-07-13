<?php
require 'system/libraries/Vendor/autoload.php';
defined('BASEPATH') or exit('No direct script access allowed');

use League\Csv\ByteSequence;
use League\Csv\ColumnConsistency;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\TabularDataReader;
use League\Csv\Writer;

class CSV_Validation
{
    public function __construct()
    {
    }

    /**
     * Converte o CSV para vetor.
     *
     * @param   string  $upload_file
     * @return  TabularDataReader
     * @throws  Exception
     */
    public function convertCsvToArray(string $upload_file): TabularDataReader
    {
        try {
            $csv = Reader::createFromPath($upload_file);
            $csv->setDelimiter(';'); // separados de colunas
            $csv->setHeaderOffset(0); // linha do header
            $stmt = new Statement();

            return $stmt->process($csv);
        } catch (\League\Csv\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Recupera o header do CSV.
     *
     * @throws \League\Csv\Exception
     */
    public function getHeaderFile(string $upload_file): array
    {
        $csv = Reader::createFromPath($upload_file);
        // $csv->setOutputBOM(mb_detect_encoding(file_get_contents($upload_file)));
        $csv->setDelimiter(';'); // separados de colunas
        $csv->setHeaderOffset(0); // linha do header

        $stmt = new Statement();
        $dados = $stmt->process($csv);

        return $dados->fetchOne();
    }

    /**
     * Verifica se a linha está toda em branco.
     *
     * @param   array   $dado
     * @return  bool
     */
    public function lineEmptyCheck(array $dado): bool
    {
        $linhaEmBranco = true;
        foreach ($dado as $line) {
            if (!empty(trim($line)) || (is_numeric($line))) {
                $linhaEmBranco = false;
                break;
            }
        }
        return $linhaEmBranco;
    }

    /**
     * Gera um novo arquivo em CSV.
     *
     * @param   string      $directory_to_save  Caminho onde será salvo o arquivo.
     * @param   array       $new_data           Dados do novo arquivo, caso não exista o parâmetro $file, informar o cabeçalho.
     * @param   string|null $file               Arquivo atual para recuperar o cabeçalho.
     * @throws  Exception
     */
    public function createNewFileCsv(string $directory_to_save, array $new_data, string $file = null)
    {
        try {
            if (!is_null($file)) {
                $header = $this->getHeaderFile($file);
            }
        } catch (\League\Csv\Exception $exception) {
            throw new Exception("Ocorreu um erro para recuperar o cabeçalho. {$exception->getMessage()}");
        }

        try {
            $newCsv = Writer::createFromFileObject(new SplTempFileObject());

            $newCsv->setOutputBOM(ByteSequence::BOM_UTF8); // mantem o arquivo em UTF8
            $newCsv->setDelimiter(';'); // demiliter de cada coluna
            if (!is_null($file)) {
                $newCsv->insertOne(array_keys($header)); // cabeçalho
            } else {
                $newCsv->insertOne(array_keys($new_data[0])); // cabeçalho
            }
            $newCsv->insertAll($new_data); // linhas com erro

            file_put_contents($directory_to_save, $newCsv->__toString());
        } catch (\League\Csv\Exception | Exception | Error $exception) {
            throw new Exception("Ocorreu um erro para gerar o arquivo com as linhas de erros. {$exception->getMessage()}");
        }
    }

    /**
     * @param string $file
     * @param array[] $new_data
     * @return void
     * @throws Exception
     */
    public function insertLinesInTheFile(string $file, array $new_data)
    {
        try {
            $newCsv = Writer::createFromPath($file, 'a');
            $newCsv->setOutputBOM(ByteSequence::BOM_UTF8); // mantem o arquivo em UTF8
            $newCsv->setDelimiter(';'); // demiliter de cada coluna
            $newCsv->insertAll($new_data);
        } catch (\League\Csv\Exception | Exception | Error $exception) {
            throw new Exception("Ocorreu um erro para gerar o arquivo com as linhas de erros. {$exception->getMessage()}");
        }
    }
}
