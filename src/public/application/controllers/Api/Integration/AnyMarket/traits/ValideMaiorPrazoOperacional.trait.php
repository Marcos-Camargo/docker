<?php
if (!defined('ValideMaiorPrazoOperacional')) {
    define('ValideMaiorPrazoOperacional', '');
    trait ValideMaiorPrazoOperacional
    {
        public function getAllAndSaveMaxPrazoOperacional($product_id_erp)
        {
            $product = $this->model_products->getByProductIdErp($product_id_erp);
            $variants = $this->model_products->getVariantsByProd_id($product['id']);
            $additionalsTime = [];
            foreach ($variants as $variant) {
                $url = $this->url_anymerket . "skus/id/" . $variant['variant_id_erp'];
                // echo ($url . "\n");
                $result = $this->sendREST($url);
                $temp_product = json_decode($result["content"], true);
                $additionalsTime[] = isset($temp_product["additionalTime"]) ? $temp_product["additionalTime"] : 0;
            }
            if (count($additionalsTime) == 0) {
                return false;
            }
            $updateData = ['prazo_operacional_extra' => $this->myMax($additionalsTime)];
            // echo ("Atualizando prazo operacional com: " . json_encode($updateData) . "\n");
            if ($updateData['prazo_operacional_extra'] != intval($product['prazo_operacional_extra'])) {
                return $this->model_products->update($updateData, $product['id'], "Atualizando para maior prazo operacional.");
            }
            return false;
        }
        private function myMax($additionalsTime)
        {
            if (count($additionalsTime) == 1) {
                return $additionalsTime[0];
            } else {
                return max($additionalsTime);
            }
        }
    }
}
