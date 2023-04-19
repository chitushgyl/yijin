<?php
namespace App\Http\Admin\Crondtab;

use App\Models\Tms\TmsOrder;
use App\Models\Tms\TmsWages;
use App\Models\Tms\DriverCommission;
use App\Models\User\UserReward;
use App\Models\User\UserExamine;
use App\Http\Controllers\Controller;
use App\Models\Group\SystemUser;
use App\Models\Tms\AwardRemind;
use App\Models\Tms\TmsDiplasic;
use App\Models\Tms\TmsPayment;
use App\Models\User\UserCapital;
use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;


class CrondtabController extends Controller {


    /**
     *查询当月有无违规 违章 事故 /admin/crondtab/userReword
     */
    public function userReword(Request $request){
        $now_time  = date('Y-m',time());
        $month_start = date('Y-m-01 00:00:00',strtotime(date('Y-m-d')));
        $month_end = date('Y-m-d 23:59:59',strtotime("$month_start+1 month-1 day"));
        $where = [
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
        ];
        $user_list = SystemUser::where($where)->select('self_id','name')->get();
        foreach ($user_list as $k => $v) {
            $where1 = [
                ['type','!=','reward'],
                ['delete_flag','=','Y'],
                ['event_time','>=',$month_start],
                ['event_time','<=',$month_end],
                ['user_id','=',$v->self_id],
            ];
            $where2 = [
                ['type','!=','reward'],
                ['delete_flag','=','Y'],
                ['event_time','>=',$month_start],
                ['event_time','<=',$month_end],
                ['escort','=',$v->self_id],
            ];
            $select = ['self_id','car_id','car_number','violation_address','violation_connect','department','handle_connect','score','payment','late_fee','handle_opinion','safe_reward','safe_flag',
                'use_flag','delete_flag','create_time','update_time','group_code','group_name','escort','reward_view','handled_by','remark','event_time','fault_address','fault_price','fault_party'
                ,'cash_back','cash_flag','type','user_name'];
            $order_list = UserReward::where($where1)->orWhere($where2)->select($select)->get();

            if(count($order_list)>0){
                $time = date('Y-m', strtotime('+6 month', strtotime($month_start)));
                $update['cash_back']          = $time;
                $update['update_time']        = date('Y-m-d H:i:s',time());
                AwardRemind::where('user_id',$v->user_id)->where('cash_flag','N')->update($update);
            }

        }
    }

    /**
     * 自动更新二次维护时间
     * */
    public function updateDiplasic(Request $request){
        $now_time = date('Y-m-d H:i:s',time());
        $now_year = date('Y',time());
        $where = [
            ['delete_flag','=','Y'],
        ];
        $order_list = TmsDiplasic::where($where)->get();
//        dd($order_list->toArray());
        foreach ($order_list as $k => $v){
            if ($v->next_service_plan){
                $year = date('Y',strtotime($v->next_service_plan));
                if ($now_year == $year){
                    $update['service_now'] = $v->next_service_plan;
                    //计算当前时间与投入时间的时间间隔 大于5年 维护周期12个月 小于5年 维护周期6个月
                    $a = (strtotime(date('Y-m-d',time()))-strtotime($v->input_date))/(24*3600*365);
                    if ($a - 5 >0){
                        $num = 12;
                    }else{
                        $num = 6;
                        $update['service_plan'] = date('Y-m-d', strtotime('+'.$num.' month', strtotime($v->next_service_plan)));
                    }
                    $update['service'] = $num.'个月';
                    $update['next_service_plan'] = date('Y-m-d', strtotime('+'.$num.' month', strtotime($v->next_service_plan)));
                    $id = TmsDiplasic::where('self_id',$v->self_id)->update($update);
                }
            }

        }
        if ($id){
            $msg['code']=200;
            $msg['msg']='更新成功！';
            return $msg;
        }
    }

    /**
     * 更新员工每月奖金金额
     * */
    public function updateReward(Request $request){

    }

    //更新员工入职时间
    public function updateUserEntry(Request $request){
        $now_time = date('Y-m-d H:i:s',time());
        $where = [
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
        ];
        $select = ['self_id','name','entry_time','working_age'];
        $user_list = SystemUser::where($where)->select($select)->get();
        
        foreach($user_list as $k => $v){
            //计算员工入职距当前多少天
            if($v->entry_time){
                $now_year = date('Y',time());
            $work_time = strtotime($v->entry_time);
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
            $update['working_age']             = $work_age;
            $update['update_time']             = $now_time;
            
            SystemUser::where('self_id',$v->self_id)->update($update);
            }
            
        }
    }

    //计算员工上月工资
    public function countSalary(Request $request){
        $now_time = date('Y-m-d H:i:s',time());
        $select =['self_id','name','salary','live_cost','social_money','safe_reward','group_code','group_name','use_flag','delete_flag'];
        $where = [
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
        ];
        $user_list=SystemUser::where($where)->orderBy('self_id','desc')->select($select)->get();

        //获取上月日期
        $start_month = date('Y-m-d', strtotime(date('Y-m-01') . ' -1 month'));
        $end_month = date('Y-m-d', strtotime(date('Y-m-01') . ' -1 day'));
        $salary_time = date('Y-m', strtotime(date('Y-m-01') . ' -1 month'));

        // dd($start_month,$end_month,$salary_time);
        //获取员工工资
        foreach($user_list as $k => $v){
            $v->company_fine = UserReward::where('event_time','>=',$start_month)->where('event_time','<=',$end_month)->where('user_id',$v->self_id)->sum('company_fine');
            $v->money_award = AwardRemind::where('cash_back','>=',$start_month)->where('cash_back','<=',$end_month)->where('user_id',$v->self_id)->sum('money_award');
            $v->money = DriverCommission::where('leave_time','>=',$start_month)->where('leave_time','<=',$end_month)->where('driver_id',$v->self_id)->sum('money');
            $v->reward_price = UserExamine::where('create_time','>=',$start_month)->where('create_time','<=',$end_month)->where('user_id',$v->self_id)->sum('reward_price');
            $v->salary_fine = UserExamine::where('create_time','>=',$start_month)->where('create_time','<=',$end_month)->where('user_id',$v->self_id)->sum('salary_fine');
            $v->date = UserExamine::where('start_time','>=',$start_month)->where('end_time','<=',$end_month)->where('user_id',$v->self_id)->sum('date_num');
            $v->water_money = 0.00;
            $v->income_tax = 0.00;

            $data['user_id']      = $v->self_id;
            $data['user_name']    = $v->name;
            $data['salary']       = $v->salary;//基本工资
            $data['live_cost']    = $v->live_cost;//住宿费
            $data['social_money'] = $v->social_money;//社保费用
            $data['safe_reward']  = $v->safe_reward;//奖金
            $data['company_fine'] = $v->company_fine;//公司罚款
            $data['money']        = $v->money;//提成
            $data['salary_fine']  = $v->salary_fine;//请假基本工资扣款
            $data['reward_price'] = $v->reward_price;//请假奖金扣款
            $data['income_tax']   = 0;//个税
            $data['water_money']  = 0;//水电费
            $data['money_award']  = $v->money_award;//奖励
            $data['date_num']     = $v->date_num;//请假天数
            
            $data['update_time']  = $now_time;

            $old_info = TmsWages::where('user_id',$v->self_id)->where('salary_time',$salary_time)->first();
            if ($old_info) {
                TmsWages::where('user_id',$v->self_id)->where('salary_time',$salary_time)->update($data);
            }else{
                $data['self_id']           = generate_id('wages_');
                $data['group_code']        = $v->group_code;
                $data['group_name']        = $v->group_name;
                $data['salary_time']       = $salary_time;
                $data['create_time']       = $now_time;
                $data['create_user_id']    = null;
                $data['create_user_name']  = null;
                TmsWages::insert($data);
            } 
            
        }
    }






































}
