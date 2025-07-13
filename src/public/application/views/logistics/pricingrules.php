<div class="content-wrapper">
	<?php
        $data['pageinfo'] = "application_manage";
        $this->load->view('templates/content_header', $data);
    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12" id="rowcol12">
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('success');?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('error');?>
                    </div>
                <?php endif;?>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-3">
                <label for="addr_uf">&nbsp;</label>
                <a href="<?php echo base_url('Shippingpricingrules/editrules') ?>" class="btn btn-primary"><?=$this->lang->line('application_shipping_price_new_rule');?></a>
            </div> 
        </div>

        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Regras de Precificação de Frete</h3>
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_id');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_shipping_companies');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_mkt_channels');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_price_range');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_date_created');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_date_enabled');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_date_disabled');?></th>
                                    <th><?=$this->lang->line('application_shipping_price_date_updated');?></th>
                                    <th><?=$this->lang->line('application_status');?></th>
                                    <th><?=$this->lang->line('application_action');?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">

var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {  
    $("#mainLogisticsNav").addClass('active');
    $("#shippingPricingRulesNav").addClass('active');
    loadTable();
});

function deleteRule(id)
{
    let rule_id = {
        'id': id
    };

    let url = `${base_url}Shippingpricingrules/deleteRule`;
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: rule_id,
        async: true,
        success: function(response) {
            console.log('Configurações salvas com sucesso.');
            loadTable();
        },
        error: function() {
            console.log('Erro ao tentar salvar as configurações.');
            Swal.fire('Erro ao tentar salvar as configurações.');
        },
    });
}

function toggleStatus(id)
{
    let toggle_id = {
        'id': id
    };

    let url = `${base_url}Shippingpricingrules/toggleStatus`;
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: toggle_id,
        async: true,
        success: function() {
            console.log('Configurações salvas com sucesso.');
            loadTable();
        },
        error: function() {
            console.log('Erro ao tentar salvar as configurações.');
            Swal.fire('Erro ao tentar salvar as configurações.');
        },
    });
}

function loadTable()
{
    $("#mainpPromoLogisticNav").addClass('active');
    $("#managepPromoLogisticNav").addClass('active');

    manageTable = $('#manageTable').DataTable({
        'paging': false,
        "destroy": true,
        "language": { 
            "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" 
        },
        'ajax': {
            "url": `${base_url}Shippingpricingrules/fetchPriceData`,
        },
        'providers': [],
        'fnDrawCallback': function(result) {
            $("input[data-bootstrap-switch]").each(function(result) {
                $(this).bootstrapSwitch();
            })
        },
        'columnDefs': [{
            "targets": '_all',
            "className": "text-center",
        }],       
    });
}
</script>
