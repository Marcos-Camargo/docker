<?php

require APPPATH . "controllers/BatchC/Integration/Integration.php";

class Main extends Integration
{
    public function __construct()
    {
        parent::__construct();
        $this->setTypeIntegration('pluggto');
    }

    /**
     * Define os dados para integração
     *
     * @param $store_id
     */
    public function setDataIntegration($store_id)
    {
        $credentials_pluggto = $this->model_settings->getSettingDatabyName('credencial_pluggto');
        $credentials = json_decode($credentials_pluggto['value']);

        // recupera a loja e identifica a company_id
        $dataStore   = $this->model_stores->getStoresData($store_id);
        $this->setStore($store_id);
        $this->setCompany($dataStore['company_id']);
        
        if (!isset($credentials->client_id_pluggto)) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Pluggto Inativa";
            $this->shutAppDesc = "Pluggto ainda não está ativa nesse ambiente.";
            return false;
        }       

        if ($this->validateToken() === false) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Credenciais inválidas ou sem permissão para acesso";
            $this->shutAppDesc = "Credenciais inválidas ou sem permissão para acesso, reveja suas credenciais cadastradas em Integração -> Solicitar Integração e/ou permissões na plataforma";
        } elseif ($this->validateToken() === null) {
            $this->shutAppStatus = false;
            $this->shutAppTitle = "Servidor PluggTo sem resposta";
            $this->shutAppDesc = "Não foi possível realizar a consulta ao servidor da PluggTo, em breve tentaremos novamente!";
        } 
        
        if($this->listPrice === false) {
            $this->shutAppStatus = true;
            $this->shutAppTitle = "Lista de Preço Não Encontrada";
            $this->shutAppDesc = "A loja está configurada para usar uma lista de preço, mas não foi encontrada, token informado={$this->token}";
        }

        // verifica se em Processos->Administrar Integração está ativo
        //if(!$this->validateIntegrationActive()) {
        //    $this->shutAppStatus = true;
         //   $this->shutAppTitle = "Loja com integração inativa";
         //   $this->shutAppDesc = "<h5>Loja está com a configuração de integração inativa.</h5> <p>Isso pode ter acontecido por alteração de credenciais ou inativação por algum administrador.</p>";
        //}
    }

    public function getIDuserSellerByStore($store_id)
    { 
        $this->load->model('model_api_integrations');
        $credentials = $this->model_api_integrations->getDataByStore($store_id);
        foreach($credentials as $credential)
        {
            if (!$credential || $credential['status'] != 1)
            return false;

            $data = json_decode($credential['credentials']);
            
            if(isset($data->user_id))
            return $data->user_id;
        }
        
    }

    public function getToken(){
        
        $pluggto_settings = $this->model_settings->getSettingDatabyName('credencial_pluggto');
        if(isset($pluggto_settings))
        {
            $credentials = json_decode($pluggto_settings['value']);
        }
        

        if(!isset($credentials->client_id_pluggto))
            return false;
        
        // Busca por token - válido por 1 hora.            
        $urlAuth = "https://api.plugg.to/oauth/token";
        $dataAuth = "grant_type=password&client_id=$credentials->client_id_pluggto&client_secret=$credentials->client_secret_pluggto&username=$credentials->username_pluggto&password=$credentials->password_pluggto";        
        

        $authResult = json_decode(json_encode($this->sendREST($urlAuth, $dataAuth, 'POST', true, 'Content-Type: application/x-www-form-urlencoded')));
        if($authResult->httpcode != 200){            
            return false;
        }

        $authResult = json_decode($authResult->content);

        return $authResult->access_token;
    }

    public function getPaymentPluggTo($forma_payment){
        //payment_type válido na pluggTo":
        // ‘credit’ : credito 
        // ‘debit’ : debito 
        // ‘ticket’ : boleto 
        // ‘voucher’ : conta 
        // ‘transfer’ : transferencia

        $payment_valid = '';
        switch ($forma_payment) {
            case 'Boleto Bancário':
                $payment_valid = "ticket";
                break;
            case 'credit_card':
                $payment_valid = "credit";
                break;
            case 'Conta a receber/pagar':
                $payment_valid = "voucher";
                break;
            case 'Dinheiro':
                $payment_valid = "transfer";
                break;
            default:
                $payment_valid = "credit";    
        }


        return $payment_valid;
    }

    /**
     * Recupera o status válido da pluggto
     *
     * @return array Retorno os pedidos na fila para integrar
     */
    public function getPluggToStatus($statusConecta){

        //  "Status válido na pluggTo":
        // 'pending' - Order is not paid
        // 'partial_payment' - Order is paid, but freight not
        // 'approved' - Order is paid and approved
        // 'waiting_invoice' - Store request to generate Invoice
        // 'invoice_error' - ERP was not able generate the Invoice
        // 'invoiced' - Invoice is done
        // 'shipping_informed' - Store informed the track code and shipped the product
        // 'shipped' - MarketPlace accept the shipping information and informed de customer
        // 'shipping_error' - MarketPlace not accept the shipping information, need to fix the invoice information
        // 'delivered' - Store market as delivered to the customer the product
        // 'canceled' - The order was canceled
        // 'under_review' - The order should not be send, untile the MarketPlace approved again the order


        $statusPluggto = '';
        switch ($statusConecta) {            
            case 'Aguardando Pagamento(Não foi pago ainda)':
                $statusPluggto = "pending";
                break;
            case 'Aguardando Faturamento':
                $statusPluggto = "waiting_invoice";
                break;
            case 'Cancelado':
            case 'Cancelar no Marketplace':
                $statusPluggto = "canceled";
                break;
            case 'Entregue':
                $statusPluggto = "delivered";
                break;
            case 'Cancelar na Transportadora':
                $statusPluggto = "shipping_error";
                break;
            case 'Em Transporte':
                $statusPluggto = "shipped";
                break;
            case 'Aguardando Coleta/Envio':
                $statusPluggto = "picking";
                break;
            case 'PLP gerada'; //(Enviar rastreio para o marketplace)'
                $statusPluggto = 'shipping_informed';
                break;
            case 'Pedido faturado':
                $statusPluggto = "invoiced";
                break;
            default:
                $statusPluggto = "pending";
                break;
        }

        return $statusPluggto;
    }

    public function getStatusIntegration($paidStatus){
        // Status do pedido
        switch ($paidStatus) {
            case 1:
                $historico = "Aguardando Pagamento(Não foi pago ainda)";
                $situacaoPluggTo = null;  //-1;
                break;
            case 3:
                $historico = "Aguardando Faturamento"; //(Pedido foi pago, está aguardando ser faturado)
                $situacaoPluggTo = 3;
                break;
            case 4:
                $historico = "Aguardando Coleta/Envio"; //(Aguardando o seller enviar ou transportadora coletar)
                $situacaoPluggTo = null; //3(antes) Pronto para picking
                break;
            case 5:
                $historico = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoPluggTo = 0; //1;
                break;
            case 6:
                $historico = "Entregue"; //(Pedido entregue ao cliente)
                $situacaoPluggTo = 0; // 1; //'D', descontinuado o Entregue, agora vai ser Atendido com data de entrega.
                break;
            case 40:
                $historico = "Aguardando Rastreio"; //(Pedido faturado, aguardando envio de rastreio)
                $situacaoPluggTo = 40; //manda dados do transporte, não existe esses status fica igual conectala.
                break;
            case 43:
                $historico = "Aguardando Coleta/Envio"; //(Pedido com rastreio, aguardando ser coletado/enviado)
                $situacaoPluggTo = 43;
                break;
            case 45:
                $historico = "Em Transporte"; //(Pedido já foi enviado ao cliente)
                $situacaoPluggTo = 45;  //41
                break;
            case 50:
                $historico = "Aguardando Seller Emitir Etiqueta"; //(Pedido faturado, contratando frete)
                $situacaoPluggTo = 0; //50;
                break;
            case 51:
                $historico = "PLP gerada"; //(Enviar rastreio para o marketplace)
                $situacaoPluggTo = 0; //51;
                break;
            case 52:
                $historico = "Pedido faturado"; //(Enviar NF-e para o marketplace)
                //$situacaoPluggTo = null; //1;
                $situacaoPluggTo = 0;  //1; //faturou, gerou nota fiscal, vai deixar no ERP, como Atentido.
                break;
            case 53:
                $historico = "Aguardando Coleta/Envio"; //(Aguardando pedido ser postado/coletado para rastrear)
                $situacaoPluggTo = 53;
                break;
            case 55:
                $historico = "Em Transporte"; //(Avisar o marketplace que o pedido foi enviado)
                $situacaoPluggTo = 55;
                break;
            case 56:
                $historico = "Processando Nota Fiscal"; //(Processando NF aguardando envio (módulo faturador))
                $situacaoPluggTo = null; //4;
                break;
            case 57:
                $historico = "Nota Fiscal Com Erro"; //(Problema para faturar o pedido (módulo faturador))
                $situacaoPluggTo = null; //4;
                break;
            case 60:
                $historico = "Entregue"; //(Avisar ao marketplace que foi entregue)
                $situacaoPluggTo = 'D';
                break;
            case 96:
                $historico = ' status = 96'; //$historico = "Cancelado"; //(Cancelado antes de realizar o pagamento)
                $situacaoPluggTo = null; //96;
                break;
            case 95:
            case 97:
                $historico = "Cancelado"; //(Cancelado após o pagamento)
                $situacaoPluggTo = 2;
                break;
            case 98:
                $historico = "Cancelar na Transportadora"; //(Cancelar rastreio na transportadora (não correios))
                $situacaoPluggTo = null; //2;
                break;
            case 99:
                $historico = "Cancelar no Marketplace"; //(Avisar o cancelamento para o marketplace)
                $situacaoPluggTo = null; //2;
                break;
            case 101:
                $historico = "Sem Cotação de Frete"; //(Deve fazer a contratação do frete manual (não correios))
                $situacaoPluggTo = null; //0;
                break;
            default:
                $historico = 'Não foi encontrato o status';
                $situacaoPluggTo = null;
                break;
        }

        $arrRetorno = array('status'    => $situacaoPluggTo,
                            'historico' => $historico);        

        return $arrRetorno;
    }

    
}