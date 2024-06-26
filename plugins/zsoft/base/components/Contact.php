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
use Zsoft\Base\Models\Contact as ContactModel;
use Illuminate\Database\Eloquent\Model;
use ZSoft\Horoscope\Laso;

class Contact extends ComponentBase
{
    public  $setting;

    public  function componentDetails(){
        return[
            'name' => 'Contact',
            'description' => ' Liên hệ với chúng tôi'
        ];
    }

    public function defineProperties(){
        return [];
    }

    public function onHandleContact(){
        if (Request::ajax()) {
            $data = input();
            if (!isset($data['type'])) {
                Flash::error('Params required');
            }else{

                $rules = [
                    'fullname' => 'required|min:3|max:60',
                    'phone'    => 'required|min:8',
                    'email'    => 'required|email',
                ];

                $names = [
                    'fullname' => 'Name',
                    'phone' => 'Phone',
                    'email' => 'Email'
                ];

                if ($data['type'] == 2) {
                    unset($rules['fullname'], $rules['phone']);
                    unset($names['fullname'], $names['phone']);
                }

                $validation = Validator::make($data, $rules, trans('pixel.shop::validation'), $names);
                if ($validation->fails()) {
                    throw new ValidationException($validation);
                }

                $model = ContactModel::create($data);
                if ($model->save()) {
                    Flash::success('Thông tin của bạn đã được gửi đi. Chúc bạn mua sắm vui vẻ!');
                }else{
                    Flash::error('Gửi thông tin thất bại. vui lòng thử lại');
                }
            }
        }
    }
}