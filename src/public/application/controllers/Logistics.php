<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingCarrier.php";

use Microservices\v1\Logistic\ShippingIntegrator;
use Microservices\v1\Logistic\ShippingCarrier;

/**
 * @property Model_integration_logistic $model_integration_logistic
 * @property Model_stores $model_stores
 * @property Model_auction $model_auction
 * @property Model_settings $model_settings
 * @property Model_api_integrations $model_api_integrations
 * @property Model_integration_erps $model_integration_erps
 *
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Output $output
 * @property CI_Session $session
 *
 * @property ShippingIntegrator $ms_shipping_integrator
 * @property ShippingCarrier $ms_shipping_carrier
 */

class Logistics extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();
        $this->load->model('model_integration_logistic');
        $this->load->model('model_stores');
        $this->load->model('model_auction');
        $this->load->model('model_settings');
        $this->load->model('model_api_integrations');
        $this->load->model('model_integration_erps');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", array(), 'ms_shipping_integrator');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingCarrier", array(), 'ms_shipping_carrier');
    }

    public function introduction()
    {
        if (!in_array('viewIntegrationLogistic', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_title'] = $this->lang->line('application_manage_logistic');
        $this->data['page_now'] = 'logisticsnew';

        if (!empty($this->model_integration_logistic->getIntegrationsByStoreId(0))) {
            redirect('logistics/integrations');
        }

        $this->render_template('logistics/introduction', $this->data);
    }

    public function introduction_manage_logistic()
    {
        if (!in_array('viewIntegrationLogistic', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_title'] = $this->lang->line('application_manage_logistic');
        $this->data['page_now'] = 'logisticsnew';

        $this->render_template('logistics/introduction_manage_logistic', $this->data);
    }

    public function integrations()
    {
        if(!in_array('viewIntegrationLogistic', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['page_now'] = 'manage_logistic';

        $this->data['integrationsLogisticsSellerCenter'] = $this->model_integration_logistic->getIntegrationsSellerCenterActiveNotUse();
        $this->data['integrationsLogisticsSeller'] = $this->model_integration_logistic->getIntegrationsSellerActiveNotUse();
        $this->data['stores'] = $this->model_stores->getActiveStore();
        $this->data['page_title'] = $this->lang->line('application_manage_logistic');
        
        $this->data['status'] = $this->model_auction->statusAuction(); 
        $this->render_template('logistics/integration', $this->data);
    }

    public function manage_logistic()
    {
        $this->data['page_now']     = 'logisticsnew';
        $this->data['page_title']   = $this->lang->line('application_logisticsnew');
        $this->data['stores']       = $this->model_stores->getStoresData();
        $this->data['userData']     = $this->session->userdata;

        $this->render_template('logistics/manage_logistic', $this->data);
    }

    public function saveIntegration()
    {
        if(!in_array('createIntegrationLogistic', $this->permission) && !in_array('updateIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Você não tem permissão para criar e alterar integrações'
                )));
        }

        $type_integration   = $this->postClean('type_integration');
        $integration        = $this->postClean('integration');
        $store_id           = 0; // set store 0 - admin
        $type               = $this->postClean('type');
        $type_operation     = $this->postClean('type_operation');

        // criar json com as credenciais
        $credentials = array();
        if ($type === 'sellercenter') {
            foreach ($this->postClean('data') ?? array() as $data) {
                $credentials[$data['name']] = is_string($data['value'] ?? array()) ? trim($data['value']) : ($data['value'] ?? array());
            }

            $dataMicroservice = array(
                'integration' => $integration,
                'credentials' => $credentials,
                'active' => true
            );
        }

        if ($type_integration === 'integrator' && $this->ms_shipping_integrator->use_ms_shipping) {
            try {
                $this->ms_shipping_integrator->saveIntegration(['integration' => $integration, "use_$type" => true]);

                if ($type === 'sellercenter') {
                    if ($type_operation == 'create') {
                        $this->ms_shipping_integrator->saveConfigure($dataMicroservice);
                    } else {
                        $this->ms_shipping_integrator->saveConfigure(['integration' => $integration, 'credentials' => $dataMicroservice['credentials']]);
                    }
                }
                if (!$this->ms_shipping_integrator->use_ms_shipping_replica) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success' => true,
                            'data' => 'Integração inserida com sucesso!'
                        )));
                }
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        }

        if ($type_integration === 'carrier' && $this->ms_shipping_carrier->use_ms_shipping) {
            try {
                $this->ms_shipping_carrier->saveIntegration(['integration' => $integration, "use_$type" => true]);

                if ($type === 'sellercenter') {
                    if ($type_operation == 'create') {
                        $this->ms_shipping_carrier->saveConfigure($dataMicroservice);
                    } else {
                        $this->ms_shipping_carrier->saveConfigure([
                            'integration' => $integration,
                            'credentials' => $dataMicroservice['credentials'],
                            'active' => 1
                        ]);
                    }
                }
                if (!$this->ms_shipping_carrier->use_ms_shipping_replica) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success' => true,
                            'data' => 'Integração inserida com sucesso!'
                        )));
                }
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        }

        $dataLogistic   = $this->model_integration_logistic->getIntegrationsByName($this->postClean('integration'));

        if (!$dataLogistic) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Integração não encontrada'
                )));
        }

        if ($type === 'sellercenter') {

            // consulta integração já existente
            $dataIntegration = $this->model_integration_logistic->getIntegrationByName($this->postClean('integration'), $store_id);

            if ($dataIntegration) { // update

                if (!in_array('updateIntegrationLogistic', $this->permission)) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success' => false,
                            'data' => 'Você não tem permissão para atualizar integrações!'
                        )));
                }

                $insertUpdateIntegration = $this->model_integration_logistic->updateIntegrationByIntegration(
                    $dataIntegration['id'],
                    array(
                        'credentials' => json_encode($credentials),
                        'user_updated' => $this->session->userdata('id')
                    )
                );

            } else { // create

                if (!in_array('createIntegrationLogistic', $this->permission))
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success' => false,
                            'data' => 'Você não tem permissão para criar novas integrações!'
                        )));

                $arrCreate = array(
                    'id_integration' => $dataLogistic['id'],
                    'integration' => $dataLogistic['name'],
                    'credentials' => json_encode($credentials),
                    'store_id' => $store_id,
                    'user_created' => $this->session->userdata('id')
                );
                $insertUpdateIntegration = $this->model_integration_logistic->createNewIntegrationByStore($arrCreate);
            }

            $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração {$dataLogistic['name']} SellerCenter atualizada!\nbackup_integration=".json_encode($dataIntegration));

            if (!$insertUpdateIntegration) // erro
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'data' => $dataIntegration ? 'Não foi possível atualizar a integração!' : 'Não foi possível inserir a integração!'
                    )));
        } else {
            $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração {$dataLogistic['name']} Seller atualizada!");
        }

        // atualiza integração para deixar em uso e quem alterou por último
        $this->model_integration_logistic->updateIntegrationsInUse(
            array(
                $type === 'seller' ? 'use_seller' : 'use_sellercenter' => true,
                'name' => $integration,
                'user_updated' => $this->session->userdata('id')
            ),
            $dataLogistic['id']
        );


        if ($type === 'seller') $dataIntegration = true;

        // sucesso
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => true,
                'data'      => $dataIntegration ? 'Integração atualizada com sucesso!' : 'Integração inserida com sucesso!'
            )));
    }

    public function saveIntegrationSeller()
    {
        if(!in_array('createIntegrationLogistic', $this->permission) && !in_array('updateIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Você não tem permissão para criar e alterar integrações'
                )));
        }

        $integrationType            = $this->postClean('integrationType');
        $externalIntegrationId      = $this->postClean('externalIntegrationId');
        $type_integration           = $this->postClean('typeIntegration');
        $type_integration_current   = $this->postClean('typeIntegrationCurrent');
        $integration_current        = $this->postClean('integrationCurrent');
        $store_id                   = (int)$this->postClean('store');
        $integration                = $this->postClean('integration');
        $externalIntegrationId      = empty($externalIntegrationId) ? null : $externalIntegrationId; // na requisição quando é null, vem uma string vazia.

        $formIntegrationData = $this->postClean('data') ?? [];
        $formIntegrationData = is_array($formIntegrationData) ? $formIntegrationData : [];
        $formCredentials = array_reduce(array_map(function ($item) {
            return [
                    $item['name'] ?? '' => $item['value'] ?? ''
            ];
        }, $formIntegrationData), 'array_merge', []);

        $dataLogistic = false;
        $use_ms = $this->ms_shipping_integrator->use_ms_shipping || $this->ms_shipping_carrier->use_ms_shipping;
        $use_ms_replica = $this->ms_shipping_integrator->use_ms_shipping_replica || $this->ms_shipping_carrier->use_ms_shipping_replica;
        if (!$use_ms || $use_ms_replica) {
            $dataLogistic = $this->model_integration_logistic->getIntegrationsByName($integration);
        }
        if ($use_ms) {
            $this->ms_shipping_integrator->setStore($store_id);
            $this->ms_shipping_carrier->setStore($store_id);

            if ($type_integration === 'integrator') {
                $data_integration_erps = array_map(function ($integration){
                    return $integration->name;
                }, $this->model_integration_erps->getListIntegrations(array('where' => array('type' => 1))));

                $data_api_integration = $this->model_api_integrations->getDataByStore($store_id, true);
                if ($data_api_integration) {
                    if (in_array(strtolower($data_api_integration['integration']), array('viavarejo_b2b_pontofrio', 'viavarejo_b2b_extra', 'viavarejo_b2b_casasbahia'))) {
                        $data_api_integration['integration'] = 'viavarejo_b2b';
                    }
                }
                if (in_array($integration, $data_integration_erps) && (!$data_api_integration || $data_api_integration['integration'] != $integration)) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success'   => false,
                            'data'      => "Integração não localizada. Acesse no menu: 'Integração -> Solicitar Integração' e informe suas credenciais."
                        )));
                }
            }
        }

        if (!$dataLogistic && !$use_ms) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Integração não encontrada'
                )));
        }

        if (!$this->model_stores->getStoresData($store_id)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Loja não encontrada'
                )));
        }

        if (!in_array('updateIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Você não tem permissão para alterar integrações de seller!'
                )));
        }

        // remove integração atual do microsserviço.
        if ($this->ms_shipping_integrator->use_ms_shipping && $type_integration_current === 'integrator' && !empty($integration_current)) {
            try {
                $this->ms_shipping_integrator->removeConfigure($integration_current);
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        } elseif ($this->ms_shipping_carrier->use_ms_shipping && $type_integration_current === 'carrier' && !empty($integration_current)) {
            try {
                $this->ms_shipping_carrier->removeConfigure($integration_current);
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        }

        // adicionar integração no microsserviço.
        $dataMicroservice = array(
            'integration' => $integration,
            'credentials' => $integrationType == 'sellercenter' ? null : (
                $use_ms ? ($type_integration === 'integrator' && in_array($integration, $data_integration_erps) ? json_decode($data_api_integration['credentials']) : $formCredentials) : '{}'
            ),
            'active'      => true
        );
        if ($this->ms_shipping_integrator->use_ms_shipping && $type_integration === 'integrator') {
            try {
                $this->ms_shipping_integrator->saveConfigure($dataMicroservice);
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        } elseif ($this->ms_shipping_carrier->use_ms_shipping && $type_integration === 'carrier') {
            try {
                $this->ms_shipping_carrier->saveConfigure($dataMicroservice);
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        }

        if ($use_ms && !$use_ms_replica) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => true,
                    'data'      => 'Integração da loja atualizada com sucesso!'
                )));
        }

        $integrationBefore = $this->model_integration_logistic->getIntegrationSeller($store_id);

        // remove a integração ativa
        $this->model_integration_logistic->removeIntegrationByStore($store_id);


        // criar json com as credenciais
        $credentials = array();
        $data_post = empty($this->postClean('data')) ? array() : $this->postClean('data');
        foreach ($data_post as $data) {
            $credentials[$data['name']] = is_string($data['value'] ?? array()) ? trim($data['value']) : ($data['value'] ?? array());
        }

        $this->model_stores->updateStoresByProvider(null, $store_id);
        if ($externalIntegrationId) {
            $erp_integration = $this->model_integration_erps->getById($externalIntegrationId);
            if ($erp_integration) {
                $provider_id = $erp_integration->provider_id;
                $this->model_stores->updateStoresByStores(array($store_id), array('provider_id' => $provider_id));
            }
        }

        $arrCreate = array(
            'id_integration'            => $dataLogistic['id'],
            'integration'               => $dataLogistic['name'],
            'credentials'               => $integrationType == 'sellercenter' ? null : json_encode($credentials),
            'store_id'                  => $store_id,
            'user_created'              => $this->session->userdata('id'),
            'external_integration_id'   => $externalIntegrationId
        );
        $insertUpdateIntegration = $this->model_integration_logistic->createNewIntegrationByStore($arrCreate);

        $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração para a loja {$store_id} atualizada!\nbackup_integration=".json_encode($integrationBefore)."\nnew_integration=".json_encode($arrCreate));

        if (!$insertUpdateIntegration) { // erro
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Não foi possível atualizar a integração da loja!'
                )));
        }

        // sucesso
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => true,
                'data'      => 'Integração da loja atualizada com sucesso!'
            )));
    }

    public function getIntegrationsInUseSellerCenter($returnJson = true)
    {
        if(!in_array('viewIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $response = array();
        if ($this->ms_shipping_carrier->use_ms_shipping) {
            try {
                foreach ($this->ms_shipping_carrier->getAllIntegrations() as $integration) {
                    if ($integration->use_sellercenter) {
                        $response[] = array_merge(array('type' => 'carrier'), (array)$integration);
                    }
                }
            } catch (Exception $exception) {}
        } else {
            $response = array_filter(array_map(function ($integration){
                if (!empty($integration['external_integration_id'])) {
                    $integration_erp = $this->model_integration_erps->getById($integration['external_integration_id']);
                    if (!$integration_erp->active) {
                        return null;
                    }
                }
                return array_merge(array('type' => null), $integration);
            }, $this->model_integration_logistic->getIntegrationsInUseSellerCenter()), function($integration){
                return !empty($integration);
            });
        }

        return $returnJson ? $this->output->set_content_type('application/json')->set_output(json_encode($response)) : $response;
    }

    public function getIntegrationsInUseSeller($returnJson = true)
    {
        if(!in_array('viewIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $ms_used = false;
        $response = array();
        if ($this->ms_shipping_integrator->use_ms_shipping) {
            try {
                foreach ($this->ms_shipping_integrator->getAllIntegrations() as $integration) {
                    if ($integration->use_seller) {
                        $response[] = array_merge(array('type' => 'integrator'), (array)$integration);
                    }
                }
                $ms_used = true;
            } catch (Exception $exception) {}
        }
        if ($this->ms_shipping_carrier->use_ms_shipping) {
            try {
                foreach ($this->ms_shipping_carrier->getAllIntegrations() as $integration) {
                    if ($integration->use_seller) {
                        $response[] = array_merge(array('type' => 'carrier'), (array)$integration);
                    }
                }
                $ms_used = true;
            } catch (Exception $exception) {}
        }

        if (!$ms_used) {
            $response = array_filter(array_map(function ($integration){
                if (!empty($integration['external_integration_id'])) {
                    $integration_erp = $this->model_integration_erps->getById($integration['external_integration_id']);
                    if (!$integration_erp->active) {
                        return null;
                    }
                }
                return array_merge(array('type' => null), $integration);
            }, $this->model_integration_logistic->getIntegrationsInUseSeller()), function($integration){
                return !empty($integration);
            });

            $integration_api = array_slice(array_filter($response, function($integration) {
                return $integration['name'] === 'precode';
            }), 0)[0] ?? null;

            if ($integration_api) {
                $external_integrations = $this->model_integration_erps->getIntegrationActive($this->model_integration_erps->type['external_logistic']);
                foreach ($external_integrations as $external_integration) {
                    if (!$this->model_integration_logistic->getByExternalIntegrationId($external_integration->id)) {
                        $response[] = array_merge($integration_api, array(
                            'external_integration_id' => $external_integration->id,
                            'external_integration_image' => base_url("assets/images/integration_erps/$external_integration->image"),
                            'description' => $external_integration->description
                        ));
                    }
                }
            }
        }

        return $returnJson ? $this->output->set_content_type('application/json')->set_output(json_encode($response)) : $response;
    }

    public function getDataIntegration(string $integration, string $type_integration, int $storeId)
    {
        if (!in_array('viewIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $response = array();
        if ($type_integration === 'carrier') {
            if ($this->ms_shipping_carrier->use_ms_shipping) {
                try {
                    $this->ms_shipping_carrier->setStore($storeId);
                    $response = $this->ms_shipping_carrier->getConfigure($integration);
                } catch (Exception $exception) {}
            }
        } else if ($type_integration === 'integrator') {
            if ($this->ms_shipping_integrator->use_ms_shipping) {
                try {
                    $this->ms_shipping_integrator->setStore($storeId);
                    $response = $this->ms_shipping_integrator->getConfigure($integration);
                } catch (Exception $exception) {}
            }
        } else {
            $response = $this->model_integration_logistic->getIntegrationByName($integration, $storeId);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function removeIntegrationSellerCenter()
    {
        if(!in_array('deleteIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Você não tem permissão para excluir integrações'
                )));
        }

        $store_id           = 0; // set store 0 - admin
        $integration        = $this->postClean('integration');
        $type_integration   = $this->postClean('type_integration');
        $type               = $this->postClean('type');

        if ($type_integration === 'integrator' && $this->ms_shipping_integrator->use_ms_shipping) {
            try {
                $this->ms_shipping_integrator->saveIntegration(['integration' => $integration, "use_$type" => false]);
                if ($type === 'sellercenter') {
                    $this->ms_shipping_integrator->removeConfigure($integration);
                }
                if (!$this->ms_shipping_integrator->use_ms_shipping_replica) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success' => true,
                            'data' => 'Integração excluída com sucesso!'
                        )));
                }
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        } else if ($type_integration === 'carrier' && $this->ms_shipping_carrier->use_ms_shipping) {
            try {
                $this->ms_shipping_carrier->saveIntegration(['integration' => $integration, "use_$type" => false]);
                if ($type === 'sellercenter') {
                    $this->ms_shipping_carrier->removeConfigure($integration);
                }
                if ($this->ms_shipping_integrator->use_ms_shipping) {
                    try {
                        $this->ms_shipping_integrator->saveIntegration(['integration' => $integration, "use_seller" => false]);
                    } catch (Throwable $e) {
                    }
                }
                if (!$this->ms_shipping_carrier->use_ms_shipping_replica) {
                    return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode(array(
                            'success' => true,
                            'data' => 'Integração excluída com sucesso!'
                        )));
                }
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        }

        $dataLogistic = $this->model_integration_logistic->getIntegrationsByName($integration);
        if (!$dataLogistic) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Integração não encontrada'
                )));
        }

        // atualizar integrations_logistic (use_sellercenter ou use_seller para 'false')
        $this->model_integration_logistic->updateIntegrationsInUse(
            array(
                "use_$type"     => false,
                "name"     => $integration,
                'user_updated'  => $this->session->userdata('id')
            ),
            $dataLogistic['id']
        );
        $backup = array();
        $seller_integrations = array();
        // se for sellercenter, remover a integração da tabela integration_logistic
        if ($type === 'sellercenter') {
            $seller_integrations = array_map( function ($integration) {
                return $integration['store_id'];
            }, $this->model_integration_logistic->getIntegrationsSellerByIntegration($dataLogistic['id']));
            $backup = $this->model_integration_logistic->removeIntegrationSellerCenter($integration, $store_id);
            $this->model_integration_logistic->removeIntegrationsSellerByMarketplaceContract($integration);
        }
        // se for seller, remover todas as  integração da tabela integration_logistic diferente de 0
        if ($type === 'seller') {
            $backup = $this->model_integration_logistic->removeIntegrationSeller($integration);
        }

        $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração {$integration} removida!\nbackup_integration=".json_encode($backup)."\nbackup_stores=".json_encode($seller_integrations));

        // sucesso
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => true,
                'data'      => 'Integração excluída com sucesso!'
            )));
    }

    public function removeAllIntegration()
    {
        if(!in_array('deleteIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Você não tem permissão para excluir integrações'
                )));
        }

        // atualizar integrations_logistic (use_sellercenter ou use_seller para 'false')
        $this->model_integration_logistic->updateAllIntegrationsInUse(array(
            $this->postClean('type') === 'seller' ? 'use_seller' : 'use_sellercenter'  => false,
            'user_updated'      => $this->session->userdata('id'))
        );
        $backup = array();
        // se for sellercenter, remover a integração da tabela integration_logistic
        if ($this->postClean('type') === 'sellercenter')
            $backup = $this->model_integration_logistic->removeAllIntegrationSellerCenter();
        // se for seller, remover todas as integrações do seller
        if ($this->postClean('type') === 'seller')
            $backup = $this->model_integration_logistic->removeAllIntegrationSeller();

        $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração removida!\nbackup_integration=".json_encode($backup));

        // sucesso
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => true,
                'data'      => 'Integração excluída com sucesso!'
            )));
    }

    public function getIntegrationSeller(int $storeId)
    {
        if(!in_array('viewIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $integrationsSellerCenter = $this->getIntegrationsInUseSellerCenter(false);
        $integrationsSeller = $this->getIntegrationsInUseSeller(false);

        $ms_used     = false;
        $integration = null;

        if ($this->ms_shipping_integrator->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_integrator->setStore($storeId);
                $integration = $this->ms_shipping_integrator->getConfigures();
            } catch (Exception $exception) {}
        }
        if (is_null($integration) && $this->ms_shipping_carrier->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_carrier->setStore($storeId);
                $integration = $this->ms_shipping_carrier->getConfigures();
            } catch (Exception $exception) {}
        }

        if (!$ms_used) {
            $integration = $this->model_integration_logistic->getIntegrationSeller($storeId);
        }
        
        $integrationSellerInUse = array();
        if ($integration) {
            $integrationSellerInUse = array(
                'integration'               => $ms_used ? $integration->integration_name : $integration['integration'],
                'sellercenter'              => $ms_used ? ($integration->type_contract === 'sellercenter') : ($integration['credentials'] === null),
                'seller'                    => $ms_used ? ($integration->type_contract === 'seller') : ($integration['credentials'] !== null),
                'credentials'               => $ms_used ? $integration->credentials : json_decode($integration['credentials']),
                'external_integration_id'   => $ms_used ? null : $integration['external_integration_id']
            );
        }

        $response = array(
            'integrationSeller'         => $integrationSellerInUse,
            'integrationsSellerCenter'  => $integrationsSellerCenter,
            'integrationsSeller'        => $integrationsSeller,
            'itsStoresModuloFrete'      => $ms_used
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function getFieldsForm($integration)
    {
        $dataIntegration = $this->model_integration_logistic->getIntegrationsByName($integration);

        return $this->output->set_content_type('application/json')->set_output($dataIntegration['fields_form'] ?? '{}');
    }

    public function removeIntegrationStore()
    {
        if(!in_array('deleteIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => false,
                    'data'      => 'Você não tem permissão para excluir integrações'
                )));
        }

        $store_id                   = $this->postClean('storeId');
        $type_integration_current   = $this->postClean('type_integration_current');
        $integration_current        = $this->postClean('integration_current');
        $external_integration_id    = empty($this->postClean('external_integration_id')) ? null : $this->postClean('external_integration_id');
        $use_ms                     = $this->ms_shipping_integrator->use_ms_shipping || $this->ms_shipping_carrier->use_ms_shipping;
        $use_ms_replica = $this->ms_shipping_integrator->use_ms_shipping_replica || $this->ms_shipping_carrier->use_ms_shipping_replica;
        if ($use_ms) {
            $this->ms_shipping_integrator->setStore($store_id);
            $this->ms_shipping_carrier->setStore($store_id);
        }

        // remove integração atual do microsserviço.
        if ($this->ms_shipping_integrator->use_ms_shipping && $type_integration_current === 'integrator') {
            try {
                $this->ms_shipping_integrator->removeConfigure($integration_current);
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        } elseif ($this->ms_shipping_carrier->use_ms_shipping && $type_integration_current === 'carrier') {
            try {
                $this->ms_shipping_carrier->removeConfigure($integration_current);
            } catch (Exception $exception) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success'   => false,
                        'data'      => $exception->getMessage()
                    )));
            }
        }

        if ($use_ms && !$use_ms_replica) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success'   => true,
                    'data'      => 'Integração excluída com sucesso!'
                )));
        }

        $integration = $this->model_integration_logistic->getIntegrationSeller($store_id);

        if (!$integration) {
            $nameIntegration = 'null';
        } else {
            $nameIntegration = $integration['integration'];
        }

        $this->log_data(__CLASS__,__CLASS__.'/'.__FUNCTION__,"Integração {$nameIntegration} removida!\nbackup_integration=".json_encode($integration));

        if ($external_integration_id) {
            $erp_integration = $this->model_integration_erps->getById($external_integration_id);
            if ($erp_integration) {
                $provider_id = $erp_integration->provider_id;
                $this->model_stores->updateStoresByProvider($provider_id, $store_id);
            }
        }

        $this->model_integration_logistic->removeIntegrationByStore($store_id);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success'   => true,
                'data'      => 'Integração excluída com sucesso!'
            )));
    }

    public function getIntegrationsSellerActiveNotUse()
    {
        if(!in_array('viewIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $ms_used = false;
        $response = array();

        if ($this->ms_shipping_carrier->use_ms_shipping) {
            try {
                foreach ($this->ms_shipping_carrier->getAllIntegrations() as $integration) {
                    if (!$integration->use_seller) {
                        $response[] = array('name' => $integration->name, 'type' => 'carrier');
                    }
                }
                $ms_used = true;
            } catch (Exception $exception) {}
        }

        if ($this->ms_shipping_integrator->use_ms_shipping) {
            try {
                foreach ($this->ms_shipping_integrator->getAllIntegrations() as $integration) {
                    if (!$integration->use_seller) {
                        $response[] = array('name' => $integration->name, 'type' => 'integrator');
                    }
                }
                $ms_used = true;
            } catch (Exception $exception) {}
        }

        if (!$ms_used) {
            foreach ($this->model_integration_logistic->getIntegrationsSellerActiveNotUse() as $integration) {
                if (!empty($integration['external_integration_id'])) {
                    $integration_erp = $this->model_integration_erps->getById($integration['external_integration_id']);
                    if (!$integration_erp->active) {
                        continue;
                    }
                }
                $response[] = array('name' => $integration['name'], 'type' => null);
            }
        }

        // Consultar ao ShippingCarrier também!

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function getIntegrationsSellerCenterActiveNotUse()
    {
        if (!in_array('viewIntegrationLogistic', $this->permission)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }

        $response = array();
        if ($this->ms_shipping_carrier->use_ms_shipping) {
            try {
                foreach ($this->ms_shipping_carrier->getAllIntegrations() as $integration) {
                    if (!$integration->use_sellercenter) {
                        $response[] = array('name' => $integration->name, 'type' => 'carrier');
                    }
                }
            } catch (Exception $exception) {}
        } else {
            foreach ($this->model_integration_logistic->getIntegrationsSellerCenterActiveNotUse() as $integration) {
                if (!empty($integration['external_integration_id'])) {
                    $integration_erp = $this->model_integration_erps->getById($integration['external_integration_id']);
                    if (!$integration_erp->active) {
                        continue;
                    }
                }
                $response[] = array('name' => $integration['name'], 'type' => null);
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function fetchIntegrationsData()
    {
        $result = array();
        $store = (int)$this->input->get('store_id');

        if (empty($store)) {
            echo json_encode(array('data' => array()));
            die;
        }

        $dataIntegrationLogistic = array();
        $ms_used = false;
        if ($this->ms_shipping_integrator->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_integrator->setStore($store);
                $dataIntegrationLogistic = array(array_merge(array('type' => 'integrator'), (array)$this->ms_shipping_integrator->getConfigures()));
            } catch (Exception $exception) {}
        }

        if ($this->ms_shipping_carrier->use_ms_shipping) {
            $ms_used = true;
            try {
                $this->ms_shipping_carrier->setStore($store);
                $dataIntegrationLogistic = array(array_merge(array('type' => 'carrier'), (array)$this->ms_shipping_carrier->getConfigures()));
            } catch (Exception $exception) {}
        }

        if (!$ms_used) {
            $dataIntegrationLogistic = $this->model_integration_logistic->getIntegrationLogistic($store);
        } else {
            $store_name = $this->model_stores->getStoresData($store)['name'];
        }

        $api_integrations = array_map(function ($item) {
            return $item['integration'];
        }, $this->model_api_integrations->getIntegrationsWithCredentials());
        // Add 'viavarejo_b2b' sem o sufixo da marca.
        $api_integrations[] = 'viavarejo_b2b';

        if ($dataIntegrationLogistic && count($dataIntegrationLogistic) > 0) {
            foreach($dataIntegrationLogistic as $_value) {
                $integration        = $ms_used ? $_value['integration_name'] : $_value['integration'];
                $active             = $ms_used ? $_value['active'] : $_value['active'];
                $store_name         = $ms_used ? $store_name : $_value['store_name'];
                $id                 = $ms_used ? $integration : $_value['id'];
                $type_itegration    = $ms_used ? $_value['type'] : null;
                $credentials        = $ms_used ? $_value['credentials'] : $_value['credentials'];
                $description        = $ms_used ? $_value['integration_description'] : $_value['description'];

                $is_active = $active == 1 ? 'checked' : '';
                $btn_credentials = "";
                if (!is_null($credentials) && (in_array($integration, $api_integrations))) {
                    $btn_credentials = '<button type="button" class="btn btn-sm btn-outline-primary" data-id="' . $id . '" data-toggle="modal" data-target="#integration_modal">Inserir suas credenciais</button>';
                } else if (!is_null($credentials)) {
                    $btn_credentials = '<button type="button" class="btn btn-sm btn-outline-primary" data-id="' . $id . '" onclick="modalIntegration(\''.$integration.'\', this, \''.$type_itegration.'\', '.$store.');"><i class="fa fa-edit"></i> Alterar credenciais</button>';
                }

                if ($ms_used && $_value['type_contract'] === 'sellercenter') {
                    $btn_credentials = "";
                }

                $btn_update_status = $active == 1 ? "<input type='checkbox' name='my-checkbox' checked data-bootstrap-switch onchange=\"updateStatusIntegration('$id',$(this),'$type_itegration', '$integration')\">" : "<input type='checkbox' name='my-checkbox' data-bootstrap-switch onchange=\"updateStatusIntegration('$id',$(this),'$type_itegration', '$integration')\">";
                $btn_update_status = empty($credentials) && !$this->data['only_admin'] ? '' : $btn_update_status;                

                $resultIntegration = array(
                    "<h4 class='no-margin'>Sua integração logística própria</h4>",
                    '<img src="'.base_url("assets/files/integrations/$integration/$integration.png") . '" width="70px" alt="'.$description.'">',
                    '<span class="border-radius-index bg-primary">1</span> ' . $btn_credentials,
                    '<span class="border-radius-index bg-primary">2</span> ' . $btn_update_status,
                    '<span class="border-radius-index bg-primary">3</span> <a href="'.base_url('shippingcompany/createsimplified').'" class="btn btn-sm btn-outline-primary">Adicionar Transportadora de contingência</a>'
                );
                $result[] = $resultIntegration;
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('data' => $result)));
    }

    public function validateCredentials(string $logistic)
    {
        // Por padrão seguir o nome da biblioteca com apenas a primeira letra em maiúsculo, pode usar underline em separação.
        $nameLib = ucfirst($logistic);

        if (empty($nameLib) || !file_exists(APPPATH . "libraries/Logistic/$nameLib.php")) {
            throw new InvalidArgumentException("Logística $logistic não configurada");
        }

        $arrValidate = array_map(function ($item) {
            return likeText(
                "%application==libraries==Logistic==Logistic.php%",
                str_replace('/', '==', str_replace('\\', '==', $item))
            );
        }, get_included_files());

        if (!in_array(true, $arrValidate)) {
            require APPPATH . "libraries/Logistic/Logistic.php";
        } else {
            unset($this->logistic);
        }

        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');

        try {
            $this->load->library(
                "Logistic/$nameLib",
                array(
                    'readonlydb'    => $this->db,
                    'store'         => 0,
                    'integration'   => $logistic,
                    'dataQuote'     => [],
                    'freightSeller' => false,
                    'sellerCenter'  => $settingSellerCenter['name'],
                    'validate_credentials' => false
                ),
                'logistic'
            );
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();
            if ($message != 'Falha para obter as credenciais da loja. Não configurada.') {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('data' => $message)))
                    ->set_status_header(400);
            }
        }

        $credentials = cleanArray($this->input->get('data'));
        try {
            $this->logistic->validateCredentials($credentials);
        } catch (Exception $exception) {

            $message = $exception->getMessage();
            $code = $exception->getCode();

            $message_decode = json_decode($message);
            if ($message_decode && is_array($message_decode)) {
                $message = '<li>' . implode('</li><li>', $message_decode) . '</li>';
            }

            if (empty($message) && in_array($code, [401, 403])) {
                $message = 'Credenciais inválidas.';
            }

            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('data' => $message)))
                ->set_status_header(400);
        }

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(array('success' => true)));
    }

}