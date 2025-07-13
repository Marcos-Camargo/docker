<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

include "./system/libraries/Vendor/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if (!function_exists('printTag')) {
     function printTag($orders, string $format, $identificador)
    {

        $CI = get_instance();
        $CI->load->model('model_company'); 

        $outputFiles = [];
        $format = strtolower($format);
        $idCliente = $identificador;
    
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $companySellerCenter = $CI->model_company->getCompanyData(1);
        $logoSellerCenter = $companySellerCenter['logo'];

        if($format === "pdf") {

            $html = '<html>
                        <head>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1, maximum-scale=1, viewport-fit=cover, shrink-to-fit=no">
                            <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
                            <title>Etiquetas</title>
                            <style>
                        @page { margin: 1cm; }
                            </style>
                        </head>
                        <body>
                            <table cellpadding="0" cellspacing="0" style="width: 100%">';
            $key = 0;

   
            foreach ($orders as $order) {
                
             
                $CI->load->model('model_orders');
                $CI->load->model('model_stores');
                $CI->load->model('model_nfes');
                $CI->load->model('model_freights');
                
                $dataOrder  =  $CI->model_orders->getOrdersData(0, $order['id']);
                $store      =  $CI->model_stores->getStoresData($dataOrder['store_id']); 
                $nfe        =  $CI->model_nfes->getNfesDataByOrderId($order['id'], true); 
                $freights   =  $CI->model_freights->getFreightsDataByOrderId($order['id']); 

                if (!$dataOrder || !$store || !$nfe || !$freights)
                continue;

                foreach ($freights as $freight) {

                    $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
                    $barCode = $generator->getBarcode(
                        !empty($freight['shipping_order_id']) ? $freight['shipping_order_id'] : $freight['codigo_rastreio'],
                        $generator::TYPE_CODE_128,
                        1,
                        40
                    );

                    $codeTagView = $freight['codigo_rastreio'];
                    if (strtolower($freight['ship_company']) == 'sequoia') {
                        $codeTagView = $freight['shipping_order_id'];
                    }

                    $html .= $key % 2 == 0 ? '<tr>' : '';
                    $col = count($orders) == 1 && count($freights) == 1 ? "40 % " : "85 % ";
                    $html .= '<td style="width: 50%;padding-bottom: 35px;">
                        <table cellpadding="0" cellspacing="0" style="width: ' . $col . '">
                            <tr>
                                <td>
                                    <table cellpadding="0" cellspacing="0" style="width: 100%;border: 1px solid #000 !important;">
                                        <tr>
                                            <td style="font-size: 11px;width:100%; padding-top: 20px; padding-left: 40%; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Nota Fiscal: ' . $nfe[0]['nfe_num'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;width:100%; padding-top: 15px; padding-left: 40%; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Pedido: ' . $dataOrder['numero_marketplace'] . '</td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="text-weight:bold;text-align:center;font-size: 13px;padding-top: 25px;padding-bottom: 5px; padding-left: 5px; text-transform: uppercase; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif"><strong>' . explode(' ', $freight['ship_company'])[0] . '</strong></td>
                                        </tr>
                                        <tr>
                                            <td style="direction: rtl;font-size: 18px;padding-top: 20px;padding-bottom: 15px; padding-left: 5px; text-align:center; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $codeTagView . '</td>
                                        </tr>
                                        <tr>
                                            <td style="direction: rtl;font-size: 18px;padding-top: 5px;padding-bottom: 5px; padding-left: 5px; text-align:center; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $barCode . '</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <img style="text-align: right;padding-left: 5px;margin-top: -145px;position: relative;top:3;left:5" src="' . base_url($logoSellerCenter) . '" width="100px" height="40px">
                                            </td>
                                        </tr>
                                    </table>
                                    <table cellpadding="0" cellspacing="0" style="width: 100%;border: 1px solid #000 !important">
                                        <tr>
                                            <td style="font-size: 14px !important;padding: 20px 0px 15px 15px !important;background-color: #000; color: #fff;width: 100%;font-weight: bold; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Destinatário</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 17px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $dataOrder['customer_name'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $dataOrder['customer_address'] . ', ' . $dataOrder['customer_address_num'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="overflow: hidden; max-width: 100%; font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $dataOrder['customer_address_compl'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="overflow: hidden; max-width: 100%; font-size: 11px;padding-top: 18px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $dataOrder['customer_address_zip'] . ' - ' . $dataOrder['customer_address_neigh'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="overflow: hidden; max-width: 100%; font-size: 11px;padding-top: 18px; padding-bottom: 10px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $dataOrder['customer_address_city'] . ' - ' . $dataOrder['customer_address_uf'] . '</td>
                                        </tr>
                                    </table>
                                    <table cellpadding="0" cellspacing="0" style="width: 100%;border: 1px solid #000 !important;">
                                        <tr>
                                            <td style="font-size: 14px !important;padding: 20px 0px 15px 15px !important;background-color: #000; color: #fff;width: 100%;font-weight: bold; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Remetente</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 17px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $store['name'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $store['raz_social'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $store['address'] . ', ' . $store['addr_num'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $store['addr_compl'] . ' ' . $store['addr_neigh'] . '</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size: 11px;padding-top: 16px; padding-bottom: 10px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $store['zipcode'] . ' ' . $store['addr_city'] . ' - ' . $store['addr_uf'] . '</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>';
                    $html .= $key % 2 != 0 ? '</tr>' : '';
                    $key++;
                }

                if($order['paid_status'] === '50'){
                    $CI->model_orders->updatePaidStatus($order['id'], 51);
                }
            }

            $html .= '</table>
                </body>
            </html>';

            // Visualizar
            $dompdf->loadHtml($html);
            $dompdf->render();
            
            $output = $dompdf->output();

            // Nome do arquivo
            $filename = "Etiquetas_Transportadora_" . $idCliente .'_'.date('d-m-Y') . ".pdf";
            $savePath = FCPATH . "assets/images/etiquetas/";
            $filePath = $savePath . $filename;

            file_put_contents($filePath, $output);

            $outputFiles[] = $filename;

        }elseif ($format === "zpl"){

            $zpls = array();
            
        
            $CI->load->model('model_settings');
            $credentialCorreios = $CI->model_settings->getSettingDatabyName('credentials_correios');
            if (!$credentialCorreios || $credentialCorreios['status'] != 1)
                return false;
    
              
            $dataCorreios = json_decode($credentialCorreios['value']);

            $CI->load->model('model_orders');
            $CI->load->model('model_stores');
            $CI->load->model('model_nfes');
            $CI->load->model('model_freights');
            
            $zpls = array();
            foreach ($orders as $order){
    
                
                $dataOrder  =  $CI->model_orders->getOrdersData(0, $order['id']);
                $dataStore  =  $CI->model_stores->getStoresData($dataOrder['store_id']); 
                $dataNfe    =  $CI->model_nfes->getNfesDataByOrderId($order['id'], true); 
                $freights   =  $CI->model_freights->getFreightsDataByOrderId($order['id']); 
                
            
                if (!$dataOrder || !$dataStore || !$dataNfe || !$freights)
                    continue;
        
                $dataNfe = $dataNfe[0];
        
                $dataFreight = $CI->model_freights->getOrderIdForCodeTracking($freights[0]['codigo_rastreio']);

                $chancela = '';
                $method = '';
    
                if ($dataFreight['method'] == 'PAC') {
                    $chancela = "^FO600,50^GFA,2337,2337,19,,::W01FF,V0KFC,T01MFE,T0OFC,S07PF8,R01QFE,R07RF8,Q01SFE,Q07TF8,Q0UFC,P03VF,P07VF8,O01WFE,O03XF,O07XF8,O0YFC,N01YFE,N03gF,N07gF8,N0gGFC,M01gGFE,M03gHF,M07gHF8,:M0gIFC,L01gIFE,L03gJF,:L07gJF8,:L0gKFC,K01gKFE,:K01gLF,K03gLF,K03gLF8,K07gLF8,:K0gMFC,::J01gMFE,::J01gNF,J03gNF,:::J03gNF8,J07gNF8,:::::::::::::::::J03gNF8,J03gNF,::J01gMFE,:::K0gMFC,::K07gLF8,:K03gLF8,K03gLF,:K01gKFE,:L0gKFC,L07gJF8,:L03gJF,:L01gIFE,M0gIFC,:M07gHF8,M03gHF,M01gGFE,N0gGFC,N07gF8,N03gF,N01YFE,O0YFC,O07XF8,O03XF,O01WFE,P07VF8,P03VF,P01UFC,Q07TF8,Q01SFE,R07RF8,R01QFE,S07PF8,T0OFC,T01MFE,V0KFE,W03FF,,::^FS";
                } elseif ($dataFreight['method'] == 'SEDEX') {
                    $chancela = "^FO600,50^GFA,2565,2565,19,,::::W0JFC,V0LFE,U0NFE,T07OFC,S01QF,S0RFC,R03SF,R0TFC,Q01UF,Q07UFC,Q0VFE,P03WF,P07WFC,P0XFE,O01YF,O03YF8,O07YFC,O0gFE,N01gGF,N03gGF8,N07gGFC,N0gHFC,N0gHFE,M01gIF,M03gIF8,M07gIF8,M07gIFC,M0gJFE,:L01gKF,L03gKF,L03gKF8,:L07gKFC,:L0gLFE,:K01gMF,::K03gMF,K03gMF8,::K07gMF8,K07gMFC,::K0gNFC,:K0gNFE,::::::K0RFJ03QFE,K0PFEM07OFE,K0OFEO0OFE,K0NFEP01NFE,K0NF8Q03MFE,K0MFCS0MFE,K0MFT01LFE,K0LFCU07KFE,K0LFV01KFE,K0KFCW0KFE,K0KF8W03JFE,K0JFEY0JFE,K0JFCY07IFE,K0JFg01IFE,K0IFEgG0IFE,K0IFCgG07FFE,K0IFgH03FFE,K0FFEgI0FFE,K0FFCgI07FE,K0FF8gI03FE,K0FFgJ01FE,K0FEgJ01FE,K0FCgK07E,K0F8gK07E,K0FgL03E,K0FgL01E,K0EgM0E,K0CgM06,,::::::W07IFC,V03KF8,U01LFE,U07MF8,U0NFE,T03OF8,T07OFC,S01PFE,S03QF8,S07QF8,S0RFE,R01SF,R03SF,R03SF8,R07SFC,R0TFC,R0TFE,Q01UF,:Q03UF8,:Q07UFC,::Q0VFC,Q0VFE,::P01VFE,P01WF,:::Q0VFE,,:::^FS";
                }
    
                //--dados para gerar o DATAMATRIX
                $qrCode['CEP_destino'] = str_replace(array('-', '.'), '', $dataOrder['customer_address_zip']);       //--8 caracteres
                $qrCode['complemento_do_CEP'] = '00000'; //--5 caracteres
                $qrCode['CEP_Origem'] = str_replace(array('-', '.'), '', $dataStore['zipcode']);        //--8 caracteres
                $qrCode['complemento_do_CEP_origem'] = '00000';       //--5 caracteres
                $validador = 0;
    
                for ($i = 0; $i < strlen($qrCode['CEP_Origem']); $i++) {
                    $qrCode['CEP_Origem'][$i] . " \n ";
                    $validador = $validador + $qrCode['CEP_Origem'][$i];
                }
                $arr[] = 10;
                $arr[] = 20;
                $arr[] = 30;
                $arr[] = 40;
                $arr[] = 50;
                $arr[] = 60;
                $arr[] = 70;
                $arr[] = 80;
                $arr[] = 90;
                foreach ($arr as $key => $val) {
                    if ($val >= $validador) {
                        $validadorCep = $val - $validador;
                        break;
                    }
                }
    
                $qrCode['Validador_do_CEP_Destino'] = $validadorCep;       //--1 caracteres
                $qrCode['IDV'] = '51';       //--2 caracteres
                //--inicio dados variaveis
                $qrCodeDadosVariaveis['Etiqueta'] = $order;       //--13 caracteres
                
                $qrCodeDadosVariaveis['Servicos_Adicionais'] = '00000000'; //--8 caracteres   (AR, MP, DD, VD) Quando não possui o serviço adicional deverá serpreenchido com 00
    
                if ($dataFreight['method'] == 'SEDEX') $qrCodeDadosVariaveis['Servicos_Adicionais'] = '00019000';
                if ($dataFreight['method'] == 'PAC') $qrCodeDadosVariaveis['Servicos_Adicionais'] = '00064000';
    
                $qrCodeDadosVariaveis['Cartao_de_Postagem'] = $dataCorreios->cartao;       //--10 caracteres
                $qrCodeDadosVariaveis['Codigo_do_Servico'] = '00000';       //--5 caracteres
                $qrCodeDadosVariaveis['Informacao_de_Agrupamento'] = '00';       //--2 caracteres
                $qrCodeDadosVariaveis['Numero_do_Logradouro']      = str_pad(trim(substr(tirarAcentos($dataOrder['customer_address_num']), 0, 5)), 5, ' ', STR_PAD_LEFT);       //--5 caracteres
                $qrCodeDadosVariaveis['complemento_do_Logradouro'] = trim(substr(strtolower($dataOrder['customer_address_compl']), 0, 20));       //--20 caracteres
                $qrCodeDadosVariaveis['complemento_do_Logradouro'] = ltrim($qrCodeDadosVariaveis['complemento_do_Logradouro']);
                $qrCodeDadosVariaveis['complemento_do_Logradouro'] = str_replace('-', '', $qrCodeDadosVariaveis['complemento_do_Logradouro']);
                $qrCodeDadosVariaveis['complemento_do_Logradouro'] = str_pad($qrCodeDadosVariaveis['complemento_do_Logradouro'], 20, ' ', STR_PAD_LEFT);
    
                $qrCodeDadosVariaveis['Valor_Declarado'] = str_pad(str_replace(array(',', '.'), '', $dataOrder['total_order']), 5, '0', STR_PAD_LEFT);       //--5 caracteres
                // $qrCodeDadosVariaveis['Valor_Declarado']='00000'; //--5 caracteres
                $qrCodeDadosVariaveis['DDD_TelefoneDestinatário'] = '000000000000'; //--12 caracteres
                $qrCodeDadosVariaveis['latitude'] = '-00.000000'; //--10 caracteres
                $qrCodeDadosVariaveis['longitude'] = '-00.000000'; //--10 caracteres
                $qrCodeDadosVariaveis['pipe'] = '|'; //--1 caracteres
                $qrCodeDadosVariaveis['Reserva_para_cliente'] = str_pad('', 30, ' ', STR_PAD_LEFT); //--30 caracteres
    
              

                $qrCode['dados_variaveis '] = '';
                foreach ($qrCodeDadosVariaveis as $key => $val) $qrCode['dados_variaveis '] .= $val; //--131 caracteres
    
                $qrCodeString = '';
                foreach ($qrCode as $key => $val) $qrCodeString .= $val;

              
                $customerName = tirarAcentos($dataOrder["customer_name"]);
                $customerAddress = tirarAcentos($dataOrder['customer_address']);
                $customerAddressNum = tirarAcentos($dataOrder['customer_address_num']);
                $customerAddressNeigh = tirarAcentos($dataOrder['customer_address_neigh']);
                $customerCity = tirarAcentos($dataOrder['customer_address_city']);
                $customerUF = tirarAcentos($dataOrder['customer_address_uf']);

                $remetenteRazaoSocial = tirarAcentos($dataStore['raz_social']);
                $remetenteAddress = tirarAcentos($dataStore['address']);
                $remetenteAddrNum = tirarAcentos($dataStore['addr_num']);
                $remetenteAddrNeigh = tirarAcentos($dataStore['addr_neigh']);
                $remetenteCity = tirarAcentos($dataStore['addr_city']);
                $remetenteUF = tirarAcentos($dataStore['addr_uf']);

                // Agora, você pode usar essas variáveis ao criar a saída ZPL
                $zpls[$order['id']] =
                "^XA
                ^FX LINHA DE CIMA
                ^FO50,30^GB700,1,3^FS

                {$chancela}

                ^FX LINHA DE CIMA DADOS CORREIO
                ^CFA,30
                ^FO40,100^FD {$dataFreight['method']} CONTRATO ^FS
                ^FO40,140^FD AGENCIA^FS
                ^CFA,20
                ^FO45,180^FD {$dataCorreios->contrato}/2016-DR/SPM ^FS

                ^FX LOGO - TOPO
                ^CF0,40 ^A0N^FO40,50^FD CONECTA LA ^FS

                ^FO360,40^BXN,5,200,0,0,1,_
                ^FD{$qrCodeString}^FS

                ^FX CODIGO DE BARRAS - RASTREIO
                ^BY3,3,200
                ^FO72,255^BC^FD{$freights[0]["codigo_rastreio"]}^FS

                ^FX DADOS DESTINATÁRIO
                ^CF0,40 ^A0N^FO60,510^FD{$customerName}^FS
                ^CF0,33 ^FO60,560^FD{$customerAddress},{$customerAddressNum} Bairro: {$customerAddressNeigh}^FS
                ^CF0,33 ^FO60,610^FDCEP: {$dataOrder['customer_address_zip']} - {$customerCity} - {$customerUF}^FS

                ^FX CODIGO DE BARRAS - CEP
                ^BY2,2,90
                ^FO430,650^BC^FD {$dataOrder['customer_address_zip']} ^FS

                ^FX LINHA DO MEIO
                ^FO50,780^GB700,1,3^FS

                ^FX REMENTENTE
                ^CF0,35 ^A0N^FO60,820^FDREMETENTE:^FS
                ^CF0,35 ^A0N^FO280,820^FD{$remetenteRazaoSocial}
                ^CF0,33 ^FO60,860^FD{$remetenteAddress}, {$remetenteAddrNum}^FS
                ^CF0,33 ^FO60,900^FDBairro: {$remetenteAddrNeigh}^FS
                ^CF0,33 ^FO60,940^FDCEP: {$dataStore['zipcode']} - {$remetenteCity} - {$remetenteUF}^FS

                ^FX LINHA DO FINAL
                ^FO50,1000^GB700,1,3^FS

                ^FX DADOS NF
                ^CF0,35 ^A0N^FO60,1040^FDNF^FS
                ^CF0,35 ^A0N^FO180,1040^FD{$dataNfe['nfe_num']}^FS

                ^CF0,35 ^A0N^FO480,1040^FDPED^FS
                ^CF0,35 ^A0N^FO570,1040^FD{$order['id']}^FS

                ^FX CODIGO DE BARRAS - FINAL
                ^BY2,1,70
                ^FO60,1080^B2^FD{$dataNfe['chave']}^FS

                ^XZ";

                if($order['paid_status'] === '50'){
                    $CI->model_orders->updatePaidStatus($order['id'], 51);
                }

            }

            $savePath = FCPATH . "assets/images/etiquetas/";
           
            foreach ($zpls as $order => $zpl) {
                            
                $filename = "Etiquetas_Transportadora_" . $idCliente .'_'.$order.'_'.date('d-m-Y-H-i-s') . "_ZPL.txt"; // Nome do arquivo ZPL
                $filePath = $savePath . $filename;
                
                // Abra o arquivo no modo de escrita
                if ($file = fopen($filePath, 'w')) {
                    // Escreva o conteúdo no arquivo
                    fwrite($file, $zpl);

                    // Feche o arquivo
                    fclose($file);

                    // Adicione o nome do arquivo criado à lista de arquivos de saída
                    $outputFiles[] = $filename;
                } else {
                   continue;
                }
            }

        }else{
            $mensagem = "retorno não suportado";
            return $mensagem;
            die;
        }
        return $outputFiles;
    }
    
}

if (!function_exists('tirarAcentos')) {
    function tirarAcentos($string) {
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }
}
