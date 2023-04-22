<?php
namespace App\Http\Admin\Tms;

use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsMoney;
use App\Models\Tms\TmsTrye;
use App\Models\Tms\TmsTryeCount;
use App\Models\Tms\TmsTryeList;
use App\Models\Tms\TryeOutList;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\WmschangeController as Change;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsTryeChange;
use App\Models\Tms\TmsCarType;
use App\Http\Controllers\FileController as File;


class TryeController extends CommonController{

    /***    车辆类型列表头部      /tms/trye/tryeList
     */
    public function  tryeList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $type                   = $request->input('type');

        $abc='车辆类型';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/轮胎出入库导入.xlsx',
        ];
        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($data['button_info'] as $k => $v){
            if($v->id == 142){
                $button_info1[]=$v;
            }
            if($v->id == 143){
                $button_info2[]=$v;
            }
            if($v->id == 169){
                $button_info2[]=$v;
            }
            if($v->id == 199){
                $button_info2[]=$v;

            }
            if($v->id == 215){
                $button_info1[]=$v;
            }

        }
        if($type == 'in'){
            $data['button_info'] = $button_info1;
        }else{
            $data['button_info'] = $button_info2;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    车辆类型分页      /tms/trye/tryePage
     */
    public function tryePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $type           =$request->input('type');
        $model          =$request->input('model');
        $car_number     =$request->input('car_number');
        $trailer_num    =$request->input('trailer_num');
        $supplier       =$request->input('supplier');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $trye_name      =$request->input('trye_name');
        $state          =$request->input('state');
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
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'=','name'=>'model','value'=>$model],
            ['type'=>'=','name'=>'supplier','value'=>$supplier],
            ['type'=>'=','name'=>'state','value'=>$state],
            ['type'=>'=','name'=>'trye_name','value'=>$trye_name],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'trailer_num','value'=>$trailer_num],
            ['type'=>'>=','name'=>'in_time','value'=>$start_time],
            ['type'=>'<=','name'=>'in_time','value'=>$end_time],
        ];


        $where=get_list_where($search);

        $select=['self_id','car_number','price','model','model','supplier','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag','state','user_id','trailer_num','remark','trye_name'];
        $select1=['self_id','kilo','price','trye_img','change','order_id','model','num','trye_num','change','create_user_name','create_time','group_code','use_flag'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsTrye::where($where)->count(); //总的数据量
                $data['items']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
                    $query->where('delete_flag','Y');
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsTrye::where($where)->count(); //总的数据量
                $data['items']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
                    $query->where('delete_flag','Y');
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsTrye::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
                    $query->where('delete_flag','Y');
                    $query->select($select1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
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
            if($v->id == 144){
                $button_info1[]=$v;
                
            }
            if($v->id == 145){
                $button_info1[]=$v;
                $button_info3[]=$v;
            }
            if($v->id == 146){
                $button_info2[]=$v;
                
            }
            if($v->id == 147){
                $button_info2[]=$v;
                $button_info4[]=$v;
            }
        }
        foreach ($data['items'] as $k=>$v) {
            
            $v->button_info=$button_info;
            if($v->type == 'in'){
                if ($v->state == 'N') {
                    $v->button_info=$button_info1;
                }else{
                    $v->button_info=$button_info3;
                }
            }else{
                if ($v->state == 'N') {
                    $v->button_info=$button_info2;
                }else{
                    $v->button_info=$button_info4;
                }
            }

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    新建车辆类型    /tms/trye/createTrye
     */
    public function createTrye(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','car_number','price','car_number','model','supplier','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag','state','user_id','trailer_num','remark','trye_name'];
        $select1=['self_id','kilo','price','trye_img','change','order_id','model','num','trye_num','change','create_user_name','create_time','group_code','use_flag','trye_name'];
        $data['info']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
            $query->where('delete_flag','Y');
             $query->select($select1);
        }])->where($where)->select($select)->first();
        if($data['info']){

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    车辆类型数据提交      /tms/trye/addTrye
     */
    public function addTrye(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='创建/修改车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
    
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $car_id             =$request->input('car_id');//车牌号
        $car_number         =$request->input('car_number');//车牌号
        $model              =$request->input('model');//型号
        $num                =$request->input('num');//数量
        $trye_num           =$request->input('trye_num');//编号
        $operator           =$request->input('operator');//经办人
        $type               =$request->input('type');//类型：in入库  out出库
        $in_time            =$request->input('in_time');//时间
        $driver_name        =$request->input('driver_name');//驾驶员
        $change             =$request->input('change');//更换位置
        $trye_list          =$request->input('trye_list');//更换位置

        /**
         $trye_list = [[
            trye_num=>'',//轮胎编号
            model=>'',//型号
            num=>2,//数量
            kilo_num=>'25062',//里程数
            change=>'',//更换位置
            trye_img=>'',//图片
          ]]
         * */
        $rules=[
            'num'=>'required',
        ];
        $message=[
            'num.required'=>'数量必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            switch ($type){
                case 'in':
                    $data['type']        =$type;
                    $data['model']       =$model;
                    $data['num']         =$num;
                    break;
                case 'out':
                    $data['type']              =$type;
                    $data['car_number']        =$car_number;
                    $data['num']               =$num;
                    $data['change']            =$change;
                    $data['driver_name']       =$driver_name;
                    $data['trye_list']         =$trye_list;
                    break;
                default:
                    break;
            }

            $data['trye_num']          =$trye_num;
            $data['in_time']           =$in_time;
            $data['operator']          =$operator;

            $count['order_id'] = $self_id;
            $count['model'] = $model;
            $count['inital_num'] = $num;
            $count['change_num'] = $num;
            $count['now_num'] = $num;
            $count['create_user_id']     =$user_info->admin_id;
            $count['create_user_name']   =$user_info->name;
            $count['create_time']        =$count['update_time']=$now_time;
            $count['group_code']         = $user_info->group_code;
            $count['group_name']         = $user_info->group_name;

            $wheres['self_id'] = $self_id;
            $old_info=TmsTrye::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsTrye::where($wheres)->update($data);
                $operationing->access_cause='修改入库';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('trye_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $user_info->group_code;
                $data['group_name']         = $user_info->group_name;
                $id=TmsTrye::insert($data);
                if ($type == 'in'){
                    TmsTryeCount::insert($count);
                }

                $operationing->access_cause='新建入库';
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

    //轮胎出库变更表
    public static function tryeChange($datalist,$type){
        $data=[];
        $payment = [];
        foreach ($datalist as $k => $v){
        
            $list=[];
            $money=[];
            $list["self_id"]            =generate_id('change_');
            $list["model"]              =$v['model'];
            $list['create_time']        =$v['create_time'];
            $list["update_time"]        =$v['update_time'];
            $list["group_code"]         =$v['group_code'];
            $list["group_name"]         =$v['group_name'];
            $list["order_id"]           =$v['order_id'];

            $list['type']               =$type;
            $list['library_sige_id']    =$v['self_id'];
            $list['price']              =$v['sale_price'];

            switch ($type){
                case 'preentry':
                    $list['initial_num']        ='0';
                    $list['now_num']            =$v['now_num'];
                    $list['change_num']         =$list['now_num']-$list['initial_num'];
                    // $list['use_flag']           ='N';
                    $list['inout_time']         =$v['date_time'];
                    break;

                case 'change':
                    $list['initial_num']        =0;
                    $list['now_num']            =0;
                    $list['change_num']         =0;
                    $list['different']          =$v['now_num_new']-$v['yuan_number'];
                    $list['type']               ='different';
                    $list['inout_time']         =$v['date_time'];
                    break;

                case 'movein':
                    $list['initial_num']        ='0';
                    $list['now_num']            =$v['now_num'];
                    $list['change_num']         =$list['now_num']-$list['initial_num'];
                    break;

                case 'moveout':
                    $list['initial_num']        =$v['now_num'];
                    if($v['now_num'] - $v['now_num_new'] >0){
                        $list['now_num']            =$v['now_num_new'];
                    }else{
                        $list['now_num']            =0;
                    }
                    $list['change_num']         =$list['initial_num']-$list['now_num'];
                    break;


                case 'out':
                    $list['initial_num']        =$v['initial_num'];             //66
                    $list['change_num']         =$v['shiji_num'];
                    $list['now_num']            =$v['initial_num']-$v['shiji_num'];
                    $list['inout_time']         =$v['inout_time'];
                    break;

            }
//            $money['total_price'] = $list['change_num']*$v['price'];
//            $payment[] = $money;
            $data[]=$list;
        }
        /***出入库时要保存费用**/
        TmsTryeChange::insert($data);


    }
    /**
     * 入库操作
     * */
    public function inTrye(Request $request,Change $change){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='创建/修改车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $car_id             =$request->input('car_id');//车牌号
        $car_number         =$request->input('car_number');//车牌号
        $model              =$request->input('model');//型号
        $num                =$request->input('num');//数量
        $trye_num           =$request->input('trye_num');//编号
        $operator           =$request->input('operator');//经办人
        $type               =$request->input('type');//类型：in入库  out出库
        $in_time            =$request->input('in_time');//时间
        $driver_name        =$request->input('driver_name');//驾驶员
        $change             =$request->input('change');//更换位置
        $trye_list          =$request->input('trye_list');//更换位置
        $price              =$request->input('price');//更换位置
        $supplier           =$request->input('supplier');//供应商

        /**
        $trye_list = [[
        trye_num=>'',//轮胎编号
        model=>'',//型号
        num=>2,//数量
        kilo_num=>'25062',//里程数
        change=>'',//更换位置
        trye_img=>'',//图片
        ]]
         * */
        $rules=[
            'num'=>'required',
        ];
        $message=[
            'num.required'=>'数量必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $data['type']              =$type;
            $data['model']             =$model;
            $data['num']               =$num;
            $data['trye_num']          =$trye_num;
            $data['in_time']           =$in_time;
            $data['operator']          =$operator;
            $data['price']             =$price;
            $data['supplier']          =$supplier;
            // $trye_model = TmsTryeList::where('model',$model)->first();


            $count['model'] = $model;
            $count['initial_num'] = $num;
            $count['change_num'] = $num;
            $count['now_num'] = $num;
            $count['date_time'] = $in_time;
            $count['sale_price'] = $price;

            $wheres['self_id'] = $self_id;
            $old_info=TmsTrye::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsTrye::where($wheres)->update($data);
                TmsTryeCount::where('order_id',$self_id)->update($count);
                $update['now_num'] = $num;
                $update['change_num'] = $num;
                TmsTryeChange::where('order_id',$self_id)->update($update);
                $operationing->access_cause='修改入库';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('trye_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $user_info->group_code;
                $data['group_name']         = $user_info->group_name;

                $count['self_id'] = generate_id('count_');
                $count['order_id'] = $data['self_id'];
                $count['create_user_id']     =$user_info->admin_id;
                $count['create_user_name']   =$user_info->name;
                $count['create_time']        =$count['update_time']=$now_time;
                $count['group_code']         = $user_info->group_code;
                $count['group_name']         = $user_info->group_name;
                $change[] = $count;
                $id=TmsTrye::insert($data);
                TmsTryeCount::insert($count);
                // $change->tryChange();
                self::tryeChange($change,'preentry');
                if (!$trye_model){
                    $model_list['self_id'] = generate_id('model_');
                    $model_list['model']   = $model;
                    $model_list['price']   = $price;
                    $model_list['create_user_id']     =$user_info->admin_id;
                    $model_list['create_user_name']   =$user_info->name;
                    $model_list['create_time']        =$model_list['update_time']=$now_time;
                    $model_list['group_code']         = $user_info->group_code;
                    $model_list['group_name']         = $user_info->group_name;
                   TmsTryeList::insert($model_list);
                }

                $operationing->access_cause='新建入库';
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

    //新改轮胎入库 入库完成后需审核完成入库
    public function tryeIn(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='创建/修改车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $car_id             =$request->input('car_id');//车牌号
        $car_number         =$request->input('car_number');//车牌号
        $model              =$request->input('model');//型号
        $num                =$request->input('num');//数量
        $trye_num           =$request->input('trye_num');//编号
        $operator           =$request->input('operator');//经办人
        $type               =$request->input('type');//类型：in入库  out出库
        $in_time            =$request->input('in_time');//时间
        $driver_name        =$request->input('driver_name');//驾驶员
        $change             =$request->input('change');//更换位置
        $trye_list          =$request->input('trye_list');//更换位置
        $price              =$request->input('price');//更换位置
        $supplier           =$request->input('supplier');//供应商
        $trye_name          =$request->input('trye_name');//供应商
        $remark             =$request->input('remark');

        /**
        $trye_list = [[
        trye_num=>'',//轮胎编号
        model=>'',//型号
        num=>2,//数量
        kilo_num=>'25062',//里程数
        change=>'',//更换位置
        trye_img=>'',//图片
        ]]
         * */
        $rules=[
            'num'=>'required',
        ];
        $message=[
            'num.required'=>'数量必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $data['type']              =$type;
            $data['model']             =$model;
            $data['num']               =$num;
            $data['trye_num']          =$trye_num;
            $data['in_time']           =$in_time;
            $data['operator']          =$operator;
            $data['price']             =$price;
            $data['supplier']          =$supplier;
            $data['trye_name']         =$trye_name;
            $data['state']             ='N';
            $data['remark']            =$remark;
            $trye_model = TmsTryeList::where('model',$model)->first();

            $wheres['self_id'] = $self_id;
            $old_info=TmsTrye::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsTrye::where($wheres)->update($data);
                
                $operationing->access_cause='修改入库';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('trye_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $user_info->group_code;
                $data['group_name']         = $user_info->group_name;

                $id=TmsTrye::insert($data);
                
                if (!$trye_model){
                    $model_list['self_id'] = generate_id('model_');
                    $model_list['model']   = $model;
                    $model_list['price']   = $price;
                    $model_list['trye_name'] = $trye_name;
                    $model_list['create_user_id']     =$user_info->admin_id;
                    $model_list['create_user_name']   =$user_info->name;
                    $model_list['create_time']        =$model_list['update_time']=$now_time;
                    $model_list['group_code']         = $user_info->group_code;
                    $model_list['group_name']         = $user_info->group_name;
                   TmsTryeList::insert($model_list);
                }

                $operationing->access_cause='新建入库';
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

    //入库审核
    public function updateInState(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='轮胎入库审核';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        
        $rules=[
            'self_id'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择审核条目！',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $count_list = [];
            $change = [];
            $model_lists = [];
            foreach(explode(',',$self_id) as $k => $v){
                $wheres['self_id'] = $v;
                $old_info=TmsTrye::where($wheres)->first();
                // $trye_model = TmsTryeList::where('model',$old_info->model)->first();

                // if (!$trye_model){
                //     $model_list['self_id'] = generate_id('model_');
                //     $model_list['model']   = $old_info->model;
                //     $model_list['price']   = $old_info->price;
                //     $model_list['create_user_id']     =$user_info->admin_id;
                //     $model_list['create_user_name']   =$user_info->name;
                //     $model_list['create_time']        =$model_list['update_time']=$now_time;
                //     $model_list['group_code']         = $user_info->group_code;
                //     $model_list['group_name']         = $user_info->group_name;
                //     $model_lists[] = $model_list;
                // }

                $count['model'] = $old_info->model;
                $count['initial_num'] = $old_info->num;
                $count['change_num'] = $old_info->num;
                $count['now_num'] = $old_info->num;
                $count['date_time'] = $old_info->in_time;
                $count['sale_price'] = $old_info->price;
                $count['trye_num'] = $old_info->trye_num;

                $count['self_id'] = generate_id('count_');
                $count['order_id'] = $old_info->self_id;
                $count['create_user_id']     =$user_info->admin_id;
                $count['create_user_name']   =$user_info->name;
                $count['create_time']        =$count['update_time']=$now_time;
                $count['group_code']         = $user_info->group_code;
                $count['group_name']         = $user_info->group_name;
                $change[] = $count;
            }
                $update['state'] = 'Y';
                $update['update_time'] = $now_time;
                DB::beginTransaction();
                try{
                   $id = TmsTrye::whereIn('self_id',explode(',',$self_id))->update($update);
                   // TmsTryeList::insert($model_lists);
                   TmsTryeCount::insert($change);
                // $change->tryChange();
                   self::tryeChange($change,'preentry');
                   DB::commit();
                }catch(\Exception $e){
                    dd($e);
                    DB::rollBack();
                }
                
                $operationing->access_cause='轮胎入库审核';
                $operationing->operation_type='create';
            

            $operationing->table_id=$old_info?$self_id:$old_info->self_id;
            $operationing->old_info=$old_info;
            $operationing->new_info=(object)$count;

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
     * 获取轮胎  根据规格获取当前已入库的轮胎参数
     * */
    public function getTrye(Request $request){
        $group_code=$request->input('group_code');

//        $input['group_code'] =  $group_code = '1234';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
        ];

        $where=get_list_where($search);
        $select = ['self_id','model','price','use_flag','delete_flag','group_code','group_name','create_time'];
        $data['info']=TmsTryeList::where($where)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    public function getStateTrye(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'state','value'=>'N'],
            ['type'=>'=','name'=>'type','value'=>'out'],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_number','price','model','model','supplier','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag','state','user_id','trailer_num'];
        $select1=['self_id','kilo','price','trye_img','change','order_id','model','num','trye_num','change','create_user_name','create_time','group_code','use_flag'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsTrye::where($where)->count(); //总的数据量
                $data['items']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
                    $query->where('delete_flag','Y');
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsTrye::where($where)->count(); //总的数据量
                $data['items']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
                    $query->where('delete_flag','Y');
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsTrye::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsTrye::with(['TryeOutList'=>function($query)use($select1){
                    $query->where('delete_flag','Y');
                    $query->select($select1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /**
     * 出库
     * */
    public function outTrye(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='创建/修改车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $car_id             =$request->input('car_id');//车牌号
        $car_number         =$request->input('car_number');//车牌号
        $trailer_num        =$request->input('trailer_num');//挂车号
        $model              =$request->input('model');//型号
        $num                =$request->input('num');//数量
        $trye_num           =$request->input('trye_num');//编号
        $operator           =$request->input('operator');//经办人
        $type               =$request->input('type');//类型：in入库  out出库
        $in_time            =$request->input('in_time');//时间
        $user_id            =$request->input('user_id');//驾驶员
        $driver_name        =$request->input('driver_name');//驾驶员
        $change             =$request->input('change');//更换位置
        $trye_list          =$request->input('trye_list');//更换位置
        $remark             =$request->input('remark');//备注

        /**
        $trye_list = [[
        trye_num=>'',//轮胎编号
        model=>'',//型号
        num=>2,//数量
        kilo_num=>'25062',//里程数
        change=>'',//更换位置
        trye_img=>'',//图片
        price=>'',//轮胎单价
        ]]
         * */
        $rules=[
            'num'=>'required',
        ];
        $message=[
            'num.required'=>'数量必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $data['type']              =$type;
            $data['car_id']            =$car_id;
            $data['car_number']        =$car_number;
            $data['trailer_num']       =$trailer_num;
            $data['num']               =$num;
            $data['change']            =$change;
            $data['user_id']           =$user_id;
            $data['driver_name']       =$driver_name;
            $data['trye_list']         =$trye_list;
            $trye_out_lists = [];
            foreach(json_decode($trye_list,true) as $k => $v){
                   
                    $trye_num_list['trye_num']           = $v['trye_num'];
                    
                    array_push($trye_out_lists,$v['trye_num']);

            }
            // dd($trye_out_lists);
            $data['trye_num']          =implode(',',$trye_out_lists);
            $data['in_time']           =$in_time;
            $data['operator']          =$operator;
            $data['remark']            =$remark;


            $wheres['self_id'] = $self_id;
            $old_info=TmsTrye::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsTrye::where($wheres)->update($data);
                $del_data['delete_flag']='N';
                $del_data['update_time']=$now_time;
                TryeOutList::where('order_id',$self_id)->update($del_data);
                $operationing->access_cause='修改入库';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('trye_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $user_info->group_code;
                $data['group_name']         = $user_info->group_name;
                $id=TmsTrye::insert($data);

                $operationing->access_cause='新建入库';
                $operationing->operation_type='create';
            }

                $operationing->table_id=$old_info?$self_id:$data['self_id'];
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;

                if($id){
                    $trye_out_list = [];
                    foreach(json_decode($trye_list,true) as $key => $value){
                        $list['self_id']            = generate_id('list_');
                        $list['model']              = $value['model'];
                        $list['trye_num']           = $value['trye_num'];
                        $list['num']                = $value['num'];
                        $list['price']              = $value['price'];
                        if ($self_id){
                            $list['order_id']           = $self_id;
                        }else{
                            $list['order_id']           = $data['self_id'];
                        }

                        $list['kilo']               = $value['kilo_num'];
                        $list['change']             = $value['change'];
                        $list['trye_img']           = $value['trye_img'];
                        $list['create_user_id']     = $user_info->admin_id;
                        $list['create_user_name']   = $user_info->name;
                        $list['create_time']        = $list['update_time']=$now_time;
                        $list['group_code']         = $user_info->group_code;
                        $list['group_name']         = $user_info->group_name;
                        $trye_out_list[] = $list;

                    }
                    TryeOutList::insert($trye_out_list);
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

    public function getTryeNum(Request $request){
        $group_code=$request->input('group_code');
        $model=$request->input('model');

//        $input['group_code'] =  $group_code = '1234';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'model','value'=>$model],
        ];
        $where=get_list_where($search);

        $select = ['self_id','model','order_id','sale_price','initial_num','change_num','now_num','trye_num','use_flag','delete_flag','group_code','group_name','create_time'];
        $data['info']=TmsTryeCount::where($where)->where('now_num','>',0)->select($select)->get();
        $res = '';
    
        foreach($data['info'] as $k => $v){
            $res .= $v->trye_num;
        }
        // $res = implode(',',$res);
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$res;
        return $msg;
    }


    /**
     * 出库审核
     * */
    public function outUpdate(Request $request){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $user_info          = $request->get('user_info');                //接收中间件产生的参数
        $order_id=$request->input('self_id');
//        $order_id=["trye_202304110936155324333258"];

        /**循环处理数据**/

        $where=[
            ['delete_flag','=','Y'],
        ];
        $select=['self_id','car_number','price','model','model','supplier','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag'];
        $select1=['self_id','kilo','price','trye_img','change','order_id','model','num','trye_num','change','create_user_name','create_time','group_code','use_flag'];
        $order = TmsTrye::with(['TryeOutList' => function($query) use($select1){
            $query->select($select1);
            $query->where('delete_flag','=','Y');
            }])->where($where)->whereIn('self_id',$order_id)
            ->select($select)->get()->toArray();
        if ($order) {
                /**检查是否包含已审核数据**/
                $check = array_column($order, 'state');
                $order_check = array_count_values($check);

                if (array_key_exists('Y', $order_check)) {
                    $msg['code'] = 301;
                    $msg['msg'] = '选中的选项中包含了已审核订单，请检查';
                    return $msg;
                }

                /**做出库订单数据**/
                $count = count($order_id);
                $temp['state'] = 'Y';
                $temp['update_time'] = $now_time;


                $order_do = [];
                foreach ($order as $k => $v) {
                    if ($v['trye_out_list']) {
                        foreach ($v['trye_out_list'] as $kk => $vv) {
                            $vv['inout_time'] = $v['in_time'];
                            $order_do[] = $vv;
                        }
                    }
                }
                DB::beginTransaction();
                try {
                    $moneylist = [];
                    foreach ($order_do as $k => $v) {
                        $where2 = [
                            ['model', '=', $v['model']],
                            ['now_num', '>', 0],
                            ['delete_flag', '=', 'Y'],
//                                ['create_time', '>',$now_time]
//                                ['create_time', '>', substr($now_time, 0, -9)]
                        ];
                      
                        $resssss = TmsTryeCount::where($where2)->orderBy('create_time', 'asc')->get()->toArray();
                        if ($resssss) {
                            $totalNum = array_sum(array_column($resssss, 'now_num'));
                            $numds = $v['num'] - $totalNum;
                            if ($numds > 0) {
                                $msg['code']=301;
                                $msg['msg']='库存不足！';
                                return $msg;
                            } else {
                                $wms_library_sige = [];
                                $wms_library_change =[];
                                $number=$v['num'];
                                foreach ($resssss as $kk =>$vv){
                                  
                                    if($number > 0 && in_array($v['trye_num'],explode(',',$vv['trye_num']))) {
                                        if ($number - $vv['now_num'] > 0) {
                                            $shiji_number = $vv['now_num'];

                                        } else {
                                            $shiji_number = $number;
                                        }
                                        $library_sige['self_id'] = $vv['self_id'];
                                        $library_sige['yuan_num'] = $vv['now_num'];
                                        $library_sige['chuku_number'] = $shiji_number;
                                        $library_sige['trye_num'] = $v['trye_num'];
                                        $wms_library_sige[] = $library_sige;

                                        
                                        $library_change['self_id']      =$vv['self_id'];
                                        $library_change['sale_price']                =$vv['sale_price'];   
                                        $library_change['group_code']           =$vv['group_code'];
                                        $library_change['group_name']           =$vv['group_name'];
                                        $library_change['shiji_num']            =$shiji_number;
                                        $library_change['model']                =$vv['model'];
                                        $library_change['inout_time']           =$v['inout_time'];
                                        $library_change['initial_num']          =$vv['now_num'];
                                        $library_change['create_user_id']       =$user_info->admin_id;
                                        $library_change["create_user_name"]     =$user_info->name;
                                        $library_change["create_time"]          =$now_time;
                                        $library_change["update_time"]          =$now_time;
                                        $library_change["order_id"]             =$v['order_id'];
                                        //DD($library_change);
                                        $wms_library_change[]=$library_change;

                                        $number -=  $vv['now_num'];
                                    }

                                }
                               
                                self::tryeChange($wms_library_change,'out');
                                foreach ($wms_library_sige as $kkk => $vvv){
                                    $where21['self_id']=$vvv['self_id'];
                                    $unset = TmsTryeCount::where($where21)->first();
                                    $new_str = str_replace($v['trye_num'].',','',$unset->trye_num);
                                    $librarySignUpdate['trye_num'] = $new_str;

                                    $librarySignUpdate['now_num']           =$vvv['yuan_num']-$vvv['chuku_number'];
                                    $librarySignUpdate['update_time']       =$now_time;
                                    TmsTryeCount::where($where21)->update($librarySignUpdate);
                                }
                            }

                        } else {
                            $msg['code']=301;
                            $msg['msg']='暂时无货，请稍后重试！';
                            return $msg;
                        }

                        /***保存费用**/
                        $trye = TmsTrye::where('self_id',$v['order_id'])->select('car_id','car_number','user_id','driver_name','group_code','group_name','create_user_id','create_user_name')->first();
                        $money['pay_type']           = 'trye';
                        $money['money']              = $v['price'];
                        $money['pay_state']          = 'Y';
                        $money['order_id']           = $v['order_id'];
                        $money['car_id']             = $trye->car_id;
                        $money['car_number']         = $trye->car_number;
                        $money['user_id']            = $trye->user_id;
                        $money['user_name']          = $trye->driver_name;
                        $money['process_state']      = 'Y';
                        $money['type_state']         = 'out';
                        // $money['use_flag']           = 'Y';
                        $money['self_id']            = generate_id('money_');
                        $money['group_code']         = $trye->group_code;
                        $money['group_name']         = $trye->group_name;
                        $money['create_user_id']     = $trye->create_user_id;
                        $money['create_user_name']   = $trye->create_user_name;
                        $money['create_time']        =$money['update_time']=$now_time;
                        $moneylist[]=$money;
                    }
                    $id = TmsTrye::whereIn('self_id',$order_id)->update($temp);
                    if($id){
                        TmsMoney::insert($moneylist);
                        $data['use_flag'] = 'Y';
                        $data['update_time'] = $now_time;
                        TryeOutList::whereIn('order_id',$order_id)->update($data);
                        DB::commit();
                        $msg['code'] = 200;
                        $msg['msg'] = "操作成功";
                        return $msg;
                    }else{
                        DB::rollBack();
                        $msg['code'] = 302;
                        $msg['msg'] = "操作失败";
                        return $msg;
                    }
                }catch(\Exception $e){
                    dd($e);
                    DB::rollBack();
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }
            }
    }

    /*
     *
     * */
    public function abc(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='创建/修改车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $car_id             =$request->input('car_id');//车牌号
        $car_number         =$request->input('car_number');//车牌号
        $model              =$request->input('model');//型号
        $num                =$request->input('num');//数量
        $trye_num           =$request->input('trye_num');//编号
        $operator           =$request->input('operator');//经办人
        $type               =$request->input('type');//类型：in入库  out出库
        $in_time            =$request->input('in_time');//时间
        $driver_name        =$request->input('driver_name');//驾驶员
        $change             =$request->input('change');//更换位置
        $trye_list          =$request->input('trye_list');//更换位置

        /**
        $trye_list = [[
        trye_num=>'',//轮胎编号
        model=>'',//型号
        num=>2,//数量
        kilo_num=>'25062',//里程数
        change=>'',//更换位置
        trye_img=>'',//图片
        price=>'',//轮胎单价
        ]]
         * */
        $rules=[
            'num'=>'required',
        ];
        $message=[
            'num.required'=>'数量必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $data['type']              =$type;
            $data['car_id']            =$car_id;
            $data['car_number']        =$car_number;
            $data['num']               =$num;
            $data['change']            =$change;
            $data['driver_name']       =$driver_name;
            $data['trye_list']         =$trye_list;
            $data['trye_num']          =$trye_num;
            $data['in_time']           =$in_time;
            $data['operator']          =$operator;

            DB::beginTransaction();
            try{
                foreach (json_decode($trye_list,true) as $k => $v) {
                    $where2 = [
                        ['model', '=', $v['model']],
                        ['now_num', '>', 0],
                        ['delete_flag', '=', 'Y'],
//                                ['create_time', '>',$now_time]
//                                ['create_time', '>', substr($now_time, 0, -9)]
                    ];

                    $resssss = TmsTryeCount::where($where2)->orderBy('create_time', 'asc')->get()->toArray();
                    if ($resssss) {

                        $totalNum = array_sum(array_column($resssss, 'now_num'));
                        $numds = $v['num'] - $totalNum;
                        if ($numds > 0) {
                            $msg['code']=301;
                            $msg['msg']='库存不足！';
                            return $msg;
                        } else {
                            $wms_library_sige = [];
                            $number=$v['num'];
                            foreach ($resssss as $kk =>$vv){
                                if($number > 0) {
                                    if ($number - $vv['now_num'] > 0) {
                                        $shiji_number = $vv['now_num'];

                                    } else {
                                        $shiji_number = $number;
                                    }
                                    $library_sige['self_id'] = $vv['self_id'];
                                    $library_sige['yuan_num'] = $vv['now_num'];
                                    $library_sige['chuku_number'] = $shiji_number;
                                    $wms_library_sige[] = $library_sige;
                                    $number -=  $vv['now_num'];
                                }

                            }

                            foreach ($wms_library_sige as $kkk => $vvv){
                                $where21['self_id']=$vvv['self_id'];

                                $librarySignUpdate['now_num']           =$vvv['yuan_num']-$vvv['chuku_number'];
                                $librarySignUpdate['update_time']       =$now_time;
                                TmsTryeCount::where($where21)->update($librarySignUpdate);
                            }
                        }

                    } else {
                        $msg['code']=301;
                        $msg['msg']='暂时无货，请稍后重试！';
                        return $msg;
                    }

                }

                $wheres['self_id'] = $self_id;
                $old_info=TmsTrye::where($wheres)->first();

                if($old_info){
                    $data['update_time']=$now_time;
                    $id=TmsTrye::where($wheres)->update($data);
                    $operationing->access_cause='修改入库';
                    $operationing->operation_type='update';

                }else{
                    $data['self_id']            =generate_id('trye_');
                    $data['create_user_id']     =$user_info->admin_id;
                    $data['create_user_name']   =$user_info->name;
                    $data['create_time']        =$data['update_time']=$now_time;
                    $data['group_code']         = $user_info->group_code;
                    $data['group_name']         = $user_info->group_name;
                    $id=TmsTrye::insert($data);
                    $trye_out_list = [];
                    $moneylist = [];
                    foreach(json_decode($trye_list,true) as $key => $value){
                        $list['self_id']            = generate_id('list_');
                        $list['model']              = $value['model'];
                        $list['trye_num']           = $value['trye_num'];
                        $list['num']                = $value['num'];
                        $list['price']              = $value['price'];
                        $list['order_id']           = $data['self_id'];
                        $list['kilo']               = $value['kilo_num'];
                        $list['change']             = $value['change'];
                        $list['trye_img']           = $value['trye_img'];
                        $list['create_user_id']     = $user_info->admin_id;
                        $list['create_user_name']   = $user_info->name;
                        $list['create_time']        = $list['update_time']=$now_time;
                        $list['group_code']         = $user_info->group_code;
                        $list['group_name']         = $user_info->group_name;
                        $trye_out_list[] = $list;

                        /***保存费用**/
                        $money['pay_type']           = 'trye';
                        $money['money']              = $value['price'];
                        $money['pay_state']          = 'Y';
                        $money['order_id']           = $data['self_id'];
                        $money['car_id']             = $car_id;
                        $money['car_number']         = $car_number;
//                        $money['user_id']            = $user_id;
                        $money['user_name']          = $driver_name;
                        $money['process_state']      = 'Y';
                        $money['type_state']         = 'out';
                        $money['use_flag']           = 'N';
                        $money['self_id']            = generate_id('money_');
                        $money['group_code']         = $user_info->group_code;
                        $money['group_name']         = $user_info->group_name;
                        $money['create_user_id']     = $user_info->admin_id;
                        $money['create_user_name']   = $user_info->name;
                        $money['create_time']        =$money['update_time']=$now_time;
                        $moneylist[]=$money;
                    }
                    TryeOutList::insert($trye_out_list);
                    TmsMoney::insert($moneylist);


                    $operationing->access_cause='新建入库';
                    $operationing->operation_type='create';
                }

                $operationing->table_id=$old_info?$self_id:$data['self_id'];
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;

                if($id){
                    DB::commit();
                    $msg['code'] = 200;
                    $msg['msg'] = "操作成功";
                    return $msg;
                }else{
                    DB::rollBack();
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }
            }catch(\Exception $e){
                DB::rollBack();
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

    /***    车辆类型禁用/启用      /tms/trye/tryeUseFlag
     */
    public function typeUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_trye';
        $medol_name='TmsTrye';
        $self_id=$request->input('self_id');
        $flag='useFlag';
//        $self_id='car_202012242220439016797353';

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

    /***    车辆类型删除     /tms/trye/tryeDelFlag
     */
    public function tryeDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_trye';
        $medol_name='TmsTrye';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        $old_info = TmsTrye::whereIn('self_id',explode(',',$self_id))->select('use_flag','self_id','delete_flag','group_code','state')->get();
        $data['delete_flag']='N';
        $data['update_time']=$now_time;
        foreach($old_info as $k => $v){
            if ($v->state == 'N'){
                TryeOutList::where('order_id',$v->self_id)->update($data);
            }
        }
//        dd($old_info);
        if ($id){
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
        }else{
            $msg['code']=301;
            $msg['msg']="删除失败";

        }

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$data;
        $operationing->operation_type=$flag;

        $msg['code']=$msg['code'];
        $msg['msg']=$msg['msg'];
        $msg['data']=(object)$data;

        return $msg;
    }

    /***    拿去车辆类型数据     /tms/type/getType
     */
    public function  getType(Request $request){
        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
        ];
        $select=['self_id','parame_name','img'];
        $data['info']=TmsCarType::where($where)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;
    }

    /***    车辆类型导入     /tms/type/import
     */
    public function import(Request $request){
        $table_name         ='tms_car_type';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         ='1234';
        $file_id            =$request->input('file_id');
        //dd($input);
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/import/TMS车辆类型导入文件范本.xlsx";
         ***/
        $rules = [
            'importurl' => 'required',
        ];
        $message = [
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
                '车辆类型' =>['Y','N','64','parame_name'],
                '承载体积(立方)' =>['Y','N','6','allvolume'],
                '承载重量(kg)' =>['Y','N','10','allweight'],
                '车厢内径(如:4.1*2*1.8m)' =>['Y','N','64','dimensions'],
                '出车最低价(元)' =>['Y','Y','10','low_price'],
                '价格(元/公里)' =>['Y','Y','10','costkm_price'],
                '市内包车价(元)' =>['N','Y','10','chartered_price'],
                '装货费' =>['Y','Y','10','pickup_price'],
                '卸货费' =>['Y','Y','10','unload_price'],
                '多点提货费' =>['N','Y','10','morepickup_price'],
            ];
            $ret=arr_check($shuzu,$info_check);

            // dump($ret);
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

            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['parame_name','=',$v['parame_name']],
                ];

                $cartype = TmsCarType::where($where)->value('self_id');

                if($cartype){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆类型已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('type_');
                    $list['parame_name']        = $v['parame_name'];
                    $list['allweight']          = $v['allweight'];
                    $list['allvolume']          = $v['allvolume'];
                    $list['dimensions']         = $v['dimensions'];
                    $list['costkm_price']       = $v['costkm_price'] ? ($v['costkm_price'] - 0 * 100) : 0;
                    $list['low_price']          = $v['low_price'] ? ($v['low_price'] - 0 * 100) : 0;
                    $list['chartered_price']    = $v['chartered_price'] ? ($v['chartered_price'] - 0 * 100) : 0;
                    $list['pickup_price']       = $v['pickup_price'] ? ($v['pickup_price'] - 0 * 100) : 0;
                    $list['unload_price']       = $v['unload_price'] ? ($v['unload_price'] - 0 * 100) : 0;
                    $list['morepickup_price']   = $v['morepickup_price'] ? ($v['morepickup_price'] - 0 * 100) : 0;
                    $list['group_code']         = $group_code;
                    $list['group_name']         = '平台方';
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
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
            $id= TmsCarType::insert($datalist);




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

    /***    车辆类型详情     /tms/type/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='tms_car_type';
        $select=['self_id','parame_name','create_time','group_code','group_name'];
        // $self_id='type_202012290957581187464100';
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

    /**
     *  导出   tms/trye/excel
     * */
    public function excel(Request $request,File $file){
        $background   =array_column(config('tms.background'),'name','key');
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $ids            =$request->input('ids');
        $type            =$request->input('type');
        // $group_code  =$input['group_code']   ='1234';
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
                ['type'=>'=','name'=>'type','value'=>$type],
            ];
            $where=get_list_where($search);

            $select=['self_id','car_number','model','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag'];

            $info=TmsTrye::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "trye_num"=>'轮胎编号',
                    "num"=>'数量',
                    "in_time"=>'日期',
                    "car_number"=>'车牌号',
                    "model"=>'型号',
                    "change"=>'更换位置',
                    "driver_name"=>'驾驶员',
                    "operator"=>'经办人',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];

                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['trye_num']=$v->trye_num;
                    $list['num']=$v->num;
                    $list['in_time']=$v->in_time;
                    $list['car_number']=$v->car_number;
                    $list['model']=$v->model;
                    $list['change']=$v->change;
                    $list['driver_name']=$v->driver_name;
                    $list['operator']=$v->operator;
                    $data_execl[]=$list;
                }
//                dd($data_execl);
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

    /**
     * 库存记录
     * */
    public function tryeCountList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }
    //库存统计列表
    public function tryeCountPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $warehouse_name      =$request->input('warehouse_name');
        $good_name           =$request->input('good_name');
        $start_time          =$request->input('start_time');
        $end_time            =$request->input('end_time');
        $listrows            =$num;
        $firstrow            =($page-1)*$listrows;

        // if ($start_time) {
        //     $start_time = $start_time.' 00:00:00';
        // }else{
        //     $msg['code']=300;
        //     $msg['msg']="请选择开始时间";
        //     return $msg;
        // }
        // if ($end_time) {
        //     $end_time = $end_time.' 23:59:59';
        // }else{
        //     $msg['code']=300;
        //     $msg['msg']="请选择结束时间";
     
        // return $msg;
        // }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],

        ];

        $search1=[
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'>=','name'=>'now_num','value'=>0],
            ['type'=>'>','name'=>'date_time','value'=>$start_time],
            ['type'=>'<=','name'=>'date_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $where1 = get_list_where($search1);
        $select=['self_id','model','group_name','use_flag'];
        $Signselect=['self_id','model','initial_num','change_num','create_time','now_num','trye_list','date_time'];
//        dd($select);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsTryeList::where($where)->count(); //总的数据量
                $data['items']=TmsTryeList::with(['TmsTryeCount' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])
                    ->with(['tryeOutList' => function($query)use($where) {
                        $query->where('use_flag','Y');
                        $query->where('delete_flag','Y');
                }])
                    ->with(['tmsTrye' => function($query)use($where) {
                        $query->where('state','Y');
                    }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsTryeList::where($where)->count(); //总的数据量
                $data['items']=TmsTryeList::with(['TmsTryeCount' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])
                    ->with(['tryeOutList' => function($query)use($where) {
                        $query->where('use_flag','Y');
                        $query->where('delete_flag','Y');
                    }])
                    ->with(['tmsTrye' => function($query)use($where) {
                        $query->where('state','Y');
                    }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsTryeList::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsTryeList::with(['TmsTryeCount' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])
                    ->with(['tryeOutList' => function($query)use($where) {
                        $query->where('use_flag','Y');
                        $query->where('delete_flag','Y');
                    }])
                    ->with(['tmsTrye' => function($query)use($where) {
                          $query->where('state','Y');
                    }])
                    ->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }


//        dump($data['items']->toArray());
        $count3=0;
        $count4=0;
        foreach ($data['items'] as $k=>$v) {
            $v->count=0;
            $v->count1=0;
            $v->count2=0;
            foreach ($v->TmsTryeCount as $kk=>$vv) {
                $v->count +=$vv->now_num;
                $v->count1 +=$vv->change_num;
                $v->count2 +=$vv->initial_num;
            }
            $count = TmsTryeCount::where('date_time','<=',$start_time)
//                ->where('sku_id',$v->self_id)
                ->sum('initial_num');
            $v->count4 = $count??$count3;
            $v->count3 = ($v->count1-$v->count)??$count4;

            $v->button_info=$button_info;
        }
//        dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }
    //轮胎库存列表
    public function tryeCount(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $warehouse_name      =$request->input('warehouse_name');
        $good_name           =$request->input('good_name');
        $start_time          =$request->input('start_time');
        $end_time            =$request->input('end_time');
        $model               =$request->input('model');
        $listrows            =$num;
        $firstrow            =($page-1)*$listrows;

        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }else{
            $msg['code']=300;
            $msg['msg']="请选择开始时间";
            return $msg;
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }else{
            $msg['code']=300;
            $msg['msg']="请选择结束时间";
     
        return $msg;
        }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'model','value'=>$model],

        ];

        $search1=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'>=','name'=>'now_num','value'=>0],
            ['type'=>'>=','name'=>'inout_time','value'=>$start_time],
            ['type'=>'<=','name'=>'inout_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $where1 = get_list_where($search1);
        $select=['self_id','model','group_name','use_flag'];
        $Signselect=['self_id','model','initial_num','change_num','create_time','now_num','trye_list','date_time','different','date_time'];
//        dd($select);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsTryeList::where($where)->count(); //总的数据量
                $data['items']=TmsTryeList::with(['TmsTryeCount' => function($query)use($Signselect,$where1) {
                 
                    $query->select($Signselect);
                }])
                    ->with(['tryeOutList' => function($query)use($where) {
                        $query->where('use_flag','Y');
                        $query->where('delete_flag','Y');
                }])
                    ->with(['tmsTrye' => function($query)use($where) {
                        $query->where('state','Y');
                    }])
                    ->with(['tmsTryeChange' => function($query)use($where1) {
                          $query->where($where1);
                    }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsTryeList::where($where)->count(); //总的数据量
                $data['items']=TmsTryeList::with(['TmsTryeCount' => function($query)use($Signselect,$where1) {
                 
                    $query->select($Signselect);
                }])
                    ->with(['tryeOutList' => function($query)use($where) {
                        $query->where('use_flag','Y');
                        $query->where('delete_flag','Y');
                    }])
                    ->with(['tmsTrye' => function($query)use($where) {
                        $query->where('state','Y');
                    }])
                    ->with(['tmsTryeChange' => function($query)use($where1) {
                          $query->where($where1);
                    }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsTryeList::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsTryeList::with(['TmsTryeCount' => function($query)use($Signselect,$where1) {
                   
                    $query->select($Signselect);
                }])
                    ->with(['tryeOutList' => function($query)use($where) {
                        $query->where('use_flag','Y');
                        $query->where('delete_flag','Y');
                    }])
                    ->with(['tmsTrye' => function($query)use($where) {
                          $query->where('state','Y');
                    }])
                    ->with(['tmsTryeChange' => function($query)use($where1) {
                          $query->where($where1);
                    }])
                    ->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }


//        dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {
            $v->count=0;
            $v->in_count=0;
            $v->out_count=0;
            $v->initial_count=0;
            $v->jie_count=0;
            foreach ($v->tmsTryeChange as $kk=>$vv) {
                $v->different_count = TmsTryeChange::where('inout_time','<',$start_time)->where('model',$v->model)->where('type','different')->sum('different');
                if ($vv->type == 'preentry'){
                    $v->in_count += $vv->now_num;
                }elseif($vv->type == 'out'){
                    $v->out_count += $vv->change_num;
                }elseif($vv->type == 'different'){
                    $v->different += $vv->different;
                }
//                $v->initial_count += $vv->initial_num;

               
                $v->count +=$vv->now_num;
            }
//            $v->jie_count = $v->in_count - $v->out_count;
            $v->in_initial_count = TmsTryeChange::where('inout_time','<',$start_time)->where('model',$v->model)->where('type','preentry')->sum('change_num');
            $v->out_initial_count = TmsTryeChange::where('inout_time','<',$start_time)->where('model',$v->model)->where('type','out')->sum('change_num');
            
            $v->initial_count = $v->in_initial_count - $v->out_initial_count;
            $v->jie_count = $v->in_count + $v->initial_count - $v->out_count + $v->different;
            $v->different_counts = $v->different_count;

            $v->button_info=$button_info;
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;
    }

    /***    库位商品列表      /tms/trye/searchList
     */
    public function  searchList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    库位商品分页      /tms/trye/searchPage
     */
    public function searchPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $in_store_status  =array_column(config('wms.in_store_status'),'name','key');
        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');
        $model              =$request->input('model');
        $price              =$request->input('price');
        $start_time         =$request->input('start_time');
        $end_time           =$request->input('end_time');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'>','name'=>'now_num','value'=>0],
            ['type'=>'=','name'=>'model','value'=>$model],
            ['type'=>'=','name'=>'sale_price','value'=>$price],
            ['type'=>'>=','name'=>'date_time','value'=>$start_time],
            ['type'=>'<=','name'=>'date_time','value'=>$end_time],
        ];


        $where=get_list_where($search);

        //DUMP($where);
        $select=['self_id','model','initial_num','change_num','create_time','now_num','trye_list','date_time','group_code','group_name','delete_flag','use_flag','sale_price','trye_num'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsTryeCount::where($where)->count(); //总的数据量
                $data['items']=TmsTryeCount::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsTryeCount::where($where)->count(); //总的数据量
                $data['items']=TmsTryeCount::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsTryeCount::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsTryeCount::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach ($data['items'] as $k=>$v) {
            if ($v->area && $v->row && $v->column){
                $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
            }else{
                $v->sign = '';
            }

            $v->good_describe =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);
            $v->in_library_state_show =$in_store_status[$v->in_library_state]??null;
            $v->button_info=$button_info;

        }

       // dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    修改差异      /tms/trye/mistakeRevise
     */
    public function mistakeRevise(Request $request,Change $change){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $input              =$request->all();
        $self_id            =$request->input('self_id');
        $num                =$request->input('num');
        $time                =$request->input('time');

        $table_name         ='wms_library_sige';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause='修改差异';
        $operationing->operation_type='update';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;
        $operationing->type='add';

        /****虚拟数据
        $input['self_id']    =$self_id="LSID_202011281559375553276821";
        $input['num']        =$num='35';
         ***/
        $rules=[
            'self_id'=>'required',
            'num'=>'required|integer',
        ];
        $message=[
            'self_id.required'=>'请填写修改的条目',
            'num.required'=>'请填写数量',
            'num.integer'=>'请填写正确的数量',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $where_sku=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $select=['self_id','model','initial_num','change_num','create_time','now_num','trye_list','date_time','group_code','group_name','delete_flag','use_flag','sale_price','trye_num','order_id'];

            $old_info=TmsTryeCount::where($where_sku)->select($select)->first();

            $yuan_number = 0;
            if($old_info){
                 if($old_info ->now_num ==  $num){
                     $msg['code'] = 303;
                     $msg['msg'] = "原数量和新数量一致，不需要修改差异";
                     return $msg;
                 }
                 $yuan_number = $old_info->now_num;
            }

            //dd($old_info);
            $data['now_num']=$num;
            $data['update_time']=$now_time;

            $operationing->table_id=$self_id;
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            $id=TmsTryeCount::where($where_sku)->update($data);



            if($id){
                $andd=$old_info->toArray();
                $andd['create_user_id']     =$user_info->admin_id;
                $andd['create_user_name']   =$user_info->name;
                $andd['create_time']        =$now_time;
                $andd["update_time"]        =$now_time;
                $andd["now_num_new"]        =$num;
                $andd["yuan_number"]        =$yuan_number;
                $andd["date_time"]          =$time;

                $abc[0]=$andd;
                //DUMP($abc);
                // $change->change($abc,'change');
                
                self::tryeChange($abc,'change');
                   
                
                

                //dd(11111);
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
