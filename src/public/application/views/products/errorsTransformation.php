<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_product_error_send')?></h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                            <tr>
                                <th><?=$this->lang->line('application_image');?></th>
                                <th><?=$this->lang->line('application_sku');?></th>
                                <th><?=$this->lang->line('application_name');?></th>
                                <th><?=$this->lang->line('application_price');?></th>
                                <th><?=$this->lang->line('application_qty');?></th>
                                <th><?=$this->lang->line('application_store');?></th>
                                <th><?=$this->lang->line('application_id');?></th>
                                <th><?=$this->lang->line('application_check_error');?></th>
                            </tr>
                            </thead>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewError">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_view');?> <span class="product_name"></span></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {
        manageTable = $('#manageTable').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url: base_url + 'products/fetchProductDataError',
                pages: 2, // number of pages to cache
            } )
        });

    });

    $(document).on('click', '.viewError', function (){
        const prd_id = $(this).attr('prd-id');
        const action = base_url + 'products/getErrosTransformationAjax';

        $('#viewError').modal();
        $('#viewError .modal-body').empty().html('<h3 class="text-center"> Carregando ... <i class="fa fa-spinner fa-spin"></i></h3>');

        // get error product
        $.post( action, { prd_id }, response => {

            if(!response['success'] || !response['data']) {
                AlertSweet.fire({
                    icon: 'error',
                    title: response['data'] ?? "Não foi possível recuperar os dados desse erro."
                });

                return false;
            }

            let data = `<div class="row mb-2 pb-2" style="border-bottom: 1px solid #ccc"><div class="col-md-12 d-flex justify-content-center"><a class="btn btn-primary col-md-4" target="_blank" href="<?=base_url("products/update/")?>${prd_id}"><?=$this->lang->line('application_correct_error');?></a></div></div>`;
            let count = 0;
            let style = '';
            $.each(response['data'], function( index, value ) {
                if (count === 1) style = 'border-top: 1px solid #ccc;padding-top: 15px';

                data += `<div class="row" style="${style}">
                            <div class="form-group col-md-4"><label><?=$this->lang->line('application_marketplace');?>: </label> ${value.mkt}</div>
                            <div class="form-group col-md-4"><label><?=$this->lang->line('application_step');?>: </label> ${value.step}</div>
                            <div class="form-group col-md-4"><label><?=$this->lang->line('application_date');?>: </label> ${value.date}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-group col-md-12">
                                <label><?=$this->lang->line('application_error');?></label>
                                <div class="col-md-12" style="border: 1px solid #ccc;padding: 10px;">
                                    ${value.message}
                                </div>
                            </div>
                        </div>`;
                count++;
            });

            $('#viewError .modal-body').empty().html(data);

        }, "json")
        .fail(error => {
            console.log(error);
            $('#viewError').modal('hide');
            AlertSweet.fire({
                icon: 'error',
                title: "Não foi possível recuperar os dados, atualize a página e tente novamente."
            });
        });

    })

</script>
