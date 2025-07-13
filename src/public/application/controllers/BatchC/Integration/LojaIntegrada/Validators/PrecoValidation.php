<?php
class PrecoValidation{
    protected $preco_field_required = [
        'field' => 'promocional', 'message' => '',
    ];
    protected $errs=[];
    public static function validate($preco){
        echo("Start validation Preço\n");
        $validation=new PrecoValidation();   
        $validation->valid($preco);
    }
    private function valid($preco){
        foreach($this->preco_field_required as $key){
            if(!isset($preco[$key['field']])){
                array_push($this->errs, "Campo " . $key['field'] . " é necessario!");
            }
        }
        if (!empty($this->errs)) {
            throw new Exception(json_encode($this->errs,JSON_UNESCAPED_UNICODE));
        }
    }
}