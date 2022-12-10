<?php
namespace App\Http\Admin\Analyze;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class PositionController  extends CommonController{
    /***    用户登录位置分析      /analyze/position/positionList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  positionList(Request $request){
        //引入配置文件
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    用户登录位置分析      /analyze/position/positionPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

	public function positionPage(Request $request){
        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数
        /**接收数据*/
        $num=$request->input('num')??10;
        $page=$request->input('page')??1;

        $listrows=$num;
        $firstrow=($page-1)*$listrows;


        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('user_position')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_position')->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
//                    ->select('self_id','good_title','good_type','classify_name','parent_classify_name','create_user_name')
                    ->get()->toArray();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=DB::table('user_position')->where($where)->count(); //总的数据量
                $data['items']=DB::table('user_position')->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
//                    ->select('self_id','good_title','good_type','classify_name','parent_classify_name','create_user_name','create_time','commodity_number','good_info',
//                        'good_status','thum_image_url','group_name','sell_start_time','sell_end_time')
                    ->get()->toArray();
                break;

            case 'more':
                $data['total']=DB::table('user_position')->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=DB::table('user_position')->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
//                    ->select('self_id','good_title','good_type','classify_name','parent_classify_name','create_user_name','create_time','commodity_number','good_info',
//                        'good_status','thum_image_url','group_name','sell_start_time','sell_end_time')
                    ->get()->toArray();
                break;
        }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

	}





}
?>
