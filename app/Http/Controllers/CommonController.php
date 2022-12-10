<?php
namespace App\Http\Controllers;
//use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
class CommonController extends Controller{

//    推送
    public function send_push_message($data,$city){

//        if ($order_type == 12){
//            $center_list = '有'. $order->startcity.'的市内整车订单';
//        }else{
//            $center_list = '有从'. $order->startcity.'发往'.$order->endcity.'的整车订单';
//        }
//        $data = array('title' => "赤途承运端",'content' => $center_list , 'payload' => "订单信息");
        include_once base_path( '/vendor/getui/GeTui.php');
        $gt = new \getui\GeTui();
        $a =  $gt->pushMessageToApp($data, $city);
        return $a;
    }

}
?>
