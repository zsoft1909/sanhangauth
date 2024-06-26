<?php namespace Pixel\Shop\Components;

use Response;
use Cms\Classes\Page;
use Pixel\Shop\Models\Item;
use Pixel\Shop\Classes\Cart;
use Pixel\Shop\Models\SalesSettings;
use Pixel\Shop\Components\CartTrait;
use Event;

use Cms\Classes\ComponentBase;

class ProductDetails extends ComponentBase{
	use CartTrait;

	public function componentDetails()
	{
		return [
			'name'        => 'Product Detail',
			'description' => 'Display detail of product'
		];
	}

	public function defineProperties(){
		return [
			'slug' => [
				'title'       => 'URL Slug',
				'description' => 'URL Slug',
				'default'     => '{{ :slug }}',
				'type'        => 'string'
			],
			'productsPage' => [
				'title'       => 'Products page',
				'description' => 'Product list page',
				'type'        => 'dropdown',
				'default'     => 'products',
			],
		];
	}

	public function getProductPageOptions(){
		return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
	}

	public function onRun(){
        $this->prepareLang();

    	$slug = $this->property('slug');
    	$product = Item::where('slug', $slug)->where("is_visible", 1)->first();

    	if (!empty($product->tags)) {
    		$product->tags = explode(',', $product->tags);
    	}

    	if (\Request::ajax()) {
    		$display = \Input::get('display');
    		return $this->renderPartial('@product-preview', ['product' => $product]);
    	}

    	// $this->addCss('/plugins/pixel/shop/assets/css/product.css');

		if ($product == null){
			return redirect($this->property('productsPage'));
		}else{
			$product->increment('views_count');
		}

		$category = $product->categories()->orderBy('id', 'DESC')->first();
		$categoryRoot = $category->getRoot();

        $this->page['category'] = $category;
        $this->page['product'] = $product;
        $this->page['shopSetting'] = SalesSettings::instance();
        $this->page['relatedProducts'] = $this->getRelatedProducts($product);
        // print_r($this->page['relatedProducts']->toArray());die;

		$product->setUrl($this->page->code, $this->controller);

			/**
			 * Quantity Event
			 */
			//$newQuantity = Event::fire('pixel.shop.getQuantityProperty', [$this, $product]);
			//$product->quantity = !empty($newQuantity) > 0 ? $newQuantity[0]['quantity'] : $product->quantity;

        if (isset($product)) {
            $this->page->meta_title = $product->name;
            $this->page->meta_description = $product->short_description;
            $this->page->meta_keywords = $product->meta_keywords;
        }
    }

    protected function prepareLang(){
        $lang = \Config::get('app.locale', 'en');

        if(\System\Models\PluginVersion::where('code', 'RainLab.Translate')->where('is_disabled', 0)->first()){
            $translator = \RainLab\Translate\Classes\Translator::instance();
            $activeLocale = $translator->getLocale();
            $lang = $activeLocale;
        }

        if(!empty(post('lang'))){
			$lang = post('lang');
		}

        \App::setLocale($lang);
    }

    public function getRelatedProducts($product){
		// $list = $product->categories->lists('id');
		// whereHas('categories', function($query) use ($list){
		// 	$query->whereIn('id', $list);
		// })->

		$products = Item::where('id','<>', $product->id)
			->where('is_visible', 1)
			// ->orderBy('views_count', 'desc')
			->orderBy('sales_count', 'desc')
			->take(4)
			->get();

		foreach ($products as $product) {
			$product->setUrl($this->page->code, $this->controller);

			/**
			 * Quantity Event
			 */
			// $newQuantity = Event::fire('pixel.shop.getQuantityProperty', [$this, $product]);
			// $product->quantity = !empty($newQuantity) > 0 ? $newQuantity[0]['quantity'] : $product->quantity;
		}

		return $products;
	}
}
