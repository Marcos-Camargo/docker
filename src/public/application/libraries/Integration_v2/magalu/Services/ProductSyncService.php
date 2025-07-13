<?php

namespace Integration_v2\magalu\Services;

use Integration\Integration_v2\magalu\ToolsProduct;

require_once APPPATH . "libraries/Integration_v2/magalu/Services/BaseProductImportService.php";

/**
 * Class ProductImportCreateService
 * @package Integration_v2\magalu\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductSyncService extends BaseProductImportService
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