<?php namespace Zsoft\Base\Models;

use Model;

/**
 * Model
 */
class Advertise extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zsoft_base_advertise';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
