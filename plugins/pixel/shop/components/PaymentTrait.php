<?php

namespace Pixel\Shop\Components;

use Log;
use Flash;
use Redirect;
use stdClass;
use Auth;
use Validator;
use Exception;
use Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Omnipay\Omnipay;
use ValidationException;
use Pixel\Shop\Models\Item;
use Pixel\Shop\Classes\Cart;
use Pixel\Shop\Models\Coupon;
use Omnipay\Common\CreditCard;
use Pixel\Shop\Models\GatewaysSettings;
use RainLab\User\Models\User;
use Responsiv\Currency\Helpers\Currency;
use Symfony\Component\Yaml\Inline;
use System;

trait PaymentTrait
{

	protected function onSendCheckout()
	{

        Flash::success(trans('pixel.shop::lang.messages.succesful_order'));
		$this->prepareLang();

		$cart = Cart::load();
		$data = input();
		$settings = GatewaysSettings::instance();

		$rules = [
			'customer_name' => 'required|min:3|max:60',
			// 'customer_email' 	=> 'required|email',
			'customer_phone' 	=> 'required|min:8',
			// 'customer_address'  => 'required|min:8',
			'gateway' 			=> 'required',
		];

		$names = [
			'customer_name' => 'Họ và tên',
			// 'customer_email' => 'Địa chỉ Email',
			'customer_phone' => 'Số điện thoại',
			// 'customer_address' => 'Địa chỉ',
			'gateway' => 'Vui lòng chọn phương thức thanh toán trước',
			'is_ship_same_bill' => strtolower(trans('pixel.shop::lang.fields.is_ship_same_bill')),
		];

		if ($extras = $this->getCustomFieldsSettings()) {
			foreach ($extras as $group => $fields) {
				if (!count($fields)) {
					continue;
				}

				foreach ($fields as $field) {
					if (empty($field['rules'])) {

						continue;
					}

					$rules['custom_fields.' . $group . '.' . $field['name'] . '.value'] = $field['rules'];
					$names['custom_fields.' . $group . '.' . $field['name'] . '.value'] = $field['label'];
				}
			}
		}

		$validation = Validator::make($data, $rules, trans('pixel.shop::validation'), $names);

		if ($validation->fails()) {
			throw new ValidationException($validation);
		}

		if (count($cart->items) < 1) {
			return [Flash::error(trans('pixel.shop::lang.messages.empty_cart'))];
		}

		if ($user = $this->user()) {
			$cart->user = $user->id;

			$user->phone = input('customer_phone');
			$user->shipping_address = input('customer_address');
			$user->billing_address = input('customer_address');

			if (input('is_save_for_later')) {
				$user->save();
			}
		}

		$cart->customer_name = input('customer_name');
		$cart->customer_address = input('customer_address');
		$cart->customer_email = input('customer_email');
		$cart->customer_phone = input('customer_phone');

		$cart->shipping_address = input('shipping_address');
		$cart->billing_address = input('is_ship_same_bill')  == 'on' ? input('shipping_address') : input('billing_address');
		$cart->notes = input('notes');
		$cart->custom_fields = input('custom_fields', array());

		// Log::debug(json_encode([
		// 	'shipping' => input('shipping_address'),
		// 	'billing' => input('billing_address')
		// ]));

		$cart->gateway = input('gateway');
		$cart->save();

		$order = $cart->createOrderFromCart();
		if ($cart->gateway == 'cash_on_delivery') {
			$order->gateway = $cart->gateway;
			$order->status = 'await_pay';
			$order->save();
			// $order->sendNotification();
			Cart::clear();

			return ['#checkout-container' => $this->renderPartial('@order_summary', [
				'order' => $order,
				'settings' => $settings,
				'thanks' => true
			])];
		}

	}

	protected function getPaymentMethodsList($country = null)
	{
		$list = array();
		$cart = Cart::load();
		$gs = GatewaysSettings::instance();

		// CREDIT CARD
		if ($gs->checkIsAllowed($country, 'cc'))
			$list[] = [
				'title' => trans('pixel.shop::lang.fields.credit_card'),
				'gateway' => 'cc',
				'partial' => $this->renderPartial('@method_cc', ['settings' => $gs])
			];

		// BANK TRANSFER
		if ($gs->checkIsAllowed($country, 'bank_transfer'))
			$list[] = [
				'title' => trans('pixel.shop::lang.fields.bank_transfer'),
				'gateway' => 'bank_transfer',
				'partial' => $this->renderPartial('@method_bank_transfer', ['settings' => $gs, 'cart' => $cart])
			];

		// CASH ON DELIVERY
		if ($gs->checkIsAllowed($country, 'cash_on_delivery'))
			$list[] = [
				'title' => trans('pixel.shop::lang.fields.cash_on_delivery'),
				'gateway' => 'cash_on_delivery',
				'partial' => $this->renderPartial('@method_cash_on_delivery', ['settings' => $gs])
			];

		// PAYPAL IPN
		if ($gs->checkIsAllowed($country, 'paypal'))
			$list[] = [
				'title' => trans('pixel.shop::lang.fields.paypal'),
				'gateway' => 'paypal',
				'partial' => $this->renderPartial('@method_paypal', ['settings' => $gs])
			];

		// PIXELPAY
		if ($gs->checkIsAllowed($country, 'pixelpay'))
			$list[] = [
				'title' => trans('pixel.shop::lang.fields.pixelpay'),
				'gateway' => 'pixelpay',
				'partial' => $this->renderPartial('@method_pixelpay', ['settings' => $gs])
			];

		return $list;
	}

	protected function processPaymentCC($order)
	{
		$settings = GatewaysSettings::instance();
		$gateway = null;

		try {
			switch (GatewaysSettings::get('cc_default')) {
				case 'stripe':
					$gateway = Omnipay::create('Stripe');
					$gateway->initialize([
						'apiKey' => GatewaysSettings::get('stripe_api_key')
					]);
					break;

				case 'paypal_pro':
					$gateway = Omnipay::create('PayPal_Pro');
					$gateway->initialize([
						'username' => GatewaysSettings::get('paypal_pro_username'),
						'password' => GatewaysSettings::get('paypal_pro_password'),
						'signature' => GatewaysSettings::get('paypal_pro_signature')
					]);
					break;
			}

			$cc_number = str_replace(' ', '', post("cc_number"));
			$cc_em = substr(post("cc_exp"), 0, 2);
			$cc_ey = substr(post("cc_exp"), -2);
			$cc_cvv = post("cc_cvv");

			$cardParams = [
				"firstName" => $order->customer_name,
				"lastName" => '',

				"number" => $cc_number,
				"expiryMonth" => $cc_em,
				"expiryYear" => $cc_ey,
				"cvv" => $cc_cvv,

				"issueNumber" => $order->id,

				"billingAddress1" => $order->billing_address['first_line'],
				"billingAddress2" => $order->billing_address['second_line'],
				"billingCity" => $order->billing_address['city'],
				"billingState" => $order->billing_address['state'],
				"billingCountry" => $order->billing_address['country'],
				"billingPostcode" => $order->billing_address['zip'],
				"billingPhone" => $order->customer_phone,

				"shippingAddress1" => $order->shipping_address['first_line'],
				"shippingAddress2" => $order->shipping_address['second_line'],
				"shippingCity" => $order->shipping_address['city'],
				"shippingState" => $order->shipping_address['state'],
				"shippingCountry" => $order->shipping_address['country'],
				"shippingPostcode" => $order->shipping_address['zip'],
				"shippingPhone" => $order->customer_phone,

				"email" => $order->customer_email,
			];

			$card = new CreditCard($cardParams);

			$response = $gateway->purchase(array(
				'amount' => floatval($order->total),
				'currency' => $order->currency,
				'card' => $card
			))->send();

			$orderResponse = [
				"isSuccessful" => $response->isSuccessful(),
				"isRedirect" => $response->isRedirect(),
				"getTransactionReference" => $response->getTransactionReference(),
				"getMessage" => $response->getMessage(),
			];

			if ($response->isSuccessful()) {
				$order->status = 'await_fulfill';
				$order->is_paid = true;
				$order->is_confirmed = true;
				$order->paid_at = Carbon::now();
				$order->save();

				$order->reduceInventory();
				$order->sendNotification();
				Cart::clear();

				return ['#checkout-container' => $this->renderPartial('@order_summary', [
					'order' => $order,
					'settings' => $settings,
					'thanks' => true
				])];
			} else {
				$eventLog = new \System\Models\EventLog();
				$data = array();
				$data["name"] = "Payment Gateway Error: $order->gateway order_id: $order->id";
				$data["response"] = $orderResponse;
				$eventLog->message = json_encode($orderResponse);
				$eventLog->save();

				Flash::error($response->getMessage());
			}
		} catch (Exception $e) {
			$eventLog = new \System\Models\EventLog();
			$data = array();
			$data["name"] = "ON PAYMENT ERROR";
			$data["response"] = $e->getMessage();
			$eventLog->message = json_encode($data);
			$eventLog->save();

			Flash::error($e->getMessage());
		}
	}
	protected function updatePixelCard($cardParams)
	{
		$pixelDomain = $this->getPixelDomain();
		$url = $pixelDomain . '/api/v2/tokenization/card/' . $cardParams['card_token'];
		return $this->doPixelPayRequest($url, $cardParams, 'PUT');
	}
	protected function deletePixelCard($token)
	{
		$pixelDomain = $this->getPixelDomain();
		$url = $pixelDomain . '/api/v2/tokenization/card/' . $token;
		return $this->doPixelPayRequest($url, "delete");
	}
	protected function getPixelDomain()
	{
		return empty(GatewaysSettings::get('pixelpay_endpoint')) ?
			'https://pixel-pay.com' : 'https://' . GatewaysSettings::get('pixelpay_endpoint'); //END POINT
	}

	protected function preparePixelPay($order)
	{
		$pixelDomain = $this->getPixelDomain();
		$apiURL = '/api/v2/transaction/hosted/october';
		$json = json_encode($order->items);
		$base64 = base64_encode($json);
		$order_content = urlencode($base64);
		$fields = [
			'pixelpay_key' => GatewaysSettings::get('pixelpay_app'),

			'order_cancel' => $this->controller->currentPageUrl() . "?order_id=$order->id&cancel=true",
			'order_complete' => $this->controller->currentPageUrl() . "?order_id=$order->id&thanks=true",
			'order_id' => $order->id,
			'order_currency' => $order->currency,
			'order_tax_amount' => number_format(floatval($order->tax_total), 2, '.', ''),
			'order_shipping_amount' => number_format(floatval($order->shipping_total), 2, '.', ''),
			'order_amount' => number_format(floatval($order->total), 2, '.', ''),
			'customer_name' => $order->customer_name,
			'customer_address' => $order->customer_address,
			'customer_email' => $order->customer_email,
			'first_line' => $order->billing_address['first_line'],
			'second_line' => $order->billing_address['second_line'],
			'zip' => $order->billing_address['zip'],
			'city' => $order->billing_address['city'],
			'state' => $order->billing_address['state'],
			'country' => $order->billing_address['country'],
			'json' => true
		];

		$response = $this->doPostRequest($pixelDomain . $apiURL, $fields);

		if ($response->success) {
			$data = $response->body;
			$json = json_decode($data);
			if (property_exists($json, 'success') && $json->success) {
				return redirect($json->url);
			} else {
				if (property_exists($json, 'message')) {
					Flash::error($json->message);
				} else {
					Flash::error(trans("pixel.shop::component.payment.request_error"));
				}
			}
		} else {
			Flash::error(trans("pixel.shop::component.payment.request_error"));
		}
	}

	protected function doPostRequest($url, $data)
	{
		$response = new stdClass();
		$response->success = false;
		$response->code = null;
		$response->body = null;


		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$body = curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close($ch);

			$response->success = true;
			$response->body = $body;
			$response->code = $code;

			\Log::debug($body);

			return $response;
		} catch (\Throwable $th) {
			report($th);
			return $response;
		}
	}

	protected  function makePaymentWithToken($order, $cardParams)
	{
		$pixelDomain = $this->getPixelDomain();

		$url = $pixelDomain . '/api/v2/transaction/sale';
		$data =
			[
				"customer_name" => $order->customer_name,
				"customer_email" => $order->customer_email,
				"order_id" => $order->id,
				"order_amount" => round($order->total, 2),
				"order_category" => 'october',
				"order_currency" => $order->currency,
				"card_token" => $cardParams['card_token'],
				"billing_address" => $order->shipping_address['first_line'],
				"billing_city" =>  $order->shipping_address['city'],
				"billing_state" => $order->shipping_address['state'],
				"billing_country" => $order->shipping_address['country'],
				"billing_phone" => $order->customer_phone,
			];
		return  $this->doPixelPayRequest($url, $data);
	}

	protected function makePaymentWithCard($order, $cardParams)
	{

		$pixelDomain = $this->getPixelDomain();
		$url = $pixelDomain . '/api/v2/transaction/sale';
		$data =
			[
				"customer_name" => $order->customer_name,
				"customer_email" => $order->customer_email,
				"order_id" => $order->id,
				"currency" => $order->currency,
				"order_amount" =>  round($order->total, 2),
				"order_category" => 'october',
				"order_currency" => $order->currency,
				"card_number" => str_replace(' ', '', $cardParams['cc_number']),
				"card_cvv" => $cardParams['cc_cvv'],
				"card_holder" => $cardParams['cc_name'],
				"card_expire" => $cardParams['cc_exp'] == "" ? "" : $cardParams['cc_exp'][5] . $cardParams['cc_exp'][6] . $cardParams['cc_exp'][0] . $cardParams['cc_exp'][1],
				"billing_address" => $order->shipping_address['first_line'],
				"billing_city" =>  $order->shipping_address['city'],
				"billing_state" => $order->shipping_address['state'],
				"billing_country" => $order->shipping_address['country'],
				"billing_phone" => $order->customer_phone,
			];
		return  $this->doPixelPayRequest($url, $data);
	}

	public static function doPixelPayRequest($url,  $data = null, $put = false)
	{
		try {
			$headers = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/json',
				'x-auth-key: ' . GatewaysSettings::get('pixelpay_app'),
				'x-auth-hash:' . md5(GatewaysSettings::get('pixelpay_hash')),
			);

			$response = new stdClass();
			$response->success = false;
			$response->code = null;
			$response->body = null;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			if ($data != null && $data != "delete") {
				if ($put) {
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				} else {
					curl_setopt($ch, CURLOPT_POST, true);
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			}
			if ($data == "delete") {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$body = curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			//$response->success = true;
			$response->body = $body;
			//dd($response->body);
			$response->code = $code;
			return json_decode($response->body, true);
		} catch (\Throwable $th) {
			report($th);
			return $th;
		}
	}

	public  function getCardsByUser()
	{
		//preparar los campos para el api de pixelpay
		if (Auth::user()) {
			$pixelDomain = $this->getPixelDomain();
			$url = $pixelDomain . '/api/v2/tokenization/customer/' . Auth::user()->pixel_token;
			return $this->doPixelPayRequest($url);
		} else {
			return [
                'success' => false,
                'code' => null,
                'body'=> null,
            ];
		}
	}

	public function paidResponse($order, $settings)
	{
        dd('order response');
		$order->status = 'paid';
		$order->is_paid = true;
		$order->is_confirmed = true;
		$order->paid_at = Carbon::now();
		$order->save();

		$order->reduceInventory();
		$order->sendNotification();
		Cart::clear();

		return ['#checkout-container' => $this->renderPartial('@order_summary', [
			'order' => $order,
			'settings' => $settings,
			'thanks' => true
		])];
	}

	static function createOrder($data)
	{
		try {
            Flash::error("entra createOrder");
			$cart = Cart::load();
			$formData = json_decode($data->formData);
			$cartData = json_decode($data->cart);
			$user = json_decode($data->user);
			//dd($cartData->billing_address);

			if (count($cartData->items) < 1) {
				return [
					'success' => false,
					'data' => [],
					'message' => trans('pixel.shop::lang.messages.empty_cart')
				];
			}

			if ($user) {
				$cart->user = $user->id;

				$user->phone = $formData->customer_phone;
				$user->is_ship_same_bill = $formData->is_ship_same_bill == 'on' ? true : false;
				$user->shipping_address = $cartData->shipping_address;
				$user->billing_address =  $formData->is_ship_same_bill  == 'on' ? $cartData->shipping_address : $cartData->billing_address;
			}

			$cart->customer_name = $formData->customer_name;
			$cart->customer_address = $formData->customer_address;
			$cart->customer_email = $formData->customer_email;
			$cart->customer_phone = $formData->customer_phone;
            //dd($cart);
			$cart->shipping_address = $cartData->shipping_address;
			$cart->billing_address = $formData->is_ship_same_bill  == 'on' ? $cartData->shipping_address : $cartData->billing_address;
            //dd($formData->is_ship_same_bill  == 'on' ? $cartData->shipping_address : $cartData->billing_address);
			//dd($cartData->billing_address);
			$cart->notes = $cartData->notes;
			$cart->custom_fields = $cartData->custom_fields;
			$cart->items =  $cartData->items;
			$cart->gateway = $cartData->gateway;
			$cart->shipping_total = $cartData->shipping_total;
			$cart->subtotal = $cartData->subtotal;
			$cart->total = $cartData->total;
			$cart->tax_total = $cartData->tax_total;
			$cart->save();

			$order = $cart->createOrderFromCart();
			$order->status = 'await_pay';
			$order->gateway = 'pixelpay';
			$order->save();

			$cart->order = $order->id;
			$cart->save();

			$hash = [
				$order->id,
				GatewaysSettings::get('pixelpay_app'),
				GatewaysSettings::get('pixelpay_hash')
			];

			$hash = implode('|', $hash);
			$hash = md5($hash);

			return [
				'success' => true,
				'data' => $order,
				'message' => 'Orden creada con exito',
                'payment_hash' => $hash

			];
		} catch (Exception $error) {
			return [
				'success' => false,
				'data' => [],
				'message' => $error->getMessage()
			];
		}
	}



	static function saveCardTokenToUser($data)
	{
		try {
			$userID = $data->userID;
			$cardToken = $data->cardToken;
			$reference = $data->reference;

			$jsonData = json_encode(
				[
					"user_id" => $userID,
					"card_token" =>$cardToken,
					"card_reference"=> $reference,
				]
			);
			DB::table('system_settings')->insert(['item' => 'pixel_user_tokens', 'value' => $jsonData ]);

			return [
				'success' => true,
				'data' => [],
				'message' => "Token guardado correctamente"
			];
		} catch (Exception $error) {
			return [
				'success' => false,
				'data' => [],
				'message' => $error->getMessage()
			];
		}
	}

	static function getCardToken($data, $delete = false)
	{
		try {
			$userID = $data->userID;
			$reference = $data->reference;


			$tokens = DB::table('system_settings')->where('item' , 'pixel_user_tokens')
			->where('value->user_id',  $userID)
			->where('value->card_reference',  $reference);

			if(empty($tokens->first())){
				return [
					'success' => false,
					'data' => [],
					'message' => "Token no encontrado"
				];

			}

			if($delete){
				$tokens->delete();
				return [
					'success' => true,
					'data' => [],
					'message' => "Token eliminado correctamente"
				];
			}

			return [
				'success' => true,
				'data' => json_decode($tokens->first()->value),
				'message' => "Información obtenida con exito"
			];
		} catch (Exception $error) {
			return [
				'success' => false,
				'data' => [],
				'message' => $error->getMessage()
			];
		}
	}


	static function saveTokenToUser($data)
	{
		try {
			$userID = $data->userID;
			$token = $data->token;

			$user =  User::find($userID);
			if ($user) {
				$user->pixel_token = $token;
				$user->save();
			}

			return [
				'success' => true,
				'data' => [],
				'message' => "Token guardado correctamente"
			];
		} catch (Exception $error) {
			return [
				'success' => false,
				'data' => [],
				'message' => $error->getMessage()
			];
		}
	}
}
