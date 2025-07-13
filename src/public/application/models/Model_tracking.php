<?php 
/*
Model de Acesso ao BD para Rastreamento de Pedidos.
*/  

class Model_tracking extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function emailValidation(string $email = null)
    {
        // Um endereço de e-mail foi informado?
        if (!empty($email)) {
            // O endereço é válido?
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'invalid';
            }

            $row = $this->db->select('COUNT(id) AS qtd')->or_where(array(
                'fr_email_contato'  => $email,
                'fr_email_nfe'      => $email,
                'fr_email_login'    => $email
            ))->get('stores')->row_array();

            // Se nenhum resultado foi retornado na consulta anterior,
            // isto quer dizer que o endereço de email NÂO é utilizado por um seller.
            // Implicando na possibilidade de ser o endereço de um cliente.
            $email_type = 'client';
            if ($row['qtd'] >= 1) {
                // Se pelo menos um resultado foi retornado na consulta anterior,
                // isto quer dizer que o endereço de email é utilizado por um seller.
                $email_type = 'seller';
            }

            return $email_type;
        }

        return 'not informed';
    }

    public function trimmedNumber($value)
    {
        $aux_value = "";
        $start_value = true;
        for ($index = 0; $index < strlen($value); $index++) {
            if (($value[$index] != '0') && ($start_value)) {
                $aux_value .= $value[$index];
                $start_value = false;
            } else if ($start_value === false) {
                $aux_value .= $value[$index];
            } else {
                $start_value = false;
            }
        }

        return $aux_value;
    }

    public function trackingCodeValidation(string $cpf_cnpj = null, string $order_id = null)
    {
        $cpf_cnpj = $this->trimmedNumber($cpf_cnpj);

        $this->db->select('o.id, o.paid_status, o.ship_company_preview, o.ship_service_preview, o.date_time, o.data_pago, o.data_envio, o.data_entrega, o.date_cancel, c.cpf_cnpj')
            ->join('freights f', "o.id = f.order_id")
            ->join('clients c', "(REPLACE(REPLACE(REPLACE(c.cpf_cnpj, '.', ''), '-', ''), '/', '')) LIKE '%$cpf_cnpj%' AND o.customer_id = c.id");

        if (!empty($cpf_cnpj) && (!empty($order_id) && ($order_id != "NA"))) {
            return $this->db->where("f.order_id", $order_id)->get('orders o')->result_array();
        } else if (!empty($cpf_cnpj)) {
            return $this->db->get('orders o')->result_array();
        }

        return 'not informed';
    }
}