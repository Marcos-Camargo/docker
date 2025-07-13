<?php

define('APPPATH', '/var/www/html/conectala/application/');

require_once APPPATH . 'libraries/FileDir.php';
require_once APPPATH . 'libraries/Helpers/File/ZipArchiveWrapper.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Controllers/ImportLoadFileController.php';

try {
    set_time_limit(0);
    ini_set('memory_limit', '1024M');

    $repo = new Model_csv_to_verifications();
    $schedule = new Model_job_schedule();
    $loadFileCrtl = new \Integration_v2\viavarejo_b2b\Controllers\ImportLoadFileController($repo, $schedule);
    $zipPath = (__DIR__) . '/files/B2BCompleto.zip';
    //$zipPath = (__DIR__) . '/files/B2BDisponibilidade.zip';
    //$zipPath = (__DIR__) . '/files/B2BParcial.zip';
    //$zipPath = (__DIR__) . '/files/B2BEstoque.zip';
    $store_id = 40;
    $timestamp = strtotime("now");
    $destinationPath = "/tmp/sellercenter/files/import/xml/viab2b/{$store_id}/{$timestamp}";
    $loadFileCrtl->extractZipFile($zipPath, $destinationPath);
    $originPath = $destinationPath;
    $destinationPath = "/var/www/html/conectala/assets/files/products_via/{$store_id}";
    $files = $loadFileCrtl->moveAndUpdateFilesPath($originPath, $destinationPath);
    $options['store']['id'] = $store_id;
    $loadFileCrtl->sendFilesToQueue($files, $options);

} catch (Throwable $e) {
    echo "Error: {$e->getMessage()} - {$e->getFile()}: {$e->getLine()}";
}

class Model_csv_to_verifications
{

    public function getByCriteria($criteria)
    {
        return rand(0, 9) % 2 == 0 ? ['id' => rand(1000, 9999)] : null;
    }

    public function update($data, $id)
    {
        echo "UPDATE {$id}:\n";
        echo json_encode($data) . "\n\n";
    }

    public function create($data)
    {
        echo "CREATE:\n";
        echo json_encode($data) . "\n\n";
    }

    public function getInsertId()
    {
        return rand(1000, 9999);
    }
}

class Model_job_schedule
{

    public function find($criteria)
    {
        return rand(0, 9) % 2 == 0 ? ['id' => rand(1000, 9999)] : null;
    }

    public function update($data, $id)
    {
        echo "UPDATE {$id}:\n";
        echo json_encode($data) . "\n\n";
    }

    public function create($data)
    {
        echo "CREATE:\n";
        echo json_encode($data) . "\n\n";
    }

    public function getInsertId()
    {
        return rand(1000, 9999);
    }
}