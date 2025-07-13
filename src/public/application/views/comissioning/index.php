<?php
use App\Libraries\Enum\ComissioningType;
?>
<div class="content-wrapper">

	<?php
	$data['pageinfo'] = "application_manage";
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

                <div class="box box-info mt-2">

                    <div class="box-header with-border">

                        <form id="active-filters" enctype="text/plain">

                            <div class="row">

                                <div class="form-group col-md-3 col-xs-3">
                                    <label for="filter_marketplace"><?= $this->lang->line('application_marketplace'); ?> (*)</label>
                                    <select class="form-control select2" name="filter_marketplace" id="filter_marketplace" onchange="filter()">
                                        <option value="0">Todos os Marketplaces</option>
                                        <?php
                                        $totalMarketplaces = count($marketplaces);
                                        foreach ($marketplaces as $marketplace){
                                            $selected = isset($_GET['filter_marketplace']) && $_GET['filter_marketplace'] == $marketplace['int_to'];
                                            ?>
                                            <option value="<?php echo $marketplace['int_to']; ?>" <?php if ($totalMarketplaces == 1 || $selected){ echo 'selected="selected"'; }?>><?php echo $marketplace['int_to']; ?> - <?php echo $marketplace['name']; ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-3 col-xs-3">
                                    <label for="filter_store"><?= $this->lang->line('application_label_store'); ?> (*)</label>
                                    <select class="form-control select2" name="filter_store" id="filter_store" onchange="filter()">
                                        <option value="0">Todas as Lojas</option>
                                        <?php
                                        foreach ($stores as $store){
                                            $selected = isset($_GET['filter_store']) && $_GET['filter_store'] == $store['id'];
                                            ?>
                                            <option value="<?php echo $store['id']; ?>" <?php echo $selected ? 'selected="selected"' : ''; ?>><?php echo $store['id']; ?> - <?php echo $store['name']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-2 col-xs-2">
                                    <label for="filter_start_date"><?= $this->lang->line('application_start_date'); ?></label>
                                    <input type="date" class="form-control" id="filter_start_date" name="filter_start_date" onchange="filter()" value="<?php echo isset($_GET['filter_start_date']) && $_GET['filter_start_date'] ? $_GET['filter_start_date'] : ''; ?>">
                                </div>

                                <div class="form-group col-md-2 col-xs-2">
                                    <label for="filter_end_date"><?= $this->lang->line('application_end_date'); ?></label>
                                    <input type="date" class="form-control" id="filter_end_date" name="filter_end_date" onchange="filter()" value="<?php echo isset($_GET['filter_end_date']) && $_GET['filter_end_date'] ? $_GET['filter_end_date'] : ''; ?>">
                                </div>

                                <div class="form-group col-md-2 col-xs-2">
                                    <label for="filter_status"><?= $this->lang->line('application_status'); ?></label>
                                    <select class="form-control select2" name="filter_status" id="filter_status" onchange="filter()">
                                        <option value="0">Todos os Status</option>
                                        <?php
                                        $statuses = [
                                                'scheduled' => 'Aguardando Inicio',
                                                'expired' => 'Encerrado',
                                                'active' => 'Ativa',
                                        ];
                                        foreach ($statuses as $status => $statusName){
                                            $selected = isset($_GET['filter_status']) && $_GET['filter_status'] == $status;
                                            ?>
                                            <option value="<?php echo $status; ?>" <?php echo $selected ? 'selected="selected"' : ''; ?>><?php echo $statusName; ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                            </div>

                        </form>

                    </div>

                </div>

                <div class="box box-info mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= lang('application_commision_vigence_for_seller'); ?></h3>
                    </div>
                    <div class="box-body">

                        <?php if (hasPermission(['createHierarchyComission'], $user_permission)): ?>
                            <a  class="btn btn-primary mb-4" onclick="return newComission('<?php echo ComissioningType::SELLER; ?>')">
                                <i class="fa fa-plus"></i>
                                <?= lang('application_add_new_comission'); ?>
                            </a>
                        <?php
                        endif;
                        ?>

                        <table id="sellerComissionTable" class="table table-bordered table-striped table-condensed">
                            <thead>
                            <tr>
                                <th><?= lang('application_id'); ?></th>
                                <th><?= lang('application_marketplace'); ?></th>
                                <th><?= lang('application_store'); ?></th>
                                <th><?= lang('application_commission'); ?></th>
                                <th><?= lang('application_vigence_start_date'); ?></th>
                                <th><?= lang('application_vigence_end_date'); ?></th>
                                <th data-orderable="false"><?= lang('payment_balance_transfers_grid_transferstatus'); ?></th>
                                <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>

                <div class="box box-info mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= lang('application_commisioning_hierarchy_title'); ?></h3>
                    </div>
                    <div class="box-body">
                        <ul class="nav nav-tabs mt-5" role="tablist">
                            <li class="active" role="presentation" >
                                <a class="nav-item nav-link" href="#brand"  data-toggle="tab" onclick="mountBrandComissionTable()">
                                    <?=$this->lang->line('application_brand')?>
                                </a>
                            </li>
                            <li role="presentation" >
                                <a class="nav-item nav-link" href="#category" data-toggle="tab" onclick="mountCategoryComissionTable()">
                                    <?=$this->lang->line('application_campaign_segment_category')?>
                                </a>
                            </li>
                            <li role="presentation" >
                                <a class="nav-item nav-link" href="#trade-policy" data-toggle="tab" onclick="mountTradePolicyComissionTable()">
                                    <?=$this->lang->line('application_credentials_erp_sales_channel_vtex')?>
                                </a>
                            </li>
                            <li role="presentation" >
                                <a class="nav-item nav-link" href="#product" data-toggle="tab" onclick="mountPaymentMethodComissionTable()">
                                    <?=$this->lang->line('application_product')?>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content campaign-tab-content p-5">

                            <div class="tab-pane fade in active" id="brand" role="tabpanel">

                                <div class="row">
                                    <div class="col-md-12">

                                        <?php if (hasPermission(['createHierarchyComission'], $user_permission)): ?>
                                            <a  class="btn btn-primary mb-4 mt-4" onclick="return newComission('<?php echo ComissioningType::BRAND; ?>')">
                                                <i class="fa fa-plus"></i>
                                                <?= lang('application_add_new_comission'); ?>
                                            </a>
                                        <?php
                                        endif;
                                        ?>

                                        <table id="brandComissionTable" class="table table-bordered table-striped table-condensed">
                                            <thead>
                                                <tr>
                                                    <th><?= lang('application_id'); ?></th>
                                                    <th><?= lang('application_marketplace'); ?></th>
                                                    <th><?= lang('application_store'); ?></th>
                                                    <th><?= lang('application_brand'); ?></th>
                                                    <th><?= lang('application_commission'); ?></th>
                                                    <th><?= lang('application_vigence_start_date'); ?></th>
                                                    <th><?= lang('application_vigence_end_date'); ?></th>
                                                    <th data-orderable="false"><?= lang('payment_balance_transfers_grid_transferstatus'); ?></th>
                                                    <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade in" id="category" role="tabpanel">

                                <div class="row">
                                    <div class="col-md-12">

                                        <?php if (hasPermission(['createHierarchyComission'], $user_permission)): ?>
                                            <a  class="btn btn-primary mb-4 mt-4" onclick="return newComission('<?php echo ComissioningType::CATEGORY; ?>')">
                                                <i class="fa fa-plus"></i>
                                                <?= lang('application_add_new_comission'); ?>
                                            </a>
                                        <?php
                                        endif;
                                        ?>

                                        <table id="categoryComissionTable" class="table table-bordered table-striped table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?= lang('application_id'); ?></th>
                                                <th><?= lang('application_marketplace'); ?></th>
                                                <th><?= lang('application_store'); ?></th>
                                                <th><?= lang('application_category'); ?></th>
                                                <th><?= lang('application_commission'); ?></th>
                                                <th><?= lang('application_vigence_start_date'); ?></th>
                                                <th><?= lang('application_vigence_end_date'); ?></th>
                                                <th data-orderable="false"><?= lang('payment_balance_transfers_grid_transferstatus'); ?></th>
                                                <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade in" id="trade-policy" role="tabpanel">

                                <div class="row">
                                    <div class="col-md-12">

                                        <?php if (hasPermission(['createHierarchyComission'], $user_permission)): ?>
                                            <a  class="btn btn-primary mb-4 mt-4" onclick="return newComission('<?php echo ComissioningType::TRADE_POLICY; ?>')">
                                                <i class="fa fa-plus"></i>
                                                <?= lang('application_add_new_comission'); ?>
                                            </a>
                                        <?php
                                        endif;
                                        ?>

                                        <table id="tradePolicyComissionTable" class="table table-bordered table-striped table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?= lang('application_id'); ?></th>
                                                <th><?= lang('application_marketplace'); ?></th>
                                                <th><?= lang('application_store'); ?></th>
                                                <th><?= lang('application_credentials_erp_sales_channel_vtex'); ?></th>
                                                <th><?= lang('application_commission'); ?></th>
                                                <th><?= lang('application_vigence_start_date'); ?></th>
                                                <th><?= lang('application_vigence_end_date'); ?></th>
                                                <th data-orderable="false"><?= lang('payment_balance_transfers_grid_transferstatus'); ?></th>
                                                <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <div class="tab-pane fade in" id="product" role="tabpanel">

                                <div class="row">
                                    <div class="col-md-12">

                                        <?php if (hasPermission(['createHierarchyComission'], $user_permission)): ?>
                                            <a  class="btn btn-primary mb-4 mt-4" onclick="return newComission('<?php echo ComissioningType::PRODUCT; ?>')">
                                                <i class="fa fa-plus"></i>
                                                <?= lang('application_add_new_comission'); ?>
                                            </a>
                                        <?php
                                        endif;
                                        ?>

                                        <table id="paymentMethodComissionTable" class="table table-bordered table-striped table-condensed">
                                            <thead>
                                            <tr>
                                                <th><?= lang('application_id'); ?></th>
                                                <th><?= lang('application_marketplace'); ?></th>
                                                <th><?= lang('application_store'); ?></th>
                                                <th><?= lang('campaigns_v2_dashboard_cards_products'); ?></th>
                                                <th><?= lang('application_vigence_start_date'); ?></th>
                                                <th><?= lang('application_vigence_end_date'); ?></th>
                                                <th data-orderable="false"><?= lang('payment_balance_transfers_grid_transferstatus'); ?></th>
                                                <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- /.box-body -->
                </div>

            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<div class="modal fade" tabindex="-1" role="dialog" id="insertModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modal-title">
                    <?php
                    echo lang('application_add_new_comission');
                    ?>
                </h4>
            </div>
            <form role="form" action="" method="post" id="form">
                <div class="modal-body" id="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('application_close')?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="editModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="modal-edit-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 id="modal-edit-title">
                    Editar Comissionamento
                </h4>
            </div>
            <form role="form" action="" method="post" id="form-edit">
                <div class="modal-body" id="modal-edit-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('application_close')?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php
$this->load->view('comissioning/commissioning_modal_details');
?>

<script type="text/javascript">

    var sellerComissionTable;
    var brandComissionTable;
    var categoryComissionTable;
    var tradePolicyComissionTable;
    var paymentMethodComissionTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {

        $("#hierarchyComissionNav").addClass('active');
        $(".select2").select2();

        mountSellerComissionTable();
        mountBrandComissionTable();

    });

    function validatePercent(input) {
        let value = input.value;
        // Replace comma with dot
        value = value.replace(',', '.');
        // Remove invalid characters
        value = value.replace(/[^0-9.]/g, '');
        // Validate the entire input
        let regex = /^\d{0,3}(\.\d{0,2})?$/;
        if (!regex.test(value)) {
            value = value.slice(0, -1);
        }
        // Prevent adjusting the value while the user is typing
        if (value === '' || value === '.' || value === ',' || value === '-') {
            input.value = value;
            return;
        }
        // Convert to float and limit to max and min values
        let floatValue = parseFloat(value);
        const max = <?php echo $max_value_hierarchy_comission; ?>;
        const min = <?php echo $min_value_hierarchy_comission; ?>;
        if (floatValue > max) {
            value = max.toString();
            Swal.fire({
                icon: 'error',
                title: "O percentual máximo é "+max+"%"
            });
        } else if (floatValue < min && value !== '') {
            value = min.toString();
            Swal.fire({
                icon: 'error',
                title: "O percentual mínimo é "+min+"%"
            });
        }
        input.value = value;
    }

    function filter() {
        const queryString = '?filter_store=' + $('#filter_store').val() +
            '&filter_marketplace=' + $('#filter_marketplace').val() +
            '&filter_start_date=' + $('#filter_start_date').val() +
            '&filter_end_date=' + $('#filter_end_date').val() +
            '&filter_status=' + $('#filter_status').val();

        // Atualiza a URL sem recarregar a página
        history.pushState(null, '', queryString);

        // Chama as funções necessárias
        mountSellerComissionTable();
        mountBrandComissionTable();
        mountTradePolicyComissionTable();
        mountPaymentMethodComissionTable();
        mountCategoryComissionTable();

        return false;
    }

    function refreshTableByType(type) {
        if (type == '<?php echo ComissioningType::BRAND; ?>'){
            mountBrandComissionTable();
        }
        if (type == '<?php echo ComissioningType::TRADE_POLICY; ?>'){
            mountTradePolicyComissionTable();
        }
        if (type == '<?php echo ComissioningType::CATEGORY; ?>'){
            mountCategoryComissionTable();
        }
        if (type == '<?php echo ComissioningType::SELLER; ?>'){
            mountSellerComissionTable();
        }
        if (type == '<?php echo ComissioningType::PRODUCT; ?>'){
            mountPaymentMethodComissionTable();
        }
    }

    function endCommisioning(itemId, type) {
        if (confirm('Tem certeza que deseja encerrar o comissionamento selecionado?')){
            $.ajax({
                url: base_url + 'commissioning/close/'+itemId, // URL do endpoint
                method: 'GET', // Tipo de requisição
                dataType: 'json', // Espera receber JSON de resposta
                success: function(data) {
                    if (data.success){
                        Swal.fire({
                            icon: 'success',
                            title: 'Comissionamento encerrado com sucesso'
                        });
                        refreshTableByType(type);
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: data.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status + ' ' + error);
                    alert('Error: ' + status + ' ' + error); // Alerta de erro
                }
            });
        }
    }

    function deleteItem(itemId, type) {
        if (confirm('Tem certeza que deseja excluir o comissionamento selecionado?')){
            $.ajax({
                url: base_url + 'commissioning/delete/'+itemId, // URL do endpoint
                method: 'GET', // Tipo de requisição
                dataType: 'json', // Espera receber JSON de resposta
                success: function(data) {
                    if (data.success){
                        Swal.fire({
                            icon: 'success',
                            title: 'Comissionamento excluído com sucesso'
                        });
                        refreshTableByType(type);
                    }else{
                        Swal.fire({
                            icon: 'error',
                            title: data.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ' + status + ' ' + error);
                    alert('Error: ' + status + ' ' + error); // Alerta de erro
                }
            });
        }
    }

    function mountSellerComissionTable() {

        if ($('#sellerComissionTable').length) {
            $('#sellerComissionTable').DataTable().destroy();
        }

        // initialize the datatable
        sellerComissionTable = $('#sellerComissionTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_search'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'commissioning/seller_comissions/',
                "type": 'POST',
                "data": {
                    "store_id" : $('#filter_store').val(),
                    "marketplace" : $('#filter_marketplace').val(),
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                    "status" : $('#filter_status').val()
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id"},
                {"data": "int_to"},
                {"data": "name"},
                {"data": "comission"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "status"},
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function mountBrandComissionTable() {

        if ($('#brandComissionTable').length) {
            $('#brandComissionTable').DataTable().destroy();
        }

        // initialize the datatable
        brandComissionTable = $('#brandComissionTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_search'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'commissioning/brand_comissions/',
                "type": 'POST',
                "data": {
                    "store_id" : $('#filter_store').val(),
                    "marketplace" : $('#filter_marketplace').val(),
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                    "status" : $('#filter_status').val()
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id"},
                {"data": "int_to"},
                {"data": "store_name"},
                {"data": "name"},
                {"data": "comission"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "status"},
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function mountCategoryComissionTable() {

        if ($('#categoryComissionTable').length) {
            $('#categoryComissionTable').DataTable().destroy();
        }

        // initialize the datatable
        categoryComissionTable = $('#categoryComissionTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_search'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'commissioning/category_comissions/',
                "type": 'POST',
                "data": {
                    "store_id" : $('#filter_store').val(),
                    "marketplace" : $('#filter_marketplace').val(),
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                    "status" : $('#filter_status').val()
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id"},
                {"data": "int_to"},
                {"data": "store_name"},
                {"data": "name"},
                {"data": "comission"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "status"},
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function mountTradePolicyComissionTable() {

        if ($('#tradePolicyComissionTable').length) {
            $('#tradePolicyComissionTable').DataTable().destroy();
        }

        // initialize the datatable
        tradePolicyComissionTable = $('#tradePolicyComissionTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_search'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'commissioning/trade_policies_comissions/',
                "type": 'POST',
                "data": {
                    "store_id" : $('#filter_store').val(),
                    "marketplace" : $('#filter_marketplace').val(),
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                    "status" : $('#filter_status').val()
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id"},
                {"data": "int_to"},
                {"data": "store_name"},
                {"data": "name"},
                {"data": "comission"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "status"},
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function mountPaymentMethodComissionTable() {

        if ($('#paymentMethodComissionTable').length) {
            $('#paymentMethodComissionTable').DataTable().destroy();
        }

        // initialize the datatable
        paymentMethodComissionTable = $('#paymentMethodComissionTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "<?php echo lang('application_search'); ?>"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'commissioning/payment_method_comissions/',
                "type": 'POST',
                "data": {
                    "store_id" : $('#filter_store').val(),
                    "marketplace" : $('#filter_marketplace').val(),
                    "startDate" : $('#filter_start_date').val(),
                    "endDate" : $('#filter_end_date').val(),
                    "status" : $('#filter_status').val()
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id"},
                {"data": "int_to"},
                {"data": "store_name"},
                {"data": "name"},
                {"data": "start_date"},
                {"data": "end_date"},
                {"data": "status"},
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function newComission(type){

        let id = $('#filter_store').val();
        let marketplace = $('#filter_marketplace').val();

        if (marketplace === '0') {

            Swal.fire({
                icon: 'error',
                title: 'Selecione um marketplace primeiro para filtrar'
            });

            return false;

        }else if (id === '0') {

            Swal.fire({
                icon: 'error',
                title: 'Selecione uma loja primeiro para filtrar'
            });

            return false;

        }else{

            $('#insertModal').modal();

            $("#modal-body").html('<i class="fa fa-spin fa-spinner"></i>');

            $("#modal-title").text('<?php echo lang('application_add_new_comission'); ?>');

            var pageURL = base_url.concat("commissioning/new_comission/"+type+"/"+id+"/"+marketplace);

            $.get( pageURL, function( data ) {
                $("#modal-content").html(data);
            });

        }

    }

    function edit(id){

        $("#modal-edit-body").html('<i class="fa fa-spin fa-spinner"></i>');

        var pageURL = base_url.concat("commissioning/edit_comission/"+id);

        $.get( pageURL, function( data ) {
            $("#form-edit").replaceWith(data);
        });

    }

</script>

<style>
    .dataTables_scrollBody {
        overflow: visible  !important;
    }
</style>