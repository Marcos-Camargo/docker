<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_cache')?></h3>
                    </div>
                    <div class="box-body no-padding mt-3">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs">
                                <li class="active"><a href="#quote" data-toggle="tab"><?=$this->lang->line('application_quotation')?></a></li>
                            </ul>
                            <div class="tab-content col-md-12">
                                <div class="tab-pane active" id="quote">
                                    <table class="table table-bordered" id="tableQuote">
                                        <thead>
                                            <th><?=$this->lang->line('application_name');?></th>
                                            <th><?=$this->lang->line('application_action');?></th>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?=$this->lang->line('application_systemconfig');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="settings"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_integration_logistic');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="logistic_integration"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_multi_cd');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="multi_cd"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_shipping_company');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="sipping_company"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_table_shipping');?> (<?=$this->lang->line('application_spreadsheet_and_simplified');?>)</td>
                                                <td><button class="btn btn-primary" data-key-clean="shipping_company_table"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_product');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="product"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_region');?> (<?=$this->lang->line('application_zip_code');?>, <?=$this->lang->line('application_city');?>, <?=$this->lang->line('application_merchant_uf');?>)</td>
                                                <td><button class="btn btn-primary" data-key-clean="region"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_auction_rule');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="auction"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                            <tr>
                                                <td><?=$this->lang->line('application_pickup_point');?></td>
                                                <td><button class="btn btn-primary" data-key-clean="pickup_point"><i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    let manageTable;
    const base_url = "<?=base_url()?>";

    $(document).ready(function () {
        $("#mainProcessesNav").addClass('active');
        $("#manageCleanCache").addClass('active');

        manageTable = $('#tableQuote').dataTable({
            "sortable": true,
            "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" }
        });
    });

    $('[data-key-clean]').on('click', function() {
        const tag = $(this).data('key-clean');

        $.ajax({
            url: `${base_url}/Cache/cleanCache/${tag}`,
            type: 'get',
            dataType: 'json',
            success: response => {
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.message
                })
            }
        });
    });
</script>

