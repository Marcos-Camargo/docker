<?php

namespace Integration\Integration_v2\viavarejo_b2b;

require_once APPPATH . 'libraries/Integration_v2/Product_v2.php';
require_once APPPATH . 'libraries/Validations/ProductIntegrationValidation.php';
require_once APPPATH . 'libraries/Integration_v2/viavarejo_b2b/Resources/Mappers/FlagMapper.php';
require_once APPPATH . 'libraries/Helpers/StringHandler.php';

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use Integration_v2\viavarejo_b2b\Resources\Mappers\FlagMapper;
use InvalidArgumentException;
use libraries\Helpers\StringHandler;

/**
 * Class ToolsProduct
 * @package Integration\Integration_v2\viavarejo_b2b
 * @property \ProductIntegrationValidation $productIntegrationValidation
 */
class ToolsProduct extends Product_v2
{

    private $parsedProduct = [];

    private $parsedVariations = [];

    private $isPublishedProduct = false;

    private $productPayload;

    private $flagName = null;
    private $flagId = null;
    private $campaignId = null;

    /**
     * Instantiate a new Tools instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->productIntegrationValidation = new \ProductIntegrationValidation(
            $this->model_products,
            $this->model_integrations
        );
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

    /**
     * Recupera dados do produto na integradora.
     *
     * @param string $id Código do produto.
     */
    public function getDataProductIntegration(string $id)
    {
        try {
            $request = $this->request('GET', "/campanhas/{$this->credentials->campaign}/produtos/$id");
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $message = method_exists($exception, 'getResponse') ? $exception->getResponse()->getBody()->getContents() : $exception->getMessage();
            echo "[LINE: " . __LINE__ . "] $message\n";
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        $product = Utils::jsonDecode($request->getBody()->getContents());

        if (
            empty($product->data) ||
            (
                !empty($product->error) &&
                !empty($product->message)
            )
        ) {
            return null;
        }

        return $product->data;
    }

    public function setFlagName($flagName)
    {
        $this->flagName = $flagName;
        return $this;
    }

    public function setFlagId($flagId)
    {
        $this->flagId = $flagId;
        return $this;
    }

    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    public function getCampaignId()
    {
        return $this->campaignId;
    }

    protected function buildImageURLFromFlagAndFileId($flag, $fileId)
    {
        // O campo '$fileId' já é a URL.
        if (!is_numeric($fileId) && likeText('%imgs.via.com.br%', $fileId)) {
            return $fileId;
        }
        $imgUrl[] = FlagMapper::MAP_IMAGES_HOSTS[$flag] ?? FlagMapper::MAP_IMAGES_HOSTS[FlagMapper::FLAG_CASASBAHIA];
        $imgUrl[] = "Control/ArquivoExibir.aspx?IdArquivo={$fileId}";
        return implode('/', $imgUrl);
    }

    protected function getIntegrationProductPayload()
    {
        return $this->productPayload;
    }

    /**
     * Formata os dados do produto para criar ou atualizar.
     *
     * @param array|object $payload Dados do produto para formatação.
     * @param mixed $option Dados opcionais para auxílio na formatação.
     * @return  array                   Retorna o preço do produto.
     * @throws Exception
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $this->isPublishedProduct = false;

        $this->productPayload = $payload;

        $imgUrl = [];
        if (isset($this->productPayload->FotoGrande)) {
            $imgUrl = [$this->buildImageURLFromFlagAndFileId($this->flagName, $this->productPayload->FotoGrande)];
        }

        // Recupera a imagem maior.
        if (
            empty($imgUrl) &&
            (
                (
                    property_exists($this->productPayload, 'UrlImagemMaior') &&
                    !empty($this->productPayload->UrlImagemMaior)
                ) ||
                (
                    property_exists($this->productPayload, 'ImagemMaior') &&
                    !empty($this->productPayload->ImagemMaior)
                )
            )
        ) {
            $imgUrl = [$this->buildImageURLFromFlagAndFileId($this->flagName,
                property_exists($this->productPayload, 'UrlImagemMaior') && !empty($this->productPayload->UrlImagemMaior) ?
                    $this->productPayload->UrlImagemMaior :
                    $this->productPayload->ImagemMaior
            )];
        }

        // Recupera a imagem zoom.
        if (
            empty($imgUrl) &&
            (
                (
                    property_exists($this->productPayload, 'ImagemZoom') &&
                    !empty($this->productPayload->ImagemZoom)
                ) ||
                (
                    property_exists($this->productPayload, 'UrlImagemZoom') &&
                    !empty($this->productPayload->UrlImagemZoom)
                )
            )
        ) {
            $imgUrl = [$this->buildImageURLFromFlagAndFileId($this->flagName,
                property_exists($this->productPayload, 'ImagemZoom') && !empty($this->productPayload->ImagemZoom) ?
                    $this->productPayload->ImagemZoom :
                    $this->productPayload->UrlImagemZoom
            )];
        }

        $decodedDescription = html_entity_decode($this->productPayload->DescricaoLonga, ENT_NOQUOTES, 'UTF-8');
        $decodedDescription = preg_replace('/[ \n\n\t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", strip_tags($decodedDescription)));
        $this->productPayload->Codigo = (string)$this->productPayload->Codigo;
        $this->productPayload->Fabricante = $this->productPayload->NomeFabricante ?? $this->productPayload->Fabricante;
        $this->parsedProduct = [
            'name' => ['value' => $this->productPayload->DisplayName, 'field_database' => 'name'],
            'sku' => ['value' => $this->productPayload->Codigo, 'field_database' => 'sku'],
            'unity' => ['value' => 'UN', 'field_database' => 'attribute_value_id'],
            'price' => ['value' => 0, 'field_database' => 'price'],
            'list_price' => ['value' => 0, 'field_database' => 'list_price'],
            'stock' => ['value' => 0, 'field_database' => 'qty'],
            'status' => ['value' => \Model_products::ACTIVE_PRODUCT, 'field_database' => 'status'],
            'ean' => ['value' => '', 'field_database' => 'EAN'],
            'origin' => ['value' => 0, 'field_database' => 'origin'],
            'ncm' => ['value' => '', 'field_database' => 'NCM'],
            'net_weight' => ['value' => 0, 'field_database' => 'peso_liquido'],
            'gross_weight' => ['value' => 0, 'field_database' => 'peso_bruto'],
            'description' => ['value' => $decodedDescription, 'field_database' => 'description'],
            'height' => ['value' => 0, 'field_database' => 'altura'],
            'depth' => ['value' => 0, 'field_database' => 'profundidade'],
            'width' => ['value' => 0, 'field_database' => 'largura'],
            'product_height' => ['value' => 0, 'field_database' => 'altura'],
            'product_depth' => ['value' => 0, 'field_database' => 'profundidade'],
            'product_width' => ['value' => 0, 'field_database' => 'largura'],
            'images' => ['value' => !empty($imgUrl) ? $imgUrl : [], 'field_database' => NULL],
            'variations' => ['value' => [], 'field_database' => 'has_variants'],
            //'extra_operating_time' => ['value' => 0, 'field_database' => 'prazo_operacional_extra'],
            'category' => ['value' => $this->productPayload->NomeCategoria ?? '', 'field_database' => 'category_id'],
            /*'category' => [
                'value' => $this->productPayload->Categoria ?? $this->productPayload->IdCategoria ?? 0, 'field_database' => 'category_id'
            ],*/
            'brand' => ['value' => $this->productPayload->Fabricante, 'field_database' => 'brand_id'],
            '_product_id_erp' => ['value' => $this->productPayload->Codigo, 'field_database' => 'product_id_erp'],
            '_published' => ['value' => false, 'field_database' => ''],
        ];

        if (isset($this->productPayload->Grupos)) {
            foreach ($this->productPayload->Grupos as $grupo) {
                foreach ($grupo->Itens as $item) {
                    if (strcasecmp($item->Descricao, 'Garantia') === 0) {
                        $this->parsedProduct['guarantee'] = ['value' => onlyNumbers($item->Valor), 'field_database' => 'garantia'];
                    }
                }
            }
        }

        $simpleProduct = empty(StringHandler::slugify(current($this->productPayload->Skus)->Modelo, '')) ? current($this->productPayload->Skus) : null;
        if ($simpleProduct) {
            $this->productPayload->Codigo = $simpleProduct->IdSku ?? $simpleProduct->Codigo;
            $this->parsedProduct['images']['value'] = [];
        }
        $this->productIntegrationValidation->validateUpgradeableProduct([
                'sku' => $this->productPayload->Codigo,
                'store_id' => $this->store,
                'product_id_erp' => $this->productPayload->Codigo
            ]
        );

        $productIdErp = $this->model_products->getByProductIdErpAndStore(
            $this->productPayload->Codigo,
            $this->store
        );
        if (!empty($productIdErp)) {
            $this->productIntegrationValidation->setProduct($productIdErp);
        }

        $existingProduct = $this->productIntegrationValidation->getProduct();
        if (isset($existingProduct['id'])) {
            $this->parsedProduct['id'] = ['value' => $existingProduct['id'], 'field_database' => 'id'];
            $this->parsedProduct['sku'] = ['value' => $existingProduct['sku'], 'field_database' => 'sku'];
            if ($this->productIntegrationValidation->isPublishedProduct($existingProduct['id'])) {
                $this->isPublishedProduct = true;
                $this->parsedProduct['_published']['value'] = true;

            }
            if ($simpleProduct) {
                $this->parsedProduct['stock'] = ['value' => $existingProduct['qty'], 'field_database' => 'qty'];
            }
            $this->parsedProduct['price'] = ['value' => $existingProduct['price'], 'field_database' => 'price'];
            $this->parsedProduct['list_price'] = ['value' => $existingProduct['list_price'], 'field_database' => 'list_price'];
        }
        if (!$this->isPublishedProduct || !$this->productIntegrationValidation->productExists()) {
            $this->parsedProduct['_manufacturer'] = [
                'value' => $this->productPayload->Fabricante,
                'field_database' => 'brand_id'
            ];
        }

        $this->parsedProduct['variations']['value'] = $this->getVariationFormatted($this->productPayload->Skus);

        // Não encontrou variação
        if (!count($this->parsedProduct['variations']['value'])) {
            if (count($payload->Skus) !== 1) {
                throw new Exception("Produto contém variação, mas não foi possível identificar o tipo");
            }
            $sku = $payload->Skus[0];

            if (property_exists($sku, 'Modelo') && $sku->Modelo !== '.') {
                throw new Exception("Produto contém variação, mas não foi possível identificar o tipo para $sku->Modelo");
            }
        }

        $this->parsedProduct['price']['value'] = $this->parsedProduct['price']['value'] > 0 ? $this->parsedProduct['price']['value'] : $this->parsedProduct['list_price']['value'];
        $this->parsedProduct['list_price']['value'] = $this->parsedProduct['list_price']['value'] > 0 ? $this->parsedProduct['list_price']['value'] : $this->parsedProduct['price']['value'];
        if ($this->isPublishedProduct && $this->productIntegrationValidation->productExists()) {
            $updatableFields = [
                'id', 'sku', 'status', 'price', 'list_price', 'stock', 'variations', '_product_id_erp', '_published', 'images'
            ];
            $this->parsedProduct = array_intersect_key($this->parsedProduct, array_flip($updatableFields));
        }

        if (empty($this->parsedProduct['price']['value']) && empty($this->parsedProduct['variations']['value'])) {
            try {
                $price_product = $this->getPriceErp($this->parsedProduct['sku']['value']);
                $this->parsedProduct['price']['value']      = $price_product['price_product'];
                $this->parsedProduct['list_price']['value'] = $price_product['listPrice_product'];
            } catch (InvalidArgumentException $exception) {}
        }

        get_instance()->log_data(__CLASS__, __FUNCTION__.'/parsedProduct', json_encode($this->parsedProduct));

        return $this->parsedProduct;
    }

    protected function fetchProductAndVariation($refId)
    {
        $product = $this->model_products->getByProductIdErpAndStore($refId, $this->store);
        if (!$product) {
            $variant = $this->model_products->getByVariantIdErpAndStore($refId, $this->store);
            if (!$variant) {
                throw new \ErrorException("Não localizado produto ou variação com o Codigo/IdSku {$refId} informado para a loja: #{$this->store}");
            }

            return [
                'id' => $variant['id'],
                'sku' => $variant['prdSku'],
                'varSku' => $variant['sku'],
                '_variant_id_erp' => $variant['variant_id_erp'],
            ];
        }
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'],
            '_product_id_erp' => $product['product_id_erp'],
        ];
    }

    public function getAvailabilityFormattedToIntegration($rawAvailability)
    {
        $parsedPrice = $this->fetchProductAndVariation($rawAvailability->Codigo);
        $price = $rawAvailability->PrecoPor > 0 ? $rawAvailability->PrecoPor : $rawAvailability->PrecoDe;
        $listPrice = $rawAvailability->PrecoDe > 0 ? $rawAvailability->PrecoDe : $price;
        return array_merge($parsedPrice, [
            'price' => $price,
            'list_price' => $listPrice,
            'status' => ((int)$rawAvailability->Disponibilidade) == 1 ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT
        ]);
    }

    public function parseStockToIntegration($rawStock)
    {
        $parsedStock = $this->fetchProductAndVariation($rawStock->IdSku);

        if (!property_exists($rawStock, 'SaldoEstoque')) {
            return [];
        }

        return array_merge($parsedStock, ['stock' => (int)$rawStock->SaldoEstoque]);
    }

    /**
     * Recupera se o preço do produto.
     *
     * @param array|string|int $id Código do sku do produto.
     * @param float|null $price Preço do produto/variação. Já tenho o preço e preciso do preço da lista de preço.
     * @return  array                       Retorna array com preço (int[price_product] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id, float $price = null): array
    {
        try {
            $request = $this->request('GET', "/campanhas/{$this->credentials->campaign}/produtos/$id");
            $regProducts = Utils::jsonDecode($request->getBody()->getContents());

            if (
                !$regProducts ||
                !property_exists($regProducts, 'data') ||
                (
                    property_exists($regProducts, 'error') &&
                    !empty($regProducts->error->message)
                )
            ) {
                throw new InvalidArgumentException("Preço não não encontrado para o sku $id");
            }

            $regProducts = $regProducts->data;
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        return array(
            'price_product'         => $regProducts->valor,
            'listPrice_product'     => $regProducts->valorDe
        );
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

        $this->parsedVariations = [];

        $hasActiveVar = false;
        foreach ($payload as $sku) {
            $skuCode = $sku->IdSku ?? $sku->Codigo;

            $sku->Preco = decimalNumber($sku->Preco ?? 0);

            $weight = (float)number_format((((int)$sku->Peso ?? 0) / 1000), 3, '.', '');

            $this->parsedProduct['price']['value'] = $sku->Preco > 0 ? $sku->Preco : $this->parsedProduct['price']['value'];
            $this->parsedProduct['list_price']['value'] = $sku->Preco > 0 ? $sku->Preco : $this->parsedProduct['price']['value'];
            $this->parsedProduct['stock']['value'] = 0;
            $this->parsedProduct['status']['value'] = ((string)$sku->Habilitado == 'true') ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT;
            $this->parsedProduct['net_weight']['value'] = $weight > 0 ? $weight : $this->parsedProduct['net_weight']['value'];;
            $this->parsedProduct['gross_weight']['value'] = $weight > 0 ? $weight : $this->parsedProduct['gross_weight']['value'];;
            $this->parsedProduct['height']['value'] = $sku->Altura > 0 ? $sku->Altura : $this->parsedProduct['height']['value'];;
            $this->parsedProduct['width']['value'] = $sku->Largura > 0 ? $sku->Largura : $this->parsedProduct['width']['value'];;
            $this->parsedProduct['depth']['value'] = $sku->Comprimento > 0 ? $sku->Comprimento : $this->parsedProduct['depth']['value'];;
            $this->parsedProduct['product_height']['value'] = $sku->Altura > 0 ? $sku->Altura : $this->parsedProduct['product_height']['value'];;
            $this->parsedProduct['product_width']['value'] = $sku->Largura > 0 ? $sku->Largura : $this->parsedProduct['product_width']['value'];;
            $this->parsedProduct['product_depth']['value'] = $sku->Comprimento > 0 ? $sku->Comprimento : $this->parsedProduct['product_depth']['value'];;
            $this->parsedProduct['_flag_id'] = ['value' => $sku->IdLojista, 'field_database' => ''];

            $parsedImages = $this->getImagesFormatted($sku->Imagens ?? []);
            $variationsTypes = [];
            foreach ($this->typeVariation as $type => $mapType) {
                $prodGroups = $this->productPayload->FichaTecnica->Grupos ?? [];
                foreach ($prodGroups as $prodGroup) {
                    foreach ($prodGroup->Itens ?? [] as $item) {
                        if (strpos(StringHandler::slugify($item->Descricao ?? ''), $type) !== false) {
                            $variationsTypes[$item->Descricao] = $item->Valor ?? '';
                        }
                    }
                }
            }
            if (!empty(StringHandler::slugify($sku->Modelo ?? '', ''))) {
                $variationValues = array_map(function ($value) {
                    return trim($value);
                }, explode('-', $sku->Modelo ?? ''));

                foreach ($sku->Grupos ?? [] as $grupo) {
                    foreach ($grupo->Itens as $item) {
                        if (!empty($this->typeVariation[strtolower($item->Descricao ?? '')] ?? '')) {
                            $variationsTypes[$item->Descricao] = $item->Valor ?? '';
                        }
                    }
                }

                foreach ($variationValues as $variationValue) {
                    $prodGroups = $this->productPayload->FichaTecnica->Grupos ?? [];
                    foreach ($prodGroups as $prodGroup) {
                        foreach ($prodGroup->Itens ?? [] as $item) {
                            if (strpos(StringHandler::slugify($item->Valor ?? ''), StringHandler::slugify($variationValue)) !== false) {
                                $variationsTypes[$item->Descricao] = $variationValue;
                            }
                        }
                    }
                }

                foreach ($sku->Grupos ?? [] as $grupo) {
                    foreach ($grupo->Itens ?? [] as $item) {
                        if (strcasecmp($sku->Modelo ?? '', $item->Valor ?? '') === 0) {
                            $variationsTypes[$item->Descricao] = $item->Valor;
                        }
                    }
                }

                foreach ($variationsTypes as $type => $value) {
                    unset($variationsTypes[$type]);
                    $type = StringHandler::slugify($type);
                    if (!empty($this->typeVariation[$type] ?? '')) {
                        if (strcasecmp($this->typeVariation[$type], 'voltage') === 0) {
                            $value = strpos(strtolower($value), 'biv') !== false ? 'Bivolt' : (int)preg_replace("/[^0-9]/", '', $value);
                            //$value = strpos($value, '110220') !== false ? 'Bivolt' : $value;
                            //$value = strpos($value, '220110') !== false ? 'Bivolt' : $value;
                        }
                        $variationsTypes[$this->typeVariation[$type]] = $value;
                    }
                }

                $parsedVariation = [
                    'id' => 0,
                    'sku' => $skuCode,
                    'price' => $sku->Preco,
                    'list_price' => $sku->Preco,
                    'stock' => 0,
                    'status' => ($sku->Habilitado ?? '') ? \Model_products::ACTIVE_PRODUCT : \Model_products::INACTIVE_PRODUCT,
                    'ean' => $sku->EAN,
                    'images' => $parsedImages,
                    'variations' => $variationsTypes,
                    '_variant_id_erp' => $skuCode,
                    '_published' => $this->isPublishedProduct
                ];

                if ($this->productIntegrationValidation->productExists()) {
                    $prd = $this->productIntegrationValidation->getProduct();
                    $variation = $this->model_products->getVariantByPrdIdAndSku($prd['id'], $parsedVariation['sku']);
                    if (empty($variation)) {
                        $variation = $this->model_products->getVariantByPrdIdAndIDErp($prd['id'], $parsedVariation['_variant_id_erp']);
                    }
                    $parsedVariation['id'] = $variation['id'] ?? 0;
                    $parsedVariation['stock'] = isset($variation['qty']) ? (int)$variation['qty'] : $parsedVariation['stock'];
                    $parsedVariation['price'] = $variation['price'] ?? $parsedVariation['price'];
                    $parsedVariation['list_price'] = $variation['list_price'] ?? $parsedVariation['list_price'];
                    $parsedVariation['price'] = $parsedVariation['price'] > 0 ? $parsedVariation['price'] : $parsedVariation['list_price'];
                    $parsedVariation['list_price'] = $parsedVariation['list_price'] > 0 ? $parsedVariation['list_price'] : $parsedVariation['price'];
                    if ($this->isPublishedProduct) {
                        $updatableFields = [
                            'id', 'sku', 'status', 'stock', 'price', 'list_price', '_variant_id_erp', '_published', 'images'
                        ];
                        $parsedVariation = array_intersect_key($parsedVariation, array_flip($updatableFields));
                    }
                }

                if (empty($parsedVariation['price'])) {
                    try {
                        $price_product = $this->getPriceErp($skuCode);
                        $parsedVariation['price'] = $price_product['price_product'];
                        $parsedVariation['list_price'] = $price_product['listPrice_product'];

                        if ($parsedVariation['price'] > $this->parsedProduct['price']['value']) {
                            $this->parsedProduct['price']['value'] = $parsedVariation['price'];
                            $this->parsedProduct['list_price']['value'] = $parsedVariation['list_price'];
                        }
                    } catch (InvalidArgumentException $exception) {}
                }

                $hasActiveVar = $parsedVariation['status'] == \Model_products::ACTIVE_PRODUCT ? true : $hasActiveVar;
                if (empty($variationsTypes)) {
                    continue;
                }
                $this->parsedVariations[] = $parsedVariation;
            } else {
                $this->parsedProduct['sku']['value'] = $skuCode;
                $this->parsedProduct['ean']['value'] = $sku->EAN;
                $this->parsedProduct['images']['value'] = array_merge($this->parsedProduct['images']['value'], $parsedImages);
                $this->parsedProduct['_product_id_erp']['value'] = $skuCode;
            }
        }

        $this->parsedProduct['status']['value'] = $hasActiveVar ? \Model_products::ACTIVE_PRODUCT : $this->parsedProduct['status']['value'];
        return $this->parsedVariations;
    }

    public function getParsedVariations()
    {
        return $this->parsedVariations;
    }

    /**
     * @param array $payload Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $images = [];
        foreach ($payload as $image) {
            $image_temp = null;

            // Recupera a imagem maior.
            if (
                (
                    property_exists($image, 'UrlImagemMaior') &&
                    !empty($image->UrlImagemMaior)
                ) ||
                (
                    property_exists($image, 'ImagemMaior') &&
                    !empty($image->ImagemMaior)
                )
            ) {
                $image_temp = $this->buildImageURLFromFlagAndFileId($this->flagName,
                    property_exists($image, 'UrlImagemMaior') && !empty($image->UrlImagemMaior) ?
                        $image->UrlImagemMaior :
                        $image->ImagemMaior
                );
            }

            // Recupera a imagem zoom.
            if (is_null($image_temp) &&
                (
                    (
                        property_exists($image, 'ImagemZoom') &&
                        !empty($image->ImagemZoom)
                    ) || (
                        property_exists($image, 'UrlImagemZoom') &&
                        !empty($image->UrlImagemZoom)
                    )
                )
            ) {
                $image_temp = $this->buildImageURLFromFlagAndFileId($this->flagName,
                    property_exists($image, 'ImagemZoom') && !empty($image->ImagemZoom) ?
                        $image->ImagemZoom :
                        $image->UrlImagemZoom
                );
            }

            // Se não encontrou imagem, não salva no vetor.
            if (empty($image_temp)) {
                continue;
            }

            // Salva a imagem recuperada no vetor.
            $images[] = $image_temp; // $image->UrlImagemZoom
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
}
