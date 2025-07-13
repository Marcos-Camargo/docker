<?php

require APPPATH . "controllers/BatchC/Integration_v2/Product/tray/BaseProductBatch.php";

/**
 * Class CreateProduct
 *
 * php index.php BatchC/Integration_v2/Product/tray/CreateProduct run {ID} {STORE}
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
                'available' => 1,
                'created' => $this->toolsProduct->dateLastJob ?? ((ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x') ? date('Y-m-d H:i:s', strtotime('-50 years')) : date('Y-m-d H:i:s', strtotime('-1 months')))
            ];
    }
}