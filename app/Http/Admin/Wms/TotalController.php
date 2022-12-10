<?php
namespace App\Http\Admin\Wms;
use App\Models\Wms\WmsWarehouseArea;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Models\Wms\WmsTotal;
use App\Models\Wms\WmsOutOrder;
use App\Models\Wms\WmsLibrarySige;
use App\Models\Wms\WmsOutOrderList;
use App\Models\Wms\WmsOutSige;
use App\Models\Wms\WmsGroup;
use App\Http\Controllers\WmschangeController as Change;
use App\Http\Controllers\DetailsController as Details;
use App\Http\Controllers\WmsMoneyController as WmsMoney;

class TotalController  extends CommonController{
    /***    总拣列表      /wms/total/totalList
     */
    public function  totalList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    总拣分页      /wms/total/totalPage
     */
    public function totalPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

		$group_code       =$request->input('group_code');
		$company_id       =$request->input('company_id');
		$warehouse_id     =$request->input('warehouse_id');
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            //['type'=>'like','name'=>'area','value'=>$input['area']],
            ['type'=>'=','name'=>'company_id','value'=>$company_id],
			['type'=>'=','name'=>'group_code','value'=>$group_code],
			['type'=>'=','name'=>'warehouse_id','value'=>$warehouse_id],
        ];

        $where=get_list_where($search);

        $select=['self_id','warehouse_name','company_name','order_count','create_user_name','create_time','group_name'];
        $wmsOutOrderSelect=['self_id','total_id'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsTotal::where($where)->count(); //总的数据量
                $data['items']=WmsTotal::with(['wmsOutOrder' => function ($query)use($wmsOutOrderSelect) {
                    $query->select($wmsOutOrderSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsTotal::where($where)->count(); //总的数据量
                $data['items']=WmsTotal::with(['wmsOutOrder' => function ($query)use($wmsOutOrderSelect) {
                    $query->select($wmsOutOrderSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsTotal::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsTotal::with(['wmsOutOrder' => function ($query)use($wmsOutOrderSelect) {
                    $query->select($wmsOutOrderSelect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {

            $v->button_info=$button_info;

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    创建总拣单      /wms/total/createTotal
     */
    public function createTotal(Request $request){
         //按照每个业务公司来完成总拣
        /** 接收数据*/
        $company_id             =$request->input('company_id');

        /*** 虚拟数据***/
        //$company_id             ='group_202011281508509934484447';

        $where=[
            ['total_flag','=','N'],
            ['company_id','=',$company_id],
            ['delete_flag','=','Y'],
        ];

        $select = ['self_id','status','count','total_flag','shop_id','shop_name','create_time','create_user_name','fuhe_flag','file_id','company_name','warehouse_name','delivery_time'];

        $data['info']=WmsOutOrder::where($where)->select($select)->get();

        if($data['info']){
            $msg['code'] = 200;
            $msg['msg'] = "数据拉取成功";
            $msg['data'] = $data;
            //dd($msg);
            return $msg;
        }else{
            $msg['code'] = 300;
            $msg['msg'] = "暂时没有需要总拣的订单";
            //dd($msg);
            return $msg;
        }
    }

    /***    生成总拣单      /wms/total/addTotal
     */
    public function addTotal(Request $request,Change $change,WmsMoney $money){
        $bulk=0;
        $weight=0;
//        $int_cold_num = 0;
//        $int_freeze_num = 0;
//        $int_normal_num = 0;
        $pull=[];
        $datalist=[];
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='wms_total';

        $operationing->access_cause='新建总拣';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        $user_info          = $request->get('user_info');                //接收中间件产生的参数

		//$input              =$request->all();
//dump($input);
        $total               =$request->input('total');
		//dd($total);
        /*** 虚拟数据
        $total=['order_202012151724538859137946'];
         */

        $where=[
            ['delete_flag','=','Y'],
        ];

        $select = ['self_id','status','count','total_flag','shop_id','shop_name',
            'company_id','company_name','warehouse_id','warehouse_name','group_code','group_name'];
        $select2 = ['self_id','good_name','spec','num','order_id','sku_id','external_sku_id','sanitation','shop_id','shop_name','recipt_code','shop_code','price','total_price'];
        $order = WmsOutOrder::with(['wmsOutOrderList' => function($query) use($select2){
            $query->select($select2);
            $query->where('delete_flag','=','Y');
        }])->where($where)->whereIn('self_id', $total)
            ->select($select)->get()->toArray();
		//dump($order);
        /** 第一步，检查是不是所有的都可以总拣**/
        if($order){
            $check=array_column($order,'total_flag');
            $order_check    =array_count_values($check);

            if(array_key_exists('Y', $order_check)){
                $msg['code']=301;
                $msg['msg']='选中的选项中包含了已总拣，请检查';
                return $msg;
            }

            //dd($order);
            /**  去执行总拣逻辑去！！！！！*/
            $count                          =count($total);
            $totalId                        =generate_id("T_");
            $data["self_id"]                =$totalId;
            $data['create_user_id']         =$user_info->admin_id;
            $data["create_user_name"]       =$user_info->name;
            $data["create_time"]            =$now_time;
            $data["update_time"]            =$now_time;
            $data["group_code"]             =$order[0]['group_code'];
            $data["group_name"]             =$order[0]['group_name'];
            $data["warehouse_id"]           =$order[0]['warehouse_id'];
            $data["warehouse_name"]         =$order[0]['warehouse_name'];
            $data["company_id"]             =$order[0]['company_id'];
            $data["company_name"]           =$order[0]['company_name'];
            $data["order_count"]            =$count;
//            $data['recipt_code']            = $order[0]['recipt_code'];
//            $data['shop_code']              = $order[0]['shop_code'];
//            $data['int_cold']               = $order[0]['int_cold'];
//            $data['int_freeze']             = $order[0]['int_freeze'];
//            $data['int_normal']             = $order[0]['int_normal'];
//            $data['scattered_cold']         = $order[0]['scattered_cold'];
//            $data['scattered_freeze']       = $order[0]['scattered_freeze'];
//            $data['scattered_normal']       = $order[0]['scattered_normal'];
//dd($data);

            $temp['total_id']=$totalId;
            $temp['total_flag']='Y';
            $temp['total_time']=$now_time;


            $order_do=[];
            foreach ($order as $k => $v){
                if($v['wms_out_order_list']){
                    foreach ($v['wms_out_order_list'] as $kk => $vv){
                        $order_do[]=$vv;
                    }
                }
            }
//dd($order_do);
            DB::beginTransaction();
            try {
                foreach ($order_do as $k => $v) {
                    //dd($v);
                    if ($v['sanitation']) {
                        $where2 = [
                            ['sku_id', '=', $v['sku_id']],
                            ['now_num', '>', 0],
                            ['can_use', '=', 'Y'],
//                            ['grounding_status', '=', 'Y'],
                            ['delete_flag', '=', 'Y'],
                            ['expire_time', '>', $v['sanitation']]
                        ];
                    } else {
                        $where2 = [
                            ['sku_id', '=', $v['sku_id']],
                            ['now_num', '>', 0],
                            ['can_use', '=', 'Y'],
//                            ['grounding_status', '=', 'Y'],
                            ['delete_flag', '=', 'Y'],
                            ['expire_time', '>', substr($now_time, 0, -9)]
                        ];
                    }
                    //$v["shop_id"]            	=$order[0]['shop_id'];
                    //$v["shop_name"]            	=$order[0]['shop_name'];

                    $resssss = WmsLibrarySige::where($where2)->orderBy('expire_time', 'asc')->get()->toArray();
                    //dd($resssss);
                    if ($resssss) {
                        $totalNum = array_sum(array_column($resssss, 'now_num'));
                        $numds = $v['num'] - $totalNum;

                        if ($numds > 0) {
                            //表示缺货$numds
                            $xiugai["quehuo"] = "Y";
                            $xiugai["quehuo_num"] = $numds;

                            $infos = self::dataInsert($xiugai, $totalId, $v, $resssss, $now_time, $user_info, $change, $pull, $bulk, $weight, $datalist);
                            $pull = $infos['pull'];
                            $bulk = $infos['bulk'];
                            $weight = $infos['weight'];
                            $datalist = $infos['datalist'];

                        } else {
                            $xiugai["quehuo"] = "N";
                            $xiugai["quehuo_num"] = 0;

                            $infos = self::dataInsert($xiugai, $totalId, $v, $resssss, $now_time, $user_info, $change, $pull, $bulk, $weight, $datalist);
                            $pull = $infos['pull'];
                            $bulk = $infos['bulk'];
                            $weight = $infos['weight'];
                            $datalist = $infos['datalist'];
//                        $int_cold_num=$infos['int_cold_num'];
//                        $int_freeze_num=$infos['int_freeze_num'];
//                        $int_normal_num=$infos['int_normal_num'];
                        }


                    } else {
                        $xiugai['quehuo'] = 'Y';
                        $xiugai['quehuo_num'] = $v['num'];
                        $infos = self::dataInsert($xiugai, $totalId, $v, $resssss, $now_time, $user_info, $change, $pull, $bulk, $weight, $datalist);
                        $pull = $infos['pull'];
                        $bulk = $infos['bulk'];
                        $weight = $infos['weight'];
                        $datalist = $infos['datalist'];
                    }


                }

                $pull = array_unique($pull);
                $pull_count = count($pull);
                $data['pull_count'] = $pull_count;
                $data['bulk'] = $bulk;
                $data['weight'] = $weight;
//            $data['int_cold'] = $int_cold_num;
//            $data['int_freeze'] = $int_freeze_num;
//            $data['int_normal'] = $int_normal_num;
                WmsOutOrder::whereIn('self_id', $total)->update($temp);
                $id = WmsTotal::insert($data);
                $msg['code']=200;

                $where_pack2=[
                    ['delete_flag','=','Y'],
                    ['self_id','=', $data["company_id"]],
                ];
                $company_select=['self_id','company_name',
                    'preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];

                $company_info = WmsGroup::where($where_pack2)->select($company_select)->first();

                $money->moneyCompute($data,$datalist,$now_time,$company_info,$user_info,'total');

                DB::commit();
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共总拣了'.$count.'个订单';

                return $msg;
            }catch (\Exception $e){
                DB::rollBack();
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }
			$operationing->table_id=$data['self_id'];
            $operationing->old_info=null;
            $operationing->new_info=$data;

            /** 这里回数据出来***/
            if($id){

            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }

        }else{
            $msg['code']=300;
            $msg['msg']='没有订单需要总拣，请检查';
            return $msg;
        }


    }

    public static function dataInsert($xiugai,$total_id,$data,$resssss,$now_time,$user_info,$change,$pull,$bulk,$weight,$datalist){

        $xiugai["update_time"]            =$now_time;

        $where['self_id']                 =$data['self_id'];
        WmsOutOrderList::where($where)->update($xiugai);
        $wms_out_sige=[];
        $wms_library_sige=[];
        $wms_library_change=[];
        $number=$data['num'];

        /*** 出库的时候，需要写那几个地方，第一个地方，需要写入
         *  wms_out_sige     需要写入wms_library_change
         **  需要改变  wms_library_sige  中的实际库存
         */
        $int_cold_num = 0;
        $int_freeze_num = 0;
        $int_normal_num = 0;
	//dump($data);dd($resssss);
		if($resssss){
			foreach($resssss as $kk=>$vv){
				if($number > 0){
					//dd($vv);
					if($number - $vv['now_num']  > 0){
						$shiji_number=$vv['now_num'];

					}else{
						$shiji_number=$number;
					}
					//dd($vv);
					/** wms_out_sige   表的数据制作**/
					$outSigeId=generate_id("SOUTID_");
					$out['self_id']             =$outSigeId;
					$out['order_id']            =$data['order_id'];
					$out['order_list_id']       =$data['self_id'];
					$out['total_id']            =$total_id;
					$out['library_sige_id']     =$vv['self_id'];
					$out['warehouse_sign_id']   =$vv['warehouse_sign_id'];
					$out['num']                 =$shiji_number;
					$out['sku_id']              =$vv['sku_id'];
                    $out['external_sku_id']     =$vv['external_sku_id'];
					$out['production_date']     =$vv['production_date'];
					$out['expire_time']         =$vv['expire_time'];
					$out['good_name']           =$vv['good_name'];
                    $out['good_english_name']   =$vv['good_english_name'];
                    $out['good_info']           =$vv['good_info'];
					$out['spec']                =$vv['spec'];
                    $out['good_target_unit']    =$vv['good_target_unit'];
                    $out['good_scale']          =$vv['good_scale'];
                    $out['good_unit']           =$vv['good_unit'];
					$out['group_code']          =$vv['group_code'];
					$out['group_name']          =$vv['group_name'];
					$out['warehouse_id']        =$vv['warehouse_id'];
					$out['warehouse_name']      =$vv['warehouse_name'];
                    $out['company_id']          =$vv['company_id'];
                    $out['company_name']        =$vv['company_name'];
					$out['shiji_num']           =$shiji_number;
                    $out['area_id']             =$vv['area_id'];
					$out['area']                =$vv['area'];
					$out['row']                 =$vv['row'];
					$out['column']              =$vv['column'];
					$out['tier']                =$vv['tier'];
                    $out['wms_length']          =$vv['wms_length'];
                    $out['wms_wide']            =$vv['wms_wide'];
                    $out['wms_high']            =$vv['wms_high'];
                    $out['wms_weight']          =$vv['wms_weight'];
					$out['create_user_id']      =$user_info->admin_id;
					$out["create_user_name"]    =$user_info->name;
					$out["create_time"]         =$now_time;
					$out["update_time"]         =$now_time;
                    $out['bulk']                =$vv['wms_length']*$vv['wms_wide']*$vv['wms_high']*$shiji_number;
                    $out['weight']              =$vv['wms_weight']*$shiji_number;
                    $out['price']               =$data['price'];
                    $out['total_price']         =$data['price']*$shiji_number;

					$out['shop_id']              =$data['shop_id'];
					$out['shop_name']            =$data['shop_name'];

					$wms_out_sige[]=$out;
					/** wms_out_sige   表的数据制作**/
					$library_sige['self_id']=$vv['self_id'];
					$library_sige['yuan_num']=$vv['now_num'];
					$library_sige['chuku_number']=$shiji_number;
					$wms_library_sige[]=$library_sige;

					/** wms_library_change   表的数据制作**/

					//DUMP($wms_out_sige);
					$library_change=$out;
					$library_change['initial_num']          =$vv['now_num'];
					$library_change['create_user_id']       =$user_info->admin_id;
					$library_change["create_user_name"]     =$user_info->name;
					$library_change["create_time"]          =$now_time;
					$library_change["update_time"]          =$now_time;
					$library_change["good_lot"]             =$vv['good_lot'];

                    //DD($library_change);
					$wms_library_change[]=$library_change;

                    $pull[]=$vv['warehouse_sign_id'];
                    $bulk+=  $vv['wms_length']*$vv['wms_wide']*$vv['wms_high']*$number;
                    $weight+=  $vv['wms_weight']*$number;

                    $xxx=$out;
                    $xxx['now_num']=$vv['now_num']-$number;
                    $datalist[]=$xxx;

                    $number -=  $vv['now_num'];
				}

			}

			WmsOutSige::insert($wms_out_sige);

			$change->change($wms_library_change,'out');
			foreach ($wms_library_sige as $k => $v){
				$where21['self_id']=$v['self_id'];

                $librarySignUpdate['now_num']           =$v['yuan_num']-$v['chuku_number'];
				$librarySignUpdate['update_time']       =$now_time;
				WmsLibrarySige::where($where21)->update($librarySignUpdate);
			}

		}

        $infos['pull']=$pull;
        $infos['bulk']=$bulk;
        $infos['weight']=$weight;
        $infos['datalist']=$datalist;
//        $infos['int_cold_num']=$int_cold_num;
//        $infos['int_freeze_num']=$int_freeze_num;
//        $infos['int_normal_num']=$int_normal_num;
        return $infos;

    }
    /***    总拣单详情      /wms/total/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
       // $self_id='T_202012091038596508561505';

        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];


        $total_select=['self_id','create_user_name','create_time','group_name','warehouse_name','order_count','company_name'];
		$order_select = ['self_id','shop_name','total_id','delivery_time'];
        $order_list_select= ['self_id','good_name','spec','order_id','external_sku_id','quehuo','quehuo_num'];
		$wms_out_sige_select= ['total_id','shop_name','good_name','spec','order_list_id','external_sku_id','area_id','num','area','row','column','tier','production_date','expire_time','good_unit','good_target_unit','good_scale'];


		$info=WmsTotal::with(['wmsOutOrder' => function($query)use($order_select,$order_list_select){
			$query->where('delete_flag','=','Y');
			$query->select($order_select);
			$query->with(['wmsOutOrderList' => function($query)use($order_list_select){
				$query->select($order_list_select);
				$query->where('delete_flag','=','Y');
			}]);
		}])->with(['wmsOutSige' => function($query)use($wms_out_sige_select){
			$query->where('delete_flag','=','Y');
			$query->select($wms_out_sige_select);
			$query->orderBy('area','asc');
		}])->where($where)
		   ->select($total_select)->first();



        $out_list=[];
        $quhuo_list=[];
        //dd($info->ToArray());
        $data['quehuo_flag']        ='N';
        $data['out_flag']           ='N';
        if($info){
			$info->delivery_time=null;
            /** 此处是出库数据 以及  处理缺货数据**/
            $list=[];
            $list2=[];
			$info->delivery_time=null;
            foreach ($info->wmsOutOrder as $k => $v){
				//dd($v['delivery_time']);
				$info->delivery_time=$v['delivery_time'];
                //DUMP($v->ToArray());
                foreach ($v->wmsOutOrderList as $kk => $vv){
                    //DUMP($vv->ToArray());

                    if($vv->quehuo == 'Y'){
                        $data['quehuo_flag']        ='Y';
                        $list2['shop_name']          =$v->shop_name;
                        $list2['external_sku_id']    =$vv->external_sku_id;
                        $list2['good_name']          =$vv->good_name;
                        $list2['spec']               =$vv->spec;
                        $list2['num']                =$vv->quehuo_num;
                        $quhuo_list[]=$list2;
                    }
                }
            }

			if($info->wmsOutSige){
				$data['out_flag']           ='Y';
				foreach ($info->wmsOutSige as $kkk => $vvv){
					$list['shop_name']          =$vvv->shop_name;
					$list['external_sku_id']    =$vvv->external_sku_id;
					$list['good_name']          =$vvv->good_name;
					$list['spec']               =$vvv->spec;
					$list['num']                =$vvv->num;
					$list['sign']               =$vvv->area.'-'.$vvv->row.'-'.$vvv->column.'-'.$vvv->tier;
					$list['production_date']    =$vvv->production_date;
					$list['expire_time']        =$vvv->expire_time;
					$list['area']        		=$vvv->area;
					$list['good_describe']      =unit_do($vvv->good_unit , $vvv->good_target_unit, $vvv->good_scale, $vvv->num);

					$out_list[]=$list;

				}
			}

//dd($out_list);



            /** 此处是出库数据 以及  处理缺货数据  结束**/


            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $data['info']=$info;
            $data['out_list']=$out_list;
            $data['quhuo_list']=$quhuo_list;


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
        //dd($data);

            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }


    }
    /***    打印总拣单      /wms/total/totalPrint
     */
    public function  totalPrint(Request $request){


    }

    /***    打印分拣单      /wms/total/orderPrint
     */
    public function  orderPrint(Request $request){

		$operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='null';

        $operationing->access_cause='打印分拣单';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
		$operationing->now_time=$now_time;


        $self_id=$request->input('self_id');
        //$self_id='T_202012091038596508561505';
		//$now_time           =date('Y-m-d H:i:s',time());
        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];


        $total_select=['self_id','create_user_name','create_time','group_name','warehouse_name','order_count','company_name','company_id'];
        $order_select = ['self_id','shop_name','total_id','shop_external_id','shop_id','create_time','total_time','delivery_time','warehouse_name','shop_address','shop_contacts','shop_tel','company_name'];
        $order_list_select= ['self_id','good_name','spec','order_id','external_sku_id','quehuo','quehuo_num','recipt_code','shop_code'];
        $wms_out_sige_select= ['total_id','area_id','order_id','order_list_id','sku_id','num','area','area_id','good_name','external_sku_id','spec','good_english_name','row','column','tier','production_date','expire_time','good_unit','good_target_unit','good_scale','shop_id','shop_name','price','total_price'];
        $group_select = ['self_id','group_code','group_name','use_flag','company_name','contacts','address','tel','total_price','pay_type'];
        $good_select = ['self_id','external_sku_id','sale_price'];
        $shop_select = ['self_id','line_code','contacts_code','external_id','name'];
		/**
        $info=WmsTotal::with(['wmsOutOrder' => function($query)use($order_select,$order_list_select,$wms_out_sige_select){
            $query->where('delete_flag','=','Y');
            $query->select($order_select);
            $query->with(['wmsOutOrderList' => function($query)use($order_list_select,$wms_out_sige_select){
                $query->select($order_list_select);
                $query->where('delete_flag','=','Y');
                $query->with(['wmsOutSige' => function($query)use($wms_out_sige_select){
                    $query->select($wms_out_sige_select);
                    $query->where('delete_flag','=','Y');
					$query->orderBy('area_id','desc');
                }]);
            }]);
        }])->where($where)
            ->select($total_select)->first();
		*/
        $tms_control_type        =array_column(config('tms.tms_control_type'),'name','key');
		$info=WmsTotal::with(['wmsOutOrder' => function($query)use($order_select,$order_list_select,$wms_out_sige_select,$good_select,$group_select,$shop_select){
			$query->where('delete_flag','=','Y');
			$query->select($order_select);
            $query->with(['wmsOutOrderList' => function($query)use($order_list_select){
                $query->select($order_list_select);
                $query->where('delete_flag','=','Y');
            }]);
            $query->with(['wmsShop' => function($query)use($shop_select){
                $query->select($shop_select);
                $query->where('delete_flag','=','Y');
            }]);
			$query->with(['wmsOutSige' => function($query)use($wms_out_sige_select,$good_select){
				$query->where('delete_flag','=','Y');
				$query->select($wms_out_sige_select);
				$query->orderBy('area','asc')
                ->with(['wmsGoods' => function($query)use($good_select){
                    $query->where('delete_flag','=','Y');
                    $query->select($good_select);
                }]);
			}]);
		}])
            ->with(['wmsGroup' => function($query)use($group_select){
                $query->where('delete_flag','=','Y');
                $query->select($group_select);
            }])
            ->where($where)
		   ->select($total_select)->first();

//dd($info->toArray());


        if($info){

            $out_list=[];

            foreach ($info->wmsOutOrder as $k => $v){

                $quhuo=[];
                $abc=[];
                //DUMP($v->ToArray());
                foreach ($v->wmsOutOrderList as $kk => $vv){
                   // DUMP($vv->ToArray());
                    $abc['order_id']=$v->self_id;
                    $abc['shop_external_id']=$v->shop_external_id;
                    $abc['shop_name']=$v->shop_name;
                    $abc['create_time']=$v->create_time;
                    $abc['total_time']=$v->total_time;
					$abc['delivery_time']=$v->delivery_time;
					$abc['recipt_code']=$vv->recipt_code;
					$abc['shop_code']=$vv->shop_code;
					$abc['shop_address']=$v->shop_address;
					$abc['contact_tel']=$v->shop_contacts.'  '.$v->shop_tel;
					$abc['pay_type']=$info->wmsGroup->pay_type;
					$abc['warehouse_name']=$v->warehouse_name;
					$abc['company_name']=$v->company_name;
					$abc['line_code']=$v->wmsShop->line_code;
					$abc['shop_num']=$v->wmsShop->external_id;

//dump($abc);
                    if($vv->quehuo == 'Y'){
                        $list2['external_sku_id']    =$vv->external_sku_id;
                        $list2['good_name']          =$vv->good_name;
                        $list2['spec']               =$vv->spec;
                        $list2['num']                =$vv->quehuo_num;


                        $quhuo[]=$list2;
                        $abc['quhuo']=$quhuo;
                        $abc['quhuo_flag']='Y';
                    }else{
                        $abc['quhuo']=null;
                        $abc['quhuo_flag']='N';
                    }

                }
                $int_cold_num = 0;
                $int_freeze_num = 0;
                $int_normal_num = 0;

				if($v->wmsOutSige){
				 $abc['out_flag']='Y';
				 $order=[];
					foreach ($v->wmsOutSige as $kkk => $vvv){
						$list['shop_name']          =$vvv->shop_name;
						$list['external_sku_id']    =$vvv->external_sku_id;
						$list['good_name']          =$vvv->good_name;
						$list['good_english_name']  =$vvv->good_english_name;
						$list['spec']               =$vvv->spec;
						$list['num']                =$vvv->num;
                        $warehouseType = WmsWarehouseArea::with(['wmsWarm' => function ($query){
                            $query->select(['self_id','control']);
                        }])->where('self_id',$vvv->area_id)->select(['self_id','warm_id'])->first();
                        if ($warehouseType->wmsWarm->control == 'freeze'){
                            if($vvv->good_unit == '箱'){
                                $int_cold_num += $vvv->num;
                            }
                        }elseif ($warehouseType->wmsWarm->control == 'refrigeration'){
                            if($vvv->good_unit == '箱'){
                                $int_freeze_num += $vvv->num;
                            }
                        }elseif($warehouseType->wmsWarm->control == 'normal'){
                            if($vvv->good_unit == '箱'){
                                $int_normal_num += $vvv->num;
                            }
                        }
						$list['good_unit']          =$vvv->good_unit;
						$list['sign']               =$vvv->area.'-'.$vvv->row.'-'.$vvv->column.'-'.$vvv->tier;
						$list['production_date']    =$vvv->production_date;
						$list['expire_time']        =$vvv->expire_time;
						$list['good_describe']      =unit_do($vvv->good_unit , $vvv->good_target_unit, $vvv->good_scale, $vvv->num);
						$list['price']              =$vvv->price;
						$list['total_money']        =(float)$vvv->price * $vvv->num;
                        $list['control']            = $tms_control_type[$warehouseType->wmsWarm->control]?? null;
						$order[]=$list;
						$abc['info']=$order;

					}
                    $abc['int_cold']=$int_cold_num;
                    $abc['int_freeze']=$int_freeze_num;
                    $abc['int_normal']=$int_normal_num;
					$out_list[]=$abc;

				}else{
					$abc['out_flag']='N';
					$abc['info']=$order;

				}

            }

			$operationing->table_id=$self_id;
            $operationing->old_info=null;
            $operationing->new_info=null;



            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$out_list;
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }


    }


}

