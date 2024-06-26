<?php namespace Zsoft\Base\Components;

use Db;
use Mail;
use Input;
use Flash;
use Request;
use Cms\Classes\ComponentBase;
use Zsoft\Base\Models\Setting as SettingModel;
use Zsoft\Base\Models\Navigations;
use Illuminate\Database\Eloquent\Model;
use Zsoft\Base\Models\GameLogs;

class Setting extends ComponentBase
{
    public  $setting;

    public  function componentDetails(){
        return[
            'name' => 'Setting',
            'description' => ' Cài đặt'
        ];
    }

    public function defineProperties(){
        return [];
    }

    public  function onRun(){
        $this->page['primaryMenu'] = Navigations::where('parent_id', 0)->where('position', 1)->where('status', 1)
            ->orderby('nest_left', 'asc')->get()->all();
        $this->page['footerMenu'] = Navigations::where('parent_id', 0)->where('position', 2)->where('status', 1)
            ->orderby('nest_left', 'asc')->get()->all();
        $this->page['setting'] = SettingModel::find(1);
    }

    public function onHandleRegister(){
        $model = new \Zsoft\Base\Models\Contact();
        $data = input();

        if (!isset($data['phone']) || empty($data['phone'])) {
            Flash::error('Số điện thoại không được để trống');
        }else{
            $model->type  = input('type');
            $model->phone = input('phone');
            if ($model->save()) {
                Flash::success('Thông tin đăng ký của bạn đã được gửi đi. Chúng tôi sẽ liên hệ lại với bạn trong thời gian sớm nhất');
            }else{
                Flash::error('Gửi thông tin thất bại. vui lòng thử lại');
            }
        }
    }
}
