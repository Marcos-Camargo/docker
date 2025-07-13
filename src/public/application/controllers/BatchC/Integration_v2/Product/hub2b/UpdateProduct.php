<?php

use Integration_v2\hub2b\Services\ProductCatalogUpdateService;

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/hub2b/BaseProductBatch.php";
require_once APPPATH . "libraries/Integration_v2/hub2b/Services/ProductCatalogUpdateService.php";

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/hub2b/UpdateProduct run {ID} {STORE}
 *
 */
class UpdateProduct extends BaseProductBatch
{

    public function __construct()
    {
        parent::__construct();
        $this->productCatalogService = new ProductCatalogUpdateService($this->toolsProduct);
    }

    protected function getProductPaginationFilters(): array
    {
        return parent::getProductPaginationFilters() + [
                'onlyActiveProducts' => false,
                'onlyWithDestinationSKU' => true
            ];
    }
}