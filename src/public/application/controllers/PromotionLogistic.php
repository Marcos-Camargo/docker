<?php
/*

*/
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 * @property CI_Lang $lang
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Output $output
 *
 * @property Model_promotionslogistic $model_promotionslogistic
 * @property Model_category $model_category
 * @property Model_stores $model_stores
 * @property Model_products $model_products
 */

class PromotionLogistic extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_promotions_logistic');

        $this->load->model('model_promotionslogistic');
        $this->load->model('model_category');
        $this->load->model('model_stores');
        $this->load->model('model_products');
    }

    public function index()
    {
        if (!in_array('createPromotionsLogistic', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['listPromo'] = $this->model_promotionslogistic->getList();
        $this->data['page_title'] = $this->lang->line('application_promotions_logistic');
        $this->render_template('promotion_logistic/list', $this->data);
    }
    
    public function seller()
    {
        if (!in_array('viewPromotionsLogistic', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['listPromo'] = $this->model_promotionslogistic->getList();
        $this->data['page_title'] = $this->lang->line('application_promotions_logistic');
        $this->render_template('promotion_logistic/seller/list', $this->data);
    }

    /**
     * Lista as promoções na visão de administrador.
     *
     * @return CI_Output
     */
    public function fetchPromoData(): CI_Output
    {
        // if (!in_array('createPromotionsLogistic', $this->permission)) {
        //     redirect('dashboard', 'refresh');
        // }
        $data['data'] = [];
        $listPromo = $this->model_promotionslogistic->getList();
        if(count($listPromo) > 0 ) {
            foreach($listPromo as $key => $value ) {
                
                $checked = $value['status'] == 1 ? 'checked' : 'disabled' ;
                // $actions  = '<a href="' . base_url('PromotionLogistic/edit/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-edit"></i></a>';
                // $actions .= '<button class="btn btn-danger" onclick="deletePromo(' . $value['id'] . ');" ><i class="fa fa-trash"></i></button>';
                
                if ($value['status'] == 1) {
                    $actions = '<button class="btn btn-danger" onclick="deletePromo(' . $value['id'] . ');" ><i class="fa fa-trash"></i></button>';

                }
                else {
                    $actions  = '<a href="' . base_url('PromotionLogistic/edit/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-edit"></i></a>';
                    $actions .= '<button class="btn btn-danger" onclick="deletePromo(' . $value['id'] . ');" ><i class="fa fa-trash"></i></button>';
                }

                $data['data'][] = array(
                    $value['id'],
                    $value['name'],
                    $this->convertDatePtBr($value['dt_start']),
                    $this->convertDatePtBr($value['dt_end']),
                    $this->convertDatePtBr($value['dt_added']),
                    is_null($value['dt_inactive']) == True ? "-" : $this->convertDatePtBr($value['dt_inactive']),
                    $value['status'] == 1 ? '<input type="checkbox" name="my-checkbox" checked data-bootstrap-switch onchange="updateStatus(' . $value['id'] . ',$(this))">' : '<input type="checkbox"  disabled name="my-checkbox" data-bootstrap-switch onchange="updateStatus(' . $value['id'] . ',$(this))">',
                    $actions
                );
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * Lista as promoções da loja (Minhas Promoções)
     *
     * @return CI_Output
     */
    public function fetchPromoDataToSeller(): CI_Output
    {
        $data['data'] = [];
        $listPromo = $this->model_promotionslogistic->getListPromoSeller();
        if(count($listPromo) > 0 ) {
            foreach($listPromo as $value ) {
                $nameStatus  = $value['status'] == 1 && !$value['dt_inactive'] ? lang('application_active') : lang('application_inactive');
                $colorStatus = $value['status'] == 1 && !$value['dt_inactive'] ? 'success' : 'danger';

                if (
                    (!$value['dt_inactive'] && $value['status'] == 0 && strtotime($value['dt_start']) < strtotime(dateNow()->format(DATETIME_INTERNATIONAL))) ||
                    (!$value['dt_inactive'] && strtotime($value['dt_start']) > strtotime(dateNow()->format(DATETIME_INTERNATIONAL)))
                ) {
                    $nameStatus = lang('application_not_started');
                    $colorStatus = 'warning';
                }

                /*if ($value['active_status'] == 1) {
                    $actions = '<a href="' . base_url('PromotionLogistic/productsPromotionLogistic/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-edit"></i></a>';
                    //$actions  .= '<button class="btn btn-danger" onclick="removeStore('. $value['id'] .');"><i class="fa fa-trash"></i></button>';
                    $actions  .= '<button class="btn btn-info" data-target=".bd-example-modal-xl" onclick="getInfo('.$value['id'].');"><i class="fa fa-info"></i></button>';
                }
                else {
                    $actions  = '<button class="btn btn-info" data-target=".bd-example-modal-xl" onclick="getInfo('.$value['id'].');"><i class="fa fa-info"></i></button>';
                }*/

                $nameBtnAddPromotion = $value['promotion_sellercenter'] == 1 && $value['segment'] === 'product' ? 'Visualizar Produtos' : 'Adicionar Produtos';
                $iconBtnAddPromotion = $value['promotion_sellercenter'] == 1 && $value['segment'] === 'product' ? 'fa-eye' : 'fa-plus';

                $actions = '<div class="d-flex flex-nowrap justify-content-center">';
                $actions .= $value['dt_inactive'] ? '' : '<a class="btn btn-default" href="' . base_url('PromotionLogistic/productsPromotionLogistic/' . $value['id']) . '" data-target="tooltip" title="'.$nameBtnAddPromotion.'"><i class="fa '.$iconBtnAddPromotion.'"></i></a>';
                $actions .= '<button class="btn btn-default mr-1 ml-1" onclick="getInfo('.$value['id'].');" data-target="tooltip" title="Visualizar Promoção"><i class="fa fa-info"></i></button>';
                $actions .= $value['dt_inactive'] ? '' : '<button class="btn btn-default exit-promotion" data-promo-id="'.$value['id'].'" data-target="tooltip" title="Sair da Promoção"><i class="fas fa-sign-out-alt"></i></button>';
                $actions .= '</div>';

                $data['data'][] = array(
                    $value['id'],
                    $value['name'],
                    dateFormat($value['dt_start'], DATETIME_BRAZIL, null),
                    dateFormat($value['dt_end'], DATETIME_BRAZIL, null),
                    dateFormat($value['dt_added'], DATETIME_BRAZIL, null),
                    is_null($value['dt_inactive']) == True ? "-" : dateFormat($value['dt_inactive'], DATETIME_BRAZIL, null),
                    "<span class='label label-$colorStatus'>$nameStatus</span>",
                    $actions
                );
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * Lista as promoções disponíveis para as lojas (Promoções)
     *
     * @return CI_Output
     */
    public function fetchPromoDataSeller(): CI_Output
    {
        $listPromo = $this->model_promotionslogistic->getListPromo();

        $data['data'] = array();
        foreach($listPromo as $value ) {

            $actions = '<div class="d-flex flex-nowrap justify-content-center">';
            $actions .= '<button class="btn btn-default mr-1" onclick="setStorePromotionLogistic('.$value['id'].');"><i class="fa fa-plus"></i></button>';
            $actions .= '<button class="btn btn-default" onclick="getInfo('.$value['id'].');"><i class="fa fa-info"></i></button>';
            $actions .= '</div>';
            
            $data['data'][] = array(
                $value['id'],
                $value['name'],
                dateFormat($value['dt_start'], DATETIME_BRAZIL, null),
                dateFormat($value['dt_end'], DATETIME_BRAZIL, null),
                dateFormat($value['dt_added'], DATETIME_BRAZIL, null),
                $actions
            );
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * Lojista aceitou participar da promoção.
     *
     * @return CI_Output
     */
    public function setStorePromotionLogistic(): CI_Output
    {
        $promotion  = (int)$this->postClean('id_promo', true);
        $store      = (int)$this->session->userdata('userstore');

        $promotionStore = $this->model_promotionslogistic->getPromoByStore($promotion, $store);

        // Existe já o cadastro da loja na tabela, irei apenas atualizar para ativar.
        if ($promotionStore) {
            $dataUpdate = array(
                'dt_update'             => dateNow()->format(DATETIME_INTERNATIONAL),
                'date_seller_accepted'  => dateNow()->format(DATETIME_INTERNATIONAL),
                'seller_accepted'       => true,
                'active_status'         => true
            );
            $updatePromotionStore = $this->model_promotionslogistic->updatePromotionByStore($promotion, $store, $dataUpdate);

            if ($updatePromotionStore) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => true,
                        'message' => lang('messages_store_accepted_promotion_logistic')
                    )));
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => lang('messages_error_occurred')
                )));
        }

        $addStore[] = array(
            'logistic_promotion_id' => $promotion,
            'id_stores'             => $store,
            'dt_added'              => dateNow()->format(DATETIME_INTERNATIONAL),
            'dt_update'             => dateNow()->format(DATETIME_INTERNATIONAL),
            'user'                  => (int)$this->session->userdata('id'),
            'seller_accepted'       => true,
            'active_status'         => true,
            'date_seller_accepted'  => dateNow()->format(DATETIME_INTERNATIONAL)
       );
       
       $insertPromoStore = $this->model_promotionslogistic->insertPromoStore($addStore);
       if ($insertPromoStore) {
           return $this->output
               ->set_content_type('application/json')
               ->set_output(json_encode(array(
                   'success' => true,
                   'message' => lang('messages_store_accepted_promotion_logistic')
               )));
       }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => false,
                'message' => lang('messages_error_occurred')
            )));
    }

    /**
     * Inativa promoção da loja.
     *
     * @return CI_Output
     */
    public function setInactivePromotionLogisticStore(): CI_Output
    {
        $promotion  = $this->postClean('id_promo', true);
        $store      = (int)$this->session->userdata('userstore');

        $inactivePromoStore      = $this->model_promotionslogistic->inactivateStorePromo($promotion, $store);
        $inactiveAllProductStore = $this->model_promotionslogistic->inactivateAllProductsStore($promotion, $store);

        if ($inactivePromoStore && $inactiveAllProductStore) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => true,
                    'message' => lang('messages_store_removed_promotion_logistic')
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => false,
                'message' => lang('messages_error_occurred')
            )));
    }
    
    public function productsPromotionLogistic(int $idPromo)
    {
        $store = (int)$this->session->userdata('userstore');
        $promo = $this->model_promotionslogistic->getPromoId($idPromo);
        $promotionStore = $this->model_promotionslogistic->getPromoByStore($idPromo, $store);

        if ($promotionStore['seller_accepted'] == 0 || $promotionStore['dt_inactive'] != null) {
            redirect('PromotionLogistic/seller', 'refresh');
        }

        $promoCategories = $this->parseCategoriesListProducts($this->model_promotionslogistic->getCategoriesPromoId($idPromo));

        $this->data['products']                 = $this->model_promotionslogistic->getProducts($promo['product_value_mim'],$promo['produtct_amonut'],$store,$promoCategories);
        $this->data['idPromo']                  = $idPromo;
        $this->data['promotion_sellercenter']   = $promo['promotion_sellercenter'] == 1 && $promo['segment'] === 'product';
        $this->data['page_title']               = $this->lang->line('application_promotions_logistic');

        $this->render_template('promotion_logistic/seller/addproduct', $this->data);
    }

    /**
     * Adicionar produto na promoção.
     *
     * @return CI_Output
     */
    public function saveProduct(): CI_Output
    {
        $promotion  = $this->postClean('idPromo', true);
        $product    = $this->postClean('id_product', true);

        try {
            $this->saveProductPromotion($product, $promotion);
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $exception->getMessage()
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Produto adicionado'
            )));
    }

    /**
     * Adicionar produto na promoção por CSV.
     *
     * @return CI_Output
     */
    public function addProductByCSV(): CI_Output
    {
        $productErrors = array();
        try {
            $promotion = $this->postClean('promotion', true);
            $rows = readTempCsv($_FILES['file']['tmp_name'], 0, ['ID do Produto']);

            foreach ($rows as $row) {
                try {
                    $product = $row['ID do Produto'];

                    if (empty($product)) {
                        continue;
                    }

                    $this->saveProductPromotion($product, $promotion);
                } catch (Exception $exception) {
                    $productErrors[] = "[$product] - {$exception->getMessage()}";
                }
            }

        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => $exception->getMessage()
                )));
        }

        if (count($rows) === count($productErrors)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'    => false,
                    'message'    => 'Produtos não foram importados.',
                    'additional' => $productErrors
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'    => true,
                'message'    => count($productErrors) ? 'Produtos importados, alguns não tiveram sucesso.' : 'Produtos importados.',
                'additional' => $productErrors
            )));
    }

    /**
     * Formata lojas para selecionar na listagem de lojas da promoção.
     *
     * @return CI_Output
     */
    public function formatStoreByCSV(): CI_Output
    {
        $stores = array();
        try {
            $rows = readTempCsv($_FILES['file']['tmp_name'], 0, ['ID da Loja']);

            foreach ($rows as $row) {
                $store = $row['ID da Loja'];

                if (empty($store)) {
                    continue;
                }

                $stores[] = $store;
            }

        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'    => false,
                    'message'    => $exception->getMessage(),
                    'additional' => array()
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'    => true,
                'message'    => 'Lojas importados, salve a promoção para ser aplicada.',
                'additional' => $stores
            )));
    }

    /**
     * Formata categorias para selecionar na listagem de categorias da promoção.
     *
     * @return CI_Output
     */
    public function formatCategoryByCSV(): CI_Output
    {
        $categories = array();
        try {
            $rows = readTempCsv($_FILES['file']['tmp_name'], 0, ['ID da Categoria']);

            foreach ($rows as $row) {
                $category = $row['ID da Categoria'];

                if (empty($category)) {
                    continue;
                }

                $categories[] = $category;
            }

        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'    => false,
                    'message'    => $exception->getMessage(),
                    'additional' => array()
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'    => true,
                'message'    => 'Categorias importadas, salve a promoção para ser aplicada.',
                'additional' => $categories
            )));
    }

    /**
     * Formata os produtos para selecionar na listagem de produtos da promoção, na criação de uma nova promoção.
     *
     * @return CI_Output
     */
    public function formatProductByCSV(): CI_Output
    {
        $valueMin       = $this->postClean('valueMin',TRUE);
        $qtyMin         = $this->postClean('qtyMin',TRUE);
        $productNotIn   = $this->postClean('productNotIn',TRUE);

        if ($productNotIn) {
            $productNotIn = explode(',', $productNotIn);
        }

        $products = array();
        $productErrors = array();
        try {
            $rows = readTempCsv($_FILES['file']['tmp_name'], 0, ['ID do Produto']);

            foreach ($rows as $row) {
                $product = $row['ID do Produto'];

                if (empty($product)) {
                    continue;
                }

                if ($productNotIn && in_array($product, $productNotIn)) {
                    continue;
                }

                $dataProduct = $this->model_promotionslogistic->getProducts(
                    $valueMin,
                    $qtyMin,
                    null,
                    '',
                    $product
                );
                if (!$dataProduct) {
                    $productErrors[] = "[$product] - Produto não pode ser adicionado. Não encontrado ou não contempla as regras.";
                    continue;
                }

                $products[] = $this->formatDataProductToTableCreatePromotion($dataProduct, false);
            }

        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'    => false,
                    'message'    => $exception->getMessage(),
                    'additional' => array(),
                    'products'   => array()
                )));
        }

        if (count($rows) === count($productErrors)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'    => false,
                    'message'    => 'Produtos não foram importados.',
                    'additional' => $productErrors,
                    'products'   => array()
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'    => true,
                'message'    => count($productErrors) ? 'Produtos importados, alguns não tiveram sucesso.' : 'Produtos importados.',
                'additional' => $productErrors,
                'products'   => $products
            )));
    }

    /**
     * Salva produto na promoção.
     *
     * @param  int  $product    Código do produto.
     * @param  int  $promotion  Código da promoção.
     * @throws Exception
     */
    private function saveProductPromotion(int $product, int $promotion)
    {
        if (empty($product)) {
            throw new Exception('Selecione um produto');
        }

        $store          = (int)$this->session->userdata('userstore');
        $promotionStore = $this->model_promotionslogistic->getPromoByStore($promotion, $store);

        // Produto não existe ou não pertence à loja.
        if (!$this->model_products->checkProductStore($store, $product)) {
            throw new Exception('Produto inexistente');
        }

        // Promoção indisponível.
        if ($promotionStore['seller_accepted'] == 0 || $promotionStore['dt_inactive'] != null) {
            throw new Exception('Promoção não acessível');
        }

        // Produto já existente na promoção.
        if ($this->model_promotionslogistic->getDataByPromotionAndProduct($promotion, $product)) {
            throw new Exception('Produto já existente na promoção');
        }

        $dataPromotion = $this->model_promotionslogistic->getPromoId($promotion);
        $promoCategories = $this->parseCategoriesListProducts($this->model_promotionslogistic->getCategoriesPromoId($promotion));

        // Produto não pode entrar na promoção, por regra.
        if (
            !$this->model_promotionslogistic->getProducts(
                $dataPromotion['product_value_mim'],
                $dataPromotion['produtct_amonut'],
                $store,
                $promoCategories,
                $product
            )
        ) {
            throw new Exception('Produto não está apto a participar da promoção. Reveja as regras.');
        }

        $addProduct[] = array(
            'promotion_id'  => $promotion,
            'product_id'    => $product,
            'store_id'      => (int)$this->session->userdata('userstore'),
            'dt_added'      => dateNow()->format(DATETIME_INTERNATIONAL),
            'dt_update'     => dateNow()->format(DATETIME_INTERNATIONAL),
            'user'          => (int)$this->session->userdata('id'),
            'active_status' => 1
        );

        if (!$this->model_promotionslogistic->insertProduct($addProduct)) {
            throw new Exception('Produto não adicionado, tente novamente!');
        }
    }

    /**
     * @return CI_Output
     */
    public function removeProduct(): CI_Output
    {
        $promotion  = $this->postClean('idPromo', true);
        $product    = $this->postClean('id_product', true);
        $store      = (int)$this->session->userdata('userstore');

        $removeProduct = $this->model_promotionslogistic->inactivateProduct($promotion, $product, $store);
        if ($removeProduct) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => true,
                    'message' => 'Produto removido'
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Produto não removido, tente novamente!'
            )));
    }

    /**
     * Lista os produtos da promoção da loja.
     *
     * @return CI_Output
     */
    public function fetchPromoProductSeller(): CI_Output
    {
        $store      = (int)$this->session->userdata('userstore');
        $promotion  = $this->input->get('idPromo', true);

        $data['data'] = [];
        $list = $this->model_promotionslogistic->getProductPromoList($promotion, $store);

        if ($list){
            $data['data'] = $this->parseListProduct($list);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    public function getPromoId($id)
    {
        $promo = $this->model_promotionslogistic->getPromoId($id);
        $this->data['promoId'] = $id;
        $this->data['promo']['info']['name'] = $promo['name'];
        $this->data['promo']['info']['dt_start'] = $promo['dt_start'];
        $this->data['promo']['info']['start_hour'] = $promo['start_hour'];        
        $this->data['promo']['info']['dt_end'] = $promo['dt_end'];
        $this->data['promo']['info']['end_hour'] = $promo['end_hour'];
        $this->data['promo']['rule'] = $promo['rule'];
        $this->data['promo']['criterion']['criterion_type'] = $promo['criterion_type'];
        $this->data['promo']['criterion']['price_type_value'] = $promo['price_type_value'];
        $this->data['promo']['criterion']['product_value_mim'] = $promo['product_value_mim'];
        $this->data['promo']['criterion']['produtct_amonut'] = $promo['produtct_amonut'];
        $this->data['promo']['criterion']['region'] = $promo['region'];
        $this->data['promo']['region'] = $this->parseRegionEdit($this->model_promotionslogistic->getRegionPromoId($id));
        $this->data['promo']['categories'] = $this->model_promotionslogistic->getCategoriesPromoId($id);
        
        $this->data['promotion']['type'] = array(
            "1" => "Compartilhado:  (Seller vai pagar 50% do frete e o Conecta Lá/Seller Center os outros 50%)"
           ,"2" => "100% Conecta Lá/Seller Center: (Frete 100% por conta do Conecta Lá ou Seller Center"
           ,"3" => "100% Seller (Frete 100% por conta do Lojista)"
        );

        $this->data['promotion']['type_desc'] = array(
            "1" => "%"
            ,"2" => "R$"
        );

        $region = $this->model_promotionslogistic->getRegions();
        $this->data['region'] = $this->parseRegion($region);        
        $this->data['categories'] = $this->model_category->getActiveCategroy();
        // $this->render_template('promotion_logistic/seller/info', $this->data);
        $this->load->view('promotion_logistic/seller/info', $this->data);
    }

    public function edit($id)
    {   
        $promo = $this->model_promotionslogistic->getPromoId($id);
        $this->data['promoId'] = $id;
        $this->data['promo']['info']['name'] = $promo['name'];
        $this->data['promo']['info']['dt_start'] = $promo['dt_start'];
        $this->data['promo']['info']['start_hour'] = $promo['start_hour'];        
        $this->data['promo']['info']['dt_end'] = $promo['dt_end'];
        $this->data['promo']['info']['end_hour'] = $promo['end_hour'];
        $this->data['promo']['rule'] = $promo['rule'];
        $this->data['promo']['criterion']['criterion_type'] = $promo['criterion_type'];
        $this->data['promo']['criterion']['price_type_value'] = $promo['price_type_value'];
        $this->data['promo']['criterion']['product_value_mim'] = $promo['product_value_mim'];
        $this->data['promo']['criterion']['produtct_amonut'] = $promo['produtct_amonut'];
        $this->data['promo']['criterion']['region'] = $promo['region'];
        $this->data['promo']['region'] = $this->parseRegionEdit($this->model_promotionslogistic->getRegionPromoId($id));
        $this->data['promo']['categories'] = $promo['segment'] === 'category' ? $this->model_promotionslogistic->getCategoriesPromoId($id) : array();
        $this->data['promo']['stores'] = $promo['segment'] === 'store' ? $this->model_promotionslogistic->getStoresPromoId($id) : array();
        $this->data['promo']['products'] = $promo['segment'] === 'product' ? $this->model_promotionslogistic->getProductPromoList($id) : array();
        $this->data['promo']['segment'] = $promo['segment'];

        $this->data['promotion']['type'] = array(
            "1" => "Compartilhado:  (Seller vai pagar 50% do frete e o Conecta Lá/Seller Center os outros 50%)",
            "2" => "100% Conecta Lá/Seller Center: (Frete 100% por conta do Conecta Lá ou Seller Center",
            "3" => "100% Seller (Frete 100% por conta do Lojista)"
        );

        $this->data['promotion']['type_desc'] = array(
            "1" => "%",
            "2" => "R$"
        );
        $region = $this->model_promotionslogistic->getRegions();
        $this->data['region'] = $this->parseRegion($region);        
        $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['stores'] = $this->model_stores->getActiveStore();

        $this->data['page_title'] = $this->lang->line('application_promotions_logistic');
        $this->render_template('promotion_logistic/edit', $this->data);
    }    

    public function create()
    {
        // if (!in_array('createPromotions', $this->permission)) {
        //     redirect('dashboard', 'refresh');
        // }
        
        $this->data['promotion']['type'] = array(
             "1" => "Compartilhado:  (Seller vai pagar 50% do frete e o Conecta Lá/Seller Center os outros 50%)"
            ,"2" => "100% Conecta Lá/Seller Center: (Frete 100% por conta do Conecta Lá ou Seller Center"
            ,"3" => "100% Seller (Frete 100% por conta do Lojista)"
        );
        
        $this->data['promotion']['type_desc'] = array(
             "1" => "%"
            ,"2" => "R$"
        );
        $region = $this->model_promotionslogistic->getRegions();
        $this->data['region'] = $this->parseRegion($region);
        $this->data['categories'] = $this->model_category->getActiveCategroy();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->render_template('promotion_logistic/create', $this->data);

        // $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
        // redirect('PromotionLogistic/', 'refresh');
    }

    /**
     * Atualiza situação da promoção.
     *
     * @return CI_Output
     */
    public function updateStatus(): CI_Output
    {   
        $data = array(
            'status'        => (int) $this->postClean('status'),
            'active_status' => (int) $this->postClean('status'),
            'user'          => $this->session->userdata['id'],
            'dt_update'     => date("Y-m-d h:m:s"),
        );

        if ($data['status'] == 0){
            $data['dt_inactive'] = date("Y-m-d h:m:s");
        }
        
        if ($this->model_promotionslogistic->updateStatus((int) $this->postClean('idPromo'), $data)) {
            $return = array(
                'success' => true,
                'message' => "Promoção alterada com sucesso."
            );
        } else {
            $return = array(
                'success' => false,
                'message' => "Promoção não pode ser alterada."
            );
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($return));
    }

    public function delete()
    {
        $data = array(
            'id'        => (int) $this->postClean('idPromo'),
            'deleted'   => 1,
            'user'      => $this->session->userdata['id'],
            'dt_update' => date("Y-m-d h:m:s"),
        );

        //desativa todas as lojas da promoção
        $this->model_promotionslogistic->batchInactivateStoresbyID($data['id']);
        $this->model_promotionslogistic->batchInactivateItensStoresByPromoId($data['id']);

        $result = $this->model_promotionslogistic->deleted((int) $this->postClean('idPromo'), $data);
            $return = array(
            'success' => '',
            'message' => "Promoção alterada com sucesso."
        );
        echo  json_encode($return);
        exit;
    }

    public function save()
    {
        $info = $this->parseInfo($this->postClean(null, true));

        // dados do array info
        $arrInfo = $this->postClean('info', true);

        $dateStart  = DateTime::createFromFormat('d/m/Y', $arrInfo['dt_start'])->format('Y-m-d') . ' ' . $arrInfo['start_hour'];
        $dateEnd    = DateTime::createFromFormat('d/m/Y', $arrInfo['dt_end'])->format('Y-m-d') . ' ' . $arrInfo['end_hour'];

        //Cria a promoção desativada.
        $info['status'] = 0;

        if (strtotime($dateStart) >= strtotime($dateEnd)) {
            $this->session->set_flashdata('error', $this->lang->line('application_promotion_error_date_info'));
            redirect('PromotionLogistic/create', 'refresh');
        }

        // se for um admin criando, a promoção é do seller center.
        $info['promotion_sellercenter'] = $this->data['usercomp'] == 1;

        $promotionId = $this->model_promotionslogistic->insertInfo($info);

        if (!empty($this->postClean('region', true))) {
            $region = $this->parseInsertRegion($this->postClean('region', true), $promotionId);
            $this->model_promotionslogistic->insertRegion($region);
        }

        if ($this->postClean('segment', true) === 'category') {
            $categories = $this->parseCategories($this->postClean('category', true), $promotionId);
            $this->model_promotionslogistic->insertCategories($categories);
        } else if ($this->postClean('segment', true) === 'store') {
            $createStores = $this->parseStores($this->postClean('store', true), $promotionId);
            $this->model_promotionslogistic->insertStores($createStores);
        } else if ($this->postClean('segment', true) === 'product') {
            $products = explode(',', $this->postClean('products_selected', true));
            $stores = array();
            $productsInsert = array();

            // Ler todos os produtos.
            $productsSelected = $this->model_products->getProductsByIds($products);
            // Verificar se todos realmente podem participar.
            foreach($productsSelected as $productSelected) {

                if (!$this->model_promotionslogistic->getProducts(
                    $this->postClean('criterion', true)['price_mim'],
                    $this->postClean('criterion', true)['amount'],
                    null,
                    '',
                    $productSelected['id']
                )) {
                    // Produto não pode participar, remover.
                    continue;
                }

                $productsInsert[] = array(
                    'id' => $productSelected['id'],
                    'store_id' => $productSelected['store_id']
                );

                // Identificar de quais lojas são os produtos.
                if (!in_array($productSelected['store_id'], $stores)) {
                    $stores[] = $productSelected['store_id'];
                }
            }

            // Entrará como se as lojas e produtos já tivesse aprovado a promoção.
            // Adicionar as lojas na tabela 'logistic_promotion_stores'.
            $createStores = $this->parseStores($stores, $promotionId, true);
            $this->model_promotionslogistic->insertStores($createStores);
            // Adicionar os produtos na tabela 'logistic_promotion_products'.
            $createProduct = $this->parseProducts($productsInsert, $promotionId, true);
            $this->model_promotionslogistic->insertProduct($createProduct);
        }

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
        redirect('PromotionLogistic/', 'refresh');
    }

    public function update($promotionId)
    {
        $dataPromotion = $this->model_promotionslogistic->getPromoId($promotionId);

        if (
            ($dataPromotion['segment'] !== 'product' || $this->postClean('segment', true) !== 'product') &&
            count($this->model_promotionslogistic->getProductsByPromotion($promotionId))
        ) {
            $this->session->set_flashdata('error', 'Promoção já contém produtos. Não é mais permitido alterar a promoção.');
            redirect("PromotionLogistic/edit/$promotionId", 'refresh');
        }
        
        $info = $this->parseInfo($this->postClean(null, true));
        // dados do array info
        $arrInfo = $this->postClean('info', true);

        $dateStart  = DateTime::createFromFormat('d/m/Y', $arrInfo['dt_start'])->format('Y-m-d') . ' ' . $arrInfo['start_hour'];
        $dateEnd    = DateTime::createFromFormat('d/m/Y', $arrInfo['dt_end'])->format('Y-m-d') . ' ' . $arrInfo['end_hour'];

        if (strtotime($dateStart) >= strtotime($dateEnd)) {
             $this->session->set_flashdata('error', $this->lang->line('application_promotion_error_date_info'));
             redirect('PromotionLogistic/edit/'.$promotionId, 'refresh');
        }

        if (!empty($this->postClean('region', true))){
            $region = $this->parseInsertRegion($this->postClean('region'),$promotionId);
            $this->model_promotionslogistic->updateRegion($region,$promotionId);
        }

        // mudou de segmento, limpo os dados do segmento anterior.
        if ($dataPromotion['segment'] != $this->postClean('segment', true)) {
            if ($this->postClean('segment', true) === 'category') {
                $this->model_promotionslogistic->removeAllStoreByPromotion($promotionId);

                foreach($this->model_promotionslogistic->getProductPromoList($promotionId) as $product) {
                    $this->model_promotionslogistic->inactivateProduct($promotionId, $product['id'], $product['store_id']);
                }
            } else if ($this->postClean('segment', true) === 'store') {
                $this->model_promotionslogistic->removeAllCategoryByPromotion($promotionId);

                foreach($this->model_promotionslogistic->getProductPromoList($promotionId) as $product) {
                    $this->model_promotionslogistic->inactivateProduct($promotionId, $product['id'], $product['store_id']);
                }
            } else if ($this->postClean('segment', true) === 'product') {
                $this->model_promotionslogistic->removeAllCategoryByPromotion($promotionId);
                $this->model_promotionslogistic->removeAllStoreByPromotion($promotionId);
            }
        }

        if ($this->postClean('segment', true) === 'category') {
            $categories = $this->parseCategories($this->postClean('category'),$promotionId);
            $this->model_promotionslogistic->updateCategories($categories,$promotionId);
        } else if ($this->postClean('segment', true) === 'store') {
            $stores = $this->parseStores($this->postClean('store', true), $promotionId);
            $this->model_promotionslogistic->updateStoresPromotion($stores, $promotionId);
        } else if ($this->postClean('segment', true) === 'product') {
            $products = explode(',', $this->postClean('products_selected', true));
            $productsDisabled = explode(',', $this->postClean('products_disabled', true));

            $stores = array();
            $productsInsert = array();
            $storesInPromotion = $this->model_promotionslogistic->getStoresPromoId($promotionId);

            // Ler todos os produtos.
            $productsSelected = $this->model_products->getProductsByIds($products);
            // Verificar se todos realmente podem participar.
            foreach($productsSelected as $productSelected) {

                $productIsPromotion = $this->model_promotionslogistic->getDataByPromotionAndProduct($promotionId, $productSelected['id']);

                if (
                    in_array($productSelected['id'], $productsDisabled) ||
                    !$this->model_promotionslogistic->getProducts(
                    $this->postClean('criterion', true)['price_mim'],
                    $this->postClean('criterion', true)['amount'],
                    null,
                    '',
                    $productSelected['id']
                )) {
                    // Produto não pode participar, inativa.
                    if ($productIsPromotion && $productIsPromotion['active_status'] == 1) {
                        $this->model_promotionslogistic->inactivateProduct($promotionId, $productSelected['id'], $productSelected['store_id']);
                    }
                    continue;
                }

                // produto já está na promoção.
                if ($productIsPromotion) {
                    continue;
                }

                $productsInsert[] = array(
                    'id' => $productSelected['id'],
                    'store_id' => $productSelected['store_id']
                );

                // Identificar de quais lojas são os produtos.
                if (
                    !in_array($productSelected['store_id'], $stores) &&
                    !in_array($productSelected['store_id'], $storesInPromotion)
                ) {
                    $stores[] = $productSelected['store_id'];
                }
            }

            // Entrará como se as lojas e produtos já tivesse aprovado a promoção.
            // Adicionar as lojas na tabela 'logistic_promotion_stores'.
            $createStores = $this->parseStores($stores, $promotionId, true);
            if (count($createStores)) {
                $this->model_promotionslogistic->insertStores($createStores);
            }
            // Adicionar os produtos na tabela 'logistic_promotion_products'.
            $createProduct = $this->parseProducts($productsInsert, $promotionId, true);
            if (count($createProduct)) {
                $this->model_promotionslogistic->insertProduct($createProduct);
            }
        }

        $info['status'] = $dataPromotion['status'];
        $this->model_promotionslogistic->updateInfo($info,$promotionId);
        
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        redirect('PromotionLogistic/', 'refresh');
    }

    function parseRegionEdit($data)
    {
        $region = array();
        foreach($data as $key => $value) {
            $region[] = $value['region'];
        }
        return  $region;
    }
    
    function parseCategoriesEdit($data)
    {   
        $categories = array();
        foreach($data as $key => $value) {
            $categories[] = (int)$value['id_categorie'];
        }
        return  $categories;
    }
    
    function parseCategoriesListProducts(array $data): string
    {   
        return '["' . implode('"],["', $data) . '"]';
    }

    public function parseListProduct(array $data): array
    {
        $return = array();
        foreach($data as $key => $value ){
            $checked = $value['active_status'] == 1 ? 'checked' : 'disabled' ;
            $return[] = array(
                "<a href='".base_url("products/update/{$value['id']}")."' target='_blank'>{$value['sku']}</a>",
                $value['name'],
                money($value['price']),
                !$value['dt_inactive'] ? "-" : $this->convertDatePtBr($value['dt_inactive']),
                "<input type='checkbox' data-product-id='{$value['id']}' $checked data-bootstrap-switch>"
            );
        }
        return $return;
    }

    public function parseInsertRegion(array $data, int $promotionId): array
    {
        $region = array();

        foreach($data['states'] as $key => $value) {
            $region[] = array(
                ' logistic_promotion_id'=> $promotionId
                ,'logistic_promotion_idregion' => $value
            );
        }
        return  $region;
    }
    
    private function parseCategories($data, $promotionId): array
    {
        $categories = array();

        foreach($data as $key => $value) {
            $categories[] = array(
                ' logistic_promotion_id'=> $promotionId
                ,'id_categorie' => $value
            );
        }
        return  $categories;
    }

    /**
     * @param   array   $data           Lojas que poderão participar da promoção.
     * @param   int     $promotionId    Código da promoção.
     * @param   bool    $active         Loja já entrará na promoção ativa.
     * @return  array                   Dados para criar as lojas que participarão da promoção.
     */
    private function parseStores(array $data, int $promotionId, bool $active = false): array
    {
        $stores = array();

        foreach($data as $value) {
            $stores[] = array(
                'logistic_promotion_id' => $promotionId,
                'id_stores'             => $value,
                'dt_added'              => dateNow()->format(DATETIME_INTERNATIONAL),
                'dt_update'             => dateNow()->format(DATETIME_INTERNATIONAL),
                'user'                  => $this->session->userdata('id'),
                'active_status'         => $active,
                'dt_inactive'           => null,
                'seller_accepted'       => $active,
                'date_seller_accepted'  => $active ? dateNow()->format(DATETIME_INTERNATIONAL) : null
            );
        }
        return  $stores;
    }

    /**
     * @param   array   $data           Dados do produto com código do produto e da loja.
     * @param   int     $promotionId    Código da promoção.
     * @param   bool    $active         Loja já entrará na promoção ativa.
     * @return  array                   Dados para criar as lojas que participarão da promoção.
     */
    private function parseProducts(array $data, int $promotionId, bool $active = false): array
    {
        $products = array();

        foreach($data as $value) {
            $products[] = array(
                'promotion_id'  => $promotionId,
                'product_id'    => $value['id'],
                'store_id'      => $value['store_id'],
                'dt_added'      => dateNow()->format(DATETIME_INTERNATIONAL),
                'dt_update'     => dateNow()->format(DATETIME_INTERNATIONAL),
                'user'          => $this->session->userdata('id'),
                'active_status' => $active,
                'dt_inactive'   => null
            );
        }
        return  $products;
    }

    private function parseInfo($data): array
    {
        return array(
            'name'=> $data['info']['name'],
            'dt_start'=> $this->convertDate($data['info']['dt_start'], $data['info']['start_hour']),
            'dt_end'=> $this->convertDate($data['info']['dt_end'], $data['info']['end_hour']),
            'dt_added'=> Date('Y-m_d h:m:s'),
            'dt_update'=> Date('Y-m_d h:m:s'),
            'user'=> $this->session->userdata('id'),
            'status'=> 1,
            'rule'=> $data['rules']['type'],
            'criterion_type'=> $data['criterion']['type'],
            'segment'=> $data['segment'],
            'price_type_value'=> $data['criterion']['price'],
            'product_value_mim'=> $data['criterion']['price_mim'],
            'produtct_amonut'=> $data['criterion']['amount'],
            'region'=> (int)$data['criterion']['region'],
            'deleted'=> 0
        );
    }

    private function parseRegion($data): array
    {
        foreach($data as $key => $value) {
           
            $region[$value['id_regiao']] = array(
                 "id" => $value['id_regiao']
                ,"name" => $value['regiao']
                ,"state" => $this->model_promotionslogistic->getStateRegions($value['id_regiao'])
            );
        }

        return $region;
    }

    private function convertDate($orgDate, $time): string
    {
        $date = str_replace('/', '-', $orgDate);
        $newDate = date("Y-m-d", strtotime($date));
        if ($time == '') {
            return $newDate . ' 00:00:00';
        } else {
            return $newDate . ' ' . $time . ':00';
        }
    }

    private function convertDatePtBr($orgDate): string
    {
        $date = str_replace('-', '/', $orgDate);
        return date("d/m/Y H:i:s", strtotime($date));
    }

    public function fetchProductToSelect(): CI_Output
    {
        $valueMin       = $this->postClean('valueMin',TRUE);
        $qtyMin         = $this->postClean('qtyMin',TRUE);
        $searchText     = $this->postClean('searchText',TRUE);
        $productNotIn   = $this->postClean('productNotIn',TRUE);

        $filter = array(
            'or_like' => array(
                'p.sku'         => $searchText,
                'p.name'        => $searchText,
                'p.description' => $searchText,
                'p.id'          => $searchText
            )
        );

        if ($productNotIn && count($productNotIn)) {
            $filter['where_not_in'] = array(
                'p.id' => $productNotIn
            );
        }

        $data   = $this->model_promotionslogistic->getProducts($valueMin, $qtyMin, null, '', null, $filter);
        $result = array();

        foreach ($data as $key => $value) {
            $result[$key] = $this->formatDataProductToTableCreatePromotion($value);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * Formata os dados dos produtos para adicionar na tabela, de criação de nova promoção.
     *
     * @param   array   $value  Dados do produto.
     * @param   bool    $btnAdd Botão será de adicionar.
     * @return  array
     */
    private function formatDataProductToTableCreatePromotion(array $value, bool $btnAdd = true): array
    {
        $button = $btnAdd ?
            '<button type="button" class="btn btn-default btnAddProduct" data-toggle="tooltip" product-id="'.$value['id'].'" title="'.lang('application_add_product').'"><i class="fa fa-plus"></i></button>':
            '<button type="button" class="btn btn-default btnRmProduct" data-toggle="tooltip" product-id="'.$value['id'].'" title="'.lang('application_remove_product').'"><i class="fa fa-minus"></i></button>';

        return array(
            $value['id'],
            $value['name'],
            $value['sku'],
            $value['price'],
            $value['qty'],
            $value['store_name'],
            $button
        );
    }

    public function selectProductCreatePromotion(): CI_Output
    {
        $valueMin   = $this->postClean('valueMin',TRUE);
        $qtyMin     = $this->postClean('qtyMin',TRUE);
        $product   = $this->postClean('product',TRUE);

        if (!$this->model_promotionslogistic->getProducts(
            $valueMin,
            $qtyMin,
            null,
            '',
            $product
        )) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'message' => 'Produto não pode ser adicionado. Alguma informação foi atualizada, atualize a lista de produtos.'
                )));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Produto adicionado.'
            )));
    }

}