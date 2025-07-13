<!--
SW Serviços de Informática 2019

Ver Profile

-->


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_myprofile";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"></h3>
            </div>
            <!-- /.box-header -->
            
            <div class="box-body">
              <table class="table table-bordered table-condensed table-hovered">
                <tr>
                  <th><?=$this->lang->line('application_username');?></th>
                  <td><?php echo $user_data['username']; ?></td>
                </tr>
                <tr>
                  <th><?=$this->lang->line('application_email');?></th>
                  <td><?php echo $user_data['email']; ?></td>
                </tr>
                <tr>
                  <th><?=$this->lang->line('application_firstname');?></th>
                  <td><?php echo $user_data['firstname']; ?></td>
                </tr>
                <tr>
                  <th><?=$this->lang->line('application_lastname');?></th>
                  <td><?php echo $user_data['lastname']; ?></td>
                </tr>
                <!-- tr>
                  <th>Gender</th>
                  <td><?php echo ($user_data['gender'] == 1) ? 'Male' : 'Gender'; ?></td>
                </tr -->
                <tr>
                  <th><?=$this->lang->line('application_phone');?></th>
                  <td><?php echo $user_data['phone']; ?></td>
                </tr>
                <tr>
                  <th><?=$this->lang->line('application_groups');?></th>
                  <td><span class="label label-info"><?php echo $user_group['group_name']; ?></span></td>
                </tr>
                <tr>
                  <th><?=$this->lang->line('application_company');?></th>
                  <td><?php echo $user_company['id']." - ".$user_company['company_name']; ?></td>
                </tr>
                
                <?php 
                $titulo = $this->lang->line('application_stores');
                foreach ($user_stores as $store) { ?>
                <tr>
                  <th><?php echo $titulo; ?></th>
                  <td><?php echo $store['id']." - ".$store['name']; ?></td>                 
                </tr>
                <?php 
                	$titulo = ''; 
                 } ?>
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
  <!-- /.content-wrapper -->
<script>
    $(function(){
        $('#profileNav').addClass('active')
    })
</script>

 
