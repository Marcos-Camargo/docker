<div class="content-wrapper">

	<?php
	$data['pageinfo'] = "application_manage";
	$this->load->view('templates/content_header', $data);
	?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

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

				<?php if (in_array('createCampaigns', $user_permission) && $only_admin && $usercomp == 1 && !$this->session->userdata('userstore')): ?>
                    <a href="<?php echo base_url('campaigns_v2/createcampaigns') ?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
						<?= lang('application_add_campaign_v2'); ?>
                    </a>
					<?php
					if ($allow_create_campaigns_b2w_type) {
						?>
                        <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=b2wcampaign') ?>" class="btn btn-primary">
                            <i class="fa fa-plus"></i>
							<?= lang('application_add_campaign_v2_b2w'); ?>
                        </a>
						<?php
					}
				endif;
				?>
				<?php if (in_array('sellerCampaignCreation', $user_permission)) { ?>
                    <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=sellerCampaign') ?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
						<?= lang('application_add_campaign_v2_seller'); ?>
                    </a>
					<?php
				}
				?>


                <div class="box box-info mt-2">

                    <div class="box-header with-border">

                        <h3 class="box-title">

                            <i class="fa fa-filter fa-2x" title="Filtro"></i>
                            Filtros

                        </h3>

                    </div>

                    <div class="box-header with-border">

                        <form id="active-campaign-filters" enctype="text/plain">

                            <div class="row">

                                <div class="form-group col-md-2 col-xs-2">
                                    <label for="filter_start_date"><?= $this->lang->line('application_start_date'); ?></label>
                                    <input type="date" class="form-control" id="filter_start_date" name="filter_start_date">
                                </div>

                                <div class="form-group col-md-2 col-xs-2">
                                    <label for="filter_end_date"><?= $this->lang->line('application_end_date'); ?></label>
                                    <input type="date" class="form-control" id="filter_end_date" name="filter_end_date">
                                </div>

                            </div>

                            <div class="row">

                                <div class="form-group col-md-2 col-xs-2">
                                    <button class="btn btn-success" onclick="return filterCampaigns()">
                                        <i class="fa fa-filter"></i> <?=lang('application_filter');?>
                                    </button>
                                </div>

                            </div>

                        </form>

                    </div>

                </div>

                <div class="box box-info mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= lang('application_active_campaigns'); ?></h3>
                    </div>
                    <div class="box-body">
                        <table id="activeCampaigns" class="table table-bordered table-striped table-condensed">
                            <thead>
                            <tr>
                                <th style="display: none;"><?= lang('application_id'); ?></th>
                                <th><?= lang('application_campaign_name'); ?></th>
                                <th><?= lang('application_campaign_type'); ?></th>
                                <th data-orderable="false"><?= lang('application_marketplace_takes_over'); ?></th>
                                <th data-orderable="false"><?= lang('application_merchant_takes_over'); ?></th>
                                <th><?= lang('application_start_date'); ?></th>
                                <th><?= lang('application_end_date'); ?></th>
                                <th><?= lang('application_deadline_for_joining_index'); ?></th>
                                <th data-orderable="false"><?= lang('application_campaign_status'); ?></th>
								<?php
								if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
									?>
                                    <th data-orderable="false"><?= lang('application_campaign_itens_selected_by_seller'); ?></th>
                                    <th data-orderable="false"><?= lang('application_campaign_itens_approved_by_marketplace'); ?></th>
                                    <th data-orderable="false"><?= lang('application_campaign_itens_not_approved_by_marketplace'); ?></th>
                                    <th data-orderable="false"><?= lang('application_has_products_pending'); ?></th>
									<?php
								}
                                if (in_array('approveCampaignCreation', $user_permission)){
								?>
                                    <th data-orderable="false"><?= lang('application_campaign_approve_status'); ?></th>
                                <?php
                                }
                                ?>
                                <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>

                <div class="box box-info mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= lang('application_expired_campaigns'); ?></h3>
                    </div>
                    <div class="box-body">
                        <table id="expiredCampaigns" class="table table-bordered table-striped table-condensed">
                            <thead>
                            <tr>
                                <th style="display: none;"><?= lang('application_id'); ?></th>
                                <th><?= lang('application_campaign_name'); ?></th>
                                <th><?= lang('application_campaign_type'); ?></th>
                                <th data-orderable="false"><?= lang('application_marketplace_takes_over'); ?></th>
                                <th data-orderable="false"><?= lang('application_merchant_takes_over'); ?></th>
                                <th><?= lang('application_start_date'); ?></th>
                                <th><?= lang('application_end_date'); ?></th>
                                <th><?= lang('application_deadline_for_joining_index'); ?></th>
                                <th data-orderable="false"><?= lang('application_campaign_status'); ?></th>
                                <th data-orderable="false"><?= lang('application_campaign_itens_selected_by_seller'); ?></th>
                                <th data-orderable="false"><?= lang('application_campaign_itens_approved_by_marketplace'); ?></th>
                                <th data-orderable="false"><?= lang('application_campaign_itens_not_approved_by_marketplace'); ?></th>
								<?php
								if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
									?>
                                    <th data-orderable="false"><?= lang('application_has_products_pending'); ?></th>
									<?php
								}
                                if (in_array('approveCampaignCreation', $user_permission)){
								?>
                                    <th data-orderable="false"><?= lang('application_campaign_approve_status'); ?></th>
                                <?php
                                }
                                ?>
                                <th data-orderable="false" class="col-md-2"><?= lang('application_action'); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>

				<?php
				if ($this->session->userdata('userstore') || $this->session->userdata('usercomp') != 1){
					?>
                    <div class="box box-info mt-2">
                        <div class="box-header with-border">
                            <h3 class="box-title"><?= lang('application_my_campaigns'); ?></h3>
                        </div>
                        <div class="box-body">
                            <table id="myCampaigns" class="table table-bordered table-striped table-condensed">
                                <thead>
                                <tr>
                                    <th style="display: none;"><?= lang('application_id'); ?></th>
                                    <th><?= lang('application_campaign_name'); ?></th>
                                    <th><?= lang('application_campaign_type'); ?></th>
                                    <th data-orderable="false"><?= lang('application_marketplace_takes_over'); ?></th>
                                    <th data-orderable="false"><?= lang('application_merchant_takes_over'); ?></th>
                                    <th><?= lang('application_start_date'); ?></th>
                                    <th><?= lang('application_end_date'); ?></th>
                                    <th><?= lang('application_deadline_for_joining_index'); ?></th>
                                    <th data-orderable="false"><?= lang('application_campaign_status'); ?></th>
                                    <th data-orderable="false"><?= lang('application_campaign_itens_selected_by_seller'); ?></th>
                                    <th data-orderable="false"><?= lang('application_campaign_itens_approved_by_marketplace'); ?></th>
                                    <th data-orderable="false"><?= lang('application_campaign_itens_not_approved_by_marketplace'); ?></th>
									<?php
									if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
										?>
                                        <th data-orderable="false"><?= lang('application_has_products_pending'); ?></th>
										<?php
									}
                                    if (in_array('approveCampaignCreation', $user_permission)){
									?>
                                        <th data-orderable="false"><?= lang('application_campaign_approve_status'); ?></th>
                                    <?php
                                    }
                                    ?>
                                    <th data-orderable="false" class="col-md-2"><?= lang('application_action'); ?></th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- /.box-body -->
                    </div>
					<?php
				}
				?>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<div class="modal fade" tabindex="-1" role="dialog" id="detailModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Detalhes da Campanha</h4>
            </div>
            <form role="form" action="" method="post" id="formObservacao">
                <div class="modal-body" id="campaignDetails">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('application_close')?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">

    var activeCampaigns;
    var expiredCampaigns;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

        $("#mainCampaignsNav").addClass('active');
        $("#manageCampaignsNav").addClass('active');

        filterCampaigns();

    });

    function showCampaignDetail(id){
        if(id){

            $("#campaignDetails").html('<i class="fa fa-spin fa-spinner"></i>');

            var pageURL = base_url.concat("campaigns_v2/detail/"+id);

            $.get( pageURL, function( data ) {
                $("#campaignDetails").html(data);
            });

        }
    }

    function mountActiveCampaignsDataTable() {

        if ($('#activeCampaigns').length) {
            $('#activeCampaigns').DataTable().destroy();
        }

        // initialize the datatable
        activeCampaigns = $('#activeCampaigns').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_campaign_search_placeholder'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'campaigns_v2/active_campaigns/',
                "type": 'POST',
                "data": {
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id", "class" : 'hidden'},
                {"data": "name"},
                {"data": "campaign_type"},
                {"data": "marketplace_takes_over"},
                {"data": "merchant_takes_over"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "deadline_for_joining"},
                {"data": "status"},
				<?php
				if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
				?>
                {"data": "download_selected_itens_by_seller"},
                {"data": "download_approved_by_marketplace"},
                {"data": "download_not_approved_by_marketplace"},
                {"data": "has_products_pending"},
				<?php
				}
                if (in_array('approveCampaignCreation', $user_permission)){
				?>
                {"data": "approved"},
                <?php
                }
                ?>
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function mountExpiredCampaigns(){

        if ($('#expiredCampaigns').length) {
            $('#expiredCampaigns').DataTable().destroy();
        }

        expiredCampaigns = $('#expiredCampaigns').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_campaign_search_placeholder'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'campaigns_v2/expired_campaigns/',
                "type": 'POST',
                "data": {
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id", "class" : 'hidden'},
                {"data": "name"},
                {"data": "campaign_type"},
                {"data": "marketplace_takes_over"},
                {"data": "merchant_takes_over"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "deadline_for_joining"},
                {"data": "status"},
                {"data": "download_selected_itens_by_seller"},
                {"data": "download_approved_by_marketplace"},
                {"data": "download_not_approved_by_marketplace"},
				<?php
				if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
				?>
                {"data": "has_products_pending"},
				<?php
				}
                if (in_array('approveCampaignCreation', $user_permission)){
				?>
                {"data": "approved"},
                <?php
                }
                ?>
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function mountMyCampaigns(){

		<?php
		if ($this->session->userdata('userstore') || $this->session->userdata('usercomp') != 1){
		?>

        if ($('#myCampaigns').length) {
            $('#myCampaigns').DataTable().destroy();
        }

        myCampaigns = $('#myCampaigns').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_campaign_search_placeholder'); ?>"
            },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'campaigns_v2/my_campaigns/',
                "type": 'POST',
                "data": {
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id", "class" : 'hidden'},
                {"data": "name"},
                {"data": "campaign_type"},
                {"data": "marketplace_takes_over"},
                {"data": "merchant_takes_over"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "deadline_for_joining"},
                {"data": "status"},
                {"data": "download_selected_itens_by_seller"},
                {"data": "download_approved_by_marketplace"},
                {"data": "download_not_approved_by_marketplace"},
				<?php
				if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
				?>
                {"data": "has_products_pending"},
				<?php
				}
                if (in_array('approveCampaignCreation', $user_permission)){
				?>
                {"data": "approved"},
                <?php
                }
                ?>
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

		<?php
		}
		?>

    }

    function filterCampaigns(){

        mountActiveCampaignsDataTable();
        mountExpiredCampaigns();
        mountMyCampaigns();

        return false;

    }

</script>

<style>
    .dataTables_scrollBody {
        overflow: visible  !important;
    }
</style>