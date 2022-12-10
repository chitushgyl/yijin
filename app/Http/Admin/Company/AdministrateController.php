<?php
namespace App\Http\Admin\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\Group\SystemGroup;
use App\Models\SystemMenuNew;
use App\Http\Controllers\DetailsController as Details;

class AdministrateController extends CommonController{
    /***    商户权限头部      /company/administrate/administrateList
     */
    public function  administrateList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    商户权限分页      /company/administrate/administratePage
     */
	public function administratePage(Request $request){
        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数

        /**接收数据地方  */
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_name     =$request->input('group_name');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
	 	$search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];
        $where=get_list_where($search);

        $select=['self_id','group_name','cms_show','group_id_show','use_flag','group_code'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        /** 按钮的出现问题需要解决
         *  第一个，如果是平台方，按钮不出现，不能禁止平台方
         *  第二个，如果集团下面的公司不能操作集团公司
         *  第三个，自己不能禁用自己的公司，也不能删除公司，只能出编辑
         **/



	 	foreach ($data['items'] as $k => $v) {
           if($v->group_code != '1234'){
                $v->button_info=$button_info;
            }
      	}

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
	}

    /***    修改商户权限数据      /company/administrate/createAdministrate
     */
	public function  createAdministrate(Request $request){
        /** 接收中间件参数**/
        $user_info          = $request->get('user_info');//接收中间件产生的参数1

        /**接收数据*/
        $self_id            =$request->input('self_id');
//        $self_id='group_202012251449437824125582';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','leaf_id','menu_id','group_name','group_code','group_id'];

        $company=SystemGroup::where($where)->select($select)->first();

        if($company){
            /** 做菜单数据权限的可选择性开始**/
			if($company->menu_id){
				$data['menu_id']=explode("*",$company->menu_id);
			}else{
				$data['menu_id']=[];
			}

			if($company->leaf_id){
				$data['leaf_id']=explode("*",$company->leaf_id);
			}else{
				$data['leaf_id']=[];
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

//            dump($user_info->menu_id);
            $selectMenu=['id','name','level','sort','node'];


            if($user_info->menu_id=="all"){
                $cms_menu=SystemMenuNew::with(['children' => function($query)use($selectMenu,$whereMenu2) {
                    $query->where($whereMenu2);
                    $query->select($selectMenu);
                    $query->orderBy('sort','asc');
                    $query->with(['children' => function($query)use($selectMenu,$whereMenu2) {
                        $query->where($whereMenu2);
                        $query->select($selectMenu);
                        $query->orderBy('sort','asc');
                    }]);
                }])->where($whereMenu)->select($selectMenu)->orderBy('sort','asc')->get()->toArray();

            }else{
                $arr=explode("*",$user_info->menu_id);

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
                    }]);
                }])->where($whereMenu)->whereIn('id',$arr)->select($selectMenu)->orderBy('sort','asc')->get()->toArray();

            }


            /** 做菜单数据权限的可选择性结束**/


            /** 做数据权限的可选择性开始**/
            $yiyou_group_id=explode("*",$company->group_id);

            $where_temp = [
                ['group_code', '!=', '1234'],
                ['delete_flag', '=', 'Y']
            ];

            if($user_info->group_id=="all"){
                $all_company=SystemGroup::where($where_temp)->select('group_name','group_code')->get();
            }else{
                $all_company=SystemGroup::where($where_temp)->whereIn('group_code',$user_info->group_id)->select('group_name','group_code')->get();
            }


            //dd($all_company);


            /** 做数据权限的可选择性结束**/

            $data['company_info']       =$company;                  //公司信息
            $data['menu_info']          =$cms_menu;                //数据菜单ID集合
            $data['group_info']         =$all_company;              //数据权限ID集合1
			$data['yiyou_group_id']     =$yiyou_group_id;


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            //d($msg);
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

	}

    /***    商户权限数据提交      /company/administrate/addAdministrate
     */
	public function  addAdministrate(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数

        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ="system_group";
        /** 接收中间件参数**/
        //$user_info = $request->get('user_info');//接收中间件产生的参数

        $operationing->access_cause     ='修改权限';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;


		//$input=Input::all();

        /**接收数据*/
        $self_id            =$request->input('self_id');
        $menu_id            =$request->input('menu_id');
		$leaf_id            =$request->input('leaf_id');
        $group_id           =$request->input('group_id');

        /*** 虚拟数据
        $input['self_id']=$self_id='group_202007171125566362359141';
        $input['menu_id']=$menu_id=['190','244'];
        $input['leaf_id']=$leaf_id=['190','244'];
        $input['group_id']=$group_id=['1234','4567'];
         **/

        //dump($menu_id);
        $where_group=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','group_code','group_name','menu_id','leaf_id','cms_show','update_time','group_id','group_id_show'];
        $old_info=SystemGroup::where($where_group)->select($select)->first();

        if($old_info){
            //初始化数据及处理菜单按钮的
            $data['menu_id']         =null;
            $data['leaf_id']         =null;
            $data['cms_show']        =null;
            $data['group_id']        =null;
            $data['group_id_show']   =null;

            if($menu_id){
                $data['menu_id']=join('*',$menu_id);
            }

            if($leaf_id){
                $data['leaf_id']=join('*',$leaf_id);
            }

            $arr4=array_merge((array)$menu_id,(array)$leaf_id);

			$wrht222['level']=1;
            $wrht222['delete_flag']='Y';
            $menu_show=SystemMenuNew::where($wrht222)->whereIn('id',$arr4)->pluck('name')->toArray();
            $data['cms_show']=join('　',$menu_show);

            /*** 以下为处理数据权限的部分**/
            if($group_id){
                $wrht2['delete_flag']='Y';
                $group_id_show=SystemGroup::where($wrht2)->whereIn('group_code',$group_id)->pluck('group_name')->toArray();

                if(!in_array($old_info->group_code,$group_id)){ //本身不在
                    $group_id[]=$old_info->group_code;
                    $group_id_show[]=$old_info->group_name;
                }

            }else{
                $group_id[]=$old_info->group_code;
                $group_id_show[]=$old_info->group_name;
            }

            $data['group_id']        =join('*',$group_id);
            $data['group_id_show']   =join('　',$group_id_show);
            $data['update_time']     =$now_time;
            //dump($dat);
            $id=SystemGroup::where($where_group)->update($data);

            $operationing->table_id=$self_id;
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
            $msg['code']=300;
            $msg['msg']='公司不存在';
            return $msg;
        }
	}

	    /***    详情     /company/administrate/details
     */
    public function details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='system_group';
        $select=['self_id','group_code','group_name','authority_name','cms_show',
        'group_id_show','create_user_id','create_user_name'];
        $info=$details->details($self_id,$table_name,$select);
	//dd($self_id);
        if($info){
            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/

            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);
            }
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }


}
?>
