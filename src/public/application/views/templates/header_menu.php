<header class="main-header">
    <!-- Logo -->
    <a href="/" class="logo" style="background-color:white;">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b></b></span>
      <!-- logo for regular state and mobile devices -->
      <?php if ($this->session->userdata('company_logo')=="") { ?>
      <span class="logo-lg"></span>
      <?php } else { ?> 
      <span class="logo-lg"><img src="<?php echo base_url() . $this->session->userdata('company_logo'); ?>"  width="150" height="50"></span>
      <?php } ?>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
 <!--   <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
 <!--   </nav> -->
  </header>
  <!-- Left side column. contains the logo and sidebar -->
  