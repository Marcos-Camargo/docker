<?php
/*
 SW Serviços de Informática 2019
 
 Controller de de erros de transformação 
 
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class ErrorsTransformation extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_errors_tranformation');

        $this->load->model('model_products');
        $this->load->model('model_errors_transformation');
        $this->load->model('model_integrations');
        $this->load->model('model_product_error');

        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $this->data['mycontroller']=$this;

    }

    public function index()
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['names_marketplaces'] = $this->model_integrations->getNamesIntegrationsbyCompanyStore($this->data['usercomp'],$this->data['userstore']);
        $this->data['stores'] = $this->model_product_error->getAllStores();

        $this->render_template('errorstransformation/index', $this->data);
    }

    public function markSelect()
    {
        if(!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!is_null($this->postClean('id'))) {
            $ids = $this->postClean('id');
            if (!is_null($this->postClean('select'))) {
                foreach ($ids as $k => $id) {
                    $this->model_errors_transformation->setStatus($id,"1");
                }
            }
            if (!is_null($this->postClean('deselect'))) { // Nao vai acontecer....
                foreach ($ids as $k => $id) {
                    $this->model_errors_transformation->setMarcaInt($id,null);
                }
            }
        }

        $this->render_template('errorstransformation/index', $this->data);
    }

    public function fetchErrorsData($isMkt = false)
    {
        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];

        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
                $procura = " AND ( e.skumkt like '%".$busca['value']."%' 
                OR e.prd_id like '%".$busca['value']."%'
                OR s.name like '%".$busca['value']."%'
                OR e.int_to like '%".$busca['value']."%'
                OR e.step like '%".$busca['value']."%'
                OR e.message like '%".$busca['value']."%'
                OR e.id like '%".$busca['value']."%' ) ";
            }
        }

        if (!empty($postdata['name'])) {
            $procura .= " AND p.name like '%" . trim($postdata['name']) . "%' ";
        }
        if (!empty($postdata['store'])) {
            $procura .= " AND s.id = '{$postdata['store']}' ";
        }

        if (!empty($postdata['marketplace'])) {
            $procura .= " AND int_to = '{$postdata['marketplace']}' ";
        }

        if(!empty($postdata['filter']) || !empty($postdata['pendencia'])){
            switch ($postdata['filter'] | $postdata['pendencia']) {
                case 1:
                    $procura = ' AND (p.principal_image is NULL or p.principal_image = "") ';
                    break;
                case 2:
                    $value = '[""]';
                    $procura .= " AND p.category_id = '$value' ";
                    break;
                case 3:
                    $procura .= ' AND (p.peso_bruto = "" OR p.largura = "" OR p.altura = "" OR p.profundidade = "" OR p.products_package = "" OR p.peso_liquido = "" )';
                    break;
                case 4:
                    $procura .= ' AND p.price = "" ';
                    break;
                case 5:
                    $procura .= ' AND p.description = "" ';
                    break;
                default:
                    $procura .= '';
                    break;
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('','','p.name','e.skumkt','s.name','e.int_to','e.step','e.date_create','e.message','');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }

        $result = [];
        $data = $this->model_errors_transformation->getDataActiveView($ini, $sOrder, $procura);

        $i = 0;
        $filtered = $this->model_errors_transformation->getDataActiveViewCount($procura);

        foreach ($data as $key => $value) {

            if ((!is_null($value['principal_image'])) && ($value['principal_image'] != '')) {
                $img = '<img src="' . $value['principal_image'] . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $img = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }

            $i++;
            $buttons = '<a target="__blank" href="'.base_url('products/update/'.$value['prd_id']).'" class="btn"><i class="fa-solid fa-pen-to-square"></i></a>';

            $result[$key] = array(
                $value['id'],
                $img,
                $value['skumkt'],
                $value['name'],
                $value['store'],
                $value['int_to'],
                $value['step'],
                date('d/m/Y H:i:s', strtotime($value['date_create'])),
                $value['message'],
                $buttons
            );

        } // /foreach
        if ($filtered==0) {$filtered = $i;}
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_errors_transformation->getDataActiveViewCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function totalNoImage(){
        $this->data['products_without_image'] = $this->model_errors_transformation->getProductsWithoutImage();
        print $this->data['products_without_image'][0]['total'];
    }
    public function totalNoCategory(){
        $this->data['noCategory'] = $this->model_errors_transformation->getProductsWithoutCategory();
        print $this->data['noCategory'][0]['total'];
    }
    public function totalNoDimensions(){
        $this->data['noDimensions'] = $this->model_errors_transformation->getProductsWithoutDimensions();
        print $this->data['noDimensions'][0]['total'];
    }
    public function totalNoPrice(){
        $this->data['noPrice'] = $this->model_errors_transformation->getProductsWithoutPrice();
        print $this->data['noPrice'][0]['total'];
    }
    public function totalNoDescription(){
        $this->data['noDescriptions'] = $this->model_errors_transformation->getProductsWithoutDescription();
        print $this->data['noDescriptions'][0]['total'];
    }

}