<?php
namespace App\Http\Admin\Wms;
use App\Models\Group\SystemUser;
use App\Models\Tms\TmsMoney;
use App\Models\Tms\TmsOrder;
use App\Models\Tms\TmsMoneyCount;
use App\Models\Tms\TmsCostMoney;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
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
        $trailer_num    =$request->input('trailer_num');
        $user_name      =$request->input('user_name');
        $type           =$request->input('pay_type');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $type_state     =$request->input('type_state');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'pay_type','value'=>$type],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'trailer_num','value'=>$trailer_num],
            ['type'=>'like','name'=>'user_name','value'=>$user_name],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<=','name'=>'create_time','value'=>$end_time],
            ['type'=>'=','name'=>'type_state','value'=>$type_state],
        ];


        $where=get_list_where($search);

        $select=['self_id','pay_type','money','create_time','update_time','create_user_id','create_user_name','group_code','group_name','trailer_num',
            'delete_flag','use_flag','pay_state','car_id','car_number','user_id','user_name','process_state','type_state','before_money','bill_flag','receipt'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsMoney::where($where)->count(); //总的数据量
                $data['items']=TmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['info']=TmsMoney::where($where)->where('use_flag','Y')->select('pay_type',DB::raw('sum(money) as price'))->groupBy('pay_type')->get();
                $data['cost']=TmsMoney::where($where)->where('use_flag','Y')->select('type_state',DB::raw('sum(money) as total_price'))->groupBy('type_state')->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsMoney::where($where)->count(); //总的数据量
                $data['items']=TmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['info']=TmsMoney::where($where)->where('use_flag','Y')->select('pay_type',DB::raw('sum(money) as price'))->groupBy('pay_type')->get();
                $data['cost']=TmsMoney::where($where)->where('use_flag','Y')->select('type_state',DB::raw('sum(money) as total_price'))->groupBy('type_state')->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['info']=TmsMoney::where($where)->where('use_flag','Y')->whereIn('group_code',$group_info['group_code'])->select('pay_type',DB::raw('sum(money) as price'))->groupBy('pay_type')->get();
                $data['cost']=TmsMoney::where($where)->where('use_flag','Y')->whereIn('group_code',$group_info['group_code'])
                    ->select('type_state',DB::raw('sum(money) as total_price'))->groupBy('type_state')->get();
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
               
            }

        }
        foreach ($data['items'] as $k=>$v) {
            $v->pay_type=$money_type_show[$v->pay_type]??null;
            $v->button_info=$button_info;
            if ($v->use_flag == 'N'){
                $v->button_info=$button_info2;
            }else{
                $v->button_info=$button_info1;
            }

        }
        foreach ($data['info'] as $k=>$v) {
            $v->pay_type=$money_type_show[$v->pay_type]??null;
        }
        $in = $out = 0;
        foreach ($data['cost'] as $k=>$v) {
            if ($v->type_state == 'in'){
                $in = $v->total_price;
            }
            if ($v->type_state == 'out'){
                $out = $v->total_price;
            }
        }
        $data['diff_price'] = $in-$out;
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
        $table_name='tms_money';
        $select=['self_id','pay_type','money','create_time','update_time','create_user_id','create_user_name','group_code','group_name','approver','submit_connect','trailer_num',
            'delete_flag','use_flag','pay_state','car_id','car_number','user_id','user_name','process_state','type_state','before_money','bill_flag','receipt'];

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $info=TmsMoney::where($where)->select($select)->first();


        if($info){
            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->money = number_format($info->money, 2);
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
        $table_name     ='tms_money';

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
        $type_state         =$request->input('type_state');//审核状态
        $approver           =$request->input('approver');//审批人
        $submit_connect     =$request->input('submit_connect');//报销内容


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
            $data['approver']           = $approver;
            $data['submit_connect']     = $submit_connect;


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
            
        ];
        $message=[
            'self_id.required'=>'请选择费用条目！',
        ];
        $validator=Validator::make($input,$rules,$message);

        //操作的表
        if($validator->passes()){
            $wheres['self_id'] = $self_id;
            $old_info=TmsMoney::where($wheres)->first();
            if($old_info->use_flag == 'N'){
                $msg['code'] = 303;
                $msg['msg'] = "费用已作废，不可修改！";
                return $msg;
            
            }

            $data['use_flag'] = 'N';
            $data['update_time']   = $now_time;
            $id = TmsMoney::where('self_id',$self_id)->update($data);

            $operationing->access_cause='费用作废';
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

    /**
     * 应收账款列表
     * */
    public function countMoney(Request $request){
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
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $type_state     =$request->input('type_state');
        $bill_falg     =$request->input('bill_flag');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'pay_type','value'=>'freight'],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'user_name','value'=>$user_name],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<','name'=>'create_time','value'=>$end_time],
            ['type'=>'=','name'=>'type_state','value'=>$type_state],
            ['type'=>'=','name'=>'bill_flag','value'=>$bill_falg],
        ];


        $where=get_list_where($search);

        $select=['self_id','pay_type','money','create_time','update_time','create_user_id','create_user_name','group_code','group_name','bill_flag','receipt',
            'delete_flag','use_flag','pay_state','car_id','car_number','user_id','user_name','process_state','type_state','before_money','company_id','company_name'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsMoney::where($where)->count(); //总的数据量
                $data['items']=TmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['info']=TmsMoney::where($where)->select('pay_type',DB::raw('sum(money) as price'))->groupBy('pay_type')->get();
                $data['cost']=TmsMoney::where($where)->select('type_state',DB::raw('sum(money) as total_price'))->groupBy('type_state')->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsMoney::where($where)->count(); //总的数据量
                $data['items']=TmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['info']=TmsMoney::where($where)->select('pay_type',DB::raw('sum(money) as price'))->groupBy('pay_type')->get();
                $data['cost']=TmsMoney::where($where)->select('type_state',DB::raw('sum(money) as total_price'))->groupBy('type_state')->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['info']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])->select('pay_type',DB::raw('sum(money) as price'))->groupBy('pay_type')->get();
                $data['cost']=TmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select('type_state',DB::raw('sum(money) as total_price'))->groupBy('type_state')->get();
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        $button_info5=[];
        $button_info6=[];
        foreach ($button_info as $k => $v){
            if($v->id == 99){
                $button_info1[] = $v;
                $button_info3[] = $v;
                $button_info4[] = $v;
            }
            if($v->id == 174){
                $button_info2[] = $v;
                $button_info3[] = $v;
            }
            if($v->id == 174){
                $button_info4[] = $v;
                $button_info5[] = $v;
            }

        }
        foreach ($data['items'] as $k=>$v) {
            $v->pay_type=$money_type_show[$v->pay_type]??null;
            $v->button_info=$button_info;
//            if ($v->pay_state == 'N'){
//                $v->button_info=$button_info3;
//            }else{
//                $v->button_info=$button_info4;
//            }
//            if($v->bill_flag == 'N'){
//                $v->button_info=$button_info5;
//            }
            $v->receipt = img_for($v->receipt,'more');

        }
        foreach ($data['info'] as $k=>$v) {
            $v->pay_type=$money_type_show[$v->pay_type]??null;
        }
        $in = $out = 0;
        foreach ($data['cost'] as $k=>$v) {
            if ($v->type_state == 'in'){
                $in = $v->total_price;
            }
            if ($v->type_state == 'out'){
                $out = $v->total_price;
            }
        }
        $data['diff_price'] = $in-$out;
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }
    //应收账款列表头部
    public function settleList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['type'] = config('tms.money_type');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    //应收账款列表
    public function settlePage(Request $request){
        $money_type_show    =array_column(config('tms.money_type'),'name','key');

        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $type           =$request->input('pay_type');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $type_state     =$request->input('type_state');
        $carriage_id     =$request->input('carriage_id');
        $carriage_name     =$request->input('carriage_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'carriage_id','value'=>$carriage_id],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<','name'=>'create_time','value'=>$end_time],
            ['type'=>'like','name'=>'carriage_name','value'=>$carriage_name],
        ];


        $where=get_list_where($search);

        $select=['self_id','total_money','money','create_time','update_time','create_user_id','create_user_name','group_code','group_name','order_id',
            'delete_flag','use_flag','pay_state','carriage_id','carriage_name','receive_money','settle_money','bill_time','bill_flag','receipt'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsMoneyCount::where($where)->count(); //总的数据量
                $data['items']=TmsMoneyCount::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['total_money']=TmsMoneyCount::where($where)->sum('total_money');
                $data['receive_money']=TmsMoneyCount::where($where)->sum('receive_money');
                $data['settle_money']=TmsMoneyCount::where($where)->sum('settle_money');
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsMoneyCount::where($where)->count(); //总的数据量
                $data['items']=TmsMoneyCount::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['total_money']=TmsMoneyCount::where($where)->sum('total_money');
                $data['receive_money']=TmsMoneyCount::where($where)->sum('receive_money');
                $data['settle_money']=TmsMoneyCount::where($where)->sum('settle_money');
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsMoneyCount::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsMoneyCount::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['total_money']=TmsMoneyCount::where($where)->sum('total_money');
                $data['receive_money']=TmsMoneyCount::where($where)->sum('receive_money');
                $data['settle_money']=TmsMoneyCount::where($where)->sum('settle_money');
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($button_info as $k => $v){
            if($v->id == 202){
                $button_info1[] = $v;
                $button_info3[] = $v;
            }
            if($v->id == 203){
                $button_info2[] = $v;
                $button_info3[] = $v;
            }
            if($v->id == 204){
                $button_info2[] = $v;
                $button_info3[] = $v;
            }

        }
        foreach ($data['items'] as $k=>$v) {
            $v->pay_type=$money_type_show[$v->pay_type]??null;
            $v->button_info=$button_info;
            if ($v->bill_flag == 'N'){
                $v->button_info=$button_info3;
            }else{
                $v->button_info=$button_info2;
            }
            $v->receipt       =img_for($v->receipt,'more');
        }
        
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    //修改应收金额
    public function updateSettle(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_money';

        $operationing->access_cause     ='创建/修改应收金额';
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
            $old_info=TmsMoneyCount::where($wheres)->first();

            if($money < $old_info->receive_money){
                $msg['code'] = 302;
                $msg['msg'] = "应收费用不能小于已收费用";
                return $msg;
            }
            
            $data['total_money']   = $money;
            $data['settle_money']  = $money - $old_info->receive_money;
            $data['update_time']   = $now_time;
            $id = TmsMoneyCount::where('self_id',$self_id)->update($data);

            $operationing->access_cause='修改应收金额';
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

    //修改已收金额
    public function updateReceive(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_money';

        $operationing->access_cause     ='创建/修改已收金额';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $receive_money      =$request->input('receive_money');//已收费用
        $settle_money       =$request->input('settle_money');//未收费用
        $receive_time      =$request->input('receive_time');//收款时间

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
            $old_info=TmsMoneyCount::where($wheres)->first();
            
            $data['receive_money'] = $receive_money;
            $data['settle_money']  = $settle_money;
            $data['update_time']   = $now_time;
            $id = TmsMoneyCount::where('self_id',$self_id)->update($data);

            $cost_money['receive_money']  = $receive_money;
            $cost_money['receive_time']   = $receive_time;
            $cost_money['self_id']        = generate_id('cost_');
            $cost_money['group_code']     = $old_info->group_code;
            $cost_money['group_name']     = $old_info->group_name;
            $cost_money['create_user_id']     = $user_info->admin_id;
            $cost_money['create_user_name']     = $user_info->admin_name;
            $cost_money['create_time']    = $cost_money['update_time'] = $now_time;

            $operationing->access_cause='修改已收金额';
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

    //获取收款明细
    public function getCostMoney(Request $request){
         $money_id=$request->input('money_id');
        
//        $input['group_code'] =  $group_code = '1234';
       
        $select = ['self_id','receive_money','receive_time','group_name','group_code','use_flag','delete_flag','money_id'];
        $data['info']=TmsCostMoney::whereIn('money_id',$money_id)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    //应收账款上传发票
    public function upReceipt(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_money_count';

        $operationing->access_cause     ='创建/修改商品';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $receipt            =$request->input('receipt');//修改金额
        $bill_time          =$request->input('bill_time');//开票时间

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
            $old_info=TmsMoneyCount::where($wheres)->first();

            // if ($old_info->bill_flag == 'Y'){
            //     $msg['code'] = 303;
            //     $msg['msg'] = "已上传发票请勿重复操作！";
            //     return $msg;
            // }
            $data['bill_flag'] = 'Y';
            $data['receipt'] = img_for($receipt,'in');
            $data['bill_time'] = $bill_time;
            $data['update_time']   = $now_time;
            $id = TmsMoneyCount::where('self_id',$self_id)->update($data);

            $operationing->access_cause='上传发票';
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

     //获取结算订单明细
    public function getSettleOrder(Request $request){
        $order_id=$request->input('order_id');
        
//        $input['group_code'] =  $group_code = '1234';
       
        $select = ['self_id','company_id','company_name','create_user_id','create_user_name','create_time','update_time','delete_flag','use_flag','group_code','id','settle_flag',
            'order_status','send_time','send_id','send_name','gather_time','gather_name','gather_id','total_money','good_name','more_money','price','trailer_num',
            'price','remark','enter_time','leave_time','order_weight','real_weight','upload_weight','different_weight','bill_flag','payment_state','order_number','odd_number',
            'car_number','car_id','car_conact','car_tel','company_id','company_name','ordertypes','escort','escort_name','order_type','transport_type','area','order_mark'
            ,'road_card','escort_name','pack_type','pick_time','user_name','escort_tel','carriage_id','carriage_name','order_mark','sale_price'];
        $data['info']=TmsOrder::whereIn('self_id',explode(',',$order_id))->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }


    /**
     * 生成工资
     * */
    public function countWages(Request $request){
        //查询员工基本工资和当月奖金
        $self_id = $request->input('self_id');
        $userInfo = SystemUser::where('self_id',$self_id)->select('self_id','type','salary','safe_reward','social_flag')->first();
        $type = $userInfo->type;
        $deduct = 0;//初始化提成费用
        switch ($type){
            case 'driver':
                //查询当月运送订单量 计算提成
                break;
            case 'cargo':
                break;
            case 'dr_cargo':
                break;
            case 'manager':
                break;
        }
        //查询当月是否有奖励返还
        $data['salary'] = 0;//基本工资
        $data['reward'] = 0;//奖金
        $data['reward_back'] = 0;//奖励返还
        $data['deduct'] = 0;//提成
        $msg['code'] = 200;
        $msg['msg'] = '';
        $msg['data'] = $data;
        return $msg;

    }


}
?>
