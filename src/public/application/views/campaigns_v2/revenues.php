<link rel="stylesheet" type="text/css" href="<?=base_url()?>/assets/dist/css/views/campaign_v2/styles.css">
<?php

    $admin_role = (!$this->session->userdata('userstore') && $this->data['only_admin'] && $this->data['usercomp'] == 1) ? true : false;

?>
<style>
    body{
        color: #000;
    }
</style>
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




                <div class="campaign-revenues-card mt-3">
                    <table>
                        <tr>
                            <td>
                                <i class="fa fa-eye icon"></i>
                            </td>
                            <td>
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_revenue'); ?>: <span class="value"><?=money($total_revenues)?></span></div>
                            </td>
                        </tr>
                    </table>
                </div>




                <div class="box mt-4 pb-5">

                    <div class="box-header" style="padding-bottom: 0;">

                        <div class="col-md-12">
                            <ul class="nav nav-tabs mt-5" role="tablist" id="store-tabs">

								<?php
								if ($admin_role):
									?>
                                    <li class="active" role="presentation" ><a id="nav_campaigns_marketplace" class="nav-item nav-link" href="#campaigns_mktplace"  data-toggle="tab"><?=$this->lang->line('campaigns_v2_revenues_tab_mktplace')?></a></li>
								<?php
								endif;
								?>

                                <li <?=(!$admin_role) ? 'class="active"' : '';?> role="presentation" ><a id="nav_campaigns_seller" class="nav-item nav-link" href="#campaigns_seller" data-toggle="tab"><?php
                                    if ($admin_role)
									{
										echo $this->lang->line('campaigns_v2_revenues_tab_seller');
									}
                                    else
                                    {
										echo $this->lang->line('application_my_campaigns');
                                    }
                                ?></a></li>
                            </ul>
                        </div>

                    </div>


                    <div class="tab-content campaign-tab-content"  style="border: 0; margin: 0 25px; padding-top: 30px;">


						<?php
						if ($admin_role):
						?>
                            <div class="tab-pane fade in active" id="campaigns_mktplace" role="tabpanel" >

<!--                                <div class="">-->
                                    <table id="activeCampaigns" class="table table-bordered table-striped table-condensed">
                                        <thead>
                                            <tr class="list-item-blue">
                                                <th style="display: none;"><?= lang('application_id'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_name'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_date_start'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_date_end'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_status'); ?></th>
                                                <th><?= lang('campaigns_v2_manage_list_th_type'); ?></th>
                                                <th>Marketplace</th>
                                                <th>Seller</th>
                                                <th>Receita Total</th>
                                                <th data-orderable="false"><? //= lang('application_action'); ?></th>
                                            </tr>
                                        </thead>
                                    </table>
<!--                                </div>-->

                            </div><!-- aba de ativas -->
						<?php
						endif;
						?>


                        <div class="tab-pane fade <?=(!$admin_role) ? 'in active' : ''?>" id="campaigns_seller" role="tabpanel">


                                <table id="expiredCampaigns" class="table table-bordered table-striped table-condensed" style="width: 100%;">
                                    <thead>
                                        <tr>
                                        <tr class="list-item-blue">
                                            <th style="display: none;"><?= lang('application_id'); ?></th>
                                            <th><?= lang('campaigns_v2_manage_list_th_name'); ?></th>
                                            <th><?= lang('campaigns_v2_manage_list_th_date_start'); ?></th>
                                            <th><?= lang('campaigns_v2_manage_list_th_date_end'); ?></th>
                                            <th><?= lang('campaigns_v2_manage_list_th_status'); ?></th>
                                            <th><?= lang('campaigns_v2_manage_list_th_type'); ?></th>
                                            <th>Marketplace</th>
                                            <th>Seller</th>
                                            <th>Receita Total</th>
                                            <th data-orderable="false"><? //= lang('application_action'); ?></th>
                                        </tr>
                                        </tr>
                                    </thead>
                                </table>


                        </div><!-- aba de inativas -->

                    </div><!-- container das abas -->

                </div><!-- container das abas -->


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

        showRevenue();

        $('#nav_campaigns_mktplace').click(function()
        {
            if ($('#activeCampaigns tbody tr').length < 1)
            {
                mountRevenueDataTable(0);
            }
        });

        $('#nav_campaigns_seller').click(function()
        {
            if ($('#expiredCampaigns tbody tr').length < 1)
            {
                mountRevenueDataTable(1);
            }
        });

        // $('#nav_campaigns_mine').click(function()
        // {
        //     if ($('#myCampaigns tbody tr').length < 1)
        //     {
        //         mountMyCampaigns();
        //     }
        // });
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

    function mountRevenueDataTable(seller_type) {

        // if ($('#activeCampaigns').length) {
        //     $('#activeCampaigns').DataTable().destroy();
        // }

        // var current_tab     = 'campaigns_marketplace';
        var current_table   = 'activeCampaigns';

        if (seller_type > 0)
        {
            current_table = 'expiredCampaigns';
        }

        if ($('#' + current_table).length)
        {
            $('#' + current_table).DataTable().destroy();
        }

        // initialize the datatable
        myCampaigns = $('#'+ current_table).DataTable({
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
                "url": base_url + 'campaigns_v2/active_campaigns',
                "type": 'POST',
                // dataType : "json",
                "data": {
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                    'revenue' : true,
                    'seller_type': seller_type,
                },
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id", "class" : 'hidden'},
                {"data": "name", "class" : "list-item-blue"},
                {"data": "start_date",
                    render: function (data)
                    {
                        var date_splitted = data.split(' ');
                        return date_splitted[0] + '<br>' + date_splitted[1];
                    },
                },
                {"data": "end_date",
                    render: function (data)
                    {
                        var date_splitted = data.split(' ');
                        return date_splitted[0] + '<br>' + date_splitted[1];
                    },
                },
                {"data": "active"},
                {"data": "campaign_type"},
                {"data": "marketplace_share", "class": "list-item-blue font-weight-bold"},
                {"data": "seller_share", "class": "list-item-blue font-weight-bold"},
                {"data": "total_revenue",
                    render: function (data)
                    {
                        return '<strong style="color: #28A745;">' + data + '</strong>';
                    },
                },
                {"data": "action", className: "text-right"}
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

        if (seller_type == 0)
        {
            // activeCampaigns = current_data_table;
        }
        else
        {
            // expiredCampaigns = current_data_table;
        }
    }


    function showRevenue()
    {
        var seller_type = <?php

			if ($admin_role)
			{
				echo 0; //mktplace
			}
			else if (!$this->session->userdata('userstore') || $this->session->userdata('usercomp') == 1)
			{
				echo 2; //company
			}
			else
			{
				echo 1; //seller
			}
			?>;

        // console.log(seller_type);

        mountRevenueDataTable(seller_type);

        return false;
    }

</script>
