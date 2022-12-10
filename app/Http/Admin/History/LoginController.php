<?php
namespace App\Http\Admin\History;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Models\Log\LogLogin;

class LoginController extends CommonController{
    /***    登录信息头部      /history/login/loginList
     */
    public function  loginList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    登录信息分页      /history/login/loginPage
     */
    public function  loginPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $tms_login_type           =array_column(config('tms.tms_login_type'),'name','key');
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $group_name     =$request->input('wx_template_id');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'type','value'=>'after'],
            ['type'=>'!=','name'=>'login','value'=>'admin'],
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
        ];

        $where=get_list_where($search);
        $select=['self_id','login','create_time','group_name','login_status','result','create_time','login_place','type','use_flag'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=LogLogin::where($where)->count(); //总的数据量
                $data['items']=LogLogin::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=LogLogin::where($where)->count(); //总的数据量
                $data['items']=LogLogin::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)
                    ->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=LogLogin::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=LogLogin::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }


        foreach ($data['items'] as $k=>$v){
            $v->type_show = $tms_login_type[$v->type]??null;
            $v->button_info=$button_info;
        }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($data['items']);
        return $msg;

    }


}
?>
