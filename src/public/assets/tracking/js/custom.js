$(function () {
    'use strict'; // Start of use strict 


/*--------------------------
    scrollUp
---------------------------- */
    $.scrollUp({
        scrollText: '<i class="fas fa-chevron-up"></i>',
        easingType: 'linear',
        scrollSpeed: 900,
        animation: 'fade'
    });

/*--------------------------
    Reviews
---------------------------- */
    $('.owl-carousel').owlCarousel({
        loop: true,
        margin: 10,
        responsiveClass: true,
        nav: true,
        smartSpeed: 900,
        navText: ['<i class="fas fa-angle-double-left"></i>', '<i class="fas fa-angle-double-right"></i>'],
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 1
            },
            1000: {
                items: 1
            }
        }
    })

/*------------------------------------------------------------------
   mobile navbar click 
------------------------------------------------------------------*/
    var w = $(window).width();
    if(w <= 991){
    $("#navbar .nav-item").on('click', function(){
        $(".navbar-toggler").trigger('click');
    });
    }
    //
    //
 $("#contact-form").submit(function (e) {

    e.preventDefault(); // avoid to execute the actual submit of the form.

    var formData = {
        name:$("#name").val(),
        email:$("#email").val(),
        message:$("#message").val()
        }; 
    if ($("#email").val()) {
        $.ajax({
            'url': 'sendmail.php',
            'method': 'POST',
            'dataType': 'json',
            'contentType': 'application/json',
            'data': JSON.stringify(formData),
            'success': function (payload) {
                if(payload.code=="200"){
                $(".alert").removeClass("d-none").html("<p>Thanks for contacting us.. we will get back to you soon.</p>").addClass("alert-success");
                $("#contact-form").trigger("reset");
                }else{
                $(".alert").removeClass("d-none").html("<p>Please enter required fields. (or) Try again..</p>").addClass("alert-warning");
                }
            }
        });
    } else {
        $(".alert").removeClass("d-none").html("<p>Please enter required fields. (or) Try again..</p>").addClass("alert-warning");
    }
});
});

/*------------------------------------------------------------------
 Fixed Navigation 
------------------------------------------------------------------*/
$(window).scroll(function () {
    if ($(this).scrollTop() > 20) {
        $('#navbar').addClass('header-scrolled');
    } else {
        $('#navbar').removeClass('header-scrolled');
    }
});

/*------------------------------------------------------------------
 Loader 
------------------------------------------------------------------*/
jQuery(window).on("load scroll", function () {
    'use strict'; // Start of use strict
    // Loader 
    $('#dvLoading').fadeOut('slow', function () {
        $(this).remove();
    });

});
//
 
