<?php
namespace App\Http\Admin\Wms;
use App\Models\Tms\TmsMoney;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsMoney;
use Illuminate\Support\Facades\Validator;

class MoneyController extends CommonController{

    /***    费用明细列表头部      /wms/money/moneyList
     */
    public function  moneyList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['type'] = config('tms.money_type');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    费用明细分页      /wms/money/moneyPage
     */
    public function moneyPage(Request $request){
        $money_type_show    =array_column(config('tms.money_type'),'name','key');

        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_number');
        $user_name      =$request->input('user_name');
        $type           =$request->input('pay_type');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $type_state     =$request->input('type_state');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'pay_type','value'=>$type],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'user_name','value'=>$user_name],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<','name'=>'create_time','value'=>$end_time],
            ['type'=>'=','name'=>'type_state','value'=>$type_state],
        ];


        $where=get_list_where($search);

        $select=['self_id','pay_type','money','create_time','update_time','create_user_id','create_user_name','group_code','group_name',
            'delete_flag','use_flag','pay_state','car_id','car_number','user_id','user_name','process_state','type_state','before_money'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsMoney::where($where)->count(); //总的数据量
                $data['items']=TmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsMoney::where($where)->count(); //总的数据量
                $data['items']=TmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($button_info as $k => $v){
            if($v->id == 99){
                $button_info1[] = $v;
                $button_info3[] = $v;
            }
            if($v->id == 174){
                $button_info2[] = $v;
                $button_info3[] = $v;
            }

        }
        foreach ($data['items'] as $k=>$v) {
            $v->pay_type=$money_type_show[$v->pay_type]??null;
//            $v->button_info=$button_info;
            if ($v->pay_state == 'N'){
                $v->button_info=$button_info3;
            }
            if ($v->pay_state == 'Y'){
                $v->button_info=$button_info4;
            }

        }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    public function createMoney(Request $request){
        $data['type'] = config('tms.money_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['info']=TmsMoney::where($where)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    费用明细详情     /wms/money/details
     */
    public function  details(Request $request,Details $details){
        $wms_money_type_show    =array_column(config('tms.money_type'),'name','key');
        $self_id=$request->input('self_id');
        //$self_id='money_202012231738203885359374';
        $table_name='wms_money';
        $select=['self_id','type','group_name','warehouse_name','create_user_name','create_time','use_flag','company_name',
            'payee_affirm_flag','payee_user_name','payee_time','payment_affirm_flag','payment_user_name','payment_time','settle_flag','settle_id','money','before_money'];


        $list_select=['self_id','area','row','column','tier','external_sku_id','good_name','good_english_name','spec','good_unit','good_target_unit','good_scale','production_date','expire_time','num',
            'create_user_name','create_time','use_flag','money_id'];

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];


        $info=WmsMoney::with(['WmsMoneyList' => function($query) use($list_select){
            $query->select($list_select);
        }])->where($where)->select($select)->first();
        //DD($info->toArray());



        if($info){


			foreach ($info->WmsMoneyList as $k=>$v){
				$v->sign                =$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
				$v->good_describe      =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->num);
			}


            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->total_show=$wms_money_type_show[$info->type];
            $info->money = number_format($info->money/100, 2);
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



    /***    付款方确认    /wms/money/paymentCheck
     */
    public function  paymentCheck(Request $request){
        $self_id=$request->input('self_id');
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_money';

        $operationing->access_cause     ='付款方确认';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $old_info=WmsMoney::where($where)->first();

        if($old_info){

            $datt['payment_affirm_flag']='Y';
            $datt['payment_user_id']=$user_info->admin_id;
            $datt['payment_user_name']=$user_info->name;
            $datt['payment_time']=$now_time;


            $id=WmsMoney::where($where)->update($datt);
            if($id){

                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$datt;

                $msg['code'] = 200;
                $msg['msg'] = "付款方确认成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "付款方确认失败";
                return $msg;
            }
        }else{
            $msg['code']=301;
            $msg['msg']="拉取不到数据";
            return $msg;
        }

    }


    /***    收款方确认     /wms/money/payeeCheck
     */
    public function  payeeCheck(Request $request){
        $self_id=$request->input('self_id');
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_money';

        $operationing->access_cause     ='收款方确认';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $old_info=WmsMoney::where($where)->first();

        if($old_info){

            $datt['payee_affirm_flag']='Y';
            $datt['payee_user_id']=$user_info->admin_id;
            $datt['payee_user_name']=$user_info->name;
            $datt['payee_time']=$now_time;


            $id=WmsMoney::where($where)->update($datt);
            if($id){

                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$datt;

                $msg['code'] = 200;
                $msg['msg'] = "收款方确认成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "收款方确认失败";
                return $msg;
            }
        }else{
            $msg['code']=301;
            $msg['msg']="拉取不到数据";
            return $msg;
        }
    }

    /**
     * 添加费用  wms/money/addMoney
     * */
    public function addMoney(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='erp_shop_goods_sku';

        $operationing->access_cause     ='创建/修改商品';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $pay_type           =$request->input('pay_type');//费用类型
        $money              =$request->input('money');//费用
        $pay_state          =$request->input('pay_state');//结算状态
        $car_id             =$request->input('car_id');//车辆ID
        $car_number         =$request->input('car_number');//车牌号
        $user_id            =$request->input('user_id');//人员ID
        $user_name          =$request->input('user_name');//人员名称
        $process_state      =$request->input('process_state');//审核状态
        $create_time        =$request->input('create_time');//审核状态
        $type_state        =$request->input('type_state');//审核状态


        $rules=[
            'pay_type'=>'required',
            'money'=>'required',
        ];
        $message=[
            'pay_type.required'=>'请选择费用类型',
            'money.required'=>'请填写费用',
        ];
        $validator=Validator::make($input,$rules,$message);

        //操作的表

        if($validator->passes()){

            $data['pay_type']           = $pay_type;
            $data['money']              = $money;
            $data['pay_state']          = $pay_state;
            $data['car_id']             = $car_id;//规格
            $data['car_number']         = $car_number;
            $data['user_id']            = $user_id;
            $data['user_name']          = $user_name;
            $data['process_state']      = $process_state;
            $data['type_state']         = $type_state;


            $wheres['self_id'] = $self_id;
            $old_info=TmsMoney::where($wheres)->first();

            if($old_info){
                $data['update_time']        = $now_time;
                $id=TmsMoney::where($wheres)->update($data);

                $operationing->access_cause='修改费用';
                $operationing->operation_type='update';


            }else{
                $data['self_id']            = generate_id('money_');
                $data['group_code']         = $user_info->group_code;
                $data['group_name']         = $user_info->group_name;
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                if ($create_time){
                    $data['create_time']=$data['update_time']=$create_time;
                }else{
                    $data['create_time']=$data['update_time']=$now_time;
                }

                $id=TmsMoney::insert($data);
                $operationing->access_cause='新建费用';
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

    /**
     * 费用审核
     * */
    public function moneyState(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_money';

        $operationing->access_cause     ='创建/修改商品';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $process_state      =$request->input('process_state');//审核状态

        $rules=[
            'self_id'=>'required',
            'process_state'=>'required',

        ];
        $message=[
            'self_id.required'=>'请选择费用条目！',
            'process_state.required'=>'请选择审核结果',
        ];
        $validator=Validator::make($input,$rules,$message);

        //操作的表
        if($validator->passes()){
            $wheres['self_id'] = $self_id;
            $old_info=TmsMoney::where($wheres)->first();

            $data['process_state'] = $process_state;
            $data['update_time']   = $now_time;
            $id = TmsMoney::where('self_id',$self_id)->update($data);

            $operationing->access_cause='费用审核';
            $operationing->operation_type='create';
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

    /**
     *修改价格差异 wms/money/updateMoney
     * */
    public function updateMoney(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_money';

        $operationing->access_cause     ='创建/修改商品';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $money              =$request->input('money');//修改金额

        $rules=[
            'self_id'=>'required',

        ];
        $message=[
            'self_id.required'=>'请选择费用条目！',
        ];
        $validator=Validator::make($input,$rules,$message);

        //操作的表
        if($validator->passes()){
            $wheres['self_id'] = $self_id;
            $old_info=TmsMoney::where($wheres)->first();
            if ($old_info->pay_state == 'Y'){
                $msg['code'] = 303;
                $msg['msg'] = "费用已结算，不可修改！";
                return $msg;
            }
            $data['before_money'] = $old_info->money;
            $data['money'] = $money;
            $data['update_time']   = $now_time;
            $id = TmsMoney::where('self_id',$self_id)->update($data);

            $operationing->access_cause='修改费用';
            $operationing->operation_type='create';
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

}
?>
