<?php

require_once APPPATH . "controllers/Api/Integration_v2/anymarket/MainController.php";
require_once APPPATH . "libraries/Integration_v2/Order_v2.php";

use Integration\Integration_v2\Order_v2;

/**
 * Class Order
 * @property Model_anymarket_order_to_update $model_anymarket_order_to_update
 * @property Model_orders $model_orders
 */
class Order extends MainController
{
    /**
     * @var Order_v2
     */
    private $order_v2;

    public function __construct()
    {
        parent::__construct();
        $this->order_v2 = new Order_v2();

        $this->load->model('model_anymarket_order_to_update');
        $this->load->model('model_orders');

        $this->order_v2->setJob(__CLASS__);
    }

    public function index_get($idInMarketplace)
    {
        $this->order_v2->startRun($this->accountIntegration['store_id']);
        $this->order_v2->setToolsOrder();
        try {
            $apiOrder = $this->order_v2->getOrder(((int)$idInMarketplace));
            $parserOrder = $this->order_v2->toolsOrder->parseOrderToIntegration($apiOrder);
            $this->response($parserOrder, REST_Controller::HTTP_OK);
        } catch (Throwable $e) {
            $this->response(['error' => $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateOrderStatusInMarketPlace_put()
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $order = $this->model_orders->getOrdersData(0, $body['idInMarketplace']);
        if (empty($order)) {
            $this->response(null, REST_Controller::HTTP_NOT_FOUND);
            return;
        }
        $data = [
            'company_id' => $this->data['usercomp'] ?? 0,
            'store_id' => $this->data['userstore'] ?? 0,
            'order_anymarket_id' => $body['orderId'] ?? 0,
            'order_id' => $body['idInMarketplace'] ?? 0,
            'old_status' => $body['oldStatus'] ?? 0,
            'new_status' => $body['currentStatus'] ?? 0,
        ];

        $responseData = true;
        $id = $this->model_anymarket_order_to_update->save(0, $data);
        try {
            $this->order_v2->startRun($this->accountIntegration['store_id']);
            $this->order_v2->setToolsOrder();
            $this->order_v2->setUniqueId($data["order_id"]);
            if ($this->order_v2->toolsOrder->updateOrderFromIntegration($data)) {
                $this->model_anymarket_order_to_update->setIntegrated($id);
            }
        } catch (Throwable $e) {
            $responseData = false;
            $this->order_v2->log_integration(
                "Erro ao atualizar pedido {$data['order_id']}",
                "<h4>Não foi possível atualizar o pedido {$data['order_id']}:</h4><p>{$e->getMessage()}</p>",
                "E"
            );
        }
        $this->response($responseData, REST_Controller::HTTP_OK);
    }

    public function afterSaveOrUpdateOrderInAnymarket_put()
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $this->response(true, REST_Controller::HTTP_OK);
    }

    public function initialImportDate_get()
    {
        $dataformat = "Y-m-d\Th:i:sP";
        $integration = $this->accountIntegration;
        $credentials = json_decode($integration['credentials'], true);
        $createAt = new DateTime($credentials['inicial_date_order'], new DateTimeZone("America/Sao_Paulo"));
        $createAt_formated = $createAt->format($dataformat);
        $inicaldate = ['Date' => isset($credentials['inicial_date_order']) ? $createAt_formated : null];
        $this->response($inicaldate, REST_Controller::HTTP_OK);
    }


    public function printTag_post(){

        $url = current_url();

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['path'])) {
            // Divide o caminho (path) em partes usando a barra ("/") como delimitador
            $pathParts = explode('/', $parsedUrl['path']);

            // Obtém o último elemento do array, que deve ser "ZPL" ou "PDF"
            $format = end($pathParts);

            // Verifica se o formato é "ZPL" ou "PDF"
            if ($format != "ZPL" && $format != "PDF") {
                // Faça o que você precisa com $format (ZPL ou PDF)
                $this->response(['error' => 'Formato inválido. Use ZPL ou PDF.'], REST_Controller::HTTP_BAD_REQUEST);
                return;
            } 
        }

        $oiUrl = $this->input->get('oi');
        $idAccountUrl = $this->input->get('idAccount');

        //dados do bd para comparar com o oi e idAccount informados na url
        $dataIntegrationBase = $this->accountIntegration;
        $credentials = json_decode($dataIntegrationBase['credentials'], true);

        //limpando para tirar o ponto, na url o "oi" é informado sem o ponto
        $dataOiBase =  str_replace(".","",$credentials['oi']);
        $dataIdAccountBase =  $credentials['idAccount'];


        /* // Verifique se ambos os campos estão vazios
        if (empty($oiUrl)) {
            $this->response(['error' => 'O valor do campo "oi" é obrigatório.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (empty($idAccountUrl)) {
            $this->response(['error' => 'O valor do campo "idAccount" é obrigatório.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        //verficando se o "oi" e o "idAccount" informado é o mesmo do token anymarket informado
        if($oiUrl != $dataOiBase){
            $this->response(['error' => 'O valor do campo "oi" não confere com o token informado.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if($idAccountUrl != $dataIdAccountBase){
            $this->response(['error' => 'O valor do campo "idAccount" não confere com o token informado.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        } */

        $requestBody  = json_decode(file_get_contents('php://input'), true);    

        // Verifique se nao esta vazio e se é um array
        if (!empty($requestBody) && is_array($requestBody)) {
         
            $idArray = $requestBody;
            $orderResults = [];

            foreach ($idArray as $id) {
                $order = $this->model_orders->getOrdersDataCanbePrint(0, $id);
                if (!empty($order)) {
                    $orderResults[] = $order;
                }
            }

            if (!empty($orderResults)) {
                
                $outputFiles = printTag($orderResults, $format, $idAccountUrl);

                $zipFileName = "etiquetas.zip";
                $zipPath = FCPATH . "assets/images/tmp/" . $zipFileName;

                if(file_exists($zipPath)){
                    unlink($zipPath);
                }

                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                    // Adicione os arquivos PDF ao arquivo zip
                    foreach ($outputFiles as $pdfFileName) {
                        $pdfPath = FCPATH . "assets/images/etiquetas/" . $pdfFileName;
                        $zip->addFile($pdfPath, $pdfFileName);
                    }
                    $zip->close();

                    // Envie o arquivo zip como resposta
                    header('Content-Type: application/octet-stream');
                    header("Content-Disposition: attachment; filename=$zipFileName");
                    readfile($zipPath);
                } else {
                    // Falha ao criar o arquivo zip
                    $this->response(['error' => 'Falha ao criar o arquivo zip.'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }

            } else {
                // Nenhum pedido encontrado para os IDs fornecidos
                $this->response(['error' => 'Nenhum dos pedidos foi encontrado.'], REST_Controller::HTTP_NOT_FOUND);
                return;
            }
        } else {
            // Chave "idInMarketplace" não encontrada ou não é um array no corpo da solicitação
            $this->response(['error' => 'O body não tem um array ou ele esta vazio.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

    }
}
