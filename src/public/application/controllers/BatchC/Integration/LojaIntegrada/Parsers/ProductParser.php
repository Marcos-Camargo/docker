<?php

class ProductParser
{
    /**
     * Alguns produtos na loja integrada podem vir com estoque não gerenciado pela plantaforma o que é
     * denotado como estoque infinito, deste modo foi inserido este valor, de 500 para produtos com estoque 
     * infinitos antes de enviar a persistencia na api conecta-lá
     */
    const QTY_TO_ILIMITED_STOCK = 500;
    const VARIATION_PARSER_TYPE = ['cor' => 'color', 'tamanho' => 'size'];
    public static function productIn($loja_product, $loja_preco, $loja_estoque)
    {
        $images = [];
        array_push($images, $loja_product["imagem_principal"]["grande"]);
        foreach ($loja_product["imagens"] as $image) {
            array_push($images, $image["grande"]);
        }
        $types_variations = [];
        $product_variations = [];
        $maior_preco = null;
        if (!empty($loja_product["variacoes"])) {
            $loja_product['peso'] = $loja_product["variacoes"][0]['peso'];
            $loja_product['altura'] = $loja_product["variacoes"][0]['altura'];
            $loja_product['largura'] = $loja_product["variacoes"][0]['largura'];
            $loja_product['profundidade'] = $loja_product["variacoes"][0]['profundidade'];
            foreach ($loja_product["variacoes"] as $variation) {
                if ($maior_preco === null) {
                    $maior_preco = $variation['preco'];
                } else {
                    echo (json_encode($maior_preco) . "\n");
                    // echo(json_encode($maior_preco['preco'])."\n");
                    echo (json_encode($variation['preco']) . "\n");
                    if (!$variation["estoque"]["gerenciado"]) {
                        $variation["estoque"]["quantidade"] = self::QTY_TO_ILIMITED_STOCK;
                        $variation["estoque"]["quantidade_disponivel"] = self::QTY_TO_ILIMITED_STOCK;
                    }
                    if (isset($variation['preco']["promocional"]) && $variation['preco']["promocional"] > 0) {
                        if (
                            isset($variation['preco']) &&
                            $variation['preco']["promocional"] > $maior_preco["promocional"]
                        ) {
                            $maior_preco["promocional"] = $variation['preco']["promocional"];
                        }
                    } else {
                        if (
                            isset($variation['preco']) &&
                            $variation['preco']["cheio"] > $maior_preco["cheio"]
                        ) {
                            $maior_preco["cheio"] = $variation['preco']["cheio"];
                        }
                    }
                }
                $images_var = [];
                if (isset($variation["imagem_principal"])) {
                    array_push($images_var, $variation["imagem_principal"]["grande"]);
                }
                foreach ($variation["imagens"] as $image) {
                    array_push($images_var, $image["grande"]);
                }
                $product_variation = [
                    'sku' => $variation["sku"],
                    'qty' => $variation["estoque"]["quantidade_disponivel"],
                    'price' => isset($variation["preco"]["promocional"]) ? $variation["preco"]["promocional"] : $variation["preco"]["cheio"],
                    'list_price' => $variation["preco"]["cheio"],
                    // 'EAN'=>'',
                    'images' => $images_var,
                ];
                foreach ($variation["variacoes"] as $variation_att) {
                    if (isset(self::VARIATION_PARSER_TYPE[strtolower($variation_att['tipo'])])) {
                        if (!in_array(self::VARIATION_PARSER_TYPE[strtolower($variation_att['tipo'])], $types_variations)) {
                            array_push($types_variations, self::VARIATION_PARSER_TYPE[strtolower($variation_att['tipo'])]);
                        }
                        $product_variation[self::VARIATION_PARSER_TYPE[strtolower($variation_att['tipo'])]] = $variation_att["nome"];
                    }
                }
                $product_variations[] = $product_variation;
            }
        }
        $conecta_prod = [
            // "product_id_erp" => $loja_product["id"],
            "name" => $loja_product["nome"],
            "sku" => $loja_product["sku"],
            "active" => $loja_product["ativo"] && !$loja_product["removido"] ? "enabled" : "disabled",
            "description" => $loja_product["descricao_completa"],
            "price" => $loja_preco["promocional"] > 0 ? $loja_preco["promocional"] : (
                $loja_preco["cheio"] > 0 ? $loja_preco["cheio"] : (
                    $maior_preco["promocional"] > 0 ? $maior_preco["promocional"] : (
                    $maior_preco["cheio"] > 0 ? $maior_preco["cheio"] : current($product_variations)['price']
                    )
                )
            ),
            "list_price" => $loja_preco["cheio"] > 0 ? $loja_preco["cheio"] : (
                $maior_preco["cheio"] > 0 ? $maior_preco["cheio"] : current($product_variations)['list_price']
            ),
            "qty" => $loja_estoque["quantidade_disponivel"],
            "ean" => $loja_product["gtin"],
            "net_weight" => $loja_product["peso"],
            "gross_weight" => $loja_product["peso"],
            "width" => $loja_product["largura"],
            "height" => $loja_product["altura"],
            "depth" => $loja_product["profundidade"],
            "guarantee" => 0,
            "origin" => 0, //Falta definir
            "sku_manufacturer" => "",
            "unity" => 'UN',
            "ncm" => $loja_product["ncm"],
            "manufacturer" => $loja_product["marca"]['nome'] ?? null,
            "extra_operating_time" => $loja_estoque["situacao_em_estoque"] ?? 0,
            "types_variations" => $types_variations,
            "product_variations" => $product_variations,
            "images" => $images,
            "product_width" => $loja_product["largura"],
            "product_height" => $loja_product["altura"],
            "product_depth" => $loja_product["profundidade"],
        ];
        return $conecta_prod;
    }
}
