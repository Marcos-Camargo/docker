<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Router $router
 * @property Model_settings $model_settings
 * @property CI_URI $uri
 * @property Model_order_to_delivered $model_order_to_delivered
 */
class OrderToDelivered extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->permission = $this->session->userdata('permission') ?? [];

        $this->data['page_title'] = $this->lang->line('application_order_to_delivered');
        $this->data['page_now'] = 'order_to_delivered';
        $this->data['company_and_store'] = $this->session->userdata('company_and_store') ?? '';
        $this->data['url_active'] = $this->uri->uri_string();

        $this->load->model('model_settings');
        $this->load->model('model_order_to_delivered');
    }

    public function index(){
        $marketplaces = $this->model_order_to_delivered->getAllMarketplaces();

        $configs = [];
        $lojas_por_marketplace = [];

        if ($marketplaces) {
            foreach ($marketplaces as $mkt) {
                if (!isset($mkt->int_to)) continue;
                $key = $mkt->int_to;
                
                $config = $this->model_order_to_delivered->getFirst($mkt->int_to);
                if ($config) {
                    $configs[$key] = $config;
                }

                $lojas_por_marketplace[$key] = $this->model_order_to_delivered->getByMarketplace($mkt->int_to);
            }
        }

        $selectedMarketplace = ($_GET['marketplace'] ?? ($marketplaces[0]->int_to ?? ''));

        $this->data['configs'] = $configs;
        $this->data['marketplaces'] = $marketplaces;
        $this->data['config'] = $configs[strtolower(trim($selectedMarketplace))] ?? [];
        $this->data['lojas'] = $lojas_por_marketplace[strtolower(trim($selectedMarketplace))] ?? [];
        $this->data['lojas_por_marketplace'] = $lojas_por_marketplace;
        $this->render_template('orderToDelivered/index', $this->data);
    }

    public function getLojasByMarketplace(){
        $marketplace = $this->input->get('marketplace');
        if (!$marketplace) {
            echo json_encode([]);
            return;
        }

        $lojas = $this->model_order_to_delivered->getByMarketplace($marketplace);
        echo json_encode($lojas);
    }
    public function save(){
        
        $post = $this->input->post();
    
        $marketplace = $post['marketplace'] ?? null;
        if (!$marketplace) {
            $this->session->set_flashdata('error', 'Marketplace não informado.');
            redirect('orderToDelivered');
        }

        $dias = $post['dias'] ?? null;

        if (!is_numeric($dias) || intval($dias) != $dias || intval($dias) < 0) {
            $this->session->set_flashdata('error', 'O campo "Dias para atualizar os pedidos" deve ser um número igual ou maior que zero.');
            redirect('orderToDelivered?marketplace=' . urlencode($post['marketplace'] ?? ''));
            return;
        }

        $urlConsulta = $post['url-consulta'] ?? null;

        if ($urlConsulta && !filter_var($urlConsulta, FILTER_VALIDATE_URL)) {
            $this->session->set_flashdata('error', 'O campo "URL de consulta" deve conter uma URL válida.');
            redirect('orderToDelivered?marketplace=' . urlencode($marketplace));
            return;
        }

        $urlRastreio = $post['url-rastreio'] ?? null;

        if ($urlRastreio && !filter_var($urlRastreio, FILTER_VALIDATE_URL)) {
            $this->session->set_flashdata('error', 'O campo "URL de rastreio" deve conter uma URL válida.');
            redirect('orderToDelivered?marketplace=' . urlencode($marketplace));
            return;
        }

        $data = [
            'dias_para_atualizar' => intval($dias),
            'url_consulta' => $urlConsulta,
            'sequencial_nfe' => isset($post['sequencial']) ? 1 : 0,
            'transportadora' => $post['transportadora'] ?? null,
            'metodo_envio' => $post['metodo-envio'] ?? null,
            'codigo_rastreio' => $post['codigo-rastreio'] ?? null,
            'url_rastreio' => $urlRastreio
        ];
    
        $config = $this->model_order_to_delivered->getFirst($marketplace);
        if ($config) {
            $this->model_order_to_delivered->update($marketplace, $data);
        } else {
            $data['marketplace'] = $marketplace;
            $this->model_order_to_delivered->create($data);
        }

        $config = $this->model_order_to_delivered->getFirst($marketplace); 
        $order_to_delivered_config_id = $config['id'] ?? null;

        $this->db->select('s.id');
        $this->db->from('stores s');
        $this->db->join('integrations i', 'i.store_id = s.id');
        $this->db->where('i.int_to', $marketplace); //pega o int_to por ser algo mais seguro ao inves do name
        $store_ids = array_column($this->db->get()->result_array(), 'id');

        if (!empty($store_ids)) {
            $this->db->where_in('id', $store_ids);
            $this->db->update('stores', ['has_order_to_send_config' => 0]);
        }

        $marcadas = $post['lojas'] ?? [];

        if (!empty($marcadas)) {
            $this->db->where_in('id', $marcadas);
            $this->db->update('stores', ['has_order_to_send_config' => 1]);
        }

        if ($order_to_delivered_config_id) {
            foreach ($store_ids as $store_id) {
                $this->model_order_to_delivered->saveTracking($store_id, $marketplace, $order_to_delivered_config_id);
            }
        }

        $unselected = array_diff($store_ids, $marcadas);
        $this->model_order_to_delivered->deleteUnselectedTrackings($unselected, $marketplace);

        $this->session->set_flashdata('success', 'Configurações salvas com sucesso.');
        redirect('orderToDelivered?marketplace=' . urlencode($marketplace));    
    }
}