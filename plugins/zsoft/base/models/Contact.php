<?php namespace Zsoft\Base\Models;

use Model;

/**
 * Model
 */
class Contact extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zsoft_base_contact';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = ['type', 'subject', 'fullname', 'phone', 'email', 'content'];
}
