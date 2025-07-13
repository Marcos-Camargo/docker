<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class LogMarketplace {
    function log($id_produto, $sku, $datahora, $cotacao_externa, $cotacao_interna,
            $cep_destino, $preco, $prazo, $transportadora, $servico, $tempo_total_consulta,
            $observacao) {

        $CI =& get_instance();

        $quotes = Array();
        $quotes['id_produto'] = $id_produto;
        $quotes['sku'] = $sku;
        $quotes['datahora'] = $datahora;
        $quotes['cotacao_externa'] = $cotacao_externa;
        $quotes['cotacao_interna'] = $cotacao_interna;
        $quotes['cep_destino'] = $cep_destino;
        $quotes['preco'] = $preco; 
        $quotes['prazo'] = $prazo;
        $quotes['transportadora'] = $transportadora;
        $quotes['servico'] = $servico;
        $quotes['tempo_total_consulta'] = $tempo_total_consulta;
        $quotes['observacao'] = $observacao;

        $CI->db->insert('quotes_marketplace', $quotes);
    }
}
