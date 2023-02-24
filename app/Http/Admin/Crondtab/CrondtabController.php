<?php
namespace App\Http\Admin\Crondtab;


use App\Http\Controllers\Controller;
use App\Models\Tms\TmsPayment;
use App\Models\User\UserCapital;
use App\Models\User\UserReward;
use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;


class CrondtabController extends Controller {


    /**
     *查询当月有无违规 违章 事故 /api/crondtab/userReword
     */
    public function userReword(Request $request){
        $now_time  = time();

        $month_start = date('Y-m-01',strtotime(date('Y-m-d')));
        $month_end = date('Y-m-d',strtotime("$month_start+1 month-1 day"));
        $where = [
            ['type','!=','reward'],
            ['delete_flag','=','Y'],
            ['event_time','>=',$month_start],
            ['event_time','<=',$month_end],
        ];
        dump($month_start,$month_end);
        $select = ['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name'];
        $order_list = UserReward::where($where)->select($select)->get();

        dd($order_list->toArray());

        foreach ($order_list as $k => $v) {

        }
    }






































}
