<?php
namespace App\Http\Admin\Sys;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroupAuthority;
use App\Models\Group\SystemGroup;
use App\Models\SysAddress;


class AdderssController  extends CommonController{

    /***    默认权限     /sys/address/address
     */
    public function  address(Request $request){
        /**拿取所有的地址，方便做搜索功能触发**/
//        $where=[
//            ['type_flag','=','express'],
//            ['delete_flag','=','Y'],
//        ];
        $data['address_all_info']=SysAddress::select('id','name','level','parent_id')->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

}
?>
