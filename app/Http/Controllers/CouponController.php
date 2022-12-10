<?php
namespace App\Http\Controllers;
class CouponController extends Controller{
    /***    从shop_coupon  做一个转化
     */
    public function shop_coupon($info){
        //对单个数据进行转化
        /** 读取配置文件信息**/
        $get_way        =config('shop.get_way');
        $get_ways       =array_column($get_way,'name','key');

        $coupon_type    =config('shop.coupon_type');
        $coupon_types   =array_column($coupon_type,'name','key');

        $coupon_state   =config('shop.coupon_state');
        $coupon_states  =array_column($coupon_state,'name','key');

        //领取路径
        $infos['get_way_show']=$get_ways[$info->get_way]??null;
        $infos['get_redeem_code']=number_format($info->get_redeem_code/100,2);

        switch($info->range_type){
            case 'reduce':
                $infos['range_type_show']='满'.number_format($info->range_condition/100, 2).'元减'.number_format($info->range/100, 2).'元';
                break;

            case 'discount':
                $abccc=$info->range/10;
                $infos['range_type_show']='满'.number_format($info->range_condition/100, 2).'元打'.$abccc.'折';
                break;

            case 'reducecount':

                $infos['range_type_show']='满'.$info->range_condition.'件减'.number_format($info->range/100, 2).'元';
                break;

        }

        switch($info->time_type){
            case 'dynamic':
                if($info->time_start_day>0){
                    $infos['time_type_show']='动态时间：领取后'.$info->time_start_day.'天生效，有效期'.$info->time_end_day.'天';
                }else{
                    $infos['time_type_show']='动态时间：领取后立即生效，有效期'.$info->time_end_day.'天';
                }
                break;

            case 'assign':
                $infos['time_type_show']=$info->time_start.'到'.$info->time_end.'有效';
                break;
        }

        //领取状态
        $infos['coupon_state_show']=$coupon_states[$info->coupon_status]??null;

        //领取状态
        $infos['use_type_show']=$coupon_types[$info->use_type]??null;

        return $infos;

    }






}
