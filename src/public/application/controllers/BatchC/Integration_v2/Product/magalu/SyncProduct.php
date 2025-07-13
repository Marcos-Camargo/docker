<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/magalu/CreateProduct run {ID} {STORE}
 *
 */
require_once APPPATH . "controllers/BatchC/Integration_v2/Product/magalu/BaseProductImport.php";
require_once APPPATH . "libraries/Integration_v2/magalu/Services/ProductSyncService.php";

/**
 * Class CreateProduct
 */
class SyncProduct extends BaseProductImport
{
    protected function buildServiceProvider(): \Integration_v2\magalu\Services\ProductSyncService
    {
        return new \Integration_v2\magalu\Services\ProductSyncService($this->toolsProduct);
    }
}