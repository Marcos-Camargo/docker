<?php
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class Trash
 * @property Model_products $model_products
 */
class Trash extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_trash');
        $this->data['usercomp'] = $this->session->userdata('usercomp');
        $this->data['userstore'] = $this->session->userdata('userstore');

        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_products');
    }

    public function index()
    {
        if (!in_array('viewTrash', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['stores'] = [];
        if (in_array('viewStore', $this->permission)) {
            $this->data['stores'] = $this->model_stores->getActiveStore();
        }

        $this->render_template('trash/index', $this->data);
    }

    public function fetchProductData()
    {
        if (!in_array('viewTrash', $this->permission)) {
            echo json_encode(['error' => $this->lang->line('application_dont_permission')]);
            return;
        }

        $params = $this->postClean(NULL, TRUE);

        $limit = $params['length'] > 0 ? $params['length'] : 20;
        $offset = $params['start'] >= 0 ? $params['start'] : 0;
        if (isset($params['search']) && is_array($params['search'])) {
            $params['search'] = current(array_values($params['search']));
        }

        $params['status'] = Model_products::DELETED_PRODUCT;

        if ((isset($this->data['usercomp']) && $this->data['usercomp'] != 1)) {
            $params['company_id'] = $this->data['usercomp'];
        }
        if ((isset($this->data['userstore']) && $this->data['userstore'] != 0)) {
            $params['store_id'] = $this->data['userstore'];
        }

        $nroRegisters = $this->model_products->countGetProductsByCriteria($params);
        $prodResults = $this->model_products->getProductsToDisplayByCriteria($params, $offset, $limit);
        $products = array_map(function ($prod) {
            $mktplaces = !empty($prod['marketplaces']) ? explode(',', $prod['marketplaces']) : [];
            $prod['marketplaces'] = '';
            $copyButton = '';
            $deleteButton = '';
            if (!empty($mktplaces)) {
                $checkMktpl = [];
                foreach ($mktplaces as $mktplace) {
                    if (!in_array($mktplace, $checkMktpl)) {
                        $checkMktpl[] = $mktplace;
                        $prod['marketplaces'] .= "<span class=\"label label-default\">{$mktplace}</span>&nbsp;";
                    }
                }
            } else {
                if (in_array('deleteProdTrash', $this->permission)) {
                    $deleteButton = "<button 
                    class='btn btn-danger btn-del-product'
                    data-toggle='tooltip'
                    data-placement='top'
                    data-product-id='{$prod['id']}'
                    data-type='delete'
                    data-view='product'
                    title='{$this->lang->line('application_permanently_delete_product')}'
                    >
                    <i class='fa fa-trash-o'></i>
                    </button>";
                }
            }

            if ((!$prod['is_kit'] && !$prod['product_catalog_id'])
                && in_array('cloneProdTrash', $this->permission)) {
                $copyButton = "<button class='btn btn-default btn-cp-product'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-product-id='{$prod['id']}'
                        title='{$this->lang->line('application_create_copy_product')}'
                    >
                        <i class='far fa-copy'></i>
                    </button>";
            } else {
                if($prod['is_kit']) {
                    $prod['name'] = "{$prod['name']}<br><span class=\"label label-warning\">Kit</span>";
                } else if ($prod['product_catalog_id']) {
                    $prod['name'] = "{$prod['name']}<br><span class=\"label label-primary\">{$this->lang->line('application_catalog')}</span>";
                }
            }

            $viewButton = "<button class='btn btn-default btn-view-product'
                        data-toggle='tooltip'
                        data-placement='top'
                        data-product-id='{$prod['id']}'
                        title='{$this->lang->line('application_view_product')}'
                    >
                        <i class='far fa-eye'></i>
                    </button>";

            $prod['actions'] = "<div class=''>
                <div class='input-group product-actions'>
                    {$viewButton}
                    {$copyButton}
                    {$deleteButton}
                </div>
            </div>";

            if ((!is_null($prod['principal_image'])) && !empty(trim($prod['principal_image']))) {
                $prod['image'] = '<img src="' . $prod['principal_image'] . '" alt="' . utf8_encode(substr($prod['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $prod['image'] = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($prod['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }

            return $prod;
        }, $prodResults);

        $totalPages = $nroRegisters > 0 ? (int)($nroRegisters / (int)$params['length']) : 0;
        $page = (int)($params['start'] > 0 ? ((int)$params['length'] / (int)($params['start'])) : 0);
        $return = [
            'draw' => (int)$params['draw'],
            'data' => $products,
            'pagination' => [
                'page' => ceil($page),
                'per_page' => (int)$params['length'],
                'total_pages' => $totalPages,
                'filtered_items' => $nroRegisters,
                'total_items' => $nroRegisters,
            ],
            'recordsTotal' => $nroRegisters,
            'recordsFiltered' => $nroRegisters,
        ];
        echo json_encode($return);
    }

    public function view($prodId)
    {
        if (!in_array('viewTrash', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $productData = $this->model_products->verifyProductsOfStore($prodId);
        if (!$productData) {
            redirect('dashboard', 'refresh');
        }

        if ($productData['is_kit']) {
            redirect('productsKit/view/' . $prodId, 'refresh');
            return;
        }

        if (!is_null($productData['product_catalog_id'])) {
            redirect('catalogProducts/viewFromCatalog/' . $prodId, 'refresh');
            return;
        }

        redirect('products/view/' . $prodId, 'refresh');
    }

    public function copy($prodId)
    {
        if (!in_array('cloneProdTrash', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $productData = $this->model_products->verifyProductsOfStore($prodId);
        if (!$productData) {
            redirect('dashboard', 'refresh');
        }

        if ($productData['is_kit']) {
            redirect('dashboard', 'refresh');
            return;
        }

        if (!is_null($productData['product_catalog_id'])) {
            redirect('dashboard', 'refresh');
            //redirect('catalogProducts/copy/' . $prodId, 'refresh');
            return;
        }

        redirect('products/copy/' . $prodId, 'refresh');
    }

    public function deletePermanently($prodId)
    {
        if (!in_array('deleteProdTrash', $this->permission)) {
            echo json_encode([
                'errors' => [$this->lang->line('application_dont_permission')]
            ]);
            return;
        }

        $criteria['product_id'] = $prodId;
        $criteria['status'] = Model_products::DELETED_PRODUCT;
        $criteria['synchronized'] = Model_products::NOT_SYNCED_MKTPLACE;
        if (($this->data['usercomp'] ?? 1) != 1) {
            $criteria['company_id'] = $this->data['usercomp'];
        }
        if (($this->data['userstore'] ?? 0) != 0) {
            $criteria['store_id'] = $this->data['userstore'];
        }
        $prodResults = $this->model_products->getProductsToDisplayByCriteria($criteria); // validate prod deletable
        if (!empty($prodResults)) {
            $productsIds = array_column($prodResults, 'id');

            $productsKits = $this->model_products->getKitsByProdsIds($productsIds[0]);
            $productsIds = array_merge($productsIds, array_column($productsKits, 'id'));

            $productsOrdersOpened = $this->model_products->getProductsByOrderStatus(
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

            $deletableProds = array_diff($productsIds, array_column($productsOrdersOpened, 'id'), $productsFromOpenedKits);
            unset($criteria['product_id']);
            $criteria['products_ids'] = $deletableProds;
            $deletableProds = $this->model_products->getProductsToDisplayByCriteria($criteria, 0, count($deletableProds));
            $deleted = 0;
            foreach ($deletableProds as $idx => $prodData) {
                if ($this->model_products->remove($prodData['id'])) {
                    $variations = $this->model_products->getProductVariants($prodData['id'], '') ?: [];
                    foreach ($variations ?? [] as $k => $variation) {
                        if (!is_numeric($k)) continue;
                        $deletableProds[$idx]['variations'][] = array_merge($variation, [
                            'productId' => $prodData['id'],
                            'marketplaces' => $prodData['marketplaces'] ?? ''
                        ]);
                    }
                    $deletableProds[$idx]['productId'] = $prodData['id'];
                    $this->model_products->deletedFromTrash($deletableProds[$idx]);
                    $deleted++;
                }
            }
            if ($deleted > 0) {
                echo json_encode([
                    'messages' => [$this->lang->line('message_permanently_deleted_product')]
                ]);
                return;
            }
        }
        header("HTTP/1.1 420");
        echo json_encode([
            'errors' => [$this->lang->line('message_product_not_deletable')]
        ]);
        return;
    }
}
