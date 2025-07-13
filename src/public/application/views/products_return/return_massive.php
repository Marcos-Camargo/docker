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

                <a href="<?php echo base_url('ProductsReturn/returnMassiveCreate') ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i>
                    <?= lang('application_load_file'); ?>
                </a>

                <div class="box box-info mt-2">

                    <div class="box-header with-border">

                        <h3 class="box-title">

                            <i class="fa fa-filter fa-2x" title="Filtro"></i>
                            Filtros

                        </h3>

                    </div>

                    <div class="box-header with-border">

                        <div class="row">

                            <div class="form-group col-md-2 col-xs-2">
                                <label for="filter_user">Usuário</label>
                                <select class="form-control select2" name="filter_user" id="filter_user" onchange="filter()">
                                    <option value="0">Todos os Usuários</option>
                                    <?php
                                    foreach ($users as $user){
                                        ?>
                                        <option value="<?php echo $user['user']; ?>"><?php echo $user['user']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="box box-info mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= lang('application_massive_order_refund'); ?></h3>
                    </div>
                    <div class="box-body">
                        <table id="itensDatatable" class="table table-bordered table-striped table-condensed">
                            <thead>
                            <tr>
                                <th><?= lang('application_id'); ?></th>
                                <th data-orderable="false"><?= lang('application_file'); ?></th>
                                <th><?= lang('application_status'); ?></th>
                                <th><?= lang('application_user'); ?></th>
                                <th><?= lang('application_date_create'); ?></th>
                                <th class="col-md-2" data-orderable="false"><?= lang('application_action'); ?></th>
                            </tr>
                            </thead>
                        </table>
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

<script type="text/javascript">

    var itensDatatable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

        $("#mainOrdersNav").addClass('active');
        $("#ReturnOrderNavMassive").addClass('active');

        filter();

    });

    function mountItensDatatableDataTable() {

        if ($('#itensDatatable').length) {
            $('#itensDatatable').DataTable().destroy();
        }

        // initialize the datatable
        itensDatatable = $('#itensDatatable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "Usuário"
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": {
                "url": base_url + 'ProductsReturn/returnMassiveItens/',
                "type": 'POST',
                "data": {
                    "user" : $('#filter_user').val(),
                }
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id"},
                {"data": "file"},
                {"data": "status"},
                {"data": "user"},
                {"data": "created_at"},
                {"data": "actions"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    function filter(){

        mountItensDatatableDataTable();

        return false;

    }

</script>

<style>
    .dataTables_scrollBody {
        overflow: visible  !important;
    }
</style>