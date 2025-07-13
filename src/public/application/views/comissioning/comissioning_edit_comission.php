<?php
use App\Libraries\Enum\ComissioningType;
?>
<form role="form-edit" action="" method="post" id="form-edit">
    <input type="hidden" name="id" value="<?php echo $commissioning['id']; ?>">
    <div class="modal-body" id="modal-edit-body">
        <div class="inputs">

            <?php
            if ($is_scheduled){

                if (isset($brands) && $brands){
                    ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="brand_id"><?= $this->lang->line('application_brand'); ?></label>
                            <select class="form-control select2" name="brand_id">
                                <?php
                                foreach ($brands as $brand){
                                    ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php if ($current_brand_id == $brand['id']) { echo "selected='selected'"; } ?>>
                                        <?php echo $brand['name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <?php
                }
                if (isset($categories) && $categories){
                    ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="category_id"><?= $this->lang->line('application_category'); ?></label>
                            <select class="form-control select2" name="category_id">
                                <?php
                                foreach ($categories as $category){
                                    ?>
                                    <option value="<?php echo $category['id']; ?>" <?php if ($current_category_id == $category['id']) { echo "selected='selected'"; } ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <?php
                }
                if (isset($trade_policies) && $trade_policies){
                    ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="trade_policy_id"><?= $this->lang->line('application_credentials_erp_sales_channel_vtex'); ?></label>
                            <select class="form-control select2" name="trade_policy_id">
                                <?php
                                foreach ($trade_policies as $trade_policy){
                                    ?>
                                    <option value="<?php echo $trade_policy['id']; ?>" <?php if ($current_trade_policy_id == $trade_policy['id']) { echo "selected='selected'"; } ?>>
                                        <?php echo $trade_policy['trade_policy_name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <?php
                }

                if ($commissioning['type'] != ComissioningType::PRODUCT){
                    ?>

                    <div class="row">

                        <div class="form-group col-md-6 col-xs-6">
                            <label for="comission"><?= $this->lang->line('application_commission'); ?></label>
                            <div class="input-group">
                                <input type="text" id="comission" name="comission" onchange="validatePercent(this)" pattern="^\d{0,3}(\.\d{0,2})?$" class="form-control" value="<?php echo $commision; ?>">
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>

                    </div>

                    <?php
                }

            }
            ?>

            <div class="row">

                <div class="form-group col-md-6 col-xs-6">
                    <label for="start_date"><?= $this->lang->line('payment_balance_transfers_date_start'); ?></label>
                    <?php
                    if ($is_scheduled){
                        ?>
                        <input type="text" id="start_date" name="start_date" class="form-control dateTime" value="<?php echo datetimeBrazil($commissioning['start_date']); ?>">
                        <small>dd/mm/aaaa hh:mm:ss</small>
                        <?php
                    }else{
                        echo datetimeBrazil($commissioning['start_date']);
                        ?>
                        <input type="hidden" id="start_date" name="start_date" class="form-control" value="<?php echo datetimeBrazil($commissioning['start_date']); ?>">
                        <?php
                    }
                    ?>
                </div>

            </div>
            <div class="row">

                <div class="form-group col-md-6 col-xs-6">
                    <label for="end_date"><?= $this->lang->line('payment_balance_transfers_date_end'); ?></label>
                    <input type="text" id="end_date" name="end_date" class="form-control dateTime" value="<?php echo datetimeBrazil($commissioning['end_date']); ?>">
                    <small>dd/mm/aaaa hh:mm:ss</small>
                </div>

            </div>

            <?php
            if ($commissioning['type'] == ComissioningType::PRODUCT && $is_scheduled){
                ?>
                <div class="file-drop-area small-shadow-top mb-4">
                    <span class="file-message"><?= $this->lang->line('drag_drop_file') ?></span>
                    <input class="file-input-hidden" type="file" name="file">
                </div>
                <button type="button" class="btn btn-select-file btn-primary" onclick="selectFile()">
                    <i class="fas fa-plus-circle"></i>
                    <?= $this->lang->line('select_file') ?>
                </button>
                <?php
            }
            ?>

        </div>
        <div id="edit-loading" class="text-center" style="display: none;">
            <i class="fa fa-spin fa-spinner" title="Processando"></i>
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('payment_balance_transfers_modal_btn_cancel')?></button>
        <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
    </div>
    <script>
        $('.dateTime').datetimepicker({
            format: "DD/MM/YYYY HH:mm:ss"
        });

        $('#form-edit').on('submit', function(event) {
            event.preventDefault();
            console.log("Form submitted!");

            let formData = new FormData(this);

            // Adicionando campos manualmente ao FormData
            formData.append('id', $('#form-edit').find('[name="id"]').val());
            formData.append('start_date', $('#form-edit').find('[name="start_date"]').val());
            formData.append('end_date', $('#form-edit').find('[name="end_date"]').val());

            if (checkPast($('[name="end_date"]').val())){
                $('.inputs').hide();
                $('#edit-loading').show();

                $.ajax({
                    url: `${base_url}/commissioning/save_edit_comission`, // Substitua pelo caminho para o seu script PHP
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Arquivo e dados enviados com sucesso:', response);
                        Swal.fire({
                            icon: response.success ? 'success' : 'error',
                            title: response.message ?? 'Comissão alterada com sucesso'
                        });

                        if (response.success) {
                            <?php
                            if ($commissioning['type'] == ComissioningType::SELLER){
                            ?>
                            $('#sellerComissionTable').DataTable().ajax.reload();
                            <?php
                            }
                            if ($commissioning['type'] == ComissioningType::BRAND){
                            ?>
                            $('#brandComissionTable').DataTable().ajax.reload();
                            <?php
                            }
                            if ($commissioning['type'] == ComissioningType::CATEGORY){
                            ?>
                            $('#categoryComissionTable').DataTable().ajax.reload();
                            <?php
                            }
                            if ($commissioning['type'] == ComissioningType::TRADE_POLICY){
                            ?>
                            $('#tradePolicyComissionTable').DataTable().ajax.reload();
                            <?php
                            }
                            if ($commissioning['type'] == ComissioningType::PRODUCT){
                            ?>
                            $('#paymentMethodComissionTable').DataTable().ajax.reload();
                            <?php
                            }
                            if ($commissioning['type'] == ComissioningType::PRODUCT){
                            ?>
                            $('#paymentMethodComissionTable').DataTable().ajax.reload();
                            <?php
                            }
                            ?>
                            $('#editModal').modal('hide');
                        } else {
                            $('.inputs').show();
                            $('#edit-loading').hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erro ao enviar arquivo e dados:', status, error);
                        $('#inputs').show();
                        $('#edit-loading').hide();
                    }
                });
            }

            return false;

        });

        function checkPast(dateString) {
            // Dividir a string da data em partes
            const [datePart, timePart] = dateString.split(' ');
            const [day, month, year] = datePart.split('/').map(Number);
            const [hours, minutes, seconds] = timePart.split(':').map(Number);

            // Criar um objeto Date
            const date = new Date(year, month - 1, day, hours, minutes, seconds);

            // Verificar se a data fornecida é válida
            if (isNaN(date.getTime())) {
                alert("A data fornecida não é válida.");
                return;
            }

            // Obter a data atual
            const now = new Date();

            // Verificar se a data fornecida é uma data passada
            if (date < now) {
                return confirm('A data final está no passado, você deseja encerrar o comissionamento?');
            } else {
                return true;
            }
        }

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
</form>