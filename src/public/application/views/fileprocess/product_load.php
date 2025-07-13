<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
  <?php $data['pageinfo'] = "application_manage"; $data['page_now'] = 'file_process';  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?=$this->session->flashdata('success')?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?=$this->session->flashdata('error')?>
          </div>
        <?php endif?>

        <div class="box box-primary" id="collapseFilter">
          <div class="box-body">
              <h4 class="mt-0">Filtro</h4>
              <div class="col-md-3 form-group no-padding" style="<?=(count($users_filter) > 1) ? "" : "display: none;"?>">
                  <select class="form-control selectpicker show-tick" id="buscaUsers" name="user[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_search_for_users');?>">
                      <?php foreach ((array)$users_filter as $user_filter) {?>
                          <option value="<?=$user_filter['id']?>" <?=set_select('user', $user_filter['id'], $user_filter['id'] == $user_id)?>><?="{$user_filter['email']} ({$user_filter['firstname']} {$user_filter['lastname']})"?></option>
                      <?php }?>
                  </select>
              </div>
          </div>
        </div>

          <div class="col-md-12 col-xs-12 no-padding" id="content_file_process">
              <div class="nav-tabs-custom">
                  <ul class="nav nav-tabs">
                      <li class="active"><a href="#products_tab" id="products_btn" data-toggle="tab"><?=$this->lang->line('application_products')?></a></li>
                      <?php if (in_array('syncPublishedSku', $user_permission)): ?></php><li><a href="#sync_published_sku_tab" id="sync_published_sku_btn" data-toggle="tab"><?=$this->lang->line('application_load_marketplace_sku')?></a></li><?php endif; ?>
                      <?php if (in_array('groupSimpleSku', $user_permission)): ?></php><li><a href="#group_simple_sku_tab" id="group_simple_sku_btn" data-toggle="tab"><?=$this->lang->line('application_group_simple_sku')?></a></li><?php endif; ?>
                      <?php if ($add_on_permission): ?><li><a href="#add_on_tab" id="add_on_btn" data-toggle="tab"><?=$this->lang->line('application_add_on')?></a></li><?php endif; ?>
                      <?php if ($catalog_product_marketplace_permission): ?><li><a href="#catalog_product_marketplace_tab" id="catalog_product_marketplace_btn" data-toggle="tab"><?=$this->lang->line('application_catalogs')?></a></li><?php endif; ?>
                  </ul>
                  <div class="tab-content col-md-12">
                      <div class="tab-pane active" id="products_tab">
                          <table id="manageTableProducts" class="table table-bordered table-striped">
                              <thead>
                              <tr>
                                  <th><?=$this->lang->line('application_id');?></th>
                                  <th><?=$this->lang->line('application_file');?></th>
                                  <th><?=$this->lang->line('application_status');?></th>
                                  <th><?=$this->lang->line('application_user');?></th>
                                  <th><?=$this->lang->line('application_date_create');?></th>
                                  <th><?=$this->lang->line('application_action');?></th>
                              </tr>
                              </thead>
                          </table>
                      </div>
                      <?php if (in_array('syncPublishedSku', $user_permission)): ?>
                          <div class="tab-pane" id="sync_published_sku_tab">
                              <table id="manageTableSyncPublishedSku" class="table table-bordered table-striped">
                                  <thead>
                                  <tr>
                                      <th><?=$this->lang->line('application_id');?></th>
                                      <th><?=$this->lang->line('application_file');?></th>
                                      <th><?=$this->lang->line('application_status');?></th>
                                      <th><?=$this->lang->line('application_user');?></th>
                                      <th><?=$this->lang->line('application_date_create');?></th>
                                      <th><?=$this->lang->line('application_action');?></th>
                                  </tr>
                                  </thead>
                              </table>
                          </div>
                      <?php endif; ?>
                      <?php if (in_array('groupSimpleSku', $user_permission)): ?>
                          <div class="tab-pane" id="group_simple_sku_tab">
                              <table id="manageTableGroupSimpleSku" class="table table-bordered table-striped">
                                  <thead>
                                  <tr>
                                      <th><?=$this->lang->line('application_id');?></th>
                                      <th><?=$this->lang->line('application_file');?></th>
                                      <th><?=$this->lang->line('application_status');?></th>
                                      <th><?=$this->lang->line('application_user');?></th>
                                      <th><?=$this->lang->line('application_date_create');?></th>
                                      <th><?=$this->lang->line('application_action');?></th>
                                  </tr>
                                  </thead>
                              </table>
                          </div>
                      <?php endif; ?>
                      <?php if ($add_on_permission): ?>
                          <div class="tab-pane" id="add_on_tab">
                              <table id="manageTableAddOn" class="table table-bordered table-striped">
                                  <thead>
                                  <tr>
                                      <th><?=$this->lang->line('application_id');?></th>
                                      <th><?=$this->lang->line('application_file');?></th>
                                      <th><?=$this->lang->line('application_status');?></th>
                                      <th><?=$this->lang->line('application_user');?></th>
                                      <th><?=$this->lang->line('application_date_create');?></th>
                                      <th><?=$this->lang->line('application_action');?></th>
                                  </tr>
                                  </thead>
                              </table>
                          </div>
                      <?php endif; ?>
                      <?php if ($catalog_product_marketplace_permission): ?>
                          <div class="tab-pane" id="catalog_product_marketplace_tab">
                              <table id="manageTableCatalogProductMarketplace" class="table table-bordered table-striped">
                                  <thead>
                                  <tr>
                                      <th><?=$this->lang->line('application_id');?></th>
                                      <th><?=$this->lang->line('application_file');?></th>
                                      <th><?=$this->lang->line('application_status');?></th>
                                      <th><?=$this->lang->line('application_user');?></th>
                                      <th><?=$this->lang->line('application_date_create');?></th>
                                      <th><?=$this->lang->line('application_action');?></th>
                                  </tr>
                                  </thead>
                              </table>
                          </div>
                      <?php endif; ?>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewStatusFile">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_status_file_process');?><span id="deletecategoryname"></span></h4>
      </div>
      <div class="modal-body text-center"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="<?=HOMEPATH?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
let manageTableProducts, manageTableSyncPublishedSku, manageTableGroupSimpleSku, manageTableAddOn, manageTableCatalogProductMarketplace;
let base_url = "<?=base_url()?>";

$(document).ready(function() {
    $("#mainProductNav").addClass('active');
    $("#navFileProcessProductsLoad").addClass('active');
    getTable('products');
});

$('#buscaUsers').on('change', function(){
    const btn_type = $('#content_file_process ul.nav-tabs li.active a').attr('id').replace('_btn', '');
    getTable(btn_type);
});

$('a[id*="_btn"]').on('show.bs.tab', function(){
    const btn_type = $(this).attr('id').replace('_btn', '');
    getTable(btn_type);
});

const getTable = type_load => {
    let type            = '';
    let content_table   = '';
    let users           = $('#buscaUsers').val();

    if (type_load === 'products') {
        if (typeof manageTableProducts !== 'undefined') {
            manageTableProducts.destroy();
        }
        content_table = 'manageTableProducts';
        type = 'Products';
    } else if (type_load === 'sync_published_sku') {
        if (typeof manageTableSyncPublishedSku !== 'undefined') {
            manageTableSyncPublishedSku.destroy();
        }
        content_table = 'manageTableSyncPublishedSku';
        type = 'SyncPublishedSku';
    } else if (type_load === 'group_simple_sku') {
        if (typeof manageTableGroupSimpleSku !== 'undefined') {
            manageTableGroupSimpleSku.destroy();
        }
        content_table = 'manageTableGroupSimpleSku';
        type = 'GroupSimpleSku';
    } else if (type_load === 'add_on') {
        if (typeof manageTableAddOn !== 'undefined') {
            manageTableAddOn.destroy();
        }
        content_table = 'manageTableAddOn';
        type = 'AddOnSkus';
    } else if (type_load === 'catalog_product_marketplace') {
        if (typeof manageTableCatalogProductMarketplace !== 'undefined') {
            manageTableCatalogProductMarketplace.destroy();
        }
        content_table = 'manageTableCatalogProductMarketplace';
        type = 'CatalogProductMarketplace';
    }

    if (!(users.length)) {
        users = '';
    }

    // initialize the datatable
    let manageTable = $(`#${content_table}`).DataTable({
        "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url +'FileProcess/fetchFileProcessProductsLoadData',
            data: { users, type },
            pages: 2
        }),
        "order": [[ 0, 'desc' ]]
    });

    if (type_load === 'products') {
        manageTableProducts = manageTable;
    } else if (type_load === 'sync_published_sku') {
        manageTableSyncPublishedSku = manageTable;
    } else if (type_load === 'group_simple_sku') {
        manageTableGroupSimpleSku = manageTable;
    } else if (type_load === 'add_on') {
        manageTableAddOn = manageTable;
    } else if (type_load === 'catalog_product_marketplace') {
        manageTableCatalogProductMarketplace = manageTable;
    }
}

$(document).on('click', '.view-status-file', function(){
    let idFile      = $(this).attr('file-id');
    let type        = '';
    const row_reg   = $(this).closest('tr');
    const link_file = row_reg.find('td:eq(1) a').prop('href').replace('.csv', '_with_error.csv');
    const has_error = row_reg.find('td:eq(2) span').hasClass('label-danger');
    const active_tab = $('li.active a[data-toggle="tab"]').attr('href');

    if (active_tab === '#add_on_tab') {
        type = 'AddOnSkus';
    } else if (active_tab === '#sync_published_sku_tab') {
        type = 'SyncPublishedSku';
    } else if (active_tab === '#group_simple_sku_tab') {
        type = 'GroupSimpleSku';
    } else if (active_tab === '#products_tab') {
        type = 'Products';
    } else if (active_tab === '#add_on_tab') {
        type = 'AddOnSkus';
    } else if (active_tab === '#catalog_product_marketplace_tab') {
        type = 'CatalogProductMarketplace';
    }

    $.post(`${base_url}/FileProcess/getResponseFile`, { type, idFile }, response => {
        let content = '';

        if (response.length === 1 && typeof response[0] == "string") {
            if (has_error) {
                content = 'Esse arquivo não contem histórico de erro, faça um novo envio para existir.';
            } else {
                content = response[0];
            }
        } else {
            if (!response.waiting) {
                content = `<a class="btn btn-flat btn-primary mb-4" href="${link_file}" download>Baixar arquivo com erros</a>`
            }
            $(response).each(function (key, value) {
              if (value.line === undefined) {
                content += `<p>${value.messages}</p>`;
              } else{
                content += `<p><b>Linha: ${value.line}</b>. ${value.messages}</p>`;
              }
            });
        }

        $("#viewStatusFile").modal('show').find('.modal-body').html(content);
    });
});
</script>
