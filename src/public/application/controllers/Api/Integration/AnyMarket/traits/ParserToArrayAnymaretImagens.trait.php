<?php
if (!defined('ParserToArrayAnymaretImagens')) {
    define('ParserToArrayAnymaretImagens', '');
    trait ParserToArrayAnymaretImagens
    {
        private function parserImagensForArray($body)
        {
            $saved_image = [];
            $imagens_product = array();
            $imagens_variant = array();
            if (isset($body['product']['imagesWithoutVariationValue'])) {
                if(method_exists($this,'myEcho')){
                    $this->myEcho("Imagens vinda em imagesWithoutVariationValue:\n".json_encode($body['product']['imagesWithoutVariationValue'])."\n");
                }
                foreach ($body['product']['imagesWithoutVariationValue'] as $img) {
                    if($img['main']){
                        $imagens_product[] = $img['standardUrl'];
                        $saved_image[] = $img['id'];
                    }
                }
                if (isset($body['variations'])) {
                    foreach ($body['product']['imagesWithoutVariationValue'] as $img) {
                        if (!in_array($img['id'], $saved_image)) {
                            $imagens_product[] = $img['standardUrl'];
                            $saved_image[] = $img['id'];
                        }
                    }
                    if (empty($body['product']['imagesWithoutVariationValue'])) {
                        foreach ($body['product']['images'] as $img) {
                            if (!in_array($img['id'], $saved_image)) {
                                $imagens_product[] = $img['standardUrl'];
                                $saved_image[] = $img['id'];
                            }
                        }
                    }
                } else {
                    foreach ($body['product']['imagesWithoutVariationValue'] as $img) {
                        if (!in_array($img['id'], $saved_image)) {
                            $imagens_product[] = $img['standardUrl'];
                            $saved_image[] = $img['id'];
                        }
                    }
                }
            }
            if (isset($body['variations'])) {
                foreach ($body['variations'] as $variant) {
                    $variant['type']['name'] = $variant['type']['name'] == 'cor' ? 'Cor' : $variant['type']['name'];
                    $variant['type']['name'] = $variant['type']['name'] == 'voltagem' ? 'Voltagem' : $variant['type']['name'];
                    $variant['type']['name'] = $variant['type']['name'] == 'tamanho' ? 'Tamanho' : $variant['type']['name'];
                    // if ($variant['type']['name'] == 'Tamanho') {
                    //     $tamanho = $variant;
                    // }
                    // if ($variant['type']['name'] == 'Cor') {
                    //     $cor = $variant;
                    // }
                    // if ($variant['type']['name'] == 'Voltagem') {
                    //     $voltagem = $variant;
                    // }
                    if ($variant['type']['visualVariation']) {
                        if (isset($body['product']['images'])) {
                            if(method_exists($this,'myEcho')){
                                $this->myEcho("Imagens vindas em 'images':".json_encode($body['product']['images'])."\n");
                            }
                            foreach ($body['product']['images'] as $image) {
                                if (isset($image['variation'])) {
                                    if (strtolower($image['variation']) == strtolower($variant['description'])) {
                                        $imagens_variant[] = $image['standardUrl'];
                                    } else {
                                        if (empty($image['variation'])) {
                                            $imagens_product[] = $image['standardUrl'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(method_exists($this,'myEcho')){
                $this->myEcho("Imagens Apos processamento do Produto:\n".json_encode($imagens_product));
                $this->myEcho("Imagens Apos processamento da Variação:\n".json_encode($imagens_variant));
            }
            return array($imagens_product, $imagens_variant);
        }
    }
}
