<?php
namespace App\Http\Admin\Tms;
use App\Http\Controllers\FileController as File;
use App\Models\Tms\CarAccident;
use App\Models\Tms\TmsMoney;
use App\Models\User\UserExamine;
use App\Models\Group\SystemUser;
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


class ExamineController extends CommonController{

    /***    车辆事故列表头部      /tms/examine/examineList
     */
    public function  examineList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/TMS车辆导入文件范本.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    车辆事故分页      /tms/examine/examinePage
     */
    public function examinePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $user_name      =$request->input('user_name');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        if ($start_time){
            $start_time     =date('Y-m-01 00:00:00',strtotime($request->input('start_time')));
        }
        if ($end_time){
            $end_time       =date('Y-m-t 23:59:59',strtotime($request->input('start_time')));
        }
        $user_id        =$request->input('user_id');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
//        dd($start_time,$end_time);
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'user_id','value'=>$user_id],
            ['type'=>'like','name'=>'user_name','value'=>$user_name],
            ['type'=>'>=','name'=>'start_time','value'=>$start_time],
            ['type'=>'<=','name'=>'end_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $select=['self_id','user_id','user_name','absence_duty','start_time','end_time','create_user_id','create_user_name',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','date_num','approver','reason'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=UserExamine::where($where)->count(); //总的数据量
                $data['items']=UserExamine::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=UserExamine::where($where)->count(); //总的数据量
                $data['items']=UserExamine::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=UserExamine::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=UserExamine::where($where)->whereIn('group_code',$group_info['group_code'])
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



    /***    新建车辆维修      /tms/examine/createExamine
     */
    public function createExamine(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select = ['self_id','user_id','user_name','absence_duty','start_time','end_time','create_user_id','create_user_name',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','date_num','approver','reason'];
        $data['info']=UserExamine::where($where)->select($select)->first();

        if ($data['info']){

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;


    }


    /***    新建车辆维修数据提交      /tms/examine/addExamine
     */
    public function addExamine(Request $request){
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
        $user_name          =$request->input('user_name');//员工名称
        $user_id            =$request->input('user_id');
        $start_time         =$request->input('start_time');//请假开始时间
        $end_time           =$request->input('end_time');//请假结束日期
        $absence_duty       =$request->input('absence_duty');// 缺勤日期
        $remark             =$request->input('remark');//备注
        $date_num           =$request->input('date_num');//请假天数
        $approver           =$request->input('approver');//审批人
        $reason             =$request->input('reason');//请假事由
        $rules=[
            'user_name'=>'required',
        ];
        $message=[
            'user_name.required'=>'请填写员工姓名！',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }
            //查询员工基本工资和每月奖金
            $user = SystemUser::where('self_id',$user_id)->select('self_id','salary','safe_reward')->first();

            $data['user_name']           =$user_name;
            $data['user_id']             =$user_id;
            $data['start_time']          =$start_time;
            $data['end_time']            =$end_time;
            $data['absence_duty']        =$absence_duty;
            $data['remark']              =$remark;
            $data['date_num']           =$date_num;
            $data['approver']           =$approver;
            $data['reason']             =$reason;

            //获取当月天数
             $day_num = date('t',strtotime($start_time));
             //获取开始和结束时间月份
             $month_start = date('m',strtotime($start_time));
             $month_end = date('m',strtotime($end_time));

            //计算工资扣款和奖金扣款
             $salary_fine = round($user->salary/$day_num,2)*$date_num;
             $reward_price = round($user->safe_reward/$day_num,2)*$date_num;   
             // if ($month_start != $month_end) {
             //        $end_day = date($start_time,strtotime("$month_start+1 month-1 day"));
             //        $start_day = date('Y-'.$month_end.'-01',strtotime($end_time));
             //        $start_days = count(getDateFromRange($start_time,$end_day));
             //        $end_days = count(getDateFromRange($start_day,$end_time));

             //  }   
             $data['salary_fine'] = $salary_fine;
             $data['reward_price'] = $reward_price;

            $wheres['self_id'] = $self_id;
            $old_info=UserExamine::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=UserExamine::where($wheres)->update($data);

                $operationing->access_cause='修改考核记录';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('mine_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=UserExamine::insert($data);
                $operationing->access_cause='新建考核记录';
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



    /***    车辆维修禁用/启用      /tms/examine/examineUseFlag
     */
    public function examineUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='user_examine';
        $medol_name='UserExamine';
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

    /***    车辆维修删除      /tms/examine/examineDelFlag
     */
    public function examineDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='user_examine';
        $medol_name='UserExamine';
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
                '车牌号' =>['Y','Y','30','car_number'],
                '品牌' =>['Y','Y','30','brand'],
                '公里数' =>['Y','Y','30','kilo_num'],
                '配件名称' =>['Y','Y','30','fittings'],
                '保养/维修时间' =>['Y','Y','50','service_time'],
                '保修期' =>['Y','Y','50','warranty_time'],
                '原因' =>['Y','Y','50','reason'],
                '详细记录' =>['Y','Y','255','service_view'],
                '保养/维修单位' =>['Y','Y','50','service_partne'],
                '保养/维修金额' =>['Y','Y','50','service_price'],
                '经办人' =>['Y','Y','50','operator'],
                '司机' =>['Y','Y','50','driver_name'],
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

                $list=[];
                if($cando =='Y'){

                    $list['self_id']            = generate_id('service_');
                    $list['car_number']         = $v['car_number'];
                    $list['brand']              = $v['brand'];
                    $list['kilo_num']           = $v['kilo_num'];
                    $list['fittings']           = $v['fittings'];
                    $list['service_time']       = $v['service_time'];
                    $list['warranty_time']      = $v['warranty_time'];
                    $list['reason']             = $v['reason'];
                    $list['service_view']       = $v['service_view'];
                    $list['service_partne']     = $v['service_partne'];
                    $list['service_price']      = $v['service_price'];
                    $list['operator']           = $v['operator'];
                    $list['driver_name']        = $v['driver_name'];
                    $list['remark']             = $v['remark'];

                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;




                    $datalist[]=$list;
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
        $table_name='car_accident';
        $select=['self_id','user_id','user_name','absence_duty','start_time','end_time','create_user_id','create_user_name',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','date_num','approver','reason'];
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

    /***    车辆导出     /tms/car/execl
     */
    public function execl(Request $request,File $file){
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

            $select=['self_id','car_number','car_possess','weight','volam','control','car_type_name','contacts','tel','remark'];
            $info=TmsCar::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "car_number"=>'车牌号',
                    "car_type_name"=>'车辆类型',
                    "car_possess"=>'属性',
                    "control"=>'温控',
                    "weight"=>'承重(kg)',
                    "volam"=>'体积(立方)',
                    "contacts"=>'联系人',
                    "tel"=>'联系电话',
                    "remark"=>'备注'
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                $tms_control_type = array_column(config('tms.tms_control_type'),'name','key');
                $tms_car_possess_type = array_column(config('tms.tms_car_possess_type'),'name','key');

                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['car_number']=$v->car_number;
                    $list['car_type_name']=$v->car_type_name;
                    $control = '';
                    $car_possess = '';
                    if (!empty($tms_control_type[$v['control']])) {
                        $control = $tms_control_type[$v['control']];
                    }

                    if (!empty($tms_car_possess_type[$v['car_possess']])) {
                        $car_possess = $tms_car_possess_type[$v['car_possess']];
                    }
                    $list['car_possess']=$car_possess;
                    $list['control']    =$control;
                    $list['weight']     =$v->weight;
                    $list['volam']      =$v->volam;
                    $list['contacts']   =$v->contacts;
                    $list['tel']        =$v->tel;
                    $list['remark']     =$v->remark;

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
