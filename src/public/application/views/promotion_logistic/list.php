<?php
// Redirecionamento temporário, relativo à LOG-457.
redirect('dashboard', 'refresh');
?>

<div class="content-wrapper">	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>  
  <section class="content">
    <div clarr="row">
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
          <a href="<?php echo base_url('PromotionLogistic/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_promotion');?></a>
      </div> 
    </div>
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div class="box box-primary">
          <div class="box-header">                    
            <h3 class="box-title">Promoções</h3>
          </div>
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                      <th><?=$this->lang->line('application_id');?></th>
                      <th><?=$this->lang->line('application_name');?></th>
                      <th><?=$this->lang->line('application_start_date');?></th>
                      <th><?=$this->lang->line('application_end_date');?></th>
                      <th><?=$this->lang->line('application_date_create');?></th>
                      <th><?=$this->lang->line('application_inactive_date');?></th>
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
  $("#logisticPromotionAdminNav").addClass('active');
  onloadTable();  
});

function getInfoPromo(id)
{
  $.ajax({
      url: base_url+"PromotionLogistic/getPromoId/" + id,
      async: true,
      success: function(response) {
          var obj = JSON.parse(response);
          console.log(obj);          
              
      },
      error: function(jqXHR, textStatus, errorThrown) {
          console.log(jqXHR,textStatus, errorThrown);
      }
    });
}

function updateStatus(id,element)
{
    $.ajax({
        url: base_url+"PromotionLogistic/updateStatus",
        type: "POST",
        data: {
            idPromo: id,
            status: element.prop("checked") ? 1 : 0
        },
        async: true,
        success: function(response) {
            Toast.fire({
                icon: response.success ? 'success' : 'error',
                title: response.message
            });
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            $('#manageTable').DataTable().ajax.reload();
        }
    });
}

function deletePromo(id)
{
  if(confirm("Deseja realmente excluir promoção? \n As lojas que fazem parte da promoção não iram mais ver a mesma. \n Desativando a promoção ela não será mais listada no leilão.")){
    $.ajax({
      url: base_url+"PromotionLogistic/delete",
    	type: "POST",
        data: {
            idPromo: id            
        },
        async: true,
        success: function(response) {
            var obj = JSON.parse(response);
            // console.log(obj);
            if (("success" in obj)==true){obj.response = "success";}

            if (("error" in obj)==true){obj.response = "error";}

            if(obj.response == "success"){
                Toast.fire({
                    icon: 'success',
                    title: obj.message
                });
                $('#manageTable').DataTable().ajax.reload();

            } else if(obj.response == "error"){
                Toast.fire({
                    icon: 'error',
                    title: obj.message
                });
                $('#manageTable').DataTable().ajax.reload();
            }               
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
    $('#manageTable').DataTable().ajax.reload();
  }  
}


function onloadTable() {
    $("#mainpPromoLogisticNav").addClass('active');
    $("#managepPromoLogisticNav").addClass('active');

    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        'paging': false,
        "destroy": true,
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
        'ajax': {
            "url": base_url + 'PromotionLogistic/fetchPromoData',
        },
       // "type": "GET",        
        'providers': [],
        'fnDrawCallback': function(result){
            $("input[data-bootstrap-switch]").each(function(result){
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
