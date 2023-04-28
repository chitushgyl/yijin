<?php
namespace App\Http\Admin\Admin;
use App\Models\Group\SystemUser;use App\Models\Tms\TmsCar;use App\Models\Tms\TmsLine;
use App\Models\Tms\TmsMessage;use App\Models\Tms\TmsOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\CommonController;
use App\Models\SystemMenuNew;
use App\Models\Group\SystemAdmin;

class IndexController extends CommonController{

    /***    首页接口，拿取菜单的地方      /admin/index
     *
     */
    public function index(Request $request){
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $group_info     = $request->get('group_info');
//        dump($group_info['group_code']);
        $where_menu=[
            ['delete_flag','=','Y'],
            ['level','=','1'],
        ];
		if($user_info->authority_id != '10'){
			$where_menu['admin_flag']='N';
		}
        $where_children=[
            ['delete_flag','=','Y'],
        ];
        if($user_info->authority_id != '10'){
            $where_children['admin_flag']='N';
        }
        $select=['id','level','name','an_name','img','node','sort','url'];

        if($user_info->menu_id== 'all'){
            $menu_info=SystemMenuNew::with(['children' => function($query)use($where_children,$select) {
                $query->where($where_children);
                $query->select($select);
                $query->orderBy('sort','asc');
                $query->with(['children' => function($query)use($where_children,$select) {
                    $query->where($where_children);
                    $query->where('type','CMS');
                    $query->select($select);
                    $query->orderBy('sort','asc');
                }]);
            }])->where($where_menu)->select($select)->orderBy('sort','asc')->get();
            foreach ($menu_info as $key => $value){
                if ($value->an_name){
                    $value->name = $value->an_name;
                }
                foreach ($value->children as $k => $v){
                    if ($v->an_name){
                        $v->name = $v->an_name;
                    }
                }
            }
        }else{
            $menu_id=explode('*', $user_info->menu_id);
            $menu_info=SystemMenuNew::with(['children' => function($query)use($where_children,$select,$menu_id) {
                $query->where($where_children);
                $query->select($select);
                $query->orderBy('sort','asc');
                $query->whereIn('id',$menu_id);
                $query->with(['children' => function($query)use($where_children,$select,$menu_id) {
                    $query->where($where_children);
                    $query->where('type','CMS');
                    $query->select($select);
                    $query->orderBy('sort','asc');
                    $query->whereIn('id',$menu_id);
            }]);
            }])->where($where_menu)->select($select)->whereIn('id',$menu_id)->orderBy('sort','asc')->get();
        }
        /** 做一个 还有多少有效期的事情**/
        $now_time   =date('Y-m-d H:i:s',time());
        $startdate  =strtotime($now_time);
        $enddate    =strtotime($user_info->expire_time);
        $days       =intval(round(($enddate-$startdate)/3600/24)) ;                 //还有多少天数到期

        $msg['code']=200;
        $msg['msg']="拉取数据成功";
        $msg['menu_info']=$menu_info;
        $msg['days']=$days;                         //后台使用到期时间
        $msg['user_info']=$user_info;
        return $msg;

    }

    /***    修改密码                /admin/changePwd
     */

    public function changePwd(Request $request){
		/** 接收数据*/
        $operationing   = $request->get('operationing');//接收中间件产生的参数

        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='system_admin';

        $operationing->access_cause     ='修改自己密码';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;


        /** 接收数据*/
        $old_pwd            =$request->input('old_pwd');
        $pwd                =$request->input('pwd');
        $pwd_confirmation   =$request->input('pwd_confirmation');

        $input=$request->all();

        /**  虚拟数据
        $input['old_pwd']           =$old_pwd           ='123456789';
        $input['pwd']               =$pwd               ='123456789';
        $input['pwd_confirmation']  =$pwd_confirmation  ='123456789';
        **/
       // dump($user_info);

        $rules=[
            'old_pwd'           =>'required',
            'pwd'               =>'required|confirmed',
            'pwd_confirmation'  =>'required',
        ];
        $message=[
            'old_pwd.required'          =>'原密码输入不能为空',
            'pwd.required'              =>'新密码输入不能为空',
            'pwd_confirmation.required' =>'重复密码输入不能为空',
            'pwd.confirmed'             =>'新密码和重复密码不一致',
        ];
        $validator=Validator::make($input,$rules,$message);
        //dump($input);


        if($validator->passes()){
            $where=[
                ['login','=',$user_info->login],
            ];
            $select=['login','self_id','group_code','group_name','pwd','update_time'];
            $old_info=SystemAdmin::where($where)->select($select)->first();

            $data['pwd']            =get_md5($pwd);
            $data['update_time']    =$now_time;

           // dump($old_info);


            $operationing->table_id     =$old_info->self_id;
            $operationing->old_info     =$old_info;
            $operationing->new_info     =$data;
            //dd($operationing);
            if($old_info->pwd == get_md5($old_pwd)){

                $id=SystemAdmin::where($where)->update($data);
                if($id){
                    $msg['code']=200;
                    $msg['msg']='修改成功';
                    return $msg;
                }else{
                    $msg['code']=303;
                    $msg['msg']='修改失败';
                    return $msg;
                }

            }else{
                //返回账号和密码不符合
                $msg['code']=301;
                $msg['msg']='您输入的老密码不正确，请您重新输入';
                return $msg;
            }

        }else{
            //前端用户验证没有通过，一般是新密码没有输入或者二次密码不对
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

     /**
     * 查询即将到期的证件
     * */
    public function getExpireCert(Request $request)
    {
        $group_code = $request->input('group_code');
        $now_time = date('Y-m-d H:i:s', time());
        $select = ['self_id', 'car_number', 'medallion_change', 'license_date', 'tank_validity', 'inspect_annually', 'sgs_date', 'compulsory_end',
            'commercial_end', 'carrier_end', 'group_code', 'group_name'];
        $where = [
            ['delete_flag', '=', 'Y'],
            ['use_flag', '=', 'Y'],
            ['group_code', '=', $group_code],
        ];
        $car_list = TmsCar::where($where)->orderBy('self_id', 'desc')->select($select)->get();
        $user_list = SystemUser::where($where)->orderBy('self_id', 'desc')->get();
        $message_list = [];
        foreach ($car_list as $k => $v) {
            if ($v->medallion_change) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->medallion_change) . ' -1 month'))) {
                    $medallion['connect'] = '运输证即将到期';
                    $medallion['car_number'] = $v->car_number;
                    $medallion['exprie_time'] = $v->medallion_change;
                    $message_list[] = $medallion;
                }
            }
            if ($v->tank_validity) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->tank_validity) . ' -1 month'))) {
                    $tank['connect'] = '罐检即将到期';
                    $tank['car_number'] = $v->car_number;
                    $tank['exprie_time'] = $v->tank_validity;
                    $message_list[] = $tank;

                }
            }
            if ($v->license_date) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->license_date) . ' -1 month'))) {
                    $license['connect'] = '行驶证即将到期';
                    $license['car_number'] = $v->car_number;
                    $license['exprie_time'] = $v->license_date;
                    $message_list[] = $license;

                }
            }
            if ($v->sgs_date) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->sgs_date) . ' -1 month'))) {
                    $sgs['connect'] = 'SGS证即将到期';
                    $sgs['car_number'] = $v->car_number;
                    $sgs['exprie_time'] = $v->sgs_date;
                    $message_list[] = $sgs;

                }
            }
            if ($v->inspect_annually) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->inspect_annually) . ' -3 month'))) {
                    $inspect_annually['connect'] = '年检即将到期';
                    $inspect_annually['car_number'] = $v->car_number;
                    $inspect_annually['exprie_time'] = $v->inspect_annually;
                    $message_list[] = $inspect_annually;

                }
            }
            if ($v->compulsory_end) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->compulsory_end) . ' -1 month'))) {
                    $compulsory['connect'] = '交强险即将到期';
                    $compulsory['car_number'] = $v->car_number;
                    $compulsory['exprie_time'] = $v->compulsory_end;
                    $message_list[] = $compulsory;

                }
            }
            if ($v->carrier_end) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->carrier_end) . ' -1 month'))) {
                    $carrier_end['connect'] = '承运险即将到期';
                    $carrier_end['car_number'] = $v->car_number;
                    $carrier_end['exprie_time'] = $v->carrier_end;
                    $message_list[] = $carrier_end;

                }
            }
            if ($v->commercial_end) {
                if ($now_time >= date('Y-m-d', strtotime(date($v->commercial_end) . ' -1 month'))) {
                    $commercial_end['connect'] = '商业险即将到期';
                    $commercial_end['car_number'] = $v->car_number;
                    $commercial_end['exprie_time'] = $v->commercial_end;
                    $message_list[] = $commercial_end;

                }
            }

        }
        $msg['code'] = '200';
        $msg['msg']  = '获取成功！';
        $msg['data'] = $message_list;
        return $msg;
    }


}
?>
