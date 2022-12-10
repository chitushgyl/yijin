<?php
namespace App\Http\Controllers\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class TaskController  extends CommonController{

    public function  task_list(Request $request){
        //引入配置文件
        $page_num=config('page.page_option');

        return view('User.Task.task_list',['page_num'=>$page_num]);
    }

	public function task_page(Request $request){
		$input=Input::all();

        $listrows=$input['num'];
        $firstrow=($input['page']-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);

        $count=DB::table('user_task')->where($where)->count();//总的数据量
        $page['page']=$input['page'];
        $page['num']=$input['num'];
        $page['total_page']=intval(ceil($count/$input['num']));
        $page['total_count']=$count;
        $page=page_show($page);
//        print_r($count);

		//查询数据
        $info=DB::table('user_task')->where($where)
            ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')->get()->toArray();

//        dd($info);
        $anniu_qiao=$request->get('anniu');

//        dd($info);

		 return view('User.Task.task_page',['page'=>$page,'info'=>$info]);

	}

    /**拿取用户的信息，看看要不要合并**/
    public function do_audit(Request $request){
        $where['self_id']=$request->input('idd');

        dd(1111);
        //dd($request->all());
        $data['use_flag']='N';
        $data['delete_flag']='N';
        $data['update_time']=date('Y-m-d H:i:s',time());

        $info=DB::table('system_group')->where($where)->select('delete_flag','update_time','group_code','group_name')->first();
        $id=DB::table('system_group')->where($where)->update($data);

        if($id){
            //做日志文件

            $operationing['access_cause']='删除商户：'.$info->group_name;
            $operationing['browse_type']=$request->path();
            $operationing['table']='system_group';
            $operationing['table_id']=$request->input('idd');
            $operationing['new_info']=(object)$data;
            $operationing['old_info']=$info;
            $operationing['group_code']=$info->group_code;
            $operationing['group_name']=$info->group_name;
            $operationing['roll_back_flag']='N';
            $operationing['operation_type']='delete';
            $operationing['admin_flag']='N';
            $operationing['ip']=$request->getClientIp();
            $operationing['log_status']='SU';
            $operationing['false_cause']=null;

            operationing($operationing);
            return response()->json(['st'=> true,'msg'=>'删除成功']);
        }else{
            return response()->json(['st'=> false,'msg'=>'删除失败']);
        }

    }

    /**合并的操作数据结果**/
    public function do_audit111(Request $request){
        dd(1111);
        //dd($request->all());
        $data['use_flag']='N';
        $data['delete_flag']='N';
        $data['update_time']=date('Y-m-d H:i:s',time());
        $where['self_id']=$request->input('idd');
        $info=DB::table('system_group')->where($where)->select('delete_flag','update_time','group_code','group_name')->first();
        $id=DB::table('system_group')->where($where)->update($data);

        if($id){
            //做日志文件

            $operationing['access_cause']='删除商户：'.$info->group_name;
            $operationing['browse_type']=$request->path();
            $operationing['table']='system_group';
            $operationing['table_id']=$request->input('idd');
            $operationing['new_info']=(object)$data;
            $operationing['old_info']=$info;
            $operationing['group_code']=$info->group_code;
            $operationing['group_name']=$info->group_name;
            $operationing['roll_back_flag']='N';
            $operationing['operation_type']='delete';
            $operationing['admin_flag']='N';
            $operationing['ip']=$request->getClientIp();
            $operationing['log_status']='SU';
            $operationing['false_cause']=null;

            operationing($operationing);
            return response()->json(['st'=> true,'msg'=>'删除成功']);
        }else{
            return response()->json(['st'=> false,'msg'=>'删除失败']);
        }

    }



}
?>
