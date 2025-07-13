<?php

require APPPATH . "controllers/BatchC/Integration/Integration.php";

class Main extends Integration
{
    public function __construct()
    {
        parent::__construct();
        $this->setTypeIntegration('bling');
    }

    /**
     * Define os dados para integração
     *
     * @param $store_id
     */
    public function setDataIntegration($store_id)
    {
        $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
        $dataStore       = $this->model_stores->getStoresData($store_id);

        $credentials = json_decode($dataIntegration['credentials']);

        $this->setStore($store_id);
        $this->setCompany($dataStore['company_id']);

        if (!isset($credentials->apikey_bling)) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Credenciais inválidas ou sem permissão para acesso";
            $this->shutAppDesc = "Credenciais inválidas ou sem permissão para acesso, reveja suas credenciais cadastradas em Integração -> Solicitar Integração e/ou permissões na plataforma";
            return false;
        }

        $this->setToken($credentials->apikey_bling);
        $this->setMultiStore($credentials->loja_bling ?? '');
        $this->setGeneralStock(!isset($credentials->stock_bling) || $credentials->stock_bling == '' ? null : $credentials->stock_bling);

        $validate = $this->validateToken();
        if(!$validate) {
            $this->shutAppStatus = true;
            if ($validate === false) {
                $this->shutAppTitle = "Credenciais inválidas ou sem permissão para acesso";
                $this->shutAppDesc = "Credenciais inválidas ou sem permissão para acesso, reveja suas credenciais cadastradas em Integração -> Solicitar Integração e/ou permissões na plataforma";
            }
            elseif ($validate === null) {
                $this->shutAppTitle = "Requisições excedido";
                $this->shutAppDesc = "O limite de requisições diario foi atingido";
            }
        }

        // verifica se em Processos->Administrar Integração está ativo
        if(!$this->validateIntegrationActive()) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Loja com integração inativa";
            $this->shutAppDesc = "<h5>Loja está com a configuração de integração inativa.</h5> <ul><li>Isso pode ter acontecido por alteração de credenciais ou inativação por algum administrador.</li></ul>";
        }
    }

    /**
     * Converter um array em XML
     *
     * @param   array   $data   Array com os dados para conversão
     * @param   string  $name   Nome da chave primária
     * @param   null    $doc    Arquivo de download(ainda não usado)
     * @param   null    $node
     * @return  string          Retorna um XML
     */
    public function arrayToXml($data, $name='pedido', &$doc=null, &$node=null){
        if ($doc==null){
            $doc = new DOMDocument('1.0','UTF-8');
            $doc->formatOutput = TRUE;
            $node = $doc;
        }

        if (is_array($data)){
            foreach($data as $var=>$val){
                if (is_numeric($var)){
                    $this->arrayToXml($val, $name, $doc, $node);
                }else{
                    if (!isset($child)){
                        $child = $doc->createElement($name);
                        $node->appendChild($child);
                    }

                    $this->arrayToXml($val, $var, $doc, $child);
                }
            }
        }else{
            $child = $doc->createElement($name);
            $node->appendChild($child);
            $textNode = $doc->createTextNode($data);
            $child->appendChild($textNode);
        }

        if ($doc==$node) return $doc->saveXML();
    }

    public function formatDateFilterBling()
    {
        if ($this->dateLastJob) {
            $space = '%20';
            $this->dateStartJob = date("d/m/Y{$space}H:i:s", strtotime($this->dateStartJob));
            $this->dateLastJob = date("d/m/Y{$space}H:i:s", strtotime($this->dateLastJob));
        }
    }
}