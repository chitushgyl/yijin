<?php
/**
 * Created by PhpStorm.
 * User: gu
 * Date: 2020/4/9
 * Time: 17:52
 */
use Illuminate\Http\Request;
use App\Http\Requests;

/**处理优惠券信息**/
function manage_coupon($coupon_info,$type,$user_id){
    foreach($coupon_info as $k=>$v){
		
		//dd($coupon_info);
		//dd($abcc);
        //使用的条件
        switch($v->use_type){
            //使用类型:全场券all，单品券good，品类券classify，门店券shop，活动券activity',
            case 'all':
                $v->use_type_show='全场券';
                break;
            case 'good':
                $v->use_type_show='单品券';
                break;
            default:
                $v->use_type_show=null;
                break;
        }
        //使用的条件
        switch($v->use_self_lifting_flag){
            //使用类型:全场券all，单品券good，品类券classify，门店券shop，活动券activity',
            case 'Y':
                $v->use_self_lifting_flag='自提可用';
                break;
            case 'N':
                $v->use_self_lifting_flag='自提不可用';
                break;
            default:
                $v->use_self_lifting_flag=null;
                break;
        }

        //优惠的显示情况
        switch($v->range_type){
            case 'discount':
                //折扣，满多少钱
                $v->range=$v->range*10;
                $v->unitDesc='折';
                $v->range_condition='满￥'.number_format($v->range_condition/100, 2).'可用';

                break;
            case 'reduce':
                //满多少金额
                $v->unitDesc='元';
                $v->range_condition='满￥'.number_format($v->range_condition/100, 2).'可用';
                break;

            case 'reducecount':
                //满多少金额
                $v->unitDesc='元';
                $v->range_condition='满'.$v->range_condition.'件可用';
                break;
        }

        $v->front_name='http://'.$v->front_name;

        $v->company_image_url=img_for($v->company_image_url,'one');

    }
    return $coupon_info;
}




?>