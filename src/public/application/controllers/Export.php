<?php
/*
SW Serviços de Informática 2019

Controller de Exportação de Dados

 */
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') or exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use League\Csv\CharsetConverter;
use League\Csv\Writer;

/**
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Lang $lang
 * @property CI_Loader $load
 *
 * @property Excel $excel
 * @property CalculoFrete $calculofrete
 * @property Model_stores $model_stores
 * @property Model_log_integration $model_log_integration
 * @property Model_brands $model_brands
 * @property Model_category $model_category
 * @property Model_settings $model_settings
 * @property Model_integrations $model_integrations
 * @property Model_freights $model_freights
 * @property Model_log_integration_unique $model_log_integration_unique
 * @property Model_products $model_products
 * @property Model_csv_generator_export $model_csv_generator_export
 * @property Model_product_return $model_product_return
 * @property Model_orders $model_orders
 * @property Model_catalogs $model_catalogs
 * @property Model_blingultenvio $model_blingultenvio
 * @property Model_receivables $model_receivables
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_company $model_company
 * @property Model_products_catalog $model_products_catalog
 * @property Bucket $bucket
 */
class Export extends Admin_Controller
{

    private $sellerCenter;
    private $arrProducts = [];
    private $integrations = [];
    private $identifying_technical_specification;

    // construct
    public function __construct()
    {
        parent::__construct();
        ini_set('display_errors', 0);

        $this->load->library('excel');
        $this->load->library('calculoFrete');
        $this->load->model('model_stores');
        $this->load->model('model_products');
        $this->load->model("model_csv_generator_export");
        $this->load->model('model_product_return');
        $this->load->model('model_brands');
        $this->load->model('model_category');
        $this->load->model('model_settings');
        $this->load->model('model_integrations');
        $this->load->model('model_freights');
        $this->load->model('model_log_integration_unique');
        $this->load->model('model_orders');
        $this->load->model('model_catalogs');
        $this->load->model('model_blingultenvio');
        $this->load->model('model_receivables');
        $this->load->model('model_categorias_marketplaces');
        $this->load->model('model_errors_transformation');
        $this->load->model('model_company');
        $this->load->model('model_products_catalog');
        $this->load->library('bucket');

        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $this->data['userstore'] = $this->session->userdata('userstore');
        // load excel library
        if ($this->session->userdata('ordersfilter') !== Null) {
            $ordersfilter = $this->session->userdata('ordersfilter');
        } else {
            $ordersfilter = "";
        }
        $this->data['ordersfilter'] = $ordersfilter;

        $this->sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $this->sellerCenter = $settingSellerCenter['value'];
        }

        $this->identifying_technical_specification = $this->model_settings->getSettingDatabyName('identifying_technical_specification');

        $this->arrProducts = [];
        $this->integrations = $this->model_integrations->getIntegrationsbyStoreId(0);
        // timeout de 20min
        set_time_limit(1200);
    }

    // create xlsx
    public function OrdersXls()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // create file name
        $fileName = 'data-' . time() . '.xlsx';
        // load model
        $listInfo = $this->model_orders->ExcelList();
        $listTranslate = array();

        // garantir ordenação de colunas
        $columns = [
            'application_id', 'application_store', 'application_item', 'application_product_id', 'application_product_sku',
            'application_product_name', 'application_item_qty', 'application_value_products', 'application_included',
            'application_approved', 'application_marketplace', 'application_order_marketplace_full', 'application_client',
            'application_cpf_cnpj', 'application_delivery_address', 'application_number',
            'application_complement', 'application_neighb', 'application_city', 'application_uf', 'application_zip_code',
            'application_total_order', 'application_orders_value', 'application_discount', 'application_ship_value',
            'application_phase', 'application_dispatch', 'application_promised', 'application_status', 'application_nfe_num',
            'application_serie', 'application_key', 'application_ship_company', 'application_service', 'application_tracking_code',
            'application_ticket', 'application_ship_date', 'application_delivered_date', 'application_transportation_status',
        ];

        $orderId = 0;
        $itemCount = 1;
        foreach ($listInfo as $num => $linha) {
            $orderData = $this->model_orders->getOrdersData(1, $listInfo[$num]['application_id']);
            $resultData = (is_null($orderData['data_pago'])) || (is_null($orderData['ship_time_preview'])) ? ''
                : date('d/m/Y', strtotime(
                    $this->somar_dias_uteis(
                        date('Y-m-d', strtotime($orderData['data_pago'])), $orderData['ship_time_preview'], '', TRUE)));
            $linha['application_promised'] = $resultData;

            $itemCount = $orderId != $linha['application_id'] ? 1 : $itemCount + 1;
            foreach ($columns as $column) {
                if (!isset($linha[$column])) {
                    $linha[$column] = '';
                }

                if ($column == 'application_key' && !empty($linha[$column])) {
                    $linha[$column] = "\"{$linha[$column]}\"";
                }

                if ($column == 'application_item') {
                    $linha[$column] = $itemCount;
                }

                if ($column == 'application_status') {
                    $linha[$column] = $this->lang->line("application_order_{$linha[$column]}");
                }

                $listTranslate[$num][$this->lang->line($column)] = $linha[$column];
            }
            $orderId = $linha['application_id'];
        }
        //$listInfo[0] = $header;

        // $this->CreateXLS($listTranslate,'Pedidos');
        $this->exportCSV($listTranslate, 'Pedidos');
    }

    public function ProductsXls()
    {
        $settingSellerCenter = $this->model_settings->getValueIfAtiveByName('product_export_by_queue');
        if ($settingSellerCenter) {
            $this->newMode();
        } else {
            $this->oldModeProduct();
        }
    }
    public function oldModeProduct()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3072M');

        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->input->get();
        foreach ($postdata as $f => $value) {
            if (is_string($value)) {
                $postdata[$f] = urldecode($value);
            }
        }
        if ($postdata['variation'] === 'true') {
            $postdata['variation'] = true;
        } else {
            $postdata['variation'] = false;
        }

        $busca = isset($postdata['search']) ? $postdata['search'] : [];
        if (isset($busca['value'])) {
            if (strlen($busca['value']) > 2) {  // Garantir no minimo 3 letras
                $this->data['ordersfilter'] .= " AND ( sku like '%" . $busca['value'] . "%' OR p.name like '%" . $busca['value'] . "%' OR s.name like '%" . $busca['value'] . "%' OR p.id like '%" . $busca['value'] . "%' OR p.EAN like '%" . $busca['value'] . "%')";
            }
        }

        if (isset($postdata['sku']) && strlen(trim($postdata['sku'])) > 0) {
            $sku = '\'%' . $postdata['sku'] . '%\'';
            $this->data['ordersfilter'] .= " AND p.sku LIKE $sku ";
        }

        if (isset($postdata['product']) && strlen(trim($postdata['product'])) > 0) {
            $product = '\'%' . $postdata['product'] . '%\'';
            $this->data['ordersfilter'] .= " AND p.name LIKE $product ";
        }

        $deletedStatus = Model_products::DELETED_PRODUCT;
        if (isset($postdata['status'])) {
            $this->data['ordersfilter'] .= " AND (p.status = {$postdata['status']}
            AND p.status NOT IN ({$deletedStatus}))";
        } else {
            $this->data['ordersfilter'] .= " AND p.status NOT IN ({$deletedStatus})";
        }

        if (isset($postdata['situation'])) {
            $this->data['ordersfilter'] .= " AND p.situacao = " . $postdata['situation'];
        }

        if (isset($postdata['estoque'])) {
            switch ($postdata['estoque']) {
                case 1:
                    $this->data['ordersfilter'] .= " AND p.qty > 0 ";
                    break;
                case 2:
                    $this->data['ordersfilter'] .= " AND p.qty < 1 ";
                    break;
            }
        }

        if (isset($postdata['kit'])) {
            switch ($postdata['kit']) {
                case 1:
                    $this->data['ordersfilter'] .= " AND p.is_kit = true ";
                    break;
                case 2:
                    $this->data['ordersfilter'] .= " AND p.is_kit != true ";
                    break;
            }
        }

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

            if (strlen(trim($postdata['integration'])) > 0 && $postdata['integration'] != 999) {
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

        if (isset($postdata['colecoes']) && !empty($postdata['colecoes'])) {
            $this->data['joinCollection'] = "  JOIN catalogs_products_catalog cpc ON p.product_catalog_id = cpc.product_catalog_id 
                                     JOIN catalogs c ON cpc.catalog_id = c.id ";

            $attributes = implode('","', $postdata['colecoes']);
            $whereJoin = ' AND c.attribute_value IN ("' . $attributes . '")';
            $this->data['ordersfilter'] .= $whereJoin;
        }

        $offset = 0;
        $limit = 100000;
        while (true) {
            $listInfo = $this->model_products->getProductData($offset,  null, $limit);
            if (count($listInfo) == 0) {
                break;
            }
            $offset += $limit;
            foreach ($listInfo as $product) {
                $this->buildProductCSVData($product, $postdata['variation']);
            }
        }
        ob_end_clean();
        if ($this->sellerCenter == 'somaplace') {
            return $this->exportCSV($this->arrProducts, 'Produtos');
        }

        $arrProductsExport = array();
        foreach ($this->arrProducts as $product) {
            array_push($arrProductsExport, $product);
        }
        return $this->exportCSV(array_reverse($arrProductsExport), 'Produtos');
    }

    public function trashProductsExport()
    {
        if (!in_array('viewTrash', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $params = $this->input->get();
        foreach ($params as $k => $v) {
            if (is_string($v))
                $params[$k] = urldecode($v);
        }
        if (isset($params['search']) && is_array($params['search'])) {
            $params['search'] = current(array_values($params['search']));
        }
        $params['status'] = Model_products::DELETED_PRODUCT;
        $this->newProductExport($params);
    }

    public function newProductExport($params = [])
    {
        $limit = 100;
        $offset = 0;

        $withVariations = $params['variation'] == 'true';
        $nroRegisters = $this->model_products->countGetProductsByCriteria($params);

        while ($nroRegisters >= $offset) {
            $prodResults = $this->model_products->getProductsToDisplayByCriteria($params, $offset, $limit);
            if (empty($prodResults)) {
                break;
            }
            foreach ($prodResults as $prod) {
                $product = $this->model_products->getProductData(0, $prod['id']);
                if (!isset($product['id'])) {
                    continue;
                }
                $this->buildProductCSVData($product, $withVariations);
            }
            $offset += $limit;
        }

        if ($this->sellerCenter == 'somaplace') {
            return $this->exportCSV($this->arrProducts, 'Produtos');
        }

        $arrProductsExport = array();
        foreach ($this->arrProducts as $product) {
            array_push($arrProductsExport, $product);
        }
        return $this->exportCSV(array_reverse($arrProductsExport), 'Produtos');
    }

    protected function buildProductCSVData($product, $withVariations = false)
    {
        $product['mkt'] = $product['mkt'] ?? 'não publicado';
        // pegar cainho de imagens
        $images = array();
        $fotos = array();
        if ($product['product_catalog_id']) {
            if (!$product['is_on_bucket']) {
                if (is_dir(FCPATH . 'assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image']))) {
                    $fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image']));
                } else if (is_dir(FCPATH . 'assets/images/product_image/' . $product['image'])) {
                    $fotos = scandir(FCPATH . 'assets/images/product_image/' . $product['image']);
                }
            } else {
                if ($this->bucket->isDirectory('assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image']))) {
                    $fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . str_replace('catalog_', '', $product['image']));
                } else if ($this->bucket->isDirectory('assets/images/product_image/' . $product['image'])) {
                    $fotos = $this->bucket->getFinalObject('assets/images/product_image/' . $product['image']);
                }
            }
        }

        if (!$product['is_on_bucket']) {
            foreach ($fotos as $foto) {
                if (($foto != ".") && ($foto != "..") && ($foto != "")) {
                    array_push($images, base_url('assets/images/product_image/' . $product['image'] . '/' . $foto));
                }
            }
        }else{
            foreach ($fotos['contents'] as $foto) {
                if ($foto['url'] != "") {
                    array_push($images, $foto['url']);
                }
            }
        }

        $images = implode(",", $images);

        $category = (int) filter_var($product['category_id'], FILTER_SANITIZE_NUMBER_INT);
        $category_name = $category == 0 ? "" : $this->model_category->getCategoryData($category)['name'];

        $brand = (int) filter_var($product['brand_id'], FILTER_SANITIZE_NUMBER_INT);
        $brand_name = $brand == 0 ? "" : $this->model_brands->getBrandData($brand)['name'];

        $collection = "";
        if(isset($product['attribute_value'])){
            $collection = $product['attribute_value'] ?? "";
        }

        if ($this->sellerCenter == 'somaplace') {
            array_push($this->arrProducts, array(
                "ID da Loja" => $product['store_id'],
                "Sku Produto Pai" => utf8_decode($product['sku']),
                "EAN" => $product['EAN'],
                "Nome do Item" => $product['name'],
                "Preco de Venda" => floatval($product['price']),
                "Quantidade em estoque" => $product['qty'],
            ));
            if($this->identifying_technical_specification && $this->identifying_technical_specification['status'] == 1){
                $this->arrProducts[] = array("Coleção" => $collection);
            }
        } else {
            if (array_key_exists($product['id'], $this->arrProducts)) {
                $this->arrProducts[$product['id']][$product['mkt']] = $product['skumkt'] ?? '';
                if($this->identifying_technical_specification && $this->identifying_technical_specification['status'] == 1){
                    $this->arrProducts[$product['id']][] = array("Coleção" => $collection);
                }
            } else {
                $this->arrProducts[$product['id']] = array(
                    "ID da Loja" => $product['loja'] ?? '',
                    "Sku do Parceiro" => utf8_decode($product['sku']),
                    "Sku Produto Pai" => '',
                    "Nome do Item" => $product['name'], //utf8_decode($product['name']),
                    "Preco de Venda" => floatval($product['price']),
                    "Quantidade em estoque" => $product['has_variants'] == '' || !$withVariations ? $product['qty'] : '',
                    "Fabricante" => $brand_name, //utf8_decode($brand_name),
                    "SKU no fabricante" => utf8_decode($product['codigo_do_fabricante']),
                    "Categoria" => $category_name, //utf8_decode($category_name),
                    "EAN" => $product['EAN'],
                    "Peso Liquido em kgs" => $product['peso_liquido'],
                    "Peso Bruto em kgs" => $product['peso_bruto'],
                    "Largura em cm" => $product['largura'],
                    "Altura em cm" => $product['altura'],
                    "Profundidade em cm" => $product['profundidade'],
                    "NCM" => $product['NCM'],
                    "Origem do Produto _ Nacional ou Estrangeiro" => $product['origin'],
                    "Garantia em meses" => $product['garantia'],
                    "Prazo Operacional em dias" => $product['prazo_operacional_extra'],
                    "Produtos por embalagem" => $product['products_package'],
                    "Descricao do Item _ Informacoes do Produto" => $product['description'], //utf8_decode($product['description']),
                    "Imagens" => $images,
                    "Status(1=Ativo|2=Inativo|3=Lixeira)" => $product['status']
                );

                if($this->identifying_technical_specification && $this->identifying_technical_specification['status'] == 1){
                    $this->arrProducts[$product['id']]["Coleção"] = $collection;
                }

                // add id do produto quando for conecta lá
                if ($this->sellerCenter == 'conectala')
                    $this->arrProducts[$product['id']]['ID Produto'] = $product['id'];

                // adiciona os campos de variação
                if ($withVariations) {
                    $this->arrProducts[$product['id']]['TAMANHO'] = '';
                    $this->arrProducts[$product['id']]['COR'] = '';
                    $this->arrProducts[$product['id']]['VOLTAGEM'] = '';
                    $this->arrProducts[$product['id']]['SABOR'] = '';
                    $this->arrProducts[$product['id']]['GRAU'] = '';
                    $this->arrProducts[$product['id']]['LADO'] = '';
                }

                // adiciona os skus nos marketplaces
                $this->arrProducts[$product['id']][$product['mkt']] = $product['skumkt'] ?? '';
                foreach ($this->integrations as $integration) {
                    $this->arrProducts[$product['id']][$integration['int_to']] = '';
                }
            }
        }
        if ($withVariations) {
            $variants = $this->model_products->getVariantsByProd_id($product['id']);
            foreach ($variants as $variant) {
                $variant_line = [
                    "ID da Loja" => $product['loja'] ?? '',
                    "Sku do Parceiro" => utf8_decode($variant['sku']),
                    "Sku Produto Pai" => utf8_decode($product['sku']),
                    "Nome do Item" => '',
                    "Preco de Venda" => floatval($variant['price']),
                    "Quantidade em estoque" => $variant['qty'],
                    "Fabricante" => '',
                    "SKU no fabricante" => '',
                    "Categoria" => '',
                    "EAN" => $variant['EAN'],
                    "Peso Liquido em kgs" => '',
                    "Peso Bruto em kgs" => '',
                    "Largura em cm" => '',
                    "Altura em cm" => '',
                    "Profundidade em cm" => '',
                    "NCM" => '',
                    "Origem do Produto _ Nacional ou Estrangeiro" => '',
                    "Garantia em meses" => '',
                    "Prazo Operacional em dias" => '',
                    "Produtos por embalagem" => '',
                    "Descricao do Item _ Informacoes do Produto" => '',
                    "Imagens" => '',
                    "Status(1=Ativo|2=Inativo|3=Lixeira)" => $variant['status'],
                ];

                if($this->identifying_technical_specification && $this->identifying_technical_specification['status'] == 1){
                    $variant_line["Coleção"] = $collection;
                }

                $variant_line['TAMANHO'] = '';
                $variant_line['COR'] = '';
                $variant_line['VOLTAGEM'] = '';
                $variant_line['SABOR'] = '';
                $variant_line['GRAU'] = '';
                $variant_line['LADO'] = '';
                $label_var = explode(';', $product['has_variants']);
                $label_value = explode(';', $variant['name']);
                foreach ($label_var as $key => $value) {
                    $variant_line[strtoupper($label_var[$key])] = $label_value[$key];
                }
                $variant_line[$product['mkt']] = $product['skumkt'] ?? '';
                foreach ($this->integrations as $integration) {
                    $variant_line[$integration['int_to']] = '';
                }
                $this->arrProducts[$product['id'] . '-' . $variant['variant']] = $variant_line;
            }
        }
    }

    public function newMode()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3072M');

        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $postdata = $this->input->get();
        foreach ($postdata as $f => $value) {
            if (is_string($value)) {
                $postdata[$f] = urldecode($value);
            }
        }
        $export = $this->model_csv_generator_export->exportProduct($postdata);
        if ($export['sucess']) {
            $this->session->set_flashdata('success', $export['message']);
            redirect('products', 'refresh');
            return;
        }
        if ($export['warning']) {
            $this->session->set_flashdata('warning', $export['message']);
            redirect('products', 'refresh');
            return;
        }
        $this->session->set_flashdata('error', $export['message']);
        redirect('products', 'refresh');
    }

    public function phasesXls()
    {
        if (!in_array('viewPhases', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
        if ($sellerCenter != 'conectala') {
            redirect('dashboard', 'refresh');
        }
        $data = $this->input->get();
        if (!isset($data['search_responsable'])) {
            $data['search_responsable'] = null;
        }
        if (!isset($data['search_phases'])) {
            $data['search_phases'] = null;
        }
        if (!isset($data['search_store'])) {
            $data['search_store'] = null;
        }
        if ($data['search_responsable'])
            $data['search_responsable'] = explode(',', $data['search_responsable']);
        if ($data['search_phases'])
            $data['search_phases'] = explode(',', $data['search_phases']);
        $store_like = $data['search_store'];
        $phase_like =  $data['search_phases'];
        $responsable_like =  $data['search_responsable'];
        $stores = $this->model_stores->getAllStoresFromStageExports($store_like, $phase_like, $responsable_like);
        return $this->exportCSV($stores, 'Fases');
        // dd($data, $stores, $this->db->last_query());
    }
    public function CategoriesXls()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // load model
        $listInfo = $this->model_category->getCategoryDataForExport();

        $this->CreateXLS($listInfo, 'Categorias');
    }

    public function FabricantesXls()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // load model
        $listInfo = $this->model_brands->getBrandData();
        $arrBrand = array();

        foreach ($listInfo as $brand) {

            array_push($arrBrand, array(
                $this->lang->line('application_id') => $brand['id'],
                $this->lang->line('application_name') => $brand['name'],
                $this->lang->line('application_company') => $brand['active'] == 1 ? $this->lang->line('application_active') : $this->lang->line('application_inactive'),
            ));
        }

        $this->CreateXLS($arrBrand, 'Marcas');
    }

    public function OrigemXls()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $listInfo = array();
        for ($i = 0; $i <= 8; $i++) {
            $listInfo[$i] = array('origem' => (string) $i, 'descricao' => $this->lang->line("application_origin_product_" . $i));
        }

        $this->CreateXLS($listInfo, 'Origens');
    }

    public function LojaXls()
    {
        // Removido pois a exportação já leva em conta as lojas que o usuário pertence
        //  if(!in_array('viewStore', $this->permission)) {
        //     redirect('dashboard', 'refresh');
        //  }

        $listInfo = $this->model_stores->getStoresForExportFormated();

        $arrStores = array();
        $dia = $this->diminuir_dias_uteis(date("Y-m-d"), 2) . " 00:00:00";
        foreach ($listInfo->result_array() as $store) {

            $count_expired = count($this->model_stores->getStoresByNewCategoriesExpired($dia, $store['codigo']));

            $first_product = $this->model_stores->getDataForTheProgressBar($store['codigo']);

            if ($first_product['date_product']) {
                $date_first_product = date('d/m/Y', strtotime($first_product['date_product']));
                $deadline = new DateTime($first_product['date_product']);
                $deadline->add(new DateInterval('P6M'));
                $expiration = $deadline->format('d/m/Y');
            } else {
                $date_first_product = null;
                $expiration = null;
            }

            $onboarding = $store['onboarding'] ? date('d/m/Y', strtotime($store['onboarding'])) : null;


            $logistic = $this->calculofrete->getLogisticStore(array(
                'freight_seller'         => $store['freight_seller'],
                'freight_seller_type'     => $store['freight_seller_type'],
                'store_id'                => $store['codigo']
            ));

            array_push($arrStores, array(

                $this->lang->line('application_id') => $store['codigo'],
                $this->lang->line('application_name') => $store['nome'],
                $this->lang->line('application_raz_soc') => $store['raz_social'],
                $this->lang->line('application_company') => $store['empresa'],
                $this->lang->line('application_cnpj') => $this->formatDoc($store['cnpj']),
                $this->lang->line('application_date_create') => $store['date_create'],
                $this->lang->line('application_first_product') => $date_first_product,
                $this->lang->line('application_first_sale') => $first_product['date_order'] ? date('d/m/Y', strtotime($first_product['date_order'])) : null,
                $this->lang->line('application_expiration_date') => $expiration,
                $this->lang->line('application_onboarding') => $onboarding,
                $this->lang->line('application_commission') => empty($store['service_charge_value']) ? 0 : $store['service_charge_value'],
                $this->lang->line('application_store_status') => $store['store_status'],
                $this->lang->line('application_uf') => $store['addr_uf'],
                $this->lang->line('application_email') => $store['responsible_email'],
                $this->lang->line('application_seller') => $store['seller'],
                $this->lang->line('application_logistics') => ucfirst($logistic['type']),
                $this->lang->line('application_anticipation_transfer') => $store['flag_antecipacao_repasse'],
            ));
        }

        $this->CreateXLS($arrStores, 'Lojas');
    }

    public function LojaNovaCategoriaXls()
    {
        if (!in_array('viewStore', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        // Pego as ordens com status = 98, isto é marcadas como canceladas mas que precisa avisar ao marketplace e frete rápido
        $data = $this->model_stores->getStoresTiposVolumesNovosData(0, '', '', true);
        $dia = $this->diminuir_dias_uteis(date("Y-m-d"), 2) . " 00:00:00";

        $arrStores = array();
        foreach ($data as $key => $value) {

            $store = $value['loja'];
            $date_update = $value['date_update'] < $dia ? date("d/m/Y H:i:s", strtotime($value['date_update'])) : date("d/m/Y H:i:s", strtotime($value['date_update']));
            $category = $value['tipo_volume'];

            if ($value['status'] == 1) {
                $status = $this->lang->line('application_New');
            } elseif ($value['status'] == 2) {
                $status = $this->lang->line('application_correio_ok');
            } elseif ($value['status'] == 3) {
                $status = $this->lang->line('application_in_registration');
            } else {
                $status = $this->lang->line('application_ok');
            }

            array_push($arrStores, array(
                $this->lang->line('application_stores') => $store,
                $this->lang->line('application_categories_frete_rapido') => $category,
                $this->lang->line('application_status') => $status,
                $this->lang->line('application_date') => $date_update,
            ));
        }

        $this->CreateXLS($arrStores, 'Lojas com Novas Categorias');
    }

    public function ProductIntegrationXls()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // load model
        $listInfo = array();
        $data = $this->model_blingultenvio->getDataSemItegracao(0, '', '', true);

        foreach ($data as $key => $value) {
            $instrucoes = "Escolha a categoria,";
            if ($value['int_to'] == 'MAGALU') {
                $instrucoes .= " zere id na loja,";
            }
            $instrucoes = substr($instrucoes, 0, -1);
            array_push($listInfo, array(
                $this->lang->line('application_runmarketplaces') => $value['int_to'],
                $this->lang->line('application_sku') => $value['skubling'],
                $this->lang->line('application_name') => $value['name'],
                $this->lang->line('application_price') => floatval($value['price']),
                $this->lang->line('application_qty') => $value['qty'],
                $this->lang->line('application_product_create') => date('d/m/Y H:i:s', strtotime($value['date_create'])),
                $this->lang->line('application_instructions') => $instrucoes,
            ));
        }

        $this->CreateXLS($listInfo, 'Produtos para integração');
    }

    public function ReceivablesXls()
    {
        if (!in_array('viewReceivables', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $data = $this->model_receivables->getReceivablesData();

        $arrReceivables = array();
        foreach ($data as $value) {

            $taxes = ($value['service_charge'] + $value['vat_charge']);
            $liquido = number_format(($value['net_amount'] - $taxes), 2, ',', '');

            if ($value['status'] == 1) {
                $paid_status = $this->lang->line('application_rec_1');
            } elseif ($value['status'] == 2) {
                $paid_status = $this->lang->line('application_rec_2');
            } elseif ($value['status'] == 3) {
                $paid_status = $this->lang->line('application_rec_3');
            } elseif ($value['status'] == 4) {
                $paid_status = $this->lang->line('application_rec_4');
            } elseif ($value['status'] == 5) {
                $paid_status = $this->lang->line('application_rec_5');
            }

            array_push($arrReceivables, array(
                $this->lang->line('application_id') => $value['id'],
                $this->lang->line('application_order') => $value['bill_no'],
                $this->lang->line('application_date_ready') => $value['date_ready'] == "" ? "" : date('d/m/Y H:i', strtotime($value['date_ready'])),
                $this->lang->line('application_date_requested') => $value['date_requested'] == "" ? "" : date('d/m/Y H:i', strtotime($value['date_requested'])),
                $this->lang->line('application_date_received') => $value['date_received'] == "" ? "" : date('d/m/Y H:i', strtotime($value['date_received'])),
                $this->lang->line('application_total') => number_format($value['gross_amount'], 2, ',', ''),
                $this->lang->line('application_discount') => number_format($value['other'], 2, ',', ''),
                $this->lang->line('application_logistics') => $value['logistics'],
                $this->lang->line('application_net_amount') => number_format($value['net_amount'], 2, ',', ''),
                $this->lang->line('application_taxes') => number_format($taxes, 2, ',', ''),
                $this->lang->line('application_net_value') => $liquido,
                $this->lang->line('application_status') => $paid_status,
            ));
        }

        $this->CreateXLS($arrReceivables, $this->lang->line('application_receivables'));
    }

    // create xlsx
    public function OrdersSemFreteXls()
    {
        if (!in_array('viewOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // load model
        $listInfo = $this->model_orders->ExcelListSemFrete();
        $listTranslate = array();
        foreach ($listInfo as $num => $linha) {
            foreach ($linha as $key => $val) {
                if ($key == 'application_status') {
                    $listTranslate[$num][$this->lang->line($key)] = $this->lang->line('application_order_' . $val);
                } else {
                    $listTranslate[$num][$this->lang->line($key)] = $val;
                }
            }
        }
        //$listInfo[0] = $header;
        //var_dump($listTranslate);
        $this->CreateXLS($listTranslate, 'Pedidos_Sem_Frete');
    }

    public function CreateXLS($listInfo, $fn)
    {
        ob_clean();
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        // set Header
        $col = 0;
        foreach ($listInfo[0] as $key => $val) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $key);
            $col++;
        }
        // set Row
        $rowCount = 2;
        foreach ($listInfo as $list) {
            $col = 0;
            foreach ($list as $key => $val) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $rowCount, trim($val));
                $col++;
            }
            $rowCount++;
        }
        $filename = "Planilha_" . $fn . "_" . date("Y-m-d-H-i-s") . ".xlsx";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function CategoriesMarketplaceXls()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // load model
        $listInfo = $this->model_categorias_marketplaces->getCategoryDataForExport();

        $this->CreateXLS($listInfo, 'Categorias_Markerplaces');
    }

    public function exportCSV($listInfo, $fn)
    {
        $filename = "Planilha_" . $fn . "_" . date("Y-m-d-H-i-s") . ".csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Encoding: UTF-8');
        //        header("Cache-Control: no-store, no-cache");
        header("Content-Disposition: attachment;filename=" . $filename);

        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo "\xEF\xBB\xBF";

        // CRIA NOME DAS COLUNAS
        $delimiter = '';
        if (!empty($listInfo)) {
            foreach (array_keys($listInfo[0]) as $field) {
                if (preg_match('/\\r|\\n|;|"/', $field)) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                echo $delimiter . $field;
                $delimiter = ';'; // separador dos campos
            }
            echo "\r\n"; // quebra de linha
        }
        foreach ($listInfo as $row) {
            $delimiter = '';
            foreach ($row as $field) {
                if (preg_match('/\\r|\\n|;|"/', $field)) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
                echo $delimiter . $field;
                $delimiter = ';'; // separador dos campos
            }
            echo "\r\n"; // quebra de linha
        }
    }

    public function errorsTransformationxls($mkt = '')
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        // load model
        $listInfo = $this->model_errors_transformation->getDataExport($mkt);
        $this->exportCSV($listInfo, 'ErrorsTransformation');
    }

    public function integrationsCsv()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $data = $this->model_stores->getStoresIntegration();        
        $encoder = (new CharsetConverter())->inputEncoding('utf-8')->outputEncoding('iso-8859-15');
        $newCsv = Writer::createFromString("");
        $newCsv->addFormatter($encoder);
        $newCsv->setDelimiter(';');
        $newCsv->insertOne(array($this->lang->line('application_store'), 
            $this->lang->line('application_company'), 
            $this->lang->line('application_date_requested'),  
            $this->lang->line('application_status'), 
            'ERP',
            'Descrição do Problema'));
        foreach ($data as $key => $value) {
            switch ($value['status']) {
                case 0: $status = "Pendente"; break;
                case 1: $status = "Concluído"; break;
                case 2: $status = "Problema"; break;
                case 3: $status = "Problema - Resolvido"; break;
                case 4: $status = "Pendente - Modificado"; break;
                default: $status = "Pendente";
            }
            $company = $this->model_company->getCompanyData($value['company_id']);
            $newCsv->insertAll(array(array(
                $value['name'],
                $company['name'],
                date('d/m/Y H:i', strtotime($value['date_updated'])),
                $status,ucfirst($value['integration']),
                $value['description_integration'] )));
        } 
        $newCsv->output('Stores_Integrations.csv');
    }

    public function billerModuleXls()
    {
        if (!in_array('doIntegration', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $result = array();
        $data = $this->model_stores->getRequestStoreInvoice();

        foreach ($data as $key => $value) {

            array_push($result, array(
                $this->lang->line('application_store') => $value['store_name'],
                $this->lang->line('application_company') => $value['company_name'],
                'ERP' => $value['erp'],
                'Token Tiny' => $value['token_tiny'],
                $this->lang->line('application_date_requested') => date('d/m/Y H:i', strtotime($value['created_at'])),
                $this->lang->line('application_status') => $value['active'] == 1 ? $this->lang->line('application_active') : $this->lang->line('application_inactive'),
            ));
        }

        $this->CreateXLS($result, 'Modulo Faturador');
    }

    public function exampleChargeProduct()
    {
        if ($this->session->userdata('userstore') != 0) {
            $store_id = (int) $this->session->userdata('userstore');
        } else {
            $stores = $this->model_stores->getCompanyStores($this->session->userdata('usercomp'));
            if (count($stores) > 1) {
                $store_id = '';
            }

            $store_id = (int) $stores[0]['id'];
        }

        $newCsv = Writer::createFromString("");
        $encoder = (new CharsetConverter())->outputEncoding('utf-8');
        $newCsv->addFormatter($encoder);
        $newCsv->setDelimiter(';'); // demiliter de cada coluna
        $newCsv->insertOne(array('ID da Loja', 'Sku do Parceiro', 'Quantidade em estoque')); // cabeçalho
        $newCsv->insertAll(array(array($store_id, 'T-001', '11'), array($store_id, 'T-002', '13'))); // linhas
        $newCsv->output('sample_products.csv'); // arquivo de saida
    }

    public function exampleChargeProductCreate()
    {
        if ($this->session->userdata('userstore') != 0) {
            $store_id = (int) $this->session->userdata('userstore');
        } else {
            $stores = $this->model_stores->getCompanyStores($this->session->userdata('usercomp'));
            if (!count($stores)) {
                $store_id = '';
            } elseif (count($stores) > 1) {
                $store_id = '';
            } else {
                $store_id = (int) $stores[0]['id'];
            }
        }

        $newCsv = Writer::createFromString("");
        $newCsv->setDelimiter(';'); // demiliter de cada coluna
        $newCsv->insertOne(array('ID da Loja', 'Sku do Parceiro', 'EAN', 'Catalogo', 'Quantidade em estoque')); // cabeçalho
        $newCsv->insertAll(array(array($store_id, 'T-001', '2431447005P', '1', '0'), array($store_id, 'T-002', '	243185700538', '1', '0'))); // linhas
        $newCsv->output('sample_products.csv'); // arquivo de saida
    }

    public function testeExportaProdutosPublicados($intto = null)
    {
        if ($this->session->userdata('usercomp') != 1) {
            redirect('dashboard', 'refresh');
        }

        $where = $intto ? " and pi.int_to = '{$intto}'" : '';
        $query = $this->db->query('
            SELECT *, s.name as loja, p.name as produto
            FROM prd_to_integration pi
            JOIN products p on p.id=pi.prd_id
            JOIN stores s on s.id=p.store_id
            WHERE pi.status_int=2' . $where);
        $rs = $query->result_array();
        $arr = [];
        foreach ($rs as $r) {
            array_push($arr, array(
                $r['skumkt'],
                $r['sku'],
                $r['produto'],
                $r['int_to'],
                $r['loja'],
            ));
        }
        $encoder = (new CharsetConverter())->inputEncoding('utf-8')->outputEncoding('iso-8859-15');
        $newCsv = Writer::createFromString("");
        $newCsv->addFormatter($encoder);
        $newCsv->setDelimiter(';'); // demiliter de cada coluna
        $newCsv->insertOne(array('SKU MKT', 'SKU Seller', 'Nome Produto', 'Marketplace', 'Loja')); // cabeçalho
        $newCsv->insertAll($arr); // linhas
        $newCsv->output('prds_mkt.csv'); // arquivo de saida
    }

    public function catalogsXls()
    {
        $catalogs = $this->model_catalogs->getExportCatalogsByStore();

        $arrCatalogs = array();

        foreach ($catalogs as $catalog) {
            array_push($arrCatalogs, array(
                $this->lang->line('application_id') => $catalog['id'],
                $this->lang->line('application_name') => $catalog['name'],
                $this->lang->line('application_store') => $catalog['store_id'],
            ));
        }

        $this->CreateXLS($arrCatalogs, 'Catalogs');
    }

    public function AddressPickUpXls()
    {
        $orders = $this->model_orders->getAddressPickUpByOrder();
        $arrOrders = array();

        foreach ($orders as $order) {
            array_push($arrOrders, array(

                $this->lang->line('application_order') => $order['order_id'],
                $this->lang->line('application_status') => $order['nome'],
                $this->lang->line('application_date') => $order['data_ocorrencia'],
                $this->lang->line('application_message') => $order['mensagem'],
                $this->lang->line('application_location') => $order['addr_place'],
                $this->lang->line('application_address') => $order['addr_name'],
                $this->lang->line('application_number') => $order['addr_num'],
                $this->lang->line('application_zip_code') => $order['addr_cep'],
                $this->lang->line('application_neighb') => $order['addr_neigh'],
                $this->lang->line('application_city') => $order['addr_city'],
                $this->lang->line('application_uf') => $order['addr_state'],
            ));
        }
        $this->CreateXLS($arrOrders, 'Address Pick Up');
    }

    public function labelsShippingCompanyWithTracking()
    {

        $logisticAutoApproval = (new LogisticTypesWithAutoFreightAcceptedGeneration(
            $this->db
        ))->setEnvironment(
            $this->sellerCenter
        );

        $stores = $this->input->get('stores') ?? '';
        $shipping = $this->input->get('shipping') ?? '';

        $stores = $stores === 'null' ? '' : $stores;
        $shipping = $shipping === 'null' ? '' : $shipping;

        $stores = explode(',', $stores);
        $shipping = explode(',', $shipping);

        $company_id = $this->session->userdata('usercomp');
        $store_id = $this->session->userdata('userstore');

        // Caso não for admin filtrar apenas a loja do usuário logado
        if ((isset($company_id) && $company_id != 1)) {
            if ((isset($store_id) && $store_id != 0)) {
                $stores = [$store_id];
            }
        }

        $orders = $this->model_freights->getEtiquetasCarrierExport($stores, $shipping);
        $arrOrders = [];
        $logistic = [];

        foreach ($orders as $order) {
            if (!array_key_exists($order['store_id'], $logistic)) {
                $store = $this->model_stores->getStoresData($order['store_id']);
                $logistic[$order['store_id']] = $this->calculofrete->getLogisticStore([
                    'freight_seller' => $store['freight_seller'],
                    'freight_seller_type' => $store['freight_seller_type'],
                    'store_id' => $store['id']
                ]);
                $logistic[$order['store_id']]['isLogisticTypeWithAutoFreightAcceptedGeneration'] = $logisticAutoApproval
                    ->isLogisticTypeWithAutoFreightAcceptedGeneration(
                        $logistic[$order['store_id']]['type']
                    );
            }

            if (!Model_orders::isFreightAcceptedGeneration($order) &&
                !$logistic[$order['store_id']]['isLogisticTypeWithAutoFreightAcceptedGeneration']
            ) {
                continue;
            }

            if (
            (
                $logistic[$order['store_id']]['type'] !== 'sgpweb' ||
                (
                    $logistic[$order['store_id']]['type'] !== 'sellercenter' &&
                    $logistic[$order['store_id']]['sellercenter']
                )
            )
            ) {
                array_push($arrOrders, array(
                    $this->lang->line('application_order') => $order['id'],
                    $this->lang->line('application_emission_date') => date('d/m/Y H:i', strtotime($order['data_etiqueta'])),
                    $this->lang->line('application_tracking_code') => $order['codigo_rastreio'],
                    $this->lang->line('application_store_name') => $order['store_name'],
                    $this->lang->line('application_ship_company') => $order['ship_company'],
                ));
            }
        }
        $this->exportCSV($arrOrders, 'Labels_Shipping_Company');
//        $this->CreateXLS($arrOrders, 'Labels_Shipping_Company');
    }

    // create xlsx
    public function ReturnProductXls()
    {
        if (!in_array('viewReturnOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        // create file name
        $fileName = 'data-' . time() . '.xlsx';

        // load model
        // Do banco de dados, recebe a lista de produtos devolvidos.
        // $listInfo = $this->model_product_return->fetchReturnedProducts();
        $listInfo = $this->model_product_return->excelList();
        $listTranslate = array();

        // Ordenação de colunas.
        $columns = [
            'application_id',                           // id
            'application_order_number',                 // order_id
            'application_order_marketplace',            // numero_marketplace
            'application_shipping_company',             // logistic_operator_type
            'application_shipping_type',                // logistic_operator_name
            'application_order_status',                 // status
            'application_tracking_number',              // *tracking_Number
            'application_reverse_logistic_code',        // *reverse_logistic_code
            'application_nfe_num',                      // devolution_invoice_number
            'application_return_product_total_value',   // return_total_value
            'application_devolution_request_date',      // *devolution_request_date
            'application_devolution_contract_date',     // *devolution_contract_date
            'application_devolution_date',              // *devolution_date
            'application_return_shipping_value',        // *return_shipping_value
            'application_sku_marketplace',              // *sku_marketplace
            'application_quantity_requested',           // *quantity_requested
            'application_quantity_in_order',            // *quantity_in_order
            'application_motive',                       // *motive
            'application_returned_item',                // product_id
            'application_store'                         // store_name
        ];

        $orderId = 0;
        $itemCount = 1;
        foreach ($listInfo as $num => $linha) {
            foreach ($columns as $column) {
                if (!isset($linha[$column])) {
                    $linha[$column] = '';
                }

                if ($column == 'application_id') {
                    $linha[$column] = $linha['id'];
                }

                if ($column == 'application_order_number') {
                    $linha[$column] = $linha['order_id'];
                }

                if ($column == 'application_order_marketplace') {
                    $linha[$column] = $linha['numero_marketplace'];
                }

                if ($column == 'application_shipping_company') {
                    $linha[$column] = $linha['logistic_operator_type'];
                }

                if ($column == 'application_shipping_type') {
                    $linha[$column] = $linha['logistic_operator_name'];
                }

                if ($column == 'application_order_status') {
                    $linha[$column] = strtoupper($linha['status']);
                }

                if ($column == 'application_tracking_number') {
                    $linha[$column] = strtoupper($linha['tracking_Number']);
                }

                if ($column == 'application_reverse_logistic_code') {
                    $linha[$column] = strtoupper($linha['reverse_logistic_code']);
                }

                if ($column == 'application_nfe_num') {
                    $linha[$column] = $linha['devolution_invoice_number'];
                }

                if ($column == 'application_return_product_total_value') {
                    $linha[$column] = $linha['return_total_value'];
                }

                if ($column == 'application_devolution_request_date') {
                    $linha[$column] = $linha['devolution_request_date'];
                }

                if ($column == 'application_devolution_contract_date') {
                    $linha[$column] = $linha['devolution_contract_date'];
                }

                if ($column == 'application_devolution_date') {
                    $linha[$column] = $linha['devolution_date'];
                }

                if ($column == 'application_return_shipping_value') {
                    $linha[$column] = $linha['return_shipping_value'];
                }

                if ($column == 'application_sku_marketplace') {
                    $linha[$column] = $linha['sku_marketplace'];
                }

                if ($column == 'application_quantity_requested') {
                    $linha[$column] = $linha['quantity_requested'];
                }

                if ($column == 'application_quantity_in_order') {
                    $linha[$column] = $linha['quantity_in_order'];
                }

                if ($column == 'application_motive') {
                    $linha[$column] = $linha['motive'];
                }

                if ($column == 'application_returned_item') {
                    $linha[$column] = $linha['product_id'];
                }

                if ($column == 'application_store') {
                    $linha[$column] = $linha['store_name'];
                }

                $listTranslate[$num][$this->lang->line($column)] = $linha[$column];
            }
            $orderId = $linha['application_id'];
        }

        $this->exportCSV($listTranslate, 'Produtos_devolvidos');
    }

    public function integration_logs()
    {
        $criteria = cleanArray($this->input->get());
        foreach ($criteria as $k => $v) {
            if (is_string($v))
                $criteria[$k] = urldecode($v);
        }
        if (!isset($criteria['store_id']) || $criteria['store_id'] == 0) {
            $criteria['store_id'] = $this->data['userstore'];
        }
        $logResults = $this->model_log_integration_unique->getLogByCriteria($criteria);

        $mapRows = [];
        foreach ($logResults as $logResult) {
            $mapRow['Tipo'] = $logResult['type'] == 'E' ? 'ERRO' : ($logResult['type'] == 'W' ? 'ALERTA' : 'SUCESSO');
            $mapRow['Loja'] = "{$logResult['store_name']} ({$logResult['store_id']})";
            $mapRow['Título'] = $logResult['title'];
            $mapRow['Descrição'] = strip_tags($logResult['description']);
            $mapRow['Data'] = date('d/m/Y H:i:s', strtotime($logResult['date_updated']));
            $mapRow['Unique ID'] = $logResult['unique_id'] ?? 0;

            array_push($mapRows, $mapRow);
        }
        if ($mapRows > 0) {
            $this->CreateXLS($mapRows, "Log_Integration");
        }
        return;
    }

    public function productXlsNew()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }


        $params = $this->input->get();
        foreach ($params as $f => $value) {
            if (is_string($value)) {
                $params[$f] = urldecode($value);
            }
        }

        $params['variation'] = (((string)($params['variation'] ?? false)) == 'true');
        if (is_array($params['search'] ?? '')) {
            $params['search'] = current(array_values($params['search']));
        }

        if (($this->data['usercomp'] ?? -1) != 1) {
            $params['company_id'] = $this->data['usercomp'];
        }

        if (($this->data['userstore'] ?? -1) != 0) {
            $params['store_id'] = $this->data['userstore'];
        }

        if (trim($params['with_stock'] ?? '') == '') {
            unset($params['with_stock']);
        }
        if (trim($params['is_kit'] ?? '') == '') {
            unset($params['is_kit']);
        }
        $nroRegisters = $this->model_products->countGetProductsByCriteria($params);
        if ($nroRegisters == 0) {
            $this->session->set_flashdata('warning', lang('application_not_export_csv_empty'));
            redirect('products', 'refresh');
            return;
        }
        if (!$this->model_csv_generator_export->save(0, [
            'sql_genereted' => $this->model_products->getSelectQueryToExportProductsByCriteria($params),
            'file_name' => 'Product_exports_' . (new DateTime())->getTimestamp() . ".csv",
            'user_id' => $this->session->userdata['id'] ?? 0,
            'type' => ($params['variation'] ?? false) ? 'Variation' : 'Product',
        ])) {
            $this->session->set_flashdata('error', lang('application_csv_scheduled_for_export_error'));
            redirect('products', 'refresh');
        }
        if ($nroRegisters >= 10000) {
            $this->session->set_flashdata('warning', lang('application_csv_scheduled_for_export_massive'));
            redirect('products', 'refresh');
            return;
        }
        $this->session->set_flashdata('success', lang('application_csv_scheduled_for_export'));
        redirect('products', 'refresh');
    }

    public function ProductCatalog(int $catalog_id)
    {
        ob_start();
        $offset = 0;
        $limit = 200;
        $arrProducts = array();
        while (true) {
            $products = $this->model_products_catalog->getProductsCatalogbyCatalogid($catalog_id, $offset, $limit);

            if (empty($products)) {
                break;
            }

            $offset += $limit;

            foreach ($products as $product) {
                // pegar cainho de imagens
                $images = array();
                $fotos = array();
                if (!$products['is_on_bucket']) {
                    if (is_dir(FCPATH . 'assets/images/catalog_product_image/' . $product['image'])) {
                        $fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $product['image']);
                    }

                    foreach ($fotos as $foto) {
                        if (($foto != ".") && ($foto != "..") && ($foto != "")) {
                            $images[] = base_url('assets/images/catalog_product_image/' . $product['image'] . '/' . $foto);
                        }
                    }
                } else {
                    if ($this->bucket->isDirectory('assets/images/catalog_product_image/' . $product['image'])) {
                        $fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . $product['image']);
                    }

                    foreach ($fotos['contents'] as $foto) {
                        if ($foto['url'] != "") {
                            $images[] = $foto['url'];
                        }
                    }
                }

                $images = implode(",", $images);

                $category = (int) filter_var($product['category_id'], FILTER_SANITIZE_NUMBER_INT);
                $category_name = $category == 0 ? "" : $this->model_category->getCategoryData($category)['name'];

                $brand = (int) filter_var($product['brand_id'], FILTER_SANITIZE_NUMBER_INT);
                $brand_name = $brand == 0 ? "" : $this->model_brands->getBrandData($brand)['name'];

                $collection = "";
                if (isset($product['attribute_value'])) {
                    $collection = $product['attribute_value'] ?? "";
                }

                if (array_key_exists($product['id'], $arrProducts)) {
                    if ($this->identifying_technical_specification && $this->identifying_technical_specification['status'] == 1) {
                        $arrProducts[$product['id']][] = array("Coleção" => $collection);
                    }
                } else {
                    $arrProducts[$product['id']] = array(
                        "ID da Loja" => $this->session->userdata('userstore') ?: '',
                        "Catalogo" => $catalog_id,
                        "EAN Produto de Catalogo" => $product['EAN'],
                        "Fabricante" => $brand_name, //utf8_decode($brand_name),
                        "Sku do Parceiro" => '',
                        "Quantidade em estoque" => '',
                        "Limite Desconto(%)" => '',
                        "Prazo Operacional em dias" => '',
                        "Status(0=Inativo|1=Ativo)" => 1,
                        "Nome do Item" => $product['name'], //utf8_decode($product['name']),
                        "Preco de Venda" => floatval($product['price']),
                        "SKU no fabricante" => utf8_decode($product['brand_code']),
                        "Categoria" => $category_name, //utf8_decode($category_name),
                        "Peso Liquido em kgs" => $product['net_weight'],
                        "Peso Bruto em kgs" => $product['gross_weight'],
                        "Largura em cm" => $product['width'],
                        "Altura em cm" => $product['height'],
                        "Profundidade em cm" => $product['length'],
                        "NCM" => $product['NCM'],
                        "Origem do Produto _ Nacional ou Estrangeiro" => $product['origin'],
                        "Garantia em meses" => $product['warranty'],
                        "Produtos por embalagem" => $product['products_package'],
                        "Descricao do Item _ Informacoes do Produto" => $product['description'], //utf8_decode($product['description']),
                        "Imagens" => $images
                    );

                    if($this->identifying_technical_specification && $this->identifying_technical_specification['status'] == 1){
                        $arrProducts[$product['id']]["Coleção"] = $collection;
                    }
                }
            }
        }

        ob_clean();
        sort($arrProducts);
        $this->exportCSV($arrProducts, 'ProdutosCatalog');
    }

    public function ProductCatalogExport(int $catalog_id)
    {
        
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $fileName = 'ProductCatalog_exports_' . (new DateTime())->getTimestamp() . ".csv";
        
        // Salva o SQL e metadados na tabela de exportação.
        if (!$this->model_csv_generator_export->save(0, [
            'sql_genereted' => $this->model_products_catalog->getSelectQueryToExportProductsByCatalogId($catalog_id),
            'file_name' => $fileName,
            'user_id' => $this->session->userdata('id') ?? 0,
            'type' => 'ProductCatalog',
        ])) {
            $this->session->set_flashdata('error', lang('application_csv_scheduled_for_export_error'));
            redirect('ProductsLoadByCSV/CatalogProduct', 'refresh');
        }

        // Exibir mensagem de sucesso para o usuário.
        $this->session->set_flashdata('success', lang('application_csv_scheduled_for_export'));
        redirect('ProductsLoadByCSV/CatalogProduct', 'refresh');
    }

    /**
     * Realiza uma exportação dos produtos de catálogo para a loja do usuário.
     */
    public function ProductCatalogExportByStore()
    {
        // Verifica se tem permissão.
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        // Busca o id da loja do usuário.
        $userstore = $this->session->userdata("userstore");

        // Verifica se tem loja definida.
        if ($userstore == 0) {
            $this->session->set_flashdata('error', lang('application_store_not_set'));
            redirect('ProductsLoadByCSV/CatalogProduct', 'refresh');
        }

        $fileName = 'ProductCatalog_exports_' . (new DateTime())->getTimestamp() . ".csv";

        // Salva o SQL e metadados na tabela de exportação.
        if (!$this->model_csv_generator_export->save(0, [
            'sql_genereted' => $this->model_products_catalog->getSelectQueryToExportProductsByStoreId($userstore),
            'file_name' => $fileName,
            'user_id' => $this->session->userdata('id') ?? 0,
            'type' => 'ProductCatalogStore',
        ])) {
            $this->session->set_flashdata('error', lang('application_csv_scheduled_for_export_error'));
            redirect('ProductsLoadByCSV/CatalogProduct', 'refresh');
        }

        // Exibir mensagem de sucesso para o usuário.
        $this->session->set_flashdata('success', lang('application_csv_scheduled_for_export'));
        redirect('ProductsLoadByCSV/CatalogProduct', 'refresh');
    }

}


