<?php

class ProductValidation
{
    protected $product_field_required = [
        ['field' => 'id', 'message' => ''],
        ['field' => 'apelido', 'message' => ''],
        ['field' => 'sku', 'message' => ''],
        ['field' => 'descricao_completa', 'message' => ''],
        ['field' => 'peso', 'message' => ''],
        ['field' => 'altura', 'message' => ''],
        ['field' => 'profundidade', 'message' => ''],
        ['field' => 'ncm', 'message' => ''],
        ['field' => 'largura', 'message' => ''],
        ['field' => 'imagem_principal', 'message' => ''],
    ];
    protected $errs = [];
    public static function validate($product, $form_validation)
    {
        echo ("Start validation product\n");
        $validation = new ProductValidation();
        $validation->valid($product);
    }
    private function valid($product)
    {
        foreach ($this->product_field_required as $key) {
            if (!isset($product[$key['field']])) {
                array_push($this->errs, "Campo " . $key['field'] . " é necessario!");
            }
        }
        if (!empty($this->errs)) {
            throw new Exception(json_encode($this->errs,JSON_UNESCAPED_UNICODE));
        }
    }
    public static function configValidator(&$form_validation)
    {
        $config = [
            array(
                'field' => 'id',
                'label' => 'id',
                'rules' => 'required',
            ),
            array(
                'field' => 'apelido',
                'label' => 'apelido',
                'rules' => 'required',
            ),
            array(
                'campo' => 'sku',
                'label' => 'sku',
                'rules' => 'required|is_unique[products.sku]',
            ),
            array(
                'campo' => 'descricao_completa',
                'label' => 'descricao_completa',
                'rules' => 'required',
            ),
            array(
                'campo' => 'peso',
                'label' => 'peso',
                'rules' => 'required',
            ),
            array(
                'campo' => 'altura',
                'label' => 'altura',
                'rules' => 'required',
            ),
            array(
                'campo' => 'profundidade',
                'label' => 'profundidade',
                'rules' => 'required',
            ),
            array(
                'campo' => 'ncm',
                'label' => 'ncm',
                'rules' => 'required',
            ),
            array(
                'campo' => 'largura',
                'label' => 'largura',
                'rules' => 'required',
            ),
            array(
                'campo' => 'imagem_principal',
                'label' => 'imagem_principal',
                'rules' => 'required',
            ),
        ];
        $form_validation->set_rules($config);
    }
}
