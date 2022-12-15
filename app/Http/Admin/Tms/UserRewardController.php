<?php
namespace App\Http\Admin\Tms;

use App\Models\Group\SystemGroup;
use App\Models\Group\SystemUser;
use App\Models\User\UserReward;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Http\Controllers\FileController as File;


class UserRewardController extends CommonController{

    /***    员工奖惩列表头部     /tms/userReward/userRewardList
     */
    public function  userRewardList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='员工奖惩信息';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/TMS地址导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    员工奖惩记录分页      /tms/userReward/userRewardPage
     */
    public function userRewardPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'car_number','value'=>$car_number],
        ];


        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])
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



    /***    添加员工奖惩记录    /tms/userReward/createUserReward
     */
    public function createUserReward(Request $request){
        $data['tms_control_type']        =config('tms.department_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name'];
        $data['info']=UserReward::where($where)->select($select)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    员工数据提交      /tms/userReward/addUserReward
     */
    public function addUserReward(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='user_reward';

        $operationing->access_cause     ='创建/修改员工奖惩记录';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();

        /** 接收数据*/
        $self_id                 =$request->input('self_id');
        $car_id                  =$request->input('car_id');//
        $car_number              =$request->input('car_number');// 车牌号
        $violation_address       =$request->input('violation_address');//违章地址
        $violation_connect       =$request->input('violation_connect');//违章内容
        $department              =$request->input('department');//部门
        $handle_connect          =$request->input('handle_connect');//处理情况
        $score                   =$request->input('score');//扣分情况
        $payment                 =$request->input('payment');//交款情况
        $late_fee                =$request->input('late_fee');//滞纳金
        $handle_opinion          =$request->input('handle_opinion');//处理意见
        $safe_flag               =$request->input('safe_flag');//是否有安全奖
        $safe_reward             =$request->input('safe_reward');//安全奖金
        $group_code              =$request->input('group_code');

        $rules=[
            'car_id'=>'required',

        ];
        $message=[
            'car_id.required'=>'请选择车辆',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $data['car_id']                 =$car_id;
            $data['car_number']             =$car_number;
            $data['violation_address']      =$violation_address;
            $data['violation_connect']      =$violation_connect;
            $data['department']             =$department;
            $data['handle_connect']         =$handle_connect;
            $data['score']                  =$score;
            $data['payment']                =$payment;
            $data['late_fee']               =$late_fee;
            $data['handle_opinion']         =$handle_opinion;
            $data['safe_flag']              =$safe_flag;
            $data['safe_reward']            =$safe_reward;

            $wheres['self_id'] = $self_id;
            $old_info=UserReward::where($wheres)->first();
            if($old_info){

                $data['update_time']=$now_time;
                $id=UserReward::where($wheres)->update($data);
                $operationing->access_cause='修改员工奖惩记录';
                $operationing->operation_type='update';


            }else{
                $data['self_id']            =generate_id('reward_');
//                $data['create_user_id']     =$user_info->admin_id;
//                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         =$group_code;
                $data['group_name']         =$group_name;

                $id=UserReward::insert($data);
                $operationing->access_cause='添加员工奖惩记录';
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



    /***    员工奖惩记录禁用/启用      /tms/userReward/userRewardFlag
     */
    public function userRewardFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='user_reward';
        $self_id=$request->input('self_id');
        $flag='use_flag';
//        $self_id='address_202103011352018133677963';
        $old_info = UserReward::where('self_id',$self_id)->select('group_code','group_name','use_flag','delete_flag','update_time')->first();
        $update['use_flag'] = 'N';
        $update['update_time'] = $now_time;
        $id = UserReward::where('self_id',$self_id)->update($update);

        $operationing->access_cause='启用/禁用员工奖惩记录';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$update;
        $operationing->operation_type=$flag;
        if($id){
            $msg['code']=200;
            $msg['msg']='操作成功！';
            $msg['data']=(object)$update;
        }else{
            $msg['code']=300;
            $msg['msg']='操作失败！';
        }

        return $msg;


    }

    /***    员工奖惩记录删除     /tms/userReward/userRewardDelFlag
     */
    public function userRewardDelFlag(Request $request,Status $status){

        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='user_reward';
        $self_id=$request->input('self_id');
        $flag='delete_flag';
//        $self_id='address_202103011352018133677963';
        $old_info = UserReward::where('self_id',$self_id)->select('group_code','group_name','use_flag','delete_flag','update_time')->first();
        $update['delete_flag'] = 'N';
        $update['update_time'] = $now_time;
        $id = UserReward::where('self_id',$self_id)->update($update);

        $operationing->access_cause='删除员工奖惩记录';
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

    /***    地址导入     /tms/address/import
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
        $company_id         =$request->input('company_id');
        $file_id            =$request->input('file_id');
        //
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/import/TMS地址导入文件范本.xlsx";
        $input['company_id']       =$company_id='company_202012291153523141320375';
         ***/
        $rules = [
            'company_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'company_id.required' => '请选择业务公司',
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
                '省份' =>['Y','Y','64','sheng_name'],
                '城市' =>['Y','Y','64','shi_name'],
                '区县' =>['Y','Y','64','qu_name'],
                '详细地址' =>['Y','Y','64','address'],
                '联系人' =>['Y','Y','64','contacts'],
                '联系电话' =>['Y','Y','64','tel'],
            ];
            $ret=arr_check($shuzu,$info_check);


            // dump($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }

            $info_wait=$ret['new_array'];
            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$company_id],
            ];

            $info= TmsGroup::where($where_check)->select('self_id','company_name','group_code','group_name')->first();
            // dd($info->toArray());
            if(empty($info)){
                $msg['code'] = 305;
                $msg['msg'] = '业务公司不存在';
                return $msg;
            }

//            dd($info);
            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            // dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){

                $where_address=[
                    ['name','=',$v['qu_name']],
                    ['level','=',3],
                ];

                $where_address2=[
                    ['name','=',$v['shi_name']],
                    ['level','=',2],
                ];
                $where_address3=[
                    ['name','=',$v['sheng_name']],
                    ['level','=',1],
                ];

                $selectMenu=['id','name','parent_id'];
                $address_info=SysAddress::with(['sysAddress' => function($query)use($selectMenu,$where_address2,$where_address3) {
                    $query->where($where_address2);
                    $query->select($selectMenu);
                    $query->with(['sysAddress' => function($query)use($selectMenu,$where_address3) {
                        $query->where($where_address3);
                        $query->select($selectMenu);
                    }]);
                }])->where($where_address)->select($selectMenu)->first();

                if(empty($address_info)){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行区不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }else{
                    if(empty($address_info->sysAddress)){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行市不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }else{
                        if(empty($address_info->sysAddress->sysAddress)){
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行省不存在".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                $location = bd_location(2,$v['sheng_name'],$v['shi_name'],$v['qu_name'],$v['address']);
                // dump($cando);
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('addresss_');
                    $list['sheng_name']         = $v['sheng_name'];
                    $list['qu_name']            = $v['qu_name'];
                    $list['shi_name']           = $v['shi_name'];
                    $list['sheng']              = $address_info->sysAddress->sysAddress->id;
                    $list['shi']                = $address_info->sysAddress->id;
                    $list['qu']                 = $address_info->id;
                    $list['address']            = $v['address'];
                    $list['longitude']          = $location ? $location['lng'] : '';
                    $list['dimensionality']     = $location ? $location['lat'] : '';
                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['company_id']         = $info->self_id;
                    $list['company_name']       = $info->company_name;
                    $list['contacts']       = $v['contacts'];
                    $list['tel']       = $v['tel'];
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
            $id= TmsAddressContact::insert($datalist);

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

    /***    员工奖惩记录详情     /tms/userReward/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='user_reward';
        $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name'];
        // $self_id='address_202012301359512962811465';
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

    /***    地址导出     /tms/address/execl
     */
    public function execl(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
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

            $select=['self_id','sheng_name','company_name','shi_name','qu_name','address','contacts','tel'];
            $info=TmsAddressContact::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "company_name"=>'业务往来公司',
                    "sheng_name"=>'省份',
                    "shi_name"=>'城市',
                    "qu_name"=>'区县',
                    "address"=>'详细地址',
                    "contacts"=>'联系人',
                    "tel"=>'联系电话',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['company_name']=$v->company_name;
                    $list['sheng_name']=$v->sheng_name;
                    $list['shi_name']=$v->shi_name;
                    $list['qu_name']=$v->qu_name;
                    $list['address']=$v->address;
                    $list['contacts']=$v->address;
                    $list['tel']=$v->address;

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

