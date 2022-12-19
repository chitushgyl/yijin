<?php
namespace App\Http\Controllers;
use App\Models\Wms\WmsLibraryChange;
class WmschangeController extends Controller{
    /***    WMS仓库数据改变量表
     */
    public function change($datalist,$type){
        $data=[];
        $payment = [];
        foreach ($datalist as $k => $v){

            $list=[];
            $money=[];
            $list["self_id"]            =generate_id('change_');
            if($type != 'preentry'){
                $list["warehouse_id"]       =$v['warehouse_id'];
                $list["warehouse_name"]     =$v['warehouse_name'];
                $list['warehouse_sign_id']  =$v['warehouse_sign_id'];
            }

            $list["sku_id"]             =$v['sku_id'];
            $list["external_sku_id"]    =$v['external_sku_id'];
            $list["good_name"]          =$v['good_name'];
            $list["good_english_name"]  =$v['good_english_name'];
            $list["good_target_unit"]   =$v['good_target_unit'];
            $list["good_scale"]         =$v['good_scale'];
            $list["good_unit"]          =$v['good_unit'];
            $list['spec']               =$v['spec'];
            $list["good_info"]          =$v['good_info'];

            $list['create_time']        =$v['create_time'];
            $list["update_time"]        =$v['update_time'];
            $list["group_code"]         =$v['group_code'];
            $list["group_name"]         =$v['group_name'];
            $list["order_id"]           =$v['order_id'];

            $list['type']               =$type;
            $list['library_sige_id']    =$v['self_id'];

            switch ($type){
                case 'preentry':
                    $list['initial_num']        ='0';
                    $list['now_num']            =$v['now_num'];
                    $list['change_num']         =$list['now_num']-$list['initial_num'];
                    $list['use_flag']           ='N';
                    break;

                case 'change':
                    $list['initial_num']        =$v['now_num'];
                    if($v['now_num'] - $v['now_num_new'] >=0){
                        $list['now_num']            =$v['now_num_new'];
                    }else{
                        $list['now_num']            =0;
                    }
                    $list['change_num']         =$list['initial_num']-$list['now_num'];
                    break;

                case 'movein':
                    $list['initial_num']        ='0';
                    $list['now_num']            =$v['now_num'];
                    $list['change_num']         =$list['now_num']-$list['initial_num'];
                    break;

                case 'moveout':
                    $list['initial_num']        =$v['now_num'];
                    if($v['now_num'] - $v['now_num_new'] >0){
                        $list['now_num']            =$v['now_num_new'];
                    }else{
                        $list['now_num']            =0;
                    }
                    $list['change_num']         =$list['initial_num']-$list['now_num'];
                    break;


                case 'out':
                    $list['initial_num']        =$v['initial_num'];             //66
                    $list['change_num']         =$v['shiji_num'];
                    $list['now_num']            =$v['initial_num']-$v['shiji_num'];
                    break;

            }
            $money['total_price'] = $list['change_num']*$v['price'];
            $payment[] = $money;
            $data[]=$list;
        }
        /***出入库时要保存费用**/
        WmsLibraryChange::insert($data);


    }






}
