<div class="content-wrapper">

  <?php $data['pageinfo'] = "application_manage_migration";
  $this->load->view('templates/content_header', $data);

  ?>
  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

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
        <div class="box">
          <div class="box-body">
            <h4><?= $this->lang->line('application_manage_migration_seller'); ?></h4>

              <div class="overlay-wrapper">

                  <div class="overlay" id="show-loading-overlay" style="display: none">
                      <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                      <div class="text-bold pt-2">Aguarde, reiniciando migração...</div>
                  </div>

                <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                  <thead>
                    <tr>
                      <th><?= $this->lang->line('application_id'); ?></th>
                      <th><?= $this->lang->line('application_store_name'); ?></th>
                      <th><?= $this->lang->line('application_total_imported_products'); ?></th>
                      <th><?= $this->lang->line('application_total_migrated_reproved_products'); ?></th>
                      <th><?= $this->lang->line('application_total_matchs'); ?></th>
                      <th><?= $this->lang->line('application_store_status'); ?></th>
                      <th><?= $this->lang->line('application_action'); ?></th>
                    </tr>
                  </thead>
                </table>
              </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";
  var company_id = null;
  var stores = null; //
  var data = null; //
  var integrations = '<?php echo $integrations; ?>';
  var options = {};
  let seller_id = null;
  let int_to = null;
  $.map(JSON.parse(integrations), function(o) {
    options[o.int_to] = o.name;
  });
  $(document).ready(function() {
    $("#migrationSeller").addClass('active');
  });
  fechMigrationData();
  $('#company_id').change(function() {
    company_id = $('#company_id').val();
    getStores(company_id);
  });


  function getStores(company_id) {
    var options = $("#selectstore");
    options.empty();
    options.append($('<option>', {
      text: 'Selecione...'
    }))
    stores = $.getJSON(base_url + 'MigrationSeller/fetchCompaniesStores/' + company_id);
    $.getJSON(base_url + 'MigrationSeller/fetchCompaniesStores/' + company_id, (data) => {
      $.each(data, function(i, value) {
        options.append($('<option>', {
          value: value.store_id,
          text: value.name
        }));
      });
    });
  }

  function fechMigrationData(searchStoreId = null) {
    if (searchStoreId) {
      procura = " AND psm.store_id = " + searchStoreId;
      procura_count = " AND store_id = " + searchStoreId;
    }
    procura = ""
    procura_count = ""
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
        url: base_url + 'MigrationSeller/fetchMigrationData',
        data: {
          id: 'id',
          company_id: company_id,
          procura_count: procura_count,
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

  async function startMigrationStore(id) {
      const {
        value: marketplace
      } = await Swal.fire({
        title: 'Selecione o Marketplace',
        input: 'select',
        inputOptions: options,
        inputPlaceholder: 'Selecione um marketplace...',
        showCancelButton: true,
        html: '<label for="seller_id">Seller id:</label>' +
          '<input id="swal-input1" class="swal2-input" type="text" placeholder="Informe o Seller id Vtex" required>',
        inputValidator: (value) => {
          return new Promise((resolve) => {
            seller_id = document.getElementById('swal-input1').value
            int_to = value
            if (!seller_id) {
              resolve('Você precisa informar um Seller ID')
            } else if (!value) {
              resolve('Você precisa selecionar um marketplace')
            } else {
              resolve()
            }
          })
        }
      })
      if (marketplace) {
        // Swal.fire(`Marketplace Selecionado: ${marketplace}`)
        $.post(base_url + 'MigrationSeller/checkSellerIdVtex', {
            'seller_id': seller_id,
            'int_to': int_to
          }, function(data) {
            checked_seller = data;
            const obj = JSON.parse(checked_seller);
            const swalWithBootstrapButtons = Swal.mixin({
              customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
              },
              buttonsStyling: false
            })
            if (obj.error) {
              Swal.fire({
                icon: 'error',
                title: 'Atenção!',
                html: 'O Seller ID informado incorreto ou inexistente na Vtex: ' + seller_id,
                // }).then((result) => {
                //   resolve()
              })
            } else if (obj.integration_error) {
              Swal.fire({
                icon: 'error',
                title: 'Atenção!',
                html: 'A integração Selecionada não é uma integração Vtex',
                // }).then((result) => {
                //   resolve()
              })
            } else if (Object.keys(obj).length === 0) {
                Swal.fire({
                  icon: 'error',
                  title: 'Atenção!',
                  html: 'A integração não obteve retorno. Confirme as politicas e o idSeller',
                })
            } else {
              Swal.fire({
                icon: 'info',
                title: 'Atenção!',
                html: 'O Seller ID informado é referente ao seguinte lojista na Vtex: <br><b>Nome da loja: </b>' + obj.name + '<br><br>Deseja iniciar a migração?',
                showCancelButton: true,
                confirmButtonText: '<?= $this->lang->line('application_yes') ?>',
                cancelButtonText: '<?= $this->lang->line('application_no') ?>',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
              }).then((result) => {
                  if (!result.dismiss) {
                    $.post(base_url + 'MigrationSeller/startMigrationSeller', {
                        'seller_id': seller_id,
                        'selectstore': id,
                        'int_to': int_to
                      },function(data) {
                        location.reload();
                      })
                    }
                    else if (result.dismiss) {
                      // resolve()
                    }
                  })
              }
            })
        }
      }

      function runMigrationStore(id) {
        // let id = event.target.getAttribute('data-id');
        if (id) {
          Swal.fire({
            icon: 'info',
            title: 'AVISO!',
            html: '<b><?= $this->lang->line('application_warning') ?>: </b>' + '<?= $this->lang->line('application_store_migration_job') ?>'
            }).then((result) => {
            $.post(base_url + 'MigrationSeller/createJobStore', {
                'store_id': id,
              },function(data) {
                location.reload();
              })
            })
          }
      }

      function restartMigrationStore(id, obj) {

          var store_name = manageTable.row( $(obj).parents('tr') ).data()[1];
          Swal.fire({
              title: "Atenção?",
              text: "Deseja reiniciar a migração do lojista " + store_name,
              icon: "warning",
              buttons: true,
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Sim',
              cancelButtonText: 'Não'
          })
          .then((result) => {
              if (result.value) {
                $('#show-loading-overlay').show();
                $.post(base_url + 'MigrationSeller/restartJobStore', { store_id: id }, data => {
                    console.log(data);
                    if (data.store_id) {
                        $.post(base_url + 'MigrationSeller/startMigrationSeller', {
                            'seller_id': data.seller_id,
                            'selectstore': data.store_id,
                            'int_to': data.int_to,
                            'restart': 1
                        }, data => {
                            location.reload();
                            $('#show-loading-overlay').hide();
                        })
                    } else {
                        location.reload();
                        $('#show-loading-overlay').hide();
                    }
                 });
              }else{
                  $('#show-loading-overlay').hide();
              }
          });

      }

      function endMigrationStore(id) {
    
        if (id) {
          Swal.fire({
            icon: 'info',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Finalizar',
            cancelButtonText: 'Cancelar',
            html: '<h3 class="text-center"><?= $this->lang->line('application_migration_seller_finix_title'); ?></h3>'+
            '<h5 class="text-left"><?= $this->lang->line('application_migration_seller_finix_msg'); ?></h5>',
          }).then((result) => {
              if (result.value) {
                  $.post(base_url + 'MigrationSeller/endMigrationSeller', {
                      'selectstore': id,
                  }, function (data) {
                      location.reload();
                  })
              }
            })
        }
      }
</script>