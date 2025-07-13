<link rel="stylesheet" href="<?php echo base_url('assets/dist/css/stylesModalDialog.css') ?>">
<script type="text/javascript">

  $(document).ready(function() {

    var $videoSrc = '';
    

    $('#video_link').click(function() {
        $videoSrc = $(this).data( "src" );
    });
    
    $('#include_link').click(function() {
      $('#createLinkModal').show();
    });

    $('#update_link').click(function() {
      $('#editLinkModal').show();
    });

    $('#close_link').click(function() {
      $('#editLinkModal').hide();
    });

    $('#close_link_x').click(function() {
      $('#editLinkModal').hide();
    });
    
    $('#create_close_link_x').click(function() {
      $('#createLinkModal').hide();
    });
    
    $('#close_link').click(function() {
      $('#createLinkModal').hide();
    });

    $('#fechar_btn_edit').click(function() {
      $('#editLinkModal').hide();
    });

    $('#fechar_btn_include').click(function() {
      $('#createLinkModal').hide();
    });

    $('#myModal').on('shown.bs.modal', function (e) {  

      if($videoSrc.substr(0,2) == "PL")
      {
        $("#video").attr('src', window.location.protocol + "//www.youtube.com/embed?listType=playlist&list=" + $videoSrc +"&fs=1"); 
      }else{
        $("#video").attr('src', window.location.protocol + "//www.youtube.com/embed/" + $videoSrc ); 
      }
      
    })
  });


$(document).on( 'submit', '#createLinkForm' , function() {
        var form = $(this);

        var urlModuleClass = window.location.pathname.split('/');

        if(urlModuleClass[urlModuleClass.length - 1] == "")
        {
          urlModuleClass.pop();
        }

        if(urlModuleClass.length <= 3)
        {
          urlModuleClass.push('index');
        }

        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize() + '&module=' + urlModuleClass[urlModuleClass.length - 2] + '&class=' + urlModuleClass[urlModuleClass.length - 1] , 
          dataType: 'json',
          success:function(response) { 
            if(response != "") {
              $('#video_link').data( "src" , response );
              $('#createLinkModal').hide();
              $('#video_link').show();
              $('#update_link').show();
              $('#include_link').hide();
            }else
            {
              Swal.fire({
              icon: 'error',
              title: "O link invalido."
              });
            }
          }
        });
        return false;
    });


  $(document).on( 'submit', '#updateLinkForm' , function() {
        var form = $(this);

        var urlModuleClass = window.location.pathname.split('/');

        $("#video").attr('src', "" ); 
        
        if(urlModuleClass[urlModuleClass.length - 1] == "")
        {
          urlModuleClass.pop();
        }

        if(urlModuleClass.length <= 3)
        {
          urlModuleClass.push('index');
        }

        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize() + '&module=' + urlModuleClass[urlModuleClass.length - 2] + '&class=' + urlModuleClass[urlModuleClass.length - 1] , 
          dataType: 'json',
          success:function(response) { 
            if(response != "") {
                $('#video_link').data( "src" , response );
                $('#editLinkModal').hide();
            }
            else
            {
              Swal.fire({
                icon: 'error',
                title: "O link invalido."
              });
            }
          }
        })
      return false;
    });

</script>

<?php if ((ENVIRONMENT == 'development') || (ENVIRONMENT == 'testing')) { ?>
  <div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <b><?php echo $this->lang->line('application_test_environment_disclaimer'); ?></b>
  </div>
<?php  } ?>

  <div class="modal fade modal-video" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-body" >
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>        
          <!-- 16:9 aspect ratio -->
            <div class="embed-responsive embed-responsive-16by9" >
              <iframe class="embed-responsive-item" src="" id="video"  allowscriptaccess="always" allow="autoplay"></iframe>
            </div>        
        </div>
      </div>
    </div>
  </div> 

  <?php if(is_array($user_permission) && in_array('updateLink', $user_permission)): ?>  
  <div class="modal fade in" tabindex="-1" role="dialog" id="editLinkModal" aria-labelledby="exampleModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" id='close_link_x' ><span aria-hidden="true">×</span></button>
          <h4 class="modal-title">Editar Link</h4>
        </div>

        <form role="form" action="<?=base_url()?>users/updateLink" method="post" id="updateLinkForm">
          <div class="modal-body">
            <div id="messages"></div>

            <div class="form-group">
              <label for="edit_link_name">Nome</label>
              <input type="text" class="form-control" id="edit_link" name="edit_link" placeholder="Digite o link do video" autocomplete="off">
              </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-default" id='fechar_btn_edit' data-dismiss="modal">Fechar</button>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
          </div>

        </form>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div>
<?php endif?>
<?php if(is_array($user_permission) && in_array('createLink', $user_permission)): ?>
  <div class="modal fade in" tabindex="-1" role="dialog" id="createLinkModal" aria-labelledby="exampleModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Cose" id='create_close_link_x' ><span aria-hidden="true">×</span></button>
          <h4 class="modal-title">Incluir Link</h4>
        </div>

        <form role="form" action="<?=base_url()?>users/createLink" method="post" id="createLinkForm">
          <div class="modal-body">
            <div id="messages"></div>

            <div class="form-group">
              <label for="create_link">Nome</label>
              <input type="text" class="form-control" id="create_link" name="create_link" placeholder="Digite o link do video" autocomplete="off">
              </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-default" id='fechar_btn_include' data-dismiss="modal">Fechar</button>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
          </div>

        </form>
      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div>
<?php endif?>


    <!-- Content Header (Page header) -->
    <section class="content-header">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      <h1>
        <?=$this->lang->line("application_".$page_now) ; ?>
        <small><?=$this->lang->line($pageinfo);?></small>  
      </h1>
      
      <ol class="breadcrumb"  data-widget="tree">
          <?php if((isset($permissionLink)) && ($permissionLink != null)){?>
            <button type="button"  class="btn btn-primary video-btn" data-toggle="modal" id='video_link' data-src="<?=$permissionLink?>"  data-target="#myModal">
              <i class="fa fa-youtube-square" title="<?=$this->lang->line('application_training_video');?>"></i>&nbsp;<?=$this->lang->line('application_training_video');?></a>
            </button>
          <?php if(in_array('updateLink', $user_permission)): ?>  
              <button type="button"  class="btn btn-warning " data-toggle="modal" id='update_link' >
                <i class="fa fa-edit" title="<?=$this->lang->line('application_update');?>"></i></a>
              </button>
          <?php endif?>
        <?php } else if(is_array($user_permission) && in_array('createLink', $user_permission)) { ?>
            <?php if(!isset($permissionLink['link'])){?>
                <button type="button"  class="btn btn-success " data-toggle="modal" id='include_link' >
                    <i class="fa fa-youtube" data-toggle="tooltip" data-placement="left" title="<?=$this->lang->line('application_include_video_training');?>"></i>&nbsp;<?=$this->lang->line('application_include_video_training');?></a>
                </button>
                <button type="button"  style="display:none;" class="btn btn-primary video-btn" data-toggle="modal" id='video_link'   data-target="#myModal">
                    <i class="fa fa-youtube-square"  title="<?=$this->lang->line('application_training_video');?>"></i>&nbsp;<?=$this->lang->line('application_training_video');?></a>
                </button>
                <button type="button" style="display:none;" class="btn btn-warning " data-toggle="modal" id='update_link'  >
                    <i class="fa fa-edit" title="<?=$this->lang ->line('application_update');?>"></i></a>
                </button>
              <?php }else{ ?>
                <button type="button"  style="display:none;" class="btn btn-primary video-btn" data-toggle="modal" id='video_link'   data-target="#myModal">
                    <i class="fa fa-youtube-square"  title="<?=$this->lang->line('application_training_video');?>"></i>&nbsp;<?=$this->lang->line('application_training_video');?></a>
                </button>
                <button type="button" style="display:none;" class="btn btn-warning " data-toggle="modal" id='update_link'  >
                    <i class="fa fa-edit" title="<?=$this->lang ->line('application_update');?>"></i></a>
                </button>
                <?php }?>
          <?php }?>

        <li>
          <?=$this->session->company_and_store?>
        </li>
      	<?php if(is_array($user_permission) && in_array('changeStore', $user_permission)): ?>
          <li><a href="<?php echo base_url('asStore/change/') ?>" data-toggle="tooltip" data-placement="left" title="<?=$this->lang->line('application_change_store');?>"><i class="fa fa-exchange"></i></a></li>
        <?php endif; ?>
        
        <?php if(is_array($user_permission) && in_array('viewProfile', $user_permission)): ?>
          <li><a href="<?php echo base_url('users/profile/') ?>" data-toggle="tooltip" data-placement="left" title="<?=$this->lang->line('application_profile');?>"><i class="fa fa-user-o"></i> <?php echo $this->session->userdata('username'); ?></a></li>
        <?php endif; ?>
        <?php if(is_array($user_permission) && in_array('updateSetting', $user_permission)): ?>
          <li><a href="<?php echo base_url('users/setting/') ?>" data-toggle="tooltip" data-placement="left" title="<?=$this->lang->line('application_settings');?>"><i class="fa fa-wrench"></i></a></li>
        <?php endif; ?>
	      <li class="hidden-xs">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
              <?php if(!empty($language)){$default_language = $language; }else{ $default_language = "english"; } ?>
              <img src="<?=base_url()?>assets/bower_components/img/<?php if($this->input->cookie('swlanguage') != ""){echo $this->input->cookie('swlanguage');}else{echo $default_language;} ?>.png" style="margin-top:-4px" align="middle">
         
            </a>
             <ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dLabel">
                <?php if ($handle = opendir('application/language/')) {

    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && $entry != "index.html") {
          ?><li><a href="<?=base_url()?>users/language/<?=$entry;?>" ><img src="<?=base_url()?>assets/bower_components/img/<?=$entry;?>.png" class="language-img"> <?=ucwords($entry);?></a></li><?php
        }
    }

    closedir($handle);
    } 
?>

              </ul>
	      </li>
        <!-- user permission info -->
        <li><a href="<?php echo base_url('auth/logout') ?>" data-toggle="tooltip" data-placement="left" title="Logout"><i class="glyphicon glyphicon-log-out"></i></a></li>
        <!-- 
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active"><?php echo ucfirst($page_now); ?></li>-->
      </ol>
    </section>
      
