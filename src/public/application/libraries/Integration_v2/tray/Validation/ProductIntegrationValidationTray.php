<?php

class ProductIntegrationValidationTray extends ProductIntegrationValidation
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
            $response = $this->apiClient->request('GET', "products/{$productId}");
            if ($response->getStatusCode() != 200) {
                return [];
            }
            $body = $response->getBody();
            $contentDecoded = json_decode($body->getContents());
            $product = $contentDecoded->Product ?? (object)[];
            if (!isset($product->id)) return [];
            return [
                'product' => [
                    'id' => $product->id ?? 0
                ]
            ];
        } catch (Throwable $e) {
        }
        return [];
    }
}