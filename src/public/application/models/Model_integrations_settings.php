<?php

class Model_integrations_settings extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createIntegration($id = '',$request){
        if($id){
            $request['integration_id'] = $id;

                # SE NÃO EXISTE INTEGRAÇÃO MAS EXISTE NA integrations
                $verify = $this->db->get_where('integrations_settings is', ['integration_id' => $request['integration_id']])->num_rows();
                if($verify == 0){
                    return $this->db->insert('integrations_settings', $request);
                }

            $this->db->where('integration_id', $id);
            return $this->db->update('integrations_settings', $request);

        }
		return $this->db->insert('integrations_settings', $request);
	}
    public function verifyExistRegister($id)
    {
        $count = $this->db->get_where('integrations_settings', ['integration_id' => $id])->num_rows();

        if($count > 0){
            return $count;
        }

        return $count;
    }

    public function createJob($int_to)
    {

        $start_date = date('Y-m-d');
        $end_date = '2200-12-31 23:59:00';

        $sql = "INSERT INTO calendar_events (title, event_type, start, end, module_path, module_method, params, alert_after) VALUES 
            ('Baixa Categorias Vtex',71, '".$start_date." 05:10:00', '".$end_date."','SellerCenter/Vtex/CategoryV2',
             'run','{$int_to}',800),
            ('Baixa Marcas da Vtex',30, '".$start_date." 05:20:00', '".$end_date."','SellerCenter/Vtex/BrandsDownload',
             'run','{$int_to}',60),        
            ('Verifica se tem Produto enviado que foi aprovado na Vtex',480, '".$start_date." 05:30:00', '".$end_date."','SellerCenter/Vtex/ProductsStatusV2',
             'run','{$int_to}',360),
            ('Baixar atualização de pedido',10, '".$start_date." 05:00:00', '".$end_date."','SellerCenter/Vtex/VtexOrders',
             'run','{$int_to}',120),
            ('Enviar atualização de pedido',10, '".$start_date." 05:05:00', '".$end_date."','SellerCenter/Vtex/VtexOrdersStatus',
             'run','{$int_to}',60),
            ('Cadastra Loja na Vtex (Seller)',10, '".$start_date." 05:15:00', '".$end_date."','SellerCenter/Vtex/SellerV2',
             'run','{$int_to}',30),
            ('Baixa URL da VTEX',240, '".$start_date." 05:35:00', '".$end_date."','SellerCenter/Vtex/ProductsGetURL',
             'run','{$int_to}',240),
            ('Baixar interações de pagamento dos pedidos VTEX',71, '".$start_date." 05:25:00', '".$end_date."','SellerCenter/Vtex/VtexPaymentInteration',
             'run','{$int_to}',360)
        ";

        return $query = $this->db->query($sql);

    }

    public function createJobConectala($int_to)
    {

        $start_date = date('Y-m-d');
        $end_date = '2200-12-31 23:59:00';

        $sql = "INSERT INTO calendar_events (title, event_type, start, end, module_path, module_method, params, alert_after) VALUES 
            ('Enviar atualização de pedido {$int_to}',10, '".$start_date." 05:10:00', '".$end_date."','Marketplace/Conectala/OrdersStatus',
             'run','{$int_to}',60),
             ('Buscar pedidos {$int_to}',10, '".$start_date." 05:10:00', '".$end_date."','Marketplace/Conectala/GetOrders',
             'run','{$int_to}',60),
             ('Cria Integrações das Lojas Novas para a {$int_to}',10, '".$start_date." 05:10:00', '".$end_date."','Marketplace/Conectala/CreateIntegrations',
             'run','{$int_to}',60)
        ";

        return $query = $this->db->query($sql);

    }

    public function getIntegrationSettingsbyId($store_id)
    {
        $sql = "SELECT * FROM integrations_settings WHERE integration_id = ?";
        $query = $this->db->query($sql, array($store_id));
        return $query->row_array();
    }

    public function getIntegrationSettingsbyIntto(string $int_to): ?array
    {
        return $this->db->select('is.*')
            ->where('i.int_to', $int_to)
            ->join('integrations i', 'i.id = is.integration_id')
            ->get('integrations_settings is')
            ->row_array();
    }
}
