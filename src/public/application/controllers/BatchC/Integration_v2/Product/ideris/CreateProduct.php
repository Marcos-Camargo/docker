<?php

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/ideris/CreateProduct run {ID} {STORE}
 *
 */
require_once APPPATH . "controllers/BatchC/Integration_v2/Product/ideris/BaseProductImport.php";
require_once APPPATH . "libraries/Integration_v2/ideris/Services/ProductImportCreateService.php";

/**
 * Class CreateProduct
 */
class CreateProduct extends BaseProductImport
{
    protected function buildServiceProvider(): \Integration_v2\ideris\Services\ProductImportCreateService
    {
        return new \Integration_v2\ideris\Services\ProductImportCreateService($this->toolsProduct);
    }
}