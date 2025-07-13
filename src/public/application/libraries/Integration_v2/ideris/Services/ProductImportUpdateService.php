<?php

namespace Integration_v2\ideris\Services;

require_once APPPATH . "libraries/Integration_v2/ideris/Services/BaseProductImportService.php";

/**
 * Class ProductImportUpdateService
 * @package Integration_v2\ideris\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductImportUpdateService extends BaseProductImportService
{

    protected function enabledToImport($product): bool
    {
        $dateUpdated = $this->toolsProduct->dateLastJob ?? date(DATETIME_INTERNATIONAL, strtotime('-1 hour', strtotime('now')));
        $updated_at = $product->updated ? datetimeNoGMT($product->updated) : 'now';
        if (strtotime($updated_at) >= strtotime($dateUpdated)) {
            return true;
        }
        return false;
    }
}