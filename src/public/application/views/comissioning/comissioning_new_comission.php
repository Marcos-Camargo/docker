<?php
use App\Libraries\Enum\ComissioningType;
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modal-title"><?php echo $title; ?></h4>
</div>
<form role="form" action="" method="post" id="form" enctype="multipart/form-data">
    <input type="hidden" name="type" value="<?php echo $type; ?>">
    <input type="hidden" name="int_to" value="<?php echo $int_to; ?>">
    <div class="modal-body" id="modal-body">
        <div id="inputs">

            <?php
            if ($type != ComissioningType::PRODUCT){
            ?>

                <div class="row" style="display: none;">

                    <div class="form-group col-md-6 col-xs-6">
                        <label for="store_id"><?= $this->lang->line('application_label_store'); ?></label>
                        <select class="form-control select2" name="store_id">
                            <?php
                            foreach ($stores as $store){
                                ?>
                                <option value="<?php echo $store['id']; ?>" <?php echo $store['id'] == $store_id ? 'selected="selected"' : ''; ?>>
                                    <?php echo $store['id']; ?> - <?php echo $store['name']; ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>

                </div>

            <?php
            }

            if ($type == ComissioningType::BRAND){
                if (isset($brands) && $brands){
                ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="brand_id"><?= $this->lang->line('application_brand'); ?></label>
                            <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="brand_id" name="brand_id[]" multiple="multiple" title="<?=$this->lang->line('application_brand');?>" multiple>
                                <?php
                                foreach ($brands as $brand){
                                    ?>
                                    <option value="<?php echo $brand['id']; ?>">
                                        <?php echo $brand['name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                <?php
                }else{
                    ?>
                    <div class="alert alert-danger">
                        Vendedor não possui nenhum produto cadastrado no Marketplace selecionado.
                    </div>
                <?php
                }

            }

            if ($type == ComissioningType::CATEGORY){
                if (isset($categories) && $categories){
                ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="category_id"><?= $this->lang->line('application_category'); ?></label>
                            <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="category_id" name="category_id[]" multiple="multiple" title="<?=$this->lang->line('application_category');?>" multiple>
                                <?php
                                foreach ($categories as $category){
                                    ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo $category['name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                <?php
                }else{
                    ?>
                    <div class="alert alert-danger">
                        Vendedor não possui nenhum produto cadastrado no Marketplace selecionado.
                    </div>
                    <?php
                }
            }

            if ($type == ComissioningType::TRADE_POLICY){
                if (isset($trade_policies) && $trade_policies){
                ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="trade_policy_id"><?= $this->lang->line('application_credentials_erp_sales_channel_vtex'); ?></label>
                            <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="trade_policy_id" name="trade_policy_id[]" multiple="multiple" title="<?=$this->lang->line('application_credentials_erp_sales_channel_vtex');?>" multiple>
                                <?php
                                foreach ($trade_policies as $trade_policy){
                                    ?>
                                    <option value="<?php echo $trade_policy['id']; ?>">
                                        <?php echo $trade_policy['trade_policy_name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                <?php
                }else{
                    ?>
                    <div class="alert alert-danger">
                        Nenhuma política comercial encontrada no Marketplace Selecionado
                    </div>
                    <?php
                }
            }

            if ($allow_create && $type != ComissioningType::PRODUCT){
            ?>

                <div class="row">

                    <div class="form-group col-md-6 col-xs-6">
                        <label for="comission"><?= $this->lang->line('application_commission'); ?></label>
                        <div class="input-group">
                            <input type="text" id="comission" name="comission" onchange="validatePercent(this)" pattern="^\d{0,3}(\.\d{0,2})?$" class="form-control">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>

                </div>

            <?php
            }

            if ($allow_create){
            ?>

                <div class="row">

                    <div class="form-group col-md-6 col-xs-6">
                        <label for="start_date"><?= $this->lang->line('payment_balance_transfers_date_start'); ?></label>
                        <input type="text" id="start_date" name="start_date" class="form-control dateTime" value="<?php echo date('d/m/Y H:i:s'); ?>">
                        <small>dd/mm/aaaa hh:mm:ss</small>
                    </div>

                </div>
                <div class="row">

                    <div class="form-group col-md-6 col-xs-6">
                        <label for="end_date"><?= $this->lang->line('payment_balance_transfers_date_end'); ?></label>
                        <input type="text" id="end_date" name="end_date" class="form-control dateTime">
                        <small>dd/mm/aaaa hh:mm:ss</small>
                    </div>

                </div>

            <?php
            }

            if ($type == ComissioningType::PRODUCT){
                ?>
                <div class="file-drop-area small-shadow-top mb-4">
                    <span class="file-message"><?= $this->lang->line('drag_drop_file') ?></span>
                    <input class="file-input-hidden" type="file" name="file">
                </div>
                <a href="<?php echo base_url('commissioning/download_example/'.$int_to); ?>" class="mb-3">Baixar Planilha de Exemplo</a>
                <br>
                <br>
                <button type="button" class="btn btn-select-file btn-primary" onclick="selectFile()">
                    <i class="fas fa-plus-circle"></i>
                    <?= $this->lang->line('select_file') ?>
                </button>
            <?php
            }
            ?>

        </div>
        <div id="loading" class="text-center" style="display: none;">
            <i class="fa fa-spin fa-spinner" title="Processando"></i>
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('payment_balance_transfers_modal_btn_cancel')?></button>
        <?php
        if ($allow_create){
        ?>
            <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        <?php
        }
        ?>
    </div>
</form>
<script>
    $('.selectpicker').selectpicker('refresh');

    $('.dateTime').datetimepicker({
        format: "DD/MM/YYYY HH:mm:ss"
    });
    $('#form').on('submit', function(event) {

        event.preventDefault();

        let formData = new FormData(this);

        // Adicionando campos manualmente ao FormData
        formData.append('type', $('#form').find('[name="type"]').val());
        formData.append('store_id', parseInt($('#form').find('[name="store_id"]').val()));
        formData.append('int_to', $('#form').find('[name="int_to"]').val());
        formData.append('brand_id', $('#form').find('#brand_id').val());
        formData.append('category_id', $('#form').find('#category_id').val());
        formData.append('trade_policy_id', $('#form').find('#trade_policy_id').val());
        formData.append('comission', '');
        if ($('#form').find('[name="comission"]').length) {
            formData.append('comission', $('#form').find('[name="comission"]').val());
        }
        formData.append('start_date', $('#form').find('[name="start_date"]').val());
        formData.append('end_date', $('#form').find('[name="end_date"]').val());

        $('#form').find('#inputs').hide();
        $('#form').find('#loading').show();

        $.ajax({
            url: `${base_url}/commissioning/save_comission`,
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: response => {
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.message ?? 'Comissão cadastrada com sucesso'
                });

                if (response.success) {
                    <?php
                    if ($type == ComissioningType::SELLER){
                    ?>
                        $('#sellerComissionTable').DataTable().ajax.reload();
                    <?php
                    }
                    if ($type == ComissioningType::BRAND){
                    ?>
                        $('#brandComissionTable').DataTable().ajax.reload();
                    <?php
                    }
                    if ($type == ComissioningType::CATEGORY){
                    ?>
                        $('#categoryComissionTable').DataTable().ajax.reload();
                    <?php
                    }
                    if ($type == ComissioningType::TRADE_POLICY){
                    ?>
                        $('#tradePolicyComissionTable').DataTable().ajax.reload();
                    <?php
                    }
                    if ($type == ComissioningType::PRODUCT){
                    ?>
                        $('#paymentMethodComissionTable').DataTable().ajax.reload();
                    <?php
                    }
                    ?>
                    $('#insertModal').modal('hide');
                }else{
                    $('#inputs').show();
                    $('#loading').hide();
                }
            }
        });

        return false;

    });

    function selectFile(){
        $('.file-input-hidden').click();
    }

    $(document).on('change', '.file-input-hidden', function() {

        var filesCount = $(this)[0].files.length;

        var textbox = $('.file-message');

        if (filesCount === 1) {
            var fileName = $(this).val().split('\\').pop();
            textbox.html('<h4>Arquivo Carregado</h4><span class="text-black">'+fileName+'</span>');
            $('.btn-select-file').removeClass('btn-primary').addClass('btn-default');
        } else {
            textbox.text(filesCount + ' <?= $this->lang->line('selected_files'); ?>');
        }
    });
</script>