<?php

namespace Integration_v2\ideris\Services;

use Integration\Integration_v2\ideris\ToolsProduct;

require_once APPPATH . "libraries/Integration_v2/ideris/Services/BaseProductImportService.php";

/**
 * Class ProductImportCreateService
 * @package Integration_v2\ideris\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductImportCreateService extends BaseProductImportService
{

    protected function enabledToImport($product): bool
    {
        $dateCreated = $this->toolsProduct->dateLastJob ?? ((ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') ? date('Y-m-d H:i:s', strtotime('-50 years')) : date('Y-m-d H:i:s', strtotime('-1 months')));
        if (strtotime($product->created ?? 'now') >= strtotime($dateCreated)) {
            if (($product->statusId ?? 1) === 1) {
                return true;
            }
        }
        return false;
    }
}