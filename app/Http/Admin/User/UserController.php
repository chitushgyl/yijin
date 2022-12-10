<?php
namespace App\Http\Admin\User;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User\UserTotal;
use App\Models\User\UserReg;
use App\Models\User\UserCapital;
use App\Models\User\UserWallet;
use App\Models\User\UserCoupon;
use App\Http\Controllers\ComputeController as Compute;
use App\Http\Controllers\CouponController as Coupon;

class UserController  extends CommonController{
    /***    用户信息头部      /user/user/userList
     */
    public function  userList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    用户信息分页     /user/user/userPage
     */
	public function userPage(Request $request){
        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;
        $tel        =$request->input('tel');
        $wx         =$request->input('wx');

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'like','name'=>'tel','value'=>$tel],
            ['type'=>'like','name'=>'token_name','value'=>$wx],
        ];

        $where=get_list_where($search);
        $select=['self_id','tel','create_time','true_name','true_name'];
        $selectUserReg=['self_id','total_user_id','create_time','tel','reg_type','token_img','token_name','token_appid'];
        $selectUserCapital=['total_user_id','integral','money','share','performance'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=UserTotal::where($where)->count(); //总的数据量

                $data['items']=UserTotal::with(['userReg' => function($query) use($selectUserReg){
                    $query->select($selectUserReg);
                    $query->where('delete_flag','=','Y');
                }])->with(['userCapital' => function($query)use($selectUserCapital) {
                        $query->select($selectUserCapital);
                        $query->where('delete_flag','=','Y');
                    }])
                    ->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)
                    ->get();
                break;

            case 'one':
                $data['total']=0; //总的数据量
                $data['items']=[];


                break;

            case 'more':
                $data['total']=0; //总的数据量
                $data['items']=[];

                break;
        }

        //dd($data['items']);

//dump($data['items']->toArray());
        foreach($data['items'] as $k => $v){
            $v->integral            =number_format($v->userCapital->integral/100,2);
            $v->money               =number_format($v->userCapital->money/100,2);
            $v->share               =number_format($v->userCapital->share/100,2);
            foreach ($v->userReg as $kk => $vv){
                switch ($vv->reg_type){
                    case 'TEL':
                        $vv->sys='glyphicon glyphicon-phone';
                        $vv->title='手机注册:'.$vv->tel;
                        break;

                    case 'ALIPAY':
                        $vv->sys='fa fa-money';
                        $vv->title='支付宝授权:'.$vv->token_name;
                        break;
                    case 'MINI':
                        $vv->sys='glyphicon glyphicon-home';
                        $vv->title='小程序:'.$vv->token_name;
                        break;

                    case 'WEIXIN':
                        $vv->sys='glyphicon glyphicon-home';
                        $vv->title='公众号:'.$vv->token_name;
                        break;
                    default:
                        $vv->sys='glyphicon glyphicon-home';
                        $vv->title='公众号:'.$vv->token_name;
                        break;
                }

            }


            $v->button_info=$button_info;
        }

        //dd($data['items']->toArray());


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
	}


    /***    余额拉取数据分页数据     /user/user/walletPage
     */

	public function walletPage(Request $request){
        /** 接收数据*/
        $input          =$request->all();
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $self_id        =$request->input('self_id');
        $capital_type   =$request->input('capital_type');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        /****虚拟数据**/
        //$input['self_id']       =$self_id       ="user_202010222339099152958180";
        //$input['capital_type']  =$capital_type  ='wallet' ;

        $rules = [
            'self_id' => 'required',
            'capital_type' => 'required',
        ];
        $message = [
            'self_id.required' => '没有选择用户',
            'capital_type.required' => '请确定流水类型',
        ];
        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            $user_where=[
                ['self_id','=',$self_id],
                ['delete_flag','=','Y'],
            ];


            $select=['self_id','tel','create_time','true_name'];
            $selectUserReg=['self_id','total_user_id','create_time','tel','reg_type','token_img','token_name','token_appid'];
            $selectUserCapital=['total_user_id','integral','money','share'];


            $data['user_info'] =UserTotal::with(['userReg' => function($query)use($selectUserReg) {
                $query->select($selectUserReg);
                $query->where('delete_flag','=','Y');
            }])->with(['userCapital' => function($query) use($selectUserCapital){
                $query->select($selectUserCapital);
                $query->where('delete_flag','=','Y');
            }])
                ->where($user_where)
                ->select($select)
                ->first();
            //dump($data['user_info']->toArray());

            if($data['user_info']){
                $data['user_info']->integral        =number_format($data['user_info']->userCapital->integral/100,2);
                $data['user_info']->money           =number_format($data['user_info']->userCapital->money/100,2);

                $where=[
                    ['total_user_id','=',$self_id],
                    ['delete_flag','=','Y'],
                    ['capital_type','=',$capital_type],
                ];
                $selectWallet=['produce_type','money','produce_cause','create_time','now_money'];

                $data['total']=UserWallet::where($where)->count(); //总的数据量
                $data['items']=UserWallet::where($where)->offset($firstrow)->limit($listrows)
                    ->orderBy('create_time', 'desc')->select($selectWallet)->get();


               // dump($data['user_info']->toArray());

                //dump($data['items']);

                foreach ($data['items'] as $k => $v){
                    if($v->produce_type == 'IN'){
                        $v->money='+'.number_format($v->money/100,2);
                    }else{
                        $v->money='-'.number_format($v->money/100,2);
                    }
                    $v->now_money=number_format($v->now_money/100,2);
                }

                //dd($data['items']->toArray());
            }

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;

            //dd($msg);
            return $msg;

        }else{
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

    /***    添加金额     /user/user/addWallet
     */
	public function addWallet(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='user_capital';
        $now_time       =date('Y-m-d H:i:s',time());

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();
        $operationing->access_cause='修改客户';
        $operationing->operation_type='update';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        /** 接收数据*/
        $user_id            =$request->input('user_id');
        $value_money        =$request->input('value_money');
        $change_type        =$request->input('change_type');
        $capital_type       =$request->input('capital_type');
        /*** 虚拟数据*/
        //$input['user_id']       =$user_id='user_202010222339099152958180';
        //$input['value_money']   =$value_money='105';
        //$input['change_type']   =$change_type='+';
        //$input['capital_type']   =$capital_type='share';
//	    dump($input);

        $rules=[
            'user_id'=>'required',
            'value_money'=>'required',
            'change_type'=>'required',
            'capital_type'=>'required',
        ];
        $message=[
            'user_id.required'=>'用户不能为空',
            'value_money.required'=>'数字不能为空',
            'change_type.required'=>'增加还是减少不能为空',
            'capital_type.required'=>'属性不能为空',
        ];

        $validator=Validator::make($input,$rules,$message);


        if($validator->passes()){
            //先通过USER_id查询出这个用户的余额
            $where=[
                ['delete_flag','=','Y'],
                ['total_user_id','=',$user_id],
            ];

            //查询用户中金额
            $old_info=UserCapital::where($where)->select('self_id','money','share','integral','update_time')->first();

            switch ($capital_type){
                case 'wallet':
                    $abc='money';
                    $message1='余额';
                    break;
                case 'integral':
                    $abc='integral';
                    $message1='积分';
                    break;
                case 'share':
                    $abc='share';
                    $message1='股份';
                    break;
            }

//            dump($abc);
//            dump($old_info);

            if($change_type=='+'){
                $message='增加'.$message1;
                $now_money=$old_info->$abc+$value_money*100;      //得到一个新的余额

                $data['produce_type']='IN';
                $data['produce_cause']='后台增加';


            }else{
                $data['produce_type']='CONSUME';
                $data['produce_cause']='后台减少';

                if($old_info->$abc > 0){
                    $now_money=$old_info->$abc-$value_money*100;      //得到一个新的余额
                    $message='减少'.$message1;
                    if($now_money<0){
                        $now_money=0;
                        $value_money=$old_info->$abc/100;
                    }
                }else{
                    //如果是减少，而原来的数字
                    $msg['code']=301;
                    $msg['msg']="客户".$message1."已经为0，不用再减少了";
                    //dd($msg);
                    return $msg;
                }

            }

            //dd($data);
            $id=null;
            /** 可以开始执行事务操作了**/
            DB::beginTransaction();

            try{
                $capital[$abc]=$now_money;
                $capital['update_time']=$now_time;
                $id=UserCapital::where($where)->update($capital);
                //dd($capital);
                //做一个流水记录1
                $data['self_id']        =generate_id('wallet_');
                $data['total_user_id']  =$user_id;
                $data['capital_type']   =$capital_type;
                $data['create_time']    =$now_time;
                $data['money']          =$value_money*100;
                $data['order_sn']       =$user_info->admin_id;
                $data['now_money']      =$now_money;
                $data['now_money_md']   =get_md5($now_money);
                $data['ip']             =$request->getClientIp();
                $data['wallet_status']  ='SU';
                UserWallet::insert($data);

                DB::commit();
            }catch (\Exception $e) {
                //接收异常处理并回滚
                DB::rollBack();
                $msg['code']=303;
                $msg['msg']="事务打断";
                return $msg;
            }

            $operationing->table_id=$old_info->self_id;
            $operationing->old_info=$old_info;
            $operationing->new_info=$capital;
            $operationing->access_cause='修改客户'.$message1;

            if($id){
                $msg['code'] = 200;
                $msg['msg'] = $message."成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = $message."失败";
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



    /***    优惠券拉取数据分页数据     /user/user/userCouponList
     */
    public function userCouponList(Request $request,Coupon $coupon){
        $now_time       =date('Y-m-d H:i:s',time());
        $self_id        =$request->input('self_id');

        /****虚拟数据**/
        $self_id       ="user_202010291622092764806292";

        $user_where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','total_user_id','create_time','tel','reg_type','token_img','token_name','token_appid','true_name'];
        $data['user_info'] =UserTotal::with(['userReg' => function($query) use($select){
            $query->select($select);
            $query->where('delete_flag','=','Y');
        }])
            ->where($user_where)
            ->select('self_id','tel','create_time')
            ->first();

        /*** 拿取可以发送优惠券的列表**/
        $where1=[
            ['a.rear_give_flag','=','Y'],
            ['a.use_flag','=','Y'],
            ['a.delete_flag','=','Y'],
            ['a.get_start_time','<',$now_time],
            ['a.get_end_time','>',$now_time],
            ['a.coupon_inventory','>','0'],
        ];

        $data['can_use_coupon'] =DB::table('shop_coupon as a')
            ->join('erp_shop_goods as b',function($join){
                $join->on('a.use_type_id','=','b.self_id');
            }, null,null,'left')
            ->where($where1)
            ->select(
                'a.*',
                'b.good_title'
            )
            ->orderBy('a.create_time','desc')
            ->get()->toArray();

        foreach($data['can_use_coupon'] as $k => $v){
            $info=$coupon->shop_coupon($v);
//            //领取路径
            $v->get_way_show=$info['get_way_show'];
            $v->get_redeem_code=$info['get_redeem_code'];
            $v->range_type_show=$info['range_type_show'];
            $v->time_type_show=$info['time_type_show'];
            $v->coupon_state_show=$info['coupon_state_show'];
            $v->use_type_show=$info['use_type_show'];
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;


    }

    /***    优惠券拉取数据分页数据     /user/user/userCouponPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
	public function userCouponPage(Request $request){
        /** 接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $self_id        =$request->input('self_id');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        /****虚拟数据**/
        $self_id       ="user_202010291622092764806292";
        /**将使用时间超过现在时间的券的状态变成过期的状态**/
        $now_time=date('Y-m-d H:i:s',time());
        $user_coupon_where_do=[
            ['total_user_id','=',$self_id],
            ['coupon_status','=','unused'],
            ['time_end','<',$now_time],
        ];
        $coupon_data['coupon_status']       ='stale';
        $coupon_data['update_time']         =$now_time;
        UserCoupon::where($user_coupon_where_do)->update($coupon_data);
        /**将使用时间超过现在时间的券的状态变成过期的状态          结束**/

        $where=[
            ['total_user_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];
        $data['total']=UserCoupon::where($where)->count(); //总的数据量
        $data['items']=UserCoupon::where($where)
            ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
            ->get();


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;

    }

    /***    添加客户优惠券     /user/user/addUserCoupon
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addUserCoupon(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='user_coupon';
        $now_time       =date('Y-m-d H:i:s',time());

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();
        $operationing->access_cause='增加优惠券';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        /** 接收数据*/
        $user_id            =$request->input('user_id');
        $coupon_id          =$request->input('coupon_id');

        /*** 虚拟数据*/
        $input['user_id']     =$user_id='user_202010291622092764806292';
        $input['coupon_id']   =$coupon_id='coupon_20200928155425956582308';

        $rules=[
            'user_id'=>'required',
            'coupon_id'=>'required',
        ];
        $message=[
            'user_id.required'=>'没有用户ID',
            'coupon_id.required'=>'没有优惠券',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$coupon_id],
            ];
            $info=DB::table('shop_coupon')->where($where)->first();


            $data['self_id']                =generate_id('usecoupon_');
            $data['shop_coupon_id']         =$info->self_id;
            $data['total_user_id']          =$user_id;
            $data['coupon_title']           =$info->coupon_title;
            $data['coupon_remark']          =$info->coupon_remark;
            $data['coupon_details']         =$info->coupon_details;
            if($info->time_type=='dynamic'){ //动态时间,以领取的时间开始计算
                $data['time_start']         =date('Y-m-d H:i:s',strtotime('+'.$info->time_start_day.'day'));
                $tempAll=$info->time_start_day+$info->time_end_day;
                $data['time_end']           =date('Y-m-d H:i:s',strtotime('+'.$tempAll.'day'));
            }else{
                $data['time_start']         =$info->time_start;
                $data['time_end']           =$info->time_end;
            }
            $data['range_type']             =$info->range_type;
            $data['range_condition']        =$info->range_condition;
            $data['range']                  =$info->range;

            $data['create_user_id']         =$user_info->admin_id;
            $data['create_user_name']       =$user_info->name;
            $data['create_time']            =$data['update_time']=$now_time;

            $data['use_type']               =$info->use_type;
            $data['use_fallticket_flag']    =$info->use_fallticket_flag;
            $data['use_self_lifting_flag']  =$info->use_self_lifting_flag;
            $data['use_type_id']            =$info->use_type_id;
            $data['group_code']             =$info->group_code;
            $data['group_name']             =$info->group_name;
            $data['get_place']              ='HOUTAI';
            $data['get_way']                =$request->path();
            $data['get_cause_reason']       ='后台赠送';
            $data['give_flag']              =$info->rear_give_flag;

            $id=DB::table($table_name)->insert($data);
//            dump($data);
//
//            dd($info);

            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功';
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
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


    /***    后台添加用户     /user/user/addUser
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addUser(Request $request,Compute $compute){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='user_total';

        $operationing->access_cause='新建用户';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;
        $user_info          = $request->get('user_info');                //接收中间件产生的参数
        $input=$request->all();

        /** 接收数据*/
        $tel                 =$request->input('tel');
        $father_tel          =$request->input('father_tel');
        $true_name           =$request->input('true_name');
        /*** 虚拟数据
        $input['tel']           =$tel           ='15000661376'.rand(11,99);
        $input['father_tel']    =$father_tel    ='15021073076';
        $input['true_name']     =$true_name    ='4545';**/
        $rules=[
            'tel'=>'required',
            'father_tel'=>'required',
            'true_name'=>'required',
        ];
		//dump($tel);dd($father_tel);
        $message=[
            'tel.required'=>'用户手机号码必须',
            'father_tel.required'=>'上级手机号码必须',
            'true_name.required'=>'真实姓名必须',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /** 二次效验过程，第一步，效验上级用户有没有账号，且是不是可以发展会员           UserTotal
             *  效验新的手机号码是不是没有被注册过，如果注册过了，则不能完成绑定工作
             **/
            $where_tel=[
                'tel'=>$tel,
            ];
            $self_id=UserTotal::where($where_tel)->value('self_id');
			//dd($self_id);
            if($self_id){
				//dd(111111);
                $msg['code'] = 301;
                $msg['msg'] = "用户手机号码已注册";
                return $msg;
            }

            $where_father_tel=[
                'tel'=>$father_tel,
            ];
            $select=['self_id','grade_id','father_user_id1','father_user_id2','father_user_id3','father_user_id4','father_user_id5','father_user_id6','father_user_id7'];

            $info=UserTotal::where($where_father_tel)->select($select)->first();
            //dump($info->toArray());
            if($info){
                if($info->grade_id == 1){
                    //9为测试环境
                    $msg['code'] = 302;
                    $msg['msg'] = "上级手机号码不能推广股东";
                    return $msg;
                }

            }else{
                $msg['code'] = 303;
                $msg['msg'] = "上级手机号码不存在";
                return $msg;
            }

            /** 下面开始做业务逻辑，完成手机号码的注册过程，一共涉及到3个表，user_total ，user_reg ， user_capital **/
            $idd                    =generate_id('user_');
            //dump($idd);
            $data['self_id']        = $idd;
            $data['total_user_id']  = $idd;
            $data['reg_type']       = 'TEL';
            $data['tel']            = $tel;
            $data['reg_place']      = 'CT_H5';
            $data['ip']             = $info['ip'];
            $data['token_appid']    = null;
            $data['token_id']       = null;
            $data['token_img']      = null;
            $data['token_name']     = null;
            $data['create_time']    = $data['update_time']       =$now_time;

            UserReg::insert($data);									//写入用户表

            $data_total['self_id']      = $idd;
            $data_total['tel']          = $tel;
            $data_total['promo_code']   = md5($idd.$now_time);
            $data_total['create_time']  =$data_total['update_time']=$now_time;
            $data_total['true_name']    =$true_name;
            $data_total['father_user_id1']    =$info->self_id;

            for($i=2;$i<9;$i++){
                $fwfhiwf='father_user_id'.$i;
                $j=$i-1;
                $fewfew="father_user_id".$j;
                $data_total[$fwfhiwf]=$info->$fewfew;
            }
            $id=UserTotal::insert($data_total);							//写入用户主表

            $capital_data['self_id']        = generate_id('capital_');
            $capital_data['total_user_id']  = $idd;
            $capital_data['update_time']    =$now_time;
            UserCapital::insert($capital_data);						//写入用户资金表

            /***    这里需要做一个功能
             **     第一步，检查上级用户要不要升级！！！！   如果满足升级条件，则将这个用户进行升级
             **/
            $compute->gradeCompute($info->self_id,$now_time);

            $operationing->table_id=$idd;
            $operationing->old_info=null;
            $operationing->new_info=$data_total;


            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 304;
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
}
?>
