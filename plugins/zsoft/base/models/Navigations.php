<?php namespace Zsoft\Base\Models;

use Model;
use BackendAuth;

/**
 * Model
 */
class Navigations extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zsoft_base_navigations';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required',
        'target' => 'required'
    ];

    public $belongsTo = [
        'user' => ['Backend\Models\User']
    ];

    public function beforeSave(){
        if (empty($this->user)) {
            $user = BackendAuth::getUser();
            if (!is_null($user)) {
                $this->user = $user->id;
            }
        }
    }

    public function afterDelete(){
        // $this->menus()->detach();
    }

    public function listParentMenuItem($fieldName, $value, $formData){
        $options = ['[-- Menu cha --]'];

        $items = self::where('parent_id', 0)->get()->all();
        foreach ($items as $item) {
            $options[$item->id] = $item->name;
        }

        return $options;
    }
}
