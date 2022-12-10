<?php
/**
 * Created by PhpStorm.
 * User: gu
 * Date: 2020/4/9
 * Time: 17:52
 */

/**用户已经有了几张该券**/
function calculate($group_code,$good_infos,$user_activity,$user_coupon,$pay,$all_coupon_id,$count=0){
	$count=$pay['good_count'];
	//dump($count);
    /**
    $pay['goods_total_money']='0';						//商品总金额
    $pay['discounts_single_total']='0';                 //单品券优惠总计
    $pay['discounts_all_total']='0';                    //全场券优惠总计
    $pay['discounts_activity_total']='0';               //活动优惠总计
    $pay['discounts_total_money']='0';                  //总计优惠
    $pay['serve_total_money']='0';                      //服务总费用
    $pay['kehu_yinfu']='0';
    $pay['good_count']='0';                         //商品总数量*/
    /**活动优惠计算开始 */

    if($user_activity){
        //dd(111);
    }
    /**活动优惠计算结束*/
    //初始化一下单品和全场优惠券
    $user_all_coupon=null;
    $user_good_coupon=null;
    if($user_coupon){
        //做一个单品优惠券，和全场优惠券的分割
        foreach ($user_coupon as $k => $v){
            switch($v->use_type){
                case 'good':
                    $user_good_coupon[$k]=$v;
					$user_good_coupon[$k]->can_use='Y';
                    break;
                case 'all':
                    $user_all_coupon[]=$v;
                    break;

            }
        }
    }


//dd(1111);



    /**单品优惠券优惠计算开始*/
    if($user_good_coupon){
		//dd($pay);
		//dd($user_good_coupon);
        //第一步，判断是不是已经有优惠了
        foreach ($good_infos as $k => $v){
				//$v->discounts_single='0';
                foreach ($user_good_coupon as $kk => $vv){
                        if($v->can_count_discount == 'N' && $v->good_id == $vv->all_type_id && $vv->can_use=='Y'){
                            //说明匹配上了单品优惠券，这个时候需要判断是不是满足优惠的条件，
                            /**注意，如果有2个一样的单品优惠券，会不会出现重复使用的情况*/
                            switch($vv->range_type){
                                case 'reduce':
                                    //满减，指的是商品的总金额是不是比优惠券要求的高
                                    if($v->total_price >= $vv->range_condition){
                                        //如果大于了，说明是可以优惠的
                                        $v->discounts_single=$vv->range;            //优惠了这么多分
                                        $v->coupon_id=$vv->user_coupon_id;          //优惠券的ID
                                        $v->coupon_info_left=$vv->coupon_title;
										$v->coupon_info_right='满减'.number_format($v->discounts_single/100,2).'元';          //优惠券的名字

                                        $v->can_count_discount = 'Y';           //做个已经优惠的标记
										//dd($v);
										if($v->can_count_discount == 'Y' && $v->checked_state=='true'){
											$pay['kehu_yinfu']-= $v->discounts_single;
											$pay['discounts_single_total']+=$v->discounts_single;                 //单品券优惠总计
											//dump(333);
										}
										$vv->can_use='N';
										$v->qiao_money=$v->total_price-$v->discounts_single;
										//dump($v->discounts_single);
										//dump($pay);
										//break;
                                    }

                                    break;
                                case 'discount':
                                    //折扣，指的是商品的总金额是不是比优惠券要求的高的时候对商品进行打折
                                    if($v->total_price >= $vv->range_condition){
                                        //如果大于了，说明是可以优惠的
                                        $v->discounts_single=$v->total_price-intval($v->total_price*$vv->range/100);   //优惠了这么多分
                                        $v->coupon_id=$vv->user_coupon_id;          //优惠券的ID
										$v->coupon_info_left=$vv->coupon_title;
										$v->coupon_info_right='折扣'.number_format($v->discounts_single/100,2).'元';          //优惠券的名字
                                        $v->can_count_discount = 'Y';           //做个已经优惠的标记
										if($v->can_count_discount == 'Y' && $v->checked_state=='true'){
											$pay['kehu_yinfu'] -= $v->discounts_single;
											$pay['discounts_single_total'] +=$v->discounts_single;                 //单品券优惠总计
											//dump(111);
										}
										
                                        
										$vv->can_use='N';
										$v->qiao_money=$v->total_price-$v->discounts_single;
										//dump($v->discounts_single);
										//dump($pay);
                                    }

                                    break;
                                case 'reducecount':
                                    //满件减，指的是商品的购买数量是不是比优惠券要求的高的时候对商品优惠
                                    if($v->good_number >= $vv->range_condition){
                                        //如果大于了，说明是可以优惠的
                                        $v->discounts_single=$vv->range;            //优惠了这么多分
                                        $v->coupon_id=$vv->user_coupon_id;          //优惠券的ID
                                        $v->coupon_info_left=$vv->coupon_title;          //优惠券的名字
										$v->coupon_info_right='满件减'.number_format($v->discounts_single/100,2).'元';          //优惠券的名字
                                        $v->can_count_discount = 'Y';           //做个已经优惠的标记
										if($v->can_count_discount == 'Y' && $v->checked_state=='true'){
											$pay['kehu_yinfu'] -= $v->discounts_single;
											$pay['discounts_single_total'] +=$v->discounts_single;                 //单品券优惠总计
											//dump(222);
										}
										$vv->can_use='N';
										$v->qiao_money=$v->total_price-$v->discounts_single;
										//dump($v->discounts_single);
										//dump($pay);
                                    }
                                    break;
                            }

                        }
                  }
				  
				  
        }
    }
	
    /**单品优惠券优惠计算结束*/
//dd($pay);
    /**全场优惠券优惠计算开始*/
    $fangan=null;           //定义一个方案，去拿一个最优秀的方案
	//做一个可以使用，和不可以使用的全场优惠券
	$user_all_coupon_can=[];
	$user_all_coupon_canno=[];
    if($user_all_coupon){
        //判断是不是订单那里过来的核算
        foreach ($user_all_coupon as $k => $v){
            //dd($v);
            switch($v->range_type){
                case 'reduce':
                    //满减，指的是商品的总金额是不是比优惠券要求的高
                    if($pay['kehu_yinfu'] >= $v->range_condition){
                        $fangan[$k]['discounts_usercoupon_id']=$v->user_coupon_id;
                        $fangan[$k]['discounts_all_total']=$v->range;
                        $fangan[$k]['discounts_usercoupon_title']=$v->coupon_title;
                        $fangan[$k]['discounts_usercoupon_title_right']='满减'.number_format($v->range/100,2).'元';
						$v->discounts_money=$v->range;
						$user_all_coupon_can[]=$v;
                    }else{
						$user_all_coupon_canno[]=$v;
					}
                    break;
                case 'discount':
                    //折扣，指的是商品的总金额是不是比优惠券要求的高的时候对商品进行打折
                    if($pay['kehu_yinfu'] >= $v->range_condition){
                        //如果大于了，说明是可以优惠的
                        $fangan[$k]['discounts_usercoupon_id']=$v->user_coupon_id;
                        $fangan[$k]['discounts_all_total']=intval($pay['kehu_yinfu']*$v->range/100);
                        $fangan[$k]['discounts_usercoupon_title']=$v->coupon_title;
                        $fangan[$k]['discounts_usercoupon_title_right']='折扣'.number_format(intval($pay['kehu_yinfu']*$v->range/100)/100,2).'元';
						$v->discounts_money=intval($pay['kehu_yinfu']*$v->range/100);
						$user_all_coupon_can[]=$v;
                    }else{
						$user_all_coupon_canno[]=$v;
					}
                    break;
                case 'reducecount':
                    //满件减，指的是商品的购买数量是不是比优惠券要求的高的时候对商品优惠
                    //dd($pay['good_count']);
                    if($pay['good_count'] >= $v->range_condition){
                        //如果大于了，说明是可以优惠的
                        $fangan[$k]['discounts_usercoupon_id']=$v->user_coupon_id;
                        $fangan[$k]['discounts_all_total']=$v->range;
                        $fangan[$k]['discounts_usercoupon_title']=$v->coupon_title;
                        $fangan[$k]['discounts_usercoupon_title_right']='满件减'.number_format($v->range/100,2).'元';
						$v->discounts_money=$v->range;
						$user_all_coupon_can[]=$v;
                    }else{
						$user_all_coupon_canno[]=$v;
					}
                    break;
            }

        }


    }
//dd($fangan);
//$all_coupon_id='usecoupon_202004171031517942637938';

    if($fangan){
		if($all_coupon_id){
			if($all_coupon_id != '123'){
				//123说明是有优惠券可以使用，但是不使用优惠券
				foreach($fangan as $k => $v){
					if($v['discounts_usercoupon_id'] == $all_coupon_id){
						$vkl=$k;
					}
				}
				$pay['discounts_usercoupon_id']=$fangan[$vkl]['discounts_usercoupon_id'];          //优惠券的ID
				$pay['discounts_usercoupon_title']=$fangan[$vkl]['discounts_usercoupon_title'];
				$pay['discounts_all_total']=$fangan[$vkl]['discounts_all_total'];
				$pay['discounts_total_money'] += $fangan[$vkl]['discounts_all_total'];
				$pay['kehu_yinfu'] -= $fangan[$vkl]['discounts_all_total'];
				//dd($vkl);
			}
		}else{
			//如果有优惠则这样处理，拿取一个最优惠的条件
			$last_names = array_column($fangan,'discounts_all_total');
			array_multisort($last_names,SORT_DESC,$fangan);
			$pay['discounts_usercoupon_id']=$fangan[0]['discounts_usercoupon_id'];          //优惠券的ID
			$pay['discounts_usercoupon_title']=$fangan[0]['discounts_usercoupon_title'];
			$pay['discounts_all_total']=$fangan[0]['discounts_all_total'];
			$pay['discounts_total_money'] += $fangan[0]['discounts_all_total'];
			$pay['kehu_yinfu'] -= $fangan[0]['discounts_all_total'];
		}
    }

	if($user_all_coupon_can){
		$user_all_coupon_can=manage_coupon($user_all_coupon_can,'order',null);
	}
	if($user_all_coupon_canno){
		$user_all_coupon_canno=manage_coupon($user_all_coupon_canno,'order',null);
	}
	//manage_coupon($coupon_info,$type,$user_id,$user_integral=null)
	//dd($pay['goods_total_money']-$pay['discounts_single_total']);
	if($pay['discounts_all_total']>0 && $count>0){
		//说明有全场优惠，为了做订单，所以需要把这个东西丢到每个里面去
		//$good_infos,$user_activity,$user_coupon,$pay,$all_coupon_id,$count=0
		
		//发起全场优惠券的密等计算
		//$jishu=$pay['discounts_total_money'];
		$jishu=$pay['goods_total_money']-$pay['discounts_single_total'];
		$qiao['zong']=$qiao['jisuan']=$pay['discounts_all_total'];
		
		foreach($good_infos as $k => $v){
			$aweu=$k+1;
			if($aweu == $count){
				$v->discounts_all_total = $qiao['jisuan'];			//如果是最后一条，则不用做比例
			}else{
				$v->discounts_all_total = intval($pay['discounts_all_total']*($v->qiao_money/$jishu));
				$qiao['jisuan'] -= $v->discounts_all_total;
			}
		}
	}

//dd($pay['discounts_total_money']);

    /**全场优惠券优惠计算结束*/
    $msg['good_infos']=$good_infos;
    $msg['pay']=$pay;
    $msg['user_all_coupon_can']=$user_all_coupon_can;
	$msg['user_all_coupon_canno']=$user_all_coupon_canno;


    //dd($good_infos);
    //dump($msg);
    return $msg;
}



?>