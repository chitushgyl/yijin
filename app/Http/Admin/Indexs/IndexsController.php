<?php
namespace App\Http\Admin\Indexs;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Models\Group\SystemGroup;
class IndexsController extends CommonController{
    /***    首页数据展示中心      /indexs/index
     */
    public function index(Request $request){
        /**  这里应该去拉取简单统计报表数据 统计  ***/


        //dump($user_info);

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
//        dd($msg);
        return $msg;

    }

}
?>
