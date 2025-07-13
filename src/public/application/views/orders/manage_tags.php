<section class="content-wrapper">

    <?php

    ini_set("auto_detect_line_endings", true);    // Treat EOL from all architectures
    $data['pageinfo'] = "";  $this->load->view('templates/content_header',$data);

    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
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
            </div>
            <div class="col-md-12 col-xs-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs <?=$typeViewTags == 'all'? 'd-flex' : 'd-none'?> justify-content-between row flex-wrap content-type-shipping-company">
                        <?php if (in_array($typeViewTags, array('all', 'correios'))): ?>
                        <li class="active">
                            <a href="#sgpweb" data-toggle="tab" aria-expanded="true">
                                <img src="<?=base_url('/assets/files/integrations/sgpweb/sgpweb.png')?>" width="100" alt="SGPWeb">
                            </a>
                        </li>
                        <li>
                            <a href="#correios" data-toggle="tab" aria-expanded="false">
                                <img src="<?=base_url('/assets/files/integrations/correios/correios.png')?>" width="100" alt="Correios">
                            </a>
                        </li>
                        <?php endif ?>
                        <?php if (in_array($typeViewTags, array('all', 'shipping_company_gateway'))): ?>
                        <li class="<?=$typeViewTags == 'all'? '' : 'active'?>">
                            <a href="#shipping_company" data-toggle="tab" aria-expanded="false">
                                <i class="fa fa-truck"></i>&nbsp; TRANSPORTADORAS / Gateway Logístico
                            </a>
                        </li>
                        <?php endif ?>
                    </ul>
                </div>
                <div class="tab-content">
                    <?php if (in_array($typeViewTags, array('all', 'correios'))): ?>
                    <div class="tab-pane active" id="sgpweb">
                        <div class="col-md-12 col-xs-12 no-padding">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="active"><a href="#sgpweb_without_tag" data-toggle="tab"><?=$this->lang->line('messages_unlabeled_order')?></a></li>
                                    <li><a href="#sgpweb_with_tag" data-toggle="tab"><?=$this->lang->line('messages_generated_tags')?></a></li>
                                </ul>
                                <div class="tab-content col-md-12">
                                    <div class="tab-pane active" id="sgpweb_without_tag">
                                        <div class="col-md-12 col-xs-12 no-padding">
                                            <div class="callout callout-warning">
                                                <h4><?=$this->lang->line('application_warning')?>!</h4>
                                                <p><?=sprintf(lang('messages_warning_product_price_minimum'), 'R$ 24,50')?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-xs-12 box_etiquetas_sgpweb">
                                            <table id="etiquetas_sgpweb" class="table table-bordered mt-4 table-responsive">
                                                <thead>
                                                    <th>#</th>
                                                    <th><?=$this->lang->line('application_clients')?></th>
                                                    <th><?=$this->lang->line('application_products')?></th>
                                                    <th><?=$this->lang->line('application_date')?></th>
                                                    <th><?=$this->lang->line('application_value')?></th>
                                                    <?=(in_array('doIntegration', $user_permission)) ? "<th>{$this->lang->line('application_store')}</th>" : ''?>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                                <tfoot>
                                                    <th>#</th>
                                                    <th><?=$this->lang->line('application_clients')?></th>
                                                    <th><?=$this->lang->line('application_products')?></th>
                                                    <th><?=$this->lang->line('application_date')?></th>
                                                    <th><?=$this->lang->line('application_value')?></th>
                                                    <?=(in_array('doIntegration', $user_permission)) ? "<th>{$this->lang->line('application_store')}</th>" : ''?>
                                                </tfoot>
                                            </table>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-primary col-md-5" id="gerar_plp_sgpweb" disabled><?=$this->lang->line('application_generate_tags')?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="sgpweb_with_tag">
                                        <div class="callout callout-warning">
                                            <h4><?=$this->lang->line('application_warning')?>!</h4>
                                            <p><?=$this->lang->line('messages_warning_expired_plps')?></p>
                                        </div>

                                        <table id="etiquetas_geradas_sgpweb" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                            <thead>
                                            <tr>
                                                <th><?=$this->lang->line('application_generated_plp');?></th>
                                                <th><?=$this->lang->line('application_orders_placed_plp');?></th>
                                                <th><?=$this->lang->line('application_expiration_date');?></th>
                                                <th><?=$this->lang->line('application_action');?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <th><?=$this->lang->line('application_generated_plp');?></th>
                                                <th><?=$this->lang->line('application_orders_placed_plp');?></th>
                                                <th><?=$this->lang->line('application_expiration_date');?></th>
                                                <th><?=$this->lang->line('application_action');?></th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="correios">
                        <div class="col-md-12 col-xs-12 no-padding">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="active"><a href="#correios_without_tag" data-toggle="tab"><?=$this->lang->line('messages_unlabeled_order')?></a></li>
                                    <li><a href="#correios_with_tag" data-toggle="tab"><?=$this->lang->line('messages_generated_tags')?></a></li>
                                </ul>
                                <div class="tab-content col-md-12">
                                    <div class="tab-pane active" id="correios_without_tag">
                                        <div class="col-md-12 col-xs-12 no-padding">
                                            <div class="callout callout-warning">
                                                <h4><?=$this->lang->line('application_warning')?>!</h4>
                                                <p><?=sprintf(lang('messages_warning_product_price_minimum'), 'R$ 24,50')?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-xs-12 box_etiquetas_correios">
                                            <table id="etiquetas_correios" class="table table-bordered mt-4 table-responsive">
                                                <thead>
                                                <th>#</th>
                                                <th><?=$this->lang->line('application_clients')?></th>
                                                <th><?=$this->lang->line('application_products')?></th>
                                                <th><?=$this->lang->line('application_date')?></th>
                                                <th><?=$this->lang->line('application_value')?></th>
                                                <?=(in_array('doIntegration', $user_permission)) ? "<th>{$this->lang->line('application_store')}</th>" : ''?>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                                <tfoot>
                                                <th>#</th>
                                                <th><?=$this->lang->line('application_clients')?></th>
                                                <th><?=$this->lang->line('application_products')?></th>
                                                <th><?=$this->lang->line('application_date')?></th>
                                                <th><?=$this->lang->line('application_value')?></th>
                                                <?=(in_array('doIntegration', $user_permission)) ? "<th>{$this->lang->line('application_store')}</th>" : ''?>
                                                </tfoot>
                                            </table>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-primary col-md-5" id="gerar_plp_correios" disabled><?=$this->lang->line('application_generate_tags')?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="correios_with_tag">
                                        <div class="callout callout-warning">
                                            <h4><?=$this->lang->line('application_warning')?>!</h4>
                                            <p><?=$this->lang->line('messages_warning_expired_label')?></p>
                                        </div>

                                        <table id="etiquetas_geradas_correios" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                            <thead>
                                            <tr>
                                                <th><?=$this->lang->line('application_orders');?></th>
                                                <th><?=$this->lang->line('application_action');?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <th><?=$this->lang->line('application_orders');?></th>
                                                <th><?=$this->lang->line('application_action');?></th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif ?>
                    <?php if (in_array($typeViewTags, array('all', 'shipping_company_gateway'))): ?>
                    <div class="tab-pane <?=$typeViewTags == 'shipping_company_gateway'? 'active' : ''?>" id="shipping_company">
                        <div class="col-md-12 col-xs-12 no-padding">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="active"><a href="#shipping_company_without_tag" data-toggle="tab"><?=$this->lang->line('messages_request_label_from_carrier')?></a></li>
                                    <li><a href="#shipping_company_with_tag" data-toggle="tab"><?=$this->lang->line('messages_issue_group_labels')?></a></li>
                                    <li><a href="#shipping_company_with_tag_queue" data-toggle="tab"><?=$this->lang->line('messages_generated_tags')?></a></li>
                                </ul>
                                <div class="tab-content col-md-12">
                                    <div class="tab-pane active" id="shipping_company_without_tag">
                                        <div class="row">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <?php if (count($stores_filter) > 1): ?>
                                                <div class="form-group col-md-4">
                                                    <label for="storesShippingCompanyWithoutLabels" class="normal"><?= $this->lang->line('application_search_for_store') ?></label>
                                                    <select class="form-control selectpicker show-tick" id="storesShippingCompanyWithoutLabels" name="storesShippingCompanyWithoutLabels" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                                        <?php foreach ($stores_filter as $store_filter) { ?>
                                                            <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <?php endif ?>
                                                <div class="form-group col-md-3">
                                                    <label>&nbsp;</label>
                                                    <button class="btn btn-primary col-md-12" id="requestGenerateTag"><i class="fas fa-check"></i> <?=$this->lang->line('application_request_tag_generation')?></button>
                                                </div>
                                            </div>
                                        </div>

                                        <table id="shippingCompanyWithoutTag" class="table table-striped table-hover display table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?=$this->lang->line('application_order')?></th>
                                                <th><?=$this->lang->line('application_clients')?></th>
                                                <th><?=$this->lang->line('application_order_date')?></th>
                                                <th><?=$this->lang->line('application_dispatch_until')?></th>
                                                <th><?=$this->lang->line('application_store')?></th>
                                                <th><?=$this->lang->line('application_nfe_num')?></th>
                                            </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                            <tr>
                                                <th><?=$this->lang->line('application_order')?></th>
                                                <th><?=$this->lang->line('application_clients')?></th>
                                                <th><?=$this->lang->line('application_order_date')?></th>
                                                <th><?=$this->lang->line('application_dispatch_until')?></th>
                                                <th><?=$this->lang->line('application_store')?></th>
                                                <th><?=$this->lang->line('application_nfe_num')?></th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="tab-pane" id="shipping_company_with_tag">
                                        <div class="row mt-3">
                                            <div class="form-group col-md-12 no-margin">
                                                <div class="alert alert-success alert-dismissible no-margin" role="alert">
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                    <?=$this->lang->line('messages_to_view_pdf_file_select_generated_Labels')?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <?php if (count($stores_filter) > 1): ?>
                                                    <div class="form-group col-md-3">
                                                        <label for="storesShippingCompanyWithLabels" class="normal"><?= $this->lang->line('application_search_for_store') ?></label>
                                                        <select class="form-control selectpicker show-tick" id="storesShippingCompanyWithLabels" name="storesShippingCompanyWithLabels" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                                            <?php foreach ($stores_filter as $store_filter) { ?>
                                                                <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                <?php endif ?>
                                                <?php if (count($ship_company_filter) > 1): ?>
                                                    <div class="form-group col-md-3">
                                                        <label for="shippingCompanyFilterWithLabels" class="normal"><?= $this->lang->line('application_shipping_company') ?></label>
                                                        <select class="form-control selectpicker show-tick" id="shippingCompanyFilterWithLabels" name="shippingCompanyFilterWithLabels" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                                            <?php foreach ($ship_company_filter as $ship_company) { ?>
                                                                <option value="<?= $ship_company['ship_company'] ?>"><?= $ship_company['ship_company'] ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                <?php endif ?>
                                                <div class="form-group col-md-3">
                                                    <label>&nbsp;</label>
                                                    <button class="btn btn-primary col-md-12" id="print_selecteds"><i class="fas fa-print"></i> <?=$this->lang->line('application_print_tags_selecteds')?></button>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label>&nbsp;</label>
                                                    <a href="" class="btn btn-primary col-md-12" id="exportShippingCompanyWithLabels"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
                                                </div>
                                            </div>
                                        </div>
                                        <table id="shippingCompanyWithTag" class="table table-striped table-hover display table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?=$this->lang->line('application_order')?></th>
                                                <th><?=$this->lang->line('application_clients')?></th>
                                                <th><?=$this->lang->line('application_order_date')?></th>
                                                <th><?=$this->lang->line('application_dispatch_until')?></th>
                                                <th><?=$this->lang->line('application_store')?></th>
                                                <th><?=$this->lang->line('application_shipping_company')?></th>
                                                <th><?=$this->lang->line('application_tracking_code')?></th>
                                                <th><?=$this->lang->line('application_nfe_num')?></th>
                                                <th><?=$this->lang->line('application_action')?></th>
                                            </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                            <tr>
                                                <th><?=$this->lang->line('application_order')?></th>
                                                <th><?=$this->lang->line('application_clients')?></th>
                                                <th><?=$this->lang->line('application_order_date')?></th>
                                                <th><?=$this->lang->line('application_dispatch_until')?></th>
                                                <th><?=$this->lang->line('application_store')?></th>
                                                <th><?=$this->lang->line('application_shipping_company')?></th>
                                                <th><?=$this->lang->line('application_tracking_code')?></th>
                                                <th><?=$this->lang->line('application_nfe_num')?></th>
                                                <th><?=$this->lang->line('application_action')?></th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="tab-pane" id="shipping_company_with_tag_queue">
                                        <div class="row">
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <?php if (count($users_filter) > 1): ?>
                                                <div class="form-group col-md-4">
                                                    <label for="storesShippingCompanyWithLabels" class="normal"><?= $this->lang->line('application_search_for_users') ?></label>
                                                    <select class="form-control selectpicker show-tick" id="usersFilterWithLabels" name="user[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_search_for_users');?>">
                                                        <?php foreach ((array)$users_filter as $user_filter) {?>
                                                            <option value="<?=$user_filter['id']?>"><?="{$user_filter['email']} ({$user_filter['firstname']} {$user_filter['lastname']})"?></option>
                                                        <?php }?>
                                                    </select>
                                                </div>
                                                <?php endif ?>
                                            </div>
                                        </div>

                                        <table id="shippingCompanyGeneratedWithTag" class="table table-striped table-hover display table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?=$this->lang->line('application_user');?></th>
                                                <th><?=$this->lang->line('application_status');?></th>
                                                <th><?=$this->lang->line('application_orders');?></th>
                                                <th><?=$this->lang->line('application_date_create');?></th>
                                                <th><?=$this->lang->line('application_action');?></th>
                                            </tr>
                                            </thead>
                                            <tbody></tbody>
                                            <tfoot>
                                            <tr>
                                                <th><?=$this->lang->line('application_user');?></th>
                                                <th><?=$this->lang->line('application_status');?></th>
                                                <th><?=$this->lang->line('application_orders');?></th>
                                                <th><?=$this->lang->line('application_date_create');?></th>
                                                <th><?=$this->lang->line('application_action');?></th>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </section>
</section>
<?php if (in_array('doIntegration', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="removePlp">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=base_url('orders/manage_tags_del')?>" method="POST" enctype="multipart/form-data" id="formRemovePlp">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_delete');?> PLP</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h2 class="text-center"><i class="fa fa-warning text-red"></i></h2>
                            <h4 class="text-center text-red"><?=$this->lang->line('messages_warning_remove_plp');?></h4>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-4" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                    <button type="submit" class="btn btn-danger col-md-4 btn-cancel-plp"><?=$this->lang->line('application_permanently_delete');?></button>
                </div>
                <input type="hidden" name="number_plp" required>
            </form>
        </div>
    </div>
</div>
<?php endif ?>

<?php if (in_array('doIntegration', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="removeTag">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=base_url('orders/manage_tags_del')?>" method="POST" enctype="multipart/form-data" id="formRemoveTag">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_delete');?> etiqueta</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h2 class="text-center"><i class="fa fa-warning text-red"></i></h2>
                            <h4 class="text-center text-red"><?=$this->lang->line('messages_warning_remove_transp_tag');?></h4>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-4" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                    <button type="submit" class="btn btn-danger col-md-4 btn-cancel-plp"><?=$this->lang->line('application_permanently_delete');?></button>
                </div>
                <input type="hidden" name="tag_number" required>
            </form>
        </div>
    </div>
</div>
<?php endif ?>

<?php if (in_array($typeViewTags, array('all', 'correios'))): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="viewTags">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_printable_tags');?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 d-flex justify-content-around flex-wrap mb-5">
                        <a href="" id="pdf_a4_group" class="btn btn-primary col-md-3" download><i class="glyphicon glyphicon-print"></i> <?=$this->lang->line('application_collated_printing_a4');?></a>
                        <a href="" id="pdf_term_group" class="btn btn-primary col-md-3" download><i class="glyphicon glyphicon-fire"></i> <?=$this->lang->line('application_collated_printing_thermal');?></a>
                    </div>
                    <div class="col-md-12">
                        <table class="table table-bordered" id="table-view-plp-tag" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewZpl">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">ZPL</h4>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<?php if (in_array($typeViewTags, array('all', 'shipping_company_gateway'))): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="modalRequestGenerateTagShippingCompany">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="formRequestGenerateTagShippingCompany">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_request_tag_generation')?></h4>
                </div>
                <div class="modal-body">
                    <h4><?=$this->lang->line('messages_confirm_request_tag_generate')?></h4>
                    <div id="listRequestGenerateTagShippingCompany"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-3"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif ?>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css"/>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/datatables.net/js/processing.js"></script>
<style>
    a.disabled {
        pointer-events: none;
    }
    #etiquetas_geradas_sgpweb tbody td:nth-child(2),
    #etiquetas_geradas_correios tbody td:nth-child(2),
    #table-view-plp-tag tbody td:nth-child(2){
        display: flex;
        justify-content: start;
        flex-wrap: wrap;
    }
    #etiquetas_geradas_sgpweb a ,
    #etiquetas_geradas_correios a {
        margin: 1px 2px;
    }
    .modal-footer::before,
    .modal-footer::after{
        display: none;
    }
    #table-view-plp-tag tbody td:nth-child(2) pre {
        padding: 3px 10px;
        font-size: 14px;
        margin: 0px 2px !important;
    }
    #etiquetas_correios_wrapper .row:nth-child(1) div,
    #etiquetas_sgpweb_wrapper .row:nth-child(1) div,
    @media (max-width: 992px) {
        #correios_without_tag .col-md-7.col-xs-12,
        #correios_without_tag .col-md-5.col-xs-12,
        #sgpweb .col-md-7.col-xs-12,
        #sgpweb .col-md-5.col-xs-12 {
            float: unset;
        }
        #correios_without_tag .col-md-7.col-xs-12,
        #sgpweb .col-md-7.col-xs-12 {
            border-right: unset !important;
        }
    }
    .content-type-shipping-company li {
        padding-right: 0px;
        padding-left: 0px;
        margin-right: 0px !important;
        background-color: #fff;
    }
    .content-type-shipping-company li a{
        color: #fff !important;
        padding: 20px 20px;
    }
    .content-type-shipping-company li.active a{
        background-color: #ccc !important;
    }

    .nav-tabs-custom {
        background: unset !important;
        box-shadow: unset !important;
    }
    .nav-tabs-custom>.content-type-shipping-company>li>a {
        background: unset !important;
        color: #000 !important;
        text-decoration: unset;
        font-weight: bold;
        font-size: 15px;
    }
    .content-type-shipping-company li a{
        padding: 5px 20px !important;
        height: 100% !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .content-type-shipping-company li {
        background: unset !important;
        border-radius: 5px;
        width: 30%;
        text-align: center;
    }
    .content-type-shipping-company li.active {
        background: #bebebe !important;
    }
    .nav-tabs-custom>.content-type-shipping-company>li.active {
        border: unset !important;
    }
    .nav-tabs-custom>.content-type-shipping-company>li:first-of-type.active>a {
        border: unset !important;
    }

    .content-type-shipping-company {
        display: flex;
        justify-content: start !important;
        margin-bottom: 20px !important;
        border: unset !important;
    }

    .content-type-shipping-company li:nth-child(1),
    .content-type-shipping-company li:nth-child(2) {
        margin-right: 10px !important;
        width: 15%;
    }

</style>
<script>
    let manageTableLabelsGeneratedCorreios;
    let manageTableViewLabelsCorreios;
    let manageTableLabelCorreios;

    let manageTableLabelsGeneratedSgpweb;
    let manageTableLabelSgpweb;

    let manageTableShippingCompanyTag;
    let manageTableShippingCompanyWithoutTag;
    let manageTableShippingCompanyGeneratedWithTag;

    const base_url = "<?=base_url()?>";

    $(function () {
        $("#mainLogisticsNav").addClass('active');
        $("#manageOrdersTagsNav").addClass('active');

        manageTableLabelCorreios = $('#etiquetas_correios').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "responsive": true,
            "sortable": true,
            "paging": false,
            "scrollY": "450px",
            "scrollCollapse": true,
            "createdRow": row => {
                $( row )
                    .attr('order-id', parseInt($( row ).find('td:eq(0)').text()))
                    .attr('id',  'item_' + parseInt($( row ).find('td:eq(0)').text()));
            },
            "columnDefs": [
                {
                    "targets": 0,
                    "className": "d-flex justify-content-around flex-nowrap"
                }
            ]
        });

        manageTableLabelSgpweb = $('#etiquetas_sgpweb').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "responsive": true,
            "sortable": true,
            "paging": false,
            "scrollY": "450px",
            "scrollCollapse": true,
            "createdRow": row => {
                $( row )
                    .attr('order-id', parseInt($( row ).find('td:eq(0)').text()))
                    .attr('id',  'item_' + parseInt($( row ).find('td:eq(0)').text()));
            },
            "columnDefs": [
                {
                    "targets": 0,
                    "className": "d-flex justify-content-around flex-nowrap"
                }
            ]
        });

        /*setTimeout(() => {
            if ($('#correios').length || $('#sgpweb').length) {
                viewTableWithTag();
            } else {
                viewShippingCompanyWithoutTags();
            }
        }, 500);*/

        createTableOrderNotTags();
        updateLinkExportShippingCompanyWithLabel();

    });

    const startTableSgpwebWithTag = () => {

        if(manageTableLabelsGeneratedSgpweb !== undefined) {
            manageTableLabelsGeneratedSgpweb.destroy();
        }

        manageTableLabelsGeneratedSgpweb = $('#etiquetas_geradas_sgpweb').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "responsive": true,
            "sortable": true,
            "order": [[ 0, 'desc' ]],
            "columnDefs": [
                {
                    "targets": 3,
                    "className" : "d-flex flex-nowrap"
                }
            ]
        });

        manageTableLabelsGeneratedSgpweb.clear().draw().processing(true);
    }

    const startTableCorreiosWithTag = () => {

        if (manageTableLabelsGeneratedCorreios !== undefined) {
            manageTableLabelsGeneratedCorreios.clear().destroy();
        }
        
        manageTableLabelsGeneratedCorreios = $('#etiquetas_geradas_correios').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "responsive": true,
            "sortable": false
        });

        manageTableLabelsGeneratedCorreios.clear().draw().processing(true);
    }

    const viewShippingCompanyGeneratedWithTags = async () => {
        let url = "<?=base_url('orders/fetchLabelsGeneratedByShippingCompany')?>";

        let users = [];
        $('#usersFilterWithLabels  option:selected').each(function() {
            users.push($(this).val());
        });

        if (manageTableShippingCompanyGeneratedWithTag !== undefined) {
            $('#shippingCompanyGeneratedWithTag').DataTable().destroy();
        }

        manageTableShippingCompanyGeneratedWithTag = $('#shippingCompanyGeneratedWithTag').DataTable({
            "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url,
                data: { users },
                pages: 2
            }),
            "order": [[ 3, 'desc' ]]
        });
    }

    const viewShippingCompanyWithTags = async () => {
        const url = "<?=base_url('orders/fetchOrdersWithLabelShippingCompany')?>";

        let stores = [];
        let ship_company = [];
        $('#storesShippingCompanyWithLabels  option:selected').each(function() {
            stores.push($(this).val());
        });
        $('#shippingCompanyFilterWithLabels  option:selected').each(function() {
            ship_company.push($(this).val());
        });

        if (manageTableShippingCompanyTag !== undefined) {
            $('#shippingCompanyWithTag').DataTable().destroy();
        }

        manageTableShippingCompanyTag = $('#shippingCompanyWithTag').DataTable({
            "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url,
                data: { stores, ship_company },
                pages: 2
            }),
            "order": [[ 0, 'desc' ]],
            'fnDrawCallback': function() {
                $('input[type="checkbox"]').iCheck({
                    checkboxClass: 'icheckbox_minimal-blue',
                    radioClass   : 'iradio_minimal-blue'
                });
            },
        });
    }

    const viewShippingCompanyWithoutTags = async () => {
        const url       = "<?=base_url('orders/fetchOrdersWithoutLabelShippingCompany')?>";

        let stores = new Array();
        $('#storesShippingCompanyWithoutLabels  option:selected').each(function() {
            stores.push($(this).val());
        });

        if (manageTableShippingCompanyWithoutTag !== undefined) {
            $('#shippingCompanyWithoutTag').DataTable().destroy();
        }

        manageTableShippingCompanyWithoutTag = $('#shippingCompanyWithoutTag').DataTable({
            "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url,
                data: { stores },
                pages: 2
            }),
            "order": [[ 0, 'desc' ]],
            'fnDrawCallback': function() {
                $('input[type="checkbox"]').iCheck({
                    checkboxClass: 'icheckbox_minimal-blue',
                    radioClass   : 'iradio_minimal-blue'
                });
            },
        });
    }

    const viewTableWithTag = async () => {
        let rowsTable   = new Array();
        let url = "<?=base_url()?>";
        url += `/orders/getTagsTransmit/${$('a[aria-expanded="true"]').attr('href').replace('#', '')}`;

        if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
            await startTableSgpwebWithTag();
        } else if ($('a[href="#correios"][aria-expanded="true"]').length) {
            await startTableCorreiosWithTag();
        } else {
            return;
        }

        await $.ajax({
            url,
            type: "GET",
            dataType: 'json',
            success: response => {
                $.each(response, function( index, value ) {
                    rowsTable.push(value);
                });

                if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
                    manageTableLabelsGeneratedSgpweb.clear().draw().rows.add(rowsTable).columns.adjust().draw().processing(false);
                } else if ($('a[href="#correios"][aria-expanded="true"]').length) {
                    manageTableLabelsGeneratedCorreios.clear().draw().rows.add(rowsTable).columns.adjust().draw().processing(false);
                }

            }, error: error => {
                console.log(error);
            }
        });
    }

    const cleanOrderSelectPlp = () => {
        if ($('a[href="#correios"][aria-expanded="true"]').length) {
            $('#gerar_plp_correios').attr('disabled', true);
        } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
            $('#gerar_plp_sgpweb').attr('disabled', true);
        } else {
            return;
        }
        createTableOrderNotTags();
    }

    const createTableOrderNotTags = () => {
        let url = "<?=base_url()?>";

        if ($('a[href="#correios"][aria-expanded="true"]').length) {
            url += '/orders/fetchEtiquetas/correios';
        } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
            url += '/orders/fetchEtiquetas/sgpweb';
        } else {
            return;
        }

        let datasRow;
        $.ajax({
            url,
            type: "GET",
            dataType: 'json',
            success: response => {

                let manageTable;

                if ($('a[href="#correios"][aria-expanded="true"]').length) {
                    manageTable = manageTableLabelCorreios;
                } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
                    manageTable = manageTableLabelSgpweb;
                } else {
                    return;
                }

                if(manageTable === undefined) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Ocorreu um problema para visualizar a tabela, recarregue a página'
                    });
                    return false;
                }

                if ($('a[href="#correios"][aria-expanded="true"]').length) {
                    manageTableLabelCorreios.clear().draw();
                } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
                    manageTableLabelSgpweb.clear().draw();
                } else {
                    return;
                }

                for (let i = 0; i < response.length; i++) {
                    datasRow = [
                        `<input type="checkbox" name="orders_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}[]" value="${response[i].id}"> <a href="<?=base_url('orders/update')?>/${response[i].id}" target="_blank">${response[i].id}</a>`,
                        response[i].customer_name,
                        response[i].name_item,
                        response[i].date_time.substr(0, 10).split('-').reverse().join('/'),
                        'R$'+parseFloat(response[i].gross_amount).toLocaleString('pt-br')
                    ];

                    if ($(`#etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')} thead th`).length == 6) {
                        datasRow.push(response[i].store);
                    }

                    if ($('a[href="#correios"][aria-expanded="true"]').length) {
                        manageTableLabelCorreios.row.add(datasRow);
                    } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
                        manageTableLabelSgpweb.row.add(datasRow);
                    } else {
                        return;
                    }
                }

                if ($('a[href="#correios"][aria-expanded="true"]').length) {
                    manageTableLabelCorreios.columns.adjust().draw();
                } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
                    manageTableLabelSgpweb.columns.adjust().draw();
                }
            }, error: error => {
                console.log(error);
            }
        });
    }

    const updateLinkExportShippingCompanyWithLabel = () => {
        let url = "<?=base_url('export/labelsShippingCompanyWithTracking') ?>";
        if ($('#storesShippingCompanyWithLabels').length) {
            if ($('#storesShippingCompanyWithLabels').val().length)
                url += `?stores=${$('#storesShippingCompanyWithLabels').val().toString()}`;
            else url += '?stores=null';
        } else url += '?stores=null';

        if ($('#shippingCompanyFilterWithLabels').length) {
            if ($('#shippingCompanyFilterWithLabels').val().length)
                url += `&shipping=${$('#shippingCompanyFilterWithLabels').val().toString()}`;
            else url += '&shipping=null';
        } else url += '&shipping=null';

        $('#exportShippingCompanyWithLabels').attr('href', url);
    }

    $(document).on('submit', '[id*="form_temp_etiquetas"]', function () {
        const action = $(this).attr('action');
        const orders = $(`input[name="orders_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}"]`, this).val();

        $.post( action, { orders }, response => {
            $(`.box_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')} .overlay-tags`).remove();

            if(!response['success']) {
                AlertSweet.fire({
                    icon: 'error',
                    title: response['message']
                });
                cleanOrderSelectPlp();
                return false;
            }

            const icon = response['warning'] ? 'warning' : 'success';
            AlertSweet.fire({
                icon,
                title: response['message']
            });

            cleanOrderSelectPlp();

            setTimeout(() => {
                $(`a[href="#${$('a[aria-expanded="true"]').attr('href').replace('#', '')}_with_tag"]`).tab('show');
            }, 1000);

        }, "json")
        .fail(error => {
            console.log(error);
        });

        return false;
    });

    $(document).on('submit', '#formRemovePlp', function () {
        const action = "<?=base_url('orders/manage_tags_del') ?>";
        const number_plp = $('input[name="number_plp"]', this).val();

        $.post( action, { number_plp }, response => {
            $(`.box_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')} .overlay-tags`).remove();

            if(!response['success']) {
                Toast.fire({
                    icon: 'error',
                    title: response['message']
                });
                return false;
            }

            Toast.fire({
                icon: 'success',
                title: response['message']
            });

            $('#removePlp').modal('hide');

            setTimeout(() => {
                viewTableWithTag();
            }, 500);

        }, "json")
        .fail(error => {
            console.log(error);
        });

        return false;
    });

    $(document).on('click', '.del-plp', function () {
        const number_plp = $(this).attr('number-plp');

        $('#removePlp input[name="number_plp"]').val(number_plp);
        $('#removePlp').modal();
    });

    $(document).on('submit', '#formRemoveTag', function () {
        const action = "<?=base_url('orders/manage_tags_transp_del') ?>";
        const tag_number = $('input[name="tag_number"]', this).val();

        $.post( action, { tag_number }, response => {
            $(`.box_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')} .overlay-tags`).remove();

            if(!response['success']) {
                Toast.fire({
                    icon: 'error',
                    title: response['message']
                });
                return false;
            }

            Toast.fire({
                icon: 'success',
                title: response['message']
            });

            $('#removeTag').modal('hide');

            setTimeout(() => {
                //viewTableCorreiosWithTag();
                viewShippingCompanyWithTags()
            }, 500);

        }, "json")
        .fail(error => {
            console.log(error);
        });

        return false;
    });

    $(document).on('click', '.del-transp-tag', function () {
        const tag_number = $(this).attr('tag-number');

        $('#removeTag input[name="tag_number"]').val(tag_number);
        $('#removeTag').modal();
    });

    $(document).on('click', '.viewTags', function () {
        const number_plp = $(this).attr('number-plp');
        const url = "<?=base_url('orders/getTagsPlp')?>";
        let link_etiquetas_a4;
        let link_etiquetas_termica, tag_a_a4, tag_a_termica, content;
        let count_etiquetas = 0;
        const integration = $('.content-type-shipping-company li.active a[data-toggle="tab"]').attr('href').replace('#', '');;

        $.ajax({
            url,
            type: "POST",
            data: { number_plp, integration },
            dataType: 'json',
            success: response => {
                if(manageTableViewLabelsCorreios !== undefined) {
                    manageTableViewLabelsCorreios.destroy();
                }

                if ($('a[href="#correios"][aria-expanded="true"]').length) {
                    $('#table-view-plp-tag thead').html(`
                        <tr><th><?=$this->lang->line('application_order');?></th>
                        <th><?=$this->lang->line('application_tracking code');?></th>
                        <th><?=$this->lang->line('application_expiration_date');?></th>
                        <th><?=$this->lang->line('application_print');?></th></tr>
                    `);
                } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
                    $('#table-view-plp-tag thead').html(`
                        <tr><th><?=$this->lang->line('application_order');?></th>
                        <th><?=$this->lang->line('application_tracking code');?></th>
                        <th><?=$this->lang->line('application_print');?></th></tr>
                    `);
                } else {
                    return;
                }

                $('#table-view-plp-tag tbody').empty();
                $.each(response, function( index, value ) {
                    count_etiquetas++;
                    link_etiquetas_a4 = value.link_etiquetas_a4;
                    link_etiquetas_termica = value.link_etiquetas_termica;

                    tag_a_a4 = link_etiquetas_a4 ? `<a href="${value.link_etiqueta_a4}" class="btn btn-primary" download><i class="glyphicon glyphicon-print"></i> A4</a>` : '';
                    tag_a_termica = link_etiquetas_termica ? `<a href="${value.link_etiqueta_termica}" class="btn btn-primary" download><i class="glyphicon glyphicon-fire"></i> Térmica</a>` : '';

                    content = `<td>${value.order_id}</td><td>${value.codigo_rastreio}</td>`;

                    if ($('a[href="#correios"][aria-expanded="true"]').length) {
                        content += `<td>${value.date_expiration}</td>`;
                    }

                    $('#table-view-plp-tag tbody').append(`
                        <tr>
                            ${content}
                            <td>
                                ${tag_a_a4}
                                ${tag_a_termica}
                                <button id="pdf_term_group" class="btn btn-primary viewZpl" data-trackins="${value.codigo_rastreio_zpl}" data-order="${value.order_id}"><i class="fas fa-file-alt"></i> ZPL</button>
                            </td>
                        </tr>
                    `);
                });

                if (link_etiquetas_a4 === "" || count_etiquetas <= 1) {
                    $('#pdf_a4_group').hide();
                } else {
                    $('#pdf_a4_group').attr('href', link_etiquetas_a4).show();
                }

                if (link_etiquetas_termica === "" || count_etiquetas <= 1) {
                    $('#pdf_term_group').hide();
                } else {
                    $('#pdf_term_group').attr('href', link_etiquetas_termica).show();
                }

                if (response.length === 0) {
                    $('#table-view-plp-tag tbody').append(`
                        <tr>
                            <td colspan="6" class="text-center"><?=$this->lang->line('messages_no_shipment_orders')?></td>
                        </tr>
                    `);
                }

                manageTableViewLabelsCorreios = $('#table-view-plp-tag').DataTable({
                    "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
                    "processing": true,
                    "responsive": true,
                    "sortable": true
                });

                $('#viewTags').modal();
            }, error: error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha para abrir as etiquetas',
                    html: error.responseJSON.message
                })
            }
        });
    });

    $(document).on('click', '.viewZpl', function(){
        const trackins  = $(this).data('trackins');
        const order     = $(this).data('order');
        const url       = "<?=base_url('orders/getZplEtiqueta')?>";

        $.ajax({
            url,
            type: "POST",
            data: { trackins, order },
            dataType: 'json',
            success: response => {
                $('#viewZpl .modal-body').empty();
                if(!response[0]) {
                    Toast.fire({
                        icon: 'error',
                        title: response[1]
                    });
                    return false;
                }
                $.each(response[1], function( index, value ) {
                    $('#viewZpl .modal-body').append(`
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>${value[0]}</label>
                                <div class="col-md-12 form-control" style="height: 100%">
                                    <p style="word-break: break-word">${value[1]}</p>
                                </div>
                            </div>
                        </div>
                    `);
                });
                $('#viewZpl').modal();

            }, error: error => {
                console.log(error);
            }
        });
    });

    $(' .nav-tabs a[href*="_without_tag"]').on('shown.bs.tab', function () {
        setTimeout(() => {
            //createTableOrderNotTags();
            cleanOrderSelectPlp();
        }, 250);
    });

    $('.nav-tabs a[href="#shipping_company"]').on('shown.bs.tab', function () {
        if ($('#shipping_company_with_tag').is(':visible')) viewShippingCompanyWithTags();
        if ($('#shipping_company_without_tag').is(':visible')) viewShippingCompanyWithoutTags();
    });

    $('.nav-tabs a[href="#sgpweb"], .nav-tabs a[href="#correios"]').on('shown.bs.tab', function () {
        $('a[href][data-toggle="tab"]').addClass('disabled');

        if ($('[id*="_without_tag"]').is(':visible')) {
            createTableOrderNotTags();
        }
        if ($('[id*="_with_tag"]').is(':visible')) {
            viewTableWithTag();
        }

        setTimeout(() => {
            $('a[href][data-toggle="tab"]').removeClass('disabled');
        }, 2000);
    });

    $('.nav-tabs a[href="#shipping_company_with_tag"]').on('shown.bs.tab', function () {
        viewShippingCompanyWithTags();
    });

    $('.nav-tabs a[href*="_with_tag"]').on('shown.bs.tab', function () {
        viewTableWithTag();
    });

    $('.nav-tabs a[href="#shipping_company_without_tag"]').on('shown.bs.tab', function () {
        viewShippingCompanyWithoutTags();
    });

    $('.nav-tabs a[href="#shipping_company_with_tag_queue"]').on('shown.bs.tab', function () {
        viewShippingCompanyGeneratedWithTags();
    });

    $('#viewZpl').on('hidden.bs.modal', function(e){
        $("body").addClass("modal-open");
    });

    $('#print_selecteds').on('click', function (){
        let orders  = new Array();
        let links   = new Array();
        let url     = "<?=base_url('orders/getLabelsByOrder')?>";

        manageTableShippingCompanyTag.$('input[type="checkbox"]:checked').each(function(){
            orders.push($(this).val());
        });

        if (orders.length === 0) {
            Toast.fire({
                icon: 'error',
                title: 'Nenhum pedido selecionado'
            });
            return false;
        }

        Swal.fire({
            icon: 'info',
            title: 'Aguarde &nbsp;<i class="fa fa-spinner fa-spin"></i>',
            showCancelButton: false,
            showConfirmButton: false,
            allowOutsideClick: false
        })

        $.ajax({
            url,
            type: "POST",
            data: { orders },
            dataType: 'json',
            success: response => {
                Swal.close();

                manageTableShippingCompanyTag.$('input[type="checkbox"]:checked').each(function(){
                    $(this).iCheck('uncheck');
                });

                for (let [key, data] of Object.entries(response)) {
                    links.push(data.link);

                    $(orders).each(function(k, v){
                         if (v == data.order) {
                             orders.splice(k, 1);
                         }
                    });
                }

                $(links).each(function(k, v){
                    const elementDownload = 'a[download].download-link-shipping_company';

                    if (!$(elementDownload).length) {
                        $(document.body).append(`<a href="${v}" download class="download-link-shipping_company" target="_blank"></a>`);
                    } else {
                        $(elementDownload).prop('href', v);
                    }

                    document.querySelectorAll(elementDownload)[0].click();
                });

                if (orders.length) {

                    $.ajax({
                        url: "<?=base_url('orders/createTagTransp/')?>" + orders.join('-'),
                        type: "POST",
                        dataType: 'json',
                        success: response => {

                            Swal.fire({
                                icon: response.success ? 'success' : 'warning',
                                html: response.message
                            });

                            if (response.success) {
                                viewShippingCompanyWithoutTags();
                            }
                        }, error: error => {
                            console.log(error);
                        }
                    });
                }
            }, error: error => {
                console.log(error);
            }
        });
    });

    $('[id*="gerar_plp"]').on('click', function () {
        const btn = $(this);
        let orders;
        let uri_form = "<?=base_url()?>";

        btn.attr('disabled', true);

        if ($('a[href="#correios"][aria-expanded="true"]').length) {
            uri_form += '/orders/groupsLabelsCorreios';
        } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
            uri_form += '/orders/manage_tags_post';
        } else {
            return;
        }

        if ($(`#form_temp_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}`).length === 0) {
            $(document.body).append(`<form action="${uri_form}" method="POST" enctype="multipart/form-data" id="form_temp_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}" style="display: none;"><input type="hidden" name="orders_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}"> <input type="submit"></form>`);
        }

        orders = $(`[name="orders_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}[]"]:checked`).map(function(idx, elem) {
            return $(elem).val();
        }).get();

        if (orders.length === 0) {
            Toast.fire({
                icon: 'error',
                title: '<?=$this->lang->line('messages_select_least_one_tag')?>'
            });
            btn.attr('disabled', false);
            return ;
        }

        $(`.box_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}`).append(`
            <div class="overlay-tags">
              <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
            </div>
        `);

        $(`#form_temp_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')} input[name="orders_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}"]`).val(`[${orders}]`);

        setTimeout(() => {
            $(`#form_temp_etiquetas_${$('a[aria-expanded="true"]').attr('href').replace('#', '')}`).submit();
        }, 500);
        btn.attr('disabled', false);

    });

    $('#requestGenerateTag').on('click', function (){
        let orders  = [];

        manageTableShippingCompanyWithoutTag.$('input[type="checkbox"]:checked').each(function(){
            orders.push($(this).val());
        });

        if (orders.length === 0) {
            Toast.fire({
                icon: 'error',
                title: 'Nenhum pedido selecionado'
            });
            return false;
        }

        $('#listRequestGenerateTagShippingCompany')
            .empty()
            .append('<ul><li>' + orders.join('</li><li>') + '</li></ul>');

        $('#modalRequestGenerateTagShippingCompany').modal();
    });

    $(document).on('submit', '#formRequestGenerateTagShippingCompany', function () {
        let orders  = new Array();
        let url     = "<?=base_url('orders/requestGenerateTagShippingCompany/')?>";

        manageTableShippingCompanyWithoutTag.$('input[type="checkbox"]:checked').each(function(){
            orders.push($(this).val());
        });

        if (orders.length === 0) {
            Toast.fire({
                icon: 'error',
                title: 'Nenhum pedido selecionado'
            });
            return false;
        }

        url += orders.join('-');

        $.ajax({
            url,
            type: "POST",
            data: { orders },
            dataType: 'json',
            success: response => {

                Swal.fire({
                    icon: response.success ? 'success' : 'warning',
                    title: response.message
                });

                if (response.success) {
                    $('#modalRequestGenerateTagShippingCompany').modal('hide');
                    setTimeout(() => {
                        viewShippingCompanyWithoutTags();
                    }, 750);
                }
            }, error: error => {
                console.log(error);
            }
        });

        return false;
    });

    $('#storesShippingCompanyWithoutLabels').on('change', function(){
        viewShippingCompanyWithoutTags();
    });

    $('#storesShippingCompanyWithLabels, #shippingCompanyFilterWithLabels').on('change', function(){
        viewShippingCompanyWithTags();
        updateLinkExportShippingCompanyWithLabel();
    });

    $(document).on('click', '[name="orders_sgpweb[]"], [name="orders_correios[]"]', function(){
        if ($('a[href="#correios"][aria-expanded="true"]').length) {
            if ($('[name="orders_correios[]"]:checked').length) {
                $('#gerar_plp_correios').attr('disabled', false);
            } else {
                $('#gerar_plp_correios').attr('disabled', true);
            }
        } else if ($('a[href="#sgpweb"][aria-expanded="true"]').length) {
            if ($('[name="orders_sgpweb[]"]:checked').length) {
                $('#gerar_plp_sgpweb').attr('disabled', false);
            } else {
                $('#gerar_plp_sgpweb').attr('disabled', true);
            }
        }
    });

    $('#usersFilterWithLabels').on('change', function(){
        viewShippingCompanyGeneratedWithTags();
    });
</script>