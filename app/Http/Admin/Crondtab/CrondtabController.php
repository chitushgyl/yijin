<?php
namespace App\Http\Api\Crondtab;


use App\Http\Controllers\Controller;
use App\Models\Tms\TmsOrder;
use App\Models\Tms\TmsOrderCost;
use App\Models\Tms\TmsOrderDispatch;
use App\Models\Tms\TmsPayment;
use App\Models\User\UserCapital;
use App\Models\User\UserReward;
use App\Models\User\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use EasyWeChat\Foundation\Application;
use WxPayApi as WxPayQ;

class CrondtabController extends Controller {


    /**
     *查询当月有无违规 违章 事故 /api/crondtab/order_done
     */
    public function order_done(){
        $now_time  = time();
        $where = [
            ['order_status','=',5]
        ];
        $select = ['self_id','order_status','total_money','pay_type','group_code','group_name','total_user_id','order_type','create_time'];
        $order_list = UserReward::where($where)->select($select)->get();
//        dump($order_list->toArray());
        foreach ($order_list as $k => $v) {
            if ($now_time - strtotime($v->create_time) >= 6 * 24 * 3600) {


            }
        }
    }






































}
