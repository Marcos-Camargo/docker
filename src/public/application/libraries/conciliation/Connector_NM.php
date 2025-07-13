<?php /** @noinspection DuplicatedCode */

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . "libraries/ConciliationConnectors.php";

class Connector_NM extends ConciliationConnectors
{
    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_billet');
    }

    function conciliation_connector_nm($inputs = null, $file = null)
    {
        if (!$inputs || !$file)
            return false;

        if (is_file($file))
        {	        
	        if ($inputs['hdnExtensao'] == "csv")
            {
                $connection_array = [];
	            $arquivo = fopen($file, "r");
	            $row = 1;  

	            while (($data = fgetcsv($arquivo, 10000, ";", "'", "\n")) !== FALSE)
                {	            
	                if ($row == "1")
                    {
	                    $count = 1; 
                        $cabecalho[1] = "id_transf";
                        $cabecalho[2] = "operacao";
                        $cabecalho[3] = "cod_transf";
                        $cabecalho[4] = "id_pedido_nm";
                        $cabecalho[5] = "id_parc";
                        $cabecalho[6] = "id_fornecedor";
                        $cabecalho[7] = "seller";
                        $cabecalho[8] = "sequence";
                        $cabecalho[9] = "data_emissao";
                        $cabecalho[10] = "data_entrega";
                        $cabecalho[11] = "orderid";
                        $cabecalho[12] = "cpf_cnpj";
                        $cabecalho[13] = "nome";
                        $cabecalho[14] = "situacao";
                        $cabecalho[15] = "total_pedido";
                        $cabecalho[16] = "valor_comissao";
                        $cabecalho[17] = "valor_repasse";
                        $cabecalho[18] = "valor_ir";
                        $cabecalho[19] = "total";

                        foreach ($data as $colunas)
                        {
                            if (str_replace( "\"", "", utf8_encode($colunas) ) <> $cabecalho[$count])
                            {
                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode($colunas)." esperado ".$cabecalho[$count];
                                die;
                            }

                            $count++;
                        }
                        
                        $row++;
	                }
                    else if ($row > 1)
                    {
                        //ao inves de salvar na tabela vai começar a criar o array de entrega ao metodo padrao
                        //deve haver conversao limpeza e recalculo pois este sera o array  padrao
                        // $save = $this->_CI->model_billet->neo_salvarArquivoNMTable($inputs, $data);

                        // $teste = $data;
                        // $teste2 = str_replace( "\"", "", utf8_encode($data[0]));

                        $connection_array[] = array
                        (
                            'lot' => $inputs['hdnLote'],
                            // 'date_creation' => ,
                            'filename' => $inputs['arquivo'],
                            'id_transf' => str_replace( "\"", "", utf8_encode($data[0])),
                            'operation' => str_replace( "\"", "", utf8_encode($data[1])),
                            'cod_transf' => str_replace( "\"", "", utf8_encode($data[2])),
                            'id_order' => str_replace( "\"", "", utf8_encode($data[3])),
                            'id_parc' => str_replace( "\"", "", utf8_encode($data[4])),
                            'id_provider' => str_replace( "\"", "", utf8_encode($data[5])),
                            // 'store_id' => ,
                            'seller' => str_replace( "\"", "", utf8_encode($data[6])),
                            'sequence' => str_replace( "\"", "", utf8_encode($data[7])),
                            'date_emission' => str_replace( "\"", "", utf8_encode($data[8])),
                            'date_delivery' => str_replace( "\"", "", utf8_encode($data[9])),
                            'orderid' => str_replace( "\"", "", utf8_encode($data[10])),
                            'cpf_cnpj' => str_replace( "\"", "", utf8_encode($data[11])),
                            'name' => str_replace( "\"", "", utf8_encode($data[12])),
                            'situation' => str_replace( "\"", "", utf8_encode($data[13])),
                            'total_order' => str_replace( "\"", "", utf8_encode($data[14])),
                            'comission_value' => str_replace( "\"", "", utf8_encode($data[15])),
                            'transfer_value' => str_replace( "\"", "", utf8_encode($data[16])),
                            'ir_value' => str_replace( "\"", "", utf8_encode($data[17])),
                            'total' => str_replace( "\"", "", utf8_encode($data[18])),
                            // 'status_conciliation' => ,
                            // 'status_conciliation_new' => ,
                            // 'order_value' => ,
                            // 'shipping_type' => ,
                            // 'shipping_value' => ,
                            // 'shipping_value_actual' => ,
                            // 'shipping_value_actual_contracted' => ,
                            // 'calculated_product_value' => ,
                            // 'partner_product_value' => ,
                            // 'partner_shipping_value' => ,                            
                            // 'partner_value' => ,
                            // 'partner_value_new' => ,
                            // 'partner_value_adjusted' => ,
                            // 'partner_percentual_value' => ,
                            // 'calculated_shipping_value' => ,
                            // 'received_product_value' => ,
                            // 'received_shipping_value' => ,
                            // 'dif_received_value' => ,
                            // 'dif_received_product_value' => ,
                            // 'dif_received_shipping_value' => ,
                            // 'calculated_revenue_value' => ,
                            // 'conecta_product_value' => ,
                            // 'conecta_product_value_adjusted' => ,
                            // 'conecta_shipping_value' => ,
                            // 'conecta_shipping_value_adjusted' => ,
                            // 'conecta_value' => ,
                            // 'conecta_value_actual' => ,
                            // 'conecta_value_adjusted' => ,
                            // 'product_value' => ,
                            // 'marketplace_value' => ,
                            // 'marketplace_value_adjusted' => ,
                            // 'marketplace_percentual_value' => ,
                            // 'agency_value' => ,
                            // 'autonomous_value' => ,
                            // 'affiliate_value' => ,
                            // 'user' => ,
                            // 'seller_name' => ,
                            // 'fixed' => ,
                            // 'order_sent' => ,
                            // 'camp_promo_discount_value' => 
                        );
                    }
                }

                return $connection_array;
                //se chegou aqui é pq todos as colunas do arquivo estao corretadas como esperado
                //o proximo looping populara o array

                


                        

                
	            fclose($arquivo);

                exit;





	            
                //Trata a conciliação
                $save2 = $this->_CI->model_billet->neo_conciliaarquivoNM($inputs);

                if ($save2)
                {
                    //Atualiza os valores divididos nas tabelas
                    $save3 = $this->_CI->model_billet->atualizavaloresconciliadivisaoNM($inputs);

                    if ($save3)
                    {
                        echo "Feito com sucesso!";
                        die;
                    }
                    else
                    {
                        echo "Erro ao conciliar arquivo carregado";
                        die;
                    }
                }
                else
                {
                    echo "Erro ao conciliar arquivo carregado";
                    die;
                }
            }
	    }
        else
        {
	        echo "Arquivo não encontrado";
	    }
    }
    

    /* function conciliation_connector_nm($inputs = null, $file = null)
    {
        if (!$inputs || !$file)
            return false;

        if (is_file($file))
        {	        
	        if ($inputs['hdnExtensao'] == "csv")
            {
	            $arquivo = fopen($file, "r");
	            $row = 1;  

	            while (($data = fgetcsv($arquivo, 10000, ";", "'", "\n")) !== FALSE)
                {	            
	                if ($row == "1")
                    {
	                    $count = 1; 
                        $cabecalho[1] = "id_transf";
                        $cabecalho[2] = "operacao";
                        $cabecalho[3] = "cod_transf";
                        $cabecalho[4] = "id_pedido_nm";
                        $cabecalho[5] = "id_parc";
                        $cabecalho[6] = "id_fornecedor";
                        $cabecalho[7] = "seller";
                        $cabecalho[8] = "sequence";
                        $cabecalho[9] = "data_emissao";
                        $cabecalho[10] = "data_entrega";
                        $cabecalho[11] = "orderid";
                        $cabecalho[12] = "cpf_cnpj";
                        $cabecalho[13] = "nome";
                        $cabecalho[14] = "situacao";
                        $cabecalho[15] = "total_pedido";
                        $cabecalho[16] = "valor_comissao";
                        $cabecalho[17] = "valor_repasse";
                        $cabecalho[18] = "valor_ir";
                        $cabecalho[19] = "total";

                        foreach ($data as $colunas)
                        {
                            if (str_replace( "\"", "", utf8_encode($colunas) ) <> $cabecalho[$count])
                            {
                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode($colunas)." esperado ".$cabecalho[$count];
                                die;
                            }

                            $count++;
                        }
	                }

	                if ($row > 1)
                    {
                        $save = $this->_CI->model_billet->neo_salvarArquivoNMTable($inputs, $data);

                        if ($save == false)
                        {
                            echo "Erro ao subir na tabela";
                            die;
                        }
                    }

	                $row++;
	            }
                
	            fclose($arquivo);
	            
                //Trata a conciliação
                $save2 = $this->_CI->model_billet->neo_conciliaarquivoNM($inputs);

                if ($save2)
                {
                    //Atualiza os valores divididos nas tabelas
                    $save3 = $this->_CI->model_billet->atualizavaloresconciliadivisaoNM($inputs);

                    if ($save3)
                    {
                        echo "Feito com sucesso!";
                        die;
                    }
                    else
                    {
                        echo "Erro ao conciliar arquivo carregado";
                        die;
                    }
                }
                else
                {
                    echo "Erro ao conciliar arquivo carregado";
                    die;
                }
            }
	    }
        else
        {
	        echo "Arquivo não encontrado";
	    }
    } */
}