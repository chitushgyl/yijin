<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Models\Wms\WmsLibraryChange;

class HistoryController  extends CommonController{
    /***    商品进出库列表      /wms/history/historyList
     */
    public function  historyList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    商品进出库分页      /wms/history/historyPage
     */

    public function historyPage(Request $request){
		$wms_order_type      = config('wms.wms_order_type');
        $wms_order_type_show  =array_column($wms_order_type,'name','key');
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');
        $warehouse_id     	=$request->input('warehouse_id');
        $sku_id    			=$request->input('sku_id');
		$external_sku_id    =$request->input('external_sku_id');
        $price              =$request->input('price');
        $spec               =$request->input('spec');
        $group_code         =$request->input('group_code');
        $warehouse_name	    =$request->input('warehouse_name');
        $good_name	        =$request->input('good_name');
        $start_time			=$request->input('start_time');
        $end_time	        =$request->input('end_time');
        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;
        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'like','name'=>'warehouse_id','value'=>$warehouse_id],
            ['type'=>'like','name'=>'sku_id','value'=>$sku_id],
            ['type'=>'like','name'=>'price','value'=>$price],
            ['type'=>'like','name'=>'spec','value'=>$spec],
			['type'=>'like','name'=>'external_sku_id','value'=>$external_sku_id],
            ['type'=>'like','name'=>'group_code','value'=>$group_code],
			['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
			['type'=>'like','name'=>'good_name','value'=>$good_name],
			['type'=>'>=','name'=>'inout_time','value'=>$start_time],
			['type'=>'<','name'=>'inout_time','value'=>$end_time],
        ];

        $where=get_list_where($search);

        $select=['self_id','group_code','group_name','warehouse_name','warehouse_id','type','create_user_name','create_user_id',
				'external_sku_id','sku_id','good_name','spec','good_lot','produce_time','expire_time','library_sige_id','total_price','price',
				'initial_num','now_num','change_num','describe','good_unit','good_target_unit','good_scale','create_time','update_time','inout_time','order_id'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsLibraryChange::where($where)->count(); //总的数据量
                $data['items']=WmsLibraryChange::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsLibraryChange::where($where)->count(); //总的数据量
                $data['items']=WmsLibraryChange::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsLibraryChange::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsLibraryChange::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach ($data['items'] as $k=>$v) {
			$abc=unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->change_num);
			$v->type_show=$wms_order_type_show[$v->type]??null;
            if($v->area && $v->row && $v->column){
                $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
            }else{
                $v->sign = '';
            }
            if($v->initial_num >$v->now_num){
                $v->change_num='减少'.$v->change_num;
				$v->good_describe ='减少'.$abc;
            }else{
                $v->change_num='增加'.$v->change_num;
				$v->good_describe ='增加'.$abc;
            }
            $v->create_time = date('Y-m-d',strtotime($v->create_time));

            $v->button_info=$button_info;

        }

//        dd($data['items']->toArray());


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;




    }


}
?>
