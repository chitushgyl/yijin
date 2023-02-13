<?php
namespace App\Http\Admin\Tms;

use App\Models\Group\SystemGroup;
use App\Models\Group\SystemUser;
use App\Models\Tms\TmsMoney;
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
        $car_number     =$request->input('car_number');
        $user_id        =$request->input('user_id');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'user_id','value'=>$user_id],
        ];


        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','user_id'];
        $select1=['self_id','name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
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
        $data['department_type']        =config('tms.department_type');
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
        $type                    =$request->input('type');//violation 违规 rule违章 accident事故 reward奖励
        $user_id                 =$request->input('user_id');//驾驶员
        $user_name               =$request->input('user_name');//驾驶员
        $escort                  =$request->input('escort');//押运员
        $car_id                  =$request->input('car_id');//
        $car_number              =$request->input('car_number');// 车牌号
        $violation_connect       =$request->input('violation_connect');//违章内容
        $handle_connect          =$request->input('handle_connect');//处理情况
        $score                   =$request->input('score');//扣分情况
        $payment                 =$request->input('payment');//罚款
        $late_fee                =$request->input('late_fee');//滞纳金
        $reward_view             =$request->input('reward_view');//是否有安全奖
        $safe_reward             =$request->input('safe_reward');//奖金
        $group_code              =$request->input('group_code');
        $handled_by              =$request->input('handled_by');//经办人
        $remark                  =$request->input('remark');//备注
        $event_time              =$request->input('event_time');//事件时间
        $fault_address           =$request->input('fault_address');//事故地点
        $fault_price             =$request->input('fault_price');//损失金额
        $fault_party             =$request->input('fault_party');//责任方

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
            switch($type){
                case 'violation':
                    $data['escort']                 =$escort;
                    $data['violation_connect']      =$violation_connect;
                    $data['payment']                =$payment;
                    break;
                case 'rule':
                    $data['handle_connect']         =$handle_connect;
                    $data['score']                  =$score;
                    $data['payment']                =$payment;
                    break;
                case 'accident':
                    $data['fault_address']          =$fault_address;
                    $data['fault_price']            =$fault_price;
                    $data['fault_party']            =$fault_party;
                    break;
                case 'reward':
                    $data['escort']                 =$escort;
                    $data['safe_reward']            =$safe_reward;
                    $data['reward_view']            =$reward_view;
                    break;
            }

            $data['car_id']                 =$car_id;
            $data['user_id']                =$user_id;
            $data['user_name']              =$user_name;
            $data['car_number']             =$car_number;
            $data['type']                   =$type;
            $data['handled_by']             =$handled_by;
            $data['remark']                 =$remark;
            $data['event_time']             =$event_time;


            /**保存费用**/
//            if ($payment || $late_fee || $safe_reward){
//                if ($safe_reward){
//                    $money['money']              = $safe_reward;
//                }else{
//                    $money['money']              = $payment;
//                }
//            }
//            $money['pay_type']           = 'reward';
//            $money['pay_state']          = 'Y';
//            $money['car_id']             = $car_id;
//            $money['car_number']         = $car_number;
//            $money['process_state']      = 'Y';
//            $money['type_state']         = 'out';

            $wheres['self_id'] = $self_id;
            $old_info=UserReward::where($wheres)->first();
            if($old_info){

                $data['update_time']=$now_time;
                $id=UserReward::where($wheres)->update($data);
                $operationing->access_cause='修改员工奖惩记录';
                $operationing->operation_type='update';


            }else{
                $data['self_id']            =generate_id('reward_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         =$group_code;
                $data['group_name']         =$group_name;

                $id=UserReward::insert($data);
//                if ($payment || $late_fee || $safe_reward){
//                    $money['self_id']            = generate_id('money');
//                    $money['group_code']         = $group_code;
//                    $money['group_name']         = $group_name;
//                    $money['create_user_id']     = $user_info->admin_id;
//                    $money['create_user_name']   = $user_info->name;
//                    $money['create_time']        =$money['update_time']=$now_time;
//                    TmsMoney::insert($money);
//                }

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
        $table_name         ='user_reward';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入奖惩记录';
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
                // dump($cando);
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('addresss_');
                    $list['sheng_name']         = $v['sheng_name'];
                    $list['qu_name']            = $v['qu_name'];
                    $list['shi_name']           = $v['shi_name'];
                    $list['address']            = $v['address'];

//                    $list['group_code']         = $info->group_code;
//                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
//                    $list['company_id']         = $info->self_id;
//                    $list['company_name']       = $info->company_name;
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
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','user_id'];
        // $self_id='address_202012301359512962811465';
        $select1 = ['self_id','name'];
        $info=$details->details($self_id,$table_name,$select);
        $info = UserReward::with(['systemUser' => function($query) use($select1){
            $query->select($select1);
        }])->where('self_id',$self_id)->select($select)->first();

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

    /***    地址导出     /tms/userReward/excel
     */
    public function excel(Request $request,File $file){
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

            $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
                'use_flag','delete_flag','create_time','update_time','group_code','group_name','user_id'];
            $select1=['self_id','name'];
            $info=UserReward::with(['systemUser' => function($query) use($select1){
                $query->select($select1);
            }])->where($where)
                ->orderBy('create_time', 'desc')
                ->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "user_name"=>'人员',
                    "department"=>'部门',
                    "car_number"=>'车牌号',
                    "score"=>'扣分情况',
                    "handle_connect"=>'处理情况',
                    "payment"=>'交款情况',
                    "late_fee"=>'滞纳金',
                    "handle_opinion"=>'处理意见',
                    "safe_flag"=>'是否有安全奖',
                    "safe_reward"=>'安全奖金',
                ]];


                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['user_name']=$v->systemUser->name;
                    if($v['department'] == 1){
                        $list['department']='运管';
                    }elseif($v['department'] == 2){
                        $list['department']='交警';
                    }else{
                        $list['department']='高速交警';
                    }
                    $list['car_number']=$v->car_number;
                    $list['score']=$v->score;
                    $list['handle_connect']=$v->handle_connect;
                    $list['payment']=$v->payment;
                    $list['late_fee']=$v->late_fee;
                    $list['handle_opinion']=$v->handle_opinion;
                    if ($v->safe_flag == 'Y'){
                        $list['safe_flag']='是';
                    }else{
                        $list['safe_flag']='无';
                    }
                    $list['safe_flag']=$v->safe_flag;
                    $list['safe_reward']=$v->safe_reward;

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

