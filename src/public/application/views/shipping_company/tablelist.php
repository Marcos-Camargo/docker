<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
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
                <div class="box box-primary">
                    <div class="box-body">
                        <a href="<?=base_url('shippingcompany/tableconfig/'.$shipping_company_id.'/'.$store_id.'') ?>" class="btn btn-primary col-md-3 pull-right"><?=$this->lang->line('application_add_new_tableshipping_company');?></a>
                    </div>
                </div>
                <div class="box box-primary">
                    <div class="box-body">
                       <div id="console-event"></div>
                        <table id="manageTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th class="text-center"><?=$this->lang->line('application_status');?></th>
                                    <th class="text-center"><?=$this->lang->line('application_id');?></th>
                                    <th class="text-center"><?=$this->lang->line('application_name');?></th>
                                    <th class="text-center"><?=$this->lang->line('application_shipping_table_dt_fim');?></th>
                                    <th class="text-center"><?=$this->lang->line('application_shipping_table_dt_create');?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="box box-primary">
                    <div class="box-body">
                        <a href="<?=base_url('shippingcompany/') ?>" class="btn btn-warning col-md-3"><?=$this->lang->line('application_back');?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
var manageTable;
var base_url = "<?=base_url(); ?>";
var shipping_company_id = <?=$shipping_company_id?>;
var store_id = <?=$store_id?>;

function updateStatusTable(idFile, status) {
    $.ajax({
        url: base_url+"shippingcompany/tablestatus",
    	type: "POST",
        data: {
            idFile,
            status
        },
        async: true,
        success: function(response) {
            location.reload();
        }, 
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

$(document).ready(function() {

    $("#mainLogisticsNav").addClass('active');
    $("#carrierRegistrationNav").addClass('active');

    let url = `${base_url}shippingcompany/tablelist/${shipping_company_id}`;
    if (store_id) {
        url = `${url}/${store_id}`
    }

    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
        'ajax': url,
        'columnDefs': [{
            "targets": '_all',
            "className": "text-center",
        }],       
    });

});

</script>

