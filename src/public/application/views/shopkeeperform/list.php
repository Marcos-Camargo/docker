<!--
SW Serviços de Informática 2019

Listar Settings
Add , Edit & Delete

-->
<style rel="stylesheet">
    .icon-loc {
        float: right;
        margin-top: -27px;
        margin-right: 11px;
        font-size: large;
    }
     .select2-container--default .select2-selection--single {
         border-radius: 0px;
     }
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">  
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
        <div class="col-md-12 col-xs-12">
            <div id="messages">
            <?php if( $shopkeeperform['user_id']){?>
                <button type="button" class="btn btn-primary"  id='btn_copyURL' onclick="$('#copyURLFormModal').modal('show');" data-toggle="modal" data-target="#copyURLModal"><i class="fas fa-external-link-alt"> Copiar URL</i></button>
            <?php }?>
            </div>
        </div>
    </div>

    
    <div class="row">
        <div class="col-md-12 col-xs-12">
            <div id="messages"></div>
            <div class="col-md-12 col-xs-12">
            <div id="messages"></div>
                
                <br> <br>
            </div>
        </div>
    </div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
                  <tr>
                    <th style="width:25%;"><?=$this->lang->line('application_fantasy_name');?></th>
                    <th style="width:25%;"><?=$this->lang->line('application_responsible_name');?></th>
                    <th style="width:25%;"><?=$this->lang->line('application_status');?></th>
                    <th style="width:20%;"><?=$this->lang->line('application_shopkeeper_form_seller_name');?></th>
                    <?php if(in_array('updateShopkeeperForm', $user_permission)): ?>
                      <th style="width:10%;"><?=$this->lang->line('application_action');?></th>
                    <?php endif; ?>
                  </tr>
              </thead>

            </table>
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="aprovedFormModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="aprovedFormName"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/aproved') ?>" method="post" id="aprovedForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="logoModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="logoForm"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/insertLogo') ?>" method="post" id="logoForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<div class="modal fade" tabindex="-1" role="dialog" id="copyURLFormModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="copyURLFormName"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/copyURL') ?>" method="post" id="copyURLForm">
        <div class="modal-body">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="copy_URL"><?=$this->lang->line('application_address');?></label>
                    <input type="text" class="form-control" readonly  id="copy_URL" name="copy_URL" value="<?php echo base_url('ShopkeeperForm/create/'.$shopkeeperform['user_id'])?>" autocomplete="off">
                    <span class="msg_copy">Copiado!</span>
                    <span class="fa fa-copy icon-loc cursor-pointer" onclick="myFunction()"></span>
                </div>
                <div class="form-group col-md-4 col-xs-12">
                    <label>Origem do Seller</label>
                    <select class="form-control select2" id="origin_seller" name="origin_seller">
                        <option value="0"><?=$this->lang->line('application_select')?></option>
                        <?php asort($get_attribute_value_utm_param);
                        foreach($get_attribute_value_utm_param as $utm_param) { ?>
                            <option value="<?=$utm_param['value'] ?>"><?=$utm_param['value'] ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" id="btn_copyURL_shopkeeperform" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<div class="modal fade" tabindex="-1" role="dialog" id="titleHeaderModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="titleHeaderForm"></span></h4>
      </div> 

      <form role="form" action="<?php echo base_url('ShopkeeperForm/insertTitleHeader') ?>" method="post" id="titleHeaderForm">
        <div class="modal-body">
            <div class="form-group col-md-12 col-xs-12">
                <label for="description">Titulo do cabecalho(*)</label>
                <textarea type="text" class="form-control" id="description" maxlength="1000" name="description" placeholder="titulo cabecalho"></textarea>
                <span id="char_description"></span><br />
                <span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" title="titulo cabecalho"></span>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<script type="text/javascript">
var manageTable;
var base_url = "<?=base_url(); ?>";

$(document).ready(function() {

$('.select2').select2({
  dropdownParent: $('#copyURLFormModal')
});

$('#origin_seller').change(function(){
    var sellet_select = $('select[name=origin_seller] option').filter(':selected').val();
    var url = '<?php echo base_url('ShopkeeperForm/create/'.$shopkeeperform['user_id'])?>';
    var url_final;
    if(sellet_select == 0){
        var utm = url;
        var url_final = $('#copy_URL').val(utm);
    }else{
        var utm = url+'?utm_source='+sellet_select;
        var url_final = $('#copy_URL').val(utm);
    }
});

  $("#addShopkeeperformNav").addClass('active');
  $("#mainShopkeeperformNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },	  
    "scrollX": true,
    'ajax': base_url + 'ShopkeeperForm/fetchShopkeepersData',
    'order': []
  });

  // submit the create from 
  
  $("#createFieldForm").unbind('submit').on('submit', function() {
    var form = $(this);

    // remove the text-danger
    $(".text-danger").remove();

    $.ajax({
      url: form.attr('action'),
      type: form.attr('method'),
      data: form.serialize(), // /converting the form data into array and sending it to server
      dataType: 'json',
      success:function(response) {

        manageTable.ajax.reload(null, false); 

        if(response.success === true) {
          $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
          '</div>');


          // hide the modal
          $("#addSettingModal").modal('hide');

          // reset the form
          $("#createFieldForm")[0].reset();
          $("#createFieldForm .form-group").removeClass('has-error').removeClass('has-success');

        } else {

          if(response.messages instanceof Object) {
            $.each(response.messages, function(index, value) {
              var id = $("#"+index);

              id.closest('.form-group')
              .removeClass('has-error')
              .removeClass('has-success')
              .addClass(value.length > 0 ? 'has-error' : 'has-success');
              
              id.after(value);

            });
          } else {
            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>');
          }
        }
      }, error: e => console.log(e)
    }); 
    return false;
  });
});

$('.msg_copy').hide();
function myFunction() {
    var copyText = document.getElementById("copy_URL");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    $('.msg_copy').show().fadeOut(4000);
}



</script>
