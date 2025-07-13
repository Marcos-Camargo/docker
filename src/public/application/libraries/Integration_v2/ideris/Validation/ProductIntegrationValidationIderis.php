<?php

class ProductIntegrationValidationIderis extends ProductIntegrationValidation
{
    protected $dbDriver;

    protected $apiClient;

    public function __construct(
        Model_products     $model_products,
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
            $response = $this->apiClient->request('GET', "/listingModel/{$productId}");
            if ($response->getStatusCode() != 200) {
                return [];
            }
            $body = $response->getBody();
            $contentDecoded = json_decode($body->getContents());
            $product = $contentDecoded->obj ?? (object)[];
            if (!isset($product->int_id)) return [];
            return [
                'product' => [
                    'id' => $product->int_id ?? 0
                ]
            ];
        } catch (Throwable $e) {
        }
        return [];
    }
}