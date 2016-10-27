var ESBS = {};
jQuery( document ).ready(function( $ ) {
    ESBS.toggleBeanstreamForm = function(){
        console.log( "toggleBean");
        $( "#beanstream_form").show();
        var total_str = $( '.estore-cart-total').find('td:eq(1)').text();
        var amount = total_str.replace(/[^0-9-.]/g, '');
        console.log( "cart total[%s] amount:", total_str, amount);
        $( '#esbs-cart-total').val( amount);
    };
});
