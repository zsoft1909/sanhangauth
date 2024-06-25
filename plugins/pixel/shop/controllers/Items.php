<?php namespace Pixel\Shop\Controllers;

use Backend\Classes\Controller;
use Pixel\Shop\Models\Item as Products;
use BackendMenu;

class Items extends Controller
{
    public $implement = [
    	'Backend\Behaviors\ListController',
    	'Backend\Behaviors\FormController'
    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $bodyClass = 'compact-container';

    public $requiredPermissions = [
        'pixel.shop.items' 
    ];

    public function __construct(){
        parent::__construct();
        BackendMenu::setContext('Pixel.Shop', 'shop', 'items');

        $this->addJs('/plugins/pixel/shop/assets/js/items.js');
    }

    public function onShowVariantCombo(){
        $eleRespone = input('eleRespone');
        if(!$product = Products::find(input('itemid')))
            Flash::error('Không tìm thấy sản phẩm');

        return $this->makePartial(
            'product-variant', [ 
                'product' => $product, 
                'eleRespone' => $eleRespone
            ]
        );
    }

    public function onGetOptions(){
        $itemid = input('itemid');
        $search = input('term');

        $query = Products::select()->where('is_visible', 1);

        if ($itemid) {
            $query->where('id','<>', $itemid);
        }

        if($search){
            $query->where(function($query) use ($search) {
                $words = explode(' ', $search);

                foreach ($words as $word){
                    $query->orWhere('name', 'LIKE', '%'.$word.'%');
                }
            });
        }

        $products = $query->get();
        $results = array();
        foreach ($products as $key => $product) {
            $results[] = [
                'id' => $product->id,
                'text' => $product->name
            ];
        }
        return [
            'results' => $results, 
            // 'pagination' => [
            //     'more' => true
            // ]
        ];
    }
}