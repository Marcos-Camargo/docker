<?php

header("Content-type: text/css; charset: UTF-8");

$sellerCenter = $_SERVER['QUERY_STRING'];
$filePath = "../CustomizationTheme/styles_".$sellerCenter.".txt";

$array = [];

if (file_exists($filePath)) {
    $handle = fopen($filePath, "r");
    if ($handle) {
        while (($buffer = fgets($handle, 4096)) !== false) {
            array_push($array, $buffer);
            $array = implode(",", $array);
            $array = (explode(",", $array));
    
                # - AJUSTES CSS DEFAULT
                    echo "
                    .input-color2{
                        height: 35px;
                    }
                    .btn-color2{
                        margin-top:-1em;
                    }
                    .nav-tabs-custom>.nav-tabs>li:first-of-type.active>a {
                        text-decoration: none;
                    }
                    .fix_color_default{
                        color:#0066CC!important;
                    }
                    ul.nav.nav-tabs li{
                    background-color: #fff!important;
                    }
                    fieldset.scheduler-border {
                    border: 1px groove #ddd !important;
                    padding: 0 1.4em 0em 1.4em !important;
                    margin: 0 0 1.5em 0 !important;
                    -webkit-box-shadow:  0px 0px 0px 0px #000;
                            box-shadow:  0px 0px 0px 0px #000;
                    }
                    legend.scheduler-border {
                        font-size: 1.2em !important;
                        font-weight: bold !important;
                        text-align: left !important;
                        width:auto;
                        padding:0 10px;
                        border-bottom:none;
                    }
                    .liAllBalck{
                        background-color:black!important;
                    }
                    .nav-tabs-custom .nav.nav-tabs li a{
                        text-decoration:none!important;
                    }";
    
                # - COLORS / VALORES
    
                if($array[14]){
    
                    echo ".menuhref {
                       color: $array[0];
                    }";
    
                    echo ".small-box-footer.bg-blue {
                        background-color:$array[14] !important;
                    }";
    
                    echo ".small-box.bg-blue-light .icon i {
                        color:$array[14];
                    }";
    
                    echo ".panel-primary>.panel-heading{
                        background-color: $array[14];
                        border-color: $array[14];
                    }";
    
                    echo ".panel-primary {
                        border-color: $array[14] !important;
                    }";
    
                    echo ".filter-option-inner-inner {
                        color: $array[14] !important;
                    }";
    
                    echo ".pagination>.active>a {
                        background-color: $array[14] !important;
                        border-color:$array[14] !important;
                    }";
    
                    echo ".inner h3, .inner p, a.sidebar-toggle,.content-header>h1>small, .breadcrumb.tree li a, .content-header h1,
                    label, label.normal, th, td, td.sorting_1 a, button.btn.btn-link, .dataTables_info, .btn_clear,
                    label.d-flex.justify-content-between a, span#char_product_name, span#char_description, h4.mb-3,
                    .col-md-1 a, .modal-title, span#char_sku, h4.mt-0, h4.col-md-12.no-padding, tr.odd td a, tr.even a,
                    .box-body h4, li.paginate_button.active, small {
                        color: $array[14];
                    }";
    
                    echo ".form-group.col-md-5 span, .form-group h5  {
                        color: $array[14] !important;
                    }";
    
                    echo ".select2-container--default.select2-container--open  {
                        color: $array[14] !important;
                    }";
    
                    echo ".box.box-primary {
                        border-top-color: $array[14];
                    }";
    
                    echo ".text-personalize {
                        color: $array[14];
                    }";
    
    
                }else{
    
                    echo ".menuhref, .inner h3, .inner p, a.sidebar-toggle,.content-header>h1>small, .breadcrumb.tree li a, .content-header h1,
                        label, label.normal, th, td, td.sorting_1 a, button.btn.btn-link, .dataTables_info, .btn_clear,
                        label.d-flex.justify-content-between a, span#char_product_name, span#char_description, h4.mb-3,
                        .col-md-1 a, .modal-title, span#char_sku, h4.mt-0, h4.col-md-12.no-padding, tr.odd td a, tr.even a,
                        .box-body h4, li.paginate_button.active, small {
                            color: $array[0];
                    }";
    
                    echo ".small-box-footer.bg-blue {
                        background-color:$array[0]!important;
                    }";
    
                    echo ".small-box.bg-blue-light .icon i {
                        color:$array[0]!important;opacity: 0.3;
                    }";
    
                    echo ".panel-primary>.panel-heading{
                        background-color: $array[0] !important;
                        border-color: $array[0] !important;
                    }";
    
                    //
                    echo ".panel-primary {
                        border-color: $array[0] !important;
                    }";
    
                    echo ".pagination>.active>a {
                        background-color: $array[0] !important;
                        border-color: $array[0] !important;
                    }";
    
                    echo ".form-group.col-md-5 span, .form-group h5  {
                        color: $array[0] !important;
                    }";
    
                    echo ".select2-container--default.select2-container--open  {
                        color: $array[0] !important;
                    }";
    
                    echo ".box.box-primary {
                        border-top-color: $array[0];
                    }";
    
                    echo ".text-personalize {
                        color: $array[0];
                    }";
    
                }
    
                echo ".bg-blue {
                     background-color:$array[0];
                }";
    
    
                echo "section.sidebar, .skin-conectala .sidebar-menu>li.active>a, .skin-conectala .sidebar-menu>li:hover>a {
                     background-color: $array[2];
                }";
    
                //treeview-menu
                echo ".skin-conectala .sidebar-menu .treeview-menu>li.active>a, .skin-conectala .sidebar-menu .treeview-menu>li>a:hover {
                    background-color: $array[3] !important;
                    color: $array[1] !important;
                    font-weight:bold!important;
                }";
    
                echo ".skin-conectala .sidebar-menu .treeview-menu>li>a {
                    background-color: $array[1]!important;
                    color: $array[0];
                }";
    
                echo ".skin-conectala .sidebar-menu>li.active>a, .skin-conectala .sidebar-menu>li:hover>a {
                    background-color: $array[3]!important;
                    color: $array[1];
                    font-weight: bold;
                }";
    
                echo ".skin-blue .sidebar-menu>li>.treeview-menu a{
                    background-color:#fff!important;
                }";
    
                // Lado do ul li
                echo ".skin-conectala .sidebar-menu>li>.treeview-menu {
                    width:none;
                }";
    
                echo ".treeview-menu>li {
                    margin-left:-5px;
                }";
    
                // UL LI a ativo
                echo ".treeview.active.menu-open li.active {
                        background-color:$array[0]!important;
                        margin-left: -5px;
                }";
    
                //
                echo ".main-sidebar,header .logo{
                    background-color:transparent;
                    border-right: 1px solid $array[0] !important;
                }";
    
                echo "a:focus {
                    background-color:$array[3]!important;
                    color: $array[1]!important;
                }";
    
                // Main background
                echo ".content-wrapper {
                    background-color:$array[4]!important;
                }";
    
                // footer background
                echo ".main-footer {
                    background-color:$array[5]!important;
                }";
    
                // Botões ==============================================
                    echo "a.btn.btn-default,button.btn.btn-default {
                        background-color:$array[6]!important;
                        border-color: $array[7]!important;
                        color: $array[7]!important;
                    }";
                    echo "a.btn.btn-primary,button.btn.btn-primary, .btn-file {
                        background-color:$array[8]!important;
                        border-color: $array[8]!important;
                        color: $array[9]!important;
                    }";
                    echo "a.btn.btn-success,button.btn.btn-success {
                        background-color:$array[16]!important;
                        border-color: $array[16]!important;
                        color: $array[17]!important;
                    }";
                    echo "a.btn.btn-danger,button.btn.btn-danger {
                        background-color:$array[10]!important;
                        border-color: $array[10]!important;
                        color: $array[11]!important;
                    }";
                    echo "a.btn.btn-warning,button.btn.btn-warning {
                        background-color:$array[12]!important;
                        border-color: $array[12]!important;
                        color: $array[13]!important;
                    }";
                // /.Botões ============================================
    
                    // footer top-border
                    echo ".skin-conectala .main-footer{
                        border-top-color: $array[0] !important;
                    }";
    
            }

        echo PHP_EOL . 'Seller Center: '. $sellerCenter;
        fclose($handle);
    }
}