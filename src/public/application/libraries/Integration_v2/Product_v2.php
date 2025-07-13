<?php

namespace Integration\Integration_v2;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2;
use InvalidArgumentException;
use libraries\Attributes\Application\Resources\CustomAttribute;
use libraries\Attributes\Custom\CustomAttributeMapService;
use Psr\Http\Message\ResponseInterface;
use libraries\Helpers\StringHandler;

require_once "Integration_v2.php";
require_once APPPATH . "libraries/Attributes/Custom/CustomAttributeMapService.php";
require_once APPPATH . "libraries/Attributes/Application/Resources/CustomAttribute.php";

/**
 * Class Product_v2
 * @package Integration\Integration_v2
 * @property CustomAttributeMapService $customAttributeMapService
 */
abstract class Product_v2 extends Integration_v2
{
    const API_PRODUCT_STATUS_ACTIVE = 'enabled';
    const API_PRODUCT_STATUS_INACTIVE = 'disabled';

    const PRODUCT_IMAGE_LIMIT = 6;

    protected $productImageLimit = Product_v2::PRODUCT_IMAGE_LIMIT;

    /**
     * @var string|null|ResponseInterface
     */
    protected $requestResponse = null;

    public $allowable_tags = null;

    public $can_update_price_catalog = true;

    // tipo de variação para comparar, INFORMAR EM MINÚSCULO A CHAVE
    protected $typeVariation = array(
        'tamanho'   => 'size',
        'size'      => 'size',
        'tamanhos'   => 'size',
        'sizes'      => 'size',
        'tam'       => 'size',
        'sapato'    => 'size',
        'calcado'   => 'size',
        'calçado'   => 'size',
        'short'     => 'size',
        'calca'     => 'size',
        'calça'     => 'size',
        'camisa'    => 'size',
        'mascara'   => 'size',
        'aro'       => 'size',
        'medidas'   => 'size',
        'tamanho-e-medidas' => 'size',
        'tamanhos-e-medidas' => 'size',
        'tamanho e medidas' => 'size',
        'tamanhos e medidas' => 'size',

        'cor'       => 'color',
        'color'     => 'color',
        'cores'       => 'color',
        'colors'     => 'color',

        'sabor' => 'flavor',
        'sabores' => 'flavor',
        'aroma' => 'flavor',
        'aromas' => 'flavor',
        'flavor' => 'flavor',
        'flavors' => 'flavor',
        'flavour' => 'flavor',
        'flavours' => 'flavor',

        'voltagem'  => 'voltage',
        'voltage'   => 'voltage',
        'volts'     => 'voltage',
        'tensao/voltagem' => 'voltage',
        'tensao-voltagem' => 'voltage',
        'tension/voltage' => 'voltage',
        'tension-voltage' => 'voltage',

        'lado' => 'side',
        'lados' => 'side',
        'ladoo' => 'side',

        'grau'  => 'degree',
        'graus' => 'degree',
        'grauu' => 'degree',

    );

    protected $typeVariationAttrMapped = [];

    // tipo de unidade para comparar, INFORMAR A CHAVE EM MINÚSCULO
    private $typesUnity = array(
        'jg'    => 'UN',
        'pc'    => 'UN',
        'pç'    => 'UN',
        'und'   => 'UN',
        'vd'    => 'UN',
        'un'    => 'UN',
        'par' => 'UN'
    );

    private $mappedProductAPI;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('text');
        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }
        $this->productImageLimit = $this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? Product_v2::PRODUCT_IMAGE_LIMIT;
        $this->productImageLimit = empty($this->productImageLimit ?? 0) ? Product_v2::PRODUCT_IMAGE_LIMIT : $this->productImageLimit;
        $this->customAttributeMapService = new CustomAttributeMapService();
        $this->can_update_price_catalog = !$this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
    }

    /**
     * Recupera se o preço do produto|sku
     *
     * @param   array|string|int    $id Código do sku do produto
     * @return  array|null|bool     Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    abstract public function getPriceErp($id);

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array|null|bool         Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    abstract public function getStockErp($id);

    /**
     * Recupera o preço e estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     * @return  array|null|bool     Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    abstract public function getPriceStockErp($id);

    /**
     * Formata os dados do produto para criar ou atualizar
     *
     * @param   array|object $payload   Dados do produto para formatação
     * @param   mixed        $option    Dados opcionais para auxílio na formatação
     * @return  array                   Retorna o preço do produto.
     */
    abstract public function getDataFormattedToIntegration($payload, $option = null): array;

    /**
     * Recupera dados do produto na integradora
     *
     * @param   string $id Código do produto
     */
    abstract public function getDataProductIntegration(string $id);

    /**
     * @param   array   $payload    Dados da variação para formatação
     * @param   mixed   $option     Dados opcionais para auxílio na formatação
     * @return  array
     */
    abstract public function getVariationFormatted(array $payload, $option = null): array;

    /**
     * @param   array   $payload    Dados de imagens para formatação
     * @return  array
     */
    abstract public function getImagesFormatted(array $payload): array;

    /**
     * Define os atributos para o produto
     *
     * @param   string      $productId          Código do produto (products.id)
     * @param   string      $productIntegration Código do produto na integradora
     * @return  array|null                      ["Cor": "Vermelho", "Tipo": "Masculino", "Composição": "Plástico"]
     */
    abstract public function getAttributeProduct(string $productId, string $productIntegration): ?array;

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku(string $sku): ?array
    {
        return $this->model_products->getProductCompleteBySkyAndStore($sku, $this->store);
    }

    /**
     * Recupera dados do produto pelo SKU da variação
     *
     * @param   string      $sku    SKU da variação
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getVariationBySkuVar(string $sku): ?array
    {
        return $this->model_products->getVariantsBySkuAndStore($sku, $this->store);
    }

    /**
     * Recupera dados da variação do produto pelo SKU do produto e SKU da variação
     *
     * @param   string    $sku      SKU do produto
     * @param   string    $skuVar   SKU da variação
     * @return  null|array          Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForSkuAndSkuVar(string $sku, string $skuVar): ?array
    {
        return $this->model_products->getVariationForSkuAndSkuVar($sku, $this->store, $skuVar);
    }

    /**
     * Cria um produto
     *
     * @param   array   $payload    Dados do produto para cadastro
     */
    public function createProduct(array $payload)
    {
        $match_produto_por_ean = $this->model_settings->getValueIfAtiveByName('match_produto_por_EAN');

        // Loja que usa catálogo não pode criar produtos.
        if ($this->store_uses_catalog && !$match_produto_por_ean) {
            if (!isset($this->credentials->api_internal['import_internal_by_csv']) || !$this->credentials->api_internal['import_internal_by_csv']) {
                return true;
            }

            $erroMessage = 'A loja utiliza catálogo, não é possível criar produtos.';

            $this->log_integration(
                "Erro para integrar produto ({$payload['sku']['value']})",
                "<h4>Existem alguns inpeditivos no cadastro do produto</h4><p>$erroMessage</p><br><strong>SKU</strong>: {$payload['sku']['value']}<br><strong>Descrição</strong>: {$payload['name']['value']}",
                "E"
            );
            throw new InvalidArgumentException($erroMessage);
        }

        // a unidade precisamos pegar o nome correto no de-para que fica na variável "typesUnity"
        if (array_key_exists(strtolower($payload['unity']['value']), $this->typesUnity)) {
            $payload['unity']['value'] = $this->typesUnity[strtolower($payload['unity']['value'])];
        }

        $product = array(
            "name"                  => $payload['name']['value'] ?? '',
            "sku"                   => $payload['sku']['value'] ?? '',
            "active"                => ($payload['status']['value'] ?? 0) == 1 ? "enabled" : "disabled",
            "description"           => $payload['description']['value'] ?? '',
            "price"                 => roundDecimal($payload['price']['value'] ?? 0),
            "list_price"            => roundDecimal($payload['list_price']['value'] ?? $payload['price']['value'] ?? 0),
            "qty"                   => (int)($payload['stock']['value'] ?? 0),
            "ean"                   => $payload['ean']['value'] ?? '',
            "sku_manufacturer"      => $payload['sku_manufacturer']['value'] ?? '',
            "net_weight"            => roundDecimal($payload['net_weight']['value'] ?? 0, 3),
            "gross_weight"          => roundDecimal($payload['gross_weight']['value'] ?? 0, 3),
            "width"                 => roundDecimal($payload['width']['value'] ?? 0),
            "height"                => roundDecimal($payload['height']['value'] ?? 0),
            "depth"                 => roundDecimal($payload['depth']['value'] ?? 0),
            "product_width"         => roundDecimal($payload['product_width']['value'] ?? $payload['width']['value'] ?? 0),
            "product_height"        => roundDecimal($payload['product_height']['value'] ?? $payload['height']['value'] ?? 0),
            "product_depth"         => roundDecimal($payload['product_depth']['value'] ?? $payload['depth']['value'] ?? 0),
            "items_per_package"     => (int)($payload['items_per_package']['value'] ?? 1),
            "guarantee"             => (int)($payload['guarantee']['value'] ?? 0),
            "origin"                => $payload['origin']['value'] ?? 0,
            "unity"                 => $payload['unity']['value'] ?? '',
            "ncm"                   => onlyNumbers($payload['ncm']['value'] ?? ''),
            "manufacturer"          => $payload['brand']['value'] ?? '',
            "extra_operating_time"  => (int)($payload['extra_operating_time']['value'] ?? 0),
            "category"              => $payload['category']['value'] ?? '',
            "images" => array_chunk($payload['images']['value'] ?? [], $this->productImageLimit)[0] ?? [],
        );

        if ($payload['variations']['value'] && count($payload['variations']['value'])) {
            $arrTypesVariations = array();
            foreach ($payload['variations']['value'] ?? [] as $key => $variation) {

                if (!array_key_exists('product_variations', $product)) {
                    $product['product_variations'] = array();
                }

                $product['product_variations'][$key] = array(
                    "sku"         => $variation['sku'],
                    "qty"         => (int)$variation['stock'],
                    "price"       => roundDecimal($variation['price'] ?? 0),
                    "list_price"  => roundDecimal($variation['list_price'] ?? $variation['price'] ?? 0),
                    "EAN"         => $variation['ean'] ?? null,
                    "images"      => array_chunk($variation['images'] ?? array(), $this->productImageLimit)[0] ?? []
                );

                if (isset($variation['stock'])) {
                    $product['product_variations'][$key]['qty'] = (int)$variation['stock'];
                }

                if (!empty($variation['variations']) && is_array($variation['variations'])) {
                    foreach ($variation['variations'] as $type => $valueType) {

                        if (array_key_exists(StringHandler::slugify($type), $this->typeVariation)) {
                            $type = $this->typeVariation[strtolower($type)];
                        } elseif (array_key_exists($type, $this->typeVariation)) {
                            $type = $this->typeVariation[$type];
                        }

                        $product['product_variations'][$key][$type] = $valueType;

                        if (!in_array($type, $arrTypesVariations)) {
                            $arrTypesVariations[] = $type;
                        }
                    }
                }
            }
            $product['types_variations'] = $arrTypesVariations;

            if (count($product['product_variations']) && !count($product['types_variations'])) {
                $erroMessage = 'O produto contém variação, mas não foram encontrados os seus tipos. Faça o envio dos tipos nas variações do produto.';
                $this->log_integration(
                    "Erro para integrar produto ({$payload['sku']['value']})",
                    "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>$erroMessage</p><br><strong>SKU</strong>: {$payload['sku']['value']}<br><strong>Descrição</strong>: {$payload['name']['value']}" . $this->createButtonLogRequestIntegration($product),
                    "E");
                throw new InvalidArgumentException($erroMessage);
            }
        }

        // request to create product
        $urlCreateProduct = $this->process_url."Api/V1/Products";
        $queryCreateProduct = array(
            'json' => array(
                'product' => $product
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->mappedProductAPI = $product;
            $this->setRequestResponse($this->client_cnl->request('POST', $urlCreateProduct, $queryCreateProduct));
        } catch (GuzzleException $exception) {
            $this->setRequestResponse($exception);
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            /*$this->log_integration(
                "Erro para integrar produto ({$payload['sku']['value']})",
                "<h4>Existem algumas pendências no cadastro do produto, para corrigir na integradora</h4><p>$erroMessage</p><br><strong>SKU</strong>: {$payload['sku']['value']}<br><strong>Descrição</strong>: {$payload['name']['value']}",
                "E");*/

            throw new InvalidArgumentException($erroMessage);
        }

        $integrationProductPayload = $this->getIntegrationProductPayload();
        /*$this->log_integration(
            "Produto ({$payload['sku']['value']}) integrado",
            "<h4>Novo produto integrado com sucesso</h4><ul><li>O produto ({$payload['sku']['value']}), foi integrado com sucesso</li></ul><br><strong>SKU</strong>: {$payload['sku']['value']}<br><strong>Nome do Produto</strong>: {$payload['name']['value']}" .
            ($integrationProductPayload ? "\nPayload:\n" . json_encode($integrationProductPayload) : ''),
            "S");*/
        return true;
    }

    /**
     * Cria um produto
     *
     * @param   array   $payload    Dados do produto para cadastro
     */
    public function createAssociateCatalogProduct(array $payload)
    {
        // Loja que usa catálogo não pode criar produtos.
        if (!$this->store_uses_catalog) {
            throw new InvalidArgumentException("Loja não contém catálogo");
        }

        // request to create product
        $urlCreateProduct = $this->process_url."Api/V1/Catalogs/associate";
        $queryCreateProduct = array(
            'json' => array(
                'catalog_associate' => $payload
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->mappedProductAPI = $payload;
            $this->setRequestResponse($this->client_cnl->request('POST', $urlCreateProduct, $queryCreateProduct));
        } catch (GuzzleException $exception) {
            $this->setRequestResponse($exception);
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            throw new InvalidArgumentException($erroMessage);
        }
    }

    public function getRequestResponse()
    {
        return $this->requestResponse;
    }

    /**
     * Cria uma variação
     *
     * @param   array   $payload    Dados da variação para cadastro
     * @param   string  $skuPai     Código SKU do produto pai (products.sku)
     * @return  bool                Retorna o estado do cadastro
     */
    public function createVariation(array $payload, string $skuPai): bool
    {
        // Loja que usa catálogo não pode criar variações.
        if ($this->store_uses_catalog) {
            return true;
        }

        $variation = array(
            "types_variations"      => array(),
            "product_variations"    => array(),
        );

        $variation['product_variations'][] = array(
            "sku_variation" => $payload['sku'],
            "qty"           => $payload['stock'],
            "price"         => $payload['price'] ?? 0,
            "list_price"    => $payload['list_price'] ?? $payload['price'] ?? 0,
            "EAN"           => $payload['ean'] ?? null,
            "images"        => array_chunk($payload['images'] ?? [], $this->productImageLimit)[0] ?? []
        );

        foreach ($payload['variations'] ?? [] as $type => $valueType) {
            if (array_key_exists(StringHandler::slugify($type), $this->typeVariation)) {
                $type = $this->typeVariation[strtolower($type)];
            } elseif (array_key_exists($type, $this->typeVariation)) {
                $type = $this->typeVariation[$type];
            }

            $variation['product_variations'][0][$type] = $valueType;

            if (!in_array($type, $variation['types_variations'])) {
                $variation['types_variations'][] = $type;
            }
        }

        // request to create variation
        $urlCreateVariation = $this->process_url."Api/V1/Variations/$skuPai";
        $queryCreateVariation = array(
            'json' => array(
                'variation' => $variation
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->setRequestResponse($this->client_cnl->request('POST', $urlCreateVariation, $queryCreateVariation));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->setRequestResponse($exception);

            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            /*$this->log_integration(
                "Erro para integrar a variação ({$payload['sku']}) no produto ($skuPai)",
                "<h4>Existem algumas pendências no cadastro da variação na plataforma de integração</h4><p>$erroMessage</p>",
                "E"
            );*/

            return false;
        }

        /*$this->log_integration(
            "Variação ({$payload['sku']}) integrada no produto ($skuPai)",
            "<h4>Nova variação integrada com sucesso</h4><p>O produto ($skuPai), recebeu uma nova variação com o sku ({$payload['sku']})</p>",
            "S"
        );*/

        return true;
    }

    /**
     * Define especificações para o produtos
     *
     * @param   int     $productId  Código do produto (products.id)
     * @param   array   $attributes Atributos para criação. ["Cor": "Vermelho", "Tipo": "Masculino", "Composição": "Plástico"]
     * @param   bool    $is_update É uma atualização
     * @todo Fazer com que a criação de atributos no produto seja feito pela nossa API
     *
     */
    public function setAttributeProduct(int $productId, array $attributes, bool $is_update = false)
    {
        // transforma as chaves em minúsculo para comparação
        $attributes_original        = $attributes;
        $attributes                 = array_change_key_case($attributes, CASE_LOWER);
        $attribute_keys             = array_keys($attributes);
        $attribute_marketplace_keys = array();
        $attributes_in_use          = array_map(function($attribute) {
            return strtolower($attribute['name']);
        }, ($is_update ? $this->model_atributos_categorias_marketplaces->getAllAttributesInUse($productId) : array()));

        $attributesMarketplace = $this->model_atributos_categorias_marketplaces->getAttributeMarketplaceByProductAndAttribute($productId, $attribute_keys);
        foreach ($attributesMarketplace as $attributeMarketplace) {
            $valueSelected = null;

            // é uma listagem de valores, é preciso percorrer e encontrar se o valor está na lista
            if ($attributeMarketplace['tipo'] === 'list') {
                // Descodificar o json para ver se existe valores definidos
                $decodeValuesRequired = Utils::jsonDecode($attributeMarketplace['valor_obrigatorio']);
                if (count($decodeValuesRequired)) {
                    foreach ($decodeValuesRequired as $valueRequired) {
                        // VTEX
                        if (
                            property_exists($valueRequired,'Value') &&
                            property_exists($valueRequired,'FieldValueId') &&
                            property_exists($valueRequired,'IsActive') &&
                            property_exists($valueRequired,'Position')
                        ) {
                            if (isset($valueRequired->Value) && isset($attributes[strtolower($attributeMarketplace['nome'])])
                                && strtolower($valueRequired->Value) == strtolower($attributes[strtolower($attributeMarketplace['nome'])])) {
                                $valueSelected = $valueRequired->FieldValueId;
                                break;
                            }
                        }
                        // Mercado Livre
                        elseif (
                            property_exists($valueRequired,'id') &&
                            property_exists($valueRequired,'name')
                        ) {
                            if (strtolower($valueRequired->name) == strtolower($attributes[strtolower($attributeMarketplace['nome'])])) {
                                $valueSelected = $valueRequired->id;
                                break;
                            }
                        }
                        // Via Varejo
                        elseif (
                            property_exists($valueRequired,'udaValueId') &&
                            property_exists($valueRequired,'udaValue')
                        ) {
                            if (strtolower($valueRequired->udaValue) == strtolower($attributes[strtolower($attributeMarketplace['nome'])])) {
                                $valueSelected = $valueRequired->udaValueId;
                                break;
                            }
                        }
                    }
                }
            } else { // tipo = string
                $valueSelected = $attributes[strtolower($attributeMarketplace['nome'])] ?? null;
            }

            if ($valueSelected !== null) {
                $attribute_marketplace_keys[] = strtolower($attributeMarketplace['nome']);

                // Atributos já está em uso.
                if (in_array(strtolower($attributeMarketplace['nome']), $attributes_in_use)) {
                    continue;
                }

                // atributo ainda não existe na tabela produtos_atributos_marketplaces, precisa criar
                if ($attributeMarketplace['valor_atributo'] === null) {
                    $this->model_atributos_categorias_marketplaces->createProductAttributeMarketplace(array(
                        'id_product'    => $productId,
                        'id_atributo'   => $attributeMarketplace['id_atributo'],
                        'valor'         => $valueSelected,
                        'int_to'        => $attributeMarketplace['int_to']
                    ));
                    continue;
                }

                // update attribute
                $this->model_atributos_categorias_marketplaces->updateProductAttributeMarketplace($valueSelected, array(
                    'id_product'    => $productId,
                    'id_atributo'   => $attributeMarketplace['id_atributo'],
                    'int_to'        => $attributeMarketplace['int_to']
                ));
            }
        }

        foreach ($attributes_original as $attribute_key => $attribute_value) {
            // Se o atributo existe no marketplace, não preciso criar.
            if (in_array(strtolower($attribute_key), $attribute_marketplace_keys)) {
                continue;
            }

            // Atributos já está em uso.
            if (in_array(strtolower($attribute_key), $attributes_in_use)) {
                continue;
            }

            $this->model_products->insertAttributesCustomProduct($productId, $attribute_key, $attribute_value);
        }
    }

    /**
     * Recupera os contagem de produtos no banco de dados por certa quantidade.
     *
     * @return  int Retorna a contagem de produtos.
     */
    public function countProductsByInterval(): int
    {
        return (int)$this->model_products->countGetProductsByCriteria([
            'store_id' => $this->store,
            'status' => \Model_products::ACTIVE_PRODUCT
        ]);
    }

    /**
     * Recupera os produtos no banco de dados por certa quantidade.
     *
     * @param   int         $offset     Deslocamento inicial da consulta.
     * @param   int         $limit      Limite da consulta, quantos resultados por consulta.
     * @param   string|null $prdUnit    SKU do produto.
     * @return  array                   Retorna uma listagem de produtos.
     */
    public function getProductsByInterval(int $offset, int $limit, string $prdUnit = null): array
    {
        return $this->model_products->getProductsActiveByStore($this->store, $offset, $limit, $prdUnit);
        //return $this->model_products->getProductsByStore($this->store, $offset, $limit, $prdUnit);
    }

    /**
     * @param   int     $product    Código do produto (products.id).
     * @return  mixed               Retorna uma listagem de produtos.
     */
    public function getVariationByIdProduct(int $product)
    {
        return $this->model_products->getVariants($product);
    }

    /**
     * Atualiza o estoque do produto.
     *
     * @param   string      $sku    SKU do produto (products.sku).
     * @param   int         $qty    Novo saldo do estoque do produto.
     * @param   string|null $skuVar SKU da variação.
     * @return  bool                Retorna o estado da atualização.
     */
    public function updateStockProduct(string $sku, int $qty, string $skuVar = null): bool
    {
        if ($qty < 0) {
            $qty = 0;
        }

        if ($skuVar !== null) {
            return $this->updateStockVariation($skuVar, $sku, $qty);
        }

        $qtyPrdCurrent = $this->getStockBySku($sku);

        // não encontrou o produto
        if ($qtyPrdCurrent === false) {
            if ($this->store_uses_catalog) {
                $product_check = $this->getVariationBySkuVar($sku);
                if ($product_check) {
                    return $this->updateStockVariation($sku, $product_check['sku_product'], $qty);
                }
            }

            $product = $this->model_products->getProductByVarSkuAndStore($sku, $this->store);

            if (!$product || !$product['is_variation_grouped']) {
                $this->log_integration(
                    "Erro para atualizar o estoque do produto ($sku)",
                    "<h4>Não foi possível realizar a atualização do estoque</h4><p>Produto não localizado.</p><br><strong>SKU Produto: </strong> $sku",
                    "E"
                );
                return false;
            }

            return $this->updateStockVariation($sku, $product['sku'], $qty);
        }

        // Produto está com o mesmo estoque
        if ($qty == $qtyPrdCurrent) {
            return false;
        }

        // request to update stock
        $urlUpdateStockProduct = $this->process_url."Api/V1/Products/$sku";
        $queryUpdateStockProduct = array(
            'json' => array(
                'product' => array(
                    'qty' => $qty
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlUpdateStockProduct, $queryUpdateStockProduct);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            /*$this->log_integration(
                "Erro para atualizar o estoque do produto ($sku)",
                "<h4>Não foi possível realizar a atualização do estoque</h4><p>$erroMessage</p><br><strong>SKU Produto: </strong> $sku",
                "E"
            );*/
            return false;
        }

        /*$this->log_integration(
            "Estoque do produto ($sku) atualizado com sucesso",
            "<h4>Estoque do produto atualizado</h4><p>Estoque do produto ($sku), foi atualizada com sucesso.<br>De: $qtyPrdCurrent, para: $qty</p><br><strong>SKU Produto: </strong> $sku",
            "S"
        );*/
        return true;
    }

    /**
     * Atualiza o estoque da variação.
     *
     * @param   string  $sku        SKU da variação.
     * @param   string  $skuPai     SKU do produto.
     * @param   int     $qty        Novo saldo do estoque da variação.
     * @return  bool                Retorna o estado da atualização.
     */
    public function updateStockVariation(string $sku, string $skuPai, int $qty): bool
    {
        if ($qty < 0) {
            $qty = 0;
        }

        $qtyVarCurrent = $this->getStockBySku($skuPai, $sku);

        // não encontrou a variação
        if ($qtyVarCurrent === false) {
            if ($this->store_uses_catalog && $this->getProductForSku($sku)) {
                return $this->updateStockProduct($sku, $qty);
            } else if ($this->store_uses_catalog && $new_sku = $this->getVariationBySkuVar($sku)) {
                return $this->updateStockVariation($sku, $new_sku['sku_product'], $qty);
            }

            $this->log_integration(
                "Erro para atualizar o estoque da variação ($sku) no produto ($skuPai)",
                "<h4>Não foi possível realizar a atualização do estoque</h4><p>Variação não localizada no produto.</p><br><strong>SKU Variação: </strong>$sku<br><strong>SKU Produto: </strong> $skuPai",
                "E"
            );
            return false;
        }

        // Variação está com o mesmo estoque
        if ($qty == $qtyVarCurrent) {
            return false;
        }

        // request to create variation
        $urlUpdateStockVariation = $this->process_url."Api/V1/Variations/sku/$skuPai/$sku";
        $queryUpdateStockVariation = array(
            'json' => array(
                'variation' => array(
                    'qty' => $qty
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlUpdateStockVariation, $queryUpdateStockVariation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            /*$this->log_integration(
                "Erro para atualizar o estoque da variação ($sku) no produto ($skuPai)",
                "<h4>Não foi possível realizar a atualização do estoque</h4><p>$erroMessage</p><br><strong>SKU Variação: </strong>$sku<br><strong>SKU Produto: </strong> $skuPai",
                "E"
            );*/

            return false;
        }

        /*$this->log_integration(
            "Estoque da variação ($sku) no produto ($skuPai) atualizada com sucesso",
            "<h4>Estoque da variação atualizada</h4><p>Estoque da variação ($sku), foi atualizada com sucesso.<br>De: $qtyVarCurrent, para: $qty</p><br><strong>SKU Variação: </strong>$sku<br><strong>SKU Produto: </strong> $skuPai",
            "S"
        );*/
        return true;
    }

    /**
     * Atualiza o preço do produto.
     *
     * @param   string      $sku    SKU do produto (products.sku).
     * @param   float       $price  Novo preço do produto.
     * @param   float       $list_price Novo preço de do produto.
     * @param   string|null $skuVar SKU da variação.
     * @return  bool                Retorna o estado da atualização.
     */
    public function updatePriceProduct(string $sku, float $price, float $list_price, string $skuVar = null): bool
    {
        // Usa catálogo, mas não pode atualizar o preço.
        if (($this->store_uses_catalog && !$this->can_update_price_catalog) || in_array('disablePrice', $this->user_permission)) {
            return false;
        }

        if(isset($this->credentials->price_not_update)){
            return false;
        }

        $list_price = $list_price ?: $price;
        if ($skuVar !== null) {
            return $this->updatePriceVariation($skuVar, $sku, $price, $list_price);
        }

        $pricePrdCurrent = $this->getPriceBySku($sku);

        // não encontrou o produto
        if ($pricePrdCurrent === false) {
            if ($this->store_uses_catalog) {
                $product_check = $this->getVariationBySkuVar($sku);
                if ($product_check) {
                    return $this->updatePriceVariation($sku, $product_check['sku_product'], $price, $list_price);
                }
            }

            $product = $this->model_products->getProductByVarSkuAndStore($sku, $this->store);
            if (!$product || !$product['is_variation_grouped']) {
                $this->log_integration(
                    "Erro para atualizar o preço do produto ($sku)",
                    "<h4>Não foi possível realizar a atualização do preço</h4><p>Produto não localizado.</p><br><strong>SKU Produto: </strong> $sku",
                    "E"
                );
                return false;
            }

            return $this->updatePriceVariation($sku, $product['sku'], $price, $list_price);
        }

        // Produto está com o mesmo preço e list_price
        if ($price == $pricePrdCurrent['price'] && $list_price == $pricePrdCurrent['list_price']) {
            return false;
        }

        // request to update price
        $urlUpdatePriceProduct = $this->process_url."Api/V1/Products/$sku";
        $queryUpdatePriceProduct = array(
            'json' => array(
                'product' => array(
                    'price' => $price,
                    'list_price' => $list_price
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlUpdatePriceProduct, $queryUpdatePriceProduct);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            /*$this->log_integration(
                "Erro para atualizar o preço do produto ($sku)",
                "<h4>Não foi possível realizar a atualização do preço</h4><p>$erroMessage</p><br><strong>SKU Produto: </strong> $sku",
                "E"
            );*/
            return false;
        }

        $message_log ='';
        if ($price <> $pricePrdCurrent['price']) {
            $message_log = "<br>Preço foi atualizado De: ".$pricePrdCurrent['price'].", para: $price. ";
        }
        if ($list_price <> $pricePrdCurrent['list_price']) {
            $message_log .= "<br>Preço de tabela foi atualizado De: ".$pricePrdCurrent['list_price'].", para: $list_price. ";
        }

        /*$this->log_integration(
            "Preço do produto ($sku) atualizado com sucesso",
            "<h4>Preço do produto atualizado</h4><p>Preço do produto ($sku), foi atualizada com sucesso.$message_log </p><br><strong>SKU Produto: </strong> $sku",
            "S"
        );*/
        return true;
    }

    /**
     * Atualiza o preço da variação
     *
     * @param   string  $sku        SKU da variação
     * @param   string  $skuPai     SKU do produto
     * @param   float   $price      Novo preço da variação
     * @param   float   $list_price Novo preço de da variação
     * @return  bool                Retorna o estado da atualização
     */
    public function updatePriceVariation(string $sku, string $skuPai, float $price, float $list_price): bool
    {
        // Usa catálogo, mas não pode atualizar o preço.
        if (($this->store_uses_catalog && !$this->can_update_price_catalog) || in_array('disablePrice', $this->user_permission)) {
            return false;
        }

        if(isset($this->credentials->price_not_update)){
            return false;
        }

        $priceVarCurrent = $this->getPriceBySku($skuPai, $sku);
        $list_price = $list_price ?: $price;

        // não encontrou a variação
        if ($priceVarCurrent === false) {
            if ($this->store_uses_catalog && $this->getProductForSku($sku)) {
                return $this->updatePriceProduct($sku, $price, $list_price);
            } else if ($this->store_uses_catalog && $new_sku = $this->getVariationBySkuVar($sku)) {
                return $this->updatePriceVariation($sku, $new_sku['sku_product'], $price, $list_price);
            }
            $this->log_integration(
                "Erro para atualizar o preço da variação ($sku) no produto ($skuPai)",
                "<h4>Não foi possível realizar a atualização do preço</h4><p>Variação não localizada no produto.</p><br><strong>SKU Variação: </strong>$sku<br><strong>SKU Produto: </strong> $skuPai",
                "E"
            );
            return false;
        }

        // Variação está com o mesmo preço e list_price
        if ($price == $priceVarCurrent['price'] && $list_price == $priceVarCurrent['list_price']) {
            return false;
        }

        // request to update price
        $urlUpdatePriceVariation = $this->process_url."Api/V1/Variations/sku/$skuPai/$sku";
        $queryUpdatePriceVariation = array(
            'json' => array(
                'variation' => array(
                    'price' => $price,
                    'list_price' => $list_price
                )
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->client_cnl->request('PUT', $urlUpdatePriceVariation, $queryUpdatePriceVariation);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            /*$this->log_integration(
                "Erro para atualizar o preço da variação ($sku) no produto ($skuPai)",
                "<h4>Não foi possível realizar a atualização do preço</h4><p>$erroMessage</p><br><strong>SKU Variação: </strong>$sku<br><strong>SKU Produto: </strong> $skuPai",
                "E"
            );*/

            return false;
        }


        $message_log ='';
        if ($price <> $priceVarCurrent['price']) {
            $message_log = "<br>Preço foi atualizado De: ".$priceVarCurrent['price'].", para: $price. ";
        }
        if ($list_price <> $priceVarCurrent['list_price']) {
            $message_log .= "<br>Preço de tabela foi atualizado De: ".$priceVarCurrent['list_price'].", para: $list_price. ";
        }

        /*$this->log_integration(
            "Preço da variação ($sku) no produto ($skuPai) atualizada com sucesso",
            "<h4>Preço da variação atualizada</h4><p>Preço da variação ($sku), foi atualizada com sucesso. $message_log </p><br><strong>SKU Variação: </strong>$sku<br><strong>SKU Produto: </strong> $skuPai",
            "S"
        );*/
        return true;
    }

    /**
     * Recupera o estoque no banco de dados de um produto ou variação pelo ID do erp
     *
     * @param   string      $skuPrd SKU do produto
     * @param   null|string $skuVar SKU da variação
     * @return  false|int           Retorna o estoque do produto/variação ou false caso falhe
     */
    public function getStockBySku(string $skuPrd, string $skuVar = null)
    {
        if ($skuVar === null) {
            $resultProd = $this->getProductForSku($skuPrd);
            if (!$resultProd) {
                return false;
            }

            return $resultProd['qty'];
        }

        $resultVar = $this->getVariationForSkuAndSkuVar($skuPrd, $skuVar);
        if (!$resultVar) {
            return false;
        }

        return $resultVar['qty'];
    }

    /**
     * Recupera o preço no banco de dados de um produto ou variação pelo ID do erp
     *
     * @param   string      $skuPrd SKU do produto
     * @param   null|string $skuVar SKU da variação
     * @return  false|array         Retorna o preço do produto/variação ou false caso falhe
     */
    public function getPriceBySku(string $skuPrd, string $skuVar = null)
    {
        $arrPrice = [];
        $arrPriceVar = [];
        if ($skuVar === null) {
            $resultProd = $this->getProductForSku($skuPrd);
            if (!$resultProd) {
                return false;
            }
            $arrPrice['price'] =$resultProd['price'];
            $arrPrice['list_price'] =$resultProd['list_price'];
            return $arrPrice;
        }

        $resultVar = $this->getVariationForSkuAndSkuVar($skuPrd, $skuVar);
        if (!$resultVar) {
            return false;
        }
        $arrPriceVar['price'] =$resultVar['price'];
        $arrPriceVar['list_price'] =$resultVar['list_price'];
        return $arrPriceVar;
    }

    /**
     * Atualização de produtos
     *
     * @param   object|array    $payload    Dados do produto para atualização
     * @return  bool                        Estado da atualização
     */
    public function updateProduct($payload): bool
    {
        $arrToUpdate = array();
        $arrUpdated = array();

        // a unidade precisamos pegar o nome correto no de-para que fica na variável "typesUnity"
        if (isset($payload['unity']) && array_key_exists(strtolower($payload['unity']['value']), $this->typesUnity)) {
            $payload['unity']['value'] = $this->typesUnity[strtolower($payload['unity']['value'])];
        }

        $dataProduct = $this->getProductForSku($payload['sku']['value']);
        // Produto não localizado
        if (!$dataProduct) {
            if ($this->store_uses_catalog) {
                $product_check = $this->getVariationBySkuVar($payload['sku']['value']);
                if ($product_check) {
                    return $this->updateVariation($this->convertUpdateProductToVariation($payload), $product_check['sku_product']);
                }
            }
            $this->setRequestResponse("SKU {$payload['sku']['value']} não localizado");
            return false;
        }

        $imagesIntegration = $payload['images']['value'] ?? [];
        $imagesIntegration = array_chunk($imagesIntegration, $this->productImageLimit)[0] ?? [];
        $payload['images']['value'] = $imagesIntegration;
        $payload['_image_dir'] = $dataProduct['image'] ?? '';
        // produto está igual ainda, não precisa ser atualizado
        if ($this->checkProductDiff($payload) === false) {
            return true;
        }

        if (!$this->store_uses_catalog && isset($payload['description']) && !empty($payload['description']['value'])) {
            $payload['description']['value'] = xssClean($payload['description']['value'], false, false);
            $payload['description']['value'] = strip_tags_products($payload['description']['value'], $this->allowable_tags);
        }
        if (!$this->store_uses_catalog && isset($payload['ncm']) && !empty($payload['ncm']['value'])) {
            $payload['ncm']['value'] = onlyNumbers($payload['ncm']['value']);
        }
        foreach (['net_weight', 'gross_weight'] as $field) {
            if (!$this->store_uses_catalog && isset($payload[$field]) && !empty($payload[$field]['value'])) {
                $payload[$field]['value'] = roundDecimal($payload[$field]['value'], 3);
            }
        }
        foreach (['width', 'height', 'depth'] as $field) {
            if (!$this->store_uses_catalog && isset($payload[$field]) && !empty($payload[$field]['value'])) {
                $payload[$field]['value'] = roundDecimal($payload[$field]['value']);
            }
        }

        $dataProduct['peso_liquido'] = roundDecimal($dataProduct['peso_liquido'], 3);
        $dataProduct['peso_bruto']   = roundDecimal($dataProduct['peso_bruto'], 3);
        $dataProduct['largura']      = roundDecimal($dataProduct['largura']);
        $dataProduct['altura']       = roundDecimal($dataProduct['altura']);
        $dataProduct['profundidade'] = roundDecimal($dataProduct['profundidade']);

        //validacao bugs-1584
        $actual_depth = $dataProduct['actual_depth'] ?? 0;
        $actual_height = $dataProduct['actual_height'] ?? 0;
        $actual_width = $dataProduct['actual_width'] ?? 0;

        if ($actual_depth === '') {
            $actual_depth = 0;
        }
        if ($actual_height === '') {
            $actual_height = 0;
        }
        if ($actual_width === '') {
            $actual_width = 0;
        }

        $dataProduct['actual_depth'] = roundDecimal($actual_depth);
        $dataProduct['actual_height'] = roundDecimal($actual_height);
        $dataProduct['actual_width'] = roundDecimal($actual_width);

        if (
            (
                ($this->store_uses_catalog && $this->can_update_price_catalog && !in_array('disablePrice', $this->user_permission)) ||
                (!$this->store_uses_catalog && !in_array('disablePrice', $this->user_permission))
            ) &&
            !empty($payload['price']['value'])
        ) {
            if ($payload['price']['value'] != $dataProduct['price']) {
                $arrToUpdate['price'] = $payload['price']['value'];
                $arrUpdated['Preço'] = array('new' => $payload['price']['value'], 'old' => $dataProduct['price']);
            }
        }

        if (
            (
                ($this->store_uses_catalog && $this->can_update_price_catalog && !in_array('disablePrice', $this->user_permission)) ||
                (!$this->store_uses_catalog && !in_array('disablePrice', $this->user_permission))
            ) &&
            !empty($payload['list_price']['value'])
        ) {
            if ($payload['list_price']['value'] != $dataProduct['list_price']) {
                $arrToUpdate['list_price'] = $payload['list_price']['value'];
                $arrUpdated['Preço de lista'] = array('new' => $payload['list_price']['value'], 'old' => $dataProduct['list_price']);
            }
        }

        if (!empty($payload['qty']['value'])) {
            if ($payload['qty']['value'] < 0) {
                $payload['qty']['value'] = 0;
            }
            if ($payload['qty']['value'] != $dataProduct['qty']) {
                $arrToUpdate['qty'] = $payload['qty']['value'];
                $arrUpdated['Estoque'] = array('new' => $payload['qty']['value'], 'old' => $dataProduct['qty']);
            }
        }

        if (isset($payload['stock']['value'])) {
            if ($payload['stock']['value'] < 0) {
                $payload['stock']['value'] = 0;
            }
            if ($payload['stock']['value'] != $dataProduct['qty']) {
                $arrToUpdate['qty'] = $payload['stock']['value'];
                $arrUpdated['Estoque'] = array('new' => $payload['stock']['value'], 'old' => $dataProduct['qty']);
            }
        }

        if (!$this->store_uses_catalog && !empty($payload['name']['value'])) {
            if ($payload['name']['value'] != $dataProduct['name']) {
                $arrToUpdate['name'] = $payload['name']['value'];
                $arrUpdated['Nome'] = array('new' => $payload['name']['value'], 'old' => $dataProduct['name']);
            }
        }
        if (!empty($payload['status']['value'])) {
            if ($payload['status']['value'] != $dataProduct['status'] && $dataProduct['status'] != \Model_products::BLOCKED_PRODUCT) {
                $arrToUpdate['active'] = $payload['status']['value'] == \Model_products::ACTIVE_PRODUCT
                    ? Product_v2::API_PRODUCT_STATUS_ACTIVE : (
                    $payload['status']['value'] == \Model_products::INACTIVE_PRODUCT ? Product_v2::API_PRODUCT_STATUS_INACTIVE : $payload['status']['value']
                    );
                $arrUpdated['Status'] = array('new' => $payload['status']['value'], 'old' => $dataProduct['status']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['description']['value'])) {
            if ($payload['description']['value'] != $dataProduct['description']) {
                $arrToUpdate['description'] = $payload['description']['value'];
                $arrUpdated['Descrição'] = array('new' => $payload['description']['value'], 'old' => $dataProduct['description']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['ean']['value'])) {
            if ($payload['ean']['value'] != $dataProduct['EAN']) {
                $arrToUpdate['ean'] = $payload['ean']['value'];
                $arrUpdated['EAN'] = array('new' => $payload['ean']['value'], 'old' => $dataProduct['EAN']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['sku_manufacturer']['value'])) {
            if ($payload['sku_manufacturer']['value'] != $dataProduct['codigo_do_fabricante']) {
                $arrToUpdate['sku_manufacturer'] = $payload['sku_manufacturer']['value'];
                $arrUpdated['SKU do Fabricante'] = array('new' => $payload['sku_manufacturer']['value'], 'old' => $dataProduct['codigo_do_fabricante']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['net_weight']['value'])) {
            if ($payload['net_weight']['value'] != $dataProduct['peso_liquido']) {
                $arrToUpdate['net_weight'] = $payload['net_weight']['value'];
                $arrUpdated['Peso líquido'] = array('new' => $payload['net_weight']['value'], 'old' => $dataProduct['peso_liquido']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['gross_weight']['value'])) {
            if ($payload['gross_weight']['value'] != $dataProduct['peso_bruto']) {
                $arrToUpdate['gross_weight'] = $payload['gross_weight']['value'];
                $arrUpdated['Peso bruto'] = array('new' => $payload['gross_weight']['value'], 'old' => $dataProduct['peso_bruto']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['width']['value'])) {
            if ($payload['width']['value'] != $dataProduct['largura']) {
                $arrToUpdate['width'] = $payload['width']['value'];
                $arrUpdated['Largura'] = array('new' => $payload['width']['value'], 'old' => $dataProduct['largura']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['height']['value'])) {
            if ($payload['height']['value'] != $dataProduct['altura']) {
                $arrToUpdate['height'] = $payload['height']['value'];
                $arrUpdated['Altura'] = array('new' => $payload['height']['value'], 'old' => $dataProduct['altura']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['depth']['value'])) {
            if ($payload['depth']['value'] != $dataProduct['profundidade']) {
                $arrToUpdate['depth'] = $payload['depth']['value'];
                $arrUpdated['Profundidade'] = array('new' => $payload['depth']['value'], 'old' => $dataProduct['profundidade']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['product_width']['value'] ?? 0)) {
            if ($payload['product_width']['value'] != $dataProduct['actual_width']) {
                $arrToUpdate['product_width'] = $payload['product_width']['value'];
                $arrUpdated['Largura Produto'] = array('new' => $payload['product_width']['value'], 'old' => $dataProduct['actual_width']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['product_height']['value'] ?? 0)) {
            if ($payload['product_height']['value'] != $dataProduct['actual_height']) {
                $arrToUpdate['product_height'] = $payload['product_height']['value'];
                $arrUpdated['Altura Produto'] = array('new' => $payload['product_height']['value'], 'old' => $dataProduct['actual_height']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['product_depth']['value'] ?? 0)) {
            if ($payload['product_depth']['value'] != $dataProduct['actual_depth']) {
                $arrToUpdate['product_depth'] = $payload['product_depth']['value'];
                $arrUpdated['Profundidade Produto'] = array('new' => $payload['product_depth']['value'], 'old' => $dataProduct['actual_depth']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['items_per_package']['value'])) {
            if ($payload['items_per_package']['value'] != $dataProduct['products_package']) {
                $arrToUpdate['items_per_package'] = $payload['items_per_package']['value'];
                $arrUpdated['Produtos por Embalagem'] = array('new' => $payload['items_per_package']['value'], 'old' => $dataProduct['products_package']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['guarantee']['value'])) {
            if ($payload['guarantee']['value'] != $dataProduct['garantia']) {
                $arrToUpdate['guarantee'] = $payload['guarantee']['value'];
                $arrUpdated['Garantia'] = array('new' => $payload['guarantee']['value'], 'old' => $dataProduct['garantia']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['origin']['value'])) {
            if ($payload['origin']['value'] != $dataProduct['origin']) {
                $arrToUpdate['origin'] = $payload['origin']['value'];
                $arrUpdated['Origem'] = array('new' => $payload['origin']['value'], 'old' => $dataProduct['origin']);
            }
        }
        if (!$this->store_uses_catalog && !empty($payload['ncm']['value'])) {
            if ($payload['ncm']['value'] != $dataProduct['NCM'] && $payload['ncm']['value'] > 0) {
                $arrToUpdate['ncm'] = $payload['ncm']['value'];
                $arrUpdated['NCM'] = array('new' => $payload['ncm']['value'], 'old' => $dataProduct['NCM']);
            }
        }
        if (isset($payload['extra_operating_time']['value'])) {
            $categoryBase = 0;
            if (!empty($payload['category']['value']) && !in_array('disabledCategoryPermission', $this->user_permission)) {
                $categoryBase = $this->model_category->getcategorybyName($payload['category']['value']);
            }
            // Bloqueia de prazo por categoria.
            if (
                $categoryBase != 0 ||
                (!empty($dataProduct['category_id']) && $dataProduct['category_id'] != '[""]')
            ) {
                $category_id = $categoryBase ?: $dataProduct['category_id'];
                $category_id = is_numeric($category_id) ? $category_id : (json_decode($category_id)[0] ?? 0);
                if ($category_id) {
                    $data_category = $this->model_category->getCategoryData($category_id);

                    if ($data_category['blocked_cross_docking']) {
                        $payload['extra_operating_time']['value'] = $data_category['days_cross_docking'];
                    }
                }
            }

            if ($payload['extra_operating_time']['value'] != $dataProduct['prazo_operacional_extra']) {
                $arrToUpdate['extra_operating_time'] = $payload['extra_operating_time']['value'];
                $arrUpdated['Prazo Operacional'] = array('new' => $payload['extra_operating_time']['value'], 'old' => $dataProduct['prazo_operacional_extra']);
            }
        }
        // ver se a unidade existe na base e se está diferente para alterar
        if (!$this->store_uses_catalog && !empty($payload['unity']['value'])) {
            $unityBase = $this->model_attributes->getAttributeValueByAttrNameAndAttrValue('Unidade', $payload['unity']['value']);
            if ($unityBase) {
                if ($unityBase['id'] != onlyNumbers($dataProduct['attribute_value_id'])) {
                    $unityBaseOld = $this->model_attributes->getAttributeValueDataById(onlyNumbers($dataProduct['attribute_value_id']));
                    $arrToUpdate['unity'] = $payload['unity']['value'];
                    $arrUpdated['Unidade'] = array('new' => $payload['unity']['value'], 'old' => $unityBaseOld['value'] ?? '');
                }
            }
        }
        // ver se o fabricante existe na base e se está diferente para alterar, caso não existe envio mesmo assim, pois será criado
        if (!$this->store_uses_catalog && !empty($payload['brand']['value'])) {
            $brandBase = $this->model_brands->getBrandDatabyName($payload['brand']['value']);
            $brandBaseOld = $this->model_brands->getBrandData(onlyNumbers($dataProduct['brand_id']));
            if ($brandBase) {
                if ($brandBase['active'] == 1) {
                    if ($brandBase['id'] != onlyNumbers($dataProduct['brand_id'])) {
                        $arrToUpdate['manufacturer'] = $payload['brand']['value'];
                        $arrUpdated['Fabricante'] = array('new' => $payload['brand']['value'], 'old' => $brandBaseOld['name'] ?? '');
                    }
                }
                $manufacturerTest = $arrToUpdate['manufacturer'] ?? $brandBaseOld['name'] ?? $payload['brand']['value'];
                if (!empty($brandBaseOld) && isset($brandBaseOld['name']) && strcasecmp($manufacturerTest, $brandBaseOld['name']) !== 0) {
                    $arrToUpdate['manufacturer'] = $arrToUpdate['manufacturer'] ?? $brandBaseOld['name'] ?? $payload['brand']['value'];
                    $arrUpdated['Fabricante'] = array('new' => $arrToUpdate['manufacturer'], 'old' => $brandBaseOld['name'] ?? '');
                }
            } else { // fabricantes não existe, precisa alterar no produto, o novo fabricante será criado
                $arrToUpdate['manufacturer'] = $payload['brand']['value'];
                $arrUpdated['Fabricante'] = array('new' => $payload['brand']['value'], 'old' => $brandBaseOld['name'] ?? '');
            }
        }
        // ver se a categoria existe na base e se está diferente para alterar
        if (!$this->store_uses_catalog && !empty($payload['category']['value']) && !in_array('disabledCategoryPermission', $this->user_permission)) {
            $categoryBase = $this->model_category->getcategorybyName($payload['category']['value']);
            if ($categoryBase) {
                if ($categoryBase != onlyNumbers($dataProduct['category_id'])) {
                    if (!empty(onlyNumbers($dataProduct['category_id']))) {
                        $categoryBaseOld = $this->model_category->getCategoryData(onlyNumbers($dataProduct['category_id']));
                    }
                    $arrToUpdate['category'] = $payload['category']['value'];
                    $arrUpdated['Categoria'] = array('new' => $payload['category']['value'], 'old' => $categoryBaseOld['name'] ?? '');
                }
            }
        }

        // Enviar as images no update caso o produto não tenha imagem
        $countImages = count($imagesIntegration);
        $savedImagesCount = $dataProduct['is_on_bucket'] ?
            count($this->bucket->listObjects('assets/images/product_image/' . $dataProduct['image'])['contents']) :
            $this->uploadproducts->countImagesDir($dataProduct['image']);

        $check_image = $countImages != $savedImagesCount || $this->integration == "viavarejo_b2b";

        if (
            !$this->store_uses_catalog &&
            $countImages > 0 &&
            $check_image
        ) {
            $arrToUpdate['images'] = $imagesIntegration;
            $arrUpdated['Imagem'] = array('new' => '<ul><li>' . implode('</li><li>', $payload['images']['value']) . '</li></ul>', 'old' => '');
        }

        // Atualização de preço e estoque é feito em outra rotina, manter somente atualização por planilha.
        if (
            !array_key_exists('import_internal_by_csv', $this->credentials->api_internal) ||
            !$this->credentials->api_internal['import_internal_by_csv']
        ) {
            unset($arrToUpdate['price']);
            unset($arrToUpdate['list_price']);
            unset($arrToUpdate['qty']);
        }

        // não tem nada para atualizar, não deveria, pois, foi validado antes
        if (empty($arrToUpdate)) {
            $this->setRequestResponse('Sem dados para atualizar');
            return false;
        }

        // request to update product
        $urlUpdateProduct = $this->process_url."Api/V1/Products/{$dataProduct['sku']}";
        $queryUpdateProduct = array(
            'json' => array(
                'product' => $arrToUpdate
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->setRequestResponse($this->client_cnl->request('PUT', $urlUpdateProduct, $queryUpdateProduct));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->setRequestResponse($exception);
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            $payload['name']['value'] = $payload['name']['value'] ?? $dataProduct['name'];
            /*$this->log_integration(
                "Erro para atualizar o produto ({$payload['sku']['value']})",
                "<h4>Não foi possível realizar a atualização do produto.</h4><p>$erroMessage</p><strong>SKU</strong>: {$payload['sku']['value']}<br><strong>Descrição</strong>: {$payload['name']['value']}",
                "E");*/
            return false;
        }

        $formatDateUpdatedToLog = '<table class="col-md-12 table table-bordered" id="tableLog"><thead><tr><th>Campo</th><th>Antes</th><th>Depois</th></tr></thead><tbody>';
        foreach ($arrUpdated as $field => $value) {
            $formatDateUpdatedToLog .= "<tr><td>$field</td><td>{$value['old']}</td><td>{$value['new']}</td></tr>";
        }
        $formatDateUpdatedToLog .= '</tbody></table>';

        $payload['name']['value'] = $payload['name']['value'] ?? $dataProduct['name'];
        /*$this->log_integration(
            "Produto ({$payload['sku']['value']}) atualizado",
            "<h4>Produto ({$payload['sku']['value']}) atualizado com sucesso</h4><h4>Alterações realizadas:</h4>$formatDateUpdatedToLog<br><strong>SKU</strong>: {$payload['sku']['value']}<br><strong>Nome do Produto</strong>: {$payload['name']['value']}",
            "S");*/

        return true;
    }

    /**
     * Atualização de produtos
     * @todo Só atualizamos imagem e EAN. Ver a necessidade de atualizar mais campos.
     *
     * @param   object|array    $payload    Dados do produto para atualização.
     * @param   string          $skuPai     SKU do produto PAI (products.sku).
     * @return  bool                        Estado da atualização.
     */
    public function updateVariation($payload, string $skuPai): bool
    {
        $arrToUpdate = array();
        $arrUpdated = array();

        $normalizedVariations = [];
        $payload['variations'] = $payload['variations'] ?? [];
        // a variação voltagem é feito um de-para, para pegar apenas os valores aceito
        foreach ($payload['variations'] as $keyVar => $valueVar) {
            $keyVarSlug = StringHandler::slugify($keyVar);
            if (array_key_exists($keyVarSlug, $this->typeVariation)) {
                $normalizedVariations[$this->typeVariation[$keyVarSlug]] = $payload['variations'][$keyVar];
            } elseif (array_key_exists($keyVar, $this->typeVariation)) {
                $normalizedVariations[$this->typeVariation[$keyVar]] = $payload['variations'][$keyVar];
            }
        }

        $dataVariation = $this->getVariationForSkuAndSkuVar($skuPai, $payload['sku']);
        if (is_null($dataVariation)) {
            if ($this->store_uses_catalog && $this->getProductForSku($payload['sku'])) {
                return $this->updateProduct($this->convertUpdateVariationToProduct($payload));
            } else if ($this->store_uses_catalog && $new_sku = $this->getVariationBySkuVar($payload['sku'])) {
                return $this->updateVariation($payload, $new_sku['sku_product']);
            }
            $this->setRequestResponse("SKU $payload[sku] não localizado");
            return false;
        }
        $imagesIntegration  = $payload['images'] ?? [];
        $imagesIntegration = array_chunk($imagesIntegration, $this->productImageLimit)[0] ?? [];

        if (!empty($payload['ean'])) {
            if ($payload['ean'] != $dataVariation['EAN']) {
                $arrToUpdate['EAN'] = $payload['ean'];
                $arrUpdated['EAN'] = array('new' => $payload['ean'], 'old' => $dataVariation['EAN']);
            }
        }

        // Enviar as images no update caso o produto não tenha imagem
        $countImages = count($imagesIntegration);
        if ($countImages > 0
            && ($countImages != $this->uploadproducts->countImagesDir("{$dataVariation['image_product']}/{$dataVariation['image']}"))) {
            $arrToUpdate['images'] = $imagesIntegration;
            $arrUpdated['Imagem'] = array('new' => '<ul><li>'.implode('</li><li>', $payload['images']).'</li></ul>', 'old' => '');
        }

        if (!empty($payload['status'])) {
            if ($payload['status'] != $dataVariation['status']) {
                $arrToUpdate['active'] = $payload['status'] == \Model_products::ACTIVE_PRODUCT
                    ? Product_v2::API_PRODUCT_STATUS_ACTIVE : (
                    $payload['status'] == \Model_products::INACTIVE_PRODUCT ? Product_v2::API_PRODUCT_STATUS_INACTIVE : $payload['status']['value']
                    );
                $arrUpdated['Status'] = array('new' => $payload['status'], 'old' => $dataVariation['status']);
            }

            if($payload['status'] == \Model_products::INACTIVE_PRODUCT) {
                $arrToUpdate['active'] = Product_v2::API_PRODUCT_STATUS_INACTIVE;
            }
        }

        if (isset($payload['qty']) && is_numeric($payload['qty'])) {
            if ($payload['qty'] < 0) {
                $payload['qty'] = 0;
            }
            if ($payload['qty'] != $dataVariation['qty']) {
                $arrToUpdate['qty'] = $payload['qty'];
                $arrUpdated['Estoque'] = array('new' => $payload['qty'], 'old' => $dataVariation['qty']);
            }
        }

        if (isset($payload['stock']) && is_numeric($payload['stock'])) {
            if ($payload['stock'] < 0) {
                $payload['stock'] = 0;
            }
            if ($payload['stock'] != $dataVariation['qty']) {
                $arrToUpdate['qty'] = $payload['stock'];
                $arrUpdated['Estoque'] = array('new' => $payload['stock'], 'old' => $dataVariation['qty']);
            }
        }

        if (isset($payload['price']) && is_numeric($payload['price'])) {
            if ($payload['price'] != $dataVariation['price']) {
                $arrToUpdate['price'] = $payload['price'];
                $arrUpdated['Preço'] = array('new' => $payload['price'], 'old' => $dataVariation['price']);
            }
        }

        if (isset($payload['list_price']) && is_numeric($payload['list_price'])) {
            if ($payload['list_price'] != $dataVariation['list_price']) {
                $arrToUpdate['list_price'] = $payload['list_price'];
                $arrUpdated['Preço de lista'] = array('new' => $payload['list_price'], 'old' => $dataVariation['list_price']);
            }
        }

        $validateVariations = [];
        $variationTypes = $dataVariation['has_variants'] ?? '';
        $variationTypes = explode(';', $variationTypes);
        foreach ($variationTypes ?? [] as $idx => $type) {
            $typeSlug = StringHandler::slugify($type);
            $variationValues = $dataVariation['name'] ?? '';
            $variationValues = explode(';', $variationValues);
            if (array_key_exists($typeSlug, $this->typeVariation)) {
                $validateVariations[$this->typeVariation[$typeSlug]] = $variationValues[$idx] ?? '';
            } elseif (array_key_exists($type, $this->typeVariation)) {
                $validateVariations[$this->typeVariation[$type]] = $variationValues[$idx] ?? '';
            }
        }
        ksort($normalizedVariations);
        ksort($validateVariations);

        $diffVariations = array_diff($normalizedVariations, $validateVariations);
        if (empty($diffVariations)) {
            $diffVariations = array_diff(array_flip($normalizedVariations), array_flip($validateVariations));
            $diffVariations = array_flip($diffVariations ?? []);
        }
        if (!empty($diffVariations)) {
            //$arrToUpdate['id'] = $dataVariation['id'];
            //$arrToUpdate['sku'] = $dataVariation['sku'];
            $diffVariations = array_merge($normalizedVariations, $diffVariations);
            //$arrUpdated['Variação'] = ['new' => implode(' ', array_values($diffVariations)), 'old' => implode(' ', array_values($normalizedVariations))];
        }
        foreach ($diffVariations ?? [] as $diffType => $diffValue) {
            //$arrToUpdate[$diffType] = $diffValue;
        }

        if (empty($arrToUpdate)) {
            $this->setRequestResponse('Sem dados para atualizar');
            return false;
        }

        // request to update product
        $urlUpdateProduct = $this->process_url."Api/V1/Variations/sku/$skuPai/{$payload['sku']}";
        $queryUpdateProduct = array(
            'json' => array(
                'variation' => $arrToUpdate
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->setRequestResponse($this->client_cnl->request('PUT', $urlUpdateProduct, $queryUpdateProduct));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            /*$this->setRequestResponse($exception->getResponse();
            $responseContent = $exception->getResponse()->getBody()->getContents();
            $responseContent = is_string($responseContent) ? $responseContent : json_encode($responseContent);
            $erroMessage = $this->getMessageRequestApiInternal($responseContent);*/
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            /*$this->log_integration(
                "Erro para atualizar a variação ({$payload['sku']})",
                "<h4>Não foi possível realizar a atualização da variação.</h4><p>$erroMessage</p><strong>SKU Variação</strong>: {$payload['sku']}<br><strong>SKU Produto</strong>: $skuPai",
                "E");*/
            $this->setRequestResponse($erroMessage);
            return false;
        }

        $formatDateUpdatedToLog = '<table class="col-md-12 table table-bordered" id="tableLog"><thead><tr><th>Campo</th><th>Antes</th><th>Depois</th></tr></thead><tbody>';
        foreach ($arrUpdated as $field => $value) {
            $formatDateUpdatedToLog .= "<tr><td>$field</td><td>{$value['old']}</td><td>{$value['new']}</td></tr>";
        }
        $formatDateUpdatedToLog .= '</tbody></table>';

        /*$this->log_integration(
            "Variação ({$payload['sku']}) do produto ($skuPai) atualizado",
            "<h4>Variação ({$payload['sku']}) atualizada com sucesso</h4><h4>Alterações realizadas:</h4>$formatDateUpdatedToLog<br><strong>SKU Variação</strong>: {$payload['sku']}<br><strong>SKU Produto</strong>: $skuPai",
            "S");*/

        return true;
    }

    /**
     * Verificar se o produto está diferente do cadastro
     *
     * @param   array   $product    Dados do produto, formatado pelo método getDataFormattedToIntegration()
     * @return  bool                Produto está diferente e precisa ser atualizado
     */
    public function checkProductDiff(array $product): bool
    {
        $arrFields  = array(
            'id',
            'sku',
            'name',
            'unity',
            'ncm',
            'origin',
            'ean',
            'net_weight',
            'gross_weight',
            'sku_manufacturer',
            'description',
            'guarantee',
            'brand',
            'height',
            'depth',
            'width',
            'product_height',
            'product_depth',
            'product_width',
            'items_per_package',
            'category',
            'extra_operating_time',
            'status',
            'price',
            'list_price',
            'stock',
            'qty'
        );
        $arrCheck = array();

        foreach ($arrFields as $field) {
            if (!empty($product[$field]['value']) || (isset($product[$field]['value']) && $product[$field]['value'] == 0)) {
                if(!isset($product[$field]['field_database'])) {
                    continue;
                }
                if ($product[$field]['field_database'] == 'category_imported') {
                    $product[$field]['field_database'] = 'category_id';
                }

                $arrCheck[$product[$field]['field_database']] = $product[$field]['value'];
            }
        }

        // trocar nome por id da marca e categoria
        if (array_key_exists('category_id', $arrCheck)) {
            if ($categoryId = $this->model_category->getcategorybyName($arrCheck['category_id'])) {
                $arrCheck['category_id'] = "[\"$categoryId\"]";
            } else {
                unset($arrCheck['category_id']);
            }
        }
        if (array_key_exists('brand_id', $arrCheck)) {
            if ($brandId = $this->model_brands->getBrandbyName($arrCheck['brand_id'])) {
                $arrCheck['brand_id'] = "[\"$brandId\"]";
            } else {
                unset($arrCheck['category_id']);
            }
        }
        if (array_key_exists('attribute_value_id', $arrCheck)) {
            if ($attrValue = $this->model_attributes->getAttributeValueByAttrNameAndAttrValue('Unidade', $arrCheck['attribute_value_id'])) {
                $arrCheck['attribute_value_id'] = "[\"{$attrValue['id']}\"]";
            } else {
                unset($arrCheck['attribute_value_id']);
            }
        }

        $hasUpdatedData = false;
        if (!empty($product['images']['value'] ?? [])) {
            $countImages = count($product['images']['value']);
            if (empty($product['_image_dir'])
                || ($countImages > 0 && $countImages != $this->uploadproducts->countImagesDir($product['_image_dir']))) {
                $hasUpdatedData = true;
            }
        }

        $arrCheck['store_id']   = $this->store;
        $arrCheck['company_id'] = $this->company;

        return (count($this->model_products->getAllProducts($arrCheck)) === 0) || $hasUpdatedData;
    }

    /**
     * Formata as datas de filtro para a integradora Bling
     */
    public function formatDateFilterBling()
    {
        if ($this->dateLastJob) {
            $this->dateStartJob = date(DATETIME_INTERNATIONAL, strtotime($this->dateStartJob));
            $this->dateLastJob  = date(DATETIME_INTERNATIONAL, strtotime($this->dateLastJob));
        }
    }

    /**
     * Atualiza o código da integradora no produto e na variação
     *
     * @param   string      $sku            Código SKU do produto (products.sku)
     * @param   string      $idIntegration  Código da integradora
     * @param   string|null $var            Código SKU da variação (prd_variants.sku)
     * @return bool
     */
    public function updateProductIdIntegration(string $sku, string $idIntegration, string $var = null): bool
    {
        if ($this->store_uses_catalog) {
            if ($var) {
                $data = $this->getVariationForSkuAndSkuVar($sku, $var);
            } else {
                $data = $this->getProductForSku($sku);
            }
            if (is_null($data)) {
                if ($var) {
                    $check_data = $this->getProductForSku($sku);
                } else {
                    $check_data = $this->getVariationBySkuVar($sku);
                }

                if ($this->store_uses_catalog && $check_data) {
                    if ($var) {
                        return $this->model_products->updateIdIntegrationBySkuAndStore($var, $idIntegration, $this->store);
                    }
                    return $this->model_products->updateIdIntegrationBySkuAndStore($check_data['sku_product'] ?? $check_data['sku'], $idIntegration, $this->store, $sku);
                }
                return false;
            }
        }

        return $this->model_products->updateIdIntegrationBySkuAndStore($sku, $idIntegration, $this->store, $var);
    }

    public function getIdIntegrationByIdIntegration(string $idIntegration, bool $var = false): ?array
    {
        return $this->model_products->getIdIntegrationByIdIntegration($idIntegration, $this->store, $var);
    }

    /**
     * Recupera dados do produto pelo código SKU da variação
     *
     * @param   string  $skuVar SKU da variação
     * @return  mixed
     */
    public function getVariationBySkuFather(string $skuVar)
    {
        return $this->model_products->getDataProductBySkuVarAndStore($skuVar, $this->store);
    }

    /**
     * Enviar variação para cadastro
     *
     * @param  array    $dataProductFormatted   Dados ddo produto formatado pelo método getDataFormattedToIntegration()
     * @param  string   $variation              Dados da variação, vindo do ERP
     * @param  string   $skuPai                 Posição do sku para cadastro
     * @param  bool     $simplifiedReturn       Retorno simplificado, retornará na excessão apenas o que recebemos do createProduct()
     * @return bool                             Situação do cadastro
     */
    public function sendVariation(array $dataProductFormatted, string $variation, string $skuPai, bool $simplifiedReturn = false): bool
    {
        $dataVariation  = array();

        // recupero apenas a variação que preciso
        foreach ($dataProductFormatted['variations']['value'] as $_variation) {
            if ($_variation['sku'] == $variation) {
                $dataVariation = $_variation;
                break;
            }
        }

        // variação não encontrada no array formatado
        if (empty($dataVariation)) {
            if ($simplifiedReturn) {
                throw new InvalidArgumentException("Variação ($variation) não encontrada no dados formatado.");
            }

            throw new InvalidArgumentException("Variação ($variation) não encontrada no array formatado" . Utils::jsonEncode($dataProductFormatted));
        }

        if ($this->createVariation($dataVariation, $skuPai)) {
            $log_name = $this->integration . '/' . __CLASS__ . '/' . __FUNCTION__;
            //get_instance()->log_data('batch', $log_name, "Variação ($variation) inserida no produto ($skuPai)\n" . Utils::jsonEncode($dataVariation));
            return true;
        }

        if ($simplifiedReturn) {
            throw new InvalidArgumentException("Não foi possível inserir a variação ($variation) no produto ($skuPai).");
        }

        throw new InvalidArgumentException("Não foi possível inserir a variação ($variation) no produto ($skuPai)\n" . Utils::jsonEncode($dataVariation));
    }

    /**
     * Enviar produto para cadastro.
     *
     * @param   array   $array              Dados do produto, vindo do ERP, formatado pelo método getDataFormattedToIntegration.
     * @param   bool    $simplifiedReturn   Retorno simplificado, retornará na excessão apenas o que recebemos do createProduct().
     */
    public function sendProduct(array $array, bool $simplifiedReturn = false)
    {
        try {
            $this->createProduct($array);
            if (
                method_exists($this->getRequestResponse(), 'getBody') &&
                method_exists($this->getRequestResponse()->getBody(), 'getContents')
            ) {
                $response = $this->getRequestResponse()->getBody()->getContents();
                $response_decode = json_decode($response, true);

                if (array_key_exists('is_variation_grouped', $response_decode) && $response_decode['is_variation_grouped']) {
                    throw new InvalidArgumentException("SKU atualizado via direcionamento por ser produto de agrupamento.");
                }
            }
            $log_name = $this->integration . '/' . __CLASS__ . '/' . __FUNCTION__;
            //get_instance()->log_data('batch', $log_name, "Produto ({$array['sku']['value']}) inserido\n" . Utils::jsonEncode($array));
        } catch (InvalidArgumentException $exception) {
            if ($simplifiedReturn) {
                throw new InvalidArgumentException($exception->getMessage());
            }

            $integrationProductPayload = $this->getIntegrationProductPayload();

            $logRequest = '';
            if (!empty($this->mappedProductAPI)) {
                $logRequest = $this->createButtonLogRequestIntegration($this->mappedProductAPI);
            }

            throw new InvalidArgumentException(
                "Produto ({$array['sku']['value']}) não inserido. ({$exception->getMessage()})\n$logRequest\n"
            );
        }
    }

    public function normalizedFormattedData($parsedData): array
    {
        $normalized = [];
        foreach ((array)$parsedData as $key => $value) {
            $value = isset($value['value']) ? $value : ['value' => $value];
            if (is_array($value['value'])) {
                if (!($value['value'] !== array_values($value['value']))) {
                    $normalized[$key] = array_map(function ($item) {
                        $item = $this->normalizedFormattedData($item);
                        return count($item) > 1 ? $item : (array_values($item) === $item ? current($item) : $item);
                    }, $value['value']);
                    continue;
                }
                $normalized[$key] = $this->normalizedFormattedData($value['value']);
                continue;
            }
            $normalized[$key] = $value['value'];
        }
        return $normalized;
    }

    protected function getIntegrationProductPayload()
    {
        return null;
    }

    public function checkProductEanExists(array $parsedProduct = [])
    {
        $normalizedProduct = $this->normalizedFormattedData($parsedProduct);
        if (!empty($normalizedProduct['ean'] ?? '')) {
            $existProduct = $this->model_products->verifyUniqueEanProductVariation($normalizedProduct['ean'], $this->store, $normalizedProduct['id'] ?? 0);
            if (($existProduct['id'] ?? 0) > 0) {
                $existProduct = $this->model_products->getProductOrVariation($existProduct['id'], $existProduct['type']);
                $type = $existProduct['type'] === 'variation' ? $this->lang->line('application_variation') : $this->lang->line('application_product');
                throw new \TransformationException(
                    sprintf(
                        $this->lang->line('message_ean_already_exists'),
                        $normalizedProduct['ean'],
                        strtolower($type),
                        $existProduct['name'],
                        $existProduct['sku']
                    )
                );
            }
            foreach ($normalizedProduct['variations'] ?? [] as $variation) {
                $existProduct = $this->model_products->verifyUniqueEanProductVariation($variation['ean'], $this->store, $variation['id'] ?? 0);
                if (($existProduct['id'] ?? 0) > 0) {
                    $existProduct = $this->model_products->getProductOrVariation($existProduct['id'], $existProduct['type']);
                    $type = $existProduct['type'] === 'variation' ? $this->lang->line('application_variation') : $this->lang->line('application_product');
                    throw new \TransformationException(
                        sprintf(
                            $this->lang->line('message_ean_already_exists'),
                            $normalizedProduct['ean'],
                            strtolower($type),
                            $existProduct['name'],
                            $existProduct['sku']
                        )
                    );
                }
            }
        }
    }
    protected function fetchVariationTypeAttrMapped(): array
    {
        if (empty($this->typeVariationAttrMapped)) {
            $mappedAttrs = $this->customAttributeMapService->fetchCustomAttributesMapByCriteria([
                'store_id' => $this->store,
                'company_id' => $this->company,
                'module' => CustomAttribute::PRODUCT_VARIATION_MODULE,
                'status' => 1,
                'enabled' => 1
            ], 0, 1000);
            foreach ($mappedAttrs as $mappedAttr) {
                $this->typeVariationAttrMapped[$mappedAttr['value'] ?? null] = $mappedAttr['code'] ?? null;
            }
        }
        return $this->typeVariationAttrMapped;
    }

    protected function setResourceConfiguration()
    {
        $this->typeVariation = array_merge($this->typeVariation, $this->fetchVariationTypeAttrMapped());
        array_multisort(array_map('strlen', array_keys($this->typeVariation)), SORT_DESC, $this->typeVariation);
    }

    /**
     * @param   string  $sku    Código SKU do produto (products.sku).
     * @return  mixed           Retorna uma listagem de produtos.
     */
    public function getVariationByProductSku(string $sku)
    {
        return $this->model_products->getVariantsForSkuAndStore($sku, $this->store);
    }

    /**
     * @param   int         $product_id     ID do produto (products.id).
     * @return  null|array                  Dados do produto.
     */
    public function getProductById(int $product_id): ?array
    {
        return $this->model_products->getProductData(0, $product_id);
    }

    /**
     * Atualização de produtos
     *
     * @info Variação não envia para lixeira, somente produto simples/pai.
     *
     * @param   string  $sku_product    SKU do produto.
     */
    public function trashProduct(string $sku_product)
    {
        // request to delete product
        $urlTrashProduct = $this->process_url."Api/V1/Products/trash/$sku_product";
        $queryTrashProduct = array(
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->setRequestResponse($this->client_cnl->request('DELETE', $urlTrashProduct, $queryTrashProduct));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->setRequestResponse($exception);
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            /*$this->log_integration(
                "Erro para enviar o produto ($sku_product) para a lixeira",
                "<h4>Não foi possível enviar o produto para a lixeira</h4><p>$erroMessage</p><br><strong>SKU</strong>: $sku_product",
                "E");*/

            throw new InvalidArgumentException($erroMessage);
        }
    }

    /**
     * Atualização de produtos
     *
     * @info Variação não envia para lixeira, somente produto simples/pai.
     *
     * @param   string  $sku_product    SKU do produto.
     */
    public function processCollection(string $sku_product, string $marketplace, array $collections)
    {
        // request to delete product
        $urlProcessCollections = $this->process_url."Api/V1/Collections/product/$sku_product/$marketplace/false";
        $queryProcessCollections = array(
            'json' => array(
                'collections' => $collections ?? [],
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->setRequestResponse($this->client_cnl->request('POST', $urlProcessCollections, $queryProcessCollections));
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $this->setRequestResponse($exception);
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());
            throw new InvalidArgumentException($erroMessage);
        }
    }

    protected function validateImageUrl(string $url): bool
    {
        try {
            $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 99);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ((int)$code) === 200;
        } catch (\Throwable $e) {

        }
        return false;
    }

    private function convertUpdateVariationToProduct(array $payload): array
    {
        $new_payload = array();

        if (!empty($payload['sku'])) {
            $new_payload['sku']['value'] = $payload['sku'];
        }

        if (!empty($payload['list_price'])) {
            $new_payload['list_price']['value'] = $payload['list_price'];
        }

        if (!empty($payload['price'])) {
            $new_payload['price']['value'] = $payload['price'];
        }

        if (!empty($payload['qty'])) {
            $new_payload['qty']['value'] = $payload['qty'];
        }

        if (!empty($payload['stock'])) {
            $new_payload['stock']['value'] = $payload['stock'];
        }

        if (!empty($payload['status'])) {
            $new_payload['status']['value'] = $payload['status'];
        }

        return $new_payload;
    }

    private function convertUpdateProductToVariation(array $payload): array
    {
        $new_payload = array();

        if (!empty($payload['sku']['value'])) {
            $new_payload['sku'] = $payload['sku']['value'];
        }

        if (!empty($payload['list_price']['value'])) {
            $new_payload['list_price'] = $payload['list_price']['value'];
        }

        if (!empty($payload['price']['value'])) {
            $new_payload['price'] = $payload['price']['value'];
        }

        if (!empty($payload['qty']['value'])) {
            $new_payload['qty'] = $payload['qty']['value'];
        }

        if (!empty($payload['stock']['value'])) {
            $new_payload['stock'] = $payload['stock']['value'];
        }

        if (!empty($payload['status']['value'])) {
            $new_payload['status'] = $payload['status']['value'];
        }

        return $new_payload;
    }

    /**
     * Associar Add-On em um produto,
     *
     * @param   array   $payload    Dados para associar add-on.
     */
    public function syncAddOnProduct(array $payload)
    {
        // Loja que usa catálogo não pode criar produtos.
        if ($this->store_uses_catalog) {
            return;
        }

        // request to create product
        $urlCreateProduct = $this->process_url."Api/V1/AddOn/".$payload['sku'];
        $queryCreateProduct = array(
            'json' => array(
                'addon' => array('skus' => $payload['sku_addon'])
            ),
            'headers' => $this->credentials->api_internal
        );

        try {
            $this->setRequestResponse($this->client_cnl->request('POST', $urlCreateProduct, $queryCreateProduct));
        } catch (GuzzleException $exception) {
            $this->setRequestResponse($exception);
            $erroMessage = $this->getMessageRequestApiInternal(method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage());

            throw new InvalidArgumentException($erroMessage);
        }
    }

    public function getStringRequestResponse()
    {
        $exception = $this->requestResponse;

        if (is_string($exception) || is_null($exception)) {
            return $exception;
        }

        if (
            method_exists($exception, 'getResponse') &&
            method_exists($exception->getResponse(), 'getBody') &&
            method_exists($exception->getResponse()->getBody(), 'getContents')
        ) {
            $exception->getResponse()->getBody()->seek(0);
            return $exception->getResponse()->getBody()->getContents();
        } else if (
            method_exists($exception, 'getBody') &&
            method_exists($exception->getBody(), 'getContents')
        ) {
            return $exception->getBody()->getContents();
        } else if (
            method_exists($exception, 'getMessage')
        ) {
            return $exception->getMessage();
        }

        return null;
    }

    private function setRequestResponse($exception)
    {
        if (is_string($exception) || is_null($exception)) {
            return $this->requestResponse = $exception;
        }
        if (
            method_exists($exception, 'getResponse') &&
            method_exists($exception->getResponse(), 'getBody') &&
            method_exists($exception->getResponse()->getBody(), 'getContents')
        ) {
            $exception->getResponse()->getBody()->seek(0);
            return $this->requestResponse = $exception;

        } else if (
            method_exists($exception, 'getBody') &&
            method_exists($exception->getBody(), 'getContents')
        ) {
            $exception->getBody()->seek(0);
            return $this->requestResponse = $exception;
        } else if (
            method_exists($exception, 'getMessage')
        ) {
            return $this->requestResponse = $exception;
        } else {
            return $this->requestResponse = null;
        }
    }

    /**
     * Recupera dados do produto pelo SKU da variação
     *
     * @param   string      $sku    SKU da variação
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductGroupedBySkuVar(string $sku): ?array
    {
        return $this->model_products->getProductGroupedVariantsBySkuAndStore($sku, $this->store);
    }
}