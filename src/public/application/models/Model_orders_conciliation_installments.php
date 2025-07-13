<?php

/**
 * @property Model_orders_payment $model_orders_payment
 */
class Model_orders_conciliation_installments extends CI_Model
{

    private $tableName = 'orders_conciliation_installments';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_orders_payment');
        $this->load->model('model_settings');
        $this->load->model('model_stores');
    }

    /**
     * @param array $conciliationData
     * @return void
     */
    public function insertInstallmentsByConciliationData(array $conciliationData): void
    {

        //FIN-926
        $parametroAntecipacaoLiberada = $this->model_settings->getSettingDatabyName('allow_payment_antecipation_by_store');

        if($parametroAntecipacaoLiberada){
            if($parametroAntecipacaoLiberada['status'] == "1"){
                $parametroAntecipacaoLiberada = true;
            }else{
                $parametroAntecipacaoLiberada = false;
            }
        }else{
            $parametroAntecipacaoLiberada = false;
        }
        
        //Só podemos cadastrar se ainda não foi cadastrado para o pedido em questão - COMENTADA A FUNÇÃO AQUI PARA INSERIR O VALOR DA PARCELA DENTRO DO LOOPING DE CADASTRO
        // POIS É PRECISO VERIFICAR O CADASTRO DO PEDIDO + PARCELA NA HORA DE INSERIR OU NÃO 
        // if ($this->orderHasConciliationInstallments($conciliationData['order_id'],$conciliationData['status_conciliacao'])) {
        //    return;
        // }
        
        //Sempre corrigindo a data do ciclo pois está chegando no formato brasileiro
        $conciliationData['data_ciclo'] = dateBrazilToDateInternational($conciliationData['data_ciclo']);

        $paymentMaxInstallment = $this->model_orders_payment->findMaxParcelFromOrder($conciliationData['order_id']);

        $maxInstallments = 1;
        $paymentMaxInstallment_orders_payment_id = null;

        $storeAllowConciliationInstallment = $this->model_stores->storeAllowConciliationInstallment($conciliationData['store_id']);

        if (!empty($paymentMaxInstallment) && $this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')) {
           
            if($parametroAntecipacaoLiberada &&  !$storeAllowConciliationInstallment){
                $maxInstallments = 1;
                $paymentMaxInstallment_orders_payment_id = null;
            }else{
                $maxInstallments = $paymentMaxInstallment['parcela'];
                $paymentMaxInstallment_orders_payment_id = $paymentMaxInstallment['id'];
            }
        }

        $installmentValue = $conciliationData['valor_repasse'] / $maxInstallments;
        $installmentValue = number_format($installmentValue, 2);

        //Gerando um registro para cada parcela que deve ser gerada
        for ($currentInstallment = 1; $currentInstallment <= $maxInstallments; $currentInstallment++) {

            //Só podemos cadastrar se ainda não foi cadastrado para o pedido em questão - Adicionado o campo de parcela também para caso mude o ciclo ele recalcule as novas parcelas
            if ($this->orderHasConciliationInstallments($conciliationData['order_id'],$conciliationData['status_conciliacao'], $currentInstallment)) {
                continue;
            }

            $orderConciliationInstallment = $conciliationData;
            //Por padrão, vamos sempre gravar o lote vazio, para sempre seguir a mesma lógica de busca da parcela a ser paga
            $orderConciliationInstallment['lote'] = '';
            $orderConciliationInstallment['orders_payment_id'] = $paymentMaxInstallment_orders_payment_id;
            $orderConciliationInstallment['current_installment'] = $currentInstallment;
            $orderConciliationInstallment['total_installments'] = $maxInstallments;
            $orderConciliationInstallment['installment_value'] = $installmentValue;
            $orderConciliationInstallment['paid'] = 0;

            //Ajustando novos valores
            if ($maxInstallments > 1) {
                $orderConciliationInstallment['valor_comissao'] /= $maxInstallments;
                $orderConciliationInstallment['valor_comissao_produto'] /= $maxInstallments;
                $orderConciliationInstallment['valor_comissao_frete'] /= $maxInstallments;
                $orderConciliationInstallment['valor_repasse'] /= $maxInstallments;
                $orderConciliationInstallment['valor_repasse_ajustado'] /= $maxInstallments;
            }


           

            //Ajustando a nova data do ciclo para a 2ª parcela em diante
            if ($currentInstallment > 1) {
                $orderConciliationInstallment['data_ciclo'] = addMonthToDate($orderConciliationInstallment['data_ciclo'], $currentInstallment - 1);
            }

            $this->insert($orderConciliationInstallment);

        }

    }

    public function findByOrderId(int $orderId, int $parcela): ?array
    {

        return $this->db->select('*')->from($this->tableName)->where(['order_id' => $orderId, 'current_installment' => $parcela])->get()->result_array();

    }

    public function findByOrderIdStatusConciliation(int $orderId, string $statusConciliacao, int $parcela): ?array
    {

        return $this->db->select('*')->from($this->tableName)->where(['order_id' => $orderId, 'status_conciliacao' => $statusConciliacao, 'current_installment' => $parcela])->get()->result_array();

    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function orderHasConciliationInstallments(int $orderId, string $statusConciliacao = null, string $parcela = null): bool
    {

        if($statusConciliacao == null){
            return (bool)$this->findByOrderId($orderId, $parcela);
        }else{
            return (bool)$this->findByOrderIdStatusConciliation($orderId,$statusConciliacao,$parcela);
        }

    }

    public function insert($data)
    {
        $this->db->insert($this->tableName, $data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool)$this->db->update($this->tableName, $data, "id = {$id}");
    }

    public function findNextUnpaidInstallment(int $orderId): ?array
    {
        return $this->db->select('*')->from($this->tableName)->where(['order_id' => $orderId, 'paid' => 0])->order_by('current_installment', 'ASC')->limit('1')->get()->row_array();
    }

    public function clearOldData(int $orderId, string $batch): void
    {

        //Verificando se a primeira parcela possui o lote cadastrado na conciliação
        if ($this->isFirstInstallmentInConciliation($orderId)) {
            //Está cadastrado, então vai limpar o lote das parcelas que ainda não estão cadastrados do pedido em questão
            $this->clearByOrderIdInvalidBatch($orderId);
        }

    }

    public function isFirstInstallmentInConciliation(int $orderId): bool
    {

        return (bool)$this->db->distinct('orders_conciliation_installments.order_id')->select('orders_conciliation_installments.order_id')
            ->from('orders_conciliation_installments')
            ->join('conciliacao_sellercenter', 'conciliacao_sellercenter.lote = orders_conciliation_installments.lote')
            ->join('conciliacao', 'conciliacao_sellercenter.lote = conciliacao.lote')
            ->where('orders_conciliation_installments.order_id', $orderId)
            ->where('orders_conciliation_installments.current_installment', 1)
            ->get()
            ->result_array();

    }

    /**
     * Coloca o novo lote nas parcelas que ainda não foram conciliadas
     * @param int $orderId
     * @return void
     */
    public function clearByOrderIdInvalidBatch(int $orderId): void
    {
        $this->db->update(
            $this->tableName,
            ['lote' => ''],
            "order_id = $orderId AND {$this->tableName}.lote <> '' AND {$this->tableName}.lote NOT IN (SELECT DISTINCT conciliacao_sellercenter.lote FROM conciliacao_sellercenter INNER JOIN conciliacao ON (conciliacao.lote = conciliacao_sellercenter.lote) WHERE order_id = $orderId)"
        );
    }

    public function deleteByOrderId(int $orderId): bool
    {
        return (bool)$this->db->delete($this->tableName, ['order_id' => $orderId]);
    }

    /**
     * Busca todos no installment onde a parcela é 1 e o lote ainda não está na conciliação
     * Exclui todos os registros do pedido que tiver caso vier algum inválido aqui
     * @return void
     */
    public function deleteInvalidInstallments(): void
    {

        $invalidInstallments = $this->findInvalidInstallments();

        if ($invalidInstallments) foreach ($invalidInstallments as $invalidInstallment) {
            $this->deleteByOrderId($invalidInstallment['order_id']);
        }

    }

    public function findInvalidInstallments(): array
    {

        return $this->db->select('*')
            ->from('orders_conciliation_installments')
            ->where('orders_conciliation_installments.current_installment', 1)
            ->where('orders_conciliation_installments.lote NOT IN (SELECT distinct lote FROM conciliacao)')
            ->get()
            ->result_array();

    }

    public function clearInvalidBatchesInstallments(): void
    {

        $data = [
            'lote' => ''
        ];

        $this->db->where('orders_conciliation_installments.lote NOT IN (SELECT distinct lote FROM conciliacao)')
                    ->update($this->tableName, $data);

    }

    public function findAllToBeReconciled(array $cicleDate, array $apelidosMkt): array
    {

        return $this->db->select('orders_conciliation_installments.*')
            ->from('orders_conciliation_installments')
            ->join('orders', 'orders.id = orders_conciliation_installments.order_id')
            ->where_in('orders_conciliation_installments.data_ciclo', $cicleDate)
            ->where_in('orders.origin', $apelidosMkt)
            ->where('orders_conciliation_installments.lote', '')
            ->where('orders_conciliation_installments.paid', '0')
            ->get()
            ->result_array();

    }

    public function getApelidosMarketplace($ids = [])
    {
        if (empty($ids)) return [];

        $this->db->select('id_mkt, apelido');
        $this->db->from('stores_mkts_linked');
        $this->db->where_in('id_mkt', $ids);
        $query = $this->db->get();

        return $query->result_array();
    }

    public function convertConciliationInstallmentToConciliationSellerCenter(array $conciliationInstallment): array
    {

        $data = [];
        $data['lote'] = $conciliationInstallment['lote'];
        $data['store_id'] = $conciliationInstallment['store_id'];
        $data['seller_name'] = $conciliationInstallment['seller_name'];
        $data['order_id'] = $conciliationInstallment['order_id'];
        $data['numero_marketplace'] = $conciliationInstallment['numero_marketplace'];
        $data['data_pedido'] = $conciliationInstallment['data_pedido'];
        $data['data_entrega'] = $conciliationInstallment['data_entrega'];
        $data['data_ciclo'] = $conciliationInstallment['data_ciclo'];
        $data['status_conciliacao'] = $conciliationInstallment['status_conciliacao'];
        $data['valor_pedido'] = $conciliationInstallment['valor_pedido'];
        $data['valor_produto'] = $conciliationInstallment['valor_produto'];
        $data['valor_frete'] = $conciliationInstallment['valor_frete'];
        $data['valor_percentual_produto'] = $conciliationInstallment['valor_percentual_produto'];
        $data['valor_percentual_frete'] = $conciliationInstallment['valor_percentual_frete'];
        $data['valor_comissao_produto'] = $conciliationInstallment['valor_comissao_produto'];
        $data['valor_comissao_frete'] = $conciliationInstallment['valor_comissao_frete'];
        $data['valor_comissao'] = $conciliationInstallment['valor_comissao'];
        $data['valor_repasse'] = !$conciliationInstallment['anticipated'] ? $conciliationInstallment['valor_repasse'] : 0;
        $data['valor_repasse_ajustado'] = !$conciliationInstallment['anticipated'] ? $conciliationInstallment['valor_repasse_ajustado'] : 0;
        $data['usuario'] = $conciliationInstallment['usuario'];
        $data['tipo_pagamento'] = $conciliationInstallment['tipo_pagamento'];
        $data['taxa_cartao_credito'] = $conciliationInstallment['taxa_cartao_credito'];
        $data['tratado'] = $conciliationInstallment['tratado'];
        $data['observacao'] = $conciliationInstallment['observacao'];
        if ($conciliationInstallment['anticipated']){
            $data['observacao'].= "Pedido antecipado";
        }
        $data['refund'] = $conciliationInstallment['refund'];
        $data['digitos_cartao'] = $conciliationInstallment['digitos_cartao'];
        $data['current_installment'] = $conciliationInstallment['current_installment'];
        $data['total_installments'] = $conciliationInstallment['total_installments'];
        $data['cnpj'] = $conciliationInstallment['cnpj'];
        $data['data_report'] = $conciliationInstallment['data_report'];

        return $data;

    }

    public function setPaidByBatch(string $batch): void
    {

        $this->db->update($this->tableName, ['paid' => 1], "lote = '$batch'");

    }

    public function sumTotalNotPaidByOrdersIdStoreId(array $ordersId, int $storeId): float
    {

        $return = $this->db->select('sum(installment_value) as total_not_paid')
            ->from($this->tableName)
            ->where(['store_id' => $storeId, 'paid' => 0, 'anticipated' => 0])
            ->where_in('order_id', $ordersId)
            ->get()->row_array();

        return (float)$return['total_not_paid'];

    }

    public function markOrdersAsAnticipated(array $ordersId, int $storeId, int $anticipated = 1): float
    {

        $data = [
            'anticipated' => $anticipated
        ];

        return $this->db->where(['store_id' => $storeId, 'paid' => 0])
                            ->where_in('order_id', $ordersId)
                        ->update($this->tableName, $data);

    }

    /**************FUNÇÕES NOVAS CICLO FISCAL */

    public function clearInvalidBatchesInstallmentsFiscal(): void
    {

        $data = [
            'lote' => ''
        ];

        $this->db->where('orders_conciliation_installments_fiscal.lote NOT IN (SELECT distinct lote FROM conciliacao_fiscal)')
                    ->update('orders_conciliation_installments_fiscal', $data);

    }

    public function clearOldDataFiscal(int $orderId, string $batch): void
    {

        //Verificando se a primeira parcela possui o lote cadastrado na conciliação
        if ($this->isFirstInstallmentInConciliationFiscal($orderId)) {
            //Está cadastrado, então vai limpar o lote das parcelas que ainda não estão cadastrados do pedido em questão
            $this->clearByOrderIdInvalidBatchFiscal($orderId);
        }

    }

    public function isFirstInstallmentInConciliationFiscal(int $orderId): bool
    {

        return (bool)$this->db->distinct('orders_conciliation_installments_fiscal.order_id')->select('orders_conciliation_installments_fiscal.order_id')
            ->from('orders_conciliation_installments_fiscal')
            ->join('conciliacao_sellercenter_fiscal', 'conciliacao_sellercenter_fiscal.lote = orders_conciliation_installments_fiscal.lote')
            ->join('conciliacao_fiscal', 'conciliacao_sellercenter_fiscal.lote = conciliacao_fiscal.lote')
            ->where('orders_conciliation_installments_fiscal.order_id', $orderId)
            ->where('orders_conciliation_installments_fiscal.current_installment', 1)
            ->get()
            ->result_array();

    }

    /**
     * Coloca o novo lote nas parcelas que ainda não foram conciliadas
     * @param int $orderId
     * @return void
     */
    public function clearByOrderIdInvalidBatchFiscal(int $orderId): void
    {
        $this->db->update(
            'orders_conciliation_installments_fiscal',
            ['lote' => ''],
            "order_id = $orderId AND orders_conciliation_installments_fiscal.lote <> '' AND orders_conciliation_installments_fiscal.lote NOT IN (SELECT DISTINCT conciliacao_sellercenter_fiscal.lote FROM conciliacao_sellercenter_fiscal INNER JOIN conciliacao_fiscal ON (conciliacao_fiscal.lote = conciliacao_sellercenter_fiscal.lote) WHERE order_id = $orderId)"
        );
    }

    /**
     * @param array $conciliationData
     * @return void
     */
    public function insertInstallmentsByConciliationDataFiscal(array $conciliationData): void
    {

        //FIN-926
        $parametroAntecipacaoLiberada = $this->model_settings->getSettingDatabyName('allow_payment_antecipation_by_store');

        if($parametroAntecipacaoLiberada){
            if($parametroAntecipacaoLiberada['status'] == "1"){
                $parametroAntecipacaoLiberada = true;
            }else{
                $parametroAntecipacaoLiberada = false;
            }
        }else{
            $parametroAntecipacaoLiberada = false;
        }
        
        //Só podemos cadastrar se ainda não foi cadastrado para o pedido em questão
        if ($this->orderHasConciliationInstallmentsFiscal($conciliationData['order_id'],$conciliationData['status_conciliacao'])) {
            return;
        }
        
        //Sempre corrigindo a data do ciclo pois está chegando no formato brasileiro
        $conciliationData['data_ciclo'] = dateBrazilToDateInternational($conciliationData['data_ciclo']);

        $paymentMaxInstallment = $this->model_orders_payment->findMaxParcelFromOrder($conciliationData['order_id']);

        $maxInstallments = 1;
        $paymentMaxInstallment_orders_payment_id = null;

        $storeAllowConciliationInstallment = $this->model_stores->storeAllowConciliationInstallment($conciliationData['store_id']);

        if (!empty($paymentMaxInstallment) && $this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')) {
           
            if($parametroAntecipacaoLiberada &&  !$storeAllowConciliationInstallment){
                $maxInstallments = 1;
                $paymentMaxInstallment_orders_payment_id = null;
            }else{
                // $maxInstallments = $paymentMaxInstallment['parcela'];
                // $paymentMaxInstallment_orders_payment_id = $paymentMaxInstallment['id'];
                
                // Alteração Feita para sempre forçar ser feito o ciclo fiscal em 1 parcela
                $maxInstallments = 1;
                $paymentMaxInstallment_orders_payment_id = null;
            }
        }

        $installmentValue = $conciliationData['valor_repasse'] / $maxInstallments;
        $installmentValue = number_format($installmentValue, 2);

        //Gerando um registro para cada parcela que deve ser gerada
        for ($currentInstallment = 1; $currentInstallment <= $maxInstallments; $currentInstallment++) {

            $orderConciliationInstallment = $conciliationData;
            //Por padrão, vamos sempre gravar o lote vazio, para sempre seguir a mesma lógica de busca da parcela a ser paga
            $orderConciliationInstallment['lote'] = '';
            $orderConciliationInstallment['orders_payment_id'] = $paymentMaxInstallment_orders_payment_id;
            $orderConciliationInstallment['current_installment'] = $currentInstallment;
            $orderConciliationInstallment['total_installments'] = $maxInstallments;
            $orderConciliationInstallment['installment_value'] = $installmentValue;
            $orderConciliationInstallment['paid'] = 0;

            //Ajustando novos valores
            if ($maxInstallments > 1) {
                $orderConciliationInstallment['valor_comissao'] /= $maxInstallments;
                $orderConciliationInstallment['valor_comissao_produto'] /= $maxInstallments;
                $orderConciliationInstallment['valor_comissao_frete'] /= $maxInstallments;
                $orderConciliationInstallment['valor_repasse'] /= $maxInstallments;
                $orderConciliationInstallment['valor_repasse_ajustado'] /= $maxInstallments;
            }


           

            //Ajustando a nova data do ciclo para a 2ª parcela em diante
            if ($currentInstallment > 1) {
                $orderConciliationInstallment['data_ciclo'] = addMonthToDate($orderConciliationInstallment['data_ciclo'], $currentInstallment - 1);
            }

            $this->insertFiscal($orderConciliationInstallment);

        }

    }

    public function orderHasConciliationInstallmentsFiscal(int $orderId, string $statusConciliacao = null): bool
    {

        if($statusConciliacao == null){
            return (bool)$this->findByOrderIdFiscal($orderId);
        }else{
            return (bool)$this->findByOrderIdStatusConciliationFiscal($orderId,$statusConciliacao);
        }

    }

    public function findByOrderIdFiscal(int $orderId): ?array
    {

        return $this->db->select('*')->from('orders_conciliation_installments_fiscal')->where(['order_id' => $orderId])->get()->result_array();

    }

    public function findByOrderIdStatusConciliationFiscal(int $orderId, string $statusConciliacao): ?array
    {

        return $this->db->select('*')->from('orders_conciliation_installments_fiscal')->where(['order_id' => $orderId, 'status_conciliacao' => $statusConciliacao])->get()->result_array();

    }

    public function insertFiscal($data)
    {
        $this->db->insert('orders_conciliation_installments_fiscal', $data);
    }

    public function updateFiscal(int $id, array $data): bool
    {
        return (bool)$this->db->update('orders_conciliation_installments_fiscal', $data, "id = {$id}");
    }

    public function findAllToBeReconciledFiscal(string $cicleDate): array
    {

        return $this->db->select('*')
            ->from('orders_conciliation_installments_fiscal')
            ->where('orders_conciliation_installments_fiscal.data_ciclo', $cicleDate)
            ->where('orders_conciliation_installments_fiscal.lote', '')
            ->where('orders_conciliation_installments_fiscal.paid', '0')
            ->get()
            ->result_array();

    }

    public function setPaidByBatchFiscal(string $batch): void
    {

        $this->db->update('orders_conciliation_installments_fiscal', ['paid' => 1], "lote = '$batch'");

    }

    public function deleteFiscalByOrderId(int $orderId): bool
    {
        return (bool)$this->db->delete('orders_conciliation_installments_fiscal', ['order_id' => $orderId]);
    }

}