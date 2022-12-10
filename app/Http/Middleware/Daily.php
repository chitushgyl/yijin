<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use Closure;
use App\Models\Log\LogRecordChange;

class Daily{
    /**
     * 此中间件主要的任务是处理操作日志的地方，用来记录系统的操作过程，方便查询后台操作员的操作行为
     */
    public function handle($request, Closure $next){
        $response       = $next($request);
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数11
        $temp           = $response->original;
        $type=$operationing->type??'add';
        /*** 公用的部分拿出来**/
        $operationing_in['access_cause']    =$operationing->access_cause;
        $operationing_in['browse_type']     =$request->getPathInfo();
        $operationing_in['table']           =$operationing->table;
        $operationing_in['create_user_id']  =$user_info->admin_id;
        $operationing_in['create_user_name']=$user_info->name;
        $operationing_in['create_time']     =$operationing_in['update_time']    =$operationing->now_time;
        $operationing_in['group_code']      =$user_info->group_code;
        $operationing_in['group_name']      =$user_info->group_name;
        $operationing_in['operation_type']  =$operationing->operation_type;
        if($user_info->login == 'admin'){
            $operationing_in['admin_flag']='N';
        }else{
            $operationing_in['admin_flag']='Y';
        }

        $operationing_in['ip']=$request->getClientIp();
        $operationing_in['log_status']=$temp['code'];
        $operationing_in['result']=$temp['msg'];

        switch ($type){
            case 'add':
                $operationing_in['self_id']         = generate_id('change_');

                $operationing_in['table_id']        =$operationing->table_id;
                if($operationing->old_info){
                    $operationing_in['old_info']    = json_encode($operationing->old_info,JSON_UNESCAPED_UNICODE);
                }

                if($operationing->new_info){
                    $operationing_in['new_info']    = json_encode($operationing->new_info,JSON_UNESCAPED_UNICODE);
                }

                $operationing_in['temp_info']= json_encode($temp,JSON_UNESCAPED_UNICODE);


                LogRecordChange::insert($operationing_in);
                break;

            case 'import':

                if($temp['code'] == 200){
                    foreach ($operationing->new_info as $k => $v){
                        $operationing_in['self_id']     = generate_id('change_');
                        $operationing_in['table_id']    =$v['self_id'];
                        $operationing_in['new_info']    = json_encode($v,JSON_UNESCAPED_UNICODE);

                        $inser[]=$operationing_in;
                    }


                    if(count($inser)>0){
                        $inser_chunk=array_chunk($inser,1000);
                        foreach ($inser_chunk as $k=>$v){
                            LogRecordChange::insert($v);
                        }
                    }


//                    LogRecordChange::insert($inser);

                }else{
                    $operationing_in['self_id']     = generate_id('change_');
                    LogRecordChange::insert($operationing_in);
                }

                break;

			case 'move':

                if($temp['code'] == 200){
					foreach	($operationing->old_info as $k => $v){
						$operationing_in['self_id']     = generate_id('change_');
                        $operationing_in['table_id']    =$v['self_id'];
						$operationing_in['old_info']    = json_encode($v,JSON_UNESCAPED_UNICODE);

						$old_infoss=$v;
						$old_infoss['now_num']=$v['now_num_new'];

                        $operationing_in['new_info']    = json_encode($old_infoss,JSON_UNESCAPED_UNICODE);

						$inser[]=$operationing_in;
					}

                    foreach ($operationing->new_info as $k => $v){
                        $operationing_in['self_id']     = generate_id('change_');
                        $operationing_in['table_id']    =$v['self_id'];
						$operationing_in['old_info']    = null;
                        $operationing_in['new_info']    = json_encode($v,JSON_UNESCAPED_UNICODE);

                        $inser[]=$operationing_in;
                    }

                    if(count($inser)>0){
                        $inser_chunk=array_chunk($inser,1000);
                        foreach ($inser_chunk as $k=>$v){
                            LogRecordChange::insert($v);
                        }
                    }

//                    LogRecordChange::insert($inser);

                }else{
                    $operationing_in['self_id']     = generate_id('change_');
                    LogRecordChange::insert($operationing_in);
                }

                break;

				case 'piliang':

                if($temp['code'] == 200){

                    foreach ($operationing->old_info as $k => $v){
                        $operationing_in['self_id']     = generate_id('change_');
                        $operationing_in['table_id']    =$v['self_id'];
						$operationing_in['old_info']    = json_encode($v,JSON_UNESCAPED_UNICODE);

                        $operationing_in['new_info']    = json_encode($operationing->new_info,JSON_UNESCAPED_UNICODE);

                        $inser[]=$operationing_in;
                    }

                    if(count($inser)>0){
                        $inser_chunk=array_chunk($inser,1000);
                        foreach ($inser_chunk as $k=>$v){
                            LogRecordChange::insert($v);
                        }
                    }

//                    LogRecordChange::insert($inser);

                }else{
                    $operationing_in['self_id']     = generate_id('change_');
                    LogRecordChange::insert($operationing_in);
                }

                break;
        }

        return $response;
    }
}
?>
