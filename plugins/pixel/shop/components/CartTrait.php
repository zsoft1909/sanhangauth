<?php namespace Pixel\Shop\Components;

use Flash;
use Pixel\Shop\Models\Item;
use Pixel\Shop\Classes\Cart;
use Pixel\Shop\Models\Coupon;

trait CartTrait{

    protected function onAddToCart(){
        $this->prepareLang();

        $currency = new \Responsiv\Currency\Helpers\Currency();
        
    	$cart = Cart::load();
    	$options = input('options');
    	$index = input('options_index');
    	$qty = input('quantity');
		$price = input('price');
		$id = input('id');
		$mode = input('mode');

		$extraData = array();
		$item = Item::find($id);
		$optionsIndex = $this->parseOptions(input('options_index'));

    	if($item->is_with_variants){
	    	foreach ($item->variants as $variant) {					
				$id = $variant['id'];
				$optionVal = null;

				if(array_key_exists($id, $optionsIndex) && !empty($optionsIndex[$id])){
					$optionVal = $optionsIndex[$id];
				}

				if($optionVal == null && $variant['optional']){
					return [ Flash::error(trans('pixel.shop::lang.messages.option_required', ['option' => $variant['variant']])) ];
				}
			}
		}

    	$itemAdded = $cart->addItem($item, $options, $price, $qty, $index, $extraData);

    	$cart->updateTotals();
    	$cart->save();

    	return [
    		'#cart-minimap' => $this->renderPartial('@cart-minimap', ['cart' => $cart]),
    		'#cart_count' => count($cart->items),
    		'#cart_total' => $currency->format($cart->total, ['format'=> 'short']),
    		'itemName' => $item->name,
    		'itemAdded' => $itemAdded,
    		'mode' => $mode
    	];
    }

    protected function parseOptions($option_index){
    	$option_index = explode(',', $option_index);
    	$options = [];

    	foreach ($option_index as $el) {
    		if(empty($el)){
				continue;
			}

    		$el = explode('::', $el);
    		$options[$el[0]] = $el[1];
    	}

    	return $options;
    }

    protected function onUpdateFromCart(){
    	$this->prepareLang();
    	$currency = new \Responsiv\Currency\Helpers\Currency();
    	$cart = Cart::load();
    	$quantity = input('quantity');
    	$index = input('index');

    	if (is_array($index)) {
    		foreach ($index as $key => $val) {
    			$cart->updateItem($val, $quantity[$key]);
    			$cart->updateTotals();
    			$cart->save();
    		}
    	}else{
	    	$cart->updateItem($index, $quantity);
	    	$cart->updateTotals();
    		$cart->save();
	    }

    	Flash::success('Giỏ hàng cập nhật số lượng thành công');

    	return [
    		'#cart_count' => count($cart->items),
    		'#cart-minimap' => $this->renderPartial('@cart-minimap', ['cart' => $cart]),
    		'#cart_total' => $currency->format($cart->total, ['format'=> 'short']),
    		'#payment_info' => $this->renderPartial('@cart_payment', [ 'cart' => $cart ]),
    		'#shop__cart-partial' => $this->renderPartial('@cart', [ 'cart' => $cart ]),
    		'#shop__carttotal-partial' => $this->renderPartial('@cart-total', [ 'cart' => $cart ]) 
    	];
    }

    protected function onDeleteFromCart(){
        $this->prepareLang();
        $currency = new \Responsiv\Currency\Helpers\Currency();

    	$cart = Cart::load();
    	$index = input('index');

    	if (is_array($index)) {
    		foreach ($index as $key => $val) {
    			$cart->deleteItem($val);
    		}
    	}else{
    		$cart->deleteItem($index);
    	}
    	
    	$cart->updateTotals();
    	$cart->save();

    	Flash::success(trans('pixel.shop::lang.messages.item_removed'));

    	return [ 
    		'#cart_count' => count($cart->items),
    		'#cart_total' => $currency->format($cart->total, ['format'=> 'short']),
    		'#cart-minimap' => $this->renderPartial('@cart-minimap', ['cart' => $cart]),
    		'#payment_info' => $this->renderPartial('@cart_payment', [ 'cart' => $cart ]),
    		'#shop__cart-partial' => $this->renderPartial('@cart', [ 'cart' => $cart ]),
    		'#shop__carttotal-partial' => $this->renderPartial('@cart-total', [ 'cart' => $cart ]) 
    	];
    }

    protected function onClearCart(){
        $this->prepareLang();
        
    	$cart = Cart::clear();

    	Flash::success(trans('pixel.shop::lang.messages.cart_clear'));

    	return [ 
    		'#shop__cart-partial' => $this->renderPartial('@cart', [ 
    			'cart' => $cart, 
    			'product_page' => $this->property('productPage') 
    		]) 
    	];
    }

    protected function onClearCoupon(){
        $this->prepareLang();
        $currency = new \Responsiv\Currency\Helpers\Currency();
    	$cart = Cart::load();

    	if($coupon = Coupon::where('code', $cart->coupon['code'])->first()){
    		$coupon->decrement('used_count', 1);
		}

    	$cart->coupon = null;
    	$cart->updateTotals();
    	$cart->save();

    	Flash::success(trans('pixel.shop::lang.messages.coupon_clear'));

    	return [ 
    		'#cart_count' => count($cart->items),
    		'#cart_total' => $currency->format($cart->total, ['format'=> 'short']),
    		'#cart-minimap' => $this->renderPartial('@cart-minimap', ['cart' => $cart]),
    		'#payment_info' => $this->renderPartial('@cart_payment', [ 'cart' => $cart ]),
    		'#shop__cart-partial' => $this->renderPartial('@cart', [ 'cart' => $cart ]),
    		'#shop__carttotal-partial' => $this->renderPartial('@cart-total', [ 'cart' => $cart ]) 
    	];
    }

    protected function onCheckCoupon(){
        $this->prepareLang();
        $currency = new \Responsiv\Currency\Helpers\Currency();

    	$code = strtoupper(input('coupon_code'));
    	if(!$coupon = Coupon::where('code', $code)->first()){
			return [ Flash::error(trans('pixel.shop::lang.coupon_codes.code_5')) ];
		}

    	$cart = Cart::load();

    	$coupon->isValid($cart->subtotal);

    	if($coupon->errorCode > 0){
			return [ Flash::error(trans('pixel.shop::lang.coupon_codes.code_' . $coupon->errorCode)) ];
		}

    	$cart->coupon = [
    		'id' => $coupon->id,
    		'code' => $coupon->code,
    		'discount' => $coupon->getValueLabel(),
    		'amount' => $coupon->getFinalDiscount($cart->subtotal)
    	];

    	$coupon->increment('used_count', 1);

    	$cart->updateTotals();
    	$cart->save();

    	return [ 
    		'#cart_count' => count($cart->items),
    		'#cart_total' => $currency->format($cart->total, ['format'=> 'short']),
    		'#cart-minimap' => $this->renderPartial('@cart-minimap', ['cart' => $cart]),
    		'#payment_info' => $this->renderPartial('@cart_payment', [ 'cart' => $cart ]),
    		'#shop__cart-partial' => $this->renderPartial('@cart', [ 'cart' => $cart ]),
    		'#shop__carttotal-partial' => $this->renderPartial('@cart-total', [ 'cart' => $cart ]),
    		Flash::success(trans('pixel.shop::lang.coupon_codes.code_0'))
    	];
    }
}
