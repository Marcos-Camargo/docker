<?php

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/ideris/UpdateProduct run {ID} {STORE}
 *
 */

use Integration\Integration_v2\ideris\ToolsProduct;

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/ideris/BaseProductImport.php";
require_once APPPATH . "libraries/Integration_v2/ideris/Services/ProductImportUpdateService.php";

/**
 * Class CreateProduct
 * @property ToolsProduct $toolsProduct
 */
class UpdateProduct extends BaseProductImport
{

    protected function buildServiceProvider(): \Integration_v2\ideris\Services\ProductImportUpdateService
    {
        return new \Integration_v2\ideris\Services\ProductImportUpdateService($this->toolsProduct);
    }
}