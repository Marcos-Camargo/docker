<?php

defined('BASEPATH') or exit('No direct script access allowed');

require 'system/libraries/Vendor/autoload.php';
include "./system/libraries/Vendor/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;
use Firebase\JWT\JWT;

/**
 * @property Model_clients $model_clients
 * @property Model_orders $model_orders
 * @property Model_settings $model_settings
 * @property Model_company $model_company
 * @property Model_nfes $model_nfes
 * @property Model_freights $model_freights
 * @property Model_stores $model_stores
 *
 * @property JWT $jwt
 *
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Output $output
 */

class Tracking extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
		$this->load->model('model_clients');
		$this->load->model('model_orders');
        $this->load->model('model_settings');
        $this->load->model('model_company');
        $this->load->model('model_nfes');
        $this->load->model('model_freights');
        $this->load->model('model_stores');
        $this->load->library('JWT');
    }

    public function index()
    {
        $sellerCenter = 'conectala';
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
        $this->data['sellerCenter'] = $sellerCenter;            
        $this->data['need_change_password'] = false;        
        $this->data['page_title'] = $this->lang->line('application_tracking');
        $this->data['title'] = $this->lang->line('application_tracking');
        $this->load->view('tracking/index', $this->data);        
    }

    public function searchOrderByCpfCnpj()
    {
        $params = cleanArray($this->input->get());
        $result = $this->model_clients->orderByClient($params['cnpj_cpf'], $params['type']);
        echo json_encode($result);
    }

    public function status($id = null)
    {
        try {

            $sellerCenter = 'conectala';
            $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        if ($settingSellerCenter) {
            $sellerCenter = $settingSellerCenter['value'];
        }
        $this->data['sellerCenter'] = $sellerCenter;    
        
            $base = base64_decode($id);

            $base = explode('-',$base);
            if (empty($base[0]) || empty($base[1])) {
                redirect('/tracking', 'refresh');
            }
            $id =$base[0];

            //$this->data['id'] = $id;
            
            $this->data['need_change_password'] = false;
            $this->data['page_title'] = $this->lang->line('application_tracking');
            $this->data['title'] = $this->lang->line('application_tracking');            
            $this->data['item'] = $this->model_clients->orderItemByClient($id);
            $result = $this->model_clients->orderStatus($id);
            
            $this->data['historico'] = $this->model_clients->historyOrder($id);
            $orders_data = $this->model_orders->getOrdersData('',$id,'');
            $this->data['id'] = $orders_data['numero_marketplace'];           
           
            $status = empty($status[0]['paid_status']) ? 0 : $result[0]['paid_status'];
            $dataEnvio =  empty($status[0]['data_envio']) ? 0 : $result[0]['data_envio'];
            $dataEntrega =  empty($status[0]['data_entrega']) ? 0 : $result[0]['data_entrega'];

            $step = ['1', '0', '0', '0', '0'];
            
            $result = empty($result[0]) ? $result : $result[0];

            if (in_array($status,['5','45', '55'])) { 
                $step = ['1', '1', '1', '0', '0'];
            }

            if ($dataEnvio != 0 || !is_null($result['ship_company_preview'])) { 
                $step = ['1', '1', '0', '0', '0'];
            }

            if (in_array($status,['6','60']) || $dataEntrega != 0) { 
                $step = ['1', '1', '1', '1', '1'];
            }
            
            $this->data['step'] = $step;
            
            $this->data['info'] = empty($result[0]) ? $result : $result[0];            
            $this->load->view('tracking/status', $this->data);
        } catch (\Exception $e) {
            redirect('/tracking', 'refresh');
        }
    }

    public function printLabel(string $orders)
    {
        $decodeJWT = $this->jwt->decode($orders, get_instance()->config->config['encryption_key'], array('HS256'));
        $orders = $decodeJWT->orders ?? array();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        if (is_string($decodeJWT) || !count($orders)) {
            $html = "<div style='width: 100%; text-align: center;margin-top: 10%'><h2 style='font-family: 'Microsoft YaHei','Source Sans Pro', sans-serif;'>Não encontramos informações para a geração da etiqueta.<br>Link está inválido ou expirado. Gere um novo link!</h4></div>";
            $dompdf->loadHtml($html);
            $dompdf->render();
            $dompdf->stream(
                'Etiquetas' . implode('-', $orders) . '.pdf', /* Nome do arquivo de saída */
                array(
                    "Attachment" => false /* Para download, altere para true */
                )
            );
            return false;
        }

        $companySellerCenter = $this->model_company->getCompanyData(1);
        $logoSellerCenter = $companySellerCenter['logo'];

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

            $dataOrder  = $this->model_orders->getOrdersData(0, $order);
            $store      = $this->model_stores->getStoresData($dataOrder['store_id']);
            $nfe        = $this->model_nfes->getNfesDataByOrderId($order, true);
            $freights   = $this->model_freights->getFreightsDataByOrderId($order);

            if (!$dataOrder || !$store || !$nfe || !count($freights)) {
                $html = "<div style='width: 100%; text-align: center;margin-top: 10%'><h2 style='font-family: 'Microsoft YaHei','Source Sans Pro', sans-serif;'>Não encontramos informações para a geração da etiqueta.</h4></div>";
                $dompdf->loadHtml($html);
                $dompdf->render();
                $dompdf->stream(
                    'Etiquetas' . implode('-', $orders) . '.pdf', /* Nome do arquivo de saída */
                    array(
                        "Attachment" => false /* Para download, altere para true */
                    )
                );
                return false;
            }

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
        }
        $html .= '</table>
            </body>
        </html>';


        $dompdf->loadHtml($html);
        $dompdf->render();
        return $dompdf->stream(
            "aaaaa.pdf",
            array(
                "Attachment" => false
            )
        );

        // Visualizar
        /*$dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream(
            "Etiquetas_Transportadora_" . date('d-m-Y-H-i-s') . ".pdf", // Nome do arquivo de saída
            array(
                "Attachment" => true // Para download, altere para true
            )
        );*/
    }
}