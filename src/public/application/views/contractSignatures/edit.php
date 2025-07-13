<div class="content-wrapper">
  <?php $data['page_now'] = 'contract_signatures';
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
          <form role="form" id="frmCadastrar" name="frmCadastrar" action="<?php base_url('contractSignatures/edit') ?>" method="post">
            <div class="box-body">
              <div class="col-md-12 col-xs-12">
                <h1><?php echo  $contract['contract_title']; ?></h1>
                <h4><?php echo '(' . $contract['document_type'] . ')'; ?></h4>
              </div>
              <div class="col-md-12 col-xs-12" style="padding:1%;"></div>

              <input type="hidden" name="attachment" id="attachment" value="<?php echo set_value('attachment', $contract['attachment']) ?>" />
              <iframe width="100%" height="1200px" allow="autoplay" frameborder=0 class="iframe_pdf" id="iframe_pdf"></iframe>

              <div class="col-md-12 col-xs-12 " style="padding: 2%;" id="signdiv">
                <input type="checkbox" style="border-radius: .12em;height: 25px;width: 25px;" name="sign" id="sign" value="1" required <?php echo set_checkbox('sign', $contract['sign'], $contract['sign'] == 1) ?>>
                <label for="sign" style="font-size: 18px; vertical-align: super; padding:1%;"><?= $this->lang->line('application_contract_term_accept'); ?></label>
              </div>

              <div class="box-footer">
                <button type="submit" id="btnSave" name="btnSave" class="btn btn-primary" <?php echo $read_only; ?>><?= $this->lang->line('application_sign'); ?></button>
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
<script type="text/javascript">
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";
  var id = "<?php echo $contract['id']; ?>"
  var sign = "<?php echo $contract['sign']; ?>"
  var active = "<?php echo $contract['active']; ?>"


  $(document).ready(function() {

    $("#btnVoltar").click(function() {
      window.location.assign(base_url.concat("contractSignatures"));
    });


    if (id) {
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

          $('#iframe_pdf').attr('src', response.ln1[0])

        }
      });
    }
    
    if(sign || active == '0'){
      $('#sign ').attr('disabled', true)
      $('#signdiv ').attr('hidden', true)
      $('#btnSave ').hide();      
    }

  });
</script>