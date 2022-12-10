<?php
namespace App\Http\Admin\Tms;
use App\Models\SysAddress;
use App\Models\Tms\TmsOrderCost;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsSettle;
use App\Models\Tms\TmsSettleList;
use App\Models\Tms\TmsSettleInList;
use App\Models\Tms\TmsOrderMoney;




class SettleController extends CommonController{

    /***    结算管理列表头部      /wms/settle/settleList
     */
    public function  settleList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    结算管理分页      /tms/settle/settlePage
     */
    public function settlePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
		$company_id     =$request->input('company_id');
		$warehouse_id   =$request->input('warehouse_id');
		$gathering_flag   =$request->input('gathering_flag');
        $area           =$request->input('area');
        $start_time     =$request->input('start_time');
        $end_time     =$request->input('end_time');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        if ($start_time){
            $start_time = $start_time.' 00:00:00';
        }
        if ($end_time){
            $end_time = $end_time.' 23:59:59';
        }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'company_id','value'=>$company_id],
			['type'=>'=','name'=>'warehouse_id','value'=>$warehouse_id],
			['type'=>'=','name'=>'gathering_flag','value'=>$gathering_flag],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<','name'=>'create_time','value'=>$end_time],
        ];

        $where=get_list_where($search);

        $select=['self_id','type','group_name','company_name','create_user_name','create_time','use_flag','receivable_money','group_code',
            'practical_money','already_money','gathering_flag','company_id','driver_name','driver_tel'];
        $select1 = ['self_id','group_name','tel'];
        $select2 = ['self_id','type','company_name','group_code','group_name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsSettle::where($where)->count(); //总的数据量
                $data['items']=TmsSettle::with(['systemGroup' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['tmsGroup' => function($query) use($select2){
                        $query->select($select2);
                    }])
                ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsSettle::where($where)->count(); //总的数据量
                $data['items']=TmsSettle::with(['systemGroup' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['tmsGroup' => function($query) use($select2){
                        $query->select($select2);
                    }])
                ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsSettle::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsSettle::with(['systemGroup' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['tmsGroup' => function($query) use($select2){
                        $query->select($select2);
                    }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
//        dump($button_info->toArray());
        $button_info1=[];
        $button_info2=[];
//            $button_info3 = [];
        $button_info4 = [];
        foreach ($button_info as $k => $v){
            if($v->id==632){
                $button_info1[]=$v;
            }
            if($v->id==649){
//                    $button_info3[] = $v;
                $button_info4[] = $v;
            }
            if($v->id==633){
                $button_info1[]=$v;
                $button_info4[] = $v;
            }
            if($v->id==634){
                $button_info1[]=$v;
                $button_info2[]=$v;
//                    $button_info3[] = $v;
                $button_info4[] = $v;
            }

        }
//        dd($button_info4,$button_info1,$button_info2);
        foreach ($data['items'] as $k=>$v) {

            if ($v->type == 'in'){
                if($v->gathering_flag == 'Y'){
                    $v->gathering_show='已完成收款';
                }else{
                    if($v->already_money > 0){
                        $v->gathering_show='部分收款';
                    }else{
                        $v->gathering_show='未收款';
                    }
                }
            }else{
                if($v->gathering_flag == 'Y'){
                    $v->gathering_show='已完成付款';
                }else{
                    if($v->already_money > 0){
                        $v->gathering_show='部分付款';
                    }else{
                        $v->gathering_show='未付款';
                    }
                }
            }
            $v->practical_money = number_format($v->practical_money/100, 2);
            $v->receivable_money = number_format($v->receivable_money/100, 2);
            $v->already_money = number_format($v->already_money/100, 2);
            $v->button_info=$button_info;

            if ($v->systemGroup){
                $v->group_name = $v->systemGroup->group_name;
            }
            if ($v->tmsGroup){
                $v->object_show = $v->tmsGroup->company_name;
            }elseif($v->driver_name){
                $v->object_show = $v->driver_name.'/'.$v->driver_tel;
            }else{
                $v->object_show = '';
            }

            if($v->type =='in' && $v->gathering_flag == 'N'){
                $v->button_info=$button_info1;
            }elseif($v->type =='out' && $v->gathering_flag == 'N'){
                $v->button_info=$button_info4;
            }else{
                $v->button_info=$button_info2;
            }

            }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /**
     * 创建结算           /tms/settle/createSettle
     * */
    public function createSettle(Request $request){
        $tms_money_type         =array_column(config('tms.tms_money_type'),'name','key');
        $money_type             =array_column(config('tms.money_type'),'name','key');
        $settle_type            =array_column(config('tms.tms_settle_type'),'name','key');
        $input              =$request->all();
        $group_code             =$request->input('group_code');
        $type                   =$request->input('type');
        $for_type               =$request->input('for_type');
        $company_id             =$request->input('company_id');
        $car_number             =$request->input('car_number');
        /** 虚拟数据
        $input['group_code']    =$group_code         = 'group_202104221337228311436766';   // 归属公司
        $input['company_id']    =$company_id         = 'company_202104221341231049734558'; // 业务公司ID
        $input['type']          =$type               = 'company'; //  company 公司  driver 司机  user 个体用户
//        $input['car_number']    =$car_number         = '';  //车牌号
         * **/

        $where=[
            ['settle_flag','=','N'],
            ['delete_flag','=','Y'],
            ['shouk_group_code','=',$group_code]
        ];

        $where1 = [
            ['settle_flag','=','N'],
            ['delete_flag','=','Y'],
            ['fk_group_code','=',$group_code]
        ];

        if($company_id && $type == 'company'){
            $where[] = ['fk_company_id','=',$company_id];
            $where1[] = ['shouk_company_id','=',$company_id];
        }
        if ($type == 'user'){
            $where[] = ['fk_type','=','USER'];
            $where1[] = ['shouk_type','=','USER'];
        }
        if ($type == 'driver'){
            $where[] = ['fk_type','=','DRIVER'];
            $where1[] = ['shouk_type','=','DRIVER'];
        }
        $rules=[
            'group_code'=>'required',
//            'type'=>'required',
        ];
        $message=[
            'group_code.required'=>'请选择结算公司',
//            'type.required'=>'请选择结算对象',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {


//        dump($where1);
//        dd($where);
        $data['info'] = [];
        $select = ['self_id','order_id','dispatch_id','carriage_id','driver_id','shouk_group_code','shouk_company_id','shouk_total_user_id','shouk_type','fk_group_code','fk_company_id',
            'fk_total_user_id','fk_type','ZIJ_group_code','ZIJ_company_id','ZIJ_total_user_id','create_time','update_time','use_flag','delete_flag','money','money_type',
            'settle_flag','settle_id','type','shouk_driver_id'
            ];
        $select1 = ['self_id','type','company_name'];
        $select2 = ['self_id','tel','token_name'];
        $select3 = ['self_id','car_number','contacts','tel'];
        $data['info'] = TmsOrderCost::with(['tmsGroup' => function($query)use($select1){
                    $query->select($select1);
                }])
           ->with(['tmsCompany' => function($query)use($select1){
                $query->select($select1);
            }])
            ->with(['userReg' => function($query)use($select2){
                $query->select($select2);
            }])
            ->with(['tmsDriver' => function($query)use($select3){
                $query->select($select3);
            }])->where($where)->orWhere($where1)->select($select)->orderBy('create_time','DESC')->get();
//        dd($data['info']->toArray());
        foreach ($data['info'] as $k => $v){
            $v->money = number_format($v->money/100, 2);
            $v->money_show=$money_type[$v->type]??null;
            $v->money_type_show=$tms_money_type[$v->money_type]??null;
            if ($v->type == 'in'){
                if ($v->fk_type == 'COMPANY'){
                    $v->object_show = $v->tmsGroup->company_name;
                }elseif($v->fk_type == 'USER'){
                    $v->object_show = $v->userReg->token_name ?? $v->userReg->tel;
                }
            }else{
                if ($v->shouk_type == 'COMPANY'){
                    $v->object_show = $v->tmsCompany->company_name;
                }elseif($v->shouk_type == 'USER'){
                    $v->object_show = $v->userReg->token_name ?? $v->userReg->tel;
                }elseif($v->shouk_type == 'DRIVER'){
                    $v->object_show = $v->tmsDriver->contacts;
                }
            }

        }
//        dd($data['info']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;
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

    /***    创建结算      /tms/settle/addSettle
     */
    public function  addSettle(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_settle';

        $operationing->access_cause     ='创建结算';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $money_list         = $request->input('money_list');
        $type               = $request->input('type');
        /*** 虚拟数据
        $money_list= ['order_money_202105111844091903617552','order_money_202105111842412174293718'];
        $type = 'driver'; // company 承运公司/客户公司   driver 司机
        **/

//		dd($money_list);

        $where=[
            ['delete_flag','=','Y'],
        ];
        $select = ['self_id','order_id','dispatch_id','carriage_id','driver_id','shouk_group_code','shouk_company_id','shouk_total_user_id','shouk_type','fk_group_code','fk_company_id',
            'fk_total_user_id','fk_type','ZIJ_group_code','ZIJ_company_id','ZIJ_total_user_id','create_time','update_time','use_flag','delete_flag','money','money_type',
            'settle_flag','settle_id','type','shouk_driver_id'
        ];
        $select1 = ['self_id','type','company_name'];
        $select2 = ['self_id','tel','token_name'];
        $select3 = ['self_id','car_number','contacts','tel'];
//        $info=TmsOrderCost::where($where)->whereIn('self_id', $money_list)->select($select)->get();
        $info = TmsOrderCost::with(['tmsGroup' => function($query)use($select1){
            $query->select($select1);
        }])
            ->with(['tmsCompany' => function($query)use($select1){
                $query->select($select1);
            }])
            ->with(['userReg' => function($query)use($select2){
                $query->select($select2);
            }])
            ->with(['tmsDriver' => function($query)use($select3){
                $query->select($select3);
            }])->whereIn('self_id', $money_list)->select($select)->orderBy('create_time','DESC')->get();
//        if ($type == 'driver'){
//            $id_list = array_column($info->toArray(),'driver_id');
//            $arr = array_unique($id_list);
//            if (count($arr)>1){
//                $msg['code'] = '305';
//                $msg['msg']  = '请选择同一结算对象';
//                return $msg;
//            }
//        }

//        dd($info->toArray());
        if($info){

            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=1;
            $in_info=[];
            $out_info=[];

            foreach ($info as $k=> $v){
                if($v->settle_flag == 'Y'){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行已结算过了，请核查".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }else{
                    switch ($v->type){
                        case 'in':
                            $in_info[]=$v->toArray();
                            break;

                        case 'out':
                            $out_info[]=$v->toArray();
                            break;
                    }
                }
                $a++;
            }

            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
//            dd($out_info);
//            dump($in_info);
            /** 做一个收款的数据出来**/
            if($in_info){
                $in_info_insert=[];
                $in_info_update=[];
                $in_money=0;
                $in_seld=generate_id('settle_');
                foreach ($in_info as $k => $v){
//                    dd($v['tms_company']);
                    $list['self_id']            =generate_id('ilist_');
                    $list['settle_id']          =$in_seld;
                    $list["group_code"]         =$v['shouk_group_code'];
//                    $list["group_name"]         =$in_info->group_name;
                    if($v['shouk_type'] == 'COMPANY'){
                        $list['company_id']         = $v['fk_company_id'];
                        $list["company_name"]       = $v['tms_company']['company_name'];
                    }else{
                        $list['total_user_id']      = $v['fk_total_user_id'];
                    }

                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$now_time;
                    $list['update_time']        =$now_time;
                    $list["money"]              =$v['money'];
                    $list["order_money_id"]     =$v['self_id'];
//                    $list['cause']              =$v['cause'];
                    $list['type']               ='plus';
                    $in_info_update[]=$v['self_id'];
                    $in_info_insert[]=$list;
                    $in_money +=$v['money'];
                }


                $in_data['self_id']            =$in_seld;
                $in_data['type']               ='in';
                if ($type == 'company'){
                    $in_data['company_id']         =$in_info[0]['fk_company_id'];
//                    $in_data["company_name"]       =$info[0]->company_name;
                }elseif($type == 'driver'){
                    $in_data['driver_name']        = $in_info[0]['tms_driver']['contacts'];
                    $in_data['driver_tel']         = $in_info[0]['tms_driver']['tel'];
                    $in_data['car_number']         = $in_info[0]['tms_driver']['car_number'];
                }else{
                    $in_data['total_user_id']      = $in_info[0]['fk_total_user_id'];
                }
                $in_data["group_code"]         =$in_info[0]['shouk_group_code'];
//                $in_data["group_name"]         =$info[0]->group_name;
                $in_data["receivable_money"]   =$in_money;
                $in_data['practical_money']    =$in_money;
                $in_data['already_money']      =0;
                $in_data['create_user_id']     = $user_info->admin_id;
                $in_data['create_user_name']   = $user_info->name;
                $in_data['create_time']        =$now_time;
                $in_data['update_time']        =$now_time;
//                $in_data['ca rriage_type']        =$v['table_type'];
//                dump($in_info_insert);
//                dump($in_data);
                $id=TmsSettle::insert($in_data);
                TmsSettleInList::insert($in_info_insert);
                $money_up['settle_flag']        ='Y';
                $money_up['settle_id']          =$in_seld;
                $money_up['update_time']        =$now_time;

                //DUMP($in_info_update);
                TmsOrderCost::where($where)->whereIn('self_id', $in_info_update)->update($money_up);


//                dd($data);
            }

            /** 做一个收款的数据出来**/
            if($out_info){
                $out_info_insert=[];
                $out_info_update=[];
                $out_money=0;
                $out_seld=generate_id('settle_');
                foreach ($out_info as $k => $v){
                    $list['self_id']            =generate_id('ilist_');
                    $list['settle_id']          =$out_seld;
                    if($v['fk_type'] == 'COMPANY'){
                        $list['company_id']         = $v['shouk_company_id'];
                        $list["company_name"]       = $v['tms_company']['company_name'];
                    }elseif($v['fk_type'] == 'user'){
                        $list['total_user_id']      = $v['shouk_total_user_id'];
                    }else{
                        $list['driver_id']          = $v['shouk_driver_id'];
                    }
                    $list["group_code"]             = $v['fk_group_code'];
//                    $list["group_name"]         =$info[0]->group_name;

                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$now_time;
                    $list['update_time']        =$now_time;
                    $list["money"]              =$v['money'];
                    $list["order_money_id"]     =$v['self_id'];
//                    $list['cause']              =$v['cause'];
                    $list['type']               ='plus';

                    $out_info_update[]=$v['self_id'];
                    $out_info_insert[]=$list;
                    $out_money +=$v['money'];
                }

                $out_data['self_id']            =$out_seld;
                $out_data['type']               ='out';
                $out_data["group_code"]         =$out_info[0]['fk_group_code'];
//                $out_data["group_name"]         =$info[0]->group_name;
                if ($type == 'company'){
                    $out_data['company_id']         =$out_info[0]['shouk_company_id'];
//                    $in_data["company_name"]       =$info[0]->company_name;
                }elseif($type == 'driver'){
                    $out_data['driver_name']        = $out_info[0]['tms_driver']['contacts'];
                    $out_data['driver_tel']         = $out_info[0]['tms_driver']['tel'];
                    $out_data['car_number']         = $out_info[0]['tms_driver']['car_number'];
                }else{
                    $out_data['total_user_id']      = $out_info[0]['shouk_total_user_id'];
                }

                $out_data["receivable_money"]   =$out_money;
                $out_data['practical_money']    =$out_money;
                $out_data['already_money']      =0;
                $out_data['create_user_id']     = $user_info->admin_id;
                $out_data['create_user_name']   = $user_info->name;
                $out_data['create_time']        =$now_time;
                $out_data['update_time']        =$now_time;

//                dump($out_data);
//                dd($out_info_insert);
                $id=TmsSettle::insert($out_data);
                TmsSettleInList::insert($out_info_insert);

//                DUMP($out_info_update);
                $money_up['settle_flag']        ='Y';
                $money_up['settle_id']          =$out_seld;
                $money_up['update_time']        =$now_time;
                TmsOrderCost::where($where)->whereIn('self_id', $out_info_update)->update($money_up);
            }


            if($id){
                $msg['code']=200;
                $msg['msg']="结算成功";
                //dd($msg);
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']="结算失败";
                //dd($msg);
                return $msg;
            }


        }else{
            $msg['code']=300;
            $msg['msg']="没有需要结算的数据";
            //dd($msg);
            return $msg;
        }
    }


    /***    创建收款条     /tms/settle/createGathering
     */
    public function createGathering(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');

//        $self_id     ='settle_202104261455292765788458';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','group_name','company_name','use_flag','receivable_money','practical_money','already_money','gathering_flag'];
        $wmsSettleListSelect=['settle_id','money','voucher','serial_bank_name','serial_number'];
        $data['info']=TmsSettle::with(['tmsSettleList' => function($query)use($wmsSettleListSelect) {
            $query->select($wmsSettleListSelect);
        }]) ->where($where)->select($select)->first();
		//dd($data);
        if($data['info']){
            $surplus = $data['info']->practical_money - $data['info']->already_money;
            $data['info']->surplus = number_format($surplus/100,2);
            $data['info']->receivable_money=number_format($data['info']->receivable_money/100,2);
            $data['info']->practical_money=number_format($data['info']->practical_money/100,2);
            $data['info']->already_money=number_format($data['info']->already_money/100,2);
			//dd($data['info']->wmsSettleList);
            if($data['info']->wmsSettleList){
                foreach ($data['info']->wmsSettleList as $k => $v){
					//($v);
                    $v->money=number_format($v->money/100,2);
                    $v->voucher=img_for($v->voucher,'more');
                }
            }
//            dd($data['info']->toArray());
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
				return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="拉取不到数据";
            return $msg;
        }
    }


    /***    收款数据提交      /tms/settle/addGathering
     */
    public function addGathering(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_settle';

        $operationing->access_cause     ='创建';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $money              =$request->input('money');
        $voucher            =$request->input('voucher');
        $serial_bank_name   =$request->input('serial_bank_name');
        $serial_number      =$request->input('serial_number');

        /*** 虚拟数据
        $input['self_id']           =$self_id='settle_202103031642225267443160';
        $input['money']             =$money='1';
        $input['voucher']           =$voucher=null;
        $input['serial_bank_name']  =$serial_bank_name='中国银行';
        $input['serial_number']     =$serial_number='211';
//		**/

        $rules=[
            'self_id'=>'required',
            'money'=>'required',
        ];
        $message=[
            'self_id.required'=>'请填结算订单编号',
            'money.required'=>'请输入金额',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $where=[
                ['gathering_flag','=','N'],
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $select=['self_id','group_code','group_name','company_id','company_name','use_flag','receivable_money',
                'practical_money','already_money','gathering_flag'];
            $old_info=TmsSettle::where($where)->select($select)->first();

            if($old_info){
                $data['already_money']      =$old_info->already_money+$money*100;
                $data['update_time']        =$now_time;
                if($data['already_money']  >= $old_info->practical_money ){
                    $data['gathering_flag']='Y';
                }

                $list['self_id']            =generate_id('carriage_');
                $list['settle_id']          =$self_id;
                $list['group_code']         =$old_info->group_code;
                $list['group_name']         =$old_info->group_name;
                $list['company_id']         =$old_info->company_id;
                $list['company_name']       =$old_info->company_name;
                $list['create_user_id']     = $user_info->admin_id;
                $list['create_user_name']   = $user_info->name;
                $list['create_time']        =$list['update_time']       =$now_time;
                $list['money']              =$money*100;
                $list['voucher']            =img_for($voucher,'in');
                $list['serial_bank_name']   =$serial_bank_name;
                $list['serial_number']      =$serial_number;

                $id=TmsSettle::where($where)->update($data);


                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;

                if($id){
                    $msg['code'] = 200;

                    TmsSettleList::insert($list);
                    $msg['msg'] = "操作成功";
                    return $msg;
                }else{
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }

            }else{
                $msg['code']=301;
                $msg['msg']="拉取不到数据";
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


    /***    收款明细详情     /tms/settle/details
     */
    public function  details(Request $request,Details $details){
        $wms_money_type_show    =array_column(config('wms.wms_money_type'),'name','key');
        $self_id=$request->input('self_id');
        // $self_id='settle_202101031253483089524814';
        $table_name='tms_settle';
        $select=['self_id','group_name','company_name','use_flag','receivable_money','practical_money','already_money','gathering_flag'];

        $list_select=['settle_id','money','voucher','serial_bank_name','serial_number'];
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];


        $info=TmsSettle::with(['tmsSettleList' => function($query) use($list_select){
            $query->select($list_select);
        }])->where($where)->select($select)->first();

        if($info){

            foreach ($info->tmsSettleList as $k=>$v){
                $v->money                = number_format($v->money/100, 2);
            }

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->receivable_money=number_format($info->receivable_money/100, 2);
            $info->practical_money=number_format($info->practical_money/100, 2);
            $info->already_money=number_format($info->already_money/100, 2);
            $data['info']=$info;

            $log_flag='N';
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

    /**
     * 修改金额  /tms/settle/updateSettle
     **/
    public function updateSettle(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $price=$request->input('price');
        $now_time = date('Y-m-d H:i:s',time());

//        $self_id     ='settle_202103031642225267443160';
//        $price = '200';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','group_name','company_name','use_flag','receivable_money','practical_money','already_money','gathering_flag'];
        $data['info']=TmsSettle::where($where)->select($select)->first();
        //dd($data);
        if($data['info']){
            $update['update_time'] = $now_time;
            $update['practical_money'] = $price*100;
            TmsSettle::where($where)->update($update);
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="拉取不到数据";
            return $msg;
        }
    }

}
?>
