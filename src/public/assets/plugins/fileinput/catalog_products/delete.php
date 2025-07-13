<?php
  // echo print_r($_REQUEST);	
  $file = $_POST['key'];
  if (strpos("..".$file,"http")>0) {
  } else {
	$serverpath = $_SERVER['SCRIPT_FILENAME'];
	$pos = strpos($serverpath,'assets');
	$serverpath = substr($serverpath,0,$pos);
    unlink($serverpath.'assets/images/catalog_product_image/'. $file);
  }	  			  

  echo json_encode( [  ] );
?>	