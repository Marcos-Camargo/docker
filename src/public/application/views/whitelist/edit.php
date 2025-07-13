<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php 
		$data['pageinfo'] = "application_manage";  
		$this->load->view('templates/content_header',$data); 
	?>

	<!-- Main content -->
	<section class="content">
		<!-- Small boxes (Stat box) -->
		<div class="row">
			<div class="col-md-12 col-xs-12">
				<div id="messages"></div>
				<?php if($this->session->flashdata('success')): ?>
				<div class="alert alert-success alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<?php echo $this->session->flashdata('success'); ?>
				</div>
				<?php elseif($this->session->flashdata('error')): ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<?php echo $this->session->flashdata('error'); ?>
				</div>
				<?php endif; ?>
				<div class="box">
					<form role="form" action="<?php base_url('Whitelist/'.(is_null($wordData['id'])) ? 'create' : 'update'); ?>" method="post">
						<input type="hidden" id="created_by" name="created_by" value='<?= $wordData['created_by'];?>' >
						<div class="box-body">
							<div class="row">
								<div class="form-group col-md-12 d-flex">
									<label for="product_id" class="col-md-4"><?= $this->lang->line('application_by_product_id') ?></label>&nbsp
									<input type="number" class="form-control col-md-8" id="product_id" name="product_id" min=1 value="<?php echo set_value('product_id', $wordData['product_id']);?>" placeholder="<?= $this->lang->line('application_enter_the_product_id') ?>" autocomplete="off">
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="word" class="col-md-4"><?= $this->lang->line('application_forbidden_word') ?></label>&nbsp
									<input type="text" class="form-control col-md-6" id="word" name="word" value="<?php echo set_value('words', $wordData['words']);?>" placeholder="<?= $this->lang->line('application_forbidden_word_here') ?>" autocomplete="off">
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="sku" class="col-md-4"><?= $this->lang->line('application_by_product_sku') ?></label>&nbsp
									<input type="text" class="form-control col-md-8" id="sku" name="sku" value="<?php echo set_value('product_sku', $wordData['product_sku']);?>" placeholder="<?= $this->lang->line('application_enter_the_product_sku') ?>" autocomplete="off">
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="store" class="col-md-4"><?= $this->lang->line('application_by_store') ?></label>&nbsp
									<select class="form-control selectpicker show-tick" data-live-search="true" id="store" name="store" multiple data-max-options="1" title="<?= $this->lang->line('application_select'); ?>">
									<?php foreach ($stores as $key => $store) { ?>
										<option value="<?= $store['id'] ?>" <?=set_select('store', $store['id'], $wordData['store_id'] == $store['id'])?>><?= $store['name'] ?></option>
									<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="phase" class="col-md-4"><?= $this->lang->line('application_by_phase') ?></label>&nbsp
									<select class="form-control selectpicker show-tick" data-live-search="true" id="phase" name="phase" multiple data-max-options="1" title="<?= $this->lang->line('application_select'); ?>">
									<?php foreach ($phases as $key => $phase) { ?>
										<option value="<?= $phase['id'] ?>" <?=set_select('phase', $phase['id'], $wordData['phase_id'] == $phase['id'])?>><?= $phase['name'] ?></option>
									<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="category" class="col-md-4"><?= $this->lang->line('application_by_category') ?></label>&nbsp
									<select class="form-control selectpicker show-tick" data-live-search="true" id="category" name="category" multiple data-max-options="1" title="<?= $this->lang->line('application_select'); ?>">
									<?php foreach ($categories as $key => $category) { ?>
										<option value="<?= $category['id'] ?>" <?=set_select('category', $category['id'], $wordData['category_id'] == $category['id'])?>><?= $category['name'] ?></option>
									<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="marketplace" class="col-md-4"><?= $this->lang->line('application_by_marketplace') ?></label>&nbsp
									<select class="form-control selectpicker show-tick" data-live-search="true" id="marketplace" name="marketplace" multiple data-max-options="1" title="<?= $this->lang->line('application_select'); ?>">
									<?php foreach ($nameOfIntegrations as $key => $nameOfIntegration) { ?>
										<option value="<?= $key ?>" <?=set_select('marketplace', $key, $wordData['marketplace'] == $key)?>><?= $nameOfIntegration ?></option>
									<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="brand" class="col-md-4"><?= $this->lang->line('application_by_brand') ?></label>&nbsp
									<select class="form-control selectpicker show-tick" data-live-search="true"  id="brand" name="brand" multiple data-max-options="1" title="<?= $this->lang->line('application_select'); ?>">
									<?php foreach ($brands as $key => $brand) { ?>
										<option value="<?= $brand['id'] ?>" <?=set_select('brand', $brand['id'], $wordData['brand_id'] == $brand['id'])?>><?= $brand['name'] ?></option>
									<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="commission" class="col-md-4"><?= $this->lang->line('application_by_commission') ?></label>
                                    <div class="col-md-12 d-flex no-padding">
									<select name="operator_commission" id="operator_commission" class="form-control col-md-6">
                                            <option value="e" <?=set_select('operator_commission', 'e', $wordData['operator_commission'] == '=')?>><?= $this->lang->line('application_equal_to') ?></option>
                                            <option value="gt" <?=set_select('operator_commission', 'gt', $wordData['operator_commission'] == '>')?>><?= $this->lang->line('application_greater_than') ?></option>
                                            <option value="gte" <?=set_select('operator_commission', 'gte', $wordData['operator_commission'] == '>=')?>><?= $this->lang->line('application_greater_or_equal_than') ?></option>
                                            <option value="lt" <?=set_select('operator_commission', 'lt', $wordData['operator_commission'] == '<')?>><?= $this->lang->line('application_less_than') ?></option>
                                            <option value="lte" <?=set_select('operator_commission', 'lte', $wordData['operator_commission'] == '<=')?>><?= $this->lang->line('application_less_or_equal_than') ?></option>
                                            <option value="ne" <?=set_select('operator_commission', 'ne', $wordData['operator_commission'] == '!=')?>><?= $this->lang->line('application_different_from') ?></option>
                                        </select>
                                        <input type="number" class="form-control col-md-6" id="commission" name="commission" min=1 value="<?php echo set_value('commission', $wordData['commission']);?>" placeholder="<?= $this->lang->line('application_enter_the_commission') ?>" autocomplete="off">
                                    </div>
                                </div>
								<div class="form-group col-md-12 d-flex">
									<label for="seller_index" class="col-md-4"><?= $this->lang->line('application_by_seller_index') ?></label>
                                    <div class="col-md-12 d-flex no-padding">
                                        <select name="operator_seller_index" id="operator_seller_index" class="form-control col-md-6">
                                            <option value="e" <?=set_select('operator_seller_index', 'e', $wordData['operator_seller_index'] == '=')?>><?= $this->lang->line('application_equal_to') ?></option>
                                            <option value="gt" <?=set_select('operator_seller_index', 'gt', $wordData['operator_seller_index'] == '>')?>><?= $this->lang->line('application_greater_than') ?></option>
                                            <option value="gte" <?=set_select('operator_seller_index', 'gte', $wordData['operator_seller_index'] == '>=')?>><?= $this->lang->line('application_greater_or_equal_than') ?></option>
                                            <option value="lt" <?=set_select('operator_seller_index', 'lt', $wordData['operator_seller_index'] == '<')?>><?= $this->lang->line('application_less_than') ?></option>
                                            <option value="lte" <?=set_select('operator_seller_index', 'lte', $wordData['operator_seller_index'] == '<=')?>><?= $this->lang->line('application_less_or_equal_than') ?></option>
                                            <option value="ne" <?=set_select('operator_seller_index', 'ne', $wordData['operator_seller_index'] == '!=')?>><?= $this->lang->line('application_different_from') ?></option>
                                        </select> 
                                        <input type="number" class="form-control col-md-6" id="seller_index" name="seller_index" min=1 value="<?php echo set_value('seller_index', $wordData['seller_index']);?>" placeholder="<?= $this->lang->line('application_enter_the_seller_index') ?>" autocomplete="off">
                                    </div>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="apply_to" class="col-md-4"><?= $this->lang->line('application_apply_rule_to'); ?></label>&nbsp
									<select name="apply_to" id="apply_to" class="form-control col-md-8">
										<option value="1" <?=set_select('status', '1', $wordData['apply_to'] == '1')?>><?= $this->lang->line('application_for_all_products'); ?></option>
										<option value="2" <?=set_select('status', '2', $wordData['apply_to'] == '2')?>><?= $this->lang->line('application_new_and_unpublished_products'); ?></option>
									</select>
								</div>
								<div class="form-group col-md-12 d-flex">
									<label for="status" class="col-md-4"><?= $this->lang->line('application_status'); ?></label>&nbsp
									<select name="status" id="status" class="form-control col-md-8">
										<option value="1" <?=set_select('status', '1', $wordData['status'] == '1')?>><?= $this->lang->line('application_active'); ?></option>
										<option value="2" <?=set_select('status', '2', $wordData['status'] == '2')?>><?= $this->lang->line('application_inactive'); ?></option>
									</select>
								</div>
							</div>
						</div>
                        <div class="box-footer">
                            <div class="row">
                                <div class="col-md-12 d-flex justify-content-between">
                                    <a href="<?php echo base_url('Whitelist/') ?>" class="btn btn-warning col-md-3"><?= $this->lang->line('application_back') ?></a>
                                    <button type="submit" class="btn btn-primary col-md-3"><?= $this->lang->line('application_save') ?></button>
                                </div>
                            </div>
                        </div>
					</form>
				</div>
			</div>
		</div>
  	</section>
</div>
<script>
    $(function (){
    })
</script>