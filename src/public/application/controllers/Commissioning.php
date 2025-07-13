<?php /** @noinspection DuplicatedCode */
/** @noinspection JSVoidFunctionReturnValueUsed */
/** @noinspection PhpUndefinedFieldInspection */
/** @noinspection PhpUnused */

defined('BASEPATH') or exit('No direct script access allowed');

use App\Libraries\Enum\ComissioningType;

class Commissioning extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->load->model('model_integrations');
        $this->load->model('model_integrations_settings');
        $this->load->model('model_category');
        $this->load->model('model_stores');
        $this->load->model('model_users');
        $this->load->model('model_brands');
        $this->load->model('model_products');
        $this->load->model('model_vtex_payment_methods');
        $this->load->model('model_vtex_trade_policy');
        $this->load->model('model_settings');
        $this->load->model('model_commissioning_brands');
        $this->load->model('model_commissioning_stores');
        $this->load->model('model_commissioning_categories');
        $this->load->model('model_commissioning_logs');
        $this->load->model('model_commissioning_products');
        $this->load->model('model_commissioning_trade_policies');
        $this->load->model('model_commissionings');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_campaigns_v2_products');
        $this->load->model('model_campaigns_v2_elegible_products');

        $this->load->library('parser');
        $this->load->library('excel');

    }

    public function index()
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_commissioning');

        $this->data['min_value_hierarchy_comission'] = $this->model_settings->getValueIfAtiveByName('min_value_hierarchy_comission');
        if (!$this->data['min_value_hierarchy_comission']) {
            $this->data['min_value_hierarchy_comission'] = 1;
        }
        $this->data['min_value_hierarchy_comission'] = str_replace(',', '.',
            $this->data['min_value_hierarchy_comission']);
        $this->data['max_value_hierarchy_comission'] = $this->model_settings->getValueIfAtiveByName('max_value_hierarchy_comission');
        if (!$this->data['max_value_hierarchy_comission'] || $this->data['max_value_hierarchy_comission'] > 100) {
            $this->data['max_value_hierarchy_comission'] = 100;
        }
        $this->data['max_value_hierarchy_comission'] = str_replace(',', '.',
            $this->data['max_value_hierarchy_comission']);

        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['marketplaces'] = $this->model_integrations->getAllDistinctIntTo(0);

        $this->render_template('comissioning/index', $this->data);

    }

    public function logs($commissioningId)
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_log_history_title');
        $this->data['commissioningId'] = $commissioningId;

        $this->render_template('comissioning/logs', $this->data);

    }

    public function delete($commissioningId)
    {

        $output = [
            'success' => false,
            'message' => '',
        ];

        try {

            if (!in_array('deleteHierarchyComission', $this->permission)
                || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
                throw new Exception('Você não tem permissão para excluir comissionamento.');
            }

            //Buscando o item
            $commissioning = $this->model_commissionings->getById($commissioningId);

            if (!$commissioning) {
                throw new Exception("Comissionamento não encontrado");
            }

            if ($this->isScheduled($commissioning['start_date'])) {
                $this->model_commissionings->delete($commissioningId);
            }

            $output['success'] = true;

        } catch (Exception $exception) {

            $output['message'] = $exception->getMessage();

        }

        header('Content-type: application/json');
        echo json_encode($output);

    }

    private function isScheduled($startDate, $dateBase = null): bool
    {
        if (!$dateBase) {
            $dateBase = dateNow()->format(DATETIME_INTERNATIONAL);
        }
        return $startDate > $dateBase;
    }

    private function isActive($startDate, $endDate, $dateBase = null): bool
    {
        if ($this->isScheduled($startDate, $endDate, $dateBase)) {
            return false;
        }
        if ($this->isExpired($endDate, $dateBase)) {
            return false;
        }
        return true;
    }

    public function new_comission($type, $storeId, $int_to)
    {

        if (!in_array('createHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $store = $this->model_stores->getStoreById($storeId);

        $this->data['store_id'] = $storeId;
        $this->data['int_to'] = $int_to;
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['type'] = $type;
        $this->data['allow_create'] = true;
        if ($type == ComissioningType::BRAND) {
            $this->data['brands'] = $this->model_brands->getBrandDataByStoreIdMarketplace($storeId, $int_to);
            if (!$this->data['brands']) {
                $this->data['allow_create'] = false;
            }
        }
        if ($type == ComissioningType::CATEGORY) {
            $this->data['categories'] = $this->model_category->getCategoriesByStoreIdMarketplace($storeId, $int_to);
            if (!$this->data['categories']) {
                $this->data['allow_create'] = false;
            }
        }
        if ($type == ComissioningType::TRADE_POLICY) {
            $this->data['trade_policies'] = $this->model_vtex_trade_policy->vtexGetTradePoliciesBeingUsed($int_to);
        }

        $this->data['title'] = lang('application_commisioning_new_seller_comission');
        if ($storeId) {
            $this->data['title'] .= ' - '.$store['name'];
        }

        $this->load->view('comissioning/comissioning_new_comission', $this->data);
    }

    public function edit_comission($commissioningId)
    {

        if (!in_array('updateHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $commissioning = $this->model_commissionings->getById($commissioningId);

        if (!$commissioning) {
            $this->session->set_flashdata('error', 'Comissionamento não encontrado');
            redirect('commissioning', 'refresh');
        }

        if (!($this->isScheduled($commissioning['start_date']) || $this->isActive($commissioning['start_date'],
                $commissioning['end_date']))) {
            $this->session->set_flashdata('error', 'Comissionamento não pode estar com encerrado');
            redirect('commissioning', 'refresh');
        }

        $this->data['commissioning'] = $commissioning;
        $this->data['is_scheduled'] = $this->isScheduled($commissioning['start_date']);
        $this->data['commision'] = 0;
        if ($commissioning['type'] == ComissioningType::BRAND) {
            $comissioningItem = $this->model_commissioning_brands->getByComissioningId($commissioning['id']);
            $this->data['current_brand_id'] = $comissioningItem['brand_id'];
            $this->data['commision'] = $comissioningItem['comission'];
            $this->data['brands'] = $this->model_brands->getBrandDataByStoreIdMarketplace($comissioningItem['store_id'],
                $commissioning['int_to']);
        }
        if ($commissioning['type'] == ComissioningType::CATEGORY) {
            $comissioningItem = $this->model_commissioning_categories->getByComissioningId($commissioning['id']);
            $this->data['current_category_id'] = $comissioningItem['category_id'];
            $this->data['commision'] = $comissioningItem['comission'];
            $this->data['categories'] = $this->model_category->getCategoriesByStoreIdMarketplace($comissioningItem['store_id'],
                $commissioning['int_to']);
        }
        if ($commissioning['type'] == ComissioningType::TRADE_POLICY) {
            $comissioningItem = $this->model_commissioning_trade_policies->getByComissioningId($commissioning['id']);
            $this->data['current_trade_policy_id'] = $comissioningItem['vtex_trade_policy_id'];
            $this->data['commision'] = $comissioningItem['comission'];
            $this->data['trade_policies'] = $this->model_vtex_trade_policy->vtexGetTradePoliciesBeingUsed($commissioning['int_to']);
        }
        if ($commissioning['type'] == ComissioningType::SELLER) {
            $comissioningItem = $this->model_commissioning_stores->getByComissioningId($commissioning['id']);
            $this->data['commision'] = $comissioningItem['comission'];
        }

        $this->load->view('comissioning/comissioning_edit_comission', $this->data);
    }

    private function getCurrentItemForComissioning(array $entry)
    {
        if ($entry['type'] == ComissioningType::PRODUCT) {
            return $this->model_commissioning_products->getByComissioningId($entry['id'])[0];
        }
        if ($entry['type'] == ComissioningType::BRAND) {
            return $this->model_commissioning_brands->getByComissioningId($entry['id']);
        }
        if ($entry['type'] == ComissioningType::CATEGORY) {
            return $this->model_commissioning_categories->getByComissioningId($entry['id']);
        }
        if ($entry['type'] == ComissioningType::TRADE_POLICY) {
            return $this->model_commissioning_trade_policies->getByComissioningId($entry['id']);
        }
        if ($entry['type'] == ComissioningType::SELLER) {
            return $this->model_commissioning_stores->getByComissioningId($entry['id']);
        }
        return null;
    }

    public function details($id)
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            $this->data['error'] = 'Você não tem acesso a essa tela!';
            return $this->load->view('comissioning/comissioning_details', $this->data);
        }

        $entry = $this->model_commissionings->getById($id);

        $this->data['entry'] = $entry;
        if (!$entry) {
            $this->data['error'] = 'Comissionamento não encontrado';
            return $this->load->view('comissioning/comissioning_details', $this->data);
        }
        $this->data['entry']['status'] = $this->generateStringStatus($entry['start_date'], $entry['end_date']);

        $creationLog = $this->model_commissioning_logs->getCreationLog($entry['id']);
        if ($creationLog) {
            $this->data['creationLog'] = $creationLog;
            $this->data['creationLog']['user'] = $this->model_users->getUserData($creationLog['user_id']);
        }

        $this->data['item'] = [];

        $comissioningItem = $this->getCurrentItemForComissioning($entry);
        $this->data['item'] = $comissioningItem;
        if ($comissioningItem) {
            $this->data['store'] = $this->model_stores->getStoreById($comissioningItem['store_id']);
        }

        $this->load->view('comissioning/comissioning_details', $this->data);

    }

    private function generateStringStatus($startDate, $endDate, $dateBase = null): string
    {

        if (!$dateBase) {
            $dateBase = dateNow()->format(DATETIME_INTERNATIONAL);
        }

        if ($this->isScheduled($startDate, $dateBase)) {
            return lang('application_commisioning_waiting_start');
        } elseif ($this->isExpired($endDate, $dateBase)) {
            return lang('application_commisioning_ended');
        }

        return lang('application_commisioning_active');
    }

    private function isExpired($endDate, $dateBase = null): bool
    {
        if (!$dateBase) {
            $dateBase = dateNow()->format(DATETIME_INTERNATIONAL);
        }
        return $endDate < $dateBase;
    }

    public function save_comission()
    {

        if (!in_array('createHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(null, true);

        //Variáveis que nem sempre são usadas
        $brandId = null;
        $categoryId = null;
        $trade_policy_id = null;

        $type = $postdata['type'];
        $storeId = $postdata['store_id'];
        $int_to = $postdata['int_to'];
        $comission = $postdata['comission'] ?? '';
        $start_date = $postdata['start_date'];
        $start_date = dateTimeBrazilToDateInternational($start_date);
        $end_date = $postdata['end_date'];
        $end_date = dateTimeBrazilToDateInternational($end_date);

        $min_period_hierarchy_comission = $this->model_settings->getValueIfAtiveByName('min_period_hierarchy_comission');
        if (!$min_period_hierarchy_comission) {
            $min_period_hierarchy_comission = 0;
        }

        $min_value_hierarchy_comission = $this->model_settings->getValueIfAtiveByName('min_value_hierarchy_comission');
        if (!$min_value_hierarchy_comission) {
            $min_value_hierarchy_comission = 0;
        }
        $min_value_hierarchy_comission = str_replace(',', '.', $min_value_hierarchy_comission);
        $max_value_hierarchy_comission = $this->model_settings->getValueIfAtiveByName('max_value_hierarchy_comission');
        if (!$max_value_hierarchy_comission) {
            $max_value_hierarchy_comission = 100;
        }
        $max_value_hierarchy_comission = str_replace(',', '.', $max_value_hierarchy_comission);

        $typeFixed = '';
        $name = '';

        try {

            $this->db->trans_begin();

            if ($type == ComissioningType::PRODUCT) {

                $typeFixed = ComissioningType::PRODUCT;

                $this->saveNewComission($type, $typeFixed, $storeId, $min_value_hierarchy_comission,
                    $max_value_hierarchy_comission, $int_to, $start_date, $end_date, $min_period_hierarchy_comission,
                    $comission, $brandId, $categoryId, $trade_policy_id, $name);

            }elseif ($type == ComissioningType::SELLER) {
                $typeFixed = ComissioningType::SELLER;
                $store = $this->model_stores->getStoreById($storeId);
                $name = $store['name'];

                $this->saveNewComission($type, $typeFixed, $storeId, $min_value_hierarchy_comission,
                    $max_value_hierarchy_comission, $int_to, $start_date, $end_date, $min_period_hierarchy_comission,
                    $comission, $brandId, $categoryId, $trade_policy_id, $name);

            } elseif ($type == ComissioningType::BRAND) {
                $brandId = $postdata['brand_id'];
                $typeFixed = ComissioningType::BRAND;
                $brandIds = explode(',', $brandId);
                if (count($brandIds) == 0) {
                    throw new Exception('Selecione uma Marca');
                }
                $brandsRegistered = 0;
                foreach ($brandIds as $brandId) {

                    if (!$brandId) {
                        continue;
                    }

                    $brand = $this->model_brands->getBrandData($brandId);
                    $name = $brand['name'];

                    $this->saveNewComission($type, $typeFixed, $storeId, $min_value_hierarchy_comission,
                        $max_value_hierarchy_comission, $int_to, $start_date, $end_date,
                        $min_period_hierarchy_comission,
                        $comission, $brandId, $categoryId, $trade_policy_id, $name);

                    $brandsRegistered++;

                }
                if ($brandsRegistered == 0) {
                    throw new Exception('Selecione uma Marca');
                }

            } elseif ($type == ComissioningType::CATEGORY) {

                $categoryId = $postdata['category_id'];
                $typeFixed = ComissioningType::CATEGORY;
                $categoryIds = explode(',', $categoryId);
                if (count($categoryIds) == 0) {
                    throw new Exception('Selecione uma Categoria');
                }
                $categoriesRegistered = 0;
                foreach ($categoryIds as $categoryId) {

                    if (!$categoryId) {
                        continue;
                    }

                    $category = $this->model_category->getCategoryData($categoryId);
                    $name = $category['name'];

                    $this->saveNewComission($type, $typeFixed, $storeId, $min_value_hierarchy_comission,
                        $max_value_hierarchy_comission, $int_to, $start_date, $end_date,
                        $min_period_hierarchy_comission,
                        $comission, $brandId, $categoryId, $trade_policy_id, $name);

                    $categoriesRegistered++;

                }

                if ($categoriesRegistered == 0) {
                    throw new Exception('Selecione uma Categoria');
                }

            } elseif ($type == ComissioningType::TRADE_POLICY) {
                $trade_policy_id = $postdata['trade_policy_id'];
                $typeFixed = ComissioningType::TRADE_POLICY;

                $tradePolicyIds = explode(',', $trade_policy_id);
                if (count($tradePolicyIds) == 0) {
                    throw new Exception('Selecione uma Política comercial');
                }
                $tradePoliciesRegistered = 0;
                foreach ($tradePolicyIds as $trade_policy_id) {

                    if (!$trade_policy_id) {
                        continue;
                    }

                    $tradePolicy = $this->model_vtex_trade_policy->getTradePolicyById($trade_policy_id);
                    $name = $tradePolicy['trade_policy_name'];

                    $this->saveNewComission($type, $typeFixed, $storeId, $min_value_hierarchy_comission,
                        $max_value_hierarchy_comission, $int_to, $start_date, $end_date,
                        $min_period_hierarchy_comission,
                        $comission, $brandId, $categoryId, $trade_policy_id, $name);

                    $tradePoliciesRegistered++;

                }

                if ($tradePoliciesRegistered == 0) {
                    throw new Exception('Selecione uma Política Comercial');
                }

            }

            $this->db->trans_commit();

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => true, 'message' => 'Comissão cadastrada')));

        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'message' => $exception->getMessage())));
        }

    }

    public function save_edit_comission()
    {

        if (!in_array('updateHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(null, true);

        //Variáveis que nem sempre são usadas
        $id = $postdata['id'];
        $comission = $postdata['comission'] ?? null;
        $brand_id = $postdata['brand_id'] ?? null;
        $category_id = $postdata['category_id'] ?? null;
        $trade_policy_id = $postdata['trade_policy_id'] ?? null;
        $start_date = $postdata['start_date'];
        $start_date = dateTimeBrazilToDateInternational($start_date);
        $end_date = $postdata['end_date'];
        $end_date = dateTimeBrazilToDateInternational($end_date);

        $min_period_hierarchy_comission = $this->model_settings->getValueIfAtiveByName('min_period_hierarchy_comission');
        if (!$min_period_hierarchy_comission) {
            $min_period_hierarchy_comission = 0;
        }

        $min_value_hierarchy_comission = $this->model_settings->getValueIfAtiveByName('min_value_hierarchy_comission');
        if (!$min_value_hierarchy_comission) {
            $min_value_hierarchy_comission = 1;
        }
        $min_value_hierarchy_comission = str_replace(',', '.', $min_value_hierarchy_comission);
        $max_value_hierarchy_comission = $this->model_settings->getValueIfAtiveByName('max_value_hierarchy_comission');
        if (!$max_value_hierarchy_comission || $max_value_hierarchy_comission > 100) {
            $max_value_hierarchy_comission = 100;
        }
        $max_value_hierarchy_comission = str_replace(',', '.', $max_value_hierarchy_comission);

        try {

            $this->db->trans_begin();

            $commissioning = $this->model_commissionings->getById($id);

            if (!$commissioning) {
                throw new Exception("Comissionamento não encontrado");
            }

            $isScheduled = $this->isScheduled($commissioning['start_date']);

            if (!($isScheduled || $this->isActive($commissioning['start_date'], $commissioning['end_date']))) {
                throw new Exception('Comissionamento não pode estar com encerrado');
            }

            if ($commissioning['type'] != ComissioningType::PRODUCT && $isScheduled) {

                if ($comission < $min_value_hierarchy_comission || $comission > $max_value_hierarchy_comission) {
                    throw new Exception("O valor da comissão não pode ser inferior a $min_value_hierarchy_comission e superior a $max_value_hierarchy_comission");
                }

            }

            if (!$start_date || !$end_date) {
                throw new Exception("Período de vigência não informado");
            }

            if ($start_date > $end_date) {
                throw new Exception('Data final não pode ser inferior a data inicial');
            }

            $days_diff = dateDiffDays(new DateTime($end_date), new DateTime($start_date));
            if ($days_diff < $min_period_hierarchy_comission) {
                throw new Exception("O período minimo de vigência não pode ser menor que $min_period_hierarchy_comission dias");
            }

            $changes = [];
            $changesItem = [];
            $commisionWasReducted = false;

            $comissioningItem = $this->getCurrentItemForComissioning($commissioning);
            $storeId = $comissioningItem['store_id'];

            if ($isScheduled) {

                if ($commissioning['type'] != ComissioningType::PRODUCT) {

                    if ($comissioningItem['comission'] != $comission) {
                        $changesItem['comission'] = $comission;
                        $commisionWasReducted = $comissioningItem['comission'] > $comission;
                    }
                    if ($commissioning['type'] == ComissioningType::BRAND) {
                        $changesItem['brand_id'] = $brand_id;
                        $brand = $this->model_brands->getBrandData($brand_id);
                        if (!$brand) {
                            throw new Exception("Marca não encontrada");
                        }
                        $changes['name'] = $brand['name'];
                    }
                    if ($commissioning['type'] == ComissioningType::CATEGORY) {
                        $changesItem['category_id'] = $category_id;
                        $category = $this->model_category->getCategoryData($category_id);
                        if (!$category) {
                            throw new Exception("Categoria não encontrada");
                        }
                        $changes['name'] = $category['name'];
                    }
                    if ($commissioning['type'] == ComissioningType::TRADE_POLICY) {
                        $changesItem['vtex_trade_policy_id'] = $trade_policy_id;
                        $tradePolicy = $this->model_vtex_trade_policy->getTradePolicyById($trade_policy_id);
                        if (!$tradePolicy) {
                            throw new Exception("Política comercial não encontrada");
                        }
                        $changes['name'] = $tradePolicy['trade_policy_name'];
                    }

                    if ($this->model_commissionings->storeHasComissioningSameTypeSamePeriod(
                        $comissioningItem['store_id'],
                        $commissioning['int_to'],
                        $commissioning['type'],
                        $start_date, $end_date, $brand_id, $category_id, $trade_policy_id, null, null,
                        $commissioning['id'])) {
                        throw new Exception("Já existe um comissionamento cadastrado para o seller {$comissioningItem['store_id']}, do tipo ".ComissioningType::getDescription($commissioning['type'])." cadastrado no mesmo período de vigência");
                    }

                } elseif (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {

                    $typeFixed = ComissioningType::PRODUCT;

                    $dataCsv = readTempCsv($_FILES['file']['tmp_name']);
                    if (!$dataCsv) {
                        throw new Exception('Nenhuma linha encontrada no arquivo csv');
                    }

                    foreach ($dataCsv as $line => $csv) {
                        if (!isset($csv['ID da Loja'])) {
                            throw new Exception('Coluna "ID da Loja" não fornecida na linha '.$line);
                        }
                        if (!isset($csv['SKU'])) {
                            throw new Exception('Coluna "SKU" não fornecida na linha '.$line);
                        }

                        $product = $this->model_products->getProductBySkuAndStore($csv['SKU'], $csv['ID da Loja']);
                        if (!$product) {
                            throw new Exception("Produto {$csv['SKU']} não encontrado na loja {$csv['ID da Loja']}");
                        }
                        $storeId = $product['store_id'];

                        $paymentMethods = array_slice($csv, 2);
                        foreach ($paymentMethods as $paymentName => $paymentMethodValue) {
                            $paymentNameExplode = explode('|', $paymentName);
                            if (count($paymentNameExplode) != 2) {
                                throw new Exception('Política comercial e método de pagamento não fornecido');
                            }
                            $tradePolicy = trim($paymentNameExplode[0]);
                            $newPayment = trim($paymentNameExplode[1]);

                            $vtex_payment_method_id = extractNumberBeforeSpace($newPayment);
                            if (!$vtex_payment_method_id) {
                                throw new Exception('Código do método de pagamento não fornecido');
                            }
                            $vtex_trade_policy_id = extractNumberBeforeSpace($tradePolicy);
                            if (!$vtex_trade_policy_id) {
                                throw new Exception('Código da política comercial não fornecido');
                            }
                            if (!$this->model_vtex_payment_methods->getPaymentMethodById($vtex_payment_method_id)) {
                                throw new Exception("Forma de pagamento $vtex_payment_method_id não encontrada.");
                            }
                            if (!$this->model_vtex_trade_policy->getTradePolicyById($vtex_trade_policy_id)) {
                                throw new Exception("Política comercial $vtex_trade_policy_id não encontrada.");
                            }
                            if ($paymentMethodValue) {
                                if (!isValidDecimal($paymentMethodValue)) {
                                    throw new Exception("Valor da comissão $paymentMethodValue no formato inválido, por favor, envie no formato de 2 casas decimais, separado por ponto, sem vírgula.");
                                }
                                if ($paymentMethodValue < $min_value_hierarchy_comission) {
                                    throw new Exception("Valor da comissão $paymentMethodValue não pode ser inferior a $min_value_hierarchy_comission");
                                }
                                if ($paymentMethodValue > $max_value_hierarchy_comission) {
                                    throw new Exception("Valor da comissão $paymentMethodValue não pode ser superior a $max_value_hierarchy_comission");
                                }
                            }

                            if ($this->model_commissionings->storeHasComissioningSameTypeSamePeriod(
                                $storeId,
                                $commissioning['int_to'],
                                $typeFixed,
                                $start_date, $end_date, null, null, $vtex_trade_policy_id, $vtex_payment_method_id,
                                $product['id'], $commissioning['id'])) {
                                throw new Exception("Já existe um comissionamento cadastrado para o seller $storeId, sku {$csv['SKU']}, cadastrado no mesmo período de vigência, política comercial $vtex_trade_policy_id, método de pagamento $vtex_payment_method_id");
                            }

                        }

                    }

                    $changesItem['products'] = $dataCsv;

                }

                if ($changesItem) {
                    $changesItem['commissioning_id'] = $commissioning['id'];
                }

                if ($start_date != $commissioning['start_date']) {
                    $changes['start_date'] = $start_date;
                    if ($start_date < dateNow()->format(DATETIME_INTERNATIONAL)) {
                        throw new Exception('Data inicial não pode ser inferior a data de agora');
                    }
                }

            }
            $changes['end_date'] = $end_date;

            $this->model_commissionings->update($changes, $id);

            if ($changesItem) {
                if ($commissioning['type'] == ComissioningType::BRAND) {
                    $this->model_commissioning_brands->update($changesItem, $comissioningItem['id']);
                    if ($commisionWasReducted){
                        $this->removeProductsFromCampaigns($start_date, $end_date, null,
                            null, null, null, $changesItem['brand_id']);
                    }
                }
                if ($commissioning['type'] == ComissioningType::CATEGORY) {
                    $this->model_commissioning_categories->update($changesItem, $comissioningItem['id']);
                    if ($commisionWasReducted){
                        $this->removeProductsFromCampaigns($start_date, $end_date, null,
                            null, null, null, null, $changesItem['category_id']);
                    }
                }
                if ($commissioning['type'] == ComissioningType::TRADE_POLICY) {
                    $this->model_commissioning_trade_policies->update($changesItem, $comissioningItem['id']);
                    if ($commisionWasReducted){
                        $this->removeProductsFromCampaigns($start_date, $end_date, null,
                            $changesItem['vtex_trade_policy_id'], null, $storeId);
                    }
                }
                if ($commissioning['type'] == ComissioningType::SELLER) {
                    $this->model_commissioning_stores->update($changesItem, $comissioningItem['id']);
                    if ($commisionWasReducted){
                        $this->removeProductsFromCampaigns($start_date, $end_date, null,
                            null, null, $storeId);
                    }
                }
                if ($commissioning['type'] == ComissioningType::PRODUCT && isset($changesItem['products']) && $changesItem['products']) {

                    //Buscando todos os produtos que estão atualmente
                    $findCurrentProducts = $this->model_commissioning_products->getByComissioningId($commissioning['id']);
                    $currentProducts = [];
                    if ($findCurrentProducts) {
                        foreach ($findCurrentProducts as $product) {
                            $currentProducts[] = [
                                'commissioning_id' => $product['commissioning_id'],
                                'product_id' => $product['product_id'],
                                'vtex_payment_method_id' => $product['vtex_payment_method_id'],
                                'vtex_trade_policy_id' => $product['vtex_trade_policy_id'],
                                'store_id' => $product['store_id'],
                                'comission' => $product['comission']
                            ];
                        }
                    }

                    $items = [];

                    foreach ($changesItem['products'] as $csv) {
                        $paymentMethods = array_slice($csv, 2);
                        foreach ($paymentMethods as $paymentName => $paymentMethodValue) {
                            if ($paymentMethodValue) {

                                $paymentNameExplode = explode('|', $paymentName);
                                $tradePolicy = trim($paymentNameExplode[0]);
                                $newPayment = trim($paymentNameExplode[1]);

                                $vtex_payment_method_id = extractNumberBeforeSpace($newPayment);
                                $vtex_trade_policy_id = extractNumberBeforeSpace($tradePolicy);

                                $product = $this->model_products->getProductBySkuAndStore($csv['SKU'],
                                    $csv['ID da Loja']);

                                $commisionWasReducted = $product['comission'] > $paymentMethodValue;

                                if ($commisionWasReducted){
                                    $this->removeProductsFromCampaigns(
                                        $start_date,
                                        $end_date,
                                        $product['id'],
                                        $vtex_trade_policy_id,
                                        $vtex_payment_method_id
                                    );
                                }

                                $items[] = [
                                    'commissioning_id' => $id,
                                    'product_id' => $product['id'],
                                    'vtex_payment_method_id' => $vtex_payment_method_id,
                                    'vtex_trade_policy_id' => $vtex_trade_policy_id,
                                    'store_id' => $product['store_id'],
                                    'comission' => $paymentMethodValue
                                ];

                            }

                        }
                    }

                    $currentProductKeys = array_map([$this, 'createUniqueKey'], $currentProducts);
                    $newProductKeys = array_map([$this, 'createUniqueKey'], $items);

                    $productsToRemove = array_diff($currentProductKeys, $newProductKeys);
                    $productsToAdd = array_diff($newProductKeys, $currentProductKeys);

                    $productsToRemove = array_map(function ($key) {
                        list($product_id, $payment_id, $trade_policy_id) = explode('-', $key);
                        return [
                            'product_id' => $product_id,
                            'vtex_payment_method_id' => $payment_id,
                            'vtex_trade_policy_id' => $trade_policy_id,
                        ];
                    }, $productsToRemove);

                    // Atualizar a Tabela de Produtos
                    if ($productsToRemove) {
                        foreach ($productsToRemove as $item) {
                            $this->model_commissioning_products->delete(
                                $id,
                                $item['product_id'],
                                $item['vtex_payment_method_id'],
                                $item['vtex_trade_policy_id']
                            );
                        }
                    }

                    if ($productsToAdd) {
                        foreach ($productsToAdd as $key) {
                            list($product_id, $payment_id, $trade_policy_id) = explode('-', $key);
                            $item = array_filter($items,
                                function ($i) use ($product_id, $payment_id, $trade_policy_id) {
                                    return $i['product_id'] === $product_id &&
                                        $i['vtex_payment_method_id'] === $payment_id &&
                                        $i['vtex_trade_policy_id'] === $trade_policy_id;
                                });
                            $item = array_shift($item);
                            $data = [
                                'commissioning_id' => $id,
                                'product_id' => $product_id,
                                'vtex_payment_method_id' => $payment_id,
                                'vtex_trade_policy_id' => $trade_policy_id,
                                'store_id' => $item['store_id'],
                                'comission' => $item['comission'],
                            ];
                            $this->model_commissioning_products->create($data);
                        }
                    }

                    // Atualizar comissões se necessário
                    foreach ($items as $item) {
                        $key = $this->createUniqueKey($item);
                        if (in_array($key, $currentProductKeys)) {
                            $currentItem = array_filter($currentProducts, function ($i) use ($item) {
                                return $i['product_id'] === $item['product_id'] &&
                                    $i['vtex_payment_method_id'] === $item['vtex_payment_method_id'] &&
                                    $i['vtex_trade_policy_id'] === $item['vtex_trade_policy_id'];
                            });
                            $currentItem = array_shift($currentItem);
                            if ($currentItem['comission'] !== $item['comission']) {
                                $this->model_commissioning_products->update(
                                    $id,
                                    $item['product_id'],
                                    $item['vtex_payment_method_id'],
                                    $item['vtex_trade_policy_id'],
                                    $item['comission']
                                );
                            }
                        }
                    }
                }
            }

            $this->db->trans_commit();

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => true, 'message' => 'Comissão alterada')));

        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'message' => $exception->getMessage())));
        }

    }

    private function createUniqueKey($item)
    {
        return $item['product_id'].'-'.$item['vtex_payment_method_id'].'-'.$item['vtex_trade_policy_id'];
    }

    public function seller_comissions()
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        ob_start();
        $postdata = $this->postClean(null, true);

        $store_id = $postdata['store_id'];
        $int_to = $postdata['marketplace'];

        $data = $this->model_commissioning_stores->getItens($postdata, $store_id, $int_to);

        $recordsTotal = $this->model_commissioning_stores->countGetItens($postdata, $store_id, $int_to);

        $itens = $this->generateReturnDataFromArrayResult($data);

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    private function generateReturnDataFromArrayResult(
        array $data
    ): array {

        $result = [];

        foreach ($data as $key => $value) {

            if ($value['type'] == ComissioningType::PRODUCT) {
                $url = base_url('commissioning/download/'.$value['id']);
                $value['name'] = "<a href='$url'>Baixar Arquivo <i class='fas fa-download'></i></a>";
            }

            $status = $this->generateStringStatus($value['start_date'], $value['end_date']);

            $result[$key] = [
                'id' => $value['id'],
                'int_to' => $value['int_to'],
                'store_name' => $value['store_name'],
                'name' => $value['name'],
                'comission' => $value['comission'].'%',
                'start_date' => datetimeBrazil($value['start_date']),
                'end_date' => datetimeBrazil($value['end_date']),
                'status' => $status,
                'action' => $this->generateActionButtons($value),
            ];

        }

        return $result;

    }

    private function generateActionButtons(array $item): string
    {

        if (hasPermission(['updateHierarchyComission'], $this->permission)) {

            $buttons[] = ' <a href="" class="btn btn-light m-2 btn-w222" onclick="return comissionDetails('.$item['id'].')" data-toggle="modal" data-target="#detailModal"> 
                            Ver Detalhes 
                            </a>';

            $url = base_url('commissioning/logs/'.$item['id']);
            $buttons[] = ' <a href="'.$url.'" class="btn btn-light m-2 btn-w222" target="_blank"> 
                            Logs 
                            </a>';

        }

        if (hasPermission(['deleteHierarchyComission', 'updateHierarchyComission'],
            $this->permission)) {
            if ($this->isScheduled($item['start_date']) || $this->isActive($item['start_date'], $item['end_date'])) {
                $buttons[] = ' <a class="btn btn-light m-2 btn-w222" onclick="return edit('.$item['id'].')" data-toggle="modal" data-target="#editModal"> 
                                Editar 
                                </a>';
            }
            if (!$this->isScheduled($item['start_date']) && $this->isActive($item['start_date'], $item['end_date'])) {
                $buttons[] = " <a class='btn btn-light m-2 btn-w222' onclick='return endCommisioning(\"{$item['id']}\", \"{$item['type']}\")'> 
                                Encerrar 
                                </a>";
            }
            if ($this->isScheduled($item['start_date'])) {
                $buttons[] = " <a class='btn btn-light m-2 btn-w222' onclick='return deleteItem(\"{$item['id']}\", \"{$item['type']}\")'> 
                                Excluir 
                                </a>";
            }
        }

        $button = '
                <div class="btn-group">
                  <button type="button" class="btn btn-wider-1 btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-list-ul"></i> &nbsp;'.lang('application_actions').'
                    </button>
                  <div class="dropdown-menu dropdown-menu-right">'.
            implode('<br>', $buttons)
            .'</div>
                </div>
                    ';

        return $button;

    }

    public function close($id)
    {

        $output = [
            'success' => false,
            'message' => '',
        ];

        try {

            //Buscando o item
            $commissioning = $this->model_commissionings->getById($id);

            if (!$commissioning) {
                throw new Exception("Comissionamento não encontrado");
            }

            $changes = [
                'end_date' => dateNow()->format(DATETIME_INTERNATIONAL)
            ];
            $this->model_commissionings->update($changes, $id);

            $output['success'] = true;

        } catch (Exception $exception) {

            $output['message'] = $exception->getMessage();

        }

        header('Content-type: application/json');
        echo json_encode($output);

    }

    public function logs_data($commisioningId)
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        ob_start();

        $postdata = $this->postClean(null, true);

        $data = $this->model_commissioning_logs->getLogs($commisioningId, $postdata);

        $recordsTotal = $this->model_commissioning_logs->countGetItens($commisioningId, $postdata);

        $commisioning = $this->model_commissionings->getById($commisioningId);

        $itens = $this->generateReturnDataFromArrayResultLogs($commisioning, $data);

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    private function generateReturnDataFromArrayResultLogs(
        array $commisioning,
        array $data
    ): array {

        $status = $this->generateStringStatus($commisioning['start_date'], $commisioning['end_date']);

        $result = [];

        foreach ($data as $key => $value) {

            $user = $this->model_users->getUserData($value['user_id']);

            $action = "Criação";
            if ($value['method'] == 'update') {
                $action = "Alteração";
            } elseif ($value['method'] == 'delete') {
                $action = "Remoção";
            }

            $dataDecoded = json_decode($value['data'], true);

            $changes = [];
            if ($value['model'] == 'commissionings') {
                if ($value['method'] == 'create') {
                    $changes[] = 'Data Inicial: '.datetimeBrazil($dataDecoded['start_date']).'<br>Data Final: '.datetimeBrazil($dataDecoded['end_date']);
                }
                if ($value['method'] == 'update') {
                    if (isset($dataDecoded['start_date'])) {
                        $changes[] = 'Data Inicial: '.datetimeBrazil($dataDecoded['start_date']);
                    }
                    if (isset($dataDecoded['end_date'])) {
                        $changes[] = 'Data Final: '.datetimeBrazil($dataDecoded['end_date']);
                    }
                }
                if ($commisioning['type'] == ComissioningType::PRODUCT) {
                    $commisioning['name'] = '';
                }
            } else {

                if (isset($dataDecoded['store_id'])
                    || isset($dataDecoded['brand_id'])
                    || isset($dataDecoded['category_id'])
                    || isset($dataDecoded['vtex_trade_policy_id'])) {

                    $change = '';
                    if ($value['method'] == 'create') {
                        $change .= 'Inclusão de ';
                    } else {
                        $change .= 'Alteração de ';
                    }
                    $change .= ComissioningType::getDescription($commisioning['type']);

                    if ($commisioning['type'] == ComissioningType::SELLER) {
                        $change .= ': '.$dataDecoded['store_id'];
                    }
                    if ($commisioning['type'] == ComissioningType::BRAND) {
                        $change .= ': '.$dataDecoded['brand_id'];
                    }
                    if ($commisioning['type'] == ComissioningType::CATEGORY) {
                        $change .= ': '.$dataDecoded['category_id'];
                    }
                    if ($commisioning['type'] == ComissioningType::TRADE_POLICY) {
                        $change .= ': '.$dataDecoded['vtex_trade_policy_id'];
                    }

                }

                if ($commisioning['type'] == ComissioningType::PRODUCT) {

                    $change .= ': '.$dataDecoded['product_id'];

                    if (isset($dataDecoded['vtex_payment_method_id'])) {
                        $changes[] = 'Método de Pagamento: '.$dataDecoded['vtex_payment_method_id'];
                    }

                    $product = $this->model_products->getProductData(0, $dataDecoded['product_id']);
                    $paymentMethod = $this->model_vtex_payment_methods->getPaymentMethodById($dataDecoded['vtex_payment_method_id']);

                    $commisioning['name'] = $product['name'].'<br>'.$paymentMethod['method_name'];

                }

                if ($change) {
                    $changes[] = $change;
                }

                if (isset($dataDecoded['comission'])) {
                    $changes[] = 'Comissão: '.$dataDecoded['comission'].'%';
                }

            }

            $statusUpdatedAt = $this->generateStringStatus($commisioning['start_date'], $commisioning['end_date'],
                $value['created_at']);

            $result[$key] = [
                'id' => $value['id'],
                'type' => ComissioningType::getDescription($commisioning['type']),
                'name' => $commisioning['name'],
                'period' => 'Data Inicial: '.datetimeBrazil($commisioning['start_date']).'<br>Data Final: '.datetimeBrazil($commisioning['end_date']),
                'current_status' => $status,
                'changes' => implode('<br>', $changes),
                'updated_at' => datetimeBrazil($value['created_at']),
                'user' => $user ? $user['email'] : '',
                'action' => $action,
                'status_updated_at' => $statusUpdatedAt
            ];

        }

        return $result;

    }

    public function brand_comissions()
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        ob_start();
        $postdata = $this->postClean(null, true);

        $store_id = $postdata['store_id'];
        $int_to = $postdata['marketplace'];

        $data = $this->model_commissioning_brands->getItens($postdata, $store_id, $int_to);

        $recordsTotal = $this->model_commissioning_brands->countGetItens($postdata, $store_id, $int_to);

        $itens = $this->generateReturnDataFromArrayResult($data);

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    public function category_comissions()
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        ob_start();
        $postdata = $this->postClean(null, true);

        $store_id = $postdata['store_id'];
        $int_to = $postdata['marketplace'];

        $data = $this->model_commissioning_categories->getItens($postdata, $store_id, $int_to);

        $recordsTotal = $this->model_commissioning_categories->countGetItens($postdata, $store_id, $int_to);

        $itens = $this->generateReturnDataFromArrayResult($data);

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    public function payment_method_comissions()
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        ob_start();
        $postdata = $this->postClean(null, true);

        $store_id = $postdata['store_id'];
        $int_to = $postdata['marketplace'];

        $data = $this->model_commissioning_products->getItens($postdata, $store_id, $int_to);

        $recordsTotal = $this->model_commissioning_products->countGetItens($postdata, $store_id, $int_to, null, true);

        $itens = $this->generateReturnDataFromArrayResult($data);

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    public function download($commisioningId)
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $data = $this->model_commissioning_products->getItensById($commisioningId);

        //Agrupando pelos métodos de pagamento disponíveis
        $groupedItems = [];
        foreach ($data as $item) {
            $groupedItems[$item['vtex_trade_policy_id'].'-'.$item['vtex_payment_method_id']] = [
                'payment_method_id' => $item['vtex_payment_method_id'],
                'payment_method_name' => $item['method_name'],
                'trade_policy_id' => $item['vtex_trade_policy_id'],
                'trade_policy_name' => $item['trade_policy_name'],
            ];
        }

        $skus = [];
        foreach ($data as $item) {
            $sku = $item['sku'];
            if (!isset($skus[$sku])) {
                $skus[$sku] = ['store_id' => $item['store_id']];
                $skus[$sku]['items'] = [];
            }
            foreach ($groupedItems as $nameGrouped => $groupedItem) {
                if ($groupedItem['payment_method_id'] == $item['vtex_payment_method_id']
                    && $groupedItem['trade_policy_id'] == $item['vtex_trade_policy_id']) {
                    $skus[$sku]['items'][$nameGrouped] = $item['comission'];
                } elseif (!isset($skus[$sku]['items'][$nameGrouped])) {
                    $skus[$sku]['items'][$nameGrouped] = '';
                }
            }
        }

        $columns = [lang('application_store_id'), 'SKU'];
        foreach ($groupedItems as $groupedItem) {
            $columns[] = $groupedItem['trade_policy_id'].' - '.$groupedItem['trade_policy_name'].' | '.$groupedItem['payment_method_id'].' - '.$groupedItem['payment_method_name'];
        }

        $data = [];
        $data[] = $columns;

        foreach ($skus as $code => $sku) {

            $item = [];
            $item[] = $sku['store_id'];
            $item[] = (string) $code;
            foreach ($sku['items'] as $skuItem) {
                $item[] = $skuItem;
            }

            $data[] = $item;

        }

        // Nome do arquivo CSV
        $filename = "commission_".date("Y-m-d-H-i-s").".csv";

        // Define os headers para o download do arquivo
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="'.$filename.'"');

        // Abre o "output stream" para escrita
        $output = fopen('php://output', 'w');

        // Escreve cada linha do array de dados no arquivo CSV
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        // Fecha o output stream
        fclose($output);

    }

    public function download_example($int_to)
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        $tradePolicies = $this->model_vtex_trade_policy->vtexGetTradePoliciesBeingUsed($int_to);
        $paymentMethods = $this->model_vtex_payment_methods->vtexGetPaymentMethods($int_to);

        $columns = [lang('application_store_id'), 'SKU'];
        foreach ($tradePolicies as $tradePolicy) {
            foreach ($paymentMethods as $paymentMethod) {
                $columns[] = "{$tradePolicy['id']} - {$tradePolicy['trade_policy_name']} | {$paymentMethod['id']} - {$paymentMethod['method_name']}";
            }
        }

        $data = [];
        $data[] = $columns;

        $item = [];
        $item[] = 1;
        $item[] = 'sku_exemplo_123';
        foreach ($tradePolicies as $tradePolicy) {
            foreach ($paymentMethods as $paymentMethod) {
                $item[] = '10.50';
            }
        }
        $item[] = 'Permitido numeros inteiros e com 2 casas decimais, use ponto para separar casa decimal';

        $data[] = $item;

        // Nome do arquivo CSV
        $filename = "example_".date("Y-m-d-H-i-s").".csv";

        // Define os headers para o download do arquivo
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="'.$filename.'"');

        // Abre o "output stream" para escrita
        $output = fopen('php://output', 'w');

        // Escreve cada linha do array de dados no arquivo CSV
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        // Fecha o output stream
        fclose($output);

    }

    public function trade_policies_comissions()
    {

        if (!in_array('viewHierarchyComission', $this->permission)
            || !$this->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')) {
            redirect('dashboard', 'refresh');
        }

        ob_start();
        $postdata = $this->postClean(null, true);

        $store_id = $postdata['store_id'];
        $int_to = $postdata['marketplace'];

        $data = $this->model_commissioning_trade_policies->getItens($postdata, $store_id, $int_to);

        $recordsTotal = $this->model_commissioning_trade_policies->countGetItens($postdata, $store_id, $int_to);

        $itens = $this->generateReturnDataFromArrayResult($data);

        $output = array(
            "draw" => $postdata['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data" => $itens,
        );

        ob_clean();
        header('Content-type: application/json');
        echo json_encode($output);

    }

    /**
     * @param  string  $startDate
     * @param  string  $endDate
     * @param  int|null  $productId
     * @param  string|null  $vtexTradePolicyId
     * @param  string|null  $vtexPaymentMethodId
     * @param  string|null  $storeId
     * @param  string|null  $brandId
     * @param  string|null  $categoryId
     * @return void
     */
    private function removeProductsFromCampaigns(
        string $startDate,
        string $endDate,
        ?int $productId = null,
        ?string $vtexTradePolicyId = null,
        ?string $vtexPaymentMethodId = null,
        ?string $storeId = null,
        ?string $brandId = null,
        ?string $categoryId = null
    ): void {

        //Sempre verificar se está em campanhas, para se tiver retirar
        $productsInCampaign = $this->model_campaigns_v2_products->getProductParticipatingAnotherCampaign(
            $startDate,
            $endDate,
            $productId,
            $vtexTradePolicyId,
            $vtexPaymentMethodId,
            $storeId,
            $brandId,
            $categoryId
        );

        //Removendo os produtos da campanha
        if ($productsInCampaign) {

            $campaignsVtexUpdated = [];
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                $campaignsOccUpdated = [];
            }
            foreach ($productsInCampaign as $product) {

                //Atualizar somente uma vez a mesma campanha
                if (($product['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $product['occ_campaign_update'] > 0)) && !in_array($product['campaign_v2_id'],
                        $campaignsVtexUpdated)) {
                    if ($product['vtex_campaign_update'] > 0){
                        $campaignsVtexUpdated[] = $product['campaign_v2_id'];
                    }
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                        if ($product['occ_campaign_update'] > 0){
                            $campaignsOccUpdated[] = $product['campaign_v2_id'];
                        }
                    }
                }

            }

            //Atualizando somente no final todas as campanhas vtex
            if ($campaignsVtexUpdated) {
                foreach ($campaignsVtexUpdated as $idCampaign) {
                    $this->model_campaigns_v2->update(['vtex_campaign_update' => 1], $idCampaign);
                }
            }
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaignsOccUpdated) {
                foreach ($campaignsOccUpdated as $idCampaign) {
                    $this->model_campaigns_v2->update(['occ_campaign_update' => 1], $idCampaign);
                }
            }


        }

    }

    /**
     * @param $type
     * @param  string  $typeFixed
     * @param $storeId
     * @param $min_value_hierarchy_comission
     * @param $max_value_hierarchy_comission
     * @param $int_to
     * @param  string  $start_date
     * @param  string  $end_date
     * @param  int  $min_period_hierarchy_comission
     * @param $comission
     * @param $brandId
     * @param $categoryId
     * @param $trade_policy_id
     * @param $name
     * @return CI_Output
     */
    public function saveNewComission(
        $type,
        string $typeFixed,
        $storeId,
        $min_value_hierarchy_comission,
        $max_value_hierarchy_comission,
        $int_to,
        string $start_date,
        string $end_date,
        int $min_period_hierarchy_comission,
        $comission,
        $brandId,
        $categoryId,
        $trade_policy_id,
        $name
    ): void {

        if ($type == ComissioningType::PRODUCT) {
            $typeFixed = ComissioningType::PRODUCT;

            if (!isset($_FILES['file']['tmp_name']) || !$_FILES['file']['tmp_name']){
                throw new Exception('Arquivo não enviado');
            }

            $dataCsv = readTempCsv($_FILES['file']['tmp_name']);
            if (!$dataCsv) {
                throw new Exception('Nenhuma linha encontrada no arquivo csv');
            }

            //Caso vier apenas uma coluna, o software usado pode ter retirado do padrão, então iremos verificar se está no separador de vírgula
            if (count($dataCsv[0]) === 1) {
                $dataCsv2 = readTempCsv($_FILES['file']['tmp_name'], 0, [], ',');
                if (count($dataCsv2[0]) > 1) {
                    $dataCsv = $dataCsv2;
                }
            }

            foreach ($dataCsv as $line => $csv) {
                if (!isset($csv['ID da Loja'])) {
                    throw new Exception('Coluna "ID da Loja" não fornecida na linha '.$line);
                }
                if (!isset($csv['SKU'])) {
                    throw new Exception('Coluna "SKU" não fornecida na linha '.$line);
                }

                $product = $this->model_products->getProductBySkuAndStore($csv['SKU'], $csv['ID da Loja']);
                if (!$product) {
                    throw new Exception("Produto {$csv['SKU']} não encontrado na loja {$csv['ID da Loja']}");
                }
                $storeId = $product['store_id'];

                $paymentMethods = array_slice($csv, 2);
                foreach ($paymentMethods as $paymentName => $paymentMethodValue) {
                    if (!$paymentName) {
                        continue;
                    }
                    $paymentNameExplode = explode('|', $paymentName);
                    if (count($paymentNameExplode) != 2) {
                        throw new Exception('Política comercial e método de pagamento não fornecido');
                    }
                    $tradePolicy = trim($paymentNameExplode[0]);
                    $newPayment = trim($paymentNameExplode[1]);

                    $vtex_payment_method_id = extractNumberBeforeSpace($newPayment);
                    if (!$vtex_payment_method_id) {
                        throw new Exception('Código do método de pagamento não fornecido');
                    }
                    $vtex_trade_policy_id = extractNumberBeforeSpace($tradePolicy);
                    if (!$vtex_trade_policy_id) {
                        throw new Exception('Código da política comercial não fornecido');
                    }
                    if (!$this->model_vtex_payment_methods->getPaymentMethodById($vtex_payment_method_id)) {
                        throw new Exception("Forma de pagamento $vtex_payment_method_id não encontrada.");
                    }
                    if (!$this->model_vtex_trade_policy->getTradePolicyById($vtex_trade_policy_id)) {
                        throw new Exception("Política comercial $vtex_trade_policy_id não encontrada.");
                    }
                    if ($paymentMethodValue) {
                        if (!isValidDecimal($paymentMethodValue)) {
                            throw new Exception("Valor da comissão $paymentMethodValue no formato inválido, por favor, envie no formato de 2 casas decimais, separado por ponto, sem vírgula.");
                        }
                        if ($paymentMethodValue < $min_value_hierarchy_comission) {
                            throw new Exception("Valor da comissão $paymentMethodValue não pode ser inferior a $min_value_hierarchy_comission");
                        }
                        if ($paymentMethodValue > $max_value_hierarchy_comission) {
                            throw new Exception("Valor da comissão $paymentMethodValue não pode ser superior a $max_value_hierarchy_comission");
                        }
                    }

                    if ($this->model_commissionings->storeHasComissioningSameTypeSamePeriod(
                        $storeId,
                        $int_to,
                        $typeFixed,
                        $start_date,
                        $end_date,
                        null,
                        null,
                        $vtex_trade_policy_id,
                        $vtex_payment_method_id,
                        $product['id'])) {
                        throw new Exception("Já existe um comissionamento cadastrado para o seller $storeId, sku {$csv['SKU']}, cadastrado no mesmo período de vigência, política comercial $vtex_trade_policy_id, método de pagamento $vtex_payment_method_id");
                    }

                }
            }

        }

        if (!$typeFixed) {
            throw new Exception('Tipo de comissionamento não encontrado');
        }

        if (!$start_date || !$end_date) {
            throw new Exception("Período de vigência não informado");
        }

        if ($start_date < dateNow()->format(DATETIME_INTERNATIONAL)) {
            throw new Exception('Data inicial não pode ser inferior a data de agora');
        }

        if ($start_date > $end_date) {
            throw new Exception('Data final não pode ser inferior a data inicial');
        }

        $days_diff = dateDiffDays(new DateTime($end_date), new DateTime($start_date));
        if ($days_diff < $min_period_hierarchy_comission) {
            throw new Exception("O período minimo de vigência não pode ser menor que $min_period_hierarchy_comission dias");
        }

        if ($typeFixed != ComissioningType::PRODUCT) {
            if ($comission < $min_value_hierarchy_comission || $comission > $max_value_hierarchy_comission) {
                throw new Exception("O valor da comissão não pode ser inferior a $min_value_hierarchy_comission e superior a $max_value_hierarchy_comission");
            }
            if ($this->model_commissionings->storeHasComissioningSameTypeSamePeriod(
                $storeId,
                $int_to,
                $typeFixed,
                $start_date,
                $end_date,
                $brandId,
                $categoryId,
                $trade_policy_id)) {
                throw new Exception("Já existe um comissionamento cadastrado para o seller $storeId, do tipo ".ComissioningType::getDescription($typeFixed)." cadastrado no mesmo período de vigência");
            }
        }

        if ($typeFixed == ComissioningType::PRODUCT && (!isset($_FILES['file']['tmp_name']) || !$_FILES['file'])) {
            throw new Exception('Arquivo de produtos não selecionado.');
        }

        $data = [
            'name' => $name,
            'type' => $typeFixed,
            'int_to' => $int_to,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $id = $this->model_commissionings->create($data);

        if ($type == ComissioningType::SELLER) {

            $this->removeProductsFromCampaigns($start_date, $end_date, null, null, null, $storeId);

            $data = [
                'commissioning_id' => $id,
                'store_id' => $storeId,
                'comission' => $comission
            ];
            $this->model_commissioning_stores->create($data);
        }
        if ($type == ComissioningType::BRAND) {
            $this->removeProductsFromCampaigns($start_date, $end_date, null, null, null, $storeId, $brandId);
            $data = [
                'commissioning_id' => $id,
                'store_id' => $storeId,
                'brand_id' => $brandId,
                'comission' => $comission
            ];
            $this->model_commissioning_brands->create($data);
        }
        if ($type == ComissioningType::CATEGORY) {
            $this->removeProductsFromCampaigns($start_date, $end_date, null, null, null, $storeId, null,
                $categoryId);
            $data = [
                'commissioning_id' => $id,
                'store_id' => $storeId,
                'category_id' => $categoryId,
                'comission' => $comission
            ];
            $this->model_commissioning_categories->create($data);
        }
        if ($type == ComissioningType::TRADE_POLICY) {
            $this->removeProductsFromCampaigns($start_date, $end_date, null, $trade_policy_id, null, $storeId);
            $data = [
                'commissioning_id' => $id,
                'store_id' => $storeId,
                'vtex_trade_policy_id' => $trade_policy_id,
                'comission' => $comission
            ];
            $this->model_commissioning_trade_policies->create($data);
        }

        if ($typeFixed == ComissioningType::PRODUCT) {

            foreach ($dataCsv as $csv) {
                $paymentMethods = array_slice($csv, 2);
                foreach ($paymentMethods as $paymentName => $paymentMethodValue) {
                    if (!$paymentName) {
                        continue;
                    }
                    if ($paymentMethodValue) {

                        $paymentNameExplode = explode('|', $paymentName);
                        $tradePolicy = trim($paymentNameExplode[0]);
                        $newPayment = trim($paymentNameExplode[1]);

                        $vtex_payment_method_id = extractNumberBeforeSpace($newPayment);
                        $vtex_trade_policy_id = extractNumberBeforeSpace($tradePolicy);

                        $product = $this->model_products->getProductBySkuAndStore($csv['SKU'], $csv['ID da Loja']);

                        $this->removeProductsFromCampaigns($start_date, $end_date, $product['id'],
                            $vtex_trade_policy_id, $vtex_payment_method_id, $product['store_id']);

                        $data = [
                            'commissioning_id' => $id,
                            'product_id' => $product['id'],
                            'vtex_payment_method_id' => $vtex_payment_method_id,
                            'vtex_trade_policy_id' => $vtex_trade_policy_id,
                            'store_id' => $product['store_id'],
                            'comission' => $paymentMethodValue
                        ];

                        $this->model_commissioning_products->create($data);

                    }

                }
            }

        }

    }

}
