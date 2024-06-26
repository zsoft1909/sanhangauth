<?php namespace Zsoft\Base\Models;

use Model;

/**
 * Model
 */
class Slider extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zsoft_base_slider';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name'   => 'required',
        'target' => 'required',
        'image'  => 'required'
    ];
}
