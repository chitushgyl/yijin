<?php
namespace App\Http\Admin\Tms;

use App\Models\Group\SystemGroup;
use App\Models\Group\SystemUser;
use App\Models\Tms\AwardRemind;
use App\Models\Tms\TmsCar;
use App\Models\Tms\TmsMoney;
use App\Models\User\UserReward;
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
        $type    =$request->input('type');

        $abc='员工奖惩信息';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/TMS地址导入文件范本.xlsx',
        ];
        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($data['button_info'] as $k => $v){
            if($v->id == 152){
                $button_info1[]=$v;
            }
            if($v->id == 155){
                $button_info2[]=$v;
            }
            if($v->id == 158){
                $button_info3[]=$v;
            }
            if($v->id == 161){
                $button_info4[]=$v;
            }
            if($v->id == 211){
                $button_info1[]=$v;
                $button_info2[]=$v;
                $button_info3[]=$v;
                $button_info4[]=$v;
            }
            if($v->id == 212){
                $button_info1[]=$v;
                $button_info2[]=$v;
                $button_info3[]=$v;
                $button_info4[]=$v;
            }

        }

            if ($type == 'violation'){
                $data['button_info']=$button_info1;
                $data['import_info']    =[
                'import_text'=>'下载'.$abc.'导入示例文件',
                'import_color'=>'#FC5854',
                'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/违规导入.xlsx',
            ];
            }elseif($type == 'rule'){
                $data['button_info']=$button_info2;
                $data['import_info']    =[
                'import_text'=>'下载'.$abc.'导入示例文件',
                'import_color'=>'#FC5854',
                'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/违章导入.xlsx',
            ];
            }elseif($type == 'accident'){
                $data['button_info']=$button_info3;
                $data['import_info']    =[
                'import_text'=>'下载'.$abc.'导入示例文件',
                'import_color'=>'#FC5854',
                'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/事故导入.xlsx',
            ];
            }elseif($type == 'reward'){
                $data['button_info']=$button_info4;
                $data['import_info']    =[
                'import_text'=>'下载'.$abc.'导入示例文件',
                'import_color'=>'#FC5854',
                'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/奖励导入.xlsx',
            ];
            }


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
        $type           =$request->input('type');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $self_id        =$request->input('self_id');
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
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'user_id','value'=>$user_id],
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'>=','name'=>'event_time','value'=>$start_time],
            ['type'=>'<=','name'=>'event_time','value'=>$end_time],
            ['type'=>'like','name'=>'self_id','value'=>$self_id],
        ];


        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear','company_fine','escort_name','user_id'];
        $select1=['self_id','name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['user' => function($query) use($select1){
                        $query->select($select1);
                    }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                if ($type == 'reward'){
                    $data['price']=UserReward::where($where)->sum('safe_reward');
                }else{
                    $data['price']=UserReward::where($where)->sum('payment');
                    $data['total_price']=UserReward::where($where)->sum('company_fine');
                }

                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['user' => function($query) use($select1){
                        $query->select($select1);
                    }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                if ($type == 'reward'){
                    $data['price']=UserReward::where($where)->sum('safe_reward');
                }else{
                    $data['price']=UserReward::where($where)->sum('payment');
                    $data['total_price']=UserReward::where($where)->sum('company_fine');
                }
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['user' => function($query) use($select1){
                        $query->select($select1);
                    }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                if ($type == 'reward'){
                    $data['price']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->sum('safe_reward');
                }else{
                    $data['price']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->sum('payment');
                    $data['price']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->sum('company_fine');
                }
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];
        $button_info4=[];
        foreach ($button_info as $k => $v){
            if($v->id == 152){
                $button_info1[]=$v;
            }
            if($v->id == 153){
                $button_info1[]=$v;
            }
            if($v->id == 154){
                $button_info1[]=$v;
            }
            if($v->id == 155){
                $button_info2[]=$v;
            }
            if($v->id == 156){
                $button_info2[]=$v;
            }
            if($v->id == 157){
                $button_info2[]=$v;
            }
            if($v->id == 158){
                $button_info3[]=$v;
            }
            if($v->id == 159){
                $button_info3[]=$v;
            }
            if($v->id == 160){
                $button_info3[]=$v;
            }
            if($v->id == 161){
                $button_info4[]=$v;
            }
            if($v->id == 162){
                $button_info4[]=$v;
            }
            if($v->id == 163){
                $button_info4[]=$v;
            }
        }
        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
            if ($v->user){
                $v->escort = $v->user->name;
            }

            if ($v->type == 'violation'){
                $v->button_info=$button_info1;
            }elseif($v->type == 'rule'){
                $v->button_info=$button_info2;
            }elseif($v->type == 'accident'){
                $v->button_info=$button_info3;
            }elseif($v->type == 'reward'){
                $v->button_info=$button_info4;
            }
            $v->reward_id_show = substr($v->self_id,7);

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
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
         ,'cash_back','cash_flag','type','user_name','bear','escort_name','company_fine','user_id'];
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
        $reward_view             =$request->input('reward_view');//奖励详情
        $safe_reward             =$request->input('safe_reward');//奖金
        $group_code              =$request->input('group_code');
        $handled_by              =$request->input('handled_by');//经办人
        $remark                  =$request->input('remark');//备注
        $event_time              =$request->input('event_time');//事件时间
        $fault_address           =$request->input('fault_address');//事故地点
        $fault_price             =$request->input('fault_price');//损失金额
        $fault_party             =$request->input('fault_party');//责任方
        $cash_back               =$request->input('cash_back');//奖金返还
        $cash_flag               =$request->input('cash_flag');//奖金是否发放
        $bear                    =$request->input('bear');//承担多少责任
        $company_fine            =$request->input('company_fine');//公司罚款
        $escort_name             =$request->input('escort_name');//押运员名称
        $fine_user               =$request->input('fine_user');//罚款对象

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
                    $data['violation_connect']      =$violation_connect;
                    $data['payment']                =$payment;
                    $data['company_fine']           =$company_fine;
                    $data['cash_back']              =$cash_back;
                    break;
                case 'rule':
                    $data['handle_connect']         =$handle_connect;
                    $data['violation_connect']      =$violation_connect;
                    $data['score']                  =$score;
                    $data['payment']                =$payment;
                    $data['company_fine']           =$company_fine;
                    $data['cash_back']              =$cash_back;
                    break;
                case 'accident':
                    $data['fault_address']          =$fault_address;
                    $data['violation_connect']      =$violation_connect;
                    $data['fault_price']            =$fault_price;
                    $data['fault_party']            =$fault_party;
                    $data['bear']                   =$bear;
                    $data['score']                  =$score;
                    $data['payment']                =$payment;
                    $data['company_fine']           =$company_fine;
                    $data['cash_back']              =$cash_back;
                    break;
                case 'reward':
                    $data['safe_reward']            =$safe_reward;
                    $data['reward_view']            =$reward_view;
                    $data['cash_back']              =$cash_back;
                    $data['cash_flag']              =$cash_flag;
                    break;
            }

            $data['car_id']                 =$car_id;
            $data['user_id']                =$user_id;
            $data['user_name']              =$user_name;
            $data['escort']                 =$escort;
            $data['escort_name']            =$escort_name;
            $data['car_number']             =$car_number;
            $data['type']                   =$type;
            $data['handled_by']             =$handled_by;
            $data['remark']                 =$remark;
            $data['event_time']             =$event_time;

            $wheres['self_id'] = $self_id;
            $old_info=UserReward::where($wheres)->first();
            if($old_info){
                $user = AwardRemind::where('user_id',$user_id)->where('reward_id',$self_id)->first();
                $escort_user = AwardRemind::where('user_id',$escort)->where('reward_id',$self_id)->first();
                if ($user){
                    $update['user_id']            = $user_id;
                    $update['user_name']          = $user_name;
                    $update['car_id']             = $car_id;
                    $update['car_number']         = $car_number;
                    $update['money_award']        = $company_fine;
                    $update['cash_back']          = $cash_back;
                    $update['update_time']=$now_time;
                    AwardRemind::where('user_id',$user_id)->where('reward_id',$self_id)->update($update);
                }
                if ($escort_user){
                    $update['user_id']            = $escort;
                    $update['user_name']          = $escort_name;
                    $update['car_id']             = $car_id;
                    $update['car_number']         = $car_number;
                    $update['cash_back']          = $cash_back;
                    $update['money_award']        = $company_fine;
                    $update['update_time']=$now_time;
                    AwardRemind::where('user_id',$escort)->where('reward_id',$self_id)->update($update);
                }
//                if ($type == 'reward'){
//                    $update['user_id']            = $user_id;
//                    $update['user_name']          = $user_name;
//                    $update['money_award']        = $safe_reward;
//                    $time = date('Y-m', strtotime('+6 month', strtotime($event_time)));
//                    $update['cash_back']          = $time;
//                    $update['update_time']=$now_time;
//                    AwardRemind::where('reward_id',$self_id)->update($update);
//                }
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
                if ($type != 'reward'){
                    
                    if ($user_id && ($company_fine || $company_fine>0)){
                        $award['self_id']            = generate_id('award_');
                        $award['reward_id']          = $data['self_id'];
                        $award['user_id']            = $user_id;
                        $award['user_name']          = $user_name;
                        $award['car_id']             = $car_id;
                        $award['car_number']         = $car_number;
                        $award['money_award']        = $company_fine;
                        $time = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $award['cash_back']          = $cash_back;
                        $award['group_code']         = $group_code;
                        $award['group_name']         = $group_name;
                        $award['create_user_id']     = $user_info->admin_id;
                        $award['create_user_name']   = $user_info->name;
                        $award['create_time']        = $award['update_time']=$now_time;
                        AwardRemind::insert($award);
                    }
                    if ($escort && ($company_fine || $company_fine>0)){
                        $award['self_id']            = generate_id('award_');
                        $award['reward_id']          = $data['self_id'];
                        $award['user_id']            = $escort;
                        $award['user_name']          = $escort_name;
                        $award['car_id']             = $car_id;
                        $award['car_number']         = $car_number;
                        $award['money_award']        = $company_fine;
                        $time = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $award['cash_back']          = $cash_back;
                        $award['group_code']         = $group_code;
                        $award['group_name']         = $group_name;
                        $award['create_user_id']     = $user_info->admin_id;
                        $award['create_user_name']   = $user_info->name;
                        $award['create_time']        = $award['update_time']=$now_time;
                        AwardRemind::insert($award);
                    }

                }

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
        $reward = AwardRemind::where('reward_id',$self_id)->get();
        if ($reward){
            foreach ($reward as $key => $value){
                if ($value->cash_flag == 'N'){
                    AwardRemind::where('reward_id',$self_id)->update($update);
                }
            }
        }

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

    /***    违规导入     /tms/userReward/violationImport
     */
    public function violationImport(Request $request){
        $table_name         ='user_reward';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='违规导入';
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

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '日期' =>['Y','Y','64','event_time'],
                '车牌号' =>['Y','Y','64','car_number'],
                '驾驶员' =>['N','Y','64','user_name'],
                '押运员' =>['N','Y','64','escort_name'],
                '罚款' =>['Y','Y','64','company_fine'],
                '罚款对象'=>['Y','Y','64','fine_user'],
                '违规详情' =>['N','Y','64','violation_connect'],
                '奖金返还日期' =>['Y','Y','64','cash_back'],
                '经办人' =>['N','Y','64','handled_by'],
                '备注' =>['N','Y','64','remark'],
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
            $user_award_list[]=[];
            $award_list[]=[];
            // dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                if ($v['event_time']){
                    if (is_numeric($v['event_time'])){
                        $v['event_time']              = gmdate('Y-m-d',($v['event_time'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['event_time'])) == $v['event_time']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行发货日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                $car = TmsCar::where('car_number',$v['car_number'])->where('group_code',$group_code)->select('self_id','car_number')->first();
                if (!$car){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                 if ($v['user_name']){
                    $driver = SystemUser::whereIn('type',['driver','dr_cargo'])->where('name',$v['user_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$driver){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }
                if($v['escort_name']){
                    $cargo = SystemUser::whereIn('type',['cargo','dr_cargo'])->where('name',$v['escort_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$cargo){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行副驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }

                if ($v['cash_back']){
                    if (is_numeric($v['cash_back'])){
                        $v['cash_back']              = gmdate('Y-m',($v['cash_back'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['cash_back'])) == $v['cash_back'] || date('Y-m',strtotime($v['cash_back'])) == $v['cash_back']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行奖金返还日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }

                if($v['fine_user'] == '驾驶员'){
                    $fine_user = 'driver';
                }elseif($v['fine_user'] == '押运员'){
                    $fine_user = 'carge';
                }elseif($v['fine_user'] == '全部'){
                    $fine_user = 'all';
                }else{
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行罚款对象填写错误".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                // dump($cando);
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('reward_');
                    $list['type']               = 'violation';
                    $list['event_time']         = $v['event_time'];
                    $list['car_id']             = $car->self_id;
                    $list['car_number']         = $car->car_number;
                    if ($v['user_name']){
                        $list['user_id']               = $driver->self_id;
                        $list['user_name']               = $driver->name;
                    }else{
                        $list['user_id']               = null;
                        $list['user_name']               = null;
               
                    }
                    if($v['escort_name']){
                        $list['escort']                  = $cargo->self_id;
                        $list['escort_name']             = $cargo->name;
                        
                    }else{
                        $list['escort']                  = null;
                        $list['escort_name']             = null;
                        
                    }
                    $list['company_fine']           = $v['company_fine'];
                    $list['violation_connect']      = $v['violation_connect'];
                    $list['cash_back']              = $v['cash_back'];
                    $list['handled_by']             = $v['handled_by'];
                    $list['remark']                 = $v['remark'];

                    $list['group_code']          = $info->group_code;
                    $list['group_name']          = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                    
                    if (($fine_user == 'driver'&& ($v['company_fine'] || $v['company_fine']>0)) || ($fine_user == 'all' && ($v['company_fine'] || $v['company_fine']>0))){
                        $user_award['self_id']            = generate_id('award_');
                        $user_award['reward_id']          = $list['self_id'];
                        $user_award['user_id']            = $list['user_id'];
                        $user_award['user_name']          = $list['user_name'];
                        $user_award['car_id']             = $list['car_id'];
                        $user_award['car_number']         = $list['car_number'];
                        $user_award['money_award']        = $v['company_fine'];
                        // $user_award = date('Y-m', strtotime('+6 month', strtotime($v['event_time'])));
                        $user_award['cash_back']          = $v['cash_back'];
                        $user_award['group_code']         = $info->group_code;
                        $user_award['group_name']         = $info->group_name;
                        $user_award['create_user_id']     = $user_info->admin_id;
                        $user_award['create_user_name']   = $user_info->name;
                        $user_award['create_time']        = $user_award['update_time']=$now_time;

                        $user_award_list[]=$user_award;
                        
                    }
                    if (($fine_user == 'cargo'&& ($v['company_fine'] || $v['company_fine']>0)) || ($fine_user == 'all' && ($v['company_fine'] || $v['company_fine']>0))){
                        $award['self_id']            = generate_id('award_');
                        $award['reward_id']          = $list['self_id'];
                        $award['user_id']            = $list['escort'];
                        $award['user_name']          = $list['escort_name'];
                        $award['car_id']             = $list['car_id'];
                        $award['car_number']         = $list['car_number'];
                        $award['money_award']        = $v['company_fine'];
                        // $time = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $award['cash_back']          = $v['cash_back'];
                        $award['group_code']         = $info->group_code;
                        $award['group_name']         = $info->group_name;
                        $award['create_user_id']     = $user_info->admin_id;
                        $award['create_user_name']   = $user_info->name;
                        $award['create_time']        = $award['update_time']=$now_time;

                        $award_list[]=$award;
                        
                    }

                
                }
                $a++;

            }
            dd($award_list,$datalist);
            $operationing->new_info=$datalist;

            //dump($operationing);
            // dd($datalist);

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= UserReward::insert($datalist);
            if(count($user_award_list)>0){
                AwardRemind::insert($user_award_list);
            }
            if(count($award_list)>0){
                AwardRemind::insert($award_list);

            }
            
            
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

    //违章导入  tms/userReward/ruleImport
    public function ruleImport(Request $request){
        $table_name         ='user_reward';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='违章导入';
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

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '日期' =>['Y','Y','64','event_time'],
                '车牌号' =>['Y','Y','64','car_number'],
                '驾驶员' =>['N','Y','64','user_name'],
                '押运员' =>['N','Y','64','escort_name'],
                '违章详情' =>['N','Y','64','violation_connect'],
                '处理情况' =>['N','Y','64','handle_connect'],
                '扣分' =>['N','Y','64','score'],
                '罚款' =>['N','Y','64','payment'],
                '罚款对象'=>['Y','Y','64','fine_user'],
                '公司罚款金额' =>['Y','Y','64','company_fine'],
                '奖金返还日期' =>['Y','Y','64','cash_back'],
                '经办人' =>['N','Y','64','handled_by'],
                '备注' =>['N','Y','64','remark'],
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
            $user_award_list[]=[];
            $award_list[]=[];
            // dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                if ($v['event_time']){
                    if (is_numeric($v['event_time'])){
                        $v['event_time']              = gmdate('Y-m-d',($v['event_time'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['event_time'])) == $v['event_time']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行发货日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                $car = TmsCar::where('car_number',$v['car_number'])->where('group_code',$group_code)->select('self_id','car_number')->first();
                if (!$car){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                 if ($v['user_name']){
                    $driver = SystemUser::whereIn('type',['driver','dr_cargo'])->where('name',$v['user_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$driver){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }
                if($v['escort_name']){
                    $cargo = SystemUser::whereIn('type',['cargo','dr_cargo'])->where('name',$v['escort_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$cargo){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行副驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }

                if ($v['cash_back']){
                    if (is_numeric($v['cash_back'])){
                        $v['cash_back']              = gmdate('Y-m',($v['cash_back'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['cash_back'])) == $v['cash_back'] || date('Y-m',strtotime($v['cash_back'])) == $v['cash_back']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行奖金返还日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }

                if($v['fine_user'] == '驾驶员'){
                    $fine_user = 'driver';
                }elseif($v['fine_user'] == '押运员'){
                    $fine_user = 'carge';
                }elseif($v['fine_user'] == '全部'){
                    $fine_user = 'all';
                }else{
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行罚款对象填写错误".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                // dump($cando);
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('reward_');
                    $list['type']               = 'rule';
                    $list['event_time']         = $v['event_time'];
                    $list['car_id']             = $car->self_id;
                    $list['car_number']         = $car->car_number;
                    if ($v['user_name']){
                        $list['user_id']               = $driver->self_id;
                        $list['user_name']               = $driver->name;
                    }else{
                        $list['user_id']                 = null;
                        $list['user_name']               = null;
               
                    }
                    if($v['escort_name']){
                        $list['escort']                  = $cargo->self_id;
                        $list['escort_name']             = $cargo->name;
                        
                    }else{
                        $list['escort']                  = null;
                        $list['escort_name']             = null;
                        
                    }
                    $list['violation_connect']        = $v['violation_connect'];
                    $list['handle_connect']           = $v['handle_connect'];
                    $list['score']                    = $v['score'];
                    $list['payment']                  = $v['payment'];
                    $list['company_fine']             = $v['company_fine'];
                    $list['cash_back']                = $v['cash_back'];
                    $list['handled_by']               = $v['handled_by'];
                    $list['remark']                   = $v['remark'];
                    $list['group_code']               = $info->group_code;
                    $list['group_name']               = $info->group_name;
                    $list['create_user_id']           = $user_info->admin_id;
                    $list['create_user_name']         = $user_info->name;
                    $list['create_time']              = $list['update_time']=$now_time;
                    $list['file_id']                  = $file_id;
                    $datalist[]=$list;
                    
                    if (($fine_user == 'driver'&& ($v['company_fine'] || $v['company_fine']>0)) || ($fine_user == 'all' && ($v['company_fine'] || $v['company_fine']>0))){
                        $user_award['self_id']            = generate_id('award_');
                        $user_award['reward_id']          = $list['self_id'];
                        $user_award['user_id']            = $list['user_id'];
                        $user_award['user_name']          = $list['user_name'];
                        $user_award['car_id']             = $list['car_id'];
                        $user_award['car_number']         = $list['car_number'];
                        $user_award['money_award']        = $v['company_fine'];
                        // $user_award = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $user_award['cash_back']          = $cash_back;
                        $user_award['group_code']         = $info->group_code;
                        $user_award['group_name']         = $info->group_name;
                        $user_award['create_user_id']     = $user_info->admin_id;
                        $user_award['create_user_name']   = $user_info->name;
                        $user_award['create_time']        = $user_award['update_time']=$now_time;

                        $user_award_list[]=$user_award;
                        
                    }
                    if (($fine_user == 'driver'&& ($v['company_fine'] || $v['company_fine']>0)) || ($fine_user == 'all' && ($v['company_fine'] || $v['company_fine']>0))){
                        $award['self_id']            = generate_id('award_');
                        $award['reward_id']          = $list['self_id'];
                        $award['user_id']            = $list['escort'];
                        $award['user_name']          = $list['escort_name'];
                        $award['car_id']             = $list['car_id'];
                        $award['car_number']         = $list['car_number'];
                        $award['money_award']        = $v['company_fine'];
                        // $time = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $award['cash_back']          = $v['cash_back'];
                        $award['group_code']         = $info->group_code;
                        $award['group_name']         = $info->group_name;
                        $award['create_user_id']     = $user_info->admin_id;
                        $award['create_user_name']   = $user_info->name;
                        $award['create_time']        = $award['update_time']=$now_time;

                        $award_list[]=$award;
                        
                    }

                
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
            $id= UserReward::insert($datalist);
            if(count($user_award_list)>0){
                AwardRemind::insert($user_award_list);
            }
            if(count($award_list)>0){
                AwardRemind::insert($award_list);

            }
            
            
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

    //事故导入  tms/userReward/accidentImport
    public function accidentImport(Request $request){
        $table_name         ='user_reward';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='事故导入';
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

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '日期' =>['Y','Y','64','event_time'],
                '车牌号' =>['Y','Y','64','car_number'],
                '驾驶员' =>['N','Y','64','user_name'],
                '押运员' =>['N','Y','64','escort_name'],
                '事故详情' =>['N','Y','64','violation_connect'],
                '事故地点' =>['N','Y','64','fault_address'],
                '责任方' =>['N','Y','64','fault_party'],
                '承担责任' =>['N','Y','64','bear'],
                '扣分' =>['N','Y','64','score'],
                '罚款' =>['N','Y','64','payment'],
                '罚款对象'=>['Y','Y','64','fine_user'],
                '公司罚款金额' =>['Y','Y','64','company_fine'],
                '损失金额' =>['N','Y','64','fault_price'],
                '奖金返还日期' =>['Y','Y','64','cash_back'],
                '经办人' =>['N','Y','64','handled_by'],
                '备注' =>['N','Y','64','remark'],
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
            $user_award_list[]=[];
            $award_list[]=[];
            // dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                if ($v['event_time']){
                    if (is_numeric($v['event_time'])){
                        $v['event_time']              = gmdate('Y-m-d',($v['event_time'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['event_time'])) == $v['event_time']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行发货日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                $car = TmsCar::where('car_number',$v['car_number'])->where('group_code',$group_code)->select('self_id','car_number')->first();
                if (!$car){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                 if ($v['user_name']){
                    $driver = SystemUser::whereIn('type',['driver','dr_cargo'])->where('name',$v['user_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$driver){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }
                if($v['escort_name']){
                    $cargo = SystemUser::whereIn('type',['cargo','dr_cargo'])->where('name',$v['escort_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$cargo){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行副驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }

                if ($v['cash_back']){
                    if (is_numeric($v['cash_back'])){
                        $v['cash_back']              = gmdate('Y-m',($v['cash_back'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['cash_back'])) == $v['cash_back'] || date('Y-m',strtotime($v['cash_back'])) == $v['cash_back']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行奖金返还日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                if($v['fine_user'] == '驾驶员'){
                    $fine_user = 'driver';
                }elseif($v['fine_user'] == '押运员'){
                    $fine_user = 'carge';
                }elseif($v['fine_user'] == '全部'){
                    $fine_user = 'all';
                }else{
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行罚款对象填写错误".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                // dump($cando);
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('reward_');
                    $list['type']               = 'accident';
                    $list['event_time']         = $v['event_time'];
                    $list['car_id']             = $car->self_id;
                    $list['car_number']         = $car->car_number;
                    if ($v['user_name']){
                        $list['user_id']               = $driver->self_id;
                        $list['user_name']               = $driver->name;
                    }else{
                        $list['user_id']                 = null;
                        $list['user_name']               = null;
               
                    }
                    if($v['escort_name']){
                        $list['escort']                  = $cargo->self_id;
                        $list['escort_name']             = $cargo->name;
                        
                    }else{
                        $list['escort']                  = null;
                        $list['escort_name']             = null;
                        
                    }
                    $list['violation_connect']        = $v['violation_connect'];
                    $list['fault_address']            = $v['fault_address'];
                    $list['fault_party']              = $v['fault_party'];   
                    $list['bear']                     = $v['bear'];
                    $list['score']                    = $v['score'];
                    $list['payment']                  = $v['payment'];
                    $list['company_fine']             = $v['company_fine'];
                    $list['fault_price']              = $v['fault_price'];
                    $list['cash_back']                = $v['cash_back'];
                    $list['handled_by']               = $v['handled_by'];
                    $list['remark']                   = $v['remark'];
                    $list['group_code']               = $info->group_code;
                    $list['group_name']               = $info->group_name;
                    $list['create_user_id']           = $user_info->admin_id;
                    $list['create_user_name']         = $user_info->name;
                    $list['create_time']              = $list['update_time']=$now_time;
                    $list['file_id']                  = $file_id;
                    $datalist[]=$list;
                    
                    if (($fine_user == 'driver'&& ($v['company_fine'] || $v['company_fine']>0)) || ($fine_user == 'all' && ($v['company_fine'] || $v['company_fine']>0))){
                        $user_award['self_id']            = generate_id('award_');
                        $user_award['reward_id']          = $list['self_id'];
                        $user_award['user_id']            = $list['user_id'];
                        $user_award['user_name']          = $list['user_name'];
                        $user_award['car_id']             = $list['car_id'];
                        $user_award['car_number']         = $list['car_number'];
                        $user_award['money_award']        = $v['company_fine'];
                        // $user_award = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $user_award['cash_back']          = $v['cash_back'];
                        $user_award['group_code']         = $info->group_code;
                        $user_award['group_name']         = $info->group_name;
                        $user_award['create_user_id']     = $user_info->admin_id;
                        $user_award['create_user_name']   = $user_info->name;
                        $user_award['create_time']        = $user_award['update_time']=$now_time;

                        $user_award_list[]=$user_award;
                        
                    }
                    if (($fine_user == 'driver'&& ($v['company_fine'] || $v['company_fine']>0)) || ($fine_user == 'all' && ($v['company_fine'] || $v['company_fine']>0))){
                        $award['self_id']            = generate_id('award_');
                        $award['reward_id']          = $list['self_id'];
                        $award['user_id']            = $list['escort'];
                        $award['user_name']          = $list['escort_name'];
                        $award['car_id']             = $list['car_id'];
                        $award['car_number']         = $list['car_number'];
                        $award['money_award']        = $v['company_fine'];
                        // $time = date('Y-m', strtotime('+6 month', strtotime($event_time)));
                        $award['cash_back']          = $v['cash_back'];
                        $award['group_code']         = $info->group_code;
                        $award['group_name']         = $info->group_name;
                        $award['create_user_id']     = $user_info->admin_id;
                        $award['create_user_name']   = $user_info->name;
                        $award['create_time']        = $award['update_time']=$now_time;

                        $award_list[]=$award;
                        
                    }
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
            $id= UserReward::insert($datalist);
            if(count($user_award_list)>0){
                AwardRemind::insert($user_award_list);
            }
            if(count($award_list)>0){
                AwardRemind::insert($award_list);

            }
            
            
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


    //奖励导入  tms/userReward/rewardImport
    public function rewardImport(Request $request){
        $table_name         ='user_reward';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='事故导入';
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

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '日期' =>['Y','Y','64','event_time'],
                '车牌号' =>['Y','Y','64','car_number'],
                '驾驶员' =>['N','Y','64','user_name'],
                '押运员' =>['N','Y','64','escort_name'],
                '奖励详情' =>['N','Y','64','reward_view'],
                '奖金' =>['N','Y','64','safe_reward'],
                '经办人' =>['N','Y','64','handled_by'],
                '备注' =>['N','Y','64','remark'],
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
            $user_award_list[]=[];
            $award_list[]=[];
            // dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                if ($v['event_time']){
                    if (is_numeric($v['event_time'])){
                        $v['event_time']              = gmdate('Y-m-d',($v['event_time'] - 25569) * 3600 * 24);
                    }else{
                        if(date('Y-m-d',strtotime($v['event_time'])) == $v['event_time']){

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行发货日期格式错误".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }
                    }
                }
                $car = TmsCar::where('car_number',$v['car_number'])->where('group_code',$group_code)->select('self_id','car_number')->first();
                if (!$car){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                 if ($v['user_name']){
                    $driver = SystemUser::whereIn('type',['driver','dr_cargo'])->where('name',$v['user_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$driver){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }
                if($v['escort_name']){
                    $cargo = SystemUser::whereIn('type',['cargo','dr_cargo'])->where('name',$v['escort_name'])->where('group_code',$group_code)->select('self_id','name','type','tel','use_flag','delete_flag','social_flag')->first();
                    if (!$cargo){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行副驾驶员不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }

            
                // dump($cando);
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('reward_');
                    $list['type']               = 'reward';
                    $list['event_time']         = $v['event_time'];
                    $list['car_id']             = $car->self_id;
                    $list['car_number']         = $car->car_number;
                    if ($v['user_name']){
                        $list['user_id']               = $driver->self_id;
                        $list['user_name']               = $driver->name;
                    }else{
                        $list['user_id']                 = null;
                        $list['user_name']               = null;
               
                    }
                    if($v['escort_name']){
                        $list['escort']                  = $cargo->self_id;
                        $list['escort_name']             = $cargo->name;
                        
                    }else{
                        $list['escort']                  = null;
                        $list['escort_name']             = null;
                        
                    }
                 
                    $list['reward_view']              = $v['reward_view'];
                    $list['safe_reward']              = $v['safe_reward'];
                    $list['handled_by']               = $v['handled_by'];
                    $list['remark']                   = $v['remark'];
                    $list['group_code']               = $info->group_code;
                    $list['group_name']               = $info->group_name;
                    $list['create_user_id']           = $user_info->admin_id;
                    $list['create_user_name']         = $user_info->name;
                    $list['create_time']              = $list['update_time']=$now_time;
                    $list['file_id']                  = $file_id;
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
            $id= UserReward::insert($datalist);
        
            
            
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
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear'];
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

    /***    奖励导出     /tms/userReward/rewardExcel
     */
    public function rewardExcel(Request $request,File $file){
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
                ['type'=>'=','name'=>'type','value'=>'reward'],

            ];
            $where=get_list_where($search);

            $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear','company_fine','escort_name','user_id'];
            $select1 = ['self_id','name'];
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
                    "event_time"=>'日期',
                    "car_number"=>'车牌号',
                    "user_name"=>'驾驶员',
                    "escort_name"=>'押运员',
                    "reward_view"=>'奖励详情',
                    "safe_reward"=>'奖金',
                    "handled_by"=>'经办人',
                    "remark"=>'备注',
                ]];
 

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['event_time']                = $v['event_time'];
                    $list['car_number']                = $v['car_number'];
                    $list['user_name']                = $v['user_name'];
                    $list['escort_name']                = $v['escort_name'];
                    $list['reward_view']              = $v['reward_view'];
                    $list['safe_reward']              = $v['safe_reward'];
                    $list['handled_by']               = $v['handled_by'];
                    $list['remark']                   = $v['remark'];

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

    /***    违规导出     /tms/userReward/violationExcel
     */
    public function violationExcel(Request $request,File $file){
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
                ['type'=>'=','name'=>'type','value'=>'violation'],
            ];
            $where=get_list_where($search);

            $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear','company_fine','escort_name','user_id'];
            $select1 = ['self_id','name'];
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
                    "event_time"=>'日期',
                    "car_number"=>'车牌号',
                    "user_name"=>'驾驶员',
                    "escort_name"=>'押运员',
                    "company_fine"=>'罚款',
                    "violation_connect"=>'违规详情',
                    "cash_back"=>'奖金返还',
                    "handled_by"=>'经办人',
                    "remark"=>'备注',
                ]];
 

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['event_time']                = $v['event_time'];
                    $list['car_number']                = $v['car_number'];
                    $list['user_name']                = $v['user_name'];
                    $list['escort_name']                = $v['escort_name'];
                    $list['company_fine']           = $v['company_fine'];
                    $list['violation_connect']      = $v['violation_connect'];
                    $list['cash_back']              = $v['cash_back'];
                    $list['handled_by']             = $v['handled_by'];
                    $list['remark']                 = $v['remark'];

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

    /***    违章导出     /tms/userReward/ruleExcel
     */
    public function ruleExcel(Request $request,File $file){
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
                ['type'=>'=','name'=>'type','value'=>'rule'],
            ];
            $where=get_list_where($search);

            $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear','company_fine','escort_name','user_id'];
            $select1 = ['self_id','name'];
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
                    "event_time"=>'日期',
                    "car_number"=>'车牌号',
                    "user_name"=>'驾驶员',
                    "escort_name"=>'押运员',
                    "violation_connect"=>'违章详情',
                    "handle_connect"=>'处理情况',
                    "score"=>'扣分',
                    "payment"=>'罚款',
                    "company_fine"=>'公司罚款金额',
                    "cash_back"=>'奖金返还',
                    "handled_by"=>'经办人',
                    "remark"=>'备注',
                ]];
 

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['event_time']                = $v['event_time'];
                    $list['car_number']                = $v['car_number'];
                    $list['user_name']                = $v['user_name'];
                    $list['escort_name']                = $v['escort_name'];
                    
                    $list['violation_connect']        = $v['violation_connect'];
                    $list['handle_connect']           = $v['handle_connect'];
                    $list['score']                    = $v['score'];
                    $list['payment']                  = $v['payment'];
                    $list['company_fine']             = $v['company_fine'];
                    $list['cash_back']                = $v['cash_back'];
                    $list['handled_by']               = $v['handled_by'];
                    $list['remark']                   = $v['remark'];

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

    /***    事故导出     /tms/userReward/accidentExcel
     */
    public function accidentExcel(Request $request,File $file){
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
                ['type'=>'=','name'=>'type','value'=>'accident'],
            ];
            $where=get_list_where($search);

            $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear','company_fine','escort_name','user_id'];
            $select1 = ['self_id','name'];
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
                    "event_time"=>'日期',
                    "car_number"=>'车牌号',
                    "user_name"=>'驾驶员',
                    "escort_name"=>'押运员',
                    "violation_connect"=>'事故详情',
                    "fault_address"=>'事故地点',
                    "fault_party"=>'责任方',
                    "bear"=>'承担责任',
                    "score"=>'扣分',
                    "payment"=>'罚款',
                    "company_fine"=>'公司罚款金额',
                    "fault_price"=>'损失金额',
                    "cash_back"=>'奖金返还',
                    "handled_by"=>'经办人',
                    "remark"=>'备注',
                ]];
 

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['event_time']                = $v['event_time'];
                    $list['car_number']                = $v['car_number'];
                    $list['user_name']                = $v['user_name'];
                    $list['escort_name']                = $v['escort_name'];
                    $list['violation_connect']        = $v['violation_connect'];
                    $list['fault_address']            = $v['fault_address'];
                    $list['fault_party']              = $v['fault_party'];   
                    $list['bear']                     = $v['bear'];
                    $list['score']                    = $v['score'];
                    $list['payment']                  = $v['payment'];
                    $list['company_fine']             = $v['company_fine'];
                    $list['fault_price']              = $v['fault_price'];
                    $list['cash_back']                = $v['cash_back'];
                    $list['handled_by']               = $v['handled_by'];
                    $list['remark']                   = $v['remark'];
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

    /**
     * tms/userReward/remindList
     * */
    public function remindList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /**
     * 奖金返还记录列表  tms/userReward/remindPage
     * */
    public function remindPage(Request $request){
            /** 接收中间件参数**/
            $group_info     = $request->get('group_info');//接收中间件产生的参数
            $button_info    = $request->get('anniu');//接收中间件产生的参数
            $now_time = date('Y-m',time());

            /**接收数据*/
            $num            =$request->input('num')??10;
            $page           =$request->input('page')??1;
            $use_flag       =$request->input('use_flag');
            $group_code     =$request->input('group_code');
            $user_id        =$request->input('user_id');
            $user_name      =$request->input('user_name');
            $start_time     =$request->input('start_time');
            $end_time       =$request->input('end_time');
            $car_number       =$request->input('car_number');
            
            $listrows       =$num;
            $firstrow       =($page-1)*$listrows;

            $search=[
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
                ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'user_id','value'=>$user_id],
                ['type'=>'=','name'=>'car_number','value'=>$car_number],
                ['type'=>'like','name'=>'user_name','value'=>$user_name],
                ['type'=>'>=','name'=>'cash_back','value'=>$start_time],
                ['type'=>'<','name'=>'cash_back','value'=>$end_time],
            ];
            $where1 = [
               ['cash_back','=',$now_time],
               ['award_flag','=','N']
            ];

            $where=get_list_where($search);
            $select=['self_id','user_id','user_name','money_award','award_flag','group_code','group_name','reward_id','delete_flag','use_flag','cash_flag','cash_back','create_time','update_time','car_id','car_number'];
            $select1=['self_id','name'];
            switch ($group_info['group_id']){
                case 'all':
                    $data['total']=AwardRemind::where($where)->count(); //总的数据量
                    $data['items']=AwardRemind::with(['systemUser' => function($query) use($select1){
                        $query->select($select1);
                    }])
                        ->where($where)
                        ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    $data['info']=AwardRemind::with(['systemUser' => function($query) use($select1){
                        $query->select($select1);
                    }])
                        ->where($where1)
                        ->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    $data['price']=AwardRemind::where($where)->sum('money_award');
                    $data['group_show']='Y';
                    break;

                case 'one':
                    $where[]=['group_code','=',$group_info['group_code']];
                    $data['total']=AwardRemind::where($where)->count(); //总的数据量
                    $data['items']=AwardRemind::with(['systemUser' => function($query) use($select1){
                        $query->select($select1);
                    }])
                        ->where($where)
                        ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    $data['info']=AwardRemind::with(['systemUser' => function($query) use($select1){
                        $query->select($select1);
                    }])
                        ->where($where1)
                        ->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    $data['price']=AwardRemind::where($where)->sum('money_award');
                    $data['group_show']='N';
                    break;

                case 'more':
                    $data['total']=AwardRemind::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                    $data['items']=AwardRemind::with(['systemUser' => function($query) use($select1){
                        $query->select($select1);
                    }])
                        ->where($where)->whereIn('group_code',$group_info['group_code'])
                        ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    $data['info']=AwardRemind::with(['systemUser' => function($query) use($select1){
                        $query->select($select1);
                    }])
                        ->whereIn('group_code',$group_info['group_code'])
                        ->where($where1)
                        ->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    $data['price']=AwardRemind::where($where)->whereIn('group_code',$group_info['group_code'])->sum('money_award');
                    $data['group_show']='Y';
                    break;
            }

            $button_info1=[];

//            foreach ($button_info as $k => $v){
//
//            }
            foreach ($data['items'] as $k=>$v) {
                $v->button_info=$button_info;
                if ($v->award_flag == 'Y'){
                    $v->button_info=[];
                }
                $v->reward_id_show = substr($v->reward_id,7);

            }

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }

        /**
         * 修改是否返还状态  tms/userReward/updateState
         * */
    public function updateState(Request $request){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $table_name='award_remind';
        $medol_name='AwardRemind';
        $self_id=$request->input('self_id');
        $flag='delFlag';
//        $self_id='car_202012242220439016797353';
        $old_info = AwardRemind::where('self_id',$self_id)->select('user_id','user_name','money_award','award_flag','cash_back','use_flag','self_id','delete_flag','group_code')->first();
        $data['award_flag']='Y';
        $data['update_time']=$now_time;
        $id=AwardRemind::where('self_id',$self_id)->update($data);
        /**保存费用**/
        // $money['pay_type']           = 'reward';
        // $money['money']              = $old_info->money_award;
        // $money['pay_state']          = 'Y';
        // $money['user_id']            = $old_info->user_id;
        // $money['user_name']          = $old_info->user_name;
        // $money['process_state']      = 'Y';
        // $money['type_state']         = 'out';
        // $money['self_id']            = generate_id('money_');
        // $money['group_code']         = $user_info->group_code;
        // $money['group_name']         = $user_info->group_name;
        // $money['create_user_id']     = $user_info->admin_id;
        // $money['create_user_name']   = $user_info->name;
        // $money['create_time']        = $money['update_time']=$now_time;

        // TmsMoney::insert($money);
        if ($id){
            $msg['code']=200;
            $msg['msg']="修改成功！";
        }else{
            $msg['code']=300;
            $msg['msg']="修改失败！";

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

    //删除奖金返还
    public function delAwardMind(Request $request){

    }

    //获取奖金返还来源
    public function getUserReward(Request $request){
        $reward_id=$request->input('reward_id');
        
//        $input['group_code'] =  $group_code = '1234';
       
        $select = ['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag','use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party','cash_back','cash_flag','type','user_name','bear','company_fine','escort_name','user_id'];
        $data['info']=UserReward::where('self_id',$reward_id)->select($select)->first();
        $data['info']->reward_id_show = substr($data['info']->self_id,7);

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /**
     * 获取
     * */

    public function userRewardCount(Request $request){
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
        $user_name        =$request->input('user_name');
        $type           =$request->input('type');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'user_id','value'=>$user_id],
            ['type'=>'!=','name'=>'type','value'=>$type],
            ['type'=>'>=','name'=>'event_time','value'=>$start_time],
            ['type'=>'<=','name'=>'event_time','value'=>$end_time],
        ];
        $search1=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'escort','value'=>$user_id],
            ['type'=>'!=','name'=>'type','value'=>$type],
            ['type'=>'>=','name'=>'event_time','value'=>$start_time],
            ['type'=>'<=','name'=>'event_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $where1=get_list_where($search1);

        $select=['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
            'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
            ,'cash_back','cash_flag','type','user_name','bear','company_fine','escort_name'];
        $select1=['self_id','name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['user' => function($query) use($select1){
                        $query->select($select1);
                    }])
                    ->where($where)->orWhere($where1)
                    ->offset($firstrow)->limit($listrows)->orderBy('event_time', 'desc')
                    ->select($select)->get();
                $data['price']=UserReward::where($where)->orWhere($where1)->sum('payment');
                $data['total_price']=UserReward::where($where)->orWhere($where1)->sum('company_fine');
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=UserReward::where($where)->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['user' => function($query) use($select1){
                        $query->select($select1);
                    }])
                    ->where($where)->orWhere($where1)
                    ->offset($firstrow)->limit($listrows)->orderBy('event_time', 'desc')
                    ->select($select)->get();
                $data['price']=UserReward::where($where)->orWhere($where1)->sum('payment');
                $data['total_price']=UserReward::where($where)->orWhere($where1)->sum('company_fine');
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=UserReward::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=UserReward::with(['systemUser' => function($query) use($select1){
                    $query->select($select1);
                }])
                    ->with(['user' => function($query) use($select1){
                        $query->select($select1);
                    }])->where($where)->orWhere($where1)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('event_time', 'desc')
                    ->select($select)->get();

                $data['price']=UserReward::where($where)->orWhere($where1)->whereIn('group_code',$group_info['group_code'])->sum('payment');
                $data['total_price']=UserReward::where($where)->orWhere($where1)->whereIn('group_code',$group_info['group_code'])->sum('company_fine');
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {
            if ($v->user){
                $v->escort = $v->user->name;
            }
            $v->reward_id_show = substr($v->self_id,7);
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    地址导出     /tms/userReward/remindExcel
     */
    public function remindExcel(Request $request,File $file){
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

            $select=['self_id','user_id','user_name','money_award','award_flag','group_code','group_name','reward_id','delete_flag','use_flag','cash_flag','cash_back','create_time','update_time','car_id','car_number'];
            $select1=['self_id','name'];
            $info=AwardRemind::with(['systemUser' => function($query) use($select1){
                $query->select($select1);
            }])->where($where)
                ->orderBy('create_time', 'desc')
                ->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "car_number"=>'车牌号',
                    "user_name"=>'姓名',
                    "money_award"=>'奖金',
                    "cash_back"=>'奖金返还',
                ]];


                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['car_number']=$v->car_number;
                    $list['user_name']=$v->user_name;
                    $list['money_award']=$v->money_award;
                    $list['late_fee']=$v->late_fee;
                    $list['cash_back']=$v->cash_back;

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

