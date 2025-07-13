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
      <div class="col-md-12 col-xs-12">
        <div class="box">
          <div class="box-header">                    
            <h3 class="box-title">Minhas Promoções</h3>
          </div>
          <div class="box-body">
            <table id="manageTableSeller" class="table table-bordered table-striped">
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

    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div class="box">
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

<!-- Modal -->
<div class="modal fade" id="modal_promotion_logitic_getinfo" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Informações Promoção</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>        
      </div>
    </div>
  </div>
</div>


<script type="text/javascript">

var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {  
  $("#mainLogisticsNav").addClass('active');
  $("#logisticPromotionNav").addClass('active');
  onloadTable();  
  onloadTableSeller();  
});


function setStorePromotionLogistic(id_promo)
{
	$.ajax({
        url: base_url+"PromotionLogistic/setStorePromotionLogistic",
    	type: "POST",
        data: { id_promo },
        async: true,
        success: function(response) {
            Toast.fire({
                icon: response.success ? 'success' : 'error',
                title: response.message
            });
            $('#manageTable').DataTable().ajax.reload();
            $('#manageTableSeller').DataTable().ajax.reload();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

function getInfo(idPromo)
{
    $('#modal_promotion_logitic_getinfo .modal-body')
    .html('')
    .load(`${base_url}PromotionLogistic/getPromoId/${idPromo}`, function(responseTxt, statusTxt, xhr){
        console.log(responseTxt, statusTxt, xhr);
        if(statusTxt === "error"){
            console.log("Error: " + xhr.status + ": " + xhr.statusText);
        } else {
            $('#modal_promotion_logitic_getinfo').modal('show');
        }
    });
}
$(document).on('click', '.exit-promotion', function() {
    const id_promo = $(this).data('promo-id');
    const namePromotion = manageTableSeller.row( $(this).closest('tr') ).data()[1];

    Swal.fire({
        title: 'Sair da Promoção',
        html: `Você está prestes a sair definitivamente da promoção <b>${namePromotion}</b>.<br><br>Deseja continuar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#bbb',
        confirmButtonText: 'Sim, sair',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.value === true) {
            $.ajax({
                url: base_url+"PromotionLogistic/setInactivePromotionLogisticStore",
                type: "POST",
                data: { id_promo },
                async: true,
                success: function(response) {
                    Toast.fire({
                        icon: response.success ? 'success' : 'erro',
                        title: response.message
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR,textStatus, errorThrown);
                    Toast.fire({
                        icon: 'erro',
                        title: 'Ocorreu um erro inesperado, tente novamente mais tarde!'
                    });
                },
                complete: function(xhr) {
                    $('#manageTable').DataTable().ajax.reload();
                    $('#manageTableSeller').DataTable().ajax.reload();
                }
            });
        }
    });
});

function onloadTableSeller() {
    $("#mainpPromoLogisticNav").addClass('active');
    $("#managepPromoLogisticNav").addClass('active');

    // initialize the datatable
    manageTableSeller = $('#manageTableSeller').DataTable({
        'paging': false,
        "destroy": true,
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
        'ajax': {
            "url": base_url + 'PromotionLogistic/fetchPromoDataToSeller',
        },
        "initComplete": () => {
            $('[data-target="tooltip"]').tooltip();
        }
    });
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
            "url": base_url + 'PromotionLogistic/fetchPromoDataSeller',
        },
        "initComplete": () => {
            $('[data-target="tooltip"]').tooltip();
        }
    });
}
</script>
