<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/9/1
 * Time: 15:21
 */
namespace App\Http\Api\School;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\School\SchoolPersonRelation;
use App\Models\WxMessage;
use App\Models\School\SchoolBasics;
use App\Models\School\SchoolInfo;
use App\Models\Group\SystemGroup;
use App\Models\WxMessageSend;
use App\Models\User\UserReg;
//use App\Http\Controllers\PushController as Push;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
class TempDataController extends Controller{

    /***    系统推送的            /school/sendCartData             测试地址
     */

    public function sendCartData($push,$info,$type,$student=null){
     //public function sendCartData(Push $push){
         /**系统自动推送有几个板块？
          * 1，发车过程中的到站，有  start发车提醒        arrive到站提醒   end结束运行提醒      go_time预约到站提醒     go_time2预约到站提醒2
          * 2，请假提醒，holidy    请假提醒，取消请假提醒      这里还要传递一个类型，是请假还是取消请假
          * 3,还有一个异常的通知     abnormal
         **/
         $wx_config			=config('page.platform');        //目前是只有这一个公号在做推送   ，如果有几个公号做推送的话，请将这个语句使用数据库查询语句来实现
         $now_time       	=date('Y-m-d H:i:s',time());

         /*** 虚拟数据
         $pa='car_path_202008241539526469382776UP2020-09-02';
         $redis = Redis::connection('carriage');
         $info=json_decode($redis->get($pa));
         $type='abnormal';

         //$idd='holiday_202009031005303123225328';



//         $info['data']=DB::table('school_holiday')->where('self_id','=',$idd)->first();
//         $info['data']=json_decode(json_encode($info['data']),true);
//         $info['info']='2020-09-04放学，2020-09-05上学';
**/
         //dump($type);
         if(empty($info)){
             $msg['code'] = 302;
             $msg['msg'] = "没有数据需要处理";
             return $msg;
         }



         switch ($type){
             case ($type=='start' || $type=='end'):                          //这里是发车及结束提醒
                 /**第一步，效验这个模板消息是不是可以触发***/
                 $template_id ='9FMbXZpvmbEsl6_a4FLJkvwkILMZoEDCyhDdutRHts0';                //微信的公号中提供的
                 $group_code=$info->group_code;
                 $status=$this->UpDown($info->status);
                 switch ($type){
                     case 'start':
                         $send_message['first']       =$info->path_name.'正在'.$status.'学运行中';
                         $send_message['keyword1']    ='';
                         $send_message['keyword2']    =$status.'学';
                         //$send_message['keyword3']    ='家长，您孩子所乘校车已经安全运行在'.$status.'学途中，请您耐心等待';
						 $send_message['keyword3']    ='家长，您孩子所乘校车已经启动。请您注意查看车辆实时状态，准确掌握接送孩子时间，谢谢';
                         $send_message['keyword4']    =$now_time;
                         //$send_message['keyword5']    ='12121';
                         $send_message['remark']      =$info->group_name.'校车车队竭诚为您服务';

                         break;
                     case 'end':
                         $send_message['first']       =$info->path_name.'正在'.$status.'学运行中';
                         $send_message['keyword1']    ='';
                         $send_message['keyword2']    =$status.'学';
                         $send_message['keyword3']    ='家长，您孩子所乘校车已安全抵达';
                         $send_message['keyword4']    =$now_time;
                         //$send_message['keyword5']    ='12121';
                         $send_message['remark']      =$info->group_name.'校车车队竭诚为您服务';
                         break;

                 }
                 //dd(3232323);

                 //上学放学状态转换中午
                 $wx_message_info=$this->wx_message($template_id,$send_message);
                 $data=$wx_message_info['data'];

                 /**第二步，查询中他要做差异化的地方***/
                 $basics=$this->check_group($group_code,$info);
                 $url=$basics['url'];
                 $miniprogram=$basics['miniprogram'];

                 /*** 现在做一个推送人员的集合   有多少个数组都可以放在集合中，不管数量的事情**/
                 $send[]=config('page.wx_push');            //平台方的
                 $basics=$basics['basics'];

                 if($basics){
                     if($basics->patriarch){
                        // $send[]=json_decode($basics->patriarch,true);           //这个是推送给这个学校固定的老师的
                     }

                     /*** 这里是抓取推送的照管和司机的地方**/
                     if($basics->zhao_flag == 'Y'){
                         $care_where=[$info->default_driver_id,$info->default_care_id];
                         //查询中这个相关的司机和照管
                        // $send[]=$this->get_care($care_where);
                     }

                     /*** 这里是抓取推送的照管和司机的地方结束**/
                     /**这里是推送给家长的地方**/
                     if(count($info->students)>0){
                         //说这个站上有小孩，那么去抓取这个小孩的家长信息 
                         $where_check=$info->students;
                         $send[]=$this->get_patriarch($where_check);
                     }
                     /**这里是推送给家长的地方结束**/
                 }

                 $reg_info=array_dispose($send,'jiaoji');

                 foreach ($reg_info as $k => $v){
                     if(array_key_exists('person_name', $v)){
                         if($v['person_name']){
                             $reg_info[$k]['keyword1']['text']=$v['person_name'];
                             $reg_info[$k]['keyword3']['text']=$v['person_name'];
                             $reg_info[$k]['keyword3']['color']='#FF7F24';
                         }
                     }
                 }


                 break;

             case ($type=='go_time' || $type=='go_time2'):                                  //这里是预约到站提醒
                 /**第一步，效验这个模板消息是不是可以触发            $student             ***/
                 $template_id ='9FMbXZpvmbEsl6_a4FLJkvwkILMZoEDCyhDdutRHts0';                //微信的公号中提供的
                 $group_code=$info->group_code;
                 //上学放学状态转换中午
                 $status=$this->UpDown($info->status);
                 $timesss=ceil($info->$type/60);
                 $send_message['first']       =$info->path_name.'正在'.$status.'学运行中';
                 $send_message['keyword1']    ='';
                 $send_message['keyword2']    =$status.'学';
                 $send_message['keyword3']    ='家长，您孩子所乘校车预计'.$timesss.'分钟后到达';
                 $send_message['keyword4']    =$now_time;
                 //$send_message['keyword5']    ='12121';
                 $send_message['remark']      =$info->group_name.'校车车队竭诚为您服务';
                 $wx_message_info=$this->wx_message($template_id,$send_message);
                 $data=$wx_message_info['data'];

                 /**第二步，查询中他要做差异化的地方***/
                 $basics=$this->check_group($group_code,$info);
                 $url=$basics['url'];
                 $miniprogram=$basics['miniprogram'];

                 /*** 现在做一个推送人员的集合   有多少个数组都可以放在集合中，不管数量的事情**/
                 $send[]=config('page.wx_push');            //平台方的
                 $basics=$basics['basics'];

                 if($basics){
                     if($basics->patriarch){
                        // $send[]=json_decode($basics->patriarch,true);           //这个是推送给这个学校固定的老师的1
                     }

                     /*** 这里是抓取推送的照管和司机的地方**/
                     if($basics->zhao_flag == 'Y'){
                         $care_where=[$info->default_driver_id,$info->default_care_id];
                         //查询中这个相关的司机和照管
                        // $send[]=$this->get_care($care_where);
                     }

                     /*** 这里是抓取推送的照管和司机的地方结束**/
                     /**这里是推送给家长的地方**/
                     if(count($info->students)>0){
                         //说这个站上有小孩，那么去抓取这个小孩的家长信息
                         $where_check=$info->students;
                        // $send[]=$this->get_patriarch($where_check);

                     }
                     /**这里是推送给家长的地方结束**/
                 }

                 $reg_info=array_dispose($send,'jiaoji');

                 foreach ($reg_info as $k => $v){
                     if(array_key_exists('person_name', $v)){
                         if($v['person_name']){
                             $reg_info[$k]['keyword1']['text']=$v['person_name'];
                             $reg_info[$k]['keyword3']['text']=$v['person_name'];
                             $reg_info[$k]['keyword3']['color']='#FF7F24';
                         }
                     }
                 }

                 break;

             case 'arrive':                         //这里是到站提醒1
                 /**第一步，效验这个模板消息是不是可以触发***/
                 $template_id ='9FMbXZpvmbEsl6_a4FLJkvwkILMZoEDCyhDdutRHts0';                //微信的公号中提供的
                 $group_code=$info->group_code;
                 //上学放学状态转换中午
                 $status=$this->UpDown($info->status);
                 $send_message['first']       =$info->path_name.'正在'.$status.'学运行中';
                 $send_message['keyword1']    ='';
                 $send_message['keyword2']    =$status.'学';
                 $send_message['keyword3']    ='家长，您孩子所乘校车已经安全运行在'.$status.'学途中，请您耐心等待';
                 $send_message['keyword4']    =$now_time;
                 //$send_message['keyword5']    ='12121';
                 $send_message['remark']      =$info->group_name.'校车车队竭诚为您服务';



                 $wx_message_info=$this->wx_message($template_id,$send_message);
                 $data=$wx_message_info['data'];

                // dump($group_code);
                // dump($info);

                 /**第二步，查询中他要做差异化的地方***/
                 $basics=$this->check_group($group_code,$info);
                 $url=$basics['url'];
                 $miniprogram=$basics['miniprogram'];


                // dd($miniprogram);

                 /*** 现在做一个推送人员的集合   有多少个数组都可以放在集合中，不管数量的事情**/
                 $send[]=config('page.wx_push');            //平台方的
				$basics=$basics['basics'];

//dd($basics);
                 if($basics){
                    if($basics->patriarch){
                       // $send[]=json_decode($basics->patriarch,true);           //这个是推送给这个学校固定的老师的
                    }

                    /*** 这里是抓取推送的照管和司机的地方**/
                     if($basics->zhao_flag == 'Y'){
                         $care_where=[$info->default_driver_id,$info->default_care_id];
                         //查询中这个相关的司机和照管
                       //  $send[]=$this->get_care($care_where);
                     }
                     /*** 这里是抓取推送的照管和司机的地方结束**/
                     /**这里是推送给家长的地方**/
                     //dump(count($info->school_pathway[$info->next]->students));
                     //dump($info->school_pathway[$info->next]->students);
                     if(count($info->school_pathway[$info->next]->students)>0){
                        //说这个站上有小孩，那么去抓取这个小孩的家长信息
                         //$info->school_pathway[$info->next]->students    这个是wherein的条件
                         $where_check=$info->school_pathway[$info->next]->students;
                        // $send[]=$this->get_patriarch($where_check);
                     }
                     /**这里是推送给家长的地方结束**/ 
                 }
                 $reg_info=array_dispose($send,'jiaoji');
                // dd($reg_info);
                 foreach ($reg_info as $k => $v){
                     if(array_key_exists('person_name', $v)){
                         if($v['person_name']){
                             $reg_info[$k]['keyword1']['text']=$v['person_name'];
                             $reg_info[$k]['keyword3']['text']=$v['person_name'];
                             $reg_info[$k]['keyword3']['color']='#FF7F24';
                         }
                     }
                 }
				// Log::info($reg_info);
				//Log::info('消息推送');
				//Log::info($data);
				
                 break;

			 case ($type=='holiday' || $type=='cancel_holiday'):  //请假通知
				$template_id ='9DO_AxGl-YdpRUfWEE1S8u-JbGlE863D1hBWuuICzC4';                //微信的公号中提供的
                $group_code=$info['data']['group_code'];
				
				 switch ($type){
                     case 'holiday':
                        $send_message['first']       ='您的假期申请已经提交成功';
						$send_message['keyword1']    =$info['data']['person_name'].' '.$info['data']['grade_name'].' '.$info['data']['class_name'];
						$send_message['keyword2']    =$info['data']['up_path_name'].'，'.$info['data']['down_path_name'];
						$send_message['keyword3']    =$info['info'];
						$send_message['keyword4']    =$info['data']['reason'];
						$send_message['keyword5']    =null;//此请假第五个keyword5 必须存在 如果数据库有5个 这里有4个程序报错 提示$wx_message_info['data']中data不存在
						$send_message['remark']      ='查看详情请打开小程序';
                         break;
                     case 'cancel_holiday':
                        $send_message['first']       ='您的假期取消已经提交成功';
						$send_message['keyword1']    =$info['data']['person_name'].' '.$info['data']['grade_name'].' '.$info['data']['class_name'];
						$send_message['keyword2']    =$info['data']['up_path_name'].'，'.$info['data']['down_path_name'];
						$send_message['keyword3']    =$info['info'];
						$send_message['keyword4']    =$info['data']['reason'];
						$send_message['keyword5']    =null;//此请假第五个keyword5 必须存在 如果数据库有5个 这里有4个程序报错 提示$wx_message_info['data']中data不存在
						$send_message['remark']      ='查看详情请打开小程序';
                         break;

                 }
                 $wx_message_info=$this->wx_message($template_id,$send_message);
                 $data=$wx_message_info['data'];

                 $where_group=[
                     ['group_code','=',$group_code],
                     ['delete_flag','=','Y'],
                 ];
                 $basics=SchoolBasics::where($where_group)->select('group_code','group_name','mini_flag','patriarch','zhao_flag')->first();

                $url='';
                $miniprogram=[
                    "appid"=>config('page.smallRoutine')['appId'],
                    "pagepath"=>"/pages/parents/application/detail?holiday_id=".$info['data']['self_id'],
                ];

                 /*** 现在做一个推送人员的集合   有多少个数组都可以放在集合中，不管数量的事情**/
                 $send[]=config('page.wx_push');            //平台方的 
				 //初始化除去平台方的集合
				$gather=[];
                 if($basics){
					 //推送模板消息给班主任 
					 if($info['data']['grade_name'] && $info['data']['class_name']){
						 $teacher=self::getTeacher($info['data']['grade_name'],$info['data']['class_name'],$info['data']['group_code']);
						 if($teacher){
							 $teacher=json_decode(json_encode($teacher,JSON_UNESCAPED_UNICODE),true);
							 $send[]=$teacher;
							 $gather[]=$teacher;
						 }
					 }
                     
					 
                     if($basics->patriarch){
                         $send[]=json_decode($basics->patriarch,true);           //这个是推送给这个学校固定的老师的
						 $gather[]=json_decode($basics->patriarch,true);  
                     }

                     /*** 这里是抓取推送的照管和司机的地方**/
                     if($basics->zhao_flag == 'Y'){
                         //查询中这个相关的司机和照管
                         
						$hosi_where=[
							 ['b.person_id','=',$info['data']['person_id']],
							 ['b.delete_flag','=','Y'],
							 ['a.delete_flag','=','Y'],
						 ];
						 $care_info=DB::table('school_path as a')
							 ->join('school_pathway_person as b',function($join){
								 $join->on('a.self_id','=','b.path_id');
							 }, null,null,'left')
							 ->where($hosi_where)
							 ->select('a.default_driver_id','a.default_care_id','a.path_name','a.site_type')
							 ->get()->toArray();
                         if($care_info){
                             $care_where=[];
                             foreach ($care_info as $k => $v){
                                 $care_where[]=$v->default_driver_id;
                                 $care_where[]=$v->default_care_id;
                             }
                             $send[]=$this->get_care($care_where);
							 $gather[]=$this->get_care($care_where);
                         }
                     }
                     /*** 这里是抓取推送的照管和司机的地方结束**/
                     /**这里是推送给家长的地方**/

                     $where_check[]=$info['data']['person_id'];
                     $send[]=$this->get_patriarch($where_check);
					 $gather[]=$this->get_patriarch($where_check);
                        /**这里是推送给家长的地方结束**/
                 }

                $reg_info=array_dispose($send,'jiaoji');
				
				if(count($gather) > 0){
					//此方法获取三位数组的集合并取出交集信息
					$gather=array_dispose($gather,'jiaoji');
					//Log::info($gather);
					$token_id=array_column($gather,'token_id');
					//Log::info(123);
					//Log::info($token_id);
					$where_token7=[
						['reg_type','=','WEIXIN'],
						['delete_flag','=','Y'],
					];
					$patr_info=DB::table('user_reg')->where($where_token7)->whereIn('token_id',$token_id)->pluck('union_id')->toArray();
					if($patr_info){
						//Log::info(199);
						//Log::info($patr_info);
						$where_token27=[
							['reg_type','=','MINI'],
							['delete_flag','=','Y'],
						];
						$patrinfo2=DB::table('user_reg')->where($where_token27)->whereIn('union_id',$patr_info)->pluck('total_user_id')->toArray();
						//Log::info(199333);
						//Log::info($patrinfo2); 
						if(count($patrinfo2) > 0){
							$where_holiday=[
								['self_id','=',$info['data']['self_id']],
								['delete_flag','=','Y'],
							];
							$sendHoliday['send_gather']=json_encode($patrinfo2); 
							DB::table('school_holiday')->where($where_holiday)->update($sendHoliday);
						}
					}
				}
                 break;
             case 'abnormal':                   //这里是车辆运行异常提醒
                 /**第一步，效验这个模板消息是不是可以触发***/
                 $template_id ='30Qpz4GWRVtOQ25vQ9v5nHMhTU93k4_7MHZ9xNWitCo';                //微信的公号中提供的
                 $group_code=$info->group_code;

                 $keyword4=substr($info->timss,0,10);

                 if($info->carriage_status == 2){
                     if($info->real_longitude && $info->real_latitude ){
                         //出错的站点信息
                         $keyword3=$info->school_pathway[$info->next]->pathway_name;
                         $remark='校车运行线路在站点“'.$keyword3.'”存在未过站的可能，请您核实检查';
                     }else{
                         //说明第一个经纬度就没有过来，那么设备未开始工作
                         $keyword3='设备未工作';
                         $remark='设备未工作,请检查';
                     }
                 }else{
                     $keyword3='车辆未发车';
                     $remark='车辆未发车,请检查';
                 }

             //dump($info->school_pathway[$info->next]->pathway_name);

                 //上学放学状态转换中午
                 $status=$this->UpDown($info->status);
                 $send_message['first']       =$info->group_name.$info->path_name.$status.'学异常';
                 $send_message['keyword1']    =$info->path_name;
                 $send_message['keyword2']    =$info->default_car_brand;
                 $send_message['keyword3']    =$keyword3;
                 $send_message['keyword4']    =$keyword4;
                 //$send_message['keyword5']    ='12121';
                 $send_message['remark']      = $remark;

                 $wx_message_info=$this->wx_message($template_id,$send_message);
                 $data=$wx_message_info['data'];

                 /**第二步，查询中他要做差异化的地方***/
                 $basics=$this->check_group($group_code,$info);
                 $url='';
                 $miniprogram='';
                 $basics=$basics['basics'];
                 /*** 现在做一个推送人员的集合   有多少个数组都可以放在集合中，不管数量的事情11**/
                 //$send[]=config('page.wx_push');            //平台方的
				 $send[]=config('page.error_push');            //平台方的
                 if($basics){
                     if($basics->patriarch){
                         //$send[]=json_decode($basics->patriarch,true);           //这个是推送给这个学校固定的老师的
                     }

                     /*** 这里是抓取推送的照管和司机的地方**/
                     if($basics->zhao_flag == 'Y'){
                         $care_where=[$info->default_driver_id,$info->default_care_id];
                         //查询中这个相关的司机和照管 
                         //$send[]=$this->get_care($care_where);
                     }
                     /*** 这里是抓取推送的照管和司机的地方结束**/
                 }

                 $reg_info=array_dispose($send,'jiaoji');
                 break;
			case 'rollCall':
                /**第一步，效验这个模板消息是不是可以触发***/
                $template_id ='9FMbXZpvmbEsl6_a4FLJkvwkILMZoEDCyhDdutRHts0';                //微信的公号中提供的
                $group_code=$info->group_code;
				//判断上午和下午是上车还是下车
                switch ($info->site_type){
                    case 'UP':
                        if($info->next == $info->end){
                            $rollCall_text='下车';
                        }else{
                            $rollCall_text='上车';
                        }
                        break;
                    case 'DOWN':
                        if($info->next>0){
                            $rollCall_text='下车';
                        }else{
                            $rollCall_text='上车';
                        }
                        break;
                }
				$send_message['first']       =$info->group_name;
                $send_message['keyword1']    =$info->path_name;
                $send_message['keyword2']    =$info->school_pathway[$info->next]->pathway_name;
                $send_message['keyword3']    =$rollCall_text;
                $send_message['remark']      ='点名名单请打开小程序';

                $url='';
                $miniprogram=[
                    "appid"=>"wx6e301696cc179a6b",
                    //"pagepath"=>"/pages/roll_call/roll_call?carriage_id=car_path_2020090613253464723574DOWN2020-10-13&pathway_id=pathway_202009071834089799265300" 
                    "pagepath"=>"/pages/roll_call/roll_call?carriage_id=".$info->carriage_id.'&pathway_id='.$info->school_pathway[$info->next]->pathway_id,
                ];
				 $wx_message_info=$this->wx_message($template_id,$send_message);
                $data=$wx_message_info['data'];

                $where_group=[
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
                ];
                $basics=SchoolBasics::where($where_group)->select('group_code','group_name','mini_flag','patriarch','zhao_flag')->first();

                $send[]=config('page.error_push');            //平台方的
                if($basics){
                    if($basics->patriarch){
                        //$send[]=json_decode($basics->patriarch,true);           //这个是推送给这个学校固定的老师的
                    }

                    /*** 这里是抓取推送的照管和司机的地方**/
                    if($basics->zhao_flag == 'Y'){
                        $care_where=[$info->default_driver_id,$info->default_care_id];
                        //查询中这个相关的司机和照管
                        //$send[]=$this->get_care($care_where);
                    }
                    /*** 这里是抓取推送的照管和司机的地方结束**/
                }
				if($send){
                    $reg_info=array_dispose($send,'jiaoji');
                }
				break;
         }

       // dd($miniprogram);
        if($wx_config && $template_id && $reg_info && $data){
            //如果都有则
            /*** 这里开始触发推送消息，这个时候需要将数据存储进入推送消息表**/
            $datatemplist['self_id']            =generate_id('temp');
            $datatemplist['app_id']             =$wx_config['app_id'];
            $datatemplist['template_id']        =$template_id;
            $datatemplist['message_info']       =json_encode($data,JSON_UNESCAPED_UNICODE);
            $datatemplist['reg_info']           =json_encode($reg_info,JSON_UNESCAPED_UNICODE);
            $datatemplist['date_time']          =date('Y-m-d',time());
            $datatemplist['create_time']        =$datatemplist['update_time']=$now_time;
            $datatemplist['template_type']      =$wx_message_info['wx_message_info']->template_title;
            $datatemplist['group_code']         =$group_code;
            $datatemplist['group_name']         =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            $datatemplist['count']              =count($reg_info);
            $datatemplist['create_user_id']     =null;
            $datatemplist['create_user_name']   ='系统触发的';
			$datatemplist['type']   			=$type;
			$datatemplist['original_info']      =json_encode($info,JSON_UNESCAPED_UNICODE);
            $id=WxMessageSend::insert($datatemplist);
//dd($id);
            $push->send($wx_config,$template_id,$reg_info,$data,$url,$miniprogram);

            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "处理成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "处理失败";
                return $msg;
            }
        }

    }

    /**
     * 校车上学和放学运行状态中文转换
     * @param $UpDown
     * @return string
     */
    private function UpDown($UpDown){
        switch($UpDown){
            case 'DOWN':
                $status='放';
                break;
            case 'UP':
                $status='上';
                break;
        }
        return $status;
    }

    /**
     * 转化wx_message    以及效验是不是存在的地方
     * @param $template_id
     * @return $data
     */
    private function wx_message($template_id,$send_message){
        $where=[
            ['wx_template_id','=',$template_id],
            ['delete_flag','=','Y'],
        ];
        $wx_message_info=WxMessage::where($where)->select('template_info','template_title')->first();
        //dd($wx_message_info);
        if($wx_message_info){
            $wx_message_info->template_info=json_decode($wx_message_info->template_info);
        }else{
            $msg['code'] = 301;
            $msg['msg'] = "模板不存在";
            return $msg;
        }
        $data   =  wx_message_info($wx_message_info->template_info,$send_message);
        return ['wx_message_info'=>$wx_message_info,'data'=>$data];
    }

    /**
     * 判断差异化的地方，处理数据源1
     * @param $template_id1
     * @return $data
     */
    private function check_group($group_code,$info){
        $where_group=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
        ];
        $basics=SchoolBasics::where($where_group)->select('group_code','group_name','mini_flag','patriarch','zhao_flag')->first();
       // dd($basics);
        if($basics){
            if($basics->mini_flag == 'Y'){
                $url='';
                $miniprogram=[
                    "appid"=>"wx6e301696cc179a6b",
                    "pagepath"=>"/pages/login/login?carriage_id=".$info->carriage_id,
                ];
            }else{
				$url='';
				$miniprogram='';
			}
        }else{
            $url='';
            $miniprogram='';
        }

        return ['url'=>$url,'miniprogram'=>$miniprogram,'basics'=>$basics];

    }

    /**
     * 抓取司机及照管的地方
     * @param $template_id
     * @return $data
     */
    private function get_care($care_where){
        $zhao_where=[
            ['a.reg_type','=','WEIXIN'],
            ['a.delete_flag','=','Y'],
        ];
        $care_info=DB::table('user_reg as a')
            ->join('school_info as b',function($join){
                $join->on('a.union_id','=','b.union_id');
            }, null,null,'left')
            ->where($zhao_where)->whereIn('b.self_id',$care_where)
            ->select('a.token_id','a.token_name','a.person_type')
            ->get()->toArray();

        return $care_info;
    }

    /**
     * 抓取家长的地方
     * @param $template_id
     * @return $data
     */
    private function get_patriarch($where_check){
        $patriarch_where['a.reg_type']='WEIXIN';
        $patriarch_info=DB::table('user_reg as a')
            ->join('school_person_relation as b',function($join){
                $join->on('a.union_id','=','b.union_id');
            }, null,null,'left')
            ->where($patriarch_where)
            ->whereIn('b.person_id',$where_check)
            ->select('a.token_id','a.token_name','a.person_type','b.person_name')
            ->get()->toArray();

        return $patriarch_info;
    }

	/**
     * 获取班主任的关注微信公众号的openid
     * @param $grade_name
     * @param $class_name
     * @return array
     */
    private static function getTeacher($grade_name,$class_name,$group_code){
        if(is_array($grade_name)|| is_object($grade_name) || is_array($class_name) || is_object($class_name)){
            return false;
        }

        $where_teacher=[
            ['a.reg_type','=','WEIXIN'],
            ['a.delete_flag','=','Y'],
            ['b.person_type','=','teacher'],
            ['b.grade_name','=',$grade_name],
            ['b.class_name','=',$class_name],
			['b.group_code','=',$group_code],
        ];
        $info_teacher=DB::table('user_reg as a')
            ->join('school_info as b',function($join){
                $join->on('a.union_id','=','b.union_id');
            }, null,null,'left')
            ->where($where_teacher)
            ->select('a.token_id','a.token_name','a.person_type')
            ->get()->toArray();
        return $info_teacher;
    }

}