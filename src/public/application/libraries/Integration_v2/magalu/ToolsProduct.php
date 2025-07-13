<?php

namespace Integration\Integration_v2\magalu;

require APPPATH."libraries/Integration_v2/Product_v2.php";
require_once APPPATH.'libraries/Validations/ProductIntegrationValidation.php';
require_once APPPATH."libraries/Integration_v2/magalu/Validation/ProductIntegrationValidationMagalu.php";
require_once APPPATH.'libraries/Helpers/StringHandler.php';

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use InvalidArgumentException;
use libraries\Helpers\StringHandler;

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\magalu
 * @property \ProductIntegrationValidation $productIntegrationValidation
 */
class ToolsProduct extends Product_v2
{
    private $parsedProduct = [];

    private $parsedVariations = [];

    private $isPublishedProduct = false;

    private $productPayload;

    private $brandList = [];
    private $categoryList = [];
    private $categories = [];

    private $ncmList = [];
    private $originList = [];

    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->productIntegrationValidation = new \ProductIntegrationValidationMagalu(
            $this->model_products,
            $this->model_integrations,
            $this->db,
            $this
        );
    }

    /**
     * Define os atributos para o produto.
     *
     * @param  string  $productId  Código do produto (products.id).
     * @param  string  $productIntegration  Código do produto na integradora.
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"].
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        return array(
            'Cor' => 'Preto',
            'Tamanho' => 'M'
        );
    }

    public function getProductsPagination(array $filter, int $page = 1, int $size = 1000)
    {
        $query = $filter;
        $query['_limit'] = $size;
        $query['_page'] = $page;

        try {
            $request = $this->request('GET', '/products', ['query' => $query]);
            try {
                $contentResponse = $request->getBody()->getContents();
                $contentResponse = empty(trim($contentResponse)) ? '{}' : $contentResponse;
                $response = Utils::jsonDecode($contentResponse);
            } catch (\Throwable $e) {
                throw new Exception(sprintf("JSON DECODE ERROR: %s: %s", $request->getBody()->getContents(),
                    print_r($request, true)));
            }
            return $response;
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $message = $e->getResponse()->getBody()->getContents();
            }
            $code = $e->getCode();
            if (in_array((int) $code, [401, 403])) {
                $message = "Erro de autenticação/permissão ({$code}).";
            }
            throw new Exception($message);
        }
    }

    /**
     * Recupera dados do produto na integradora.
     *
     * @param  string  $id  Código do produto.
     */
    public function getDataProductIntegration(string $id)
    {
        $urlGetSku = "/products/$id";

        try {
            $request = $this->request('GET', $urlGetSku);
        } catch (InvalidArgumentException|GuzzleException $exception) {
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        return Utils::jsonDecode($request->getBody()->getContents());
    }

    protected function sendRequest(string $endpoint, array $options = [])
    {
        try {
            $request = $this->request('GET', $endpoint, $options);
        } catch (\Throwable $e) {
            return (object) [];
        }
        $result = Utils::jsonDecode($request->getBody()->getContents());
        return $result->obj ?? (object) [];
    }

    /**
     * Formata os dados do produto para criar ou atualizar.
     *
     * @param  array|object  $payload  Dados do produto para formatação.
     * @param  mixed  $option  Dados opcionais para auxílio na formatação.
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $this->parsedProduct = [];
        $this->productPayload = $payload;

        $decodedDescription = $this->productPayload->description;

        $ncm = '';
        $origin = 0;
        $warranty = $this->getValueFromFactsheet('prazo-de-garantia');

        $productIdErp = $this->productPayload->id;
        $eanProduct = $this->productPayload->ean;

        $brandName = $this->productPayload->brand;
        $categoryName = $this->mountCategoryName();
        $prodImages = $this->productPayload->medias;

        $heightPackage = $this->productPayload->dimensions->height ? $this->productPayload->dimensions->height * 100 : 0;
        $lengthPackage = $this->productPayload->dimensions->depth ? $this->productPayload->dimensions->depth * 100 : 0;
        $widthPackage = $this->productPayload->dimensions->width ? $this->productPayload->dimensions->width * 100 : 0;
        $weightPackage = $this->productPayload->dimensions->weight ?: 0;

        $height = $heightPackage;
        $length = $lengthPackage;
        $width = $widthPackage;
        $weight = $weightPackage;

        $price_stock_sku = $this->getPriceStockErp($productIdErp);
        $price_product = $price_stock_sku['price_product'];
        $listPrice_product = $price_stock_sku['listPrice_product'];
        $status = $price_stock_sku['availability'] && $price_stock_sku['active'];
        $stock_product = $price_stock_sku['stock_product'];

        $this->parsedProduct = [
            'name' => ['value' => $this->productPayload->title, 'field_database' => 'name'],
            'sku' => ['value' => $this->productPayload->parent_sku, 'field_database' => 'sku'],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => $price_product, 'field_database' => 'price'],
            'list_price' => ['value' => $listPrice_product, 'field_database' => 'list_price'],
            'stock' => [
                'value' => $stock_product,
                'field_database' => 'qty'
            ],
            'status' => [
                'value' => $status ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
                'field_database' => 'status'
            ],
            'ean' => ['value' => $eanProduct ?? '', 'field_database' => 'EAN'],
            'origin' => ['value' => $origin, 'field_database' => 'origin'],
            'ncm' => ['value' => $ncm, 'field_database' => 'NCM'],
            'net_weight' => ['value' => $weight, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => $weightPackage, 'field_database' => 'peso_bruto'],
            'description' => ['value' => $decodedDescription, 'field_database' => 'description'],
            'height' => ['value' => $heightPackage, 'field_database' => 'altura'],
            'depth' => ['value' => $lengthPackage, 'field_database' => 'profundidade'],
            'width' => ['value' => $widthPackage, 'field_database' => 'largura'],
            'product_height' => ['value' => $height, 'field_database' => 'actual_height'],
            'product_depth' => ['value' => $length, 'field_database' => 'actual_depth'],
            'product_width' => ['value' => $width, 'field_database' => 'actual_width'],
            'images' => ['value' => [], 'field_database' => null],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            'extra_operating_time' => ['value' => 0, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => $categoryName, 'field_database' => 'category_id'],
            'brand' => ['value' => $brandName ?? '', 'field_database' => 'brand_id'],
            'guarantee' => ['value' => $warranty, 'field_database' => 'garantia'],
            '_product_id_erp' => ['value' => $productIdErp, 'field_database' => 'product_id_erp'],
            '_published' => ['value' => false, 'field_database' => '']
        ];

        $this->productValidationHandler($this->productPayload);

        $existingProduct = $this->productIntegrationValidation->getProduct();
        
        if (($existingProduct['id'] ?? 0) > 0) {
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->isPublishedProduct) {
                $this->parsedProduct['_published']['value'] = true;
            }
//            $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > 0 ? $this->parsedProduct['price']['value'] : $existingProduct['price'];
//            $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > 0 ? $this->parsedProduct['list_price']['value'] : $existingProduct['list_price'];
        }

        $this->parsedProduct['variations']['value'] = $this->getVariationFormatted((array) $this->productPayload);

        if (empty($this->parsedProduct['variations']['value'])) {

            $this->parsedProduct['images']['value'] = $this->getImagesFormatted($this->productPayload->medias);
            $this->parsedProduct['sku']['value'] = $this->productPayload->sku;
            $price_stock_sku = $this->getPriceStockErp($productIdErp);
            $this->parsedProduct['price']['value'] = $price_stock_sku['price_product'];
            $this->parsedProduct['list_price']['value'] = $price_stock_sku['listPrice_product'];
            $this->parsedProduct['stock']['value'] = $price_stock_sku['stock_product'];
            $this->parsedProduct['status']['value'] = $price_stock_sku['availability'] && $price_stock_sku['active'] ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT;

        }

        if ($this->isPublishedProduct && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'variations', '_product_id_erp', '_published'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }

        return $this->parsedProduct;
    }

    public function productValidationHandler($productData)
    {
        $this->productIntegrationValidation->validateUpgradeableProduct([
                'sku' => $productData->parent_sku,
                'store_id' => $this->store,
                'product_id_erp' => $productData->id
            ]
        );
        $productIdErp = $this->model_products->getByProductIdErpAndStore(
            $productData->id,
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
     * @param  array|string|int  $id  Código do sku do produto.
     * @param  float|null  $price  Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array               Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null): array
    {
        $idErpExplode = explode('_', $id);
        $id_erp = $idErpExplode[0];
        $seller = $idErpExplode[1];
        $urlGetSku = "/products/pricing_and_availability";

        try {
            $request = $this->request('POST', $urlGetSku, array('json' => array(
                'products' => array(
                    array(
                        'sku'       => $id_erp,
                        'seller_id' => $seller
                    )
                ),
                'show_payment_methods' => false
            )));
        } catch (InvalidArgumentException|GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($response) || !is_array($response)){
            throw new InvalidArgumentException("Não encontrado disponibilidade para $id " . json_encode($response));
        }

        foreach ($response as $pricing_and_availability) {
            if ($pricing_and_availability->sku == $id_erp) {
                return array(
                    'price_product'         => $pricing_and_availability->price,
                    'price_variation'       => $pricing_and_availability->price,
                    'listPrice_product'     => $pricing_and_availability->list_price,
                    'listPrice_variation'   => $pricing_and_availability->list_price,
                    'availability'          => $pricing_and_availability->availability === 'in stock',
                    'active'                => $pricing_and_availability->active
                );
            }
        }

        throw new InvalidArgumentException("Não encontrado disponibilidade para $id " . json_encode($response));
    }

    /**
     * Formata os dados da variação.
     *
     * @param  array  $payload  Dados da variação para formatação.
     * @param  mixed  $option  Dados opcionais para auxílio na formatação.
     * @return  array
     * @throws Exception
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {

        $variation = $payload;

        $this->parsedVariations = [];

        $variationsTypes = [];
        foreach ($variation['attributes'] ?? [] as $variationAttr) {
            $variationsTypes[$variationAttr->type] = $variationAttr->value;
        }
        //hack para se o produto não ter atributo da variação, criar um com a marca
        if (!$variationsTypes) {
            //$variationsTypes['brand'] = $payload['brand'];
            return $this->parsedVariations;
        }

        $price_stock_sku = $this->getPriceStockErp($variation['id']);
        $price = $price_stock_sku['price_product'];
        $list_price = $price_stock_sku['listPrice_product'];
        $availability = $price_stock_sku['availability'];
        $active = $price_stock_sku['active'];
        $stock = $price_stock_sku['stock_product'];

        $parsedVariation = [
            'id' => 0,
            'sku' => $variation['sku'],
            'status' => $availability && $active ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
            'ean' => $this->productPayload->ean ?? '',
            'images' => $this->getImagesFormatted($this->productPayload->medias),
            'variations' => $variationsTypes,
            'price' => $price,
            'list_price' => $list_price,
            'stock' => $stock,
            '_parent_sku' => $this->parsedProduct['sku']['value'],
            '_variant_id_erp' => $variation['id']
        ];

//        $this->parsedProduct['price']['value'] = !empty($this->parsedProduct['price']['value']) || $this->parsedProduct['price']['value'] > ($parsedVariation['price'] ?? 0) ? $this->parsedProduct['price']['value'] : $parsedVariation['price'] ?? 0;
//        $this->parsedProduct['list_price']['value'] = !empty($this->parsedProduct['list_price']['value']) || $this->parsedProduct['list_price']['value'] > ($parsedVariation['list_price'] ?? 0) ? $this->parsedProduct['list_price']['value'] : $parsedVariation['list_price'] ?? 0;

        $this->parsedProduct['ncm']['value'] = '';
        $this->parsedProduct['origin']['value'] = 0;

        if ($this->productIntegrationValidation->productExists()) {
            $prd = $this->productIntegrationValidation->getProduct();
            $variation = $this->model_products->getVariantByPrdIdAndSku($prd['id'], $parsedVariation['sku']);
            if (empty($variation)) {
                $variation = $this->model_products->getVariantByPrdIdAndIDErp(
                    $prd['id'],
                    $parsedVariation['_variant_id_erp']
                );
            }
            $parsedVariation['id'] = $variation['id'] ?? 0;
//            $parsedVariation['stock'] = (int) $parsedVariation['stock'] ?? $variation['qty'];
//            $parsedVariation['price'] = $parsedVariation['price'] > 0 ? $parsedVariation['price'] : $variation['price'];
//            $parsedVariation['list_price'] = $parsedVariation['list_price'] > 0 ? $parsedVariation['list_price'] : $variation['list_price'] ?? $parsedVariation['price'];
            $parsedVariation['_parent_sku'] = $prd['sku'] ?? $parsedVariation['_parent_sku'];
            if (empty($parsedVariation['id'] ?? 0)) {
                $this->isPublishedProduct = false;
            }
            if ($this->isPublishedProduct) {
                $updatableFields = [
                    'id', 'sku', 'status', 'stock', 'price', 'list_price', '_parent_sku', '_variant_id_erp',
                    '_published'
                ];
                $parsedVariation = array_intersect_key($parsedVariation, array_flip($updatableFields));
            }
            $parsedVariation['_published'] = $this->isPublishedProduct;
        }

        if (
            empty($this->parsedVariations) &&
            empty($this->parsedProduct['images']['value']) &&
            !empty($this->credentials->save_images_in_father_product)
        ) {
            $this->parsedProduct['images']['value'] = $parsedVariation['images'];
        }

        $this->parsedVariations[] = $parsedVariation;

        return $this->parsedVariations;
    }

    /**
     * @param  array  $payload  Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $images = [];

        foreach ($payload as $image) {
            $images[] = str_replace('{w}x{h}', '9000x9000', $image);
        }

        return $images;

    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param  array|string|int  $id  Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array                   Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id): array
    {
        $idErpExplode = explode('_', $id);
        $id_erp = $idErpExplode[0];
        $seller = $idErpExplode[1];
        $urlGetSku = "/products/pricing_and_availability";

        try {
            $request = $this->request('POST', $urlGetSku, array('json' => array(
                'products' => array(
                    array(
                        'sku'       => $id_erp,
                        'seller_id' => $seller
                    )
                ),
                'show_payment_methods' => false
            )));
        } catch (InvalidArgumentException|GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($response) || !is_array($response)){
            throw new InvalidArgumentException("Não encontrado disponibilidade para $id " . json_encode($response));
        }

        foreach ($response as $pricing_and_availability) {
            if ($pricing_and_availability->sku == $id_erp) {
                return array(
                    'stock_product' => $pricing_and_availability->stock_count,
                    'stock_variation' => $pricing_and_availability->stock_count
                );
            }
        }

        throw new InvalidArgumentException("Não encontrado estoque para $id " . json_encode($response));
    }

    /**
     * Recupera o estoque de produto(s)
     * @warning Caso haja necessidade de implementar um método para recuperar os dois dados ao mesmo tempo.
     *
     * @param  array|string|int  $id  Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array                Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceStockErp($id): array
    {
        $idErpExplode = explode('_', $id);
        $id_erp = $idErpExplode[0];
        $seller = $idErpExplode[1];
        $urlGetSku = "/products/pricing_and_availability";

        try {
            $request = $this->request('POST', $urlGetSku, array('json' => array(
                'products' => array(
                    array(
                        'sku'       => $id_erp,
                        'seller_id' => $seller
                    )
                ),
                'show_payment_methods' => false
            )));
        } catch (InvalidArgumentException|GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode());
        }

        $response = Utils::jsonDecode($request->getBody()->getContents());

        if (empty($response) || !is_array($response)){
            throw new InvalidArgumentException("Não encontrado disponibilidade para $id " . json_encode($response));
        }

        foreach ($response as $pricing_and_availability) {
            if ($pricing_and_availability->sku == $id_erp) {
                $availability = $pricing_and_availability->availability === 'in stock';
                $active = $pricing_and_availability->active;
                return array(
                    'price_product'         => $pricing_and_availability->price,
                    'price_variation'       => $pricing_and_availability->price,
                    'listPrice_product'     => $pricing_and_availability->list_price,
                    'listPrice_variation'   => $pricing_and_availability->list_price,
                    'availability'          => $availability,
                    'active'                => $active,
                    'stock_variation'       => $availability && $active ? $pricing_and_availability->stock_count : 0,
                    'stock_product'         => $availability && $active ? $pricing_and_availability->stock_count : 0,
                );
            }
        }

        throw new InvalidArgumentException("Não encontrado disponibilidade para $id " . json_encode($response));
    }

    public function getParsedVariations(): array
    {
        return $this->parsedVariations;
    }

    private function mountCategoryName()
    {

        $category = (array) $this->productPayload->categories[0];
        $name = [];
        $name[] = $category['name'];
        if ($category['sub_categories']) {
            foreach (array_reverse($category['sub_categories']) as $subcategory) {
                $name[] = $subcategory->name;
            }
        }

        return implode(' > ', $name);

    }

    private function getValueFromFactsheet($slug)
    {

        foreach ($this->productPayload->factsheet as $factsheet) {
            if ($factsheet->slug != 'ficha-tecnica') {
                continue;
            }
            foreach ($factsheet->elements as $element) {
                if ($element->slug != $slug) {
                    continue;
                }
                return $element->elements[0]->value;
            }
        }

        return '';

    }
}