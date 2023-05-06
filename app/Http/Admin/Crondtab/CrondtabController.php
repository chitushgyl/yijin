<?php
namespace App\Http\Admin\Crondtab;

use App\Http\Admin\Tms\MessageController;
use App\Models\Tms\TmsCar;
use App\Models\Tms\TmsMessage;
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
                $time = date('Y-m-d', strtotime('+6 month', strtotime($month_start)));
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
        $group_code=$request->input('group_code');
        $now_time = date('Y-m-d H:i:s',time());
        $select =['self_id','name','salary','live_cost','social_money','safe_reward','group_code','group_name','use_flag','delete_flag','type'];
        $where = [
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['group_code','=',$group_code],
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
            $v->reward_price = UserExamine::where('start_time','>=',$start_month)->where('end_time','<=',$end_month)->where('user_id',$v->self_id)->sum('reward_price');
            $v->salary_fine = UserExamine::where('start_time','>=',$start_month)->where('end_time','<=',$end_month)->where('user_id',$v->self_id)->sum('salary_fine');
            $v->date = UserExamine::where('start_time','>=',$start_month)->where('end_time','<=',$end_month)->where('user_id',$v->self_id)->sum('date_num');
            $v->water_money = 0.00;
            $v->income_tax = 0.00;

            $data['user_id']      = $v->self_id;
            $data['user_name']    = $v->name;
            $data['type']         = $v->type;
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

            $data['total_money']  = ($data['salary'] + $data['safe_reward'] + $data['money'] + $data['money_award']) -($data['live_cost'] - $data['social_money'] - $data['company_fine'] - $data['salary_fine'] -$data['reward_price'] - $data['income_tax'] - $data['water_money']);

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
        $msg['code'] = '200';
        $msg['msg']  = '生成成功！';
        return $msg;
    }

    /**
     * 查询即将到期的证件
     * */
    public function getExpireCert(Request $request){
        $now_time = date('Y-m-d H:i:s',time());
        $select =['self_id','car_number','medallion_change','license_date','tank_validity','inspect_annually','sgs_date','compulsory_end',
            'commercial_end','carrier_end','group_code','group_name'];
        $where = [
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
        ];
        $car_list=TmsCar::where($where)->orderBy('self_id','desc')->select($select)->get();
        $user_list=SystemUser::where($where)->orderBy('self_id','desc')->get();
        foreach ($car_list as $k => $v){
            if($v->medallion_change){
                if($now_time >= date('Y-m-d', strtotime(date($v->medallion_change) . ' -1 month'))){
                    $medallion['self_id'] = generate_id('msg_');
                    $medallion['connect'] = '运输证即将到期';
                    $medallion['car_number'] = $v->car_number;
                    $medallion['exprie_time'] =$v->medallion_change;
                    $medallion['group_code'] = $v->group_code;
                    $medallion['group_name'] = $v->group_name;
                    $medallion['create_time'] = $now_time;
                    $medallion['update_time'] = $now_time;

                    TmsMessage::insert($medallion);
                }
            }
            if($v->tank_validity){
                if($now_time >= date('Y-m-d', strtotime(date($v->tank_validity) . ' -1 month'))){
                    $tank['self_id'] = generate_id('msg_');
                    $tank['connect'] = '罐检即将到期';
                    $tank['car_number'] = $v->car_number;
                    $tank['exprie_time'] =$v->tank_validity;
                    $tank['group_code'] = $v->group_code;
                    $tank['group_name'] = $v->group_name;
                    $tank['create_time'] = $now_time;
                    $tank['update_time'] = $now_time;

                    TmsMessage::insert($tank);
                }
            }
            if($v->license_date){
                if($now_time >= date('Y-m-d', strtotime(date($v->license_date) . ' -1 month'))){
                    $license['self_id'] = generate_id('msg_');
                    $license['connect'] = '行驶证即将到期';
                    $license['car_number'] = $v->car_number;
                    $license['exprie_time'] =$v->license_date;
                    $license['group_code'] = $v->group_code;
                    $license['group_name'] = $v->group_name;
                    $license['create_time'] = $now_time;
                    $license['update_time'] = $now_time;

                    TmsMessage::insert($license);
                }
            }
            if($v->sgs_date){
                if($now_time >= date('Y-m-d', strtotime(date($v->sgs_date) . ' -1 month'))){
                    $sgs['self_id'] = generate_id('msg_');
                    $sgs['connect'] = 'SGS证即将到期';
                    $sgs['car_number'] = $v->car_number;
                    $sgs['exprie_time'] =$v->sgs_date;
                    $sgs['group_code'] = $v->group_code;
                    $sgs['group_name'] = $v->group_name;
                    $sgs['create_time'] = $now_time;
                    $sgs['update_time'] = $now_time;

                    TmsMessage::insert($sgs);
                }
            }
            if($v->inspect_annually){
                if($now_time >= date('Y-m-d', strtotime(date($v->inspect_annually) . ' -3 month'))){
                    $inspect_annually['self_id'] = generate_id('msg_');
                    $inspect_annually['connect'] = '年检即将到期';
                    $inspect_annually['car_number'] = $v->car_number;
                    $inspect_annually['exprie_time'] =$v->inspect_annually;
                    $inspect_annually['group_code'] = $v->group_code;
                    $inspect_annually['group_name'] = $v->group_name;
                    $inspect_annually['create_time'] = $now_time;
                    $inspect_annually['update_time'] = $now_time;

                    TmsMessage::insert($inspect_annually);
                }
            }
            if($v->compulsory_end){
                if($now_time >= date('Y-m-d', strtotime(date($v->compulsory_end) . ' -1 month'))){
                    $compulsory['self_id'] = generate_id('msg_');
                    $compulsory['connect'] = '交强险即将到期';
                    $compulsory['car_number'] = $v->car_number;
                    $compulsory['exprie_time'] =$v->compulsory_end;
                    $compulsory['group_code'] = $v->group_code;
                    $compulsory['group_name'] = $v->group_name;
                    $compulsory['create_time'] = $now_time;
                    $compulsory['update_time'] = $now_time;

                    TmsMessage::insert($compulsory);
                }
            }
            if($v->carrier_end){
                if($now_time >= date('Y-m-d', strtotime(date($v->carrier_end) . ' -1 month'))){
                    $carrier_end['self_id'] = generate_id('msg_');
                    $carrier_end['connect'] = '承运险即将到期';
                    $carrier_end['car_number'] = $v->car_number;
                    $carrier_end['exprie_time'] =$v->carrier_end;
                    $carrier_end['group_code'] = $v->group_code;
                    $carrier_end['group_name'] = $v->group_name;
                    $carrier_end['create_time'] = $now_time;
                    $carrier_end['update_time'] = $now_time;

                    TmsMessage::insert($carrier_end);
                }
            }
            if($v->commercial_end){
                if($now_time >= date('Y-m-d', strtotime(date($v->commercial_end) . ' -1 month'))){
                    $commercial_end['self_id'] = generate_id('msg_');
                    $commercial_end['connect'] = '商业险即将到期';
                    $commercial_end['car_number'] = $v->car_number;
                    $commercial_end['exprie_time'] =$v->commercial_end;
                    $commercial_end['group_code'] = $v->group_code;
                    $commercial_end['group_name'] = $v->group_name;
                    $commercial_end['create_time'] = $now_time;
                    $commercial_end['update_time'] = $now_time;

                    TmsMessage::insert($commercial_end);
                }
            }

        }

//        foreach ($user_list as $key => $value){
//
//        }
    }






































}
