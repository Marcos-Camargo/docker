<?php

use phpDocumentor\Reflection\Types\Integer;

defined('BASEPATH') or exit('No direct script access allowed');

class BlacklistOfWords
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('model_blacklist_words');
        $this->CI->load->model('model_whitelist');
        $this->CI->load->model('model_stores');
        $this->CI->load->model('model_products');
        $this->CI->load->model('model_phases');
        $this->CI->load->model('model_integrations');
    }

    /**
     * Recupera comissão e seller index da loja
     *
     * @param  int   $storeId Código da loja
     * @return array          Retorna um array com comissão e seller index
     */
    public function getCommissionAndSellerIndexStore(int $storeId): array
    {
        $dataStore = $this->CI->model_stores->getStoresData($storeId);

        if (!$dataStore) {
            return [
                'comission'     => null,
                'seller_index'  => null
            ];
        }

        $sellerIndex = $this->CI->model_stores->getSellerIndex(['store_id' => $storeId]);
        $index = $sellerIndex ? $sellerIndex[0]['seller_index'] : 0;

        return [
            'comission'     => $dataStore['service_charge_value'],
            'seller_index'  => $index
        ];
    }

    /**
     * Recuperar situação do produto, se deve ou não se bloqueado
     *
     * @param  array        $request                Dados do produto
     * @param  int|null     $prd_id                 Código do produto
     * @param  array|null   $permissionRules        Regra de permissão, se não informado ou array vázio, será recuperada todas
     * @param  array|null   $blockingRules          Regra de bloqueio, se não informado ou array vázio, será recuperada todas
     * @param  bool         $checkMarketplace       Filtra ou não nos marketplaces
     * @return array                                Retornar se o produto foi ou não bloqueado, se bloqueado será informado por qual regra
     */
    public function getBlockProduct(array $request, int $prd_id = null, $permissionRules = null, $blockingRules = null, $checkMarketplace = false): array
    {
        if ($prd_id !== null && !isset($request['id'])) $request['id'] = $prd_id;
        if ($prd_id === null && isset($request['id'])) $prd_id = $request['id'];

        $product = $this->getProductForCheck($request);
		$product_integrated = ($this->CI->model_integrations->countProductIntegrations($prd_id) > 0);

        $whiteList = array();

        if ($permissionRules !== null) {
            if (!$checkMarketplace) {

                $permissionRulesChunk = array_chunk($permissionRules, 50);
                foreach ($permissionRulesChunk as $permissionRuleChunk) {
                    $rulesId = array_map(function ($permission) {
                        return $permission['id'];
                    }, $permissionRuleChunk);

                    if (!empty($rulesId)) {
                        $whiteList = array_merge($whiteList, $this->CI->model_whitelist->searchWhitelistAndIds($product, $rulesId, $product_integrated));
                    }
                }
            } else {
                $whiteList = $permissionRules;
            }
        } else {
            $whiteList = $this->CI->model_whitelist->searchWhitelist($product, $product_integrated);
        }

        if ($whiteList) {
            foreach ($whiteList as $list) {
                $unlockedList = true;

                if ($list['status'] == 2) $unlockedList = false;

                if ($list['words']) {
                    $unlockedWord = strtolower($list['words']);

                    $nameUnlocked = $this->likeText($unlockedWord, strtolower($product['name']));
                    $descriptionUnlocked = $this->likeText($unlockedWord, strtolower($product['description']));

                    if (!$nameUnlocked && !$descriptionUnlocked) $unlockedList = false;
                }

                if ($list['seller_index']) {
                    $blockSellerIndex = $this->checkOperatorSellerIndex($list['seller_index'], $list['operator_seller_index'], $product['seller_index']);
                    if (!$blockSellerIndex) $unlockedList = false;
                }

                if ($list['commission']) {
                    $blockCommission = $this->checkOperatorCommission($list['commission'], $list['operator_commission'], $product['commission']);
                    if (!$blockCommission) $unlockedList = false;
                }

                if ($list['product_id']) {
                    if ($list['product_id'] != $prd_id) $unlockedList = false;
                }
                if ($list['phase_id']) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                }

                if ($list['brand_id']) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                }

                if ($list['category_id']) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                }

                // todas as info da white list libera o produto
                if ($unlockedList) return ['blocked' => false];
            }
        }

        // recuperar listagem de black list e remover o que não contemplar o registro
        $blackList = array();
        if ($blockingRules !== null && count($blockingRules)) {
            if (!$checkMarketplace) {
                $blockingRulesChunk = array_chunk($blockingRules, 10);
                foreach ($blockingRulesChunk as $permissionRuleChunk) {
                    $rulesId = array_map(function ($permission) {
                        return $permission['id'];
                    }, $permissionRuleChunk);

                    if (!empty($rulesId)) {
                        $blackList = array_merge($blackList, $this->CI->model_blacklist_words->getDataBlackListActiveAndIds($product, $rulesId, $product_integrated));
                    }
                }
            } else {
                $blackList = $blockingRules;
            }
        } else {
            $blackList = $this->CI->model_blacklist_words->getDataBlackListActive($product, $product_integrated);
        }
        $arrBlackList = array();
        $arrBlackListRow = array();

        foreach ($blackList as $key => $list) {

            $acceptAllRules = true;
            $numberSequence = false;
            $arrBlackTemp = array();

            if ($list['words']) {

                $lockedWord = strtolower($list['words']);
                $nameLocked = $list['words'] && $this->likeText($lockedWord, strtolower($product['name']));
                $descriptionLocked = $list['words'] && $this->likeText($lockedWord, strtolower($product['description']));

                if ($nameLocked || $descriptionLocked) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                } else $acceptAllRules = false;
            }

            $canBePhoneName = preg_match('/[0-9]{7}/', $product['name']);
            $canBePhoneDesc = preg_match('/[0-9]{7}/', $product['description']);

            if ($canBePhoneName || $canBePhoneDesc) {

                if ($acceptAllRules === false) continue;

                $numberSequence = true;
            }

            if ($list['seller_index']) {

                if ($acceptAllRules === false) continue;

                $blockSellerIndex = $this->checkOperatorSellerIndex($list['seller_index'], $list['operator_seller_index'], $product['seller_index']);

                if ($blockSellerIndex) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                } else $acceptAllRules = false;
            }

            if ($list['commission']) {

                if ($acceptAllRules === false) continue;

                $blockCommission = $this->checkOperatorCommission($list['commission'], $list['operator_commission'], $product['commission']);

                if ($blockCommission) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                } else $acceptAllRules = false;
            }

            if ($list['product_id']) {

                if ($acceptAllRules === false) continue;

                if ($list['product_id'] == $prd_id) {
                    $arrBlackTemp = array(
                        'arrBlackList' => ucfirst($list['sentence']),
                        'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                    );
                } else $acceptAllRules = false;
            }
            if ($list['phase_id']) {
                $arrBlackTemp = array(
                    'arrBlackList' => ucfirst($list['sentence']),
                    'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                );
            }

            if ($list['brand_id']) {
                $arrBlackTemp = array(
                    'arrBlackList' => ucfirst($list['sentence']),
                    'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                );
            }

            if ($list['category_id']) {
                $arrBlackTemp = array(
                    'arrBlackList' => ucfirst($list['sentence']),
                    'arrBlackListRow' => $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null
                );
            }

            if ($acceptAllRules) {
                if ($numberSequence) {
                    if (!array_key_exists('arrBlackList', $arrBlackTemp))
                        $arrBlackTemp['arrBlackList'] = '';

                    if (!array_key_exists('arrBlackListRow', $arrBlackTemp))
                        $arrBlackTemp['arrBlackListRow'] = array();

                    if (!array_key_exists('sentence', $arrBlackTemp['arrBlackListRow']))
                        $arrBlackTemp['arrBlackListRow']['sentence'] = '';

                    $arrBlackTemp['arrBlackList'] .= 'de sequência numérica que caracteriza número de telefone ';
                    if ($prd_id) {
                        $arrBlackTemp['arrBlackListRow']['product_id']   = $prd_id;
                        $arrBlackTemp['arrBlackListRow']['blacklist_id'] = $list['id'];
                        $arrBlackTemp['arrBlackListRow']['sentence']    .= 'de sequência numérica que caracteriza número de telefone ';
                    } else $arrBlackTemp['arrBlackListRow'] = null;
                }

                if (isset($arrBlackTemp['arrBlackList']) && isset($arrBlackTemp['arrBlackListRow'])) {
                    array_push($arrBlackList, $arrBlackTemp['arrBlackList']);
                    array_push($arrBlackListRow, $arrBlackTemp['arrBlackListRow']);
                } else if ($checkMarketplace && $list['marketplace']) {
                    array_push($arrBlackList, ucfirst($list['sentence']));
                    array_push($arrBlackListRow, $prd_id ? ['product_id' => $prd_id, 'blacklist_id' => $list['id'], 'sentence' => $list['sentence']] : null);
                }
            }
        }

        // retorna se existe ou não bloqueio no produto
        if (count($arrBlackList) == 0) return ['blocked' => false];

        //if ($prd_id == 192) dd(['blocked' => true, 'data' => $arrBlackList, 'data_row' => $arrBlackListRow]);

        return ['blocked' => true, 'data' => $arrBlackList, 'data_row' => $arrBlackListRow];
    }

    /**
     * Atualiza status do produto após criação ou atualização. Apenas nas regras atualizadas e/ou novas
     *
     * @param array $productUpdate  Dados do produto
     * @param int   $prd_id         Código do produto
     * @return array                retorna um array informado se o produto foi bloqueado ou liberado, caso de bloqueio por qual regra
     */
    public function updateStatusProductAfterUpdateOrCreate(array $productUpdate, int $prd_id): array
    {
        $checkBlock = $this->getBlockProduct($productUpdate, $prd_id);
        $status = null;

        // se for bloqueado coloca no status de bloqueada
        $checkBlock = array_merge($checkBlock, array('original_status' => $productUpdate['status']));
        if ($checkBlock['blocked']) {
            $status = 4;
            if ($prd_id)
                $this->CI->model_blacklist_words->createProductWithLock($checkBlock['data_row']);
        } else {
            // se não for bloqueado e o status anterior(em caso de update) estiver como bloqueio, deixa como ativo se completo
            if ($productUpdate['status'] == 4) {
                // remove todos os avisos de bloqueio
                $this->CI->model_blacklist_words->deleteProductWithLock($prd_id);
                $status = 1;
            }
        }

        if ($status !== null) $this->CI->model_products->update(['status' => $status], $prd_id);

        return $checkBlock;
    }

    /**
     * Formato dados do produto para recuperar apenas as informações necessárias
     *
     * @param  array $product Dados do produto original
     * @return array          Dados do produto formatado
     */
    public function getProductForCheck(array $product): array
    {
        $phase_id = $this->CI->model_phases->getPhaseByStore_id($product['store_id']);
        $productCheck = [
            'product_id'    => $product['id'] ?? null,
            'name'          => trim($product['name']) ? trim($product['name']) : null,
            'description'   => trim($product['description']) ? trim($product['description']) : null,
            'commission'    => isset($product['service_charge_value']) && trim($product['service_charge_value']) ? trim($product['service_charge_value']) : null,
            'seller_index'  => isset($product['seller_index']) && trim($product['seller_index']) ? $product['seller_index'] : null,
            'product_sku'   => trim($product['sku']) ? trim($product['sku']) : null,
            'store_id'      => trim($product['store_id']) ? $product['store_id'] : null,
            'category_id'   => trim($product['category_id']) ? (preg_replace('/[^0-9]/', '', $product['category_id']) ?? null) : null,
            'brand_id'      => trim($product['brand_id']) ? (preg_replace('/[^0-9]/', '', $product['brand_id']) ?? null) : null,
            'phase_id' => $phase_id['id'] ?? null
        ];

        if (isset($product['marketplace']) && trim($product['marketplace']))
            $productCheck['marketplace'] = trim($product['marketplace']);

        if ($productCheck['store_id'] && (!$productCheck['commission'] || !$productCheck['seller_index'])) {
            $dataStore = $this->getCommissionAndSellerIndexStore($productCheck['store_id']);
            $productCheck['commission']    = $dataStore['comission'];
            $productCheck['seller_index']  = $dataStore['seller_index'];
        }

        return $productCheck;
    }

    /**
     * Verifica se o seller index da loja corresponde ao seller index da regra
     *
     * @param   int         $sellerIndex         Seller index da regra
     * @param   string      $operatorSellerIndex Operador da condição de comparação
     * @param   int         $sellerIndexSeller   Seller index da loja
     * @return  bool
     */
    public function checkOperatorSellerIndex(int $sellerIndex, string $operatorSellerIndex, int $sellerIndexSeller): bool
    {
        switch ($operatorSellerIndex) {
            case '>':
                if ($sellerIndexSeller > $sellerIndex) return true;
                break;
            case '>=':
                if ($sellerIndexSeller >= $sellerIndex) return true;
                break;
            case '<':
                if ($sellerIndexSeller < $sellerIndex) return true;
                break;
            case '<=':
                if ($sellerIndexSeller <= $sellerIndex) return true;
                break;
            case '=':
                if ($sellerIndexSeller == $sellerIndex) return true;
                break;
            case '!=':
                if ($sellerIndexSeller != $sellerIndex) return true;
                break;
            default:
                return false;
        }
        return false;
    }

    /**
     * Verifica se o seller index da loja corresponde ao seller index da regra
     *
     * @param   int         $commission         Comissão da regra
     * @param   string      $operatorCommission Operador da condição de comparação
     * @param   int         $commissionSeller   Comissão da loja
     * @return  bool
     */
    public function checkOperatorCommission(int $commission, string $operatorCommission, int $commissionSeller): bool
    {
        switch ($operatorCommission) {
            case '>':
                if ($commissionSeller > $commission) return true;
                break;
            case '>=':
                if ($commissionSeller >= $commission) return true;
                break;
            case '<':
                if ($commissionSeller < $commission) return true;
                break;
            case '<=':
                if ($commissionSeller <= $commission) return true;
                break;
            case '=':
                if ($commissionSeller == $commission) return true;
                break;
            case '!=':
                if ($commissionSeller != $commission) return true;
                break;
            default:
                return false;
        }
        return false;
    }

    /**
     * Atualiza status do produto após criação ou atualização. Apenas nas regras atualizadas e/ou novas
     *
     * @param array $productUpdate      Dados do produto
     * @param int   $prd_id             Código do produto
     * @param array $permissionRules    regras de permissão atualizas e/ou criadas
     * @param array $blockingRules      regras de bloqueio atualizas e/ou criadas
     * @return array                    retorna um array informado se o produto foi bloqueado ou liberado, caso de bloqueio por qual regra
     */
    public function updateStatusProductAfterUpdateOrCreateRules(array $productUpdate, int $prd_id, array $permissionRules, array $blockingRules): array
    {
        $blockingRules = array_merge($this->CI->model_blacklist_words->getProductLockByPrdId($prd_id), $blockingRules);

        $checkBlock = $this->getBlockProduct($productUpdate, $prd_id, $permissionRules, $blockingRules);
        $status = null;

        // se for bloqueado coloca no status de bloqueada
        $checkBlock = array_merge($checkBlock, array('original_status' => $productUpdate['status']));
        if ($checkBlock['blocked']) {
            $status = 4;
            if ($prd_id)
                $this->CI->model_blacklist_words->createRuleWithLock($checkBlock['data_row']);
        } else {
            // se não for bloqueado e o status anterior(em caso de update) estiver como bloqueio, deixa como ativo se completo
            if ($productUpdate['status'] == 4) {
                // remove todos os avisos de bloqueio
                $this->CI->model_blacklist_words->deleteProductWithLock($prd_id);
                $status = 1;
            }
        }

        if ($status !== null) $this->CI->model_products->update(['status' => $status], $prd_id);

        return $checkBlock;
    }

    /**
     * Consulta string em uma parte de outra string
     *
     * @param   string  $needle     Valor a ser procurado
     * @param   string  $haystack   Valor real para comparação
     * @return  bool                Retorna o status da consulta
     */
    public function likeText(string $needle, string $haystack): bool
    {
        $needle = preg_replace(
            array(
                "/ý|ÿ/",
                "/Ý/",
                "/(á|à|ã|â|ä)/",
                "/(Á|À|Ã|Â|Ä)/",
                "/(é|è|ê|ë)/",
                "/(É|È|Ê|Ë)/",
                "/(í|ì|î|ï)/",
                "/(Í|Ì|Î|Ï)/",
                "/(ó|ò|õ|ô|ö)/",
                "/(Ó|Ò|Õ|Ô|Ö)/",
                "/(ú|ù|û|ü)/",
                "/(Ú|Ù|Û|Ü)/",
                "/(ñ)/",
                "/(Ñ)/"
            ),
            explode(" ", "y Y a A e E i I o O u U n N"),
            $needle
        );

        $haystack = preg_replace(
            array(
                "/ý|ÿ/",
                "/Ý/",
                "/(á|à|ã|â|ä)/",
                "/(Á|À|Ã|Â|Ä)/",
                "/(é|è|ê|ë)/",
                "/(É|È|Ê|Ë)/",
                "/(í|ì|î|ï)/",
                "/(Í|Ì|Î|Ï)/",
                "/(ó|ò|õ|ô|ö)/",
                "/(Ó|Ò|Õ|Ô|Ö)/",
                "/(ú|ù|û|ü)/",
                "/(Ú|Ù|Û|Ü)/",
                "/(ñ)/",
                "/(Ñ)/"
            ),
            explode(" ", "y Y a A e E i I o O u U n N"),
            $haystack
        );
		$needle = str_replace("/","\/",$needle);    		// acerta a / para o preg_match
		$needle = str_replace("(","\(",$needle);    		// acerta a ( para o preg_match
		$needle = str_replace("[","\[",$needle);    		// acerta a [ para o preg_match
		$needle = str_replace(")","\)",$needle);    		// acerta a ) para o preg_match
		$needle = str_replace("]","\]",$needle);    		// acerta a ] para o preg_match
		$haystack = preg_replace('/\s+/', ' ', $haystack);  // remove os espaços sobrando
		$needle = preg_replace('/\s+/', ' ', $needle);		// remove os espaços sobrando

        return preg_match("/\b{$needle}\b/i", $haystack);  // \b é por palavra

        // expressão anterior
        //$regex = '/' . str_replace('%', '.*?', $needle) . '/';
        //return preg_match($regex, $haystack) > 0;
    }
}
