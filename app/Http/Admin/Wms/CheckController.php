<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\Wms\WmsWarehouseArea;
use App\Models\Wms\WmsStock;
use App\Models\Wms\WmsStockList;
use App\Models\Wms\WmsLibrarySige;
use App\Models\Wms\WmsLibraryChange;
use App\Http\Controllers\DetailsController as Details;
use App\Http\Controllers\FileController as File;
class CheckController extends CommonController{
    /***    盘点      /wms/check/checkList
     */
    public function  checkList(Request $request){
        $group_info = $request->get('group_info');//接收中间件产生的参数

        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;
    }
    /***    盘点分页      /wms/check/checkPage
     */
    public function checkPage(Request $request){

        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            = $request->input('num') ?? 10;
        $page           = $request->input('page') ?? 1;
        $use_flag       = $request->input('use_flag');
        $listrows       = $num;
        $firstrow       = ($page - 1) * $listrows;


		$group_code     = $request->input('group_code');
		$company_id     = $request->input('company_id');
		$warehouse_id   = $request->input('warehouse_id');
		$area_id       	= $request->input('area_id');
		$type       	= $request->input('type');

        $search = [
            ['type' => '=', 'name' => 'delete_flag', 'value' => 'Y'],
            ['type' => 'all', 'name' => 'use_flag', 'value' => $use_flag],
			['type' => '=', 'name' => 'group_code', 'value' => $group_code],
			['type' => '=', 'name' => 'company_id', 'value' => $company_id],
			['type' => '=', 'name' => 'warehouse_id', 'value' => $warehouse_id],
			['type' => '=', 'name' => 'area_id', 'value' => $area_id],
			['type' => '=', 'name' => 'type', 'value' => $type],
        ];

        $where = get_list_where($search);
//DD($where);
        $select = ['self_id','group_name','warehouse_name','area','count','create_user_name','create_time','type','time'];

        switch ($group_info['group_id']) {
            case 'all':
                $data['total'] = WmsStock::where($where)->count(); //总的数据量
                $data['items'] = WmsStock::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show'] = 'Y';
                break;

            case 'one':
                $where[] = ['group_code', '=', $group_info['group_code']];
                $data['total'] = WmsStock::where($where)->count(); //总的数据量
                $data['items'] = WmsStock::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show'] = 'N';
                break;

            case 'more':
                $data['total'] = WmsStock::where($where)->whereIn('group_code', $group_info['group_code'])->count(); //总的数据量
                $data['items'] = WmsStock::where($where)->whereIn('group_code', $group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show'] = 'Y';
                break;
        }

        foreach ($data['items'] as $k => $v) {

            $v->button_info = $button_info;
            $v['type_show']=null;
            switch ($v['type']){
                case 'dynamic':
                    $v['type_show']="动盘";
                    break;

                case 'All':
                    $v['type_show']="全盘";
                    break;

                case 'area':
                    $v['type_show']="区域盘点";
                    break;


            }


        }
        //dd($data);
        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        //dd($msg);
        return $msg;

    }

    /***    创建盘点数据入库      /wms/check/addCheck
     */
    public function addCheck(Request $request,File $file){
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());

        $table_name     ='wms_stock';


        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $input          =$request->all();
        /** 接收数据：动盘：dynamic         */
        $company_id     =$request->input('company_id');
        $type           =$request->input('type');
        $warehouse_id   =$request->input('warehouse_id');
        $area_id        =$request->input('area_id');
		$start_time     =$request->input('start_time');
		$end_time       =$request->input('end_time');
		//dump($input);
        /** 虚拟数据 dynamic
        $input['company_id']     =$company_id='company_202012151639063453254100';
        $input['type']           =$type='dynamic';
        $input['warehouse_id']   =$warehouse_id='warehouse_202012151604470715850249';
        $input['area_id']        =$area_id='area_202012151606236525876470';
        $input['date1']           =$date1='2020-12-17';
        $input['date2']           =$date2='2020-12-21';
		*/
       // dd(1212121);
        $wms_stock_list=[];
        $data_execl=[];
        $idd=generate_id('stock_');

        switch ($type ){
            case 'dynamic':
				$operationing->access_cause     ='动态盘点';
                $wms_order_type      = config('wms.wms_order_type');
                $wms_order_type_show  =array_column($wms_order_type,'name','key');
				//dd($wms_order_type_show);
                $where=[
                    ['delete_flag','=','Y'],
                    ['company_id','=',$company_id],
                    ['create_time','>',$start_time.' 00:00:00'],
                    ['create_time','<',$end_time.' 23:59:59'],
                ];
				//dd($where);
                $select=['self_id','group_name','group_code','warehouse_id','warehouse_name','company_name','company_id','good_name','good_english_name','external_sku_id','spec',
                    'area','row','column','tier','now_num','good_unit','good_target_unit','good_scale','type','create_time'];

                $info=WmsLibraryChange::where($where)->orderBy('create_time', 'desc')->select($select)->get();
                if ((array)$info){
                    $msg['code']=302;
                    $msg['msg']="没有盘点数据";
                    return $msg;
                }
                /** 现在根据查询到的数据去做一个导出的数据**/
				//dd($info);
				if($info){

					foreach ($info as $k=>$v){
						$list=[];
						$list['company_name']       =$v->company_name;
						$list['group_name']         =$v->group_name;
						$list['warehouse_name']     =$v->warehouse_name;
						$list['sign']               =$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
						$list['now_num']            =$v->now_num.$v->good_unit;
						$list['external_sku_id']    =$v->external_sku_id;
						$list['good_name']          =$v->good_name;
						$list['good_english_name']  =$v->good_english_name;
						$list['describe']           =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);
						//$list['good_unit']          =$v->good_unit;
						$list['production_date']    =null;
						$list['expire_time']        =null;
						$list['type']               =$wms_order_type_show[$v->type];
						$list['create_time']        =$v->create_time;
						$data_execl[]=$list;
					}

                    $data['self_id']            =generate_id('stock_');		//优惠券表ID
                    $data['create_user_id']     =$user_info->admin_id;
                    $data['create_user_name']   =$user_info->name;
                    $data['create_time']        =$data['update_time']=$now_time;
                    $data['count']              = $info->count();
                    $data['group_code']         = $info[0]->group_code;
                    $data['group_name']         = $info[0]->group_name;
                    $data['company_id']         = $info[0]->company_id;
                    $data['company_name']       = $info[0]->company_name;
                    $data['warehouse_id']       = $info[0]->warehouse_id;
                    $data['warehouse_name']     = $info[0]->warehouse_name;
                    $data['type']               = $type;
                    $data['time']               = $start_time.' 00:00:00'.'-'.$end_time.' 23:59:59';

                }

                break;
            case 'All':
				$operationing->access_cause     ='全库盘点';
                $where=[
                    ['delete_flag','=','Y'],
                    ['warehouse_id','=',$warehouse_id],
                    ['now_num','>',0],
                ];

                $select=['self_id','group_name','group_code','warehouse_id','warehouse_name','company_name','company_id','area','area_id','warehouse_sign_id',
                    'sku_id','external_sku_id','good_name','good_english_name','spec','good_info','production_date','expire_time','wms_length','wms_wide','wms_high','wms_weight',
                    'row','column','tier','now_num','good_unit','good_target_unit','good_scale','create_time'];

                $info=WmsLibrarySige::where($where)->orderBy('create_time', 'desc')->select($select)->get();
				//dd($info);
				if($info){
					foreach ($info as $k=>$v){
						$list=[];
						$list['company_name']       =$v->company_name;
						$list['group_name']         =$v->group_name;
						$list['warehouse_name']     =$v->warehouse_name;
						$list['sign']               =$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
						$list['now_num']            =$v->now_num.$v->good_unit;
						$list['external_sku_id']    =$v->external_sku_id;
						$list['good_name']          =$v->good_name;
						$list['good_english_name']  =$v->good_english_name;
						$list['describe']           =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);
						//$list['good_unit']          =$v->good_unit;
						$list['production_date']    =$v->production_date;
						$list['expire_time']        =$v->expire_time;
						$list['type']               ='库存';
						$list['create_time']        =$v->create_time;
						$data_execl[]=$list;

						$stock_list=[];
						$stock_list['self_id']            =generate_id('stock_list_');
						$stock_list['group_name']         =$v->group_name;
						$stock_list['group_code']         =$v->group_code;
						$stock_list['company_name']       =$v->company_name;
						$stock_list['company_id']         =$v->company_id;
						$stock_list['warehouse_name']     =$v->warehouse_name;
						$stock_list['warehouse_id']       =$v->warehouse_id;
						$stock_list['area']               =$v->area;
						$stock_list['area_id']            =$v->area_id;
						$stock_list['library_sige_id']    =$v->self_id;
						$stock_list['warehouse_sign_id']  =$v->warehouse_sign_id;

						$stock_list['row']                =$v->row;
						$stock_list['column']             =$v->column;
						$stock_list['tier']               =$v->tier;

						$stock_list['sku_id']             =$v->sku_id;
						$stock_list['external_sku_id']    =$v->external_sku_id;
						$stock_list['good_name']          =$v->good_name;
						$stock_list['good_english_name']  =$v->good_english_name;
						$stock_list['spec']               =$v->spec;
						$stock_list['good_unit']          =$v->good_unit;
						$stock_list['good_target_unit']   =$v->good_target_unit;
						$stock_list['good_scale']         =$v->good_scale;
						$stock_list['good_info']          =$v->good_info;
						$stock_list['production_date']    =$v->production_date;
						$stock_list['expire_time']        =$v->expire_time;
						$stock_list['wms_length']         =$v->wms_length;
						$stock_list['wms_wide']           =$v->wms_wide;
						$stock_list['wms_high']           =$v->wms_high;
						$stock_list['num']                =$v->now_num;
						$stock_list['create_user_id']     =$user_info->admin_id;
						$stock_list['create_user_name']   =$user_info->name;
						$stock_list['create_time']        =$stock_list['update_time']=$now_time;

						$stock_list['stock_id']           =$idd;

						$wms_stock_list[]=$stock_list;


					}

                    $data['self_id']            =generate_id('stock_');		//优惠券表ID
                    $data['create_user_id']     =$user_info->admin_id;
                    $data['create_user_name']   =$user_info->name;
                    $data['create_time']        =$data['update_time']=$now_time;
                    $data['count']              = $info->count();
                    $data['group_code']         = $info[0]->group_code;
                    $data['group_name']         = $info[0]->group_name;
                    $data['company_id']         = $info[0]->company_id;
                    $data['company_name']       = $info[0]->company_name;
                    $data['warehouse_id']       = $info[0]->warehouse_id;
                    $data['warehouse_name']     = $info[0]->warehouse_name;
                    $data['type']               = $type;
                    $data['time']               = date('Y-m-d',time());
                }


                break;
            case 'area':
				$operationing->access_cause     ='区域盘点';
                $where=[
                    ['delete_flag','=','Y'],
                    ['area_id','=',$area_id],
                    ['now_num','>',0],
                ];

                $select=['self_id','group_name','group_code','warehouse_id','warehouse_name','company_name','company_id','area','area_id','warehouse_sign_id',
                    'sku_id','external_sku_id','good_name','good_english_name','spec','good_info','production_date','expire_time','wms_length','wms_wide','wms_high','wms_weight',
                    'row','column','tier','now_num','good_unit','good_target_unit','good_scale','create_time'];
                $info=WmsLibrarySige::where($where)->orderBy('create_time', 'desc')->select($select)->get();
				//dd($info);
				if($info){
					foreach ($info as $k=>$v){
						$list=[];
						$list['company_name']       =$v->company_name;
						$list['group_name']         =$v->group_name;
						$list['warehouse_name']     =$v->warehouse_name;
						$list['sign']               =$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
						$list['now_num']            =$v->now_num.$v->good_unit;
						$list['external_sku_id']    =$v->external_sku_id;
						$list['good_name']          =$v->good_name;
						$list['good_english_name']  =$v->good_english_name;
						$list['describe']           =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);
						//$list['good_unit']          =$v->good_unit;
						$list['production_date']    =$v->production_date;
						$list['expire_time']        =$v->expire_time;
						$list['type']               ='库存';
						$list['create_time']        =$v->create_time;
						$data_execl[]=$list;

						$stock_list=[];
						$stock_list['self_id']            =generate_id('stock_list_');
						$stock_list['group_name']         =$v->group_name;
						$stock_list['group_code']         =$v->group_code;
						$stock_list['company_name']       =$v->company_name;
						$stock_list['company_id']         =$v->company_id;
						$stock_list['warehouse_name']     =$v->warehouse_name;
						$stock_list['warehouse_id']       =$v->warehouse_id;
						$stock_list['area']               =$v->area;
						$stock_list['area_id']            =$v->area_id;
						$stock_list['library_sige_id']    =$v->self_id;
						$stock_list['warehouse_sign_id']  =$v->warehouse_sign_id;

						$stock_list['row']                =$v->row;
						$stock_list['column']             =$v->column;
						$stock_list['tier']               =$v->tier;

						$stock_list['sku_id']             =$v->sku_id;
						$stock_list['external_sku_id']    =$v->external_sku_id;
						$stock_list['good_name']          =$v->good_name;
						$stock_list['good_english_name']  =$v->good_english_name;
						$stock_list['spec']               =$v->spec;
						$stock_list['good_unit']          =$v->good_unit;
						$stock_list['good_target_unit']   =$v->good_target_unit;
						$stock_list['good_scale']         =$v->good_scale;
						$stock_list['good_info']          =$v->good_info;
						$stock_list['production_date']    =$v->production_date;
						$stock_list['expire_time']        =$v->expire_time;
						$stock_list['wms_length']         =$v->wms_length;
						$stock_list['wms_wide']           =$v->wms_wide;
						$stock_list['wms_high']           =$v->wms_high;
						$stock_list['num']                =$v->now_num;
						$stock_list['create_user_id']     =$user_info->admin_id;
						$stock_list['create_user_name']   =$user_info->name;
						$stock_list['create_time']        =$stock_list['update_time']=$now_time;

						$stock_list['stock_id']           =$idd;

						$wms_stock_list[]=$stock_list;
					}

                    $data['self_id']            =$idd;
                    $data['create_user_id']     =$user_info->admin_id;
                    $data['create_user_name']   =$user_info->name;
                    $data['create_time']        =$data['update_time']=$now_time;
                    $data['count']              = $info->count();
                    $data['group_code']         = $info[0]->group_code;
                    $data['group_name']         = $info[0]->group_name;
                    $data['company_id']         = $info[0]->company_id;
                    $data['company_name']       = $info[0]->company_name;
                    $data['warehouse_id']       = $info[0]->warehouse_id;
                    $data['warehouse_name']     = $info[0]->warehouse_name;
                    $data['area_id']            = $info[0]->area_id;
                    $data['area']               = $info[0]->area;
                    $data['type']               = $type;
                    $data['time']               = date('Y-m-d',time());
                }

                break;
        }



        //dd($data_execl);
		set_time_limit(0);
        if(count($data_execl)>0){

            $id=WmsStock::insert($data);
            if(count($wms_stock_list)>0){
                $wms_stock_list_chunk=array_chunk($wms_stock_list,1000);
                foreach ($wms_stock_list_chunk as $k=>$v){
                    WmsStockList::insert($v);
                }
            }
            $group_name=$info[0]->group_name;
            $group_code=$info[0]->group_code;

            //设置表头
            $row = [[
                "group_name"=>'所属公司',
                "company_name"=>'业务往来公司',
                "warehouse_name"=>'仓库',
                "type"=>'操作类型',
                "sign"=>'库位',
				"external_sku_id"=>'商品编号',
                "good_name"=>'商品名称',
                "good_english_name"=>'商品英文名称',
				"now_num"=>'数量',
                //"good_unit"=>'最小单位',
				"production_date"=>'生产日期',
				"expire_time"=>'到期时间',
                "describe"=>'换算单位',
                "create_time"=>'入库时间',
            ]];


            /** 调用EXECL导出公用方法，将数据抛出来***/
            $browse_type=$request->path();
			//dd($data_execl);
            $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);

            //dd($msg);
            $operationing->table_id=$idd;
            $operationing->old_info=null;
            $operationing->new_info=$data;
            return $msg;
        }else{
            $msg['code']=301;
            $msg['msg']="没有数据可以导出";
            return $msg;
        }


    }

	/***    盘点详情     /wms/check/details
     */
    public function  details(Request $request,Details $details){
        $wms_money_type_show    =array_column(config('wms.wms_money_type'),'name','key');
        $self_id=$request->input('self_id');
        //$self_id='stock_202012241637534669626855';
        $table_name='wms_money';
        $select=['self_id','type','time','group_name','warehouse_name','create_user_name','create_time','use_flag','company_name','area','count'
            ];


        $list_select=['self_id','area','row','column','tier','external_sku_id','good_name','good_english_name','spec','good_unit','good_target_unit','good_scale','production_date','expire_time','num',
            'create_user_name','create_time','use_flag','stock_id'];

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];


        $info=WmsStock::with(['WmsStockList' => function($query) use($list_select){
            $query->select($list_select);
        }])->where($where)->select($select)->first();
        $info->type_show=null;
        switch ($info->type) {
            case 'All':
                $info->type_show = '全库盘点';
                break;

            case 'dynamic':

                $info->type_show = '动态盘点';
                break;

            case 'area':
                $info->type_show = '分区盘点';
                break;
        }

        foreach ($info->WmsStockList as $k=>$v){
            $v->sign                =$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
            $v->good_describe      =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->num);
        }
        //DD($info->toArray());
        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $data['info']=$info;

            $log_flag='N';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);

            }

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }


}
?>
