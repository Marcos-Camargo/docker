<!--

-->
<!-- Content Wrapper. Contains page content -->
  <style>

pre {
	overflow: auto;
	white-space: pre-wrap;
	}
 
.tracking-detail {
 padding:3rem 0
}
#tracking {
 margin-bottom:1rem
}
[class*=tracking-status-] p {
 margin:0;
 color:#fff;
 text-transform:uppercase;
 text-align:center
}
[class*=tracking-status-] {
 padding:1.6rem 0
}
.tracking-status-intransit {
 background-color:#65aee0
}
.tracking-status-outfordelivery {
 background-color:#f5a551
}
.tracking-status-deliveryoffice {
 background-color:#f7dc6f
}
.tracking-status-delivered {
 background-color:#4cbb87
}
.tracking-status-attemptfail {
 background-color:#b789c7
}
.tracking-status-error,.tracking-status-exception {
 background-color:#d26759
}
.tracking-status-expired {
 background-color:#616e7d
}
.tracking-status-pending {
 background-color:#ccc
}
.tracking-status-inforeceived {
 background-color:#214977
}
.tracking-list {
 border:1px solid #e5e5e5
}
.tracking-item {
 border-left:1px solid #e5e5e5;
 position:relative;
 padding:2rem 1.5rem .5rem 2.5rem;
 margin-left:3rem;
 min-height:5rem
}
.tracking-item:last-child {
 padding-bottom:4rem
}
.tracking-item .tracking-date {
 margin-bottom:.5rem
}
.tracking-item .tracking-date span {
 color:#888;
 font-size:85%;
 padding-left:.4rem
}
.tracking-item .tracking-content {
 padding:.5rem .8rem;
 background-color:#f4f4f4;
 border-radius:.5rem
}
.tracking-item .tracking-content span {
 display:block;
 color:#888;
 font-size:90%
}
.tracking-item .tracking-icon {
 line-height:2.6rem;
 position:absolute;
 left:-1.3rem;
 width:2.6rem;
 height:2.6rem;
 text-align:center;
 border-radius:50%;
 background-color:#fff;
 color:#fff
}
.tracking-item .tracking-icon.status-sponsored {
 background-color:#f68
}
.tracking-item .tracking-icon.status-delivered {
 background-color:#4cbb87
}
.tracking-item .tracking-icon.status-outfordelivery {
 background-color:#f5a551
}
.tracking-item .tracking-icon.status-deliveryoffice {
 background-color:#f7dc6f
}
.tracking-item .tracking-icon.status-attemptfail {
 background-color:#b789c7
}
.tracking-item .tracking-icon.status-exception {
 background-color:#d26759
}
.tracking-item .tracking-icon.status-inforeceived {
 background-color:#214977
}
.tracking-item .tracking-icon.status-intransit {
 color:#e5e5e5;
 border:1px solid #e5e5e5;
}
@media(min-width:992px) {
 .tracking-item {
  margin-left:10rem
 }
 .tracking-item .tracking-date {
  position:absolute;
  left:-10rem;
  width:7.5rem;
  text-align:right
 }
 .tracking-item .tracking-date span {
  display:block
 }
 .tracking-item .tracking-content {
  padding:0;
  background-color:transparent
 }
}
  </style>

<div class="content-wrapper">
	
  <?php 
  $data['page_now'] = "integration_log";
  $data['pageinfo'] = "application_product";
  $this->load->view('templates/content_header', $data);?>

  <section class="content">
    <div class="row">
      <div class="col-md-12 col-xs-12">
        <div id="messages"></div>
        <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <?php echo $this->session->flashdata('success'); ?>
        </div>
        <?php elseif ($this->session->flashdata('error')): ?>
        <div class="alert alert-error alert-dismissible" role="alert">
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <?php echo $this->session->flashdata('error'); ?>
        </div>
        <?php endif;?>
        
        <div class="box box-primary">
          <div class="box-body">
		   <div class="row">
		      <div class="col-md-12 col-lg-12">
		         <div id="tracking-pre"></div>
		         <div id="tracking">
		            <div class="text-center tracking-status-intransit">
		            	<p class="tracking-status text-tight">
		               <?php  if ($btnRec) {?>
		               	 <a class="btn btn-primary" href="<?php echo $btnRec ?>"><i class="fa fa-arrow-circle-left"></i></a>&nbsp;&nbsp;
		               <?php }?>
		               <?=$this->lang->line('application_integration_log_with');?><?php echo $int_to; ?>
		               <?php  if ($btnFwd) {?>
		               	 &nbsp;&nbsp;<a class=" btn btn-primary" href="<?php echo $btnFwd ?>"><i class="fa fa-arrow-circle-right"></i></a>
		               <?php }?>
		                <a href="<?php echo base_url('products/update/'.$prd_id);?>" class="pull-right btn btn-warning"><?=$this->lang->line('application_back');?></a>
		               </p>
		            </div>
		            <div class="tracking-list">
		            	<?php foreach($logs as $log) {?>
		            	<div class="tracking-item">
		            	  <?php if ((($log['httpcode'] >= 200) && ($log['httpcode'] <= 204)) || ($log['httpcode']==true)) {  
		            	  	  $alert = 'blue'; ?>
			            	  <div class="tracking-icon status-inforeceived">
			                     <img src="<?php echo base_url('assets/images/system/icon-success.png');?>"  class="img-rounded" width="30" height="30" />
			                  </div>
		            	  <?php } else { 
		            	  	$alert = 'red';?>
			                  <div class="tracking-icon status-outfordelivery">
			                  	  <img src="<?php echo base_url('assets/images/system/icon-error.png');?>"  class="img-rounded" width="30" height="30" />
			                  </div>
		                  <?php } ?>
		                  <div class="tracking-date"><?php echo date_format(date_create($log['date_create']),'d/m/Y'); ?><span><?php echo date_format(date_create($log['date_create']),'H:i:s'); ?></span></div>
		                  <div class="tracking-content">
		                  	<?php echo 'Função: '.$log['function']; ?>
		                  	<a class="pull-right btn btn-primary" id="collapseRecBot<?php echo $log['id']; ?>" style="margin-right: 5px;" role="button" onclick="changeHide('<?php echo $log['id']; ?>')"><?=$this->lang->line('application_show');?></a>
		                  	<div style="display:none;"id="collapseRec<?php echo $log['id']; ?>">
			                  	<table>
			                  		<tr>
			                  			<th><span style="color:<?=$alert;?>"><?php echo 'httpcode:'; ?></span></th>
			                  			<td><span style="color:<?=$alert;?>"><?php echo $log['httpcode']; ?></span></td>
			                  		</tr>
			                  		<tr>
			                  			<th><span><?php echo 'url:'; ?></span></th>
			                  			<td><span><?php echo $log['url']; ?></span></td>
			                  		</tr>
			                  		<tr>
			                  			<th><span><?php echo 'Método:'; ?></span></th>
			                  			<td><span><?php echo $log['method']; ?></span></td>
			                  		</tr>
			                  		<tr>
			                  			<th style="text-align:left;vertical-align:top;padding:0"><span><?php echo 'Enviado:'; ?></span></th>
			                  			<td><pre><small><?php echo htmlspecialchars(json_encode(json_decode($log['sent']),JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES)); ?></small></pre></td>
			                  		</tr>
			                  		<tr>
			                  			<th style="text-align:left;vertical-align:top;padding:0"><span><?php echo 'Recebido:'; ?></span></th>
			                  			<td><pre><small><?php echo htmlspecialchars(json_encode(json_decode($log['response']),JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES)); ?></small></pre> </td>
			                  		</tr>
			                  	</table>
			                  	</div>
		                   </div>
		               </div>
		            	<?php } ?>
						
		            </div>
		         </div>
		      </div>
		   </div>
		   </div>
		</div>

      </div>
    </div>
  </section>
</div>

<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";
var prd_id = "<?php echo $prd_id; ?>";

$(document).ready(function() {

});

function changeHide(id) {
  let text = document.getElementById('collapseRecBot'+id).innerHTML;
  let collapseRec = document.getElementById('collapseRec'+id);
  if (text == '<?=$this->lang->line('application_hide');?>') {
  	collapseRec.style.display = 'none';
    document.getElementById('collapseRecBot'+id).innerHTML = '<?=$this->lang->line('application_show');?>';
  } else {
  	collapseRec.style.display = 'block';
    document.getElementById('collapseRecBot'+id).innerHTML = '<?=$this->lang->line('application_hide');?>';
  }
}

</script>
