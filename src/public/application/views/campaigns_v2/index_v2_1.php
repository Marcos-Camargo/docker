<link rel="stylesheet" type="text/css" href="<?=base_url()?>/assets/dist/css/views/campaign_v2/styles.css">
<div class="content-wrapper">

    <?php
    $data['pageinfo'] = "";
    if (isset($data['page_now_selected'])){ $data['page_now'] = $data['page_now_selected']; }

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


                

                <div class="box mt-2">




                    <div class="box-header pb-0">

<!--                        <h3 class="box-title">-->
<!---->
<!--                            <i class="fa fa-filter fa-2x" title="Filtro"></i>-->
<!--                            Filtros-->
<!---->
<!--                        </h3>-->


                        <div class="col-md-9">
                            <ul class="nav nav-tabs mt-5" role="tablist" id="store-tabs">
                                <li class="active" role="presentation" ><a class="nav-item nav-link" href="#campaigns_active"  data-toggle="tab"><?=$this->lang->line('application_active_campaigns')?></a></li>
                                <li role="presentation" ><a class="nav-item nav-link" href="#campaigns_inactive" data-toggle="tab" id="nav_campaigns_inactive"><?=$this->lang->line('application_expired_campaigns')?></a></li>
                                <?php if ($this->session->userdata('userstore') || $this->session->userdata('usercomp') != 1): ?>
                                    <li role="presentation" ><a class="nav-item nav-link" href="#campaigns_mine" data-toggle="tab"  id="nav_campaigns_mine"><?=$this->lang->line('application_my_campaigns')?></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="col-md-3 pt-5 pb-2 pr-0" style="text-align: right; border-bottom: 1px solid #ddd; position: absolute; right: 25px; bottom: 0;">


                            <?php if (in_array('createCampaigns', $user_permission) && $only_admin && $usercomp == 1 && !$this->session->userdata('userstore')): ?>
                                <a href="<?php echo base_url('campaigns_v2/createcampaigns') ?>" class="btn btn-wider-1 btn-outline-primary">
                                    <i class="fa fa-plus-circle"></i>&nbsp;
                                    <?= lang('application_add_campaign_v2'); ?>
                                </a>
                                <?php
                                if ($allow_create_campaigns_b2w_type) {
                                    ?>
                                    <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=b2wcampaign') ?>" class="btn btn-wider-1 btn-outline-primary">
                                        <i class="fa fa-plus-circle"></i>&nbsp;
                                        <?= lang('application_add_campaign_v2_b2w'); ?>
                                    </a>
                                    <?php
                                }
                            endif;
                            ?>
                            <?php if (in_array('sellerCampaignCreation', $user_permission)) { ?>
                                <a href="<?php echo base_url('campaigns_v2/createcampaigns?defaultType=sellerCampaign') ?>" class="btn btn-wider-1 btn-outline-primary">
                                    <i class="fa fa-plus-circle"></i>&nbsp;
                                    <?= lang('application_add_campaign_v2_seller'); ?>
                                </a>
                                <?php
                            }
                            ?>

                        </div>


                    </div>


                    <div class="box-body pt-0 "style="padding-right: 25px; padding-left: 25px;">

                        <div class="col-md-12 p-5">

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



                                    <div class="form-group col-md-2 col-xs-2">
                                        <label for="filter_start_date">&nbsp;</label><br/>
                                        <button class="btn btn-outline-primary" onclick="return filterCampaigns()">
                                            <i class="fa fa-filter"></i> <?=lang('application_filter');?>
                                        </button>
                                    </div>

                                </div>

                            </form>
                        </div>







                        <div class="tab-content campaign-tab-content p-5">

                            <div class="tab-pane fade in active" id="campaigns_active" role="tabpanel">

                                <div class="mt-5 pt-5">

                                    <table id="activeCampaigns" class="table table-bordered table-striped table-condensed">
                                        <thead>
                                            <tr>
                                                <th style="display: none;"><?= lang('application_id'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_name'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_type'); ?></th>
<!--                                                                                        <th data-orderable="false">--><?php //= lang('application_marketplace_takes_over'); ?><!--</th>-->
<!--                                                                                        <th data-orderable="false">--><?php //= lang('application_merchant_takes_over'); ?><!--</th>-->
                                                <th><?= lang('campaigns_v2_manage_list_th_date_start'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_date_end'); ?></th>
<!--                                                                                        <th>--><?php //= lang('application_deadline_for_joining_index'); ?><!--</th>-->
                                                <th data-orderable="false"><?= lang('campaigns_v2_manage_list_th_status'); ?></th>
                                                <?php
                                                if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
                                                    ?>
                                                    <!--                                            <th data-orderable="false">--><?php //= lang('application_campaign_itens_selected_by_seller'); ?><!--</th>-->
                                                    <!--                                            <th data-orderable="false">--><?php //= lang('application_campaign_itens_approved_by_marketplace'); ?><!--</th>-->
                                                    <!--                                            <th data-orderable="false">--><?php //= lang('application_campaign_itens_not_approved_by_marketplace'); ?><!--</th>-->
                                                    <!--                                            <th data-orderable="false">--><?php //= lang('application_has_products_pending'); ?><!--</th>-->
                                                    <?php
                                                }
                                                ?>
<!--                                                <th data-orderable="false">--><?php //=lang('campaigns_v2_manage_list_th_files');?><!--</th>-->
                                                <th data-orderable="false"><? //= lang('application_action'); ?></th>
                                            </tr>
                                        </thead>
                                    </table>


                                </div>

                            </div><!-- aba de ativas -->


                            <div class="tab-pane fade " id="campaigns_inactive" role="tabpanel">

                                <div>

                                    <table id="expiredCampaigns" class="table table-bordered table-striped table-condensed" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th style="display: none;"><?= lang('application_id'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_name'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_type'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_date_start'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_date_end'); ?></th>
                                                <th data-orderable="false"><?= lang('campaigns_v2_manage_list_th_status'); ?></th>
                                                <th data-orderable="false"></th>
                                            </tr>
                                        </thead>
                                    </table>

                                </div>

                            </div><!-- aba de inativas -->

                            <?php if ($this->session->userdata('userstore') || $this->session->userdata('usercomp') != 1): ?>

                                <div class="tab-pane fade" id="campaigns_mine" role="tabpanel">

                                    <div class="mt-5 pt-5">

                                        <table id="myCampaigns" class="table table-bordered table-striped table-condensed">
                                            <thead>
                                            <tr>
                                                <th style="display: none;"><?= lang('application_id'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_name'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_type'); ?></th>
<!--                                                <th data-orderable="false">--><?php //= lang('application_marketplace_takes_over'); ?><!--</th>-->
<!--                                                <th data-orderable="false">--><?php //= lang('application_merchant_takes_over'); ?><!--</th>-->
                                                <th><?= lang('campaigns_v2_manage_list_th_date_start'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_date_end'); ?></th>
<!--                                                <th>--><?php //= lang('application_deadline_for_joining_index'); ?><!--</th>-->
                                                <th data-orderable="false"><?= lang('campaigns_v2_manage_list_th_status'); ?></th>

<!--                                                <th data-orderable="false">--><?php //= lang('application_campaign_itens_selected_by_seller'); ?><!--</th>-->
<!--                                                <th data-orderable="false">--><?php //= lang('application_campaign_itens_approved_by_marketplace'); ?><!--</th>-->
<!--                                                <th data-orderable="false">--><?php //= lang('application_campaign_itens_not_approved_by_marketplace'); ?><!--</th>-->
                                                <?php
                                                if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
                                                    ?>
<!--                                                    <th data-orderable="false">--><?php //= lang('application_has_products_pending'); ?><!--</th>-->
                                                    <?php
                                                }
                                                ?>
<!--                                                <th data-orderable="false" class="col-md-2">--><?php //= lang('application_action'); ?><!--</th>-->
<!--                                                <th data-orderable="false">--><?php //=lang('campaigns_v2_manage_list_th_files');?><!--</th>-->
                                                <th data-orderable="false"><? //= lang('application_action'); ?></th>
                                            </tr>
                                            </thead>
                                        </table>

                                    </div>
                                </div><!-- aba de minhas campanhas -->

                            <?php endif; ?>

                        </div><!-- container das abas -->


                    </div>



                </div><!-- box com todo conteudo -->



                <?php
                if ($this->session->userdata('userstore') || $this->session->userdata('usercomp') != 1){
                ?>
<!--                    <div class="box box-info mt-2">-->
<!--                        <div class="box-header with-border">-->
<!--                            <h3 class="box-title">--><?php //= lang('application_my_campaigns'); ?><!--</h3>-->
<!--                        </div>-->
<!--                        <div class="box-body">-->
<!--                            <table id="myCampaigns" class="table table-bordered table-striped table-condensed">-->
<!--                                <thead>-->
<!--                                    <tr>-->
<!--                                        <th style="display: none;">--><?php //= lang('application_id'); ?><!--</th>-->
<!--                                        <th>--><?php //= lang('application_campaign_name'); ?><!--</th>-->
<!--                                        <th>--><?php //= lang('application_campaign_type'); ?><!--</th>-->
<!--                                        <th data-orderable="false">--><?php //= lang('application_marketplace_takes_over'); ?><!--</th>-->
<!--                                        <th data-orderable="false">--><?php //= lang('application_merchant_takes_over'); ?><!--</th>-->
<!--                                        <th>--><?php //= lang('application_start_date'); ?><!--</th>-->
<!--                                        <th>--><?php //= lang('application_end_date'); ?><!--</th>-->
<!--                                        <th>--><?php //= lang('application_deadline_for_joining_index'); ?><!--</th>-->
<!--                                        <th data-orderable="false">--><?php //= lang('application_campaign_status'); ?><!--</th>-->
<!--                                        <th data-orderable="false">--><?php //= lang('application_campaign_itens_selected_by_seller'); ?><!--</th>-->
<!--                                        <th data-orderable="false">--><?php //= lang('application_campaign_itens_approved_by_marketplace'); ?><!--</th>-->
<!--                                        <th data-orderable="false">--><?php //= lang('application_campaign_itens_not_approved_by_marketplace'); ?><!--</th>-->
<!--                                        --><?php
//                                        if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
//                                            ?>
<!--                                            <th data-orderable="false">--><?php //= lang('application_has_products_pending'); ?><!--</th>-->
<!--                                            --><?php
//                                        }
//                                        ?>
<!--                                        <th data-orderable="false" class="col-md-2">--><?php //= lang('application_action'); ?><!--</th>-->
<!--                                    </tr>-->
<!--                                </thead>-->
<!--                            </table>-->
<!--                        </div>-->
                        <!-- /.box-body -->
<!--                    </div>-->
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
                <h4 class="modal-title"><?=$this->lang->line('campaign_v2_campaign_modal_title_campaign_info')?></h4>
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
    var myCampaigns;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function()
    {
        $("#mainCampaignsNav").addClass('active');
        $("#manageCampaignsNav").addClass('active');

        filterCampaigns();

        $('#nav_campaigns_inactive').click(function()
        {
            if ($('#expiredCampaigns tbody tr').length < 1)
            {
                mountExpiredCampaigns();
            }
        });

        $('#nav_campaigns_mine').click(function()
        {
            if ($('#myCampaigns tbody tr').length < 1)
            {
                mountMyCampaigns();
            }
        });
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
                {"data": "start_date"},
                {"data": "end_date"},
                // {"data": "marketplace_takes_over"},
                // {"data": "merchant_takes_over"},
                // {"data": "deadline_for_joining"},
                {"data": "status"},
                <?php
                if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
                ?>
                // {"data": "download_selected_itens_by_seller"},
                // {"data": "download_approved_by_marketplace"},
                // {"data": "download_not_approved_by_marketplace"},
                // {"data": "has_products_pending"},
                <?php
                }
                ?>
                // {"data": "files"},
                {"data": "action", className: "text-right"}
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
            "autoWidth": true,
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
                // {"data": "marketplace_takes_over"},
                // {"data": "merchant_takes_over"},
                {"data": "start_date"},
                {"data": "end_date"},
                // {"data": "deadline_for_joining"},
                {"data": "status"},
                // {"data": "download_selected_itens_by_seller"},
                // {"data": "download_approved_by_marketplace"},
                // {"data": "download_not_approved_by_marketplace"},
                <?php
                if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
                ?>
                // {"data": "has_products_pending"},
                <?php
                }
                ?>
                // {"data": "files"},
                {"data": "action", className: "text-right"}
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

            // console.log('carregando mycampaigns');

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
                    // {"data": "marketplace_takes_over"},
                    // {"data": "merchant_takes_over"},
                    {"data": "start_date"},
                    {"data": "end_date"},
                    // {"data": "deadline_for_joining"},
                    {"data": "status"},
                    // {"data": "download_selected_itens_by_seller"},
                    // {"data": "download_approved_by_marketplace"},
                    // {"data": "download_not_approved_by_marketplace"},
                    <?php
                    if ($only_admin && $usercomp == 1 && !$this->session->userdata('userstore')){
                    ?>
                    // {"data": "has_products_pending"},
                    <?php
                    }
                    ?>
                    // {"data": "files"},
                    {"data": "action", className: "text-right"},
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
        // mountExpiredCampaigns();
        // mountMyCampaigns();

        return false;

    }

</script>
