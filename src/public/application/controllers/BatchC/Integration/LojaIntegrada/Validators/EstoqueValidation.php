<?php
class EstoqueValidation
{
    protected $estoque_field_required = [
        'field' => 'quantidade_disponivel', 'message' => '',
    ];
    protected $errs = [];
    public static function validate($estoque)
    {
        echo ("Start validation Estoque\n");
        $validation = new EstoqueValidation();
        $validation->valid($estoque);
    }
    private function valid($estoque)
    {
        foreach ($this->estoque_field_required as $key) {
            if (!isset($estoque[$key['field']])) {
                // throw new Exception("Campo ".$key['field']." é necessario!");
                array_push($this->errs, "Campo " . $key['field'] . " é necessario!");
            }
        }
        if (!empty($this->errs)) {
            throw new Exception(json_encode($this->errs, JSON_UNESCAPED_UNICODE));
        }
    }
}
