<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use Illuminate\Support\Facades\DB;
use Closure;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\RedisController;

class HoldCheck{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     * 通过user_token  换取   $user_id
     * 通过$user_id    换取   $user_info      如果没有$user_info  则去数据库中捞起信息放在redis中
     * 返回各个页面   $user_token     $user_id            $user_info
     */
    public function handle($request, Closure $next, $guard = null){

        $user_info = $request->get('user_info');//接收中间件产生的参数
        //dump($user_info);
        if($user_info){
            return $next($request);
        }else{
            //dd(23);
            $msg['code']=401;
            $msg['msg']="未登录或者登陆失效";
            echo json_encode($msg);
            exit;
        }
    }
}
?>
