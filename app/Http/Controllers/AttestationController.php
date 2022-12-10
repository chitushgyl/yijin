<?php
namespace App\Http\Controllers;
use App\Models\Group\SystemAdmin;
use App\Models\Group\SystemAuthority;
use App\Models\Group\SystemGroup;
use App\Models\Group\SystemGroupAuthority;
use App\Models\Log\LogLogin;
use App\Models\SysAddressAll;
use App\Models\Shop\ShopFreight;
use App\Models\Tms\TmsAttestation;
use App\Models\User\UserCapital;
use App\Models\User\UserIdentity;

class AttestationController extends Controller{
    /***    初始化公司快递地址，在公司创建的地方用，在运费的板块要补充
     */
    public function attestationPass($res,$user_info){
        $now_time = date('Y-m-d H:i:s',time());
        $info = $res;
        /**保存公司数据**/
        $group_where = [
            ['group_name','=',$info['name']],
            ['delete_flag','!=','N']
        ];
        $count_group = SystemGroup::where($group_where)->count();
        if ($count_group>0){
            $msg['code'] = 303;
            $msg['msg'] = '认证公司名称不能重复！';
            return $msg;
        }
        $data['group_name']         =$info['name'];
        $data['tel']                =$info['tel'];
        $data['business_type']      ='TMS';
        $data['city']               =$info['shi_name'];
        $data['address']            =$info['address'];

        /** 查询出所有的预配置权限，然后把预配置权限给与这个公司**/
        $where_business=[
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
            ['business_type','=','TMS'],
        ];
        $select=['self_id','authority_name','menu_id','leaf_id','type','cms_show'];
        $authority_info=SystemGroupAuthority::where($where_business)->orderBy('create_time', 'desc')->select($select)->get();
        $authority_list=[];
        if($authority_info){
            foreach ($authority_info as $k => $v){
                if($v->type == 'system'){
                    $data['menu_id']       =$v->menu_id;
                    $data['leaf_id']       =$v->leaf_id;
                    $data['cms_show']      =$v->cms_show;
                }else{
                    $authority_list[]=$v;
                }
            }

        }

        //说明是新增1
        $group_code                 =generate_id('group_');
        $data['self_id']            =$data['group_code']=$data['group_id']=$group_code;
        $data['create_time']        =$data['update_time']=$now_time;
        $data['group_id_show']      =$data['group_name'];
        $data['create_user_id']     ='1234';
        $data['create_user_name']   ='共享平台';
        $data['father_group_code']  ='1234';
        $data['binding_group_code'] ='1234';
        $data['use_flag']           = 'N';

        //$data['user_number'] 		=$user_number;

        //添加公司资金表
        $capital_data['self_id']        = generate_id('capital_');
        $capital_data['total_user_id']  = null;
        $capital_data['group_code']    =$group_code;
        $capital_data['group_name']    =$data['group_name'];
        $capital_data['update_time']    =$now_time;
        UserCapital::insert($capital_data);						//写入用户资金表

        $id=SystemGroup::insert($data);

        /**添加数据权限**/
        if ($info['type'] == 'TMS3PL'){
            //认证3pl公司
            $dat['menu_id']='145*157*620*624*621*622*623*141*142*511*227*228*467*273*232*233*143*285*512*159*177*255*309*242*602*258*256*257*161*178*532*259*555*260*261*262*477*590*484*671*672*673*294*584*629*640*586*587*588*589*211*626*597*596*637*315*601*600*307*612*609*608*628*639*610*611*613*618*615*619*614*646*616*617*150*212*647*224*272*191*636*645*643*644*306*648*252*631*632*649*633*634*650*651*652*653*241*299*199*202';
            $dat['leaf_id']='145*157*620*624*621*622*623*141*142*511*227*228*467*273*232*233*143*285*512*159*177*255*309*242*602*258*256*257*161*178*532*259*555*260*261*262*477*590*484*671*672*673*294*584*629*640*586*587*588*589*211*626*597*596*637*315*601*600*307*612*609*608*628*639*610*611*613*618*615*619*614*646*616*617*150*212*647*224*272*191*636*645*643*644*306*648*252*631*632*649*633*634*650*651*652*653*241*299*199*202';
            $dat['cms_show']='公司权限员工管理　首页　TMS调度系统　系统设置　操作记录　TMS基础设置　TMS线上订单';
        }else{
            //认证货主公司
            $dat['menu_id']='141*294*150*142*511*227*228*467*273*232*233*143*512*285*159*177*255*309*242*602*258*257*256*161*532*555*259*178*260*261*262*307*612*608*609*628*639*610*611*212*647*224*272*306*648*252*631*632*649*633*634*650*651*652*653*241*299*199*202';
            $dat['leaf_id']= '142*511*227*228*467*273*232*233*143*512*285*159*177*255*309*242*602*258*257*256*161*532*555*259*178*260*261*262*307*612*608*609*628*639*610*611*212*647*224*272*306*648*252*631*632*649*633*634*650*651*652*653*241*299*199*202';
            $dat['cms_show']='公司权限员工管理　TMS调度系统　操作记录　TMS基础设置　TMS线上订单';
        }

        $dat['group_id']=$group_code;
        $dat['group_id_show']=$info['name'];

        //查询一下这个里面是不是第一个权限，如果是第一个权限，则把他设置为lock_flag 为Y
        $wheere['group_code']=$group_code;
        $wheere['delete_flag']='Y';
        $idsss=SystemAuthority::where($wheere)->value('self_id');
        if($idsss){
            $dat["lock_flag"]='N';
        }else{
            $dat["lock_flag"]='Y';
        }
        $dat["self_id"]             =generate_id('authority_');
        $dat["authority_name"]      =$info['name'];
        $dat['create_user_id']      ='admin_id201710272123271474838779197';
        $dat['create_user_name']    ='系统管理员';
        $dat["create_time"]         =$dat["update_time"]=$now_time;
        $dat["group_code"]          =$group_code;
        $dat["group_name"]          =$info['name'];

        $authhority=SystemAuthority::insert($dat);

        /**添加账号信息**/


        $name_where=[
            ['login','=',$info['login_account']],
            ['delete_flag','!=','N'],
        ];

        $name_count = SystemAdmin::where($name_where)->count();            //检查名字是不是重复

//        if($name_count > 0){
//            $msg['code'] = 301;
//            $msg['msg'] = '账号名称重复！';
//            return $msg;
//        }
        $account['login']              =$info['login_account'];
        $account['name']               =$info['name'];
        $account['tel']                =$info['tel'];
        $account['email']              =$info['email'];
        $account['group_code']         =$group_code;
        $account['group_name']         =$info['name'];
        $account['authority_id']       =$dat['self_id'];
        $account['authority_name']     =$info['name'];
        $account['use_flag']           ='N';

        $account['self_id']=generate_id('admin_');
        $account['pwd']=get_md5(123456);
        $account['create_time']=$account['update_time']=$now_time;
        $account['create_user_id'] ='admin_id201710272123271474838779197';
        $account['create_user_name'] = '系统管理员';
        $admin = SystemAdmin::insert($account);

        /**绑定身份**/
        $identity['self_id']            = generate_id('identity_');
        $identity['total_user_id']      =$user_info->total_user_id;
        if ($info['type'] == 'TMS3PL'){
            $identity['type']               ='TMS3PL';
        }else{
            $identity['type']               ='company';
        }

        $identity['create_user_id']     ='1234';
        $identity['create_user_name']   = '共享平台';
        $identity['group_code']         =$group_code;
        $identity['group_name']         =$info['name'];
        $identity['admin_login']        =$info['tel'];
        $identity['total_user_id']      =$info['total_user_id'];
        $identity['atte_state']         = 'W';
        $identity['create_time']       = $identity['update_time'] = $now_time;
        $user_identity=UserIdentity::insert($identity);

        /***切换身份**/
        $switch = 'Y';//是否默认切换身份 Y是 N不
        if($switch == 'Y'){
            $where = [
                ['default_flag','=','Y'],
                ['delete_flag','=','Y'],
                ['total_user_id','=',$info['total_user_id']]
            ];
            $user_update['default_flag'] = 'N';
            $user_update['update_time'] = $now_time;
            UserIdentity::where($where)->update($user_update);

            $arr['default_flag'] = 'Y';
            $arr['update_time'] = $now_time;
            $id=UserIdentity::where('self_id','=',$identity['self_id'])->update($arr);

            /** 第三步，如果这个切换的属性是SPL的，帮他完成一次后台的登录**/
            $user_token=null;
            if($info['type'] =='TMS3PL' || $info['type'] == 'company'){
                $user_token					 =md5($account['self_id'].$now_time);
                $reg_place                   ='CT_H5';
                $token_data['self_id']       = generate_id('login_');
                $token_data["login"]         = $info['login_account'];
                $token_data["user_id"]         = $account['self_id'];
                $token_data['type']          = 'after';
                $token_data['login_status']  = 'SU';
                $token_data["user_token"]    = $user_token;
                $token_data["create_time"]   = $token_data["update_time"] = $now_time;
                $id=LogLogin::insert($token_data);
            }
        }
    }






}
