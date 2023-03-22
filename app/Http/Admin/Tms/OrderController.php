<?php
namespace App\Http\Admin\Tms;
use App\Http\Controllers\FileController as File;
use App\Models\Group\SystemUser;
use App\Models\Tms\AppSettingParam;
use App\Models\Tms\OrderLog;
use App\Models\Tms\TmsCar;
use App\Models\Tms\TmsGroup;
use App\Models\Tms\TmsMoney;
use App\Models\Tms\TmsReceipt;
use App\Models\Tms\TmsWages;
use App\Models\Tms\TmsWares;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsOrder;


use App\Http\Controllers\TmsController as Tms;
class OrderController extends CommonController{

    /***    订单头部      /tms/order/orderList
     */
    public function  orderList(Request $request){
        /** 接收中间件参数**/
        $group_info             = $request->get('group_info');
        $user_info              = $request->get('user_info');
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $data['user_info']      = $user_info;
        $abc='';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/订单导入.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

//        dd($data);
        return $msg;
    }

    /***    订单列表     /tms/order/orderPage
     */
    public function orderPage(Request $request){
        /** 接收中间件参数**/
        $order_type    =array_column(config('tms.order_type'),'name','key');
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $user_info     = $request->get('user_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $buttonInfo     = $request->get('buttonInfo');

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $company_id     =$request->input('company_id');
        $state          =$request->input('order_status');
        $order_status   =$request->input('status') ?? null;
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $enter_time     =$request->input('enter_time');
        $leave_time     =$request->input('leave_time');
        $odd_number     =$request->input('odd_number');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'company_id','value'=>$company_id],
            ['type'=>'=','name'=>'order_status','value'=>$state],
            ['type'=>'=','name'=>'create_time','value'=>$start_time],
            ['type'=>'=','name'=>'create_time','value'=>$end_time],
            ['type'=>'=','name'=>'enter_time','value'=>$enter_time],
            ['type'=>'=','name'=>'leave_time','value'=>$leave_time],
            ['type'=>'=','name'=>'odd_number','value'=>$odd_number],
        ];


        $where=get_list_where($search);

        $select=['self_id','company_id','company_name','create_user_id','create_user_name','create_time','update_time','delete_flag','use_flag','group_code',
            'order_status','send_time','send_id','send_name','gather_time','gather_name','gather_id','total_money','good_name','more_money','price',
            'price','remark','enter_time','leave_time','order_weight','real_weight','upload_weight','different_weight','bill_flag','payment_state','order_number','odd_number',
            'car_number','car_id','car_conact','car_tel','company_id','company_name','ordertypes','escort','escort_name','order_type','transport_type','area','order_mark'
            ,'road_card','escort_name','pack_type','pick_time'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsOrder::where($where)->count(); //总的数据量
                $data['items']=TmsOrder::where($where);
                if ($order_status){
                    if ($order_status == 1){
                        $data['items'] = $data['items']->where('order_status',3);
                    }elseif($order_status == 2){
                        $data['items'] = $data['items']->whereIn('order_status',[4,5]);
                    }elseif($order_status == 3){
                        $data['items'] = $data['items']->where('order_status',6);
                    }elseif($order_status == 6){
                        $data['items'] = $data['items']->where('order_status',2);
                    }else{
                        $data['items'] = $data['items']->where('order_status',7);
                    }
                }
                $data['items'] = $data['items']
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsOrder::where($where)->count(); //总的数据量
                $data['items']=TmsOrder::where($where);
                $data['items'] = $data['items']
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsOrder::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsOrder::where($where);

                $data['items'] = $data['items']
                    ->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($button_info as $k => $v){
            if($v->id == 83){
                $button_info1[] = $v;
                $button_info3[] = $v;
            }
            if($v->id == 84){
                $button_info2[] = $v;
                $button_info3[] = $v;
                $button_info4[] = $v;
            }
            if($v->id == 124){
                $button_info4[] = $v;
            }

        }

        foreach ($data['items'] as $k=>$v) {
            $v->order_type_show=$order_type[$v->order_status]??null;
//            $v->button_info = $button_info;
            if ($v->order_status == 1){
                $v->button_info = $button_info3;
            }
            if (in_array($v->order_status,[2,3,4,5,6])){
                $v->button_info = $button_info4;
            }
        }

//        dd($data['items']);
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    新建订单     /tms/order/createOrder
     */
    public function createOrder(Request $request){
        /** 接收数据*/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id'];
        $detail = TmsOrder::where($where)->select($select)->first();
        if ($detail) {


        }
        $data['info']= $detail;
        $msg['user'] = $user_info->type;
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        // dd($msg);
        return $msg;


    }


    /***    新建订单数据提交      /tms/order/addOrder
     */
    public function addOrder(Request $request,Tms $tms){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_order';

        $operationing->access_cause     ='创建/修改订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

//        /** 接收数据*/
        $self_id                   = $request->input('self_id');
        $order_type                = $request->input('order_type');// 1硫磺一队  2硫磺二队 3危废队
        $group_code                = $request->input('group_code');//
        $send_id                   = $request->input('send_id');//装车点ID
        $send_name                 = $request->input('send_name');//装车点
        $gather_id                 = $request->input('gather_id');//卸车点ID
        $gather_name               = $request->input('gather_name');//卸车点
        $send_time                 = $request->input('send_time');//发货时间
        $gather_time               = $request->input('gather_time');//交货时间
        $good_name                 = $request->input('good_name');//货物品名/危废名称
        $order_number              = $request->input('order_number');//订单编码
        $enter_time                = $request->input('enter_time');//进厂时间
        $leave_time                = $request->input('leave_time');//出厂时间
        $order_weight              = $request->input('order_weight');//装货吨位
        $real_weight               = $request->input('real_weight');//实际提货量
        $upload_weight             = $request->input('upload_weight');//卸货吨位
        $pack_type                 = $request->input('pack_type');//运输方式
        $different_weight          = $request->input('different_weight');//装卸货量差
        $bill_flag                 = $request->input('bill_flag');//开票状态
        $payment_state             = $request->input('payment_state');//结算状态
        $odd_number                = $request->input('odd_number');//运单号
        $remark                    = $request->input('remark');//备注
        $car_num                   = $request->input('car_num');//车数
        $sale_price                = $request->input('sale_price');//单价
        $total_money               = $request->input('total_money');//运费总额
        $driver_id                 = $request->input('driver_id');//驾驶员ID
        $user_name                 = $request->input('user_name');//驾驶员名称
        $escort                    = $request->input('escort');//押运员ID
        $escort_name               = $request->input('escort_name');//押运员名称
        $trailer_num               = $request->input('trailer_num');//挂车号
        $car_id                    = $request->input('car_id');//车牌号
        $car_number                = $request->input('car_number');//车牌号
        $company_id                = $request->input('company_id');//所属组织
        $company_name              = $request->input('company_name');//所属组织
        $ordertypes                = $request->input('ordertypes');//
        $area                      = $request->input('area');//区域
        $transport_type            = $request->input('transport_type');//运输方式
        $order_mark                = $request->input('order_mark');//标识
        $road_card                 = $request->input('road_card');//路卡
        $carriage_group            = $request->input('carriage_group');//承运商组别
        $carriage_id               = $request->input('carriage_id');//委托单位
        $carriage_name             = $request->input('carriage_name');//委托单位
        $car_tel                   = $request->input('car_tel');//电话
        $pick_time                 = $request->input('pick_time');//提货时间段


        $rules=[
            'car_number'=>'required',
            'good_name'=>'required',
        ];
        $message=[
            'car_number.required'=>'请填写车牌号',
            'good_name.required'=>'请填写货物品名',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            /***开始做二次效验**/
            $where_group=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code],
            ];
            $group_info    =SystemGroup::where($where_group)->select('group_code','group_name')->first();
            if(empty($group_info)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

//            if (empty($good_name)) {
//                $msg['code'] = 306;
//                $msg['msg'] = '货物品名不能为空！';
//                return $msg;
//            }

            /** 处理一下发货地址  及联系人 结束**/

            /** 开始处理正式的数据*/
            $data['send_id']                 = $send_id;
            $data['send_name']               = $send_name;
            $data['gather_id']               = $gather_id;
            $data['gather_name']             = $gather_name;
            $data['good_name']               = $good_name;
            $data['total_money']             = $total_money;
            $data['enter_time']              = $enter_time;
            $data['leave_time']              = $leave_time;
            $data['order_weight']            = $order_weight;
            $data['real_weight']             = $real_weight;
            $data['upload_weight']           = $upload_weight;
            $data['different_weight']        = $different_weight;
            $data['bill_flag']               = $bill_flag;
            $data['payment_state']           = $payment_state;
            $data['odd_number']              = $odd_number;
            $data['remark']                  = $remark;
            $data['car_num']                 = $car_num;
            $data['sale_price']              = $sale_price;
            $data['driver_id']               = $driver_id;
            $data['user_name']               = $user_name;
            $data['escort']                  = $escort;
            $data['escort_name']             = $escort_name;
            $data['trailer_num']             = $trailer_num;
            $data['car_id']                  = $car_id;
            $data['car_number']              = $car_number;
            $data['company_id']              = $company_id;
            $data['company_name']            = $company_name;
            $data['ordertypes']              = $ordertypes;
            $data['order_type']              = $order_type;
            $data['send_time']               = $send_time;
            $data['gather_time']             = $gather_time;
            $data['order_number']            = $order_number;
            $data['leave_time']              = $leave_time;
            $data['transport_type']          = $transport_type;
            $data['area']                    = $area;
            $data['order_mark']              = $order_mark;
            $data['road_card']               = $road_card;
            $data['carriage_group']          = $carriage_group;
            $data['carriage_id']             = $carriage_id;
            $data['carriage_name']           = $carriage_name;
            $data['car_tel']                 = $car_tel;
            $data['pick_time']               = $pick_time;
            $data['pack_type']               = $pack_type;

            $old_info = TmsOrder::where('self_id',$self_id)->first();


            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsOrder::where('self_id',$self_id)->update($data);
                $operationing->access_cause='修改订单';
                $operationing->operation_type='update';
            }else{
                $data['self_id']            = generate_id('order_');
                $data['group_code']         = $group_info->group_code;
                $data['group_name']         = $group_info->group_name;
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['create_time']        = $data['update_time']=$now_time;

                $id=TmsOrder::insert($data);

//                /**保存费用**/
//                $money['self_id']                = generate_id('money_');
//                $money['pay_type']               = 'freight';
//                $money['money']                  = $total_money;
//                $money['pay_state']              = 'Y';
//                $money['order_id']               = $data['self_id'];
//                $money['process_state']          = 'Y';
//                $money['type_state']             = 'in';
//                $money['company_id']             = $company_id;
//                $money['company_name']           = $company_name;
//                $money['group_code']             = $group_code;
////                $money['group_name']             = $group_name;
//                $money['create_user_id']         = $user_info->admin_id;
//                $money['create_user_name']       = $user_info->name;
//                $money['create_time']            = $money['update_time'] = $now_time;
//                TmsMoney::insert($money);

                /***生成工资表**/
//                if ($car_id){
//                    $wages['self_id']      = generate_id('wages_');
//                    $wages['order_id']     = $data['self_id'];
//                    $wages['car_id']       = $car_id;
//                    $wages['car_number']   = $car_number;
//                    $wages['driver_id']    = $driver_id;
//                    $wages['driver_name']  = $user_name;
//                    $wages['social_flag']  = $social_flag;
//                    $wages['date']         = $enter_time;
//                    $wages['escort']       = $escort;
//                    $wages['goodsname']    = $good_name;
//                    $wages['pick_weight']  = $real_weight;
//                    $wages['unload_weight']= $upload_weight;
//                    $wages['total_money']  = $total_money;
//                    $wages['remark']       = $remark;
//                    TmsWages::insert($wages);
//
//                    $payment['self_id']                = generate_id('money_');
//                    $payment['pay_type']               = 'salary';
//                    $payment['money']                  = $total_money;
//                    $payment['pay_state']              = 'Y';
//                    $payment['order_id']               = $data['self_id'];
//                    $payment['process_state']          = 'Y';
//                    $payment['type_state']             = 'out';
//                    $payment['car_id']                 = $car_id;
//                    $payment['car_number']             = $car_number;
//                    $payment['user_id']                = $driver_id;
//                    $payment['user_name']              = $user_name;
//                    $payment['group_code']             = $group_code;
//                    $payment['create_user_id']         = $user_info->admin_id;
//                    $payment['create_user_name']       = $user_info->name;
//                    $payment['create_time']            = $payment['update_time'] = $now_time;
//                    TmsMoney::insert($payment);
//
//                }


                $operationing->access_cause='新建订单';
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

            /***二次效验结束**/
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
     * 调度  tms/order/dispatchOrder
     * */
    public function dispatchOrder(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_order';

        $operationing->access_cause     ='调度订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;
        $operationing->type             ='update';
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

//        /** 接收数据*/
        $order_id                  = $request->input('order_id');//订单ID
        $car_number                = $request->input('car_number');//车牌号
        $car_id                    = $request->input('car_id');//车辆ID
        $driver_id                 = $request->input('driver_id');//驾驶员
        $driver_name               = $request->input('driver_name');//驾驶员
        $escort                    = $request->input('escort');//押运员
        $social_flag               = $request->input('social_flag');//是否有社保
        $car_conact                = $request->input('car_conact');//联系人
        $car_tel                   = $request->input('car_tel');//联系方式


        $rules=[
            'order_id'=>'required',
            'car_number'=>'required',

        ];
        $message=[
            'order_id.required'=>'请选择要调度的订单',
            'car_number.required'=>'请选择车辆',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $data['car_number']               = $car_number;
            $data['car_id']                   = $car_id;
            $data['car_conact']               = $car_conact;
            $data['car_tel']                  = $car_tel;
            $data['order_status']             = 2;
            $data['driver_id']                = $driver_id;
            $data['user_name']                = $driver_name;
            $data['escort']                   = $escort;
            $data['create_time']              = $data['update_time'] = $now_time;
            $old_info = TmsOrder::where('self_id',$order_id)
                ->select('self_id','order_status','gather_shi_name','send_shi_name','odd_number','enter_time','real_weight','upload_weight','price','total_money')
                ->first();

            $id = TmsOrder::where('self_id',$order_id)->update($data);
            $order_log['self_id'] = generate_id('log_');
            $order_log['info'] = '调度运单:'.'预约单号'.$old_info->odd_number.','.'车牌号：'.$data['car_number'];
            $order_log['create_time'] = $order_log['update_time'] = $now_time;
            $order_log['order_id']    = $order_id;
            $order_log['state']       = 2;
            $order_log['group_code']    = $user_info->group_code;
            $order_log['group_name']    = $user_info->group_name;
            $order_log['create_user_id']       = $user_info->admin_id;
            $order_log['create_user_name']     = $user_info->admin_name;
            OrderLog::insert($order_log);

            $money['car_id']     = $car_id;
            $money['car_number'] = $car_number;
            $money['update_time'] = $now_time;
            $money['user_name'] = $car_conact;
            TmsMoney::where('order_id',$order_id)->update($money);
            /*** 保存工资表**/
            $tms_wages = TmsWages::where('order_id',$order_id)->first();
            if ($car_id &&$tms_wages){
                $wages['order_id']     = $order_id;
                $wages['car_id']       = $car_id;
                $wages['car_number']   = $car_number;
                $wages['driver_id']    = $driver_id;
                $wages['driver_name']  = $driver_name;
                $wages['social_flag']  = $social_flag;
                $wages['date']         = $old_info->enter_time;
                $wages['escort']       = $escort;
                $wages['goodsname']    = $old_info->goods_name;
                $wages['pick_weight']  = $old_info->real_weight;
                $wages['unload_weight']= $old_info->upload_weight;
                $wages['price']        = $old_info->price;
                $wages['total_money']  = $old_info->total_money;
                $wages['remark']       = $old_info->remark;
                TmsWages::insert($wages);

            }
            $operationing->access_cause='调度订单';
            $operationing->operation_type='update';
            $operationing->table_id=$old_info?$order_id:$order_id;
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
     * 装货
     * */
    public function pickOrder(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_order';

        $operationing->access_cause     ='调度订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;
        $operationing->type             ='update';
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

//        /** 接收数据*/
        $order_id                  = $request->input('order_id');//订单ID
        $real_weight               = $request->input('real_weight');//实际装货量


        $rules=[
            'order_id'=>'required',
            'real_weight'=>'required',
        ];
        $message=[
            'order_id.required'=>'请选择要调度的订单',
            'real_weight.required'=>'请填写实际装货量',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {


            $data['real_weight']              = $real_weight;
            $data['order_status']             = 3;
            $data['create_time']              = $data['update_time'] = $now_time;
            $old_info = TmsOrder::where('self_id',$order_id)->select('self_id','odd_number','car_number','order_status','gather_shi_name','send_shi_name','real_weight')->first();

            $id = TmsOrder::where('self_id',$order_id)->update($data);
            $order_log['self_id'] = generate_id('log_');
            $order_log['info'] = '装货:'.'预约单号'.$old_info->odd_number.','.'车牌号：'.$old_info->car_number;
            $order_log['create_time'] = $order_log['update_time'] = $now_time;
            $order_log['order_id']    = $order_id;
            $order_log['state']       = 3;
            $order_log['group_code']    = $user_info->group_code;
            $order_log['group_name']    = $user_info->group_name;
            $order_log['create_user_id']       = $user_info->admin_id;
            $order_log['create_user_name']     = $user_info->admin_name;
            OrderLog::insert($order_log);

            $operationing->access_cause='装货,预约单号：'.$old_info->odd_number;
            $operationing->operation_type='update';
            $operationing->table_id=$old_info?$order_id:$data['self_id'];
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
     * 卸货
     * */
    public function upOrder(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_order';

        $operationing->access_cause     ='调度订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;
        $operationing->type             ='update';
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

//        /** 接收数据*/
        $order_id                  = $request->input('order_id');//订单ID
        $upload_weight               = $request->input('upload_weight');//实际卸货量


        $rules=[
            'order_id'=>'required',
            'upload_weight'=>'required',
        ];
        $message=[
            'order_id.required'=>'请选择要调度的订单',
            'upload_weight.required'=>'请填写实际卸货量',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $old_info = TmsOrder::where('self_id',$order_id)->select('self_id','odd_number','car_number','order_status','gather_shi_name','send_shi_name','upload_weight','real_weight')->first();

            $data['upload_weight']              = $upload_weight;
            $data['order_status']               = 5;
            $data['different_weight']           = $old_info->real_weight - $upload_weight;
            $data['create_time']                = $data['update_time'] = $now_time;
            $id = TmsOrder::where('self_id',$order_id)->update($data);
            $order_log['self_id'] = generate_id('log_');
            $order_log['info'] = '卸货:'.'预约单号'.$old_info->odd_number.','.'车牌号：'.$old_info->car_number;
            $order_log['create_time'] = $order_log['update_time'] = $now_time;
            $order_log['order_id']    = $order_id;
            $order_log['state']       = 6;
            $order_log['group_code']    = $user_info->group_code;
            $order_log['group_name']    = $user_info->group_name;
            $order_log['create_user_id']       = $user_info->admin_id;
            $order_log['create_user_name']     = $user_info->admin_name;
            OrderLog::insert($order_log);

            $operationing->access_cause='卸货,预约单号：'.$old_info->odd_number;
            $operationing->operation_type='update';
            $operationing->table_id=$old_info?$order_id:$data['self_id'];
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
     * 上传回单  /tms/order/uploadReceipt
     * */
    public function uploadReceipt(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_receipt';

        $operationing->access_cause     ='上传回单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $order_id            =$request->input('order_id');
        $receipt             =$request->input('receipt');


        /*** 虚拟数据
        $input['order_id']           =$order_id='dispatch_202103191711247168432433';
        $input['receipt']              =$receipt=[['url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2021-03-20/829b89fa038d26bc6af59a76f16794c5.jpg','width'=>'','height'=>'']];
         **/
        $rules=[
            'order_id'=>'required',
            'receipt'=>'required',
        ];
        $message=[
            'order_id.required'=>'请选择运输单',
            'receipt.required'=>'请选择要上传的回单',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$order_id],
            ];
            $select=['self_id','create_time','create_time','group_name','gather_sheng_name','gather_shi_name','gather_qu_name',
                'gather_address','order_status','send_sheng_name','send_shi_name','send_qu_name','send_address','total_money'];
            $wait_info=TmsOrder::where($where)->select($select)->first();
            if(!in_array($wait_info->order_status,[5,6])){
                $msg['code']=301;
                $msg['msg']='请确认订单已送达';
                return $msg;
            }
            $data['self_id'] = generate_id('receipt_');
            $data['receipt'] = img_for($receipt,'in');
            $data['order_id'] = $order_id;
            $data['create_time'] = $data['update_time'] = $now_time;
            $data['group_code']  = $user_info->group_code;
            $data['group_name']  = $user_info->group_name;

            $id=TmsReceipt::insert($data);

            $order_update['receipt_flag'] = 'Y';
            $order_update['update_time']  = $now_time;
            TmsOrder::where($where)->update($order_update);
            $operationing->old_info = (object)$wait_info;
            $operationing->table_id = $order_id;
            $operationing->new_info=$data;

            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "上传成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "上传失败";
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
     * 获取日志记录
     * */
    public function getOrderLog(Request $request){
        /** 接收数据*/
        $order_type    =array_column(config('tms.order_log_type'),'name','key');
        $self_id=$request->input('order_id');
//        $self_id = 'car_20210313180835367958101';

        $where=[
            ['delete_flag','=','Y'],
            ['order_id','=',$self_id],
        ];

        $select = ['self_id','order_id','info','state','create_time','create_user_id','create_user_name','group_code',
           ];
        $data['info']=OrderLog::where($where)->select($select)->get();

        if ($data['info']){
            foreach($data['info'] as $k =>$v){
                $v->type_show=$order_type[$v->state]??null;
            }
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;
    }


    /***    订单删除     /tms/order/orderDelFlag
     */
    public function orderDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_order';
        $medol_name='TmsOrder';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='car_202012242220439016797353';
        $old_info = TmsOrder::where('self_id',$self_id)->select('self_id','order_status','delete_flag')->first();
        $data['delete_flag'] = 'N';
        $data['update_time'] = $now_time;

        DB::beginTransaction();
        try{
            TmsOrder::where('self_id',$self_id)->update($data);
            DB::commit();
            $msg['code']=200;
            $msg['msg']='删除成功！';
        }catch(\Exception $e){
            DB::rollBack();
            $msg['code']=301;
            $msg['msg']='删除失败！';
        }

        return $msg;

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$data;
        $operationing->operation_type=$flag;
    }


    /***    订单导入     /tms/order/import
     */
    public function import(Request $request,Tms $tms){
        $table_name         ='wms_warehouse_area';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数


        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code          =$request->input('group_code');
        $file_id            =$request->input('file_id');
        //dd($input);
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/2020-10-13/车辆导入文件范本.xlsx";
         ***/
        $rules = [
            'importurl' => 'required',
        ];
        $message = [
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code],
            ];

            $info= SystemGroup::where($where_check)->select('self_id','group_code','group_name')->first();
            if(empty($info)){
                $msg['code'] = 305;
                $msg['msg'] = '所属公司不存在';
                return $msg;
            }

            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
            if (!file_exists($importurl)) {
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }

            $res = Excel::toArray((new Import),$importurl);
            //dump($res);
            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }


            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '标识' =>['Y','N','100','order_number'],
                '所属组织' =>['Y','N','100','group_name'],
                '货物品名' =>['Y','N','64','good_name'],
                '承运人' =>['Y','N','64','company_name'],
                '车牌号' =>['Y','N','64','car_number'],
                '挂车号' =>['Y','N','64','trailer_num'],
                '驾驶员' =>['N','N','64','user_name'],
                '副驾驶员' =>['N','N','64','escort'],
                '发货日期' =>['Y','N','64','enter_time'],
                '装车点' =>['Y','N','100','send_address'],
                '卸车点' =>['Y','N','100','gather_address'],
            ];

            $ret=arr_check($shuzu,$info_check);

            //dump($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }

            $info_wait=$ret['new_array'];

            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;
            $order_log_list = [];
            $money_list = [];
            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['order_number','=',$v['order_number']],
                ];

                $area_info = TmsOrder::where($where)->value('order_number');

                if($area_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行预约单号已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }


                $company = TmsGroup::where('type','check')->where('company_name',$v['company_name'])->where('group_code',$group_code)->select('self_id','company_name','use_flag','delete_flag')->first();
                $car = TmsCar::where('car_number',$v['car_number'])->select('self_id','car_number')->first();
                if ($v['user_name']){
                    $driver = SystemUser::where('type','driver')->where('name',$v['user_name'])->where('group_code',$group_code)->select('self_id','name','use_flag','delete_flag','social_flag')->first();
                    if (!$driver){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }
                if($v['escort']){
                    $cargo = SystemUser::where('type','cargo')->where('name',$v['escort'])->where('group_code',$group_code)->select('self_id','name','use_flag','delete_flag','social_flag')->first();
                    if (!$cargo){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行副驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }

                if (!$company){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行承运人不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                if (!$car){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                if ($v['enter_time']){
                    if (is_numeric($v['enter_time'])){
                        $v['enter_time']              = gmdate('Y-m-d',($v['enter_time'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['enter_time'])) == $v['enter_time']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行发货日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                $list=[];
                $order_log=[];
                $money = [];
                if($cando =='Y'){

                    $list['self_id']                 = generate_id('order_');
                    $list['order_number']            = $v['order_number'];
                    $list['send_address']            = $v['send_address'];
                    $list['gather_address']          = $v['gather_address'];
                    $list['good_name']               = $v['good_name'];
                    $list['enter_time']              = $v['enter_time'];
                    $list['car_id']                  = $car->self_id;
                    $list['car_number']              = $car->car_number;
                    $list['trailer_num']             = $v['trailer_num'];
                    if ($v['user_name']){
                        $list['driver_id']               = $driver->self_id;
                        $list['user_name']               = $driver->name;
                    }
                    if($v['escort']){
                        $list['escort']                  = $cargo->self_id;
                    }

                    $list['company_id']              = $company->self_id;
                    $list['company_name']            = $company->company_name;
                    $list['group_code']              = $info->group_code;
                    $list['group_name']              = $info->group_name;
                    $list['create_user_id']          = $user_info->admin_id;
                    $list['create_user_name']        = $user_info->name;
                    $list['create_time']             = $list['update_time']=$now_time;
                    $list['file_id']                 = $file_id;

                    $datalist[]=$list;

                    $order_log['self_id'] = generate_id('log_');
                    $order_log['info'] = '创建运单:'.','.'标识：'.$list['order_number'];
                    $order_log['create_time'] = $order_log['update_time'] = $now_time;
                    $order_log['order_id']    = $list['self_id'];
                    $order_log['group_code']    = $info->group_code;
                    $order_log['group_name']    = $info->group_name;
                    $order_log['create_user_id']       = $user_info->admin_id;
                    $order_log['create_user_name']     = $user_info->admin_name;

                    $order_log_list[] = $order_log;


                    /**保存费用**/
//                    $money['self_id']                = generate_id('money');
//                    $money['pay_type']               = 'freight';
//                    $money['money']                  = $list['total_money'];
//                    $money['pay_state']              = 'Y';
//                    $money['order_id']               = $list['self_id'];
//                    $money['process_state']          = 'Y';
//                    $money['type_state']             = 'in';
//                    $money['group_code']             = $group_code;
////                $money['group_name']             = $group_name;
//                    $money['create_user_id']         = $user_info->admin_id;
//                    $money['create_user_name']       = $user_info->name;
//                    $money['create_time']            = $money['update_time'] = $now_time;
//                    $money['company_id']             = $company->self_id;
//                    $money['company_name']           = $company->company_name;
//
//                    $money_list[] = $money;

                    /**生成工资***/
//                    $wages['self_id']      = generate_id('wages_');
//                    $wages['order_id']     = $list['self_id'];
//                    $wages['car_id']       = $car->self_id;
//                    $wages['car_number']   = $car->car_number;
//                    $wages['driver_id']    = $driver->self_id;
//                    $wages['driver_name']  = $driver->name;
//                    $wages['social_flag']  = $driver->social_flag;
//                    $wages['date']         = $v['enter_time'];
//                    $wages['escort']       = $cargo->self_id;
//                    $wages['goodsname']    = $v['good_name'];
//                    $wages['pick_weight']  = $v['real_weight'];
//                    $wages['unload_weight']= $v['upload_weight'];
//                    $wages['price']        = $v['price'];
//                    $wages['total_money']  = $v['total_money'];
//                    $wages['remark']       = $v['remark'];
//
//                    $wages_list[] = $wages;


//                    $payment['self_id']                = generate_id('money');
//                    $payment['pay_type']               = 'salary';
//                    $payment['money']                  = $v['total_money'];
//                    $payment['pay_state']              = 'Y';
//                    $payment['order_id']               = $list['self_id'];
//                    $payment['process_state']          = 'Y';
//                    $payment['type_state']             = 'out';
//                    $payment['car_id']                 = $car->self_id;
//                    $payment['car_number']             = $car->car_number;
//                    $payment['user_id']                = $car->self_id;
//                    $payment['user_name']              = $car->name;
//                    $payment['group_code']             = $group_code;
//                    $payment['create_user_id']         = $user_info->admin_id;
//                    $payment['create_user_name']       = $user_info->name;
//                    $payment['create_time']            = $payment['update_time'] = $now_time;
//                    $payment_list[] = $payment;

                }

                $a++;
            }


            $operationing->new_info=$datalist;

            //dump($operationing);

            // dd($datalist);

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= TmsOrder::insert($datalist);
            OrderLog::insert($order_log_list);
//            TmsMoney::insert($money_list);
//            TmsMoney::insert($payment_list);
//            TmsWages::insert($wages_list);

            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=307;
                $msg['msg']='操作失败';
                return $msg;
            }
        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }


    }

    /**
     * 订单导出
     * */
    public function excel(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
//        $group_code  =$input['group_code']   ='group_202012251449437824125582';
        //dd($group_code);
        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择公司',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /** 下面开始执行导出逻辑**/
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            //查询条件
            $search=[
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];
            $where=get_list_where($search);

            $select=['self_id','company_id','company_name','create_user_id','create_user_name','create_time','update_time','delete_flag','use_flag','group_code',
                'order_status','send_time','send_name','send_tel','send_sheng','send_shi','send_qu','send_sheng_name','send_shi_name','send_qu_name','send_address',
                'send_address_longitude','send_address_latitude','gather_time','gather_name','gather_tel','gather_sheng','gather_shi','gather_qu','gather_sheng_name',
                'gather_shi_name','gather_qu_name','gather_address','gather_address_longitude','gather_address_latitude','total_money','good_name','more_money','price',
                'price','remark','enter_time','leave_time','order_weight','real_weight','upload_weight','different_weight','bill_flag','payment_state','order_number','odd_number',
                'car_number','car_id','car_conact','car_tel','user_name','trailer_num','sale_price','car_num','escort'];
            $select1 = ['self_id','parame_name'];
            $info=TmsOrder::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "order_number"=>'标识',
                    "odd_number" =>'预约单号',
                    "send_adress"=>'装货点',
                    "gather_address"=>'卸货点',
                    "car_number"=>'车牌号',
                    "trailer_num"=>'挂车号',
                    "user_name"=>'驾驶员',
                    "escort"=>'押运员',
                    "good_name"=>'货物名称',
                    "sale_price"=>'单价',
                    "car_num"=>'车数',
                    "real_weight"=>'装货数量',
                    "upload_weight"=>'卸货数量',
                    "company_name"=>'托运人',
                    "enter_time"=>'日期',
                    "price"=>'运费',
                    "more_money"=>'其他费用',
                    "total_money"=>'总运费',
                    "remark"=>'备注'
                ]];
                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];

                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['order_number']        = $v['order_number'];
                    $list['send_address']        = $v['send_address'];
                    $list['gather_address']      = $v['gather_address'];
                    $list['odd_number']          = $v['odd_number'];
                    $list['good_name']           = $v['good_name'] ;
                    $list['real_weight']         = $v['real_weight'];
                    $list['upload_weight']       = $v['upload_weight'];
                    $list['enter_time']          = $v['enter_time'];
                    $list['price']               = $v['price'];
                    $list['more_money']          = $v['more_money'];
                    $list['total_money']         = $v['total_money'];
                    $list['car_number']          = $v['car_number'];
                    $list['trailer_num']         = $v['trailer_num'];
                    $list['user_name']           = $v['user_name'];
                    $cargo = SystemUser::where('self_id',$v['escort'])->select('self_id','name','use_flag','delete_flag','social_flag')->first();
                    if($cargo){
                        $list['escort']              = $cargo->name;
                    }else{
                        $list['escort']              = null;
                    }
                    $list['company_name']        = $v['company_name'];
                    $list['sale_price']          = $v['sale_price'];
                    $list['car_num']             = $v['car_num'];
                    $list['remark']              = $v['remark'];

                    $data_execl[]=$list;
                }
                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);

                //dd($msg);
                return $msg;

            }else{
                $msg['code']=301;
                $msg['msg']="没有数据可以导出";
                return $msg;
            }
        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code']=300;
            return $msg;
        }
    }

    /***    订单明细详情     /tms/order/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
//        $self_id = 'order_202106231710070766328312';

        $select = ['self_id','company_id','company_name','create_user_id','create_user_name','create_time','update_time','delete_flag','use_flag','group_code',
            'order_status','send_time','send_name','send_tel','send_sheng','send_shi','send_qu','send_sheng_name','send_shi_name','send_qu_name','send_address',
            'send_address_longitude','send_address_latitude','gather_time','gather_name','gather_tel','gather_sheng','gather_shi','gather_qu','gather_sheng_name',
            'gather_shi_name','gather_qu_name','gather_address','gather_address_longitude','gather_address_latitude','total_money','good_name','more_money','price','receipt_flag',
            'price','remark','enter_time','leave_time','order_weight','real_weight','upload_weight','different_weight','bill_flag','payment_state','order_number','odd_number',
            'car_number','car_id','car_conact','car_tel','car_num','sale_price','driver_id','user_name','escort','trailer_num','ordertypes'];
        $select1 = ['self_id','receipt','order_id','total_user_id','group_code','group_name'];
        $select2 = ['self_id','name','type'];
        $where = [
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $info = TmsOrder::with(['tmsReceipt'=>function($query)use($select1,$select2){
            $query->where('delete_flag','=','Y');
            $query->select($select1);
        }])
            ->with(['systemuser'=>function($query)use($select2){
                $query->where('delete_flag','=','Y');
                $query->select($select2);
            }])->where($where)->select($select)->first();

        if($info){
            $order_type    =array_column(config('tms.order_type'),'name','key');
            /** 如果需要对数据进行处理，请自行在下面对 $info 进行处理工作*/
            $info->total_money = $info->total_money;
            $info->price       = $info->price;
            $info->order_type_show = $order_type[$info->order_status]??null;
            if ($info->tmsReceipt){
                $receipt_info = img_for($info->tmsReceipt->receipt,'more');
                $info->receipt = $receipt_info;
            }
            if ($info->systemuser){
                $info->escort = $info->systemuser->name;
            }

            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;
            $data['info']=$info;

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
     *取消订单(3pl)  /tms/order/orderCancel
     * */
    public function orderCancel(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_order';
//        dd($user_info);
        $operationing->access_cause     ='取消订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();

        /** 接收数据*/
        $order_id         = $request->input('order_id'); //调度单ID
        /*** 虚拟数据
        $input['order_id']     =$order_id='order_202105081609390186653744';
         * ***/
        $rules=[
            'order_id'=>'required',
        ];
        $message=[
            'order_id.required'=>'请选择要取消的订单',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            //二次验证
            $order = TmsOrder::where('self_id',$order_id)->select(['self_id','order_status','total_money','pay_type','group_code','total_user_id'])->first();
            if($user_info->group_code != '1234'){
                if ($order->order_status == 3){
                    $msg['code'] = 303;
                    $msg['msg'] = '该订单已被承接，取消请联系客服';
                    return $msg;
                }
            }

            if ($order->order_status == 6 ){
                $msg['code'] = 304;
                $msg['msg'] = '此订单已完成不可以取消';
                return $msg;
            }
            if ($order->order_status == 7 ){
                $msg['code'] = 305;
                $msg['msg'] = '此订单已取消';
                return $msg;
            }
            /** 修改订单状态为已取消 **/
            $order_update['order_status'] = 7;
            $order_update['update_time'] = $now_time;
            $id = TmsOrder::where('self_id',$order_id)->update($order_update);
            /*** 修改可调度订单为已取消**/

            /** 判断是在线支付还是货到付款,在线支付应退还支付费用**/

            /** 取消订单应该删除应付费用**/



            /** 订单如果被承接应通知承运方订单已取消 **/

            $operationing->old_info = (object)$order;
            $operationing->table_id = $order_id;
            $operationing->new_info=$order_update;

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
    确认完成 /tms/order/orderDone
     **/
    public function orderDone(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_order';

        $operationing->access_cause     ='调度订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;
        $operationing->type             ='update';
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

//        /** 接收数据*/
        $order_id                  = $request->input('order_id');//订单ID


        $rules=[
            'order_id'=>'required',
        ];
        $message=[
            'order_id.required'=>'请选择要调度的订单',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $old_info = TmsOrder::where('self_id',$order_id)->select('self_id','odd_number','order_status','gather_shi_name','send_shi_name','upload_weight','real_weight','car_number')->first();

            $data['order_status']               = 6;
            $data['create_time']                = $data['update_time'] = $now_time;
            $id = TmsOrder::where('self_id',$order_id)->update($data);
            $order_log['self_id'] = generate_id('log_');
            $order_log['info'] = '签收:'.'预约单号'.$old_info->odd_number.','.'车牌号：'.$old_info->car_number;
            $order_log['create_time'] = $order_log['update_time'] = $now_time;
            $order_log['order_id']    = $order_id;
            $order_log['state']       = 7;
            $order_log['group_code']    = $user_info->group_code;
            $order_log['group_name']    = $user_info->group_name;
            $order_log['create_user_id']       = $user_info->admin_id;
            $order_log['create_user_name']          = $user_info->admin_name;
            OrderLog::insert($order_log);

            $operationing->access_cause='签收,预约单号：'.$old_info->odd_number;
            $operationing->operation_type='update';
            $operationing->table_id=$old_info?$order_id:$order_id;
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
