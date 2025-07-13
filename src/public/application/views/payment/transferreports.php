
<!-- Content Wrapper. Contains page content -->
<script>

    let base_url = "<?php echo base_url(); ?>";

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
	       $data['page_now'] = "transfer_report";
	       $this->load->view('templates/content_header',$data); ?>


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
        
        <?php if(in_array('createTransferReport', $user_permission)): ?>

            <div class="box">
                <div class="box-body">

                    <table class="col-md-12 col-xs-12">
                        <tr>
                            <td >
                                <div class="col-md-8">
                                    <h4><?=$this->lang->line('transfer_report_box_title');?>: <?=$current_mes_ano?></h4>
                                </div>
<!--                                <div class="col-md-1">-->
<!--                                    <button type="button" style="position: relative; float: right; clear: both !important;" id="btn-export" class="btn btn-success text-right" style="position:relative; float:right;">--><?php //=$this->lang->line('payment_balance_transfers_btn_export');?><!-- &nbsp; <i class="fa fa-fw fa-file-excel-o"></i></button>-->
<!--                                </div>-->
                                <div class="col-md-4 text-right">
                                    <table style="float: right;border-spacing: 10px;border-collapse: separate;">
                                        <tr>
                                            <td>
                                                <label for="slc_date"><?=$this->lang->line('transfer_report_filter_period_label');?></label>
                                            </td>
                                            <td>
                                                <select class="form-control text-right" style="width: auto !important; position: relative; float: right; clear: both !important; margin: 10px auto; " id="slc_date" name="slc_date" >

                                                    <?php
                                                    foreach ($all_periods as $key => $period)
                                                    {
                                                        $formatted_period = explode('-', $period['ano_mes']);
                                                        $formatted_period = $formatted_period[1].'/'.$formatted_period[0];


                                                        ?>
                                                        <option value="<?=$period['ano_mes']?>"
                                                            <?php
                                                            if ($period['ano_mes'] == $current_ano_mes):
                                                                ?>
                                                                disabled="disabled" selected
                                                            <?php
                                                            endif;
                                                            ?>
                                                        ><?=$formatted_period?></option>
                                                        <?php
                                                    }
                                                    ?>

                                                </select>
                                            </td>
                                        </tr>
                                    </table>




                                </div>

                            </td>
                        </tr>
                    </table>
                    <table id="manageTable" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th><?=$this->lang->line('application_id');?></th>
                            <th><?=$this->lang->line('transfer_report_table_head_gateway_name');?></th>
                            <th><?=$this->lang->line('transfer_report_table_head_sender');?></th>
                            <th><?=$this->lang->line('transfer_report_table_head_receiver');?></th>
                            <th><?=$this->lang->line('transfer_report_table_head_amount');?></th>
                            <th><?=$this->lang->line('transfer_report_table_head_datetime');?></th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->


<!--            <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>-->
            <script>

                var manageTable;

                $(function()
                {
                    $('#slc_date').change(function()
                    {
                        var date = $(this).val();

                        if (date)
                        {
                            window.location.href = base_url + 'payment/transferReports/' + date;
                        }
                    });


/*                    $('#btn-export').click(function()
                    {
                        var cycle = $('#cycle-reference').text();

                        TableToExcel.convert(document.getElementById("report-table-list"), {
                            name: slugify(cycle+'-<?=substr($this->lang->line('application_transfer_report'),0,30);?>')+'.xlsx',
                            sheet: {
                                name: "Transferencias"
                            }
                        });
                    });*/

                    // initialize the datatable
                    manageTable = $('#manageTable').DataTable({
                        "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },
                        'ajax': base_url + 'payment/transferReportResults/<?=$current_ano_mes?>'
                    });

                });

            </script>


        <?php endif; ?>
        


      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->