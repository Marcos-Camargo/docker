<?php
use App\Libraries\Enum\CampaignSegment;
use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\ComissionRuleEnum;
use App\Libraries\Enum\DiscountTypeEnum;
use App\Libraries\Enum\PaymentGatewayEnum;

/*<link rel="stylesheet" href="<?=base_url()?>/assets/tracking/css/bootstrap.min.css">*/ ?>
<link rel="stylesheet" type="text/css" href="<?=base_url()?>/assets/dist/css/views/campaign_v2/styles.css">
<!--https://github.com/mengxiong10/vue2-datepicker-->
<link rel="stylesheet" href="<?php echo base_url('assets/vue/datetime_picker/index.css') ?>">

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/index.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/pt-br.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/money/v-money.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>

<script src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/pipeline.js') ?>" type="text/javascript"></script>

<script>
    $(function()
    {
        $('.product-type').click(function()
        {
            var id = $(this).attr('id').split('-')[2];
            if (id)
            {
                $('.product-type').removeClass('active');
                $('#product-type-' + id).addClass('active');
            }
        });

        // $('#discount_type').val('discount_percentage').change();
    });
</script>

<style>
    .dropdown-menu.open {
        max-width: 100%;
    }

    .inner.open{
        max-height: 500px !important;
    }
</style>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php
    $data['pageinfo'] = "";
    if (isset($data['page_now_selected'])){ $data['page_now'] = $data['page_now_selected']; }
    $this->load->view('templates/content_header', $data);
    ?>

    <!-- Main content -->
    <section class="content" id="app">

        <!-- Small boxes (Stat box) -->
        
        <div class="row">
            <?php
            if ($only_admin && $usercomp == 1 && !$userstore){
            ?>
                <div class="col-md-12 col-xs-12" v-if="!entry.b2w_type">
                    <?php
                    if ($allow_create_campaigns_b2w_type) {
                        ?>
                        <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=b2wcampaign') ?>" class="btn btn-primary mb-3 mt-3 pull-right">
                            <i class="fa fa-plus"></i>
                            <?= lang('application_add_campaign_v2_b2w'); ?>
                        </a>
                        <?php
                    }
                    ?>
                </div>
                <div class="col-md-12 col-xs-12" v-if="entry.b2w_type">
                    <a href="<?php echo base_url('campaigns_v2/createcampaigns') ?>" class="btn btn-primary mb-3 mt-3 pull-right">
                        <i class="fa fa-plus"></i>
                        <?= lang('application_add_campaign_v2'); ?>
                    </a>
                </div>
            <?php
            }
            ?>
            <?php if (in_array('sellerCampaignCreation', $user_permission)) { ?>
                <div class="col-md-12 col-xs-12" v-if="!entry.seller_type">
                    <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=sellerCampaign') ?>" class="btn btn-primary mb-3 mt-3 pull-right">
                        <i class="fa fa-plus"></i>
                        <?= lang('application_add_campaign_v2_seller'); ?>
                    </a>
                </div>
            <?php } ?>
            </div>
       
        <div class="row">

            <div class="col-md-12 col-xs-12">

                <div class="alert" v-bind:class="generateClassSubmitResponse()" role="alert" v-show="submitResponse" style="display: none;">
                    <button type="button" class="close" @click="closeSubmitDialog()">
                    <span aria-hidden="true">&times;</span></button>
                    <span v-html="submitResponse.message"></span>
                </div>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <form role="form" id="formCadastro" method="post" v-on:submit.prevent="onSubmit">


                    <div class="form-group col-md-4 col-xs-4" style="display: none">
                        <label for="campaign_type"><?=$this->lang->line('application_campaign_type')?> *</label>
                        <select v-model.trim="entry.campaign_type"

                                @change="changeCampaignType"
                                class="form-control selectpicker show-tick"
                                data-live-search="true"
                                id="campaign_type" :disabled="allInputsDisabled || entry.b2w_type" :readonly="allInputsDisabled || entry.b2w_type">
                            <?php foreach ($campaign_types as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                            <?php endforeach ?>
                        </select>
<!--                        braun hack -> removendo input a pedido do dilnei-->
<!--                        @input="changeCampaignType"-->
                    </div>




                    <!-- 1 - dados gerais -->
                    <div class="box px-5 py-5 mt-4">

                        <div class="box-header with-border">

                            <div class="col-md-1 px-0 num-list">
                                1
                            </div>
                            <div class="col-md-5">
                                <h3 class="box-title num-list-title"><?= $this->lang->line('application_general_info'); ?></h3>
                            </div>

                        </div>

                        <div class="box-body">

                            <div class="row pr-0" style="display: flex;flex-wrap: wrap;">

                                <div class="col-md-9 pr-5"  style="display: flex; flex-direction: column;">

                                    <div class="row" >

                                        <div class="form-group <?php echo (count($marketplaces) > 1) ? 'col-md-8' : 'col-md-12'; ?>">
                                            <label for="name"><?= $this->lang->line('application_name_campaign'); ?> *</label>
                                            <input :disabled="allInputsDisabled" :readonly="allInputsDisabled" v-model.trim="entry.name" type="text" class="form-control" id="name" autocomplete="off"
                                                   placeholder="<?= $this->lang->line('application_enter_name_campaign') ?>" maxlength="100" />
                                        </div>

                                        <?php if (count($marketplaces) > 1): ?>

                                            <div class="form-group col-md-4">
                                                <label for="marketplaces"><?= $this->lang->line('application_marketplaces') ?> *</label>
                                                <select v-model.trim="entry.marketplaces"

                                                        @change="changeMarketplace"
                                                        class="form-control selectpicker show-tick" data-live-search="true"
                                                        data-actions-box="true" id="marketplaces" :disabled="allInputsDisabled || entry.b2w_type" :readonly="allInputsDisabled || entry.b2w_type" multiple="multiple">
                                                    <?php foreach ($marketplaces as $marketplace): ?>
                                                        <option value="<?php echo $marketplace['int_to'] ?>"><?php echo $marketplace['name'] ?></option>
                                                    <?php endforeach ?>
                                                </select>

<!--                                                braun hack -> removendo input-->
<!--                                                @input="changeMarketplace"-->
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="form-group col-md-7">
                                                    <label for="start_date"  style="white-space: nowrap;"><?= $this->lang->line('application_start_date'); ?>(*)</label><br/>
                                                    <date-picker id="start_date"
                                                                 v-model.trim="entry.start_date"
                                                                 type="date"
                                                                 value-type="YYYY-MM-DD"
                                                                 format="DD/MM/YYYY"
                                                                 :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                    ></date-picker>
                                                </div>
                                                <div class="form-group col-md-5">
                                                    <label for="start_date_pick_time" style="white-space: nowrap;"><?= $this->lang->line('application_start_time'); ?>(*)</label><br/>
                                                    <date-picker id="start_time"
                                                                 v-model.trim="entry.start_time"
                                                                 type="time"
                                                                 value-type="HH:mm"
                                                                 format="HH:mm"
                                                                 :minute-options="[00,05,10,15,20,25,30,35,40,45,50,55]"
                                                                 :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                    ></date-picker>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group col-md-7">
                                                <label for="end_date"  style="white-space: nowrap;"><?= $this->lang->line('application_end_date'); ?>(*)</label><br/>
                                                <date-picker id="end_date"
                                                             v-model.trim="entry.end_date"
                                                             type="date"
                                                             value-type="YYYY-MM-DD"
                                                             format="DD/MM/YYYY"
                                                             :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                ></date-picker>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label for="end_date_pick_time"  style="white-space: nowrap;"><?= $this->lang->line('application_end_time'); ?>(*)</label><br/>
                                                <date-picker id="end_time"
                                                             v-model.trim="entry.end_time"
                                                             type="time"
                                                             value-type="HH:mm"
                                                             format="HH:mm"
                                                             :minute-options="[05,10,15,20,25,30,35,40,45,50,55]"
                                                             :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                ></date-picker>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group col-md-12">
                                                <label for="deadline_for_joining" style="width: 100%; white-space: nowrap;"><?= $this->lang->line('application_deadline_for_joining'); ?></label><br/>
                                                <date-picker id="deadline_for_joining"
                                                            v-model.trim="entry.deadline_for_joining"
                                                            type="datetime"
                                                            value-type="YYYY-MM-DD HH:mm"
                                                            format="DD/MM/YYYY - HH:mm"
                                                            :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                ></date-picker>
                                            </div>
                                        </div>
                                </div>

                                </div>

                                <div class="col-md-3 pr-0"   style="display: flex; flex-direction: column;">

                                    <div class="row" style="display: flex; flex-wrap: wrap; height: 100%;">
                                        <div class="col-md-1 px-0" style="display: flex; flex-direction: column; background-color: #ccc; width: 1px;"></div>
                                        <div class="form-group pl-5 col-md-11 col-xs-11 <?php echo (form_error('description')) ? 'has-error' : ''; ?> " style="display: flex; flex-direction: column;">
                                            <label for="description"><?= $this->lang->line('application_description'); ?></label>
                                            <textarea :disabled="allInputsDisabled" :readonly="allInputsDisabled" v-model.trim="entry.description" class="form-control" rows="5" id="description" autocomplete="off" placeholder="<?= $this->lang->line('application_enter_description') ?>"></textarea>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div><!-- box body -->

                    </div>
                    <!-- fim do item 1 -->





                    <!-- 2 - Regras -->
                    <div class="box px-5 py-5">

                        <div class="box-header with-border">

                            <div class="col-md-1 px-0 num-list">
                                2
                            </div>
                            <div class="col-md-5">
                                <h3 class="box-title  num-list-title"><?= $this->lang->line('application_rules'); ?></h3>
                            </div>

                        </div>

                        <div class="box-body">

                            <div class="row">

                                <div class="col-md-12">

                                    <div class="row">

                                        <div class="form-group col-md-3" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type)">
                                            <label for="campaign_type"><?=$this->lang->line('application_discount_type')?></label><br/>
                                            <select v-model.trim="entry.discount_type" class="form-control" id="discount_type" :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                    @change="changeDiscountType">
                                                <?php foreach ($discount_types as $key => $name): ?>
                                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-3" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type) && entry.discount_type == 'discount_percentage'">

                                            <label for="fixed_discount" v-if="['shared_discount'].includes(entry.campaign_type)">
                                                <?=$this->lang->line('application_discount_percentage_total')?>
                                            </label>
                                            <label for="fixed_discount" v-if="['merchant_discount'].includes(entry.campaign_type)">
                                                <?=$this->lang->line('application_discount_percentage_total_seller')?>
                                            </label>
                                            <label for="fixed_discount" v-if="['channel_funded_discount'].includes(entry.campaign_type)">
                                                <?=$this->lang->line('application_discount_percentage_total_marketplace')?>
                                            </label>
                                            <br/>
                                            <div class="input-group">
                                                <input v-model.trim="entry.discount_percentage"
                                                       id="discount_percentage"
                                                       type="number"
                                                       step="1"
                                                       min="1"
                                                       max="100"
                                                       oninput="this.value=(parseInt(this.value)||0)"
                                                       onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                       class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled" />
                                                <div class="input-group-addon">
                                                    %
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group col-md-2" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type) && entry.discount_type == 'fixed_discount'">

                                            <label for="fixed_discount" v-if="['shared_discount'].includes(entry.campaign_type)">
                                                <?=$this->lang->line('application_fixed_discount_total')?>
                                            </label>
                                            <label for="fixed_discount" v-if="['merchant_discount'].includes(entry.campaign_type)">
                                                <?=$this->lang->line('application_fixed_discount_total_seller')?>
                                            </label>
                                            <label for="fixed_discount" v-if="['channel_funded_discount'].includes(entry.campaign_type)">
                                                <?=$this->lang->line('application_fixed_discount_total_marketplace')?>
                                            </label>
                                            <br/>
                                            <money v-model.trim="entry.fixed_discount"
                                                   v-bind="money"
                                                   @change="changeDiscountType"
                                                   @input="changeDiscountType"
                                                   id="fixed_discount"
                                                   class="form-control"
                                                   :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                        </div>


                                        <div class="col-md-6" v-if="['shared_discount'].includes(entry.campaign_type)">
                                            <div class="form-group col-md-1 pt-5 text-center fa-green" v-if="['shared_discount'].includes(entry.campaign_type)">
                                                <i class="fa fa-equals"></i>
                                            </div>
                                            <div class="form-group col-md-5" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">

                                                <label for="seller_discount_percentual"><?=$this->lang->line('application_discount_percentage_total_seller')?></label>
                                                <br/>
                                                <div class="input-group">
                                                    <input v-model.trim="entry.seller_discount_percentual"
                                                           id="seller_discount_percentual" type="number" step="1" min="1" max="100"
                                                           oninput="this.value=(parseInt(this.value)||0)"
                                                           onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                           class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled" />
                                                    <div class="input-group-addon">
                                                        %
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-md-5" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">

                                                <label for="seller_discount_fixed"><?=$this->lang->line('application_fixed_discount_total_seller')?></label>
                                                <br/>
                                                <money v-model.trim="entry.seller_discount_fixed"
                                                       v-bind="money"
                                                       id="seller_discount_fixed"
                                                       class="form-control"
                                                       :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                            </div>

                                            <div class="form-group col-md-1 pt-5 text-center fa-green" v-if="['shared_discount'].includes(entry.campaign_type)">
                                                <i class="fa fa-plus"></i>
                                            </div>

                                            <div class="form-group col-md-5" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">
                                                <label for="marketplace_discount_percentual"><?=$this->lang->line('application_discount_percentage_total_marketplace')?></label>
                                                <div class="input-group">
                                                    <input v-model.trim="entry.marketplace_discount_percentual" id="marketplace_discount_percentual"
                                                           type="number" step="1" min="1" max="100"
                                                           oninput="this.value=(parseInt(this.value)||0)"
                                                           onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                           class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled" />
                                                    <div class="input-group-addon">
                                                        %
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group col-md-5" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                <label for="marketplace_discount_fixed"><?=$this->lang->line('application_fixed_discount_total_marketplace')?></label><br/>
                                                <money v-model.trim="entry.marketplace_discount_fixed"
                                                       v-bind="money"
                                                       id="marketplace_discount_fixed"
                                                       class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                            </div>
                                        </div>



                                                <div v-if="['channel_funded_discount'].includes(entry.campaign_type)">

                                                    <div class="form-group col-md-3">
                                                        <label for="fixed_discount_from"><?=$this->lang->line('application_participating_comission')?></label><br/>
                                                        <div class="input-group">
                                                            <div class="input-group-addon">
                                                                <?=$this->lang->line('application_from')?>
                                                            </div>
                                                            <input v-model.trim="entry.participating_comission_from"
                                                                   id="participating_comission_from"
                                                                   type="number"
                                                                   step="1"
                                                                   min="1"
                                                                   max="100" class="form-control"
                                                                   @change="changeParticipatingComission"
                                                                   @input="changeParticipatingComission"
                                                                   :disabled="allInputsDisabled" :readonly="allInputsDisabled" />
                                                            <div class="input-group-addon">
                                                                %
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>&nbsp;</label>
                                                        <div class="input-group">
                                                            <div class="input-group-addon">
                                                                <?=$this->lang->line('application_to')?>
                                                            </div>
                                                            <input v-model.trim="entry.participating_comission_to"
                                                                   id="participating_comission_to"
                                                                   type="number"
                                                                   step="1"
                                                                   min="1"
                                                                   max="100"
                                                                   class="form-control"
                                                                   @change="changeParticipatingComission"
                                                                   @input="changeParticipatingComission"
                                                                   :disabled="allInputsDisabled"
                                                                   :readonly="allInputsDisabled" />
                                                            <div class="input-group-addon">
                                                                %
                                                            </div>
                                                        </div>
                                                    </div>

<!--                                                </div>-->
<!--                                            </div>-->

                                            <?php
                                            if(isset($use_payment_methods) && $use_payment_methods && $only_admin) {
                                                ?>
                                                <div class="row">

                                                    <div class="form-group col-md-4 col-xs-4" v-show="paymentMethodsOptionsAvailable.length > 0 && entry.campaign_type.length > 0 && ['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                                        <label for="paymentMethods"><?= $this->lang->line('application_payment_methods') ?></label><br/>
                                                        <select v-model.trim="entry.paymentMethods" class="form-control selectpicker show-tick" data-live-search="true"
                                                                data-actions-box="true" id="paymentMethods" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                                                            <option v-for="paymentMethodOptionAvailable in paymentMethodsOptionsAvailable" :value="paymentMethodOptionAvailable.method_id">{{paymentMethodOptionAvailable.method_name}}</option>
                                                        </select>
                                                    </div>

                                                </div>
                                                <?php
                                            }

                                            if(isset($use_trade_policies) && $use_trade_policies && $only_admin) {
                                                ?>
                                                <div class="row">

                                                    <div class="form-group col-md-4 col-xs-4" v-show="tradePoliciesOptionsAvailable.length > 0 && entry.campaign_type.length > 0 && ['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                                        <label for="tradePolicies"><?= $this->lang->line('application_trade_policies') ?></label><br/>
                                                        <select v-model.trim="entry.tradePolicies" class="form-control selectpicker show-tick" data-live-search="true"
                                                                data-actions-box="true" id="tradePolicies" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                                                            <option v-for="tradePolicyOptionAvailable in tradePoliciesOptionsAvailable" :value="tradePolicyOptionAvailable.trade_policy_id">{{tradePolicyOptionAvailable.trade_policy_id}} - {{tradePolicyOptionAvailable.trade_policy_name}}</option>
                                                        </select>
                                                    </div>

                                                </div>
                                                <?php
                                            }
                                            ?>




                                        </div><!-- col md 12 -->





                                    </div>

                                </div><!-- col md 12 -->


                                <div class="col-md-12">

                                    <div class="row" v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">

                                        <div class="form-group col-md-2">

                                            <label for="comission_rule"><?=$this->lang->line('application_comission_rule')?></label><br/>

                                            <select id="comission_rule" v-model.trim="entry.comission_rule" class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                                <option v-for="(name, value) in comissionRules" :value="value">{{name}}</option>
                                            </select>

                                        </div>

                                        <div class="form-group col-md-2">

                                            <div v-if="entry.comission_rule == 'new_comission'">

                                                <label for="comission_rule"><?=$this->lang->line('application_new_comission')?></label><br/>
                                                <div class="input-group">
                                                    <money maxlength="6"
                                                           v-model.trim="entry.new_comission"
                                                           v-bind="percentage"
                                                           class="form-control"
                                                           placeholder="<?php echo $this->lang->line('application_new_comission');?>"
                                                           :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                                    <div class="input-group-addon">
                                                        %
                                                    </div>
                                                </div>

                                            </div>

                                            <div v-if="entry.comission_rule == 'comission_rebate'">
                                                <label for="comission_rule"><?=$this->lang->line('application_comission_rebate')?></label><br/>
                                                <money v-model.trim="entry.rebate_value"
                                                       v-bind="money"
                                                       class="form-control"
                                                       placeholder="<?php echo $this->lang->line('application_comission_rebate');?>"
                                                       :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-12">

                                    <div class="row">

                                        <div class="form-group col-md-3 col-xs-3" v-if="['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                            <label for="product_min_value"><?=$this->lang->line('application_prod_min_value')?></label><br/>
                                            <money v-model.trim="entry.product_min_value"
                                                   v-bind="money"
                                                   id="product_min_value"
                                                   class="form-control"
                                                   :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                        </div>

                                        <div class="form-group col-md-3 col-xs-3" v-if="['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                            <label for="product_min_quantity"><?=$this->lang->line('application_product_min_stock_quantity')?></label><br/>
                                            <input v-model.trim="entry.product_min_quantity" id="product_min_quantity" type="number" step="1" min="0" class="form-control"
                                                   :disabled="allInputsDisabled" :readonly="allInputsDisabled"/>
                                        </div>

                                        <?php if($this->input->get('defaultType') !== "sellerCampaign" && (isset($campaign['seller_type']) && $campaign['seller_type'] == 0)){ ?>
                                            <div class="form-group col-md-2" v-show="['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">

                                                <label for="seller_index"><?= $this->lang->line('application_seller_index') ?> >=</label>
                                                <br/>
                                                <input type="number" class="form-control" id="seller_index" v-model.trim="entry.min_seller_index"
                                                       @change="validateMaxSellerIndex"
                                                       @input="validateMaxSellerIndex"
                                                       :min="1" :max="5"
                                                       placeholder="<?= $this->lang->line('application_enter_the_seller_index') ?>" autocomplete="off"
                                                       :disabled="allInputsDisabled" :readonly="allInputsDisabled">

                                            </div>
                                        <?php } ?>

                                    </div>

                                </div><!-- col md 12 -->

                            </div><!-- row -->

                        </div><!-- box 2 -->

                    </div>
                    <!-- fim di item 2 -->





                    <!-- item 3 -->
                    <div class="box px-5 py-5" v-show="entry.campaign_type">

                        <div class="box-header with-border">

                            <div class="col-md-1 px-0 num-list">
                                3
                            </div>
                            <div class="col-md-5">
                                <h3 class="box-title  num-list-title"><?= $this->lang->line('application_criteria_participate'); ?></h3>
                            </div>

                        </div>


                        <div class="box-body">

                            <div class="row" v-if="['channel_funded_discount'].includes(entry.campaign_type)" v-show="!allInputsDisabled">
                                <div class="form-group col-md-12">
                                    <?php
                                    if ($sellercenter != 'conectala' && $payment_gateway_id == PaymentGatewayEnum::PAGARME){
                                    ?>
                                        <div class="alert alert-warning" role="alert">
                                            <p>
                                                <?php echo $this->lang->line('application_your_gateway_is_pagarme');?>
                                            </p>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- novo menu de escolha de adesao de produtos -->
                            <div class="row">

                                <div class="col-md-12" style="display: none">
                                    <label for="segment"><?=lang('application_campaign_segment_by')?> *</label><br/>
                                    <select v-model.trim="entry.segment" class="select-opened"

                                            @change="changeSegment"
                                            :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                        <option class="select-opened-item" v-for="(name, value) in segments" :value="value">{{name}}</option>
                                    </select>
<!--                                    braun hack -. removendo input a pedido do dilnei-->
<!--                                    @input="changeSegment"-->
                                </div>

                                <ul class="nav nav-pills  nav-justified" style="border: 1px solid #ccc; border-radius: 5px;">
                                    <li class="product-type active" id="product-type-store" @click="changeSegmentClick('store')"><a href="#0"><?=$this->lang->line('application_store')?></a></li>
                                    <li class="product-type" id="product-type-category"><a href="#0"  @click="changeSegmentClick('category')"><?=$this->lang->line('application_category')?></a></li>
                                    <li class="product-type" id="product-type-product" @click="changeSegmentClick('product')"><a href="#0"><?=$this->lang->line('application_product')?></a></li>
                                </ul>

                            </div>



                            <!-- adesao por loja -->
                            <div class="row" v-show="entry.segment == '<?php echo CampaignSegment::STORE; ?>' && page == ''">

                                <ul class="nav nav-tabs mt-5" role="tablist" id="store-tabs">
                                    <li class="active" role="presentation" ><a class="nav-item nav-link" href="#store_individual"  data-toggle="tab"><?=$this->lang->line('campaigns_v2_nav_tab_single_include')?></a></li>
                                    <li role="presentation" ><a class="nav-item nav-link" href="#store_mass" data-toggle="tab" id="link-store-add-mass"><?=$this->lang->line('campaigns_v2_nav_tab_mass_include')?></a></li>
                                </ul>

                                <div class="tab-content campaign-tab-content p-5" id="store-content">

                                    <div class="tab-pane fade in active" id="store_individual" role="tabpanel">

                                        <div class="row">
                                            <div class="col-md-12">
                                                <label for="stores"><?=$this->lang->line('application_participating_stores');?> *</label>
                                                <br/>
                                                <select v-model.trim="entry.stores" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="stores" multiple="multiple">
                                                    <option v-for="store in filteredStores" :value="store.id">{{store.name}}</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade" id="store_mass" role="tabpanel">

                                        <div class="row" id="store-instructions-row">

                                            <div class="col-md-3" >

                                                <div class="box instructions-box">

                                                    <div class="box-header"  id="store-instructions-box">
                                                        <div class="row px-5 pt-3">
                                                            <div class="col-md-2  icon">
                                                                !
                                                            </div>
                                                            <div class="col-md-10 p-0 pl-4 mt-0 ">
                                                                <h3 class="instructions-box-title m-0"><?= $this->lang->line('campaigns_v2_instructions_box_store_mass'); ?></h3>
                                                            </div>
                                                            <div class="col-md-12 mt-5">
                                                                <?=$this->lang->line('campaigns_v2_information_import_rules_product_1')?>
                                                            </div>
                                                            <div class="col-md-12 my-5">
                                                                <?=$this->lang->line('campaigns_v2_information_import_rules_product_2')?>
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="col-md-9">
                                                <div class="row">

                                                    <div class="col-md-4 py-5">

                                                        <div class="col-md-12">

                                                            <div class="num-list-before ">
                                                                <h3 class="box-title num-list-title my-0"><?= $this->lang->line('application_step'); ?></h3>
                                                            </div>
                                                            <div class="num-list num-list-after ml-3">
                                                                1
                                                            </div>

                                                        </div>

                                                        <div class="col-md-12 mt-1">
                                                            <h3 class="instructions-box-title m-0"><?=$this->lang->line('download_example_xls')?></h3>
                                                        </div>

                                                        <div class="col-md-12 mt-5">
                                                            <?=$this->lang->line('campaigns_v2_information_import_rules_product_model_rules')?>
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4 py-5">

                                                        <div class="col-md-12">

                                                            <div class="num-list-before ">
                                                                <h3 class="box-title num-list-title my-0"><?= $this->lang->line('application_step'); ?></h3>
                                                            </div>
                                                            <div class="num-list num-list-after ml-3">
                                                                2
                                                            </div>

                                                        </div>

                                                        <div class="col-md-12 mt-1">
                                                            <h3 class="instructions-box-title m-0"><?= $this->lang->line('campaigns_v2_information_import_rules_product_download_registers'); ?></h3>
                                                        </div>

                                                        <div class="col-md-12 mt-5">
                                                            <?= $this->lang->line('campaigns_v2_information_import_rules_product_download_registers_info'); ?>
                                                        </div>


                                                    </div>


                                                    <div class="col-md-4 py-5">

                                                        <div class="col-md-12">

                                                            <div class="num-list-before ">
                                                                <h3 class="box-title num-list-title my-0"><?= $this->lang->line('application_step'); ?></h3>
                                                            </div>
                                                            <div class="num-list num-list-after ml-3">
                                                                3
                                                            </div>
                                                        </div>

                                                        <div class="col-md-12 mt-1">
                                                            <h3 class="instructions-box-title m-0"><?= $this->lang->line('application_load_file'); ?></h3>
                                                        </div>

                                                        <div class="col-md-12 mt-5">
                                                            <?= $this->lang->line('campaigns_v2_information_import_rules_product_import_file_info'); ?>
                                                        </div>

                                                    </div>

                                                </div>
                                                <div class="row">

                                                    <div class="col-md-4 pt-5">

                                                        <div class="col-md-12 px-4">
                                                            <hr>
                                                        </div>

                                                        <div class="col-md-12 pt-5 instructions-box-tip">

                                                            <a href="<?=base_url('assets/files/campaign_sample_stores.csv') ?>">
<!--                                                                        <i class="fa-solid fa-download"></i>-->
                                                                <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>
                                                                <?=lang('campaigns_v2_instructions_box_download_sample');?>
                                                            </a>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-4 pt-5">

                                                        <div class="col-md-12 px-4">
                                                            <hr>
                                                        </div>

                                                        <div class="col-md-12 pt-5 instructions-box-tip">
                                                            <a target="_blank" href="<?=base_url('/stores') ?>">
                                                                <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>
                                                                <?=lang('campaigns_v2_instructions_box_export_all_stores');?>
                                                            </a>
                                                        </div>

<!--                                                        <div class="form-group col-md-12 mt-5">-->
<!--                                                            <label for="comission_rule">Categoria</label>-->
<!--                                                            <br>-->
<!--                                                            <select id="store_filter_category" class="form-control">-->
<!--                                                                <option value="0">Categoria 1</option>-->
<!--                                                                <option value="0">Categoria 2</option>-->
<!--                                                                <option value="0">Categoria 3</option>-->
<!--                                                                <option value="0">Categoria 4</option>-->
<!--                                                            </select>-->
<!--                                                        </div>-->
<!---->
<!--                                                        <div class="col-md-12 mt-1 instructions-box-tip">-->
<!--                                                            <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>-->
<!--                                                            --><?php //=lang('campaigns_v2_information_import_rules_product_export_by_category_info');?>
<!--                                                        </div>-->


                                                    </div>

                                                    <div class="col-md-4 pt-5">

                                                        <div class="col-md-12 pt-0" style="margin-top: 20px;">
                                                            <div class="row">
                                                                <div class="box">

                                                                    <input type="file" name="fileStore" ref="filesStore" class="form-control mass-input-file" @change="cloneStoreFile"/>

                                                                    <div class="mass-input-file-clone px-4" id="store-clone" >{{storeMessage}}</div>

                                                                </div>
                                                                <button type="button" class="btn btn-primary mt-0"
                                                                        @click="sendCsvStore"
                                                                        :disabled="uploadingStores">
                                                                    <span class="glyphicon glyphicon-plus-sign mr-1" aria-hidden="true"></span>
                                                                    <?=lang('campaigns_v2_instructions_box_send_file');?>
                                                                    <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingStores"></i><span class="sr-only"><?=lang('application_conciliacao_txts_loading');?>...</span>
                                                                </button>
                                                            </div>
                                                        </div>

                                                    </div><!-- col md 4 -->

                                                </div>
                                            </div>





                                        </div>

                                    </div>
<!--                                            </div>-->
                                </div>

                            </div>


                            <!-- adesao por categoria -->
                            <div class="row" v-show="entry.segment == '<?php echo CampaignSegment::CATEGORY; ?>'" v-if="!allInputsDisabled">

                                <div class="tab-content campaign-tab-content p-5">

                                    <div class="tab-pane fade in active" id="category_individual">

                                        <div class="row">
                                            <div class="col-md-12">

                                                <label for="name"><?=$this->lang->line('application_promotion_category');?> *</label>

                                                <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="categories" multiple="multiple" v-model="entry.categories">
                                                    <option v-for="category in categories" :value="category.id">{{category.name}}</option>
                                                </select>

                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- adesao por produtos -->
                            <div class="row" v-show="entry.segment == '<?php echo CampaignSegment::PRODUCT; ?>'" v-if="!allInputsDisabled">

                                <ul class="nav nav-tabs mt-5">
                                    <li class="active"><a href="#product_individual" data-toggle="tab"><?=lang('campaigns_v2_nav_tab_single_include');?></a></li>
                                    <li><a href="#product_mass" data-toggle="tab" id="link-product-add-mass"><?=lang('campaigns_v2_nav_tab_mass_include');?></a></li>
                                </ul>

                                <div class="tab-content campaign-tab-content p-5">

                                    <div class="tab-pane fade in active" id="product_individual">

                                        <div class="row">

                                            <div class="col-md-12">

                                                <table class="mt-5" style="width: 100%">

                                                    <tr class="ml-5 my-5 py-3" >

                                                        <td style="width: 50%">

                                                            <h3 class="instructions-box-title m-0 mb-4"><?=lang('campaigns_v2_instructions_box_product_title_individual');?></h3>

                                                            <label for="search_product"><?=lang('application_product');?></label>
                                                            <div class="row pr-5 mr-2">
                                                                <div class="col-md-10">
                                                                    <input id="search_product" type="text" v-model.trim="productSearchQuery" placeholder="<?= $this->lang->line('application_inform_sku_name_description_product'); ?>" class="form-control" >
                                                                </div>

                                                                <div class="col-md-2 px-0">
                                                                    <button class="btn btn-primary form-control px-4" name="search-product-title" @click="submitSearchProduct">Buscar</button>
                                                                </div>
                                                            </div>



                                                            <div v-show="productsearchresult.length > 0">

                                                                <div class="box box-solid mt-5">
                                                                    <div class="box-header">
                                                                        <h3 class="box-title"><?= $this->lang->line('application_alloc_product'); ?></h3>
                                                                    </div>
                                                                    <div class="box-body">

                                                                        <data-table :productsearchresult="filteredProducts"></data-table>

                                                                    </div>

                                                                </div>

                                                            </div>

                                                        </td>

                                                        <td style="width: 1px; min-width: 1px; max-width: 1px;background-color: #007CFF; height: 100%; vertical-align: top;">
                                                            <div class="px-0" style="background-color: #fff; width: 1px; height: 50px;"></div>
                                                        </td>

                                                        <td style="width: 50%;" class="pl-5 pr-0" :style="[entry.products.length == 0 ? {'vertical-align': 'middle'} : {'vertical-align': 'top'}]">


                                                            <div v-if="entry.products.length > 0">

                                                                <h3 class="instructions-box-title m-0 mb-4">&nbsp;</h3>
                                                                <label for="search_product_filtered"><?=lang('application_product');?></label>
                                                                <div class="row  mr-2">
                                                                    <div class="col-md-10">
                                                                        <input id="search_product_filtered" type="text" placeholder="<?= $this->lang->line('application_product_name'); ?>" class="form-control" >
                                                                        <!--v-model.trim="productSearchQuery" placeholder="<?= $this->lang->line('application_product_name'); ?>" -->
                                                                    </div>

                                                                    <div class="col-md-2 px-0">
                                                                        <button class="btn btn-primary form-control px-4" name="search-product-title"><?=lang('application_search');?></button>
                                                                        <!-- @click="submitSearchProduct" -->
                                                                    </div>
                                                                </div>


                                                                <div class="box box-solid  mt-5">

                                                                    <div class="box-header ">
                                                                        <h3 class="box-title  "><?= $this->lang->line('application_products'); ?></h3>
                                                                    </div>
                                                                    <div class="box-body ">
<!--                                                                            style="border-collapse: separate|collapse|initial|inherit;"-->
                                                                        <table class="table table-striped table-hover responsive table-condensed" style="border-collapse: collapse;">
                                                                            <thead>
                                                                            <tr>
                                                                                <th>ID</th>
                                                                                <th><?= $this->lang->line('application_store'); ?></th>
                                                                                <th><?= $this->lang->line('application_sku'); ?></th>
                                                                                <th><?= $this->lang->line('application_name'); ?></th>
                                                                                <th><?= $this->lang->line('application_price'); ?></th>
                                                                                <th><?= $this->lang->line('application_qty'); ?></th>
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_maximum_selling_price_of_the_share'); ?><!--</th>-->
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?><!--', '--><?php //=CampaignTypeEnum::MARKETPLACE_TRADING;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_comission_rule'); ?><!--</th>-->
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?><!--', '--><?php //=CampaignTypeEnum::MARKETPLACE_TRADING;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_enter_value'); ?><!--</th>-->
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::SHARED_DISCOUNT;?><!--', '--><?php //=CampaignTypeEnum::MERCHANT_DISCOUNT;?><!--', '--><?php //=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_discount_type'); ?><!--</th>-->
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::SHARED_DISCOUNT;?><!--', '--><?php //=CampaignTypeEnum::MERCHANT_DISCOUNT;?><!--', '--><?php //=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_conciliacao_grids_title_productdiscountvalue'); ?><!--</th>-->
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::SHARED_DISCOUNT;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_seller_discount'); ?><!--</th>-->
<!--                                                                                    <th v-show="['--><?php //=CampaignTypeEnum::SHARED_DISCOUNT;?><!--'].includes(entry.campaign_type)">--><?php //= $this->lang->line('application_marketplace_discount'); ?><!--</th>-->
                                                                                <th class="px-0" colspan="2" style="text-align: center"><?= $this->lang->line('application_actions'); ?></th>
                                                                            </tr>
                                                                            </thead>
                                                                            <tbody>


                                                                            <template v-for="product,index in entry.products" class="product-fields">
                                                                                <tr :id="'product-data-' + product.id"  :class="show_product_fields == product.id ? 'show-product-outline' : ''" style="border-bottom: 0;">
                                                                                    <td>{{product.id}}</td>
                                                                                    <td>{{product.store}}</td>
                                                                                    <td>{{product.sku}}</td>
                                                                                    <td>{{product.name}}</td>
                                                                                    <td>{{product.price}}</td>
                                                                                    <td>{{product.qty}}</td>
                                                                                    <td  class="px-0" >
                                                                                        <a class="btn btn-lg btn-link" @click="deleteProduct(index)" title="<?php echo $this->lang->line('application_remove_campaign_product');?>">
                                                                                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                                                                        </a>
                                                                                    </td>
                                                                                    <td class="px-0" >
                                                                                        <button class="btn btn-lg btn-link" @click="toggleProductFields(product.id)">
                                                                                            <i   class="fa fa-arrow-circle-down btn-outline-success222 btn-slide-down" :class="show_product_fields == product.id ? 'btn-slide-down-flip' : ''" aria-hidden="true"></i></button>
                                                                                    </td>
                                                                                </tr>

                                                                                <tr class="toggle-visibility" :id="'product-field-' + product.id" v-if="show_product_fields == product.id"  :class="show_product_fields == product.id ? 'show-product-outline' : ''" style="border-top: 0;">
                                                                                    <td colspan="8"  style="width: 100%;">
                                                                                        <table>
                                                                                            <tr class="table-condensed " style="vertical-align: top;">
                                                                                                <td class="dynamic-form pr-2" v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)">
                                                                                                    <money v-model.trim="product.maximum_share_sale_price"
                                                                                                           v-bind="money"
                                                                                                           class="form-control form-control-condensed"
                                                                                                           placeholder="<?php echo $this->lang->line('application_maximum_selling_price_of_the_share');?>"></money>

                                                                                                </td>
                                                                                                <td class="dynamic-form  pr-2" v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">
                                                                                                    <select v-model.trim="product.comission_rule"
                                                                                                            class="form-control   form-control-condensed"
                                                                                                            v-on:change="changeProductComissionRule(product,index)">
                                                                                                        <option v-for="(name, value) in comissionRules" :value="value">{{name}}</option>
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td class="dynamic-form pr-2" v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">
                                                                                                    <div class="input-group"
                                                                                                         v-show="product.comission_rule == 'new_comission'">
                                                                                                        <money maxlength="6"
                                                                                                               v-model.trim="product.new_comission"
                                                                                                               v-bind="percentage"
                                                                                                               class="form-control form-control-condensed"
                                                                                                               placeholder="<?php echo $this->lang->line('application_new_comission');?>"></money>
                                                                                                        <div class="input-group-addon form-control-condensed form-control-condensed-support">
                                                                                                            %
                                                                                                        </div>
<!--                                                                                                            @change="e => e.target.classList.toggle('show-product-outline-changed')"-->
                                                                                                    </div>

                                                                                                    <money v-show="product.comission_rule == 'comission_rebate'"
                                                                                                           v-model.trim="product.rebate_value"
                                                                                                           v-bind="money"
                                                                                                           class="form-control form-control-condensed"
                                                                                                           placeholder="<?php echo $this->lang->line('application_comission_rebate');?>"></money>
                                                                                                </td>
                                                                                                <td class="dynamic-form pr-2" v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)">
                                                                                                    <label for="product_discount_type" class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_type_title')?></label><br/>
                                                                                                    <select v-model.trim="product.discount_type" class="form-control form-control-condensed" id="product_discount_type" @change="markAsEdited(product.id)" :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                                                                                        <?php foreach ($discount_types as $key => $name): ?>
                                                                                                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                                                                                        <?php endforeach ?>
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td class="dynamic-form pr-2" v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)">

                                                                                                    <!-- % desconto total -->
                                                                                                    <div class="col-md-2 col-xs-2" v-if="product.discount_type == '<?=DiscountTypeEnum::PERCENTUAL;?>'">
                                                                                                        <div><label for="product_discount_percentage" class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_total_title')?></label></div>

                                                                                                        <div class="input-group">

                                                                                                            <input v-model.trim="product.discount_percentage"
                                                                                                                   id="product_discount_percentage"
                                                                                                                   type="number"
                                                                                                                   step="1" min="1" max="100"
                                                                                                                   oninput="this.value=(parseInt(this.value)||0)"
                                                                                                                   onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                                                                                   class="form-control form-control-condensed" />
                                                                                                            <div class="input-group-addon form-control-condensed form-control-condensed-support">
                                                                                                                %
                                                                                                            </div>
                                                                                                        </div>

                                                                                                    </div>

                                                                                                    <!-- R$ desconto total -->
                                                                                                    <div class="col-md-2" v-if="product.discount_type == 'fixed_discount'">
                                                                                                        <label for="product_fixed_discount" class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_total_title')?></label><br/>
                                                                                                        <money v-model.trim="product.fixed_discount" v-bind="money" id="product_fixed_discount" class="form-control form-control-condensed" style="width: 100px;"></money>
                                                                                                    </div>

                                                                                                </td>
                                                                                                <td class="dynamic-form pr-2" v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)">

                                                                                                    <!-- % desconto seller -->
                                                                                                    <div class="col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">
                                                                                                        <div><label class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_seller_title')?></label></div>
                                                                                                        <div class="input-group">
                                                                                                            <input v-model.trim="product.seller_discount_percentual"
                                                                                                                   id="product_seller_discount_percentual"
                                                                                                                   type="number" step="1" min="1" max="100"
                                                                                                                   oninput="this.value=(parseInt(this.value)||0)"

                                                                                                                   onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                                                                                   class="form-control form-control-condensed" style="width: 65px;" />
                                                                                                            <div class="input-group-addon form-control-condensed form-control-condensed-support">
                                                                                                                %
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>

                                                                                                    <!-- R$ desconto seller -->
                                                                                                    <div class="col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                                                                        <div><label for="product_seller_discount_fixed" class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_seller_title')?></label></div>
                                                                                                        <money v-model.trim="product.seller_discount_fixed" v-bind="money" id="product_seller_discount_fixed" class="form-control form-control-condensed" style="width: 100px;"></money>
                                                                                                    </div>

                                                                                                </td>
                                                                                                <td class="dynamic-form pr-2" v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)">

                                                                                                    <!-- % desconto marketplace -->
                                                                                                    <div class="col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">
                                                                                                        <div><label for="product_marketplace_discount_percentual" class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_mktplace_title')?></label></div>
                                                                                                        <div class="input-group">

                                                                                                            <input v-model.trim="product.marketplace_discount_percentual"
                                                                                                                   id="product_marketplace_discount_percentual"
                                                                                                                   type="number" step="1" min="1" max="100"
                                                                                                                   oninput="this.value=(parseInt(this.value)||0)"
                                                                                                                   onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                                                                                   class="form-control form-control-condensed" :disabled="allInputsDisabled" :readonly="allInputsDisabled" style="width: 65px;" />
                                                                                                            <div class="input-group-addon form-control-condensed form-control-condensed-support">
                                                                                                                %
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>

                                                                                                    <!-- R$ desconto marketplace -->
                                                                                                    <div class="col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                                                                        <label for="product_marketplace_discount_fixed" class="no-wrap"><?=$this->lang->line('campaigns_v2_information_products_form_discount_mktplace_title')?></label><br/>
                                                                                                        <money v-model.trim="product.marketplace_discount_fixed" v-bind="money" id="product_marketplace_discount_fixed" class="form-control form-control-condensed" style="width: 100px;"></money>
                                                                                                    </div>

                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            </template>







                                                                            </tbody>
                                                                        </table>

                                                                    </div>

                                                                </div>

                                                            </div>





                                                            <table class="mt-5" style="height: 100%; width: 100%;" v-show="entry.products.length == 0">
                                                                <tr>
                                                                    <td class="px-5" style="width: 10%; vertical-align: middle;">
                                                                        <h1><span class="glyphicon glyphicon-menu-left" aria-hidden="true"></span></h1>
                                                                    </td>

                                                                    <td style="width: 90%; vertical-align: middle;">
                                                                        <h3><?=lang('campaigns_v2_information_products_filter_info');?></h3>
                                                                    </td>
                                                                </tr>
                                                            </table>

                                                        </td>

                                                    </tr>

                                                </table>

                                            </div>

                                        </div>

                                    </div>



                                    <div class="tab-pane fade" id="product_mass">



                                        <div class="row" id="product-instructions-row">

                                            <div class="col-md-3" >

                                                <div class="box instructions-box">

                                                    <div class="box-header"  id="product-instructions-box">
                                                        <div class="row px-5 pt-3">
                                                            <div class="col-md-2 icon">
                                                                !
                                                            </div>
                                                            <div class="col-md-10 p-0 pl-4 mt-0 ">
                                                                <h3 class="instructions-box-title m-0"><?= $this->lang->line('campaigns_v2_instructions_box_store_mass'); ?></h3>
                                                            </div>
                                                            <div class="col-md-12 mt-5">
                                                                <?=lang('campaigns_v2_information_import_rules_product_1');?>
                                                            </div>
                                                            <div class="col-md-12 my-5">
                                                                <?=lang('campaigns_v2_information_import_rules_product_2');?>
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="col-md-9">
                                                <div class="row">

                                                    <div class="col-md-4 py-5">

                                                        <div class="col-md-12">

                                                            <div class="num-list-before ">
                                                                <h3 class="box-title num-list-title my-0"><?= $this->lang->line('application_step'); ?></h3>
                                                            </div>
                                                            <div class="num-list num-list-after ml-3">
                                                                1
                                                            </div>

                                                        </div>

                                                        <div class="col-md-12 mt-1">
                                                            <h3 class="instructions-box-title m-0"><?=lang('download_example_xls');?></h3>
                                                        </div>

                                                        <div class="col-md-12 mt-5">
                                                            <?=lang('campaigns_v2_information_import_rules_product_model_rules');?>
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4 py-5">

                                                        <div class="col-md-12">

                                                            <div class="num-list-before ">
                                                                <h3 class="box-title num-list-title my-0"><?= $this->lang->line('application_step'); ?></h3>
                                                            </div>
                                                            <div class="num-list num-list-after ml-3">
                                                                2
                                                            </div>

                                                        </div>

                                                        <div class="col-md-12 mt-1">
                                                            <h3 class="instructions-box-title m-0"><?=lang('campaigns_v2_information_import_rules_product_download_registers');?></h3>
                                                        </div>

                                                        <div class="col-md-12 mt-5">
                                                            <?=lang('campaigns_v2_information_import_rules_product_download_registers_info');?>
                                                        </div>


                                                    </div>


                                                    <div class="col-md-4 py-5">

                                                        <div class="col-md-12">

                                                            <div class="num-list-before ">
                                                                <h3 class="box-title num-list-title my-0"><?= $this->lang->line('application_step'); ?></h3>
                                                            </div>
                                                            <div class="num-list num-list-after ml-3">
                                                                3
                                                            </div>
                                                        </div>

                                                        <div class="col-md-12 mt-1">
                                                            <h3 class="instructions-box-title m-0"><?= $this->lang->line('application_load_file'); ?></h3>
                                                        </div>

                                                        <div class="col-md-12 mt-5">
                                                            <?= $this->lang->line('campaigns_v2_information_import_rules_product_import_file_info'); ?>
                                                        </div>

                                                    </div>

                                                </div>
                                                <div class="row">

                                                    <div class="col-md-4 pt-5">

                                                        <div class="col-md-12 px-4">
                                                            <hr>
                                                        </div>

                                                        <div class="col-md-12 pt-5 instructions-box-tip">

                                                            <a v-if="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)" href="<?=base_url('assets/files/campaign_sample_products_comission_reduction_rebate.csv') ?>"><span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>&nbsp;<?php echo lang('application_download_sample');?></a>
                                                            <a v-if="['<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)" href="<?=base_url('assets/files/campaign_sample_products_marketplace_trading.csv') ?>"><span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>&nbsp;<?php echo lang('application_download_sample');?></a>
                                                            <a v-if="!['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)" href="<?=base_url('assets/files/campaign_sample_products.csv') ?>"><span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>&nbsp;<?php echo lang('application_download_sample');?></a>
                                                            <!--                                                                        <i class="fa-solid fa-download"></i>-->
                                                            <!--                                                                                <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>-->
                                                            <!--                                                                                --><?php //=lang('campaigns_v2_instructions_box_download_sample');?>
                                                            </a>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-4 pt-5">

                                                        <div class="col-md-12 px-4">
                                                            <hr>
                                                        </div>

<!--                                                        <div class="col-md-12 pt-5 instructions-box-tip">-->
<!--                                                            <a href="--><?php //=base_url('assets/files/campaign_sample_stores.csv') ?><!--">-->
<!--                                                                <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>&nbsp;-->
<!--                                                                --><?php //=lang('campaigns_v2_instructions_box_export_all_products');?>
<!--                                                            </a>-->
<!--                                                        </div>-->

                                                        <div class="col-md-12 pt-5 instructions-box-tip">
                                                            <a target="_blank" href="<?=base_url('/products') ?>">
                                                                <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>&nbsp;
																<?=lang('campaigns_v2_instructions_box_export_all_products');?>
                                                            </a>
                                                        </div>

<!--                                                        <div class="col-md-12 pt-5 instructions-box-tip">-->
<!--                                                            <a target="_blank" href="--><?php //=base_url('/products') ?><!--">-->
<!--                                                                <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>-->
<!--																--><?php //=lang('campaigns_v2_instructions_box_export_all_products');?>
<!--                                                            </a>-->
<!--                                                        </div>-->
<!---->
<!--                                                        <div class="form-group col-md-12 mt-5">-->
<!--                                                            <label for="comission_rule">--><?php //= $this->lang->line('application_category'); ?><!--</label>-->
<!--                                                            <br>-->
<!---->
<!--                                                            -->
<!--                                                            <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="categories_product_mass" multiple="multiple" v-model="entry.categories">-->
<!--                                                                <option v-for="category in categories" :value="category.id">{{category.name}}</option>-->
<!--                                                            </select>-->
<!---->
<!--                                                        </div>-->
<!---->
<!--                                                        <div class="col-md-12 mt-1 instructions-box-tip">-->
<!--                                                            <span class="glyphicon glyphicon-cloud-download" aria-hidden="true"></span>-->
<!--                                                            --><?php //= $this->lang->line('campaigns_v2_information_import_rules_product_export_by_category_info'); ?>
<!--                                                        </div>-->


                                                    </div>

                                                    <div class="col-md-4 pt-5">

                                                        <div class="col-md-12 pt-0" style="margin-top: 20px;">
                                                            <div class="row">
                                                                <div class="box">

                                                                    <input type="file" name="fileProduct" ref="fileProduct" class="form-control mass-input-file" @change="cloneProductFile"/>

                                                                    <div class="mass-input-file-clone px-4" id="product-clone" >{{productMessage}}</div>

                                                                </div>
                                                                <button type="button" class="btn btn-primary mt-0"
                                                                        @click="sendCsvProduct"
                                                                        :disabled="uploadingProducts">
                                                                    <span class="glyphicon glyphicon-plus-sign mr-1" aria-hidden="true"></span>
                                                                    <?=lang('campaigns_v2_instructions_box_send_file');?>
                                                                    <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingStores"></i><span class="sr-only"><?= $this->lang->line('application_conciliacao_txts_loading'); ?>...</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div><!-- col md 4 -->
                                                </div>
                                            </div>
                                        </div>
<!--                                        </div>-->



                                    </div>

                            </div>


                        </div><!-- box do item 3 -->


                    </div>
                    <!-- fim do item 3 -->



                </form>

                <?php
                if ($page == 'products'){

                    if ($allow_insert_products){
                    ?>
                        <form role="form" id="formCadastroProdutos" method="post" v-on:submit.prevent="onSubmit">

                            <div class="box box-success">

                                <div class="box-header with-border">
                                    <h3 class="box-title"><?= $this->lang->line('application_add_products_in_campaign'); ?></h3>
                                </div>

                                <div class="box-body">

                                    <div class="row">
                                        <div class="form-group form-group col-md-5">
                                            <label for="search_product"><?=lang('application_search_for_products');?></label>
                                            <input id="search_product" type="text" v-model.trim="productSearchQuery" placeholder="<?= $this->lang->line('application_inform_sku_name_description_product'); ?>" class="form-control" @keyup="submitSearchProductElegible" @change="submitSearchProductElegible">
                                        </div>
                                        <div class="form-group form-group col-md-4" v-show="categories.length > 0">
                                            <label for="categories"><?=lang('application_search_for_products_in_selected_categories');?></label>
                                            <select class="form-control selectpicker show-tick" id="categories" multiple="multiple" data-live-search="true" data-actions-box="true" v-model="entry.categories" @change="submitSearchProductElegible">
                                                <option v-for="category in categories" :value="category">{{category.name}}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="box">
                                        <div class="box-header">
                                            <h3 class="box-title"><?= $this->lang->line('application_alloc_product'); ?></h3>
                                            <button class="btn btn-sm btn-success pull-right mt-5" v-if="productsearchresult.length > 0" v-on:click="addAllProductsFromSearchResult()">
                                                <i class="fa fa-plus"></i>
                                                <?=lang('application_add_all');?>
                                            </button>
                                            <div class="form-group col-md-3 pull-right">
                                                <label><?=$this->lang->line('application_upload_products_csv_to_massive_import');?></label>
                                                <a href="<?=base_url('assets/files/campaign_sample_products_seller.csv') ?>"><?=lang('application_download_sample');?></a>
                                                <div class="input-group">
                                                    <input type="file" id="fileMassImportSeller" name="fileMassImportSeller" ref="filesMassImportSeller" class="form-control" />
                                                    <div class="input-group-addon">
                                                        <button type="button" class="btn btn-outline-secondary" style="line-height: 0.4; padding: 0;"
                                                                @click="sendImportProductsSeller"
                                                                :disabled="uploadingImportProductsSeller">
                                                            <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingImportProductsSeller"></i><span class="sr-only"><?= $this->lang->line('application_conciliacao_txts_loading'); ?>...</span>
                                                            <?=lang('application_send');?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="box-body">

                                            <data-table :productsearchresult="filteredProducts"></data-table>

                                        </div>

                                    </div>

                                    <!-- Produtos Novos para Adicionar na Campanha - Grid do lojista adicionando os produtos -->
                                    <h5 class="box-title" v-show="entry.products.length > 0">
                                        <?= $this->lang->line('application_products_new_to_add_in_campaign'); ?>
                                    </h5>

                                    <table class="table table-striped table-hover responsive table-condensed" v-show="entry.products.length > 0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th><?= $this->lang->line('application_store'); ?></th>
                                                <th><?= $this->lang->line('application_sku'); ?></th>
                                                <th><?= $this->lang->line('application_name'); ?></th>
                                                <th><?= $this->lang->line('application_price'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_maximum_selling_price_of_the_share'); ?></th>
                                                <th><?= $this->lang->line('application_qty'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_comission_rule'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_enter_value'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_discount_type'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_conciliacao_grids_title_productdiscountvalue'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_seller_discount'); ?></th>
                                                <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_marketplace_discount'); ?></th>
                                                <th><?= $this->lang->line('application_action'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="product,index in entry.products">
                                            <td>{{product.id}}</td>
                                            <td>{{product.store}}</td>
                                            <td>{{product.sku}}</td>
                                            <td>{{product.name}}</td>
                                            <td>{{product.price}}</td>
                                            <td v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)">
                                                <money v-model.trim="product.maximum_share_sale_price"
                                                       v-bind="money"
                                                       class="form-control"
                                                       placeholder="<?php echo $this->lang->line('application_maximum_selling_price_of_the_share');?>"></money>

                                            </td>
                                            <td>{{product.qty}}</td>
                                            <td v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">
                                                <select v-model.trim="product.comission_rule"
                                                        class="form-control"
                                                        v-on:change="changeProductComissionRule(product,index)">
                                                    <option v-for="(name, value) in comissionRules" :value="value">{{name}}</option>
                                                </select>
                                            </td>
                                            <td v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">

                                                <div class="input-group"
                                                     v-show="product.comission_rule == 'new_comission'">
                                                    <money maxlength="6"
                                                           v-model.trim="product.new_comission"
                                                           v-bind="percentage"
                                                           class="form-control"
                                                           placeholder="<?php echo $this->lang->line('application_new_comission');?>"></money>
                                                    <div class="input-group-addon">
                                                        %
                                                    </div>
                                                </div>

                                                <money v-show="product.comission_rule == 'comission_rebate'"
                                                       v-model.trim="product.rebate_value"
                                                       v-bind="money"
                                                       class="form-control"
                                                       placeholder="<?php echo $this->lang->line('application_comission_rebate');?>"></money>
                                            </td>
                                            <td v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)">
                                                <select v-model.trim="product.discount_type" class="form-control" id="product_discount_type" :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                                    <?php foreach ($discount_types as $key => $name): ?>
                                                        <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                                    <?php endforeach ?>
                                                </select>
                                            </td>
                                            <td v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)">

                                                <!-- % desconto total -->
                                                <div class="form-group col-md-2 col-xs-2" v-if="product.discount_type == '<?=DiscountTypeEnum::PERCENTUAL;?>'">
                                                    <div class="input-group">
                                                        <input v-model.trim="product.discount_percentage" id="product_discount_percentage"
                                                               type="number" step="1" min="1" max="100"
                                                               oninput="this.value=(parseInt(this.value)||0)"
                                                               onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                               class="form-control" style="width: 65px;" :disabled="allInputsDisabled" :readonly="allInputsDisabled" />
                                                        <div class="input-group-addon">
                                                            %
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- R$ desconto total -->
                                                <div class="form-group col-md-2" v-if="product.discount_type == 'fixed_discount'">
                                                    <money v-model.trim="product.fixed_discount" v-bind="money" id="product_fixed_discount" class="form-control" style="width: 100px;"  :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                                </div>

                                            </td>
                                            <td v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)">

                                                <!-- % desconto seller -->
                                                <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">
                                                    <div class="input-group">
                                                        <input v-model.trim="product.seller_discount_percentual"
                                                               id="product_seller_discount_percentual"
                                                               type="number" step="1" min="1" max="100"
                                                               oninput="this.value=(parseInt(this.value)||0)"
                                                               onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                               class="form-control" style="width: 65px;"  :disabled="allInputsDisabled" :readonly="allInputsDisabled" />
                                                        <div class="input-group-addon">
                                                            %
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- R$ desconto seller -->
                                                <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                    <money v-model.trim="product.seller_discount_fixed" v-bind="money" id="product_seller_discount_fixed" class="form-control" style="width: 100px;"  :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                                </div>

                                            </td>
                                            <td v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)">
                                                <!-- % desconto marketplace -->
                                                <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">
                                                    <div class="input-group">
                                                        <input v-model.trim="product.marketplace_discount_percentual"
                                                               id="product_marketplace_discount_percentual"
                                                               type="number" step="1" min="1" max="100"
                                                               oninput="this.value=(parseInt(this.value)||0)"
                                                               onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                               class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled" style="width: 65px;" />
                                                        <div class="input-group-addon">
                                                            %
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- R$ desconto marketplace -->
                                                <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                    <money v-model.trim="product.marketplace_discount_fixed" v-bind="money" id="product_marketplace_discount_fixed" class="form-control" style="width: 100px;"  :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                                </div>

                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-danger" @click="deleteProduct(index)" title="<?php echo $this->lang->line('application_remove_campaign_product');?>"><i class="fa fa-minus"></i></a>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>

                                </div>

                            </div>



                        </form>

                        <button type="button" id="btnSave" class="btn btn-warning pull-right mb-5" @click="submitProducts()" :disabled="saving" v-show="entry.products.length > 0">
                            <i class="fa fa-plus" v-show="!saving"></i>
                            <i class="fa fa-spinner fa-spin fa-fw" v-show="saving"></i><span class="sr-only">Loading...</span>
                            <?= $this->lang->line('application_add_products_in_campaign'); ?>
                        </button>

                    <?php
                    }
                    ?>

                    <div class="row mt-5">
                        <div class="col-md-12 col-xs-12">
                            <div class="box box-success">

                                <div class="box-header with-border" v-show="!['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">
                                    <h3 class="box-title"><?= lang('application_products_in_campaign'); ?></h3>
                                    <?php
                                    if ($only_admin && $usercomp == 1 && !$userstore){
                                        ?>
                                        <br />
                                        <a onclick="return downloadFile(this, '<?php echo $campaign['id'];?>')" class="btn btn-sm btn-default mt-4">
                                            <i class="fa fa-arrow-alt-circle-down"></i> <?=lang('application_export_file_with_all_campaign_itens');?>
                                        </a>

                                        <a onclick="return confirm('<?php echo lang('application_are_you_sure_approve_all_products_this_campaign') ; ?>')"
                                           href="<?php echo base_url("campaigns_v2/approve_all_products/{$campaign['id']}"); ?>"
                                           class="btn btn-sm btn-success pull-right mt-4">
                                            <i class="fa fa-check"></i> <?=lang('application_approve_all');?>
                                        </a>

                                        <div class="form-group col-md-3 pull-right">
                                            <label for="stores"><?=$this->lang->line('application_import_csv_to_massive_approvement');?></label>
                                            <a href="<?=base_url('assets/files/campaign_sample_products.csv') ?>"><?=lang('application_download_sample');?></a>
                                            <div class="input-group">
                                                <input type="file" name="fileMassApprovement" ref="filesMassApprovement" class="form-control" />
                                                <div class="input-group-addon">
                                                    <button type="button" class="btn btn-outline-secondary" style="line-height: 0.4; padding: 0;"
                                                            @click="sendApprovementProducts"
                                                            :disabled="uploadingApprovementProducts">
                                                        <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingApprovementProducts"></i><span class="sr-only"><?= $this->lang->line('application_conciliacao_txts_loading'); ?>...</span>
                                                        <?=lang('application_send');?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <?php
                                    }
                                    ?>
                                </div>

                                <div class="box-body">

                                    <?php
                                    if ($only_admin && $usercomp == 1 && !$userstore){
                                        ?>

                                        <nav class="navbar mb-0">

                                            <div class="row mt-12">
                                                <div class="col-md-1" style="width: 35px;">
                                                    <a class="nav-item"><i class="fa fa-filter fa-2x"></i></a>
                                                </div>
                                                <div class="col-md-3">
                                                    <select id='searchByStore' class="nav-item form-control">
                                                        <option selected="selected" disabled="disabled"><?php echo lang('application_filter_by_store'); ?></option>
                                                        <?php
                                                        foreach ($array_stores as $store){
                                                            ?>
                                                            <option value='<?php echo $store['store_id']; ?>'><?php echo $store['name']; ?></option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                        </nav>

                                        <?php
                                    }
                                    ?>

                                    <!-- Grid de Produtos na Campanha (Visão de detalhes da campanha) -->
                                    <table id="productsInCampaign" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><?=lang('application_id'); ?></th>
                                                <th><?=lang('application_name'); ?></th>
                                                <?php if ($only_admin && $usercomp == 1 && !$userstore || $usercomp != 1){?><th><?=lang('application_store'); ?></th><?php } ?>
                                                <th><?=lang('application_marketplace'); ?></th>
                                                <th><?=lang('application_product_price_before_campaign'); ?></th>
                                                <th><?=lang('application_product_price_in_campaign'); ?></th>
                                                <?php
                                                if(!in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                                                ?>
                                                    <th><?=lang('application_promotion_desc_type'); ?></th>
                                                    <th><?=lang('application_discount'); ?></th>
                                                    <?php
                                                    if(in_array($campaign['campaign_type'], [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT])) {
                                                    ?>
                                                        <th><?=lang('application_seller_discount'); ?></th>
                                                    <?php
                                                    }
                                                    if(in_array($campaign['campaign_type'], [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT])) {
                                                    ?>
                                                        <th><?=lang('application_marketplace_discount'); ?></th>
                                                    <?php
                                                    }
                                                    ?>
                                                <?php
                                                }
                                                ?>
                                                <th><?=lang('application_qty'); ?></th>
                                                <th><?=lang('application_item_sold_last_30_days'); ?></th>
                                                <?php
                                                if(in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                                                    if($campaign['campaign_type'] == CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE) {
                                                    ?>
                                                        <th data-orderable="false"><?=lang('application_maximum_selling_price_of_the_share'); ?></th>
                                                    <?php
                                                    }
                                                    ?>
                                                    <th data-orderable="false"><?=lang('application_participating_in_campaign'); ?></th>
                                                    <th data-orderable="false"><?=lang('application_new_comission'); ?></th>
                                                    <th data-orderable="false"><?=lang('application_comission_rebate'); ?></th>
                                                <?php
                                                }else{
                                                    ?>
                                                    <th><?=lang('application_active'); ?></th>
                                                    <th><?=lang('application_approved'); ?></th>
                                                    <?php
                                                }
                                                ?>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php
                }
                ?>






            </div>


            <table style="display:none;">
                <tr>
                    <td class="pl-2 pt-3">
                        <div class="form-group" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type)">

                            <input type="checkbox"
                                   id="products_auto_approval"
                                   v-model="entry.products_auto_approval"
                                   true-value="1"
                                   false-value="0"
                                   class="form-check-input"
                                   style="width: 23px; height: 23px; border-radius: 5px;"
                                   :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                            >
                        </div>
                    </td>
                    <td class="p-4 pt-1" style="white-space: nowrap;">
                        <label for="products_auto_approval" style="width: 100%; font-size: 2.3vh;"><?= $this->lang->line('campaign_auto_approval_products'); ?></label><br/>
                    </td>
                    <td class="px-4 small pt-2" style="color: #666;">
                        <table>
                            <tr>
                                <td>
                                    <strong><?= $this->lang->line('campaigns_v2_information_products_check_info1_title'); ?>:</strong> <?= $this->lang->line('campaigns_v2_information_products_check_info1_text'); ?>.
                                </td>
                            </tr>
                            <tr>
                                <td class="pt-1">
                                    <strong><?= $this->lang->line('campaigns_v2_information_products_check_info2_title'); ?>:</strong> <?= $this->lang->line('campaigns_v2_information_products_check_info2_text'); ?>.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>



            <div class="row">
                <div class="col-md-12 my-4">

                    <a href="<?php echo base_url('campaigns_v2') ?>" class="btn btn-default col-md-1" style="background-color: #fff;"><?= $this->lang->line('application_cancel'); ?></a>

                    <button type="button" id="btnSave" class="btn btn-primary col-md-2 ml-4" @click="submit()" :disabled="saving" v-if="!allInputsDisabled">
                        <i class="fa fa-check fa-fw" aria-hidden="true"></i>&nbsp;
                        <?= $this->lang->line('application_save'); ?>
                        <i class="fa fa-spinner fa-spin fa-fw" v-show="saving"></i><span class="sr-only"><?= $this->lang->line('application_conciliacao_txts_loading'); ?>...</span>
                    </button>

                </div>
            </div>




        </div>

    </section>

</div>

<script type="text/javascript">

    $(document).ready(function ()
    {
        $("#mainCampaignsNav").addClass('active');
        $("#addCampaignsNav").addClass('active');

    });

    var base_url = "<?php echo base_url(); ?>";

    <?php
        if ($page == 'products'){
    ?>
        $(document).ready(function () {
            productsInCampaign = $('#productsInCampaign').DataTable({
                "language": {
                    "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                    "searchPlaceholder": "<?php echo lang('application_campaign_search_placeholder'); ?>"
                },
                "processing": true,
                "serverSide": true,
                "serverMethod": "post",
                'ajax': {
                    url: base_url + 'campaigns_v2/products_in_campaign/<?php echo $campaign['id'];?>',
                    'data': function(data){
                        // Read values
                        var store = $('#searchByStore').val();

                        // Append to data
                        data.searchByStore = store;
                    }
                },
                "order": [[ 0, "desc" ]],
                "columns": [
                    {"data": "id"},
                    {"data": "name"},
                    <?php if ($only_admin && $usercomp == 1 && !$userstore || $usercomp != 1){?>{"data": "store"},<?php } ?>
                    {"data": "int_to"},
                    {"data": "product_price"},
                    {"data": "action_value"},
                    <?php
                    if(!in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                    ?>
                        {"data": "discount_type_name"},
                        {"data": "discount"},
                        <?php
                        if(in_array($campaign['campaign_type'], [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT])) {
                        ?>
                            {"data": "seller_discount"},
                        <?php
                        }
                        if(in_array($campaign['campaign_type'], [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT])) {
                        ?>
                            {"data": "marketplace_discount"},
                        <?php
                        }
                    }
                    ?>
                    {"data": "qty"},
                    {"data": "gmv_last_30_days"},
                    <?php
                    if(in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                        if(in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE])) {
                        ?>
                            {"data": "maximum_share_sale_price"},
                        <?php
                        }
                        ?>
                        {"data": "participating"},
                        {"data": "new_comission"},
                        {"data": "rebate_value"},
                    <?php
                    }else{
                    ?>
                        {"data": "active","class" : "text-center"},
                        {"data": "approved","class" : "text-center"},
                    <?php
                    }
                    ?>
                ],
                fnServerParams: function(data) {
                    data['order'].forEach(function(items, index) {
                        data['order'][index]['column'] = data['columns'][items.column]['data'];
                    });
                },
            });

            $('#searchByStore').change(function(){
                productsInCampaign.draw();
            });

        });
    <?php
    }
    ?>

    //https://codepen.io/stwilson/pen/oBRePd
    Vue.component('data-table', {
        template: '<table class="table table-striped table-hover responsive table-condensed" style="width: 100%;"></table>',
        props: ['productsearchresult'],
        data() {
            return {
                headers: [
                    { title: 'ID', 'class': 'col-md-1' },
                    <?php if ($only_admin && $usercomp == 1 && !$userstore){ ?>
                    { title: '<?= $this->lang->line('application_store'); ?>', 'class': 'col-md-2' },
                    <?php } ?>
                    { title: '<?= $this->lang->line('application_sku'); ?>','class': 'col-md-2' },
                    { title: '<?= $this->lang->line('application_name'); ?>', 'class': 'col-md-4' },
                    { title: '<?= $this->lang->line('application_price'); ?>', 'class': 'col-md-1' },
                    { title: '<?= $this->lang->line('application_qty'); ?>', 'class': 'col-md-1' },
                    { title: '<?= $this->lang->line('application_action'); ?>', 'class': 'col-md-1', 'sortable': false },
                ],
                rows: [] ,
                dtHandle: null
            }
        },
        watch: {
            productsearchresult(val, oldVal) {
                this.showRows(val, oldVal);
            }
        },
        mounted() {
            let vm = this;
            // Instantiate the datatable and store the reference to the instance in our dtHandle element.
            vm.dtHandle = $(this.$el).DataTable({
                // Specify whatever options you want, at a minimum these:
                columns: vm.headers,
                data: vm.rows,
                searching: true,
                paging: true,
                info: true
            });
        },
        methods: {
            showRows(val, oldVal){
                let vm = this;
                vm.rows = [];
                // You should _probably_ check that this is changed data... but we'll skip that for this example.
                val.forEach(function (item) {

                    canInsert = true;
                    app.entry.products.forEach(function (productSelected){
                        if (productSelected.id == item.id){
                            canInsert = false;
                        }
                    });

                    if (canInsert){

                        // Fish out the specific column data for each item in your data set and push it to the appropriate place.
                        // Basically we're just building a multi-dimensional array here. If the data is _already_ in the right format you could
                        // skip this loop...
                        let row = [];

                        row.push(item.id);
                        <?php if ($only_admin && $usercomp == 1 && !$userstore){ ?>
                        row.push(item.store);
                        <?php } ?>
                        row.push(item.sku);
                        row.push(item.name);
                        row.push(item.price);
                        row.push(item.qty);
                        row.push('<a class="btn btn-lg btn-link" onclick="addProduct(this, '+item.id+', '+item.maximum_share_sale_price+', '+item.another_discount_campaign+', '+item.another_comission_rebate_campaign+', '+item.another_marketplace_trading_campaign+')"><!--<i class="fa fa-plus"></i>--><i class="fa fa-arrow-right" aria-hidden="true"></i></a>');

                        vm.rows.push(row);

                    }

                });
                //@todo paginação, precisa analisar como fazer, mas é possível:
                //@todo https://willvincent.com/2016/04/08/making-vuejs-and-datatables-play-nice/
                // Here's the magic to keeping the DataTable in sync.
                // It must be cleared, new rows added, then redrawn!
                vm.dtHandle.clear();
                vm.dtHandle.rows.add(vm.rows);
                vm.dtHandle.draw();
            }
        }
    });

    $.extend($.fn.dataTable.defaults, {
        language: {
            url: base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'
        }
    });

    var app = new Vue({
        el: '#app',
        data: {
            storeMessage: "<?=lang('drag_drop_file')?>",
            productMessage: "<?=lang('drag_drop_file')?>",
            show_product_fields: 0,
            page : '<?php echo $page; ?>',
            allInputsDisabled : <?php echo $page != '' ? 'true' : 'false' ?>,
            entry : <?php echo $entry; ?>,
            categories: <?php echo $categories; ?>,
            stores: <?php echo $stores; ?>,
            filteredStores: <?php echo $stores; ?>,
            comissionRules: <?php echo $comission_rules; ?>,
            paymentMethodsOptions: <?php echo $payment_methods_options; ?>,
            paymentMethodsOptionsAvailable: [],
            tradePoliciesOptions: <?php echo $trade_policies_options; ?>,
            tradePoliciesOptionsAvailable: [],
            segments: <?php echo $segments; ?>,
            segmentsOri: <?php echo $segments; ?>,
            money: { //https://github.com/vuejs-tips/v-money
                decimal: ',',
                thousands: '.',
                prefix: 'R$ ',
                suffix: '',
                precision: 2,
                masked: false
            },
            percentage: { //https://github.com/vuejs-tips/v-money
                decimal: '.',
                thousands: '',
                prefix: '',
                suffix: '',
                precision: 2,
                masked: false
            },
            productsearchresult: [],
            productSearchQuery: '',
            submitResponse: '',
            saving: false,
            uploadingStores: false,
            uploadingProducts: false,
            uploadingApprovementProducts: false,
            uploadingImportProductsSeller: false,
        },
        computed: {
            filteredProducts: function () {
                let self = this
                let productSearchQuery = self.productSearchQuery.toLowerCase()
                return self.productsearchresult.filter(function (product) {
                    return 	true
                })
            },
        },
        mounted() {
            <?php
            if ($allow_insert_products){
            ?>
            this.submitSearchProductElegible();
            <?php
            }
            ?>
            this.changeMarketplace(false);
        },
        ready: function() {
        },
        methods: {
            async submit() {

                this.saving = true;

                let reqURL = base_url + 'campaigns_v2/save_insert_edit';

                var canContinue = true;

                //Se o tipo de campanha for negociação marketplace e segmento loja, vamos verificar se alguma das lojas já tem produto em outra campanha de negociação marketplace
                if (this.entry.campaign_type == '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>' && this.entry.segment == '<?=CampaignSegment::STORE;?>') {

                    canContinue = false;

                    await this.$http.post(base_url + 'campaigns_v2/validate_campaign_marketplace_trading_segment_stores', this.entry).then(response => {

                        if ((response.body.result && confirm('<?=lang('any_store_has_any_product_in_another_campaign_marketplace_trading');?>')) || !response.body.result){
                            canContinue = true;
                        }

                    }, response => {
                        alert('Ocorreu um erro inesperado, por favor, tente novamente.');
                        this.saving = false;
                    });

                }

                if (canContinue) {

                    const entry = Object.assign({}, this.entry);
                    for (let i = 0; i < entry.products.length; i++) {
                        delete entry.products[i].name;
                    }

                    this.$http.post(reqURL, entry).then(response => {

                        if (response.body.redirect) {
                            window.location.href = response.body.redirect;
                        } else {
                            this.submitResponse = response.body;
                            window.scrollTo(0, 0);
                            this.saving = false;
                        }

                    }, response => {
                        alert('Ocorreu um erro inesperado ao salvar a campanha.');
                        this.saving = false;
                    });

                } else {
                    this.saving = false;
                }


            },
            submitProducts () {

                this.confirmSubmitProducts();

            },
            confirmSubmitProducts () {
                this.saving = true;

                let reqURL = base_url + 'campaigns_v2/add_products';

                this.$http.post(reqURL, this.entry).then(response => {

                    if (response.body.redirect) {
                        window.location.href = response.body.redirect;
                    } else {
                        this.saving = false;
                        this.submitResponse = response.body;
                        window.scrollTo( 0, 0 );
                    }

                }, response => {
                    this.saving = false;
                    alert('Ocorreu um erro inesperado ao salvar os produtos da campanha.');
                });

            },
            generateClassSubmitResponse () {
                return 'alert-'+this.submitResponse.type;
            },
            closeSubmitDialog () {
                this.submitResponse = '';
            },
            changeProductComissionRule ( product, index ) {
            },
            validateMaxSellerIndex(){
                if (this.entry.min_seller_index.length > 1){
                    this.entry.min_seller_index = this.entry.min_seller_index[0];
                }
                if (this.entry.min_seller_index < 1){
                    this.entry.min_seller_index = 1;
                }
                if (this.entry.min_seller_index > 5){
                    this.entry.min_seller_index = 5;
                }
            },
            changeParticipatingComission(){

                var filteredStores = [];

                this.stores.forEach(function (store){

                    //Se ambos estão em branco, tudo liberado
                    if (app.entry.participating_comission_from == 0 && app.entry.participating_comission_to == 0){
                        filteredStores.push(store);
                    }
                    //Se inicial em branco, não tem limite de inicio
                    if (app.entry.participating_comission_from == 0 && app.entry.participating_comission_to >= store.service_charge_value){
                        filteredStores.push(store);
                    }
                    //Se final em branco, não tem limite de fim
                    if (app.entry.participating_comission_to == 0 && app.entry.participating_comission_from <= store.service_charge_value){
                        filteredStores.push(store);
                    }
                    //Se está no range
                    if (app.entry.participating_comission_from <= store.service_charge_value && app.entry.participating_comission_to >= store.service_charge_value){
                        filteredStores.push(store);
                    }

                });

                this.filteredStores = filteredStores;

                $('.selectpicker').selectpicker('refresh');

            },
            changeCampaignType ({ type, target }) {
                this.entry.discount_type = 'discount_percentage';
                this.entry.participating_comission_from = null;
                this.entry.participating_comission_to = null;
                if (this.entry.campaign_type == '<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>') {
                    this.entry.comission_rule = '<?=ComissionRuleEnum::NEW_COMISSION;?>';
                    this.entry.segment = '<?=CampaignSegment::PRODUCT;?>';
                    this.segments = {"<?=CampaignSegment::PRODUCT;?>": "<?=CampaignSegment::getDescription(CampaignSegment::PRODUCT);?>"};
                    this.entry.paymentMethods = [];
                    this.entry.tradePolicies = [];
                }else if(this.entry.campaign_type == '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'){
                    this.entry.comission_rule = '<?=ComissionRuleEnum::NEW_COMISSION;?>';
                    this.entry.segment = '<?=CampaignSegment::PRODUCT;?>';
                    this.segments = {"<?=CampaignSegment::PRODUCT;?>": "<?=CampaignSegment::getDescription(CampaignSegment::PRODUCT);?>","<?=CampaignSegment::STORE;?>": "<?=CampaignSegment::getDescription(CampaignSegment::STORE);?>"};
                }else{
                    this.segments = this.segmentsOri;
                }

                setTimeout(function(){
                    $('.selectpicker').selectpicker('refresh');
                },100);

            },
            // changeMarketplace ({ type, target }) {
            changeMarketplace: function(clear = true) {

                this.paymentMethodsOptionsAvailable = [];
                this.tradePoliciesOptionsAvailable = [];

                if (clear){
                    this.entry.paymentMethods = [];
                    this.entry.tradePolicies = [];
                }

                if (this.entry.marketplaces.length == 1 && Object.keys(this.paymentMethodsOptions).length > 0) {
                    let int_to = this.entry.marketplaces[0];
                    if (typeof this.paymentMethodsOptions[int_to] !== 'undefined') {
                        this.paymentMethodsOptionsAvailable = this.paymentMethodsOptions[int_to];
                    }
                }

                if (this.entry.marketplaces.length == 1 && Object.keys(this.tradePoliciesOptions).length > 0) {
                    let int_to = this.entry.marketplaces[0];
                    if (typeof this.tradePoliciesOptions[int_to] !== 'undefined') {
                        this.tradePoliciesOptionsAvailable = this.tradePoliciesOptions[int_to];
                    }
                }

                setTimeout(function(){
                    $('.selectpicker').selectpicker('refresh');
                },100);

            },
            changeSegment ({ type, target }) {

                if (this.entry.segment == '<?=CampaignSegment::STORE?>') {
                    this.entry.categories = [];
                    this.entry.products = [];
                    $('#categories').selectpicker('deselectAll');
                }else if (this.entry.segment == '<?=CampaignSegment::CATEGORY?>'){
                    this.entry.stores = [];
                    this.entry.products = [];
                    $('#stores').selectpicker('deselectAll');
                }else{
                    this.entry.categories = [];
                    this.entry.stores = [];
                    $('#categories').selectpicker('deselectAll');
                }

            },changeSegmentClick (value) {

                this.entry.segment = value;
                this.$root.$emit('changeSegment');
            },
            cloneStoreFile(e)
            {
                this.storeMessage = e.target.files[0].name;  // Actual assignment
            },
            cloneProductFile(e)
            {
                this.productMessage = e.target.files[0].name;  // Actual assignment
            },
            toggleProductFields(id)
            {
                this.show_product_fields = (this.show_product_fields == id) ? this.show_product_fields = 0 : this.show_product_fields = id
            },
            changeDiscountType ({ type, target }) {
                if (target.value == 'discount_percentage'){
                    this.entry.fixed_discount = null;
                }else{
                    this.entry.discount_percentage = null;
                }
            },
            submitSearchProduct: function() {

                let reqURL = base_url + 'campaigns_v2/searchProducts?searchString='+this.productSearchQuery;

                let entry = JSON.parse(JSON.stringify(this.entry))

                entry.categories = [];

                this.$http.post(reqURL, JSON.stringify(entry)).then(response => {

                    this.productsearchresult = response.body;

                }, response => {
                    alert('Ocorreu um erro ao buscar os produtos');
                });

            },
            submitSearchProductElegible: function() {

                let reqURL = base_url + 'campaigns_v2/searchProductsElegible?searchString='+this.productSearchQuery;

                this.$http.post(reqURL, JSON.stringify(this.entry)).then(response => {

                    this.productsearchresult = response.body.products;

                }, response => {
                    alert('Ocorreu um erro ao buscar os produtos');
                });

            },

            addAllProductsFromSearchResult: async function() {

                //Se o tipo de campanha for redução de comissão ou negociação marketplace, vamos deixar como era
                if (this.entry.campaign_type == '<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>' || this.entry.campaign_type == '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'){
                    for (let i = 0; i < this.productsearchresult.length; i++) {

                        // if(( i % 100 ) === 0){
                        //     await new Promise(r => setTimeout(r, 5000));
                        //     console.log('Pausa para recuperacão do browser: ' + i);
                        // }

                        let product = this.productsearchresult[i];

                        this.addProduct(product.id, null, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign);

                    }
                }else{

                    //Tipo de campanha é de desconto, então precisamos executar uma validação em massa e não 1 por 1

                    let marketplacesIntTo = '';
                    if (this.entry.marketplaces){
                        marketplacesIntTo = this.entry.marketplaces.join('|');
                    }

                    let arrayProducts = [];
                    for (let i = 0; i < this.productsearchresult.length; i++) {
                        arrayProducts.push(this.productsearchresult[i].id)
                    }

                    let anotherDiscountCampaign = [];
                    let anotherDiscountCampaignB2w = [];

                    let reqURL = base_url + 'campaigns_v2/arrayProductIsAnotherCampaign/'+marketplacesIntTo;
                    await this.$http.post(reqURL, arrayProducts).then(response => {
                        anotherDiscountCampaign = response.body.exist ?? false;
                        anotherDiscountCampaignB2w = response.body.b2w_exist ?? false;
                    }, error => {
                        console.log(error);
                        alert('Ocorreu um erro ao buscar os dados do produto. Tente mais tarde!');
                    });

                    var messages_array = [];

                    for (let i = 0; i < this.productsearchresult.length; i++) {

                        let product = this.productsearchresult[i];

                        if (this.entry.marketplaces.includes('B2W')
                            && anotherDiscountCampaignB2w && anotherDiscountCampaignB2w.includes(product.id)){
                            messages_array.push(
                                'Produto: '+product.id+': <?=lang('campaign_cannot_join_another_b2w_campaign');?>'
                            );
                        }else{

                            if (anotherDiscountCampaign && anotherDiscountCampaign.includes(product.id)){
                                messages_array.push('Produto '+product.id+' ('+product.name+') já foi adicionado em outra campanha do mesmo Marketplace. Caso queira adicionar mesmo assim, clique no botão de adicionar do próprio produto.');
                            }else{

                                var inserted = false;

                                this.entry.products.forEach(function (productSelected){
                                    if (productSelected.id == product.id){
                                        inserted = true;
                                    }
                                });

                                if (!inserted){

                                    Vue.set(product, 'maximum_share_sale_price', '0')

                                    this.entry.products.push(product);

                                }

                            }

                        }

                    }

                }

                if (messages_array.length > 0){

                    this.submitResponse = {"message" : messages_array.join('<br>'), "type" : "warning"};
                    window.scrollTo( 0, 0 );

                }

            },
            deleteProduct: function(index) {
                this.entry.products.splice(index, 1);
                this.submitSearchProductElegible();
            },
            addProduct: async function (productId, maximum_share_sale_price, anotherDiscountCampaign, anotherComissionReductionRebate, another_marketplace_trading_campaign) {

                let marketplacesIntTo = '';
                if (this.entry.marketplaces){
                    marketplacesIntTo = this.entry.marketplaces.join('|');
                }

                let tradePolicies = '';
                if (this.entry.tradePolicies){
                    tradePolicies = this.entry.tradePolicies.join('|');
                }

                let showConfirm;
                let indexFound = null;

                //Só podemos usar a validação abaixo caso a campanha atual for do tipo de desconto
                if (this.entry.campaign_type != '<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>' && this.entry.campaign_type != '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'){

                    let reqURL = base_url + 'campaigns_v2/productIsAnotherCampaign/'+productId+'/'+marketplacesIntTo+'/'+tradePolicies;
                    await this.$http.get(reqURL).then(response => {
                        anotherDiscountCampaign = response.body.exist ?? false;
                        anotherDiscountCampaignB2w = response.body.b2w_exist ?? false;
                    }, error => {
                        console.log(error);
                        alert('Ocorreu um erro ao buscar os dados do produto. Tente mais tarde!');
                    });

                }else{
                    anotherDiscountCampaign = false;
                    anotherDiscountCampaignB2w = false;
                }

                //Se já está em outra B2W, não permitir
                if (this.entry.marketplaces.includes('B2W') && anotherDiscountCampaignB2w){
                    alert('<?=lang('campaign_cannot_join_another_b2w_campaign');?>');
                }else{

                    //Se não se repetiu em nenhum lugar, não precisamos validar mais nada.
                    if (!anotherDiscountCampaign && !anotherComissionReductionRebate && !another_marketplace_trading_campaign){
                        showConfirm = false;
                    }else{

                        //Se o tipo é redução de comissão e rebate e está em outra redução de comissão e rebate.
                        showConfirm = (this.entry.campaign_type == '<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>' && anotherComissionReductionRebate) || this.entry.campaign_type == '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>' && another_marketplace_trading_campaign;

                        //Se até aqui não é para mostrar, validaremos se estiver na página do produto e está em outra campanha com desconto.
                        if (!showConfirm && anotherDiscountCampaign && this.page == 'products'){
                            showConfirm = true;
                        }

                    }

                    if (!showConfirm || confirm('<?=lang('application_product_is_participating_another_campaign');?>'.replace('%s', productId))){

                        for (let i = 0; i < this.productsearchresult.length; i++) {

                            let product = this.productsearchresult[i];

                            if (product.id == productId){

                                indexFound = i;

                                var inserted = false;

                                this.entry.products.forEach(function (productSelected){
                                    if (productSelected.id == productId){
                                        inserted = true;
                                    }
                                });

                                if (!inserted){

                                    Vue.set(product, 'maximum_share_sale_price', maximum_share_sale_price)

                                    this.entry.products.push(product);

                                    if (this.entry.products.length == 1)
                                    {
                                        this.show_product_fields = productId;
                                    }
                                }
                            }

                        }

                    }

                }


            },
            async sendCsvStore() {
                this.uploadingStores = true;
                let dataForm = new FormData();
                for (let file of this.$refs.filesStore.files) {
                    dataForm.append(`file`, file);
                }
                let reqURL = base_url + 'campaigns_v2/upload_csv_stores';
                let res = await fetch(reqURL, {
                    method: 'POST',
                    body: dataForm,
                });
                let data = await res.json();


                if (data.message){

                    this.submitResponse = data;

                    window.scrollTo( 0, 0 );

                }else{

                    if (data){
                        let ids = [];
                        for (i = 0; i < data.length; i++) {
                            ids.push(data[i].ID);
                        }
                        this.entry.stores = ids;
                        $('#stores').selectpicker('val', ids);
                    }

                }
                
                this.uploadingStores = false;
            },
            <?php
            if (isset($campaign) && $campaign['id']){
                ?>
                async sendApprovementProducts() {
                    this.uploadingApprovementProducts = true;
                    let dataForm = new FormData();
                    for (let file of this.$refs.filesMassApprovement.files) {
                        dataForm.append(`file`, file);
                    }
                    let reqURL = base_url + 'campaigns_v2/upload_approvement_products/<?php echo $campaign['id'];?>';
                    let res = await fetch(reqURL, {
                        method: 'POST',
                        body: dataForm,
                    });
                    let data = await res.json();
                    if (data){
                        if (data.redirect){
                            window.location.href = data.redirect;
                        }else{
                            this.submitResponse = data;
                            window.scrollTo( 0, 0 );
                        }
                    }
                    this.uploadingApprovementProducts = false;
                },
                async sendImportProductsSeller() {
                    this.uploadingImportProductsSeller = true;
                    let dataForm = new FormData();
                    for (let file of this.$refs.filesMassImportSeller.files) {
                        dataForm.append(`file`, file);
                    }
                    let reqURL = base_url + 'campaigns_v2/upload_products_seller/<?php echo $campaign['id'];?>';
                    let res = await fetch(reqURL, {
                        method: 'POST',
                        body: dataForm,
                    });
                    let data = await res.json();
                    if (data){
                        if (data.message){

                            this.submitResponse = data;

                            window.scrollTo( 0, 0 );

                        }

                        if (data.products){

                            this.productsearchresult = data.products;

                            data.products.forEach(function (product){
                                app.addProduct(product.id, product.maximum_share_sale_price, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign);
                            });

                        }
                    }
                    $('#fileMassImportSeller').val('');
                    this.uploadingImportProductsSeller = false;
                },
            <?php
            }
            ?>
            async sendCsvProduct() {
                this.uploadingProducts = true;
                let dataForm = new FormData();
                for (let file of this.$refs.filesProduct.files) {
                    dataForm.append(`file`, file);
                }
                if (['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(this.entry.campaign_type)){
                    dataForm.append(`comission_rule`, this.entry.comission_rule);
                    dataForm.append(`new_comission`, this.entry.new_comission);
                    dataForm.append(`comission_rebate`, this.entry.rebate_value);
                    reqURL = base_url + 'campaigns_v2/upload_csv_products_reduction_comission_rebate_campaign';
                }else{
                    dataForm.append(`entry`, JSON.stringify(this.entry));
                    reqURL = base_url + 'campaigns_v2/upload_csv_products';
                }
                let res = await fetch(reqURL, {
                    method: 'POST',
                    body: dataForm,
                });
                let data = await res.json();
                if (data){

                    if (data.message){

                        this.submitResponse = data;

                        window.scrollTo( 0, 0 );

                    }else{

                        this.productsearchresult = data;

                        data.forEach(function (product){
                            app.addProduct(product.id, product.maximum_share_sale_price, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign);
                        });

                    }

                    $('#fileProduct').val('');

                    this.uploadingProducts = false;

                }
            }
        }
    });

    function addProduct(obj, productId, maximum_share_sale_price, another_discount_campaign, another_comission_rebate_campaign, another_marketplace_trading_campaign){
        app.addProduct(productId, maximum_share_sale_price, another_discount_campaign, another_comission_rebate_campaign, another_marketplace_trading_campaign);
    }

    function activateDesactivateProduct(obj, campaignId, campaignProductKey){

        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                active: obj.checked ? 1 : 0,
            },
            url: base_url + "campaigns_v2/manage_products_status/"+campaignId+'/'+campaignProductKey,
            dataType: "json",
            async: true,
            success: function(response) {
                if (response.redirect){
                    window.location.href = response.redirect;
                }
            },
            error: function(error) {
                console.log(error)
            }
        });

    }

    function aproveUnaproveProduct(obj, campaignId, campaignProductKey){

        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                approved: obj.checked ? 1 : 0,
            },
            url: base_url + "campaigns_v2/manage_products_status/"+campaignId+'/'+campaignProductKey,
            dataType: "json",
            async: true,
            success: function(response) {
                if (response.redirect){
                    window.location.href = response.redirect;
                }
            },
            error: function(error) {
                console.log(error)
            }
        });

    }

    function downloadFile(obj, campaignId){

        let store = $('#searchByStore').val();
        if (!store){
            store = '';
        }

        let url = base_url + "campaigns_v2/download_products_in_campaign/"+campaignId+"?store="+store;

        window.location.href = url;

    }




</script>