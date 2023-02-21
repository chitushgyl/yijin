<?php
namespace App\Http\Admin\Staff;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Models\Group\SystemAuthority;
use App\Models\SystemMenuNew;
use App\Models\Group\SystemGroup;
use App\Http\Controllers\DetailsController as Details;

class AuthorityController extends CommonController{
    /***    用户权限头部      /staff/authority/authorityList
     */
    public function  authorityList(Request $request){
        //引入配置文件
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    用户权限分页      /staff/authority/authorityPage
     */
    public function authorityPage(Request $request){
        /** 接收中间件参数**/
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??100;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $authority_id   =$request->input('authority_id');
		$authority_name =$request->input('authority_name');
		$group_name =$request->input('group_name');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'authority_name','value'=>$authority_name],
            ['type'=>'all','name'=>'self_id','value'=>$authority_id],
			['type'=>'=','name'=>'group_name','value'=>$group_name],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];
        $where=get_list_where($search);

        $select=['self_id','authority_name','cms_show','group_id_show','group_code','group_name','use_flag','create_time','lock_flag'];

        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemAuthority::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=SystemAuthority::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemAuthority::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=SystemAuthority::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemAuthority::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemAuthority::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                $data['group_show']='Y';
                break;
        }

//dd($user_info);
        foreach ($data['items'] as $k=>$v) {
			if($v->self_id != '10'){
				if($user_info->authority_id == '10'){
					$v->button_info=$button_info;
				}else{
					if($v->lock_flag == 'N'){
						$v->button_info=$button_info;
					}
				}
            }


        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;
    }

    /***    创建权限      /staff/authority/createAuthority
     */
    public function createAuthority(Request $request){
        /**接收数据*/
        $self_id                =$request->input('self_id');
        $group_code             =$request->input('group_code');
		/**接收数据**/
        //$self_id='authority_202012251456116592603238';
//        $group_code='1234';

        /**如果是修改，则出这个数据出来**/
        if($self_id){
            $where_authority['self_id']=$self_id;
            //dump($where_authority);

            $select_authority=['self_id','authority_name','group_name','group_code','menu_id','leaf_id','group_id'];
            $authority=SystemAuthority::where($where_authority)->select($select_authority)->first();

            //dump($authority);

            if($authority){
                $group_code=$authority->group_code;

                if($authority->menu_id){
                    $data['menu_id']=explode("*",$authority->menu_id);
                }else{
                    $data['menu_id']=[];
                }

                if($authority->leaf_id){
                    $data['leaf_id']=explode("*",$authority->leaf_id);
                }else{
                    $data['leaf_id']=[];
                }


                $yiyou_group_id=explode("*",$authority->group_id);
            }else{
                $msg['code']=301;
                $msg['msg']="没有查询到这个权限信息";
                return $msg;
            }
        }else{
            //应该是新建
            $authority=null;
            $data['menu_id']=[];
            $data['leaf_id']=[];
            $yiyou_group_id=[$group_code];
        }




//dump($self_id);
        $where['group_code']=$group_code;
        $company=SystemGroup::where($where)->select('menu_id','self_id','group_name','group_code','group_id')->first();

        if($company){
            $datt["delete_flag"]='Y';
            $datt["admin_flag"]='N';


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

            if($company->menu_id=="all"){



                //$cms_menu=SystemMenuNew::where($datt)->select('name','id','level','sort','node')->get()->toArray();

                $cms_menu=SystemMenuNew::with(['children' => function($query)use($selectMenu,$whereMenu2) {
                    $query->where($whereMenu2);
                    $query->select($selectMenu);
                    $query->orderBy('sort','asc');
                    $query->with(['children' => function($query)use($selectMenu,$whereMenu2) {
                        $query->where($whereMenu2);
                        $query->select($selectMenu);
                        $query->orderBy('sort','asc');
                        $query->with(['children' => function($query)use($selectMenu,$whereMenu2) {
                            $query->where($whereMenu2);
                            $query->select($selectMenu);
                            $query->orderBy('sort','asc');
                        }]);
                    }]);
                }])->where($whereMenu)->select($selectMenu)->orderBy('sort','asc')->get()->toArray();

            }else{

                $arr=explode("*",$company->menu_id);
                //$cms_menu=SystemMenuNew::where($datt)->whereIn('id',$arr)->select('name','id','level','sort','node')->get()->toArray();


                $cms_menu=SystemMenuNew::with(['children' => function($query)use($selectMenu,$whereMenu2,$arr) {
                    $query->where($whereMenu2);
                    $query->whereIn('id',$arr);
                    $query->select($selectMenu);
                    $query->orderBy('sort','asc');
                    $query->with(['children' => function($query)use($selectMenu,$whereMenu2,$arr) {
                        $query->where($whereMenu2);
                        $query->whereIn('id',$arr);
                        $query->select($selectMenu);
                        $query->orderBy('sort','asc');
                        $query->with(['children' => function($query)use($selectMenu,$whereMenu2,$arr) {
                            $query->where($whereMenu2);
                            $query->whereIn('id',$arr);
                            $query->select($selectMenu);
                            $query->orderBy('sort','asc');
                        }]);
                    }]);
                }])->where($whereMenu)->whereIn('id',$arr)->select($selectMenu)->orderBy('sort','asc')->get()->toArray();



            }



            /***菜单制作结束**/

            //做一组数据的权限      //抓取可选择的权限
            $where_temp = [
                ['group_code', '!=', '1234'],
                ['delete_flag', '=', 'Y']
            ];
            if($company->group_id=="all"){
                $all_company=SystemGroup::where($where_temp)->select('group_name','group_code')->get()->toArray();
            }else{
                $arr=explode("*",$company->group_id);
                $all_company=SystemGroup::where($where_temp)->whereIn('group_code',$arr)->select('group_name','group_code')->get()->toArray();
            }

        }else{
            $msg['code']=301;
            $msg['msg']="没有传递必要的公司参数";
            return $msg;
        }

		$data['authority']=$authority;              	//数据菜单ID集合
        $data['menu_info']=$cms_menu;              	//数据菜单ID集合
        $data['group_info']=$all_company;           	//数据权限ID集合
		$data['yiyou_group_id']=$yiyou_group_id;        //数据权限ID集合



        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    权限数据提交      /staff/authority/addAuthority
     */

    public function  addAuthority(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='system_authority';
        $now_time=date('Y-m-d H:i:s',time());

        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();

        $operationing->access_cause='新建及修改权限';
        $operationing->table=$table_name;
        $operationing->operation_type='update';
        $operationing->now_time=$now_time;

        /** 接收数据*/
        $group_code=$request->input('group_code');
        $authority_name=$request->input('authority_name');

        $self_id=$request->input('self_id');
        $menu_id=$request->input('menu_id');
		$leaf_id=$request->input('leaf_id');
        $group_id=$request->input('group_id');

        //$authority_name=$input['authority_name']='管理员';
        //$group_code=$input['group_code']='1234';

        $rules=[
            'group_code'=>'required',
            'authority_name'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
            'authority_name.required'=>'权限名称不能为空',
        ];

        $validator=Validator::make($input,$rules,$message);
        //操作的表

        if($validator->passes()){


            /** 首先效验一下这个能不能进库**/
            if($self_id){
                $name_where=[
                    ['authority_name','=',trim($authority_name)],
                    ['self_id','!=',$self_id],
                    ['group_code','=',$group_code],
                ];
            }else{
                $name_where=[
                    ['authority_name','=',trim($authority_name)],
                    ['group_code','=',$group_code],
                ];
            }

            $authority_count = SystemAuthority::where($name_where)->count();            //检查名字是不是重复

            if($authority_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '权限名称重复！';
                return $msg;
            }

            /** 现在开始可以做数据了**/
            $dat['menu_id']=null;
            $dat['leaf_id']=null;
            $dat['cms_show']=null;
            if($menu_id){
                $dat['menu_id']=join('*',$menu_id);
            }

            if($leaf_id){
                $dat['leaf_id']=join('*',$leaf_id);
            }

			$arr4=array_merge((array)$menu_id,(array)$leaf_id);
			$wrht222['level']=1;
			$wrht222['delete_flag']='Y';
			$menu_show=SystemMenuNew::where($wrht222)->whereIn('id',$arr4)
				->pluck('name')->toArray();
			$dat['cms_show']=join('　',$menu_show);


            $dat['group_id']=null;
            $dat['group_id_show']=null;
            //dd($dat);
            //处理下本身是不是在里面
            $where_group['self_id']=$group_code;
            $group_name=SystemGroup::where($where_group)->value('group_name');

            if($group_id){
                $wrht2['delete_flag']='Y';
                $group_id_show=SystemGroup::where($wrht2)->whereIn('group_code',$group_id)
                    ->pluck('group_name')->toArray();

                if(!in_array($group_code,$group_id)){ //本身不在
                    $group_id[]=$group_code;
                    $group_id_show[]=$group_name;
                }

            }else{
                $group_id[]=$group_code;
                $group_id_show[]=$group_name;
            }

            $dat['group_id']=join('*',$group_id);
            $dat['group_id_show']=join('　',$group_id_show);

            $where_authority['self_id']=$self_id;
            $select=['self_id','group_code','group_name','authority_name','menu_id','cms_show','group_name','update_time'];
            $old_info=SystemAuthority::where($where_authority)->select($select)->first();

            if($old_info){
                //说明是修改权限


                $dat['update_time']=$now_time;

                $id=SystemAuthority::where($where_authority)->update($dat);
				$operationing->access_cause='修改权限';
				$operationing->operation_type='update';

            }else{
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
                $dat["authority_name"]      =$authority_name;
                $dat['create_user_id']      =$user_info->admin_id;
                $dat['create_user_name']    =$user_info->name;
                $dat["create_time"]         =$dat["update_time"]=$now_time;
                $dat["group_code"]          =$group_code;
                $dat["group_name"]          =$group_name;

                $id=SystemAuthority::insert($dat);
				$operationing->access_cause='新增权限';
				$operationing->operation_type='create';

            }

            $operationing->table_id=$old_info?$self_id:$dat["self_id"];
            $operationing->old_info=$old_info;
            $operationing->new_info=$dat;

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
            //错误的处理
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

    /***    权限的启禁用      /staff/authority/authorityUseFlag
     */
    public function authorityUseFlag(Request $request,Status $status){
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='system_authority';
        $medol_name     ='SystemAuthority';
        $self_id        =$request->input('self_id');
        $flag           ='useFlag';
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



    /***    获取权限     /staff/authority/getAuthority
     */
    public function getAuthority(Request $request){
        $group_code        =$request->input('group_code');
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $where['delete_flag']='Y';
        $select=['self_id','authority_name','group_code','group_name'];


        $where['delete_flag']='Y';
        $select=['self_id','authority_name','group_code','group_name'];
        if($group_code){
            $where[]=['group_code','=',$group_code];
            $data['items']=SystemAuthority::where($where)->select($select)->orderBy('create_time', 'desc')->get();

        }else {
            switch ($group_info['group_id']){
                case 'all':
                    $data['info']=SystemAuthority::where($where)->orderBy('create_time', 'desc')->select($select)->get();
                    break;

                case 'one':
                    $where[]=['group_code','=',$group_info['group_code']];
                    $data['info']=SystemAuthority::where($where)->orderBy('create_time', 'desc')->select($select)->get();
                    break;

                case 'more':
                    $data['info']=SystemAuthority::where($where)->whereIn('group_code',$group_info['group_code'])->orderBy('create_time', 'desc')->select($select)->get();
                    break;
            }

        }

        $msg['code']=200;
        $msg['msg']='拉取数据成功';
        $msg['data']=$data;

        return $msg;

    }


}
?>
