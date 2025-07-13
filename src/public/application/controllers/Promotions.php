<?php
/*

*/
defined('BASEPATH') or exit('No direct script access allowed');

class Promotions extends Admin_Controller
{
    private $sellercenter_name;

    public function __construct()
    {
        parent::__construct();

        if (!in_array($this->router->method, ['edit', 'fetchProductsPromotionListData']))
        {
            redirect('dashboard', 'refresh');
        }

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_promotions');

        $this->load->model('model_promotions');
        $this->load->model('model_products');
        $this->load->model('model_billet');
        $this->load->model('model_stores');
    }

    public function index()
    {

        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
	    $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');

        $this->data['filters'] = array(
            'filter_sku' => '',
            'filter_store' => '',
            'filter_start_date' => '',
            'filter_end_date' => '',
            'filter_status' => array(1, 3, 4),
            'filter_type' => array(3),
        );

        $this->data['page_title'] = $this->lang->line('application_manage_promotions');
        $this->data['gsoma'] = $valor['status'];
		$this->data['nmundo'] = $valorNM['status'];

        $this->render_template('promotions/list', $this->data);
    }

    public function fetchPromotionsDataNew($tipoPromo = null)
    {

        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($tipoPromo == null) {
            redirect('dashboard', 'refresh');
        }

        $promotions = $this->model_promotions->getPromotionsGroupData($tipoPromo);

        $result = array();

        foreach ($promotions as $key => $value) {

            // button
            $buttons = '';

            if (in_array('createPromotions', $this->permission)) {

                if (in_array('updatePromotionsShare', $this->permission)) {
                    if ($value['ativo'] == "Ativo") {
                        $buttons .= ' <button type="button" class="btn btn-default" onclick="desativarPromocao(\'' . $value['id'] . '\')"><i class="fa  fa-minus-square"></i></button>';
                    } else {
                        $buttons .= ' <button type="button" class="btn btn-default" onclick="desativarPromocao(\'' . $value['id'] . '\')"><i class="fa fa-check-square"></i></button>';
                    }
                }
                 
                $buttons .= '<a href="' . base_url('promotions/edit/' . $value['id']) . '" class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_edit') . '"><i class="fa fa-pencil-square-o"></i></a>';


            }

            $result['data'][$key] = array(
                $value['id'],
                $value['nome'],
                $value['data_inicio'],
                $value['data_fim'],
                $value['data_criacao'],
                $value['ativo'],
                $buttons
            );

        }
        if (!$result) {
            $result['data'][0] = array("", "", "", "", "", "", "");
        }

        echo json_encode($result);

    }

    public function createpromo()
    {
        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['permissao'] = $this->data['usergroup'];

        $arrayEdit['lote'] = "";
        $arrayEdit['nome'] = "";
        $arrayEdit['descricao'] = "";
        $arrayEdit['marketplace'] = "";
        $arrayEdit['tipo_promocao'] = "";
        $arrayEdit['ativo'] = "";
        $arrayEdit['data_inicio'] = "";
        $arrayEdit['data_inicio_hora'] = "";
        $arrayEdit['data_fim'] = "";
        $arrayEdit['data_fim_hora'] = "";
        $arrayEdit['categoryN1'] = "";
        $arrayEdit['categoryN2'] = "";
        $arrayEdit['categoryN3'] = "";
        $arrayEdit['percentual_seller'] = "";
        $arrayEdit['lock_edit_promocao'] = "";

        $categoriaN1 = $this->model_promotions->getCategories(1);
        $categoriaN2 = $this->model_promotions->getCategories(2);
        $categoriaN3 = $this->model_promotions->getCategories(3);

        $group_data1 = $this->model_billet->getMktPlacesData();
        $this->data['mktplaces'] = $group_data1;
        $this->data['hdnLote'] = date('YmdHis') . rand(1, 1000000);
        $this->data['hdnEdit'] = "0";
        $this->data['promocao'] = $arrayEdit;

        $this->data['categoriaN1'] = $categoriaN1;
        $this->data['categoriaN2'] = $categoriaN2;
        $this->data['categoriaN3'] = $categoriaN3;

        if (in_array('updatePromotionsShare', $this->permission)) {
            $this->data['promocao_compartilhada'] = true;
        }else{
            $this->data['promocao_compartilhada'] = false;
        }
        
        $this->data['page_title'] = $this->lang->line('application_manage_promotions');
        $this->render_template('promotions/createnew', $this->data);
    }

    public function edit($id)
    {

        if (!in_array('updatePromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $promotions = $this->model_promotions->getPromotionsGroupData(null, $id);

        $atualiza = $this->model_promotions->getPromotionsDataToTemp($promotions[0]['lote'], $id);

        if ($promotions[0]['categoria_n1'] <> "") {
            $categoriaN1[0]['categoryN1'] = $promotions[0]['categoria_n1'];
            $arrayEdit['categoryN1'] = $promotions[0]['categoria_n1'];
        } else {
            $categoriaN1 = $this->model_promotions->getCategories(1);
            $arrayEdit['categoryN1'] = "";
        }

        if ($promotions[0]['categoria_n2'] <> "") {
            $categoriaN2[0]['categoryN2'] = $promotions[0]['categoria_n2'];
            $arrayEdit['categoryN2'] = $promotions[0]['categoria_n2'];
        } else {
            $categoriaN2 = $this->model_promotions->getCategories(2);
            $arrayEdit['categoryN2'] = "";
        }

        if ($promotions[0]['categoria_n3'] <> "") {
            $categoriaN3[0]['categoryN3'] = $promotions[0]['categoria_n3'];
            $arrayEdit['categoryN3'] = $promotions[0]['categoria_n3'];
        } else {
            $categoriaN3 = $this->model_promotions->getCategories(3);
            $arrayEdit['categoryN3'] = "";
        }


        $arrayEdit['lote'] = $promotions[0]['lote'];
        $arrayEdit['nome'] = $promotions[0]['nome'];
        $arrayEdit['descricao'] = $promotions[0]['descricao'];
        $arrayEdit['marketplace'] = $promotions[0]['marketplace'];

        $arrayEdit['percentual_seller'] = $promotions[0]['percentual_seller'];

        if ($promotions[0]['tipo_promocao'] == "2") {
            $arrayEdit['tipo_promocao'] = "checked";
            if (in_array('updatePromotionsShare', $this->permission)) {
                $arrayEdit['lock_edit_promocao'] = "";
            }else{
                $arrayEdit['lock_edit_promocao'] = "readonly=\"true\"";
            }
        } else {
            $arrayEdit['tipo_promocao'] = "";
            $arrayEdit['lock_edit_promocao'] = "";
        }

        if ($promotions[0]['ativo_id'] != 0) {
            $arrayEdit['ativo'] = "checked";
        } else {
            $arrayEdit['ativo'] = "";
        }

        $dataInicio = explode(" ", $promotions[0]['data_inicio']);
        $dataFim = explode(" ", $promotions[0]['data_fim']);

        $arrayEdit['data_inicio'] = $dataInicio[0];
        $arrayEdit['data_inicio_hora'] = $dataInicio[1];
        $arrayEdit['data_fim'] = $dataFim[0];
        $arrayEdit['data_fim_hora'] = $dataFim[1];

        $group_data1 = $this->model_billet->getMktPlacesData();
        $this->data['mktplaces'] = $group_data1;
        $this->data['hdnLote'] = $promotions[0]['lote'];
        $this->data['hdnEdit'] = $promotions[0]['id'];
        $this->data['promocao'] = $arrayEdit;

        $this->data['categoriaN1'] = $categoriaN1;
        $this->data['categoriaN2'] = $categoriaN2;
        $this->data['categoriaN3'] = $categoriaN3;

        $this->data['permissao'] = $this->data['usergroup'];

        if (in_array('updatePromotionsShare', $this->permission)) {
            $this->data['promocao_compartilhada'] = true;
        }else{
            $this->data['promocao_compartilhada'] = false;
        }
        

        $this->data['page_title'] = $this->lang->line('application_manage_promotions');
        $this->render_template('promotions/createnew', $this->data);

    }

    public function insertpromo()
    {

        $inputs = $this->postClean(NULL,TRUE);

        $retStores = $this->model_promotions->getFirstProductTemp($inputs['hdnLote']);
        if($retStores){
            $retStore = $retStores[0];
        }else{
            $retStore['store_id'] = $this->data['userstore'];
            $retStore['company_id'] = $this->data['usercomp'];
        }
        $inputs['store_id'] = $retStore['store_id'];
        $inputs['company_id'] = $retStore['company_id'];


        if ($inputs['hdnEdit'] == "0") {
            $promocao = $this->model_promotions->insertpromotiongroup($inputs);
        } else {
            $productsPromotion  = $this->model_promotions->getProductPromotionByLote($inputs['hdnLote']);
            $promotionGroup     = $this->model_promotions->editpromotiongroup($inputs);
            $promotion          = $this->model_promotions->setPromotionByLote($inputs);
            $promocao           = $inputs['hdnEdit'];

            // atualiza data dos produtos
            foreach ($productsPromotion as $prd)
                $this->model_products->setDateUpdatedProduct($prd['product_id'], null, __METHOD__);
        }
        
        if($promocao){

            //Desativa os produtos duplicados em outras promoções
            $inputs['id_promocao_group'] = $promocao;
            $saidaDesativa = $this->model_promotions->desativaprodutospromocoes($inputs);
            echo "0;".$promocao;
        }else{
            echo "1;0";
        }


    }

    public function fetchProductsListData()
    {
        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

//        dd($_POST);
//        "start" => "20"
//        "length" => "10"

        /** @var $inputs CI_Input */
        $inputs = $this->input;

        $produtos = $this->model_promotions->getMyProductsPromotionsData($inputs->get(),$inputs->post());

        $count = $produtos['count'];
        $produtos = $produtos['data'];

        foreach ($produtos as $key => $value) {

            // button
            $buttons = '';

            if (in_array('createPromotions', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="incluirPedidoPromocao(\'' . $value['id'] . '\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-plus"></i></button>';
            }

            $result['data'][$key] = array(
                $value['id'],
                $value['store'],
                $value['sku'],
                $value['name'],
                $value['categoryN1'],
                $value['categoryN2'],
                $value['categoryN3'],
                $value['qty'],
                number_format($value['price'], 2),
                $buttons
            );
        } 
        
        if (empty($result)) {
            $result['data'][0] = array("", "", "", "", "", "", "", "", "", "");
        }

        $count = (!$count) ? 0 : $count;

        $result = [
            'draw' => $inputs->post()['draw'],
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => $result['data']
        ];

        echo json_encode($result);
        
        
    }

    public function fetchProductsPromotionListData($lote = null, $seller = null)
    {

        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($lote == null) {
            redirect('dashboard', 'refresh');
        }

        /*$result['data'][0] = array("","","","","","","","","","","","");
        echo json_encode($result);
        die;*/

        $produtos = $this->model_promotions->getPromotionsTemp($lote);

        $result = array();

        foreach ($produtos as $key => $value) {

            $buttons = '';

            if ($value['ativoTemp'] == "1" || $value['ativoTemp'] == "2") {

                $inputPreco = $value['precoNovo'];
                $inputQuantidade = $value['qtdPromo'];
                $inputDesconto = $value['percentualDesconto'] . "%";

                //$buttons .= ' <button type="button" class="btn btn-default" onclick="removerPedidoPromocao(\'' . $value['id'] . '\')"><i class="fa fa-trash"></i></button>';

            } else {

                if ($seller <> null) {

                    $precoCalculadoNovo = number_format($value['price'] - ($value['price'] * ($seller / 100)), 2);

                    $inputPreco = '<input type="text" class="form-control" id="preco_' . $value['id'] . '" name="preco_' . $value['id'] . '" value="' . $precoCalculadoNovo . '" readonly="readonly" >';
                    $inputDesconto = '<input type="text" class="form-control" id="qtd_percent_' . $value['id'] . '" name="qtd_percent_' . $value['id'] . '" value="' . $seller . '%" placeholder="%" readonly="readonly" >';
                    $inputQuantidade = '<input type="number" class="form-control" id="qtd_' . $value['id'] . '" name="qtd_' . $value['id'] . '" >';

                } else {

                    $inputPreco = '<input type="text" class="form-control" id="preco_' . $value['id'] . '" name="preco_' . $value['id'] . '" >';
                    $inputDesconto = '<input type="text" class="form-control" id="qtd_percent_' . $value['id'] . '" name="qtd_percent_' . $value['id'] . '" placeholder="%">';
                    $inputQuantidade = '<input type="number" class="form-control" id="qtd_' . $value['id'] . '" name="qtd_' . $value['id'] . '" >';

                }


                //$buttons .= ' <button type="button" class="btn btn-default" onclick="aprovarPedidoPromocao(\'' . $value['id'] . '\')"><i class="fa fa-check"></i></button>';
                //$buttons .= ' <button type="button" class="btn btn-default" onclick="removerPedidoPromocao(\'' . $value['id'] . '\')"><i class="fa fa-trash"></i></button>';
            }


            $result['data'][$key] = array(
                $value['id'],
                $value['store'],
                $value['sku'],
                $value['name'],
                $value['category'],
                number_format(empty($value['price']) ? 0 : $value['price'], 2),
                $inputPreco,
                $inputDesconto,
                $value['qty'],
                $inputQuantidade,
                $value['start_date'],
                $value['end_date'],
                $buttons
            );
        } // /foreach


        if (empty($result)) {
            $result['data'][0] = array("", "", "", "", "", "", "", "", "", "", "", "", "");
        }

        echo json_encode($result);


    }

    public function checkaddproductpromotiontemp()
    {

        
        $inputs = $this->postClean(NULL,TRUE);

        if ($inputs['hdnLote'] <> "" && $inputs['produto'] <> "") {
            $produtos = $this->model_promotions->checkproductacitveotherpromotion($inputs);
            
            if ($produtos > 0) {
                echo true;
            }else {
                echo false;
            }
        } else {
            echo false;
        }
    }

    public function addProductPromotionTemp()
    {

        $inputs = $this->postClean(NULL,TRUE);

        if ($inputs['hdnLote'] <> "" && $inputs['produto'] <> "") {
            $produtos = $this->model_promotions->insertPromotionTemp($inputs);
            if ($produtos) {
                echo true;
            }
        } else {
            echo false;
        }
    }
    
    

    public function aproveproductpromotion()
    {

        $inputs = $this->postClean(NULL,TRUE);

        if ($inputs['hdnLote'] <> "" && $inputs['produto'] <> "" && ((is_numeric($inputs['desconto']) && $inputs['desconto'] > 0 && $inputs['desconto'] <= 100) || (is_numeric($inputs['preco']) && $inputs['preco'] > 0)) ) {
            $produtos = $this->model_promotions->AproveProductsFromPromotionTemp($inputs);
            if ($produtos) {
                echo true;
            }
        } else {
            echo false;
        }
    }

    public function removeproductpromotion()
    {
        //desabilitando features de promocoes
        return false;

        $inputs = $this->postClean(NULL,TRUE);

        if ($inputs['hdnLote'] <> "" && $inputs['produto'] <> "") {
            $produtos = $this->model_promotions->removeProductsFromPromotionTemp($inputs['produto'], $inputs['hdnLote']);
            if ($produtos) {
                echo true;
            }
        } else {
            echo false;
        }
    }

    public function desativarpromocao()
    {

        $inputs = $this->postClean(NULL,TRUE);

        if ($inputs['id'] <> "") {
            $produtos = $this->model_promotions->desativarativarpromocao($inputs['id']);
            if ($produtos) {
                echo true;
            }
        } else {
            echo false;
        }

    }

    public function addskumassivo()
    {

        $inputs = $this->postClean(NULL,TRUE);
        if ($inputs['hdnLote'] <> "" && $inputs['SKU'] <> "") {
            $produtos = $this->model_promotions->addskumassivo($inputs);
            if ($produtos) {
                echo true;
            }
        } else {
            echo false;
        }

    }
    
    public function teste(){
        
        $this->model_promotions->activateAndDeactivate();
        
    }

    /*******************************************************************************************************************************/

    public function filter()
    {
        if (!in_array('viewPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_manage_promotions_filtered');
        $promotionsfilter = "";
        $filters = array(
            'filter_sku' => '',
            'filter_store' => '',
            'filter_start_date' => '',
            'filter_end_date' => '',
            'filter_status' => array(1, 3, 4),
            'filter_type' => array(3),
        );
        if (!is_null($this->postClean('do_filter'))) {

            if ((!is_null($this->postClean('filter_store'))) && ($this->postClean('filter_store_op') != "0")) {
                $store = $this->postClean('filter_store');
                if ($this->postClean('filter_store_op') == 'LIKE') {
                    $store = '%' . $this->postClean('filter_store') . '%';
                }
                $promotionsfilter .= " AND s.name " . $this->postClean('filter_store_op') . " '" . $store . "'";
                $filters['filter_store'] = $store;
                $filters['filter_store_op'] = $this->postClean('filter_store_op');
            }
            if ((!is_null($this->postClean('filter_sku'))) && ($this->postClean('filter_sku_op') != "0")) {
                $sku = $this->postClean('filter_sku');
                if ($this->postClean('filter_sku_op') == 'LIKE') {
                    $sku = '%' . $this->postClean('filter_sku') . '%';
                }
                $promotionsfilter .= " AND pr.sku " . $this->postClean('filter_sku_op') . " '" . $sku . "'";
                $filters['filter_sku'] = $sku;
                $filters['filter_sku_op'] = $this->postClean('filter_sku_op');
            }
            if ((!is_null($this->postClean('filter_start_date'))) && ($this->postClean('filter_start_date_op') != "0")) {
                $promotionsfilter .= " AND pr.start_date " . $this->postClean('filter_start_date_op') . " '" . $this->convertDate($this->postClean('filter_start_date'), date("H:i", $this->postClean('filter_start_date'))) . "'";
                $filters['filter_start_date'] = $this->postClean('filter_start_date');
                $filters['filter_start_date_op'] = $this->postClean('filter_start_date_op');
            }
            if ((!is_null($this->postClean('filter_end_date'))) && ($this->postClean('filter_end_date_op') != "0")) {
                $promotionsfilter .= " AND pr.end_date " . $this->postClean('filter_end_date_op') . " '" . $this->convertDate($this->postClean('filter_end_date'), date("H:i", $this->postClean('filter_end_date'))) . "'";
                $filters['filter_end_date'] = $this->postClean('filter_end_date');
                $filters['filter_end_date_op'] = $this->postClean('filter_end_date_op');
            }
            $fil_type = '';
            $filter_type = $this->postClean('filter_type');
            foreach ($filter_type as $option_type) {
                if (($option_type == 3) || ($option_type == 1)) {
                    $fil_type .= " OR pr.type = 1";
                }
                if (($option_type == 3) || ($option_type == 2)) {
                    $fil_type .= " OR pr.type = 2";
                }
            }
            if ($fil_type != "") {
                $promotionsfilter .= ' AND (' . substr($fil_type, 3) . ')';
            }

            $fil_sta = '';
            $filter_status = $this->postClean('filter_status');
            foreach ($filter_status as $option_status) {
                $fil_sta .= " OR pr.active = " . $option_status;
            }
            if ($fil_sta != "") {
                $promotionsfilter .= ' AND (' . substr($fil_sta, 3) . ')';
            }

            // $this->session->set_flashdata('success',$promotionsfilter);
        }

        $this->session->set_userdata(array('promotionsfilter' => $promotionsfilter));
        $this->data['filters'] = $filters;
        $this->render_template('promotions/index', $this->data);
    }

    public function fetchPromotionsData()
    {
        if (!in_array('viewPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'] ?: 0;
        $draw = $postdata['draw'];

        // get_instance()->log_data('Products','fetchsearch',print_r($postdata,true));
        $busca = $postdata['search'];
        $procura = '';

        if ($busca['value']) {
            if (strlen($busca['value']) > 1) {  // Garantir no minimo 2 letras
                $procura = " AND ( pr.id like '%" . $busca['value'] . "%' OR ";
                $procura .= " p.sku like '%" . $busca['value'] . "%' OR ";
                $procura .= " p.name like '%" . $busca['value'] . "%' OR ";
                $procura .= " s.store like '%" . $busca['value'] . "%' OR ";
                $procura .= " pr.start_date like '%" . $busca['value'] . "%' OR ";
                $procura .= " pr.end_date like '%" . $busca['value'] . "%' ) ";
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('id', 'type', 'p.sku', 'p.name', 's.store', 'p.price', 'pr.price', '', 'p.qty', 'pr.qty', 'start_date', 'end_date', 'active');
            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
        }

        $result = array();

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_promotions->getPromotionsViewData($ini, $procura, $sOrder);

        $i = 0;
        $filtered = $this->model_promotions->getPromotionsViewCount($procura);

        foreach ($data as $key => $value) {
            $i++;

            $type = ($value['type'] != 1) ? $this->lang->line('application_promotion_type_date') : $this->lang->line('application_promotion_type_stock');
            $buttons = '';

            if ((in_array('updatePromotions', $this->permission)) && (($value['active'] == 3) || ($value['active'] == 4))) { // posso editar se está agendada ou em aprovação
                $buttons .= '<a href="' . base_url('promotions/update/' . $value['id']) . '" class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_edit') . '"><i class="fa fa-pencil-square-o"></i></a>';
            }
            if ((in_array('deletePromotions', $this->permission)) && ($value['active'] == 1)) { // Posso inativar se estiver ativo
                $buttons .= '<button class="btn btn-danger" onclick="inactivePromotion(event,' . $value['id'] . ')" data-toggle="tooltip" title="' . $this->lang->line('application_inactivate') . '"><i class="fa fa-minus-square"></i></button>';
            }
            if ((in_array('updatePromotions', $this->permission)) && ($value['active'] == 3)) {  // Posso aprovar se está em aprovação
                $buttons .= '<button class="btn btn-success" onclick="approvePromotion(event,' . $value['id'] . ')" data-toggle="tooltip" title="' . $this->lang->line('application_approve') . '"><i class="fa fa-check"></i></button>';
            }
            if ((in_array('deletePromotions', $this->permission)) && (($value['active'] == 3) || ($value['active'] == 4))) { // posso deletar se está em aprovação ou agendado
                $buttons .= '<button class="btn btn-warning" onclick="deletePromotion(event,' . $value['id'] . ')" data-toggle="tooltip" title="' . $this->lang->line('application_delete') . '"><i class="fa fa-trash"></i></button>';
            }

            $linkprd = '<a href="' . base_url() . 'products/update/' . $value['product_id'] . '" target="_blank">' . $value['product'] . '</a>';
            if ($value['active'] == 1) {
                $status = '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
            } elseif ($value['active'] == 3) {
                $status = '<span class="label label-warning">' . $this->lang->line('application_approval') . '</span>';
            } elseif ($value['active'] == 4) {
                $status = '<span class="label label-info">' . $this->lang->line('application_scheduled') . '</span>';
            } else {
                $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
            }

            $result[$key] = array(
                $value['id'],
                $type,
                $value['sku'],
                $linkprd,
                $value['store'],
                $this->formatprice($value['price_from']),
                $this->formatprice($value['price']),
                number_format((1 - $value['price'] / $value['price_from']) * 100, 2) . "%",
                $value['stock'],
                $value['qty'] . ' / ' . $value['qty_used'],
                date('d/m/Y h:i', strtotime($value['start_date'])),
                date('d/m/Y h:i', strtotime($value['end_date'])),
                $status,
                $buttons
            );

        } // /foreach
        if ($filtered == 0) $filtered = $i;
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_promotions->getPromotionsViewCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );

        echo json_encode($output);
    }

    public function aproveAllCreation()
    {
        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $company_id = $this->postClean('company_id');
        $store_id = $this->postClean('store_id');
        if ($this->model_promotions->aproveAll($company_id, $store_id)) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        };
    }

    public function deletePromotionCreation()
    {
        if (!in_array('deletePromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id');
        if ($this->model_promotions->remove($id)) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        };
    }

    public function approvePromotion()
    {
        if (!in_array('updatePromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id_approve');
        if ($this->model_promotions->chanceActive($id, '4')) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        };
        if ($this->postClean('id_product')) { // também é chamado do produtos então volta para lá
            redirect('products/update/' . $this->postClean('id_product'), 'refresh');
        } else {
            redirect('promotions/', 'refresh');
        }
    }

    public function inactivePromotion()
    {
        if (!in_array('deletePromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id_inactive');
        $product_id = $this->postClean('id_product');
        if ($this->model_promotions->chanceActive($id, '2', $product_id)) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        };
        if ($this->postClean('id_product')) { // também é chamado do produtos então volta para lá
            redirect('products/update/' . $this->postClean('id_product'), 'refresh');
        } else {
            redirect('promotions/', 'refresh');
        }

    }

    public function removePromotion()
    {
        if (!in_array('deletePromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id_remove');
        if ($this->model_promotions->remove($id)) {
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        };
        if ($this->postClean('id_product')) { // também é chamado do produtos então volta para lá
            redirect('products/update/' . $this->postClean('id_product'), 'refresh');
        } else {
            redirect('promotions/', 'refresh');
        }
    }

    public function create()
    {
        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->form_validation->set_rules('start_date', $this->lang->line('application_start_date'), 'trim|required');
        $this->form_validation->set_rules('end_date', $this->lang->line('application_end_date'), 'trim|required');

        $productsfilter = '';
        $this->session->set_userdata(array('createPromo' => false));
        if ($this->form_validation->run() == TRUE) {
            // true case
            $this->data['porestoque'] = false;
            if ($this->postClean('typepromo') == false) {
                $productsfilter .= " AND p.qty >99 ";
                $this->data['porestoque'] = true;
            }
            if ($this->postClean('category') != "") {
                $category = $this->postClean('category');
                $productsfilter .= " AND category_id = '[\"" . $category . "\"] '";
                $this->data['category'] = $this->postClean('category');;
            }
            if ($this->postClean('brands') != "") {
                $productsfilter .= " AND brand_id = '[\"" . $this->postClean('brands') . "\"]' ";
                $this->data['brands'] = $this->postClean('brands');
            }
            if (($this->postClean('sku') != "") && ($this->postClean('sku_op') != "0")) {
                $sku = $this->postClean('sku');
                if ($this->postClean('sku_op') == 'LIKE') {
                    $sku = '%' . $sku . '%';
                }
                $productsfilter .= " AND sku " . $this->postClean('sku_op') . " '" . $sku . "'";
                $this->data['sku'] = $this->postClean('sku');;
                $this->data['sku_op'] = $this->postClean('sku_op');
            }
            if (($this->postClean('product_name') != "") && ($this->postClean('product_name_op') != "0")) {
                $product_name = $this->postClean('product_name');
                if ($this->postClean('product_name_op') == 'LIKE') {
                    $product_name = '%' . $product_name . '%';
                }
                $productsfilter .= " AND p.name " . $this->postClean('product_name_op') . " '" . $product_name . "'";
                $this->data['product_name'] = $this->postClean('product_name');;
                $this->data['product_name_op'] = $this->postClean('product_name_op');
            }
            if (($this->postClean('id') != "") && ($this->postClean('id_op') != "0")) {
                $id = $this->postClean('id');
                if ($this->postClean('id_op') == 'LIKE') {
                    $id = '%' . $id . '%';
                }
                $productsfilter .= " AND p.id " . $this->postClean('id_op') . " '" . $id . "'";
                $this->data['id'] = $this->postClean('id');;
                $this->data['id_op'] = $this->postClean('id_op');
            }
            if (($this->postClean('EAN') != "") && ($this->postClean('EAN_op') != "0")) {
                $EAN = $this->postClean('EAN');
                if ($this->postClean('EAN_op') == 'LIKE') {
                    $EAN = '%' . $EAN . '%';
                }
                $productsfilter .= " AND EAN " . $this->postClean('EAN_op') . " '" . $EAN . "'";
                $this->data['EAN'] = $this->postClean('EAN');;
                $this->data['EAN_op'] = $this->postClean('EAN_op');
            }
            $this->session->set_userdata(array('createPromo' => true));
            $this->session->set_userdata(array('productsfilter' => $productsfilter));

            $this->data['promotions'] = $this->model_promotions->getPromotionsOnCreationData();
            $this->data['products'] = $this->model_products->getMyProductsPromotionsData();
        }
        // $this->data['productsfilter']= $productsfilter;
        $this->data['categories'] = $this->model_products->getProductsCategoriesData();
        $this->data['brands'] = $this->model_products->getProductsBrandsData();
        $this->render_template('promotions/create', $this->data);


    }

    function price_check($price, $price_from)
    {
        if ($price >= $price_from) {
            $this->form_validation->set_message('price_check', 'O preço do produto em promoção deve ser menor que o preço original do produto');
            return false;
        }
        return true;
    }

    function qty_check($qty, $stock)
    {
        if ($qty >= $stock) {
            $this->form_validation->set_message('qty_check', 'A quantidade em promoção não pode ser maior que o estoque do produto');
            return false;
        }
        if ($qty < 50) {
            $this->form_validation->set_message('qty_check', 'A quantidade mínima de produtos em promoção é de 50 unidades.');
            return false;
        }
        return true;
    }

    public function update($id)
    {
        if (!in_array('updatePromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if ($id == "") {
            redirect('promotions', 'refresh');
        }
        $promotion = $this->model_promotions->verifyPromotionOfStore($id);

        if (!$promotion) {
            redirect('promotions', 'refresh');
        }

        $promotion['type'] = $promotion['type'] - 1;
        $promotion['start_time'] = date('H:i', strtotime($promotion['start_date']));
        $promotion['end_time'] = date('H:i', strtotime($promotion['end_date']));
        $promotion['start_date'] = date('d/m/Y', strtotime($promotion['start_date']));
        $promotion['end_date'] = date('d/m/Y', strtotime($promotion['end_date']));

        $this->form_validation->set_rules('start_date', $this->lang->line('application_start_date'), 'trim|required');
        $this->form_validation->set_rules('end_date', $this->lang->line('application_end_date'), 'trim|required');

        $this->form_validation->set_rules('price', $this->lang->line('application_price_sale'), 'trim|required|greater_than[0]|callback_price_check[' . $promotion['price_from'] . ']');
        if ($this->postClean('typepromo') == 0) {
            $this->form_validation->set_rules('qty', $this->lang->line('application_promotion_qty'), 'trim|required|callback_qty_check[' . $promotion['stock'] . ']');
        }
        if ($this->form_validation->run() == TRUE) {
            $start_date = $this->convertDate($this->postClean('start_date'), "00:00");
            if ($this->postClean('typepromo') == 1) {
                $qty = NULL;
                $qty_used = NULL;
            } else {
                $qty = $this->postClean('qty');
                $qty_used = 0;
            }
            // true case
            $rec = array(
                'id' => $promotion['id'],
                'product_id' => $promotion['product_id'],
                'active' => (strtotime($start_date) < strtotime(date("d-m-Y H:i:s"))) ? 1 : 4,
                'type' => $this->postClean('typepromo') + 1,
                'qty' => $qty,
                'qty_used' => $qty_used,
                'price' => $this->postClean('price'),
                'start_date' => $this->convertDate($this->postClean('start_date'), $this->postClean('start_time')),
                'end_date' => $this->convertDate($this->postClean('end_date'), $this->postClean('end_time')),
                'store_id' => $promotion['store_id'],
                'company_id' => $promotion['company_id'],
            );
            $update = $this->model_promotions->update($rec, $id);
            if ($update == true) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('promotions/', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('promotions/edit', 'refresh');
            }
        } else {

            $this->data['promotion'] = $promotion;
            $this->render_template('promotions/edit', $this->data);
        }

    }

    public function createOne($id)
    {
        if (!in_array('createPromotions', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        if ($id == "") {
            redirect('promotions', 'refresh');
        }
        $product = $this->model_products->getProductWithCategoryStoreData($id);

        $this->form_validation->set_rules('start_date', $this->lang->line('application_start_date'), 'trim|required');
        $this->form_validation->set_rules('end_date', $this->lang->line('application_end_date'), 'trim|required');

        $this->form_validation->set_rules('price', $this->lang->line('application_price_sale'), 'trim|required|greater_than[0]|callback_price_check[' . $product['price'] . ']');
        if ($this->postClean('typepromo') == 0) {
            $this->form_validation->set_rules('qty', $this->lang->line('application_promotion_qty'), 'trim|required|callback_qty_check[' . $product['qty'] . ']');
        }
        if ($this->form_validation->run() == TRUE) {
            $start_date = $this->convertDate($this->postClean('start_date'), "00:00");
            // true case
            if ($this->postClean('typepromo') == 1) {
                $qty = NULL;
                $qty_used = NULL;
            } else {
                $qty = $this->postClean('qty');
                $qty_used = 0;
            }
            $rec = array(
                'product_id' => $product['id'],
                'active' => (strtotime($start_date) < strtotime(date("d-m-Y H:i:s"))) ? 1 : 4,
                'type' => $this->postClean('typepromo') + 1,
                'qty' => $qty,
                'qty_used' => $qty_used,
                'price' => $this->postClean('price'),
                'start_date' => $this->convertDate($this->postClean('start_date'), $this->postClean('start_time')),
                'end_date' => $this->convertDate($this->postClean('end_date'), $this->postClean('end_time')),
                'store_id' => $product['store_id'],
                'company_id' => $product['company_id'],
            );
            $update = $this->model_promotions->create($rec);
            if ($update == true) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('products/update/' . $id, 'refresh'); // vem do product/update entao volta para lá
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('products/update/' . $id, 'refresh'); // vem do product/update entao volta para lá
            }
        } else {

            $this->data['product'] = $product;
            $this->render_template('promotions/createone', $this->data);
        }

    }

    function convertDate($orgDate, $time)
    {
        $date = str_replace('/', '-', $orgDate);
        $newDate = date("Y-m-d", strtotime($date));
        if ($time == '') {
            return $newDate . ' 00:00:00';
        } else {
            return $newDate . ' ' . $time . ':00';
        }
    }

}