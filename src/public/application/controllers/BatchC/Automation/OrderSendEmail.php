<?php

/**
 * @property Model_users $model_users
 * @property Model_orders_to_send $model_orders_to_send
 * @property Model_notification_config $model_notification_config
 * @property Model_orders $model_orders
 * @property Model_settings $model_settings
 */
class OrderSendEmail extends BatchBackground_Controller
{
    const PASTA_DE_IMAGEM = 'assets/images/product_image';
    public $data=[];
    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_users');
        $this->load->model('model_orders_to_send');
        $this->load->model('model_notification_config');
        $this->load->model('model_orders');
        $this->load->model('model_settings');
    }
    //php index.php BatchC/Automation/OrderSendEmail config 1 2 3 4 n
    public function config(...$users)
    {
        foreach ($users as $key => $user) {
            $user_in_db = $this->model_users->getUserData($user);
            if ($user_in_db) {
                echo ("Criando configuração para {$user_in_db['email']}\n");
                $notification_config = $this->model_notification_config->get_by_user($user);
                print_r($notification_config);
                if ($notification_config == null) {
                    echo ("Usuario não tem configuração para si, criando configuração.");
                    $data = [
                        'order_notification' => 'receive_instantly',
                        'id_user' => $user_in_db['id'],
                    ];
                    $create = $this->model_notification_config->create($data);
                    if ($create) {
                        echo ("Criado configuração com sucesso para: {$user_in_db['id']}\n");
                    } else {
                        echo ("Falha no processo de criação para: {$user_in_db['id']}\n");
                    }
                } else if ($notification_config['order_notification'] != 'receive_instantly') {
                    $this->model_notification_config->update_by_user($notification_config['id_user'], ['order_notification' => 'receive_instantly']);
                }
            } else {
                echo "Não encotrado usuario com esta id : {$user}\n";
            }

        }
    }
    // php index.php BatchC/Automation/OrderSendEmail run null receive_instantly null
    // php index.php BatchC/Automation/OrderSendEmail run null receive_daily null
    public function run($id = null, $params = null)
    {
        $this->config();
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            get_instance()->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        get_instance()->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
        $notification_configs = $this->model_notification_config->getByOrderNotification($params);
        if ($notification_configs) {
            foreach ($notification_configs as $notification_config) {
                if(!$notification_config["id_user"]){
                    continue;
                }
                $user = $this->model_users->getUserData($notification_config["id_user"]);
                $orders = $this->model_orders->getOrderToNotificationByUser($notification_config["id_user"]);
                if ($notification_config["order_notification"] == $params) {
                    if ($params == 'receive_instantly') {
                        for ($i = 0; $i < count($orders); $i++) {
                            $order = $orders[$i];
                            $itens = $this->model_orders->getOrdersItemData($order['id']);
                            $order['itens'] = $itens;
                            if (!isset($order['ship_company'])) {
                                $order['ship_company'] = '';
                            }
                            $order['status'] = $this->getPaidStatus($order, $user);
                            if (!$this->model_orders_to_send->isSent($order['id'], $user['id'])) {
                                $itens = $this->model_orders->getOrdersItemData($order['id']);
                                $order['itens'] = $itens;
                                $this->sendEmailOneOrder($user, $order);
                                $users = $this->model_orders_to_send->userOrdersSetSent($order['id'], $user['id']);
                                print_r('enviado para : ' . $user["firstname"] . " " . $user["lastname"] . ':' . $user["email"] . "\n");
                            }
                        }
                    } else {
                        $to_unset = [];
                        for ($i = 0; $i < count($orders); $i++) {
                            $order = $orders[$i];
                            if ($this->model_orders_to_send->isSent($order['id'], $user['id'])) {
                                array_push($to_unset, $i);
                            } else {
                                $itens = $this->model_orders->getOrdersItemData($order['id']);
                                $order['itens'] = $itens;
                                if (!isset($order['ship_company'])) {
                                    $order['ship_company'] = '';
                                }
                                $order['status'] = $this->getPaidStatus($order, $user);
                            }
                        }
                        for ($i = 0; $i < count($to_unset); $i++) {
                            unset($orders[$i]);
                        }
                        if (count($orders) > 0) {
                            $this->sendEmailmanyOrder($user, $orders);
                            for ($i = 0; $i < count($orders); $i++) {
                                echo ("Pedido: {$i}-{$orders[$i]['id']}\n");
                                $this->model_orders_to_send->userOrdersSetSent($orders[$i]['id'], $user['id']);
                            }
                            print_r('enviado para : ' . $user["firstname"] . " " . $user["lastname"] . ':' . $user["email"] . "\n");
                        }
                    }
                    $logData = [];
                    $logData['user'] = $user;
                    $logData['orders'] = $orders;
                    $this->log_data(__CLASS__, __FUNCTION__ . '-' . 'send_news_orders_by_email', json_encode($logData), 'I');

                }
            }
        }

        /* encerra o job */
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    public function sendEmailOneOrder($user, $order, $user_email = '')
    {
        $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
        if (!$from) {
            $from = 'marketing@conectala.com.br';
        }

        $user_email = $user['email'];
        $data = [];
        $title = "Relatorio de pedidos cadastrados.";

        $email = $user_email;
        echo ("Enviando email para : {$user_email} contendo o pedido {$order['id']}\n");
        $company = $this->model_company->getCompanyData(1);
        $data['logo'] = base_url() . $company['logo'];
        $order['status'] = $this->getPaidStatus($order, $user);
        // $data['logo'] = 'http://teste.conectala.com.br/app/assets/images/company_image/5e94be53acdd8.png';
        $data['order'] = $order;
        $data['userName'] = $user["firstname"] . " " . $user["lastname"];
        $body = $this->load->view('mailtemplate/notification_email_single', $data, true);
        $this->sendEmailMarketing($email, $title, $body, $from);
    }
    public function sendEmailmanyOrder($user, $orders)
    {
        $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
        if (!$from) {
            $from = 'marketing@conectala.com.br';
        }

        $data = [];
        $title = "Relatorio de pedidos cadastrados.";
        $email = $user["email"];
        $qtd = count($orders);
        echo ("Enviando email para : {$user["email"]} contendo {$qtd} pedidos.\n");
        $company = $this->model_company->getCompanyData(1);
        $data['logo'] = base_url() . $company['logo'];
        // $data['logo'] = 'http://teste.conectala.com.br/app/assets/images/company_image/5e94be53acdd8.png';
        $data['orders'] = $orders;
        $data['userName'] = $user["firstname"] . " " . $user["lastname"];
        $body = $this->load->view('mailtemplate/notification_email', $data, true);
        $this->sendEmailMarketing($email, $title, $body, $from);
    }

    public function getPaidStatus($value, $user)
    {
        $nd = $this->datedif($value['date_time']);
        $lb = "label-success";
        $lw = "label-warning";
        $np = $lp = "label-primary";
        $ld = "label-danger";
        if ($nd > 2) {
            $lb = "label-warning";
        }

        if ($nd > 5) {
            $lb = "label-danger";
        }

        $tooltip = '';
        $paid_status = '';
        if ($value['paid_status'] == 1) { // Não Pago
            $paid_status = '<span class="label label-primary"' . $tooltip . '>' . $this->lang->line('application_order_1') . '</span>';
        } elseif ($value['paid_status'] == 2) { // NOVO e Pago  - NÂO DEVE OCORRER
            $paid_status = '<span class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_2') . '</span>';
        } elseif ($value['paid_status'] == 3) { // Em Andamento - Aguardando faturamento (ACABOU DE CHEGAR DO BING)
            $paid_status = '<span class="label "' . $tooltip . '>' . $this->lang->line('application_order_3') . '</span>';
        } elseif ($value['paid_status'] == 4) { // Aguardando Coleta
            $paid_status = '<span class="label "' . $tooltip . '>' . $this->lang->line('application_order_4') . '</span>';
        } elseif ($value['paid_status'] == 5) { // Enviado
            $paid_status = '<span class="label "' . $tooltip . '>' . $this->lang->line('application_order_5') . '</span>';
        } elseif ($value['paid_status'] == 6) { // Entregue
            $paid_status = '<span class="label label-success"' . $tooltip . '>' . $this->lang->line('application_order_6') . '</span>';
        } elseif ($value['paid_status'] == 50) { // Nota Fiscal Registrada - Contratar o Frete.
            $paid_status = '<span class="label label-warning"' . $tooltip . '>' . $this->lang->line('application_order_50') . '</span>';
        } elseif ($value['paid_status'] == 40) {
            $paid_status = '<span class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_40') . '</span>';
        } elseif ($value['paid_status'] == 51) { // Frete Contratado - Mandar para o marketplace
            //$hasLabel = $this->model_freights->getFreightsHasLabel($value['id']);
            //if (empty($hasLabel)) {
            //    $paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_51').'</span>';
            //}
            //else {
            $paid_status = '<span  class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_4') . '</span>';
            //}
        } elseif ($value['paid_status'] == 52) { //  Mandar NF para o marketplace
            //$hasLabel = $this->model_freights->getFreightsHasLabel($value['id']);
            //if (empty($hasLabel)) {
            $paid_status = '<span class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_52') . '</span>';
            //}
            //else {
            //    $paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_4').'</span>';
            //
        } elseif ($value['paid_status'] == 53) { // Tudo ok. Agora é com o Rastreio do frete
            //$hasLabel = $this->model_freights->getFreightsHasLabel($value['id']);
            //if (empty($hasLabel)) {
            //    $paid_status = '<span class="label '.$lb.'"'.$tooltip.'>'.$this->lang->line('application_order_53').'</span>';
            //}
            //else {
            $paid_status = '<span class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_4') . '</span>';
            //}
        } elseif ($value['paid_status'] == 43) {
            $paid_status = '<span class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_43') . '</span>';
        } elseif ($value['paid_status'] == 54) { // Igual a 50 só que veio sem cotacao e fez cotacao manual
            $paid_status = '<span class="label ' . $ld . '"' . $tooltip . '>' . $this->lang->line('application_order_50') . '</span>';
        } elseif ($value['paid_status'] == 55) { // Pedido foi enviado mas precisa de intervenção manual no Marketplace para ser informado que foi enviado
            $paid_status = '<span class="label ' . $lw . '">' . $this->lang->line('application_order_55') . '</span>';
        } elseif ($value['paid_status'] == 45) {
            $paid_status = '<span class="label ' . $lb . '"' . $tooltip . '>' . $this->lang->line('application_order_45') . '</span>';
        } elseif ($value['paid_status'] == 56) { // Processando nfe aguardando envio para tiny
            $paid_status = '<span class="label ' . $lw . '">' . $this->lang->line('application_order_56') . '</span>';
        } elseif ($value['paid_status'] == 57) { // Problema para faturar o pedido
            $paid_status = '<span class="label ' . $ld . '">' . $this->lang->line('application_order_57') . '</span>';
        } elseif ($value['paid_status'] == 60) { // Pedido foi Entregue mas precisa de intervenção manual no Marketplace para ser informado que foi entregue
            $paid_status = '<span class="label ' . $ld . '"' . $tooltip . '>' . $this->lang->line('application_order_60') . '</span>';
        } elseif ($value['paid_status'] == 70) { // Pedido foi enviado para trocar de seller
            $paid_status = '<span class="label ' . $ld . '"' . $tooltip . '>' . $this->lang->line('application_order_70') . '</span>';
        } elseif ($value['paid_status'] == 95) { // cancelado pelo seller
            $paid_status = '<span class="label label-danger"' . $tooltip . '>' . $this->lang->line('application_order_95') . '</span>';
        } elseif ($value['paid_status'] == 96) { // cancelado pré-pagamento
            $paid_status = '<span class="label label-default"' . $tooltip . '>' . $this->lang->line('application_order_96') . '</span>';
        } elseif ($value['paid_status'] == 97) { // Cancelado em definitivo
            $paid_status = '<span class="label label-danger"' . $tooltip . '>' . $this->lang->line('application_order_97') . '</span>';
        } elseif ($value['paid_status'] == 98) { // Cancelar no Marketplace
            $paid_status = '<span class="label label-danger"' . $tooltip . '>' . ($value['paid_status'] == 98 ? $this->lang->line('application_order_98') : $this->lang->line('application_order_97')) . '</span>';
        } elseif ($value['paid_status'] == 99) { // Em Cancelamento - status para cancelar no Bling (BlingCancelar)
            $paid_status = '<span class="label label-danger"' . $tooltip . '>' . ($value['paid_status'] == 99 ? $this->lang->line('application_order_99') : $this->lang->line('application_order_97')) . '</span>';
        } elseif ($value['paid_status'] == 101) { // Sem cotação de frete - deve ter falhado a consulta frete e precisa ser feita manualmente
            $paid_status = '<span class="label label-danger"' . $tooltip . '>' . ($value['paid_status'] == 101 ? $this->lang->line('application_order_101') : $this->lang->line('application_order_101')) . '</span>';
        }
        if (($value['has_incident']) && (in_array('admDashboard', $this->permission))) {
            $paid_status .= '<br><span class="label ' . $ld . '">' . $this->lang->line('application_has_incident_mini') . '</span>';
        }
        return $paid_status;
    }
}
