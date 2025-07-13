<?php

use Integration\Integration_v2\anymarket\ApiException;
use Integration\Integration_v2\Order_v2;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_api_integrations $model_api_integrations
 * @property Model_orders $model_orders
 * @property CalculoFrete $calculofrete
 * @property Model_settings $model_settings
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 */
class Quote extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();
        if ($this->data['only_admin'] != 1) {
            redirect('dashboard', 'refresh');
        }

        $this->load->model('model_api_integrations');
        $this->load->model('model_orders');
        $this->load->library('calculoFrete');
        $this->load->model('model_settings');
        $this->load->model('model_products');
        $this->load->model('model_stores');
    }

    public function search(string $type = null, bool $debug = false): CI_Output
    {
        $this->calculofrete->_time_start = microtime(true) * 1000;
        if (!$debug) {
            $this->output->set_content_type('application/json');
        }
        
        if ($type !== 'quote') {
            return $this->output->set_output(json_encode(array('error' => "Tipo informado não encontrado"),JSON_UNESCAPED_UNICODE));
        }
        
        ob_start();
        $param = $this->postClean();

        $zipcode    = onlyNumbers($param['zipcode']);
        $store_id   = (int)$param['store_id'];
        $skus       = $param['sku'] ?? [];
        $quantities = $param['quantity'] ?? [];

        if (empty($zipcode)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um zipcode válido."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($store_id)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um store_id válido."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($skus)) {
            return $this->output->set_output(json_encode(array('error' => "Informe um sku válido."),JSON_UNESCAPED_UNICODE));
        }
        if (empty($quantities)) {
            return $this->output->set_output(json_encode(array('error' => "quantity deve ser maior que zero."),JSON_UNESCAPED_UNICODE));
        }

        $logistic = $this->calculofrete->getLogisticStore(array(
            'store_id' => $store_id
        ));

        $this->calculofrete->instanceLogistic($logistic['type'], $store_id, [], $logistic['seller']);

        // Consultar dados da loja
        // Consultar dados do sku

        $store   = $this->model_stores->getStoresData($store_id);

        $dataQuote = array(
            'zipcodeRecipient' => $zipcode,
            'zipcodeSender' => $store['zipcode'],
            'crossDocking' => 0,
            'items' => array(),
            'dataInternal' => array()
        );

        $equals_sku = [];
        foreach ($skus as $key => $sku) {
            $quantity = $quantities[$key] ?? 0;

            if (empty($quantity)) {
                return $this->output->set_output(json_encode(array('error' => "Informe uma quantidade válida para todas as linhas."),JSON_UNESCAPED_UNICODE));
            }

            if (empty($sku)) {
                return $this->output->set_output(json_encode(array('error' => "Informe um sku válido para todas as linhas."),JSON_UNESCAPED_UNICODE));
            }

            if (in_array($sku, $equals_sku)) {
                return $this->output->set_output(json_encode(array('error' => "Informe o sku somente uma vez em cada linha."),JSON_UNESCAPED_UNICODE));
            }

            $equals_sku[] = $sku;

            $product = $this->model_products->getProductBySkuAndStore($sku, $store_id);
            // Tentar encontrar pela variação.
            if (!$product) {
                $product = $this->model_products->getProductsBySkuVariantAndStore($sku, $store_id);
                if (!$product) {
                    return $this->output->set_output(json_encode(array('error' => "sku não localizado"),JSON_UNESCAPED_UNICODE));
                }

                // Alterar preço. @todo alterar método para pegar o preço na consulta.
                $variant = $this->model_products->getVariantsByProd_idAndVariant($product['id'], $product['variant']);
                $product['price'] = $variant['price'];
                $product['list_price'] = $variant['list_price'];
                $product['qty'] = $variant['qty'];
            }

            $dataQuote['items'][] = array(
                'skuseller'     => $sku,
                'quantidade'    => $quantity,
                'sku'           => $sku,
                'valor'         => $product['price'],
                'comprimento'   => $product['profundidade'] / 100, // Precisa dividir por 100.
                'largura'       => $product['largura'] / 100, // Precisa dividir por 100.
                'altura'        => $product['altura'] / 100, // Precisa dividir por 100.
                'peso'          => $product['peso_bruto'],
                'tipo'          => 999,
                'variant'       => $product['variant'] ?? null
            );
            $dataQuote['dataInternal'][$sku] = array(
                'CNPJ'      => $store['CNPJ'],
                'prd_id'    => $sku,
                'qty_atual' => $product['qty']
            );
        }

        try {
            if ($debug) {
                $this->calculofrete->logistic->setDebug(true);
            }
            $response = $this->calculofrete->logistic->getQuote($dataQuote, true);

            $this->calculofrete->_time_end = microtime(true) * 1000;
            $response_time = array('request_time' => $this->calculofrete->_time_end - $this->calculofrete->_time_start);
            $response = array_merge($response, $response_time);

        } catch (InvalidArgumentException | Exception | Throwable $exception) {
            if (!$debug) {
                ob_clean();
                return $this->output
                    ->set_output(json_encode(array('error' => array('Message' => $exception->getMessage(), 'Code' => $exception->getCode())),JSON_UNESCAPED_UNICODE));
            }

            echo "\n";
            echo "response:\n";
            return $this->output->set_output(json_encode($exception->getMessage(),JSON_UNESCAPED_UNICODE));
        }

        if (!$debug) {
            ob_clean();
            return $this->output
                ->set_output(json_encode($response,JSON_UNESCAPED_UNICODE));
        }

        echo "\n";
        echo "response:\n";
        return $this->output->set_output(json_encode($response,JSON_UNESCAPED_UNICODE));
    }
}
