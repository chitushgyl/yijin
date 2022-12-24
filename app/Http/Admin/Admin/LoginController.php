<?php
namespace App\Http\Admin\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Group\SystemGroup;
use App\Models\Group\SystemAdmin;
use App\Models\Log\LogLogin;

class LoginController extends Controller{
    /***    登录拉取接口      /login/login
     */
    public function login(Request $request){
		//dd(1212121);
        $host=$request->header('host');
        $where=[
            ['domain_name','=',$host],
        ];
        $select=['group_name','company_image_url'];
        //$where['domain_name']='ceshiadmin.zhaodaolo.com';

        $group_info=SystemGroup::where($where)->select($select)->first();

        if(empty($group_info)){
            $group_info=SystemGroup::select($select)->first();
        }

        $group_info->company_image_url=img_for($group_info->company_image_url,'more');
        $msg['code']=200;
        $msg['msg']="登录成功";
        $msg['data']=$group_info;

        return $msg;

    }

    /***    登录拉取接口      /login/loginOn
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function loginOn(Request $request){
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='user_login';
        $input          =$request->all();
        /** 接收数据*/
        $login          =$request->input('login');
        $pwd            =$request->input('pwd');

		//dump($login);dump($pwd);
        /**  虚拟数据
        $input['login']=$login='admin';
        $input['pwd']=$pwd='forlove504616';
         */

        $rules=[
            'login'=>'required',
            'pwd'=>'required',
        ];
        $message=[
            'login.required'=>'账号输入为空',
            'pwd.required'=>'密码输入为空',
        ];


        $validator=Validator::make($input,$rules,$message);
       //dump($input);
        if($validator->passes()){
            $user_token=null;
            $select=['self_id','login','pwd','name','group_code','group_name','use_flag','authority_id','delete_flag'];
            $where=[
                ['login','=',$login],
                ['delete_flag','=','Y'],
                ['use_flag','=','Y'],
            ];
            $user_track=[
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];

            $user_info = SystemAdmin::wherehas('systemAuthority',function($query)use($user_track){
                $query->where($user_track);
            })->wherehas('systemGroup',function($query)use($user_track){
                $query->where($user_track);
            })->with(['systemGroup' => function($query) {
                $query->select('group_code','expire_time');
            }])->where($where)
                ->select($select)
                ->first();


            if($user_info){
                $md5_pwd=get_md5($pwd);             //前端输入的密码1
                if($md5_pwd != $user_info->pwd){
                    //输入的密码错误
                    $msg['code']=301;
                    $msg['msg']='密码输入错误';
                }else{
                    if($now_time>$user_info->systemGroup->expire_time){
                        $msg['code']=302;
                        $msg['msg']="您的后台使用已到期，请联系管理员";
                    }else{
                        $user_token= md5($user_info->self_id.$now_time);
                    }
                }
            }else{
                $msg['code']=303;
                $msg['msg']='账号异常，请联系管理员';           //给用户的信息提醒
            }


            //dump($user_token);
            if($user_token){

                $token_data["user_token"]    =$user_token;
                $token_data["user_id"]       =$user_info?$user_info->self_id:null;
                $token_data["user_name"]     =$user_info?$user_info->name:null;
                $token_data["group_code"]    =$user_info?$user_info->group_code:null;
                $token_data["group_name"]    =$user_info?$user_info->group_name:null;
                $token_data["login_status"]  ='SU';
                $token_data['user_info']    = json_encode($user_info,JSON_UNESCAPED_UNICODE);

            }else{
                $token_data["result"]        =$msg['msg'];
                $token_data["pwd"]           =$pwd;
                $token_data["login_status"]  ='FS';
            }

            $token_data['self_id']       = generate_id('login_');
            $token_data["login"]         =$login;
            $token_data["ip"]            =$request->getClientIp();
            $token_data['type']          = 'after';
            $token_data['create_time']   =$token_data['update_time']=$now_time;

            $id=LogLogin::insert($token_data);

            if($id && $user_token){
                $msg['code']    =200;
                $msg['msg']     ="登录成功";
                $msg['data']    =$user_token;
                return $msg;
            }else{
                $msg['code']    =$msg['code']??304;
                $msg['msg']     =$msg['msg']??"登录失败";
                return $msg;
            }


        }else{
            //前端用户没有输入完整的信息
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


    /***    注销接口      /login/loginOut
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function loginOut(Request $request){
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='user_login';
        /** 接收数据*/
        $user_token     =$request->header('dtoken');
        //$user_token     ='7f449c062063493392691ac6bfcab5fb';
        //查询出这个用户的信息出来
        $user_where=[
            ['user_token','=',$user_token],
        ];

        $user_info = LogLogin::with(['systemAdmin' => function($query) {
            $query->select('self_id','login','name','group_code','group_name');
        }])->where($user_where)
            ->select('self_id','user_id')->first();
        if($user_info){
            //把这个token处理掉
            $where_login['user_token']=$user_token;

            $update['update_time']  =$now_time;
            $update['use_flag']     ='N';
            $id=LogLogin::where($where_login)->update($update);
            //dd($id);
            if($id){
                $msg['code']=200;
                $msg['msg']="注销成功";
                return $msg;

            }else{
                $msg['code']=301;
                $msg['msg']="注销失败";
                return $msg;
            }

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到信息";
            return $msg;
        }


    }

}
?>
