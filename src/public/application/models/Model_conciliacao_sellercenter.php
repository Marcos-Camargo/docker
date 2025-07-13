<?php

class Model_conciliacao_sellercenter extends CI_Model
{

    public $tableName = 'conciliacao_sellercenter';

    public function __construct()
    {
        parent::__construct();
    }

    public function insert(string $lote, string $data_criacao, int $store_id, string $seller_name, int $order_id,
                           string $numero_marketplace, string $data_pedido, $data_entrega, string $data_ciclo,
                           string $status_conciliacao, string $valor_pedido, string $valor_produto, string $valor_frete,
                           string $valor_percentual_produto, string $valor_percentual_frete, string $valor_comissao_produto,
                           string $valor_comissao_frete, string $valor_comissao, string $valor_repasse, string $valor_repasse_ajustado,
                           string $usuario, string $tratado, string $observacao): bool
    {

        $data = [];
        $data['lote'] = $lote;
        $data['data_criacao'] = $data_criacao;
        $data['store_id'] = $store_id;
        $data['seller_name'] = $seller_name;
        $data['order_id'] = $order_id;
        $data['numero_marketplace'] = $numero_marketplace;
        $data['data_pedido'] = $data_pedido;
        $data['data_entrega'] = $data_entrega;
        $data['data_ciclo'] = $data_ciclo;
        $data['status_conciliacao'] = $status_conciliacao;
        $data['valor_pedido'] = $valor_pedido;
        $data['valor_produto'] = $valor_produto;
        $data['valor_frete'] = $valor_frete;
        $data['valor_percentual_produto'] = $valor_percentual_produto;
        $data['valor_percentual_frete'] = $valor_percentual_frete;
        $data['valor_comissao_produto'] = $valor_comissao_produto;
        $data['valor_comissao_frete'] = $valor_comissao_frete;
        $data['valor_comissao'] = $valor_comissao;
        $data['valor_repasse'] = $valor_repasse;
        $data['valor_repasse_ajustado'] = $valor_repasse_ajustado;
        $data['usuario'] = $usuario;
        $data['tratado'] = $tratado;
        $data['observacao'] = $observacao;

        return $this->create($data);

    }

    public function create($data): bool
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);
            return $insert == true;
        }
    }

    public function update(array $data, int $id): bool
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);
        return $update == true;
    }

    public function remove(int $id): bool
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete($this->tableName);
        return $delete == true;
    }

    public function sumSellerShippingValueByDeliveredOrderAndStoreIdAndLote(int $store_id, string $lote): array
    {
        return $this->db->select('valor_frete, order_id')
            ->distinct('valor_frete, order_id')
            ->where(array(
                'lote'               => $lote,
                'store_id'           => $store_id,
                'status_conciliacao' => 'Conciliação Ciclo'
            ))->get('conciliacao_sellercenter')
            ->result_array();
    }

    public function sumSellerValueByStatusAndStoreIdAndLote(int $store_id, string $lote, string $status_conciliacao): ?array
    {
        return $this->db->select('sum(valor_comissao) as sum_valor_comissao')
            ->where(array(
                'lote'               => $lote,
                'store_id'           => $store_id,
                'status_conciliacao' => $status_conciliacao
            ))->get('conciliacao_sellercenter')
            ->row_array();
    }

    public function sumSellerValueAdjustedByStatusAndStoreIdAndLote(int $store_id, string $lote, string $status_conciliacao): ?array
    {
            return $this->db->select('sum(valor_repasse_ajustado) as sum_valor_repasse_ajustado')
            ->where(array(
                'lote'               => $lote,
                'store_id'           => $store_id,
                'status_conciliacao' => $status_conciliacao
            ))->get('conciliacao_sellercenter')
            ->row_array();
    }
}