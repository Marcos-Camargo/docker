<?php

require APPPATH . "controllers/Api/V1/API.php";

class Infos extends API
{
    public function index_get($code = null)
    {
        // Verificação inicial
        $verifyInit = $this->verifyInit();
        if(!$verifyInit[0])
            return $this->response($verifyInit[1], REST_Controller::HTTP_UNAUTHORIZED);

        $result = $this->createArrayInfo();

        $this->response(array('success' => true, 'result' => $result), REST_Controller::HTTP_OK);
    }

    private function createArrayInfo()
    {
        $info = $this->getDataStore();

        return array(
            'code'           => $info->id,
            'fantasy_name'   => $info->name,
            'company_name'   => $info->raz_social,
            'identification' => preg_replace("/[^0-9]/", "", $info->CNPJ),
            'create_at'      => $info->create_at
        );
    }
}