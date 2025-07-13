<?php

namespace Integration\Integration_v2\anymarket;

require_once APPPATH . "libraries/Integration_v2/Product_v2.php";

use GuzzleHttp\Exception\GuzzleException;
use Integration\Integration_v2\Product_v2;
use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Attributes\Custom\CustomApplicationAttributeService;

require_once APPPATH . "libraries/Integration_v2/anymarket/ApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/AnyMarketApiException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/TransformationException.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/AnyMarketConfiguration.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/AuthTools.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/RequestTools.php";
require_once APPPATH . "libraries/Validations/ProductIntegrationValidation.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/Validation/ProductIntegrationValidationAnyMarket.php";
require_once APPPATH . "libraries/Integration_v2/anymarket/Validation/VariationIntegrationAnyMarket.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomApplicationAttributeService.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\anymarket
 * @property AnyMarketConfiguration $config
 * @property \ProductIntegrationValidationAnyMarket $productIntegrationValidation
 * @property \VariationIntegrationAnyMarket $variationIntegrationAnyMarket
 * @property \Model_categories_anymaket_from_to $model_categories_anymaket_from_to
 * @property \Model_brand_anymaket_from_to $model_brand_anymaket_from_to
 * @property \Model_brands $model_brands
 * @property \Model_category $model_category
 * @property CustomApplicationAttributeService $customAppAttrService
 */
class ToolsProduct extends Product_v2
{

    use AuthTools, RequestTools;

    private $parsedProduct;

    public $parsedFullProduct = null;

    private $parsedVariations;

    private $config;

    public $productIntegrationValidation;
    private $variationIntegrationAnyMarket;

    private $updateOnlyPriceStock;
    private $updateProductCrossdocking;

    private $receivedProduct = null;
    public $announcementData;
    private $skuData;
    private $skuProductData;
    private $skuVariationsData;
    private $stockData;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'model_categories_anymaket_from_to',
            'model_brand_anymaket_from_to',
            'model_brands',
            'model_settings',
            'model_category'
        ]);
        $this->productIntegrationValidation = new \ProductIntegrationValidationAnyMarket(
            $this->model_products,
            $this->model_integrations,
            $this->db,
            $this
        );
        $this->variationIntegrationAnyMarket = new \VariationIntegrationAnyMarket();
        $this->customAppAttrService = new CustomApplicationAttributeService();
    }

    public function getUpdateOnlyPriceStock(): bool
    {
        return $this->updateOnlyPriceStock;
    }

    public function setProductReceived($product)
    {
        $this->receivedProduct = $product;
    }

    public function getDataProductIntegration($id)
    {
        $skuMarketplace = $id;
        try {
            $response = $this->request('GET', "/skumarketplace/$skuMarketplace");
            $body = $response->getBody();
            return json_decode($body->getContents());
        } catch (AnyMarketApiException $e) {
            throw new ApiException("Erro ao obter o produto $skuMarketplace na AnyMarket. {$e->getMessage()}", $e->getCode());
        }
    }

    public function getDataProductIntegrationById($id)
    {
        try {
            $response = $this->request('GET', "/skus/id/{$id}");
            $body = $response->getBody();
            return json_decode($body->getContents());
        } catch (AnyMarketApiException $e) {
            throw new ApiException("Erro ao obter o anuncio {$id} na AnyMarket", $e->getCode());
        }
    }

    public function getProductsPagination(array $filter = [], int $page = 1, int $size = 20)
    {
        $filter['limit'] = $size;
        $filter['offset'] = (--$page) * $size;
        try {
            $response = $this->request('GET', "/skumarketplace/all", ['query' => $filter]);
            $body = $response->getBody();
            return json_decode($body->getContents());
        } catch (AnyMarketApiException $e) {
            throw new ApiException("Erro ao obter produtos na AnyMarket", $e->getCode());
        }
    }

    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $this->parsedVariations = [];
        $this->announcementData = $this->skuData = $this->skuProductData = $this->stockData = null;
        $this->updateOnlyPriceStock = $this->isUpdateOnlyPriceStock();
        $this->updateProductCrossdocking = $this->isUpdateProductCrossdocking();
        try {
            $product = $this->receivedProduct ?? $this->getDataProductIntegration($payload['idSkuMarketplace']);
        } catch (ApiException $e) {
            $this->log_integration($e->getMessage(), "{$e->getResponseBody()}", "E");
            throw $e;
        }

        $this->announcementData = $product;
        $this->skuData = $product->sku ?? null;
        $this->skuProductData = $this->skuData->product ?? null;
        $this->skuVariationsData = $this->skuData->variations ?? null;
        $this->stockData = $product->stock ?? null;

        if (!$this->skuData) {
            throw new AnyMarketApiException("Erro ao consultar o produto {$payload['idSkuMarketplace']}. Objeto inválido.");
        }

        $price = $this->announcementData->discountPrice > 0 ? $this->announcementData->discountPrice : $this->announcementData->price;
        $eanProduct =  $this->announcementData->ean ?? ($this->skuProductData->hasVariations ? '' : ($this->skuData->ean ?? '')) ?? '';
        $this->parsedProduct = [
            'name' => ['value' => $this->announcementData->title ?? $this->skuData->title, 'field_database' => 'name'],
            'status' => [
                'value' => in_array($this->announcementData->status, ['ACTIVE', 'UNPUBLISHED', 'WITHOUT_STOCK'])
                    ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
                'field_database' => 'status'
            ],
            'sku' => [
                'value' => $this->skuData->partnerId,
                'field_database' => 'sku'
            ],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => $price, 'field_database' => 'price'],
            'list_price' => ['value' => $this->announcementData->price, 'field_database' => 'list_price'],
            'stock' => ['value' => $this->stockData->availableAmount ?? null, 'field_database' => 'qty'],
            'ean' => ['value' => $eanProduct, 'field_database' => 'EAN'],
            'origin' => ['value' => $this->skuProductData->origin->id ?? 0, 'field_database' => 'origin'],
            'ncm' => ['value' => $this->skuProductData->nbm->id ?? '', 'field_database' => 'NCM'],
            'net_weight' => ['value' => $this->skuProductData->weight, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => $this->skuProductData->weight, 'field_database' => 'peso_bruto'],
            'description' => ['value' => property_exists($this->skuProductData, 'description') ? $this->skuProductData->description : '', 'field_database' => 'description'],
            'guarantee' => ['value' => $this->skuProductData->warrantyTime ?? 0, 'field_database' => 'garantia'],
            'height' => ['value' => $this->skuProductData->height, 'field_database' => 'altura'],
            'depth' => ['value' => $this->skuProductData->length, 'field_database' => 'profundidade'],
            'width' => ['value' => $this->skuProductData->width, 'field_database' => 'largura'],
            'product_height' => ['value' => $this->skuProductData->height, 'field_database' => 'actual_height'],
            'product_depth' => ['value' => $this->skuProductData->length, 'field_database' => 'actual_depth'],
            'product_width' => ['value' => $this->skuProductData->width, 'field_database' => 'actual_width'],
            'images' => ['value' => [], 'field_database' => NULL],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            'extra_operating_time' => ['value' => $this->stockData->additionalTime ?? null, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => $this->skuProductData->category->id ?? '', 'field_database' => 'category_id'],
            'brand' => ['value' => $this->skuProductData->brand->id ?? '', 'field_database' => 'brand_id'],
            '_product_id_erp' => ['value' => $this->skuProductData->id, 'field_database' => 'product_id_erp'],
            '_current_product_id_erp' => ['value' => null, 'field_database' => null],
            '_published' => ['value' => false, 'field_database' => '']
        ];

        // Produto contém variação. Isso pode ser alterado caso o produto já exista.
        $this->parsedProduct['sku']['value'] = $this->skuProductData->hasVariations ? "{$this->parsedProduct['sku']['value']}-PRD" : $this->parsedProduct['sku']['value'];

        $productIdErp = $this->model_products->getByProductIdErpAndStore(
            $this->skuProductData->id,
            $this->store
        );

        if (!$this->skuProductData->hasVariations && empty($productIdErp)) {
            $productIdErp = $this->getProductGroupedBySkuVar($this->parsedProduct['sku']['value']);
        }

        $this->productIntegrationValidation->validateUpgradeableProduct(array_merge([
                'sku' => $this->parsedProduct['sku']['value'],
                'store_id' => $this->store,
                'product_id_erp' => $this->skuProductData->id
            ], ($this->skuProductData->hasVariations ? [
                'variation_sku' => $this->announcementData->skuInMarketplace ?? $this->skuData->partnerId
            ] : [])
            )
        );

        /*if (!empty($productIdErp)) {
            // Variação
            if (!empty($productIdErp['has_variants'])) {
                $variation_data = $this->getVariationBySkuVar($this->skuData->partnerId);
                if (!empty($variation_data) && $variation_data['variant_id_erp'] != $this->skuData->id) {
                    throw new \TransformationException(
                        "SKU {$this->skuData->partnerId} não processado pois não conseguiu reconhecer o sku corretamente, identificou o código {$variation_data['variant_id_erp']}, onde deveria ser {$this->skuData->id}"
                    );
                }
                if (!empty($variation_data) && $variation_data['prd_id'] != $productIdErp['id']) {
                    throw new \TransformationException(
                        "SKU {$this->skuData->partnerId} não processado pois não conseguiu reconhecer o sku corretamente, identificou o código {$this->skuData->partnerId}, em um produto diferente"
                    );
                }
            }
            // Simples
            else {
                if ($productIdErp['sku'] != $this->skuData->partnerId) {
                    throw new \TransformationException(
                        "SKU {$this->parsedProduct['sku']['value']} não processado pois não conseguiu reconhecer o produto corretamente, identificou o sku {$productIdErp['sku']}"
                    );
                }
            }
        }*/

        // Se não encontrou pelo id e a loja usa catálogo, deve consultar pelo sku do produto, caso tenha variação, consultar pelo sku da variação.

        if ($this->store_uses_catalog && empty($productIdErp)) {
            $productIdErp = $this->getProductForSku($this->skuData->partnerId);
            if (!empty($productIdErp)) {
                $this->productIntegrationValidation->setProduct($productIdErp);
            }
        } else {
            if (!empty($productIdErp)) {
                $this->productIntegrationValidation->setProduct($productIdErp);
            }
        }

        $existingProduct = $this->productIntegrationValidation->getProduct();
        $is_variation_grouped = $existingProduct && $existingProduct['is_variation_grouped'];
        if (isset($existingProduct['id'])) {
            if ($is_variation_grouped) {
                $this->parsedProduct['_sku_variation_agrupped'] = $this->parsedProduct['sku'];
                $this->parsedProduct['_sku_variation_agrupped']['value'] = $this->skuData->partnerId;
            }
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->productIntegrationValidation->isPublishedProduct($existingProduct['id'])) {
                $this->updateOnlyPriceStock = true;
                $this->parsedProduct['_published']['value'] = true;
            }
            if (!$this->updateProductCrossdocking) {
                unset($this->parsedProduct['extra_operating_time']);
            }
            $this->parsedProduct['_current_product_id_erp']['value'] = $existingProduct['product_id_erp'];
        }

        $this->parsedProduct['variations']['value'] = [];
        if ($this->skuProductData->hasVariations) {
            $this->parsedProduct['variations']['value'] = property_exists($this->skuData, 'variations')
                ? $this->getVariationFormatted($this->skuVariationsData, $this->announcementData) : [];
            $this->parsedProduct['name']['value'] = $this->skuProductData->title;
            $this->parsedProduct['stock']['value'] = null;
        } else if (in_array($this->announcementData->status, ['WITHOUT_STOCK', 'PAUSED'])) {
            $this->parsedProduct['stock']['value'] = 0;
        }

        if (!$this->updateOnlyPriceStock || !$this->productIntegrationValidation->productExists()) {
            $this->parsedProduct['brand']['value'] = $this->getLocalManufacturerByExternalId($this->skuProductData->brand);
            $this->parsedProduct['category']['value'] = $this->getLocalCategoryByExternalId($this->skuProductData->category);
            $this->parsedProduct['_manufacturer'] = [
                'value' => $this->parsedProduct['brand']['value'],
                'field_database' => 'brand_id'
            ];

            $this->parsedProduct['images']['value'] = $this->getProductImagesFormatted($this->skuProductData);
        }
        if (!empty($this->parsedProduct['ean']['value'] ?? '')) {
            $variation = current($this->parsedProduct['variations']['value'] ?? []) ?? [];
            if (!empty($variation['ean'] ?? '') && (strcasecmp($variation['ean'], $this->parsedProduct['ean']['value']) === 0)) {
                $this->parsedProduct['ean']['value'] = '';
            }
        }

        $this->parsedFullProduct = $this->parsedProduct;

        if ($this->updateOnlyPriceStock && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'extra_operating_time', 'variations', '_product_id_erp', '_current_product_id_erp', '_published', '_sku_variation_agrupped'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }

        return $this->parsedProduct;
    }

    public function getLocalManufacturerByExternalId($brand)
    {
        //$brand->id = 221412;
        $linkedBrand = $this->model_brand_anymaket_from_to->getData([
            'api_integration_id' => $this->integrationData['id'],
            'idBrandAnymarket' => $brand->id
        ]);
        if (empty($linkedBrand)) {
            $nameSellerCenter = !empty($this->nameSellerCenter ?? '') ? $this->nameSellerCenter : 'seller center';
            throw new \TransformationException(
                "Marca '{$brand->name}' (ID: {$brand->id}) não vinculada com '{$nameSellerCenter}'"
            );
        }
        $localBrand = $this->model_brands->getBrandData($linkedBrand['brand_id']);
        return $localBrand['name'];
    }

    public function getLocalCategoryByExternalId($category)
    {
        //$category->id = 761775;
        $linkedCategory = $this->model_categories_anymaket_from_to->getData([
            'api_integration_id' => $this->integrationData['id'],
            'idCategoryAnymarket' => $category->id
        ]);

        if (empty($linkedCategory)) {
            $nameSellerCenter = !empty($this->nameSellerCenter ?? '') ? $this->nameSellerCenter : 'seller center';
            throw new \TransformationException(
                "Categoria '{$category->name}' (ID: {$category->id}) não vinculada com '{$nameSellerCenter}'"
            );
        }

        $localCategory = $this->model_category->getCategoryData($linkedCategory['categories_id']);
        return $localCategory['name'];
    }

    public function getVariationFormatted(array $payload, $option = null): array
    {
        $parsedVariations = [];
        $parsedVariationImages = [];
        $variationsTypes = [];

        $this->variationIntegrationAnyMarket->setIntegrationData([
            'company_id' => $this->company,
            'store_id' => $this->store,
            'integration_id' => $this->integrationData['id']
        ]);
        foreach ($payload as $variation) {
            $typeVariation = $variation->type;
            if ($typeVariation->visualVariation) {
                $parsedVariationImages = array_merge(
                    $parsedVariationImages,
                    $this->getVariationImagesFormatted(
                        $option->sku->product->images ?? [], $variation
                    )
                );
            }
            $variation = $this->variationIntegrationAnyMarket
                ->overwriteVariationWithIntegrationCustomAttribute($variation);
            $variationsTypes[strtolower($typeVariation->name)] = $variation->description;
        }

        if (!is_object($this->announcementData)) {
            return [];
        }
        $price = $this->announcementData->discountPrice > 0 ? $this->announcementData->discountPrice : $this->announcementData->price;
        $skuInMarketplace = $this->skuData->partnerId ?? null;
        if (strcmp($this->parsedProduct['sku']['value'], $skuInMarketplace) === 0) {
            $sufixo = '-PRD';
            if (substr($skuInMarketplace, -strlen($sufixo)) === $sufixo) {
                $skuInMarketplace = substr($skuInMarketplace, 0, -strlen($sufixo));
            }
        }
        $sku = $skuInMarketplace;

        $parsedVariation = [
            'id' => 0,
            'sku' => $sku,
            'stock' => $this->stockData->availableAmount ?? null,
            'price' => $price,
            'list_price' => $this->announcementData->price,
            'ean' => $this->skuData->ean ?? $this->announcementData->ean ?? '',
            'variations' => $variationsTypes,
            'images' => $parsedVariationImages,
            'status' => in_array($this->announcementData->status, ['ACTIVE', 'UNPUBLISHED', 'WITHOUT_STOCK'])
                ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
            '_variant_id_erp' => $this->skuData->id,
            '_current_variant_id_erp' => null,
            '_published' => $this->parsedProduct['_published']['value']
        ];

        if (in_array($this->announcementData->status, ['WITHOUT_STOCK', 'PAUSED'])) {
            $parsedVariation['stock'] = 0;
        }

        if ($this->productIntegrationValidation->productExists()) {
            $prd = $this->productIntegrationValidation->getProduct();
            $variation = $this->model_products->getVariantByPrdIdAndSku($prd['id'], $sku);
            if (empty($variation)) {
                $variation = $this->model_products->getVariantByPrdIdAndIDErp($prd['id'], $this->skuData->id);
            }
            $parsedVariation['id'] = $variation['id'] ?? 0;
            $parsedVariation['_current_variant_id_erp'] = $variation['variant_id_erp'] ?? null;
            if (!empty($parsedVariation['id'] ?? 0)) {
                if ($this->updateOnlyPriceStock) {
                    $updatableFields = [
                        'id', 'sku', 'status', 'price', 'list_price', 'stock', '_variant_id_erp', '_current_variant_id_erp', '_published'
                    ];
                    $parsedVariation = array_intersect_key($parsedVariation, array_flip($updatableFields));
                }
            }
        }

        $parsedVariations[] = $parsedVariation;

        $this->parsedVariations = $parsedVariations;
        return $parsedVariations;
    }

    public function getParsedProduct()
    {
        return $this->parsedProduct;
    }

    public function getParsedVariations()
    {
        return $this->parsedVariations;
    }

    protected function getProductImagesFormatted($product)
    {
        $images = !empty($product->imagesWithoutVariationValue) ? $product->imagesWithoutVariationValue : [];
        return $this->getImagesFormatted($images);
    }

    protected function getVariationImagesFormatted($productImages, $currentVariation)
    {
        $images = [];
        foreach ($productImages as $productImage) {
            if (strcasecmp($productImage->variation ?? '', $currentVariation->description) === 0) {
                array_push($images, $productImage);
            }
        }
        return $this->getImagesFormatted($images);
    }

    public function getImagesFormatted(array $payload): array
    {
        $images = [];
        foreach ($payload as $image) {
            $urlImage = !empty($image->standardUrl ?? '') ? $image->standardUrl : (!empty($image->url ?? '') ? $image->url : $image->originalImage);
            if ($image->main) {
                $this->parsedProduct['_principal_image'] = [
                    'value' => $urlImage,
                    'field_database' => 'principal_image'
                ];
            }
            array_push($images, $urlImage); // $image->standardUrl
        }
        return $images;
    }

    public function getFormattedProductFieldsToUpdate($payload, $idIntegration = null): array
    {
        $statusToUpdated = $payload->status == 'ACTIVE' ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT;
        $product = $this->model_products->getByProductIdErpAndStore($idIntegration ?: $payload->sku->product->id, $this->store);
        $this->parsedVariations = [];
        if (!$product) {
            $variation = $this->model_products->getVariantsByVariant_id_erp($idIntegration ?: $payload->sku->id);
            if (!$variation) {
                throw new \Exception("Não foi localizado nenhum produto ou variação vinculado ao Seller Center {$this->sellerCenter}.");
            }
            $product = $this->model_products->getProductData(0, $variation['prd_id']);
            if (!$product) {
                throw new \Exception("Não foi localizado nenhum produto  vinculado ao Seller Center {$this->sellerCenter}.");
            }
        }

        $price = $payload->discountPrice > 0 ? $payload->discountPrice : $payload->price;
        $prdVariations = $this->model_products->getVariantsByProd_id($product['id']);
        if (!empty($prdVariations)) {
            $this->parsedVariations = [];
            $hasActiveVar = false;
            foreach ($prdVariations as $prdVariation) {
                $validate_sku =  $product['is_variation_grouped'] ? ($prdVariation['sku'] == $payload->skuInMarketplace) : ($prdVariation['variant_id_erp'] == $payload->idSku);
                if ($prdVariation['variant_id_erp'] == $validate_sku) {
                    $prdVariation['status'] = $statusToUpdated;
                    $this->parsedVariations[] = [
                        'id' => ['value' => $prdVariation['id'], 'field_database' => 'id'],
                        'status' => ['value' => $statusToUpdated, 'field_database' => 'status'],
                        'sku' => ['value' => $prdVariation['sku'], 'field_database' => 'sku'],
                        'qty' => ['value' => $payload->availableAmount ?? 0, 'field_database' => 'qty'],
                        'price' => ['value' => $price ?? 0, 'field_database' => 'price'],
                        'list_price' => ['value' => $payload->price ?? 0, 'field_database' => 'list_price'],
                        '_variant_id_erp' => ['value' => $prdVariation['variant_id_erp'], 'field_database' => 'variant_id_erp'],
                    ];
                }

                $hasActiveVar = \Model_products::isActive($prdVariation) ? true : $hasActiveVar;
            }
            $statusToUpdated = $hasActiveVar ? \Model_products::ACTIVE_PRODUCT : $statusToUpdated;
        }
        $this->parsedProduct = [
            'id' => ['value' => $product['id'], 'field_database' => 'id'],
            'status' => ['value' => $statusToUpdated, 'field_database' => 'status'],
            'sku' => ['value' => $product['sku'], 'field_database' => 'sku'],
            'variations' => ['value' => $this->parsedVariations, 'field_database' => 'has_variants'],
            'price' => ['value' => $price ?? 0, 'field_database' => 'price'],
            'list_price' => ['value' => $payload->price ?? 0, 'field_database' => 'list_price'],
            '_product_id_erp' => ['value' => $product['product_id_erp'], 'field_database' => 'product_id_erp'],
        ];
        if (empty($this->parsedVariations)) {
            $this->parsedProduct['stock'] = ['value' => $payload->availableAmount ?? 0, 'field_database' => 'qty'];
        }
        return $this->parsedProduct;
    }

    public function getPriceErp($id)
    {
        // TODO: Implement getPriceErp() method.
    }

    public function getStockErp($id)
    {
        // TODO: Implement getStockErp() method.
    }

    public function getPriceStockErp($id)
    {
        // TODO: Implement getPriceStockErp() method.
    }

    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        if (empty($this->announcementData)) {
            try {
                $this->announcementData = $this->getDataProductIntegration($productIntegration);
            } catch (\Throwable $e) {

            }
        }
        $categoryId = $this->model_products->getProductData(0, $productId)['category_id'] ?? json_encode([]);
        $categoryId = current(json_decode($categoryId) ?? []);
        if (empty($categoryId)) return [];

        $attributes = [];
        foreach ($this->announcementData->attributes ?? [] as $code => $value) {
            $customAttr = $this->customAppAttrService->getCustomAttributeByCriteria([
                'company_id' => $this->company,
                'store_id' => $this->store,
                'category_id' => $categoryId,
                'module' => CustomAttribute::PRODUCT_CATEGORY_ATTRIBUTE_MODULE,
                'code' => $code,
            ]);
            if(!$customAttr->exists()) continue;
            $attrName = $customAttr->getValueByColumn('name');
            $attributes[$attrName] = $value;
            $customAttrValue = $this->customAppAttrService->getCustomAttributeValueByCriteria([
                'company_id' => $this->company,
                'store_id' => $this->store,
                'custom_application_attribute_id' => $customAttr->getValueByColumn('id'),
                'code' => $value,
            ]);
            if(!$customAttrValue->exists()) continue;
            $attributes[$attrName] = $customAttrValue->getValueByColumn('value');
        }
        return $attributes;
    }

    private function isUpdateOnlyPriceStock()
    {
        return $this->config->getParameterValueByName('updateProductPriceStock') ?? true;
    }

    private function isUpdateProductCrossdocking()
    {
        return $this->config->getParameterValueByName('updateProductCrossdocking') ?? true;
    }

    public function getProductIdBySku($sku)
    {
        $product = $this->getProductForSku($sku);
        return $product['id'] ?? 0;
    }

    public function getVariationIdBySku(string $sku, string $skuVar): int
    {
        $variation = $this->getVariationForSkuAndSkuVar($sku, $skuVar);
        return $variation['id'] ?? 0;
    }

    public function sendTransmissionIntegration($body, bool $success, ?int $stock_sku = null, $message = ''): array
    {
        $normalizedVar = $this->normalizedFormattedData(current($this->parsedVariations ?? []));
        $normalizedProd = $this->normalizedFormattedData($this->parsedProduct);
        $productId = $body['productId'] ?? (($normalizedProd['id'] ?? 0) > 0 ? $normalizedProd['id'] : null);

        if (!$this->store_uses_catalog) {
            $body['productId'] = ($normalizedVar['id'] ?? 0) > 0 ? $normalizedVar['id'] : ($body['id'] ?? $productId ?? null);
            $body['sku'] = !empty($normalizedVar['sku'] ?? 0) ? $normalizedVar['sku'] : ($normalizedProd['_sku_variation_agrupped'] ?? (!empty($normalizedProd['sku'] ?? 0) ? $normalizedProd['sku'] : ($body['sku'] ?? null)));
        }

        $body['status'] = empty($body['productId']) ? 'UNPUBLISHED' : $body['status'] ?? 'PAUSED';
        $body['status'] = empty($stock_sku) && $body['status'] !== 'UNPUBLISHED' ? 'WITHOUT_STOCK' : $body['status'];
        $new_sku = empty($normalizedProd['id']) || (!empty($normalizedVar) && empty($normalizedVar['id']));
        $body['marketplaceStatus'] = $this->getMarketplaceNameStatus($body['status'], $new_sku) ?? "ATIVO";

        $data = [
            'idInMarketplace'       => (empty($body['skuInMarketplace']) ? $body['sku'] : $body['skuInMarketplace']) ?? $body['idInMarketplace'] ?? (empty($body['id'] ?? 0) ? $body['productId'] : $body['id']),
            'idInSite'              => $body['productId'] ?? (isset($body['idInSite']) ?: null),
            'skuInMarketplace'      => empty($body['skuInMarketplace']) ? $body['sku'] : $body['skuInMarketplace'],
            'marketplaceStatus'     => $body['marketplaceStatus'],
            'idSku'                 => $body['idSku'] ?? null,
            'transmissionStatus'    => $success ? "OK" : 'ERROR',
            'errorMsg'              => $body['errorMsg'] ?? $message,
            'status'                => $body['status'] ?? 'ACTIVE',
        ];
        $this->sendTransmission($body['idSkuMarketplace'], $data);
        return $data;
    }

    public function sendErrorTransmission($body, $message)
    {
        $body = $this->handleWithTransmissionData($body);
        $data = [
            'idInMarketplace' => !empty($body['productId']) ? $body['sku'] : null,
            'idInSite' => !empty($body['productId']) ? $body['productId'] : null,
            'skuInMarketplace' => !empty($body['productId']) ? (empty($body['skuInMarketplace']) ? $body['sku'] : $body['skuInMarketplace']) : null,
            'marketplaceStatus' => $body['marketplaceStatus'],
            'idSku' => $body['idSku'] ?? null,
            'transmissionStatus' => "ERROR",
            'errorMsg' => $body['errorMsg'] ?? $message,
            'status' => $body['status'],
        ];
        $this->sendTransmission($body['idSkuMarketplace'], $data);
        return $data;
    }

    public function sendSuccessTransmission($body)
    {
        $body = $this->handleWithTransmissionData($body);
        $body['id'] = empty($body['id'] ?? 0) ? $body['productId'] : $body['id'];
        $data = [
            'idInMarketplace' => $body['sku'] ?? $body['idInMarketplace'] ?? $body['id'],
            'idInSite' => $body['productId'] ?? (isset($body['idInSite']) ? isset($body['idInSite']) : null),
            'skuInMarketplace' => empty($body['skuInMarketplace']) ? $body['sku'] : $body['skuInMarketplace'],
            'marketplaceStatus' => $body['marketplaceStatus'] ?? "ATIVO",
            'idSku' => $body['idSku'] ?? null,
            'transmissionStatus' => "OK",
            'errorMsg' => '',
            'status' => $body['status'] ?? 'ACTIVE',
        ];
        $this->sendTransmission($body['idSkuMarketplace'], $data);
        return $data;
    }

    protected function handleWithTransmissionData($body)
    {
        $normalizedVar = $this->normalizedFormattedData(current($this->parsedVariations ?? []));
        $normalizedProd = $this->normalizedFormattedData($this->parsedProduct);
        $status = ($normalizedVar['status'] ?? -1) >= 0 ? $normalizedVar['status'] : (($normalizedProd['status'] ?? -1) >= 0 ? $normalizedProd['status'] : null);
        $productId = $body['productId'] ?? (($normalizedProd['id'] ?? 0) > 0 ? $normalizedProd['id'] : null);
        $overrideData = [];
        if (!empty($productId)) {
            $prod = $this->model_products->getProductData(0, $productId) ?? [];
            $status = $prod['status'] ?? $status;
            if (($normalizedVar['status'] ?? -1) >= 0) {
                $status = $normalizedVar['status'] ?? $status;
                $var = $this->model_products->getVariantionById($body['id'] ?? $normalizedVar['id'] ?? null) ?? [];
                if (((int)($var['qty'] ?? -1)) === 0) {
                    if (($var['status'] ?? 1) == \Model_products::INACTIVE_PRODUCT) {
                        $overrideData['status'] = 'PAUSED';
                    } else {
                        $overrideData['status'] = 'WITHOUT_STOCK';
                    }
                }
            } else {
                if (((int)($prod['qty'] ?? -1)) === 0) {
                    if (($prod['status'] ?? 1) == \Model_products::INACTIVE_PRODUCT) {
                        $overrideData['status'] = 'PAUSED';
                    } else {
                        $overrideData['status'] = 'WITHOUT_STOCK';
                    }
                }
            }
        }
        if (!$this->store_uses_catalog) {
            $body['productId'] = ($normalizedVar['id'] ?? 0) > 0 ? $normalizedVar['id'] : ($body['id'] ?? $productId ?? null);
            $body['sku'] = !empty($normalizedVar['sku'] ?? 0) ? $normalizedVar['sku'] : ($normalizedProd['_sku_variation_agrupped'] ?? (!empty($normalizedProd['sku'] ?? 0) ? $normalizedProd['sku'] : ($body['sku'] ?? null)));
        }
        $body['status'] = empty($body['productId']) ? 'UNPUBLISHED' : ($status == \Model_products::ACTIVE_PRODUCT ? 'ACTIVE' : ($body['status'] ?? 'PAUSED'));
        $body['marketplaceStatus'] = empty($body['productId']) ? 'NÃO PUBLICADO' : ($status == \Model_products::ACTIVE_PRODUCT ? 'ATIVO' : 'INATIVO');
        return array_merge($body, $overrideData);
    }

    public function sendTransmission($idSkuMarketplace, $data)
    {
        try {
            $this->requestResponse = $this->request('PUT', "/skumarketplace/{$idSkuMarketplace}", ['json' => $data]);
        } catch (\Throwable $e) {

        }
    }

    public function getProductValidationExists()
    {
        return $this->productIntegrationValidation->getProduct() ?? [];
    }

    /**
     * @throws ApiException
     */
    public function getProductByPartnerId(string $partnerId)
    {
        try {
            $response = $this->request('GET', "/skus/partnerId/$partnerId");
            $body = $response->getBody();
            return json_decode($body->getContents());
        } catch (AnyMarketApiException $e) {
            throw new ApiException("Erro ao obter produtos na AnyMarket", $e->getCode());
        }
    }

    public function setCheckedAnymarketQueue(array $product_queue): bool
    {
        $receiveData = json_decode($product_queue["received_body"] ?? '{}', true);

        $checked = 0;
        $update = array();

        if ($receiveData) {
            $onlySyncPrice  = $receiveData["onlySyncPrice"];
            $onlySyncStock  = $receiveData["onlySyncStock"];
            $onlySyncStatus = $receiveData["onlySyncStatus"];
            $updateStatus   = $receiveData["updateStatus"];
            $updatePrice    = $receiveData["updatePrice"];
            $updateStock    = $receiveData["updateStock"];

            if ($this->job == 'UpdatePriceStock') {
                if ($onlySyncPrice || $onlySyncStock) {
                    $checked = 1;
                }

                $receiveData["updatePrice"] = false;
                $receiveData["updateStock"] = false;

                if (!$updateStatus && !$onlySyncStatus) {
                    $checked = 1;
                }
            } else if ($this->job == 'UpdateProduct') {
                if ($onlySyncStatus) {
                    $checked = 1;
                }
                $receiveData["updateStatus"] = false;

                if (!$onlySyncPrice && !$onlySyncStock && !$updatePrice && !$updateStock) {
                    $checked = 1;
                }
            }
            $update['received_body'] = json_encode($receiveData, JSON_UNESCAPED_UNICODE);
        }
        $update['checked'] = $checked;

        return $this->db->update('anymarket_queue', $update, ['id' => $product_queue['id']]);
    }

    private function getMarketplaceNameStatus(string $status, bool $new_product): string
    {
        $status = mb_strtoupper($status);
        switch ($status) {
            case 'UNPUBLISHED':
                return $new_product ? "O Marketplace está processando a atualização" : "NÃO PUBLICADO";
            case 'ACTIVE':
                return "ATIVO";
            case 'PAUSED':
                return "PAUSADO";
            case 'CLOSED':
                return "FECHADO";
            case 'WITHOUT_STOCK':
                return "SEM ESTOQUE";
            default:
                return $status;
        }
    }

    /**
     * @param string $sku
     * @return mixed
     * @throws ApiException
     */
    public function getProductsBySku(string $sku)
    {
        $sku = str_replace('-PRD', '', $sku);
        $options = [
            'query' => [
                'skuPartnerId' => $sku
            ]
        ];
        try {
            $request = $this->request('GET', "/skumarketplace/all", $options);
            $response = json_decode($request->getBody()->getContents(), true);

            if (empty($response['content'])) {
                throw new AnyMarketApiException("Erro ao obter o sku $sku na AnyMarket. Não retornou conteúdo.");
            }

            $current_sku = array_filter($response['content'], function ($product) use ($sku) {
                return $product['sku']['partnerId'] == $sku;
            });

            if (empty($current_sku)) {
                throw new AnyMarketApiException("Erro ao obter o sku $sku na AnyMarket. Não encontrou o código sku referente ao partnerId no retorno.");
            }

            sort($current_sku);

            $sku_integration = $current_sku[0];
            $sku_integration['idSkuMarketplace'] = $sku_integration['id'];
            return $sku_integration;
        } catch (AnyMarketApiException $e) {
            throw new ApiException("Erro ao obter o sku $sku na AnyMarket. {$e->getMessage()}", $e->getCode());
        }
    }

    /**
     * @param array $payload
     * @throws ApiException
     */
    public function sendProductToNotification(array $payload)
    {
        // request to anymarket send product
        $urlCreateProduct = $this->process_url."Api/Integration/AnyMarket/Remotes/sendProduct";
        $queryCreateProduct = array(
            'json' => $payload,
            'headers' => array(
                'x-anymarket-token' => $this->credentials->token2
            )
        );

        try {
            $this->client_cnl->request('POST', $urlCreateProduct, $queryCreateProduct);
        } catch (GuzzleException $exception) {
            if ($exception->getCode() == 401) {
                $this->credentials->token2 = $this->credentials->token;
            }
            throw new ApiException($exception->getMessage(), $exception->getCode());
        }
    }

    public function getRealValueNormalized(array $array, string $field)
    {
        return array_key_exists($field, $array) &&
        is_array($array[$field]) &&
        array_key_exists('value', $array[$field]) ?
            $array[$field]['value'] :
            ($array[$field] ?? null);
    }

    public function checkIdSkuIntegration(array $normalizedProduct, array $parsedVariation = null)
    {
        $product_sku            = $normalizedProduct['sku'];
        $variation_sku          = $parsedVariation['sku'] ?? null;
        $product_id_erp         = $normalizedProduct['_product_id_erp'] ?? null;
        $current_product_id_erp = $this->getRealValueNormalized($normalizedProduct, '_current_product_id_erp');
        $variant_id_erp         = $parsedVariation['_variant_id_erp'] ?? null;
        $current_variant_id_erp = $parsedVariation['_current_variant_id_erp'] ?? null;

        $agrupped = array_key_exists('_sku_variation_agrupped', $normalizedProduct) && !empty($normalizedProduct['_sku_variation_agrupped']);
        if ($agrupped) {
            $variation_sku = $normalizedProduct['_sku_variation_agrupped'];
            $variant_id_erp = $product_id_erp;
            $product_id_erp = null;
            $variation = $this->getVariationForSkuAndSkuVar($product_sku, $variation_sku);
            if ($variation) {
                $current_variant_id_erp = $variation['variant_id_erp'];
            }
        }

        if ($this->store_uses_catalog || $agrupped) {
            if (empty($current_product_id_erp) && !empty($product_id_erp)) {
                $this->updateProductIdIntegration($product_sku, $product_id_erp);
            }
            if (empty($current_variant_id_erp) && !empty($variant_id_erp)) {
                $this->updateProductIdIntegration($product_sku, $variant_id_erp, $variation_sku);
            }
        }
    }
}