<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_DB_driver $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Security $security
 * @property CI_Output $output
 *
 * @property Model_settings $model_settings
 * @property Model_collections $model_collections
 * @property Model_products $model_products
 * @property Model_integrations $model_integrations
 */

class Collections extends API
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
        $this->load->model('model_collections');
        $this->load->model('model_products');
        $this->load->model('model_integrations');

        if (!$this->model_settings->getValueIfAtiveByName('collection_occ')) {
            $this->response($this->lang->line('api_unauthorized_request'), REST_Controller::HTTP_UNAUTHORIZED);
            die;
        }
    }

    /**
     * Recupera todas as navegações.
     *
     * @return void|NULL
     */
    public function all_get()
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $filters = $this->getFilterAllCollections($this->cleanGet());

        $collections = $this->model_collections->getActiveCollectionsByFilter(array('like' => $filters));

        $response = array();
        foreach ($collections as $collection) {
            $response[] = array(
                'id' => $collection['id'],
                'external_id' => $collection['mktp_id'],
                'name' => $collection['name'],
                'description' => $collection['long_description']
            );
        }

        return $this->response(array(
            "success" => !empty($response),
            "collections" => $response
        ), empty($response) ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_OK);
    }

    /**
     * Recupera a navegação pelo código.
     *
     */
    public function index_get(int $id)
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit(false);
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }
        $collection = $this->model_collections->getCollectionData($id);

        if (empty($collection) || !$collection['active']) {
            return $this->response(array(
                "success" => false,
                "message" => sprintf($this->lang->line('api_collection_not_found'), $id)
            ), REST_Controller::HTTP_NOT_FOUND);
        }

        $response['id'] = $collection['id'];
        $response['external_id'] = $collection['mktp_id'];
        $response['name'] = $collection['name'];
        $response['description'] = $collection['long_description'];

        return $this->response(array(
            "success" => true,
            "collection" => $response
        ), REST_Controller::HTTP_OK);
    }

    /**
     * Recupera as navegações pelo código por produto.
     *
     */
    public function product_get(string $sku)
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $product = $this->model_products->getProductBySkuAndStore($sku, $this->store_id);

        $collections = $this->model_collections->getProductCollectionByProductId($product['id']);

        $response = array();
        foreach ($collections as $collection) {
            $response[] = (int)$collection['collection_id'];
        }

        return $this->response(array(
            "success" => !empty($response),
            "collection" => $response
        ), empty($response) ? REST_Controller::HTTP_NOT_FOUND : REST_Controller::HTTP_OK);
    }

    public function product_post(string $sku, string $marketplace, $externalId)
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if (!$verifyInit[0]) {
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);
        }

        try {
            // Recupera dados enviado pelo body
            $data = json_decode($this->input->raw_input_stream);

            if (!property_exists($data, 'collections') || !is_array($data->collections)) {
                throw new Exception("A propriedade collections deve ser informada e deve ser um vetor.");
            }

            // marketplace não encontrado.
            if (!$this->model_integrations->getIntegrationbyStoreIdAndInto(0, $marketplace)) {
                throw new Exception("Makretplace '$marketplace' não localizado.");
            }

            
            if($externalId == 'externalId'){
                $externalId = true;
            }else{
                $externalId = false;
            }
            $product = $this->model_products->getProductBySkuAndStore($sku, $this->store_id);
            $this->validateCollections($data->collections,$externalId);
            $this->model_collections->removeProductCollections($product['id']);

            foreach ($data->collections as $collection) {
                if($externalId){
                    $dataCollection = $this->model_collections->getCollectionByMktpId($collection);
                }else{
                    $dataCollection = $this->model_collections->getCollectionData($collection);
                }                

                $this->model_collections->createProductCollection(array(
                    'collection_id'         => $dataCollection['id'],
                    'mktp_collection_id'    => $dataCollection['mktp_id'],
                    'date'                  => dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL),
                    'user'                  => $this->user_id,
                    'product_id'            => $product['id']
                ));
            }
        } catch (Exception | Error $exception) {
            return $this->response(array(
                "success" => false,
                "message" => $exception->getMessage()
            ), REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response(array(
            "success" => true,
            "message" => $this->lang->line('api_collection_updated_successfully')
        ), REST_Controller::HTTP_OK);
    }

    /**
     * Valida se as navegações existem e se estão válidas.
     *
     * @param   array   $collections
     * @return  void
     * @throws  Exception
     */
    private function validateCollections(array $collections, $externalId = false)
    {
        foreach ($collections as $collection) {

            // if (!is_numeric($collection)) {
            //     throw new Exception(sprintf($this->lang->line('api_collection_must_be_numeric'), $collection));
            // }

            if($externalId){
                $dataCollection = $this->model_collections->getCollectionByMktpId($collection);
            }else{
                $dataCollection = $this->model_collections->getCollectionData($collection);
            }
            
            if (!$dataCollection) {
                throw new Exception(sprintf($this->lang->line('api_collection_not_found'), $collection));
            }

            if (!$dataCollection['active']) {
                throw new Exception(sprintf($this->lang->line('api_collection_not_found'), $collection));
            }
        }
    }

    /**
     * Cria um vetor de filtros, na listagem de todas as navegações.
     *
     * @param array $searchFilter
     * @return array
     */
    private function getFilterAllCollections(array $searchFilter): array
    {
        $filtersAvailable = array(
            'long_description' => 'description',
            'name' => 'name'
        );
        $filters = array();

        foreach ($filtersAvailable as $friendlyFilter => $filter) {
            if (key_exists($filter, $searchFilter)) {
                $filters[$friendlyFilter] = $searchFilter[$filter];
            }
        }

        return $filters;
    }
}
