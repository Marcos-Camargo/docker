<?php

namespace Integration\Integration_v2\microvix;

require APPPATH . "libraries/Integration_v2/Product_v2.php";
require_once APPPATH . 'libraries/Integration_v2/microvix/Services/LinxB2InputService.php';
require_once APPPATH . 'libraries/Integration_v2/microvix/Services/LinxB2OutputService.php';


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\microvix\Services\LinxB2InputService;
use Integration\Integration_v2\microvix\Services\LinxB2OutputService;
use Integration\Integration_v2\Product_v2;
use InvalidArgumentException;
use Exception;

class ToolsProduct extends Product_v2
{
    /**
     * @var LinxB2InputService Classe de serviço que envia requests para endpoint de entrada da Microvix
     */
    public $linxB2InputService;

    /**
     * @var LinxB2OutputService Classe de serviço que envia requests para endpoint de Saida da Microvix
     */
    public $linxB2OutputService;

    public $initialized = false;

    public function __construct()
    {
        parent::__construct();
    }

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->linxB2InputService = new LinxB2InputService($this->store);
        $this->linxB2OutputService = new LinxB2OutputService($this->store);
        $this->initialized = true;
    }

    /**
     * Recupera dados do produto na integradora.
     * @param       string $id Código do sku do produto
     * @param       string $dateLastedRun Data da ultima execução da integração
     * @return      array|null Array resultante caso sucesso, se não, null.
     */
    public function getDataProductIntegration(string $id)
    {
        $this->initialize();
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $id
        ];

        $command = 'B2CConsultaProdutos';

        $response = $this->linxB2OutputService->request($command, $data);
        if ($response['status'] != 200) {
            throw new InvalidArgumentException('Erro ao buscar produtos');
        }

        return $response['body'][0];
    }

    /**
     * Recupera dados de todos os produtos na integradora.
     * @return      array|null Array resultante caso sucesso, se não, null.
     */
    public function getAllDataProductIntegration($dateLastedRun)
    {
        $this->initialize();

        $timestamp = is_null($dateLastedRun) ? 0 : strtotime($dateLastedRun);
        $data = [
            'timestamp' => $timestamp
        ];

        $command = 'B2CConsultaProdutos';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            throw new InvalidArgumentException('Erro ao buscar produtos');
        }

        return $response['body'];
    }

    /**
     * Formata os dados do produto para criar ou atualizar
     * @param   array|object $payload   Dados do produto para formatação
     * @param   mixed        $option    Dados opcionais para auxílio na formatação
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        $this->initialize();

        $ean = $this->getEanProduct($payload['codigoproduto']);
        if (empty($ean)) {
            return [];
        }

        $price = $this->getPriceErp($payload['codigoproduto']);
        if (empty($price)) {
            return [];
        }

        $stock = $this->getStockErp($payload['codigoproduto']);
        if (empty($stock)) {
            return [];
        }

        $image = $this->getImageProduct($payload['codigoproduto']);

        $description = empty($payload['descricao_completa_commerce']) ? $payload['descricao_basica'] : $payload['descricao_completa_commerce'];
        $imageProduct =  empty($image['url_imagem_blob']) ? [] : $this->getImagesFormatted($image['url_imagem_blob']);

        $productFormatted = array(
            'name'                  => array('value' => $payload['nome_produto'], 'field_database' => 'name'),
            'status'                => array('value' => $payload['ativo'], 'field_database' => 'status'),
            'sku'                   => array('value' => $payload['codigoproduto'], 'field_database' => 'sku'),
            'unity'                 => array('value' => $payload['unidade'], 'field_database' => 'attribute_value_id'),
            'price'                 => array('value' => $price['price_product'], 'field_database' => 'price'),
            'list_price'            => array('value' => $price['listPrice_product'], 'field_database' => 'list_price'),
            'stock'                 => array('value' => $stock['stock_product'], 'field_database' => 'qty'),
            'ncm'                   => array('value' => $payload['ncm'], 'field_database' => 'NCM'),
            'origin'                => array('value' =>  0, 'field_database' => 'origin'), //perguntar qual campo se existe ou não
            'ean'                   => array('value' => $ean['codebar'], 'field_database' => 'EAN'),
            'net_weight'            => array('value' => (float) $payload['peso_liquido'], 'field_database' => 'peso_liquido'),
            'gross_weight'          => array('value' => (float) $payload['peso_bruto'], 'field_database' => 'peso_bruto'),
            'sku_manufacturer'      => array('value' => 'cod_fornecedor', 'field_database' => 'codigo_do_fabricante'),
            'description'           => array('value' => $description, 'field_database' => 'description'),
            'guarantee'             => array('value' => $payload['meses_garantia_fabrica'], 'field_database' => 'garantia'),
            'brand'                 => array('value' => '', 'field_database' => 'brand_id'),
            'height'                => array('value' => (float) $payload['altura_para_frete'], 'field_database' => 'altura'),
            'depth'                 => array('value' => (float) $payload['comprimento_para_frete'], 'field_database' => 'profundidade'),
            'width'                 => array('value' => (float) $payload['largura_para_frete'], 'field_database' => 'largura'),
            'items_per_package'     => array('value' => null, 'field_database' => 'products_package'),
            'category'              => array('value' => '', 'field_database' => 'category_id'),
            'images'                => array('value' => $imageProduct, 'field_database' => NULL),
            'variations'            => array('value' => $this->getVariationFormatted($payload, $payload), 'field_database' => 'has_variants'),
            'extra_operating_time'  => array('value' => 1, 'field_database' => 'prazo_operacional_extra')
        );

        return $productFormatted;
    }

    protected function getEanProduct(int $productId): ?array
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $productId
        ];

        $command = 'B2CConsultaProdutosCodebar';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            throw new InvalidArgumentException('Erro ao buscar codigo de barras do produto');
        }

        $body = $response['body'][0] ?? null;
        if ($body === null) {
            return [];
        }

        return $response['body'][0];
    }

    protected function getImageProduct($id): ?array
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $id
        ];

        $command = 'B2CConsultaImagensHD';

        $response = $this->linxB2OutputService->request($command, $data);
        if ($response['status'] != 200) {
            throw new InvalidArgumentException('Erro ao buscar imagens do produto');
        }

        return $response['body'];
    }

    /**
     * Recupera o preço de um determinado produto no ERP.
     * @param       null|string|int $id Código do sku do produto.
     * @return      array|null|bool Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id): ?array
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $id
        ];

        $command = 'B2CConsultaProdutosCustos';

        $response = $this->linxB2OutputService->request($command, $data);
        if ($response['status'] != 200) {
            throw new InvalidArgumentException('Erro ao buscar preço do produto');
        }

        $body = $response['body'][0] ?? null;
        if ($body === null) {
            return [];
        }

        return [
            'price_product'     => $response['body'][0]['precovenda'],
            'listPrice_product' => $response['body'][0]['precovenda'],
            'listPrice_variation' => '',
            'price_variation'   => ''
        ];
    }

    /**
     * Recupera o estoque de produtos.
     * Caso o $id seja passado como null, recupera todos produtos baseados no timestamp.
     *
     * @param   string|int|null         Id do produto. Se nulo, retorna todos produtos desde o último timestamp.
     * @return  array|null|bool         Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id): ?array
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $id
        ];

        $command = 'B2CConsultaProdutosDetalhes';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            throw new InvalidArgumentException('Erro ao buscar preço do produto');
        }

        $stockSkus = [];

        $body = $response['body'][0] ?? null;
        if ($body === null) {
            return [];
        }

        return [
            'stock_product'     => $response['body'][0]['saldo'],
            'stock_variation'   => $stockSkus
        ];
    }

    /**
     * @param   array   $payload    Dados da variação para formatação
     * @param   mixed   $option     Dados opcionais para auxílio na formatação
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        return array();
    }

    /**
     * Recupera o estoque de produto(s)
     *
     * @param   array|string|int    $id Código(s) do(s) sku(s) do(s) produto(s)
     *  @return  array|null|false        Retorna array com estoque (int[stock_product], array[stock_variation], int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro
     */
    public function getPriceStockErp($id)
    {
        return null;
    }

    /**
     * Define os atributos para o produto
     * @param   string      $productId          Código do produto (products.id)
     * @param   string      $productIntegration Código do produto na integradora
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"]
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        $collor = $this->getColorAttribute($productIntegration);
        $size = $this->getSizeAttribute($productIntegration);
        $thickness = $this->getThicknessAttribute($productIntegration);

        return [
            'color' => $collor,
            'size' => $size,
            'thickness' => $thickness
        ];
    }

    /**
     * @param string $productIntegration
     * @return string|null
     */
    protected function getSizeAttribute(string $productIntegration): ?string
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $productIntegration
        ];

        $command = 'B2CConsultaGrade1';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            return null;
        }

        $body = $response['body'] ?? null;

        if (empty($body) || !isset($body['nome_grade1'])) {
            return null;
        }

        return $body['nome_grade1'];
    }

    /**
     * @param string $productIntegration
     * @return string|null
     */
    protected function getColorAttribute(string $productIntegration): ?string
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $productIntegration
        ];

        $command = 'B2CConsultaGrade2';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            return null;
        }

        $body = $response['body'] ?? null;

        if (empty($body) || !isset($body['nome_grade2'])) {
            return null;
        }

        return $body['nome_grade2'];
    }

    /**
     * @param string $productIntegration
     * @return string|null
     */
    protected function getThicknessAttribute(string $productIntegration): ?string
    {
        $data = [
            'timestamp' => 0,
            'codigoproduto' => $productIntegration
        ];

        $command = 'B2CConsultaEspessuras';

        $response = $this->linxB2OutputService->request($command, $data);

        if ($response['status'] != 200) {
            return null;
        }

        $body = $response['body'] ?? null;

        if (empty($body) || !isset($body['nome_espessura'])) {
            return null;
        }

        return $body['nome_espessura'];
    }

    /**
     * Define os atributos para o sku
     * @param   string      $productId      Código do produto (products.id)
     * @param   string      $skuIntegration Código do produto na integradora
     * @return  array|null                  ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"]
     */
    public function getAttributeSku(string $productId, string $skuIntegration): ?array
    {
        return null;
    }

    /**
     * Consulta os dados do atributo.
     * @param   int     $attribute_id   Código do atributo
     * @return  object
     * @throws  Exception
     */
    public function getDataAttribute(int $attribute_id): object
    {
        $urlSpecification = "api/catalog/pvt/specification/$attribute_id";

        try {
            $request = $this->request('GET', $urlSpecification);
            return Utils::jsonDecode($request->getBody()->getContents());
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param   array   $payload    Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(?array $payload): array
    {
        $arrImages = array();

        if (empty($payload)) {
            return [];
        }

        foreach ($payload as $image) {
            $arrImages[] = $image->imageUrl ?? $image->ImageUrl;
        }

        return $arrImages;
    }

}