<?php

/**
 * @property CI_DB_query_builder $dbDriver
 */
class ProductIntegrationValidationAnyMarket extends ProductIntegrationValidation
{
    protected $dbDriver;

    protected $apiClient;

    public function __construct(
        Model_products                                     $model_products,
        Model_integrations                                 $model_integrations,
                                                           $dbDriver,
        \Integration\Integration_v2\anymarket\ToolsProduct $apiClient
    )
    {
        parent::__construct($model_products, $model_integrations);
        $this->dbDriver = $dbDriver;
        $this->apiClient = $apiClient;
    }

    protected function getProductFromExternalPlatform($productId): array
    {
        $integrationId = $this->apiClient->integrationData['id'] ?? 0;
        $this->dbDriver->select()
            ->from('anymarket_queue')->where([
                'idProduct' => $productId
            ]);
        if (!empty($integrationId)) {
            $this->dbDriver->where('integration_id', $integrationId);
        }
        $productAnymarket = $this->dbDriver->order_by('id', 'DESC')->limit(1)
            ->get()->row_array();
        if (!$productAnymarket) {
            return [];
        }
        $idSkuMarketplace = $productAnymarket['idSkuMarketplace'];
        if (empty($idSkuMarketplace)) {
            return [];
        }
        try {
            $response = $this->apiClient->request('GET', "/skumarketplace/{$idSkuMarketplace}");
            if ($response->getStatusCode() != 200) {
                return [];
            }
            $body = $response->getBody();
            $contentDecoded = json_decode($body->getContents(), true);
            return $contentDecoded ? ['product' => ['id' => $contentDecoded['sku']['product']['id'] ?? 0]] : [];
        } catch (Throwable $e) {
            if(((int)$e->getCode()) == 404) {
                return [];
            }
            throw $e;
        }
        return [];
    }

    protected function middlewarePlatformProductValidation($params): void
    {
        // AnyMarket não possui sku para produto pai. Tentar obter através do sku de uma variação
        if (!$this->product && !empty($params['variation_sku'] ?? '')) {
            $this->product = $this->model_products->getProductByVarSkuAndStore(
                $params['variation_sku'],
                $params['store_id']
            );
        }
    }
}