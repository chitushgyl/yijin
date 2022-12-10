<?php
namespace App\Http\Admin\Analyze;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class TrackController  extends CommonController{
    /***    用户购物车分析      /analyze/track/trackList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  trackList(Request $request){
        //引入配置文件
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    用户购物车分析      /analyze/track/trackPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

	public function trackPage(Request $request){

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
        $select=['a.self_id','a.create_time','a.delete_flag','a.delete_time','a.data_id','b.token_img','b.token_name','c.good_title','c.thum_image_url'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('user_track as a')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_track as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->join('erp_shop_goods as c',function($join){
                        $join->on('a.data_id','=','c.self_id');
                    }, null,null,'left')
                    ->where($where)->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();

                //做一个收藏排行榜出来
                $data['ranking_info']=DB::table('user_track as a')
                    ->join('erp_shop_goods as c',function($join){
                        $join->on('a.data_id','=','c.self_id');
                    }, null,null,'left')
                    ->where($where)
                    ->select('c.good_title', DB::raw('count(c.good_title) as count'))
                    ->groupBy('c.good_title')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();


                break;

            case 'one':
                $where[]=['a.group_code','=',$group_info['group_code']];
                $data['total']=DB::table('user_track as a')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_track as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->join('erp_shop_goods as c',function($join){
                        $join->on('a.data_id','=','c.self_id');
                    }, null,null,'left')
                    ->where($where)->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();

                //做一个收藏排行榜出来
                $data['ranking_info']=DB::table('user_track as a')
                    ->join('erp_shop_goods as c',function($join){
                        $join->on('a.data_id','=','c.self_id');
                    }, null,null,'left')
                    ->where($where)
                    ->select('c.good_title', DB::raw('count(c.good_title) as count'))
                    ->groupBy('c.good_title')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                break;

            case 'more':
                $data['total']=DB::table('user_track as a')->where($where)->whereIn('a.group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=DB::table('user_track as a')
                    ->join('user_reg as b',function($join){
                        $join->on('a.total_user_id','=','b.self_id');
                    }, null,null,'left')
                    ->join('erp_shop_goods as c',function($join){
                        $join->on('a.data_id','=','c.self_id');
                    }, null,null,'left')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select($select)
                    ->get()->toArray();

                //做一个收藏排行榜出来
                $data['ranking_info']=DB::table('user_track as a')
                    ->join('erp_shop_goods as c',function($join){
                        $join->on('a.data_id','=','c.self_id');
                    }, null,null,'left')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->select('c.good_title', DB::raw('count(c.good_title) as count'))
                    ->groupBy('c.good_title')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();


                break;
        }

        foreach ($data['items'] as $k => $v){
            if($v->thum_image_url){
                $v->thum_image_url=img_for($v->thum_image_url,'one');
            }
        }



        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

	}





}
?>
