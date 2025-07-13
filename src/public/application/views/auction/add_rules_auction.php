<div class="content-wrapper">
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div class="box">
          <div class="box-body">
            <div class="row">
              <div class="col-md-12 col-xs-12">
                  <table id="manageTable" class="table table-bordered table-striped mt-5">
                      <thead>
                          <tr>
                              <th class="text-center"><?=$this->lang->line('application_id');?></th>
                              <th class="text-center"><?=$this->lang->line('application_marketplace');?></th>
                              <th class="text-center"><?=$this->lang->line('application_Rule_Name');?></th>
                              <th class="text-center"><?=$this->lang->line('application_action');?></th>
                          </tr>
                      </thead>
                  </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="modalUpdateCreateRule" tabindex="-1" role="dialog" aria-labelledby="modalUpdateCreateRule" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formUpdateCreateRules">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUpdateCreateRule">Alterar Regra</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="from-group col-md-12">
                            <label for="marketplace">Marketplace</label>
                            <select name="marketplace" class="form-control" disabled>
                                <?php foreach ($mkt as $m) {?>
                                    <option value="<?=$m['id'];?>"><?=$m['name'];?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="from-group col-md-12">
                            <label for="rules">Regras</label>
                            <select name="rules" class="form-control">
                                <option>Nenhuma</option>
                                <?php foreach ($rules as $r) {?>
                                    <option value="<?=$r['id'];?>"><?=$r['descricao'];?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
                <input type="hidden" name="rule_id">
            </form>
        </div>
    </div>
</div>

<style>
    div.dt-buttons {
        float: right;
        width: 20%;
    }

    #manageTable_filter{
        float: left;
        width: 30%;
    }

    #manageTable_filter label,
    #manageTable_filter input[type="search"] {
        width: 100%;
    }

    #manageTable {
        width: 100% !important;
    }
</style>

<script type="application/javascript" src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
<script>
    const base_url = "<?php echo base_url(); ?>";

    $(function(){
        // initialize the datatable
        $('#manageTable').DataTable({
            'paging': false,
            "destroy": true,
            "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
            'ajax': {
                "url": base_url + 'Auction/fetchData'
            },
            'columnDefs': [{
                "targets": '_all',
                "className": "text-center",
            }],
            dom: "Bfrtip",
            "buttons": [
                {
                    text: '<i class="fa fa-plus"></i> Adicionar Regra de Leilão',
                    className: 'btn btn-success col-md-12 create-rule'
                }
            ]
        });

        $("#mainLogisticsNav").addClass('active');
        $("#auctionRulesNav").addClass('active');
    });

    $(document).on('click', '.update-rule, .create-rule', function(e){
        const is_create = $(e.target).hasClass("create-rule") || $(e.target).closest(".create-rule").hasClass("create-rule");

        const rule_status       = $(this).data('rule-status');
        const rule_marketplace  = $(this).data('rule-mkt');

        $('#modalUpdateCreateRule form [name="marketplace"]').prop('disabled', !is_create).val(is_create ? '' : rule_marketplace);
        $('#modalUpdateCreateRule form [name="rule_id"]').val(is_create ? '' : $(this).data('rule-id'));

        $('#modalUpdateCreateRule form [name="rules"]').val(is_create ? '' : rule_status);

        $('#modalUpdateCreateRule').modal();
    });


    $('#formUpdateCreateRules').on('submit', function() {
        let rule_id     = parseInt($('[name="rule_id"]', this).val());
        let rules       = parseInt($('[name="rules"]', this).val());
        let marketplace = parseInt($('[name="marketplace"]', this).val());

        if (rules === 0 || isNaN(rules)) {
            Toast.fire({
                icon: 'error',
                title: 'Selecione uma regra.'
            });
            return false;
        }

        if (isNaN(rule_id) && marketplace === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Selecione uma regra.'
            });
            return false;
        }

        if (isNaN(rule_id)) {
            rule_id = null;
        }

        $.ajax({
            url: `${base_url}/auction/saveRules`,
            type: 'POST',
            dataType: 'json',
            data: { rule_id, rules, marketplace },
            success: response => {
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.message ?? 'Regra de leilão atualizada'
                });

                if (response.success) {
                    $('#manageTable').DataTable().ajax.reload();
                    $('#modalUpdateCreateRule').modal('hide');
                }
            }
        });

        return false;
    });

    $(document).on('click', '.remove-rule', function (){
        const rule_id = $(this).data('rule-id');
        const makretplace_name = $(this).closest('tr').find('td:eq(1)').text();

        Swal.fire({
            title: `Tem certeza que deseja excluir a regra de leilão?`,
            html: `<h4>Marketplace: <b>${makretplace_name}</b></h4>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Excluir regra de leilão',
            cancelButtonText: 'Cancelar Operação'
        }).then((result) => {
            if (result.value) {
                $.post(base_url + "auction/removerRule", { rule_id }, response => {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.message
                    });

                    if (response.success) {
                        $('#manageTable').DataTable().ajax.reload();
                    }
                });
            } else if (result.dismiss === 'cancel') {
                Swal.fire({
                    icon: 'error',
                    title: 'Operação cancelada',
                    confirmButtonText: "Ok",
                });
            }
        });
    });

</script>