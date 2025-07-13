<?php
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\VarDumper\Cloner\Data;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_seller_migration_register $model_seller_migration_register
 * @property Model_products_seller_migration $model_products_seller_migration
 * @property Model_integrations $model_integrations
 * @property Model_integrations_settings $model_integrations_settings
 * @property Model_products $model_products
 * @property Model_company $model_company
 * @property Model_stores $model_stores
 * @property Model_users $model_users
 * @property Model_calendar $model_calendar
 * @property UploadProducts $uploadproducts
 */
 class MigrationSeller extends Admin_Controller
{
    const MIGRATION_SELLER_ROUTE = 'MigrationSeller/index';
    const VIEW_MIGRATION_SELLER_ROUTE = 'MigrationSeller/viewmigration';
    const VIEW_END_MIGRATION_SELLER_ROUTE = 'MigrationSeller/endmigration';

    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();
        $this->load->model('model_company');
        $this->load->model('model_integrations');
        $this->load->model('model_integrations_settings');
        $this->load->model('model_stores');
        $this->load->model('model_users');
        $this->load->model('model_calendar');
        $this->load->model('model_products_seller_migration');
        $this->load->model('model_seller_migration_register');
        $this->load->model('model_products');
        $this->load->library('UploadProducts');
        $userstore = $this->session->userdata('userstore');
        $this->data['userstore'] = $userstore;
    }

    public function index()
    {
        if (!in_array('initStoreMigration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['empresas'] = $this->model_company->getCompanyData(); //getStoresDataView
        $this->data['lojas'] = $this->model_stores->getStoresDataView();

        $this->data['page_title'] = $this->lang->line('application_manage_migration_seller');
        $this->data['integrations'] = json_encode($this->fetchIntegrations());
        $this->data['pageinfo'] = $this->lang->line('application_manage') . " - " . $this->lang->line('application_manage_migration_seller');
        $this->render_template('migrationseller/index', $this->data);
    }
    public function new()
    {
        if (!in_array('initStoreMigration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['empresas'] = $this->model_company->getCompanyData(); //getStoresDataView
        $this->data['lojas'] = $this->model_stores->getStoresDataView();

        $this->data['page_title'] = $this->lang->line('application_migration_seller');
        $this->data['integrations'] = $this->fetchIntegrations();
        $this->data['pageinfo'] = $this->lang->line('application_manage') . " - " . $this->lang->line('application_migration_seller');
        $this->render_template('migrationseller/new', $this->data);
    }
    public function viewmigrations()
    {
        $this->data['stores_filter'] = $this->model_stores->getActiveStoreToSellerMigrate();
        $this->data['page_title'] = $this->lang->line('application_migration_seller');
        $this->data['qtd_produtc_to_migration'] = $this->model_products_seller_migration->getProductsDataCount();
        $this->data['qtd_produtc_seller_migration'] = $this->model_products_seller_migration->getProductsDataCount();
        $this->data['qtd_produtc_unmigrated'] = $this->model_products_seller_migration->getUnmigratedProducts();
        $this->data['qtd_produtc_migrated'] = $this->data['qtd_produtc_to_migration'] - $this->data['qtd_produtc_unmigrated'];

        $this->render_template('migrationseller/viewmigraiton', $this->data);
    }

    public function endMigration()
    {
        $this->data['empresas'] = $this->model_company->getCompanyData(); //getStoresDataView
        $this->data['lojas'] = $this->model_stores->getStoresDataView();
        $migration = $this->model_products_seller_migration->getMigration($this->data['userstore']);
        if (!$migration) {
            $migration = 0;
        }
        $this->data['migration'] = $migration;
        $this->data['page_title'] = $this->lang->line('application_migration_seller');
        $this->data['integrations'] = $this->fetchIntegrations();
        $this->data['page_title'] = $this->lang->line('application_migration_seller_end');
        $this->render_template('migrationseller/endmigration', $this->data);
    }

    public function fetchMigrationData()
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $filter_default[]['where']['s.active'] = 1;
            $filter_default[]['where']['s.flag_store_migration'] = 1;

            $fields_order = array(
                's.id',
                's.name',
                '(SELECT Count(*) FROM products_seller_migration WHERE store_id = s.id)',
                '(SELECT Count(*) FROM products_seller_migration WHERE store_id = s.id AND internal_id IS NULL AND date_disapproved IS NOT NULL)',
                '(SELECT Count(*) FROM products_seller_migration WHERE store_id = s.id AND internal_id IS NOT NULL AND date_approved IS NOT NULL)',
                'sm.status',
                '');

            $query = array();
            $query['select'][] = "
                s.id, 
                s.name as store_name, 
                (SELECT Count(*) FROM products_seller_migration WHERE store_id = s.id) as total_imported_products, 
                (SELECT Count(*) FROM products_seller_migration WHERE store_id = s.id AND internal_id IS NULL AND date_disapproved IS NOT NULL) as total_migrated_products, 
                (SELECT Count(*) FROM products_seller_migration WHERE store_id = s.id AND internal_id IS NOT NULL AND date_approved IS NOT NULL) as total_matchs, 
                sm.status as migration_status, 
                sm.finish_date
            ";
            $query['from'][] = 'stores s';
            $query['join'][] = ["seller_migration_register sm", "s.id = sm.store_id", 'LEFT'];

            $data = fetchDataTable(
                $query,
                array('s.id', 'DESC'),
                array(
                    'company'   => 's.company_id',
                    'store'     => 's.id'
                ),
                null,
                ['initStoreMigration'],
                $filters,
                $fields_order,
                $filter_default
            );
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(
                    json_encode(array(
                        "draw"              => $draw,
                        "recordsTotal"      => 0,
                        "recordsFiltered"   => 0,
                        "data"              => $result,
                        "message"           => $exception->getMessage()
                    ))
                );
        }

        foreach ($data['data'] as $key => $value) {
            $buttons =  "";
            $link  = "<a href=" . base_url('MigrationSeller/viewmigrations').">". $value['id'] ."</a>";
            if($value['migration_status'] == null){
                $value['migration_status'] = '<span class="label label-default">Aguardando...</span>';
                $buttons = '<button onclick="startMigrationStore( \'' . $value['id'] . '\')" class="btn btn-success mr-2" data-id='. $value['id'] .'  title="' . $this->lang->line('application_start_migration_seller') . '"><i class="fa fa-play-circle" aria-hidden="true"></i></button>';
            }
            if($value['migration_status'] == "0"){
                $value['migration_status'] = '<span class="label label-primary">Migração iniciada</span>';
                $buttons .= '<button onclick="runMigrationStore( \'' . $value['id'] . '\')" class="btn btn-primary mr-2" data-id='. $value['id'] .' title="' . $this->lang->line('application_run_now') . '"><i class="fas fa-fast-forward"></i></button> ';
                $buttons .= '<button onclick="endMigrationStore( \'' . $value['id'] . '\')" class="btn btn-success mr-2" data-id='. $value['id'] .' title="' . $this->lang->line('application_end_migration_seller') . '"><i class="fa fa-square" aria-hidden="true"></i></button>';
            }
            if($value['migration_status'] == 1){
                $value['migration_status'] = '<span class="label label-success">Finalizada</span>';
                $buttons .= '<button onclick="restartMigrationStore( \'' . $value['id'] . '\', this)" class="btn btn-info mr-2" data-id='. $value['id'] .' title="' . $this->lang->line('application_restart_now') . '"><i class="fas fa-sync"></i></button> ';
            }

            $result[] = array(
                $link,
                $value['store_name'],
                $value['total_imported_products'],
                $value['total_migrated_products'],
                $value['total_matchs'],
                $value['migration_status'],
                $buttons
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    public function fetchProductsMigrationData(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $store_id       = $this->postClean('store_id');
            $filters        = array();
            $filter_default = array();

            $filter_default[]['where']['store_id'] = $store_id;
            $filter_default[]['where']['internal_id'] = null;
            $filter_default[]['where']['date_disapproved'] = null;
            $filter_default[]['where']['date_approved'] = null;

            $fields_order = array('id', 'product_name', 'sku_name', 'id_sku', '');

            $query = array();
            $query['select'][] = "id, id_sku, sku_name, product_name";
            $query['from'][] = 'products_seller_migration';

            $data = fetchDataTable(
                $query,
                array('id', 'ASC'),
                null,
                null,
                ['initStoreMigration'],
                $filters,
                $fields_order,
                $filter_default
            );
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(
                    json_encode(array(
                        "draw"              => $draw,
                        "recordsTotal"      => 0,
                        "recordsFiltered"   => 0,
                        "data"              => $result,
                        "message"           => $exception->getMessage()
                    ))
                );
        }

        foreach ($data['data'] as $key => $value) {
            $buttons =  "";
            $productSkuStore = $this->model_products->getVariantsBySkuAndStore($value['id_sku'], $store_id);
            if ($productSkuStore) {
                $product = $this->model_products->getProductData(0, $productSkuStore['prd_id']);
                $productSkuStore['name'] = $product['name'] . ' -  ' . $productSkuStore['name'];
                $productSkuStore['id'] = $productSkuStore['prd_id'];
            } else {
                $productSkuStore = $this->model_products->getProductBySkuAndStore($value['id_sku'], $store_id);
            }
            if ($productSkuStore != null) {
                $value['internal_id'] =  $productSkuStore['sku'];
                $buttons = '<button onclick="changeMigrationApproval(event,\'' . $value['id'] . '\',\'' . $productSkuStore['id'] . '\', \'' . $productSkuStore['sku'] . '\')" class="btn btn-success mr-2" data-toggle="tooltip" title="' . $this->lang->line('application_approve') . '"><i class="fas fa-thumbs-up"></i></button>';
                $buttons .= '<button onclick="changeMigrationRepproval(event,\'' . $value['id'] . '\',\'' . $productSkuStore['id'] . '\', \'' . $productSkuStore['sku'] . '\')" class="btn btn-danger" data-toggle="tooltip" title="' . $this->lang->line('application_disapprove') . '"><i class="fas fa-thumbs-down"></i></button>';
            } else {
                $value['internal_id'] = " Não Encontrado ";
                $productSkuStore['name'] = " Não Encontrado ";
                $buttons = '<button onclick="changeMigrationApproval(event,\'' . $value['id'] . '\')" class="btn btn-success mr-2" data-toggle="tooltip" title="' . $this->lang->line('application_approve') . '" disabled><i class="fas fa-thumbs-up"></i></button>';
                $buttons .= '<button onclick="changeMigrationRepproval(event,\'' . $value['id'] . '\')" class="btn btn-danger" data-toggle="tooltip" title="' . $this->lang->line('application_disapprove') . '" disabled><i class="fas fa-thumbs-down"></i></button>';
            }
            $result[$key] = array(
                $value['id'] = (int) $value['id'],
                $value['id_sku'],
                $productSkuStore['name'],
                $value['product_name'] . " - " . $value['sku_name'],
                $buttons
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
    public function fetchCompanyStores($company_id)
    {
        $result = $this->model_stores->getCompanyStoresUnmigrated($company_id);
        $stores = [];
        foreach ($result as $key => $value) {
            $store = [
                "store_id" => $value['id'],
                "name" => $value['name']
            ];
            $stores[] = $store;
        }
        echo json_encode($stores);

    }
    public function fetchCompanyStoresOnMigration($company_id)
    {
        $result = $this->model_stores->getCompanyStoresOnMigration($company_id);
        $stores = [];
        foreach ($result as $key => $value) {
            $store = [
                "store_id" => $value['store_id'],
                "name" => $value['name']
            ];
            $stores[] = $store;
        }
        if($stores == []){
            $this->session->set_flashdata('error', 'Não há lojas de migração para empresa selecionada.');
        }
        echo json_encode($stores);

    }
    public function fetchStoresToMigrate($company_id)
    {
        $result = $this->model_stores->getCompanyStoresUnmigrated($company_id);
        $stores = [];
        foreach ($result as $key => $value) {
            $store = [
                "store_id" => $value['id'],
                "name" => $value['name']
            ];
            $stores[] = $store;
        }
        echo json_encode($stores);
    }

    public function fetchIntegrations()
    {
        $result = $this->model_integrations->get_integrations_list();
        $integrations = [];
        foreach ($result as $key => $value) {
            $integration = [
                "integration_id" => $value['id'],
                "int_to" => $value['int_to'],
                "name" => $value['name']
            ];
            $integrations[] = $integration;
        }
        return $integrations;
    }

    public function fechMigrations($store_id = null)
    {
        $store_id = $this->postClean('store_id', TRUE);

        $migration = $this->model_products_seller_migration->getMigration($store_id);
        echo $migration[0]['seller_id'];
    }

    public function startMigrationSeller()
    {
        $seller_id = $this->postClean('seller_id', TRUE);
        $int_to = $this->postClean('int_to', TRUE);
        $store_id = (int) $this->postClean('selectstore', TRUE);
        $restart = $this->postClean('restart', TRUE);
        $validate = [
            'seller_id' => $seller_id,
            'store_id' => $store_id,
            'int_to' => $int_to
        ];

        foreach ($validate as $key => $value) {
            if (empty($value)) {
                $this->session->set_flashdata('error', 'Preencha todos os campos obrigatórios: ' . $key);
                redirect(self::MIGRATION_SELLER_ROUTE);
            }
        }
       
        $store = $this->model_stores->getStoresData($store_id);

        $integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $int_to);
        $int_to = $integration_data['int_to'];
        $store_integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id, $int_to);
        $date_integrate = date('Y-m-d h:i:s');
        if ($store_integration_data == null) {
            $data = array(
                'name' => $integration_data['name'],
                'int_from' => 'HUB',
                'int_to' => $int_to,
                'auth_data' => json_encode(array('date_integrate' => $date_integrate, 'seller_id' => $seller_id)),
                'store_id' => $store_id,
                'company_id' => $store['company_id'],
                'int_type' => 'BLING',
                'active' => 0,
                'auto_approve' => $integration_data['auto_approve'],
            );
            $this->model_integrations->create($data);
        } else {
            //uptade
            $data = array(
                'active' => 0,
                'auth_data' => json_encode(array('date_integrate' => $date_integrate, 'seller_id' => $seller_id)),
            );

            $this->model_integrations->update($data,  $store_integration_data['id']);
        }

        $procura = " WHERE  seller_id = '$seller_id'  AND  store_id = $store_id";
        $started_migration = $this->model_seller_migration_register->getData(null, $procura);
        if (!$started_migration) {
            $import_start_date = date('Y-m-d h:i:s');
            $data = [
                'seller_id' => $seller_id,
                'store_id' => $store_id,
                'user_id' =>  $this->session->userdata('id'),
                'int_to' => $int_to,
                'status' => 0,
                'import_start_date' => $import_start_date
            ];
            $this->model_seller_migration_register->create($data);
            $this->createJobs($seller_id, $store_id, $int_to);
            $this->session->set_flashdata('success', $this->lang->line('application_migration_seller_start_sucess'));
            redirect(self::MIGRATION_SELLER_ROUTE, 'refresh');
        }

        if ($restart) {
            $this->session->set_flashdata('success', "Migração reiniciada.");
        } else {
            $this->session->set_flashdata('error', "Já existe migração para esta loja!");
        }
        redirect(self::MIGRATION_SELLER_ROUTE, 'refresh');
    }

    public function endMigrationSeller()
    {
        $store_id = (int) $this->postClean('selectstore', TRUE);

        $int_to = $this->model_products_seller_migration->getIntTo($store_id);
        $ck = date('Y-m-d H:i:s');

        if ($int_to == 0 || $int_to == null || $store_id == 0 || $store_id == null) {
            $this->session->set_flashdata('error', 'Preecha todos os campos obrigatórios');
            redirect(self::VIEW_END_MIGRATION_SELLER_ROUTE);
        }

        $products_store = $this->model_products_seller_migration->getProductDatabyStore($store_id);
        foreach ($products_store as $value) {
            $productSkuStore = $this->model_products->getProductBySkuAndStore($value['id_sku'], $store_id);
            $date = date("Y-m-d H:i:s");
            if (!$productSkuStore) {
                $data = [
                    'user_id' =>  $this->session->userdata('id'),
                    'date_disapproved' => $date
                ];
                $this->model_products_seller_migration->update($data, $value['id']);
            } else {
                $data = [
                    'user_id' =>  $this->session->userdata('id'),
                    'date_approved' => $date
                ];
                $this->model_products_seller_migration->update($data, $value['id']);
            }
        }

        $this->endMigrationRegister($store_id);

        $this->session->set_flashdata('success', $this->lang->line('application_migration_seller_end_sucess'));
        redirect(self::VIEW_END_MIGRATION_SELLER_ROUTE, 'refresh');
    }

    public function approveProducMigration($data = null, $id = null)
    {
        $store_id   = $this->postClean('store_id');
        $id         = $this->postClean('id');
        $product_id = $this->postClean('product_id');

        $prd_migration_data = $this->model_products_seller_migration->getProductDataById($id);

        if (!$prd_migration_data) {
            $this->session->set_flashdata('error', $this->lang->line('application_migration_product_unlinked'));
        }

        $product_data = $this->model_products->getProductData(0, $product_id);

        if ($product_data) {
            $this->updateBrandAndCategoryToProduct($product_data, $prd_migration_data);

            $store_integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id, $prd_migration_data['int_to']);
            $data = [
                'user_id'       => $this->session->userdata('id'),
                'date_approved' => date("Y-m-d H:i:s"),
                'internal_id'   => $product_data['id']
            ];
            $this->model_products_seller_migration->update($data, $id);
            $PrdIntId = $this->createPrdToIntegration($product_data, $prd_migration_data['int_to'], $prd_migration_data['id_sku'], $store_integration_data);

            if (!$PrdIntId) {
                $this->session->set_flashdata('error', $this->lang->line('application_migration_product_unlinked'));
            } else {
                $this->session->set_flashdata('success', $this->lang->line('application_migration_product_linked'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('application_migration_product_unlinked'));
        }

        redirect(self::VIEW_MIGRATION_SELLER_ROUTE, 'refresh');
    }

    public function reproveProducMigration($data = null, $id = null)
    {
        $store_id   = $this->postClean('store_id');
        $id         = $this->postClean('id', TRUE);

        $data = [
            'user_id' =>  $this->session->userdata('id'),
            'date_disapproved' => date("Y-m-d H:i:s")
        ];

        $this->model_products_seller_migration->update($data, $id);
        $this->session->set_flashdata('error', $this->lang->line('application_migration_product_unlinked'));
        redirect(self::VIEW_MIGRATION_SELLER_ROUTE, 'refresh');
    }

    public function aproveAllProductMigration($store_id = null)
    {
        $store_id   = $this->postClean('store_id', TRUE);
        $int_to     = $this->model_products_seller_migration->getIntTo($store_id);

        $store_integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto($store_id, $int_to['int_to']);

        $products_store = $this->model_products_seller_migration->getUnmigratedProductsByStore($store_id);
        foreach ($products_store as $key => $value) {
            $productSkuStore = $this->model_products->getVariantsBySkuAndStore($value['id_sku'], $store_id);
            if ($productSkuStore) {
                $productSkuStore = $this->model_products->getProductData(0, $productSkuStore['prd_id']);
            } else {
                $productSkuStore = $this->model_products->getProductBySkuAndStore($value['id_sku'], $store_id);
            }

            // Não encontrou o produto, reprovar.
            if (!$productSkuStore) {
                $data = [
                    'user_id' =>  $this->session->userdata('id'),
                    'date_disapproved' => date("Y-m-d H:i:s"),
                ];
                $this->model_products_seller_migration->update($data, $value['id']);
                continue;
            }

            $data = [
                'user_id' =>  $this->session->userdata('id'),
                'date_approved' => date("Y-m-d H:i:s"),
                'internal_id' => $productSkuStore['id']
            ];
            $this->model_products_seller_migration->update($data, $value['id']);
            $this->updateBrandAndCategoryToProduct($productSkuStore, $value);
            $this->createPrdToIntegration($productSkuStore, $int_to['int_to'], $value['id_sku'], $store_integration_data);
        }
        $this->session->set_flashdata('success', $this->lang->line('application_migration_approve_all_sucess'));
        redirect(self::MIGRATION_SELLER_ROUTE, 'refresh');
    }

    private function updateBrandAndCategoryToProduct(array $product, array $data_product_mmigrated)
    {
        $data_brand_category = array();

        $prd_category = $this->model_products_seller_migration->getProductCategory($data_product_mmigrated['category_id'], $data_product_mmigrated['int_to']);
        $prd_brand = $this->model_products_seller_migration->getProductBrand($data_product_mmigrated['brand_id'], $data_product_mmigrated['int_to']);

        if ($prd_category) {
            $data_brand_category['category_id'] =  '["'.$prd_category['category_id'].'"]';
        }

        if ($prd_brand) {
            $data_brand_category['brand_id'] = '["'.$prd_brand['brand_id'].'"]';
        }

        $images_product = 0;

        if (!empty($product['has_variants'])) {
            $variants = $this->model_products->getVariants($product['id']);
            foreach ($variants as $variant) {
                $images_product += $this->uploadproducts->countImagesDir("$product[image]/$variant[image]");
            }
        } else {
            $images_product = $this->uploadproducts->countImagesDir($product['image']);
        }

        $category_id = $data_brand_category['category_id'] ?? $product['category_id'];
        $brand_id    = $data_brand_category['brand_id'] ?? $product['brand_id'];

        $data_brand_category['situacao'] = $category_id !== '[""]' && $brand_id !== '[""]' && $images_product ? $this->model_products::COMPLETE_SITUATION : $this->model_products::INCOMPLETE_SITUATION;

        $this->model_products->update($data_brand_category, $product['id']); // Salva a marca e categoria no produto
    }

    public function createPrdToIntegration($product, $int_to, $skumkt, $integration) {
      	$variant = $this->model_products->getVariantSku($product['id'], $skumkt);
        $toSavePrd = [
            'prd_id'        => $product['id'],
            'company_id'    => $product['company_id'],
            'status'        => 1,
            'status_int'    => 0,
            'int_to'        => $int_to,
            'skumkt'        => $skumkt,
            'skubling'      => $skumkt,
            'store_id'      => $product['store_id'],
            'approved'      => 1,
            'int_id'        => $integration['id'],
            'user_id'       => $this->session->userdata('id'),
            'int_type'      => 0
        ];

        if ($variant) {
            $toSavePrd['variant'] = $variant['variant'];
        }

        return $this->model_integrations->createIfNotExist($product['id'], $int_to, null,  $toSavePrd);
    }
    
    function createJobs($seller_id, $store_id, $int_to)
    {
        $ck = date('Y-m-d H:i:s');
        $ini = date('Y-m-d H:i:s', strtotime("+01 minutes", strtotime($ck)));
        $params = $seller_id . "/" . $store_id . "/" . $int_to;
        $module_path = 'SellerCenter/Vtex/SellerMigration';
        $query = "module_path = '$module_path' AND params = '$params' AND status = 0";
        $job =  $this->model_calendar->get_jobs($query);
        $job = $job->row_array();
        if(!$job){
            $this->model_calendar->add_job(
                array(
                    "module_path" => $module_path,
                    "module_method" => 'run',
                    "params" => "$params",
                    "status" => 0,
                    "finished" => 0,
                    "error" => NULL,
                    "error_count" => 0,
                    "error_msg" => NULL,
                    "date_start" => $ini,
                    "date_end" => null,
                    "server_id" => 0
                )
            );
            $this->session->set_flashdata('success', "Aguarde a execução do Job!");
            return true;
        }else{
            $this->session->set_flashdata('error', "Já existe Job para esta loja aguarde a execução!");
            return false;
        }
    }

    public function endMigrationRegister($store_id)
    {

        $procura = " WHERE store_id = $store_id";
        $started_migration = $this->model_seller_migration_register->getData(null, $procura,1);
        if ($started_migration) {
            $data = [
                'user_id' =>  $this->session->userdata('id'),
                'end_date' => date("Y-m-d H:i:s"),
                'status' => 1
            ];
            $this->model_seller_migration_register->update($data, (int) $started_migration['id']);
        }
        return true;
    }
    public function checkSellerIdVtex()
    {
        $seller_id = $this->postClean('seller_id', TRUE);
        $int_to = $this->postClean('int_to', TRUE);
        $integration_data = $this->model_integrations->getIntegrationbyStoreIdAndInto(0, $int_to); 
        $auth_data = json_decode($integration_data['auth_data']);
        if(!isset($auth_data->accountName) && !isset($auth_data->environment) && !isset($auth_data->suffixDns)){
            $error =[
                'data' => null,
                'integration_error' => "No Integration"
            ];
            echo json_encode($error);
            exit;
        }
        $arrayPolicies = $this->model_integrations_settings->getIntegrationSettingsbyId($integration_data['id']);
        
        if(!empty($arrayPolicies)){
            $policies = explode(",",$arrayPolicies['tradesPolicies']);
            
            $policies = array_map(function($policy) {
                $policy = str_replace(['[', ']', '"'], '', $policy);
                return trim($policy);
            }, $policies);

            $police = $policies[0];
        }else{
            $police = 1;
        }

        $url = "https://" . $auth_data->accountName . "." . $auth_data->environment . $auth_data->suffixDns;
        $getSellerId = $url . "/api/seller-register/pvt/sellers/{$seller_id}?sc={$police}";
        
        $client = new Client();
        $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'X-VTEX-API-AppKey' => $auth_data->X_VTEX_API_AppKey,
        'X-VTEX-API-AppToken' => $auth_data->X_VTEX_API_AppToken
        ];
        $request = new Request('GET',$getSellerId, $headers);
        try {
            $res = $client->send($request);
        } catch (RequestException $e) {
            if($e->getResponse()->getStatusCode() > 400){
                $res = $e->getResponse();
            }
            if ($e->hasResponse()) {
                $res = $e->getResponse();
            }  
        }
        $resJson = $res->getBody()->getContents();  
        echo $resJson;
    }

    public function createJobStore()
    {
        $store_id = (int) $this->postClean('store_id', TRUE);
        $procura = " WHERE  store_id = $store_id ";
        $started_migration_store = $this->model_seller_migration_register->getData(null, $procura);
       $this->createJobs($started_migration_store['seller_id'], $store_id, $started_migration_store['int_to']);
    }

    public function restartJobStore()
    {
        $store_id = (int) $this->postClean('store_id', TRUE);

        $migration = $this->model_seller_migration_register->getSellerMigrationByStoreId($store_id);
        if ($migration) {
            $this->model_seller_migration_register->updateSellerMigrationRegisterByStoreId($store_id);
            $this->model_seller_migration_register->updateProductsInProductsSellerMigration($store_id);

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'store_id' => $store_id,
                    'seller_id' => $migration['seller_id'],
                    'int_to' => $migration['int_to']
                ]));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'store_id' => 0,
                'seller_id' => 0,
                'int_to' => 0
            ]));
    }
}
