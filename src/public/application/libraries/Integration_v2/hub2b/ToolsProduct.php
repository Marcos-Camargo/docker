<?php

namespace Integration\Integration_v2\hub2b;

require APPPATH . "libraries/Integration_v2/Product_v2.php";
require_once APPPATH . "libraries/Integration_v2/hub2b/Validation/ProductIntegrationValidationHub2b.php";
require_once APPPATH . "libraries/Integration_v2/hub2b/Resources/Configuration.php";

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use Integration_v2\hub2b\Resources\Configuration;
use InvalidArgumentException;
use libraries\Helpers\StringHandler;
use libraries\Integration_v2\hub2b\Resources\Mappers\ProductV1Mapper;

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\hub2b
 * @property \ProductIntegrationValidation $productIntegrationValidation
 * @property Configuration $configuration
 */
class ToolsProduct extends Product_v2
{

    private $parsedProduct = [];

    private $parsedVariations = [];

    private $isPublishedProduct = false;

    private $productPayload;

    private $productImages;

    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->productIntegrationValidation = new \ProductIntegrationValidationHub2b(
            $this->model_products,
            $this->model_integrations,
            $this->db,
            $this
        );
        $this->configuration = new Configuration($this->model_settings);
        array_multisort(array_map('strlen', array_keys($this->typeVariation)), SORT_DESC, $this->typeVariation);
    }

    /**
     * Define os atributos para o produto.
     *
     * @param string $productId Código do produto (products.id).
     * @param string $productIntegration Código do produto na integradora.
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"].
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        return [];
    }

    public function getProductsPagination(array $filter = [], int $page = 1, int $size = 50)
    {
        $filter['offset'] = ($page - 1) * $size;
        $filter['limit'] = $size;

        try {
            $request = $this->request('GET', '/catalog/product/ID_MARKETPLACE/ID_TENANT', ['query' => $filter]);
            try {
                $contentResponse = $request->getBody()->getContents();
                $contentResponse = empty(trim($contentResponse)) ? '{}' : $contentResponse;
                $response = Utils::jsonDecode($contentResponse);
            } catch (\Throwable $e) {
                throw new \Exception(sprintf("JSON DECODE ERROR: %s: %s", $request->getBody()->getContents(), print_r($request, true)));
            }
            return $response;
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            throw new \Exception($message);
        }
    }

    public function getDataProductIntegration(string $id)
    {
        $filter = [
            'getAdditionalInfo' => false,
            'destinationSKU' => $id
        ];
        $request = $this->request('GET', '/catalog/product/ID_MARKETPLACE/ID_TENANT', ['query' => $filter]);
        $contentResponse = $request->getBody()->getContents();
        $contentResponse = empty(trim($contentResponse)) ? '{}' : $contentResponse;
        $response = Utils::jsonDecode($contentResponse);
        if (!empty($response)) {
            return current($response);
        }
        return (object)[];
    }

    public function getVariationsIntegration(string $parentSKU)
    {
        try {
            $filter = [
                'filter' => "parentSKU:{$parentSKU}|salesChannel:{$this->configuration->getSalesChannelId()}"
            ];
            require_once APPPATH . "libraries/Integration_v2/hub2b/Resources/Mappers/ProductV1Mapper.php";
            return array_map(function ($product) {
                return ProductV1Mapper::toV2($product);
            }, $this->getDataIntegrationByEndpoint("/listskus/ID_TENANT", ['query' => $filter]) ?? []);
        } catch (\Throwable $e) {
            echo $e->getMessage();
            return [];
        }
    }

    public function getDataIntegrationByEndpoint(string $endpoint, array $options = [])
    {
        try {
            $request = $this->request('GET', $endpoint, $options);
        } catch (InvalidArgumentException|GuzzleException $exception) {
            $message = $exception->getMessage();
            if ($exception instanceof GuzzleException) {
                $message = $exception->getResponse()->getBody()->getContents();
            }
            throw new \Exception($message);
        }
        $response = Utils::jsonDecode($request->getBody()->getContents());
        $data = $response->data ?? (object)[];
        $filterSkus = [];
        return array_filter($data->list ?? [], function ($prod) use (&$filterSkus) {
            if (isset($filterSkus[$prod->sku])) return false;
            $filterSkus[$prod->sku] = $prod->sku;
            return true;
        });
    }

    public function fetchProductPriceStockProductVariation($productId, $variation = null): array
    {
        try {
            $price = $this->getPriceErp($variation ?? $productId);
            $stock = $this->getStockErp($variation ?? $productId);

            return [[
                'sku'    => $productId,
                'varSku' => $variation ?: null,
                'stock'  => $stock['stock'] ?? null
            ], [
                'sku'       => $productId,
                'varSku'    => $variation ?: null,
                'listPrice' => $price['listprice'] ?? null,
                'price'     => $price['price'] ?? null
            ]];
        } catch (\Throwable $e) {
            return [[], [
                'sku'       => null,
                'varSku'    => null,
                'listPrice' => 0,
                'price'     => 0,
                'costPrice' => 0
            ]];
        }
    }

    /**
     * Formata os dados do produto para criar ou atualizar.
     *
     * @param array|object $payload Dados do produto para formatação.
     * @param mixed $option Dados opcionais para auxílio na formatação.
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $this->parsedVariations = [];

        $this->productPayload = $payload;
        $decodedDescription = html_entity_decode($this->productPayload->description->sourceDescription ?? '', ENT_NOQUOTES, 'UTF-8');
        $decodedDescription = preg_replace('/[ \n\n\t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", strip_tags($decodedDescription)));
        $this->productImages = $this->productPayload->images ?? [];
        $groupers = $this->productPayload->groupers ?? null;
        $parentSku = !empty($groupers->destinationGrouper ?? '') ? $groupers->destinationGrouper : ($groupers->parentSKU ?? null);
        $productSku = !empty($parentSku) ? $parentSku : (!empty($this->productPayload->skus->destination ?? '') ? $this->productPayload->skus->destination : ($this->productPayload->skus->source ?? ''));
        $productIdErp = !empty($this->productPayload->id ?? '') ? $this->productPayload->id : $productSku;
        $productDimensions = $this->productPayload->dimensions ?? null;
        $weight = $productDimensions->weight ?? null;
        $destinationPrices = $this->productPayload->destinationPrices ?? (object)['priceBase' => 0, 'priceSale' => 0];
        $price = $destinationPrices->priceSale > 0 ? $destinationPrices->priceSale : $destinationPrices->priceBase;
        $listPrice = $destinationPrices->priceBase > 0 ? $destinationPrices->priceBase : $price;
        $productStock = $this->productPayload->stocks ?? null;
        $productCategory = $this->productPayload->categorization->source ?? null;
        $this->parsedProduct = [
            'name' => ['value' => $this->productPayload->name, 'field_database' => 'name'],
            'sku' => ['value' => $productSku, 'field_database' => 'sku'],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => $price, 'field_database' => 'price'],
            'list_price' => ['value' => $listPrice, 'field_database' => 'list_price'],
            'stock' => ['value' => $productStock->sourceStock, 'field_database' => 'qty'],
            'status' => ['value' => in_array(($this->productPayload->status->id ?? 0), [2,3,4]) ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT, 'field_database' => 'status'],
            'ean' => ['value' => ((int)$this->productPayload->idProductType) === 1 ? $this->productPayload->ean : '', 'field_database' => 'EAN'],
            'origin' => ['value' => 0, 'field_database' => 'origin'],
            'ncm' => ['value' => $this->productPayload->ncm, 'field_database' => 'NCM'],
            'net_weight' => ['value' => $weight, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => $weight, 'field_database' => 'peso_bruto'],
            'description' => ['value' => $decodedDescription, 'field_database' => 'description'],
            'height' => ['value' => $productDimensions->height ?? null, 'field_database' => 'altura'],
            'depth' => ['value' => $productDimensions->length ?? null, 'field_database' => 'profundidade'],
            'width' => ['value' => $productDimensions->width ?? null, 'field_database' => 'largura'],
            'product_height' => ['value' => $productDimensions->height ?? null, 'field_database' => 'actual_height'],
            'product_depth' => ['value' => $productDimensions->length ?? null, 'field_database' => 'actual_depth'],
            'product_width' => ['value' => $productDimensions->width ?? null, 'field_database' => 'actual_width'],
            'images' => ['value' => $this->getImagesFormatted($this->productImages ?? []), 'field_database' => NULL],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            'extra_operating_time' => ['value' => $productStock->handlingTime, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => $productCategory->name ?? '', 'field_database' => 'category_id'],
            'brand' => ['value' => $this->productPayload->brand ?? '', 'field_database' => 'brand_id'],
            'guarantee' => ['value' => $this->productPayload->warranty ?? 0, 'field_database' => 'garantia'],
            '_product_id_erp' => ['value' => $productIdErp, 'field_database' => 'product_id_erp'],
            '_published' => ['value' => false, 'field_database' => ''],
            '_external_sku' => ['value' => $productSku, 'field_database' => '']
        ];

        $this->productValidationHandler([
            'sku' => $productSku,
            'productIdErp' => $productIdErp
        ]);

        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (($existingProduct['id'] ?? 0) > 0) {
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->isPublishedProduct) {
                $this->parsedProduct['_published']['value'] = true;
            }
            $isSimpleProduct = ((int)$this->productPayload->idProductType) === 1;
            if ($isSimpleProduct) {
                $this->parsedProduct['stock']['value'] = $this->parsedProduct['stock']['value'] ?? $existingProduct['qty'];
            }
            $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > 0 ? $this->parsedProduct['price']['value'] : $existingProduct['price'];
            $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > 0 ? $this->parsedProduct['list_price']['value'] : $existingProduct['list_price'];
        }
        $this->parsedProduct['variations']['value'] = ((int)$this->productPayload->idProductType) === 3 ? $this->getVariationFormatted($this->getVariationsIntegration($productSku)) : [];

        if ($this->isPublishedProduct && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'variations', '_product_id_erp', '_published'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }
        $this->handleWithParsedProductDimensions('meter' ?? null);
        return $this->parsedProduct;
    }

    protected function handleWithParsedProductDimensions($unit = 'centimeter')
    {
        foreach (['height', 'depth', 'width', 'product_height', 'product_depth', 'product_width'] as $dType) {
            if (!isset($this->parsedProduct[$dType]['value'])) continue;
            $conValue = $this->parsedProduct[$dType]['value'];
            if (!is_numeric($conValue)) continue;
            if (strcasecmp($unit, 'meter') === 0) $conValue = (int)($conValue * 100);
            $this->parsedProduct[$dType]['value'] = $conValue;
        }
    }

    public function productValidationHandler(array $params)
    {
        $this->productIntegrationValidation->validateUpgradeableProduct([
                'sku' => $params['sku'] ?? null,
                'store_id' => $this->store,
                'product_id_erp' => $params['productIdErp'] ?? null,
            ]
        );
        $productIdErp = $this->model_products->getByProductIdErpAndStore(
            $params['productIdErp'],
            $this->store
        );
        if (!empty($productIdErp)) {
            $this->productIntegrationValidation->setProduct($productIdErp);
        }
        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (($existingProduct['id'] ?? 0) > 0) {
            $this->isPublishedProduct = false;
            if ($this->productIntegrationValidation->isPublishedProduct($existingProduct['id'])) {
                $this->isPublishedProduct = true;
            }
        }
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param array|string|int $id Código do sku do produto.
     * @param float|null $price Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array                       Retorna array com preço (int[price_product] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     * @throws GuzzleException
     */
    public function getPriceErp($id, float $price = null): array
    {
        $filter = [
            'salesChannel' => $this->configuration->getSalesChannelId()
        ];
        $request = $this->request('GET', "/catalog/product/listprice/$id", ['query' => $filter]);
        $contentResponse = $request->getBody()->getContents();
        $response = Utils::jsonDecode($contentResponse);

        if (!property_exists($response, 'totalObjects') || $response->totalObjects == 0) {
            return [
                'price'     => null,
                'listprice' => null
            ];
        }

        foreach ($response->response as $sku) {
            if ($sku->sourceSKU == $id) {
                return [
                    'price'     => $sku->price,
                    'listprice' => $sku->listPrice
                ];
            }
        }

        return [
            'price'     => null,
            'listprice' => null
        ];
    }

    /**
     * Formata os dados da variação.
     *
     * @param array $payload Dados da variação para formatação.
     * @param mixed $option Dados opcionais para auxílio na formatação.
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        $hasActiveVar = false;
        foreach ($payload as $variationData) {
            try {
                $variationDataDimensions = $variationData->dimensions ?? (object)[];
                $this->parsedProduct['net_weight']['value'] = $this->parsedProduct['net_weight']['value'] ?? $variationDataDimensions->weight ?? 0;
                $this->parsedProduct['gross_weight']['value'] = $this->parsedProduct['gross_weight']['value'] ?? $variationDataDimensions->weight ?? 0;
                $this->parsedProduct['height']['value'] = $this->parsedProduct['height']['value'] ?? $variationDataDimensions->height ?? 0;
                $this->parsedProduct['depth']['value'] = $this->parsedProduct['depth']['value'] ?? $variationDataDimensions->length ?? 0;
                $this->parsedProduct['width']['value'] = $this->parsedProduct['width']['value'] ?? $variationDataDimensions->width ?? 0;
                $this->parsedProduct['product_height']['value'] = $this->parsedProduct['product_height']['value'] ?? $variationDataDimensions->height ?? 0;
                $this->parsedProduct['product_depth']['value'] = $this->parsedProduct['product_depth']['value'] ?? $variationDataDimensions->length ?? 0;
                $this->parsedProduct['product_width']['value'] = $this->parsedProduct['product_width']['value'] ?? $variationDataDimensions->width ?? 0;

                $parsedVariation = $this->getVariationParsed($variationData);
                if (empty($parsedVariation['variations'] ?? [])) continue;

                $this->parsedProduct['price']['value'] = empty($this->parsedProduct['price']['value']) || $this->parsedProduct['price']['value'] > ($parsedVariation['price'] ?? 0) ? $this->parsedProduct['price']['value'] : $parsedVariation['price'] ?? 0;
                $this->parsedProduct['list_price']['value'] = empty($this->parsedProduct['list_price']['value']) || $this->parsedProduct['list_price']['value'] > ($parsedVariation['list_price'] ?? 0) ? $this->parsedProduct['list_price']['value'] : $parsedVariation['list_price'] ?? 0;

                $hasActiveVar = $parsedVariation['status'] == \Model_products::ACTIVE_PRODUCT ? true : $hasActiveVar;
                array_push($this->parsedVariations, $parsedVariation);

            } catch (\Throwable $e) {
            }
        }

        $this->parsedProduct['status']['value'] = $hasActiveVar ? \Model_products::ACTIVE_PRODUCT : $this->parsedProduct['status']['value'];
        return $this->parsedVariations;
    }

    public function getVariationParsed(object $variationData): array
    {

        $groupers = $variationData->groupers ?? null;
        $parentSku = !empty($groupers->destinationGrouper ?? '') ? $groupers->destinationGrouper : $groupers->parentSKU ?? null;
        $sku = !empty($variationData->skus->destination) ? $variationData->skus->destination : ($variationData->skus->source ?? '');
        if (empty(trim($sku)) || strcasecmp($sku, $parentSku) === 0) {
            return [];
        }

        $variationsTypes = [];
        foreach ($variationData->attributes ?? [] as $attribute) {
            if (strcasecmp($attribute->attributeType, 'specification') !== 0) continue;
            $slugAttr = StringHandler::slugify($attribute->name, '-');
            if (strcasecmp($slugAttr, 'genero') === 0) continue;
            if (!isset($this->typeVariation[$slugAttr])) {
                $checkAttr = array_filter(array_keys($this->typeVariation), function ($attrVar) use ($slugAttr) {
                    return strpos($slugAttr, StringHandler::slugify($attrVar)) !== false;
                });
                if (empty($checkAttr)) continue;
                $attribute->name = current($checkAttr) ?? null;
            }
            $variationsTypes[$attribute->name ?? null] = $attribute->value ?? null;
        }
        $destinationPrices = $this->productPayload->destinationPrices ?? (object)['priceBase' => 0, 'priceSale' => 0];
        $price = $destinationPrices->priceSale > 0 ? $destinationPrices->priceSale : $destinationPrices->priceBase;
        $listPrice = $destinationPrices->priceBase > 0 ? $destinationPrices->priceBase : $price;
        $productStock = $variationData->stocks ?? null;

        $parsedVariation = [
            'id' => 0,
            'sku' => $sku,
            'status' => in_array(($variationData->status->id ?? 0), [2,3,4]) ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
            'ean' => $variationData->ean,
            'images' => $this->getImagesFormatted($variationData->images ?? []),
            'variations' => $variationsTypes,
            'price' => $price,
            'list_price' => $listPrice,
            'stock' => $productStock->sourceStock,
            '_parent_sku' => $parentSku,
            '_variant_id_erp' => $variationData->id ?? $sku,
            '_published' => $this->isPublishedProduct,
            '_external_sku' => ($variationData->skus->source ?? '')
        ];

        if ($this->productIntegrationValidation->productExists()) {
            $prd = $this->productIntegrationValidation->getProduct();
            $variation = $this->model_products->getVariantByPrdIdAndSku($prd['id'], $parsedVariation['sku']);
            if (empty($variation)) {
                $variation = $this->model_products->getVariantByPrdIdAndIDErp($prd['id'], $parsedVariation['_variant_id_erp']);
            }
            $parsedVariation['id'] = $variation['id'] ?? 0;
            $parsedVariation['stock'] = (int)$parsedVariation['stock'] ?? $variation['qty'];
            $parsedVariation['price'] = $parsedVariation['price'] > 0 ? $parsedVariation['price'] : $variation['price'];
            $parsedVariation['list_price'] = $parsedVariation['list_price'] > 0 ? $parsedVariation['list_price'] : $variation['list_price'] ?? $parsedVariation['price'];
            $parsedVariation['_parent_sku'] = $prd['sku'] ?? $parsedVariation['_parent_sku'];
            if ($this->isPublishedProduct) {
                $updatableFields = [
                    'id', 'sku', 'status', 'stock', 'price', 'list_price', '_parent_sku', '_variant_id_erp', '_published'
                ];
                $parsedVariation = array_intersect_key($parsedVariation, array_flip($updatableFields));
            }
        }
        return $parsedVariation;
    }

    /**
     * @param array $payload Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $images = [];
        foreach ($payload as $image) {
            $fullUrl = $image->url ?? '';
            //if (!$this->validateImageUrl($fullUrl)) continue;
            if (empty($fullUrl)) {
                continue;
            }
            $images[] = $fullUrl;
        }
        return $images;
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param array|string|int $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array                   Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id): array
    {
        $filter = [
            'salesChannel' => $this->configuration->getSalesChannelId()
        ];
        $request = $this->request('GET', "/catalog/product/liststock/$id", ['query' => $filter]);
        $contentResponse = $request->getBody()->getContents();
        $response = Utils::jsonDecode($contentResponse);

        if (!property_exists($response, 'totalObjects') || $response->totalObjects == 0) {
            return [
                'stock' => 0
            ];
        }

        foreach ($response->response as $sku) {
            if ($sku->sourceSKU == $id) {
                return [
                    'stock' => $sku->stocks->sourceStock
                ];
            }
        }

        return [
            'stock' => 0
        ];
    }

    /**
     * Recupera o estoque de produto(s)
     * @warning Caso haja necessidade de implementar um método para recuperar os dois dados ao mesmo tempo.
     *
     * @param array|string|int $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  bool                    Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): bool
    {
        return false;
    }

    public function getParsedVariations()
    {
        return $this->parsedVariations;
    }

    public function confirmImportProduct(array $params = [])
    {
        try {
            $request = $this->request('POST', '/catalog/product/mapsku/ID_SALES_CHANNEL', [
                'json' => [
                    [
                    'sourceSKU' => $params['externalSku'],
                    'destinationSKU' => $params['sku']
                    ]
                ]
            ]);
        } catch (\Throwable $e) {

        }
    }

    /**
     * Este serviço tem como intuito alterar o status de um produto na Plataforma Hub, para um determinado canal de venda.
     * Os possíveis status são: (1) Desconectado, (2) Pendente, (3) Sincronizado, (4) Com Erro.
     *
     * @param   string  $sku    SKU do produto.
     * @param   int     $status Status do produto. (1) Desconectado, (2) Pendente, (3) Sincronizado, (4) Com Erro.
     */
    public function setStatusProductIntegration(string $sku, int $status)
    {
        try {
            $this->request('POST', '/catalog/product/setproductstatus', [
                'json' => [
                    [
                        "itemId"        => $sku,
                        "salesChannel"  => $this->configuration->getSalesChannelId(),
                        "status"        => $status
                    ]
                ]
            ]);
        } catch (\Throwable | InvalidArgumentException $e) {

        }
    }
}