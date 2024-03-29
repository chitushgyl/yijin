<?php
namespace App\Http\Admin\Tms;
use App\Http\Controllers\FileController as File;
use App\Models\Group\SystemUser;
use App\Models\Tms\CarService;
use App\Models\Tms\TmsCommonInfo;
use App\Models\Tms\TmsMoney;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsCar;
use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsCarType;
use App\Models\Tms\TmsGroup;

class CarServiceController extends CommonController{

    /***    车辆维修列表头部      /tms/carService/serviceList
     */
    public function  serviceList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/维修保养导入.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    车辆维修分页      /tms/carService/servicePage
     */
    public function servicePage(Request $request){
        /** 接收中间件参数**/
        $service_type    =array_column(config('tms.service_type'),'name','key');
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_number');
        $trailer_num    =$request->input('trailer_num');
        $driver_name    =$request->input('driver_name');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $type           =$request->input('type');
        $service_partne =$request->input('service_partne');
        $settle_flag    =$request->input('settle_flag');
        $brand    =$request->input('brand');
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
//            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'trailer_num','value'=>$trailer_num],
            ['type'=>'like','name'=>'driver_name','value'=>$driver_name],
            ['type'=>'>=','name'=>'service_time','value'=>$start_time],
            ['type'=>'<=','name'=>'service_time','value'=>$end_time],
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'like','name'=>'service_partne','value'=>$service_partne],
            ['type'=>'=','name'=>'settle_flag','value'=>$settle_flag],
            ['type'=>'=','name'=>'brand','value'=>$brand],
        ];

        $where=get_list_where($search);
        $where1=[
            ['delete_flag','=','Y'],
//            ['use_flag','=','Y'],
        ];

        $select=['self_id','car_number','car_id','brand','kilo_num','service_time','reason','service_price','service_partne','service_partne','driver_name','contact','operator','settle_flag','trailer_num',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','fittings','warranty_time','service_view','type','driver_id','service_item','servicer','next_kilo'];
        $select1 = ['self_id','name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=CarService::where($where)->count(); //总的数据量
                $data['items']=CarService::with(['SystemUser' => function($query)use($where1,$select1){
                    $query->where($where1);
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['price']=CarService::where($where)->where('use_flag','Y')->sum('service_price');
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=CarService::where($where)->count(); //总的数据量
                $data['items']=CarService::with(['SystemUser' => function($query)use($where1,$select1){
                    $query->where($where1);
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['price']=CarService::where($where)->where('use_flag','Y')->sum('service_price');
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=CarService::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=CarService::with(['SystemUser' => function($query)use($where1,$select1){
                    $query->where($where1);
                    $query->select($select1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['price']=CarService::where($where)->where('use_flag','Y')->whereIn('group_code',$group_info['group_code'])->sum('service_price');
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($button_info as $k => $v){
            if($v->id == 76){
                $button_info1[] = $v;
            }
            if($v->id == 77){
                $button_info2[] = $v;

            }
            if($v->id == 78){
                $button_info2[] = $v;
                $button_info3[] = $v;
            }

        }

        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
            $v->type = $service_type[$v->type]??null;
            if ($v->settle_flag == 'Y') {
                $v->button_info = $button_info2;
                if($v->use_flag == 'N'){
                    $v->button_info = $button_info3;
                }
            }else{
                $v->button_info = $button_info1;
            }
            if ($v->SystemUser){
                $v->driver_name = null?$v->driver_name:$v->SystemUser->name;
            }


        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    新建车辆维修      /tms/carService/createService
     */
    public function createService(Request $request){
        $data['type']        =config('tms.service_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select = ['self_id','car_number','car_id','brand','kilo_num','service_time','reason','service_price','service_partne','service_partne','driver_name','contact','operator','trailer_num',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','fittings','warranty_time','service_view','type','driver_id','service_item','servicer','next_kilo'];
        $data['info']=CarService::where($where)->select($select)->first();

        if ($data['info']){

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;


    }


    /***    新建车辆维修数据提交      /tms/carService/addService
     */
    public function addService(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_car';
        $operationing->access_cause     ='创建/修改车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $type               =$request->input('type');//类型 service维修  保养preserve
        $car_id             =$request->input('car_id');
        $car_number         =$request->input('car_number');//车牌号
        $trailer_num        =$request->input('trailer_num');//挂车号
        $brand              =$request->input('brand');//品牌型号
        $driver_id          =$request->input('driver_id');//驾驶员 self_id
        $driver_name        =$request->input('driver_name');//驾驶员
        $service_item       =$request->input('service_item');//维修/保养项目
        $service_view       =$request->input('service_view');//维修/保养明细
        $service_time       =$request->input('service_time');//维修/保养时间
        $servicer           =$request->input('servicer');//维修人员
        $service_partne     =$request->input('service_partne');//维修/保养单位
        $service_price      =$request->input('service_price');//金额
        $kilo_num           =$request->input('kilo_num');//保养公里数
        $next_kilo          =$request->input('next_kilo');//下次保养公里数
        $operator           =$request->input('operator');//经办人
        $remark             =$request->input('remark');//备注

        $rules=[
            'car_number'=>'required',
        ];
        $message=[
            'car_number.required'=>'车牌号必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }
            $data['car_number']        =$car_number;
            $data['car_id']            =$car_id;
            $data['trailer_num']       =$trailer_num;
            $data['brand']             =$brand;
            $data['kilo_num']          =$kilo_num;
            $data['next_kilo']         =$next_kilo;
            $data['type']              =$type;
            $data['service_time']      =$service_time;
            $data['driver_id']         =$driver_id;
            $data['service_item']      =$service_item;
            $data['service_view']      =$service_view;
            $data['service_partne']    =$service_partne;
            $data['service_price']     =$service_price;
            $data['driver_name']       =$driver_name;
            $data['servicer']          =$servicer;
            $data['operator']          =$operator;
            $data['remark']            =$remark;

            /**保存费用**/
            // if ($service_price){
            //     if ($type == 'service'){
            //         $money['pay_type']           = 'repair';
            //     }else{
            //         $money['pay_type']           = 'preserve';
            //     }
            //     $money['money']              = $service_price;
            //     $money['pay_state']          = 'Y';
            //     $money['car_id']             = $car_id;
            //     $money['car_number']         = $car_number;
            //     $money['process_state']      = 'Y';
            //     $money['type_state']         = 'out';
            // }

            $wheres['self_id'] = $self_id;
            $old_info=CarService::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=CarService::where($wheres)->update($data);

                $operationing->access_cause='修改车辆维修';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('service_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=CarService::insert($data);
                $common_info['self_id']    = generate_id('common_');
                $common_info['car_number'] =  $car_number;
                $common_info['trailer_num'] = $trailer_num;
                $common_info['driver_id']   = $driver_id;
                $common_info['driver_name'] = $driver_name;
                $common_info['group_code'] = $group_code;
                $common_info['group_name'] = $group_name;
                $common_info['create_time'] = $common_info['update_time'] = $now_time;
                TmsCommonInfo::insert($common_info);
                // if($service_price){
                //     $money['self_id']            = generate_id('money_');
                //     $money['group_code']         = $group_code;
                //     $money['group_name']         = $group_name;
                //     $money['create_user_id']     = $user_info->admin_id;
                //     $money['create_user_name']   = $user_info->name;
                //     $money['create_time']        =$money['update_time']=$service_time;
                //     TmsMoney::insert($money);
                // }
                $operationing->access_cause='新建车辆维修';
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

    public function getCommonInfo(Request $request){
        $group_code=$request->input('group_code');
        $car_number=$request->input('car_number');

//        $input['group_code'] =  $group_code = '1234';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'car_number','value'=>$car_number],
        ];

        $where=get_list_where($search);
        $select = ['self_id','car_number','car_id','group_code','use_flag','delete_flag','group_code','group_name','driver_id','driver_name','trailer_num'];
        $data['info']=TmsCommonInfo::where($where)->orderBy('create_time','desc')->select($select)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    //维修保养审核
    public function serviceSettle(Request $request){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='car_service';
        $medol_name='CarService';
        $self_id=$request->input('self_id');
        $flag='delFlag';
//        $self_id='car_202012242220439016797353';
        $old_info = CarService::whereIn('self_id',explode(',',$self_id))->get();
        //保存费用
        $moneyList=[];
        foreach($old_info as $k => $v){

                if ($v->type == 'service'){
                    $money['pay_type']           = 'repair';
                }elseif($v->type == 'vehicle'){
                    $money['pay_type']           = 'vehicle_fee';
                }else{
                    $money['pay_type']           = 'preserve';
                }
                $money['order_id']           = $v->self_id;
                $money['money']              = $v->service_price;
                $money['pay_state']          = 'Y';
                $money['car_id']             = $v->car_id;
                $money['car_number']         = $v->car_number;
                $money['trailer_num']        = $v->trailer_num;
                $money['user_id']            = $v->driver_id;
                $money['user_name']          = $v->driver_name;
                $money['process_state']      = 'Y';
                $money['type_state']         = 'out';
                $money['self_id']            = generate_id('money_');
                $money['group_code']         = $v->group_code;
                $money['group_name']         = $v->group_name;
                $money['create_user_id']     = $v->admin_id;
                $money['create_user_name']   = $v->name;
                $money['create_time']        =$money['update_time']=$v->service_time;
                $moneyList[] = $money;

        }
        $data['settle_flag']='Y';
        $data['update_time']=$now_time;
//        dd($old_info);
        $id=CarService::whereIn('self_id',explode(',',$self_id))->update($data);
        TmsMoney::insert($moneyList);
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


    /***    车辆维修禁用/启用      /tms/carService/serviceUseFlag
     */
    public function serviceUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='car_service';
        $medol_name='CarService';
        $self_id=$request->input('self_id');
        $flag='useFlag';
//        $self_id='car_202012242220439016797353';



       $old_info = CarService::where('self_id',$self_id)->select('use_flag','self_id','delete_flag','group_code')->get();
        $data['use_flag']='N';
        $data['update_time']=$now_time;
//        dd($old_info);
        $id=CarService::where('self_id',$self_id)->update($data);
        TmsMoney::where('order_id',$self_id)->update($data);
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

    /***    车辆维修删除      /tms/carService/serviceDelFlag
     */
    public function serviceDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='car_service';
        $medol_name='CarService';
        $self_id=$request->input('self_id');
        $flag='delFlag';
//        $self_id='car_202012242220439016797353';

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

    /***    拿去车辆维修数据     /tms/carService/getService
     */
    public function  getService(Request $request){
        $group_code=$request->input('group_code');
        //$input['group_code'] =  $group_code = '1234';
        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['group_code','=',$group_code],
        ];
        $data['info']=CarService::where($where)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    车辆维修导入     /tms/carService/import
     */
    public function import(Request $request){
        $table_name         ='car_service';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建车辆维修记录';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');

        /****虚拟数据
        $input['importurl']     =$importurl="uploads/import/TMS车辆导入文件范本.xlsx";
        $input['group_code']       =$group_code='1234';
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
                'ID' =>['N','Y','30','id'],
                '类型' =>['Y','Y','30','type'],
                '车牌号' =>['Y','Y','30','car_number'],
                '挂车号' =>['N','Y','30','trailer_num'],
                '品牌型号' =>['N','Y','30','brand'],
                '维修/保养/检车日期' =>['Y','Y','30','service_time'],
                '送修/保养/检车驾驶员' =>['N','Y','30','driver_name'],
                '维修/保养/检车项目' =>['Y','Y','50','service_item'],
                '维修/保养/检车明细' =>['N','Y','200','service_view'],
                '维修/保养/检车单位' =>['N','Y','50','service_partne'],
                '维修/检车人员' =>['N','Y','30','servicer'],
                '保养公里数' =>['N','Y','50','kilo_num'],
                '下次保养公里数' =>['N','Y','50','next_kilo'],
                '金额' =>['Y','Y','50','service_price'],
                '经办人' =>['N','Y','50','operator'],
                '备注' =>['N','Y','200','remark'],
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

            // dd($info);

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;
            $moneylist=[];
            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                if (!check_carnumber($v['car_number'])) {
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号错误！".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                if ($v['type'] == '维修'){
                     $type = 'service';
                }elseif($v['type'] == '保养'){
                     $type = 'preserve';
                }elseif($v['type'] == '检车'){
                    $type = 'vehicle';
                }else{
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行类型错误！".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $driver = SystemUser::whereIn('type',['driver','dr_cargo'])->where('name',$v['driver_name'])->where('group_code',$group_code)->select('self_id','name','use_flag','delete_flag','social_flag')->first();
                if (!$driver){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行驾驶员不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $list=[];
                $money=[];
                if($cando =='Y'){
                    $list['self_id']            = generate_id('service_');
                    $list['type']               = $type;
                    $list['car_number']         = $v['car_number'];
                    $list['trailer_num']        = $v['trailer_num'];
                    $list['brand']              = $v['brand'];
                    $list['service_time']       = $v['service_time'];
                    $list['driver_id']          = $driver->self_id;
                    $list['driver_name']        = $driver->name;
                    $list['service_item']       = $v['service_item'];
                    $list['service_view']       = $v['service_view'];
                    $list['service_partne']     = $v['service_partne'];
                    $list['servicer']           = $v['servicer'];
                    $list['kilo_num']           = $v['kilo_num'];
                    $list['next_kilo']          = $v['next_kilo'];
                    $list['service_price']      = $v['service_price'];
                    $list['operator']           = $v['operator'];
                    $list['remark']             = $v['remark'];

                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;

                    $datalist[]=$list;

                    if ($v['service_price']){
                        if($type == 'service'){
                            $money['pay_type']           = 'repair';
                        }elseif($type == 'preserve'){
                            $money['pay_type']           = 'preserve';
                        }else{
                            $money['pay_type']           = 'vehicle_fee';
                        }

                        $money['money']              = $v['service_price'];
                        $money['pay_state']          = 'Y';
//                        $money['car_id']             = $car_id;
                        $money['car_number']         = $v['car_number'];
                        $money['process_state']      = 'Y';
                        $money['type_state']         = 'out';
                        $money['self_id']            = generate_id('money_');
                        $money['group_code']         = $info->group_code;
                        $money['group_name']         = $info->group_name;
                        $money['create_user_id']     = $user_info->admin_id;
                        $money['create_user_name']   = $user_info->name;
                        $money['create_time']        =$money['update_time']=$v['service_time'];
                        $moneylist[]=$money;
                    }
                }

                $a++;
            }
            $operationing->old_info=null;
            $operationing->new_info=(object)$datalist;

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= CarService::insert($datalist);
            TmsMoney::insert($moneylist);


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

    /***    车辆详情     /tms/carService/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='car_service';
        $select=['self_id','car_number','car_id','brand','kilo_num','service_time','reason','service_price','service_partne','service_partne','driver_name','contact','operator','trailer_num',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','fittings','warranty_time','service_view','type','driver_id','service_item','servicer','next_kilo'];
        // $self_id='car_202012291341297595587871';
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
            // dd($data);

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

    /***    维修导出     /tms/car/excel
     */
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

            $select=['self_id','car_number','car_id','brand','kilo_num','service_time','reason','service_price','service_partne','service_partne','driver_name','contact','operator','trailer_num',
                'remark','create_time','update_time','use_flag','delete_flag','group_code','fittings','warranty_time','service_view','type','driver_id','service_item','servicer','next_kilo'];
            $info=CarService::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "type"=>'类型',
                    "car_number"=>'车牌号',
                    "trailer_num"=>'挂车号',
                    "brand"=>'品牌型号',
                    "driver_name"=>'送修/保养/检车驾驶员',
                    "service_time"=>'维修/保养/检车日期',
                    "service_item"=>'维修/保养/检车项目',
                    "service_view"=>'维修/保养/检车明细',
                    "servicer"=>'维修/检车人员',
                    "service_partne"=>'维修/保养/检车单位',
                    "kilo_num"=>'保养公里数',
                    "next_kilo"=>'下次保养公里数',
                    "service_price"=>'金额',
                    "operator"=>'经办人',
                    "remark"=>'备注'
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']            =($k+1);
                    if ($v->type == 'service') {
                        $list['type']          ='维修';
                    }elseif($v->type == 'preserve'){
                        $list['type']          ='保养';
                    }else{
                        $list['type']          ='检车';
                    }
                    $list['car_number']    =$v->car_number;
                    $list['trailer_num']   =$v->trailer_num;
                    $list['brand']         =$v->brand;
                    $list['driver_name']   =$v->driver_name;
                    $list['service_time']  =$v->service_time;
                    $list['service_item']  =$v->service_item;
                    $list['service_view']  =$v->service_view;
                    $list['servicer']      =$v->servicer;
                    $list['service_partne']=$v->service_partne;
                    $list['kilo_num']      =$v->kilo_num;
                    $list['next_kilo']     =$v->next_kilo;
                    $list['service_price'] =$v->service_price;
                    $list['operator']      =$v->operator;
                    $list['remark']        =$v->remark;

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

}
?>
