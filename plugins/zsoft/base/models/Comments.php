<?php namespace Zsoft\Base\Models;

use Model;

/**
 * Model
 */
class Comments extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zsoft_base_comments';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = ['module', 'moduleid', 'name', 'email', 'website', 'rating', 'content'];

    public function scopeIsBlog($query){
        return $query->where('status', 1)->where('module', '=', 'blog');
    }

    public function scopeIsProduct($query){
        return $query->where('status', 1)->where('module', '=', 'product');
    }
}
