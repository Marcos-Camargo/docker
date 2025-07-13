<?php

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_DB_driver $db
 *
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_attributes_products_value $model_attributes_products_value
 * @property Model_stores $model_stores
 * @property Model_seller_attributes_marketplace $model_seller_attributes_marketplace
 */
class SetValueToAttributeMarketplaceProduct extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->session->set_userdata(array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        ));
        $usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$userstore = $this->session->userdata('userstore');
		$this->data['userstore'] = $userstore;
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_attributes_products_value');
        $this->load->model('model_stores');
        $this->load->model('model_seller_attributes_marketplace');
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params));

        if ($params === 'null') {
            $params = null;
        }

        $this->checkProducts($params);

        $this->log_data('batch', $log_name,'finish');
        $this->gravaFimJob();
    }

    private function checkProducts(int $store_id = null)
    {
        $stores = $this->model_stores->getStoresData($store_id);

        if ($store_id) {
            $stores = array($stores);
        }

        foreach ($stores as $store) {
            $store_id   = $store['id'];
            $last_id    = 0;

            // Ajusta valores do de-para.
            while (true) {
                $attributes = $this->model_seller_attributes_marketplace->getValuesToChangeInProduct($store_id, $last_id);

                if (!count($attributes)) {
                    echo "Loja $store_id não tem atributos para serem atualizados ao de para.\n";
                    break;
                }

                foreach ($attributes as $attribute) {
                    $product_id                 = $attribute['prd_id'];
                    $attribute_marketplace_id   = $attribute['attribute_marketplace_id'];
                    $int_to                     = $attribute['int_to'];
                    $last_id                    = $attribute['last_id'];
                    $attribute_value            = $attribute['attribute_value_marketplace_id'];

                    // Quando está em branco, significa que não precisa fazer o de-para de valores,
                    // somente pegar o valor recebido pelo seller e enviar para o marketplace.
                    if (empty($attribute['attribute_value_marketplace_id'])) {
                        $attribute_value = $attribute['attribute_value_custom_seller'];
                    }

                    $this->model_atributos_categorias_marketplaces->createProductAttributeMarketplace(array(
                        'id_product' => $product_id,
                        'id_atributo' => $attribute_marketplace_id,
                        'valor' => $attribute_value,
                        'int_to' => $int_to
                    ));
                    echo "STORE_ID=$store_id - PRD_ID=$product_id - ATTR_MKT_ID=$attribute_marketplace_id - ATTR_VALUE=$attribute_value - INT_TO=$int_to\n";
                    // Por enquanto não remover os atributo customizado.
                    //$this->model_attributes_products_value->removeByAttributeProductValue($attribute['attribute_id_custom_seller'], $product_id, $attribute['attribute_value_custom_seller']);
                }
            }

            $last_product_id = 0;

            // Ajusta valores com o mesmo nome.
            while (true) {
                $products = $this->model_attributes_products_value->getProductsToSendMarketplaceInProduct($store_id, $last_product_id);

                // fim da lista
                if (empty($products)) {
                    echo "Loja $store_id não tem atributos para serem atualizados sem de para.\n";
                    break;
                }

                foreach ($products as $product) {
                    $product_id         = $product['prd_id'];
                    $last_product_id    = $product_id;
                    $attributes         = $this->model_attributes_products_value->getValuesAttributeToSendMarketplaceInProduct($store_id, $product_id);

                    foreach ($attributes as $attribute) {
                        $type_attribute                 = $attribute['tipo'];
                        $values_attribute               = json_decode($attribute['valor']);
                        $value_attribute_seller         = $attribute['value'];
                        $value_attribute_seller_to_send = $attribute['value'];
                        $product_id                     = $attribute['prd_id'];
                        $attribute_marketplace_id       = $attribute['id_atributo'];
                        $int_to                         = $attribute['int_to'];

                        // Ler todos os valores para saber se existe um igual.
                        if ($type_attribute !== 'string') {
                            $value_attribute_seller_to_send = null;
                            foreach ($values_attribute as $value_attribute) {
                                if (strtolower(removeAccents($value_attribute_seller, true)) == strtolower(removeAccents($value_attribute->Value, true))) {
                                    $value_attribute_seller_to_send = $value_attribute->FieldValueId;
                                    break;
                                }
                            }
                        }

                        if (empty($value_attribute_seller_to_send)) {
                            echo "Valor ($value_attribute_seller) para o atributo não encontrado na listagem.\n";
                            continue;
                        }

                        $this->model_atributos_categorias_marketplaces->createProductAttributeMarketplace(array(
                            'id_product'    => $product_id,
                            'id_atributo'   => $attribute_marketplace_id,
                            'valor'         => $value_attribute_seller_to_send,
                            'int_to'        => $int_to
                        ));
                        echo "STORE_ID=$store_id - PRD_ID=$product_id - ATTR_MKT_ID=$attribute_marketplace_id - ATTR_VALUE=$value_attribute_seller_to_send - INT_TO=$int_to\n";
                    }
                }
            }
        }
    }
}