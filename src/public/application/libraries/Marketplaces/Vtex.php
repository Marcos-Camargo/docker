<?php

namespace Marketplaces;

use DateTime;
use Exception;

require_once "BaseMarketplace.php";
require_once 'system/libraries/Vendor/autoload.php';

class Vtex extends BaseMarketplace
{
    /**
     * Instantiate a new Vtex instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setMapAuthRequest(array(
            'X_VTEX_API_AppKey'     => [
                'field' => 'x-vtex-api-appkey',
                'type'  => 'headers'
            ],
            'X_VTEX_API_AppToken'   => [
                'field' => 'x-vtex-api-apptoken',
                'type'  => 'headers'
            ]
        ));
    }

    /**
     * @param string $order
     * @return mixed
     * @throws Exception
     */
    public function getOrder(string $order)
    {
        try {
            $this->setBaseUri("https://{$this->getCredentials('accountName')}.vtexcommercestable.com.br");
            $request = $this->request('GET', "/api/oms/pvt/orders/$order");
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        return json_decode($request->getBody()->getContents());
    }

    /**
     * @param string $numero_marketplace
     * @return mixed
     * @throws Exception
     */
    public function getPromissoryPaymentMethod(string $numero_marketplace)
    {
        try {
            $this->setBaseUri("https://{$this->getCredentials('accountName')}.vtexcommercestable.com.br");
            $request = $this->request('GET', "/api/do/notes", ['query' => ['target.id' => $numero_marketplace]]);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $notes = json_decode($request->getBody()->getContents(), true);

        if (empty($notes['items'][0]['description'])) {
            throw new Exception("informações de notes não encontrada. ".json_encode($notes));
        }

        $description_note = json_decode($notes['items'][0]['description'], true);

        $description_note['transactionDateTime'] = trim($description_note['transactionDateTime']);
        if (!empty($description_note['transactionDateTime'])) {
            $description_note['transactionDateTime'] = DateTime::createFromFormat("YmdHis", $description_note['transactionDateTime'])->format("Y-m-d H:i:s");
        } else {
            $description_note['transactionDateTime'] = date('Y-m-d H:i:s');
        }

        return $description_note;
    }
}