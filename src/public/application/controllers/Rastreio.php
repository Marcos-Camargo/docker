<?php

class Rastreio extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_tracking');
        $this->load->model('model_newsletter');
    }

    public function index()
    {
        $seller_center = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $seller_center = $settingSellerCenter['value'];
        }

        $this->data['stylesheet'] = base_url('assets/tracking/css/style.css');
        if ($seller_center == 'conectala') {
            $this->data['stylesheet'] = base_url('assets/tracking/css/style-conecta.css');
            $this->data['conecta_style'] = false;
        } else {
            // Configurações padrão da tela de rastreio.
            $this->data['top_selected'] = 'basic';
            $this->data['top_basic_back_color'] = '#d3d3d3';
            $this->data['top_basic_text_color'] = '#777777';
            $this->data['top_image_name'] = '';
            $this->data['middle_back_color'] = '#808080';
            $this->data['middle_text_color'] = '#ffffff';
            $this->data['middle_button_back_color'] = '#3a2c51';
            $this->data['middle_button_text_color'] = '#ffffff';
            $this->data['bottom_selected'] = 'basic';
            $this->data['bottom_basic_back_color'] = '#d3d3d3';
            $this->data['bottom_basic_text_color'] = '#808080';
            $this->data['bottom_html_content'] = htmlentities('<div>&copy; 2022 ' . $seller_center . ' &mdash; Todos os direitos reservados.</div>');

            $sql = "SELECT custom_settings 
                    FROM custom_tracking_interface";
            $query = $this->db->query($sql);

            $result = $query->row_array();
            if (($result !== null) && ($result !== false) && (count($result) > 0)) {
                // Configurações personalizadas da tela de rastreio.
                $assoc_result = json_decode($result['custom_settings']);
                $this->data['top_selected'] = $assoc_result->top_selected;
                $this->data['top_basic_back_color'] = $assoc_result->top_basic_back_color;
                $this->data['top_basic_text_color'] = $assoc_result->top_basic_text_color;
                $this->data['top_image_name'] = $assoc_result->top_image_name;
                $this->data['middle_back_color'] = $assoc_result->middle_back_color;
                $this->data['middle_text_color'] = $assoc_result->middle_text_color;
                $this->data['middle_button_back_color'] = $assoc_result->middle_button_back_color;
                $this->data['middle_button_text_color'] = $assoc_result->middle_button_text_color;
                $this->data['bottom_selected'] = $assoc_result->bottom_selected;
                $this->data['bottom_basic_back_color'] = $assoc_result->bottom_basic_back_color;
                $this->data['bottom_basic_text_color'] = $assoc_result->bottom_basic_text_color;
                $this->data['bottom_html_content'] = $assoc_result->bottom_html_content;
            }
            $this->data['conecta_style'] = true;
        }

        $this->data['need_change_password'] = false;
        $this->data['page_title'] = $this->lang->line('application_tracking');
        $this->data['title'] = $this->lang->line('application_tracking');
        $this->load->view('rastreio/index', $this->data);
    }

    // Formata data e hora para que ambas as informações sejam mostradas 
    // na tela de rastreamento de pedidos.
    public function format_datetime(string $datetime)
    {
        $date_time = explode(" ", $datetime);

        // Se informação sobre data foi encontrada.
        $date_found = false;
        // Se informação sobre hora foi encontrada.
        $time_found = false;
        // Contador de informações de data e hora: entre 0 e 2.
        $date_time_counter = 0;

        // Nenhuma informação de data e hora foi armazenada.
        if (count($date_time) == 0) {
            return "---";

        // Alguma informação de data e hora foi armazenada.
        // É necessário descobrir o que é.
        } else if (count($date_time) == 1) {
            $date_time_counter = 1;

            $string_position = strpos($date_time[0], "-");
            if ($string_position !== false) {
                $date_found = true;
            } else {
                $string_position = strpos($date_time[0], ":");
                if ($string_position !== false) {
                    $time_found = true;
                }
            }

        // Informação encontrada tanto sobre hora quanto sobre data.
        } else if (count($date_time) == 2) {
            $date_time_counter = 2;
            $date_found = true;
            $time_found = true;
        }

        if ($date_found) {
            $date_only = explode("-", $date_time[0]);
            $date_formatted = $date_only[2] . " de ";
            if ($date_only[1] == "01") {
                $date_formatted .= "janeiro";
            } else if ($date_only[1] == "02") {
                $date_formatted .= "fevereiro";
            } else if ($date_only[1] == "03") {
                $date_formatted .= "março";
            } else if ($date_only[1] == "04") {
                $date_formatted .= "abril";
            } else if ($date_only[1] == "05") {
                $date_formatted .= "maio";
            } else if ($date_only[1] == "06") {
                $date_formatted .= "junho";
            } else if ($date_only[1] == "07") {
                $date_formatted .= "julho";
            } else if ($date_only[1] == "08") {
                $date_formatted .= "agosto";
            } else if ($date_only[1] == "09") {
                $date_formatted .= "setembro";
            } else if ($date_only[1] == "10") {
                $date_formatted .= "outubro";
            } else if ($date_only[1] == "11") {
                $date_formatted .= "novembro";
            } else if ($date_only[1] == "12") {
                $date_formatted .= "dezembro";
            }
            $date_formatted .= ", " . $date_only[0];
        } else {
            $date_formatted = "";
        }

        if ($time_found) {
            if ($date_time_counter == 1) {
                $date_time = $date_time[0];
            } else if ($date_time_counter == 2) {
                $date_time = $date_time[1];
            }

            $time_only = explode(":", $date_time);
            $time_formatted = "";

            if ($time_only[0] < "10") {
                $time_formatted .= str_pad($time_only[0], 2, '0', STR_PAD_LEFT) . ":" . $time_only[1];
            } else {
                $time_formatted .= $time_only[0] . ":" . $time_only[1];
            }
        } else {
            $time_formatted = "";
        }

        return $date_formatted . "---" . $time_formatted;
    }

    // Status do pedido, mostrando em que etapa do percurso ele se encontra.
    public function status()
    {

        $tracking_data = array();
        $params = cleanArray($this->input->get());
        $tracking_result = array();

        if (
            !array_key_exists('email', $params) ||
            !array_key_exists('tracking_code', $params) ||
            !array_key_exists('lgpd_agree', $params) ||
            !array_key_exists('corder', $params) ||
            empty($params['tracking_code'])
        ) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(array(
                'status' => 'fail'
            )));
        }

        $email = $params['email'];
        $valid_email = false;
        $code = $params['tracking_code'];
        $valid_code = false;
        $lgpd_agreement = $params['lgpd_agree'];
        $customer_id = $params['corder'];

        // Se o usuário NÃO concorda com os termos do contrato para divulgar o e-mail 
        // e nem em receber e-mails promocionais.
        $lgpd = 0;
        if ($email !== false) {
            if (!empty($lgpd_agreement)) {
                $lgpd_array = explode(",", $lgpd_agreement);

                if (count($lgpd_array) == 1) {
                    $counter = 0;
                    foreach ($lgpd_array as $lgpd_value) {
                        if ($lgpd_value == 'agreement') {
                            // Se o usuário concorda SOMENTE com os termos do contrato para divulgar o e-mail.
                            $counter = 1;
                        } else if ($lgpd_value == 'advertisement') {
                            // Se o usuário concorda SOMENTE em receber e-mails promocionais.
                            $counter = 2;
                        }
                    }
                    $lgpd = $counter;
                } else if (count($lgpd_array) == 2) {
                    // Se o usuário concorda com os termos do contrato para divulgar o e-mail 
                    // e em receber e-mails promocionais.
                    $lgpd = 3;
                }
            }

            // Validação do e-mail.
            $tracking_data['order_email'] = $this->model_tracking->emailValidation($email);
            if (($tracking_data['order_email'] == 'client') || ($tracking_data['order_email'] == 'seller')) {

                // E-mail válido.
                $valid_email = true;
            }
        }

        $cpf_cnpj = preg_replace('/[^0-9]/is', '', $code);
        if ((strlen($cpf_cnpj) == 11) || (strlen($cpf_cnpj) == 14)) {
            $code = $cpf_cnpj;
        }

        $tracking_result['status'] = 'fail';
        // Validação do código de rastreamento.
        $tracking_data['order_code'] = $this->model_tracking->trackingCodeValidation($code, $customer_id);

        $total_rows = $tracking_data['order_code'];
        if (count($total_rows) > 1) {
            $tracking_result['steps'] = '
            <div class="row">
                <div class="col-lg-6">
                    <div class="tracking-item">
                        <div class="tracking-icon status-complete">
                            <i class="fa fa-file"></i>
                        </div>

                        <div class="tracking-content">Qual pedido você quer rastrear?';

                        $cont = 0;
                        foreach ($tracking_data['order_code'] as $td) {
                            $dt_tracking = '';
                            if (!empty($td['data_pago'])) {
                                $dt_tracking = $td['data_pago'];
                            } else if (!empty($td['date_time'])) {
                                $dt_tracking = $td['date_time'];
                            }

                            $aux_cpf_cnpj = preg_replace('/[^0-9]/is', '', $td['cpf_cnpj']);
                            if (strlen($aux_cpf_cnpj) < 11) {
                                $aux_cpf_cnpj = str_pad($aux_cpf_cnpj, 11, '0', STR_PAD_LEFT);
                            } else if ((strlen($aux_cpf_cnpj) > 11) && (strlen($aux_cpf_cnpj) < 14)) {
                                $aux_cpf_cnpj = str_pad($aux_cpf_cnpj, 14, '0', STR_PAD_LEFT);
                            }

                            if ($cont == 0) {
                                $tracking_result['steps'] .= '<span style="padding-top: 10px;">' . $this->format_datetime($dt_tracking) . ' - Pedido <a href="#tracker" onclick="consultaPedidos(\'' . $aux_cpf_cnpj . '\', \'' . $td['id'] . '\');">' . $td['id'] . '</a></span>';
                            } else {
                                $tracking_result['steps'] .= '<span style="padding-top: 3px;">' . $this->format_datetime($dt_tracking) . ' - Pedido <a href="#tracker" onclick="consultaPedidos(\'' . $aux_cpf_cnpj . '\', \'' . $td['id'] . '\');">' . $td['id'] . '</a></span>';
                            }
                            $cont += 1;
                        }

            $tracking_result['steps'] .= '
                        </div>
                    </div>
                </div>
            </div>';
        } else {
            if (
                !empty($tracking_data['order_code']) && 
                ($tracking_data['order_code'] != 'not informed')
            ) {
                // Código de rastreamento válido.
                $valid_code = true;
                $tracked_order = $tracking_data['order_code'];
                $tracked_order = $tracked_order[0];

                if (($email !== false) && $valid_email) {
                    $final_consumer = 1;
                    // Se o usuário atual não é o consumidor final.
                    if ($tracking_data['order_email'] == 'seller') {
                        $final_consumer = 0;
                    }

                    // Adiciona as informações de consulta de rastreio no DB.
                    $newsletter_result = $this->model_newsletter->insert(
                        $code, 
                        $email, 
                        $final_consumer, 
                        $lgpd
                    );

                    // Se o usuário concorda em receber e-mails promocionais.
                    if ($lgpd >= 2) {
                        // Adiciona o contato ao Mailchimp.
                        $mailchimp_result = $this->model_newsletter->putListMemberMailchimp(
                            "us5", 
                            "d91e80d0fd19e0603c12a0b00cbc187a", 
                            "231ba06521", 
                            $email
                        );

                        /*
                        Se o valor de "$mailchimp_result['status']" for igual a: 
                        - "subscribed", a operação foi bem sucedida;
                        - "4XX", houve um erro quando a API tentou fazer a inserção no servidor remoto;
                        - "fail", houve um erro antes mesmo de a API tentar fazer a inserção no servidor remoto.
                        */
                    }
                }

                // Id do pedido.
                $id = $tracked_order['id'];

                // Evento atual no rastreamento do pedido.
                $tracking_current = 0;

                // Status da entrega do pedido.
                $status = 0;
                if (!empty($tracked_order['paid_status'])) {
                    $status = $tracked_order['paid_status'];
                }

                $data_cancelamento = '';
                $data_recebimento = '';
                $data_retirada = '';
                $data_entrega = '';
                $data_envio = '';
                $data_faturamento = '';
                $data_confirmado = '';
                $data_processamento = '';
                $status_complete = '';

                $cancelled_order = array(90, 95, 96, 97, 98);
                // Pedido cancelado.
                if (!empty($tracked_order['date_cancel'])) {
                    $formatted_datetime = explode("---", $this->format_datetime($tracked_order['date_cancel']));
                    $status_complete = 'active';

                    $message = '';
                    if (($status == 90) || ($status == 98)) {
                        $message = 'Cancelamento solicitado.';
                    } else if ($status == 95) {
                        $message = 'Pedido cancelado pelo vendedor.';
                    } else if ($status == 96) {
                        $message = 'Pedido cancelado antes da realização do pagamento.';
                    } else if ($status == 97) {
                        $message = 'Pedido cancelado após a realização do pagamento.';
                    }

                    $data_cancelamento = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-thumbs-up"></i></div>
                        <div class="tracking-date">' . $formatted_datetime[0] . '<span>' . $formatted_datetime[1] . '</span></div>
                        <div class="tracking-content">Cancelado<span>' . $message . '</span></div>
                    </div>';
                } else if (in_array($status, $cancelled_order)) {
                    $status_complete = 'active';

                    $message = '';
                    if (($status == 90) || ($status == 98)) {
                        $message = 'Cancelamento solicitado.';
                    } else if ($status == 95) {
                        $message = 'Pedido cancelado pelo vendedor.';
                    } else if ($status == 96) {
                        $message = 'Pedido cancelado antes da realização do pagamento.';
                    } else if ($status == 97) {
                        $message = 'Pedido cancelado após a realização do pagamento.';
                    }

                    $data_cancelamento = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-thumbs-up"></i></div>
                        <div class="tracking-date"></div>
                        <div class="tracking-content">Cancelado<span>' . $message . '</span></div>
                    </div>';
                }

                $received_order = array(6, 60);
                // Pedido recebido.
                if (!empty($tracked_order['data_entrega'])) {
                    $formatted_datetime = explode("---", $this->format_datetime($tracked_order['data_entrega']));

                    if ($status_complete == '') {
                        $status_complete = 'active';
                    } else if ($status_complete == 'active') {
                        $status_complete = 'complete';
                    }

                    $data_recebimento = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-check"></i></div>
                        <div class="tracking-date">' . $formatted_datetime[0] . '<span>' . $formatted_datetime[1] . '</span></div>
                        <div class="tracking-content">Recebido<span>O seu pedido foi recebido.</span></div>
                    </div>';
                } else if (in_array($status, $received_order)) {
                    $status_complete = 'active';

                    $data_recebimento = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-check"></i></div>
                        <div class="tracking-date"></div>
                        <div class="tracking-content">Recebido<span>O seu pedido foi recebido.</span></div>
                    </div>';
                }

                $awaiting_order = array(58);
                // Pedido aguardando retirada.
                if (in_array($status, $awaiting_order)) {
                    $status_complete = 'active';

                    $data_retirada = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-truck"></i></div>
                        <div class="tracking-date"></div>
                        <div class="tracking-content">Aguardando<span>Pedido aguardando retirada na agência.</span></div>
                    </div>';
                }

                $transit_order = array(5, 45, 55, 59);
                // Pedido em trânsito.
                if (!empty($tracked_order['data_envio'])) {
                    $formatted_datetime = explode("---", $this->format_datetime($tracked_order['data_envio']));

                    if ($status_complete == '') {
                        $status_complete = 'active';
                    } else if ($status_complete == 'active') {
                        $status_complete = 'complete';
                    }

                    $message = '';
                    if (($status == 5) || ($status == 45) || ($status == 55)) { 
                        $message = 'O pedido está em trânsito.';
                    } else if ($status == 59) {
                        $message = 'Pedido com extravio/devolução ao remetente.';
                    }

                    $data_entrega = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-truck"></i></div>
                        <div class="tracking-date">' . $formatted_datetime[0] . '<span>' . $formatted_datetime[1] . '</span></div>
                        <div class="tracking-content">Postado<span>' . $message . '</span></div>
                    </div>';
                } else if (in_array($status, $transit_order)) {
                    $status_complete = 'active';

                    $message = '';
                    if (($status == 5) || ($status == 45) || ($status == 55)) { 
                        $message = 'O pedido está em trânsito.';
                    } else if ($status == 59) {
                        $message = 'Pedido com extravio/devolução ao remetente.';
                    }

                    $data_entrega = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-truck"></i></div>
                        <div class="tracking-date"></span></div>
                        <div class="tracking-content">Postado<span>' . $message . '</span></div>
                    </div>';
                }

                $collect_order = array(4, 43);
                // Pedido aguardando coleta/envio.
                if (in_array($status, $collect_order)) {
                    $status_complete = 'active';

                    $data_envio = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-truck"></i></div>
                        <div class="tracking-date"></div>
                        <div class="tracking-content">Aguardando<span>Pedido aguardando para ser coletado/enviado.</span></div>
                    </div>';
                }

                $billing_order = array(3);
                // Pedido aguardando faturamento.
                if (in_array($status, $billing_order)) {
                    $status_complete = 'active';

                    $data_faturamento = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-thumbs-up"></i></div>
                        <div class="tracking-date"></div>
                        <div class="tracking-content">Aguardando<span>Pedido aguardando faturamento.</span></div>
                    </div>';
                }

                // Pedido confirmado.
                if (!empty($tracked_order['data_pago'])) {
                    $tracking_result['status'] = 'success';
                    $formatted_datetime = explode("---", $this->format_datetime($tracked_order['data_pago']));

                    if ($status_complete == '') {
                        $status_complete = 'active';
                    } else if ($status_complete == 'active') {
                        $status_complete = 'complete';
                    }

                    $data_confirmado = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-thumbs-up"></i></div>
                        <div class="tracking-date">' . $formatted_datetime[0] . '<span>' . $formatted_datetime[1] . '</span></div>
                        <div class="tracking-content">Confirmado<span>O seu pedido foi confirmado.</span></div>
                    </div>';
                }

                $processing_order = array(2);
                // Pedido sendo processado.
                if (in_array($status, $processing_order)) {
                    $status_complete = 'active';

                    $data_processamento = '
                    <div class="tracking-item">
                        <div class="tracking-icon status-' . $status_complete . '"><i class="fas fa-thumbs-up"></i></div>
                        <div class="tracking-date"></div>
                        <div class="tracking-content">Confirmando<span>Pedido sendo processado.</span></div>
                    </div>';
                }

                $steps = '';
                if ($data_processamento != '') {
                    $steps .= $data_processamento;
                }

                if ($data_confirmado != '') {
                    $steps .= $data_confirmado;
                }

                if ($data_faturamento != '') {
                    $steps .= $data_faturamento;
                }

                if ($data_envio != '') {
                    $steps .= $data_envio;
                }

                if ($data_entrega != '') {
                    $steps .= $data_entrega;
                }

                if ($data_retirada != '') {
                    $steps .= $data_retirada;
                }

                if ($data_recebimento != '') {
                    $steps .= $data_recebimento;
                }

                if ($data_cancelamento != '') {
                    $steps .= $data_cancelamento;
                }

                $tracking_result['steps'] = '
                <div class="row">
                    <div class="col-lg-12">' . 
                        $steps . 
                    '</div>
                </div>';
            }
        }

        echo json_encode($tracking_result);
    }

    // Envia um e-mail com as informações do formulário de contato para o email do atendimento.
    public function sendEmail()
    {
        $to = 'atendimento@conectala.com.br';
        $from = 'atendimento@conectala.com.br';
        $subject = 'Contato sobre o rastreio de pedido';

        $data['name'] = $this->input->get('name', true);
        $data['email'] = $this->input->get('email', true);
        if (empty($this->input->get('phone', true))) {
            $data['phone'] = '[Não informado.]';
        } else {
            $data['phone'] = $this->input->get('phone', true);;
        }
        $data['message'] = $this->input->get('message', true);
        $data['datetime'] = date('Y-m-d H:i:s', strtotime('now'));
        $order_number = $this->input->get('order_number', true);
        $order_number_status = $this->orderNumberLookup($order_number);
        if ($order_number_status == 'order_found') {
            $data['order_number'] = $order_number;
        } else {
            $data['order_number'] = '[Não informado]';
        }

        $sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
        if (!$sellercenter) {
            $sellercenter = 'conectala';
        }
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$sellercenter_name) {
            $sellercenter_name = 'Conecta Lá';
        }
        $data['sellercenter'] = $sellercenter;
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/tracking_problem.php')) {
            $body= $this->load->view('mailtemplate/'.$sellercenter.'/tracking_problem',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/tracking_problem',$data,TRUE);
        }
        $resp = $this->sendEmailMarketing($to, $subject, $body, $from, null);

        $tracking_data = array();
        if ($resp['ok'] === true) {
            $tracking_data['status'] = 'success';
        } else {
            $tracking_data['status'] = 'fail';
        }

        echo json_encode($tracking_data);
    }

    public function customization()
    {
        if (!in_array('viewTrackingPage', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $seller_center = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        if ($settingSellerCenter) {
            $seller_center = $settingSellerCenter['value'];
        }

        $this->data['seller_center'] = $seller_center;
        $this->data['page_title'] = $this->lang->line('application_tracking_custom');
        $this->data['page_now'] = $this->lang->line('application_tracking_custom');

        $this->render_template('rastreio/customization', $this->data);
    }

    public function imageUpload()
    {
        $config['upload_path'] = 'assets/files/sellercenter/';
        $file_name = "top-" . date("Ymdhis", strtotime("now")) . "-" . str_replace(" ", "_", basename($_FILES["file_upload"]["name"]));
        $return = json_encode($file_name);
        $config['file_name'] = $file_name;
        $config['allowed_types'] = 'jpg|jpeg';
        $config['max_size'] = '1500';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('file_upload')) {
            $return = json_encode("fail");
        }
        print_r($return);
    }

    public function loadSettings()
    {
        $seller_center = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        if ($settingSellerCenter) {
            $seller_center = $settingSellerCenter['value'];
        }

        $sql = "SELECT custom_settings 
                FROM custom_tracking_interface";

        $query = $this->db->query($sql);

        $return = json_encode('default');
        $result = $query->row_array();
        if (count($result) == 0) {
            $default_settings = '{"top_selected":"basic","top_basic_back_color":"#d3d3d3","top_basic_text_color":"#777777","top_image_name":"","middle_back_color":"#808080","middle_text_color":"#ffffff","middle_button_back_color":"#3a2c51","middle_button_text_color":"#ffffff","bottom_selected":"basic","bottom_basic_back_color":"#d3d3d3","bottom_basic_text_color":"#808080","bottom_html_content":"' . htmlentities('<div>&copy; 2022 ' . $seller_center . ' &mdash; Todos os direitos reservados.</div>') . '"}';
            $return = json_encode($default_settings);

            $datetime = date('Y-m-d H:i:s');

            $sql = "INSERT INTO custom_tracking_interface (
                        default_settings, 
                        custom_settings, 
                        date_updated
                    ) VALUES (
                        '$default_settings', 
                        '$default_settings', 
                        '$datetime'
                    )";

            $query = $this->db->query($sql);
        } else {
            $return = $result['custom_settings'];
            $send_tracking_code_to_mkt = 0;

            $sql = "SELECT * FROM settings";
            $query = $this->db->query($sql);
            $result = $query->result_array();
            if (!empty($result)) {
                foreach($result as $r) {
                    if ($r['name'] == 'send_tracking_code_to_mkt') {
                        $send_tracking_code_to_mkt = $r['value'];
                    }
                }
            }

            $custom_settings = str_replace(
                '"bottom_html_content"', 
                '"send_tracking_code_to_mkt":"' . $send_tracking_code_to_mkt . '","bottom_html_content"', 
                $return
            );

            $return = json_encode($custom_settings);
        }

        print_r($return);
    }

    public function updateSettings()
    {
        $postdata = $this->postClean(NULL,TRUE);

        $custom_settings = '{';
        foreach ($postdata as $key => $value) {
            if ($key == 'bottom_html_content') {
                $custom_settings .= '"' . $key . '":"' . $value . '"';
            } else {
                $custom_settings .= '"' . $key . '":"' . $value . '",';
            }
        }
        $custom_settings .= '}';

        $sql = "UPDATE custom_tracking_interface 
                SET custom_settings = '$custom_settings'";
        $update = $this->db->query($sql);

        print_r(json_encode($update));
    }

    public function orderNumberLookup($order_number = null)
    {
        $o_number = -1;
        if ($order_number !== null) {
            $o_number = $order_number;
        } else {
            $postdata = $this->input->get();
            $o_number = $postdata['order_number'];
        }

        $sql = "SELECT numero_marketplace
                FROM orders
                WHERE LOWER(numero_marketplace) = '" . strtolower($o_number) . "'";

        $query = $this->db->query($sql);

        $return = json_encode('not_found');
        $result = $query->row_array();
        if (!empty($result)) {
            $return = json_encode('order_found');
        }

        if ($order_number !== null) {
            return json_decode($return);
        } else {
            print_r(json_encode($return));
        }
    }

    public function toggleSendTrackingCode()
    {
        $postdata = $this->postClean(NULL,TRUE);

        $toggle_url = -1;
        $tracking_url = -1;
        $return = json_encode('not_updated');

        /*
        A função espera duas variáveis:
        - toggle_send_tracking_code: ativa/desativa o envio da informação de rastreio para o marketplace;
        - tracking_url: a URL de rastreio do marketplace.
        */
        foreach ($postdata as $key => $value) {
            if ($key == 'toggle_send_tracking_code') {
                if (!$value || ($value == "false")) {
                    $toggle_url = 0;
                } else if ($value == "true") {
                    $toggle_url = 1;
                }
            } else if ($key == 'tracking_url') {
                $tracking_url = $value;
            }
        }

        $send_tracking_code_to_mkt = -1;
        $tracking_code_to_send = -1;

        // Verifica se já existem na tabela do banco os valores "send_tracking_code_to_mkt" e "tracking_code_to_send".
        $sql = "SELECT * FROM settings";
        $query = $this->db->query($sql);
        $result = $query->result_array();
        if (!empty($result)) {
            foreach($result as $r) {
                if ($r['name'] == 'send_tracking_code_to_mkt') {
                    $send_tracking_code_to_mkt = $r['value'];
                } else if ($r['name'] == 'tracking_code_to_send') {
                    $tracking_code_to_send = $r['value'];
                }
            }
        }

        // Se necessário, cria no banco o valor "send_tracking_code_to_mkt".
        if (($send_tracking_code_to_mkt == -1) && ($toggle_url != -1)) {
            $datetime = date('Y-m-d H:i:s');

            $sql = "INSERT INTO settings (
                        name, 
                        value, 
                        status,
                        user_id,
                        date_updated
                    ) VALUES (
                        'send_tracking_code_to_mkt', 
                        '$toggle_url', 
                        '1',
                        '1',
                        '$datetime'
                    )";

            $query = $this->db->query($sql);
            $return = json_encode('updated');

        /*
        Se o valor "send_tracking_code_to_mkt" já existe, e se a função recebeu "toggle_send_tracking_code", 
        atualiza a informação no banco de dados.
        */
        } else if (($send_tracking_code_to_mkt != -1) && ($toggle_url != -1)) {
            $datetime = date('Y-m-d H:i:s');

            $sql = "UPDATE settings
                    SET `value` = '$toggle_url', `date_updated` = '$datetime'
                    WHERE `name` = 'send_tracking_code_to_mkt'";
            $update = $this->db->query($sql);
            $return = json_encode('updated');
        }

        // Se necessário, cria no banco o valor "tracking_code_to_send".
        if (($tracking_code_to_send == -1) && ($tracking_url != -1)) {
            $datetime = date('Y-m-d H:i:s');

            $sql = "INSERT INTO settings (
                        name, 
                        value, 
                        status,
                        user_id,
                        date_updated
                    ) VALUES (
                        'tracking_code_to_send', 
                        '$tracking_url', 
                        '1',
                        '1',
                        '$datetime'
                    )";

            $query = $this->db->query($sql);
            $return = json_encode('updated');

        /*
        Se o valor "tracking_code_to_send" já existe, e se a função recebeu "tracking_url",
        atualiza a informação no banco de dados.
        */
        } else if (($tracking_code_to_send != -1) && ($tracking_url != -1)) {
            $datetime = date('Y-m-d H:i:s');

            $sql = "UPDATE settings
                    SET `value` = '$tracking_url', `date_updated` = '$datetime'
                    WHERE `name` = 'tracking_code_to_send'";
            $update = $this->db->query($sql);
            $return = json_encode('updated');
        }

        print_r(json_encode($return));
    }
}
