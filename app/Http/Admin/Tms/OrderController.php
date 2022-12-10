<?php
namespace App\Http\Admin\Tms;
use App\Models\Tms\AppSettingParam;
use App\Models\Tms\TmsCarType;
use App\Models\Tms\TmsLittleOrder;
use App\Models\Tms\TmsOrderCost;
use App\Models\Tms\TmsOrderMoney;
use App\Models\User\UserCapital;
use App\Models\User\UserWallet;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsGroup;
use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsOrder;
use App\Models\Tms\TmsOrderDispatch;
use App\Models\Tms\TmsLine;
use App\Http\Controllers\TmsController as Tms;
class OrderController extends CommonController{

    /***    订单头部      /tms/order/orderList
     */
    public function  orderList(Request $request){
        /** 接收中间件参数**/
        $group_info             = $request->get('group_info');
        $user_info              = $request->get('user_info');
        $order_state_type        =config('tms.3pl_order_state');
        $data['state_info']       =$order_state_type;
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $data['user_info']      = $user_info;
        $abc='';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/车辆导入文件范本.xlsx',
        ];

        /** 抓取可调度的订单**/
        $where['delete_flag'] = 'Y';
        $where['dispatch_flag'] = 'Y';
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsOrderDispatch::where($where)->count(); //总的数据量
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsOrderDispatch::where($where)->count(); //总的数据量
                break;

            case 'more':
                $data['total']=TmsOrderDispatch::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                break;
        }


        foreach ($data['button_info'] as $k => $v){
            if($v->id == '625'){
                $v->name.='（'.$data['total'].'）';
            }
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

//        dd($data);
        return $msg;
    }

    /***    费用明细分页      /tms/order/orderPage
     */
    public function orderPage(Request $request){
        $tms_order_status_type    =array_column(config('tms.tms_order_status_type'),'pay_status_text','key');
        $tms_order_type           =array_column(config('tms.tms_order_type'),'name','key');
        $tms_control_type         =array_column(config('tms.tms_control_type'),'name','key');
        $tms_order_inco_type         =array_column(config('tms.tms_order_inco_type'),'icon','key');
        /** 接收中间件参数**/
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
        $type           =$request->input('type');
        $state          =$request->input('order_status');
        $order_status   =$request->input('status') ?? null;
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'company_id','value'=>$company_id],
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'=','name'=>'order_status','value'=>$state],
        ];


        $where=get_list_where($search);

        $select=['self_id'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsOrder::where($where)->count(); //总的数据量
                $data['items']=TmsOrder::where($where);
//                if ($order_status){
//                    if ($order_status == 1){
//                        $data['items'] = $data['items']->where('order_status',3);
//                    }elseif($order_status == 2){
//                        $data['items'] = $data['items']->whereIn('order_status',[4,5]);
//                    }elseif($order_status == 3){
//                        $data['items'] = $data['items']->where('order_status',6);
//                    }elseif($order_status == 6){
//                        $data['items'] = $data['items']->where('order_status',2);
//                    }else{
//                        $data['items'] = $data['items']->where('order_status',7);
//                    }
//                }
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

        foreach ($button_info as $k => $v){
            if($v->id == 647){
                $button_info1[]=$v;
            }

        }
        foreach ($data['items'] as $k=>$v) {

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
//        $data['tms_order_type']          =config('tms.tms_order_type');
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
        $self_id       = $request->input('self_id');
        $group_code    = $request->input('group_code');
        $company_id    = $request->input('company_id');
        $order_type    = $request->input('order_type');
        $line_id       = $request->input('line_id');
        $pick_flag     = $request->input('pick_flag');
        $send_flag     = $request->input('send_flag');
        $pick_money    = $request->input('pick_money');
        $send_money    = $request->input('send_money');
        $price         = $request->input('price');
        $send_time     = $request->input('send_time') ?? null;
        $gather_time   = $request->input('gather_time') ??null;
        $total_money   = $request->input('total_money');
        $good_name_n   = $request->input('good_name');
        $good_number_n = $request->input('good_number');
        $good_weight_n = $request->input('good_weight');
        $good_volume_n = $request->input('good_volume');
        $dispatcher    = $request->input('dispatcher')??[];
        $clod          = $request->input('clod');
        $more_money    = $request->input('more_money') ? $request->input('more_money') - 0 : null ;
        $car_type      = $request->input('car_type')??''; //车型
        $pay_type      = $request->input('pay_type');
        $remark        = $request->input('remark');
        $app_flag      = $request->input('app_flag');//app上下单   1 是 2 PC下单
        $payer         = $request->input('payer');//付款方：发货人 consignor  收货人receiver
        $kilo          = $request->input('kilometre');
        if (empty($price)){
            $price = $request->input('line_price');
        }


        $rules=[
            'order_type'=>'required',
        ];
        $message=[
            'order_type.required'=>'必须选择',
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




                if (empty($v['good_name'])) {
                    $msg['code'] = 306;
                    $msg['msg'] = '货物名称不能为空！';
                    return $msg;
                }

                if (empty($v['good_number']) || $v['good_number'] <= 0) {
                    $msg['code'] = 307;
                    $msg['msg'] = '货物件数错误！';
                    return $msg;
                }

                if (empty($v['good_weight']) || $v['good_weight'] <= 0) {
                    $msg['code'] = 308;
                    $msg['msg'] = '货物重量错误！';
                    return $msg;
                }

                if (empty($v['good_volume']) || $v['good_volume'] <= 0) {
                    $msg['code'] = 309;
                    $msg['msg'] = '货物体积错误！';
                    return $msg;
                }

                if (empty($v['clod'])) {
                    $msg['code'] = 309;
                    $msg['msg'] = '请选择温度！';
                    return $msg;
                }


            /** 处理一下发货地址  及联系人 结束**/

            /** 开始处理正式的数据*/


                    if($old_info){
                        $data['update_time']=$now_time;
                        $id=TmsOrder::where($wheres)->update($data);
//
                        $operationing->access_cause='修改订单';
                        $operationing->operation_type='update';
                    }else{
                        $data['self_id']            = $order_id;
                        $data['group_code']         = $group_info->group_code;
                        $data['group_name']         = $group_info->group_name;
                        $data['create_user_id']     = $user_info->admin_id;
                        $data['create_user_name']   = $user_info->name;
                        $data['create_time']        = $data['update_time']=$now_time;

                        $id=TmsOrder::insert($data);

                        $operationing->access_cause='新建订单';
                        $operationing->operation_type='create';

                    }

                    $operationing->table_id=$old_info?$self_id:$data['self_id'];
                    $operationing->old_info=$old_info;
                    $operationing->new_info=$data;

                    if($id){
                        $msg['code'] = 200;
                        $msg['order_id'] = $order_id;
                        $msg['order_id_show'] = substr($order_id,15);
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

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$data;
        $operationing->operation_type=$flag;
        DB::beginTransaction();
        try{
            TmsOrder::where('self_id',$self_id)->update($data);
            TmsOrderDispatch::where('order_id',$self_id)->update($data);
            DB::commit();
            $msg['code']=200;
            $msg['msg']='删除成功！';
        }catch(\Exception $e){
            DB::rollBack();
            $msg['code']=301;
            $msg['msg']='删除失败！';
        }

        return $msg;
    }

    /***    拿去订单数据     /tms/order/getOrder
     */
    public function  getOrder(Request $request){
        /** 接收数据*/
        $warehouse_id        =$request->input('warehouse_id');

        /*** 虚拟数据**/
        //$warehouse_id='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['warehouse_id','=',$warehouse_id],
        ];
        $select=['self_id','area','group_code','group_name','warehouse_id','warehouse_name','warm_id','warm_name'];
        //dd($where);
        $data['wms_warehouse_area']=WmsWarehouseArea::where($where)->select($select)->get();
        foreach ($data['wms_warehouse_area'] as $k=>$v) {
            $v->warm_name=warm($v->wmsWarm->warm_name,$v->wmsWarm->min_warm,$v->wmsWarm->max_warm);
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    订单导入     /tms/order/import
     */
    public function import(Request $request){
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
        $warm_id            =$request->input('warm_id');
        $file_id            =$request->input('file_id');
        //dd($input);
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/2020-10-13/车辆导入文件范本.xlsx";
        $input['warm_id']       =$warm_id='warm_202012171029290396683997';
         ***/
        $rules = [
            'warm_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'warm_id.required' => '请选择温区',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {

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

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '车辆' =>['Y','N','64','area'],
            ];
            $ret=arr_check($shuzu,$info_check);

            //dump($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }

            $info_wait=$ret['new_array'];


            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warm_id],
            ];

            $info= WmsWarm::where($where_check)->select('self_id','warm_name','warehouse_id','warehouse_name','group_code','group_name')->first();
            if(empty($info)){
                $msg['code'] = 305;
                $msg['msg'] = '温区不存在';
                return $msg;
            }
            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['area','=',$v['area']],
                    ['warehouse_id','=',$info->warehouse_id],
                ];

                $area_info = WmsWarehouseArea::where($where)->value('group_code');

                if($area_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('area_');
                    $list['area']               = $v['area'];
                    $list['warehouse_id']       = $info->warehouse_id;
                    $list['warehouse_name']     = $info->warehouse_name;
                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        = $list['update_time']=$now_time;
                    $list['warm_id']            = $info->self_id;
                    $list['warm_name']          = $info->warm_name;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
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
            $id= WmsWarehouseArea::insert($datalist);




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

    /***    订单明细详情     /tms/order/details
     */
    public function  details(Request $request,Details $details){
        $tms_money_type    =array_column(config('tms.tms_money_type'),'name','key');
        $tms_order_status_type    =array_column(config('tms.tms_order_status_type'),'pay_status_text','key');
        $tms_order_type           =array_column(config('tms.tms_order_type'),'name','key');
        $tms_control_type        =array_column(config('tms.tms_control_type'),'name','key');
        $self_id=$request->input('self_id');
//        $self_id = 'order_202106231710070766328312';

        $select = ['self_id','group_code','group_name','company_name','create_user_name','create_time','use_flag','order_type','order_status','gather_address_id','gather_contacts_id','gather_name','gather_tel','gather_sheng','gather_shi','gather_qu','gather_time','send_time',
            'gather_address','send_address_id','send_contacts_id','send_name','send_tel','send_sheng','send_shi','send_qu','send_address','remark','total_money','price','pick_money','send_money','good_name','good_number','good_weight','good_volume','pick_flag','send_flag','info'
            ,'good_info','clod','line_info','pay_type','pay_state'];

        $list_select=['self_id','order_type','order_status','company_name','dispatch_flag','group_code','group_name','use_flag','on_line_flag','gather_sheng_name','gather_shi_name','gather_qu_name','gather_address','send_sheng_name','send_shi_name'
            ,'send_qu_name','send_address','total_money','good_info','good_number','good_weight','good_volume','carriage_group_name','on_line_money','line_gather_address_id','line_gather_contacts_id','line_gather_name','line_gather_tel',
            'line_gather_sheng','line_gather_shi','line_gather_qu','line_gather_sheng_name','line_gather_shi_name','line_gather_qu_name' , 'line_gather_address','remark',
            'line_gather_address_longitude','line_gather_address_latitude','line_send_address_id','line_send_contacts_id','line_send_name','line_send_tel', 'line_send_sheng','line_send_shi',
            'line_send_qu','line_send_sheng_name','line_send_shi_name','line_send_qu_name','line_send_address','line_send_address_longitude','line_send_address_latitude','clod','pick_flag','send_flag',
            'pay_type','order_id','pay_status','pay_time','receiver_type','gather_name','gather_tel','send_name','send_tel','receipt_flag','receiver_id'
        ];
        $where = [
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select1=['self_id','create_time','create_time','group_name','dispatch_flag','receiver_id','on_line_flag',
            'gather_sheng_name','gather_shi_name','gather_qu_name','gather_address',
            'send_sheng_name','send_shi_name','send_qu_name','send_address','order_id',
            'good_info','good_number','good_weight','good_volume','total_money','on_line_money'];

        $select2 = ['self_id','carriage_id','order_dispatch_id'];
        $select3 = ['self_id','company_id','company_name','carriage_flag','total_money','carriage_flag'];
        $select4 = ['carriage_id','car_number','contacts','tel','price','car_id'];
        $selectList = ['self_id','receipt','order_id','total_user_id','group_code','group_name'];
        $select5 = ['self_id','tel'];
        $select6 = ['self_id','group_code','group_name','tel'];
        $info = TmsOrder::with(['TmsOrderDispatch' => function($query) use($list_select,$selectList,$select1,$select2,$select3,$select4,$select5,$select6){
            $query->select($list_select);
            $query->with(['tmsCarriageDispatch'=>function($query)use($select1,$select2,$select3,$select4){
                $query->where('delete_flag','=','Y');
                $query->select($select2);
                $query->with(['tmsCarriage'=>function($query)use($select3){
                    $query->where('delete_flag','=','Y');
                    $query->select($select3);
                }]);
                $query->with(['tmsCarriageDriver'=>function($query)use($select4){
                    $query->where('delete_flag','=','Y');
                    $query->select($select4);
                }]);
            }]);
            $query->with(['userTotal'=>function($query)use($select5) {
                $query->where('delete_flag','Y');
                $query->select($select5);
            }]);
            $query->with(['systemGroup'=>function($query)use($select6) {
                $query->where('delete_flag','Y');
                $query->select($select6);
            }]);
            $query->with(['tmsReceipt'=>function($query)use($selectList) {
                $query->where('delete_flag', '=', 'Y');
                $query->select($selectList);
            }]);

        }])->where($where)->select($select)->first();

//        DD($info->toArray());


        if($info){
            $info->order_status_show = $tms_order_status_type[$info->order_status] ?? null;
            $info->order_type_show   = $tms_order_type[$info->order_type] ??null;
            if ($info->pay_state == 'Y' && $info->pay_type == 'offline'){
                $info->pay_state = '已付款';
            }elseif($info->pay_type == 'online'){
                $info->pay_state = '已付款';
            }elseif($info->pay_type == 'offline' && $info->pay_state == 'N'){
                $info->pay_state = '未付款';
            }elseif(!$info->pay_type && $info->pay_state == 'N'){
                $info->pay_state = '未付款';
            }
            $receipt_info = [];
            $receipt_info_list= [];
            foreach ($info->TmsOrderDispatch as $k =>$v){

                $v->pay_type_show = $tms_pay_type[$v->pay_type]??null;
                $v->good_info     = json_decode($v->good_info,true);
                $temperture = json_decode($v->clod,true);
                foreach ($temperture as $key => $value){
                    $temperture[$key] = $tms_control_type[$value];
                }
                $info->receipt_flag = $v->receipt_flag;
                $v->temperture = implode(',',$temperture);
                if ($v->tmsReceipt){
//                    $info->receipt = json_decode($v->tmsReceipt->receipt,true);
                    $receipt_info = img_for($v->tmsReceipt->receipt,'more');
                    $receipt_info_list[] = $receipt_info;

                }
                $car_list = [];
                if ($v->tmsCarriageDispatch){
                    if ($v->tmsCarriageDispatch->tmsCarriageDriver){
                        foreach ($v->tmsCarriageDispatch->tmsCarriageDriver as $kk => $vv){
                            $carList['car_id'] = $vv->car_id;
                            $carList['car_number'] = $vv->car_number;
                            $carList['tel'] = $vv->tel;
                            $carList['contacts'] = $vv->contacts;
                            $car_list[] = $carList;
                        }
                        $info->car_info = $car_list;
                    }
                    if($v->tmsCarriageDispatch['tmsCarriage'][0]['carriage_flag'] == 'carriers'){
                        $carriage_where = [
                            ['type','=','carriers'],
                            ['self_id','=',$v->tmsCarriageDispatch['tmsCarriage'][0]['company_id']]
                        ];
                        $carriage_company = TmsGroup::where($carriage_where)->select('tel','contacts')->first();
                        $carList['car_id']     = '';
                        $carList['car_number'] = '';
                        $carList['tel'] = $carriage_company->tel;
                        $carList['contacts'] = '';
                        $car_list[] = $carList;
                        $info->car_info = $car_list;
                    }
                }
                $order_details11['value'] = '021-59111020';
                if(!empty($v->userTotal)){
                    $order_details11['value'] = $v->userTotal->tel;
                }
                if(!empty($v->systemGroup)){
                    $order_details11['value'] = $v->systemGroup->tel;
                }
            }

            /** 零担发货收货仓**/
            $line_info = [];
            $pick_store_info = [];
            $send_store_info = [];
            if($info->line_info){
                $info->line_info = json_decode($info->line_info,true);
//                dd($info->line_info);
                $pick_store['pick_store'] = $info->line_info['send_sheng_name'].$info->line_info['send_shi_name'].$info->line_info['send_qu_name'].$info->line_info['send_address'];
                $pick_store['contacts']   = $info->line_info['send_name'];
                $pick_store['tel']   = $info->line_info['send_tel'];
                $pick_store_info[] = $pick_store;
                $send_store['send_store'] = $info->line_info['gather_sheng_name'].$info->line_info['gather_shi_name'].$info->line_info['gather_qu_name'].$info->line_info['gather_address'];
                $send_store['contacts']   = $info->line_info['gather_name'];
                $send_store['tel']   = $info->line_info['gather_tel'];
                $send_store_info[] = $send_store;
                $info->shift_number = $info->line_info['shift_number'];
                $info->trunking = $info->line_info['trunking'].'天';
            }
            $info->pick_store_info = $pick_store_info;
            $info->send_store_info = $send_store_info;
            $info->receipt = $receipt_info_list;
            $order_info              = json_decode($info->info,true);
            $send_info = [];
            $gather_info = [];
            foreach ($order_info as $kkk => $vvv){
//                dd($vvv);
                if ($info->pick_flag == 'Y'){
                    $send['address_info'] = $vvv['send_sheng_name'].$vvv['send_shi_name'].$vvv['send_qu_name'];
                    $send['contacts']     = $vvv['send_name'];
                    $send['tel']     = $vvv['send_tel'];
                    $send_info[] =$send;
                }

                if ($info->send_flag == 'Y'){
                    $gather['address_info'] = $vvv['gather_sheng_name'].$vvv['gather_shi_name'].$vvv['gather_qu_name'];
                    $gather['contacts'] = $vvv['gather_name'];
                    $gather['tel']  = $vvv['gather_tel'];
                    $gather['good_number'] = $vvv['good_number'];
                    $gather['good_weight'] = $vvv['good_weight'];
                    $gather['good_volume'] = $vvv['good_volume'];
                    $gather['good_cold']   = $tms_control_type[$vvv['clod']];
                    $gather['good_name']   = $vvv['good_name'];
                    $gather_info[] = $gather;
                }
                $vvv['clod'] =  $tms_control_type[$vvv['clod']];
                if ($info->order_type == 'vehicle' || $info->order_type == 'lift'){
                    $order_info[$kkk]['good_weight'] = ($vvv['good_weight']/1000).'吨';
                }
            }
            $info->info = $order_info;
//            dd($send_info,$gather_info);
            $info->send_info = $send_info;
            $info->gather_info = $gather_info;
            $info->self_id_show = substr($info->self_id,15);
            $info->good_info         = json_decode($info->good_info,true);
            $info->clod              = json_decode($info->clod,true);
            /** 如果需要对数据进行处理，请自行在下面对 $info 进行处理工作*/
            $info->total_money = number_format($info->total_money/100, 2);
            $info->price       = number_format($info->price/100, 2);
            $info->pick_money  = number_format($info->pick_money/100, 2);
            $info->send_money  = number_format($info->send_money/100, 2);
            if ($info->order_type == 'vehicle' || $info->order_type == 'lift'){
                $info->good_weight = ($info->good_weight/1000).'吨';
            }
            $info->color = '#FF7A1A';
            $info->order_id_show = '订单编号'.$info->self_id_show;
            $order_details = [];
            $receipt_list = [];
            $car_info = [];
            $order_details1['name'] = '订单金额';
            $order_details1['value'] = '¥'.$info->total_money;
            $order_details1['color'] = '#FF7A1A';
            $order_details2['name'] = '是否付款';
            $order_details2['value'] = $info->pay_state;
            $order_details2['color'] = '#FF7A1A';

            if($info->order_status == 3){
                $order_details11['name'] = '接单人电话';
                $order_details11['color'] = '#FF7A1A';
            }

            $order_details4['name'] = '收货时间';
            $order_details4['value'] = $info->gather_time;
            $order_details4['color'] = '#000000';
            if ($info->order_type == 'vehicle' || $info->order_type == 'lcl' || $info->order_type == 'lift'){
                $order_details3['name'] = '装车时间';
                $order_details3['value'] = $info->send_time;
                $order_details3['color'] = '#000000';
                $order_details5['name'] = '是否装卸';
                if($info->pick_flag == 'Y'){
                    $pick_flag_show = '需要装货';
                }else{
                    $pick_flag_show = '不需装货';
                }
                if ($info->send_flag == 'Y'){
                    $send_flag_show = '需要卸货';
                }else{
                    $send_flag_show = '不需卸货';
                }
                $order_details5['value'] = $pick_flag_show.' '.$send_flag_show;
                $order_details5['color'] = '#000000';
            }else{
                $order_details3['name'] = '提货时间';
                $order_details3['value'] = $info->send_time;
                $order_details3['color'] = '#000000';
                $order_details5['name'] = '是否提配';
                if($info->pick_flag == 'Y'){
                    $pick_flag_show = '需要提货';
                }else{
                    $pick_flag_show = '不需提货';
                }
                if ($info->send_flag == 'Y'){
                    $send_flag_show = '需要配送';
                }else{
                    $send_flag_show = '不需配送';
                }
                $order_details5['value'] = $pick_flag_show.' '.$send_flag_show;
                $order_details5['color'] = '#000000';
            }
            $order_details6['name'] = '订单备注';
            $order_details6['value'] = $info->remark;
            $order_details6['color'] = '#000000';
            $order_details7['name'] = '班次号';
            $order_details7['value'] = $info->shift_number;
            $order_details7['color'] = '#000000';
            $order_details8['name'] = '时效';
            $order_details8['value'] = $info->trunking;
            $order_details8['color'] = '#000000';

            $order_details9['name'] = '运输信息';
            $order_details9['value'] = $info->car_info;
            if ($info->tmsOrderDispatch[0]["tmsCarriageDispatch"]){
                if($info->tmsOrderDispatch[0]["tmsCarriageDispatch"]->tmsCarriage[0]['carriage_flag'] == 'carriers'){
                    $order_details9['name'] = '调度信息';
                }
            }

            $order_details10['name'] = '回单信息';
            $order_details10['value'] = $info->receipt;

            $order_details[] = $order_details1;
            $order_details[]= $order_details2;

            if($info->order_status == 3){
                $order_details[] = $order_details11;
            }

            if ($info->order_type == 'vehicle' || $info->order_type == 'lcl' || $info->order_type == 'lift'){
                $order_details[] = $order_details3;
                $order_details[]= $order_details4;
                $order_details[]= $order_details5;
                $order_details[]= $order_details6;
            }else{
                $order_details[]= $order_details7;
                $order_details[]= $order_details8;
                $order_details[]= $order_details3;
                $order_details[]= $order_details4;
                $order_details[]= $order_details5;
                $order_details[]= $order_details6;
            }
            if(!empty($info->car_info)){
                $car_info[] = $order_details9;
            }
            if (!empty($info->receipt)){
                $receipt_list[] = $order_details10;
            }
//            dd($info->toArray());
            $data['info']=$info;
            $data['order_details'] = $order_details;
            $data['receipt_list'] = $receipt_list;
            $data['car_info'] = $car_info;
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
    确认完成（内部订单） /tms/order/orderDone
     **/
    public function orderDone(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input         = $request->all();
        $now_time    = date('Y-m-d H:i:s',time());
        $self_id     = $request->input('self_id');
        $rules = [
            'self_id'=>'required',
        ];
        $message = [
            'self_id.required'=>'请选择订单',
        ];
        /**虚拟数据
        $input['self_id']       = $self_id       = 'order_202102251709515094516145';
         **/

        $validator = Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $where = [
                ['self_id','=',$self_id],
                ['order_status','!=',7]
            ];
            $select = ['self_id','order_status','total_money'];
            $order = TmsOrder::where($where)->select($select)->first();
            if ($order->order_status != 5){
                $msg['code'] = 301;
                $msg['msg'] = '请等待司机确认！';
                return $msg;
            }
            if ($order->order_status == 6){
                $msg['code'] = 301;
                $msg['msg'] = '订单已完成';
                return $msg;
            }
            $update['update_time'] = $now_time;
            $update['order_status'] = 6;
            $id = TmsOrder::where($where)->update($update);

            /** 查找所有的运输单 修改运输状态**/
            $TmsOrderDispatch = TmsOrderDispatch::where('order_id',$self_id)->select('self_id')->get();
            if ($TmsOrderDispatch){
                $dispatch_list = array_column($TmsOrderDispatch->toArray(),'self_id');
                $orderStatus = TmsOrderDispatch::where('delete_flag','=','Y')->whereIn('self_id',$dispatch_list)->update($update);

                /*** 订单完成后，如果订单是在线支付，添加运费到承接司机或3pl公司余额 **/
                if ($orderStatus){
//                    if ($order->pay_type == 'online'){
//                        dd($dispatch_list);
                    foreach ($dispatch_list as $key => $value){

                        $carriage_order = TmsOrderDispatch::where('self_id','=',$value)->first();
                        $idit = substr($carriage_order->receiver_id,0,5);
                        if ($idit == 'user_'){
                            $wallet_where = [
                                ['total_user_id','=',$carriage_order->receiver_id]
                            ];
                            $data['wallet_type'] = 'user';
                            $data['total_user_id'] = $carriage_order->receiver_id;
                        }else{
                            $wallet_where = [
                                ['group_code','=',$carriage_order->receiver_id]
                            ];
                            $data['wallet_type'] = '3PLTMS';
                            $data['group_code'] = $carriage_order->receiver_id;
                        }
                        $wallet = UserCapital::where($wallet_where)->select(['self_id','money','wait_money'])->first();

                        $money['money'] = $wallet->money + $carriage_order->on_line_money;
                        $money['wait_money'] = $wallet->wait_money - $carriage_order->on_line_money;
                        $data['money'] = $carriage_order->on_line_money;
                        if ($carriage_order->group_code == $carriage_order->receiver_id){
                            $money['money'] = $wallet->money + $carriage_order->total_money;
                            $money['wait_money'] = $wallet->wait_money - $carriage_order->total_money;
                            $data['money'] = $carriage_order->total_money;
                        }

                        $money['update_time'] = $now_time;
                        UserCapital::where($wallet_where)->update($money);
                    }

                }
            }

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
            $erro = $validator->errors()->all();
            $msg['code'] = 300;
            $msg['msg']  = null;
            foreach ($erro as $k => $v) {
                $kk = $k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }
    }


}
?>
