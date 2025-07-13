<?php
use App\Libraries\Enum\CampaignSegment;
use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\ComissionRuleEnum;
use App\Libraries\Enum\DiscountTypeEnum;
use App\Libraries\Enum\PaymentGatewayEnum;
?>

<!--https://github.com/mengxiong10/vue2-datepicker-->
<link rel="stylesheet" href="<?php echo base_url('assets/vue/datetime_picker/index.css') ?>">

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/index.min.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/datetime_picker/pt-br.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/money/v-money.js') ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/vue/vue-resource.min.js') ?>" type="text/javascript"></script>

<script src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/pipeline.js') ?>" type="text/javascript"></script>

<style type="text/css">
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
                <div class="col-md-12 col-xs-12" v-if="entry.seller_type == 0">
                    <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=sellerCampaign') ?>" class="btn btn-primary mb-3 mt-3 pull-right">
                        <i class="fa fa-plus"></i>
						<?= lang('application_add_campaign_v2_seller'); ?>
                    </a>
                </div>
			<?php } ?>
        </div>

        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div class="alert" v-bind:class="generateClassSubmitResponse()" role="alert" v-show="submitResponse && submitResponse.message" style="display: none;">
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

                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><?= $this->lang->line('application_general_info'); ?></h3>
                        </div>
                        <div class="box-body">

                            <div class="row">

                                <div class="form-group col-md-4 col-xs-4">
                                    <label for="name"><?= $this->lang->line('application_name_campaign'); ?> *</label>
                                    <input :disabled="allInputsDisabled" :readonly="allInputsDisabled" v-model.trim="entry.name" type="text" class="form-control" id="name" autocomplete="off" placeholder="<?= $this->lang->line('application_enter_name_campaign') ?>" maxlength="100" />
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="start_date"><?= $this->lang->line('application_start_date'); ?>(*)</label>
                                    <date-picker id="start_date"
                                                 v-model.trim="entry.start_date"
                                                 type="date"
                                                 value-type="YYYY-MM-DD"
                                                 format="DD/MM/YYYY"
                                                 :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                    ></date-picker>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="start_date_pick_time"><?= $this->lang->line('application_start_time'); ?>(*)</label>
                                    <date-picker id="start_time"
                                                 v-model.trim="entry.start_time"
                                                 type="time"
                                                 value-type="HH:mm"
                                                 format="HH:mm"
                                                 :minute-options="[00,05,10,15,20,25,30,35,40,45,50,55]"
                                                 :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                    ></date-picker>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="end_date"><?= $this->lang->line('application_end_date'); ?>(*)</label>
                                    <date-picker id="end_date"
                                                 v-model.trim="entry.end_date"
                                                 type="date"
                                                 value-type="YYYY-MM-DD"
                                                 format="DD/MM/YYYY"
                                                 :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                    ></date-picker>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="end_date_pick_time"><?= $this->lang->line('application_end_time'); ?>(*)</label>
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

                            <div class="row">
                                <div class="form-group col-md-10 col-xs-10 <?php echo (form_error('description')) ? 'has-error' : ''; ?>">
                                    <label for="description"><?= $this->lang->line('application_description'); ?></label>
                                    <textarea :disabled="allInputsDisabled" :readonly="allInputsDisabled" v-model.trim="entry.description" class="form-control" rows="4" id="description" autocomplete="off" placeholder="<?= $this->lang->line('application_enter_description') ?>"></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-4 col-xs-4">
                                    <label for="marketplaces"><?= $this->lang->line('application_marketplaces') ?> *</label>
                                    <select v-model.trim="entry.marketplaces"
                                            @change="changeMarketplace"
                                            class="form-control selectpicker show-tick" data-live-search="true"
                                            data-actions-box="true" id="marketplaces" :disabled="allInputsDisabled || entry.b2w_type" :readonly="allInputsDisabled || entry.b2w_type" multiple="multiple">
										<?php foreach ($marketplaces as $marketplace): ?>
                                            <option value="<?php echo $marketplace['int_to'] ?>"><?php echo $marketplace['name'] ?></option>
										<?php endforeach ?>
                                    </select>
                                </div>
								<?php if($this->input->get('defaultType') !== "sellerCampaign"){ ?>
                                    <div class="form-group col-md-4">
                                        <label for="deadline_for_joining" style="width: 100%;"><?= $this->lang->line('application_deadline_for_joining'); ?></label>
                                        <date-picker id="deadline_for_joining"
                                                     v-model.trim="entry.deadline_for_joining"
                                                     type="datetime"
                                                     value-type="YYYY-MM-DD HH:mm"
                                                     format="DD/MM/YYYY - HH:mm"
                                                     :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                        ></date-picker>
                                    </div>
								<?php } ?>
                            </div>

                        </div>

                    </div>

                    <!-- Regras -->
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title"><?= $this->lang->line('application_rules'); ?></h3>
                        </div>
                        <div class="box-body">

                            <div class="row">

                                <div class="form-group col-md-4 col-xs-4">
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
                                </div>

                                <div class="form-group col-md-3" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type)">
                                    <label for="products_auto_approval" style="width: 100%;"><?= $this->lang->line('campaign_auto_approval_products'); ?></label>
                                    <input type="checkbox"
                                           id="products_auto_approval"
                                           v-model="entry.products_auto_approval"
                                           true-value="1"
                                           false-value="0"
                                           class="form-check-input"
                                           style="width: 25px; height: 25px;"
                                           :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                    >
                                </div>

                            </div>

                            <div class="row">

                                <div class="form-group col-md-3 col-xs-3" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type)">
                                    <label for="campaign_type"><?=$this->lang->line('application_discount_type')?></label>
                                    <?php
                                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                                    ?>
                                    <select v-model.trim="entry.discount_type" class="form-control" id="discount_type"
                                            :disabled="allInputsDisabled"
                                            :readonly="allInputsDisabled"
                                            @change="changeDiscountType">
                                        <option v-for="(name, key) in filteredDiscountTypes" :value="key">{{ name }}</option>
                                    </select>
                                    <?php
                                    }else{
                                    ?>
                                        <select v-model.trim="entry.discount_type" class="form-control" id="discount_type" :disabled="allInputsDisabled" :readonly="allInputsDisabled"
                                                @change="changeDiscountType">
                                            <?php foreach ($discount_types as $key => $name): ?>
                                                <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <div class="form-group col-md-3 col-xs-3" v-if="['shared_discount', 'merchant_discount', 'channel_funded_discount'].includes(entry.campaign_type) && entry.discount_type == 'discount_percentage'">

                                    <label for="fixed_discount" v-if="['shared_discount'].includes(entry.campaign_type)">
										<?=$this->lang->line('application_discount_percentage_total')?>
                                    </label>
                                    <label for="fixed_discount" v-if="['merchant_discount'].includes(entry.campaign_type)">
										<?=$this->lang->line('application_discount_percentage_total_seller')?>
                                    </label>
                                    <label for="fixed_discount" v-if="['channel_funded_discount'].includes(entry.campaign_type)">
										<?=$this->lang->line('application_discount_percentage_total_marketplace')?>
                                    </label>

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

                                    <money v-model.trim="entry.fixed_discount"
                                           v-bind="money"
                                           @change="changeDiscountType"
                                           @input="changeDiscountType"
                                           id="fixed_discount"
                                           class="form-control"
                                           :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                </div>

                                <div class="form-group col-md-1 pt-5" v-if="['shared_discount'].includes(entry.campaign_type)" style="width: 45px;">
                                    <i class="fa fa-equals"></i>
                                </div>

                                <div class="form-group col-md-2" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">

                                    <label for="seller_discount_percentual"><?=$this->lang->line('application_discount_percentage_total_seller')?></label>

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

                                <div class="form-group col-md-2" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">

                                    <label for="seller_discount_fixed"><?=$this->lang->line('application_fixed_discount_total_seller')?></label>

                                    <money v-model.trim="entry.seller_discount_fixed"
                                           v-bind="money"
                                           id="seller_discount_fixed"
                                           class="form-control"
                                           :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                </div>

                                <div class="form-group col-md-1 pt-5" v-if="['shared_discount'].includes(entry.campaign_type)" style="width: 45px;">
                                    <i class="fa fa-plus"></i>
                                </div>

                                <div class="form-group col-md-2" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::PERCENTUAL; ?>'">
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

                                <div class="form-group col-md-2" v-if="['shared_discount'].includes(entry.campaign_type) && entry.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                    <label for="marketplace_discount_fixed"><?=$this->lang->line('application_fixed_discount_total_marketplace')?></label>
                                    <money v-model.trim="entry.marketplace_discount_fixed"
                                           v-bind="money"
                                           id="marketplace_discount_fixed"
                                           class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                </div>

                            </div>

                            <div class="row" v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">

                                <div class="form-group col-md-3">

                                    <label for="comission_rule"><?=$this->lang->line('application_comission_rule')?></label>

                                    <select id="comission_rule" v-model.trim="entry.comission_rule" class="form-control" :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                        <option v-for="(name, value) in comissionRules" :value="value">{{name}}</option>
                                    </select>

                                </div>

                                <div class="form-group col-md-3">

                                    <div v-if="entry.comission_rule == 'new_comission'">

                                        <label for="comission_rule"><?=$this->lang->line('application_new_comission')?></label>
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
                                        <label for="comission_rule"><?=$this->lang->line('application_comission_rebate')?></label>
                                        <money v-model.trim="entry.rebate_value"
                                               v-bind="money"
                                               class="form-control"
                                               placeholder="<?php echo $this->lang->line('application_comission_rebate');?>"
                                               :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                    </div>

                                </div>

                            </div>

                            <div class="row">
                                <div v-if="['channel_funded_discount'].includes(entry.campaign_type)">

                                    <div class="form-group col-md-3">
                                        <label for="fixed_discount_from"><?=$this->lang->line('application_participating_comission')?></label>
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

                                </div>
                            </div>

							<?php
							if(isset($use_payment_methods) && $use_payment_methods && $only_admin) {
								?>
                                <div class="row">

                                    <div class="form-group col-md-4 col-xs-4" v-show="paymentMethodsOptionsAvailable.length > 0 && entry.campaign_type.length > 0 && ['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                        <label for="paymentMethods"><?= $this->lang->line('application_payment_methods') ?></label>
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
                                        <label for="tradePolicies"><?= $this->lang->line('application_trade_policies') ?></label>
                                        <select v-model.trim="entry.tradePolicies" class="form-control selectpicker show-tick" data-live-search="true"
                                                data-actions-box="true" id="tradePolicies" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                                            <option v-for="tradePolicyOptionAvailable in filteredTradePoliciesOptionsAvailable"
                                                    :value="tradePolicyOptionAvailable.trade_policy_id">
                                                {{tradePolicyOptionAvailable.trade_policy_id}} - {{tradePolicyOptionAvailable.trade_policy_name}}
                                            </option>
                                        </select>
                                    </div>

                                </div>
								<?php
							}
							?>

                            <div class="row">

                                <div class="form-group col-md-3 col-xs-3" v-if="['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                    <label for="product_min_value"><?=$this->lang->line('application_prod_min_value')?></label>
                                    <money v-model.trim="entry.product_min_value"
                                           v-bind="money"
                                           id="product_min_value"
                                           class="form-control"
                                           :disabled="allInputsDisabled" :readonly="allInputsDisabled"></money>
                                </div>

                                <div class="form-group col-md-3 col-xs-3" v-if="['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">
                                    <label for="product_min_quantity"><?=$this->lang->line('application_product_min_stock_quantity')?></label>
                                    <input v-model.trim="entry.product_min_quantity" id="product_min_quantity" type="number" step="1" min="0" class="form-control"
                                           :disabled="allInputsDisabled" :readonly="allInputsDisabled"/>
                                </div>

								<?php if($this->input->get('defaultType') !== "sellerCampaign" && (isset($campaign['seller_type']) && $campaign['seller_type'] == 0)){ ?>
                                    <div class="form-group col-md-2" v-show="['shared_discount', 'channel_funded_discount', 'merchant_discount'].includes(entry.campaign_type)">

                                        <label for="seller_index"><?= $this->lang->line('application_seller_index') ?> >=</label>

                                        <input type="number" class="form-control" id="seller_index" v-model.trim="entry.min_seller_index"
                                               @change="validateMaxSellerIndex"
                                               @input="validateMaxSellerIndex"
                                               :min="1" :max="5"
                                               placeholder="<?= $this->lang->line('application_enter_the_seller_index') ?>" autocomplete="off"
                                               :disabled="allInputsDisabled" :readonly="allInputsDisabled">

                                    </div>
								<?php } ?>

                            </div>


                        </div>

                    </div>

                    <?php
                    if ($campaign['id'] && $campaign['is_owner'] && ($campaign['vtex_campaign_update'] > 0 || (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && $campaign['occ_campaign_update'] > 0))){
                    ?>
                        <div class="box box-warning with-border">
                            <div class="box-header with-border">
                                <h3 class="box-title"><?= $this->lang->line('application_vtex_status'); ?></h3>
                            </div>
                            <div class="box-body">
                                <?php
                                if ($campaign && isset($campaign['ds_vtex_campaign_creation']) && $campaign['ds_vtex_campaign_creation']){
                                ?>
                                    <p class="bg-danger">
                                        <?php
                                        echo $campaign['ds_vtex_campaign_creation'];
                                        ?>
                                    </p>
                                <?php
                                }elseif(isset($last_vtex_campaign) && $last_vtex_campaign){
                                ?>
                                    <p class="bg-success">
                                        <?= $this->lang->line('application_campaign_vtex_status_message_success'); ?>
                                        <b><?php echo dateFormat($last_vtex_campaign['date_insert'], DATETIME_BRAZIL); ?></b>
                                    </p>
                                <?php
                                }else{
                                ?>
                                    <p>
                                        <?= $this->lang->line('application_campaign_vtex_status_message_in_process'); ?>
                                    </p>
                                <?php
                                }
                                ?>
                            </div>
                        </div>

                    <?php
                    }
                    ?>

                    <div class="box box-danger" v-show="entry.campaign_type">

                        <div class="box-header with-border">
                            <h3 class="box-title"><?= $this->lang->line('application_criteria_participate'); ?></h3>
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

                            <div class="row">
                                <div class="form-group col-md-3 col-xs-3">
                                    <label for="segment"><?=lang('application_campaign_segment_by')?> *</label>
                                    <select v-model.trim="entry.segment" class="form-control"
                                            @change="changeSegment"
                                            :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                        <option v-for="(name, value) in segments" :value="value">{{name}}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row" v-show="entry.segment == '<?php echo CampaignSegment::CATEGORY; ?>'" v-if="!allInputsDisabled">

                                <div class="form-group col-md-12">

                                    <label for="name"><?=$this->lang->line('application_promotion_category');?> *</label>

                                    <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="categories" multiple="multiple" v-model="entry.categories">
                                        <option v-for="category in categories" :value="category.id">{{category.name}}</option>
                                    </select>

                                </div>

                            </div>

                            <div class="row">

                                <div class="form-group col-md-5" v-show="entry.segment == '<?php echo CampaignSegment::STORE; ?>' && page == ''">

                                    <label for="stores"><?=$this->lang->line('application_participating_stores');?> *</label>

                                    <select v-model.trim="entry.stores" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="stores" multiple="multiple">
                                        <option v-for="store in filteredStores" :value="store.id">{{store.name}}</option>
                                    </select>

                                </div>

                                <div class="form-group col-md-4" v-show="entry.segment == '<?php echo CampaignSegment::STORE; ?>'  && page == ''">
                                    <label for="stores"><?=$this->lang->line('application_upload_stores_csv_to_massive_import');?></label>
                                    <a href="<?=base_url('assets/files/campaign_sample_stores.csv') ?>"><?=lang('application_download_sample');?></a>
                                    <div class="input-group">
                                        <input type="file" name="fileStore" ref="filesStore" class="form-control" />
                                        <div class="input-group-addon">
                                            <button type="button" class="btn btn-outline-secondary" style="line-height: 0.4; padding: 0;"
                                                    @click="sendCsvStore"
                                                    :disabled="uploadingStores">
                                                <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingStores"></i><span class="sr-only">Loading...</span>
												<?=lang('application_send');?>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div v-show="entry.segment == '<?php echo CampaignSegment::PRODUCT; ?>'" v-if="!allInputsDisabled || entry.segment == '<?php echo CampaignSegment::PRODUCT; ?>'">

                                <div class="row">

                                    <div class="form-group col-md-3">
                                        <label for="search_product"><?=lang('application_product');?></label>
                                        <input id="search_product" type="text" v-model.trim="productSearchQueryElegible" placeholder="<?= $this->lang->line('application_inform_sku_name_description_product'); ?>" class="form-control" @keyup="submitSearchProduct" @change="submitSearchProduct">
                                    </div>

                                    <div class="form-group col-md-4" v-show="entry.segment == '<?php echo CampaignSegment::PRODUCT; ?>'">
                                        <label for="stores"><?=$this->lang->line('application_upload_products_csv_to_massive_import');?></label>
                                        <a v-if="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)" href="<?=base_url('assets/files/campaign_sample_products_comission_reduction_rebate.csv') ?>"><?php echo lang('application_download_sample');?></a>
                                        <a v-if="['<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)" href="<?=base_url('assets/files/campaign_sample_products_marketplace_trading.csv') ?>"><?php echo lang('application_download_sample');?></a>
                                        <a v-if="!['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)" href="<?=base_url('assets/files/campaign_sample_products.csv') ?>"><?php echo lang('application_download_sample');?></a>
                                        <div class="input-group">
                                            <input id="fileProduct" type="file" name="fileProduct" ref="filesProduct" class="form-control" />
                                            <div class="input-group-addon">
                                                <button type="button" class="btn btn-outline-secondary" style="line-height: 0.4; padding: 0;"
                                                        @click="sendCsvProduct"
                                                        :disabled="uploadingProducts">
                                                    <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingProducts"></i><span class="sr-only">Loading...</span>
													<?=lang('application_send');?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group col-md-7">
                                        <div class="alert alert-warning" role="alert">
                                            <b>Atenção:</b> Só serão listados os produtos integrados no marketplace, com estoque e ativos, que ainda não foram aderidos ou reprovados automaticamente.
                                        </div>
                                    </div>
                                </div>

                                <div v-show="productsearchresultelegible.length > 0">


                                    <div class="box">
                                        <div class="box-header">
                                            <h3 class="box-title"><?= $this->lang->line('application_alloc_product'); ?></h3>
                                        </div>
                                        <div class="box-body">

                                            <data-table-eligible :productsearchresultelegible="filteredProductsElegible"></data-table-eligible>

                                        </div>

                                    </div>

                                </div>

                                <div v-if="entry.add_elegible_products.length > 0">

                                    <div class="box">
                                        <div class="box-header">
                                            <h3 class="box-title"><?= $this->lang->line('add_campaign_v2_elegible_products'); ?></h3>
                                        </div>
                                        <div class="box-body">

                                            <table class="table table-striped table-hover responsive table-condensed lista-produtos-1 lista-elegiveis-tabela-1">
                                                <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th><?= $this->lang->line('application_store'); ?></th>
                                                    <th><?= $this->lang->line('application_sku'); ?></th>
                                                    <th><?= $this->lang->line('application_name'); ?></th>
                                                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                                    <th><?= $this->lang->line('application_variation'); ?></th>
                                                    <?php } ?>
                                                    <th><?= $this->lang->line('application_price'); ?></th>
                                                    <th><?= $this->lang->line('application_qty'); ?></th>
                                                    <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_maximum_selling_price_of_the_share'); ?></th>
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
                                                <tr v-for="product,index in entry.add_elegible_products">
                                                    <td>{{product.id}}</td>
                                                    <td>{{product.store}}</td>
                                                    <td>{{product.sku}}</td>
                                                    <td>{{product.name}}</td>
                                                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                                    <td>
                                                        <span v-if="product.has_variants && product.variant_name">
                                                            <template v-for="(variantType, index) in product.has_variants.split(';')">
                                                                <template v-if="index > 0">; </template>
                                                                {{ variantType }}: {{ product.variant_name.split(';')[index] }}
                                                            </template>
                                                        </span>
                                                    </td>
                                                    <?php } ?>
                                                    <td>{{product.price}}</td>
                                                    <td>{{product.qty}}</td>
                                                    <td v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)">
                                                        <money v-model.trim="product.maximum_share_sale_price"
                                                               v-bind="money"
                                                               class="form-control"
                                                               placeholder="<?php echo $this->lang->line('application_maximum_selling_price_of_the_share');?>"></money>

                                                    </td>
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
                                                                <input v-model.trim="product.discount_percentage"
                                                                       id="product_discount_percentage"
                                                                       type="number"
                                                                       step="1" min="1" max="100"
                                                                       oninput="this.value=(parseInt(this.value)||0)"
                                                                       onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                                       class="form-control" style="width: 65px;" />
                                                                <div class="input-group-addon">
                                                                    %
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- R$ desconto total -->
                                                        <div class="form-group col-md-2" v-if="product.discount_type == 'fixed_discount'">
                                                            <money v-model.trim="product.fixed_discount" v-bind="money" id="product_fixed_discount" class="form-control" style="width: 100px;"></money>
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
                                                                       class="form-control" style="width: 65px;" />
                                                                <div class="input-group-addon">
                                                                    %
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- R$ desconto seller -->
                                                        <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                            <money v-model.trim="product.seller_discount_fixed" v-bind="money" id="product_seller_discount_fixed" class="form-control" style="width: 100px;"></money>
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
                                                                       class="form-control" style="width: 65px;" />
                                                                <div class="input-group-addon">
                                                                    %
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- R$ desconto marketplace -->
                                                        <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                            <money v-model.trim="product.marketplace_discount_fixed" v-bind="money" id="product_marketplace_discount_fixed" class="form-control" style="width: 100px;"></money>
                                                        </div>

                                                    </td>
                                                    <td>
                                                        <a class="btn btn-sm btn-danger" @click="deleteProduct(index)" title="<?php echo $this->lang->line('application_remove_campaign_product');?>"><i class="fa fa-minus"></i></a>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>

                                            <button type="button" id="btnSave" class="btn btn-warning pull-right mb-5 mt-5" @click="submitNewElegibleProducts()" :disabled="saving" v-show="entry.add_elegible_products.length > 0 && entry.id">
                                                <i class="fa fa-plus" v-show="!saving"></i>
                                                <i class="fa fa-spinner fa-spin fa-fw" v-show="saving"></i><span class="sr-only">Loading...</span>
                                                <?= $this->lang->line('application_add_new_elegible_products_in_campaign'); ?>
                                            </button>

                                        </div>

                                    </div>

                                </div>


                            </div>

                        </div>

                    </div>

                    <div v-if="entry.elegible_products.length > 0">
                        <!-- Produtos que já estão na campanha elegíveis -->
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title"><?= $this->lang->line('campaign_v2_eligible_products'); ?></h3>
                            </div>
                            <div class="box-body">
                                <table class="table table-striped table-hover responsive table-condensed lista-produtos-1 lista-elegiveis-tabela-2">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?= $this->lang->line('application_store'); ?></th>
                                        <th><?= $this->lang->line('application_sku'); ?></th>
                                        <th><?= $this->lang->line('application_name'); ?></th>
                                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                        <th><?= $this->lang->line('application_variation'); ?></th>
                                        <?php } ?>
                                        <th><?= $this->lang->line('application_price'); ?></th>
                                        <th><?= $this->lang->line('application_qty'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_maximum_selling_price_of_the_share'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_comission_rule'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_enter_value'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_discount_type'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_conciliacao_grids_title_productdiscountvalue'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_seller_discount'); ?></th>
                                        <th v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>'].includes(entry.campaign_type)"><?= $this->lang->line('application_marketplace_discount'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="product,index in entry.elegible_products">
                                        <td>{{product.id}}</td>
                                        <td>{{product.store}}</td>
                                        <td>{{product.sku}}</td>
                                        <td>{{product.name}}</td>
                                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                        <td>
                                            <span v-if="product.has_variants && product.variant_name">
                                                <template v-for="(variantType, index) in product.has_variants.split(';')">
                                                    <template v-if="index > 0">; </template>
                                                    {{ variantType }}: {{ product.variant_name.split(';')[index] }}
                                                </template>
                                            </span>
                                        </td>
                                        <?php } ?>
                                        <td>{{product.price}}</td>
                                        <td>{{product.qty}}</td>
                                        <td v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>'].includes(entry.campaign_type)">
                                            <money v-model.trim="product.maximum_share_sale_price"
                                                   v-bind="money"
                                                   class="form-control"
                                                   :disabled="allInputsDisabled"
                                                   placeholder="<?php echo $this->lang->line('application_maximum_selling_price_of_the_share');?>"></money>

                                        </td>
                                        <td v-show="['<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>', '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'].includes(entry.campaign_type)">
                                            <select v-model.trim="product.comission_rule"
                                                    class="form-control"
                                                    :disabled="allInputsDisabled"
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
                                                       placeholder="<?php echo $this->lang->line('application_new_comission');?>"
                                                       :disabled="allInputsDisabled"></money>
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
                                            <?php
                                                        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                                                        ?>
                                                            <select v-model.trim="product.discount_type" class="form-control" id="product_discount_type"
                                                                    :disabled="allInputsDisabled"
                                                                    :readonly="allInputsDisabled"
                                                                    @change="changeDiscountType">
                                                                <option v-for="(name, key) in filteredDiscountTypes" :value="key">{{ name }}</option>
                                                            </select>
                                                        <?php
                                                        }else{
                                                        ?>
                                                            <select v-model.trim="product.discount_type" class="form-control" id="product_discount_type" :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                                <?php foreach ($discount_types as $key => $name): ?>
                                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                                <?php endforeach ?>
                                            </select><?php
                                                        }
                                                        ?>
                                        </td>
                                        <td v-show="['<?=CampaignTypeEnum::SHARED_DISCOUNT;?>', '<?=CampaignTypeEnum::MERCHANT_DISCOUNT;?>', '<?=CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT;?>'].includes(entry.campaign_type)">

                                            <!-- % desconto total -->
                                            <div class="form-group col-md-2 col-xs-2" v-if="product.discount_type == '<?=DiscountTypeEnum::PERCENTUAL;?>'">
                                                <div class="input-group">
                                                    <input v-model.trim="product.discount_percentage"
                                                           id="product_discount_percentage"
                                                           type="number"
                                                           step="1" min="1" max="100"
                                                           oninput="this.value=(parseInt(this.value)||0)"
                                                           onkeypress="return !(event.charCode == 45||event.charCode == 46||event.charCode == 43)"
                                                           class="form-control" style="width: 65px;" :disabled="allInputsDisabled" />
                                                    <div class="input-group-addon">
                                                        %
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- R$ desconto total -->
                                            <div class="form-group col-md-2" v-if="product.discount_type == 'fixed_discount'">
                                                <money v-model.trim="product.fixed_discount" v-bind="money" id="product_fixed_discount" class="form-control" style="width: 100px;" :disabled="allInputsDisabled"></money>
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
                                                           class="form-control" style="width: 65px;" :disabled="allInputsDisabled" />
                                                    <div class="input-group-addon">
                                                        %
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- R$ desconto seller -->
                                            <div class="form-group col-md-2" v-if="product.discount_type == '<?php echo DiscountTypeEnum::FIXED_DISCOUNT; ?>'">
                                                <money v-model.trim="product.seller_discount_fixed" v-bind="money" id="product_seller_discount_fixed" class="form-control" style="width: 100px;" :disabled="allInputsDisabled"></money>
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
                                                <money v-model.trim="product.marketplace_discount_fixed" v-bind="money" id="product_marketplace_discount_fixed" class="form-control" style="width: 100px;" :disabled="allInputsDisabled"></money>
                                            </div>

                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <nav aria-label="Product pagination" v-if="elegible_products.total_elegible_products">
                                    <ul class="pagination">
                                        <!-- Botão Primeira Página -->
                                        <li :class="{ disabled: elegible_products.current_page === 1 }">
                                            <a
                                                    href="javascript:void(0);"
                                                    aria-label="First"
                                                    @click="changePage(1)"
                                                    :disabled="elegible_products.current_page === 1"
                                            >
                                                <span aria-hidden="true">&laquo;&laquo;</span>
                                            </a>
                                        </li>

                                        <!-- Botão Anterior -->
                                        <li :class="{ disabled: elegible_products.current_page === 1 }">
                                            <a
                                                    href="javascript:void(0);"
                                                    aria-label="Previous"
                                                    @click="changePage(elegible_products.current_page - 1)"
                                                    :disabled="elegible_products.current_page === 1"
                                            >
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>

                                        <!-- Botões de Páginas -->
                                        <li
                                                v-for="page in pagesToShow"
                                                :key="page"
                                                :class="{ active: page === elegible_products.current_page }"
                                        >
                                            <a href="javascript:void(0);" @click="changePage(page)">{{ page }}</a>
                                        </li>

                                        <!-- Botão Próximo -->
                                        <li :class="{ disabled: elegible_products.current_page === totalPages }">
                                            <a
                                                    href="javascript:void(0);"
                                                    aria-label="Next"
                                                    @click="changePage(elegible_products.current_page + 1)"
                                                    :disabled="elegible_products.current_page === totalPages"
                                            >
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>

                                        <!-- Botão Última Página -->
                                        <li :class="{ disabled: elegible_products.current_page === totalPages }">
                                            <a
                                                    href="javascript:void(0);"
                                                    aria-label="Last"
                                                    @click="changePage(totalPages)"
                                                    :disabled="elegible_products.current_page === totalPages"
                                            >
                                                <span aria-hidden="true">&raquo;&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <div class="product-info" v-if="elegible_products.total_elegible_products">
                                    <p>
                                        Exibindo: {{ entry.elegible_products.length }} de {{ elegible_products.total_elegible_products }} produtos elegíveis na campanha
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                <?php
                                if ($allow_add_products_by_deadline){
                                ?>
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

                                    <div class="row">
                                        <div class="form-group col-md-5">
                                            <div class="alert alert-warning" role="alert">
                                                <b>Atenção:</b> Só serão listados os produtos integrados no marketplace, com estoque e ativos
                                            </div>
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
                                                            <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingImportProductsSeller"></i><span class="sr-only">Loading...</span>
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

                                    <table class="table table-striped table-hover responsive table-condensed lista-produtos-2" v-show="entry.products.length > 0">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th><?= $this->lang->line('application_store'); ?></th>
                                            <th><?= $this->lang->line('application_sku'); ?></th>
                                            <th><?= $this->lang->line('application_name'); ?></th>
                                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                            <th><?= $this->lang->line('application_variation'); ?></th>
                                            <?php } ?>
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
                                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                            <td>
                                                <span v-if="product.has_variants && product.variant_name">
                                                    <template v-for="(variantType, index) in product.has_variants.split(';')">
                                                        <template v-if="index > 0">; </template>
                                                        {{ variantType }}: {{ product.variant_name.split(';')[index] }}
                                                    </template>
                                                </span>
                                            </td>
                                            <?php } ?>
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
                                                <?php
                                                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                                                    ?>
                                                    <select v-model.trim="product.discount_type" class="form-control" id="product_discount_type"
                                                            :disabled="allInputsDisabled"
                                                            :readonly="allInputsDisabled"
                                                            @change="changeDiscountType">
                                                        <option v-for="(name, key) in filteredDiscountTypes" :value="key">{{ name }}</option>
                                                    </select>

                                                <?php
                                                }else{
                                                    ?>
                                                    <select v-model.trim="product.discount_type" class="form-control" id="product_discount_type" :disabled="allInputsDisabled" :readonly="allInputsDisabled">
                                                        <?php foreach ($discount_types as $key => $name): ?>
                                                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                <?php
                                                }
                                                ?>
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
                                <?php
                                }else{
                                    ?>
                                    <div class="alert alert-warning" role="alert">
                                        Não é possível adicionar novos produtos na campanha, pois o prazo para adesão a campanha já passou.
                                    </div>
                                    <?php
                                }
                                ?>

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
                                                        <i class="fa fa-spinner fa-spin fa-fw" v-show="uploadingApprovementProducts"></i><span class="sr-only">Loading...</span>
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
                                            <th><?=lang('application_sku'); ?></th>
                                            <th><?=lang('application_name'); ?></th>
											<?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                            <th><?=lang('application_variation'); ?></th>
											<?php } ?>
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

                <button type="button" id="btnSave" class="btn btn-primary col-md-2" @click="submit()" :disabled="saving" v-if="!allInputsDisabled">
                    <i class="fa fa-spinner fa-spin fa-fw" v-show="saving"></i><span class="sr-only">Loading...</span>
					<?= $this->lang->line('application_save'); ?>
                </button>

                <a href="<?php echo base_url('campaigns_v2') ?>" class="btn btn-warning col-md-2"><?= $this->lang->line('application_back'); ?></a>

            </div>

        </div>

    </section>

</div>

<script type="text/javascript">

    $(document).ready(function () {

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
                {"data": "sku"},
                {"data": "name"},
                <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                {"data": "variant_info"},
                <?php } ?>
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
        template: '<table class="table table-striped table-hover responsive table-condensed lista-produtos-3.1" style="width: 100%;"></table>',
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
                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                    { title: '<?= $this->lang->line('application_variation'); ?>', 'class': 'col-md-2' },
                    <?php } ?>
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
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                        if (productSelected.sku == item.sku){
                            canInsert = false;
                        }
                        <?php }else{ ?>
                        if (productSelected.id == item.id){
                            canInsert = false;
                        }
                        <?php } ?>
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
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                        // Format variant information
                        let variantInfo = '';
                        if (item.has_variants && item.variant_name) {
                            let variantTypes = item.has_variants.split(';');
                            let variantValues = item.variant_name.split(';');

                            for (let i = 0; i < variantTypes.length && i < variantValues.length; i++) {
                                if (i > 0) variantInfo += '; ';
                                variantInfo += variantTypes[i] + ': ' + variantValues[i];
                            }
                        }
                        row.push(variantInfo);
                        <?php } ?>
                        row.push(item.price);
                        row.push(item.qty);
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                        row.push('<a class="btn btn-sm btn-default" onclick="addProduct(this, '+item.id+', '+item.maximum_share_sale_price+', '+item.another_discount_campaign+', '+item.another_comission_rebate_campaign+', '+item.another_marketplace_trading_campaign+', '+item.prd_variant_id+')"><i class="fa fa-plus"></i></a>');
                        <?php } else { ?>
                        row.push('<a class="btn btn-sm btn-default" onclick="addProduct(this, '+item.id+', '+item.maximum_share_sale_price+', '+item.another_discount_campaign+', '+item.another_comission_rebate_campaign+', '+item.another_marketplace_trading_campaign+')"><i class="fa fa-plus"></i></a>');
                        <?php } ?>

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
            },
        }
    });

    Vue.component('data-table-eligible', {
        template: '<table class="table table-striped table-hover responsive table-condensed lista-produtos-3" style="width: 100%;"></table>',
        props: ['productsearchresultelegible'],
        data() {
            return {
                headers: [
                    { title: 'ID', 'class': 'col-md-1' },
					<?php if ($only_admin && $usercomp == 1 && !$userstore){ ?>
                    { title: '<?= $this->lang->line('application_store'); ?>', 'class': 'col-md-2' },
					<?php } ?>
                    { title: '<?= $this->lang->line('application_sku'); ?>','class': 'col-md-2' },
                    { title: '<?= $this->lang->line('application_name'); ?>', 'class': 'col-md-4' },
                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                    { title: '<?= $this->lang->line('application_variation'); ?>', 'class': 'col-md-2' },
                    <?php } ?>
                    { title: '<?= $this->lang->line('application_price'); ?>', 'class': 'col-md-1' },
                    { title: '<?= $this->lang->line('application_qty'); ?>', 'class': 'col-md-1' },
                    { title: '<?= $this->lang->line('application_action'); ?>', 'class': 'col-md-1', 'sortable': false },
                ],
                rows: [] ,
                dtHandle: null
            }
        },
        watch: {
            productsearchresultelegible(val, oldVal) {
                this.showRowsElegible(val, oldVal);
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
            showRowsElegible(val, oldVal){
                let vm = this;
                vm.rows = [];
                // You should _probably_ check that this is changed data... but we'll skip that for this example.
                val.forEach(function (item) {

                    canInsert = true;
                    app.entry.add_elegible_products.forEach(function (productSelected){
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                            if (productSelected.sku == item.sku){
                                canInsert = false;
                            }
                        <?php }else{ ?>
                            if (productSelected.id == item.id){
                                canInsert = false;
                            }
                        <?php } ?>
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
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                        // Format variant information
                        let variantInfo = '';
                        if (item.has_variants && item.variant_name) {
                            let variantTypes = item.has_variants.split(';');
                            let variantValues = item.variant_name.split(';');

                            for (let i = 0; i < variantTypes.length && i < variantValues.length; i++) {
                                if (i > 0) variantInfo += '; ';
                                variantInfo += variantTypes[i] + ': ' + variantValues[i];
                            }
                        }
                        row.push(variantInfo);
                        <?php } ?>
                        row.push(item.price);
                        row.push(item.qty);
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                        row.push('<a class="btn btn-sm btn-default" onclick="addElegibleProduct(this, '+item.id+', '+item.maximum_share_sale_price+', '+item.another_discount_campaign+', '+item.another_comission_rebate_campaign+', '+item.another_marketplace_trading_campaign+', '+item.prd_variant_id+')"><i class="fa fa-plus"></i></a>');
                        <?php } else { ?>
                        row.push('<a class="btn btn-sm btn-default" onclick="addElegibleProduct(this, '+item.id+', '+item.maximum_share_sale_price+', '+item.another_discount_campaign+', '+item.another_comission_rebate_campaign+', '+item.another_marketplace_trading_campaign+')"><i class="fa fa-plus"></i></a>');
                        <?php } ?>

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
            },
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
            <?php
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            ?>
                vtexMarketplaces : <?php echo json_encode($vtex_marketplaces); ?>,
                occMarketplaces : <?php echo json_encode($occ_marketplaces); ?>,
            <?php
            }
            ?>
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
            <?php
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            ?>
                discountTypes: <?php echo json_encode($discount_types); ?>,
            <?php
            }
            ?>
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
            productsearchresultelegible: [],
            productsearchresult: [],
            productSearchQueryElegible: '',
            productSearchQuery: '',
            submitResponse: {},
            saving: false,
            uploadingStores: false,
            uploadingProducts: false,
            uploadingApprovementProducts: false,
            uploadingImportProductsSeller: false,
            elegible_products: {
                current_page: 1,
                per_page: 30,
                total_elegible_products: 0
            }
        },
        computed: {
            filteredProductsElegible: function () {
                let self = this
                let productSearchQueryElegible = self.productSearchQueryElegible.toLowerCase()
                return self.productsearchresultelegible.filter(function (product) {
                    return 	true
                })
            },
            filteredProducts: function () {
                let self = this
                let productSearchQuery = self.productSearchQuery.toLowerCase()
                return self.productsearchresult.filter(function (product) {
                    return 	true
                })
            },
            filteredTradePoliciesOptionsAvailable() {
                return this.tradePoliciesOptionsAvailable.filter(option =>
                    this.entry.marketplaces.includes(option.int_to)
                );
            },
            totalPages() {
                return Math.ceil(
                    this.elegible_products.total_elegible_products /
                    this.elegible_products.per_page
                );
            },
            pagesToShow() {
                const currentPage = this.elegible_products.current_page;
                const totalPages = this.totalPages;

                const maxPagesToShow = 9; // 1 atual + 4 anteriores + 4 próximas
                const half = Math.floor(maxPagesToShow / 2);

                let startPage = Math.max(currentPage - half, 1);
                let endPage = Math.min(currentPage + half, totalPages);

                // Ajustar para garantir sempre 9 páginas exibidas, se possível
                if (endPage - startPage + 1 < maxPagesToShow) {
                    if (startPage === 1) {
                        endPage = Math.min(startPage + maxPagesToShow - 1, totalPages);
                    } else if (endPage === totalPages) {
                        startPage = Math.max(endPage - maxPagesToShow + 1, 1);
                    }
                }

                // Gerar array de páginas a serem exibidas
                const pages = [];
                for (let i = startPage; i <= endPage; i++) {
                    pages.push(i);
                }

                return pages;
            },
            <?php
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            ?>
                filteredDiscountTypes() {
                    const marketplacesSelected = this.entry.marketplaces.some(marketplace =>
                        this.occMarketplaces.includes(marketplace)
                    );
                    const hasPaymentMethods = this.entry.paymentMethods.length > 0;

                    if (marketplacesSelected && hasPaymentMethods) {
                        if (this.entry.discount_type !== 'discount_percentage') {
                            this.entry.discount_type = 'discount_percentage';
                        }
                        // Retorna apenas as opções válidas
                        return { discount_percentage: this.discountTypes.discount_percentage };
                    }

                    return this.discountTypes; // Retorna todas as opções
                }
            <?php
            }
            ?>
        },
        mounted() {
			<?php
			if ($allow_insert_products){
			?>
            // Use the initial search functions that bypass the empty query check
            this.initialSearchProductElegible();
            this.initialSearchProduct();
			<?php
			}
			?>
            this.changeMarketplace(false);
            if (this.entry.id && this.entry.segment == '<?=CampaignSegment::PRODUCT;?>'){
                this.fetchEligibleProducts();
            }
        },
        <?php
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
        ?>
        watch: {
            'entry.marketplaces': function() {
                this.updateDiscountType();
            },
            'entry.paymentMethods': function() {
                this.updateDiscountType();
            }
        },
        <?php
        }
        ?>
        ready: function() {
        },
        created: function() {
            // Initialize monetary values for all products
            this.initializeMonetaryValues();
        },
        methods: {
            // Initialize monetary values for all products to prevent null values
            initializeMonetaryValues: function() {
                // Initialize monetary values in entry.add_elegible_products
                if (this.entry.add_elegible_products) {
                    this.entry.add_elegible_products.forEach(product => {
                        if (product.maximum_share_sale_price === null || product.maximum_share_sale_price === undefined) {
                            Vue.set(product, 'maximum_share_sale_price', '');
                        }
                        if (product.new_comission === null || product.new_comission === undefined) {
                            Vue.set(product, 'new_comission', '');
                        }
                        if (product.rebate_value === null || product.rebate_value === undefined) {
                            Vue.set(product, 'rebate_value', '');
                        }
                        if (product.fixed_discount === null || product.fixed_discount === undefined) {
                            Vue.set(product, 'fixed_discount', '');
                        }
                        if (product.seller_discount_fixed === null || product.seller_discount_fixed === undefined) {
                            Vue.set(product, 'seller_discount_fixed', '');
                        }
                        if (product.marketplace_discount_fixed === null || product.marketplace_discount_fixed === undefined) {
                            Vue.set(product, 'marketplace_discount_fixed', '');
                        }
                    });
                }

                // Initialize monetary values in entry.products
                if (this.entry.products) {
                    this.entry.products.forEach(product => {
                        if (product.maximum_share_sale_price === null || product.maximum_share_sale_price === undefined) {
                            Vue.set(product, 'maximum_share_sale_price', '');
                        }
                        if (product.new_comission === null || product.new_comission === undefined) {
                            Vue.set(product, 'new_comission', '');
                        }
                        if (product.rebate_value === null || product.rebate_value === undefined) {
                            Vue.set(product, 'rebate_value', '');
                        }
                        if (product.fixed_discount === null || product.fixed_discount === undefined) {
                            Vue.set(product, 'fixed_discount', '');
                        }
                        if (product.seller_discount_fixed === null || product.seller_discount_fixed === undefined) {
                            Vue.set(product, 'seller_discount_fixed', '');
                        }
                        if (product.marketplace_discount_fixed === null || product.marketplace_discount_fixed === undefined) {
                            Vue.set(product, 'marketplace_discount_fixed', '');
                        }
                    });
                }
            },
            <?php
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            ?>
            updateDiscountType() {
                const marketplacesSelected = this.entry.marketplaces.some(marketplace =>
                    this.occMarketplaces.includes(marketplace)
                );
                const hasPaymentMethods = this.entry.paymentMethods.length > 0;

                if (marketplacesSelected && hasPaymentMethods) {
                    this.entry.discount_type = 'discount_percentage';
                }
            },
            <?php
            }
            ?>
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
            submitNewElegibleProducts() {
                this.saving = true;
                let reqURL = base_url + 'campaigns_v2/add_new_elegible_products';
                let canContinue = true;
                let validationErrors = [];

                this.entry.add_elegible_products.forEach(function(productSelected) {
                    if (this.entry.campaign_type === 'shared_discount') {
                        if (productSelected.discount_type === 'discount_percentage') {
                            // Convertendo para números decimais antes da soma
                            const marketplaceDiscount = parseFloat(productSelected.marketplace_discount_percentual) || 0;
                            const sellerDiscount = parseFloat(productSelected.seller_discount_percentual) || 0;
                            const totalDiscount = marketplaceDiscount + sellerDiscount;
                            const expectedDiscount = parseFloat(productSelected.discount_percentage) || 0;

                            if (totalDiscount !== expectedDiscount) {
                                canContinue = false;
                                validationErrors.push(`O produto ${productSelected.id} possui soma de descontos percentuais (${totalDiscount}%) diferente do desconto total (${expectedDiscount}%)`);
                            }
                        }
                        else if (productSelected.discount_type === 'fixed_discount') {
                            // Convertendo para números decimais antes da soma
                            const marketplaceDiscount = parseFloat(productSelected.marketplace_discount_fixed) || 0;
                            const sellerDiscount = parseFloat(productSelected.seller_discount_fixed) || 0;
                            const totalDiscount = marketplaceDiscount + sellerDiscount;
                            const expectedDiscount = parseFloat(productSelected.fixed_discount) || 0;

                            if (totalDiscount !== expectedDiscount) {
                                canContinue = false;
                                validationErrors.push(`O produto ${productSelected.id} possui soma de descontos fixos (R$ ${totalDiscount.toFixed(2)}) diferente do desconto total (R$ ${expectedDiscount.toFixed(2)})`);
                            }
                        }
                    }
                }.bind(this));

                if (!canContinue) {
                    this.saving = false;

                    const errorList = validationErrors.map(error => `<li>${error}</li>`).join('');
                    const errorHtml = `<ul style="text-align: left; margin-top: 10px;">${errorList}</ul>`;

                    Swal.fire({
                        title: 'Erro de Validação',
                        html: `<div>
                    <p>Foram encontrados os seguintes problemas:</p>
                    ${errorHtml}
                   </div>`,
                        icon: 'error',
                        confirmButtonText: 'Entendi',
                        confirmButtonColor: '#3085d6',
                        customClass: {
                            container: 'custom-swal-container',
                            popup: 'custom-swal-popup',
                        }
                    });

                    return false;
                }

                if (canContinue) {
                    this.$http.post(reqURL, this.entry).then(response => {
                        if (response.body.redirect) {
                            window.location.href = response.body.redirect;
                        } else {
                            this.saving = false;
                            this.submitResponse = response.body;
                            window.scrollTo(0, 0);
                        }
                    }, response => {
                        this.saving = false;
                        Swal.fire({
                            title: 'Erro',
                            text: 'Ocorreu um erro inesperado ao salvar os produtos da campanha.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3085d6'
                        });
                    });
                }
            },
            fetchEligibleProducts() {
                let reqURL = base_url + 'campaigns_v2/elegible_products/'+this.entry.id;

                this.$http.get(reqURL, {
                    params: {
                        page: this.elegible_products.current_page, // Página atual
                        limit: this.elegible_products.per_page, // Limite por página
                    },
                }).then(response => {
                    let responseJson = JSON.parse(response.data);
                    this.entry.elegible_products = responseJson.products; // Produtos da página
                    this.elegible_products.total_elegible_products = responseJson.total; // Total de produtos
                }, error => {
                    console.error(error);
                    alert('Ocorreu um erro ao buscar os produtos elegíveis');
                });

            },

            // Alterar página
            changePage(page) {
                this.elegible_products.current_page = page;
                this.fetchEligibleProducts();
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
                    this.entry.elegible_products = [];
                    $('#categories').selectpicker('deselectAll');
                }else if (this.entry.segment == '<?=CampaignSegment::CATEGORY?>'){
                    this.entry.stores = [];
                    this.entry.elegible_products = [];
                    $('#stores').selectpicker('deselectAll');
                }else{
                    this.entry.categories = [];
                    this.entry.stores = [];
                    $('#categories').selectpicker('deselectAll');
                }

            },
            changeDiscountType ({ type, target }) {
                if (target.value == 'discount_percentage'){
                    this.entry.fixed_discount = null;
                }else{
                    this.entry.discount_percentage = null;
                }
            },
            lastSearchQueryElegible: '',
            // Function to be called on initial load - bypasses empty query check
            initialSearchProduct: function() {
                let reqURL = base_url + 'campaigns_v2/searchProducts?searchString='+this.productSearchQueryElegible;

                if (this.entry.id){
                    reqURL += '&campaign_id=' + this.entry.id;
                }

                let entry = JSON.parse(JSON.stringify(this.entry))

                entry.categories = [];

                this.productsearchresultelegible = [];

                this.$http.post(reqURL, JSON.stringify(entry)).then(response => {
                    this.productsearchresultelegible = response.body;
                    // Store the current search query as the last one
                    this.lastSearchQueryElegible = this.productSearchQueryElegible;
                }, response => {
                    alert('Ocorreu um erro ao buscar os produtos');
                });
            },
            submitSearchProduct: function() {
                // Only search if the query has changed
                if (this.productSearchQueryElegible === '' || this.productSearchQueryElegible === this.lastSearchQueryElegible) {
                    return;
                }

                let reqURL = base_url + 'campaigns_v2/searchProducts?searchString='+this.productSearchQueryElegible;

                if (this.entry.id){
                    reqURL += '&campaign_id=' + this.entry.id;
                }

                let entry = JSON.parse(JSON.stringify(this.entry))

                entry.categories = [];

                this.productsearchresultelegible = [];

                this.$http.post(reqURL, JSON.stringify(entry)).then(response => {
                    this.productsearchresultelegible = response.body;
                    // Store the current search query as the last one
                    this.lastSearchQueryElegible = this.productSearchQueryElegible;
                }, response => {
                    alert('Ocorreu um erro ao buscar os produtos');
                });

            },
            lastSearchQuery: '',
            // Function to be called on initial load - bypasses empty query check
            initialSearchProductElegible: function() {
                let reqURL = base_url + 'campaigns_v2/searchProductsElegible?searchString='+this.productSearchQuery;

                this.$http.post(reqURL, JSON.stringify(this.entry)).then(response => {

                    if (response.body.products) {
                        this.productsearchresult = response.body.products;
                    } else {
                        this.productsearchresult = [];
                    }

                    // Store the current search query as the last one
                    this.lastSearchQuery = this.productSearchQuery;

                }, response => {
                    alert('Ocorreu um erro ao buscar os produtos');
                });
            },
            submitSearchProductElegible: function() {
                // Only search if the query has changed
                if (this.productSearchQuery === '' || this.productSearchQuery === this.lastSearchQuery) {
                    return;
                }

                let reqURL = base_url + 'campaigns_v2/searchProductsElegible?searchString='+this.productSearchQuery;

                this.$http.post(reqURL, JSON.stringify(this.entry)).then(response => {

                    if (response.body.products) {
                        this.productsearchresult = response.body.products;
                    } else {
                        this.productsearchresult = [];
                    }

                    // Store the current search query as the last one
                    this.lastSearchQuery = this.productSearchQuery;

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

                    let tradePolicies = 'null';
                    if (this.entry.tradePolicies && this.entry.tradePolicies.length > 0){
                        tradePolicies = this.entry.tradePolicies.join('|');
                    }

                    let paymentMethods = 'null';
                    if (this.entry.paymentMethods && this.entry.paymentMethods.length > 0){
                        paymentMethods = this.entry.paymentMethods.join('|');
                    }

                    let arrayProducts = [];
                    for (let i = 0; i < this.productsearchresult.length; i++) {
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>

                            let productToSend = {};
                            productToSend.id = this.productsearchresult[i].id;
                            productToSend.prd_variant_id = this.productsearchresult[i].prd_variant_id;

                            arrayProducts.push(productToSend);

                        <?php }else{ ?>
                            arrayProducts.push(this.productsearchresult[i].id);
                        <?php } ?>
                    }

                    let anotherDiscountCampaign = [];
                    let anotherDiscountCampaignB2w = [];

                    let reqURL = base_url + 'campaigns_v2/arrayProductIsAnotherCampaign/'+marketplacesIntTo+'/'+tradePolicies+'/'+paymentMethods+'/'+this.entry.id;
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

                                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                    if (productSelected.id == product.id && productSelected.prd_variant_id == product.prd_variant_id){
                                        inserted = true;
                                    }
                                    <?php } else { ?>
                                    if (productSelected.id == product.id){
                                        inserted = true;
                                    }
                                    <?php } ?>


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
                if (this.entry.id){
                    this.entry.products.splice(index, 1);
                    this.submitSearchProductElegible();
                }else{
                    this.entry.add_elegible_products.splice(index, 1);
                    this.submitSearchProduct();
                }
            },
            addElegibleProduct: async function (productId, maximum_share_sale_price, anotherDiscountCampaign, anotherComissionReductionRebate, another_marketplace_trading_campaign, prd_variant_id) {

                let marketplacesIntTo = '';
                if (this.entry.marketplaces){
                    marketplacesIntTo = this.entry.marketplaces.join('|');
                }

                let tradePolicies = 'null';
                if (this.entry.tradePolicies && this.entry.tradePolicies.length > 0){
                    tradePolicies = this.entry.tradePolicies.join('|');
                }

                let paymentMethods = 'null';
                if (this.entry.paymentMethods && this.entry.paymentMethods.length > 0){
                    paymentMethods = this.entry.paymentMethods.join('|');
                }

                let showConfirm;
                let indexFound = null;

                //Só podemos usar a validação abaixo caso a campanha atual for do tipo de desconto
                if (this.entry.campaign_type != '<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>' && this.entry.campaign_type != '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'){

                    let reqURL = base_url + 'campaigns_v2/productIsAnotherCampaign/'+productId+'/'+marketplacesIntTo+'/'+tradePolicies+'/'+paymentMethods+'/'+this.entry.id;
                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                    // If feature flag is enabled and product has variant ID, add it to the URL
                    for (let i = 0; i < this.productsearchresultelegible.length; i++) {
                        let product = this.productsearchresultelegible[i];
                        if (product.id == productId && product.prd_variant_id) {
                            reqURL += '/' + product.prd_variant_id;
                            break;
                        }
                    }
                    <?php } ?>
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

                        //Usado durante a importação de planilha || Mantendo assim para garantir compatibilidade e evitar bugs
                        let productsSearch = this.productsearchresult;

                        //Usado durante a busca de produtos || Mantendo assim para garantir compatibilidade e evitar bugs
                        if (this.productsearchresultelegible.length > 0){
                            productsSearch = this.productsearchresultelegible;
                        }

                        for (let i = 0; i < productsSearch.length; i++) {

                            let product = productsSearch[i];

                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                if (product.id == productId && prd_variant_id == product.prd_variant_id){
                            <?php } else { ?>
                                if (product.id == productId){
                            <?php } ?>


                                indexFound = i;

                                var inserted = false;

                                this.entry.add_elegible_products.forEach(function (productSelected){

                                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                        if (productSelected.id == productId && prd_variant_id == productSelected.prd_variant_id){
                                            inserted = true;
                                        }
                                    <?php } else { ?>
                                        if (productSelected.id == productId){
                                            inserted = true;
                                        }
                                    <?php } ?>

                                });

                                if (!inserted){

                                    Vue.set(product, 'maximum_share_sale_price', maximum_share_sale_price || '')

                                    // Ensure all monetary values are initialized with empty string instead of null
                                    if (product.new_comission === null || product.new_comission === undefined) {
                                        Vue.set(product, 'new_comission', '');
                                    }
                                    if (product.rebate_value === null || product.rebate_value === undefined) {
                                        Vue.set(product, 'rebate_value', '');
                                    }
                                    if (product.fixed_discount === null || product.fixed_discount === undefined) {
                                        Vue.set(product, 'fixed_discount', '');
                                    }
                                    if (product.seller_discount_fixed === null || product.seller_discount_fixed === undefined) {
                                        Vue.set(product, 'seller_discount_fixed', '');
                                    }
                                    if (product.marketplace_discount_fixed === null || product.marketplace_discount_fixed === undefined) {
                                        Vue.set(product, 'marketplace_discount_fixed', '');
                                    }

                                    this.entry.add_elegible_products.push(product);

                                }
                            }

                        }

                    }

                }


            },
            addProduct: async function (productId, maximum_share_sale_price, anotherDiscountCampaign, anotherComissionReductionRebate, another_marketplace_trading_campaign, prd_variant_id) {

                let marketplacesIntTo = '';
                if (this.entry.marketplaces){
                    marketplacesIntTo = this.entry.marketplaces.join('|');
                }

                let tradePolicies = 'null';
                if (this.entry.tradePolicies && this.entry.tradePolicies.length > 0){
                    tradePolicies = this.entry.tradePolicies.join('|');
                }

                let paymentMethods = 'null';
                if (this.entry.paymentMethods && this.entry.paymentMethods.length > 0){
                    paymentMethods = this.entry.paymentMethods.join('|');
                }

                let showConfirm;
                let indexFound = null;

                //Só podemos usar a validação abaixo caso a campanha atual for do tipo de desconto
                if (this.entry.campaign_type != '<?=CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE;?>' && this.entry.campaign_type != '<?=CampaignTypeEnum::MARKETPLACE_TRADING;?>'){

                    let reqURL = base_url + 'campaigns_v2/productIsAnotherCampaign/'+productId+'/'+marketplacesIntTo+'/'+tradePolicies+'/'+paymentMethods+'/'+this.entry.id;
                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                    // If feature flag is enabled and product has variant ID, add it to the URL
                    for (let i = 0; i < this.productsearchresult.length; i++) {
                        let product = this.productsearchresult[i];
                        if (product.id == productId && product.prd_variant_id == prd_variant_id) {
                            reqURL += '/' + product.prd_variant_id;
                            break;
                        }
                    }
                    <?php } ?>
                    await this.$http.get(reqURL).then(response => {
                        anotherDiscountCampaign = response.body.exist ?? false;
                        anotherDiscountCampaignB2w = response.body.b2w_exist ?? false;
                    }, error => {
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

                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                if (product.id == productId && prd_variant_id == product.prd_variant_id){
                            <?php } else { ?>
                                if (product.id == productId){
                            <?php } ?>

                                indexFound = i;

                                var inserted = false;

                                this.entry.products.forEach(function (productSelected){
                                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                                        if (productSelected.id == productId && productSelected.prd_variant_id == prd_variant_id){
                                            inserted = true;
                                        }
                                    <?php }else{ ?>
                                        if (productSelected.id == productId){
                                            inserted = true;
                                        }
                                    <?php } ?>
                                });

                                if (!inserted){

                                    Vue.set(product, 'maximum_share_sale_price', maximum_share_sale_price || '')

                                    // Ensure all monetary values are initialized with empty string instead of null
                                    if (product.new_comission === null || product.new_comission === undefined) {
                                        Vue.set(product, 'new_comission', '');
                                    }
                                    if (product.rebate_value === null || product.rebate_value === undefined) {
                                        Vue.set(product, 'rebate_value', '');
                                    }
                                    if (product.fixed_discount === null || product.fixed_discount === undefined) {
                                        Vue.set(product, 'fixed_discount', '');
                                    }
                                    if (product.seller_discount_fixed === null || product.seller_discount_fixed === undefined) {
                                        Vue.set(product, 'seller_discount_fixed', '');
                                    }
                                    if (product.marketplace_discount_fixed === null || product.marketplace_discount_fixed === undefined) {
                                        Vue.set(product, 'marketplace_discount_fixed', '');
                                    }

                                    this.entry.products.push(product);

                                }
                            }

                        }

                    }

                }


            },
            async sendCsvStore() {

                if (this.$refs.filesStore.files.length == 0) {
                    return;
                }

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
                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                            app.addElegibleProduct(product.id, product.maximum_share_sale_price, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign, product.prd_variant_id);
                            <?php } else { ?>
                            app.addElegibleProduct(product.id, product.maximum_share_sale_price, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign);
                            <?php } ?>
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

                if (this.$refs.filesProduct.files.length == 0) {
                    return;
                }

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
                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){ ?>
                            app.addElegibleProduct(product.id, product.maximum_share_sale_price, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign, product.prd_variant_id);
                            <?php } else { ?>
                            app.addElegibleProduct(product.id, product.maximum_share_sale_price, product.another_discount_campaign, product.another_comission_rebate_campaign, product.another_marketplace_trading_campaign);
                            <?php } ?>
                        });

                    }

                    $('#fileProduct').val('');

                    this.uploadingProducts = false;

                }
            }
        }
    });

    function addElegibleProduct(obj, productId, maximum_share_sale_price, another_discount_campaign, another_comission_rebate_campaign, another_marketplace_trading_campaign, prd_variant_id){
        app.addElegibleProduct(productId, maximum_share_sale_price, another_discount_campaign, another_comission_rebate_campaign, another_marketplace_trading_campaign, prd_variant_id);
    }

    function addProduct(obj, productId, maximum_share_sale_price, another_discount_campaign, another_comission_rebate_campaign, another_marketplace_trading_campaign, prd_variant_id){
        app.addProduct(productId, maximum_share_sale_price, another_discount_campaign, another_comission_rebate_campaign, another_marketplace_trading_campaign, prd_variant_id);
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
