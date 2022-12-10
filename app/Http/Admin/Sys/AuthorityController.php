<?php
namespace App\Http\Admin\Sys;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroupAuthority;
use App\Http\Controllers\StatusController as Status;
use App\Models\SystemMenuNew;

class AuthorityController  extends CommonController{

    /***    默认权限     /sys/authority/authorityList
     */
    public function  authorityList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    默认权限分页      /sys/authority/authorityPage
     */
    public function authorityPage(Request $request){
        $button_info = $request->get('anniu');//接收中间件产生的参数
        $business_type  =config('page.business_type');
        $business_type  =array_column($business_type,'name','key');

        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],

        ];

        $where=get_list_where($search);
        $select=['self_id','create_user_name','create_time','authority_name','cms_show','type','business_type','use_flag'];

        $data['total']=SystemGroupAuthority::where($where)->count(); //总的数据量
        $data['items']=SystemGroupAuthority::where($where)
            ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
            ->select($select)->get();


        foreach($data['items'] as $k => $v){
            switch ($v->type){
                case 'system':
                    $v->type='公司权限';
                    break;
                default:
                    $v->type='职务权限';
                    break;
            }
            $v->business_type_show=$business_type[$v->business_type]??null;

            $v->button_info=$button_info;

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    创建默认权限      /sys/authority/createAuthority
     */
    public function createAuthority(Request $request){
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        //$self_id='authority_202012291339142725889806';
        $data['business_type']  =config('page.business_type');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select=['self_id','authority_name','business_type','group_code','menu_id','leaf_id','cms_show','type','business_type'];

        $data['info']=SystemGroupAuthority::where($where)->select($select)->first();
		//dd($data['info']);
		if(!empty($data['info']->leaf_id)){
			$data['leaf_id']=explode("*",$data['info']->leaf_id);
		}

        $whereMenu = [
            ['admin_flag', '=', 'N'],
            ['use_flag', '=', 'Y'],
            ['level', '=', '1'],
            ['delete_flag', '=', 'Y']
        ];
        $whereMenu2 = [
            ['admin_flag', '=', 'N'],
            ['use_flag', '=', 'Y'],
            ['delete_flag', '=', 'Y']
        ];
        $selectMenu=['id','name','level','sort','node'];
        $data['cms_menu']=SystemMenuNew::with(['children' => function($query)use($selectMenu,$whereMenu2) {
            $query->where($whereMenu2);
            $query->select($selectMenu);
            $query->orderBy('sort','asc');
            $query->with(['children' => function($query)use($selectMenu,$whereMenu2) {
                $query->where($whereMenu2);
                $query->select($selectMenu);
                $query->orderBy('sort','asc');
            }]);
        }])->where($whereMenu)->select($selectMenu)->orderBy('sort','asc')->get()->toArray();


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;

    }

    /***    创建默认权限入库      /sys/authority/addAuthority
     */
    public function addAuthority(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='system_group_authority';

        $operationing->access_cause='新建/修改系统权限';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $type               =$request->input('type');
        $menu_id            =$request->input('menu_id');
        $leaf_id            =$request->input('leaf_id');
        $business_type      =$request->input('business_type');
        $authority_name     =$request->input('authority_name');

        /*** 虚拟数据
        //$input['self_id']=$self_id='group_202007171125566362359141';
        $input['type']=$type='authority';
        $input['menu_id']=$menu_id=['620','244'];
        $input['leaf_id']=$leaf_id=['147','148'];
        $input['business_type']=$business_type='SHOP';
        $input['authority_name']=$authority_name='管理员';
			**/
        $rules=[
            'type'=>'required',
            'business_type'=>'required',
        ];
        $message=[
            'type.required'=>'请选择类型',
            'business_type.required'=>'请选择业务类型',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            $data['authority_name']         =$authority_name;
            $data['business_type']          =$business_type;
            if($menu_id){
                $data['menu_id']=join('*',$menu_id);
            }

            if($leaf_id){
                $data['leaf_id']=join('*',$leaf_id);
            }
            $data['type']                   =$type;
            $arr4=array_merge((array)$menu_id,(array)$leaf_id);
            $wrht222['level']=1;
            $wrht222['delete_flag']='Y';
            $menu_show=SystemMenuNew::where($wrht222)->whereIn('id',$arr4)
                ->pluck('name')->toArray();
            $data['cms_show']=join('　',$menu_show);


            /** 第一步，效验**/
            switch ($type){
                case 'system':
                    $where=[
                        ['delete_flag','=','Y'],
                        ['business_type','=',$business_type],
                        ['self_id','=',$self_id],
                    ];
                    break;


                case 'authority':
                    $where=[
                        ['delete_flag','=','Y'],
                        ['self_id','=',$self_id],
                    ];
                    break;
            }


            $select=['self_id','use_flag','type','authority_name','menu_id','leaf_id','update_time'];
            $old_info=SystemGroupAuthority::where($where)->select($select)->first();
            if($old_info){
                //说明是修改权限
                $data['update_time']=$now_time;
                $id=SystemGroupAuthority::where($where)->update($data);
                $operationing->access_cause='修改权限';
                $operationing->operation_type='update';

            }else{
                $data["self_id"]             =generate_id('authority_');
                $data['create_user_id']      =$user_info->admin_id;
                $data['create_user_name']    =$user_info->name;
                $data["create_time"]         =$data["update_time"]=$now_time;
                $data["group_code"]          =$user_info->group_code;
                $data["group_name"]          =$user_info->group_name;
                $id=SystemGroupAuthority::insert($data);
                $operationing->access_cause='新增权限';
                $operationing->operation_type='create';

            }

            $operationing->table_id=$old_info?$self_id:$data["self_id"];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功';
                return $msg;
            }else{
                $msg['code']=302;
                $msg['msg']='操作失败';
                return $msg;
            }


        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.":".$v."\r\n";
            }
            //dd($msg);
            return $msg;
        }
    }



    /***    权限启禁用      /sys/authority/authorityUseFlag
     */

    public function authorityUseFlag(Request $request,Status $status){
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='system_group_authority';
        $medol_name     ='SystemGroupAuthority';
        $self_id        =$request->input('self_id');
        $flag='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='启用/禁用';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }

    /***    权限删除      /sys/authority/authorityDelFlag
     */

    public function authorityDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='system_group_authority';
        $medol_name='SystemGroupAuthority';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;

    }




}
?>
