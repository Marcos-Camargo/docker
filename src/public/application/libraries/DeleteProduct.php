<?php

/**
 * Class DeleteProduct
 * @property \CI_Loader $load
 * @property \Microservices\v1\Integration\Price $ms_price
 * @property \Microservices\v1\Integration\Stock $ms_stock
 * @property Model_products_catalog_associated $model_products_catalog_associated
 */
class DeleteProduct
{

    /**
     * @var Model_products
     */
    private $productModel;

    /**
     * @var Model_orders
     */
    private $ordersModel;

    private $lang;

    public function __construct($args)
    {
        $this->productModel = $args['productModel'];
        $this->ordersModel = $args['ordersModel'] ?? null;
        $this->lang = $args['lang'];

        $this->ms_stock = $args['ms_stock'] ?? null;
        $this->ms_price = $args['ms_price'] ?? null;

        $this->load->model('model_products_catalog_associated');
    }

    /**
     * Método mágico para utilização do CI_Controller
     *
     * @param   string  $var    Propriedade para consulta
     * @return  mixed           Objeto da propriedade
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    public function moveToTrash($products = [], int $company_id = null, int $store_id = null)
    {

        $productsIds = array_column($products, 'id');

        $productsKits = $this->productModel->getKitsByProdsIds($productsIds[0]);
        $productsIds = array_merge($productsIds, array_column($productsKits, 'id'));

        /*
         * ATD-598910
        $productsOrdersOpened = $this->productModel->getProductsByOrderStatus(
            $productsIds,
            Model_orders::getOpenedOrderStatus()
        );

        $productsFromOpenedKits = array_map(function ($item) {
            $ids = explode(',', $item);
            return array_map(function ($id) {
                return (int)trim($id);
            }, $ids);
        }, array_column($productsOrdersOpened, 'prod_ids'));
        $productsFromOpenedKits = array_reduce($productsFromOpenedKits, 'array_merge', []);

        $updatableProds = array_diff($productsIds, array_column($productsOrdersOpened, 'id'), $productsFromOpenedKits);
        */
        $updatableProds = $productsIds;
        $deleted = 0;
        if (!empty($updatableProds)) {
            $criteria['products_ids'] = $updatableProds;
            if (!is_null($company_id)) {
                $criteria['company_id'] = $company_id;
            } else {
                if (($this->data['usercomp'] ?? 1) != 1) {
                    $criteria['company_id'] = $this->data['usercomp'];
                }
            }
            if (!is_null($store_id)) {
                $criteria['store_id'] = $store_id;
            } else {
                if (($this->data['userstore'] ?? 0) != 0) {
                    $criteria['store_id'] = $this->data['userstore'];
                }
            }
            $updatableProds = $this->productModel->getProductsToDisplayByCriteria($criteria, 0, count($updatableProds));
            foreach ($updatableProds as $idx => $prodData) {
                $prod = [
                    'sku' => self::generateTrashSku(self::normalizeTrashSku($prodData['sku'])),
                    'status' => Model_products::DELETED_PRODUCT,
                    'product_id_erp' => null
                ];
                if ($this->productModel->updateProductData($prodData['id'], $prod)) {
                    $deleted++;
                    $this->productModel->log($prodData['id'], $prod, 'Movido para a lixeira.');
                    $variations = $this->productModel->getProductVariants($prodData['id'], '') ?: [];
                    foreach ($variations ?? [] as $k => $variation) {
                        if (!is_numeric($k)) continue;
                        $var = [
                            'id' => $variation['id'],
                            'sku' => self::generateTrashSku(self::normalizeTrashSku($variation['sku'])),
                            'variant_id_erp' => null,
                            'status' => Model_products::DELETED_PRODUCT
                        ];
                        if ($this->productModel->updateVariationData($variation['id'], $prodData['id'], $var)) {
                            $updatableProds[$idx]['variations'][] = array_merge($variation, [
                                'productId' => $prodData['id'],
                                'marketplaces' => $prodData['marketplaces'] ?? ''
                            ]);
                        }
                    }
                    $updatableProds[$idx]['productId'] = $prodData['id'];
                    $this->productModel->movedToTrash($updatableProds[$idx]);
                }
            }
        }

        if (!empty($productsOrdersOpened)) {
            $errs = array_map(function ($item) {
                return sprintf(
                    $this->lang->line('message_product_with_opened_orders'),
                    $item['id'],
                    $item['name'],
                    $item['sku']
                );
            }, $productsOrdersOpened);
            return [
                'errors' => $errs
            ];
        }

        foreach ($productsIds as $product_id) {
            $this->model_products_catalog_associated->removeByProductId($product_id);
        }

        return [
            'total' => count($products),
            'trashed' => $deleted,
            'message' => $this->lang->line('message_products_moved_to_trash')
        ];
    }

    public static function generateTrashSku($sku)
    {
        $time = strtotime("now");
        $rnd = random_int(100, 999); 
        return "DEL_{$sku}_{$time}_{$rnd}";
    }

    public static function normalizeTrashSku($trashSku)
    {
        preg_match('/(?<=DEL_).*(?=_[0-9]{10}(?:_[0-9]{0,3})?$)/', $trashSku, $matches);
        return $matches[0] ?? $trashSku;
    }

    public function setModelsData($data)
    {
        foreach ($data as $field => $value) {
            $this->setModelData($this->productModel, $field, $value);
            $this->setModelData($this->ordersModel, $field, $value);
        }
    }

    protected function setModelData(&$model, $field, $value)
    {
        if (!isset($model->{'data'})) {
            $model->{'data'} = [];
        }
        $model->{'data'} = array_merge($model->{'data'}, [
            $field => $value
        ]);
    }

    protected function removeRecordsFromMS(array $productData = [])
    {
        foreach ($productData['_variations'] ?? [] as $variation) {

        }
    }
}