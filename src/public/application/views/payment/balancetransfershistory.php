
<!-- Content Wrapper. Contains page content -->
<script>

    // let transfer_value = 0;
    // let transfers_selected = [];
    // let current_cycles = [];
    // let modal_data;
    let base_url = "<?php echo base_url(); ?>";
    // let double_store = 0;

</script>

<div id='loading_wrap' style='
            display: none; 
            position:fixed; 
            z-index: 1050; 
            padding: 20vw calc(50vw - 50px); 
            color: #fff; 
            font-size: 2rem;
            height:100%; 
            width:100%; 
            overflow:hidden; 
            top:0; left:0; bottom:0; right:0; 
            background-color: rgba(0,0,0,0.5);'><?=$this->lang->line('application_process');?>...</div>

<div class="content-wrapper">
	  
    <?php  $data['pageinfo'] = ""; 
	       $data['page_now'] = "balance_transfers_history";
	       $this->load->view('templates/content_header',$data); ?>

<style>              

    .box-title{
        margin-left: 14px;
        /* margin-top: 80px !important; */
    }
    .box-title-modal{
        margin-top: 40px !important;
        margin-left: 20px;
    }

    .box-btns{
        padding: 10px;
        margin: 10px;
        border: 0;
        width: 100%;
    }

    .box-btns button{
        margin-left: 20px;
        position: relative;
        float: right;
    }

    #box-btns, #transfer-list-header{
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        z-index: 100;
        background-color: #fff;
        padding: 10px 10px 15px 15px;   
    }

    .box-btns-floating{
        -webkit-box-shadow: 0px 15px 9px -9px rgba(0,0,0,0.29); 
        box-shadow: 0px 15px 9px -9px rgba(0,0,0,0.29);
    }

    .negative{
        color:  #dc3545;
        /* color:  #008000; */
        font-weight: bold;
    }

    .payment-report-icon{
        margin-right: 4px;
    }

</style>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

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
        
        <?php if(in_array('createPaymentReport', $user_permission)): ?>

            <div class="box">
          <div class="box-body" id="grid-body">        

            <style>

                .warning{
                    color: red;
                }

            </style>

            <div class="col-md-10"></div>
            <div class="form-group col-md-2">
                <button type="button" id="btn-pendency_export" style="position: relative; float: right;" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_export');?> &nbsp; <i class="fa fa-fw fa-file-excel-o"></i></button>
            </div>



            <table  id="advance-historic-table" class="table table-bordered table-striped" >
              <thead>
                
                <tr id="transfer-list-header">
                <th><?=$this->lang->line('balance_transfer_history_column_store_id');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_gateway_name');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_store_name');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_advance_total');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_advance_returned');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_current_balance');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_advance_pendency');?></th>
                    <th><?=$this->lang->line('balance_transfer_history_column_modal_btn');?></th>
                </tr>
              </thead>

              
              <tbody id="report-table-list">
                  

                <?php 
                    
                    if (is_array($history_data) && !empty($history_data))
                    {
                        foreach ($history_data as $key => $history)
                        {         
                            $warning = ($history['balance'] > $history['total_pendency']) ? ' class="warning" title="'.$this->lang->line('balance_transfer_history_warning_message').'"': '';                  
                ?>
                            <tr>
                                <td><?=$history['store_id']?></td>
                                <td><?=$history['gateway_name']?></td>
                                <td><?=$history['store_name']?></td>
                                <td><?=money(round($history['total_advanced'] / 100, 2))?></td>
                                <td><?=money(round($history['total_returned'] / 100, 2))?></td>
                                <td><?=money(round($history['balance'] / 100, 2))?></td>
                                <td <?=$warning?>><?=money(round($history['total_pendency'] / 100, 2))?></td>
                                <td><button type="button" data-id="<?=$history['store_id']?>" data-name="<?=$history['store_name']?>" class="btn btn-outline-dark btn-sm modal-details"><?=$this->lang->line('balance_transfer_history_btn_open_modal');?>&nbsp; <i class="fa fa-list-ul"></i></button></td>
                            </tr>
                <?php
                        }
                    }

                    
                ?>
            
                    <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
                    <script>

                         $(function() 
                        {
                            $('#slc_cycle').change(function()
                            {
                                var cycle = $(this).val();
                                
                                if (cycle)
                                {
                                    window.location.href = base_url + 'payment/paymentReports/' + cycle;
                                }
                            });


                            $('#btn-pendency_export').click(function()
                            {
                                TableToExcel.convert(document.getElementById("advance-historic-table"), {
                                    name: slugify('<?=substr($this->lang->line('application_balance_transfers_history'),0,30);?>')+'.xlsx',
                                    sheet: {
                                        name: slugify('<?=substr($this->lang->line('balance_transfer_history_export_sheet'),0,30);?>')
                                    }
                                });
                            });


                            $('#btn-modal-pendency_export').click(function()
                            {
                                TableToExcel.convert(document.getElementById("modal-advance-historic-table"), {
                                    name: slugify('<?=substr($this->lang->line('balance_transfer_history_modal_title'),0,30);?>')+'.xlsx',
                                    sheet: {
                                        name: slugify('<?=substr($this->lang->line('balance_transfer_history_export_sheet'),0,30);?>')
                                    }
                                });
                            });


                            $('.modal-details').click(function()
                            {
                                var store_id    = parseInt($(this).attr('data-id'));
                                var store_name  = $(this).attr('data-name');

                                if (store_id <= 0)
                                {
                                    return false;
                                }

                                loading('show');

                                $.get(base_url + 'payment/getBalanceTransferHistory/'+store_id, function(data)
                                {
                                    loading('hide');

                                    if (isJson(data))
                                    {
                                        var history = jQuery.parseJSON(data);
                                        var row, returned;

                                        $('#modal-store-name, #modal-pendency-list').html('');

                                        $('#modal-store-name').text(store_name);

                                        $.each(history, function(k, v)
                                        {
                                            console.log(v);
                                            var returned = '<i class="fa fa-check" style="margin-right: 5px;"></i> ' + v['returned'];

                                            if (v['status'] == 't')
                                            {
                                                returned = '<i class="fa fa-ban" style="color: red;"></i> ---';
                                            }
                                            row = ' <tr>' +
                                                        '<td>'+v['advance']+'</td>' +
                                                        '<td>'+v['amount']+'</td>' +
                                                        '<td>'+ returned+'</td>' +
                                                    '</tr>';

                                            $('#modal-pendency-list').append(row);

                                        });
                                    }
                                    else
                                    {
                                        console.log('erro json');
                                    }

                                    $("#modal-details").modal();
                                });
                            });


                            $('#modal-details').on('hidden.bs.modal', function () 
                            {
                                $('#modal-store-name, #modal-pendency-list').html('');
                            });

                        });

                        


                        function slugify(text) {
                            const from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;"
                            const to = "aaaaaeeeeeiiiiooooouuuunc------"

                            const newText = text.split('').map(
                                (letter, i) => letter.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i)))

                            return newText
                                .toString()                     // Cast to string
                                .toLowerCase()                  // Convert the string to lowercase letters
                                .trim()                         // Remove whitespace from both sides of a string
                                .replace(/\s+/g, '-')           // Replace spaces with -
                                .replace(/&/g, '-y-')           // Replace & with 'and'
                                .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                                .replace(/\-\-+/g, '-');        // Replace multiple - with single -
                        }


                        function loading(display = 'show')
                        {
                            if (display == 'show')
                                $('#loading_wrap').fadeIn();
                            else
                                $('#loading_wrap').fadeOut();
                        }


                        function isJson(str) 
                        {
                            try
                            {
                                JSON.parse(str);
                            } 
                            catch (e) 
                            {
                                return false;
                            }

                            return true;
                        }

                    </script>


              </tbody>
            </table>
          </div>
          <!-- /.box-body -->

          <div class="box-footer ml-3 pb-5">
            <button type="button" id="btnVoltar" name="btnVoltar" onClick="window.location.href = base_url + 'payment/balanceTransfers';" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
        </div>

        </div>
        <!-- /.box -->

        <?php endif; ?>
        


      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->




<!-- waiting orders modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modal-details">
  <div class="modal-dialog" role="document"> 
    <div class="modal-content" >
      
        <div class="modal-header" style="border-bottom:0 !important;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="application_close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?=$this->lang->line('balance_transfer_history_modal_title');?></h4>
        </div>

      <div class="modal-body">

        <div class="row">
            <div class="col-md-9"></div>
            <div class="form-group col-md-2">
                <button type="button" id="btn-modal-pendency_export" style="position: relative; float: right;" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_export');?> &nbsp; <i class="fa fa-fw fa-file-excel-o"></i></button>
            </div>
            <div class="col-md-1"></div>
        </div>

      <div class="row">
        <div class="col-md-1"></div>
        <div class="container col-md-10">



                    
                    <div class="row">

                        <h3 id="modal-store-name"></h3>

                        <table  id="modal-advance-historic-table" class="table table-bordered table-striped" >

                            <thead>
                                <tr>                                    
                                    <th><?=$this->lang->line('balance_transfer_history_modal_column_date');?></th>
                                    <th><?=$this->lang->line('balance_transfer_history_modal_column_amount');?></th>
                                    <th><?=$this->lang->line('balance_transfer_history_modal_column_returned');?></th>                                    
                                </tr>
                            </thead>
                
                            <tbody id="modal-pendency-list"></tbody>

                        </table>

                    </div>
                

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>

            <!-- </form> -->

        </div>
        <div class="col-md-1"></div>
      </div>
      </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->