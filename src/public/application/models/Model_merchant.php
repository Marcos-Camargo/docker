<?php

class Model_merchant extends CI_Model
{
    private $dbMerchant;

    public function __construct()
    {
        parent::__construct();
        $this->dbMerchant = $this->load->database('merchant', TRUE);
    }

    public function list($offset, $limit, $procura='', $orderby = '')
    {
        $query = 'select 
                    nome as company_name, 
                    ap.`text` as cna, 
                    porte as company_size, 
                    uf, 
                    municipio as city_name,
                    s.B2W_cnpj as cnpj
                from Sellers s 
                    left join atividade_principal ap on ap.B2W_cnpj = s.B2W_cnpj 
                where s.nome is not null ';

        $query .= $procura.$orderby." LIMIT ?,? ";

        $merchant = $this->dbMerchant->query($query, array(intval($offset), intval($limit)));
        return $merchant->result_array();
    }

    public function listCount($procura='')
    {
        $query = 'select 
               count(1) as total
            from Sellers s 
                left join atividade_principal ap on ap.B2W_cnpj = s.B2W_cnpj 
            where s.nome is not null ';

        $query .= $procura;

        $merchant = $this->dbMerchant->query($query);
        return $merchant->row_array();
    }

    public function count()
    {
        $query = 'select count(1) as total_records from Sellers where nome is not null';
        $merchant = $this->dbMerchant->query($query);
        return $merchant->row_array();
    }

    public function find($cnpj) {
        $query = 'select 
            s.B2W_cnpj as cnpj,
            s.nome as razao_social, 
            s.fantasia as nome_fantasia,
            ap.`text` as cna, 
            s.uf,
            s.municipio,
            s.bairro,
            s.logradouro,
            s.numero,
            s.email,
            s.telefone,
            s.ultima_atualizacao 
        from Sellers s 
            left join atividade_principal ap on ap.B2W_cnpj = s.B2W_cnpj
        where 
            s.B2W_cnpj = ?';

        $merchant = $this->dbMerchant->query($query, array( (string)$cnpj ));
        return $merchant->row_array();
    }

}
