<?php

use App\Libraries\FeatureFlag\FeatureManager;

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property Model_prd_addon $model_prd_addon
 * @property Model_products $model_products
 * @property Model_integrations $model_integrations
 */

class AddOn extends API
{
    const POST = 1;
    const PUT = 2;
    const DELETE = 3;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_prd_addon');
        $this->load->model('model_products');
        $this->load->model('model_integrations');

        $this->lang->load('application', 'english');
        $this->lang->load('messages', 'english');
    }

    public function index_post($sku = null)
    {
        if (FeatureManager::isFeatureAvailable('OEP-1957-update-delete-publica-addon-occ')) {
            $this->handleRequest($sku, self::POST);
        } else {
            // Recupera dados enviado pelo body
            $data = file_get_contents('php://input');
            $data = preg_replace('/\s/', ' ', $data);
            $data = json_decode(json_encode($this->cleanGet(json_decode($data, true), false)));

            // Verificação inicial
            $verifyInit = $this->verifyInit();
            if (!$verifyInit[0]) {
                $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                return;
            }

            if (empty($sku)) {
                $this->response(array('success' => false, "message" => $this->lang->line('api_sku_code_not_informed')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
                return;
            }

            if ($this->store_id) {
                $catalog = $this->getDataCatalogByStore($this->store_id);
                if ($catalog) {
                    $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                    return;
                }
            }

            // MUDAR PARA HTTP_BAD_REQUEST
            if (empty($data)) {
                $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
                return;
            }

            try {
                $sku_warning = $this->syncAddOnSku($data, $sku);
                $message = $this->lang->line('api_product_synced');
                if (!empty($sku_warning)) {
                    $message .= implode(' | ', $sku_warning);
                }

                $this->response(array('success' => true, "message" => $message), REST_Controller::HTTP_CREATED, $this->createButtonLogRequestIntegration($data));
            } catch (Exception $exception) {
                $this->response($this->returnError($exception->getMessage()), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            }
        }
    }

    public function index_put($sku = null)
    {
        $this->handleRequest($sku, self::PUT);
    }

    public function index_delete($sku = null)
    {
        $this->handleRequest($sku, self::DELETE);
    }

    private function handleRequest($sku, $method)
    {
        $data = file_get_contents('php://input');
        $data = preg_replace('/\s/', ' ', $data);
        $data = json_decode(json_encode($this->cleanGet(json_decode($data, true), false)));

        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
            return;
        }

        if (empty($sku)) {
            $this->response(array('success' => false, "message" => $this->lang->line('api_sku_code_not_informed')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }

        if ($this->store_id) {
            $catalog = $this->getDataCatalogByStore($this->store_id);
            if ($catalog) {
                $this->response(array('success' => false, "message" => $this->lang->line('api_feature_unavailable_catalog')), REST_Controller::HTTP_UNAUTHORIZED, $this->createButtonLogRequestIntegration($data));
                return;
            }
        }

        if (empty($data)) {
            $this->response(array('success' => false, "message" => $this->lang->line('api_json_invalid_format')), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
            return;
        }

        try {
            switch ($method) {
                case self::POST:
                    $sku_warning = $this->syncAddOnSku($data, $sku);
                    $message = $this->lang->line('api_product_synced');
                    if (!empty($sku_warning)) {
                        $message .= implode(' | ', $sku_warning);
                    }

                    $this->log_data('API/Addon', 'create', $message);
                    $this->response(array('success' => true, "message" => $message), REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
                    break;
                case self::PUT:
                    $sku_warning = $this->updateAddOnSku($data, $sku);
                    $message = $this->lang->line('api_product_synced');
                    if (!empty($sku_warning)) {
                        $message .= implode(' | ', $sku_warning);
                    }

                    $this->response(array('success' => true, "message" => $message), REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
                    break;
                case self::DELETE:
                    $sku_warning = $this->deleteAddOnSku($data, $sku);
                    $message = "Addon deletado com sucesso.";
                    if (!empty($sku_warning)) {
                        $message .= implode(' | ', $sku_warning);
                    }

                    $this->response(array('success' => true, "message" => $message), REST_Controller::HTTP_OK, $this->createButtonLogRequestIntegration($data));
                    break;
            }
        } catch (\Throwable $th) {
            $this->response(array('success' => true, "message" => $th->getMessage()), REST_Controller::HTTP_BAD_REQUEST, $this->createButtonLogRequestIntegration($data));
        }
    }

    /**
     * @param   object $data        Dados da requisição para sincronizar Add-On ao produto.
     * @param   string $sku_product Sku do produto para ser sincronizado.
     * @return  array
     * @throws  Exception
     */
    private function syncAddOnSku(object $data, string $sku_product): array
    {
        if (!isset($data->addon)) {
            throw new Exception($this->lang->line('api_not_addon_key'));
        }
        if (empty($data->addon->skus) || !is_array($data->addon->skus)) {
            throw new Exception($this->lang->line('api_not_sku_addon_key'));
        }

        $data_product = $this->model_products->getProductBySkuAndStore($sku_product, $this->store_id);
        $sku_warning = [];
        $skus_to_create = [];

        foreach ($data->addon->skus as $value) {
            $sku_add_on = $this->model_products->getProductBySkuAndStore($value, $this->store_id);

            if (empty($sku_add_on)) {
                $sku_warning[] = "Sku $value não encontrado.";
                continue;
            }

            $check_sku_exist = $this->model_prd_addon->getAddonDataByPrdIdAddOnAndPrdId($sku_add_on['id'], $data_product['id']);

            if ($check_sku_exist) {
                $sku_warning[] = "Sku $value já sincronizado.";
                continue;
            }

            $prds_to_integration_add_on = $this->model_integrations->getPrdIntegration($sku_add_on['id']);

            if (empty($prds_to_integration_add_on)) {
                $sku_warning[] = "Sku $value ainda não publicado.";
                continue;
            }

            if (empty(array_filter($prds_to_integration_add_on, function ($sku) {
                return !empty($sku['skumkt']);
            }))) {
                $sku_warning[] = "Sku $value ainda não publicado.";
                continue;
            }

            $skus_to_create[] = array(
                "prd_id"        => $data_product["id"],
                "prd_id_addon"  => $sku_add_on["id"],
                "store_id"      => $sku_add_on["store_id"],
                "company_id"    => $sku_add_on["company_id"]
            );
        }

        if (empty($skus_to_create)) {
            $message = "Todos os skus informado já estão sincronizados.";
            if (!empty($sku_warning)) {
                $message .= implode(' | ', $sku_warning);
            }

            throw new Exception($message);
        }

        $this->db->trans_begin();

        foreach ($skus_to_create as $sku_to_create) {
            $this->model_prd_addon->create($sku_to_create);
        }

        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            throw new Exception("Ocorreu um problema para sincronizar os Addon no sku $sku_product!");
        } else {
            $this->db->trans_commit();
        }

        return $sku_warning;
    }

    /**
     * @param   object $data Dados da requisição para atualizar Add-On ao produto.
     * @param   string $sku_product Sku do produto para ser sincronizado.
     * @return  array
     * @throws  Exception
     */
    private function updateAddOnSku(object $data, string $sku_product): array
    {
        if (!isset($data->addon)) {
            throw new Exception($this->lang->line('api_not_addon_key'));
        }

        $data_product = $this->model_products->getProductBySkuAndStore($sku_product, $this->store_id);
        $sku_warning = [];
        $skus_to_update = [];

        foreach ($data->addon->skus as $value) {
            $sku_add_on = $this->model_products->getProductBySkuAndStore($value, $this->store_id);

            if (empty($sku_add_on)) {
                $sku_warning[] = "AddOn $value não encontrado.";
                continue;
            }

            $prds_to_integration_add_on = $this->model_integrations->getPrdIntegration($sku_add_on['id']);

            if (empty($prds_to_integration_add_on)) {
                $sku_warning[] = "AddOn $value ainda não publicado.";
                continue;
            }

            if (empty(array_filter($prds_to_integration_add_on, function ($sku) {
                return !empty($sku['skumkt']);
            }))) {
                $sku_warning[] = "AddOn $value ainda não publicado.";
                continue;
            }

            $skus_to_update[] = array(
                "prd_id"        => $data_product["id"],
                "prd_id_addon"  => $sku_add_on["id"],
                "store_id"      => $sku_add_on["store_id"],
                "company_id"    => $sku_add_on["company_id"]
            );
        }

        if (empty($skus_to_update)) {
            $message = "Todos os AddOns informado já estão sincronizados.";
            if (!empty($sku_warning)) {
                $message .= implode(' | ', $sku_warning);
            }

            throw new Exception($message);
        }

        $this->db->trans_begin();

        $this->model_prd_addon->removeByPrdId($data_product["id"]);
        foreach ($skus_to_update as $sku_to_update) {
            $this->model_prd_addon->create($sku_to_update);
            $this->log_data('API/Addon', 'update', json_encode($sku_to_update));
        }

        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            throw new Exception("Ocorreu um problema para alterar os Addon do sku $sku_product!");
        } else {
            $this->db->trans_commit();
        }

        return $sku_warning;
    }

    /**
     * @param   object $data Dados da requisição para deletar Add-On do produto.
     * @param   string $sku_product Sku do produto para ser sincronizado.
     * @return  array
     * @throws  Exception
     */
    private function deleteAddOnSku(object $data, string $sku_product): array
    {
        if (!isset($data->addon)) {
            throw new Exception($this->lang->line('api_not_addon_key'));
        }

        $data_product = $this->model_products->getProductBySkuAndStore($sku_product, $this->store_id);
        $sku_warning = [];
        $skus_to_delete = [];

        foreach ($data->addon->skus as $value) {
            $sku_add_on = $this->model_products->getProductBySkuAndStore($value, $this->store_id);

            if (empty($sku_add_on)) {
                $sku_warning[] = "Addon $value não encontrado.";
                continue;
            }

            $check_sku_exist = $this->model_prd_addon->getAddonDataByPrdIdAddOnAndPrdId($sku_add_on['id'], $data_product['id']);

            if (empty($check_sku_exist)) {
                $sku_warning[] = "Addon $value não existe para o SKU.";
                continue;
            }

            $prds_to_integration_add_on = $this->model_integrations->getPrdIntegration($sku_add_on['id']);

            if (empty($prds_to_integration_add_on)) {
                $sku_warning[] = "Addon $value ainda não publicado.";
                continue;
            }

            if (empty(array_filter($prds_to_integration_add_on, function ($sku) {
                return !empty($sku['skumkt']);
            }))) {
                $sku_warning[] = "Addon $value ainda não publicado.";
                continue;
            }

            $skus_to_delete[] = $sku_add_on["id"];
        }

        if (empty($skus_to_delete)) {
            $message = "Nenhum dos Addons informados foram encontrados para o SKU.";
            if (!empty($sku_warning)) {
                $message .= implode(' | ', $sku_warning);
            }

            throw new Exception($message);
        }

        $this->db->trans_begin();

        foreach ($skus_to_delete as $sku_to_delete) {
            $this->model_prd_addon->remove($sku_to_delete);
            $this->log_data('API/Addon', 'delete', json_encode($sku_to_delete));
        }

        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            throw new Exception("Ocorreu um problema para deletar os Addon do sku $sku_product!");
        } else {
            $this->db->trans_commit();
        }

        return $sku_warning;
    }
}
