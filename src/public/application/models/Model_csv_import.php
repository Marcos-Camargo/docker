<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

 */
class Model_csv_import extends CI_Model
{
    public $err = false;
    public $infos_err = array();
    public function __construct()
    {
        parent::__construct();
    }
    public function __destruct()
    {
    }
    public function begin_transation()
    {
        $this->err = false;
        $this->db->trans_begin();
        echo ("## Iniciando transaction\n");
    }
    public function finish_transation()
    {
        if ($this->err) {
            echo ('## Deu algum erro.Iniciando Rollback - {$this->err}\n');
            $this->db->trans_rollback();
        } else {
            echo ("## Commitando transaction\n");
            $this->db->trans_commit();
        }
    }
    public function thereWasAnError($info_err)
    {
        echo ("## Existe um erro no produto.\n");
        $this->err = true;
        array_push($this->infos_err, $info_err);
    }
}
