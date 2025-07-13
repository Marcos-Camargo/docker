<?php

require_once APPPATH . "controllers/BatchC/Integration_v2/Product/hub2b/BaseProductBatch.php";

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/hub2b/CreateProduct run {ID} {STORE}
 *
 */
class CreateProduct extends BaseProductBatch
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getProductPaginationFilters(): array
    {
        return parent::getProductPaginationFilters() + [
                'onlyActiveProducts' => true,
                'onlyWithDestinationSKU' => false
            ];
    }
}