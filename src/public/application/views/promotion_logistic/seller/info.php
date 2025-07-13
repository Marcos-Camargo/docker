<?php
// Redirecionamento temporário, relativo à LOG-457.
redirect('dashboard', 'refresh');
?>

<section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">Informações</h3>
				</div>        	
				<div class="box-body">            
					<div class="row">
						<div class="form-group col-md-4">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_name');?></label>
							<input disabled type="text" class="form-control" id="name" name="info[name]" required placeholder="<?=$this->lang->line('application_promotion_logistic_name')?>" autocomplete="off" value="<?php echo $promo['info']['name'];?>">
			            </div>						
						<div class="form-group col-md-3">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_dt_start');?></label>							
							<div class='input-group date' id='dt_start' name="dt_start">
								<input type='text' disabled required class="form-control" id='dt_start' name="info[dt_start]" autocomplete="off" value="<?php echo $promo['info']['dt_start'];?>" />
								<span class="input-group-addon">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_start_hour');?></label>							
							<div class='input-group date' id='start_hour' name="start_hour">
								<input disabled type='time' class="form-control" id='start_hour' name="info[start_hour]" value="<?php echo $promo['info']['start_hour'];?>" required autocomplete="off" value="" min="00:00" max="23:59"/>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-md-3">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_dt_end');?></label>							
							<div class='input-group date' id='dt_end' name="dt_end">
								<input disabled type='text' required class="form-control" id='dt_end' name="info[dt_end]" autocomplete="off" required value="<?php echo $promo['info']['dt_end'];?>" />
								<span class="input-group-addon">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
						<div class="form-group col-md-2">
							<label for="name"><?=$this->lang->line('application_promotion_logistic_end_hour');?></label>							
							<div class='input-group date' id='end_hour' name="end_hour">
								<input disabled type='time' class="form-control" id='end_hour' name="info[end_hour]" autocomplete="off" required value="<?php echo $promo['info']['end_hour'];?>" min="00:00" max="23:59"/>
							</div>
						</div>						
					</div>				
				</div>				
			</div>
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">Regras</h3>
				</div>				
				<div class="box-body">
					<div class="row">
						<div class="form-group col-md-12">
							<label for="name"><?=$this->lang->line('application_promotion_type');?></label>
							<select disabled class="form-control select_group" id="type" name="rules[type]">
								<option value=""><?=$this->lang->line('application_promotion_type_select');?></option>
								<?php foreach ($promotion['type'] as $k => $v): ?>
									<?php if($promo['rule'] == $k) { ?>
										<option value="<?php echo $k ?>" selected ><?php echo $v ?></option>
									<?php  } else { ?>
										<option value="<?php echo $k ?>"><?php echo $v ?></option>
									<?php }?>									
								<?php endforeach ?>
							</select>
						</div>						
					</div>
				</div>
			</div>
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">Critério</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="form-group col-md-3">
							<label for="name"><?=$this->lang->line('application_promotion_desc_type');?></label>
							<select disabled class="form-control select_group" id="type_desc" name="criterion[type]">
								<option value=""><?=$this->lang->line('application_promotion_desc_type_descont');?></option>
								<?php foreach ($promotion['type_desc'] as $k => $v): ?>
									<?php if($promo['criterion']['criterion_type'] == $k) { ?>
										<option value="<?php echo $k ?>" selected ><?php echo $v ?></option>
									<?php  } else { ?>
										<option value="<?php echo $k ?>"><?php echo $v ?></option>
									<?php }?>
								<?php endforeach ?>
							</select>
						</div>						
						<div class="form-group col-md-3">					
							<label for="name"><?=$this->lang->line('application_promotion_desc_type_value');?></label>
							<input disabled type="number" min="0" max="99999" step="0.01" class="form-control two-decimals" id="criterion_price" name="criterion[price]" required placeholder="<?=$this->lang->line('application_promotion_desc_type_value')?>" autocomplete="off" value="<?php echo $promo['criterion']['price_type_value'];?>">
						</div>
					</div>
					<div class="row">						
						<div class="form-group col-md-4">
							<label for="name"><?=$this->lang->line('application_promotion_prod_mim_value');?></label>
							<input disabled type="number" min="0" max="99999" step="0.01" class="form-control two-decimals" id="criterion_price_mim" name="criterion[price_mim]" required placeholder="<?=$this->lang->line('application_promotion_prod_mim_value')?>" autocomplete="off" value="<?php echo $promo['criterion']['product_value_mim'];?>">
						</div>						
						<div class="form-group col-md-4">
							<label for="name"><?=$this->lang->line('application_promotion_prod_qt');?></label>
							<input disabled type="number" min="0" max="99999" step="0.01" class="form-control two-decimals" id="product_amount" name="criterion[amount]" required placeholder="<?=$this->lang->line('application_promotion_prod_mim_value')?>" autocomplete="off" value="<?php echo $promo['criterion']['produtct_amonut'];?>">
						</div>
						<div class="form-group col-md-4">
							<label for="name"><?=$this->lang->line('application_promotion_prod_region');?></label>
							<div class="row">							
								<input disabled type="radio" name="criterion[region]" id="regionAll" required onchange="checkRegion(0)" <?php echo ( $promo['criterion']['region'] == 0 ) ? "checked" : "" ;?> value="0">
								<label for="criterion[region]">Para todo Brazil</label><br> 
								<input disabled type="radio" name="criterion[region]" id="regionToState" required onchange="checkRegion(1)" <?php echo ( $promo['criterion']['region'] == 1 ) ? "checked" : "" ;?> value="1">
								<label for="criterion[region]">Por Região</label><br> 
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">Selecione as regiões participantes</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<?php foreach($region as $key => $value ) { ?>
							<div class="form-group col-md-2">								
								<div class="form-check">
									<label class="form-check-label"><?php echo $value['name'];?></label>
								</div>
								<?php foreach($value['state'] as $keyState => $valueState ) { ?>
									<div class="form-check" style="padding-left: 10px;">
									<?php if ( in_array($valueState['cod_uf'], $promo['region'])) { ?>
										<label class="form-check-label"><?php echo $valueState['estado'];?></label>
									<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>			
			<div class="box">
				<div class="box-header">
					<h3 class="box-title">Categorias participantes</h3>
				</div>
				<div class="box-body">
					<div class="row">
						<div class="form-group col-md-12">
							<label for="name"><?=$this->lang->line('application_promotion_category');?></label>
							<select class="form-control select_group" id="category" name="category[]" multiple disabled>
								<option value=""><?=$this->lang->line('application_promotion_category');?></option>
								<?php foreach ($categories as $k => $v): ?>								
									<option value="<?php echo $v['id']; ?>" <?php echo ( in_array($v['id'], $promo['categories'])) ? "selected" : "" ;?> ><?php echo $v['name']; ?></option>
								<?php endforeach ?>
							</select>
						</div>						
					</div>
				</div>
			</div>			
      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
  </section>

<script type="text/javascript">


function checkRegion(check)
{	
	if(check == 0 ) {
		$("input[type=checkbox]").prop("checked", true);
	} else {
		$("input[type=checkbox]").prop("checked", false);
	}
}

function setRegion()
{
	$("#regionToState").prop("checked", true);
}

$(document).ready(function() {
	
	$('#category').select2();

	$("#criterion_price").on("change",function(){
	   $(this).val(parseFloat($(this).val()).toFixed(2));
	});
	
	$("#criterion_price_mim").on("change",function(){
	   $(this).val(parseFloat($(this).val()).toFixed(2));
	});


	$('#dt_start').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});
	
	$('#dt_end').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});

});

</script>

