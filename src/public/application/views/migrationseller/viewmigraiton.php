<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_edit";
    $this->load->view('templates/content_header', $data);
    ?>
    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 ">
                <div class="box">
                    <div class="box-body">
                        <label for="selectstore"><?= $this->lang->line('application_store'); ?></label>
                        <select class="form-control" id="selectstore" name="selectstore" required onchange="selectStore()">
                            <option selected disabled value=""><?= $this->lang->line('application_select'); ?></option>
                            <?php foreach ($stores_filter as $stores_filter) {
                            $enable = $defenable;
                            if ($stores_filter['id'] == $this->session->flashdata('stores_filter')) {
                              $enable = "";
                            }
                            ?>
                                <option <?php echo $enable; ?> value="<?php echo $stores_filter['id']; ?>" ><?php echo $stores_filter['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 ">

                <?php if ($this->session->flashdata('success')) : ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')) : ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <div class="box">
                    <div class="box-body">
                        <div class="row">
                            <?php
                            if (validation_errors()) {
                                foreach (explode("</p>", validation_errors()) as $erro) {
                                    $erro = trim($erro);
                                    if ($erro != "") { ?>
                                        <div class="alert alert-error alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <?php echo $erro . "</p>"; ?>
                                        </div>
                            <?php
                                    }
                                }
                            } ?>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= $this->lang->line('application_id'); ?></th>
                                            <th><?= $this->lang->line('application_seller_sku'); ?></th>
                                            <th><?= $this->lang->line('application_name_on_seller_center'); ?></th>
                                            <th><?= $this->lang->line('application_name_on_marketplace'); ?></th>
                                            <th><?= $this->lang->line('application_actions'); ?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="col-md-12">
                                <button class="btn btn-success" onclick="approveAllProducts()"><?=$this->lang->line('application_approve_migration_all');?></button>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>

    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";
    var manageTable;
    let id = $('#id').val();
    let sku_name = $('#sku_name').val();
    let id_sku = $('#id_sku').val();
    let product_name = $('#product_name').val();
    let sku_marketplace = $('#sku_marketplace').val();
    var procura = "";
    var procura_count = "";
    var store_id = null;

    $(document).ready(function() {
        $("#migrationSeller").addClass('active');
        $('#selectstore').change(function() {
            store_id = $('#selectstore').val();
        });
        loadmanegeTable(store_id);
        $("#selectstore").select2();

        $('#manageTable').on('draw.dt', function() {
            $('#manageTable [data-toggle="tootip"]').tooltip();
        });

        $('#manageTable').on('preXhr.dt', function(e, settings, data) {
            //reloadFiltersExport({columns: data.columns, order: data.order});
        });
    });
    function loadmanegeTable(searchStoreId = null){
        if(searchStoreId){
            procura = " AND psm.store_id = " + searchStoreId;
            procura_count = " AND store_id = " + searchStoreId;
        }
        else{
            procura = ""
            procura_count = ""
        }
        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            'columnDefs': [{
                'targets': 0,
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center'
            }],
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'MigrationSeller/fetchProductsMigrationData',
                data: {
                    id: 'id',
                    id_sku: id_sku,
                    product_name: product_name,
                    procura: procura,
                    procura_count: procura_count,
                    sku_marketplace: sku_name,
                    store_id: $('#selectstore').val()
                },
                pages: 2, // number of pages to cache
            }),

            "createdRow": function(row, data, dataIndex) {
                $(row).find('td:eq(3)').addClass('d-flex align-items-center');
            },
            "initComplete": function(settings, json) {
                $('#manageTable [data-toggle="tootip"]').tooltip();
            }
        });


    }
    function changeMigrationApproval(event, id, product_id, id_sku) {

        // alert(id)
        $.post(base_url + 'MigrationSeller/approveProducMigration', {
            id: id,
            id_sku: id_sku,
            product_id: product_id,
            store_id: $('#selectstore').val()
        }, function(response) {
            location.reload();
        });
    }
    function changeMigrationRepproval(event, id, product_id, id_sku) {
        // alert(id + ' - ' + id_sku + ' - ' + product_id)

        $.post(base_url + 'MigrationSeller/reproveProducMigration', {
            id: id,
            id_sku: id_sku,
            product_id: product_id,
            store_id: $('#selectstore').val()
        }, function(response) {
            location.reload();
        });
    }
    function approveAllProducts(store_id) {
        store_id = $('#selectstore').val();

        $.post(base_url + 'MigrationSeller/aproveAllProductMigration', {
            store_id: store_id
        }, function(response) {
            location.reload();
        });
    }
    function selectStore(){
        store_id = $('#selectstore').val();
        manageTable.destroy();
        loadmanegeTable(store_id);
    }
</script>