<!--
SW Serviços de Informática 2019

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
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>


        <div class="box">
          <form role="form" action="<?php base_url('asStore/change') ?>" method="post">
            <div class="box-header with-border">
              <h3 class="box-title"><?=$this->lang->line('application_change_store');?></h3>
            </div>
            <div class="box-body">

              <?php
              if (validation_errors()) {
                foreach (explode("</p>",validation_errors()) as $erro) {
                  $erro = trim($erro);
                  if ($erro!="") { ?>
                  <div class="alert alert-danger alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <?php echo $erro."</p>"; ?>
                  </div>
              <?php	}
                }
              } ?>

                <div class="row">
                  <div class="form-group col-md-6 <?php echo (form_error('company')) ? 'has-error' : '';  ?>">
                      <label for="company"><?=$this->lang->line('application_company');?></label>
                      <select class="form-control" id="company" name="company" required >
                          <?php foreach ($company_data as $k => $v)  {  ?>
                          <option value="<?php echo $v['id'] ?>" <?php echo set_select('company', $v['id'], $v['id'] == $usercomp); ?> ><?php echo $v['name'] ?></option>
                          <?php } ?>
                      </select>
                      <?php echo '<i style="color:red">'.form_error('company').'</i>'; ?>
                  </div>
              
                  <div class="form-group col-md-6 <?php echo (form_error('store_id')) ? 'has-error' : '';  ?>">
                      <label for="store_id"><?=$this->lang->line('application_store');?></label>
                      <select class="form-control" id="store_id" name="store_id" required >
                          <?php if ($companyCurrent == 1 || $storeCurrent == 0): ?>
                          <option value="0"><?=$this->lang->line('application_all_stores');?></option>
                          <?php endif ?>
                      </select>
                      <?php echo '<i style="color:red">'.form_error('store_id').'</i>'; ?>
                  </div>

                </div>

                <div class="row">
                  <div class="form-group col-md-12">
                      <label for="search_store">Pesquisar em todas as empresas:</label>
                      <input type="text" class="form-control" id="search_store" placeholder="Digite o nome da loja...">
                      <div id="search_results" class="list-group" style="position: absolute; z-index: 1000; width: 95%;"></div>
                  </div>
                </div>
            </div>
            <div class="box-footer">
              <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
              <a href="<?php echo base_url('dashboard') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
            </div>
          </form>
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
var allStores = <?php echo json_encode($all_stores); ?>;
var userCompany = <?php echo $usercomp; ?>;
var userStore = <?php echo $userstore; ?>;
var isInitialLoad = true;

function populateStores(companyId) {
    var storeSelect = $('#store_id');
    storeSelect.empty();

    // Add 'All Stores' option if companyCurrent is 1 or storeCurrent is 0
    <?php if ($companyCurrent == 1 || $storeCurrent == 0): ?>
    storeSelect.append($('<option>', {
        value: 0,
        text: '<?=$this->lang->line('application_all_stores');?>'
    }));
    <?php endif ?>

    var filteredStores = allStores.filter(function(store) {
        return store.company_id == companyId;
    });

    $.each(filteredStores, function(index, store) {
        storeSelect.append($('<option>', {
            value: store.id,
            text: store.name
        }));
    });

    if (isInitialLoad) {
        storeSelect.val(userStore);
        isInitialLoad = false;
    }

    // If there is only one option ("All Stores"), select it.
    if (storeSelect.find('option').length === 1 && storeSelect.find('option[value="0"]').length === 1) {
        storeSelect.val(0);
    }

    storeSelect.select2();
}

$(document).ready(function() {
    $("#company, #store_id").select2();
    
    // Define the change handler before triggering it
    $("#company").change(function() {
        var selectedCompanyId = $(this).val();
        populateStores(selectedCompanyId);
    });

    // Set the initial company and trigger the change event to populate stores
    $('#company').val(userCompany).trigger('change');

    $("#mainUserNav").addClass('active');
    $("#manageUserNav").addClass('active');


    $('#search_store').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        var searchResultsDiv = $('#search_results');
        searchResultsDiv.empty();

        if (searchTerm.length > 0) {
            var matchingStores = allStores.filter(function(store) {
                return store.name.toLowerCase().includes(searchTerm);
            });

            $.each(matchingStores, function(index, store) {
                var companyName = $('#company option[value="' + store.company_id + '"]').text();
                searchResultsDiv.append(
                    `<a href="#" class="list-group-item list-group-item-action" 
                       data-company-id="${store.company_id}" data-store-id="${store.id}">
                       ${store.name} (${companyName})
                     </a>`
                );
            });
        }
    });

    $('#search_results').on('click', 'a', function(e) {
        e.preventDefault();
        var companyId = $(this).data('company-id');
        var storeId = $(this).data('store-id');

        $('#company').val(companyId).trigger('change');
        // Give a small delay to allow the store dropdown to be populated
        setTimeout(function() {
            $('#store_id').val(storeId).trigger('change');
        }, 100);

        $('#search_results').empty();
        $('#search_store').val('');
    });
});
</script>
