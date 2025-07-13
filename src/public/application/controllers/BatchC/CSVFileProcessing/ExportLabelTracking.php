<?php

require 'system/libraries/Vendor/autoload.php';
include "./system/libraries/Vendor/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Router $router
 *
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_shipping_company $model_shipping_company
 * @property Model_table_shipping $model_table_shipping
 * @property Model_company $model_company
 * @property Model_orders $model_orders
 * @property Model_nfes $model_nfes
 * @property Model_freights $model_freights
 * @property Model_stores $model_stores
 */

class ExportLabelTracking extends BatchBackground_Controller
{
	private $module;
	
	public function __construct()
	{
		parent::__construct();
		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp' 	=> 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);

		$this->load->model('model_csv_to_verifications');
		$this->load->model('model_shipping_company');
		$this->load->model('model_table_shipping');
        $this->load->model('model_company');
        $this->load->model('model_orders');
        $this->load->model('model_nfes');
        $this->load->model('model_freights');
        $this->load->model('model_stores');
    }
	
	public function run($id=null,$params=null)
	{
		$this->module = 'ExportLabelTracking';

		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}

		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

		$this->processTableFreight();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	private function processTableFreight()
	{
        ini_set('output_buffering', true); // no limit
        ini_set('output_buffering', 12288); // 12KB limit
		$labels = $this->model_csv_to_verifications->getDontChecked(false, $this->module);

		foreach ($labels as $label) {
            $t_start = microtime(true) * 1000;
            $fileTempId = $label['id'];

			echo "Exportando etiqueta com id=$fileTempId ...\n";

			$orders = json_decode($label['form_data'], true);
			
			if (empty($orders)) {
				echo "Valor do campo 'form_data' chegou vazio ou errado. arquivo id=$fileTempId\n";
                $this->model_csv_to_verifications->update(
                    array(
                        'processing_response' 	=> 'A geração foi mal sucedida, faça um novo envio. (form_data empty)',
                        'final_situation' 		=> 'err',
                        'checked' 			  	=> true
                    ),
                    $fileTempId
                );
				continue;
			}

			try {
                $path_label_file = str_replace(FCPATH, '', $this->createLabelTracking($orders));
			} catch (Exception $exception) {
				echo "Encontrou um erro para gerar as etiquetas. arquivo id=$fileTempId. Erros na coluna 'processing_response'\n";
				$this->model_csv_to_verifications->update(
					array(
						'processing_response'   => $exception->getMessage(),
						'final_situation' 		=> 'err',
						'checked' 			  	=> true
					),
					$fileTempId
				);
				continue;
			}

			$this->model_csv_to_verifications->update(
				array(
					'processing_response' => 'Etiquetas geradas com sucesso!',
					'final_situation'     => 'success',
					'checked'             => true,
                    'upload_file'         => $path_label_file
				),
				$fileTempId
			);

            echo "Total time process: " . number_format((((microtime(true) * 1000) - $t_start) / 1000), 5, '.', '')."\n";
			echo "Arquivo processado com sucesso. arquivo id=$fileTempId\n";
		}
	}

    /**
     * @throws Exception
     */
    public function createLabelTracking(array $orders): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $companySellerCenter = $this->model_company->getCompanyData(1);
        $logoSellerCenter = $companySellerCenter['logo'];

        $html = '<html lang="pt-br">
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1, maximum-scale=1, viewport-fit=cover, shrink-to-fit=no">
                        <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
                        <title>Etiquetas</title>
                        <style>
                    @page { margin: 0.2cm; }
                        </style>
                    </head>
                    <body>
                        <table style="width: 100%">';
        $key = 0;

        $dataStoreTemp  = array();
        $nfe_label      = array();
        $freights_label = array();
        $data_orders    = $this->model_orders->getOrdersDataByIds($orders);
        $data_nfe       = $this->model_nfes->getNfesDataByOrderIds($orders);
        $data_freights  = $this->model_freights->getFreightsDataByOrderIds($orders);

        foreach ($data_nfe as $nfe_order) {
            $nfe_label[$nfe_order['order_id']][] = $nfe_order;
        }
        foreach ($data_freights as $freight_order) {
            $freights_label[$freight_order['order_id']][] = $freight_order;
        }

        foreach ($data_orders as $dataOrder) {
            if (!array_key_exists($dataOrder['store_id'], $dataStoreTemp)) {
                $store = $this->model_stores->getStoresData($dataOrder['store_id']);
                $dataStoreTemp[$dataOrder['store_id']] = $store;
            }

            $store      = $dataStoreTemp[$dataOrder['store_id']];
            $nfe        = $nfe_label[$dataOrder['id']];
            $freights   = $freights_label[$dataOrder['id']];

            if (!$dataOrder || !$store || !$nfe || !count($freights)) {
                throw new Exception('Não encontramos informações para a geração da etiqueta.');
            }

            $ignore_tracking_code_duplicated = array();
            $count_tracking = 0;
            foreach ($freights as $freight) {
                $tracking_code = !empty($freight['shipping_order_id']) ? $freight['shipping_order_id'] : $freight['codigo_rastreio'];

                if (in_array($tracking_code, $ignore_tracking_code_duplicated)) {
                    continue;
                }
                $ignore_tracking_code_duplicated[] = $tracking_code;
                $count_tracking++;
            }

            $ignore_tracking_code_duplicated = array();
            foreach ($freights as $freight) {
                $tracking_code = !empty($freight['shipping_order_id']) ? $freight['shipping_order_id'] : $freight['codigo_rastreio'];

                if (in_array($tracking_code, $ignore_tracking_code_duplicated)) {
                    continue;
                }

                $ignore_tracking_code_duplicated[] = $tracking_code;

                $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
                $barCode = $generator->getBarcode(
                    $tracking_code,
                    $generator::TYPE_CODE_128,
                    1,
                    40
                );

                $codeTagView = $freight['codigo_rastreio'];
                if (strtolower($freight['ship_company']) == 'sequoia') {
                    $codeTagView = $freight['shipping_order_id'];
                }

                $html .= $key % 2 == 0 ? '<tr>' : '';
                $col = count($orders) == 1 && $count_tracking == 1 ? "40% " : "85% ";
                $html .= '<td style="width: 50%;padding-bottom: 10px;"">
                    <table style="width: ' . $col . '">
                        <tr>
                            <td>
                                <table style="width: 100%; border: 1px solid #000 !important;">
                                    <tr>
                                        <td style="font-size: 11px;width:100%; padding-top: 20px; padding-left: 40%; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Nota Fiscal: ' . $nfe[0]['nfe_num'] . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;width:100%; padding-top: 15px; padding-left: 40%; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Pedido: ' . wordwrap($dataOrder['numero_marketplace'], 20, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;font-size: 13px;padding-top: 30px;padding-bottom: 5px; padding-left: 5px; text-transform: uppercase; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif"><strong>' . explode(' ', $freight['ship_company'])[0] . '</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="direction: rtl;font-size: 18px;padding-top: 20px;padding-bottom: 15px; padding-left: 5px; text-align:center; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $codeTagView . '</td>
                                    </tr>
                                    <tr>
                                        <td style="direction: rtl;font-size: 18px;padding-top: 5px;padding-bottom: 5px; padding-left: 25px; text-align:center; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . $barCode . '</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <img style="text-align: right;padding-left: 5px;margin-top: -160px;position: relative;top:2;left:5" src="' . $logoSellerCenter . '" width="100px" height="40px" alt="logo">
                                        </td>
                                    </tr>
                                </table>
                                <table style="width: 100%; border: 1px solid #000 !important;">
                                    <tr>
                                        <td style="font-size: 14px !important;padding: 20px 0 15px 15px !important;background-color: #000; color: #fff;width: 100%;font-weight: bold; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Destinatário</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 17px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($dataOrder['customer_name'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($dataOrder['customer_address'] . ', ' . $dataOrder['customer_address_num'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="overflow: hidden; max-width: 100%; font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($dataOrder['customer_address_compl'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="overflow: hidden; max-width: 100%; font-size: 11px;padding-top: 18px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($dataOrder['customer_address_zip']. ' - ' . $dataOrder['customer_address_neigh'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="overflow: hidden; max-width: 100%; font-size: 11px;padding-top: 18px; padding-bottom: 10px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($dataOrder['customer_address_city'] . ' - ' . $dataOrder['customer_address_uf'], 40, "<br/>", true) . '</td>
                                    </tr>
                                </table>
                                <table style="width: 100%; border: 1px solid #000 !important;">
                                    <tr>
                                        <td style="font-size: 14px !important;padding: 20px 0 15px 15px !important;background-color: #000; color: #fff;width: 100%;font-weight: bold; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">Remetente</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 17px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($store['name'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($store['raz_social'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($store['address'] . ', ' . $store['addr_num'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 16px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($store['addr_compl'] . ' ' . $store['addr_neigh'], 40, "<br/>", true) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px;padding-top: 16px; padding-bottom: 10px; padding-left: 5px; font-family: \'Trebuchet MS\', \'arial\', \'helvetica\', \'Open Sans\', sans-serif">' . wordwrap($store['zipcode'] . ' ' . $store['addr_city'] . ' - ' . $store['addr_uf'], 40, "<br/>", true) . '</td>
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

        $name_label_file = microtime(true) * 10000;
        $path_label_file = FCPATH . "assets/images/etiquetas/P_{$name_label_file}_A4.pdf";

        $dompdf->loadHtml($html);
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents($path_label_file, $output);

        return $path_label_file;
    }
	
}
