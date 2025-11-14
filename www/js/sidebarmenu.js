/* 
 * JQuery para el manejo de menu
 */

$('#dismiss, .overlay').on('click', function () {
    // hide sidebar
    $('#sidebar').removeClass('active');
    // hide overlay
    $('.overlay').removeClass('active');
    $('.menu').css('visibility', 'visible');
});

$('#sidebarCollapse').on('click', function () {
    // open sidebar
    $('#sidebar').addClass('active');
    // fade in the overlay
    $('.overlay').addClass('active');
    $('.menu').css('visibility', 'hidden');
    //$('.collapse.in').toggleClass('in');
    $('a[aria-expanded=true]').attr('aria-expanded', 'false');
});