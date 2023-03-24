<?php
namespace App\Http\Admin\Wms;
use App\Http\Controllers\WmschangeController as Change;
use App\Models\Tms\TmsMoney;
use App\Models\Wms\WmsLibraryChange;
use App\Models\Wms\WmsOutSige;
use App\Models\Wms\WmsTotal;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Models\Wms\WmsOutOrder;
use App\Models\Wms\WmsLibrarySige;
use App\Models\Wms\WmsOutOrderList;
use App\Models\Wms\WmsGroup;
use App\Models\Wms\WmsWarehouse;
use App\Models\Wms\WmsShop;
use App\Models\Shop\ErpShopGoodsSku;
use App\Http\Controllers\WmsMoneyController as WmsMoney;

class OrderController extends CommonController{
    /***    出库订单列表      /wms/order/orderList
     */
    public function  orderList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='出库订单';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/出库导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    出库订单分页     /wms/order/orderPage
     */
    public function orderPage(Request $request){
        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num                = $request->input('num') ?? 10;
        $page               = $request->input('page') ?? 1;
        $use_flag           = $request->input('use_flag');
        $warehouse_name     = $request->input('warehouse_name');
        $status             = $request->input('status');
        $total_flag         = $request->input('total_flag');
        $picker             = $request->input('picker');
        $car_number         = $request->input('car_number');
        $start_time         = $request->input('start_time');
        $end_time           = $request->input('end_time');
        $good_name          = $request->input('good_name');
        $spec               = $request->input('spec');
        $number             = $request->input('number');
        $listrows           = $num;
        $firstrow           = ($page - 1) * $listrows;

        $search = [
            ['type' => '=', 'name' => 'delete_flag', 'value' => 'Y'],
            ['type' => 'all', 'name' => 'use_flag', 'value' => $use_flag],
            ['type'=>'all','name'=>'total_flag','value'=>$total_flag],
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'=','name'=>'status','value'=>$status],
            ['type'=>'like','name'=>'picker','value'=>$picker],
            ['type'=>'=','name'=>'car_num','value'=>$car_number],
            ['type'=>'>=','name'=>'out_time','value'=>$start_time],
            ['type'=>'<','name'=>'out_time','value'=>$end_time],
        ];

        $where = get_list_where($search);

        $select = ['self_id','status','count','total_flag','create_time','create_user_name','out_time',
            'fuhe_flag','file_id','warehouse_name','group_name','picker','operator','purchase','car_num'];
        $order_list_select= ['self_id','good_name','good_unit','spec','num','order_id','external_sku_id','remarks','price','total_price','out_library_state'];
        switch ($group_info['group_id']) {
            case 'all':
                $data['total'] = WmsOutOrder::where($where)->count(); //总的数据量
                $data['items'] = WmsOutOrder::with(['wmsOutOrderList'=>function($query)use($order_list_select,$good_name,$spec,$number){
                    $query->where('delete_flag','=','Y');
                    if ($good_name){
                        $query->where('good_name','like',$good_name);
                    }
                    if ($spec){
                        $query->where('spec','=',$spec);
                    }
                    if ($number){
                        $query->where('num','=',$number);
                    }
                    $query->select($order_list_select);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show'] = 'Y';
                break;

            case 'one':
                $where[] = ['group_code', '=', $group_info['group_code']];
                $data['total'] = WmsOutOrder::where($where)->count(); //总的数据量
                $data['items'] = WmsOutOrder::with(['wmsOutOrderList'=>function($query)use($order_list_select,$good_name,$spec,$number){
                    if ($good_name){
                        $query->where('good_name','like',$good_name);
                    }
                    if ($spec){
                        $query->where('spec','=',$spec);
                    }
                    if ($number){
                        $query->where('num','=',$number);
                    }
                    $query->where('delete_flag','=','Y');
                    $query->select($order_list_select);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show'] = 'N';
                break;

            case 'more':
                $data['total'] = WmsOutOrder::where($where)->whereIn('group_code', $group_info['group_code'])->count(); //总的数据量
                $data['items'] = WmsOutOrder::with(['wmsOutOrderList'=>function($query)use($order_list_select,$good_name,$spec,$number){
                    $query->where('delete_flag','=','Y');
                    if ($good_name){
                        $query->where('good_name','like',$good_name);
                    }
                    if ($spec){
                        $query->where('spec','=',$spec);
                    }
                    if ($number){
                        $query->where('num','=',$number);
                    }
                    $query->select($order_list_select);
                }]) ->where($where)->whereIn('group_code', $group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show'] = 'Y';
                break;
        }

//dd($data);

        foreach ($data['items'] as $k => $v) {
			$v->status_show=null;
            $v->button_info = $button_info;
			switch ($v->status) {
				case '1':
				$v->status_show='待出库';
					break;

				case '2':
				$v->status_show='未完成';
					break;

				case '3':
				$v->status_show='已完成';
					break;
			}

        }
        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        //dd($msg);
        return $msg;

    }

    /**
     * 手动添加出库订单
     * */
    public function outOrder(Request $request,Change $change){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_out_order';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='创建出库订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        /** 接收数据*/
        $input              = $request->all();
        $warehouse_id       = $request->input('warehouse_id');
        $goods              = json_decode($request->input('goods'),true);
        $car_num            = $request->input('car_num');
        $user_id            = $request->input('user_id');
        $picker             = $request->input('picker');
        $operator           = $request->input('operator');
        $purchase           = $request->input('purchase');
        $out_time           = $request->input('out_time');
        $self_id            = $request->input('self_id');
        $good_list          = json_decode($request->input('good_list'),true);//产品ID集合
        $group_code         = $request->input('group_code');
        $group_name         = $request->input('group_name');
        $bill_flag          = $request->input('bill_fiag');


        /***
        $input['warehouse_id'] = $warehouse_id = 'warehouse_20221215135058787296124';
        $input['car_num'] = $car_num = '485615612';
        $input['picker'] = $picker = '12345';
        $input['operator'] = $operator = '1234';
        $input['purchase'] = $purchase = '123';
        $input['goods']=$goods=[
        '0'=>[
        'sku_id'=>'sku_202212171207283772516693',
        'can_use'=>'Y',
        'num'=>29,
        'wms_unit'=>'',
        'price'=>'200',
        'total_price'=>6200,
        'remark'=>'0.2',
        'out_library_state'=>'',

        ],
        ];
         * **/
        $rules = [
            'goods' => 'required',
            'bill_fiag' => 'required',
        ];
        $message = [
            'goods.required' => '请选择要出库的产品',
            'bill_fiag.required' => '请选择有无发票',
        ];
        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            $where_warehouse=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warehouse_id],
            ];

            $warehouse_info = WmsWarehouse::where($where_warehouse)->select('self_id','warehouse_name','group_name','group_code')->first();
            if(empty($warehouse_info)){
                $msg['code'] = 302;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }
            $order_id = generate_id('order_');
            $datalist = [];
            $order_2['count']               =count($goods);
            $order_2['warehouse_id']        =$warehouse_info->self_id;
            $order_2['warehouse_name']      =$warehouse_info->warehouse_name;
            $order_2['car_num']             =$car_num;
            $order_2['picker']              =$picker;
            $order_2['operator']            =$operator;
            $order_2['purchase']            =$purchase;
            $order_2['out_time']            =$out_time;
            $order_2['status']              =2;
            $order_2['bill_flag']           =$bill_flag;

            DB::beginTransaction();
            try{
                /***有产品信息 说明是编辑 先删除之前添加的数据 **/
                if (count($good_list)>0){
                    $order_update['delete_flag'] = 'N';
                    $order_update['update_time'] = $now_time;
                    WmsOutOrderList::where('order_id',$self_id)->update($order_update);
                    TmsMoney::where('order_id',$self_id)->update($order_update);
                }
                /***处理本次添加或修改的产品数据**/
                $list=[];
                foreach($goods as $k =>$v){
                    $where_sku=[
                        ['delete_flag','=','Y'],
                        ['self_id','=',$v['sku_id']],
                    ];
                    $select_ErpShopGoodsSku=['self_id','group_code','group_name','external_sku_id','wms_unit','good_name','wms_spec'];
                    $sku_info = ErpShopGoodsSku::where($where_sku)->select($select_ErpShopGoodsSku)->first();

                    //dd($vv);
                    $list['self_id']            =generate_id('list_');
                    $list['good_name']          = $sku_info->good_name;
                    $list['spec']               = $sku_info->wms_spec;
                    $list['num']                = $v['num'];
                    $list['good_unit']          = $v['wms_unit'];
                    $list['group_code']         = $sku_info->group_code;
                    $list['group_name']         = $sku_info->group_name;
                    if ($self_id){
                        $list['order_id']           = $self_id;
                    }else{
                        $list['order_id']           = $order_id;
                    }

                    $list['sku_id']             = $sku_info->self_id;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        = $list['update_time']=$now_time;
                    $list['price']              = $v['price'];
                    $list['total_price']        = $v['total_price'];
                    $list['remarks']            = $v['remark'];
                    $list['out_time']           = $out_time;
//                $list['out_library_state']  = $v['out_library_state'];
                    $datalist[]=$list;

                    /***保存费用**/
                    if ($bill_flag == 'Y'){
                        $money['pay_type']           = 'delivery_fee';
                        $money['money']              = $v['total_price'];
                        $money['pay_state']          = 'Y';
                        $money['order_id']           = $order_id;
//                    $money['car_id']             = $car_id;
                        $money['car_number']         = $car_num;
                        $money['user_id']            = $user_id;
                        $money['user_name']          = $picker;
                        $money['process_state']      = 'Y';
                        $money['type_state']         = 'out';
                        $money['self_id']            = generate_id('money');
                        $money['group_code']         = $group_code;
                        $money['group_name']         = $group_name;
                        $money['create_user_id']     = $user_info->admin_id;
                        $money['create_user_name']   = $user_info->name;
                        $money['create_time']       =$money['update_time']=$now_time;
                        $moneylist[]=$money;
                    }

                }
                $wheres['self_id'] = $self_id;
                $old_info=WmsOutOrder::where($wheres)->first();

                if ($old_info){
                    $order_2['update_time']=$now_time;
                    $id=WmsOutOrder::where($wheres)->update($order_2);

                    $operationing->access_cause='修改出库信息';
                    $operationing->operation_type='update';
                }else{
                    $order_2['self_id']             =$order_id;
                    $order_2['group_code']          =$group_code;
                    $order_2['group_name']          =$group_name;
                    $order_2['create_user_id']      =$user_info->admin_id;
                    $order_2['create_user_name']    =$user_info->name;
                    $order_2['create_time']         =$order_2['update_time']            =$now_time;
                    $id=WmsOutOrder::insert($order_2);

                    $operationing->access_cause='添加出库信息';
                    $operationing->operation_type='add';
                }
                if($id){
                    WmsOutOrderList::insert($datalist);
                    if ($bill_flag == 'Y'){
                        TmsMoney::insert($moneylist);
                    }

                    DB::commit();
                    $msg['code']=200;
                    $msg['msg']='操作成功!';
                    return $msg;
                }else{
                    DB::rollBack();
                    $msg['code']=301;
                    $msg['msg']='操作失败';
                    return $msg;
                }
            }catch(\Exception $e){
                DB::rollBack();
                $msg['code'] = '300';
                $msg['msg']  = '操作失败！';
                return $msg;
            }
        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }
    }


    /***    出库订单导入      /wms/order/import
     */
    public function import(Request $request){

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_out_order';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建出库订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $company_id         =$request->input('company_id');
        $warehouse_id       =$request->input('warehouse_id');
        $file_id            =$request->input('file_id');


        /****虚拟数据
        $input['importurl']         =$importurl="uploads/2020-11-21/出库导入文件范本.xlsx";
        $input['company_id']        =$company_id='group_202011201723168962577585';
        $input['warehouse_id']      =$warehouse_id='warehouse_20201120171042632802462';
		***/
        $rules = [
            'company_id' => 'required',
            'warehouse_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'company_id.required' => '请选择公司',
            'warehouse_id.required' => '请选择公司',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
            $res = Excel::toArray((new Import),$importurl);
			//dd($res);
            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }
            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */

            $shuzu=[
                '往来单位编码' =>['N','Y','64','shop_code'],
                '单据编号' =>['N','Y','64','recipt_code'],
                '门店编码' =>['Y','Y','64','shop_external_id'],
                '门店简称' =>['N','Y','255','shop_name'],
                '商品编码' =>['Y','Y','255','external_sku_id'],
                '商品名称' =>['N','Y','255','good_name'],
                '数量' =>['Y','Y','255','num'],
                '含税单价' =>['N','Y','255','price'],
                '含税金额' =>['N','Y','255','total_price'],
                '卫检日期' =>['N','Y','255','sanitation'],
				'发货日期' =>['N','Y','255','delivery_time'],
            ];
            $ret=arr_check($shuzu,$info_check);
			//dd($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];


            $where_company=[
                ['delete_flag','=','Y'],
                ['self_id','=',$company_id],
            ];
            $group_info = WmsGroup::where($where_company)->select('company_name','group_name','group_code')->first();
            //dump($group_info);
            if(empty($group_info)){
                $msg['code'] = 302;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            //dump($group_info);

            $where_warehouse=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warehouse_id],
            ];

            $warehouse_info = WmsWarehouse::where($where_warehouse)->select('self_id','warehouse_name','group_name','group_code')->first();
            if(empty($warehouse_info)){
                $msg['code'] = 302;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }

            /** 二次效验结束**/
            $orderdata=[];       //初始化数组为空
            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            /** 现在开始处理$car***/
            //通过门店拆分订单

            $order_check    =array_column($info_wait,'shop_external_id');
            $order_num    =array_column($info_wait,'shop_code','shop_external_id');
			$last_names		=array_flip(array_unique($order_check));

//			dump(array_unique($order_check));dump($last_names);
			$order_check    =array_count_values($order_check);
//            dd($order_check);
            foreach ($last_names as $key =>$value){
                $where_shop1=[
                    ['delete_flag','=','Y'],
                    ['external_id','=',$key],
                    ['company_id','=',$company_id],
                ];
                $select_wmsShop1=['self_id','group_code','external_id','name','contacts','address','tel','group_name','company_id','company_name'];
                $shop_info2 = wmsShop::where($where_shop1)->select($select_wmsShop1)->first();
                if(empty($shop_info2)){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.($value+2)."行门店编码不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
            }

            foreach($order_num as $k => $v){
                $where_shop=[
                    ['delete_flag','=','Y'],
                    ['external_id','=',$k],
                    ['contacts_code','=',$v],
                    ['company_id','=',$company_id],
                ];
//                dump($where_shop);
                $select_wmsShop=['self_id','group_code','external_id','name','contacts','address','tel','delete_flag','group_name','company_id','company_name','contacts_code'];
                $shop_info = wmsShop::where($where_shop)->select($select_wmsShop)->first();
                if($cando == 'Y'){
                    $order_2=[];
                    $order_2['self_id']             =generate_id('order_');
                    $order_2['shop_id']             =$shop_info['self_id'];
                    $order_2['shop_external_id']    =$shop_info['external_id'];
                    $order_2['shop_name']           =$shop_info['name'];
                    $order_2['shop_contacts']       =$shop_info['contacts'];
                    $order_2['shop_address']        =$shop_info['address'];
                    $order_2['shop_tel']            =$shop_info['tel'];
                    $order_2['group_code']          =$shop_info['group_code'];
                    $order_2['group_name']          =$shop_info['group_name'];
                    $order_2['count']               =$order_check[$k];
                    $order_2['warehouse_id']        =$warehouse_info->self_id;
                    $order_2['warehouse_name']      =$warehouse_info->warehouse_name;
                    $order_2['company_id']          =$shop_info['company_id'];
                    $order_2['company_name']        =$shop_info['company_name'];
                    $order_2['create_user_id']      =$user_info->admin_id;
                    $order_2['create_user_name']    =$user_info->name;
                    $order_2['create_time']         =$order_2['update_time']            =$now_time;
                    $order_2['file_id']             =$file_id;
                    $orderdata[$k]=$order_2;
                }
            }

//			dump($last_names);
//			dump($order_check);
//			DUMP($info_wait);
//			dd($orderdata);

            if($cando == 'Y'){
                foreach($info_wait as $k => $v){
					//dd($v);
                    //效验商品是不是存在
                    $where_sku=[
                        ['delete_flag','=','Y'],
                        ['external_sku_id','=',$v['external_sku_id']],
                        ['company_id','=',$company_id],
                    ];
                    $select_ErpShopGoodsSku=['self_id','group_code','group_name','external_sku_id','wms_unit','good_name','wms_spec'];
                    $sku_info = ErpShopGoodsSku::where($where_sku)->select($select_ErpShopGoodsSku)->first();

                    if(empty($sku_info)){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行商品编号不存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
//                    dump($shop_info);
//                    dump($sku_info);
                    $list=[];
                    if($cando =='Y'){
                        //dd($vv);
                        $list['self_id']            =generate_id('list_');
                        $list['shop_id']            = $orderdata[$v['shop_external_id']]['shop_id'];
                        $list['shop_name']          = $orderdata[$v['shop_external_id']]['shop_name'];
                        $list['good_name']          = $sku_info->good_name;
                        $list['spec']               = $sku_info->wms_spec;
                        $list['num']                = $v['num'];
                        $list['good_unit']          = $sku_info->wms_unit;
                        $list['group_code']         = $sku_info->group_code;
                        $list['group_name']         = $sku_info->group_name;
                        $list['order_id']           = $orderdata[$v['shop_external_id']]['self_id'];
                        $list['sku_id']             = $sku_info->self_id;
                        $list['external_sku_id']    = $sku_info->external_sku_id;
                        $list['create_user_id']     = $user_info->admin_id;
                        $list['create_user_name']   = $user_info->name;
                        $list['create_time']        =$list['update_time']=$now_time;
                        $list['sanitation']         = $v['sanitation'];
                        $list['recipt_code']        = $v['recipt_code'];
                        $list['shop_code']          = $v['shop_code'];
                        $list['price']              = $v['price'];
                        $list['total_price']        = $v['total_price'];
                        $list['out_library_state']        = 'normal';
//                        $list['int_cold']           = $v['int_cold'];
//                        $list['int_freeze']         = $v['int_freeze'];
//                        $list['int_normal']         = $v['int_normal'];
//                        $list['scattered_cold']     = $v['scattered_cold'];
//                        $list['scattered_freeze']   = $v['scattered_freeze'];
//                        $list['scattered_normal']   = $v['scattered_normal'];
						//$list['expect_date']        = $v['expect_date'];

                        $datalist[]=$list;

                    }

                    $a++;
                }
            }
            $operationing->new_info=$orderdata;

            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($orderdata);
            WmsOutOrderList::insert($datalist);
            $id= WmsOutOrder::insert($orderdata);


            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }

        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }

    }


    /***    出库订单详情      /wms/order/details
     */
    public function details(Request $request){
        $self_id=$request->input('self_id');
//        $self_id='order_202012221122071857614366';
        $out_store_status  =array_column(config('wms.out_store_status'),'name','key');
        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];
        $order_select = ['self_id','status','create_user_name','create_time','group_name','warehouse_name','total_flag','total_time',
            'picker','operator','purchase','car_num','delivery_time','out_time'];
        $order_list_select= ['self_id','good_name','good_unit','spec','num','order_id','external_sku_id','remarks','price','total_price','out_library_state'];
        $wms_out_sige_select= ['order_list_id','num','good_unit','good_target_unit','good_scale','good_english_name'];

        $info=wmsOutOrder::with(['wmsOutOrderList'=>function($query)use($order_list_select,$wms_out_sige_select){
            $query->where('delete_flag','=','Y');
            $query->select($order_list_select);
            $query->with(['wmsOutSige' => function($query)use($wms_out_sige_select){
                $query->select($wms_out_sige_select);
                $query->where('delete_flag','=','Y');
            }]);
        }])->where($where)
        ->select($order_select)->first();

        if($info){
            $list=[];
            $list2=[];
            $quhuo_list=[];
            $out_list=[];
            foreach ($info->wmsOutOrderList as $k=>$v){
                    $v->out_library_state_show  = $out_store_status[$v->out_library_state] ?? null;
                if($v->quehuo == 'Y'){
                    $data['quehuo_flag']         ='Y';
                    $list2['external_sku_id']    =$v->external_sku_id;
                    $list2['good_name']          =$v->good_name;
                    $list2['spec']               =$v->spec;
                    $list2['num']                =$v->quehuo_num;
                    $list2['good_unit']          =$v->good_unit;
                    $quhuo_list[]=$list2;
                }
                //dd($v->toArray());
                if($v->wmsOutSige){
                    $data['out_flag']        ='Y';
                    foreach ($v->wmsOutSige as $kk => $vv){
                        $list['external_sku_id']    =$v->external_sku_id;
                        $list['good_name']          =$v->good_name;
						$list['good_english_name']  =$vv->good_english_name;
                        $list['spec']               =$v->spec;
                        $list['num']                =$vv->num;
                        $list['good_unit']          =$vv->good_unit;
                        $list['sign']               =$vv->area.'-'.$vv->row.'-'.$vv->column.'-'.$vv->tier;
                        $list['good_describe']      =unit_do($vv->good_unit , $vv->good_target_unit, $vv->good_scale, $vv->num);
                        //dd($vv->toArray());
                        $out_list[]=$list;
                    }
                }

            }

            $data['info']=$info;
            $data['out_list']=$out_list;
            $data['quhuo_list']=$quhuo_list;
            $msg['code']=200;
            $msg['data']=$data;
            $msg['msg']="获取数据成功";
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

	}


    /***    出库订单      /wms/order/getOrder
     */
    public function getOrder(Request $request){
        /** 接收数据*/
        $company_id             =$request->input('company_id');


        /*** 虚拟数据
        $company_id             ='group_202011281508509934484447';
         ***/
        $where=[
            ['total_flag','=','Y'],
            ['status','=',1],
            ['company_id','=',$company_id],
            ['delete_flag','=','Y'],
        ];

        $select = ['self_id','status','count','total_flag','shop_id','shop_external_id','shop_name','create_time','create_user_name',
            'fuhe_flag','company_name','warehouse_name','group_name'];

        $data['info'] = WmsOutOrder::where($where)->orderBy('create_time', 'desc')->select($select)->get();

        //dd($data['info']->toArray());
        if($data['info']){
            $msg['code'] = 200;
            $msg['msg'] = "数据拉取成功";
            $msg['data'] = $data;
            return $msg;
        }else{
            $msg['code'] = 300;
            $msg['msg'] = "暂时没有需要出库的订单";
            //dd($msg);
            return $msg;
        }
    }

    /***    出库订单      /wms/order/statusOrder
     */
    public function statusOrder(Request $request,WmsMoney $money){
        $user_info               = $request->get('user_info');//接收中间件产生的参数
        $now_time                = date('Y-m-d H:i:s', time());
        /** 接收数据*/
        $company_id              =$request->input('company_id');
        $order_ids               =$request->input('order_ids');

		$table_name         ='wms_out_order';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='出库订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='update';
        $operationing->now_time         =$now_time;
		$operationing->type   			='piliang';


		//dump($company_id);dd($order_ids);
        /*** 虚拟数据
        $order_ids=['order_202012131040331581707991','group_202011281508509934484447'];
        $company_id='group_202011281508509934484447';
         **/

       // $total=array_column($order_ids,'self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['status','=',1],
        ];
        $select=['self_id','status','group_code','group_name','warehouse_id','warehouse_name','company_id','company_name'];

        $selectSku=['self_id','storage_number'];

        $order_info=WmsOutOrder::with(['wmsOutSige' => function($query)use($selectSku) {
            $query->where('delete_flag','=','Y');
            $query->with(['wmsLibrarySige' => function($query) use($selectSku) {
                $query->where('delete_flag','=','Y');
                $query->select($selectSku);
            }]);
        }])->where($where)->whereIn('self_id',$order_ids)
            ->select($select)->get()->toArray();



        //dd($order_info);
        //判断两个数组的长度
        if(count($order_ids) != count($order_info)){
            $msg['code']=302;
            $msg['msg']="您选择的订单中已经有订单出库，请核查";
            return $msg;
        }



        $where_pack2=[
            ['delete_flag','=','Y'],
            ['self_id','=', $company_id],
        ];
        $company_select=['self_id','company_name',
            'preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];

        $company_info = WmsGroup::where($where_pack2)->select($company_select)->first();
        //dump($company_info);
        if(empty($company_info)){
            $msg['code']=303;
            $msg['msg']="您选择的公司不存在";
            return $msg;
        }

//dump($order_info);
        if($order_info){
            $id=false;
            foreach ($order_info as $k => $v){

                //DD($v['wms_out_sige']);
                //查询需要出库的订单
               // dump($order_list->toArray());

                $datalist=[];

                //dd($order_list);
                if($v['wms_out_sige']){
                    $data['self_id']=$v['self_id'];
                    $data['status']=$v['status'];
                    $data['group_code']=$v['group_code'];
                    $data['group_name']=$v['group_name'];
                    $data['warehouse_id']=$v['warehouse_id'];
                    $data['warehouse_name']=$v['warehouse_name'];
                    $data['company_id']=$v['company_id'];
                    $data['company_name']=$v['company_name'];

                    $pull=[];
                    $bulk=0;
                    $weight=0;
                    foreach ($v['wms_out_sige'] as $kk => $vv){
                        $abc=$vv;
                        $pull[]=$vv['warehouse_sign_id'];
                        $bulk+=$vv['bulk'];
                        $weight+=$vv['weight'];


                        $abc['initial_num']=$abc['num'];
                        $abc['now_num']=$abc['num'];
                        //把锁定的数据从锁定表中去掉
                        if($vv['wms_library_sige']){

                            $where_sine['self_id']=$vv['wms_library_sige']['self_id'];
                            // DUMP($where_sine);
//                            //DUMP($vv);
                            $data_sine['storage_number']=$vv['wms_library_sige']['storage_number']-$vv['shiji_num'];
                            $data_sine['update_time']   =$now_time;
//
                            // DUMP($data_sine);
                            WmsLibrarySige::where($where_sine)->update($data_sine);
                        }

                        unset($abc['id']);
                        unset($abc['bulk']);
                        unset($abc['weight']);
                        unset($abc['num']);
                        unset($abc['order_list_id']);
                        unset($abc['shiji_num']);
                        unset($abc['total_id']);
                        unset($abc['wms_library_sige']);

                        $datalist[]=$abc;
                    }

                    $data['bulk']=$bulk;
                    $data['weight']=$weight;
                    $pull=array_unique($pull);
                    $pull_count=count($pull);
                    $data['pull_count']=$pull_count;

//                    dump($data);
//                    dump($datalist);
//                    dump($company_info);
//                    dump($user_info);
//                    dump($now_time);

                    $money->moneyCompute($data,$datalist,$now_time,$company_info,$user_info,'out');

                }



                $outdatt['status']        ='2';
                $outdatt['update_time']   =$now_time;

                $outdatt2['expect_date']   =$now_time;
                $outdatt2['update_time']   =$now_time;

                $where_outdatt=[
                    ['self_id','=',$v['self_id']],
                    ['delete_flag','=','Y'],
                    ['status','=',1],
                ];

                $where_outdatt_list=[
                    ['order_id','=',$v['self_id']],
                    ['delete_flag','=','Y'],
                ];

                $id=WmsOutOrder::where($where_outdatt)->update($outdatt);
                WmsOutOrderList::where($where_outdatt_list)->update($outdatt2);
            }

			$operationing->table_id=null;
            $operationing->old_info=$order_info;
            $operationing->new_info=$outdatt;


//            dd($order_ids);


            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }
        }else{
            $msg['code'] = 300;
            $msg['msg'] = "请勾选需要出库的订单";
            return $msg;
        }
    }


    /**
     * 删除出库订单 /wms/order/delOutOrder
     * */
    public function  delOutOrder(Request $request){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_out_order';
        $medol_name='WmsOutOrder';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';
        $wms_order = WmsOutOrder::where('self_id',$self_id)->first();
        $update['update_time'] = $now_time;
        $update['delete_flag'] = 'N';
        DB::beginTransaction();
        try{
            $res = WmsOutOrder::where('self_id',$self_id)->update($update);
            WmsOutOrderList::where('order_id',$self_id)->update($update);
            DB::commit();
            if ($res){
                $msg['code']=200;
                $msg['msg']='删除成功';
            }
        }catch (\Exception $e){
            DB::rollBack();
            $msg['code']=300;
            $msg['msg']='删除失败';
        }



        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$wms_order;
        $operationing->new_info=(object)$update;
        $operationing->operation_type=$flag;

        return $msg;
    }

    /**
     * 出库审核
     * */
    public function outOrderDone(Request $request,Change $change){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $user_info          = $request->get('user_info');                //接收中间件产生的参数
        $order_id=$request->input('self_id');
//        $order_id=["order_202212171611413592429427"];

        /**循环处理数据**/

            $where=[
                ['delete_flag','=','Y'],

            ];
            $select = ['self_id','status','count','total_flag','warehouse_id','warehouse_name','group_code','group_name','out_time'];
            $select2 = ['self_id','good_name','spec','num','order_id','sku_id','external_sku_id','price','total_price','out_time'];
            $order = WmsOutOrder::with(['wmsOutOrderList' => function($query) use($select2){
                $query->select($select2);
                $query->where('delete_flag','=','Y');
            }])->where($where)->whereIn('self_id',$order_id)
                ->select($select)->get()->toArray();
            if ($order){
                /**检查是否包含已审核数据**/
                $check=array_column($order,'total_flag');
                $order_check    =array_count_values($check);

                if(array_key_exists('Y', $order_check)){
                    $msg['code']=301;
                    $msg['msg']='选中的选项中包含了已审核订单，请检查';
                    return $msg;
                }

                /**做出库订单数据**/
                $count                          =count($order_id);
                $temp['total_flag']             ='Y';
                $temp['total_time']             = $temp['update_time'] = $now_time;
                $temp['status'] = 3;


                $order_do=[];
                foreach ($order as $k => $v){
                    if($v['wms_out_order_list']){
                        foreach ($v['wms_out_order_list'] as $kk => $vv){
                            $order_do[]=$vv;
                        }
                    }
                }

                DB::beginTransaction();
                try {
                    $datalist= [];
                    foreach ($order_do as $k => $v) {
                        dd($v);
                        $where2 = [
                                ['sku_id', '=', $v['sku_id']],
                                ['now_num', '>', 0],
                                ['can_use', '=', 'Y'],
                                ['delete_flag', '=', 'Y'],
//                                ['create_time', '>',$now_time]
//                                ['create_time', '>', substr($now_time, 0, -9)]
                        ];

                        $resssss = WmsLibrarySige::where($where2)->orderBy('create_time', 'asc')->get()->toArray();
                        if ($resssss) {
                            $totalNum = array_sum(array_column($resssss, 'now_num'));
                            $numds = $v['num'] - $totalNum;

                            if ($numds > 0) {
                                $msg['code']=301;
                                $msg['msg']='库存不足！';
                                return $msg;
//                                $infos = self::dataInsert($v,$resssss,$now_time,$user_info,$change,$datalist);
//                                $datalist = $infos['datalist'];

                            } else {

                                $infos = self::dataInsert($v,$resssss,$now_time,$user_info,$change,$datalist);
                                $datalist = $infos['datalist'];

                            }

                        } else {
                            $msg['code']=301;
                            $msg['msg']='暂时无货，请稍后重试！';
                            return $msg;
                        }
                    }

                    WmsOutOrder::whereIn('self_id', $order_id)->update($temp);
                    $data['use_flag'] = 'Y';
                    $data['update_time'] = $now_time;
                    WmsLibraryChange::whereIn('order_id',$order_id)->update($data);
                    DB::commit();
                    $msg['code']=200;
                    $msg['msg']='操作成功!';
                    return $msg;
                }catch (\Exception $e){
                    dd($e);
                    DB::rollBack();
                    $msg['code']=301;
                    $msg['msg']='操作失败';
                    return $msg;
                }

            }else{
                $msg['code']=300;
                $msg['msg']='没有订单需要总拣，请检查';
                return $msg;
            }

        $operationing->access_cause='出库审核';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=null;
        $operationing->new_info=(object)$update;
        $operationing->operation_type=$flag;

        return $msg;
    }

    public static function dataInsert($data,$resssss,$now_time,$user_info,$change,$datalist){

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
                    $out['library_sige_id']     =$vv['self_id'];
                    $out['warehouse_sign_id']   =$vv['warehouse_sign_id'];
                    $out['num']                 =$shiji_number;
                    $out['sku_id']              =$vv['sku_id'];
                    $out['external_sku_id']     =$vv['external_sku_id'];

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
                    $out['shiji_num']           =$shiji_number;
                    $out['create_user_id']      =$user_info->admin_id;
                    $out["create_user_name"]    =$user_info->name;
                    $out["create_time"]         =$now_time;
                    $out["update_time"]         =$now_time;

                    $out['price']               =$data['price'];
                    $out['total_price']         =$data['price']*$shiji_number;


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
                    $library_change["order_id"]             =$vv['good_lot'];

                    //DD($library_change);
                    $wms_library_change[]=$library_change;

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
        $infos['datalist']=$datalist;
        return $infos;

    }

    /**
     * 添加/编辑出库订单商品
     * */
    public function addOutorderSku(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_sige';

        $operationing->access_cause     ='添加/修改出库商品信息';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info        = $request->get('user_info');//接收中间件产生的参数
        $input            = $request->all();
        $self_id          = $request->input('self_id'); //商品列表self_id
        $order_id         = $request->input('order_id');// 大订单self_id
        $shop_id          = $request->input('shop_id');//门店self_id
        $shop_name        = $request->input('shop_name');//门店名称
        $external_sku_id  = $request->input('external_sku_id');//商品编号
        $good_name        = $request->input('good_name');//商品名称
        $num              = $request->input('num');//数量
        $name             = $request->input('name');//制单人
        $out_library_state= $request->input('out_library_state');//出库状态
        $sku_id           = $request->input('sku_id');//商品self_id
        $price            = $request->input('price');//金额
        $total_price      = $request->input('total_price');//总价
        $delivery_time    = $request->input('delivery_time');//发货时间
        $sanitation       = $request->input('sanitation');//卫检
        $remark           = $request->input('remark');//备注
        $wms_spec           = $request->input('wms_spec');//备注
        $group_code           = $request->input('group_code');//备注
        $group_name           = $request->input('group_name');//备注
        $rules = [

        ];
        $message = [

        ];
        $validator = Validator::make($input,$rules,$message);
        if($validator->passes()){
            $wheres['self_id'] = $self_id;
            $old_info=WmsOutOrderList::where($wheres)->first();

            $data['num'] = $num;
            $data['price'] = $price;
            $data['total_price'] = $num*$price;
//            $data['delivery_time'] = $delivery_time;
            $data['remarks'] = $remark;
            $data['out_library_state'] = $out_library_state;
            $data['sanitation'] = $sanitation;
            $data['spec'] = $wms_spec;
            if ($old_info){
                $data['update_time']=$now_time;
                $res = WmsOutOrderList::where('self_id',$self_id)->update($data);
//                $result =  WmsLibraryChange::where('order_id',$order_id)->where('external_sku_id',$external_sku_id)->update($data);
                $operationing->access_cause='修改货物信息';
                $operationing->operation_type='update';
            }else{
                $data['self_id'] = generate_id('list_');
                $data['order_id'] = $order_id;
                $data['shop_id'] = $shop_id;
                $data['shop_name'] = $shop_name;
                $data['good_name'] = $good_name;
                $data['external_sku_id'] = $external_sku_id;
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $name;
                $data['sku_id'] = $sku_id;
                $data['create_time'] = $data['update_time'] = $now_time;
                $data['group_name']  = $group_name;
                $data['group_code']  = $group_code;
                $res = WmsOutOrderList::insert($data);
            }
            if ($res){
                $msg['code']=200;
                $msg['msg']='操作成功';
            }else{
                $msg['code']=302;
                $msg['msg']='操作失败';
            }
            return $msg;
        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }
    }

    /**
     *获取数据  wms/order/createOrder
     * */
    public function createOrder(Request $request){
        $self_id=$request->input('self_id');
//        $self_id='order_202012221122071857614366';
        $out_store_status  =array_column(config('wms.out_store_status'),'name','key');
        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];
        $order_select = ['self_id','status','create_user_name','create_time','group_name','warehouse_name','total_flag','total_time',
            'picker','operator','purchase','car_num','delivery_time','out_time'];
        $order_list_select= ['self_id','sku_id','external_sku_id','good_name','good_unit','spec','num','order_id','external_sku_id','remarks','price','total_price','out_library_state'];
        $wms_out_sige_select= ['self_id','sku_id','library_sige_id','external_sku_id','order_list_id','good_name','spec','num','good_unit','good_target_unit','good_scale','good_english_name'
        ,'price','total_price'];

        $info=wmsOutOrder::with(['wmsOutOrderList'=>function($query)use($order_list_select,$wms_out_sige_select){
            $query->where('delete_flag','=','Y');
            $query->select($order_list_select);
            $query->with(['wmsOutSige' => function($query)use($wms_out_sige_select){
                $query->select($wms_out_sige_select);
                $query->where('delete_flag','=','Y');
            }]);
        }])->where($where)
            ->select($order_select)->first();

        if($info){
            $list=[];
            $list2=[];
            $quhuo_list=[];
            $out_list=[];
            foreach ($info->wmsOutOrderList as $k=>$v){
                $v->out_library_state_show  = $out_store_status[$v->out_library_state] ?? null;
                if($v->quehuo == 'Y'){
                    $data['quehuo_flag']         ='Y';
                    $list2['external_sku_id']    =$v->external_sku_id;
                    $list2['good_name']          =$v->good_name;
                    $list2['spec']               =$v->spec;
                    $list2['num']                =$v->quehuo_num;
                    $list2['good_unit']          =$v->good_unit;
                    $quhuo_list[]=$list2;
                }
                //dd($v->toArray());
                if($v->wmsOutSige){
                    $data['out_flag']        ='Y';
                    foreach ($v->wmsOutSige as $kk => $vv){
                        $list['external_sku_id']    =$v->external_sku_id;
                        $list['good_name']          =$v->good_name;
                        $list['good_english_name']  =$vv->good_english_name;
                        $list['spec']               =$v->spec;
                        $list['num']                =$vv->num;
                        $list['good_unit']          =$vv->good_unit;
                        $list['sign']               =$vv->area.'-'.$vv->row.'-'.$vv->column.'-'.$vv->tier;
                        $list['good_describe']      =unit_do($vv->good_unit , $vv->good_target_unit, $vv->good_scale, $vv->num);
                        //dd($vv->toArray());
                        $out_list[]=$list;
                    }
                }

            }

            $data['info']=$info;
            $data['out_list']=$out_list;
            $data['quhuo_list']=$quhuo_list;
            $msg['code']=200;
            $msg['data']=$data;
            $msg['msg']="获取数据成功";
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }

    /**
     * 出库订单修改 wms/order/editOrder
     * */
    public function editOrder(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_out_order';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='创建出库订单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        /** 接收数据*/
        $input              = $request->all();
        $warehouse_id       = $request->input('warehouse_id');
        $goods              = json_decode($request->input('goods'),true);
        $delivery_time      = $request->input('delivery_time');
        $car_num            = $request->input('car_num');
        $picker             = $request->input('picker');
        $operator           = $request->input('operator');
        $purchase           = $request->input('purchase');
        $out_time           = $request->input('out_time');


        /***
        $input['warehouse_id'] = $warehouse_id = 'warehouse_20221215135058787296124';
        $input['car_num'] = $car_num = '485615612';
        $input['picker'] = $picker = '12345';
        $input['operator'] = $operator = '1234';
        $input['purchase'] = $purchase = '123';
        $input['goods']=$goods=[
        '0'=>[
        'sku_id'=>'sku_202212171207283772516693',
        'can_use'=>'Y',
        'num'=>29,
        'wms_unit'=>'',
        'price'=>'200',
        'total_price'=>6200,
        'remark'=>'0.2',
        'out_library_state'=>'',

        ],
        ];
         * **/
        $rules = [
            'goods' => 'required',
        ];
        $message = [
            'goods.required' => '请选择要出库的产品',
        ];
        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            $where_warehouse=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warehouse_id],
            ];

            $warehouse_info = WmsWarehouse::where($where_warehouse)->select('self_id','warehouse_name','group_name','group_code')->first();
            if(empty($warehouse_info)){
                $msg['code'] = 302;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }


            $order_2['self_id']             =generate_id('order_');
            $order_2['group_code']          =$user_info->group_code;
            $order_2['group_name']          =$user_info->group_name;
            $order_2['count']               =count($goods);
            $order_2['warehouse_id']        =$warehouse_info->self_id;
            $order_2['warehouse_name']      =$warehouse_info->warehouse_name;
            $order_2['create_user_id']      =$user_info->admin_id;
            $order_2['create_user_name']    =$user_info->name;
            $order_2['create_time']         =$order_2['update_time']            =$now_time;
            $order_2['delivery_time']       =$delivery_time;
            $order_2['car_num']             =$car_num;
            $order_2['picker']              =$picker;
            $order_2['operator']            =$operator;
            $order_2['purchase']            =$purchase;
            $order_2['out_time']            =$out_time;
            $order_2['status']              =2;

            $list=[];
            foreach($goods as $k =>$v){

                $where_sku=[
                    ['delete_flag','=','Y'],
                    ['self_id','=',$v['sku_id']],
                ];
                $select_ErpShopGoodsSku=['self_id','group_code','group_name','external_sku_id','wms_unit','good_name','wms_spec'];
                $sku_info = ErpShopGoodsSku::where($where_sku)->select($select_ErpShopGoodsSku)->first();

                //dd($vv);
                $list['self_id']            =generate_id('list_');
                $list['good_name']          = $sku_info->good_name;
                $list['spec']               = $sku_info->wms_spec;
                $list['num']                = $v['num'];
                $list['good_unit']          = $v['wms_unit'];
                $list['group_code']         = $sku_info->group_code;
                $list['group_name']         = $sku_info->group_name;
                $list['order_id']           = $order_2['self_id'];
                $list['sku_id']             = $sku_info->self_id;
                $list['create_user_id']     = $user_info->admin_id;
                $list['create_user_name']   = $user_info->name;
                $list['create_time']        = $list['update_time']=$now_time;
                $list['price']              = $v['price'];
                $list['total_price']        = $v['total_price'];
                $list['remarks']            = $v['remark'];
//                $list['out_library_state']  = $v['out_library_state'];
                $datalist[]=$list;

            }

            WmsOutOrderList::insert($datalist);
            $id= WmsOutOrder::insert($order_2);

            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功!';

                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }

        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }
    }

    /**
     * 删除出库订单商品
     * */
    public function delOutorderSku(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_out_order';

        $operationing->access_cause     ='删除';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='delete';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $self_id = $request->input('self_id');//列表数据self_id
        $order_id = $request->input('order_id');//入库订单self_id


        $rules=[
            'self_id'=>'required',
            'order_id'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择入库订单',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $show = 'Y';
            $data['delete_flag'] = 'N';
            $data['update_time'] = $now_time;

            $id = WmsOutOrderList::where('self_id',$self_id)->update($data);

            $order_list = WmsOutOrderList::where('order_id',$order_id)->where('delete_flag','Y')->get()->toArray();
            if(count($order_list) > 0){

            }else{
                WmsOutOrder::where('self_id',$order_id)->update($data);
                $show = 'N';
            }
            if($id){
                $msg['code'] = 200;
                $msg['msg'] = '操作成功！';
                $msg['show'] = $show;
            }else{
                $msg['code'] = 301;
                $msg['msg'] = '操作失败！';
            }
            return $msg;
        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }
    }

}
?>
