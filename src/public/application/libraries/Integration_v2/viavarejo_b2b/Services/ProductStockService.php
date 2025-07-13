<?php


namespace Integration_v2\viavarejo_b2b\Services;


use Integration\Integration_v2\viavarejo_b2b\ToolsProduct;

/**
 * Class ProductStockService
 * @package Integration_v2\viavarejo_b2b\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductStockService
{
    protected $toolsProduct;

    public function __construct(ToolsProduct $toolsProduct)
    {
        $this->toolsProduct = $toolsProduct;
    }

    public function handleWithRawObject(object $object): array
    {
        $stockError = array();

        if (property_exists($object, 'Estoques')) {
            $object = $object->Estoques;
        }
        
        foreach ($object as $stock) {
            try {
                $this->toolsProduct->setUniqueId("$stock->IdSku");
                $parsedStock = $this->parserRawStock($stock);
                if (!empty($parsedStock)) {
                    $this->handleParsedStock($parsedStock);
                    if (ENVIRONMENT === 'development') {
                        echo "Processou estoque: $stock->IdSku\n";
                    }
                }
            } catch (\Throwable $e) {
                $stockError[] = $stock;

                $error_message = $e->getMessage();
                if (!likeTextNew('%Não localizado produto ou variação com o Codigo%', $error_message)) {
                    $this->toolsProduct->log_integration(
                        "Ocorreu um erro ao atualizar o estoque do produto $stock->IdSku",
                        $error_message,
                        'E'
                    );
                    if (ENVIRONMENT === 'development') {
                        echo "Não processou estoque: $stock->IdSku\n";
                    }
                }
            }
        }

        return $stockError;
    }

    protected function parserRawStock(object $rawStock)
    {
        return $this->toolsProduct->parseStockToIntegration($rawStock);
    }

    protected function handleParsedStock($parsedStock)
    {
        $this->updateStockProduct($parsedStock['sku'], $parsedStock['stock'], $parsedStock['varSku'] ?? null);
    }

    protected function updateStockProduct($productSku, $qty, $varSku = null)
    {
        $this->toolsProduct->updateStockProduct($productSku, $qty, $varSku);
    }
}