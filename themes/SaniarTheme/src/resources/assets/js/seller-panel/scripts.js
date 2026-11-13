$(document).ready(function () {
   $('.new-navbar-menu li').hover(function () {
        var item=this;
        $(item).addClass('active')
    },function () {
        var item=this;
       $(item).removeClass('active');
       $(item).removeClass('show');
       $('.dropdown-menu').removeClass('show');

    })
});
