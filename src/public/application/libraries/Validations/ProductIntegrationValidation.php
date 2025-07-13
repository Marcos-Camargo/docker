<?php

/**
 * Class ProductIntegrationValidation
 * @property Model_products $model_products;
 * @property Model_integrations $model_integrations
 */
class ProductIntegrationValidation
{
    protected $model_products;

    protected $model_integrations;

    protected $product;

    protected $publishedProduct;

    public function __construct(Model_products $model_products, Model_integrations $model_integrations)
    {
        $this->model_products = $model_products;
        $this->model_integrations = $model_integrations;
    }

    public function setProduct($product)
    {
        $this->product = $product;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function productExists(): bool
    {
        return isset($this->product['id']) && $this->product['id'] > 0;
    }

    public function validateUpgradeableProduct($params)
    {
        $this->product = null;
        if ($this->checkIfOtherProductExists($params)) {
            $resultCheck = $this->getProductFromExternalPlatform($this->product['product_id_erp']);
            $idProduct = $resultCheck['product']['id'] ?? 0;
            if (!empty($idProduct) && $idProduct != $params['product_id_erp']) {
                throw new Exception(
                    "Já existe um produto com sku igual no Seller Center: {$this->product['name']} (ID: {$this->product['id']})"
                );
            }
        }
        return $this->product;
    }

    protected function checkIfOtherProductExists($params): bool
    {
        $this->searchAndSetProductByCriteria($params);

        $this->middlewarePlatformProductValidation($params);

        return $this->validationProductIntegrationERP($params);
    }

    protected function searchAndSetProductByCriteria($params)
    {
        $this->product = $this->model_products->getProductBySkuAndStore(
            $params['sku'],
            $params['store_id']
        );
    }

    protected function validationProductIntegrationERP($params): bool
    {
        if (!empty($this->product['product_id_erp'] ?? '') && ($this->product['product_id_erp'] != $params['product_id_erp'])) {
            return true;
        }

        return false;
    }

    protected function getProductFromExternalPlatform($productId): array
    {
        return [];
    }

    protected function middlewarePlatformProductValidation($params): void
    {
    }

    public function isPublishedProduct($productId): bool
    {
        $productIntegrations = $this->model_integrations->getPrdIntegration($productId);
        $canUpdateProduct    = true;

        foreach ($productIntegrations as $productIntegration) {
            // Produto já tem código SKU no marketplace, então já foi enviado pra lá e não pode ser mais alterado.
            if ($canUpdateProduct && $productIntegration['skumkt']) {
                $canUpdateProduct = false;
            }
        }

        return !$canUpdateProduct;

        /*$productIntegrations = $this->model_integrations->getIntegrationsProductAll($productId);
        $prodPublishedIntegrations = array_filter($productIntegrations ?? [], function ($item) {
            return !$item['errors'];
        });
        $this->publishedProduct = $prodPublishedIntegrations ?? [];
        return !empty($this->publishedProduct);*/
    }

}