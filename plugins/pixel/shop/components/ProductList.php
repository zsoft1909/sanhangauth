<?php namespace Pixel\Shop\Components;

use Flash;
use Lang;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Event;
use Input;
use Redirect;
use Pixel\Shop\Models\Brand;
use Pixel\Shop\Models\Item;
use Pixel\Shop\Models\Category;
use Pixel\Shop\Models\Favorite;
use Pixel\Shop\Models\SalesSettings;
use Pixel\Shop\Components\CartTrait;

class ProductList extends ComponentBase
{
    use CartTrait;

    public $products = array();
	public $settings = array();

    public function componentDetails()
    {
        return [
            'name'        => 'Products',
			'description' => 'Display list of products'
        ];
    }

    public function defineProperties()
    {
        return [
        	'categoryFilter' => [
				'title'       => 'Category Filter',
				'description' => 'Look up the blog category using the supplied slug value. This property is used by the default component partial for marking the currently active category.',
				'type'        => 'dropdown',
				'default'     => '{{ :slug }}',
			],
			'scope' => [
				'title'       => 'Scope by',
				'default'     => 'showItems',
				'type'        => 'dropdown',
				'options'     => [
					'showItems' => 'No scope (Show All)',
					'onSale' => 'On Sale Only',
				]
			],
			'limitType' => [
				'title'       => 'Paginate or only Take',
				'default'     => 'paginate',
				'type'        => 'dropdown',
				'options' => [
					'paginate'	=> 'Paginate Items',
					'take'		=> 'Only Take a limit'
				]
			],
			'take' => [
				'title'       => 'Limit of products',
				'description' => 'Take X products only or paginate',
				'default'     => '20',
				'type'        => 'string',
				'validationPattern' => '^[0-9]+$',
				'validationMessage' => 'The Max Items property can contain only numeric symbols'
			],
			'orderBy' => [
				'title'       => 'Order By',
				'description' => 'Order By',
				'default'     => 'name',
				'type'        => 'dropdown',
				'options'     => [
					'name' => 'Name',
					'price' => 'Price with Tax',
					'is_on_sale' => 'On Sale',
					'id' => 'ID',
					'code' => 'Code or SKU',
					'quantity' => 'Quantity',
					'updated_at' => 'Updated At',
					'created_at' => 'Created At',
					'sales_count' => 'Sales count',
					'views_count' => 'Views count'
				]
			],
			'sort' => [
				'title'       => 'Sort',
				'description' => 'Sort ASC or DESC',
				'default'     => 'asc',
				'type'        => 'dropdown',
				'options'     => ['asc'=>'Ascending', 'desc'=>'Descending']
			],
			'productPage' => [
				'title'       => 'Product page',
				'description' => 'Product detail page',
				'type'        => 'dropdown',
				'default'     => 'product',
			],
			'categoryPage' => [
				'title'       => 'Products page by category',
				'type'        => 'dropdown',
				'default'     => 'products',
			],
			'showCategoriesFilter' => [
				'title'		  => 'Show categories filter',
				'type'		  => 'checkbox',
				'default'     => false,
			],
			'showSearchBar' => [
				'title'		  => 'Show search bar',
				'type'		  => 'checkbox',
				'default'     => false,
			],
			'showQuickAdd' => [
				'title'		  => 'Quick "add to cart"',
				'type'		  => 'checkbox',
				'default'     => true,
            ],
            'typeCategoriesFilter' => [
				'title'       => 'Categories Filter Type',
				'default'     => 'dropdown',
				'type'        => 'dropdown',
				'options'     => ['dropdown'=>'Dropdown', 'buttons'=>'Buttons Group']
			],
        ];
    }

    public function onRun()
	{
        $this->prepareLang();

		// $this->addCss('/plugins/pixel/shop/assets/css/products.css');
        $category = $this->loadCategory();
        if (empty($category)){
			return Redirect::to('/404');
		}

		$this->page['activeCategory'] = $category;
		$this->products = $this->page['products'] = $this->loadProducts($category);
		// $this->settings = $this->page['shopSetting'] = SalesSettings::instance();

		$this->page['brands'] = Brand::get();
		$this->page['categoryAll'] = Category::where('parent_id', 0)->orderBy('nest_left', 'asc')->get();
		$this->page['productsRecent'] = Item::where('is_visible', 1)->orderBy('id', 'desc')->take(3)->get();
		$this->page['productsViews'] = Item::where('is_visible', 1)->orderBy('views_count', 'desc')->take(3)->get();
		$this->page['productsPopular'] = Item::where('is_visible', 1)->where('is_highlight', 1)->orderBy('id', 'desc')->take(3)->get();

        $this->page['showCategoriesFilter'] = $this->property('showCategoriesFilter');
		$this->page['showSearchBar'] = $this->property('showSearchBar');
		$this->page['showQuickAdd'] = $this->property('showQuickAdd');
        $this->page['typeCategoriesFilter'] = $this->property('typeCategoriesFilter');
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

    // LOAD MODELS
    protected function loadCategory(){
		if (!$categoryId = $this->property('categoryFilter')){
			return null;
		}

		if (!$category = Category::whereSlug($categoryId)->first()){
			return null;
		}

		return $category;
	}

	protected function getCategoryChildProducts($category){
		$categoriesChildProduct = [];
		$query = Item::select()->ShowItems()->orderBy('created_at', 'asc')->take(8);
		foreach ($category->getChildren() as $subcategory){
			$queryClone = clone $query;
			$categoriesChildProduct[$subcategory->id] = $subcategory;
	    	$categoriesChildProduct[$subcategory->id]['products'] = $queryClone->categories($subcategory)->get();
	    }

	    return $categoriesChildProduct;
	}

	protected function loadProducts($category)
	{
		$page = $this->property('productPage');
		$scope = $this->property('scope');
		$take = $this->property('take');

		$orderBy = Input::get('order');
		if (empty($orderBy)) {
			$orderBy = $this->property('orderBy');
		}
		$orderSort = Input::get('sort');
		if (empty($orderSort)) {
			$orderSort = $this->property('sort');
		}
		
	    $products = null;

	    if (isset($category)) {
            $this->page->meta_title = $category->name;
            $this->page->meta_description = $category->meta_description;
            $this->page->meta_keywords = $category->meta_keywords;

            // $categoryRoot = $category->getRoot();
            // if($categoryRoot->id == 1)
            // 	$this->page['color_category'] = 'color_blue';
        	
        	$this->page['orderBy'] = $orderBy;
        	$this->page['sortOrder'] = $orderSort;
			$this->page['categoryList'] = $this->getCategoryList();
			// if ($category->getChildren()->count() > 0) {
			// 	$this->page['categoryList'] = $this->getCategoryChildProducts($category);
			// 	return $products;
			// }
		}
		$query = Item::select();

		$search = input('search');
		if($search){
			$this->page['search_keyword'] = '<font>'.$search.'</font>';
			$query->where(function($query) use ($search) {
				$words = explode(' ', $search);

				foreach ($words as $word){
					$query->orWhere('name', 'LIKE', '%'.$word.'%');
					$query->orWhere('tags', 'LIKE', '%'.$word.'%');
				}
			});
		}

		if($scope){
			$query->$scope();
		}

		if($category){
			$query->categories($category);
		}

		if($orderBy){
			$query->orderBy($orderBy, $orderSort);
		}

		if($this->property('limitType') == 'take'){
			$products = $query->take($take)->get();
		}
		else{
			$products = $query->paginate($take);
		}

		$products->each(function($product) use ($page) {
			$product->setUrl($page, $this->controller);

			/**
			 * Quantity Event
			 */
			//$newQuantity = Event::fire('pixel.shop.getQuantityProperty', [$this, $product]);
			//$product->quantity = !empty($newQuantity) > 0 ? $newQuantity[0]['quantity'] : $product->quantity;
        });

		return $products;
	}

    // OPTIONS
    public function getProductPageOptions(){
		return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
	}

	public function getCategoryPageOptions(){
		return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
	}

	public function getCategoryFilterOptions(){
		$list = Category::lists('name', 'slug');
		array_unshift($list, trans('pixel.shop::lang.components.pl_all_cats'));
		return $list;
	}


	// METHODS
	public function getCategoryList(){
		if(!$param = $this->paramName('categoryFilter')){
			return;
		}

		$categories = Category::all();
		$page = $this->property('categoryPage');
		$list = array();

		// $empty = new Category();
		// $empty->name = trans('pixel.shop::lang.components.pl_all_cats');
		// $empty->setUrl($page, $this->controller, $param);

		// $list[] = $empty;

		$categories->each(function($item) use ($page, $param, &$list) {
			$item->setUrl($page, $this->controller, $param);

			if($item->items->count() > 0){
				$list[] = $item;
			}
		});

		return $list;
	}

	public function onSetFavorite(){
        $this->prepareLang();

		$item_id = post('id');

		if (class_exists("\RainLab\User\Models\User")){
			$user = \RainLab\User\Facades\Auth::getUser();
		}else{
			Flash::error('The plugin RainLab.User not found, please install first');
			return;
		}

		if($user){
			if($item_id){
				if($fav = Favorite::where('user_id', $user->id)->where('item_id', $item_id)->first()){
					$fav->is_favorite = !$fav->is_favorite;
					$fav->save();
				}else{
					$fav = Favorite::create([
						'user_id' => $user->id,
						'item_id' => $item_id,
						'is_favorite' => 1
					]);
				}

				$item = Item::find($item_id);

					return ['#favorite-product-'.$item_id => $this->renderPartial('@favorite', [
						'userLogged' => $user,
						'product' => $item
					])];
			}
		}else{
			Flash::error(trans('pixel.shop::lang.components.pl_please_login'));
			return;
		}
	}
}
