<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use Closure;
use App\Models\Log\LogLogin;
class FrontCheck{
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
    	//这个中间件去login表去拿数据，是从那个端口进来了，然后去里面做分支
        /**  前端接口应该走3个中间件，
         *  第一个是中间件应该是通过用户的ftoken  去拿取数据，                决定了后面一个中间件走那个分支1
         ** 第二个中间件是拿取用户准确信息的地方，也拿取分享数据的地方11
         * 第三个中间件控制用户是不是登录情况1
         */
        $user_token=$request->header('ftoken');
		$mini_token=$request->get('ftoken');

		$user_token=$user_token??$mini_token;

//        $user_token='72d0581f7b979e9ed60892692729b7d1';

        $token_where=[
            ['user_token','=',$user_token],
            ['delete_flag','=','Y'],
        ];
        $token_info=LogLogin::where($token_where)->select('user_id','user_token','type')->first();

        $mid_params = ['token_info'=>$token_info];

        $request->attributes->add($mid_params);                 //(暂存数据)参数
        //dd($token_info);
        return $next($request);
    }
}
