<?php
namespace App\Http\Admin\Crondtab;


use App\Http\Controllers\Controller;
use App\Models\Tms\AwardRemind;
use App\Models\Tms\TmsPayment;
use App\Models\User\UserCapital;
use App\Models\User\UserReward;
use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;


class CrondtabController extends Controller {


    /**
     *查询当月有无违规 违章 事故 /admin/crondtab/userReword
     */
    public function userReword(Request $request){
        $now_time  = date('Y-m',time());
        $month_start = date('Y-m-01',strtotime(date('Y-m-d')));
        $month_end = date('Y-m-d',strtotime("$month_start+1 month-1 day"));
        $where = [
            ['delete_flag','=','Y'],
            ['award_flag','=','N'],
            ['cash_back','=',$now_time],
        ];
        $user_list = AwardRemind::where($where)->get();
        foreach ($user_list as $k => $v) {
            $where1 = [
                ['type','!=','reward'],
                ['delete_flag','=','Y'],
                ['event_time','>=',$month_start],
                ['event_time','<=',$month_end],
                ['user_id','=',$v->user_id],
            ];
            $where2 = [
                ['type','!=','reward'],
                ['delete_flag','=','Y'],
                ['event_time','>=',$month_start],
                ['event_time','<=',$month_end],
                ['escort','=',$v->user_id],
            ];
            $select = ['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
                'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
                ,'cash_back','cash_flag','type','user_name'];
            $order_list = UserReward::where($where1)->orWhere($where2)->select($select)->get();
            if(count(get_object_vars($order_list))>0){
                $time = date('Y-m', strtotime('+6 month', strtotime($month_start)));
                $update['cash_back']          = $time;
                $update['update_time']        = date('Y-m-d H:i:s',time());
                AwardRemind::where('user_id',$v->user_id)->update($update);
            }

        }
    }

    /**
     * 自动更新二次维护时间
     * */
    public function updateDiplasic(Request $request){
        $now_time = date('Y-m-d H:i:s',time());
    }






































}
