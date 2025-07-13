<?php
/*
Controller da tela de precificação de frete.
*/
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";

use Microservices\v1\Logistic\FreightTables;

/**
 * @property CI_Loader $load
 *
 * @property Model_shipping_price_rules $model_shipping_price_rules
 * @property Model_shipping_company $model_shipping_company
 * @property Model_settings $model_settings
 *
 * @property FreightTables $ms_freight_tables
 */

class Shippingpricingrules extends Admin_Controller
{
    private $AdminGroup = '';

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->load->model('model_shipping_price_rules');
        $this->load->model('model_shipping_company');
        $this->load->model('model_settings');

        $this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');

        $this->data['page_title'] = $this->lang->line('application_shipping_pricing');
        $this->AdminGroup = $this->model_settings->getValueIfAtiveByName('admin_group_sellercenter');
    }

    public function index()
    {
        // O usuário atual tem as permissões necessárias e faz parte do grupo de administradores?
        if (!in_array('createPricingRules', $this->permission) || !isset($this->data['user_group_id']) || (($this->data['user_group_id'] != 1) && ($this->data['user_group_id'] != $this->AdminGroup))) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_shipping_pricing');
        $this->render_template('logistics/pricingrules', $this->data);
        
    }

    public function editrules()
    {
        // O usuário atual tem as permissões necessárias e faz parte do grupo de administradores?
        if (!in_array('createPricingRules', $this->permission) || !isset($this->data['user_group_id']) || (($this->data['user_group_id'] != 1) && ($this->data['user_group_id'] != $this->AdminGroup))) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_shipping_pricing');
        $this->render_template('logistics/editrules', $this->data);
    }

    public function fetchPriceData()
    {
        $pricing_rules = $this->model_shipping_price_rules->getAllShippingPriceRules();

        $data['data'] = [];
        foreach ($pricing_rules as $p) {
            $id = $p['id'];

            $companies = "";
            $shipping_companies = explode(";", $p['shipping_companies']);
            foreach ($shipping_companies as $sc) {
                if (empty($companies)) {
                    $companies = $sc;
                } else {
                    $companies .= "<br/>$sc";
                }
            }

            $channels = "";
            $mkt_channels = explode(";", $p['mkt_channels']);
            foreach ($mkt_channels as $mc) {
                if (empty($channels)) {
                    $channels = $mc;
                } else {
                    $channels .= "<br/>$mc";
                }
            }

            $price_rules = "";
            /*
            Os valores das regras de precificação são armazenados em sequências separadas por ponto-e-vírgula.
            Cada sequência é separada por vírgulas e estruturada da seguinte forma:
                - (valor do produto, em R$) Maior que;
                - (valor do produto, em R$) Menor que;
                - (em %, valor do) Custo Marketplace;
                - (em %, valor do) Custo RMA;
                - (em %, valor da) Margem Frete.

            No exemplo a seguir são listadas duas regras de precificação: 50.0,150,5,3,10;150.01,150,6,2,10.5

            Observações:
            1. Os valores decimais precisam ser marcados com um ponto (.), em vez de vírgula;
            2. Não devem ser usados espaços entre os valores.
            */

            $price_range = explode(";", $p['price_range']);
            foreach ($price_range as $pr) {
                $prices = explode(",", $pr);

                if (empty($price_rules)) {
                    $price_rules .= "De " . money($prices[0]) . " até " . money($prices[1]);    
                } else {
                    $price_rules .= "<br/>De " . money($prices[0]) . " até " . money($prices[1]);
                }
            }

            $formatted_date = "d/m/Y H:i:s";
            $date_created = date_format(date_create($p['date_created']), $formatted_date);

            $no_time = '0000-00-00 00:00:00';
            $date_enabled = $p['date_enabled'];
            if ($date_enabled != $no_time) {
                $date_enabled = date_format(date_create($date_enabled), $formatted_date);
            } else if (($date_enabled == $no_time) || ($date_enabled === null)) {
                $date_enabled = '-';
            }

            $date_disabled = $p['date_disabled'];
            if ($date_disabled != $no_time) {
                $date_disabled = date_format(date_create($date_disabled), $formatted_date);
            } else if (($date_disabled == $no_time) || ($date_disabled === null)) {
                $date_disabled = '-';
            }

            $date_updated = $p['date_updated'];
            if ($date_updated != $no_time) {
                $date_updated = date_format(date_create($date_updated), $formatted_date);
            } else if (($date_updated == $no_time) || ($date_updated === null)) {
                $date_updated = '-';
            }

            $checked = ($p['active'] == 1) ? 'checked' : 'unchecked';
            $actions  = '<a href="' . base_url("Shippingpricingrules/editrules/$id") . '" class="btn btn-default"><i class="fa fa-edit"></i></a>
            <button class="btn btn-danger" onclick="deleteRule(' . $id . ');" ><i class="fa fa-trash"></i></button>';

            $data['data'][] = array(
                $id, 
                $companies,
                $channels,
                $price_rules,
                $date_created,
                $date_enabled,
                $date_disabled,
                $date_updated,
                "<input type='checkbox' name='my-checkbox' $checked class='col-md-12' data-bootstrap-switch onchange='toggleStatus($id);'>",
                $actions
            );
        }

        echo json_encode($data);
    }

    public function toggleStatus()
    {
        $success = false;
        $postdata = $this->postClean();
        if (isset($postdata['id']) && ($postdata['id'] != "") && (is_numeric($postdata['id']))) {
            $success = $this->model_shipping_price_rules->toggleStatus($postdata['id']);
        }

        print_r(json_encode($success));
    }

    public function deleteRule()
    {
        $success = false;
        $postdata = $this->postClean();
        if (isset($postdata['id']) && ($postdata['id'] != "") && (is_numeric($postdata['id']))) {
            $success = $this->model_shipping_price_rules->deleteRule($postdata['id']);
        }

        print_r(json_encode($success));
    }

    public function saveRules()
    {
        $success = false;
        $postdata = $this->postClean();
        if (
            (isset($postdata['rule_index']) && ($postdata['rule_index'] != "")) &&
            (isset($postdata['shipping']) && ($postdata['shipping'] != "")) &&
            (isset($postdata['channels']) && ($postdata['channels'] != "")) &&
            (isset($postdata['range']) && ($postdata['range'] != ""))
        ) {
            $data['id'] = $postdata['rule_index'];
            $data['table_shipping_ids'] = $postdata['shipping'];
            $data['mkt_channels_ids'] = $postdata['channels'];
            $data['price_range'] = $postdata['range'];

            $success = $this->model_shipping_price_rules->saveRules($data);
        }

        print_r(json_encode($success));
    }

    public function loadRule()
    {
        $success = false;
        $postdata = $this->postClean();
        if (isset($postdata['id']) && ($postdata['id'] != "") && ($postdata['id'] != "[NA]")) {
            $response = $this->model_shipping_price_rules->loadRule($postdata['id']);

            if ($response[0]) {
                // Montagem dos selects.
                $success = '<label for="shipping_companies" class="col-md-4">
                    Transportadoras <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                </label><br/>
                <select class="form-control selectpicker show-tick" id="shipping_companies" name ="shipping_companies[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="' . $this->lang->line("application_select") . '">';

                $shipping_companies_ids = explode(";", $response[0]['table_shipping_ids']);

                if ($this->ms_freight_tables->use_ms_shipping) {
                    try {
                        $shipping_companies = $this->ms_freight_tables->getShippingCompanies();
                    } catch (Exception $exception) {
                        $shipping_companies = array($exception->getMessage());
                    }
                } else {
                    $shipping_companies = $this->model_shipping_price_rules->getShippingCompanies();
                }

                foreach ($shipping_companies as $shipping_company) {
                    $shipping_company_id    = $this->ms_freight_tables->use_ms_shipping ? $shipping_company->id : $shipping_company['id'];
                    $shipping_company_name  = $this->ms_freight_tables->use_ms_shipping ? $shipping_company->name : $shipping_company['name'];
                    $selected               = in_array($shipping_company_id, $shipping_companies_ids) ? 'selected' : '';

                    $success .= "<option value='$shipping_company_id' $selected>$shipping_company_name</option>";
                }

                $shipping_integrations = $this->model_shipping_price_rules->getShippingIntegrations();
                foreach ($shipping_integrations as $si_value) {
                    $aux_id = '100000' . $si_value['id'];
                    if (in_array($aux_id, $shipping_companies_ids)) {
                        $success .= '<option value="' . $aux_id . '" selected>' . $si_value['name'] . '</option>';
                    } else {
                        $success .= '<option value="' . $aux_id . '">' . $si_value['name'] . '</option>';
                    }
                }

                $success .= '</select>

                <label for="mkt_channels" class="col-md-4" style="padding-top: 20px;">
                    Canais de Vendas <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                </label><br/>
                <select class="form-control selectpicker show-tick" id="mkt_channels" name ="mkt_channels[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="' . $this->lang->line("application_select") . '">';

                $mkt_channels_ids = explode(";", $response[0]['mkt_channels_ids']);
                $mkt_channels = $this->model_shipping_price_rules->getIntegrations();
                foreach ($mkt_channels as $mc_key => $mc_value) {
                    if (in_array($mc_value['id'], $mkt_channels_ids)) {
                        $success .= '<option value="' . $mc_value['id'] . '" selected>' . $mc_value['name'] . '</option>';
                    } else {
                        $success .= '<option value="' . $mc_value['id'] . '">' . $mc_value['name'] . '</option>';
                    }
                }
                $success .= '</select>

                <div class="row" id="rule" style="padding-top: 25px;">
                    <div class="col-md-4" style="text-align: center; font-size: 18px; font-weight: bold; padding-bottom: 10px;">
                        Faixa de preço de produto (em R$)
                    </div>

                    <div class="col-md-6" style="text-align: center; font-size: 18px; font-weight: bold; padding-bottom: 10px;">
                        Taxas Adicionais (em %)
                    </div>
                </div>

                <div class="row" id="rule">
                    <!-- Maior que -->
                    <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                        <label for="maior_que" class="normal">' .
                            $this->lang->line("application_shipping_price_greater_than") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                        </label>
                    </div>

                    <!-- Menor que -->
                    <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                        <label for="menor_que" class="normal">' .
                            $this->lang->line("application_shipping_price_less_than") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                        </label>
                    </div>

                    <!-- Custo Marketplace -->
                    <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                        <label for="custo_mkt" class="normal">' .
                            $this->lang->line("application_shipping_price_mkt_cost") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                        </label>
                    </div>

                    <!-- Custo RMA -->
                    <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                        <label for="custo_rma" class="normal">' .
                            $this->lang->line("application_shipping_price_rma_cost") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                        </label>
                    </div>

                    <!-- Margem de Frete -->
                    <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                        <label for="margem_frete" class="normal">' .
                            $this->lang->line("application_shipping_price_shipping_margin") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                        </label>
                    </div>

                    <!-- Ação -->
                    <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                        <label for="action" class="normal">' . $this->lang->line("application_shipping_price_actions") . '</label>
                    </div>
                </div>';

                // Montagem dos campos de texto.
                $cont = 0;
                $price_range = explode(";", $response[0]['price_range']);
                $price_range_length = sizeof($price_range);
                foreach ($price_range as $pr) {
                    $rules = explode(",", $pr);

                    $success .= ' 
                    <div class="row" id="rule-' . $cont . '">
                        <!-- Maior que -->
                        <div class="col-md-2" style="padding-bottom: 6px;">
                            <input type="text" id="maior_que' . $cont . '" class="form-control" placeholder="R$" onblur="checkValues(' . $cont . ');" value="' . substr(money($rules[0]), 3) . '">
                        </div>
    
                        <!-- Menor que -->
                        <div class="col-md-2">
                            <input type="text" id="menor_que' . $cont . '" class="form-control" placeholder="R$" onblur="checkValues(' . $cont . ');" value="' . substr(money($rules[1]), 3) . '">
                        </div>
    
                        <!-- Custo Marketplace -->
                        <div class="col-md-2">
                            <input type="text" id="custo_mkt' . $cont . '" class="form-control" placeholder="%" onblur="checkValues(' . $cont . ');" value="' . $rules[2] . '">
                        </div>
    
                        <!-- Custo RMA -->
                        <div class="col-md-2">
                            <input type="text" id="custo_rma' . $cont . '" class="form-control" placeholder="%" onblur="checkValues(' . $cont . ');" value="' . $rules[3] . '">
                        </div>
    
                        <!-- Margem de Frete -->
                        <div class="col-md-2">
                            <input type="text" id="margem_frete' . $cont . '" class="form-control" placeholder="%" onblur="checkValues(' . $cont . ');" value="' . $rules[4] . '">
                        </div>
    
                        <!-- Ação -->
                        <div class="col-md-2">
                            <button type="button" id="remover_regra' . $cont . '" class="btn btn-primary" onclick="deleteRules(' . $cont . ');">-</button> ';

                        if ($cont == ($price_range_length - 1)) {
                            $success .= '<button type="button" id="adicionar_regra' . $cont . '" class="btn btn-primary" onclick="createRules();">+</button>';
                        }

                        $success .= '
                        </div>
                    </div>

                    <div id="current_msg' . $cont . '" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
                    <div id="cross_current_msg' . $cont . '" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
                    <div id="cross_previous_msg' . $cont . '" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
                    <div id="pdg' . $cont . '" style="padding-bottom: 25px; display: none;"></div>';

                    if ($cont == ($price_range_length - 1)) {
                        $success .= '<div id="next"></div>';
                    }

                    $cont++;
                }
            }
        } else {
            $response = $this->model_shipping_price_rules->loadRule('[NA]');

            $success = '<label for="shipping_companies" class="col-md-4">
                Transportadoras <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
            </label><br/>
            <select class="form-control selectpicker show-tick" id="shipping_companies" name ="shipping_companies[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="' . $this->lang->line("application_select") . '">';

            $shipping_companies = $this->model_shipping_price_rules->getShippingCompanies();
            foreach ($shipping_companies as $sc_key => $sc_value) {
                $success .= '<option value="' . $sc_value['id'] . '">' . $sc_value['name'] . '</option>';
            }

            $shipping_integrations = $this->model_shipping_price_rules->getShippingIntegrations();
            foreach ($shipping_integrations as $si_key => $si_value) {
                $aux_id = '100000' . $si_value['id'];
                $success .= '<option value="' . $aux_id . '">' . $si_value['name'] . '</option>';
            }

            $success .= '</select>

            <label for="mkt_channels" class="col-md-4" style="padding-top: 20px;">
                Canais de Vendas <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
            </label><br/>
            <select class="form-control selectpicker show-tick" id="mkt_channels" name ="mkt_channels[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="' . $this->lang->line("application_select") . '">';

            $mkt_channels = $this->model_shipping_price_rules->getIntegrations();
            foreach ($mkt_channels as $mc_key => $mc_value) {
                $success .= '<option value="' . $mc_value['id'] . '">' . $mc_value['name'] . '</option>';
            }
            $success .= '</select>

            <div class="row" id="rule" style="padding-top: 25px;">
                <div class="col-md-4" style="text-align: center; font-size: 18px; font-weight: bold; padding-bottom: 10px;">
                    Faixa de preço de produto (em R$)
                </div>

                <div class="col-md-6" style="text-align: center; font-size: 18px; font-weight: bold; padding-bottom: 10px;">
                    Taxas Adicionais (em %)
                </div>
            </div>

            <div class="row" id="rule">
                <!-- Maior que -->
                <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                    <label for="maior_que" class="normal">' .
                        $this->lang->line("application_shipping_price_greater_than") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                    </label>
                </div>

                <!-- Menor que -->
                <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                    <label for="menor_que" class="normal">' .
                        $this->lang->line("application_shipping_price_less_than") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                    </label>
                </div>

                <!-- Custo Marketplace -->
                <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                    <label for="custo_mkt" class="normal">' .
                        $this->lang->line("application_shipping_price_mkt_cost") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                    </label>
                </div>

                <!-- Custo RMA -->
                <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                    <label for="custo_rma" class="normal">' .
                        $this->lang->line("application_shipping_price_rma_cost") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                    </label>
                </div>

                <!-- Margem de Frete -->
                <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                    <label for="margem_frete" class="normal">' .
                        $this->lang->line("application_shipping_price_shipping_margin") . ' <span data-toggle="tooltip" data-placement="top" title="Campo de preenchimento obrigatório">*</span>
                    </label>
                </div>

                <!-- Ação -->
                <div class="col-md-2" style="text-align: center; padding-bottom: 6px;">
                    <label for="action" class="normal">' . $this->lang->line("application_shipping_price_shipping_actions") . '</label>
                </div>
            </div>

            <div class="row" id="rule-0">
                <!-- Maior que -->
                <div class="col-md-2" style="padding-bottom: 6px;">
                    <input type="text" id="maior_que0" class="form-control" placeholder="R$" onblur="checkValues(0);">
                </div>

                <!-- Menor que -->
                <div class="col-md-2">
                    <input type="text" id="menor_que0" class="form-control" placeholder="R$" onblur="checkValues(0);">
                </div>

                <!-- Custo Marketplace -->
                <div class="col-md-2">
                    <input type="text" id="custo_mkt0" class="form-control" placeholder="%" onblur="checkValues(0);">
                </div>

                <!-- Custo RMA -->
                <div class="col-md-2">
                    <input type="text" id="custo_rma0" class="form-control" placeholder="%" onblur="checkValues(0);">
                </div>

                <!-- Margem de Frete -->
                <div class="col-md-2">
                    <input type="text" id="margem_frete0" class="form-control" placeholder="%" onblur="checkValues(0);">
                </div>

                <!-- Ação -->
                <div class="col-md-2">
                    <button type="button" id="adicionar_regra0" class="btn btn-primary" onclick="createRules();">+</button>
                </div>
            </div>

            <div id="current_msg0" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
            <div id="cross_current_msg0" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
            <div id="cross_previous_msg0" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
            <div id="pdg0" style="padding-bottom: 25px; display: none;"></div>

            <div id="next"></div>';
        }

        print_r(json_encode($success));
    }
}
