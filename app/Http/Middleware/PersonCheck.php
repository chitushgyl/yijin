<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use Closure;
use App\Models\School\SchoolInfo;

class PersonCheck{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        //通过电话号码去抓角色出来
        if($user_info->tel){
            $where=[
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
                ['person_tel','=',$user_info->tel],
            ];
            $abc=SchoolInfo::where($where)->select('self_id','person_type')->first();

            if($abc){
                $user_info->person_id   =$abc->self_id;
                $user_info->person_type =$abc->person_type;
            }else{
                $user_info->person_id=null;
                $user_info->person_type=null;
            }

        }else{
            $user_info->person_id=null;
            $user_info->person_type=null;
        }

        $mid_params = ['user_info'=>$user_info];

        $request->attributes->add($mid_params);                 //(暂存数据)参数cha

        //dd($request->attributes);
        return $next($request);

    }
}
?>
