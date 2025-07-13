<?php
require APPPATH . "libraries/Traits/LengthValidationProduct.trait.php";
require APPPATH . "libraries/Traits/ValidationSkuSpace.trait.php";
if (!defined('VerifyFieldsProduct')) {
    define('VerifyFieldsProduct', '');
    /**
     * Esta trait tem como proposito validar os diversos campos pertencentes em produtos e variações.
     */
    trait VerifyFieldsProduct
    {
        use ValidationSkuSpace;
        use LengthValidationProduct;
        private $ALLOWABLE_TAGS = "<p><br><h1><h2><h3><h4><h5><h6><strong><b><em><i><u><small><ul><ol><li>";
        public $allowable_tags = null;
        /**
         * Verifica os campos para validação
         *
         * @param   string  $key        Campo para validação
         * @param   string  $value      Valor do campo
         * @param   boolean $required   Se é um campo obrigatório ou não
         * @param   string  $type       Tipo do dados para formatação
         * @param   bool    $newProduct Validação é para um novo produto
         * @return  array               Retorna uma array com 2 posições, a primeira diz o status da validação, a segunda uma mensagem complementar
         */
        public function verifyFieldsProduct($key, $value, $required, $type, $newProduct = true, $idProd = null)
        {
            // if($key!=='descricao'){
            //     echo ($key . " : " . (is_string($value) ? $value : json_encode($value)) . "\n");
            // }
            $value_ok = array(true, $this->_setValueFormat($value, $type));
            if ($key === 'preco' && $required) {
                if ($value === "" || (float)$value <= 0) return array(false, "O preço do produto não pode ser zero ou negativo!");
                $value_ok = array(true, number_format($this->_setValueFormat($value, $type), 2, '.', ''));
            }
            if ($key === 'sku' && $required && $newProduct) {
                // echo ($value . "\n");
                if ($value === "") return array(false, "O código SKU do produto precisa ser informado!");
                if (!$this->checkSkuAvailable($value, $idProd)) return array(false, "O código SKU já está em uso!");
                if (!$this->validateSkuSpace($value)) {
                    return array(false, $this->getMessagemSkuFormatInvalid($idProd != null ? 'variação' : 'produto'));
                }
                if (!$this->validateLengthSku($value)) {
                    return array(false, $this->getMessageLenghtSkuInvalid($idProd != null ? 'variação' : 'produto'));
                }
                $value_ok = array(true, $this->_setValueFormat($value, $type));
            } elseif ($key === "ean" && $required) {

                if (!$this->_this->model_products->ean_check($value))
                    return array(false, "Código EAN inválido!");
            } elseif ($key === "ncm" && $required) {

                $value = filter_var(preg_replace('~[.-]~', '', $value), FILTER_SANITIZE_NUMBER_INT);
                if (strlen($value) != 8 && $value != "")
                    return array(false, "Código NCM do produto está inválido, deve conter 8 caracteres!");

                $value_ok = array(true, trim($this->_setValueFormat($value, $type)));
            }
            if ($key === "origem" && ($value < 0 || $value > 8) && $required) {

                return array(false, "A origem do produto deve ser entre 0 e 8! http://legislacao.sef.sc.gov.br/html/regulamentos/icms/ricms_01_10.htm");
            }

            if ($key === "un" || $key === "fabricante") {

                $value = trim($value);

                if (empty($value)) {
                    if ($key === 'un') return array(false, "Unidade não pode estar em branco!");
                    if ($key === 'fabricante') return array(false, "Marca não pode estar em branco!");
                }

                if ($key === "un") $codeInfoProduct = $this->_getCodeInfo('attribute_value', 'value', $value);
                elseif ($key === "fabricante") $codeInfoProduct = $this->_getCodeInfo('brands', 'name', $value);

                if ($codeInfoProduct) $value_ok = array(true, $this->_setValueFormat("[\"{$codeInfoProduct}\"]", $type));
                else {
                    if ($key === "un") {

                        $existFromTo = false;
                        foreach ($this->tipoUnidades as $tipoUn => $realUn) {
                            if ($tipoUn == strtolower($value)) {
                                $searchUn = $this->_getCodeInfo('attribute_value', 'value', $realUn);
                                $value_ok = array(true, $this->_setValueFormat("[\"{$searchUn}\"]", $type));
                                $existFromTo = true;
                                break;
                            }
                        }

                        if ($required && !$existFromTo)
                            return array(false, "Unidade informada do produto não encontrada, informe uma válida. (UN/Kg). Valor informado: {$value}");
                    } elseif ($key === "fabricante" && $required) return array(false, "Marca '" . $value . "' informado do produto não encontrado, informe um válido ou abra um chamado para a criação.");
                    else $value_ok = array(true, $this->_setValueFormat('[""]', $type));
                }
            }
            if ($key === "variacoes") {
                $varArr     = array();
                $tipoVarArr = array();
                $varCodes   = array();
                $stockReal  = array();
                $varNotMultiLoja = array();
                $value = $value == "" ? array() : $value;

                foreach ($value as $keyVar => $type_v) {

                    $sku        = $type_v->variacao->codigo ?? $type_v->codigo;
                    $payloadVar = $this->getProductsMultiLoja($sku);

                    if ($sku == '')
                        return array(false, "Todas as variações precisam ter o código SKU preenchidos .");

                    if (!isset($payloadVar->produtoLoja)) {
                        array_push($varNotMultiLoja, $sku);
                        //echo "Variação {$sku} não está na multiloja\n";
                        continue;
                    }

                    $id         = $payloadVar->id;
                    $preco      = $payloadVar->produtoLoja->preco->precoPromocional == 0 ? $payloadVar->produtoLoja->preco->preco : $payloadVar->produtoLoja->preco->precoPromocional;
                    $variacoes  = $type_v->variacao->nome;

                    if (isset($type_v->variacao->estoqueAtual))
                        $stockReal[$id] = $this->getGeneralStock($type_v->variacao->depositos, $type_v->variacao->estoqueAtual);
                    else
                        $stockReal[$id] = $this->getGeneralStock($payloadVar->depositos ?? 0, $payloadVar->estoqueAtual ?? 0);

                    // define o sku e id da variação
                    $varArr[$keyVar]['sku']     = $sku;
                    $varArr[$keyVar]['id']      = $id;
                    $varArr[$keyVar]['preco']   = $preco;
                    $varArr[$keyVar]['variacao'] = array();
                    $varArr[$keyVar]['imagem']    = $payloadVar->imagem;  //rick
                    $varArr[$keyVar]['ean']        = $payloadVar->gtin; //rick 
                    $varArr[$keyVar]['clonardadospai'] = isset($payloadVar->clonarDadosPai) ? $payloadVar->clonarDadosPai : false; //rick 

                    array_push($varCodes, $id);

                    $variacoes = explode(';', $variacoes);

                    foreach ($variacoes as $var) {

                        $var = explode(':', $var);

                        if (!isset($var[0]) || !isset($var[1])) continue;

                        $tipo   = $var[0];
                        $valor  = str_replace(';', ',', $var[1]);

                        $realVarEnvia = false;

                        foreach ($this->tipoVariacoes as $tipoVar => $realVar) {
                            if ($this->likeText("%{$tipoVar}%", strtolower($tipo))) {
                                $realVarEnvia = $realVar;
                                continue;
                            }
                        }
                        if (!$realVarEnvia) continue;

                        if (!in_array($realVarEnvia, $tipoVarArr))
                            array_push($tipoVarArr, $realVarEnvia);

                        $varArr[$keyVar]['variacao'][$realVarEnvia] = $valor;
                    }

                    if (count($variacoes) != count($varArr[$keyVar]['variacao']))
                        return array(false, "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).");
                }

                foreach ($varArr as $varVerify) {
                    $verifyVarMerge = array();
                    foreach ($varVerify['variacao'] as $typeVarVerify => $valueVerify) {
                        array_push($verifyVarMerge, $typeVarVerify);
                    }
                    if (array_diff($verifyVarMerge, $tipoVarArr) || array_diff($tipoVarArr, $verifyVarMerge))
                        return array(false, "Todas as variações devem conter os mesmos tipos. Exemplo: <br> (Var1 = Cor:Preto;Tamanho:42) (Var2 = Cor:Preto;Tamanho: 40)");
                }

                $varStr = "";
                if (in_array('TAMANHO', $tipoVarArr))   $varStr .= ";TAMANHO";
                if (in_array('Cor', $tipoVarArr))       $varStr .= ";Cor";
                if (in_array('VOLTAGEM', $tipoVarArr))  $varStr .= ";VOLTAGEM";

                $value_ok = array(
                    true,
                    array(
                        'tipos'     => $varStr != "" ? substr($varStr, 1) : "",
                        'variacoes' => $varArr,
                        'codigos'   => $varCodes,
                        'estoque'   => $stockReal,
                        'varNotMultiLoja' => $varNotMultiLoja
                    )
                );
            }
            if ($key === 'peso_liquido' && $value <= 0 && $required) {
                return array(false, "O peso líquido do produto não pode ser zero.");
            }
            if ($key === 'peso_bruto' && $value <= 0 && $required) {
                return array(false, "O peso bruto do produto não pode ser zero.");
            }
            if ($key === 'largura' && $value <= 0 && $required) {
                return array(false, "A largura do produto não pode ser menor que 0(zero) cm.");
            }
            if ($key === 'altura' && $value <= 0 && $required) {
                return array(false, "A altura do produto não pode ser menor que 0(zero) cm.");
            }
            if ($key === 'comprimento' && $value <= 0 && $required) {
                return array(false, "A profundidade do produto não pode ser menor que 0(zero) cm.");
            }
            if ($key === 'nome') {
                if ($value == ""){
                    return array(false, 'Nome do item não pode ser branco');
                }
                if (!$this->validateLengthName($value)) {
                    // echo "\n*Erro tipo11:\n\"{$value}\" \n Resultando em " . strlen($this->removeAccentsAndCedilla($value)) . " caracteres que para o if temos: " . (strlen($this->removeAccentsAndCedilla($value)) > $this->product_length_name) . "\n\n";
                    return array(false, $this->getMessageLenghtNameInvalid());
                }
                $value_ok = array(true, trim($this->_setValueFormat($value, $type)));
            }
            if ($key === 'descricao' && $required) {
                if ($value == "")
                    return array(false, "A descrição do produto não pode estar em branco.");

                if (trim(strip_tags($value), " \t\n\r\0\x0B\xC2\xA0") == '')
                    return array(false, "A descrição do produto não pode estar em branco.");

                if (!$this->validateLengthDescription($value)) {
                    // echo "\n*Erro tipo12:\n\"{$value}\" \n Resultando em " . strlen($this->removeAccentsAndCedilla($value)) . " caracteres que para o if temos: " . (strlen($this->removeAccentsAndCedilla($value)) > $this->product_length_description) . "\n" . $this->getMessageLenghtDescriptionInvalid() . "\n\n";
                    return array(false, $this->getMessageLenghtDescriptionInvalid());
                }

                if (!property_exists($this->CI, 'model_settings')) {
                    $this->CI->load->model('model_settings');
                }
                if ($allowableTags = $this->CI->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
                    if (!empty($allowableTags)) {
                        $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
                    }
                }
                $value_ok = array(true, strip_tags_products($this->_setValueFormat($value, $type), $this->allowable_tags));
            }

            return $value_ok;
        }
        /**
         * @param   string      $sku    SKU a ser validado
         * @param   null|int    $prd_id Código do produto a ser ignorado
         * @return  bool
         */
        private function checkSkuAvailable($sku, $prd_id = null)
        {
            $where = '';
            if ($prd_id) $where .= " AND p.id <> {$prd_id}";

            $sql = "SELECT p.id,v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.store_id = ? {$where} AND (p.sku = ? OR v.sku = ?) limit 1";
            $query = $this->_this->db->query($sql, array($this->_this->store, $sku, $sku));
            return $query->row_array() ? false : true;
        }
        public function _getCodeInfo($table, $column, $value)
        {
            $this->CI->load->model('model_brands');
            $query = $this->_this->db
                ->select('id')
                ->from($table)
                ->where(array($column => $value))
                ->get();

            if ($query->num_rows() === 0 && $table == "brands") {
                if ($this->disable_brand_creation) {
                    return false;
                }
                return $this->CI->model_brands->create(array('name' => $value, 'active' => 1));
            }

            if ($query->num_rows() === 0 && $table != "brands") return false;

            $result = $query->first_row();

            return $result->id;
        }
        public function _setValueFormat($value, $type)
        {
            switch ($type) {
                case 'S':
                    return (string)$value;
                case 'A':
                    return (array)$value;
                case 'F':
                    return (float)$value;
                case 'I':
                    return (int)$value;
                default:
                    return $value;
            }
        }
    }
}
