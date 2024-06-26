"use strict";

var Variant = {};

$('[data-variant-new]').click(function(event) {
    event.preventDefault();
    console.log("NEW VARIANT");
    $(this).request('onAddVariant', {
        loading: $.oc.stripeLoadIndicator,
        success: function(data){
            if(data.success && data.render){
                $('.variants-main-container .variants-items').append(data.render);
                Variant.onRefreshUI();
            }
        }
    });
});

Variant.init = function(){
	this.onRefreshUI();
	this.onRefreshValues();
};

Variant.onRefreshUI = function(){
	var typingTimerName;
	var doneTypingIntervalName = 1000;

	$('[data-variant-pending]').each(function(index, el) {
		$(this).find('[data-variant-delete]').click(function(event) {
			event.preventDefault();
            let rows = document.querySelectorAll('.variant-item').length;
            if(rows > 1){
               $(this).closest('.variant-item').remove();
				Variant.onRefreshValues();
			}else{
				$.oc.flashMsg({text: $('.variants-items').data('delete-message'), 'class': 'error'});
			}
		});

		$(this).find('[data-option-new]').click(function(event) {
			event.preventDefault();
			var el = $(this);
			$(this).request('onAddOption', {
				loading: $.oc.stripeLoadIndicator,
				success: function(data){
					if(data.success && data.render){
						el.closest('.wrapper-variants')
							.find('tbody')
							.append(data.render);
						Variant.onRefreshUI();
					}
				}
			});
		});

		$(this).find('.input-option-change').change(function(event) {
			event.preventDefault();
			Variant.onRefreshValues();
		});

		$(this).find('.input-option-variant').keyup(function(event) {
            event.preventDefault();
			clearTimeout(typingTimerName);
			typingTimerName = setTimeout(doneTyping, doneTypingIntervalName);

			function doneTyping () {
				Variant.onRefreshValues();
			}
		});

		$(this).find('.input-option-optional').change(function(event) {
            event.preventDefault();
			Variant.onRefreshValues();
		});

		$(this).removeAttr('data-variant-pending');
	});

	$('[data-option-pending]').each(function(index, el) {
		$(this).find('.input-option-price').maskMoney({ allowNegative: true });
		$(this).find('.input-option-quantity').mask('00,000', { reverse: true });

		if($(this).find('.input-option-price').attr('data-price')){
			var dataPrice = $(this).find('.input-option-price-diff').attr('data-price');
			$(this).find('.product-price').val(parseFloat(dataPrice).toFixed(2));
		}

		$(this).find('[data-option-delete]').click(function(event) {
            event.preventDefault();
            let rows = document.querySelectorAll('.item-option').length;
			if(rows > 1){
				$(this).closest('.item-option').remove();
                rows = document.querySelectorAll('.item-option').length;
                console.log("rows after delete ",rows);
                Variant.onRefreshValues();
			}else{
                $.oc.flashMsg({text: $('.variants-items').data('delete-message'), 'class': 'error'});
            }
		});

		$(this).find('.form-control').change(function(event) {
			event.preventDefault();
			Variant.onRefreshValues();
		});

		$(this).find('.form-control').keyup(function(event) {
            event.preventDefault();
			clearTimeout(typingTimerName);
			typingTimerName = setTimeout(doneTyping, doneTypingIntervalName);

			function doneTyping () {
				Variant.onRefreshValues();
			}
		});

		$(this).removeAttr('data-option-pending');
	});
};

Variant.onRefreshValues = function(){
	var variants = [];

	$('.variants-main-container .variant-item').each(function(index, el) {
		var options = {
			"id" : $(this).find('.input-option-id').val() || null,
			"variant" : $(this).find('.input-option-variant').val() || null,
			"optional" : $(this).find('.input-option-optional').prop('checked'),
			"items" : []
		};

		$(this).find('.item-option').each(function(index, el) {
			var option = {};

			option.ref = $(this).find('.input-option-ref').val() || null
			option.val = $(this).find('.input-option-value').val() || null;
			option.code = $(this).find('.input-option-code').val() || null;
			option.image = $(this).find('.input-option-image').val() || null;
			option.quantity = $(this).find('.input-option-quantity').val() || null;
			option.price = $(this).find('.input-option-price-diff').val() || null;

			if(option.price)
				option.price = parseFloat(option.price.replace(/,/g, ''));

			if(option.quantity)
				option.quantity = parseInt(option.quantity);
console.log(option);
			options.items.push(option);
		});

		variants.push(options);
	});

	variants = encodeURIComponent(JSON.stringify(variants));
	$('.variants-field').val(variants);
};

jQuery(document).ready(function($) {
	Variant.init();
});
