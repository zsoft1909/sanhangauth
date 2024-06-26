<?php

namespace Pixel\Shop;

use App;
use Yaml;
use File;
use Event;
use Backend;
use Validator;

use Pixel\Shop\Models\Item;
use Pixel\Shop\Models\Order;
use Pixel\Shop\Models\Brand;
use Pixel\Shop\Models\Category;

use System\Models\Parameter;
use System\Classes\PluginBase;

use Illuminate\Foundation\AliasLoader;
use October\Rain\Exception\ApplicationException;
use Backend\Classes\Controller as BackendController;

class Plugin extends PluginBase
{

	// DEPENDENCIES
	public $require = ['RainLab.User', 'RainLab.Location', 'Responsiv.Currency'];


	// PLUGIN DETAILS
	public function pluginDetails()
	{
		return [
			'name' => 'pixel.shop::lang.plugin.name',
			'description' => 'pixel.shop::lang.plugin.description',
			'author' => 'Pixel',
			'icon' => 'icon-shopping-basket'
		];
	}

	public function registerComponents()
	{
		return [
			'Pixel\Shop\Components\ProductList' => 'shopProductsList',
			'Pixel\Shop\Components\ProductDetails' => 'shopProductsDetails',
			'Pixel\Shop\Components\CartContainer' => 'cartContainer',
			'Pixel\Shop\Components\CartButton' => 'cartButton',
			'Pixel\Shop\Components\UserProfile' => 'userProfile',
		];
	}

	// REGISTER PERMISSIONS
	public function registerPermissions()
	{
		return [
			'pixel.shop.orders' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.access_orders'],
			'pixel.shop.items' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.access_items'],
			'pixel.shop.categories' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.access_categories'],
			'pixel.shop.brands' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.access_brands'],
			'pixel.shop.coupons' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.access_coupons'],
			'pixel.shop.gateways_settings' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.gateways_settings'],
			'pixel.shop.sales_settings' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.sales_settings'],
			'pixel.shop.shipping' => ['tab' => 'pixel.shop::lang.plugin.name', 'label' => 'pixel.shop::lang.plugin.access_coupons'],

		];
	}

	// REGISTER MENU NAVIGATION
	public function registerNavigation()
	{
		return [
			'shop' => [
				'label' => 'pixel.shop::lang.menu.shop',
				'url' => Backend::url('pixel/shop/index'),
				'icon' => 'icon-shopping-cart',
				'iconSvg' => 'plugins/pixel/shop/assets/icon.svg',
				'permissions' => ['pixel.shop.*'],
				'order' => 405,
				'sideMenu' => [
					'orders' => [
						'label' => 'pixel.shop::lang.menu.orders',
						'icon' => 'icon-shopping-basket',
						'url' => Backend::url('pixel/shop/orders'),
						'permissions' => ['pixel.shop.orders'],
						'counter'     => $this->ordersCounter(),
					],
					'items' => [
						'label' => 'pixel.shop::lang.menu.items',
						'icon' => 'icon-cubes',
						'url' => Backend::url('pixel/shop/items'),
						'permissions' => ['pixel.shop.items'],
						'counter'     => $this->productsStockCounter(),
					],
					'categories' => [
						'label' => 'pixel.shop::lang.menu.categories',
						'icon' => 'icon-tags',
						'url' => Backend::url('pixel/shop/categories'),
						'permissions' => ['pixel.shop.categories'],
					],
					'brands' => [
						'label' => 'pixel.shop::lang.menu.brands',
						'icon' => 'icon-copyright',
						'url' => Backend::url('pixel/shop/brands'),
						'permissions' => ['pixel.shop.brands'],
					],
					'coupons' => [
						'label' => 'pixel.shop::lang.menu.coupons',
						'icon' => 'icon-ticket',
						'url' => Backend::url('pixel/shop/coupons'),
						'permissions' => ['pixel.shop.coupons'],
					],

				]
			]
		];
	}

	// SETTINGS
	public function registerSettings()
	{
		return [
			'sales' => [
				'label'       => 'pixel.shop::lang.plugin.sales_settings',
				'description' => 'pixel.shop::lang.plugin.sales_settings_description',
				'icon'        => 'icon-dollar',
				'class'       => 'Pixel\Shop\Models\SalesSettings',
				'category'    => 'pixel.shop::lang.menu.shop',
				'order'       => 100,
				'keywords'    => 'crm customer relationship management invoice estimate',
				'permissions' => ['pixel.shop.sales_settings']
			],
			'gateways' => [
				'label'       => 'pixel.shop::lang.plugin.gateways_settings',
				'description' => 'pixel.shop::lang.plugin.gateways_settings_description',
				'icon'        => 'icon-credit-card',
				'class'       => 'Pixel\Shop\Models\GatewaysSettings',
				'category'    => 'pixel.shop::lang.menu.shop',
				'order'       => 101,
				'keywords'    => 'management gateways payment method',
				'permissions' => ['pixel.shop.gateways_settings']
			]
		];
	}

	// FORM WIDGETS
	public function registerFormWidgets()
	{
		return [
			'Pixel\Shop\FormWidgets\Currency' => [
				'label' => 'Currency',
				'code'  => 'shop-currency'
			],
			'Pixel\Shop\FormWidgets\Slider' => [
				'label' => 'Slider',
				'code'  => 'shop-slider'
			],
			'Pixel\Shop\FormWidgets\Selector' => [
				'label' => 'Selector',
				'code'  => 'shop-selector'
			],
			'Pixel\Shop\FormWidgets\Variants' => [
				'label' => 'Variants',
				'code'  => 'shop-variants'
			],
			'Pixel\Shop\FormWidgets\Rates' => [
				'label' => 'Rates',
				'code'  => 'shop-rates'
			],
			'Pixel\Shop\FormWidgets\Zones' => [
				'label' => 'Zone',
				'code'  => 'shop-zones'
			],
			'Pixel\Shop\FormWidgets\ZoneProfile' => [
				'label' => 'Zone Profile',
				'code'  => 'shop-zone-profile'
			],
		];
	}

	// MAIL TEMPLATES
	public function registerMailPartials()
	{
		return [
			'badge'  => 'pixel.shop::partials.badge',
			'items'  => 'pixel.shop::partials.items',
		];
	}

	public function registerMailTemplates()
	{
		return [
			'pixel.shop::mail.new_order',
			'pixel.shop::mail.order_awaitpay',
			'pixel.shop::mail.order_cancelled',
			'pixel.shop::mail.order_completed',
			'pixel.shop::mail.order_payed',
			'pixel.shop::mail.recovery_password'
		];
	}

	// RAINLAB PAGES MENU SUPPORT
	private function bootMenuItem()
	{
		Event::listen('pages.menuitem.listTypes', function () {
			return [
				'pixel-shop-categories' => 'All Shop Categories',
				'pixel-shop-brands' => 'All Shop Brands',
			];
		});

		Event::listen('pages.menuitem.getTypeInfo', function ($type) {
			if ($type == 'pixel-shop-categories')
				return Category::getMenuTypeInfo($type);

			if ($type == 'pixel-shop-brands')
				return Brand::getMenuTypeInfo($type);
		});

		Event::listen('pages.menuitem.resolveItem', function ($type, $item, $url, $theme) {
			if ($type == 'pixel-shop-categories')
				return Category::resolveMenuItem($item, $url, $theme);

			if ($type == 'pixel-shop-brands')
				return Brand::resolveMenuItem($item, $url, $theme);
		});
	}

	// EXTEND PLUGIN RAINLAB USER
	private function bootRainlabUserExtend()
	{
		if (class_exists("\RainLab\User\Models\User")) {

			Event::listen('backend.list.extendColumns', function ($widget) {
				if (!$widget->getController() instanceof \RainLab\Location\Controllers\Locations) {
					return;
				}

				if ($widget->model instanceof \RainLab\Location\Models\Country) {
					$widget->addColumns([
						'shipping_fee' => [
							'label' => 'pixel.shop::lang.fields.shipping_fee',
							'type' => 'currency'
						]
					]);
				}

				if ($widget->model instanceof \RainLab\Location\Models\State) {
					$widget->addColumns([
						'shipping_fee' => [
							'label' => 'pixel.shop::lang.fields.shipping_fee',
							'type' => 'currency'
						]
					]);
				}
			});

			// FILLABE ATTRIBUTE
			\RainLab\User\Models\User::extend(function ($model) {
				$model->addFillable([
					"billing_address",
					"shipping_address",
					"is_ship_same_bill"
				]);

				// $model->addJsonable([
				// 	// 'billing_address',
				// 	// 'shipping_address',
				// 	'father',
				// 	'mother',
				// 	'baby'
				// ]);

				$model->addCasts([
					'is_ship_same_bill' => 'boolean',
				]);
			});

			// EXTEND FIELDS
			\RainLab\User\Controllers\Users::extendFormFields(function ($widget) {
				if (!$widget->model instanceof \RainLab\User\Models\User)
					return;

				// $configFile = __DIR__ . '/models/user/fields.yaml';
				// $config = Yaml::parse(File::get($configFile));
				// $widget->addSecondaryTabFields($config);
				
				$configFileTab = __DIR__ . '/models/user/fields_tab.yaml';
				$configTab = Yaml::parse(File::get($configFileTab));
				if($widget->isNested === false){
					$widget->addTabFields($configTab);
				}
			});

			\RainLab\Location\Controllers\Locations::extendFormFields(function ($widget) {
				if ($widget->model instanceof \RainLab\Location\Models\Country) {
					$widget->addFields([
						'name' => [
							'label' => 'rainlab.location::lang.country.name',
							'span' => 'full'
						],
						'shipping_fee' => [
							'label' => 'pixel.shop::lang.fields.shipping_fee',
							'type' => 'shop-currency',
							'span' => 'auto',
							'comment' => 'pixel.shop::lang.messages.shipping_fee_country'
						]
					]);
				}

				if ($widget->model instanceof \RainLab\Location\Models\State) {
					$widget->addFields([
						'name' => [
							'label' => 'rainlab.location::lang.state.name',
							'span' => 'full',
							'comment' => 'rainlab.location::lang.state.name_comment'
						],
						'code' => [
							'label' => 'rainlab.location::lang.state.code',
							'span' => 'auto',
							'comment' => 'rainlab.location::lang.state.code_comment',
							'preset' => [
								'field' => 'name',
								'type' => 'slug',
							]
						],
						'shipping_fee' => [
							'label' => 'pixel.shop::lang.fields.shipping_fee',
							'type' => 'shop-currency',
							'span' => 'auto',
							'comment' => 'pixel.shop::lang.messages.shipping_fee_state'
						]
					]);
				}
			});

			// EXTEND RELATIONS
			\RainLab\User\Models\User::extend(function ($model) {
				$model->hasMany['orders'] = [
					'Pixel\Shop\Models\Order',
					'order' => 'created_at desc'
				];

				$model->hasMany['favorites'] = [
					'Pixel\Shop\Models\Favorite',
					'conditions' => 'is_favorite = 1'
				];
			});

			// EXTEND beforeCreate
			\RainLab\User\Models\User::extend(function ($model) {
			    $model->bindEvent('model.beforeCreate', function () use ($model) {
			    	$model->name = $model->surname . ' ' .$model->name;
			    	$model->surname = '';
			    	$dataArray = [];
			        if ( !empty($model->name) ) {
			            $dataArray[0]['name'] = $model->name;
			        }
			        if ( !empty($model->phone) ) {
			            $dataArray[0]['phone'] = $model->phone;
			        }

			        $model->father = $model->mother = $model->baby = $dataArray;
			    });
			});

		}
	}

	// EXTENDING VALIDATION TYPES
	private function bootValidatorExtend()
	{

		Validator::extend('ccn', function ($attribute, $value, $parameters, $validator) {
			$number = preg_replace('/\D/', '', $value);

			$number_length = strlen($number);
			$parity = $number_length % 2;

			$total = 0;
			for ($i = 0; $i < $number_length; $i++) {
				$digit = $number[$i];
				if ($i % 2 == $parity) {
					$digit *= 2;

					if ($digit > 9)
						$digit -= 9;
				}

				$total += $digit;
			}

			if ($total % 10 != 0)
				return false;

			return self::evaluatePAN($value);
		});

		Validator::extend('cvv', function ($attribute, $value, $parameters, $validator) {
			$data = $validator->getData();

			if (
				is_array($parameters)
				&& !array_key_exists($parameters[0], $data)
				&& !empty($data[$parameters[0]])
			)
				return;

			$type = self::evaluatePAN($data[$parameters[0]]);

			if (!$type)
				return;

			if ($type == 'amex')
				return preg_match("/^[0-9]{4}$/", $value);
			else
				return preg_match("/^[0-9]{3}$/", $value);
		});

		Validator::extend('ccexp', function ($attribute, $value, $parameters, $validator) {
			$part =  explode('/', $value);
			$new_date = $part[1] . '-' . $part[0];
			$dt =date_create_from_format('Y-m', $new_date);
			$format = $dt->format('Y-m');
			$new_format = explode('-', $format);
			$exp = intval($new_format[0] . $new_format[1]);

			$now = intval(date('Ym'));

			$limit = intval(date('Ym', strtotime('+20 years')));

			if ($exp >= $now && $exp <= $limit)
				return true;

			return;
		});

		Validator::extend('money', function ($attribute, $value, $parameters, $validator) {
			return preg_match("/^\d*(\.\d{1,2})?$/", $value);
		});

		Validator::extend('amount', function ($attribute, $value, $parameters, $validator) {
			if ($value < 1)
				return;

			return preg_match("/^\d*(\.\d{1,2})?$/", $value);
		});
	}

	// ON BOOT
	public function boot()
	{
		// ALIAS
		$alias = AliasLoader::getInstance();
		$alias->alias('CurrencyShop', 'Pixel\Shop\Classes\Currency');

		// BOOT ELEMENTS
		$this->bootMenuItem();
		$this->bootValidatorExtend();
		$this->bootRainlabUserExtend();

		// PLUGIN ASSETS
		BackendController::extend(function ($controller) {
			$controller->addCss('/plugins/pixel/shop/assets/main.css');
		});

		// OMNYPAY BOOT
		App::register('Ignited\LaravelOmnipay\LaravelOmnipayServiceProvider');
		$alias->alias('Omnipay', 'Ignited\LaravelOmnipay\Facades\OmnipayFacade');
	}

	private function productsStockCounter()
	{
		$low_stock = Item::getLowStockProducts();
		return ($low_stock) ? $low_stock : null;
	}

	private function ordersCounter()
	{
		$orders = Order::where("is_paid", true)->where('status', '<>', 'completed')->count();
		return ($orders) ? $orders : null;
	}

	public static function evaluatePAN($value)
	{
		$cards = array(
			"visa" => "(4\d{12}(?:\d{3})?)",
			"amex" => "(3[47]\d{13})",
			"maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
			"mastercard" => "(5[1-5]\d{14})",
			"jcb" => "(35[2-8][89]\d\d\d{10})",
			"solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
			"switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)",
		);

		$names = array(
			"visa",
			"amex",
			"maestro",
			"mastercard",
			"jcb",
			"solo",
			"switch"
		);

		$matches = array();
		$pattern = "#^(?:" . implode("|", $cards) . ")$#";
		$result = preg_match($pattern, str_replace(" ", "", $value), $matches);

		return ($result > 0) ? $names[sizeof($matches) - 2] : false;
	}
}
