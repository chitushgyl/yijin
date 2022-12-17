<?php
namespace App\Http\Controllers;
use App\Models\Wms\WmsMoney;
use App\Models\Wms\WmsMoneyList;

class WmsMoneyController extends Controller{
    /***    仓库费用计算
     *      有几个费用，第一个，入库费用
     *      第二个，出库费用
     *      第三个，分拣费用
     *
     */
    public function moneyCompute($data,$datalist,$now_time,$company_info,$user_info,$type){
        /** 根据传递的参数做不同的费用计算方式****/
        switch ($type){
            case 'in':
                $rule=$company_info->preentry_type;
                $price=$company_info->preentry_price;
                break;
            case 'out':
                $rule=$company_info->out_type;
                $price=$company_info->out_price;
                break;
            case 'storage':
                $rule=$company_info->storage_type;
                $price=$company_info->storage_price;
                break;
            case 'total':
                $rule=$company_info->total_type;
                $price=$company_info->total_price;
                break;
        }

        /** 计算费用                拖pull，重量weight，体积bulk,不收费：no           ****/
        $money=0;
        switch ($rule){
            case 'pull':
                $money=$price*$data['pull_count'];
                break;
            case 'bulk':
                $money=$price*$data['bulk'];
                break;
            case 'weight':
                $money=$price*$data['weight'];
                break;
            case 'no':
                $money=0;
                break;
        }

        $seld=generate_id('money_');

        $data_money['self_id']              =$seld;
        $data_money['type']                 =$type;
		$data_money["time"] 				=substr($now_time,0,-9);
        $data_money["group_code"]           =$data['group_code'];
        $data_money["group_name"]           =$data['group_name'];
        $data_money["warehouse_id"]         =$data['warehouse_id'];
        $data_money["warehouse_name"]       =$data['warehouse_name'];
        $data_money['company_id']           =$data['company_id'];
        $data_money["company_name"]         =$data['company_name'];
        $data_money['create_user_id']       = $user_info->admin_id;
        $data_money['create_user_name']     = $user_info->name;
        $data_money['create_time']          =$now_time;
        $data_money["update_time"]          =$now_time;
        $data_money["info"]                 =json_encode($data,JSON_UNESCAPED_UNICODE);
        $data_money["money"]                =$money;

        WmsMoney::insert($data_money);


//        DD($datalist);
        if(count($datalist)>0){
            $money_list=[];

            foreach ($datalist as $k => $v){

                $list['self_id']                =generate_id('mlist_');
                $list['warehouse_id']           =$v['warehouse_id'];
                $list['warehouse_name']         =$v['warehouse_name'];
                $list['library_sige_id']        =$v['self_id'];
                $list['warehouse_sign_id']      =$v['warehouse_sign_id'];
                $list['area_id']                =$v['area_id'];
                $list['area']                   =$v['area'];
                $list['row']                    =$v['row'];
                $list['column']                 =$v['column'];
                $list['tier']                   =$v['tier'];
                $list['group_code']             =$v['group_code'];
                $list['group_name']             =$v['group_name'];
                $list['company_id']             =$v['company_id'];
                $list['company_name']           =$v['company_name'];
                $list['sku_id']                 =$v['sku_id'];
                $list['external_sku_id']        =$v['external_sku_id'];
                $list['good_name']              =$v['good_name'];
                $list['good_english_name']      =$v['good_english_name'];
                $list['spec']                   =$v['spec'];
                $list['good_unit']              =$v['good_unit'];
                $list['good_target_unit']       =$v['good_target_unit'];
                $list['good_scale']             =$v['good_scale'];
                $list['good_info']              =$v['good_info'];
                $list['production_date']        =$v['production_date'];
                $list['expire_time']            =$v['expire_time'];
                $list['wms_length']             =$v['wms_length'];
                $list['wms_wide']               =$v['wms_wide'];
                $list['wms_high']               =$v['wms_high'];
                $list['wms_weight']             =$v['wms_weight'];
                $list['create_user_id']         = $user_info->admin_id;
                $list['create_user_name']       = $user_info->name;
                $list['create_time']            =$now_time;
                $list["update_time"]            =$now_time;

                switch ($type){
                    case 'in':
                        $list['num']=$v['now_num'];
                        break;
                    case 'out':
                        $list['num']=$v['now_num'];
                        break;
                    case 'storage':
                        $list['num']=$v['storage_number'];
                        break;
                    case 'total':
//                        $list['num']=$v['num'];
                        break;
                }

                $list['money_id']=$seld;
                $money_list[]=$list;
            }
            WmsMoneyList::insert($money_list);
        }

    }







}
