<?php
namespace App\Http\Admin\Sys;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class VersionsController  extends CommonController{
    /***    APP版本控制      /sys/versions/versionsList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

    public function  versionsList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    APP版本控制      /sys/versions/versionsPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

	public function versionsPage(Request $request){



	    DD(111);
		$input=Input::all();
        $anniu=$request->get('anniu');
//        dd($anniu);
//        $anniu=array_column($request->get('anniu'),'id');//接收中间件产生的参数,二维数组处理成一维数组，只取id
        $level=session(('level'));//接收中间件产生的参数
        $session_group_code=array_column($request->get('session_group_code'),'group_code');//接收中间件产生的参数,二维数组处理成一维数组，只取id
//        dd($session_group_code);
        $listrows=$request->input('num');
        $firstrow=($request->input('page')-1)*$listrows;
        $where['delete_flag']='Y';
        $search=[
            ['type'=>'like','name'=>'company_name','value'=>$input['company_name']],
            ['type'=>'all','name'=>'use_flag','value'=>$input['use_flag']],
            ['type'=>'all','name'=>'tel','value'=>$input['tel']],
        ];

        $qiao_info=DB::table('system_group')->where($where)->whereIn('group_code',$session_group_code);

        $count=getList($qiao_info,$search)->count();//总的数据量
        $page['page']=$request->input('page');
        $page['num']=$request->input('num');
        $page['total_page']=intval(ceil($count/$request->input('num')));
        $page['total_count']=$count;
        $page=page_show($page);

		//查询数据
        $info=getList($qiao_info,$search)->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')->get()->toArray();

		$anniu=$request->get('anniu');
		foreach ($info as $k=>$v) {

			$v->flag=use_flag($v->use_flag,3);

			$v->caozuo=anniu($v->self_id,$anniu)['list_caozuo'];

		}


		 return view('Branch.BranchCompany.branchCompany_page',['page'=>$page,'info'=>$info]);

	}





}
?>
