<?php
/*

Controller de Add-On

 */

use App\Libraries\FeatureFlag\FeatureManager;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Model_prd_addon $model_prd_addon
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 */

class AddOn extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

		$this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_add_on');

        $this->load->model('model_prd_addon');
        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->model('model_queue_products_marketplace');
    }

    public function list($prd_id = null)
    {
        if (!in_array('addOn', $this->permission) || empty($prd_id)) {
			redirect('dashboard', 'refresh');
		}

        $product = $this->model_products->getProductData(0,$prd_id);
        $this->data['prd_id'] = $product["id"];
        $this->data['store_id'] = $product["store_id"];

        $this->render_template('addon/add_on', $this->data);
    }

    public function fetchAddOnData(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $prd_id = $this->postClean('prd_id');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $filter_default[]['where']['p.status !='] = Model_products::DELETED_PRODUCT;
            $filter_default[]['where']['ad.prd_id'] = $prd_id;

            $fields_order = array(
                'p.id',
                'p.name',
                'p.sku',
                'p.status',
                '');

            $query = array();
            $query['select'][] = "
                p.id,
                p.name,
                p.sku,
                p.status,
                ad.prd_id_addon
            ";
            $query['from'][] = 'prd_addon ad';
            $query['join'][] = ["products p", "p.id = ad.prd_id_addon"];

            $data = fetchDataTable(
                $query,
                array('p.id', 'DESC'),
                array(
                    'company'   => 'ad.company_id',
                    'store'     => 'ad.store_id'
                ),
                null,
                ['addOn'],
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

        foreach ($data['data'] as $value) {
            $buttons = ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].')"><i class="fa fa-trash"></i></button>';
            $buttons .= ' <a href="'.base_url('products/update/'.$value['id']).'" class="btn btn-default" target="_blank"><i class="fa fa-eye"></i></a>';

            if (FeatureManager::isFeatureAvailable('OEP-1957-update-delete-publica-addon-occ')) {     
                $integrations = $this->model_integrations->getIntegrationsProduct($value['id'], 1);
                if ($integrations) {
                    foreach ($integrations as $v) {
                        if ($v['errors'] == 1) {
                            $status = '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_errors_tranformation'), 'UTF-8') . '"> Erro de transformação </span>&nbsp;';
                        } elseif ($v['status_int'] == 2) {
                            $status = '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_sent'), 'UTF-8') . '"> Enviado para marketplace </span>&nbsp;';
                        } else {
                            $status = '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper("Não publicado") . '"> Não publicado</span>&nbsp;';
                        }
                    }
                } else {
                    $status = "<span></span>";
                }
            } else {
                $status = ($value['status'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';
            }

            $result[] = array(
                $value['id'],
                $value['name'],
                $value['sku'],
                $status,
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

    public function fetchProductStore(int $store_id): CI_Output
    {
        $draw   = $this->postClean('draw');
        $prd_id_ignore   = $this->postClean('prd_id_ignore');
        $result = array();

        try {
            $my_stores = $this->model_stores->getMyStores();

            // Usuário não encontrado.
            if (!in_array($store_id, $my_stores)) {
                throw new Exception("Empresa não encontrada", 404);
            }

            $filters        = array();
            $filter_default = array();

            $filter_default[]['where']['p.status !='] = Model_products::DELETED_PRODUCT;
            $filter_default[]['where']['ad.id'] = null;
            $filter_default[]['where']['p.store_id'] = $store_id;
            $filter_default[]['where']['p.id !='] = $prd_id_ignore;

            $fields_order = array(
                'p.principal_image',
                'p.id',
                'p.name',
                'p.sku',
                'p.status',
                '');

            $query = array();
            $query['select'][] = "
                p.principal_image,
                p.id,
                p.name,
                p.sku,
                p.status
            ";
            $query['from'][] = 'prd_to_integration pti';
            $query['join'][] = ["prd_addon ad", "pti.prd_id = ad.prd_id_addon AND ad.prd_id = $prd_id_ignore", 'left'];
            $query['join'][] = ["products p", "pti.prd_id = p.id"];

            $data = fetchDataTable(
                $query,
                array('p.id', 'DESC'),
                array(
                    'company'   => 'ad.company_id',
                    'store'     => 'ad.store_id'
                ),
                null,
                ['addOn'],
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

        foreach ($data['data'] as $value) {
            $buttons = ' <button type="button" class="btn btn-success btn-add-product" data-product="'.$value['id'].'"><i class="fa fa-plus"></i></button>';
            $buttons .= ' <a href="'.base_url('products/update/'.$value['id']).'" class="btn btn-primary" target="_blank"><i class="fa fa-eye"></i></a>';

            $status = ($value['status'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';
            $image = ($value['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') :  $value['principal_image'];

            $result[] = array(
                "<img src='$image' class='rounded' width='50' height='50' alt='$value[name]'>",
                $value['name'],
                $value['sku'],
                $status,
                $value['id'],
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

    public function removeAdditionalProduct(): CI_Output
    {
        $prd_id_addon = $this->postClean('product_id_addon');
        $prd_id = $this->postClean('prd_id');
		if (!in_array('addOn', $this->permission) || !$prd_id_addon || !$prd_id) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
			        'message'   => $this->lang->line('messages_refresh_page_again')
                )));
		}

        $product_addon = $this->model_prd_addon->getAddonDataByPrdIdAddOnAndPrdId($prd_id_addon, $prd_id);
        if ($product_addon && $this->model_prd_addon->removeByPrdIdAddOnAndPrdId($prd_id_addon, $prd_id)) {
            $this->log_data('Addon','remove',$prd_id_addon);

            $this->model_queue_products_marketplace->create(array(
                'status' => 0,
                'prd_id' => $product_addon['prd_id']
            ));

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => true,
                    'message'   => $this->lang->line('messages_successfully_removed')
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => false,
                'message'   => $this->lang->line('messages_error_database_create_addon')
            )));
	}

    public function create(): CI_Output
    {
        $prd_id = $this->postClean('prd_id');
        $product_id_addon = $this->postClean('product_id_addon');

        if (!in_array('addOn', $this->permission) && !$prd_id && !$product_id_addon) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'message'   => $this->lang->line('messages_refresh_page_again')
                )));
        }

        $productMain    = $this->model_products->getProductData(0, $prd_id);
        $productAddOn   = $this->model_products->getProductData(0, $product_id_addon);

        if (empty($productMain) || empty($productAddOn)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'message'   => $this->lang->line('messages_refresh_page_again')
                )));
        }

        if ($productMain['store_id'] != $productAddOn['store_id']) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'message'   => 'Produto anunciado e produto adicional precisam ser da mesma loja'
                )));
        }

        $addonExist = $this->model_prd_addon->getAddonDataByPrdIdAddOnAndPrdId($productAddOn["id"], $productMain['id']);
        if (empty($addonExist)) {
            $createAddOn = array(
                "prd_id"        => $productMain["id"],
                "prd_id_addon"  => $productAddOn["id"],
                "store_id"      => $productAddOn["store_id"],
                "company_id"    => $productAddOn["company_id"]
            );

            if ($this->model_prd_addon->create($createAddOn)) {
                $this->log_data('Addon','create', json_encode($createAddOn, JSON_UNESCAPED_UNICODE));

                $this->model_queue_products_marketplace->create(array(
                    'status' => 0,
                    'prd_id' => $productMain["id"]
                ));

                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => true,
                        'message'   => $this->lang->line('messages_success_database_create_addon')
                    )));
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => false,
                'message'   => $this->lang->line('messages_error_database_create_addon')
            )));
    }
}
