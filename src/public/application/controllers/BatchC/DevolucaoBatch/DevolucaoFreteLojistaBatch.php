<?php
/** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Class DevolucaoFreteLojistaBatch
 */
class DevolucaoFreteLojistaBatch extends GenericBatch
{

    private $check_batch_refund_release_payment_param;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );

        $this->session->set_userdata($logged_in_sess);

        //Models        
        $this->load->model('model_settings');
        $this->load->model('model_product_return');
        $this->load->model('model_legal_panel');

        $this->check_batch_refund_release_payment_param = $this->model_settings->getValueIfAtiveByName('check_batch_refund_release_payment_param');

    }

    public function runDevolutions($id = null, $params = null): void
    {

        if(!$this->check_batch_refund_release_payment_param)
            return;

        $this->startJob(__FUNCTION__, $id, $params);

        $produtos = $this->model_product_return->getProductsToReturn();
        if($produtos){
            foreach($produtos as $key => $produto){
                $orderId = $produto->orderId;
                $product_return_id = $produto->pr_id;
                $data_entrega = $produto->data_de_entrega;
                $repasse = 0;

                // RECUPERA A REGRA DE ESTORNO ATIVA NO MOMENTO
                $regra = $this->model_product_return->getActiveChargeBackRule($produto->origin);
                if(is_null($regra)){
                    echo 'Não existe regra de devolução cadastrada. Cadastre uma regra de devolução.';
                    return;
                }

                $qtdProductsInOrder = $this->model_product_return->getQtdProductsInOrder($orderId);
                $ciclo = $this->model_product_return->isAfterPaymentCicle($orderId, $product_return_id, $data_entrega);

                $devolucao = $this->model_product_return->checkQtdProductInProductReturn($produto->quantity_requested, $orderId);
                $devolucao = $devolucao ? "integral" : "parcial";

                $frete = $produto->tipo_frete > 0 ? "lojista" : "sellercenter";

                if($frete == 'lojista'){

                    // INTEGRAL E DENTRO DO CICLO - 421
                    if($ciclo && $devolucao == 'integral')
                    {

                        // SEM COBRANÇA
                        if($regra->rule_full_refund_inside_cicle == 'no_charge'){
                            // valor_liquido - frete
                            $repasse = $produto->net_amount - $produto->service_charge;
                        }
                        // ESTORNO DA COMISSAO DENTRO DO CICLO
                        if($regra->rule_full_refund_inside_cicle == 'commission_reversal'){ // OK

                            // $valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            // valor_bruto_produtos + comissao_frete
                            $repasse = $produto->total_order + $produto->total_ship;
                        }
                    }

                    // INTEGRAL E FORA DO CICLO - 422
                    if(!$ciclo && $devolucao == 'integral'){

                        // VALOR BRUTO
                        if($regra->rule_full_refund_outside_cicle == 'refund_gross_order_amount'){
                            //$valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            // valor_bruto_produtos + comissao_frete
                            $repasse = $produto->gross_amount;
                        }
                        // VALOR LIQUIDO
                        if($regra->rule_full_refund_outside_cicle == 'reversal_net_order_value'){ // OK
                            // valor_liquido - frete
                            $repasse = $produto->net_amount - $produto->service_charge;
                        }
                    }

                    // PARCIAL E DENTRO DO CICLO - 423
                    if($ciclo && $devolucao == 'parcial')
                    {

                        // SEM COBRANÇA
                        if($regra->rule_partial_refund_inside_cicle == 'no_charge'){

                            $valor_produto = $produto->return_total_value;
                            $comissao_produto_devolvido = ($valor_produto) * ($produto->service_charge_rate / 100);
                            $comissao_produto_devolvido = number_format($comissao_produto_devolvido, 2, '.', '.');
                            $valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            $frete_pedido = $produto->total_ship;
                            // (valor_produto_devolvido - comissao_produto_devolvido) + ((frete - comissao_frete)/n_produtos_pedido)
                            $repasse = ($valor_produto - $comissao_produto_devolvido) + (($frete_pedido - $valor_comissao_frete) / $qtdProductsInOrder);

                        }

                        // Estorno da comissão (frete total)
                        if($regra->rule_partial_refund_inside_cicle == 'refund_commission_returned_products_commission_total_shipping'){
                            $valor_produto = $produto->return_total_value;
                            // (valor_produto_devolvido) + (frete)
                            $repasse = ($valor_produto) + ($produto->total_ship);

                        }
                        // Estorno da comissão (frete parcial)
                        if($regra->rule_partial_refund_inside_cicle == 'refund_commission_returned_products_commission_partial_shipping'){
                            $valor_comissao_frete = $produto->total_ship;
                            $valor_produto = $produto->return_total_value;
                            // (valor_produto_devolvido) + (frete/n_produtos_pedido)
                            $repasse = $valor_produto + ($valor_comissao_frete / $qtdProductsInOrder);

                        }
                    }

                    // PARCIAL E FORA DO CICLO - 424
                    if(!$ciclo && $devolucao == 'parcial')
                    {

                        // Estorno do valor bruto (frete total)
                        if($regra->rule_partial_refund_outside_cicle == 'refund_gross_value_returned_products_product_total_freight'){
                            //$valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            $valor_produto = $produto->return_total_value;
                            // (valor_produto_devolvido) + (frete)
                            $repasse = $valor_produto + $produto->total_ship;

                        }
                        // Estorno do valor bruto (frete parcial)
                        if($regra->rule_partial_refund_outside_cicle == 'refund_gross_value_returned_products_product_partial_freight'){
                            $valor_produto = $produto->return_total_value;
                            $valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            $repasse = $valor_produto + ($produto->total_ship / $qtdProductsInOrder);
                        }
                        // Estorno do valor líquido (frete total)
                        if($regra->rule_partial_refund_outside_cicle == 'refund_net_value_returned_products_products_total_shipping_product_commission_total_shipping_commission'){
                            $valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            $valor_produto = $produto->return_total_value;
                            $comissao_produto_devolvido = ($produto->gross_amount - $produto->total_ship) * ($produto->service_charge_rate / 100);
                            $comissao_produto_devolvido = number_format($comissao_produto_devolvido / $qtdProductsInOrder, 2, '.', '.');
                            // (valor_produto_devolvido - comissao_produto_devolvido) + (frete - comissao_frete)
                            $repasse = ($valor_produto - $comissao_produto_devolvido) + ($produto->total_ship - $valor_comissao_frete);
                        }
                        // Estorno do valor líquido (frete parcial)
                        if($regra->rule_partial_refund_outside_cicle == 'refund_net_value_returned_products_products_partial_shipping_product_commission_partial_shipping_commission'){
                            $frete_pedido = $produto->total_ship;
                            $valor_comissao_frete = $produto->total_ship * ($produto->service_charge_freight_value / 100);
                            $valor_produto = $produto->return_total_value;
                            $comissao_produto_devolvido = ($produto->gross_amount - $produto->total_ship) * ($produto->service_charge_rate / 100);
                            $comissao_produto_devolvido = number_format($comissao_produto_devolvido / $qtdProductsInOrder, 2, '.', '.');
                            // (valor_produto_devolvido - comissao_produto_devolvido) + ((frete - comissao_frete)/n_produtos_pedido)
                            $repasse = ($valor_produto - $comissao_produto_devolvido) + (($frete_pedido - $valor_comissao_frete) / $qtdProductsInOrder);
                        }
                    }

                    $repasse = number_format($repasse, 2, '.', '');

                    $this->model_legal_panel->createDebit(
                        $orderId,
                        'Devolução automática',
                        'Chamado Aberto',
                        'Chamado Aberto - Regra de estorno ativa: '.$regra->id,
                        $repasse,
                        'Rotina API'
                    );
                    // ATUALIZA A COLUNA batch_refund_check DA TABELA product_return PARA NÃO SER LIDA NA PRÓXIMA VEZ QUE O BATCH EXECUTAR
                    $this->model_product_return->updateCheckedProduct($produto->pr_id);

                }


            }
        }

        $this->endJob();

    }

}
