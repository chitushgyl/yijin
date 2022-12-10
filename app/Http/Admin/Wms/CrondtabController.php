<?php
namespace App\Http\Admin\Wms;


use App\Http\Controllers\CommonController;
use App\Models\Wms\WmsLibrarySige;
use Illuminate\Http\Client\Request;

class CrondtabController extends CommonController{
     /**
      * 定时查看商品有效期，过期冻结货物
      * */
    public function updateSkuState(){
        $now_time = date('Y-m-d H:i:s',time());
        $where = [
            ['now_num','>',0],
            ['expire_time','<',$now_time],
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
        ];
        $select = ['self_id','order_id','sku_id','use_flag','delete_flag','can_use','production_date','expire_time'];
        $info = WmsLibrarySige::where($where)->select($select)->get();
        $ids = [];
        foreach ($info as $k => $v) {
            $ids[] = $v->self_id;
        }
        $data['can_use'] = 'N';
        $data['update_time'] = $now_time;
        $res = WmsLibrarySige::whereIn('self_id',$ids)->update($data);
    }
}
