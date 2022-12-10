<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/18
 * Time: 14:43
 */
namespace App\Tools;
use App\Models\User\UserCart;

class CartNumber {

    public function cart_number($user_info,$group_code){
        $now_time=date('Y-m-d H:i:s',time());
        if($group_code == config('page.platform.group_code')){
            $where=[
                ['total_user_id','=',$user_info->total_user_id],
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];
        }else{
            $where=[
                ['group_code','=',$group_code],
                ['total_user_id','=',$user_info->total_user_id],
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];

        }
        $where_erp_shop_goods_sku=[
            ['delete_flag','=','Y'],
            ['good_status','=','Y'],
            ['sell_start_time','<',$now_time],
            ['sell_end_time','>',$now_time],
        ];
        $where_erp_shop_goods=[
            ['sell_start_time','<',$now_time],
            ['sell_end_time','>',$now_time],
            ['delete_flag','=','Y'],
            ['good_status','=','Y'],
        ];
        $where_group=[
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
        ];

        $number=UserCart::wherehas('erpShopGoodsSku',function($query)use($where_erp_shop_goods_sku){
            $query->where($where_erp_shop_goods_sku);
        })->wherehas('erpShopGoods',function($query)use($where_erp_shop_goods){
            $query->where($where_erp_shop_goods);
        })->wherehas('systemGroup',function($query)use($where_group){
            $query->where($where_group);
        })->where($where)->sum('good_number');

        return $number;


    }

}