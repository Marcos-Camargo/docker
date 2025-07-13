<?php


namespace Integration_v2\viavarejo_b2b\Services;

use \Integration\Integration_v2\viavarejo_b2b\ToolsProduct;

/**
 * Class ProductAvailabilityService
 * @package Integration_v2\viavarejo_b2b\Services
 * @property ToolsProduct $toolsProduct
 */
class ProductAvailabilityService
{

    protected $toolsProduct;

    public function __construct(ToolsProduct $toolsProduct)
    {
        $this->toolsProduct = $toolsProduct;
    }

    public function handleWithRawObject(object $object)
    {
        foreach ($object->Skus as $skuAvailability) {
            try {
                if (isset($skuAvailability->IdCampanha)) {
                    if ($skuAvailability->IdCampanha != $this->toolsProduct->getCampaignId()) {
                        throw new \Exception("A campanha {$skuAvailability->IdCampanha}, não está associada a loja #{$this->toolsProduct->store}");
                    }
                }
                $this->toolsProduct->setUniqueId("{$skuAvailability->Codigo}");
                $parsedAvailability = $this->parserRawAvailability($skuAvailability);
                $this->handleParsedAvailability($parsedAvailability);
            } catch (\Throwable $e) {
                $error_message = $e->getMessage();
                if (!likeTextNew('%Não localizado produto ou variação com o Codigo%', $error_message)) {
                    $this->toolsProduct->log_integration(
                        "Ocorreu um erro ao atualizar o preço e disponibilidade do produto {$skuAvailability->Codigo}",
                        $error_message,
                        'E'
                    );
                }
            }
        }
    }

    protected function parserRawAvailability(object $skuAvailability): array
    {
        return $this->toolsProduct->getAvailabilityFormattedToIntegration($skuAvailability);
    }

    protected function handleParsedAvailability($parsedAvailability)
    {
        $this->updatePriceProduct($parsedAvailability['sku'], $parsedAvailability['price'], $parsedAvailability['list_price'], $parsedAvailability['varSku'] ?? null);
        $this->updateStatusProduct($parsedAvailability);
    }

    protected function updatePriceProduct($productSku, $price, $listPrice = null, $varSku = null)
    {
        $listPrice = $listPrice ?? $price;
        $this->toolsProduct->updatePriceProduct($productSku, $price, $listPrice, $varSku);
    }

    protected function updateStatusProduct($parsedAvailability)
    {
        if (isset($parsedAvailability['varSku']) && !empty($parsedAvailability['varSku'])) {
            $productSku = $parsedAvailability['sku'];
            $parsedAvailability['sku'] = $parsedAvailability['varSku'];
            $parsedAvailability['status'] = $parsedAvailability['status'] == 0 ? 2 : $parsedAvailability['status'];

            // Verifica se precisa desabilitar o produto.
            /*$status_product = 1;
            if ($parsedAvailability['status'] != 1) {
                $status_product = 2;
            } else {
                // Ler as variações para saber se existe alguma inativa.
                foreach ($this->toolsProduct->getVariationByProductSku($productSku) as $variant) {
                    if ($variant['sku'] == $parsedAvailability['varSku']) {
                        continue;
                    }
                    if ($variant['status'] != 1) {
                        $status_product = 2;
                        break;
                    }
                }
            }

            // Se precisa ativar/inativar a variação, verifica se o produto também precisa ser ativado/inativado.
            $data_current_product       = $this->toolsProduct->getProductForSku($productSku);
            $can_update_status_product  = ($data_current_product['status'] == 2 && $status_product == 1) || ($data_current_product['status'] == 1 && $status_product == 2);
            */
            // Atualiza a variação.
            return $this->toolsProduct->updateVariation($parsedAvailability, $productSku);
            /*$updateProduct   = true;

            // Altera o status da variação.
            if ($can_update_status_product) {
                $updateProduct   = $this->toolsProduct->updateProduct(array(
                    'sku' => array(
                        'value'          => $productSku,
                        'field_database' => 'sku'
                    ),
                    'status' => array(
                        'value'          => $status_product,
                        'field_database' => 'status'
                    )
                ));
            }

            return $updateVariation || $updateProduct;*/
        }
        $parsedAvailability['sku'] = ['value' => $parsedAvailability['sku'], 'field_database' => 'sku'];
        $parsedAvailability['name'] = ['value' => $parsedAvailability['name'], 'field_database' => 'name'];
        $parsedAvailability['id'] = ['value' => $parsedAvailability['id'], 'field_database' => 'id'];
        $parsedAvailability['status'] = ['value' => $parsedAvailability['status'], 'field_database' => 'status'];
        return $this->toolsProduct->updateProduct($parsedAvailability);
    }
}