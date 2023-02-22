<?php
namespace App\Http\Admin\Tms;

use App\Models\Group\SystemGroup;
use App\Models\Group\SystemSection;
use App\Models\Group\SystemUser;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Http\Controllers\FileController as File;


class UserController extends CommonController{

    /***    员工列表头部     /tms/user/userList
     */
    public function  userList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='员工';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/员工导入模板.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    员工分页      /tms/user/userPage
     */
    public function userPage(Request $request){
        /** 接收中间件参数**/
        $user_type    =array_column(config('tms.user_type'),'name','key');
        $background   =array_column(config('tms.background'),'name','key');
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $name           =$request->input('name');
        $self_id        =$request->input('self_id');
        $type           =$request->input('type');
        $social_flag    =$request->input('social_flag');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'name','value'=>$name],
            ['type'=>'=','name'=>'self_id','value'=>$self_id],
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'=','name'=>'social_flag','value'=>$social_flag],
        ];

        $where=get_list_where($search);

        $select=['self_id','name','tel','department','identity_num','entry_time','leave_time','social_flag','live_cost','education_background','now_address','safe_reward','salary',
        'group_insurance','use_flag','delete_flag','create_time','update_time','group_code','group_name','type','birthday','sex','age','contract_date','working_age','id_validity',
            'drive_type','nvq_num','nvq_organ','nvq_validity','drive_organ','drive_validity','id_address','driver_nvq_num','driver_nvq_validity','driver_nvq_organ','driver_nvq'];
        $select1 = ['self_id','section_name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemUser::where($where)->count(); //总的数据量
                $data['items']=SystemUser::with(['SystemSection' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemUser::where($where)->count(); //总的数据量
                $data['items']=SystemUser::with(['SystemSection' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemUser::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemUser::with(['SystemSection' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
            $v->driver_license     =img_for($v->driver_license,'no_json');
            $v->nvq                =img_for($v->nvq,'no_json');
            $v->contract           =img_for($v->contract,'no_json');
            $v->identity_front     =img_for($v->identity_front,'no_json');
            $v->identity_back      =img_for($v->identity_back,'no_json');
            $v->contract_back      =img_for($v->contract_back,'no_json');
            $v->license_back       =img_for($v->license_back,'no_json');
            $v->work_license       =img_for($v->work_license,'more');
            $v->type               =$user_type[$v->type]??null;
            $v->education_background               =$background[$v->education_background]??null;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    添加员工    /tms/user/createUser
     */
    public function createUser(Request $request){
        $data['type']    =config('tms.user_type');
        $data['background']    =config('tms.background');
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','type','name','tel','department','identity_num','entry_time','leave_time','social_flag','live_cost','education_background','now_address','driver_license','nvq','safe_reward','contract'
            ,'group_insurance','identity_front','identity_back','use_flag','delete_flag','create_time','update_time','group_code','group_name','type','contract_back','license_back','work_license'
        ,'birthday','sex','age','contract_date','working_age','id_validity','id_address','salary',
            'drive_type','nvq_num','nvq_organ','nvq_validity','drive_organ','drive_validity','driver_nvq_num','driver_nvq_validity','driver_nvq_organ','driver_nvq'];
        $data['info']=SystemUser::where($where)->select($select)->first();
        if($data['info']){
            $data['info']->driver_license     =img_for($data['info']->driver_license,'no_json');
            $data['info']->nvq                =img_for($data['info']->nvq,'no_json');
            $data['info']->contract           =img_for($data['info']->contract,'no_json');
            $data['info']->identity_front     =img_for($data['info']->identity_front,'no_json');
            $data['info']->identity_back      =img_for($data['info']->identity_back,'no_json');
            $data['info']->contract_back      =img_for($data['info']->contract_back,'no_json');
            $data['info']->license_back       =img_for($data['info']->license_back,'no_json');
            $data['info']->work_license       =img_for($data['info']->work_license,'more');
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    员工数据提交      /tms/user/addUser
     */
    public function addUser(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_address';

        $operationing->access_cause     ='创建/修改员工';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();

        /** 接收数据*/
        $self_id                 =$request->input('self_id');
        $name                    =$request->input('name');// 姓名
        $tel                     =$request->input('tel');// 联系方式
        $department              =$request->input('department');// 部门
        $identity_num            =$request->input('identity_num');//身份证号
        $entry_time              =$request->input('entry_time');//入职时间
        $leave_time              =$request->input('leave_time');//离职时间
        $social_flag             =$request->input('social_flag');//是否参加社保
        $live_cost               =$request->input('live_cost');//住宿费
        $education_background    =$request->input('education_background');//学历
        $now_address             =$request->input('now_address');//现居地
        $driver_license          =$request->input('driver_license');//驾驶证
        $nvq                     =$request->input('nvq');//资格证
        $safe_flag               =$request->input('safe_flag');//是否有安全奖
        $safe_reward             =$request->input('safe_reward');//安全奖金
        $contract                =$request->input('contract');//合同
        $group_insurance         =$request->input('group_insurance');//团体险
        $identity_front          =$request->input('identity_front');//身份证正面
        $identity_back           =$request->input('identity_back');//身份证反面
        $group_code              =$request->input('group_code');
        $type                    =$request->input('type');
        $license_back            =$request->input('license_back');//驾驶证反面
        $contract_back           =$request->input('contract_back');//合同反面
        $work_license            =$request->input('work_license');//岗位证件
        $salary                  =$request->input('salary');//工资
        $birthday                =$request->input('birthday');//出生日期
        $sex                     =$request->input('sex');//性别
        $age                     =$request->input('age');//年龄
        $contract_date           =$request->input('contract_date');//合同有效期
        $working_age             =$request->input('working_age');//工龄
        $id_validity             =$request->input('id_validity');//身份证有效期
        $drive_type              =$request->input('drive_type');//准驾车型
        $nvq_num                 =$request->input('nvq_num');//资格证号
        $nvq_organ               =$request->input('nvq_organ');//资格证签发机构
        $nvq_validity            =$request->input('nvq_validity');//资格证有效期限
        $drive_organ             =$request->input('drive_organ');//驾照签发机构
        $drive_validity          =$request->input('drive_validity');//驾驶证有效期限
        $id_address              =$request->input('id_address');//身份证上的地址
        $driver_nvq_num          =$request->input('driver_nvq_num');//驾驶员资格证号
        $driver_nvq_validity     =$request->input('driver_nvq_validity');//驾驶员资格证有效期截止
        $driver_nvq_organ        =$request->input('driver_nvq_organ');//驾驶员资格证发证机构
        $driver_nvq              =$request->input('driver_nvq');//驾驶员资格证



        $rules=[
            'name'=>'required',
            'tel'=>'required',
            'identity_num'=>'required',

        ];
        $message=[
            'name.required'=>'请填写姓名',
            'tel.required'=>'请填写联系方式',
            'identity_num.required'=>'请填写身份证号',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $data['name']                 =$name;
            $data['tel']                  =$tel;
            $data['department']           =$department;
            $data['identity_num']         =$identity_num;
            $data['entry_time']           =$entry_time;
            $data['leave_time']           =$leave_time;
            $data['social_flag']          =$social_flag;
            $data['live_cost']            =$live_cost;
            $data['education_background'] =$education_background;
            $data['now_address']          =$now_address;
            $data['driver_license']       =img_for($driver_license,'one_in');
            $data['nvq']                  =img_for($nvq,'one_in');
            $data['safe_flag']            =$safe_flag;
            $data['safe_reward']          =$safe_reward;
            $data['contract']             =img_for($contract,'one_in');
            $data['group_insurance']      =$group_insurance;
            $data['identity_front']       =img_for($identity_front,'one_in');
            $data['identity_back']        =img_for($identity_back,'one_in');
            $data['type']                 =$type;
            $data['license_back']         =img_for($license_back,'one_in');
            $data['contract_back']        =img_for($contract_back,'one_in');
            $data['work_license']         =img_for($work_license,'in');
            $data['salary']               =$salary;
            $data['birthday']             =$birthday;
            $data['sex']                  =$sex;
            $data['age']                  =$age;
            $data['contract_date']        =$contract_date;
            $data['working_age']          =$working_age;
            $data['id_validity']          =$id_validity;
            $data['drive_type']           =$drive_type;
            $data['nvq_num']              =$nvq_num;
            $data['nvq_organ']            =$nvq_organ;
            $data['nvq_validity']         =$nvq_validity;
            $data['drive_organ']          =$drive_organ;
            $data['drive_validity']       =$drive_validity;
            $data['id_address']           =$id_address;
            $data['driver_nvq_num']       =$driver_nvq_num;
            $data['driver_nvq_validity']  =$driver_nvq_validity;
            $data['driver_nvq_organ']     =$driver_nvq_organ;
            $data['driver_nvq']           =img_for($driver_nvq,'one_in');

            $wheres['self_id'] = $self_id;
            $old_info=SystemUser::where($wheres)->first();
            if($old_info){

                $data['update_time']=$now_time;
                $id=SystemUser::where($wheres)->update($data);
                $operationing->access_cause='修改员工信息';
                $operationing->operation_type='update';


            }else{
                $data['self_id']            =generate_id('user_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         =$group_code;
                $data['group_name']         =$group_name;

                $id=SystemUser::insert($data);
                $operationing->access_cause='添加员工';
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



    /***    员工禁用/启用      /tms/user/userFlag
     */
    public function userFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='system_user';
        $medol_name='SystemUser';
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

    /***    员工删除     /tms/user/userDelFlag
     */
    public function userDelFlag(Request $request,Status $status){

        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='system_user';
        $self_id=$request->input('self_id');
        $flag='delete_flag';
//        $self_id='address_202103011352018133677963';
        $old_info = SystemUser::where('self_id',$self_id)->select('group_code','group_name','use_flag','delete_flag','update_time')->first();
        $update['delete_flag'] = 'N';
        $update['update_time'] = $now_time;
        $id = SystemUser::where('self_id',$self_id)->update($update);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$update;
        $operationing->operation_type=$flag;
        if($id){
            $msg['code']=200;
            $msg['msg']='删除成功！';
            $msg['data']=(object)$update;
        }else{
            $msg['code']=300;
            $msg['msg']='删除失败！';
        }

        return $msg;
    }


    /***    拿地址信息     /tms/user/getUser
     */
    public function  getUser(Request $request){
        $group_code=$request->input('group_code');
        $type=$request->input('type');
        // $company_id='company_202012281339503129654415';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'type','value'=>$type],
        ];

        $where=get_list_where($search);
        $select=['self_id','name','social_flag'];
        $data['info']=SystemUser::where($where)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    员工导入     /tms/user/import
     */
    public function import(Request $request){
        $table_name         ='tms_address_contact';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建地址';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数


        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $file_id            =$request->input('file_id');
        //
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/import/TMS地址导入文件范本.xlsx";
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
//            dump($res);
            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }

//            dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '姓名' =>['Y','Y','30','name'],
                '性别' =>['Y','Y','30','sex'],
                '身份证号' =>['Y','Y','30','identity_num'],
                '出生日期' =>['N','Y','50','birthday'],
                '年龄' =>['N','Y','30','age'],
                '手机号' =>['Y','Y','64','tel'],
                '学历' =>['Y','Y','64','education_background'],
                '部门' =>['Y','Y','64','department'],
                '职位' =>['Y','Y','30','type'],
                '入职时间' =>['Y','Y','64','entry_time'],
                '工龄' =>['N','Y','64','working_age'],
                '现居地' =>['Y','Y','200','now_address'],
                '住宿费' =>['N','Y','64','live_cost'],
                '是否参加社保' =>['N','Y','64','social_flag'],
                '基本工资' =>['N','Y','64','salary'],
                '身份证地址' =>['N','Y','200','id_address'],
                '离职时间' =>['N','Y','64','leave_time'],
                '身份证有效期截止' =>['N','Y','30','id_validity'],
                '准驾车型' =>['N','Y','30','drive_type'],
                '驾驶证有效期截止' =>['N','Y','30','drive_validity'],
                '驾驶证发证机构' =>['N','Y','100','drive_organ'],
                '资格证号' =>['N','Y','30','nvq_num'],
                '资格证有效期截止' =>['N','Y','30','nvq_validity'],
                '资格证发证机构' =>['N','Y','100','nvq_organ'],
            ];
            $ret=arr_check($shuzu,$info_check);

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

//             dd($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['section_name','=',$v['department']],
                    ['group_code','=',$user_info->group_code],
                ];
                $section = SystemSection::where($where)->select('self_id','section_name','group_code')->first();
//                if ($section){
//
//                }else{
//                    $strs .= '数据中的第'.$a."行部门不存在".'</br>';
//                    $cando='N';
//                    $abcd++;
//                }

                if ($v['type'] == '驾驶员'){
                    $type = 'driver';
                }elseif($v['type'] == '押运员'){
                    $type = 'cargo';
                }elseif($v['type'] == '管理人员'){
                    $type = 'manager';
                }elseif($v['type'] == '驾驶员/押运员'){
                    $type = 'dr_cargo';
                }
                if($v['sex'] == '男'){
                    $sex = 'Y';
                }else{
                    $sex = 'N';
                }
                if($v['social_flag'] == '是'){
                    $social_flag = 'Y';
                }else{
                    $social_flag = 'N';
                }
                if(preg_match("/^[1-9]([0-9a-zA-Z]{17}|[0-9a-zA-Z]{14})$/",$v['identity_num'])){
                    $list['identity_num']=$v['identity_num'];
                }else{
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."身份证号填写错误".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $list=[];
                if($cando =='Y'){
                    $list['self_id']                 = generate_id('user_');
                    $list['name']                    = $v['name'];
                    $list['sex']                     = $sex;
                    $list['identity_num']            = $v['identity_num'];
                    $year = substr($v['identity_num'],6,4);
                    $month = substr($v['identity_num'],10,2);
                    $day = substr($v['identity_num'],12,2);
                    $now_year = date('Y',time());
                    $list['birthday']                = $year.'-'.$month.'-'.$day;
                    $list['age']                     = $now_year-$year;
                    $list['tel']                     = $v['tel'];
                    $list['education_background']    = $v['education_background'];
//                    $list['department']              = $section->self_id;
                    $list['type']                    = $type;
                    if (is_numeric($v['entry_time'])){
                        $list['entry_time']              = gmdate('Y-m-d',($v['entry_time'] - 25569) * 3600 * 24);
                    }else{
                        $list['entry_time']              = $v['entry_time'];
                    }
                    $work_time = strtotime($list['entry_time']);
                    $work_time1 = date('Y',$work_time);
                    $work_time2 = date('m',$work_time);
                    $work = $now_year-$work_time1;

                    if ($work==0){
                        $work_age = (date('m',time())-$work_time2).'个月';
                    }elseif($work == 1){
                        if(12-$work_time2+date('m',time()) == 12){
                            $work_age = '1年';
                        }else{
                            $work_age = (12-$work_time2+date('m',time())).'个月';
                        }
                    }else{
                        if ($work_time2>date('m',time())){
                            $work_age = ($work-1).'年'.(12-$work_time2+date('m',time())).'个月';
                        }else{
                            $work_age = $work.'年'.(date('m',time())-$work_time2).'个月';
                        }

                    }
                    $list['working_age']             = $work_age;
                    $list['now_address']             = $v['now_address'];
                    $list['live_cost']               = $v['live_cost'];
                    $list['social_flag']             = $social_flag;
                    $list['salary']                  = $v['salary'];
                    $list['id_address']              = $v['id_address'];
                    $list['leave_time']              = $v['leave_time'];
                    if ($v['id_validity']){
                        if(date('Y-m-d',strtotime($v['id_validity'])) == $v['id_validity']){

                        }else{
                            $strs .= '数据中的第'.$a."行日期格式错误".'</br>';
//                          $cando='N';
//                          $abcd++;
                        }
                    }
                    $list['id_validity']             = $v['id_validity'];
                    $list['drive_type']              = $v['drive_type'];
                    $list['drive_validity']          = $v['drive_validity'];
                    $list['drive_organ']             = $v['drive_organ'];
                    $list['nvq_num']                 = $v['nvq_num'];
                    $list['nvq_validity']            = $v['nvq_validity'];
                    $list['nvq_organ']               = $v['nvq_organ'];
                    $list['create_time']             = $list['update_time']=$now_time;
                    $list['file_id']                 = $file_id;
                    $list['group_code']              = $user_info->group_code;
                    $list['group_name']              = $user_info->group_name;
                    $list['create_user_id']          = $user_info->create_user_id;
                    $list['create_user_name']        = $user_info->create_user_name;
                    $datalist[]=$list;
                }
                $a++;

            }

            $operationing->new_info=$datalist;

            //dump($operationing);

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= SystemUser::insert($datalist);

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

    /***    员工详情     /tms/user/details
     */
    public function  details(Request $request,Details $details){
        $user_type    =array_column(config('tms.user_type'),'name','key');
        $background   =array_column(config('tms.background'),'name','key');
        $self_id=$request->input('self_id');
        $table_name='system_user';
        $select=['self_id','name','tel','department','identity_num','entry_time','leave_time','social_flag','live_cost','education_background','now_address','driver_license','nvq','safe_reward','contract'
            ,'group_insurance','identity_front','identity_back','use_flag','delete_flag','create_time','update_time','group_code','group_name','type','contract_back','license_back','work_license','birthday',
            'sex','age','contract_date','working_age','id_validity','salary','drive_type','nvq_num','nvq_organ','nvq_validity','drive_organ','drive_validity','id_address','driver_nvq_num','driver_nvq_validity','driver_nvq_organ','driver_nvq'
        ];
        // $self_id='address_202012301359512962811465';
        $info=$details->details($self_id,$table_name,$select);

        if($info){
            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->driver_license     =img_for($info->driver_license,'no_json');
            $info->nvq                =img_for($info->nvq,'no_json');
            $info->contract           =img_for($info->contract,'no_json');
            $info->identity_front     =img_for($info->identity_front,'no_json');
            $info->identity_back      =img_for($info->identity_back,'no_json');
            $info->contract_back      =img_for($info->contract_back,'no_json');
            $info->license_back       =img_for($info->license_back,'no_json');
            $info->work_license       =img_for($info->work_license,'more');
            $info->type_show               =$user_type[$info->type]??null;
            $info->education_background    =$background[$info->education_background]??null;
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

    /***    人员导出     /tms/user/execl
     */
    public function execl(Request $request,File $file){
        $background   =array_column(config('tms.background'),'name','key');
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $ids            =$request->input('ids');
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
            ];
            $where=get_list_where($search);

            $select=['self_id','name','tel','department','identity_num','entry_time','leave_time','social_flag','live_cost','education_background','now_address','driver_license','nvq','safe_reward','contract'
                ,'group_insurance','identity_front','identity_back','use_flag','delete_flag','create_time','update_time','group_code','group_name','type','contract_back','license_back','work_license',
                'birthday','sex','age','contract_date','working_age','id_validity','salary','drive_type','nvq_num','nvq_organ','nvq_validity','drive_organ','drive_validity','id_address',
                'driver_nvq_num','driver_nvq_validity','driver_nvq_organ','driver_nvq'];
            $select1 = ['self_id','section_name'];
            $info=SystemUser::with(['SystemSection' => function($query) use($select1){
                $query->select($select1);
            }])->where($where)->whereIn('self_id',explode(',',$ids))->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "name"=>'姓名',
                    "sex"=>'性别',
                    "identity_num"=>'身份证号',
                    "birthday"=>'出生日期',
                    "age"=>'年龄',
                    "tel"=>'手机号',
                    "education_background"=>'学历',
                    "department"=>'部门',
                    "type"=>'职位',
                    "entry_time"=>'入职时间',
                    "working_age"=>'工龄',
                    "now_address"=>'现居地',
                    "live_cost"=>'住宿费',
                    "social_flag"=>'是否参加社保',
                    "salary"=>'基本工资',
                    "id_address"=>'身份证地址',
                    "leave_time"=>'离职时间',
                    "id_validity"=>'身份证有效期截止',
                    "drive_type"=>'准驾车型',
                    "drive_validity"=>'驾驶证有效期截止',
                    "drive_organ"=>'驾驶证发证机构',
                    "nvq_num"=>'资格证号',
                    "nvq_validity"=>'资格证有效期截止',
                    "nvq_organ"=>'资格证发证机构',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];

                foreach ($info as $k=>$v){
                    $list=[];
                    if ($v->type == 'driver'){
                        $type = '驾驶员';
                    }elseif($v->type == 'cargo'){
                        $type = '押运员';
                    }elseif($v->type == 'dr_cargo'){
                        $type = '驾驶员/押运员';
                    }else{
                        $type = '管理人员';
                    }
                    $list['id']=($k+1);
                    $list['name']=$v->name;
                    if ($v->sex == 'Y'){
                        $sex = '男';
                    }else{
                        $sex = '女';
                    }
                    $list['sex']=$sex;
                    $list['identity_num']=$v->identity_num;
                    $list['birthday']=$v->birthday;
                    $list['age']=$v->age;
                    $list['tel']=$v->tel;
                    $list['education_background']=$background[$v->education_background]??null;;
                    $list['department']=$v->SystemSection->section_name;
                    $list['type']=$type;
                    $list['entry_time']=$v->entry_time;
                    $list['working_age']=$v->working_age;
                    $list['now_address']=$v->now_address;
                    $list['live_cost']=$v->live_cost;
                    if ($v->social_flag == 'Y'){
                        $social_flag = '是';
                    }else{
                        $social_flag = '否';
                    }
                    $list['social_flag']=$social_flag;
                    $list['salary']=$v->salary;
                    $list['id_address']=$v->id_address;
                    $list['leave_time']=$v->leave_time;
                    $list['id_validity']=$v->id_validity;
                    $list['drive_type']=$v->drive_type;
                    $list['drive_validity']=$v->drive_validity;
                    $list['drive_organ']=$v->drive_organ;
                    $list['nvq_num']=$v->nvq_num;
                    $list['nvq_validity']=$v->nvq_validity;
                    $list['nvq_organ']=$v->nvq_organ;

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
     * 打印  tms/user/printUser
     * */
    public function printUser(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='null';

        $operationing->access_cause='打印分拣单';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        $self_id=$request->input('self_id');

        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];


        $select=['self_id','name','tel','department','identity_num','entry_time','leave_time','social_flag','live_cost','education_background','now_address','safe_reward',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','type',
        ];
        $select1 = ['self_id','section_name'];
        $info=SystemUser::with(['SystemSection' => function($query) use($select1){
            $query->select($select1);
        }])->where($where)->whereIn('self_id',explode(',',$ids))
           ->orderBy('create_time', 'desc')
           ->select($select)
           ->get();


        $tms_control_type        =array_column(config('tms.tms_control_type'),'name','key');
        $info=WmsTotal::with(['wmsOutOrder' => function($query)use($order_select,$order_list_select,$wms_out_sige_select,$good_select,$group_select,$shop_select){
            $query->where('delete_flag','=','Y');
            $query->select($order_select);
            $query->with(['wmsOutOrderList' => function($query)use($order_list_select){
                $query->select($order_list_select);
                $query->where('delete_flag','=','Y');
            }]);
            $query->with(['wmsShop' => function($query)use($shop_select){
                $query->select($shop_select);
                $query->where('delete_flag','=','Y');
            }]);
            $query->with(['wmsOutSige' => function($query)use($wms_out_sige_select,$good_select){
                $query->where('delete_flag','=','Y');
                $query->select($wms_out_sige_select);
                $query->orderBy('area','asc')
                    ->with(['wmsGoods' => function($query)use($good_select){
                        $query->where('delete_flag','=','Y');
                        $query->select($good_select);
                    }]);
            }]);
        }])
            ->with(['wmsGroup' => function($query)use($group_select){
                $query->where('delete_flag','=','Y');
                $query->select($group_select);
            }])
            ->where($where)
            ->select($total_select)->first();

//dd($info->toArray());


        if($info){

            $out_list=[];

            foreach ($info->wmsOutOrder as $k => $v){

                $quhuo=[];
                $abc=[];
                //DUMP($v->ToArray());
                foreach ($v->wmsOutOrderList as $kk => $vv){
                    // DUMP($vv->ToArray());
                    $abc['order_id']=$v->self_id;
                    $abc['shop_external_id']=$v->shop_external_id;
                    $abc['shop_name']=$v->shop_name;
                    $abc['create_time']=$v->create_time;
                    $abc['total_time']=$v->total_time;
                    $abc['delivery_time']=$v->delivery_time;
                    $abc['recipt_code']=$vv->recipt_code;
                    $abc['shop_code']=$vv->shop_code;
                    $abc['shop_address']=$v->shop_address;
                    $abc['contact_tel']=$v->shop_contacts.'  '.$v->shop_tel;
                    $abc['pay_type']=$info->wmsGroup->pay_type;
                    $abc['warehouse_name']=$v->warehouse_name;
                    $abc['company_name']=$v->company_name;
                    $abc['line_code']=$v->wmsShop->line_code;
                    $abc['shop_num']=$v->wmsShop->external_id;

//dump($abc);
                    if($vv->quehuo == 'Y'){
                        $list2['external_sku_id']    =$vv->external_sku_id;
                        $list2['good_name']          =$vv->good_name;
                        $list2['spec']               =$vv->spec;
                        $list2['num']                =$vv->quehuo_num;


                        $quhuo[]=$list2;
                        $abc['quhuo']=$quhuo;
                        $abc['quhuo_flag']='Y';
                    }else{
                        $abc['quhuo']=null;
                        $abc['quhuo_flag']='N';
                    }

                }
                $int_cold_num = 0;
                $int_freeze_num = 0;
                $int_normal_num = 0;

                if($v->wmsOutSige){
                    $abc['out_flag']='Y';
                    $order=[];
                    foreach ($v->wmsOutSige as $kkk => $vvv){
                        $list['shop_name']          =$vvv->shop_name;
                        $list['external_sku_id']    =$vvv->external_sku_id;
                        $list['good_name']          =$vvv->good_name;
                        $list['good_english_name']  =$vvv->good_english_name;
                        $list['spec']               =$vvv->spec;
                        $list['num']                =$vvv->num;
                        $warehouseType = WmsWarehouseArea::with(['wmsWarm' => function ($query){
                            $query->select(['self_id','control']);
                        }])->where('self_id',$vvv->area_id)->select(['self_id','warm_id'])->first();
                        if ($warehouseType->wmsWarm->control == 'freeze'){
                            if($vvv->good_unit == '箱'){
                                $int_cold_num += $vvv->num;
                            }
                        }elseif ($warehouseType->wmsWarm->control == 'refrigeration'){
                            if($vvv->good_unit == '箱'){
                                $int_freeze_num += $vvv->num;
                            }
                        }elseif($warehouseType->wmsWarm->control == 'normal'){
                            if($vvv->good_unit == '箱'){
                                $int_normal_num += $vvv->num;
                            }
                        }
                        $list['good_unit']          =$vvv->good_unit;
                        $list['sign']               =$vvv->area.'-'.$vvv->row.'-'.$vvv->column.'-'.$vvv->tier;
                        $list['production_date']    =$vvv->production_date;
                        $list['expire_time']        =$vvv->expire_time;
                        $list['good_describe']      =unit_do($vvv->good_unit , $vvv->good_target_unit, $vvv->good_scale, $vvv->num);
                        $list['price']              =$vvv->price;
                        $list['total_money']        =(float)$vvv->price * $vvv->num;
                        $list['control']            = $tms_control_type[$warehouseType->wmsWarm->control]?? null;
                        $order[]=$list;
                        $abc['info']=$order;

                    }
                    $abc['int_cold']=$int_cold_num;
                    $abc['int_freeze']=$int_freeze_num;
                    $abc['int_normal']=$int_normal_num;
                    $out_list[]=$abc;

                }else{
                    $abc['out_flag']='N';
                    $abc['info']=$order;

                }

            }

            $operationing->table_id=$self_id;
            $operationing->old_info=null;
            $operationing->new_info=null;



            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$out_list;
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }

    /**验证身份证**/
    function getIDCardInfo($IDCard){
        $result['error']=0;//0：未知错误，1：身份证格式错误，2：无错误
        $result['flag']='';//0标示成年，1标示未成年
        $result['tdate']='';//生日，格式如：2012-11-15
        if(!eregi("^[1-9]([0-9a-zA-Z]{17}|[0-9a-zA-Z]{14})$",$IDCard)){
            $result['error']=1;
            return $result;
        }else{
            if(strlen($IDCard)==18){
                $tyear=intval(substr($IDCard,6,4));
                $tmonth=intval(substr($IDCard,10,2));
                $tday=intval(substr($IDCard,12,2));
                if($tyear>date("Y")||$tyear<(date("Y")-100)){
                    $flag=0;
                }elseif($tmonth<0||$tmonth>12){
                    $flag=0;
                }elseif($tday<0||$tday>31){
                    $flag=0;
                }else{
                    $tdate=$tyear."-".$tmonth."-".$tday." 00:00:00";
                    if((time()-mktime(0,0,0,$tmonth,$tday,$tyear))>18*365*24*60*60){
                        $flag=0;
                    }else{
                        $flag=1;
                    }
                }
            }elseif(strlen($IDCard)==15){
                $tyear=intval("19".substr($IDCard,6,2));
                $tmonth=intval(substr($IDCard,8,2));
                $tday=intval(substr($IDCard,10,2));
                if($tyear>date("Y")||$tyear<(date("Y")-100)){
                    $flag=0;
                }elseif($tmonth<0||$tmonth>12){
                    $flag=0;
                }elseif($tday<0||$tday>31){
                    $flag=0;
                }else{
                    $tdate=$tyear."-".$tmonth."-".$tday." 00:00:00";
                    if((time()-mktime(0,0,0,$tmonth,$tday,$tyear))>18*365*24*60*60){
                        $flag=0;
                    }else{
                        $flag=1;
                    }
                }
            }
        }
        $result['error']=2;//0：未知错误，1：身份证格式错误，2：无错误
        $result['isAdult']=$flag;//0标示成年，1标示未成年
        $result['birthday']=$tdate;//生日日期
        return $result;
    }

}
?>

