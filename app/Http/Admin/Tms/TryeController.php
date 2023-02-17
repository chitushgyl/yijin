<?php
namespace App\Http\Admin\Tms;

use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsTrye;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsCarType;
use App\Http\Controllers\FileController as File;


class TryeController extends CommonController{

    /***    车辆类型列表头部      /tms/trye/tryeList
     */
    public function  tryeList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆类型';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/TMS车辆类型导入文件范本.xlsx',
        ];
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
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'like','name'=>'model','value'=>$model],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'>','name'=>'in_time','value'=>$start_time],
            ['type'=>'<=','name'=>'in_time','value'=>$end_time],
        ];


        $where=get_list_where($search);

        $select=['self_id','car_number','model','model','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsTrye::where($where)->count(); //总的数据量
                $data['items']=TmsTrye::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsTrye::where($where)->count(); //总的数据量
                $data['items']=TmsTrye::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsTrye::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsTrye::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {

            $v->button_info=$button_info;

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
        $select=['self_id','car_number','model','model','num','trye_num','operator','type','in_time','driver_name','change','create_user_name','create_time','group_code','use_flag'];
        $data['info']=TmsTrye::where($where)->select($select)->first();
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
                    break;
                case 'out':
                    $data['type']              =$type;
                    $data['car_number']        =$car_number;
                    $data['num']               =$num;
                    $data['change']            =$change;
                    $data['driver_name']       =$driver_name;
                    break;
                default:
                    break;
            }
            $data['num']         =$num;
            $data['trye_num']          =$trye_num;
            $data['in_time']           =$in_time;
            $data['operator']          =$operator;
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

}
?>
