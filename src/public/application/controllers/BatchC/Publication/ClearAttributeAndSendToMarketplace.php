<?php

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 * @property CI_DB_driver $db
 *
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 */
class ClearAttributeAndSendToMarketplace extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->session->set_userdata(array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        ));
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_queue_products_marketplace');
    }

    public function run(string $id, string $attribute, string $int_to, string $target_value)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $attribute));

        if ($attribute !== 'null' && $int_to !== 'null' && $target_value !== 'null') {
            $this->updateAttributeProduct($attribute, $int_to, $target_value);
        } else {
            echo "Informe os campos (attribute, int_to e target_value) para executar.\n";
        }

        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    private function updateAttributeProduct(string $attribute, string $int_to, string $target_value)
    {
        $attribute      = str_replace('-', ' ', $attribute);
        $target_value   = str_replace('-', ' ', $target_value);



        $limit = 200;
        $last_prd_id = 0;
        $check_registers_queue = 400;
        $time_sleep_queue_full = 30;

        $data_attribute = $this->model_atributos_categorias_marketplaces->getAttributeIdByNameAndIntTo($attribute, $int_to);

        if (empty($data_attribute)) {
            echo "Atributo $attribute não encontrado\n";
            return;
        }

        if ($data_attribute['tipo'] !== 'list') {
            echo "Atributo $attribute não é uma lista\n";
            return;
        }

        $values = json_decode($data_attribute['valor'] ?? '');

        $value = array_filter($values, function($value) use ($target_value) {
            return $value->Value == $target_value;
        });

        if (empty($value)) {
            echo "Valor $target_value não encontrado para o atributo $attribute.\n";
            return;
        }

        if (count($value) !== 1) {
            echo "Valor $target_value encontrado mais que uma vez para o atributo $attribute. (" . json_encode($value, JSON_UNESCAPED_UNICODE) . ")\n";
            return;
        }

        $value = $value[0];
        $value_id = $value->FieldValueId;

        while (true) {
            $values_attribute = $this->model_atributos_categorias_marketplaces->getProductsByAttributeAndIntToAndValue($data_attribute['id_atributo'], $int_to, $value_id, $limit, $last_prd_id);

            // Não encontrou mais produtos.
            if (empty($values_attribute)) {
                break;
            }

            echo "Adicionando ".count($values_attribute)." produtos, iniciando no prd_id $last_prd_id.\n";

            // Lendo todos os produtos com erros.
            $create = array();
            foreach ($values_attribute as $value_attribute) {
                // Id do ultimo produto, para a próxima consulta.
                $last_prd_id = $value_attribute['id_product'];

                // Adicionar todos os produtos no vetor para adicionar na fila.
                $create[] = array(
                    'status' => 0,
                    'prd_id' => $value_attribute['id_product'],
                    'int_to' => $value_attribute['int_to'],
                );

                // remove o valor do produto.
                $this->model_atributos_categorias_marketplaces->removeAttributeValueByAttributeAndIntToAndValueAndProduct($value_attribute['id_atributo'], $value_attribute['int_to'], $value_attribute['valor'], $value_attribute['id_product']);

                echo "[id_atributo=$value_attribute[id_atributo]] - [int_to=$value_attribute[int_to]] - [valor=$value_attribute[valor]] - [id_product=$value_attribute[id_product]]\n";
            }

            // Validar se a fila está cheia.
            while (true) {
                $queue = $this->model_queue_products_marketplace->countQueue();
                if (!$queue || ($queue['qtd'] ?? 0) <= $check_registers_queue) {
                    break;
                }
                echo "A fila contem $queue[qtd] produtos, esperar diminuir para no mínimo $check_registers_queue registros. Esperar $time_sleep_queue_full segundos para tentar novamente.\n";
                // Se tem mais de 1000 produtos na fila, esperar 30 segundos para validar novamente antes de adicionar na fila.
                sleep($time_sleep_queue_full);
            }

            // Adiciona os produtos na fila.
            if (!empty($create)) {
                $this->model_queue_products_marketplace->create($create, true);
            }
        }
    }
}