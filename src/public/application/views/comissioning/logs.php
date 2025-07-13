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
                        <h3 class="box-title"><?= lang('application_log_history_title'); ?></h3>
                    </div>
                    <div class="box-body">

                        <table id="logsTable" class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo de Comissionamento</th>
                                    <th>Nome</th>
                                    <th>Período de Vigência Atual</th>
                                    <th>Status Atual</th>
                                    <th>Status Na Alteração</th>
                                    <th>Alteração</th>
                                    <th>Data de Alteração</th>
                                    <th>Usuário</th>
                                    <th>Ação Executada</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>

            </div>
            <!-- col-md-12 -->
        </div>
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <a href="<?php echo base_url('commissioning'); ?>" class="btn btn-info">Voltar para Comissionamentos</a>

            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">

    var logsTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {

        $("#hierarchyComissionNav").addClass('active');
        $(".select2").select2();

        mountlogsTable();

    });

    function mountlogsTable() {

        if ($('#logsTable').length) {
            $('#logsTable').DataTable().destroy();
        }

        // initialize the datatable
        logsTable = $('#logsTable').DataTable({
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
                "url": base_url + 'commissioning/logs_data/<?php echo $commissioningId; ?>',
                "type": 'POST'
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "id", "class" : 'hidden'},
                {"data": "type"},
                {"data": "name"},
                {"data": "period"},
                {"data": "current_status"},
                {"data": "status_updated_at"},
                {"data": "changes"},
                {"data": "updated_at"},
                {"data": "user"},
                {"data": "action"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

</script>

<style>
    .dataTables_scrollBody {
        overflow: visible  !important;
    }
</style>