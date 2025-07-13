<?php

require APPPATH . "controllers/BatchC/Integration/Integration.php";

class Main extends Integration
{
    public function __construct()
    {
        parent::__construct();
        $this->setTypeIntegration('jn2');
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

        if (!isset($credentials->token_jn2)) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Credenciais inválidas ou sem permissão para acesso";
            $this->shutAppDesc = "Credenciais inválidas ou sem permissão para acesso, reveja suas credenciais cadastradas em Integração -> Solicitar Integração e/ou permissões na plataforma";
            return false;
        }

        $this->setToken($credentials->token_jn2);

        if ($this->validateToken() === false) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Credenciais inválidas ou sem permissão para acesso";
            $this->shutAppDesc = "Credenciais inválidas ou sem permissão para acesso, reveja suas credenciais cadastradas em Integração -> Solicitar Integração e/ou permissões na plataforma";
        } elseif ($this->validateToken() === null) {
            $this->shutAppStatus = false;
            $this->shutAppTitle = "Servidor JN2 sem resposta";
            $this->shutAppDesc = "Não foi possível realizar a consulta ao servidor da JN2, em breve tentaremos novamente!";
        } 
        
        if($this->listPrice === false) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Lista de Preço Não Encontrada";
            $this->shutAppDesc = "A loja está configurada para usar uma lista de preço, mas não foi encontrada, token informado={$this->token}";
        }

        // verifica se em Processos->Administrar Integração está ativo
        if(!$this->validateIntegrationActive()) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Loja com integração inativa";
            $this->shutAppDesc = "<h5>Loja está com a configuração de integração inativa.</h5> <p>Isso pode ter acontecido por alteração de credenciais ou inativação por algum administrador.</p>";
        }
    }
}