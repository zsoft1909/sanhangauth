var CartButton = {};

CartButton.interval = null;
CartButton.intervalTimeOut = 5000;

CartButton.onSuccess = function(data) {
    if(data.itemAdded) CartButton.show();
    if (data.mode == 'redirect') {
    	window.location.href = '/cart';
    }
}

CartButton.show = function(text){
	$('.shop__cart-button').removeClass('empty');

	$('.shop__cart-button').addClass('notify');
	CartButton.interval = setTimeout(function(){
		$('.shop__cart-button').removeClass('notify');
	}, CartButton.intervalTimeOut);
};

$(document).ready(function($) {
	$('.shop__cart-button').addClass('active');
});