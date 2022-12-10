<?php
namespace App\Http\Admin\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Models\Group\SystemGroupApply;
use App\Models\Group\SystemGroupApplyDetails;

class ApplyController extends CommonController{
    /***    公司申请头部      /company/apply/applyList
     */
    public function  applyList(Request $request){
        $data['page_info']          =config('page.listrows');
        $data['button_info']        =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;

    }

    /***    公司申请分页      /company/apply/applyPage
     */

    public function applyPage(Request $request){
        /** 读取配置文件信息**/

        $business_type  =array_column(config('page.business_type'),'name','key');


//        DUMP($business_type);
        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
//dump($button_info);

        //拿取数据权限


        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');

        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];

        $where=get_list_where($search);
        $select=['self_id','total_user_id','use_flag','group_name','create_time'];

        $select_reg=['total_user_id','tel','token_name'];

        $select_apply=['apply_id','create_time','business_type','name','leader_phone','default_login','auditor_cause'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroupApply::where($where)->count(); //总的数据量
                $data['items']=SystemGroupApply::with(['systemGroupApplyDetails' => function($query)use($select_apply) {
                    $query->select($select_apply);
                    $query->orderBy('create_time','desc');
                }])->with(['userReg' => function($query)use($select_reg) {
                    $query->select($select_reg);
//                    $query->orderBy('create_time','desc');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroupApply::where($where)->count(); //总的数据量
                $data['items']=SystemGroupApply::with(['systemGroupApplyDetails' => function($query)use($select_apply) {
                    $query->select($select_apply);
                    $query->orderBy('create_time','desc');
                }])->with(['userReg' => function($query)use($select_reg) {
                    $query->select($select_reg);
//                    $query->orderBy('create_time','desc');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemGroupApply::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroupApply::with(['systemGroupApplyDetails' => function($query)use($select_apply) {
                    $query->select($select_apply);
                    $query->orderBy('create_time','desc');
                }])->with(['userReg' => function($query)use($select_reg) {
                    $query->select($select_reg);
//                    $query->orderBy('create_time','desc');
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {

                $v->business_type   =$business_type[$v->systemGroupApplyDetails->business_type]??null;
                if($v->use_flag =='W' ){
                    $v->button_info=$button_info;
                }else{
                    $v->button_info=null;
                }

            }

//        dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    审核商户的信息      /company/apply/createApply
     */
    public function createApply(Request $request){
        $apply_id            =$request->input('apply_id');

//        $input=$request->all();

        /*** 虚拟数据*/
        $apply_id='group_202010161117337333533537';

        $where['apply_id']=$apply_id;
        //$where['use_flag']='W';
        $apply_info=SystemGroupApplyDetails::where($where)->first();

//        dump($apply_info->toArray());


        if($apply_info){
            $business_type  =array_column(config('page.business_type'),'name','key');
            $apply_info->business_type_show      =$business_type[$apply_info->business_type];

//            DD($apply_info->toArray());

            if($apply_info->use_flag== 'W'){
                $msg['code']=200;
                $msg['data']=$apply_info;
                $msg['msg']='获取数据成功';
                return $msg;
            }else{
                $msg['code']=302;
                $msg['data']=$apply_info;
                $msg['msg']='该申请已经操作完成';
                return $msg;
            }

        }else{
            $msg['code']=301;
            $msg['msg']='没有查询到信息';
            return $msg;
        }

    }

    /***    审核商户的操作      /company/apply/addApply
     */
    public function addApply(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());

        /** 接收中间件参数**/
        $user_info          = $request->get('user_info');//接收中间件产生的参数

        $table_name         ='system_group_apply';
        $operationing->access_cause     ='审核商户';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;


        /**接收数据*/
        $self_id            =$request->input('self_id');
        $type               =$request->input('type')??'Y';
        $auditor_cause      =$request->input('auditor_cause');


        //$self_id='group_202005201344154541223633';

        $where['apply_id']=$self_id;
        $where['use_flag']='W';
        $old_info=DB::table('system_group_apply_details')->where($where)->first();

        if($old_info){

            $data['auditor_id']     =$user_info->admin_id;
            $data['auditor_name']   =$user_info->name;
            $data['auditor_time']   =$now_time;
            $data['auditor_cause']  =$auditor_cause;

            if($type == 'Y'){
                //判断登录账户是否已存在
                $where_admin['login']=$old_info->default_login;
                $where_admin['delete_flag']='Y';
                $count1=DB::table('system_admin')->where($where_admin)->count();
                if($count1>0){
                    $msg['code'] = 302;
                    $msg['msg'] = "该用户名已存在";
                    return $msg;
                }

                $where_system_group['group_code']=$old_info->group_code;
                $where_system_group['delete_flag']='Y';
                $count2=DB::table('system_group')->where($where_system_group)->count();
                if($count2>0){
                    $msg['code'] = 303;
                    $msg['msg'] = "该公司已存在";
                    return $msg;
                }

                DB::beginTransaction();
                $data['use_flag']='Y';

                //审核通过,增加公司表、账号表、权限表数据
                $apply_shop=config('page.business_type');
                $system_group['menu_id']=$apply_shop[$old_info->business_type]['menu_id'];
                $system_group['cms_show']=$apply_shop[$old_info->business_type]['cms_show'];
                $system_group['self_id']=$system_group['group_code']=$old_info->apply_id;
                $system_group['group_name']=$old_info->group_name;
                $system_group['name']=$old_info->name;
                $system_group['leader_phone']=$old_info->leader_phone;
                $system_group['leader_phone']=$old_info->leader_phone;
                $system_group['group_id']=$old_info->apply_id;
                $system_group['group_id_show']=$old_info->group_name;
                $system_group['business_type']=$old_info->business_type;
                $system_group['create_time']=$system_group['update_time']=$now_time;
                $system_group['create_user_id']=$user_info->admin_id;
                $system_group['create_user_name']=$user_info->name;
                $sq1=DB::table('system_group')->insert($system_group);

                //权限表
                $system_authority['self_id']=generate_id('authority_');
                $system_authority['authority_name']=$old_info->group_name.'管理员';
                $system_authority['group_id']=$old_info->group_code;
                $system_authority['group_id_show']=$old_info->group_name;
                $system_authority['create_user_id']=$user_info->admin_id;
                $system_authority['create_user_name']=$user_info->name;
                $system_authority['create_time']=$system_authority['update_time']=$now_time;
                $system_authority['group_code']=$old_info->group_code;
                $system_authority['group_name']=$old_info->group_name;
                $system_authority['cms_type']='Y';
                $sq2=DB::table('system_authority')->insert($system_authority);

                //账号表
                $system_admin['self_id']=generate_id('admin_');
                $system_admin['login']=$old_info->default_login;
                $system_admin['pwd']=get_md5(123456);
                $system_admin['name']=$old_info->name;
                $system_admin['tel']=$old_info->leader_phone;
                $system_admin['create_user_id']=$user_info->admin_id;
                $system_admin['create_user_name']=$user_info->name;
                $system_admin['create_time']=$system_admin['update_time']=$now_time;
                $system_admin['authority_id']=$system_authority['self_id'];
                $system_admin['authority_name']=$system_authority['authority_name'];
                $system_admin['group_code']=$old_info->group_code;
                $system_admin['group_name']=$old_info->group_name;
                $sq3=DB::table('system_admin')->insert($system_admin);

                if($sq1&&$sq2&&$sq3){
                    DB::commit();

                }else{
                    DB::rollback();
                    $msg['code'] = 305;
                    $msg['msg'] = "创建公司失败";
                    return $msg;
                }


            }else{
                $data['use_flag']='X';
            }

            $data['update_time']=$now_time;
            $id=DB::table('system_group_apply_details')->where($where)->update($data);

            $where_apply['self_id']=$self_id;

            $data_apply['use_flag']=$data['use_flag'];
            $data_apply['update_time']=$now_time;

            $operationing->table_id=$self_id;
            $operationing->old_info=$old_info;
            $operationing->new_info=$data_apply;

            if($id){
                DB::table($table_name)->where($where_apply)->update($data_apply);
                $msg['code']=200;
                $msg['msg']='操作成功';
                return $msg;
            }else{
                $msg['code']=304;
                $msg['msg']='操作失败';
                return $msg;
            }
        }else{
            $msg['code']=301;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }

}
?>
