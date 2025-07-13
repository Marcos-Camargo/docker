<?php
if (!defined('CheckImageProduct')) {
    define('CheckImageProduct', '');
    trait CheckImageProduct
    {
        private function checkImageProduct($product_saved)
        {
            $this->PASTA_DE_IMAGEM = 'assets/images/product_image';
            if (!empty($product_saved["has_variants"]) && preg_match('/[Cor]/', $product_saved["has_variants"])) {
                $update = [];
                $update['situacao'] = $this->getSituationByImageInProductsVariants($product_saved);
                $update = $this->saveFirstImage($product_saved, $update);
                $this->model_products->update($update, $product_saved['id']);
            }
        }
        private function saveFirstImage($product_saved, $update)
        {
            if ($product_saved['principal_image'] && is_file($product_saved['principal_image'])) {
                return $update;
            }

            $variants = $this->model_products->getVariants($product_saved['id']);
            if (empty($variants)) {
                return $update;
            }

            foreach ($variants as $variant) {
                $path = $this->PASTA_DE_IMAGEM . '/' . $product_saved['image'] . '/' . $variant['image'];
                if (!$product_saved["is_on_bucket"]) {
                    $array_image = array_diff(scandir($path), array('..', '.'));
                    if (!empty($array_image)) {
                        $update['principal_image'] = base_url($path . "/" . current($array_image));
                        break;
                    }
                } else {
                    $CI = &get_instance();
                    $images = $CI->bucket->getFinalObject($path);
                    foreach($images['contents'] as $image){
                        $update['principal_image']=$image['url'];
                        break;
                    }
                }
            }

            return $update;
        }
        private function getSituationByImageInProductsVariants($product)
        {
            $product_id     = $product['id'];
            $product_folder = $product['image'];

            $variants = $this->model_products->getVariants($product_id);
            $path = $this->PASTA_DE_IMAGEM . '/' . $product_folder;

            if (!$product['is_on_bucket']) {
                if (!empty(glob($path . '/*.{jpeg,png,jpg}', GLOB_BRACE))) {
                    return $product['category_id'] !== '[""]' && $product['brand_id'] !== '[""]' ? 2 : 1;
                }
            } else {
                $CI =& get_instance();
                $images = $CI->bucket->getFinalObject($path);
                if($images['success'] && !empty($images['contents'])){
                    foreach($images['contents'] as  $image){
                        $extension  = strtolower(pathinfo($image['url'],PATHINFO_EXTENSION));
                        if(in_array($extension,['jpg','jpeg','png'])){
                            return $product['category_id'] !== '[""]' && $product['brand_id'] !== '[""]' ? 2 : 1;
                        }
                    }
                }
            }
            if (empty($variants)) {
                return 1;
            }
            foreach ($variants as $variant) {
                $path = $this->PASTA_DE_IMAGEM . '/' . $product_folder . '/' . $variant['image'];
                if (!empty(glob($path . '/*.{jpeg,png,jpg}', GLOB_BRACE))) {
                    return $product['category_id'] !== '[""]' && $product['brand_id'] !== '[""]' ? 2 : 1;
                }
            }

            return 1;
        }
    }
}
