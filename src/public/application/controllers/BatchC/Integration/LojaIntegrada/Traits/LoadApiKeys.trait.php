<?php
if (!defined('LoadApiKey')) {
    define('LoadApiKey', '');
    trait LoadApiKey
    {
        public function loadApiKey($store)
        {
            if (!is_subclass_of($this, CI_Controller::class)) {
                throw new Exception("Este controller só pode ser usado em subclass de Integration");
            }
            if (!isset($this->model_api_integrations)) {
                $this->load->model('model_api_integrations');
            }
            if (!isset($this->model_settings)) {
                $this->load->model('model_settings');
            }
            $this->integration = $this->model_api_integrations->getDataByStore($store);
            $this->integration = $this->integration[0];
            $this->credentials = json_decode($this->integration["credentials"], true);
            if (!isset($this->credentials['chave_api'])) {
                throw new Exception("Falha no processamento, chave api não definida.");
            }
            $this->chave_api = $this->credentials['chave_api'];
            $this->chave_aplicacao = $this->model_settings->getValueIfAtiveByName('chave_aplicacao_loja_integrada');
            if (!$this->chave_aplicacao) {
                throw new Exception("Falha no processamento, chave da aplicação não definida.");
            }
            $this->params = "format=json&chave_api={$this->chave_api}&chave_aplicacao={$this->chave_aplicacao}";
            $conectala_api = $this->model_settings->getValueIfAtiveByName('conecta_la_api_url');
            if (!$conectala_api) {
                throw new Exception("Url da API Conecta-lá(conecta_la_api_url) não definida no sistema.");
            }
            $this->url_conectala = $conectala_api;
            $loja_integrada_api = $this->model_settings->getValueIfAtiveByName('loja_integrada_api_url');
            if (!$loja_integrada_api) {
                throw new Exception("Url da API loja integrada(loja_integrada_api_url) não definida no sistema.");
            }
            $this->url = $loja_integrada_api;
        }
    }
}
