  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <?php $data['page_now'] = 'contracts';
    $data['pageinfo'] = "";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">

          <div id="messages2"></div>

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
            <form role="form" id="frmCadastrar" name="frmCadastrar" action="<?php base_url('contracts/create') ?>" method="post">
              <div class="box-body">

                <div class="col-md-6 col-xs-6">
                  <label for="contract_title"><?=$this->lang->line('application_contract_title');?></label>
                  <input type="text" class="form-control" id="contract_title" required name="contract_title" placeholder="" value="<?php echo set_value('contract_title', $contract['contract_title']) ?>" <?php echo $read_only; ?>>
                </div>

                <div class="col-md-6 col-xs-6">
                  <label for="document_type"><?=$this->lang->line('application_contract_type');?></label>
                  <select class="form-control" name="document_type" id="document_type" <?php echo $read_only; ?> required>
                    <option value=""><?= $this->lang->line('application_select'); ?></option>
                    <?php foreach ($attribs as $atributte) { ?>
                      <option value="<?= $atributte['id'] ?>" <?= set_select('document_type', $atributte['id'], ($contract['document_type'] == $atributte['id'])) ?>><?= $atributte['value'] ?></option>
                    <?php } ?>
                  </select>
                </div>

                <?php
                $allstores = array();
                foreach ($contract['participating_stores'] as $store) {
                  $allstores[] = $store;
                }
                ?>
                <div class="form-group col-md-6 col-xs-12 <?php echo (form_error('catalogs[]')) ? "has-error" : ""; ?>">
                  <label for="catalogs" class="normal"><?=$this->lang->line('application_participating_stores');?>(*)</label>
                  <select class="form-control selectpicker show-tick" id="participating_stores" name="participating_stores[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="Lojas Participantes" required>
                    <?php foreach ($stores as $store) { ?>
                      <option value="<?= $store['id'] ?>" <?php echo set_select('stores', $store['id'], in_array($store['id'], $allstores)); ?>><?= $store['name'] ?></option>
                    <?php } ?>
                  </select>
                  <?php echo '<i style="color:red">' . form_error('participating_stores[]') . '</i>'; ?>
                </div>
                <div class="row"></div>      

                <div class="panel panel-primary">
                  <div class="panel-heading"><?= $this->lang->line('application_validity'); ?> : &nbsp
                    <span class="h6"> (<?= $this->lang->line('application_validity_helper'); ?>) </span>
                  </div>
                  <div class="panel-body">          
                    <div class="form-group col-md-6 col-xs-12">
                      <label for="validity"><?= $this->lang->line('application_validity'); ?> :</label>
                      <input type="date" class="form-control" id="validity" name="validity" value="<?php echo set_value('valid', $contract['validity']) ?>">
                    </div>
  
                    <div class="col-md-12 col-xs-12">
                      <label class="col-md-6 col-xs-6">
                        <input type="checkbox" class="minimal" name="block" id="block" value="1" <?php echo set_checkbox('block', $contract['block'], $contract['block'] == 1) ?>>
                        <?=$this->lang->line('application_block');?>
                      </label class="col-md-6 col-xs-6">
                      <label>
                        <input type="checkbox" class="minimal" name="active" id="active" value="1" <?php echo set_checkbox('active', $contract['active'], $contract['active'] == 1) ?>>
                        <?=$this->lang->line('application_active');?>
                      </label>
                    </div>
                  </div>
                </div>


                <div class="col-md-12 col-xs-12">
                  <div class="box-body" id="divUpload" name="divUpload" style="display:block">
                    <!-- ?php echo validation_errors(); ?  -->
                    <div class="row">
                      <div class="form-group col-md-12 col-xs-12" class="div_upload" id="div_upload">
                        <label for="document_upload" hidden><?= $this->lang->line('messages_upload_file'); ?></label>
                        <div class="kv-avatar">
                          <div class="file-loading">
                            <input type="file" id="document_upload" name="document_upload[]" <?php echo $read_only; ?> required>
                          </div>
                        </div>
                        <input type="hidden" name="attachment" id="attachment" value="<?php echo set_value('attachment', $contract['attachment']) ?>" />
                      </div>
                    </div> <!-- row -->
                  </div> <!-- box body -->

                  <div id="load_frames"></div>
                  <div class="box-footer">
                    <button type="submit" id="btnSave" name="btnSave" class="btn btn-primary" <?php echo $read_only; ?>><?= $this->lang->line('application_save'); ?></button>
                    <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></button>
                  </div>
            </form>


          </div>
          <!-- /.box -->
        </div>
        <!-- col-md-12 -->
      </div>
      <!-- /.row -->


    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
  <script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";
    var id = "<?php echo $contract['id']; ?>"


    $(document).ready(function() {

      $('.select2').select2();

      $("#btnVoltar").click(function() {
        window.location.assign(base_url.concat("contracts/"));
      });

      var uploadUrl = base_url.concat("contracts/fileUpload");
      var deleteUrl = base_url.concat("contracts/deleteFile");

      $("#document_upload").fileinput({
        overwriteInitial: false,
        language: 'pt-BR',
        maxFileSize: 15000,
        uploadUrl: uploadUrl,
        uploadAsync: true,
        showClose: false,
        showCaption: false,
        maxFileCount: 1,
        uploadExtraData: {
          uploadToken: $("#attachment").val(), // for access control / security 
        },
        elErrorContainer: '#kv-avatar-errors-1',
        msgErrorClass: 'alert alert-block alert-danger',
        theme: 'fas',
        allowedFileExtensions: ["pdf"],
        deleteUrl: deleteUrl
      }).on("filebatchselected", function(event, files) {
        $("#document_upload").fileinput("upload");
      }).on('filesorted', function(event, params) {
        console.log('File sorted params', params);
      }).on('fileuploaded', function(event, previewId, index, fileId) {
        console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
        $('#document_upload ').attr('required', false)
      }).on('fileuploaderror', function(event, data, msg) {
        AlertSweet.fire({
            icon: 'error',
            title: 'Atenção!',
            html: msg ?? 'Erro no upload do arquivo.<br>Garanta que seja um arquivo do tipo "pdf" "!<br>Faça o ajuste e tente novamente!'
        });
        console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
      })

      $('#load_frames').attr('hidden', true)
      if (id) {
        $('#div_upload ').attr('hidden', true)
        $('#block ').attr('disabled', true)
        $('#validity ').attr('disabled', true)
        $('select option:selected').prop('disabled', true);
        if($('#active').is(":checked")){
          $('#active').attr('disabled', 'true')
        }
      
        $.ajax({
          type: "POST",
          enctype: 'multipart/form-data',
          data: {
            token: $("#attachment").val(),
          },
          url: base_url + "contracts/getFiles",
          dataType: "json",
          async: true,
          success: function(response) {
              $('#load_frames').attr('hidden', false)

              $(response.ln1).each(function(k, value){
                  $('#load_frames').append(`<iframe width="100%" height="1200px" allow="autoplay" frameborder=0 class="iframe_pdf" id="iframe_pdf_${k}" src="${value}"></iframe>`);
              })


          }
        });
      }

      $("form").submit(function() {
          $("#active").removeAttr("disabled");
      });

      $("#validity").change(function(){
        if($("#validity").val()){
          $("#block").prop( "checked", false );
          $('#block ').attr('disabled', true)
        }else{
          $('#block ').attr('disabled', false)
        }
      });  

    });
  </script>
<style>
.filter-option { background-color: white; }
</style>