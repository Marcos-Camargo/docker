<!--
SW Serviços de Informática 2019

Editar usuarios

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
          
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
			
		      <?php if(in_array('updateUser', $user_permission)): ?>
		 	      <?php if (isset($sendpass)): ?>
		 		        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#emailBoasVindas"><?=$this->lang->line('application_welcome_email');?></button>
           	<?php endif; ?>
            <?php if (is_null($user_data['external_authentication_id'])) { // se tiver external não troca senha ?>
           	  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#resetPassword"><?=$this->lang->line('application_reset_password_email');?></button>
            <?php } ?>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sendMailCredentialApi"><?=$this->lang->line('application_send_credential_api_email');?></button>
          <?php endif; ?>
          
          <?php if(in_array('updateUser', $user_permission)): ?>
              <a type="button" class="<?=$user_data['active'] != 2 ?'btn btn-danger':'btn btn-success'?>" href="<?=$user_data['active'] != 2 ?base_url('users/inactive/' . $user_data['id']) : base_url('users/active/' . $user_data['id'])?>">
                <?=$user_data['active'] != 2 ?$this->lang->line('application_inactive_user'):$this->lang->line('application_active_user');?>
              </a>
          <?php endif; ?>
          	
          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=in_array('updateUser', $this->permission)?$this->lang->line('application_update_information'):$this->lang->line('application_view_information')?></h3>
            </div>
            <form id="formEditUser" role="form" action="<?php base_url('users/create') ?>" method="post" autocomplete="off">
                <input type="text" style="display:none">
                <input type="password" style="display:none">
            	  <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
              <div class="box-body">
                <div class="row">
            <?php 
            if (validation_errors()) { 
	          foreach (explode("</p>",validation_errors()) as $erro) { 
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>
                </div>
                <div class="row">
                    <div class="form-group col-md-5 <?php echo (form_error('username')) ? 'has-error' : '';  ?>">
                        <label for="username"><?=$this->lang->line('application_username');?>(*)</label>
                        <input type="text" class="form-control" id="username" required minlenght="5" name="username" placeholder="<?=$this->lang->line('application_enter_user_username');?>" value="<?php echo set_value('username', $user_data['username']) ?>"  autocomplete="off">
                        <p id="usernameDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_username')?></p>
                        <?php echo '<i style="color:red">'.form_error('username').'</i>'; ?>  
                    </div>
                
                    <div class="form-group col-md-5 <?php echo (form_error('email')) ? 'has-error' : '';  ?>">
                        <label for="email"><?=$this->lang->line('application_email');?>(*)</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="<?=$this->lang->line('application_enter_user_email');?>" value="<?php echo set_value('email', $user_data['email']) ?>" autocomplete="off">
                        <p id="emailDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_email')?></p>
                        <?php echo '<i style="color:red">'.form_error('email').'</i>'; ?>  
                    </div>
                
                    <div class="form-group col-md-2  <?php echo (form_error('active')) ? 'has-error' : '';  ?>">
                        <label for="active"><?=$this->lang->line('application_active');?>(*)</label>
                        <select class="form-control" id="active" name="active">
                          <option value="1" <?php echo set_select('active', 1, ($user_data['active'] == 1)) ?>><?=$this->lang->line('application_yes');?></option>
                          <option value="2" <?php echo set_select('active', 2, ($user_data['active'] == 2)) ?>><?=$this->lang->line('application_no');?></option>
                        </select>
                        <?php echo '<i style="color:red">'.form_error('active').'</i>'; ?>  
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-5 <?php echo (form_error('fname')) ? 'has-error' : '';  ?>">
                        <label for="fname"><?=$this->lang->line('application_firstname');?>(*)</label>
                        <input type="text" class="form-control" id="fname" name="fname" required placeholder="<?=$this->lang->line('application_enter_user_fname');?>" value="<?php echo set_value('fname', $user_data['firstname']) ?>" autocomplete="off">
                        <p id="fnameDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_fname')?></p>
                        <?php echo '<i style="color:red">'.form_error('fname').'</i>'; ?>  
                    </div>

                    <div class="form-group col-md-5 <?php echo (form_error('lname')) ? 'has-error' : '';  ?>">
                        <label for="lname"><?=$this->lang->line('application_lastname');?>(*)</label>
                        <input type="text" class="form-control" id="lname" name="lname" required placeholder="<?=$this->lang->line('application_enter_user_lname');?>" value="<?php echo set_value('lname', $user_data['lastname']) ?>" autocomplete="off">
                        <p id="lnameDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_lname')?></p>
                        <?php echo '<i style="color:red">'.form_error('lname').'</i>'; ?>  
                    </div>
                  
                    <div class="form-group col-md-2  <?php echo (form_error('phone')) ? 'has-error' : '';  ?>">
                        <label for="phone"><?=$this->lang->line('application_phone');?>(*)</label>
                        <input type="text" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone');?>" value="<?php echo set_value('phone', $user_data['phone']) ?>"  autocomplete="off">
                        <p id="phoneDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_phone')?></p>
                        <?php echo '<i style="color:red">'.form_error('phone').'</i>'; ?>  
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-3 <?php echo (form_error('company')) ? 'has-error' : '';  ?>">
                        <label for="company"><?=$this->lang->line('application_company');?>(*)</label>
                        <select class="form-control" id="company" name="company" required>
                            <option disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($company_data as $k => $v)  {  ?>
                                <option value="<?php echo $v['id'] ?>" <?php echo set_select('company', $v['id'], $v['id'] == $user_data['company_id']); ?> ><?php echo $v['name'] ?></option>
                            <?php } ?>
                        </select>
                        <p id="companyDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_company')?></p>
                        <?php echo '<i style="color:red">'.form_error('company').'</i>'; ?>  
                    </div>
                
                    <div class="form-group col-md-3 <?php echo (form_error('store_id')) ? 'has-error' : '';  ?>">
                        <label for="store_id"><?=$this->lang->line('application_store');?></label>
                        <select class="form-control" id="store_id" name="store_id" required>
                            <option value="0"><?=$this->lang->line('application_all_stores');?></option>
                        </select>
                        <?php echo '<i style="color:red">'.form_error('store_id').'</i>'; ?>  
                    </div>
                
                    <div class="form-group col-md-3 <?php echo (form_error('groups')) ? 'has-error' : '';  ?>">
                        <label for="groups"><?=$this->lang->line('application_groups');?>(*)</label>
                        <?php 
                        if (($user_group_id != 1 ) && ($user_group['id'] == 1)) { // o grupo do usuário é o Adminitrador mas o usuário atual não é do mesmo grupo
                            $group_data = array($user_group); 
                        }?> 
                          <select class="form-control" id="groups" name="groups" required>
                            <option disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($group_data as $k => $v): ?>
                              <option value="<?php echo $v['id'] ?>" <?php echo set_select('groups', $v['id'], $v['id'] == $user_group['id']); ?>  ><?php echo $v['group_name'] ?></option>
                            <?php endforeach ?>
                          </select>
                        <p id="groupsDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_groups')?></p>
                      <?php echo '<i style="color:red">'.form_error('groups').'</i>'; ?>  
                    </div>
                    <div class="form-group col-md-3 <?php echo (form_error('associate_type_pj')) ? 'has-error' : '';  ?>">
                        <label for="associate_type_pj"><?=$this->lang->line('application_associate_type')?>(*)</label>
                        <?php ?>
                        <select class="form-control" id="associate_type_pj" name="associate_type_pj">
                            <option value="0" <?= set_select('associate_type_pj', 0, ($user_data['associate_type'] == 0)) ?>><?=$this->lang->line('application_parent_company')?></option>
                            <option value="1" <?= set_select('associate_type_pj', 1, ($user_data['associate_type'] == 1)) ?>><?=$this->lang->line('application_agency')?></option>
                            <option value="2" <?= set_select('associate_type_pj', 2, ($user_data['associate_type'] == 2)) ?>><?=$this->lang->line('application_partner')?></option>
                            <option value="3" <?= set_select('associate_type_pj', 3, ($user_data['associate_type'] == 3)) ?>><?=$this->lang->line('application_affiliate')?></option>
                            <option value="4" <?= set_select('associate_type_pj', 4, ($user_data['associate_type'] == 4)) ?>><?=$this->lang->line('application_autonomous')?></option>
                            <option value="5" <?= set_select('associate_type_pj', 5, ($user_data['associate_type'] == 5)) ?>><?=$this->lang->line('application_indicator')?></option>
                        </select>
                        <?php echo '<i style="color:red">'.form_error('associate_type_pj').'</i>'; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-3">
                        <label> Tornar Usuário Agente no Agidesk</label>
                        <select class="form-control" id="make_user_agent" name="make_user_agent">
                            <option value="0" <?php echo set_select('make_user_agent', 0, ($user_data['make_user_agent'] == 0)) ?>> Inativo </option>
                            <option value="1" <?php echo set_select('make_user_agent', 1, ($user_data['make_user_agent'] == 1)) ?>> Ativo </option>
                        </select>
                    </div>
                </div>

                <div class="row" id="viewDataIndicate" style="display: none">
                    <div class="form-group col-md-3 <?php echo (form_error('bank')) ? "has-error" : "";?>">
                        <label for="bank"><?=$this->lang->line('application_bank');?></label>
                        <select class="form-control" id="bank" name="bank">
                            <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php 
                            foreach ($banks as $k => $v): ?>
                              <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'], $user_data['bank'] == trim($v['name'])) ?>><?=$v['name'] ?></option>
                            <?php endforeach ?>
                        </select>
                        <p id="bankDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_bank')?></p>
                        <?php echo '<i style="color:red">'.form_error('bank').'</i>'; ?>
                    </div>
                    <div class="form-group col-md-3 <?php echo (form_error('agency')) ? "has-error" : "";?>">
                        <label for="agency"><?=$this->lang->line('application_agency');?></label>
                        <input type="text" class="form-control" id="agency" name="agency" placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency', $user_data['agency'])?>">
                        <p id="agencyDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_agency')?></p>
                        <?php echo '<i style="color:red">'.form_error('agency').'</i>'; ?>
                    </div>
                    <div class="form-group col-md-3 <?php echo (form_error('account_type')) ? "has-error" : "";?>">
                        <label for="account_type"><?=$this->lang->line('application_type_account');?></label>
                        <select class="form-control" id="account_type" name="account_type">
                            <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($type_accounts as $k => $v): ?>
                                <option value="<?=trim($v)?>" <?=set_select('account_type', trim($v), $user_data['account_type'] == trim($v))?>><?=$v ?></option>
                            <?php endforeach ?>
                        </select>
                        <p id="account_typeDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_account_type')?></p>
                        <?php echo '<i style="color:red">'.form_error('account_type').'</i>'; ?>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3 <?php echo (form_error('account')) ? "has-error" : "";?>">
                            <label for="account"><?=$this->lang->line('application_account');?></label>
                            <input type="text" class="form-control" id="account" name="account" placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account', $user_data['account'])?>">
                            <p id="accountDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_bank_account')?></p>
                            <?php echo '<i style="color:red">'.form_error('account').'</i>'; ?>
                        </div>
                    </div>
                </div>
                
                <?php if(in_array('updateUser', $this->permission)): ?>

                <div class="row"  >
                    <label class="form-group col-md-3">
                        <input type="checkbox" onclick="checkLegalAdministrator()" class="minimal" name="legal_administrator" id="legal_administrator" value="1" <?php echo set_checkbox('legal_administrator', '1', $user_data['legal_administrator'] == 1) ?>>
                        <?=$this->lang->line('application_legal_administrator');?>
                    </label>
                    <div class="form-group col-md-3  <?php echo (form_error('cpf')) ? 'has-error' : '';  ?>" id="cpfdiv">
                        <label for="cpf"><?=$this->lang->line('application_cpf');?></label>
                        <input type="text" class="form-control"  id="cpf" name="cpf" required placeholder="<?=$this->lang->line('application_cpf');?>" value="<?php echo set_value('cpf',$user_data['cpf']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" maxlength="14">
                        <p id="cpfDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_enter_CPF')?></p>
                        <?php echo '<i style="color:red">'.form_error('cpf').'</i>'; ?>  
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-5">
                        <label for="external_authentication_id">Tipo de Authenticação</label>
                        <select class="form-control" id="external_authentication_id" name="external_authentication_id">
                            <option selected value="0">Interna (senha gravada localmente)</option>
                            <?php foreach ($externals_authentication as $extauth): ?>
                                <option value="<?=$extauth['id']?>" <?=set_select('external_authentication_id', $extauth['id'], $user_data['external_authentication_id'] == $extauth['id']); ?>><?=$extauth['name'].' ('.$extauth['type'].')';?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div id="viewInternalAuthentication" style="display: none">
                    <div class="row">
                        <div class="form-group  col-md-12">
                            <div class="alert alert-info alert-dismissible" role="alert">
                              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <?=$this->lang->line('messages_password_nochange');?>
                            </div>
                        </div>
                    </div>
                
                    <div class="row">
                        <div class="form-group col-md-5 <?php echo (form_error('password')) ? 'has-error' : '';  ?>">
                            <label for="password"><?=$this->lang->line('application_password');?>(*)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="<?=$this->lang->line('application_enter_user_password');?>"  autocomplete="off" value="<?php echo set_value('password', '') ?>">
                            <span onclick="hideShowPass(event, 'password')" ><small><i class="far fa-eye"></i><?=$this->lang->line('application_view_password');?></small></span>
                            <?php echo '<i style="color:red">'.form_error('password').'</i>'; ?> 
                        </div>
      
                        <div class="form-group col-md-5 <?php echo (form_error('cpassword')) ? 'has-error' : '';  ?>">
                            <label for="cpassword"><?=$this->lang->line('application_confirm_password');?>(*)</label>
                            <input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="<?=$this->lang->line('application_enter_user_cpassword');?>" autocomplete="off" value="<?php echo set_value('cpassword', '') ?>">
                            <span onclick="hideShowPass(event, 'cpassword')" ><small><i class="far fa-eye"></i><?=$this->lang->line('application_view_password');?></small></span>
                            <?php echo '<i style="color:red">'.form_error('cpassword').'</i>'; ?> 
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(in_array('updateUser', $this->permission)): ?>
                <div class="row">
                    <div class="form-group  col-md-12">
                        <h3 class="box-title"><?=$this->lang->line('messages_agidesk_information');?></h3>
                    </div>
                </div>
                <div class="row">
	                  <div class="form-group col-md-4">
                        <label for="password_agidesk"><?=$this->lang->line('application_password');?></label>
                        <input type="text" readonly class="form-control" id="password_agidesk" name="password_agidesk" placeholder="<?=$this->lang->line('application_enter_user_password');?>" autocomplete="off" value="<?php echo set_value('password_agidesk', $user_data['password_agidesk']) ?>" >
                    </div>
                    <div class="form-group col-md-4">
                	      <button type="button" class="btn btn-primary" id="bttestaragidesk"><i class="fa fa-ticket ">&nbsp<?=$this->lang->line('messages_agidesk_test_login');?></i></button>
				            </div>
                </div>
                <?php endif; ?>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                  <?php if(in_array('updateUser', $this->permission)): ?>
                  <button id="btnUserEdit" type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                  <?php endif; ?>
                  <a href="<?php echo base_url('users/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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

<div class="modal fade" tabindex="-1" role="dialog" id="emailBoasVindas">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?=$this->lang->line('application_welcome_email');?></h4>
        </div>
        <form role="form" action="<?php echo base_url('users/welcomeEmail') ?>" method="post" id="emailBoasVindasForm">
            <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
            <div class="modal-body">
                <p><?=$this->lang->line('application_only_one_welcome_email');?></p>			
                <input type="hidden" name="id_user_boas"  id="id_user_boas" value="<?php echo $user_data['id'];?>" autocomplete="off">
            </div> <!-- modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>	      
            </div>		
   	    </form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="resetPassword">
  <div class="modal-dialog" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title"><?=$this->lang->line('application_reset_password_email');?></h4>
          </div>
          <form role="form" action="<?php echo base_url('users/resetPassword') ?>" method="post" id="resetPasswordForm">
              <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
              <div class="modal-body">
                  <p><?=$this->lang->line('application_confirm_reset_password');?></p>			
                  <input type="hidden" name="id_user_reset"  id="id_user_boas" value="<?php echo $user_data['id'];?>" autocomplete="off">
              </div> <!-- modal-body -->
              <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                  <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>	      
              </div>		
          </form>  	
      </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="sendMailCredentialApi">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_send_credential_api_email');?></h4>
            </div>
            <form role="form" action="<?=base_url('users/sendMailCredentialApi') ?>" method="post" id="sendMailCredentialApiForm">
              	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <p><?=$this->lang->line('messages_send_mail_credential_api');?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="store_id_api"><?=$this->lang->line('application_select_store_send_credential');?></label>
                            <select class="form-control" id="store_id_api" name="store_id_api" required></select>
                        </div>
                    </div>
                    <input type="hidden" name="id_user"  id="id_user" value="<?=$user_data['id'];?>" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
<?php 
$tenant = $this->model_settings->getSettingDatabyName('agidesk');
if ($tenant['value'] !== 'conectala') {
	$url_agi = "https://".$tenant['value'].".agidesk.com/api/v1/auth/token";
}
else {
	$url_agi = "https://agidesk.com/api/v1/auth/token";
}
?>

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
var lojas = <?php echo json_encode($lojas); ?>;
var lojaoriginal = <?=$user_data['store_id']??''; ?>; 
var url_agi = "<?= $url_agi??''; ?>"; 
var banks = <?php echo json_encode($banks) ?>;
var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";


  $(document).ready(function() {
    var agency = $('#agency').val();
    var account = $('#account').val();
    var bank_name = $('#bank option:selected').val();

    var val = $("#cpf").val();
		$("#cpf").val(val.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4"));

    $("#cpf").on('focusout',function(){
				var val = $(this).val();
			  $(this).val(val.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4"));
		});

    if(usar_mascara_banco == true){
        applyBankMask(bank_name);
    }

    $("#bank").change(function () {
      $('#agency').val('');
        $('#account').val('');
        bank_name = $('#bank option:selected').val();
        if(usar_mascara_banco == true){
            applyBankMask(bank_name);
        }
    });
    if(<?=!in_array('updateUser', $this->permission)? 'true' : 'false' ?>){
      $('form input[type=checkbox]')
          .attr("onclick", "return false;");
      $('form input')
      .attr("readonly",true);
      $('form select')
          .attr("disabled", true);
      $('form textarea')
          .attr("disabled", true);
    }
    // $("#groups").select2();

    $("#mainUserNav").addClass('active');
    $("#manageUserNav").addClass('active');
    
    var id_company=$('#company option:selected').val();
    selectAndSetStore(id_company,lojas);
	  
	  $('#store_id').val(lojaoriginal);

  function applyBankMask(bank_name){
        $.each(banks, function(i,bank){
            if(banks[i].name == bank_name) {
                var pattern = /[a-zA-Z0-9]/ig;
                mask_account = banks[i].mask_account.replaceAll(pattern, "#")
                mask_agency = banks[i].mask_agency.replace(pattern, "#")
                $('#agency').mask(mask_agency);
                $('#agency').attr("placeholder", banks[i].mask_agency);
                $('#agency').attr("maxlength", mask_agency.length);
                $('#agency').attr("minlength", mask_agency.length);
                $('#account').mask(mask_account);
                $('#account').attr("placeholder", banks[i].mask_account);
                $('#account').attr("maxlength", mask_account.length);
                $('#account').attr("minlength", mask_account.length);
            }
        });
    }
    $("#company").change(function () {
		  var id_company=$('#company option:selected').val();
      //alert(id_company);
      selectAndSetStore(id_company,lojas);
	  });

    if ($('#associate_type_pj').val() != 0) {
        $('#viewDataIndicate').show();
        $('#viewDataIndicate .form-control').each(function () {
            $(this).prop('required', true);
        })
    }
    if ($('#external_authentication_id').val() == 0) {
        $('#viewInternalAuthentication').show();
    }else{
        $('#viewInternalAuthentication').hide();
    }

    checkLegalAdministrator()
  });
  
    $("#bttestaragidesk").click(function(e) {
    e.preventDefault();
    if (($("#email").val()=='') || ($("#password_agidesk").val()=='')) {
    	alert('Digite, pelo menos, o email e a senha do Agidesk');
    	return ;
    }
	var params = { 
		"username": $("#email").val(),
		"password": $("#password_agidesk").val(),
		"grant_type": "password"};
    $.ajax({
    	contentType: 'application/x-www-form-urlencoded',
        headers:{
            "X-Tenant-ID":"<?=$site_agidesk?>"
        },
    	crossDomain: true,
        type: "POST",
        // url: "https://agidesk.com/api/v1/auth/token",
        url: url_agi,
        data: params,
        dataType: 'json',
        success: function(result) {
            alert('Login OK');
          //  alert(result.access_token); 
        },
        error: function(result) {
        	var responseJson = result.responseJSON;
        	//alert(JSON.stringify(responseJson));
            alert('Error: '+responseJson.error_description);
            //alert(JSON.stringify(result));
        }
    });
  });

      //   $('[name="bank"]').attr({'disabled': true, 'required': false});
      //   $('[name="agency"]').attr({'disabled': true, 'required': false});
      //   $('[name="account_type"]').attr({'disabled': true, 'required': false});
      //   $('[name="account"]').attr({'disabled': true, 'required': false});

  $('#external_authentication_id').on('change', function (){
      if ($(this).val() == 0) {
          $('#viewInternalAuthentication').slideDown('slow');
      }
      else {
          $('#viewInternalAuthentication').slideUp('slow');
      }
  });

  $('#associate_type_pj').on('change', function (){
    if ($(this).val() == 0) {
         $('[name="bank"]').attr({'disabled': true, 'required': false});
         $('[name="agency"]').attr({'disabled': true, 'required': false});
         $('[name="account_type"]').attr({'disabled': true, 'required': false});
         $('[name="account"]').attr({'disabled': true, 'required': false});
    }else{
         $('[name="bank"]').attr({'disabled': false, 'required': false});
         $('[name="agency"]').attr({'disabled': false, 'required': false});
         $('[name="account_type"]').attr({'disabled': false, 'required': false});
         $('[name="account"]').attr({'disabled': false, 'required': false});
    }
   
    if ($(this).val() != 0) {
        $('#viewDataIndicate').slideDown('slow');
        $('[name="bank"]').val('');
        $('#viewDataIndicate .form-control').each(function () {
            $(this).prop('required', true);
        })
    } else {
        $('#viewDataIndicate').slideUp('slow');
        $('[name="bank"]').val('');
        $('[name="agency"]').val('');
        $('[name="account_type"]').val('');
        $('[name="account"]').val('');
        $('#viewDataIndicate .form-control').each(function () {
            $(this).prop('required', false);
        })
    }
  });
  
  function hideShowPass(e, fieldid) {
      e.preventDefault();
      var x = document.getElementById(fieldid);
      if (x.type === "password") {
        x.type = "text";
      } else {
        x.type = "password";
      }
  }
  async function selectAndSetStore(company_id,lojas){
    $('#store_id option:not(:first), #store_id_api option:not(:first)').remove();
      let filtered_store=lojas.filter((element)=>{
          return element.company_id==company_id;
    })
    await filtered_store.forEach((element)=>{
      $('#store_id').append('<option value="'+element.id+'">'+element.name+'</option>');
      $('#store_id_api').append('<option value="'+element.id+'">'+element.name+'</option>');
      if (lojaoriginal!=0 && lojaoriginal == element.id) {
        console.log(lojaoriginal && lojaoriginal == element.id);
        $('#store_id').val(lojaoriginal);
      }
    })
    if(filtered_store.length==1){
      console.log(filtered_store.length==1);
      console.log(filtered_store[0]);
      $('#store_id').val(filtered_store[0].id);
    }else{
    }
    
  }

  function checkLegalAdministrator(){
      if($('#legal_administrator').is(":checked")){
        $('#cpfdiv').show()
        $('#cpf').attr('required', true)
      }else{
        $('#cpfdiv').hide()
        $('#cpf').attr('required', false)
      }
  }

    var behavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
    options = {
        onKeyPress: function (val, e, field, options) {
            field.mask(behavior.apply({}, arguments), options);
        }
    };

    $('#phone').mask(behavior, options);

    $('#btnUserEdit').click(function() {
        var formError = 0;
        
        $('#formEditUser input, #formEditUser select').each(function() {
            if ($(this).attr('id') !== 'password' && $(this).attr('id') !== 'cpassword' && $(this).attr('id') !== 'bank' && $(this).attr('id') !== 'account_type') {
                if ($(this).attr('required') === 'required' && $(this).val() === '' || $(this).val() === null) {
                    $($(this)).css('border-color', '#dd4b39');
                    $('#' + $(this).attr('id') + 'Danger').show();
                    formError++;
                }
                else {
                    $($(this)).css('border-color', 'gray');
                    $('#' + $(this).attr('id') + 'Danger').hide();
                }
            }
        });

        if (formError !== 0) {
            return false;
        }
    });
</script>
