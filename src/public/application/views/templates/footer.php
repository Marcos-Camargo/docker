  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version:</b> <?= ' ' . (($show_version_git_tag ?? false) ? ($version ?? '1.4.0') : '1.4.0'); ?>
    </div>
	<img src="<?php echo base_url() . $this->session->userdata('logo'); ?>"  width="100">
    <strong>Copyright &copy; 2018-<?php echo date('Y') ?>. </strong> All rights reserved. Powered by Full Nine Digital Consultoria LTDA.
  </footer>

  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->
<script src="<?php echo base_url('assets/bower_components/sweetalert/dist/sweetalert2.all.min.js') ?>" type="text/javascript"></script>
<script>
// Avoid form resubmit
if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    target: 'body',
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
    onOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})
const AlertSweet = Swal.mixin({
    target: 'body'
})

$("#mySearch").focus(function(){
    $('.treeview').addClass('menu-open');
    $('.treeview-menu').css("display", "block");
});

$("#mySearch").blur(function(){
    var input = $("#mySearch").val();
    if(!input.length){
        $("#emptyMsg").hide();
        $("#mySearch").val("");
        $("#myMenu").load(location.href + " #myMenu");
    }
});

$(".btn_clear").click(function(){
    $("#mySearch").val("");
    $("#emptyMsg").hide();
    $("#myMenu").load(location.href + " #myMenu");
});

$(document).ready(function(){
    $("#mySearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#myMenu li a").filter(function() {
           $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
       if($("#myMenu li a:visible").length > 0){
            $("#emptyMsg").hide();
       }else{
            $("#emptyMsg").show();
       }
      });
});

</script>
</body>
</html>
