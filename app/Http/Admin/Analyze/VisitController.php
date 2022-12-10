<?php
namespace App\Http\Admin\Analyze;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class VisitController  extends CommonController{
    /***    用户PV UV分析      /analyze/visit/visitList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  visitList(Request $request){
        //引入配置文件
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    用户PV UV分析      /analyze/visit/visitPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

	public function visitPage(Request $request){
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
        $select=['a.self_id','a.browse_path','a.ip','a.group_name','a.create_time','a.place','b.token_img','b.token_name'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('user_uv as a')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_uv as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();
                //做购物车的排行榜单
                $data['ranking_info']=DB::table('user_uv as a')
                    ->where($where)
                    ->select('a.browse_path', DB::raw('count(a.browse_path) as count'))
                    ->groupBy('a.browse_path')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
				$data['group_show']='Y';

                break;

            case 'one':
                $where[]=['a.group_code','=',$group_info['group_code']];
                $data['total']=DB::table('user_uv as a')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_uv as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();
                //做购物车的排行榜单
                $data['ranking_info']=DB::table('user_uv as a')
                    ->where($where)
                    ->select('a.browse_path', DB::raw('count(a.browse_path) as count'))
                    ->groupBy('a.browse_path')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
				$data['group_show']='N';
                break;

            case 'more':
                $data['total']=DB::table('user_uv as a')->where($where)->whereIn('a.group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=DB::table('user_uv as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();
                //做购物车的排行榜单
                $data['ranking_info']=DB::table('user_uv as a')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->select('a.browse_path', DB::raw('count(a.browse_path) as count'))
                    ->groupBy('a.browse_path')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
				$data['group_show']='Y';	
                break;
        }

        /*** 做一个转化的控制变量**/
        $rule=config('page.rule');

        foreach ($data['items'] as $k => $v){
            //判断这个值在不再规则的数组KEY中，如果在则转化成中文，如果不在，则不动
            if(array_key_exists($v->browse_path, $rule)){
                $v->browse_path=$rule[$v->browse_path];
            }

        }

        foreach ($data['ranking_info'] as $k => $v){
            //判断这个值在不再规则的数组KEY中，如果在则转化成中文，如果不在，则不动
            if(array_key_exists($v->browse_path, $rule)){
                $v->browse_path=$rule[$v->browse_path];
            }

        }


        //dd($data);
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;

	}





}
?>
