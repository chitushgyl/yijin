<?php
namespace App\Http\Controllers;
use App\Models\Shop\ShopFreight;
use App\Models\User\UserCapital;
use App\Models\Group\SystemAuthority;
use App\Models\SysAddress;
use App\Models\Group\SystemGroup;
class CompanyController extends Controller{

    /***    创建公司进行初始化设置
     *
     */
    public function  create($business_type,$user_info,$group_code,$group_name,$now_time,$authority_list){

        $capital_data['self_id']        = generate_id('capital_');
        $capital_data['total_user_id']  = null;
        $capital_data['group_code']    =$group_code;
        $capital_data['group_name']    =$group_name;
        $capital_data['update_time']    =$now_time;
        UserCapital::insert($capital_data);						//写入用户资金表

        if($business_type=='SHOP'){
            $where=[
                ['level','=',1],
            ];
            $info=SysAddress::where($where)->select('id','name')->get();

            $data=[];
            foreach ($info as $k=>$v) {
                $freight_data['self_id']            =generate_id('freight_');
                $freight_data['code_id']            =$v->id;
                $freight_data['code_name']          =$v->name;
                $freight_data['postage_flag']       ='Y';
                $freight_data['use_flag']           ='Y';
                $freight_data['freight']            =0;
                $freight_data['free']               =0;
                $freight_data['create_user_id']     =$user_info->admin_id;
                $freight_data["create_user_name"]   =$user_info->name;
                $freight_data['create_time']        =$freight_data['update_time']=$now_time;
                $freight_data['group_code']         =$group_code;
                $freight_data['group_name']         =$group_name;
                $data[]=$freight_data;
            }
            ShopFreight::insert($data);

        }

        if($authority_list){
            $list_infos=[];
            foreach ($authority_list as $k => $v){
                $dgytr["self_id"]                   =generate_id('authority_');
                $dgytr["authority_name"]            =$v->authority_name;
                $dgytr['create_user_id']            =$user_info->admin_id;
                $dgytr['create_user_name']          =$user_info->name;
                $dgytr["create_time"]               =$dgytr["update_time"]=$now_time;
                $dgytr["group_code"]                =$group_code;
                $dgytr["group_name"]                =$group_name;
                $dgytr["menu_id"]                   =$v->menu_id;
                $dgytr["leaf_id"]                   =$v->leaf_id;
                $dgytr["cms_show"]                  =$v->cms_show;
                $dgytr["group_id"]                  =$group_code;
                $dgytr["group_id_show"]             =$group_name;
                $dgytr["system_group_authority_id"] =$v->self_id;
                $list_infos[]=$dgytr;
            }
            SystemAuthority::insert($list_infos);

        }


        if($user_info->group_group_id !== 'all'){
            $where_group_code['group_code']=$user_info->group_code;
            $group_code_ifss=SystemGroup::where($where_group_code)->select('group_id','group_id_show')->first();
            $datass['group_id']         =$group_code_ifss->group_id.'*'.$group_code;
            $datass['group_id_show']    =$group_code_ifss->group_id_show.'　'.$group_name;
            $datass['update_time']      =$now_time;
            SystemGroup::where($where_group_code)->update($datass);
        }

        if($user_info->authority_group_id !== 'all'){
            $system_authority['self_id']=$user_info->authority_id;
            $system_authority_ifss=SystemAuthority::where($system_authority)->select('group_id','group_id_show')->first();
            $data_authority['group_id']         =$system_authority_ifss->group_id.'*'.$group_code;
            $data_authority['group_id_show']    =$system_authority_ifss->group_id_show.'　'.$group_name;
            $data_authority['update_time']      =$now_time;
            SystemAuthority::where($system_authority)->update($data_authority);
        }

    }



}
