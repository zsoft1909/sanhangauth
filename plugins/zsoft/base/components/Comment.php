<?php namespace Zsoft\Base\Components;

use Db;
use Mail;
use Input;
use Flash;
use Request;
use Validator;
use Exception;
use ValidationException;

use Cms\Classes\ComponentBase;
use Zsoft\Base\Models\Comments;
use Illuminate\Database\Eloquent\Model;

class Comment extends ComponentBase
{
    public  $comment;

    public  function componentDetails(){
        return[
            'name' => 'Comment',
            'description' => ' comment'
        ];
    }

    public function defineProperties(){
        return [];
    }

    public function onHandleComment(){
        if (Request::ajax()) {
            $data = input();
            if (!isset($data['module']) || !isset($data['moduleid'])) {
                Flash::error('Params required');
            }else{

                $rules = [
                    'name' => 'required|min:3|max:60',
                    'email' => 'required|email',
                    'content' => 'required',
                ];
                $names = [
                    'name' => 'Name',
                    'phone' => 'Email',
                ];

                if ($data['module'] == 'product') {
                    $rules['rating'] = 'required|numeric';
                    $names['rating'] = 'Rating';
                }

                $validation = Validator::make($data, $rules, trans('pixel.shop::validation'), $names);
                if ($validation->fails()) {
                    throw new ValidationException($validation);
                }

                $modelSave = Comments::create($data);
                if ($modelSave) {
                    Flash::success('Cảm ơn bạn đã gửi đánh giá. chúc bạn mua sắm vui vẻ!');
                }else{
                    Flash::error('Gửi thông tin thất bại. vui lòng thử lại');
                }
            }
        }
    }
}
