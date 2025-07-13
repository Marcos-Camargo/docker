<?php

require_once APPPATH . "libraries/Validations/ProductIntegrationValidation.php";

class ProductIntegrationValidationHub2b extends ProductIntegrationValidation
{
    protected $dbDriver;

    protected $apiClient;

    public function __construct(
        Model_products $model_products,
        Model_integrations $model_integrations,
        $dbDriver,
        $apiClient
    )
    {
        parent::__construct($model_products, $model_integrations);
        $this->dbDriver = $dbDriver;
        $this->apiClient = $apiClient;
    }

    protected function getProductFromExternalPlatform($productId): array
    {
        try {
            $filter = [
                'getAdditionalInfo' => false,
                'destinationSKU' => $productId
            ];
            $response = $this->apiClient->request('GET','/catalog/product/ID_MARKETPLACE/ID_TENANT', ['query' => $filter]);
            if ($response->getStatusCode() != 200) {
                return [];
            }
            $body = $response->getBody();
            $contentDecoded = json_decode($body->getContents());
            $data = $contentDecoded->data ?? (object)[];
            $list = $data->list ?? [];
            if (empty($list)) return [];
            $product = current($list);
            if (((int)$this->product['id']) === ((int)$product->sourceId ?? 0)) return [];
            return [
                'product' => [
                    'id' => $product->sku ?? 0
                ]
            ];
        } catch (Throwable $e) {
        }
        return [];
    }
}