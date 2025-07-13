<link rel="stylesheet" type="text/css" href="<?=base_url()?>/assets/dist/css/views/campaign_v2/styles.css">
<style>
    .wrapper{
        overflow: hidden;
    }
</style>
<div class="content-wrapper">

    <?php

	$admin_role = (!$this->session->userdata('userstore') && $this->data['only_admin'] && $this->data['usercomp'] == 1) ? true : false;

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

                <div class="row mt-4">
                    <div class="col-md-8 my-4 comparison">
<!--                        Comparando <strong>últimos 30 dias</strong>-->
                    </div>

                    <div class="col-md-4 text-right align-middle pt-4">
<!--                        <label class="btn btn-wider-1 btn-outline-primary" id="show-filters" style="display: none;">-->
<!--                            <i class="fa fa-filter"></i>&nbsp-->
<!--                            <span>-->
<!--                            --><?php //= lang('campaigns_v2_dashboard_btn_showfilters_off'); ?>
<!--                            </span>-->
<!--                        </label>-->
                    </div>
                </div>


                <style>
                    .dropdown-toggle{
                        min-width: 10vw;
                        border: 1px solid #ccc;
                        padding: 16px;
                    }

                    .filter-input-text{
                        border: 1px solid #ccc;

                    }
                </style>
                <!-- filters -->
                <div class="box mt-4" id="filters-row" style="display: none;">

                    <div class="box-body">

                        <div class="row p-5">

                            <table class="mx-3" style="width: 100%">
                                <tr>
                                    <td class="px-3">
                                        <label for="stores"><?=$this->lang->line('application_participating_stores22');?>Busca por Lojas</label>
                                        <br/>
                                        <select class="form-control-sm selectpicker show-tick mr-3" data-live-search="true" data-actions-box="true" id="stores" multiple="multiple">
											<?php
											foreach ($array_stores as $store){
												?>
                                                <option value='<?php echo $store['store_id']; ?>'><?php echo $store['name']; ?></option>
												<?php
											}
											?>
                                        </select>
                                    </td>
                                    <td class="px-3">
                                        <label for="stores2"><?=$this->lang->line('application_participating_stores222');?>Busca por Campanha</label>
                                        <br/>
                                        <select class="form-control-sm selectpicker show-tick mr-3" data-live-search="true" data-actions-box="true" id="stores2" multiple="multiple">
											<?php
											foreach ($array_stores as $store){
												?>
                                                <option value='<?php echo $store['store_id']; ?>'><?php echo $store['name']; ?></option>
												<?php
											}
											?>
                                        </select>
                                    </td>
                                    <td>
                                        <table>
                                            <tr>
                                                <td colspan="3">
                                                    <label for="stores3"><?=$this->lang->line('application_participating_stores222');?>Selecione um período</label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <select class="form-control-sm selectpicker show-tick mr-4" data-live-search="true" data-actions-box="true" id="stores3" multiple="multiple">
														<?php
														foreach ($array_stores as $store){
															?>
                                                            <option value='<?php echo $store['store_id']; ?>'><?php echo $store['name']; ?></option>
															<?php
														}
														?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="date"  class="form-control-sm text-center form-control-condensed-narrow filter-input-text" size="10" placeholder="--/--/----">
                                                </td>
                                                <td class="px-3">
                                                    <?=$this->lang->line('campaigns_v2_dashboard_date_to_date')?>
                                                </td>
                                                <td>
                                                    <input type="date"  class="form-control-sm text-center form-control-condensed-narrow filter-input-text" size="10" placeholder="--/--/----">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table>
                                            <tr>
                                                <td colspan="3">
                                                    <label for="stores4"><?=$this->lang->line('application_participating_stores222');?>Compare com</label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <select class="form-control-sm form-control-condensed selectpicker show-tick mr-4" data-live-search="true" data-actions-box="true" id="stores4" multiple="multiple">
														<?php
														foreach ($array_stores as $store){
															?>
                                                            <option value='<?php echo $store['store_id']; ?>'><?php echo $store['name']; ?></option>
															<?php
														}
														?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="date" class="form-control-sm text-center form-control-condensed-narrow filter-input-text" size="10" placeholder="--/--/----">
                                                </td>
                                                <td class="px-3">
													<?=$this->lang->line('campaigns_v2_dashboard_date_to_date')?>
                                                </td>
                                                <td>
                                                    <input type="date" class="form-control-sm text-center form-control-condensed-narrow filter-input-text" size="10" placeholder="--/--/----">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <div class="col-md-12 text-right form-control-condensed2222">
                                            <label>&nbsp&nbsp;</label>
                                            <br/>
                                            <button class="btn btn-wider-12 btn-outline-primary">
                                                <i class="fa fa-search"></i>&nbsp;
												<?= lang('application_search'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                        </div>

                    </div>

                </div>



                <!-- cards -->

                <style>

                    .campaign-dashboard-cards{
                    <?php
                        if (!$admin_role)
                            {
                                echo 'grid-template-columns: repeat(4, 1fr);';
                            }
                     ?>
                    }
                </style>


                <div class="campaign-dashboard-cards mt-5 mb-3">



                        <a href="<?=base_url()?>campaigns_v2" target="_blank" class="campaign-dashboard-card">
                            <div class="col-md-3">
                                <i class="fa fa-money icon"></i>
                            </div>
                            <div class="col-md-9 pl-2">
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_activecampaigns'); ?></div>
                                <div class="value"><?=$card_active_campaigns?></div>
                            </div>
                        </a>

                        <div class="campaign-dashboard-card">
                            <div class="col-md-3">
                                <i class="fa fa-filter icon"></i>
                            </div>
                            <div class="col-md-9 pl-2">
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_endthismonth'); ?></div>
                                <div class="value"><?=$card_end_this_month?></div>
                            </div>
                        </div>

					<?php
					    if ($admin_role):
					?>
                        <div class="campaign-dashboard-card">
                            <div class="col-md-3">
                                <i class="fa fa-eye icon"></i>
                            </div>
                            <div class="col-md-9 pl-2">
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_adhesionrate'); ?></div>
                                <div class="value"><?=$card_adherence?>%</div>
                            </div>
                        </div>
                    <?php
						endif;
					?>

                        <div class="campaign-dashboard-card">
                            <div class="col-md-3">
                                <i class="fa fa-hand-pointer-o icon"></i>
                            </div>
                            <div class="col-md-9 pl-2">
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_products'); ?></div>
                                <div class="value"><?=preg_replace('/([\d]{1,})([\d]{3})/','$1.$2',$card_products);?></div>
                            </div>
                        </div>

                    <?php
                        if ($admin_role):
                    ?>
                        <div class="campaign-dashboard-card">
                            <div class="col-md-3">
                                <i class="fa fa-line-chart icon"></i>
                            </div>
                            <div class="col-md-9 pl-2">
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_pendingapproval'); ?></div>
                                <div class="value"><?=$card_approval?></div>
                            </div>
                        </div>
					<?php
					    endif;
					?>

                    <a href="<?=base_url()?>campaigns_v2/revenues" target="_blank" class="campaign-dashboard-card">
                            <div class="col-md-3">
                                <i class="fa fa-eye icon"></i>
                            </div>
                            <div class="col-md-9 pl-2">
                                <div class="title"><?= $this->lang->line('campaigns_v2_dashboard_cards_revenue'); ?></div>
                                <div class="value"><?=money($card_revenue)?></div>
                            </div>
                    </a>

                </div>


                <div class="campaign-dashboard-graphs mt-5">

                    <div class="title">
                        <h3><?= $this->lang->line('campaigns_v2_dashboard_graphs_title'); ?></h3>
                    </div>
                    <div class="title">
                        <h3><?= $this->lang->line('campaigns_v2_dashboard_campaigns_list_title'); ?></h3>
                    </div>


                    <div class="campaign-dashboard-graph g-2">

                        <div class="col-md-12 graph p-3">
                            <img src="<?=base_url()?>/assets/images/campaign_v2/tutorial_shopping.png" alt="" width="100%" height="300px">
                        </div>


                        <div class="col-md-6 mt-4 px-0">
                            <div class="graph mr-3 p-3">
                                <img src="<?=base_url()?>/assets/images/campaign_v2/tutorial_shopping.png" alt="" width="100%" height="300px">
                            </div>
                        </div>
                        <div class="col-md-6 mt-4 px-0">
                            <div class="graph ml-3 p-3">
                                <img src="<?=base_url()?>/assets/images/campaign_v2/tutorial_shopping.png" alt="" width="100%" height="300px">
                            </div>
                        </div>

                        <div class="col-md-12 graph mt-4" p-3>
                            <img src="<?=base_url()?>/assets/images/campaign_v2/tutorial_shopping.png" alt="" width="100%" height="300px">
                        </div>

                    </div>

                    <div class="campaign-dashboard-graph list px-4 pt-5">

                        <table class="mt-1 ml-3">
                            <tr>
                                <td>
                                    <a href="<?=base_url()?>campaigns_v2" class="btn btn-outline-primary">
                                        <span><?= $this->lang->line('campaigns_v2_dashboard_campaigns_list_btn_all'); ?></span>
                                    </a></td>
                                <td>
                                    <a href="<?=base_url()?>campaigns_v2/newcampaign" class="btn btn-primary ml-5">
                                        <span><?= $this->lang->line('campaigns_v2_dashboard_campaigns_list_btn_new'); ?></span>
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <div id="campaigns-list" class="py-5">
                            <div class="campaign-list-item">
							    <?= $this->lang->line('campaigns_v2_dashboard_campaigns_list_loading'); ?>
                            </div>
                        </div>

                    </div>


                </div>

            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->


<!--braun hack -> remover modal caso nao tenha uso-->
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


<script>

    var base_url = "<?php echo base_url(); ?>";
    var show_filters_off = '<?=$this->lang->line('campaigns_v2_dashboard_btn_showfilters_off')?>';
    var show_filters_on = '<?=$this->lang->line('campaigns_v2_dashboard_btn_showfilters_on')?>';
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

    $(document).ready(function ()
    {
        $("#mainCampaignsNav").addClass('active');
        $("#DashboardCampaignsNav").addClass('active');

        mountActiveCampaignsList();

        $('#show-filters').fadeIn('slow');

        $('#show-filters').click(function()
        {
            if ($('#filters-row').is(":visible"))
            {
                $('#filters-row').slideUp();
                $(this).find('span').text(show_filters_off);
            }
            else
            {
                $('#filters-row').slideDown();
                $(this).find('span').text(show_filters_on);
            }
        });

        var graphs_height = $('.campaign-dashboard-graph').height() - 100;
        // console.log(graphs_height);
        $('#campaigns-list').css({'max-height' : graphs_height + 'px'});


    });

    function mountActiveCampaignsList()
    {

        var send_data = {
                "startDate" : '',
                "endDate" : '',
                'dashboard_list': 1
            }

        <?php
            if (!$admin_role)
			{
                echo '                
                send_data.revenue = true;
                send_data.seller_type = seller_type;
                ';
            }

        ?>

        console.log(send_data);

        $.post(
            base_url + 'campaigns_v2/active_campaigns/',
            send_data,
            function(data)
            {
                console.log(data);

                if (Array.isArray(data.data))
                {
                    // console.log(data.data);

                    $('#campaigns-list').html('');

                    data.data.forEach(function(k,v)
                    {
                        var campaign_line = '';

                        if (typeof k === 'object')
                        {
                            campaign_line += '<a href="<?=base_url()?>campaigns_v2/products/'+ k.id +'" target="_blank"><h4 class="title">' + k.name + '</h4></a>';

                            if (k.start_date != '' || k.end_date != '')
                            {
                                campaign_line += '<div class="end-date">';
                            }
                            if (k.start_date != '')
                            {
                                campaign_line += '<?php echo lang('campaigns_v2_dashboard_campaigns_list_date_start'); ?>: <span class="blue">'+ k.start_date.split(' ')[0] +'</span>' ;
                            }

                            if (k.end_date != '')
                            {
                                campaign_line += ' - <?php echo lang('campaigns_v2_dashboard_campaigns_list_date_end'); ?> <span class="blue">'+ k.end_date.split(' ')[0]+'</span>';
                            }

                            if (k.start_date != '' || k.end_date != '')
                            {
                                campaign_line += '</div>';
                            }

                            if (k.campaign_type != '')
                            {
                                campaign_line += '<div class="type"><?php echo lang('campaigns_v2_dashboard_campaigns_list_campaign_type'); ?>: '+ k.campaign_type + '</div>';
                            }

                            if (k.marketplace_takes_over != '' || k.merchant_takes_over != '')
                            {
                                campaign_line += '<div class="type">';
                            }

                            if (k.marketplace_takes_over != '')
                            {
                                var mktplace = '<?php echo lang('campaigns_v2_dashboard_campaigns_list_mkt_takes'); ?>: <span class="blue">'+ k.marketplace_takes_over + '</span>';
                                campaign_line += mktplace.replace('<a', '<a target=_blank ');
                            }

                            if (k.merchant_takes_over != '')
                            {
                                var merchant = ' - <?php echo lang('campaigns_v2_dashboard_campaigns_list_seller_takes'); ?>: <span class="blue">'+ k.merchant_takes_over + '</span>';
                                campaign_line += merchant.replace('<a', '<a target=_blank ');
                            }

                            if (k.marketplace_takes_over != '' || k.merchant_takes_over != '')
                            {
                                campaign_line += '</div>';
                            }

                           // console.log(campaign_line);
                            // campaign_line.replace(campaign_line, '<a', '<a target=_blank ');
                        }

                        $('#campaigns-list').append('<div class="campaign-list-item">'+ campaign_line + '</div>');

                    });
                }
                else
                {
                    $('#campaigns-list').html('<?php echo lang('campaigns_v2_dashboard_campaigns_list_empty'); ?>');
                }
            }

        );

        return false;
        // if ($('#activeCampaigns').length) {
        //     $('#activeCampaigns').DataTable().destroy();
        // }

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


</script>
