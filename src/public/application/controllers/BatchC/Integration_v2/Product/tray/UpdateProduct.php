<?php

require APPPATH . "controllers/BatchC/Integration_v2/Product/tray/BaseProductBatch.php";

/**
 * Class UpdateProduct
 *
 * php index.php BatchC/Integration_v2/Product/tray/UpdateProduct run {ID} {STORE}
 *
 */
class UpdateProduct extends BaseProductBatch
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function getProductPaginationFilters(): array
    {
        return parent::getProductPaginationFilters() + [
                'modified' => $this->toolsProduct->dateLastJob ?? date('Y-m-d H:i:s', strtotime('-1 hours'))
            ];
    }
}