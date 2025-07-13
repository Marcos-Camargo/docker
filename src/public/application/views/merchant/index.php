<!--
-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
            <?php if ($this->session->flashdata('success') !== 'create_success' && $this->session->flashdata('success') !== 'update_success'): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
        
        <?php elseif($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>
        <div class="box box-primary" id="collapseFilter">
          <div class="box-body">
            <h4 class="mt-0">Filtros</h4>
            <div class="col-md-4 form-group no-padding">
              <div class="input-group">
                <input type="search" id="busca_cnpj" class="form-control" placeholder="CNPJ" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
            <div class="col-md-8 form-group">
              <div class="input-group">
                <input type="search" id="busca_razao_social" class="form-control" placeholder="Razão Social" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
            <div class="col-md-6 form-group no-padding">
              <div class="input-group">
                <input type="search" id="busca_nome_fantasia" class="form-control" placeholder="Nome Fantasia" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
            <div class="col-md-6 form-group">
              <div class="input-group">
                <input type="search" id="busca_ramo_atividade" class="form-control" placeholder="Ramo de Atividade" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
            <div class="col-md-2 form-group no-padding">
              <div class="input-group">
                <input type="search" id="busca_estado" class="form-control" placeholder="Estado" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
            <div class="col-md-6 form-group">
              <div class="input-group">
                <input type="search" id="busca_cidade" class="form-control" placeholder="Cidade" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
            
            <button type="button" onclick="clearFilters()" class="pull-right btn btn-primary" style="margin-right: 5px;"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear'); ?> </button>
          </div>
        </div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed"  style="border-collapse: collapse; width: 99%; border-spacing: 0; ">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_merchant_company_name');?></th>
                <th><?=$this->lang->line('application_merchant_branch');?></th>
                <th><?=$this->lang->line('application_merchant_size');?></th>
                <th><?=$this->lang->line('application_merchant_uf');?></th>
                <th><?=$this->lang->line('application_merchant_city');?></th>
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

<?php if(in_array('viewMerchant', $user_permission)): ?>
<!-- edit merchant modal -->
<div class="modal fade in" tabindex="-1" role="dialog" id="viewMerchantModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" onclick="hideModal()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_merchant_detail_seller');?></h4>
      </div>
      <div class="box-body">
        <div class="col-md-4 form-group">
          <label for="view_detail_cnpj"><?=$this->lang->line('application_cnpj');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_cnpj');?>" id="view_detail_cnpj" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-8 form-group">
          <label for="view_detail_razao_social"><?=$this->lang->line('application_raz_soc');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_raz_soc');?>" id="view_detail_razao_social" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-12 form-group">
          <label for="view_detail_nome_fantasia"><?=$this->lang->line('application_fantasy_name');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_fantasy_name');?>" id="view_detail_nome_fantasia" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-12 form-group">
          <label for="view_detail_natureza_juridica"><?=$this->lang->line('application_merchant_natureza_juridica');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_merchant_natureza_juridica');?>" id="view_detail_natureza_juridica" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-2 form-group">
          <label for="view_detail_estado"><?=$this->lang->line('application_uf');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_uf');?>" id="view_detail_estado" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-6 form-group">
          <label for="view_detail_city"><?=$this->lang->line('application_city');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_city');?>" id="view_detail_city" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-4 form-group">
          <label for="view_detail_bairro"><?=$this->lang->line('application_neighb');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_neighb');?>" id="view_detail_bairro" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-10 form-group">
          <label for="view_detail_logradouro"><?=$this->lang->line('application_logradouro');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_logradouro');?>" id="view_detail_logradouro" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-2 form-group">
          <label for="view_detail_number"><?=$this->lang->line('application_number');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_number');?>" id="view_detail_number" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-6 form-group">
          <label for="view_detail_email"><?=$this->lang->line('application_email');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_email');?>" id="view_detail_email" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-6 form-group">
          <label for="view_detail_phone"><?=$this->lang->line('application_phone');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_phone');?>" id="view_detail_phone" aria-describedby="basic-addon1" readonly>
        </div>
        <div class="col-md-6 form-group">
          <label for="view_detail_dt_alteracao"><?=$this->lang->line('application_dt_alteracao');?></label>
          <input class="form-control" placeholder="<?=$this->lang->line('application_dt_alteracao');?>" id="view_detail_dt_alteracao" aria-describedby="basic-addon1" readonly>
        </div>
      </div>
      <div class="modal-footer">
          <button type="button" class="btn btn-default" onclick="hideModal()"><?=$this->lang->line('application_close');?></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

$(document).ready(function() {

  <?php if ($this->session->flashdata('success') === 'create_success'): ?>
      Swal.fire({
          icon: 'success',
          title: '<?=$this->lang->line('messages_successfully_created')?>'
      })
  <?php endif; ?>

  <?php if ($this->session->flashdata('success') === 'update_success'): ?>
      Swal.fire({
          icon: 'success',
          title: '<?=$this->lang->line('messages_successfully_updated')?>'
      })
  <?php endif; ?>
  
  $("#MerchantNav").addClass('active');

	fetchMerchantData({
  	  ramo: '',
		  nomeFantasia: '',
		  razaoSocial: '',
		  cnpj: '',
		  estado: '',
		  cidade: ''
	});

});

function fetchMerchantData(data) {
   var body = { ...data }	
   manageTable = $('#manageTable').DataTable({
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "sortable": true,
        "searching": true,
        "scrollX": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'merchants/fetchMerchantData',
            pages: 2, // number of pages to cache
            // data: { [csrfName]: csrfHash }
            data: body
        }),
        "columns": [
            { data: 'company_name'}, 
            { data: 'cna' }, 
            { data: 'company_size' }, 
            { data: 'uf' }, 
            { data: 'city_name' }, 
            { 
                data: null, 
                render: function(data, type, row) { 
                    return '<button type="button" class="btn btn-default" onclick="viewDetailMerchant(\''+ data.cnpj.trim() +'\')"><i class="fa fa-eye"></i></button>';
                } 
            }
        ]
    });
}

function personalizedSearch() {
    let ramo = $('#busca_ramo_atividade').val();
    let nomeFantasia = $('#busca_nome_fantasia').val();
    let razaoSocial = $('#busca_razao_social').val();
    let cnpj = $('#busca_cnpj').val();
    let estado = $('#busca_estado').val();
    let cidade = $('#busca_cidade').val();
    
    manageTable.destroy();
    fetchMerchantData({ ramo, nomeFantasia, razaoSocial, cnpj, estado, cidade });
}

function clearFilters() {
    $('#busca_ramo_atividade').val('');
    $('#busca_nome_fantasia').val('');
    $('#busca_razao_social').val('');
    $('#busca_cnpj').val('');
    $('#busca_estado').val('');
    $('#busca_cidade').val('');

    personalizedSearch({});
}

function hideModal() {
    $('#manageTable_processing').hide();
    $('#viewMerchantModal').hide();
}

function viewDetailMerchant(cnpj)
{ 
    $('#viewMerchantModal').hide();
    $('#manageTable_processing').show();
    $('#viewMerchantForm button[type="submit"]').prop('disabled', true);
    $("#view_detail_cnpj").val('');
    $("#view_detail_razao_social").val('');
    $("#view_detail_nome_fantasia").val('');
    $("#view_detail_natureza_juridica").val('');
    $("#view_detail_estado").val('');
    $("#view_detail_city").val('');
    $("#view_detail_bairro").val('');
    $("#view_detail_logradouro").val('');
    $("#view_detail_number").val('');
    $("#view_detail_email").val('');
    $("#view_detail_phone").val('');
    $("#view_detail_dt_alteracao").val('');

    var dataJson = { [csrfName]: csrfHash};

    $.ajax({
        url: base_url + 'merchants/get/' + cnpj,
        dataType: 'json',
        data: dataJson,  
        success:function(response) {
            $('#manageTable_processing').hide();
            if (typeof response !== 'object' ) {
              response = JSON.parse(response);
            }
            $("#view_detail_cnpj").val(response.cnpj);
            $("#view_detail_razao_social").val(response.razao_social);
            $("#view_detail_nome_fantasia").val(response.nome_fantasia);
            $("#view_detail_natureza_juridica").val(response.cna);
            $("#view_detail_estado").val(response.uf);
            $("#view_detail_city").val(response.municipio);
            $("#view_detail_bairro").val(response.bairro);
            $("#view_detail_logradouro").val(response.logradouro);
            $("#view_detail_number").val(response.numero);
            $("#view_detail_email").val(response.email);
            $("#view_detail_phone").val(response.telefone);
            $("#view_detail_dt_alteracao").val(response.ultima_atualizacao);

            $('#viewMerchantModal').show();
        }
    });
}

</script>
