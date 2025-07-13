<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">
 
    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12" id="rowcol12">

                    <?php if($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('success'); ?>
                        </div>
                    <?php elseif($this->session->flashdata('error')): ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>
                    <?php //if(in_array('createShippingRules', $user_permission)): ?>                                            
                        <a href="<?=base_url('shippingrules/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_rules_add');?></a>
                        <br /> <br />
                    <?php // endif; ?>                   
                    <div class="box">
                        <div class="box-body">
                        <div id="console-event"></div>                        
                            <table id="manageTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th><?=$this->lang->line('application_rules_priority');?></th>
                                        <th><?=$this->lang->line('application_rules_name');?></th>
                                        <th><?=$this->lang->line('application_rules_conditions');?></th>                                    
                                        <th><?=$this->lang->line('application_rules_action');?></th>
                                        <th><?=$this->lang->line('application_rules_status');?></th>
                                        <th><?=$this->lang->line('application_rules_dt_update');?></th>
                                        <th><?=$this->lang->line('application_rules_dt_create');?></th>
                                        <th><?=$this->lang->line('application_rules_dt_validated_start');?></th>
                                        <th><?=$this->lang->line('application_rules_dt_validated_end');?></th>
                                        <th><?=$this->lang->line('application_action');?></th> 
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                </div>
                <!-- col-md-12 -->
            </div>
            <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">
var manageTable;
var base_url = "<?=base_url(); ?>";

function updateStatus(idTransportadora, element) {
    
	$.ajax({
        url: base_url+"shippingcompany/updateStatusShippingCompany",
    	type: "POST",
        data: {
            id_transportadora: idTransportadora,            
            status: element.prop("checked") ? 1 : 0
        },
        async: true,
        success: function(response) {
            var obj = JSON.parse(response);
            Toast.fire({
                icon: 'success',
                title: obj.message
            });
        }, 
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

$(document).ready(function() {

    $("#mainShippingCompanyNav").addClass('active');
    $("#manageShippingCompanyNav").addClass('active');

    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
        'ajax': base_url + 'shippingrules/fetchShippingRulesData',
        'providers': [],
    });

});

</script>

