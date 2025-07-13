<?php

/**
 * Class ParserOrderAnymerket
 * @property Admin_Controller $CI
 */
class ParserOrderAnymerket
{
    public function __construct($ci)
    {
        $this->CI = &$ci;
    }
    public function parserToAnymarketFormat($order, $itens, $payments, $clients, $integration, $sellercenter)
    {
        // $dataformat="Y-d-mTH:i:s";
        $dataformat = "Y-m-d\TH:i:sP";

        // dd($order);
        $anyMarketOrder = [];
        $anyMarketOrder['marketPlaceId'] = $order['id'];
        $anyMarketOrder['marketPlaceNumber'] = $order['numero_marketplace'];
        $createAt = new DateTime($order['date_time'], new DateTimeZone("America/Sao_Paulo"));
        $anyMarketOrder['createdAt'] = $createAt->format($dataformat);

        // dd($order);
//        if ($order["date_cancel"]) {
//            $cancelDate = new DateTime($order["date_cancel"], new DateTimeZone("America/Sao_Paulo"));
//            $anyMarketOrder['cancelDate'] = $cancelDate->format($dataformat);
//        } else {
        // pedido sempre irá como pago, cancelado não precisa ser integrado
        $anyMarketOrder['cancelDate'] = null;
//        }
        $anyMarketOrder['cancellationCode'] = null;
        $anyMarketOrder['transmissionStatus'] = null;
        //$anyMarketOrder['status'] = $this->getAnyStatus($order['paid_status']);
        $anyMarketOrder['status'] = 'PAID'; // sempre envia pedido pagos

        $anyMarketOrder['marketPlaceStatusComplement'] = null;
        $anyMarketOrder['marketPlaceUrl'] = null;
        $anyMarketOrder['marketPlaceShipmentStatus'] = null;
        $anyMarketOrder['invoice'] = null; //? aqui é pra ser um objeto com os dados da fatura?
        //$anyMarketOrder['marketPlaceStatus'] = $this->getAnyStatus($order['paid_status']);
        $anyMarketOrder['marketPlaceStatus'] = 'PAID'; // sempre envia pedido pagos

        $anyMarketOrder['shipping'] = [
            'city' => $order['customer_address_city'],
            'state' => $order['customer_address_uf'],
            'stateNameNormalized' => $order['customer_address_uf'],
            'country' => 'Brazil',
            'countryAcronymNormalized' => null,
            'countryNameNormalized' => 'Brazil',
            'address' => null,
            'number' => $order['customer_address_num'],
            'neighborhood' => $order['customer_address_neigh'],
            'street' => $order['customer_address'],
            'comment' => $order['customer_address_compl'],
            'reference' => $order['customer_reference'],
            'zipCode' => $order['customer_address_zip'],
            'receiverName' => $order['customer_name']
        ];
        try {
            $anyMarketOrder['shipping']['promisedShippingTime'] = $this->CI->somar_dias_uteis($order["data_pago"], $order["ship_time_preview"]);
        } catch (Throwable $e) {

        }
        $anyMarketOrder['billingAddress'] = [
            /*
            'city' => $clients['addr_city'],
            'state' => $clients['addr_uf'],
            'stateNameNormalized' => $clients['addr_uf'],
            'country' => 'Brazil',
            'countryAcronymNormalized' => null,
            'countryNameNormalized' => 'Brazil',
            'number' => $clients['addr_num'],
            'neighborhood' => $clients['addr_neigh'],
            'street' => $clients['customer_address'],
            'comment' => null,
            'reference' => null,
            'zipCode' => $clients['zipcode'],
            */
            'city' => $order['customer_address_city'],
            'state' => $order['customer_address_uf'],
            'stateNameNormalized' => $order['customer_address_uf'],
            'country' => 'Brazil',
            'countryAcronymNormalized' => null,
            'countryNameNormalized' => 'Brazil',
            'number' => $order['customer_address_num'],
            'neighborhood' => $order['customer_address_neigh'],
            'street' => $order['customer_address'],
            'comment' => $order['customer_address_compl'],
            'reference' => $order['customer_reference'],
            'zipCode' => $order['customer_address_zip'],
            'receiverName' => $clients['customer_name'],
        ];
        $anyMarketOrder['anymarketAddress'] = null;
        $anyMarketOrder['buyer'] = [
            'id' => $clients['id'],
            'marketPlaceId' => $clients['id'],
            'name' => $clients['customer_name'],
            'email' => $clients['email'],
            'document' => $clients['cpf_cnpj'],
            'documentType' => 'CPF',
            'phone' => $clients['phone_2'],
            'cellPhone' => $clients['phone_1'],
            'documentNumberNormalized' => $clients['cpf_cnpj'],
        ];
        $anyMarketOrder['tracking'] = null; // Aqui é o codigo de rastreio?

        $anyMarketOrder['items'] = [];

        $totalNetItems = 0;
        $totalGrossItems = 0;
        foreach ($itens as $key => $item) {
            if ($key == 1) {
                // dd(round(floatval($item["amount"]) - floatval($item["discount"]), 2));
            }
            $product = $this->CI->model_products->getProductData(0, $item["product_id"]);
            $partnerId = ParserOrderAnymerket::skuCodeNormalize($product["sku"]);
            if ($item['variant'] != "") {
                $item_var = $this->CI->model_products->getVariantsByProd_idAndVariant($item["product_id"], $item['variant']);
                $partnerId = ParserOrderAnymerket::skuCodeNormalize($item_var["sku"]);
            }
            $data = [
                'sku' => [
                    'partnerId' => $partnerId
                ],
                "amount" => $item['qty'],
                "unit" => round(floatval($item["rate"]) + floatval($item["discount"]), 2),
                "gross" => round((floatval($item["rate"]) + floatval($item["discount"])) * floatval($item['qty']), 2),
                "total" => round((floatval($item["rate"])) * floatval($item['qty']), 2),
                "discount" => round(floatval($item["discount"]) * floatval($item['qty']), 2),
                "marketPlaceId" => $item['product_id'],
                "orderItemId" => $item['id'],
                "shippings" => []
            ];
            $totalNetItems += $data['total'];
            $totalGrossItems += $data['gross'];
            $data["shippings"][] = [
                "id" => null,
                "shippingtype" => $order["ship_companyName_preview"],
                "shippingCarrierNormalized" => null,
                "shippingCarrierTypeNormalized" => null,
            ];
            $anyMarketOrder['items'][] = $data;
        }

        $anyMarketOrder['interestValue'] = 0;
        $anyMarketOrder['discount'] = 0; // round(floatval($order['discount']), 2);
        $anyMarketOrder['freight'] = round(floatval($order['total_ship']), 2);
        // dd(round(floatval($order['total_order']) - floatval($order['discount']), 2), $order['discount']);

        $order['total_order'] = round(floatval($order['total_order']), 2);
        $order['gross_amount'] = round(floatval($order['gross_amount']), 2);
        $order['net_amount'] = round(floatval($order['net_amount']), 2);

        $diffTotalNetProd = $order['total_order'] - $totalNetItems;
        if ($diffTotalNetProd > 0) {
            $anyMarketOrder['interestValue'] = (float)number_format($diffTotalNetProd, 2, '.', '');
            $order['total_order'] -= $anyMarketOrder['interestValue'];
        }

        $anyMarketOrder['discount'] = round(floatval($order['discount'] ?? 0), 2);
        $totalDiscountItems = $totalGrossItems - $totalNetItems;

        // Garante que os valores terão apenas 2 decimais.
        $anyMarketOrder['discount'] = (float)number_format($anyMarketOrder['discount'], 2, '.', '');
        $totalDiscountItems = (float)number_format($totalDiscountItems, 2, '.', '');

        if ($anyMarketOrder['discount'] >= $totalDiscountItems) {
            $anyMarketOrder['discount'] -= $totalDiscountItems;
        }
        $totalCalcGrossOrder = ($totalNetItems + $anyMarketOrder['freight'] + $anyMarketOrder['interestValue']);
        $totalCalcGrossOrder = (float)number_format($totalCalcGrossOrder, 2, '.', '');
        $totalDbNetOrder = (float)number_format((($order['total_order'] ?? 0) + $anyMarketOrder['freight'] + $anyMarketOrder['interestValue']), 2, '.', '');
        $anyMarketOrder['discount'] = ($totalCalcGrossOrder - $anyMarketOrder['discount']) >= $totalDbNetOrder ? $anyMarketOrder['discount'] : 0;

        $discount = round($anyMarketOrder['discount'], 2);
        $anyMarketOrder['discount'] = (float)number_format("{$discount}", 2, '.', '');
        $anyMarketOrder['productNet'] = round(floatval($totalNetItems), 2);
        $totalOrder = $totalCalcGrossOrder - $anyMarketOrder['discount'];
        $anyMarketOrder['total'] = round($totalOrder, 2);

        if ($payments) {
            $anyMarketOrder['payments'] = [];
            $paymentDate = new DateTime($payments[0]["data_vencto"], new DateTimeZone("America/Sao_Paulo"));
            $anyMarketOrder['paymentDate'] = $paymentDate->format($dataformat);
            foreach ($payments as $key => $payment) {
                $data = [
                    "method" => $payment['forma_desc'],
                    "status" => 'PAGO',
                    "value" => round(floatval($anyMarketOrder['total']), 2),
                    "installments" => (int)$payment['parcela'] ?? 1,
                    "marketplaceId" => null,
                    "paymentMethodNormalized" => null,
                    "paymentDetailNormalized" => null,
                    "dueDate" => $paymentDate->format($dataformat),
                ];
                $anyMarketOrder['payments'][] = $data;
            }
        } else {
            $anyMarketOrder['paymentDate'] = null;
        }

        $anyMarketOrder['deliverStatus'] = null;
        // throw new Exception('Falta colocar aqui os dados da deliverStatus que não faço ideia do que passar');
        $anyMarketOrder['errorMsg'] = null;
        $anyMarketOrder['observation'] = null;
        $anyMarketOrder['accountName'] = null;
        // $whereTemp = [
        //     'integration_id' => $integration['id'],
        //     'id_sku_product' => $product["sku"],
        // ];
        // $tempProduct = $this->CI->model_anymarket_temp_product->getData($whereTemp);
        // if(!$tempProduct){
        //     $whereTemp = [
        //         'integration_id' => $integration['id'],
        //         'id_sku_product' => $product["sku"]."-PRD",
        //     ];
        //     $tempProduct = $this->CI->model_anymarket_temp_product->getData($whereTemp);
        // }
        $credentiais = json_decode($integration["credentials"], true);
        $anyMarketOrder['idAccount'] = $credentiais['idAccount'] ?? $integration['user_id'];
        $anyMarketOrder['marketPlace'] = strtoupper($order['origin']);
        // throw new Exception('Verificar com a anymaret se este é para ir como conectala ou com o campo origin(B2W, Farm,...)');
        $anyMarketOrder['shipmentExceptionDate'] = $order['date_cancel'];
        $anyMarketOrder['shipmentExceptionDescription'] = $order['incidence_message'];
        $anyMarketOrder['metadata'] = null;
        $anyMarketOrder['cancelDetails'] = null;
        // dd($anyMarketOrder, $order['paid_status']);
        return $anyMarketOrder;
    }

    public static function skuCodeNormalize($code, $delimiter = '')
    {
        return trim(
            preg_replace('/[\s]+/', $delimiter,
                preg_replace('/[^A-Za-z0-9-_@]+/', $delimiter,
                    preg_replace('/[&]/', '',
                        preg_replace('/[\']/', '',
                            ParserOrderAnymerket::toASCII($code)
                        )
                    )
                )
            ), $delimiter
        );
    }

    public static function toASCII($string)
    {
        return iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $string
        );
    }

    public function getAnyStatus($paid_status)
    {
        // Erro ao criar o pedido. Somente é possivel criar pedidos com status Pendente ou Pago.
        // modelo de negócio da Conecta Lá, só permite enviar pedidos já pagos.
        return 'PAID';

        if ($paid_status == 1) {
            return 'PENDING';
        }
        if ($paid_status == 3) {
            return 'PAID';
        }
        if ($paid_status == 58) {
            return 'SHIPPED';
        }
        if ($paid_status == 6 || $paid_status == 60) {
            return 'DELIVERED';
        }
        if ($paid_status == 59) {
            return 'CANCELED';
        }
    }
}
