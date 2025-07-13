<?php
defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/Extrato/Extrato.php";

class ExtratoLibrary extends ExtratoHelper
{

  protected $CI;
  protected $length = null;
  protected $start = null;
  protected  $total_count = 0;

  public function __construct($params = array())
  {
    $this->CI = &get_instance();

    $this->CI->load->model('model_settings');

    $this->painelGrupoSoma = $this->CI->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
    $this->painelNovoMundo = $this->CI->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
    $this->painelOrtobom = $this->CI->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
    $this->painelSellerCenter = $this->CI->model_settings->getSettingDatabyName('sellercenter');

    $this->dataRepasseSellerCenter = $this->CI->model_settings->getSettingDatabyName('painel_financeiro_data_repasse_sellercenter');
    $this->valorRealRepasseSellerCenter = $this->CI->model_settings->getSettingDatabyName('painel_financeiro_valor_real_repasse_sellercenter');

    $this->data_transferencia_gateway_extrato = $this->CI->model_settings->getStatusbyName('data_transferencia_gateway_extrato');
    $this->valor_pagamento_gateway_extrato = $this->CI->model_settings->getStatusbyName('valor_pagamento_gateway_extrato');

    foreach ($params as $property => $value) {
      $this->$property = $value;
    }
  }

    public function extratopedidos()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->CI->load->model('model_campaigns_v2');

        if (!$this->dataRepasseSellerCenter) {
            $this->dataRepasseSellerCenter = array('status' => 0);
            //$this->dataRepasseSellerCenter['status'] == "0";
        }

        if (!$this->valorRealRepasseSellerCenter) {
            $this->valorRealRepasseSellerCenter = array();
            $this->valorRealRepasseSellerCenter['status'] = 0;
        }

        if ($this->painelSellerCenter['value'] <> "conectala") {
            $this->painelSellerCenter['status'] = 1;
        } else {
            $this->painelSellerCenter['status'] = 0;
        }

        if ($this->source == self::TELA) {
            $this->setHeader();
        }

        $inputs = $this->CI->input;
        $post = $inputs->post();
        $inputs = $inputs->get();

        if ($this->source == self::TELA) {
            $inputs = cleanArray($inputs);
        }

        if (!is_null($this->length) && !is_null($this->start)) {
            $post['length'] = $this->length;
            $post['start'] = $this->start;
            $post['page'] = $this->page;
        }

        if ($inputs['slc_status'] <> "") {
            $statusFiltro = array();
            $inputs['slc_status'] = explode(',', $inputs['slc_status']);
            $j = 0;

            foreach ($inputs['slc_status'] as $item) {
                //Busca todos os status pelo selecionado na tela
                $statusTela =  $this->CI->model_iugu->statuspedido($item);

                for ($i = 0; $i <= 101; $i++) {
                    $status =  $this->CI->model_iugu->statuspedido($i);

                    if ($status <> false) {
                        if ($status == $statusTela) {
                            $statusFiltro[$j] = $i;
                            $j++;
                        }
                    }
                }
            }

            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        $inputs['extract_api_data_count'] = true;
        $data = $this->CI->model_billet->getPedidosExtratoConciliado($inputs, null, $post, null, null, null);

        $this->total_count = $data['count'];

        $response = [];

        $loop_data = $data['data'];

        foreach ($loop_data as $key => $value) {
            $campaigns_data                 = $this->CI->model_campaigns_v2->getCampaignsTotalsByOrderId($value['id']);

            $campaigns_pricetags            = (!empty($campaigns_data)) ? $campaigns_data['total_pricetags'] : 0;
            $campaigns_campaigns            = (!empty($campaigns_data)) ? $campaigns_data['total_campaigns'] : 0;
            $campaigns_mktplace             = (!empty($campaigns_data)) ? $campaigns_data['total_channel'] : 0;
            $campaigns_seller               = (!empty($campaigns_data)) ? $campaigns_data['total_seller'] : 0;
            $campaigns_promotions           = (!empty($campaigns_data)) ? $campaigns_data['total_promotions'] : 0;
            $campaigns_rebate               = (!empty($campaigns_data)) ? $campaigns_data['total_rebate'] : 0;
            $campaigns_comission_reduction  = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction'] : 0;
            $refund                         = 0;

            $campaigns_comissionreduxchannel    = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction_marketplace'] : 0;
            $campaigns_rebatechannel            = (!empty($campaigns_data)) ? $campaigns_data['total_rebate_marketplace'] : 0;
            $campaigns_channelrefund            = 0;

            $comission = $value['service_charge_value'];

            if ($campaigns_campaigns > 0)
                $campaigns_promotions = 0;

            if ($campaigns_mktplace > 0)
                $valor_repasse = $value['expectativaReceb'] + $campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100)));
            else
                $valor_repasse = $value['expectativaReceb'] + $campaigns_comission_reduction;

            $valor_repasse += $campaigns_rebate;

            if ($campaigns_mktplace > 0)
                $valor_comissao = $value['comission'] - $campaigns_comission_reduction + ($campaigns_mktplace * ($comission / 100));
            else
                $valor_comissao = $value['comission'] - $campaigns_comission_reduction;

            $refund = $valor_repasse - $value['expectativaReceb'];

            $campaigns_pricetags            = number_format($campaigns_pricetags, 2, ",", ".");
            $campaigns_campaigns            = number_format($campaigns_campaigns, 2, ",", ".");
            $campaigns_mktplace             = number_format($campaigns_mktplace, 2, ",", ".");
            $campaigns_seller               = number_format($campaigns_seller, 2, ",", ".");
            $campaigns_promotions           = number_format($campaigns_promotions, 2, ",", ".");
            $campaigns_rebate               = number_format($campaigns_rebate, 2, ",", ".");
            $campaigns_comission_reduction  = number_format($campaigns_comission_reduction, 2, ",", ".");
            $refund                         = number_format($refund, 2, ",", ".");

            $status = $this->CI->model_iugu->statuspedido($value['paid_status']);
            $observacao = "";

            if ($value['observacao'] <> "")
                $observacao .= $value['observacao'];

            if ($value['numero_chamado'] <> "") {
                $observacao .= ' <br>Aberto o chamado ' . $value['numero_chamado'] . ' junto ao marketplace ' . $value['marketplace'] . ' para esclarecimento do pedido';

                if ($value['previsao_solucao'] <> "" && $value['previsao_solucao'] <> "00/00/0000")
                    $observacao .= ', com previsao de retorno em ' . $value['previsao_solucao'];
            }

            $valoPedido = "";

            if ($value['gross_amount'] <> '-')
                $valoPedido = number_format($value['gross_amount'], 2, ",", ".");
            else
                $valoPedido = $value['gross_amount'];

            $valorProduto = "";

            if ($value['total_order'] <> '-')
                $valorProduto = number_format($value['total_order'], 2, ",", ".");
            else
                $valorProduto = $value['total_order'];

            $valorFrete = "";

            if ($value['total_ship'] <> '-' && $value['total_ship'] <> '')
                $valorFrete = number_format($value['total_ship'], 2, ",", ".");
            else
                $valorFrete = $value['total_ship'];

            $expectativaReceb = number_format($valor_repasse, 2, ",", ".");

            $valor_parceiro = "";

            if ($value['valor_parceiro'] <> '-')
                $valor_parceiro = number_format($value['valor_parceiro'], 2, ",", ".");
            else
                $valor_parceiro = $value['valor_parceiro'];

            $comissao_descontada = "";

            if ($value['comissao_descontada'] <> '-')
                $comissao_descontada = number_format($value['comissao_descontada'], 2, ",", ".");
            else
                $comissao_descontada = $value['comissao_descontada'];

            $calc_comissao_descontada = "";

            if ($value['calc_comissao_descontada'] <> '-')
                $calc_comissao_descontada = number_format($value['calc_comissao_descontada'], 2, ",", ".");
            else
                $calc_comissao_descontada = $value['calc_comissao_descontada'];

            $imposto_renda_comissao_descontada = "";

            if ($value['imposto_renda_comissao_descontada'] <> '-')
                $imposto_renda_comissao_descontada = number_format($value['imposto_renda_comissao_descontada'], 2, ",", ".");
            else
                $imposto_renda_comissao_descontada = $value['imposto_renda_comissao_descontada'];

            $total_comissao_descontada = number_format($valor_comissao, 2, ",", ".");;

            $percentualFrete = "";

            if ($value['percentual_frete'] <> '-')
                $percentualFrete = number_format($value['percentual_frete'], 2, ",", ".") . '%';
            else
                $percentualFrete = $value['percentual_frete'] . '%';

            $percentualProduto = "";

            if ($value['percentual_produto'] <> '-')
                $percentualProduto = number_format($value['percentual_produto'], 2, ",", ".") . '%';
            else
                $percentualProduto = $value['percentual_produto'] . '%';

            $transferencia_gateway_extrato = $this->CI->model_iugu->getDataTransferencia($value['id']);;
            $pagamento_gateway_extrato = $this->CI->model_iugu->getValorTransferencia($value['id']);;

            array_push($response, array(
                "order_id"                            => $value['id'],
                "marketplace"                         => $value['marketplace'],
                "numero_pedido"                       => $value['numero_pedido'],
                "status"                              => $status,
                "data_criacao"                        => $value['date_time'],
                "data_entrega"                        => $value['data_entrega'],
                "data_pagamento_mktplace"             => $value['data_pagamento_mktplace'],
                "data_pagamento_conectala"            => $value['data_pagamento_conectala'],
                "data_recebimento_marketplace"        => $value['data_recebimento_mktpl'],
                "data_caiu_na_conta"                  => $value['data_caiu_na_conta'],
                "pago"                                => $value['pago'],
                "data_transferencia"                  => $value['data_transferencia'],
                "valor_pedido"                        => "R$ " . $valoPedido . "",
                "valor_produto"                       => "R$ " . $valorProduto . "",
                "valor_frete"                         => "R$ " . $valorFrete . "",
                "expectativa_recebimento"             => "R$ " . $expectativaReceb . "",
                "valor_parceiro"                      => "R$ " . $valor_parceiro . "",
                "observacao"                          => $observacao,
                "loja"                                => $value['nome_loja'],
                "comissao_descontada"                 => "R$ " . $comissao_descontada . "",
                "razao_social"                        => $value['raz_social'],
                "erp_customer_supplier_code"          => $value['erp_customer_supplier_code'],
                "store_id"                            => $value['id_loja'],
                "percentual_frete"                    => $percentualFrete,
                "calc_comissao_descontada"            => "R$ " . $calc_comissao_descontada . "",
                "imposto_renda_comissao_descontada"   => "R$ " . $imposto_renda_comissao_descontada . "",
                "total_comissao_descontada"           => "R$ " . $total_comissao_descontada . "",
                "percentual_produto"                   => $percentualProduto,
                "campaigns_pricetags"                 => "R$ " . $campaigns_pricetags . "",
                "campaigns_campaigns"                 => "R$ " . $campaigns_campaigns . "",
                "campaigns_mktplace"                  => "R$ " . $campaigns_mktplace . "",
                "campaigns_seller"                    => "R$ " . $campaigns_seller . "",
                "campaigns_promotions"                => "R$ " . $campaigns_promotions . "",
                "campaigns_comission_reduction"       => "R$ " . $campaigns_comission_reduction . "",
                "campaigns_rebate"                    => "R$ " . $campaigns_rebate . "",
                "refund"                              => "R$ " . $refund . "",
            ));
        }

        ob_end_clean();

        $painel = $this->getValidPainelToShow($inputs);
        $this->{$painel}($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato);

        if ($this->source == self::API) {
            return $this->responseApi($this->responseApi, $post);
        }

    }

    private function responseApi($response, $post)
  {

    $totalCurrentPage = count($response);

    $item_per_page = $post['length'] ?? $totalCurrentPage;

      return [
        'page' => $post['page'] ?? 1,
        'items_per_page' => $item_per_page,
        'total_count' => intval($this->total_count),
        'pages_count' => ceil($this->total_count/$item_per_page),
        'total_current_page' => $totalCurrentPage,
        'data' => $response
      ];
  }

  private function getValidPainelToShow($inputs)
  {
    if (isset($this->painelGrupoSoma['status']) && $this->painelGrupoSoma['status'] == "1") {
      return "tabelaGrupoSoma";
    } else
    if (isset($this->painelNovoMundo['status']) && $this->painelNovoMundo['status'] == "1") {
      return "tabelaNovoMundo";
    } else if (isset($this->painelOrtobom['status']) && $this->painelOrtobom['status'] == "1") {
      return "tabelaOrtobom";
    } else if ($this->painelSellerCenter['status'] == "1") {
      return "tabelaSellerCenter";
    } else {
      if (array_key_exists("extratonovo", $inputs)) {
        return "tabelaExtratoNovo";
      } else {
        return "tabelaNaoExtratoNovo";
      }
    }
  }

  private function tabelaExtratoNovo($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato)
  {
    $this->openTag("table", ["border" => "1"]);
    $this->openTag("tr");
    $this->openTag("th", ["colspan" => "7"], "", true);
    $this->openTag("th", ["colspan" => "2"], "REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)", true);
    $this->openTag("th", ["colspan" => "2"], "PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)", true);
    $this->openTag("th", ["colspan" => "5"], "", true);
    $this->closeTag("tr");
    $this->openTag("tr");
    $this->openTag("th", [], $this->CI->lang->line('application_id') . " - Pedido", true);
    $this->openTag("th", [], $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_marketplace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_id'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_status'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " Pedido", true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " de Entrega", true);
    $this->openTag("th", [], "Data que recebemos o repasse", true);
    $this->openTag("th", [], "Data em que pagamos", true);
    $this->openTag("th", [], $this->CI->lang->line('application_order_2'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " " . $this->CI->lang->line('application_bank_transfer'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_total'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_value_products'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_ship_value'), true);
    $this->openTag("th", [], "Valor pago pelo Marketplace", true);
    $this->openTag("th", [], $this->CI->lang->line('application_extract_obs'), true);
    $this->closeTag("tr");

    foreach ($response as $value) {
      $this->value = $value;
      $this->drawRow(true);
      $this->buildResponse('order_id');
      $this->buildResponse('loja');
      $this->buildResponse('marketplace');
      $this->buildResponse('numero_pedido');
      $this->buildResponse('status');
      $this->buildResponse('data_criacao');
      $this->buildResponse('data_entrega');
      $this->buildResponse('data_recebimento_marketplace');
      $this->buildResponse('data_caiu_na_conta');
      $this->buildResponse('pago');
      $this->buildResponse('data_transferencia');
      $this->buildResponse('valor_pedido', true);
      $this->buildResponse('valor_produto', true);
      $this->buildResponse('valor_frete', true);
      $this->buildResponse('valor_parceiro', true);
      $this->buildResponse('observacao');
      $this->resetArray();
    }

    $this->closeTag("table");
  }

  private function tabelaNaoExtratoNovo($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato)
  {
    $this->openTag("table", ["border" => "1"]);
    $this->openTag("tr");
    $this->openTag("th", ["colspan" => "7"], "", true);
    $this->openTag("th", ["colspan" => "2"], "PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)", true);
    $this->openTag("th", ["colspan" => "2"], "REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)", true);
    $this->openTag("th", ["colspan" => "2"], "PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)", true);
    $this->openTag("th", ["colspan" => "5"], "", true);
    $this->openTag("th", ["colspan" => "8"], $this->CI->lang->line('conciliation_sc_gridok_campaigns'), true);
    $this->closeTag("tr");
    $this->openTag("tr");
    $this->openTag("th", [], $this->CI->lang->line('application_id') . " - Pedido", true);
    $this->openTag("th", [], $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_marketplace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_id'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_status'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " Pedido", true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " de Entrega", true);
    $this->openTag("th", [], $this->CI->lang->line('application_payment_date') . " Marketplace", true);
    $this->openTag("th", [], $this->CI->lang->line('application_payment_date_conecta'), true);
    $this->openTag("th", [], "Data que recebemos o repasse", true);
    $this->openTag("th", [], "Data em que pagamos", true);
    $this->openTag("th", [], $this->CI->lang->line('application_order_2'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " " . $this->CI->lang->line('application_bank_transfer'), true);
    if ($this->data_transferencia_gateway_extrato == "1") {
      $this->openTag("th", [], "Data Transferência Bancária Gateway", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_total'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_value_products'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_ship_value'), true);
    $this->openTag("th", [], "Expectativa Recebimento", true);
    $this->openTag("th", [], "Valor pago pelo Marketplace", true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_pricetags'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_campaigns'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_marketplace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_seller'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_promotions'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_comission_reduction'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_rebate'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_refund'), true);
    if ($this->valor_pagamento_gateway_extrato == "1") {
      $this->openTag("th", [], "Valor Real Repasse", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_extract_obs'), true);
    $this->closeTag("tr");

    foreach ($response as $value) {
      $this->value = $value;
      $this->drawRow(true);
      $this->buildResponse('order_id');
      $this->buildResponse('loja');
      $this->buildResponse('marketplace');
      $this->buildResponse('numero_pedido');
      $this->buildResponse('status');
      $this->buildResponse('data_criacao');
      $this->buildResponse('data_entrega');
      $this->buildResponse('data_pagamento_mktplace');
      $this->buildResponse('data_pagamento_conectala');
      $this->buildResponse('data_recebimento_marketplace');
      $this->buildResponse('data_caiu_na_conta');
      $this->buildResponse('pago');
      $this->buildResponse('data_transferencia');
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($transferencia_gateway_extrato);
      }
      $this->buildResponse('valor_pedido', true);
      $this->buildResponse('valor_produto', true);
      $this->buildResponse('valor_frete', true);
      $this->buildResponse('expectativa_recebimento', true);
      $this->buildResponse('valor_parceiro', true);
      $this->buildResponse('campaigns_pricetags', true);
      $this->buildResponse('campaigns_campaigns', true);
      $this->buildResponse('campaigns_mktplace', true);
      $this->buildResponse('campaigns_seller', true);
      $this->buildResponse('campaigns_promotions', true);
      $this->buildResponse('campaigns_comission_reduction', true);
      $this->buildResponse('campaigns_rebate', true);
      $this->buildResponse('refund', true);
      if ($this->valor_pagamento_gateway_extrato == "1") {
        $this->buildResponse($pagamento_gateway_extrato, true);
      }
      $this->buildResponse('observacao');
      $this->resetArray();
    }

    $this->closeTag("table");
  }

  private function tabelaSellerCenter($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato)
  {

    $this->openTag("table", ["border" => "1"]);
    $this->openTag("tr");
    $this->openTag("th", [], $this->CI->lang->line('application_quotation_id') . " - " . $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_numero_marketlace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_category'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_status'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " " . $this->CI->lang->line('application_order'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " de Entrega", true);
    $this->openTag("th", [], $this->CI->lang->line('application_payment_date'), true);
    if ($this->data_transferencia_gateway_extrato == "1") {
      $this->openTag("th", [], "Data Transferência Bancária Gateway", true);
    }
    if ($this->dataRepasseSellerCenter['status'] == "1") {
      $this->openTag("th", [], $this->CI->lang->line('application_date') . " " . $this->CI->lang->line('application_bank_transfer'), true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_Paid'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_parameter_mktplace_value_ciclo'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_total'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_value_products'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_service_value'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_ship_value'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_charge_amount'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_service_charge_amount'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_charge_amount_freight'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_total') . " " . $this->CI->lang->line('application_commission'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_ir_value'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_total_to_transfer'), true);
    if ($this->valorRealRepasseSellerCenter['status'] == "1") {
      $this->openTag("th", [], $this->CI->lang->line('application_total_to_transfer') . " Real", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_pricetags'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_campaigns'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_marketplace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_seller'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_promotions'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_comission_reduction'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_rebate'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_refund'), true);
    if ($this->valor_pagamento_gateway_extrato == "1") {
      $this->openTag("th", [], "Valor Real Repasse", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_extract_obs'), true);
    $this->closeTag("tr");

    foreach ($response as $value) {
      $this->value = $value;
      $this->drawRow(true);
      $this->buildResponse('store_id');
      $this->buildResponse('loja');
      $this->buildResponse('numero_pedido');
      $this->buildResponse("");
      $this->buildResponse('status');
      $this->buildResponse('data_criacao');
      $this->buildResponse('data_entrega');
      $this->buildResponse('data_pagamento_mktplace');
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($transferencia_gateway_extrato, true);
      }
      if ($this->dataRepasseSellerCenter['status'] == "1") {
        $this->buildResponse('data_transferencia');
      }
      $this->buildResponse('pago');
      $this->buildResponse("");
      $this->buildResponse('valor_pedido');
      $this->buildResponse('valor_produto');
      $this->buildResponse("");
      $this->buildResponse('valor_frete');
      $this->buildResponse('percentual_produto');
      $this->buildResponse("");
      $this->buildResponse('percentual_frete');
      $this->buildResponse('calc_comissao_descontada', true);
      $this->buildResponse('imposto_renda_comissao_descontada', true);
      $this->buildResponse('expectativa_recebimento', true);
      if ($this->valorRealRepasseSellerCenter['status'] == "1") {
        $this->buildResponse('valor_parceiro', true);
      }
      $this->buildResponse('campaigns_pricetags', true);
      $this->buildResponse('campaigns_campaigns', true);
      $this->buildResponse('campaigns_mktplace', true);
      $this->buildResponse('campaigns_seller', true);
      $this->buildResponse('campaigns_promotions', true);
      $this->buildResponse('campaigns_comission_reduction', true);
      $this->buildResponse('campaigns_rebate', true);
      $this->buildResponse('refund', true);
      if ($this->valor_pagamento_gateway_extrato == "1") {
        $this->buildResponse($pagamento_gateway_extrato);
      }
      $this->buildResponse('observacao');
      $this->resetArray();
    }

    $this->closeTag("table");
  }

  private function tabelaOrtobom($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato)
  {
    $this->openTag("table", ["border" => "1"]);
    $this->openTag("tr");
    $this->openTag("th", [], $this->CI->lang->line('application_quotation_id') . " - " . $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], "Pedido Seller", true);
    $this->openTag("th", [], $this->CI->lang->line('application_category'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_status'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " " . $this->CI->lang->line('application_order'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_payment_date'), true);
    if ($this->data_transferencia_gateway_extrato == "1") {
      $this->openTag("th", [], "Data Transferência Bancária Gateway", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_Paid'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_parameter_mktplace_value_ciclo'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_total'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_value_products'), true);
    $this->openTag("th", [], "Valor Serviço", true);
    $this->openTag("th", [], $this->CI->lang->line('application_ship_value'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_charge_amount'), true);
    $this->openTag("th", [], "Comissão sobre o Serviço (%)", true);
    $this->openTag("th", [], $this->CI->lang->line('application_charge_amount_freight'), true);
    $this->openTag("th", [],  $this->CI->lang->line('application_total') . " " . $this->CI->lang->line('application_commission'), true);
    $this->openTag("th", [], "Imposto de Renda", true);
    $this->openTag("th", [], "Total do repasse", true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_pricetags'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_campaigns'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_marketplace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_seller'), true);

    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_promotions'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_comission_reduction'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_rebate'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_refund'), true);
    if ($this->valor_pagamento_gateway_extrato == "1") {
      $this->openTag("th", [], "Valor Real Repasse", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_extract_obs'), true);
    $this->closeTag("tr");

    foreach ($response as $value) {
      $this->value = $value;
      $this->drawRow(true);
      $this->buildResponse('store_id');
      $this->buildResponse('loja');
      $this->buildResponse('numero_pedido');
      $this->buildResponse("");
      $this->buildResponse('status');
      $this->buildResponse('data_criacao');
      $this->buildResponse('data_entrega');
      $this->buildResponse('data_pagamento_mktplace');
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($transferencia_gateway_extrato);
      }
      $this->buildResponse('pago');
      $this->buildResponse("");
      $this->buildResponse('valor_pedido');
      $this->buildResponse('valor_produto');
      $this->buildResponse("");
      $this->buildResponse('valor_frete', true);
      $this->buildResponse('percentual_produto');
      $this->buildResponse("");
      $this->buildResponse('percentual_frete');
      $this->buildResponse('calc_comissao_descontada', true);
      $this->buildResponse('imposto_renda_comissao_descontada', true);
      $this->buildResponse('expectativa_recebimento', true);
      $this->buildResponse('campaigns_pricetags', true);
      $this->buildResponse('campaigns_campaigns', true);
      $this->buildResponse('campaigns_mktplace', true);
      $this->buildResponse('campaigns_seller', true);
      $this->buildResponse('campaigns_promotions', true);
      $this->buildResponse('campaigns_comission_reduction', true);
      $this->buildResponse('campaigns_rebate', true);
      $this->buildResponse('refund', true);
      if ($this->valor_pagamento_gateway_extrato == "1") {
        $this->buildResponse($pagamento_gateway_extrato, true);
      }
      $this->buildResponse('observacao');
      $this->resetArray();
    }

    $this->closeTag("table");
  }

  private function tabelaGrupoSoma($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato)
  {

    $this->openTag("table", ["border" => "1"]);
    $this->openTag("tr");
    $this->openTag("th", ["colspan" => "8"], "", true);
    $this->openTag("th", ["colspan" => "1"], "PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)", true);
    $this->openTag("th", ["colspan" => "1"], "PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)", true);
    $this->openTag("th", ["colspan" => "7"], "", true);
    $this->closeTag("tr");
    $this->openTag("tr");
    $this->openTag("th", [], $this->CI->lang->line('application_id') . " - Pedido", true);
    $this->openTag("th", [], "Marca", true);
    $this->openTag("th", [], "Clifor", true);
    $this->openTag("th", [], $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], "Razão Social", true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_id'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_status'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " Pedido", true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " de Entrega", true);
    $this->openTag("th", [], $this->CI->lang->line('application_payment_date')  . " Marca", true);
    if ($this->data_transferencia_gateway_extrato == "1") {
      $this->openTag("th", [], "Data Transferência Bancária Gateway", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_order_2'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_total'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_value_products'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_ship_value'), true);
    $this->openTag("th", [], "Expectativa Recebimento", true);
    $this->openTag("th", [], "Comissão Descontada", true);
    if ($this->valor_pagamento_gateway_extrato == "1") {
      $this->openTag("th", [], "Valor Real Repasse", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_extract_obs'), true);
    $this->closeTag("tr");


    foreach ($response as $value) {
      $this->value = $value;
      $this->drawRow(true);
      $this->buildResponse('order_id');
      $this->buildResponse('marketplace');
      $this->buildResponse('erp_customer_supplier_code');
      $this->buildResponse('loja');
      $this->buildResponse('razao_social');
      $this->buildResponse('numero_pedido');
      $this->buildResponse('status');
      $this->buildResponse('data_criacao');
      $this->buildResponse('data_entrega');
      $this->buildResponse('data_pagamento_mktplace');
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($transferencia_gateway_extrato);
      }
      $this->buildResponse('pago');
      $this->buildResponse('valor_pedido', true);
      $this->buildResponse('valor_produto', true);
      $this->buildResponse('valor_frete', true);
      $this->buildResponse('expectativa_recebimento', true);
      $this->buildResponse('comissao_descontada', true);
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($pagamento_gateway_extrato);
      }
      $this->buildResponse('observacao');
      $this->resetArray();
    }
    $this->closeTag("table");
  }

  private function tabelaNovoMundo($response, $status, $transferencia_gateway_extrato, $pagamento_gateway_extrato)
  {
    $this->openTag("table", ["border" => "1"]);
    $this->openTag("tr");
    $this->openTag("th", [], $this->CI->lang->line('application_quotation_id') . " - " . $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_store'), true);
    $this->openTag("th", [], "Pedido Novo Mundo", true);
    $this->openTag("th", [], "Pedido Seller", true);
    $this->openTag("th", [], $this->CI->lang->line('application_category'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_status'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " " . $this->CI->lang->line('application_order'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_date') . " de Entrega", true);
    $this->openTag("th", [], $this->CI->lang->line('application_payment_date'), true);
    if ($this->data_transferencia_gateway_extrato == "1") {
      $this->openTag("th", [], "Data Transferência Bancária Gateway", true);
    }
    $this->openTag("th", [],  $this->CI->lang->line('application_Paid'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_parameter_mktplace_value_ciclo'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_purchase_total'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_value_products'), true);
    $this->openTag("th", [], "Valor Serviço", true);
    $this->openTag("th", [], $this->CI->lang->line('application_ship_value'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_charge_amount'), true);
    $this->openTag("th", [], "Comissão sobre o Serviço (%)", true);
    $this->openTag("th", [], $this->CI->lang->line('application_charge_amount_freight'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_total') . " " . $this->CI->lang->line('application_commission'), true);
    $this->openTag("th", [], "Imposto de Renda", true);
    $this->openTag("th", [], "Total do repasse", true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_pricetags'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_campaigns'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_marketplace'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_seller'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_promotions'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_comission_reduction'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_rebate'), true);
    $this->openTag("th", [], $this->CI->lang->line('application_campaigns_refund'), true);
    if ($this->valor_pagamento_gateway_extrato == "1") {
      $this->openTag("th", [], "Valor Real Repasse", true);
    }
    $this->openTag("th", [], $this->CI->lang->line('application_extract_obs'), true);
    $this->closeTag("tr");

    foreach ($response as $value) {
      $this->value = $value;
      $this->drawRow(true);
      $this->buildResponse('store_id');
      $this->buildResponse('loja');
      $this->buildResponse("");
      $this->buildResponse('numero_pedido');
      $this->buildResponse("");
      $this->buildResponse('status');
      $this->buildResponse('data_criacao');
      $this->buildResponse('data_entrega');
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($transferencia_gateway_extrato);
      }
      $this->buildResponse('data_pagamento_mktplace');
      $this->buildResponse('pago');
      $this->buildResponse("");
      $this->buildResponse('valor_pedido');
      $this->buildResponse('valor_produto');
      $this->buildResponse("");
      $this->buildResponse('valor_frete');
      $this->buildResponse('percentual_produto');
      $this->buildResponse("");
      $this->buildResponse('percentual_frete');
      $this->buildResponse('calc_comissao_descontada');
      $this->buildResponse('imposto_renda_comissao_descontada');
      $this->buildResponse('total_comissao_descontada');
      $this->buildResponse('campaigns_pricetags');
      $this->buildResponse('campaigns_campaigns');
      $this->buildResponse('campaigns_mktplace');
      $this->buildResponse('campaigns_seller');
      $this->buildResponse('campaigns_promotions');
      $this->buildResponse('campaigns_comission_reduction');
      $this->buildResponse('campaigns_rebate');
      $this->buildResponse('refund');
      if ($this->data_transferencia_gateway_extrato == "1") {
        $this->buildResponse($pagamento_gateway_extrato);
      }
      $this->buildResponse('observacao');
      $this->resetArray();
    }
    $this->closeTag("table");
  }

  private function setHeader()
  {
    header("Pragma: public");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: pre-check=0, post-check=0, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Content-Transfer-Encoding: none");
    header("Content-Type: application/vnd.ms-excel;");
    header("Content-type: application/x-msexcel;");

    if ($this->painelNovoMundo['status'] == "1")
      header("Content-Disposition: attachment; filename=Extrato - Novo Mundo.xls");
    else
      header("Content-Disposition: attachment; filename=Extrato.xls");
  }
}
