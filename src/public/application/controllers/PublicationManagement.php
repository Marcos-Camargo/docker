<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Model_products $Model_products
 * @property Model_publication_management $Model_publication_management
 * @property Model_stores $Model_stores
 * @property Model_settings $Model_settings
 * @property Model_integrations $Model_integrations
 * @property Model_blacklist_words $Model_blacklist_words
 * @property Model_products_catalog $Model_products_catalog
 */
class PublicationManagement extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();
        $this->data['page_title'] = $this->lang->line('application_publication_management');
        $this->load->model('Model_publication_management');
        $this->load->model('Model_stores');
        $this->load->model('Model_reports');
        $this->load->model('Model_products');
        $this->load->model('Model_products_catalog');
        $this->load->model('Model_settings');
        $this->load->model('Model_integrations');
        $this->load->model('Model_blacklist_words');

        if ($this->session->userdata('ordersfilter') !== Null) {
            $ordersfilter = $this->session->userdata('ordersfilter');
        } else {
            $ordersfilter = "";
        }
        $this->data['ordersfilter'] = $ordersfilter;

    }

    public function index()
    {
        if (!in_array('viewPublicationManagement', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_now'] = $this->lang->line('application_publication_management');
        $this->data['get_total_incomplete_products_detalhe'] = $this->Model_publication_management->getTotalIncompleteProductsDetalhe();

        $this->data['products_without_image'] = $this->Model_publication_management->getProductsWithoutImage();
        $this->data['products_without_image'][0]['msg'] = $this->lang->line('application_product_missing_image');
        $this->data['products_without_image'][0]['filter'] = 1;

        $this->data['products_without_category'] = $this->Model_publication_management->getProductsWithoutCategory();
        $this->data['products_without_category'][0]['msg'] = $this->lang->line('application_product_without_category');
        $this->data['products_without_category'][0]['filter'] = 2;

        $this->data['products_without_price'] = $this->Model_publication_management->getProductsWithoutPrice();
        $this->data['products_without_price'][0]['msg'] = $this->lang->line('application_product_missing_price');
        $this->data['products_without_price'][0]['filter'] = 3;

        $this->data['products_without_dimensions'] = $this->Model_publication_management->getProductsWithoutDimensions();
        $this->data['products_without_dimensions'][0]['msg'] = $this->lang->line('application_incomplete_dimenssions');
        $this->data['products_without_dimensions'][0]['filter'] = 4;

        $this->data['products_without_descriptions'] = $this->Model_publication_management->getProductsWithoutDescription();
        $this->data['products_without_descriptions'][0]['msg'] = $this->lang->line('application_product_missing_description');
        $this->data['products_without_descriptions'][0]['filter'] = 5;

        $totalErros = 0;
        $totalErros += (int) $this->data['products_without_category'][0]['total'];
        $totalErros += (int) $this->data['products_without_image'][0]['total'];
        $totalErros += (int) $this->data['products_without_price'][0]['total'];
        $totalErros += (int) $this->data['products_without_dimensions'][0]['total'];
        $totalErros += (int) $this->data['products_without_descriptions'][0]['total'];

        $this->data['totalErros'] =  $totalErros;

        $this->data['allProducts'] = array_merge(
            $this->data['products_without_image'] ?: 0,
            $this->data['products_without_category'],
            $this->data['products_without_price'],
            $this->data['products_without_dimensions'],
            $this->data['products_without_descriptions']
        );

        $this->data['categories'] = $this->Model_publication_management->getAllCategories();
        $this->data['stores'] = $this->Model_stores->getActiveStore();
        $this->data['integrations'] = $this->Model_publication_management->getAllIntegrations();
        $this->data['brands'] = $this->Model_publication_management->getAllBrands();

        $this->filtersSelects();
        $this->getProductsErrorTransformDetails();
        $this->render_template('publicationManagement/index', $this->data);
    }

    public function getTotalIncompleteProductsAsync(){
        $this->data['total_incomplete_products'] = $this->Model_publication_management->getTotalIncompleteProducts();
        print $this->data['total_incomplete_products'][0]['total'];
    }

    public function getTotalErrorProductsAsync(){
        $this->data['total_transformation_errors'] = $this->Model_publication_management->TotalErrosTransform();
        $totalErrosTransform = 0;
        $totalErrosTransform += (int) $this->data['total_transformation_errors'][0]['total'];
        print $this->data['total_transformation_errors'][0]['total'] =  $totalErrosTransform;

    }

    public function indicators()
    {
        $this->data['products_without_image'] = $this->Model_publication_management->getProductsWithoutImage();
        $this->data['products_without_image'][0]['msg'] = $this->lang->line('application_product_missing_image');
        $this->data['products_without_image'][0]['filter'] = 1;

        $this->data['products_without_category'] = $this->Model_publication_management->getProductsWithoutCategory();
        $this->data['products_without_category'][0]['msg'] = $this->lang->line('application_product_without_category');
        $this->data['products_without_category'][0]['filter'] = 2;

        $this->data['products_without_price'] = $this->Model_publication_management->getProductsWithoutPrice();
        $this->data['products_without_price'][0]['msg'] = $this->lang->line('application_product_missing_price');
        $this->data['products_without_price'][0]['filter'] = 3;

        $this->data['products_without_dimensions'] = $this->Model_publication_management->getProductsWithoutDimensions();
        $this->data['products_without_dimensions'][0]['msg'] = $this->lang->line('application_incomplete_dimenssions');
        $this->data['products_without_dimensions'][0]['filter'] = 4;

        $this->data['products_without_descriptions'] = $this->Model_publication_management->getProductsWithoutDescription();
        $this->data['products_without_descriptions'][0]['msg'] = $this->lang->line('application_product_missing_description');
        $this->data['products_without_descriptions'][0]['filter'] = 5;

        $this->data['allProducts'] = array_merge(
            $this->data['products_without_image'] ?: 0,
            $this->data['products_without_category'],
            $this->data['products_without_price'],
            $this->data['products_without_dimensions'],
            $this->data['products_without_descriptions']
        );

        rsort($this->data['allProducts']);

        print json_encode($this->data['allProducts']);
    }

    public function filtersSelects(){

        $this->data['categories'] = $this->Model_publication_management->getAllCategories();
        $this->data['stores'] = $this->Model_stores->getActiveStore();
        $this->data['integrations'] = $this->Model_publication_management->getAllIntegrations();
        $this->data['brands'] = $this->Model_publication_management->getAllBrands();

        return $this->data;
    }

    public function getProductsErrorTransformForMkt()
    {
        $this->data['product_erro_transform_mkt'] = $this->Model_publication_management->getProductsErrorTransform();
        $totalErrosTransform = 0;
        $totalErrosTransform += (int) $this->data['product_erro_transform_mkt'][0]['total'];
        $this->data['totalErrosTransform'] = $totalErrosTransform;
        print json_encode($this->data['product_erro_transform_mkt']);
    }

    public function getProductsErrorTransformDetails()
    {

        $postdata = (!is_null($this->postClean('mkt'))) ? $this->postClean('mkt') : '';

        if ($postdata) {
            $this->data['get_details_erros_for_marketplace'] = $this->Model_publication_management->getProductsErrorTransformForMkt($postdata);
            print json_encode($this->data['get_details_erros_for_marketplace']);
        } else{
            return;
        }
    }

    public function fetchProductData()
    {

        $isMkt = $this->postClean('ismkt', TRUE);
        $postdata = $this->postClean(NULL, TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];

        if(
            isset($postdata['buscarIncompleto']) ||
            isset($postdata['buscarCategoria']) ||
            isset($postdata['buscarLoja']) ||
            isset($postdata['buscarMarketplace']) ||
            isset($postdata['buscarMarca']) ||
            isset($postdata['buscaStatus']) ||
            isset($postdata['buscaSituacao']) ||
            isset($postdata['buscaEstoque'])
        ){
            $result_filter = [
                'buscarIncompleto' => $postdata['buscarIncompleto'],
                'buscarCategoria' => $postdata['buscarCategoria'],
                'buscarLoja' => $postdata['buscarLoja'],
                'buscarMarketplace' => $postdata['buscarMarketplace'],
                'buscarMarca' => $postdata['buscarMarca'],
                'buscaStatus' => $postdata['buscaStatus'],
                'buscaSituacao' => $postdata['buscaSituacao'],
                'buscaEstoque' => $postdata['buscaEstoque'],
            ];
            $result_filter = array_filter($result_filter);
        }else{
            $result_filter = [];
        }

        $busca = $postdata['search'];

        if ($busca['value']) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $this->data['ordersfilter'] .= " AND ( p.sku like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR p.id like '%" . $busca['value'] . "%' OR p.EAN like '%" . $busca['value'] . "%')";
            }
        }

        $priceRO = $this->Model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
        if (in_array('disablePrice', $this->permission)) {
            $priceRO = true;
        }
        $percPriceCatalogSetting = $this->Model_settings->getSettingDatabyName('alert_percentage_update_price_catalog');
        $daysPriceCatalogSetting = $this->Model_settings->getSettingDatabyName('alert_days_update_price_catalog');
        if (!$percPriceCatalogSetting || !$daysPriceCatalogSetting || $daysPriceCatalogSetting['status'] == 2 || $percPriceCatalogSetting['status'] == 2)
            $percPriceCatalog = false;
        else $percPriceCatalog = $percPriceCatalogSetting['value'];

        if (isset($postdata['lojas'])) {
            if (is_array($postdata['lojas'])) {
                $lojas = $postdata['lojas'];
                $this->data['ordersfilter'] .= " AND (";
                foreach ($lojas as $loja) {
                    $this->data['ordersfilter'] .= "s.id = " . (int)$loja . " OR ";
                }
                $this->data['ordersfilter'] = substr($this->data['ordersfilter'], 0, (strlen($this->data['ordersfilter']) - 3));
                $this->data['ordersfilter'] .= ") ";
            }
        }

        if (isset($postdata['marketplace'])) {

            $this->data['join'] = " LEFT JOIN prd_to_integration i ON i.prd_id = p.id 
                                    LEFT JOIN errors_transformation et ON et.prd_id = p.id ";

            $whereJoin = " AND ( ";
            foreach ($postdata['marketplace'] as $key => $marketplace) {
                $whereJoin .= " i.int_to = '" . $marketplace . "' ";
                ($key + 1) < (count($postdata['marketplace'])) ? $whereJoin .= " OR " : '';
            }
            $whereJoin .= " ) ";

            $this->data['ordersfilter'] .= $whereJoin;

            if ($postdata['integration'] != 999) {
                switch ($postdata['integration']) {
                    case 30:
                        $this->data['ordersfilter'] .= " AND et.status = 0 ";
                        break;
                    case 40:
                        $this->data['ordersfilter'] .= " AND i.ad_link IS NOT NULL ";
                        break;
                    default:
                        $this->data['ordersfilter'] .= " AND i.status_int = " . $postdata['integration'];
                        break;
                }
            }
        }

        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('', 'sku', 'name', 'CAST(price AS DECIMAL(12,2))', 'CAST(qty AS UNSIGNED)', 's.name', 'p.id', '', '', '');
            $campo = $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY " . $campo . " " . $direcao;
            }
            $this->data['orderby'] = $sOrder;
        }

        $result = array();
        if (isset($this->data['ordersfilter'])) {
            $this->session->set_userdata('productExportFilters', $this->data['ordersfilter']);
            $filtered = $this->Model_products->getProductCount($this->data['ordersfilter']);
            //get_instance()->log_data('Products','fetchfilter',print_r($this->data['ordersfilter'],true));
        } else {
            $filtered = 0;
            if ($this->session->has_userdata('productExportFilters')) {
                $this->session->unset_userdata('productExportFilters');
            }
        }

        $paramIndicator = "";
        $mkt = "";
        if(isset($result_filter)){
            $typefilter = array_keys($result_filter);
            foreach($typefilter as $t){
                if($t == 'buscarIncompleto'){
                    $paramIndicator = $result_filter['buscarIncompleto'];
                    switch ($paramIndicator) {
                        case 1:
                            $paramIndicator = ' AND (p.principal_image is NULL or p.principal_image = "") ';
                            break;
                        case 2:
                            $value = '[""]';
                            $paramIndicator = " AND p.category_id = '$value' ";
                            break;
                        case 3:
                            $paramIndicator = ' AND price = "" ';
                            break;
                        case 4:
                            $paramIndicator = ' AND (peso_bruto = "" OR largura = "" OR altura = "" OR profundidade = "" OR products_package = "" OR peso_liquido = "" )';
                            break;
                        case 5:
                            $paramIndicator = ' AND p.description = "" ';
                            break;
                        default:
                            $paramIndicator = '';
                            break;
                    }
                }
                if($t == 'buscarCategoria'){
                    $valor = $result_filter['buscarCategoria'];
                    $paramIndicator .=  "AND p.category_id = '[".'"'.$valor.'"'."]' ";
                }
                if($t == 'buscarLoja'){
                    $valor = $result_filter['buscarLoja'];
                    $paramIndicator .= "AND p.store_id = '$valor' ";
                }
                if($t == 'buscarMarketplace'){
                    $mkt = $result_filter['buscarMarketplace'];
                }
                if($t == 'buscarMarca'){
                    $valor = $result_filter['buscarMarca'];
                    $paramIndicator .= "AND p.brand_id = '[".'"'.$valor.'"'."]' ";
                }
                if($t == 'buscaStatus'){
                    $valor = $result_filter['buscaStatus'];
                    $paramIndicator .= "AND p.status = '$valor' ";
                }
                if($t == 'buscaSituacao'){
                    $valor = $result_filter['buscaSituacao'];
                    $paramIndicator .= "AND p.situacao = '$valor' ";
                }
                if($t == 'buscaEstoque'){
                    $valor = $result_filter['buscaEstoque'];
                    switch ($valor) {
                        case 1:
                            $paramIndicator .= " AND p.qty > 0 ";
                            break;
                        case 2:
                            $paramIndicator .= " AND p.qty < 1 ";
                            break;
                    }
                }
            }
        }

        $_SESSION['PublicationManagement'] = [
            $paramIndicator,
        ];

        $data = $this->Model_publication_management->getProductData($ini, null, $length, $paramIndicator, $mkt);

        $i = 0;
        foreach ($data as $key => $value) {
            $i++;
            $buttons = '';

            if (in_array('deleteProduct', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc(' . $value['id'] . ',\'' . $value['sku'] . '\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }
            if ((!is_null($value['principal_image'])) && ($value['principal_image'] != '')) {
                $img = '<img src="' . $value['principal_image'] . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $img = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }

            switch ($value['status']) {
                case 1:
                    if ($value['situacao'] == "1") {
                        $status =  '<span class="label label-warning">' . $this->lang->line('application_active') . '</span>';
                    } else {
                        $status =  '<span class="label label-success">' . $this->lang->line('application_active') . '</span>';
                    }
                    break;

                case 4:
                    $status = '<span class="label label-danger" data-toggle="tooltip" title="' . $this->lang->line('application_product_has_prohibited_word') . '">' . $this->lang->line('application_under_analysis') . '</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">' . $this->lang->line('application_inactive') . '</span>';
                    break;
            }

            $situacao = '';
            switch ($value['situacao']) {
                case 1:
                    $situacao = '<span class="label label-danger">' . $this->lang->line('application_incomplete') . '</span>';
                    break;
                case 2:
                    $situacao = '<span class="label label-success">' . $this->lang->line('application_complete') . '</span>';
                    break;
            }

            $integrations = $this->Model_integrations->getIntegrationsProduct($value['id'], 1);
            if ($integrations) {
                $plataforma = "";
                foreach ($integrations as $v) {
                    if ($v['rule']) {
                        $ruleBlock = array();
                        $ruleId = is_numeric($v['rule']) ? (array)$v['rule'] : json_decode($v['rule']);
                        foreach ($ruleId as $ruleBlockId) {
                            $rule = $this->Model_blacklist_words->getWordById($ruleBlockId);
                            if ($rule)
                                array_push($ruleBlock, strtoupper(str_replace('"', "'", $rule['sentence'])));
                        }
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" data-html="true" title="' . implode('<br><br>', $ruleBlock) . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['errors'] == 1) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_errors_tranformation'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 0) {
                        $plataforma .= '<span class="label label-warning" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_in_analysis'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 1) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 2) {
                        $plataforma .= '<span class="label label-primary" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_sent'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 11) {
                        $over = $this->Model_integrations->getPrdBestPrice($value['EAN']);
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . ' (' . $over . ')">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 12) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 13) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_higher_price'), 'UTF-8') . ' ">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 14) {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_release'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 20) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 21) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 22) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 23) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 24) {
                        $plataforma .= '<span class="label label-success" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_in_registration'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 90) {
                        $plataforma .= '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_inactive'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 91) {
                        $plataforma .= '<span class="label label-default" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_no_logistics'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } elseif ($v['status_int'] == 99) {
                        $plataforma .= '<span class="label label-warning" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_in_analysis'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    } else {
                        $plataforma .= '<span class="label label-danger" data-toggle="tooltip" title="' . mb_strtoupper($this->lang->line('application_product_out_of_stock'), 'UTF-8') . '">' . $v['int_to'] . '</span>&nbsp;';
                    }
                }
            } else {
                $plataforma = "<span></span>";
            }
            $qty_status = '';
            if ($value['qty'] <= 10) {
                $qty_status = '<span class="label label-warning">' . $this->lang->line('application_low') . ' !</span>';
            } else if ($value['qty'] <= 0) {
                $qty_status = '<span class="label label-danger">' . $this->lang->line('application_out_stock') . ' !</span>';
            }
            $is_kit = '';
            if ($value['is_kit'] == 1) {
                $is_kit = '<br><span class="label label-warning">Kit</span>';
            }

            $link_id = "<a href='" . base_url('products/update/' . $value['id']) . "'>" . $value['sku'] . ' ' . $is_kit . "</a>";
            $price = $this->formatprice($value['price']);
            $qty_read_only = (trim($value['has_variants']) ? 'disabled data-toggle="tooltip" data-placement="top" title="Produto com variação" data-container="body"' : '');
            $price_read_only = ($priceRO) ? 'disabled' : '';

            $colorAlertPrice = '';
            $directionAlertPrice = '';

            if ($percPriceCatalog && $value['qty'] > 0) {
                $productWithChangedPrice = $this->Model_products_catalog->getProductWithChangedPrice($value['product_catalog_id']);
                if ($productWithChangedPrice) {
                    $colorAlertPrice = 'style="color: red"';
                    $directionAlertPrice = $productWithChangedPrice['old_price'] > $productWithChangedPrice['new_price'] ? '&nbsp;<i class="fas fa-arrow-down" data-toggle="tootip" data-placement="right" title="Preço do catálogo sofreu redução."></i>' : '&nbsp;<i class="fas fa-arrow-up" data-toggle="tootip" data-placement="right" title="Preço do catálogo sofreu aumento."></i>';
                }
            }

            if ($isMkt) {
                $result[$key] = array(
                    $value['id'] . "|" . $value['company_id'],
                    $img,
                    $link_id, // $value['sku'].' '.$is_kit,
                    $value['name'],
                    "<input type='text' class='form-control' $price_read_only onchange='this.value=changePrice($value[id], $value[price], this.value)' onfocus='this.value=$value[price]' onKeyUp='this.value=formatPrice(this.value)' value='$price' size='7' {$colorAlertPrice} " . ($value['is_kit'] != 1 ? '' : 'disabled') . "/>" . $directionAlertPrice,
                    "<input type='text' class='form-control' readonly $qty_read_only onchange='changeQty($value[id], $value[qty], this.value)' onKeyPress='return digitos(event, this)' value='$value[qty]' size='3' /> . ' ' . $qty_status", //$value['qty'] . ' ' . $qty_status,
                    $value['loja'],
                    $value['id'],
                    $status,
                    $situacao,
                    $plataforma
                );
            } else {
                $result[$key] = array(
                    $img,
                    $link_id, // $value['sku'].' '.$is_kit,
                    $value['name'],
                    "<input type='text' class='form-control' $price_read_only onchange='this.value=changePrice($value[id], $value[price], this.value)' onfocus='this.value=$value[price]' onKeyUp='this.value=formatPrice(this.value)' value='$price' size='7' {$colorAlertPrice} " . ($value['is_kit'] != 1 ? '' : 'disabled') . "/>" . $directionAlertPrice, //$value['price'],
                    "<input type='text' class='form-control' readonly $qty_read_only onchange='changeQty($value[id], $value[qty], this.value)' onKeyPress='return digitos(event, this)' value='$value[qty]' size='3' />" . ' ' . $qty_status, //$value['qty'] . ' ' . $qty_status,
                    $value['loja'],
                    $value['id'],
                    $status,
                    $situacao,
                    $plataforma,
                );
            }
        }
        if ($filtered == 0) {
            $filtered = $i;
        }
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->Model_products->getProductCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        echo json_encode($output);
    }

    public function exportCsv()
    {
        $filterSave = $_SESSION['PublicationManagement'][0];
        $dataExport = $this->Model_publication_management->queryExport($filterSave);
        $file = fopen('php://output', 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        $header = array(
            'ID Produto',
            'Nome do Item',
            'Sku do Parceiro',
            'Preco de Venda',
            'Quantidade em estoque',
            'Imagens',
            'Fabricante',
            'Descricao do Item _ Informacoes do Produto',
            'Categoria',
            'Status(1=Ativo|2=Inativo|3=Lixeira)',
            'EAN',
            'SKU no fabricante',
            'Peso Liquido em kgs',
            'Peso Bruto em kgs',
            'Largura em cm',
            'Altura em cm',
            'Profundidade em cm',
            'NCM',
            'ID da Loja'
        );

        fputcsv($file, $header, ';', '"');

        header('Content-Disposition: attachment;filename=todos.csv');
        header("Content-Type: application/csv; charset=utf-8");

        foreach ($dataExport as $key => $value) {
            fputcsv($file, $value, ';', '"');
        }

        fclose($file);

   }
}
