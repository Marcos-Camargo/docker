<?php
/*
 
Realiza a integração no via varejo

*/   
class ViaAttributes {
    public function __construct() 
    { 
    }

    public function linkAttributes($attributes_conectala, $attributes_via)
    {
        $attributes = array();
        $variants = array();
        foreach($attributes_conectala as $attr_conectala)
        {
            foreach ($attributes_via as $attr_via) {
                if ($attr_conectala['id_atributo'] == $attr_via["udaId"]){
                    if ($attr_via["isVariant"] != "Y") {
                        $arr = array (
                            "idUda" => $attr_conectala['id_atributo'],
                            "valor" => $attr_conectala['valor']
                        );
                        array_push($attributes, $arr);   
                    }
                }
            }
        }
        return array(
            'attributes' => $attributes,
            'variants' => $variants
        );
    }

    public function linkVariants($variants_conectala, $attributes_via) 
    {
        $variants = array();
        
        foreach($variants_conectala as $variant)
        {
            $selecione = '';
            $attributes = array();
            foreach ($attributes_via as $attr_via)
            {
                if  ($attr_via["isVariant"] == "Y") {
                    if (array_key_exists('voltage', $variant)) {
                        $selecione = ($selecione != "" ? '; ' : '') . 'Voltagem: ' . $variant['voltage'];
                        if ($this->compare(trim('VOLTAGE'), trim($attr_via['udaName'])))
                        {
                            $arr = array (
                                "idUda" => $attr_via['udaId'],
                                "valor" => $variant['voltage']
                            );
                            array_push($attributes, $arr);
                        }
                    }
                    
                    if (array_key_exists('color', $variant)) {
                        $selecione = ($selecione != "" ? ', ' : '') . 'Cor: ' . $variant['color'];
                        if ($this->compare(trim('COLOR'), trim($attr_via['udaName'])))
                        {
                            $arr = array (
                                "idUda" => $attr_via['udaId'],
                                "valor" => $variant['color']
                            );
                            array_push($attributes, $arr);
                        }
                    }
                    
                    if (array_key_exists('size', $variant)) {
                        $selecione = ($selecione != "" ? ', ' : '') . 'Tamanho: ' . $variant['size'];
                        if ($this->compare(trim('SIZE'), trim($attr_via['udaName'])))
                        {
                            $arr = array (
                                "idUda" => $attr_via['udaId'],
                                "valor" => $variant['size']
                            );
                            array_push($attributes, $arr);
                        }
                    }
                }
            }

            if (count($attributes) == 0) {
                foreach ($attributes_via as $attr_via)
                {
                    if  ($attr_via["isVariant"] == "Y") {
                        if ($this->compare(trim('SELECIONE'), trim($attr_via['udaName'])))
                        {
                            $arr = array (
                                "idUda" => $attr_via['udaId'],
                                "valor" => $selecione
                            );
                            array_push($attributes, $arr);
                        }
                    }
                }
            }

            $variant['attributes'] = $attributes;
            array_push($variants, $variant);
        }
        
        return $variants;
    }

    public function hasVariant($variant_name, $attributes_via)
    {
        $has = false;
        foreach ($attributes_via as $attr_via)
        {
            if ($this->compare(trim($variant_name), trim($attr_via['udaName']))) {
                $has = true;
            }
            else {
                if ($this->compare(trim('SELECIONE'), trim($attr_via['udaName']))) {
                    $has = true;
                }
            }
        }
        return $has;
    }

    public function getWarranty($product, $attributes_via) {
        foreach ($attributes_via as $attr_via) {
            if ($attr_via['udaName'] == 'Garantia') {
                if (($attr_via['isVariant'] === 'N')) {
                    if ((int)$product['garantia'] > 0) {
                        return array(
                            'id_product' => $product['id'],
                            'id_atributo' => $attr_via['udaId'],
                            "valor" => $product['garantia'],
                            'int_to' => 'VIA'    
                        );
                    }
                }
            }
        }
        return false;
    }

    private function compare($name, $name_mkt)
    {
        $arr_attributes = array();
        switch ($name) {
            case 'COLOR':
            case 'COR':
                $arr_attributes = $this->colors;
                break;
            case 'VOLTAGEM':
            case 'VOLTAGE':
                $arr_attributes = $this->voltages;
                break;
            case 'MODEL':
                $arr_attributes = $this->models;
                break;
            case 'TAMANHO':
            case 'SIZE':
                $arr_attributes = $this->sizes;
                break;
            case 'SELECIONE':
                $arr_attributes = $this->selecione;
                break;
            default:
                break;
        }
        $result = in_array(strtoupper($name_mkt), $arr_attributes);        
        
        return $result;
    }

    private $colors = array('COR');

    private $voltages = array('VOLTAGEM', 'TENSãO/VOLTAGEM');

    private $models = array('MODELO');

    private $sizes = array('TAMANHO');

    private $selecione = array('SELECIONE');

}