<?php

namespace Integration\Integration_v2\linx_microvix;

require APPPATH . "libraries/Integration_v2/Product_v2.php";

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Integration\Integration_v2\Product_v2;
use Integration_v2\linx_microvix\Resources\Configuration;
use InvalidArgumentException;
use MicrovixXMLUtil;
use SimpleXMLElement;

/**
 * ToolsProduct para integração com a Linx-Microvix.
 * @property Configuration $configuration Instância da configuração da Microvix.
 * @property MicrovixXMLUtil $xmlUtil Instância do gerador de XML.
 */
class ToolsProduct extends Product_v2
{
    /**
     * Cria uma instância da ToolsProduct.
     */
    public function __construct()
    {
        parent::__construct();
        $this->formatRequest = "text/xml";
        $this->configuration = new Configuration();
        $this->xmlUtil = new MicrovixXMLUtil($this->credentials);
    }

    /**
     * Define os atributos para o produto
     * @param   string      $productId          Código do produto (products.id)
     * @param   string      $productIntegration Código do produto na integradora
     * @return  array|null                      ["Cor": "Vermelho", "Gênero": "Masculino", "Composição": "Plástico"]
     */
    public function getAttributeProduct(string $productId, string $productIntegration): ?array
    {
        return null;
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
     * Recupera dados do produto na integradora.
     * @param       string $id Código do sku do produto
     * @return      SimpleXMLElement|null XML resultante caso sucesso, se não, null.
     */
    public function getDataProductIntegration(string $id)
    {
        // Monta o body para realizar a request de consulta de produto.
        $requestBody = $this->xmlUtil->generateBody("B2CConsultaProdutos", ['cnpjEmp' => $this->credentials->microvix_cnpj, 'timestamp' => 0]);
        try {
            // Realiza a request para o endpoint de saida.
            $request = $this->request('POST', $this->configuration->getUrlSaida(), ['body' => $requestBody]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            return null; // retorna null, pois, ocorreu um problema na consulta
        }

        // Realiza o parsing da response para XML.
        $response = new SimpleXMLElement($request->getBody()->getContents());
        if ($this->isSuccessfullRequest($response)) {
            return $response;
        } else {
            return null;
        }
    }

    /**
     * Formata os dados do produto para criar ou atualizar
     * @param   array|object $payload   Dados do produto para formatação
     * @param   mixed        $option    Dados opcionais para auxílio na formatação
     * @return  array                   Retorna o preço do produto.
     */
    public function getDataFormattedToIntegration($payload, $option = null): array
    {
        return array();
    }

    /**
     * Recupera o preço de um determinado produto no ERP. 
     * @param       null|string|int $id Código do sku do produto.
     * @return      array|null|bool Retorna array com preço (int[price_product], int[listPrice_product], int[listPrice_variation] e array[price_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getPriceErp($id)
    {

        $priceProd  = 0;
        $listPriceProd = 0;
        $requestBody = null;

        // Necessário adicionar timestamp.
        if ($id != null) {
            if (!is_numeric($id)) return false;
            // Monta o body para realizar a request de consulta de um determinado produto.
            $requestBody = $this->xmlUtil->generateBody("B2CConsultaProdutosCustos", null, ['cnpjEmp' => $this->credentials->microvix_cnpj, 'codigoproduto' => $id]);
        } else {
            // Monta o body para pegar todos os produtos desde o último timestamp.
            $requestBody = $this->xmlUtil->generateBody("B2CConsultaProdutosCustos", null, ['cnpjEmp' => $this->credentials->microvix_cnpj]);
        }

        try {
            // Realiza a request para o endpoint de saida.
            $request = $this->request('POST', $this->configuration->getUrlSaida(), ['body' => $requestBody]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            return null;
        }

        // Realiza o parsing da response para XML.
        $response = new SimpleXMLElement($request->getBody()->getContents());
        if (!$this->isSuccessfullRequest($response)) {
            return null;
        }

        /**
         * IMPLEMENTAR LÓGICA DE PREÇO.
         */

        return array(
            /*'price_product'     => $priceProd,
            'listPrice_product' => $listPriceProd,
            'listPrice_variation' => $listPriceSkus,
            'price_variation'   => $priceSkus*/);
    }

    /**
     * @param   array   $payload    Dados da variação para formatação
     * @param   mixed   $option     Dados opcionais para auxílio na formatação
     * @return  array
     */
    public function getVariationFormatted(array $payload, $option = null): array
    {
        /* $arrVariation = array();

        foreach ($payload as $variation) {
            $arrTempVariation = array();
            foreach ($variation->variations as $typeVariation) {
                $arrTempVariation[$typeVariation] = $variation->$typeVariation[0] ?? '';
            }
            $arrVariation[] = array(
                'id'            => $variation->itemId,
                'sku'           => $variation->itemId,
                'stock'         => 0,
                'price'         => 0,
                'list_price'    => 0,
                'ean'           => $variation->ean ?? null,
                'images'        => $this->getImagesFormatted($variation->images ?? array()),
                'variations'    => $arrTempVariation
            );
        }

        return $arrVariation;*/
        return array();
    }

    /**
     * @param   array   $payload    Dados de imagens para formatação
     * @return  array
     */
    public function getImagesFormatted(array $payload): array
    {
        $arrImages = array();

        foreach ($payload as $image) {
            $arrImages[] = $image->imageUrl ?? $image->ImageUrl;
        }

        return $arrImages;
    }

    /**
     * Recupera o estoque de produtos.
     * Caso o $id seja passado como null, recupera todos produtos baseados no timestamp.
     * 
     * @param   string|int|null         Id do produto. Se nulo, retorna todos produtos desde o último timestamp.
     * @return  array|null|bool         Retorna array com estoque (int[stock_product] e array[stock_variation]). NULL= Produto indisponível. Ocorreu um erro, talvez instabilidade.
     */
    public function getStockErp($id)
    {
        $requestBody = null;
        if ($id != null) {
            // Monta o body para realizar a request de consulta de produto.
            $requestBody = $this->xmlUtil->generateBody("B2CConsultaProdutosDetalhes", null, ['cnpjEmp' => $this->credentials->microvix_cnpj, "codigoproduto" => $id]);
        } else {
            // Monta o body para realizar a request de consulta de produto.
            $requestBody = $this->xmlUtil->generateBody("B2CConsultaProdutosDetalhes", null, ['cnpjEmp' => $this->credentials->microvix_cnpj]);
        }

        try {
            // Realiza a request para o endpoint de saida.
            $request = $this->request('POST', $this->configuration->getUrlSaida(), ['body' => $requestBody]);
        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            return null;
        }

        // Realiza o parsing da response para XML.
        $response = new SimpleXMLElement($request->getBody()->getContents());
        if ($this->isSuccessfullRequest($response)) {
            return $response;
        } else {
            return null;
        }

        /**
         * IMPLEMENTAR LÓGICA DE ESTOQUE.
         */

        return array(
            //'stock_product'     => $stockProd,
            //'stock_variation'   => $stockSkus
        );
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
     * Função necessária para validar uma response da microvix.
     * Mesmo para casos de erro, 200 será retornado, apresentando flag para definir se deu certo ou não.
     * @param        SimpleXMLElement $response Resultado da resposta da Microvix.
     * @return       bool Verdadeiro caso tenha sido sucesso, se não, falso.
     */
    public function isSuccessfullRequest($response)
    {
        // Apenas retorna se a o resultado do sucesso foi verdadeiro ou falso.
        return filter_var($response->ResponseResult->ResponseSuccess, FILTER_VALIDATE_BOOL);
    }
}
