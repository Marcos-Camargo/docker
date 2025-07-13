<div class="content-wrapper">
    <style>
        .dropdown.bootstrap-select.show-tick.form-control {
            display: block;
            width: 100%;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
        }

        .bootstrap-select>.dropdown-toggle.bs-placeholder {
            padding: 5px 12px;
        }

        .bootstrap-select .dropdown-toggle .filter-option {
            background-color: white !important;
        }

        .bootstrap-select .dropdown-menu li a {
            border: 1px solid gray;
        }

        .input-group-addon {
            cursor: pointer;
        }
    </style>
    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <section class="content">
        <div id="messages"></div>
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
        <div class="" id="collapseFilter">
            <div class="col-md-3 form-group no-padding" style="<?= (count($stores_filter) > 1) ? "" : "display: none;" ?>">
                <label for="search_store" class="normal"><?= $this->lang->line('application_store'); ?></label>
                <select class="form-control selectpicker show-tick" id="search_store" onchange="personalizedSearch()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?= $this->lang->line('application_search_for_store'); ?>">
                    <?php foreach ((array)$stores_filter as $store_filter) { ?>
                        <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="search_phases" class="normal"><?= $this->lang->line('application_phase'); ?></label>
                <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="search_phases" multiple="multiple" title="<?= $this->lang->line('application_select'); ?>" onchange="personalizedSearch()">
                    <option value="" disabled><?= $this->lang->line('application_select'); ?></option>
                    <?php foreach ($phases as $k => $v) { ?>
                        <option value="<?php echo $v['id'] ?>"><?php echo $v['name'] ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="search_responsable" class="normal"><?= $this->lang->line('application_responsible_name'); ?></label>
                <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="search_responsable" multiple="multiple" title="<?= $this->lang->line('application_select'); ?>" onchange="personalizedSearch()">
                    <option value="" disabled><?= $this->lang->line('application_select'); ?></option>
                    <?php foreach ($users as $k => $v) { ?>
                        <option value="<?php echo $v['id'] ?>"><?php echo $v['name'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="pull-right">
                    <a type="button" href="<?= base_url('export/phasesXls') ?>" class="btn btn-primary" id="exportcsv"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_export'); ?> </a>
                    <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear'); ?></button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                                <tr>
                                    <th><?= $this->lang->line('application_store_id'); ?></th>
                                    <th><?= $this->lang->line('application_store_name'); ?></th>
                                    <th><?= $this->lang->line('application_phase'); ?></th>
                                    <th><?= $this->lang->line('application_responsible_name'); ?></th>
                                    <th><?= $this->lang->line('application_store_meta'); ?></th>
                                    <th><?= $this->lang->line('application_action'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="editGoalAndPhaseModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?= $this->lang->line('application_change_phase'); ?></h4>
            </div>
            <form role="form" action="<?php echo base_url('phases/update') ?>" method="post" id="createBrandForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>" />
                <div class="modal-body">
                    <input id="store_id" name="store_id" type="hidden" \>
                    <h4 id="store_name"></h2>
                    <div class="form-group">
                        <label for="phase_id"><?= $this->lang->line('application_phase'); ?></label>
                        <select class="form-control" aria-label="Default select example" id="phase_id" name="phase_id">
                            <option selected disabled><?= $this->lang->line('application_select_phase_name') ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $this->lang->line('application_store_meta'); ?></label>
                        <input type="text" name="goal_month" id="goal_month" class="form-control" value="0,00">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- <?php if (in_array('deleteProduct', $user_permission)) : ?>
    <div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= $this->lang->line('application_delete_product'); ?><span id="deleteproductname"></span></h4>
                </div>

                <form role="form" action="<?php echo base_url('products/remove') ?>" method="post" id="removeForm">
                    <div class="modal-body">
                        <p><?= $this->lang->line('messages_delete_message_confirm'); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                        <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_confirm'); ?></button>
                    </div>
                </form>

            </div>
        </div>
    </div>
<?php endif; ?> -->

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="https://cdn.rawgit.com/plentz/jquery-maskmoney/master/dist/jquery.maskMoney.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script type="text/javascript">
    var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
        csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";
    var stores=<?=json_encode($stores_filter)?>;
    $(document).ready(function() {
        console.log(stores);
        $('[name="goal_month"]').maskMoney({
            thousands: '.',
            decimal: ',',
            precision: 2,
            allowZero: true,
            affixesStay: true
        });
        $("#mainPhasesNav").addClass('active');
        $("#managePhasesNav").addClass('active');
        $("#hide").click(function() {
            $("#filterModal").hide();
            $("#showActions").show();
        });
        $("#show").click(function() {
            $("#filterModal").show();
            $("#showActions").hide();
        });


        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "sortable": true,
            "scrollX": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'phases/fetchPhasesStoresData',
                data: {
                    [csrfName]: csrfHash
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

        $.get(base_url + 'phases/getphases', function(data, status) {
            data = JSON.parse(data);
            data.forEach(element => {
                $('#phase_id').append('<option value="' + element.id + '">' + element.name + '</option>');
            });
        });
        $.get(base_url + 'users/get_name_id_active_users', function(data, status) {
            data = JSON.parse(data);
            data.forEach(element => {
                $('#responsable_select').append('<option value="' + element.id + '">' + element.name + '</option>');
            });
        });

        $('#manageTable').on('draw.dt', function() {
            $('#manageTable [data-toggle="tootip"]').tooltip();
        });

        $('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            // alert('oi');
            $($.fn.dataTable.tables(true)).DataTable()
                .columns.adjust()
                .responsive.recalc();
        });
        $('input[type="checkbox"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });

        reloadFiltersExport();
    });

    function editPhase(store, phase, goal_month) {
        $('#phase_id').val(phase);
        $('#store_id').val(store);
        let element=stores.filter((element)=>{
            if(store==element.id){
                return element;
            }
        });
        console.log(element[0]);
        $('#store_name').empty();
        $('#store_name').append(element[0].name);
        $('#goal_month').val(('' + goal_month).replace(".", ","));
        $("#goal_month").maskMoney('mask');
    }

    function personalizedSearch() {
        let store = $('#search_store').val();
        let phases = $('#search_phases').val();
        console.log(phases);
        let responsable = $('#search_responsable').val();
        manageTable.destroy();

        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'phases/fetchPhasesStoresData',
                data: {
                    store,
                    phases,
                    responsable,
                    [csrfName]: csrfHash
                },
                pages: 2, // number of pages to cache
            })
        });
        reloadFiltersExport();
    }

    function clearFilters() {
        $('#search_store').val('');
        $('#search_phases').val('');
        $('#search_responsable').val('');
        personalizedSearch();
    }

    function changeFilter() {
        let text = document.getElementById('buttonCollapseFilter').innerHTML;
        if (text == 'Ocultar Filtros') {
            document.getElementById('buttonCollapseFilter').innerHTML = 'Exibir Filtros';
        } else {
            document.getElementById('buttonCollapseFilter').innerHTML = 'Ocultar Filtros';
        }
    }

    const reloadFiltersExport = () => {
        setHrelButtom('exportcsv', '');
    }
    const setHrelButtom = (id, adicional_param) => {
        const href = $('#' + id).attr('href');
        console.log(123, href);
        const splitHref = href.split('?');
        let filter = '?';
        $('#collapseFilter input').each(function() {
            if (typeof $(this).attr('id') !== "undefined" && typeof $(this).val() !== "undefined" && $(this).val() != '') {
                filter += `${$(this).attr('id')}=${$(this).val()}&`;
            }
        });
        $('#collapseFilter select').each(function() {
            if (typeof $(this).attr('id') !== "undefined" && typeof $(this).val() !== "undefined" && $(this).val() != 0) {
                filter += `${$(this).attr('id')}=${$(this).val()}&`;
            }
        });
        filter = filter.substring(0, filter.length - 1);

        let new_href = splitHref[0] + filter;
        if (adicional_param != '') {
            new_href = new_href + "&" + adicional_param
        }
        $('#' + id).attr('href', new_href);
    }
</script>