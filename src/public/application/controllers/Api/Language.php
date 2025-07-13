<?php

require APPPATH . "/libraries/REST_Controller.php";

class Language extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index_get($text, $language = null)
    {
        if (!$text)
            return $this->response(array('success' => false, 'text' => ''), REST_Controller::HTTP_NOT_FOUND);

        if ($language === null) $language = $this->input->cookie('swlanguage') ?? 'portuguese_br';

        $this->lang->load('application', $language, FALSE, TRUE, __DIR__."/../../");


        $line = $this->lang->line($text);

        if ($line === false)
            return $this->response(array('success' => false, 'text' => ''), REST_Controller::HTTP_NOT_FOUND);

        $this->response(array('success' => true, 'text' => $line), REST_Controller::HTTP_OK);
    }
}