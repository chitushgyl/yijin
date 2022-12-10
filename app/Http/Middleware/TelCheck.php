<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use Closure;


class TelCheck{
    /**
     * 此中间件主要的任务是处理操作日志的地方，用来记录系统的操作过程，方便查询后台操作员的操作行为
     */
    public function handle($request, Closure $next){
        $response       = $next($request);
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        if($user_info){
            if ($user_info->tel){
                return $response;
            }else{
                $msg['code']=403;
                $msg['msg']="请绑定手机号";
                echo json_encode($msg);
                exit;
            }
        }else{
            return $response;
        }
    }
}
?>
