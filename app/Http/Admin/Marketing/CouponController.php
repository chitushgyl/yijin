<?php
namespace App\Http\Admin\Marketing;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\CouponController as Coupon;
use App\Models\Shop\ShopCoupon;
use App\Models\Shop\ShopCouponExchange;
use App\Models\User\UserCoupon;
use App\Models\Group\SystemGroup;
use App\Http\Controllers\DetailsController as Details;

class CouponController  extends CommonController{
    /***    优惠券信息头部      /marketing/coupon/couponList
     */
    public function  couponList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

       // dd($msg);
        return $msg;
    }

    /***    优惠券信息分页      /marketing/coupon/couponPage
     */

	public function couponPage(Request $request,Coupon $coupon){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);

        $select=['self_id','coupon_title','coupon_status','coupon_amount','coupon_inventory','coupon_get_number','coupon_remark',
            'get_way','get_way_value','get_limit_number','get_redeem_code','get_start_time','get_end_time',
            'range_type','range_condition','range',
            'time_type','time_start_day','time_end_day','time_type','time_start','time_end',
            'create_user_name','create_time','use_flag',
            'use_type','use_type_id','use_fallticket_flag','use_self_lifting_flag','group_name'];
        $select_erpShopGoodsSku=['self_id','good_name'];


        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ShopCoupon::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ShopCoupon::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->with(['erpShopGoodsSku' => function($query)use($select_erpShopGoodsSku) {
                    $query->select($select_erpShopGoodsSku);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ShopCoupon::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量

                $data['items']=ShopCoupon::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->with(['erpShopGoodsSku' => function($query)use($select_erpShopGoodsSku) {
                    $query->select($select_erpShopGoodsSku);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ShopCoupon::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量

                $data['items']=ShopCoupon::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->with(['erpShopGoodsSku' => function($query)use($select_erpShopGoodsSku) {
                    $query->select($select_erpShopGoodsSku);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

//        dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {

            $info=$coupon->shop_coupon($v);
//            //领取路径
            $v->get_way_show            =$info['get_way_show'];
            $v->get_redeem_code         =$info['get_redeem_code'];
            $v->range_type_show         =$info['range_type_show'];
            $v->time_type_show          =$info['time_type_show'];
            $v->coupon_state_show       =$info['coupon_state_show'];
            $v->use_type_show           =$info['use_type_show'];


            //查询中已使用的数量
            $seriu['shop_coupon_id']=$v->self_id;
            $seriu['coupon_status']='used';
            $seriu['delete_flag']='Y';
            $seriu['use_flag']='Y';
            $v->useed=UserCoupon::where($seriu)->count();

            $v->button_info=$button_info;

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//         dd($data['items']->toArray());
        return $msg;

	}

    /***    创建优惠券      /marketing/coupon/createCoupon
     */
    public function createCoupon(Request $request){
        /** 读取配置文件信息**/
        $data['get_way']                =config('shop.get_way');
        $data['coupon_type']            =config('shop.coupon_type');
        $data['coupon_state']           =config('shop.coupon_state');


        /** 接收数据*/
        $self_id            =$request->input('self_id');

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $data['coupon_info']=ShopCoupon::where($where)->first();
        if($data['coupon_info']){
            $data['coupon_info']->range_condition=$data['coupon_info']->range_condition/100;
            $data['coupon_info']->range=$data['coupon_info']->range/100;

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }


    /***    优惠券数据提交      /marketing/coupon/addCoupon
     */
    public function addCoupon(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='shop_coupon';

        $operationing->access_cause     ='创建/修改优惠券';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;


        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();
		//dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $coupon_title       =$request->input('coupon_title');
        $coupon_status      =$request->input('coupon_status');
        $coupon_amount      =$request->input('coupon_amount');
        $coupon_inventory   =$request->input('coupon_inventory');
        $coupon_remark      =$request->input('coupon_remark');

        //领取属性
        $get_way            =$request->input('get_way');
        $get_way_value      =$request->input('get_way_value');
        $get_limit_number   =$request->input('get_limit_number');
        $get_redeem_code    =$request->input('get_redeem_code');
        $get_start_time     =$request->input('get_start_time');
        $get_end_time       =$request->input('get_end_time');

        //优惠属性
        $range_type         =$request->input('range_type');
        $range_condition    =$request->input('range_condition');
        $range              =$request->input('range');
        //优惠券时间属性
        $time_type          =$request->input('time_type');
        $time_start_day     =$request->input('time_start_day');
        $time_end_day       =$request->input('time_end_day');
        $time_start         =$request->input('time_start');
        $time_end           =$request->input('time_end');

        //使用限制属性
        $use_type               =$request->input('$use_type');
        $use_type_id            =$request->input('use_type_id');
        $use_fallticket_flag    =$request->input('use_fallticket_flag');
        $use_self_lifting_flag  =$request->input('use_self_lifting_flag');
        $rear_give_flag         =$request->input('rear_give_flag');




        /*** 虚拟数据**/
        //$input['self_id']=$self_id='good_202007011336328472133661';
        //$input['group_code']        =$group_code='1234';
        //$input['coupon_title']      =$coupon_title='优惠券名称';
        //$input['coupon_status']     =$coupon_status='wait';
        //$input['coupon_remark']     =$coupon_remark='我是备注哦';
        //$input['coupon_amount']     =$coupon_amount='100';
        //$input['coupon_inventory']  =$coupon_inventory='100';
        //$input['get_way']           =$get_way='exchange';
        //$input['get_way_value']     =$get_way_value='get_way_value';
        //$input['get_limit_number']  =$get_limit_number='2';
        //$input['get_redeem_code']   =$get_redeem_code='200';
        //$input['get_start_time']    =$get_start_time='2020-06-16 21:22:12';
        //$input['get_end_time']      =$get_end_time='2020-12-16 21:22:12';

        //$input['range_type']        =$range_type='reduce';
        //$input['range_condition']   =$range_condition='20';
        //$input['range']             =$range='1';

        //$input['time_type']          =$time_type='assign';
        //$input['time_start_day']     =$time_start_day='1';
        //$input['time_end_day']       =$time_end_day='10';
        //$input['time_start']         =$time_start='2020-12-16 21:22:12';
        //$input['time_end']           =$time_end='2021-12-16 21:22:12';


        //$input['use_type']                  =$use_type='all';
        //$input['use_type_id']               =$use_type_id='all';
        //$input['use_fallticket_flag']       =$use_fallticket_flag='N';
        //$input['use_self_lifting_flag']     =$use_self_lifting_flag='N';
        //$input['rear_give_flag']            =$rear_give_flag='Y';
        /**效验数据的合法性
         * 那些字段是必须要的:优惠券的名称，优惠券类型
         ***/
        $rules=[
            'coupon_title'=>'required',
            'range_type'=>'required',
        ];
        $message=[
            'coupon_title.required'=>'优惠券名称不能为空',
            'range_type.required'=>'优惠类型必须选择',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $data['coupon_title']=$coupon_title;
            $data['coupon_status']=$coupon_status;

            if($self_id){
                $data['coupon_inventory']=$coupon_inventory;
            }else{
                $data['coupon_amount']=$coupon_amount;
                $data['coupon_inventory']=$coupon_inventory;
                $data['coupon_get_number']=0;
            }

            $data['coupon_remark']      =$coupon_remark;                               //优惠券后台备注
            $data['get_way']            =$get_way;	//领取路径:prize奖品券，common普通券(首页领取),late迟到券,web页面领取，integral积分兑换获得，exchange兑换码兑换，
            $data['get_way_value']      =$get_way_value;
            $data['get_limit_number']   =$get_limit_number;        //每人限领张数
            $data['get_start_time']     =$get_start_time;	//优惠券发送开始时间
            $data['get_end_time']       =$get_end_time;	//优惠券发送结束时间
            if($get_way == 'integral'){
                $data['get_redeem_code']=$get_redeem_code*100;//积分兑换券需要使用的积分，或者是生成的兑换码
            }


            //下面做优惠的属性
            $data['range_type']=$range_type;    //优惠券类型:满减reduce，折扣discount，无门槛all
            switch($range_type){
                //  满减reduce，折扣discount，满件减reducecount
                case 'reduce':
                    $data['range_condition']=$range_condition*100;			    //优惠条件
                    $data['range']=$range*100;                                  //如果是满减，则计算单位为分
                    break;
                case 'discount':
                    $data['range_condition']=$range_condition*100;			        //优惠条件
                    $data['range']=$range*10;                                      //优惠幅度,如果是折扣8折，则数字是80
                    break;
                case 'reducecount':
                    $data['range_condition']=$range_condition;			            //优惠条件,
                    $data['range']=$range*100;                                       //计算单位为分
                    break;
            }

            $data['time_type']=$time_type;	//有效时间类型 assign:指定日期 dynamic:动态日期'
            switch($time_type){
                case 'assign':
                    $data['time_start']=$time_start;	//使用开始时间,指定时间有效'
                    $data['time_end']=$time_end;	//使用结束时间,指定时间有效'
                    break;
                case 'dynamic':
                    $data['time_start_day']=$time_start_day;	//动态日期的天数范围'
                    $data['time_end_day']=$time_end_day;	//推迟生效时间
                    break;
            }

            $data['use_type']               =$use_type;	//使用类型:全场券all，单品券good，品类券classify，门店券shop，活动券activity'
            $data['use_type_id']            =$use_type_id;
            $data['use_fallticket_flag']    =$use_fallticket_flag;
            $data['use_self_lifting_flag']  =$use_self_lifting_flag;    //是否自提可用Y,N'
            $data['rear_give_flag']         =$rear_give_flag; //是否后台可以赠送给指定的用户

            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];
            $old_info=ShopCoupon::where($where)->first();

            if($old_info){
				//dd(1111);
                $data['update_time']=$now_time;
                $id=ShopCoupon::where($where)->update($data);

                $operationing->access_cause='修改优惠券';
                $operationing->operation_type='update';


            }else{
				//dd(2222);
                $group_name=SystemGroup::where('self_id','=',$group_code)->value('group_name');

                $data['self_id']            =generate_id('coupon_');		//优惠券表ID
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         =$group_code;
                $data['group_name']         =$group_name;
                $id=ShopCoupon::insert($data);

                if($get_way=='exchange'){
                    $shop_coupon_exchange=[];

                    $exchange['coupon_id']          =$data['self_id'];
                    $exchange['create_user_id']     =$user_info->admin_id;
                    $exchange['create_user_name']   =$user_info->name;
                    $exchange['create_time']        =$exchange['update_time']=$now_time;
                    $exchange['group_code']         =$group_code;
                    $exchange['group_name']         =$group_name;
                    for($i=0;$i<$coupon_amount;$i++){
                        $exchange['self_id']        =generate_id('exchange_');
                        /** 弄一个8位的随机数字+字母的组合出来*/
                        $str=chr(rand(65,90)).chr(rand(65,90)).chr(rand(65,90)).rand(10,99).chr(rand(97,122)).chr(rand(97,122)).chr(rand(97,122));
                        $exchange['exchange_code']  =$str;
                        $shop_coupon_exchange[]=$exchange;
                    }
                    ShopCouponExchange::insert($shop_coupon_exchange);
                }
                $operationing->access_cause='新建优惠券';
                $operationing->operation_type='create';

            }

            $operationing->table_id=$old_info?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
                return $msg;
            }

        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }

    }

    /***    优惠券查询商品   很大的可能用不用这个了！！！！！     /marketing/coupon/couponSearchGoods
     */
    public function  couponSearchGoods(Request $request){
        /** 接收数据*/
        $group_code=$request->input('group_code');
        $search_name=$request->input('search_name');
        $group_code='1234';

        if($search_name){
            $where_goods=[
                ['good_name','like','%'.$search_name.'%'],
                ['delete_flag','=','Y'],
                ['use_flag','=','Y'],
                ['group_code','=',$group_code],
            ];
        }else{
            $where_goods=[
                ['delete_flag','=','Y'],
                ['use_flag','=','Y'],
                ['group_code','=',$group_code],
            ];
        }

        $data['goods_info']=DB::table('erp_shop_goods')->where($where_goods)->select('self_id','good_title','group_name')->get()->toArray();


        $msg['code']=200;
        $msg['msg']='数据拉取成功';
        $msg['data']=$data;

        return $msg;

    }

    /***    优惠券启用禁用      /marketing/coupon/couponUseFlag
    */
    public function couponUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_coupon';
        $medol_name='ShopCoupon';
        $self_id=$request->input('self_id');
        $flag='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='启用/禁用';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }

    /***    优惠券删除      /staff/staff/couponDelFlag
     */

    public function couponDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_coupon';
        $medol_name='ShopCoupon';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }

    /***    优惠券详情      /marketing/coupon/details
     */
    public function details(Request $request,Details $details){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $table_name='shop_coupon';
        $select=[
	'self_id',
	'group_code','group_name','use_flag','create_user_name','create_time','coupon_title',
	'coupon_status','coupon_amount','coupon_inventory','coupon_get_number','coupon_remark','coupon_details','get_way','get_way_value','get_limit_number','get_redeem_code','get_start_time',
	'get_end_time','range_type','range_condition','range','time_type','time_start_day','time_end_day','time_start','time_end','use_type','use_type_id','use_fallticket_flag','use_self_lifting_flag','rear_give_flag'
	];
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/


            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);

            }


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }


    }

}
?>
