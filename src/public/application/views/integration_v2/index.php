<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
  <?php $data['pageinfo'] = "application_manage"; $data['page_now'] = 'external_integration';  $this->load->view('templates/content_header',$data); ?>

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

        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary" id="collapseFilter">
                  <div class="box-body">
                      <h4 class="mt-0">Filtro</h4>
                      <div class="col-md-4 form-group no-padding">
                          <select class="form-control select2" id="stores">
                              <option value='0'><?=$this->lang->line('application_select_store')?>...</option>
                              <?php
                              foreach ($stores as $store) {
                                  echo "<option value='{$store['store_id']}' ".set_select('stores', $store['store_id'], $store['store_id'] == $store_id).">{$store['name']}</option>";
                              }
                              ?>
                          </select>
                      </div>
                      <div class="col-md-2 form-group no-padding">
                          <div class="integration-image widget-user-image">
                              <img class="ml-4" src="" alt="" width="75">
                          </div>
                      </div>
                      <div class="col-md-2 form-group no-padding">
                          <button type="button" class="btn btn-primary col-md-12" id="viewIntegration" data-toggle="tooltip" title="Visualizar"><i class="fa fa-eye"></i> Visualizar credenciais</button>
                      </div>
                  </div>
                </div>
            </div>
        </div>

          <div class="row" id="content-integration-search">
              <div class="col-md-12 col-xs-12">
                  <div class="nav-tabs-custom">
                      <ul class="nav nav-tabs">
                          <li class="active"><a href="#product_tab" id="product_btn" data-toggle="tab"><?=$this->lang->line('application_product')?></a></li>
                          <li><a href="#order_tab" id="order_btn" data-toggle="tab"><?=$this->lang->line('application_order')?></a></li>
                          <li><a href="#quote_tab" id="quote_btn" data-toggle="tab"><?=$this->lang->line('application_quotation')?></a></li>
                      </ul>
                      <div class="tab-content col-md-12">
                          <div class="tab-pane active" id="product_tab">
                              <table class="table table-bordered table-striped">
                                  <thead>
                                      <tr>
                                          <th><?=$this->lang->line('application_module');?></th>
                                          <th><?=$this->lang->line('application_action');?></th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td>Consultar Produto</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="sku"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="sku" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>Consultar Preço</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="price"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="price" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>Consultar Estoque</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="stock"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="stock" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>Atributos</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="attribute"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="attribute" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                  </tbody>
                              </table>
                          </div>
                          <div class="tab-pane" id="order_tab">
                              <table class="table table-bordered table-striped">
                                  <thead>
                                      <tr>
                                          <th><?=$this->lang->line('application_module');?></th>
                                          <th><?=$this->lang->line('application_action');?></th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td>Consultar Pedido</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="order"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="order" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>Consultar Nota Fiscal</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="invoice"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="invoice" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>Consultar Rastreio</td>
                                          <td>
                                            <button class="btn btn-primary action-search" type="button" data-route="tracking"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search" type="button" data-route="tracking" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                  </tbody>
                              </table>
                          </div>
                          <div class="tab-pane" id="quote_tab">
                              <table class="table table-bordered table-striped">
                                  <thead>
                                      <tr>
                                          <th><?=$this->lang->line('application_module');?></th>
                                          <th><?=$this->lang->line('application_action');?></th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td>Consultar Cotação</td>
                                          <td>
                                            <button class="btn btn-primary action-search-quote" type="button" data-route="quote"><i class="fa fa-eye"></i></button>
                                            <button class="btn btn-primary action-search-quote" type="button" data-route="quote" data-debug="1"><i class="fa fa-bug"></i></button>
                                          </td>
                                      </tr>
                                  </tbody>
                              </table>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="view_product">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_product');?></h4>
      </div>
      <div class="modal-body">
          <div class="row">
              <div class="col-md-12">
                  <div class="form-group">
                      <label for="sku">SKU</label>
                      <input type="text" class="form-control" name="sku" id="sku" required>
                      <small>Em caso de um produto com variação, informe o SKU da variação</small>
                  </div>
              </div>
          </div>
          <div class="row">
              <div class="col-md-12">
                  <pre class="product_result"></pre>
              </div>
          </div>
          <input type="hidden" name="action">
          <input type="hidden" name="debug">
      </div>
      <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        <button type="button" class="btn btn-primary submit-form"><?=$this->lang->line('application_search');?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="view_order">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_order');?></h4>
      </div>
      <div class="modal-body">
          <div class="row">
              <div class="col-md-12">
                  <div class="form-group">
                      <label for="order_id">Código do pedido no seller center</label>
                      <input type="text" class="form-control" name="order_id" id="order_id" required>
                  </div>
              </div>
          </div>
          <div class="row">
              <div class="col-md-12">
                  <pre class="order_result"></pre>
              </div>
          </div>
          <input type="hidden" name="action">
          <input type="hidden" name="debug">
      </div>
      <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        <button type="button" class="btn btn-primary submit-form"><?=$this->lang->line('application_search');?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="view_quote">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_quotation');?></h4>
      </div>
      <div class="modal-body">
          <div class="products_to_quote">
              <div class="row content-sku-to-quote">
                  <div class="col-md-5">
                      <div class="form-group">
                          <label>SKU</label>
                          <input type="text" class="form-control" name="sku[]" required>
                      </div>
                  </div>
                  <div class="col-md-5">
                      <div class="form-group">
                          <label>Quantidade</label>
                          <input type="number" class="form-control" name="quantity[]" required>
                      </div>
                  </div>
                  <div class="col-md-2">
                      <div class="form-group">
                          <label>&nbsp;</label><br>
                          <button class="btn btn-danger btnRemoveSkuToQuote"><i class="fa fa-trash"></i></button>
                      </div>
                  </div>
              </div>
          </div>
          <div class="row">
              <div class="col-md-12">
                  <div class="form-group">
                      <button class="btn btn-primary col-md-12 btn-sm btnAddSkuToQuote"><i class="fa fa-plus"></i> Adicionar SKU</button>
                  </div>
              </div>
          </div>
          <div class="row">
              <div class="col-md-12">
                  <div class="form-group">
                      <label for="order_id">CEP</label>
                      <input type="text" class="form-control" name="zipcode" id="zipcode" required>
                  </div>
              </div>
          </div>
          <div class="row">
              <div class="col-md-12">
                  <pre class="quote_result"></pre>
              </div>
          </div>
          <input type="hidden" name="action">
          <input type="hidden" name="debug">
      </div>
      <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        <button type="button" class="btn btn-primary submit-form-quote"><?=$this->lang->line('application_search');?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewModalIntegration">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_integration');?></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary col-md-5" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>

<style>
    .product_result {
        max-height: 400px;
    }
</style>
<script type="text/javascript" src="<?=HOMEPATH?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
let manageTable;
let base_url = "<?=base_url()?>";

$(document).ready(function() {
    $("#mainProcessesNav").addClass('active');
    $("#manageIntegrationsNav").addClass('active');
    $('.select2').select2();
    $('#stores').trigger('change');
});

$('#stores').on('change', function(){
    getIntegartion();
});

const getIntegartion = () => {
    const store_id = parseInt($('#stores').val());
    $('.integration-image.widget-user-image img').removeAttr('src').removeAttr('alt');
    $('#content-integration-search').hide();
    if (store_id !== 0) {
        $.get(`${base_url}/Integration_v2/General/getIntegrationByStore/${store_id}`, response => {
            if (!Object.keys(response).length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Integração não encontrada para a loja'
                });
                return;
            }
            $('#content-integration-search').show();

            response.integration

            $('.integration-image.widget-user-image img').attr('src', "<?=base_url("assets/images/integration_erps/")?>"+response.integration_image).attr('alt', response.integration_description);
        });
    }
}

$(document).on('click', '.action-search', function(){
    const type = $(this).closest('.tab-pane').attr('id').replace('_tab', '');
    const action = $(this).data('route');
    const debug = $(this).data('debug') ?? 0;

    $(`#view_${type} [name="sku"], #view_${type} [name="order_id"]`).val('');
    $(`#view_${type} .${type}_result`).empty();
    $(`#view_${type}`).modal();
    $(`#view_${type} [name="action"]`).val(action);
    $(`#view_${type} [name="debug"]`).val(debug);
});

$(document).on('click', '.action-search-quote', function(){
    const type = $(this).closest('.tab-pane').attr('id').replace('_tab', '');
    const action = $(this).data('route');
    const debug = $(this).data('debug') ?? 0;

    $(`#view_${type} [name="zipcode"]`).val('');
    $(`#view_${type} .products_to_quote .content-sku-to-quote`).remove();
    $(`#view_${type} .btnAddSkuToQuote`).trigger('click');
    $(`#view_${type} .${type}_result`).empty();
    $(`#view_${type}`).modal();
    $(`#view_${type} [name="action"]`).val(action);
    $(`#view_${type} [name="debug"]`).val(debug);
});

$(document).on('click', '.submit-form', function(){
    const field = $(this).closest('.modal-content').find('[name="sku"]').val() ??
        $(this).closest('.modal-content').find('[name="order_id"]').val();
    const store_id = $('#stores').val();
    const action = $(this).closest('.modal-content').find('[name="action"]').val();
    const debug = parseInt($(this).closest('.modal-content').find('[name="debug"]').val()) === 1;
    const type = $('#content-integration-search .nav.nav-tabs li.active a').attr('id').replace('_btn', '');
    const route_class = type.charAt(0).toUpperCase() + type.substring(1);
    if (field === '') {
        Swal.fire({
            icon: 'error',
            title: 'Informe um código'
        });
        return;
    }
    $(`#view_${type} .${type}_result`).empty();

    const query_debug = debug ? '1' : '0';

    $.get(`${base_url}/Integration_v2/${route_class}/search/${action}/${store_id}/${field}/${query_debug}`, response => {
        if (response) {
            $(`#view_${type} .${type}_result`).text(debug ? response : JSON.stringify(response, undefined, 2));
        }
    });
});

$(document).on('click', '.submit-form-quote', function(){
    const sku = $(this).closest('.modal-content').find('[name="sku[]"]').map(function() { return this.value; }).get()
    const quantity = $(this).closest('.modal-content').find('[name="quantity[]"]').map(function() { return this.value; }).get()
    const zipcode = $(this).closest('.modal-content').find('[name="zipcode"]').val();
    const store_id = $('#stores').val();
    const debug = parseInt($(this).closest('.modal-content').find('[name="debug"]').val()) === 1;

    const action = $(this).closest('.modal-content').find('[name="action"]').val();
    const type = $('#content-integration-search .nav.nav-tabs li.active a').attr('id').replace('_btn', '');
    const route_class = type.charAt(0).toUpperCase() + type.substring(1);
    $(`#view_${type} .${type}_result`).empty();

    const query_debug = debug ? '1' : '0';

    $.post(`${base_url}/Integration_v2/${route_class}/search/${action}/${query_debug}`, {sku, quantity, zipcode, store_id}, response => {
        console.log(response);
        if (response) {
            const json_stringify = JSON.stringify(response, undefined, 2);
            $(`#view_${type} .${type}_result`).text(debug ? response : (typeof json_stringify !== "undefined" ? json_stringify : response));
        }
    });
});

$(document).on('click', '.btnRemoveSkuToQuote', function(){
    $(this).closest('.content-sku-to-quote').remove();
});

$(document).on('click', '.btnAddSkuToQuote', function(){
    $('.products_to_quote').append(`<div class="row content-sku-to-quote">
          <div class="col-md-5">
              <div class="form-group">
                  <label>SKU</label>
                  <input type="text" class="form-control" name="sku[]" required>
              </div>
          </div>
          <div class="col-md-5">
              <div class="form-group">
                  <label>Quantidade</label>
                  <input type="number" class="form-control" name="quantity[]" required>
              </div>
          </div>
          <div class="col-md-2">
              <div class="form-group">
                  <label>&nbsp;</label><br>
                  <button class="btn btn-danger btnRemoveSkuToQuote"><i class="fa fa-trash"></i></button>
              </div>
          </div>
      </div>`);
});

$('#viewIntegration').on('click', function(){

    const store_id = parseInt($('#stores').val());
    if (store_id !== 0) {
        $.get(`${base_url}/Stores/getDataIntegrations/${store_id}`, response => {
            console.log(response);
            let str = `<div class="row">
                               <h3 class="text-center">${response.integration}</h3>
                           </div>`;

            $.each(response.credentials, function( index, value ) {
                str += `
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label>${index}</label>
                            <input type="text" class="form-control" value="${value}" readonly>
                        </div>
                    </div>
                    `;
            });

            $('#viewModalIntegration').modal().find('.modal-body').empty().append(str);
        });
    }
    // http://localhost/
})
</script>
