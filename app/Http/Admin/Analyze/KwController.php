<?php
namespace App\Http\Admin\Analyze;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class KwController  extends CommonController{
    /***    用户搜索分析      /analyze/kw/kwList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

    public function  kwList(Request $request){
        //引入配置文件
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    用户搜索分析      /analyze/kw/kwPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

	public function kwPage(Request $request){
        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数
        /**接收数据*/
        $num=$request->input('num')??10;
        $page=$request->input('page')??1;

        $listrows=$num;
        $firstrow=($page-1)*$listrows;


        $search=[
            ['type'=>'=','name'=>'a.delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);
        $select=['a.self_id','a.keyword_name','a.create_time','a.kw_classifys_name','b.token_img','b.token_name'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('user_kw as a')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_kw as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->where($where)->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();
                //做搜索的排行榜单
                $data['ranking_info']=DB::table('user_kw as a')->where($where)
                ->select('a.keyword_name', DB::raw('count(a.keyword_name) as count'))
                ->groupBy('a.keyword_name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray();


                break;

            case 'one':
                $where[]=['a.group_code','=',$group_info['group_code']];
                $data['total']=DB::table('user_kw as a')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_kw as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->where($where)->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();
                //做搜索的排行榜单
                $data['ranking_info']=DB::table('user_kw as a')->where($where)
                    ->select('a.keyword_name', DB::raw('count(a.keyword_name) as count'))
                    ->groupBy('a.keyword_name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
                break;

            case 'more':
                $data['total']=DB::table('user_kw as a')->where($where)->whereIn('a.group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=DB::table('user_kw as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();
                //做搜索的排行榜单
                $data['ranking_info']=DB::table('user_kw as a')->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->select('a.keyword_name', DB::raw('count(a.keyword_name) as count'))
                    ->groupBy('a.keyword_name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                break;

        }

        //做搜索的排行榜单

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

	}





}
?>
