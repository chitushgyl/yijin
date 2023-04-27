<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use App\Models\Log\LogLogin;
use App\Models\SysFoot;
use Closure;
class LoginCheck{
    /**
     * Handle an incoming request.1
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null){
        $now_time=date('Y-m-d H:i:s',time());
        /** 用户登录后，传递给前台一个token，前端存储为dtoken  ，之后，使用dtoken  在头部中传递给后台*/

        /*** 做3个假数据，一个是平台方的账号，一个是公司账号，一个是分公司的账号**/
        $user_token=$request->header('dtoken');
//        $user_token='1bfe7d8f5a656830f7442bed87eece3f';             //超级管理员
//        $user_token='ea8d7f618cd0457bc0eb6f8eebc1f85a';             //集团总公司账号
//        $user_token='f7d31b8f44e8dc01a388075b2969e5cc';             //集团分公司的账号

        $user_info=null;
        /**查询出这个用户的信息出来**/
        $user_where=[
            ['user_token','=',$user_token],
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
			['type','=','after'],
            ['login_status','=','SU'],
        ];

        $where_group=[
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
        ];



        $user_info = LogLogin::wherehas('systemAdmin',function($query)use($where_group){
                $query->where($where_group);
            })->with(['systemAdmin' => function($query)use($where_group){
                $query->select('self_id','login','name','authority_id','authority_name','group_code','group_name');
                $query->wherehas('systemAuthority',function($query)use($where_group){
                    $query->where($where_group);
                });
                $query->with(['systemAuthority' => function($query){
                    $query->select('self_id','menu_id','group_id');
                }]);
                $query->wherehas('systemGroup',function($query)use($where_group){
                    $query->where($where_group);
                });
                $query->with(['systemGroup' => function($query){
                    $query->select('group_code','menu_id','group_id','expire_time','company_image_url','group_qr_code','delete_flag','use_flag','company_type','threshold');
                }]);
            }])
            ->where($user_where)
            ->select('user_id')
            ->first();


//        dump($user_info->toArray());
        if(empty($user_info)){
            $msg['code']=401;
            $msg['msg']="未登录或者登陆失效";
            echo json_encode($msg);
            exit;
        }

        if(empty($user_info->systemAdmin)){
            $msg['code']=401;
            $msg['msg']="未登录或者登陆失效";
            echo json_encode($msg);
            exit;
        }

        if($now_time>$user_info->systemAdmin->systemGroup->expire_time){
            $msg['code']=401;
            $msg['msg']="您的后台使用已到期，请联系管理员";
            echo json_encode($msg);
            exit;
        }



        $user_info->admin_id            =$user_info->systemAdmin->self_id;
        $user_info->login               =$user_info->systemAdmin->login;
        $user_info->name                =$user_info->systemAdmin->name;
        $user_info->group_code          =$user_info->systemAdmin->group_code;
        $user_info->group_name          =$user_info->systemAdmin->group_name;
        $user_info->group_qr_code       =img_for($user_info->systemAdmin->systemGroup->group_qr_code,'no_json');
        $user_info->expire_time         =$user_info->systemAdmin->systemGroup->expire_time;
        $user_info->authority_name      =$user_info->systemAdmin->authority_name;
		$user_info->group_group_id		=$user_info->systemAdmin->systemGroup->group_id;
		$user_info->authority_group_id  =$user_info->systemAdmin->systemAuthority->group_id;
		$user_info->authority_id  		=$user_info->systemAdmin->authority_id;


//        dump($user_info->toArray());


        /** 做一个 还有多少有效期的事情**/
        $startdate  		=strtotime($now_time);
        $enddate    		=strtotime($user_info->expire_time);
        $user_info->days    =intval(round(($enddate-$startdate)/3600/24)) ;                 //还有多少天数到期


        /*** 处理一下权限的问题   做成一个权限的数组，主要是为了saas拿取多公司数据的时候使用的
         *   这个地方要注意的是：公司权限，自己的权限，如果都是all才是all 如果不是all则取2个值中的小的值
         *  还有一个比较需要思考的问题，如果没有给本公司的权限，我们现在是加的，那么到底要不要的问题
         **/
        $group_info['group_id']=null;
        $group_info['group_code']=null;

		//dump($user_info->systemAdmin->systemAuthority->group_id);
		//dump($user_info->systemAdmin->systemGroup->group_id);
        if($user_info->systemAdmin->systemAuthority->group_id  =='all' && $user_info->systemAdmin->systemGroup->group_id	=='all'){
            $group_id='all';
        }else{

            if($user_info->systemAdmin->systemAuthority->group_id	=='all'){
                $authority_group_id=null;
            }else{
                $authority_group_id=explode('*',$user_info->systemAdmin->systemAuthority->group_id);
            }

            if($user_info->systemAdmin->systemGroup->group_id	=='all'){
                $group_group_id=null;
            }else{
                $group_group_id=explode('*',$user_info->systemAdmin->systemGroup->group_id);
            }

            if($authority_group_id && $group_group_id){
                $group_id=array_intersect($group_group_id,$authority_group_id);
            }else{
                $group_id=$authority_group_id;
            }
        }
        $user_info->group_id=$group_id;

        /*** 处理一下菜单 规则和数据权限一致*/
        if($user_info->systemAdmin->systemAuthority->menu_id  =='all' && $user_info->systemAdmin->systemGroup->menu_id	=='all'){
            $menu_id='all';
        }else{

            if($user_info->systemAdmin->systemAuthority->menu_id	=='all'){
                $authority_menu_id=null;
            }else{
                $authority_menu_id=explode('*',$user_info->systemAdmin->systemAuthority->menu_id);
            }

            if($user_info->systemAdmin->systemGroup->menu_id	=='all'){
                $group_menu_id=null;
            }else{
                $group_menu_id=explode('*',$user_info->systemAdmin->systemGroup->menu_id);
            }

            if($authority_menu_id && $group_menu_id){
                $menu_id=implode("*", array_intersect($authority_menu_id,$group_menu_id));
            }else{
                $menu_id=implode("*", $authority_menu_id);
            }
        }
        $user_info->menu_id=$menu_id;


//dd($user_info->toArray());1
        /** **/
        if($user_info->group_id){
            if($user_info->group_id == 'all'){
                $group_info['group_id']='all';
                $session_group_code2323='all';
            }else{
                $session_group_code2323=$user_info->group_id;
                $count=count($session_group_code2323);

                if($count>1){
                    $group_info['group_id']='more';
                }else{
                    $group_info['group_id']='one';
                }

            }
        }else{
            $group_info['group_id']='one';
            $session_group_code2323[]=$user_info->group_code;
        }
        $group_info['group_code']=$session_group_code2323;

//        dd($group_info);
        $mid_params = ['user_info'=>$user_info,'group_info'=>$group_info];
        /** 处理个人中心订单操作按钮开始 **/
        $button=[];
        if ($user_info->admin_id){
//            $user_info->type = 'TMS3PL';
            $user_info->type = $user_info->systemAdmin->systemGroup->company_type;
        }
        $button_where['type']='button';
        $button_where['delete_flag']='Y';
        $button_where['project_type'] = $user_info->type.'_button';
//            dd($anniu_where['project_type']);
        $select=['id','name','app_path','project_type','level','path','button_color','app_url','routine_path'];

        $button=SysFoot::where($button_where)->select($select)->get();

//            dd($anniu->toArray());
        $mid_params['buttonInfo'] = $button;
        /** 处理个人中心订单操作按钮结束 **/
        $request->attributes->add($mid_params);                 //(暂存数据)参数cha1
        return $next($request);

    }
}
?>
