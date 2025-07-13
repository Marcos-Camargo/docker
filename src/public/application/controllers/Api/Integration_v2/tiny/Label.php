<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

class Label extends REST_Controller
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    /**
     * Instantiate a new Label instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();
        header('Integration: v2');
    }

    /**
     * Atualização de estoque, receber via POST(eu acho, confirmar com Tiny)
     */
    public function index_post()
    {
        ob_start();

        /**
         * example payload
         *
         {
             "labelType": "pdf",
             "groupLabel": true,
             "orders": [
                {
                 "id": 123451
                },
                {
                 "id": 123452
                },
                {
                 "id": 123453
                },
                {
                 "id": 123454
                },
                {
                 "id": 123455
                },
                {
                 "id": 123456
                },
                {
                 "id": 123457
                },
                {
                 "id": 123458
                }
             ]
         }
         */

        if (!isset($_GET['apiKey'])) {
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store  = $this->order_v2->getStoreForApiKey($apiKey);

        if (!$store) {
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            $this->order_v2->startRun($store);
        } catch (InvalidArgumentException $exception) {
            return $this->response($exception->getMessage(), REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->order_v2->setToolsOrder();

        // Recupera dados enviado pelo body
        $body = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'Api/Label/Tiny', json_encode($body));

        if (!property_exists($body, 'labelType') || !property_exists($body, 'groupLabel') || !property_exists($body, 'orders')) {
            return $this->response('Campos requeridos incompletos!', REST_Controller::HTTP_BAD_REQUEST);
        }

        $labelType  = $body->labelType;
        $groupLabel = $body->groupLabel;
        $orders     = $body->orders;
        $labels     = array();

        if (!in_array($labelType, array('pdf', 'zpl', 'thermal'))) {
            return $this->response('O tipo de etiqueta está inválido, informa pdf, zpl ou thermal!', REST_Controller::HTTP_BAD_REQUEST);
        }

        switch ($labelType) {
            case 'pdf':
                $fieldLabel = 'file_a4';
                break;
            case 'zpl':
                $fieldLabel = 'file_zpl';
                break;
            case 'thermal':
                $fieldLabel = 'file_thermal';
                break;
            default:
                $fieldLabel = 'file_a4';
        }

        foreach ($orders as $order) {

            if (!property_exists($order, 'id')) {
                return $this->response('Campos ID do pedido não localizado!', REST_Controller::HTTP_BAD_REQUEST);
            }

            try {
                $dataTracking = $this->order_v2->getTrackingOrder($order->id);
            } catch (InvalidArgumentException $exception) {
                return $this->response("Pedido ($order->id) não localizado ou etiqueta está indisponível.", REST_Controller::HTTP_BAD_REQUEST);
            }

            $labels[$order->id] = array();

            foreach ($dataTracking->label as $label) {
                $labels[$order->id] = array(
                    'label'         => empty($label->$fieldLabel) ? null : $label->$fieldLabel,
                    'plp'           => empty($label->file_plp) ? null : $label->file_plp,
                    'number_plp'    => empty($label->number_plp) ? null : $label->number_plp,
                );
            }
        }

        $labelGroup = array();
        $checkPlpGroup = array();
        $i = -1;
        foreach ($labels as $order => $label) {

            if ($groupLabel && $label['number_plp']) {
                if (array_key_exists($label['number_plp'], $checkPlpGroup)) {
                    $labelGroup['archives'][$checkPlpGroup[$label['number_plp']]]['orders'][] = $order;

                    $groupPlp = base_url("assets/images/etiquetas/P_T_{$label['number_plp']}_A4.pdf");
                    $labelGroup['archives'][$checkPlpGroup[$label['number_plp']]]['taglink'] = $groupPlp;
                    continue;
                }

                $checkPlpGroup[$label['number_plp']] = ++$i;
            } else {
                $i++;
            }

            $archive = array(
                "orders" => [$order],
                "taglink" => $label['label']
            );

            if ($label['plp']) {
                $archive["plplink"] = $label['plp'];
            }

            $labelGroup['archives'][] = $archive;

        }

        ob_clean();
        return $this->response($labelGroup, REST_Controller::HTTP_OK);
    }
}