<?php
defined('BASEPATH') or exit('No direct script access allowed');

use League\Csv\Reader;
use League\Csv\Statement;

require_once (APPPATH . "libraries/Traits/LengthValidationProduct.trait.php");
require APPPATH . "libraries/Traits/ValidationSkuSpace.trait.php";
require_once APPPATH . "controllers/Api/V1/Helpers/VariationTypeHelper.php";
class VerifyFieldsImport
{
    use ValidationSkuSpace;
    use LengthValidationProduct;
    use VariationTypeHelper {
        VariationTypeHelper::__construct as private __varTypeHelperConstruct;
    }

    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';

    public const MAP_STATUS = [
        self::STATUS_ENABLED => 1,
        self::STATUS_DISABLED => 2,
    ];
    protected $CI;

    private $arrColumnsUpdate = array(
        'images'                => array('columnDatabase' => 'image','type' => 'A', 'required' => false),
        'name'                  => array('columnDatabase' => 'name','type' => 'S', 'required' => true),
        'sku'                   => array('columnDatabase' => 'sku', 'type' => 'S', 'required' => true),
        'active'                => array('columnDatabase' => 'status','type' => 'S', 'required' => true),
        'description'           => array('columnDatabase' => 'description','type' => 'S', 'required' => true),
        'price'                 => array('columnDatabase' => 'price','type' => 'F', 'required' => true),
        'list_price'            => array('columnDatabase' => 'list_price','type' => 'F', 'required' => false),
        'qty'                   => array('columnDatabase' => 'qty','type' => 'F', 'required' => true),
        'ean'                   => array('columnDatabase' => 'EAN','type' => 'S', 'required' => false),
        'sku_manufacturer'      => array('columnDatabase' => 'codigo_do_fabricante','type' => 'S', 'required' => false),
        'net_weight'            => array('columnDatabase' => 'peso_liquido','type' => 'F', 'required' => true),
        'gross_weight'          => array('columnDatabase' => 'peso_bruto','type' => 'F', 'required' => true),
        'width'                 => array('columnDatabase' => 'largura','type' => 'F', 'required' => true),
        'height'                => array('columnDatabase' => 'altura','type' => 'F', 'required' => true),
        'depth'                 => array('columnDatabase' => 'profundidade','type' => 'F', 'required' => true),
        'items_per_package'     => array('columnDatabase' => 'products_package','type' => 'I', 'required' => false),
        'guarantee'             => array('columnDatabase' => 'garantia','type' => 'I', 'required' => true),
        'ncm'                   => array('columnDatabase' => 'NCM','type' => 'S', 'required' => false),
        'origin'                => array('columnDatabase' => 'origin','type' => 'I', 'required' => true),
        'unity'                 => array('columnDatabase' => 'attribute_value_id','type' => 'S', 'required' => true),
        'manufacturer'          => array('columnDatabase' => 'brand_id','type' => 'S', 'required' => true),
        'extra_operating_time'  => array('columnDatabase' => 'prazo_operacional_extra','type' => 'S', 'required' => false),
        'category'              => array('columnDatabase' => 'category_id', 'type' => 'S', 'required' => false),
        'product_width'         => array('columnDatabase' => 'actual_width','type' => 'F', 'required' => false),
        'product_height'        => array('columnDatabase' => 'actual_height','type' => 'F', 'required' => false),
        'product_depth'         => array('columnDatabase' => 'actual_depth','type' => 'F', 'required' => false),
        //'prazo_operacional'     => array('columnDatabase' => 'prazo_operacional_extra', 'type' => 'I', 'required' => false),
    );
    private $arrColumnsInsert = array(
        'product_variations'=> array('columnDatabase' => 'product_variations','type' => '', 'required' => true),
        'types_variations'  => array('columnDatabase' => 'has_variants', 'type' => 'A', 'required' => false)
    );

    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_products');
        $this->_CI->load->model('model_orders');
        $this->_CI->load->model('model_settings');
        $this->instance = &get_instance();
        $this->readonlydb = $this->instance->load->database('readonly', TRUE);

        $this->_CI->lang->load('api', 'portuguese_br');

        //$this->_CI->lang->load('messages', 'english');
        $this->_CI->load->library('DeleteProduct', [
            'productModel' => $this->_CI->model_products,
            'ordersModel' => $this->_CI->model_orders,
            'lang' => $this->_CI->lang
        ], 'deleteProduct');
        $this->__varTypeHelperConstruct();

        $this->loadLengthSettings();

        if ($allowableTags = $this->_CI->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $settingPriceVariation = $this->_CI->model_settings->getSettingDatabyName('price_variation');

        if ($allowableTags = $this->_CI->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        if ($settingPriceVariation && $settingPriceVariation['status'] == 1) {
            $this->usePriceVariation = true;
        }
    }

    /**
     * Verificando campos de importação/atualização via planilha
     *
     * @param  string  $fileCSV   Arquivo csv
     * @param  int     $usercomp  id do usuário da copania
     * @return array   $response  Retorna array multidimensional com arrays de qnt de erros, acertos e linhas lidas
     */
    public function getVerifyFieldsImport(string $fileCSV, int $usercomp)
    {
        $errors         = [];
        $correct        = [];
        $response       = [];
        $datas          = [];

        $this->company_id = $usercomp;

        if(!$fileCSV){
            redirect('ProductsLoadByCSV/index', 'refresh');
        }
        $count = 1;
        $csv = Reader::createFromPath(getcwd() . "/$fileCSV"); // lê o arquivo csv
        $csv->setDelimiter(';'); // separados de colunas
        $csv->setHeaderOffset(0); // linha do header
        $headers = $csv->getHeader();

        // Processa o arquivo enviado
        $stmt  = new Statement();
        $datas = $stmt->process($csv);

        $validations    = [];
        $results        = [];
        $position       = 0;
        foreach ($datas as $cam => $data) {
            if (!preg_match('/^[0-9]+$/', $data['ID da Loja'])) {
                $position = $cam;
                $validations[] = [
                    0 => array(false, "Os valores das colunas ID da Loja precisam ser apenas números")
                ];
                break;
            }
            if(empty($data['ID da Loja']) || empty($data['Sku do Parceiro'] )){
                $validations[] = [
                    0 => array(false, "Os valores das colunas ID da Loja e Sku do Parceiro são obrigatórias o preenchimento")
                ];
                break;
            }else{
                $totalColumns = count($headers);
                if($totalColumns > 3 && $totalColumns <= 7){
                    $columnsAccept = [
                        "ID da Loja",
                        "Sku do Parceiro",
                        "Sku Produto Pai",
                        "Preco de Venda",
                        "Preco de lista",
                        "Quantidade em estoque",
                        "Categoria",
                    ];
                    $columns_filter = array_intersect($columnsAccept, $headers);
                    if(!empty($columns_filter)){
                        $columns_update = [
                            'store_id'              => $data['ID da Loja'] ?? '',
                            'sku'                   => $data['Sku do Parceiro'] ?? '',
                            'sku_pai'               => $data['Sku Produto Pai'] ?? '',
                            'price'                 => $data['Preco de Venda'] ?? '',
                            'list_price'            => $data['Preco de lista'] ?? '',
                            'qty'                   => $data['Quantidade em estoque'] ?? '',
                            'category'              => $data['Categoria'] ?? '',
                        ];
                        $receivedHeader = array_filter($columns_update);
                        $columns_filter = $receivedHeader;
                    }else{
                        $columns_diff = array_diff($columnsAccept, $headers);
                        $validations[] = [
                            0 => array(false, 'Coluna(s) inválida(s): '. implode(', ', $columns_diff))
                        ];
                        break;
                    }
                }elseif($totalColumns == 3){
                    $columnsAccept = [
                        "ID da Loja",
                        "Sku do Parceiro",
                        "Status(1=Ativo|2=Inativo|3=Lixeira)",
                    ];
                    $columns_filter = array_intersect($columnsAccept, $headers);
                    if(!empty($columns_filter)){
                        $columns_inative = [
                            'store_id'    => $data['ID da Loja'] ?? '',
                            'sku'         => $data['Sku do Parceiro'] ?? '',
                            'status'      => $data['Status(1=Ativo|2=Inativo|3=Lixeira)'] ?? '',
                        ];
                        $receivedHeader = array_filter($columns_inative);
                        $columns_filter = $receivedHeader;
                    }else{
                        $columns_diff = array_diff($columnsAccept, $headers);
                        $validations[] = [
                            0 => array(false, 'Coluna(s) inválida(s): '. implode(', ', $columns_diff))
                        ];
                        break;
                    }
                }elseif($totalColumns <= 27){
                    $columnsAccept = [
                        "ID da Loja",
                        "Sku do Parceiro",
                        "Sku Produto Pai",
                        "Nome do Item",
                        "Preco de Venda",
                        "Preco de lista",
                        "Quantidade em estoque",
                        "Fabricante",
                        "SKU no fabricante",
                        "Categoria",
                        "EAN",
                        "Peso Liquido em kgs",
                        "Peso Bruto em kgs",
                        "Largura em cm",
                        "Altura em cm",
                        "Profundidade em cm",
                        "NCM",
                        "Origem do Produto _ Nacional ou Estrangeiro",
                        "Garantia em meses",
                        "Prazo Operacional em dias",
                        "Produtos por embalagem",
                        "Descricao do Item _ Informacoes do Produto",
                        "Imagens",
                        "Status(1=Ativo|2=Inativo|3=Lixeira)",
                        "Voltagem",
                        "Cor",
                        "Tamanho",
                        "Sabor",
                        "Grau",
                        "Lado",
                    ];
                    $missingColumns = array_intersect($columnsAccept, $headers);
                    if (!empty($missingColumns)) {
                        $columns_insert_or_update = [
                            'store_id'              => $data['ID da Loja'] ?? '',
                            'sku'                   => $data['Sku do Parceiro'] ?? '',
                            'sku_pai'               => $data['Sku Produto Pai'] ?? '',
                            'name'                  => $data['Nome do Item'] ?? '',
                            'price'                 => $data['Preco de Venda'] ?? '',
                            'list_price'            => $data['Preco de lista'] ?? '',
                            'qty'                   => $data['Quantidade em estoque'] ?? '',
                            'manufacturer'          => $data['Fabricante'] ?? '',
                            'sku_manufacturer'      => $data['SKU no fabricante'] ?? '',
                            'category'              => $data['Categoria'] ?? '',
                            'ean'                   => $data['EAN'] ?? '',
                            'net_weight'            => $data['Peso Liquido em kgs'] ?? '',
                            'gross_weight'          => $data['Peso Bruto em kgs'] ?? '',
                            'width'                 => $data['Largura em cm'] ?? '',
                            'height'                => $data['Altura em cm'] ?? '',
                            'depth'                 => $data['Profundidade em cm'] ?? '',
                            'ncm'                   => $data['NCM'] ?? '',
                            'origin'                => $data['Origem do Produto _ Nacional ou Estrangeiro'] ?? '',
                            'guarantee'             => $data['Garantia em meses'] ?? '',
                            'extra_operating_time'  => $data['Prazo Operacional em dias'] ?? '',
                            'items_per_package'     => $data['Produtos por embalagem'] ?? '',
                            'description'           => $data['Descricao do Item _ Informacoes do Produto'] ?? '',
                            'images'                => $data['Imagens'] ?? '',
                            'status'                => $data['Status(1=Ativo|2=Inativo|3=Lixeira)'] ?? '',
                            'size'                  => $data['Tamanho'] ?? '',
                            'color'                 => $data['Cor'] ?? '',
                            'voltage'               => $data['Voltagem'] ?? '',
                            'flavor'                => $data['Sabor'] ?? '',
                            'degree'                => $data['Grau'] ?? '',
                            'side'                  => $data['Lado'] ?? '',
                        ];
                        $receivedHeader = array_filter($columns_insert_or_update);
                        $columns_filter = $receivedHeader;
                    }else{
                        $columns_diff = array_diff($columnsAccept, $headers);
                        $validations[] = [
                            0 => array(false, 'Coluna(s) inválida(s): '. implode(', ', $columns_diff))
                        ];
                        break;
                    }
                }else{
                    return false;
                }

                $this->columns_filter = $columns_filter;
                foreach ($columns_filter as $key => $value) {
                    ${$key}    = $value;
                    $results[] = $this->verifyFields($key, $value);
                }
                $validations[] = $results;
                $results       = [];
            }
        }

        foreach($validations as $validation){
            foreach($validation as $v){
                switch ($v[0]){
                    case true:
                        $correct[] = "{$v[0]} ";
                    break;
                    case false:
                        $errors[] = "{$v[1]} ";
                    break;
                    default:
                        $errors[] = "ops, algum problema ao validar os dados da planilha";
                }
            }
            $datas_response[] = $errors;
            $errors           = [];
            $correct          = [];
        }

        $data_error   = 0;
        $count_accept = 0;
        $error_row    = [];
        $bodyerror    = [];
        $position     = 0;
        foreach ($datas_response as $key => $data_response){
            $position++;
            if(empty($data_response)){
                $count_accept++;
            }
            if(!empty($data_response)){

                $bodyerror = [
                   'error'    => $data_response,
                   'position' => $position
                ];

                $data_error++;
                $error_row[] = $bodyerror;
            }
        }
         return $response = [
            'payloadErrors'       => $error_row,
            'payloadErrorsTotal'  => $data_error,
            'payloadCorrectTotal' => $count_accept,
            'payloadTotalLines'   => count($validations),
        ];
    }
    /**
     * Validação de campo para cadastro/atualização de produtos
     *
     * @param   string   $key      Coluna atual sendo analizada.
     * @param   string   $value    Valor da coluna atual analizada.
     * @param   string   $type     Tipo de ação "I" = insert "U" = update "null" = validando campos de planilha
     * @param   array    $dataSql  Dados de conexão quando vem via api o request. "null" = vem via planilha
     */
    public function verifyFields($key, $value, $type = null, $dataSql = null) {

        if(is_null($dataSql)){
            if($key == 'store_id'){
                $this->store_id = $value;
            }
            if($key == 'sku'|| $key === 'SKU'){
                $this->sku = $value;
            }
        }

        $value_ok = array(true, $value);

        if ($key === 'status' && !empty($value)){
            if (!preg_match('/^[0-9]+$/', $value)) {
                return array(false, "No campo Status só é permitido apenas os valores 1,2 ou 3 ");
            }
            if(in_array($value, [Model_products::DELETED_PRODUCT])) {
                return array(false, $this->_CI->lang->line('api_not_move_product_trash'));
            }
        }

        if ($key === 'price') {
            if ($value === "" || (float)$value <= 0)
                return array(false, $this->_CI->lang->line('api_price_zero'));
                if($value){
                    if (strpos($value, ',') !== false) {
                        return array(false, "Só é aceito ponto em valores.");
                    }
                }
                $value_ok = array(true, (float)number_format($value, 2, '.', ''));
        }

        if ($key === 'list_price') {
            if ($value === "" || (float)$value <= 0){
                return array(false, "O Campo preço por não pode ser zero.");
            }elseif($value > 0) {
                if ($value) {
                    if (strpos($value, ',') !== false) {
                        return array(false, "Só é aceito ponto em valores.");
                    }
                }
            }else{
                $value_ok = array(true, (float)number_format($value, 2, '.', ''));
            }
        }
        if ($key === 'store_id' && !empty($value)) {
            if (!preg_match('/^[0-9]+$/', $value)) {
                return array(false, "O id da loja não pode ser texto. Apenas números.");
                $value = intval($value);
            }
            $value_ok = array(true, ($value));
        }
        if ($key === 'qty' && !empty($value)) {
            if (!preg_match('/^[0-9]+$/', $value)) {
                return array(false, "O campo Quantidade de estoque não pode ser texto. Apenas números.");
                $value = intval($value);
            }
            $value_ok = array(true, $value);
        }
        if ($key === 'sku' || $key === 'SKU') {
            $this->sku = $value;
            if ($value === "") return array(false, $this->_CI->lang->line('api_sku_blank'));

            if (!$this->verifySKUAvailable($value, $dataSql)) return array(false, $this->_CI->lang->line('api_sku_in_use'));

            $value_ok = array(true, str_replace("/", "-", trim($value)));
        }
        if ($key === "active") {
            if ($value != "enabled" && $value != "disabled")
                return array(false, $this->_CI->lang->line('api_active_field'));

            $value_ok = array(true, $value == "enabled" ? self::MAP_STATUS[self::STATUS_ENABLED] : self::MAP_STATUS[self::STATUS_DISABLED]);
        }
        if ($key === "ean" || $key === "EAN") {
            if (!$this->_CI->model_products->ean_check($value)) {
                return array(false, $this->_CI->lang->line('api_ean_invalid'));
            }
            return array(true, "EAN correto");
        }
        if ($key === "ncm" || $key === "NCM") {
            $value = filter_var(preg_replace('~[.-]~', '', $value), FILTER_SANITIZE_NUMBER_INT);
            $value = $value == 0 ? null : $value;
            if (strlen($value) != 8 && $value != "")
                return array(false, $this->_CI->lang->line('api_invalid_ncm'));

            $value_ok = array(true, trim($value));
        }
        if ($key === "origin" && ($value < 0 || $value > 8)) {
            return array(false, $this->_CI->lang->line('api_origin_product_code'));
        }

            if($key === 'sku_pai' && !empty($value)){
                $this->skuPaiExists = true;
            }elseif($key === 'sku_pai' && empty($value)){
                $this->skuPaiExists = false;
            }else{
                $this->skuPaiExists = false;
            }

            if ($key === "unity" || $key === "manufacturer" || $key === "category") {
                $value = trim($value);
                if ($key === "unity") {
                    $codeInfoProduct = $this->getCodeInfo('attribute_value', 'value', $value);
                } elseif ($key === "manufacturer") {
                    if(boolval($this->skuPaiExists) === true) {
                        $codeInfoProduct = '';
                        $this->skuPaiExists = false;
                    }else{
                        $codeInfoProduct = $this->getCodeInfo('brands', 'name', $value);
                    }
                } elseif ($key === "category") {

                    $permissios = $this->_CI->model_groups->getGroupDataFromIdUser($this->CI->user_id);
                    $permissios = unserialize($permissios['permission']);

                    if(in_array('disabledCategoryPermission', $permissios) == true){
                        $codeInfoProduct = '';
                        $this->skuPaiExists = false;
                    }else{
                        if (boolval($this->skuPaiExists) === true) {
                            $codeInfoProduct = '';
                            $this->skuPaiExists = false;
                        } else {
                            $codeInfoProduct = $this->getCodeInfo('categories', 'name', trim($value), "AND active = 1");
                        }
                    }
                }

                if ($codeInfoProduct) {
                    $value_ok = array(true, "[\"$codeInfoProduct\"]");
                } else {
                    if ($type == "I") {
                        $this->arrColumnsInsert = array_merge($this->arrColumnsUpdate, $this->arrColumnsInsert);
                    }
                    $required = $type == "I" ? $this->arrColumnsInsert[$key]['required'] : $this->arrColumnsUpdate[$key]['required'];
                    if ($key === "unity" && $required) {
                        return array(false, $this->_CI->lang->line('api_invalid_unit'));
                    } elseif ($key === "manufacturer" && $required) {
                        return array(false, $this->_CI->lang->line('api_invalid_manufacturer'));
                    } elseif ($key === "category" && $required && !empty(trim($value))) {
                        return array(false, $this->_CI->lang->line('api_invalid_category'));
                    } else {
                        $value_ok = array(true, '[""]');
                    }
                }
            }

        if ($key === "types_variations") {
            $varList = [];
            if (count($value) === 1 && $value[0] === "") {
                $value_ok = array(true, "produto sem variação");
            } else {
                foreach ($value as $type_v) {
                    switch ($type_v) {
                        case "size":
                            $varList[] = "TAMANHO";
                            break;
                        case "color":
                            $varList[] = "Cor";
                            break;
                        case "voltage":
                            $varList[] = "VOLTAGEM";
                            break;
                        case "flavor":
                            $varList[] = "SABOR";
                            break;
                        case "degree":
                            $varList[] = "GRAU";
                            break;
                        case "side":
                            $varList[] = "LADO";
                            break;
                        default:
                            list($type, $variation) = $this->fetchCustomAttributesMapByCriteria($type_v, []);
                            if ($type == $type_v) {
                                return array(false, $this->_CI->lang->line('api_type_variation'));
                            }
                            $varList[] = $type_v;
                    }
                }
                $value_ok = array(true, !empty($varList) ? implode(';', $varList) : "");
            }
        }

        if(boolval($this->skuPaiExists) === false) {
            if ($key === 'net_weight' && $value <= 0) {
                return array(false, $this->_CI->lang->line('api_net_weight_zero'));
            }
            if ($key === 'gross_weight' && $value <= 0) {
                return array(false, $this->_CI->lang->line('api_gross_weight_zero'));
            }
            if ($key === 'width' && $value <= 0) {
                return array(false, $this->_CI->lang->line('api_width_zero'));
            }
            if ($key === 'height' && $value <= 0) {
                return array(false, $this->_CI->lang->line('api_height_zero'));
            }
            if ($key === 'depth' && $value <= 0) {
                return array(false, $this->_CI->lang->line('api_depth_zero'));
            }
        }


        if ($key === 'items_per_package') {
            $value = (int)$value;
            if ($value <= 0) {
                $value = 1;
            }
            $value_ok = array(true, $value);
        }
        if ($key === 'name') {
            if(boolval($this->skuPaiExists) === false) {
                if($value == '' || empty($value)){
                  return array(false, "O campo " . $key ." não pode ser vázio");
                }
            }
            if (!$this->validateLengthName($value)) {
                return array(false, $this->getMessageLenghtNameInvalid());
            }
            $value_ok = array(true, trim($value));
        }
        if ($key === 'sku') {
            if (!$this->validateSkuSpace($value)) {
                  return array(false, $this->getMessagemSkuFormatInvalid());
            }
            if(!$this->validateLengthSku($value)){
                return array(false, $this->getMessageLenghtSkuInvalid());
            }
            $value_ok = array(true, $value);
        }
        if ($key === 'description') {
            if ($value === "") return array(false, $this->_CI->lang->line('api_description_blank'));

            if (!$this->validateLengthDescription($value))
                return array(false, $this->getMessageLenghtDescriptionInvalid());

            $value_ok = array(true, strip_tags_products($value, $this->allowable_tags));
        }

        if ($key === 'extra_operating_time' && $value > 100) {
            return array(false, $this->_CI->lang->line('api_extra_operating_time'));
        }
        return $value_ok;
    }
    private function verifySKUAvailable($sku, $dataSql)
    {
        if(!$this->store_id || !$this->company_id || !$this->sku ){
            $this->store_id   = $dataSql['store_id'];
            $this->company_id = $dataSql['company_id'];
            $this->sku        = null;
        }

        $sql = "SELECT * FROM products WHERE sku = '{$sku}' AND store_id = {$this->store_id} AND company_id = {$this->company_id} AND sku <> '{$this->sku}'";
        $query = $this->readonlydb->query($sql);
        return $query->num_rows() === 0 ? true : false;
    }
    public function getCodeInfo($table, $column, $value, $where = "", $columnCode = 'id')
    {
        if ($value == "") {
            return false;
        }

        $columnCodeBkp = $columnCode;
        if ($columnCode === 'id' && in_array($table, array('brands', 'categories'))) {
            $columnCode = 'id,active';
        }

        $a = "SELECT {$columnCode} FROM {$table} WHERE {$column} = ".'"'.$value.'"'." {$where}";
        $query = $this->readonlydb->query($a);
        //$query = $this->readonlydb->query("SELECT {$columnCode} FROM {$table} WHERE {$column} = '{$value}' {$where}");

        if($query->num_rows() === 0 && $table == "brands") {
            $sqlBrand = $this->readonlydb->insert_string('brands', array('name' => $value, 'active' => 1));
            $this->readonlydb->query($sqlBrand);
            $query = $this->readonlydb->query("SELECT {$columnCode} FROM {$table} WHERE {$column} = '{$value}' {$where}");
        }

        $columnCode = $columnCodeBkp;

        if($query->num_rows() === 0 && $table != "brands") {
            return false;
        }

        $result = $query->first_row();

        if (isset($result->active) && $result->active == 2) {
            return false;
        }

        if (count(explode('.', $columnCode)) == 2) {
            $columnCode =  explode('.', $columnCode)[1];
        }

        return $result->$columnCode;
    }
}