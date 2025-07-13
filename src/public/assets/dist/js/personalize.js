function sidebarA(){
        // color_side_bar_a
        var color_side_bar_a = document.getElementById('color_side_bar_a').value;


        // linha sidebar vertical top
        var aside_main_sidebar_top = document.querySelectorAll('header.main-header a.logo');
        for(var x = 0; x < aside_main_sidebar_top.length; x++) {
          aside_main_sidebar_top[x].setAttribute("style", "background-color:transparent;border-right:"+color_side_bar_a+"!important;");
        }

        // color_all_text
        var color_all_text = document.getElementById('color_all_text').value;
        var color_all_text_tags = document.querySelectorAll('.inner h3, .inner p, a.sidebar-toggle,.content-header>h1>small, .breadcrumb.tree li a, .content-header h1, label, label.normal, th, td, td.sorting_1 a, button.btn.btn-link, .dataTables_info, .btn_clear, label.d-flex.justify-content-between a, span#char_product_name, span#char_description, h4.mb-3, .col-md-1 a, .modal-title, span#char_sku, h4.mt-0, h4.col-md-12.no-padding, tr.odd td a, tr.even a, .box-body h4');
        for(var x = 0; x < color_all_text_tags.length; x++) {
          color_all_text_tags[x].setAttribute("style", "color:"+color_all_text+"!important;");
        }
        var result_um_text = document.getElementById('result_um_text').innerHTML = color_all_text;

        // linha sidebar vertical
        var aside_main_sidebar = document.querySelectorAll('aside.main-sidebar');
        for(var x = 0; x < aside_main_sidebar.length; x++) {
          aside_main_sidebar[x].setAttribute("style", "border-color:"+color_side_bar_a+"!important;");
        }

        // link de menu ativo
        var link_a_active = document.querySelectorAll('li.treeview.active.menu-open');
        // Apenas textos
        var side_bar_a = document.querySelectorAll('.menuhref, .inner h3, .inner p, a.sidebar-toggle,.content-header>h1>small, .breadcrumb.tree li a, .content-header h1, label, label.normal, th, td, td.sorting_1 a, button.btn.btn-link, .dataTables_info, .btn_clear, label.d-flex.justify-content-between a, span#char_product_name, span#char_description, h4.mb-3, .col-md-1 a, .modal-title, span#char_sku, h4.mt-0, h4.col-md-12.no-padding, tr.odd td a, tr.even a, .box-body h4');

          // Botões e links - background
          var button_and_link_a = document.querySelectorAll('.paginate_button.active a, .panel-primary>.panel-heading');
          // botões - background e color fixa branca
          var button_background_and_border_color_and_color_white = document.querySelectorAll('#catlinkbuttondiv button');

        // border-color
        var border_color = document.querySelectorAll('.panel.panel-primary');
        // border-top-color
        var border_top_color = document.querySelectorAll('.box.box-primary');

        // button background default
        var button_default_background_color = document.getElementById('button_default_background_color').value;
        var button_default = document.querySelectorAll('a.btn.btn-default,button.btn.btn-default');
        for(var x = 0; x < button_default.length; x++) {
          button_default[x].setAttribute("style", "background-color:"+button_default_background_color+"!;important;border-color:"+button_default_background_color+"!important;");
        }

        // button background default [background]
        var button_default_background_color = $('#button_default_background_color').val();
        var button_default = document.querySelectorAll('a.btn.btn-default,button.btn.btn-default');
        for(var x = 0; x < button_default.length; x++) {
          button_default[x].setAttribute("style", "background:"+button_default_background_color+"!important;border-color:"+button_default_background_color+"!important;");
        }

          // button background default [color]
          var button_default_color = $('#button_default_color').val();
          var button_default = document.querySelectorAll('a.btn.btn-default,button.btn.btn-default');
          for(var x = 0; x < button_default.length; x++) {
            button_default[x].style.color = button_default_color;
          }

        // button background primary [background]
        var button_primary_background_color = $('#button_primary_background_color').val();
        var button_primary = document.querySelectorAll('a.btn.btn-primary,button.btn.btn-primary');
        for(var x = 0; x < button_primary.length; x++) {
          button_primary[x].setAttribute("style", "background:"+button_primary_background_color+"!important;border-color:"+button_primary_background_color+"!important;");
        }

          // button background primary [color]
          var button_primary_color = $('#button_primary_color').val();
          var button_primary = document.querySelectorAll('a.btn.btn-primary,button.btn.btn-primary');
          for(var x = 0; x < button_primary.length; x++) {
            button_primary[x].style.color = button_primary_color;
          }


        // button background success [background]
        var button_success_background_color = $('#button_success_background_color').val();
        var button_success = document.querySelectorAll('a.btn.btn-success,button.btn.btn-success');
        for(var x = 0; x < button_success.length; x++) {
          button_success[x].setAttribute("style", "background:"+button_success_background_color+"!important;border-color:"+button_success_background_color+"!important;");
        }

          // button background success [color]
          var button_success_color = $('#button_success_color').val();
          var button_success = document.querySelectorAll('a.btn.btn-success,button.btn.btn-success');
          for(var x = 0; x < button_success.length; x++) {
            button_success[x].style.color = button_success_color;
          }



        // button background danger [background]
        var button_danger_background_color = $('#button_danger_background_color').val();
        var button_danger = document.querySelectorAll('a.btn.btn-danger,button.btn.btn-danger');
        for(var x = 0; x < button_danger.length; x++) {
          button_danger[x].setAttribute("style", "background:"+button_danger_background_color+"!important;border-color:"+button_danger_background_color+"!important;");
        }

          // button background danger [color]
          var button_primary_color = $('#button_primary_color').val();
          var button_primaryA = document.querySelectorAll('a.btn.btn-primary,button.btn.btn-primary');
          for(var x = 0; x < button_primaryA.length; x++) {
            button_primaryA[x].style.color = button_primary_color;
          }


        // button background warning [background]
        var button_warning_background_color = $('#button_warning_background_color').val();
        var button_warning = document.querySelectorAll('a.btn.btn-warning,button.btn.btn-warning');
        for(var x = 0; x < button_danger.length; x++) {
          button_warning[x].setAttribute("style", "background:"+button_warning_background_color+"!important;border-color:"+button_warning_background_color+"!important;");
        }

          // button background warning [color]
          var button_primary_color = $('#button_primary_color').val();
          var button_primaryA = document.querySelectorAll('a.btn.btn-primary,button.btn.btn-primary');
          for(var x = 0; x < button_primaryA.length; x++) {
            button_primaryA[x].style.color = button_primary_color;
          }

        // button background warning
        var button_background_warning = document.querySelectorAll('.btn.btn-warning');
        // button background danger
        var button_background_danger = document.querySelectorAll('.btn.btn-danger');

        var side_bar_a2 = document.querySelectorAll('.small-box.bg-blue-light .icon i');
        var side_bar_a3 = document.querySelectorAll('.bg-blue');
        var footer_color = document.querySelectorAll('.main-footer');

        for(var x = 0; x < side_bar_a.length; x++) {
            side_bar_a[x].style.color = color_side_bar_a;
        }
        for(var x = 0; x < side_bar_a2.length; x++) {
            side_bar_a2[x].setAttribute("style", "color:"+color_side_bar_a+"!important;opacity: 0.3;");
        }
        for(var x = 0; x < side_bar_a3.length; x++) {
            side_bar_a3[x].setAttribute("style", "background-color:"+color_side_bar_a+"!important;");
        }
        for(var x = 0; x < footer_color.length; x++) {
          footer_color[x].style.color = color_side_bar_a;
        }
        for(var x = 0; x < button_and_link_a.length; x++) {
          button_and_link_a[x].setAttribute("style", "background-color:"+color_side_bar_a+"!important;border-color:"+color_side_bar_a+"!important;");
        }
        for(var x = 0; x < border_color.length; x++) {
          border_color[x].setAttribute("style", "border-color:"+color_side_bar_a+"!important;");
        }
        for(var x = 0; x < button_background_and_border_color_and_color_white.length; x++) {
          button_background_and_border_color_and_color_white[x].setAttribute("style", "background-color:"+color_side_bar_a+"!important;border: 1px solid "+color_side_bar_a+"!important;color:#fff!important");
        }
        for(var x = 0; x < border_top_color.length; x++) {
          border_top_color[x].setAttribute("style", "border-top-color:"+color_side_bar_a+"!important;");
        }
        var result_um = document.getElementById('result_um').innerHTML = color_side_bar_a;

      // color_side_bar_a_hover
        var color_side_bar_a_hover = document.getElementById('color_side_bar_a_hover').value;
        var side_bar_a_hover = document.querySelectorAll('.menuhref');
        for (let i = 0; i < side_bar_a_hover.length; i++) {
            side_bar_a_hover[i].addEventListener("mouseover", () => mOver(i), false);
            side_bar_a_hover[i].addEventListener("mouseout", () => mOut(i), false);
        }
        function mOver(i) {
            side_bar_a_hover[i].setAttribute("style", "color:"+color_side_bar_a_hover);
        }
        function mOut(i) {
            side_bar_a_hover[i].setAttribute("style", "color:"+color_side_bar_a);
        }
        var result_um_hover = document.getElementById('result_um_hover').innerHTML = color_side_bar_a_hover;

      // color_side_bar_a_background
        var color_side_bar_a_background = document.getElementById('color_side_bar_a_background').value;
        var side_bar_a_background = document.querySelectorAll('.menuhref');
        var side_bar_a_background2 = document.querySelectorAll('.sidebar');
        for(var i = 0; i < side_bar_a_background.length; i++) {
            side_bar_a_background[i].style.backgroundColor = color_side_bar_a_background;
        }
        for(var i = 0; i < side_bar_a_background2.length; i++) {
          side_bar_a_background2[i].style.backgroundColor = color_side_bar_a_background;
        }
        var result_dois = document.getElementById('result_dois').innerHTML = color_side_bar_a_background;

      // color_side_bar_a_background_hover
        var color_side_bar_a_background_hover = document.getElementById('color_side_bar_a_background_hover').value;
        var side_bar_a_background_hover = document.querySelectorAll('.menuhref');
        for (let i = 0; i < side_bar_a_background_hover.length; i++) {
            side_bar_a_background_hover[i].addEventListener("mouseover", () => mOver2(i), false);
            side_bar_a_background_hover[i].addEventListener("mouseout", () => mOut2(i), false);
        }
        function mOver2(i) {
            side_bar_a_background_hover[i].setAttribute("style", "background-color:"+color_side_bar_a_background_hover+";color:"+color_side_bar_a_hover);
        }
        function mOut2(i) {
            side_bar_a_background_hover[i].setAttribute("style", "background-color:"+color_side_bar_a_background+";color:"+color_side_bar_a);
        }
        var result_dois_houver = document.getElementById('result_dois_houver').innerHTML = color_side_bar_a_background_hover;

      // color_main_background
        var color_main_background = document.getElementById('color_main_background').value;
        var content_wrapper = document.querySelectorAll('.content-wrapper');
        for(var x = 0; x < content_wrapper.length; x++) {
          content_wrapper[x].style.backgroundColor = color_main_background;
        }
        var color_main_background_result = document.getElementById('color_main_background_result').innerHTML = color_main_background;
    // =================== FOOTER ====================
      // color_footer_background
        var color_footer_background = document.getElementById('color_footer_background').value;
        var main_footer = document.querySelectorAll('.main-footer');
        for(var x = 0; x < main_footer.length; x++) {
          main_footer[x].style.backgroundColor = color_footer_background;
        }
        var color_footer_background_result = document.getElementById('color_footer_background_result').innerHTML = color_footer_background;
      // color_footer_color
        // var color_footer_color = document.getElementById('color_footer_color').value;
        // var footer_color = document.querySelectorAll('.main-footer');
        // for(var x = 0; x < footer_color.length; x++) {
        //   footer_color[x].style.color = color_footer_color;
        // }
        // var color_footer_color_result = document.getElementById('color_footer_color_result').innerHTML = color_footer_color;
    // =================== FOOTER ====================

    for(var x = 0; x < link_a_active.length; x++) {
      link_a_active[x].setAttribute("style", "color:#123;background-color:#fff;");
    }

    }