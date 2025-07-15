<?php
require APPPATH . "controllers/Api/V1/API.php";

class PartialShipping extends API
{
    /**
     * Processa dados de envio parcial de pedidos multiseller.
     * Opcionalmente recebe o payload para facilitar testes, caso
     * nulo os dados serão lidos de php://input.
     *
     * @param array|null $payload
     * @return array
     */
    public function index_post(array $payload = null)
    {
        if ($payload === null) {
            $payload = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'JSON inválido'
                ];
            }
        }

        $required = ['bill_no', 'tracking_code', 'carrier', 'shipping_date'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return [
                    'success' => false,
                    'message' => "Campo obrigatório: $field"
                ];
            }
        }

        $billNo = xssClean($payload['bill_no']);
        $shippingData = [
            'tracking_code' => xssClean($payload['tracking_code']),
            'carrier' => xssClean($payload['carrier']),
            'shipping_date' => xssClean($payload['shipping_date']),
        ];

        if (!empty($payload['estimated_delivery'])) {
            $shippingData['estimated_delivery'] = xssClean($payload['estimated_delivery']);
        }

        if (!class_exists('GetOrders')) {
            require_once APPPATH . 'controllers/BatchC/Marketplace/Conectala/GetOrders.php';
        }

        $processor = new GetOrders();
        return $processor->processPartialShipping($billNo, $shippingData);
    }
}
