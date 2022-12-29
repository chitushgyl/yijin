<?php
namespace App\Http\Admin\Wms;
use App\Models\Tms\TmsMoney;
use App\Models\Wms\WmsLibraryChange;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\WmschangeController as Change;
use App\Http\Controllers\DetailsController as Details;
use App\Http\Controllers\WmsMoneyController as WmsMoney;
use App\Models\Wms\WmsLibraryOrder;
use App\Models\Wms\WmsLibrarySige;
use App\Models\Wms\WmsWarehouse;
use App\Models\Wms\WmsWarehouseSign;
use App\Models\Wms\WmsGroup;
use App\Models\Shop\ErpShopGoodsSku;

class LibraryController extends CommonController{
    /***    入库列表      /wms/library/libraryList
     */
    public function  libraryList(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数

        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='入库';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/直接入库导入文件范本.xlsx',
        ];

        //抓取他需要待审核的数据多少
        $where['delete_flag'] = 'Y';
        $where['grounding_status'] = 'N';
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsLibraryOrder::where($where)->count(); //总的数据量
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsLibraryOrder::where($where)->count(); //总的数据量
                break;

            case 'more':
                $data['total']=WmsLibraryOrder::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                break;
        }

        foreach ($data['button_info'] as $k => $v){
            if($v->id == '448'){
                $v->name.='（'.$data['total'].'）';
            }
        }


	$data['key_info']=[
            ['key'=>'voucher',
                'count'=>'5',
                'name'=>'导入凭证']
            ];



        //dd($data['button_info']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    入库分页      /wms/library/libraryPage
     */
    public function libraryPage(Request $request){
        /** 接收中间件参数**/
        $group_info          = $request->get('group_info');//接收中间件产生的参数
        $button_info         = $request->get('anniu');//接收中间件产生的参数
        $wms_order_type      = config('wms.wms_order_type');

        $wms_order_type_show  =array_column($wms_order_type,'name','key');

        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');
		$group_code			=$request->input('group_code');
        $warehouse_id     	=$request->input('warehouse_id');
        $grounding_status   =$request->input('grounding_status');
        $order_status       =$request->input('order_status');
        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'all','name'=>'grounding_status','value'=>$grounding_status],
            ['type'=>'like','name'=>'warehouse_id','value'=>$warehouse_id],
			['type'=>'like','name'=>'group_code','value'=>$group_code],
			['type'=>'=','name'=>'order_status','value'=>$order_status],
        ];

        $where=get_list_where($search);

        $select=['self_id','order_status','grounding_status','group_name','group_code','warehouse_name','warehouse_id','count','type','create_user_name',
            'check_time','create_time','accepted','purchase','operator'];
        $WmsLibrarySigeSelect=[
            'self_id','grounding_status','in_library_state','grounding_type','good_remark','good_lot','order_id','external_sku_id','good_name','spec',
            'production_date','expire_time','initial_num as now_num','good_unit','good_target_unit','good_scale','can_use', 'delete_flag'
        ];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsLibraryOrder::where($where)->count(); //总的数据量
                $data['items']=WmsLibraryOrder::with(['wmsLibrarySige' => function($query)use($WmsLibrarySigeSelect) {
                    $query->where('delete_flag','Y');
                    $query->select($WmsLibrarySigeSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsLibraryOrder::where($where)->count(); //总的数据量
                $data['items']=WmsLibraryOrder::with(['wmsLibrarySige' => function($query)use($WmsLibrarySigeSelect) {
                    $query->where('delete_flag','Y');
                    $query->select($WmsLibrarySigeSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsLibraryOrder::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsLibraryOrder::with(['wmsLibrarySige' => function($query)use($WmsLibrarySigeSelect) {
                    $query->where('delete_flag','Y');
                    $query->select($WmsLibrarySigeSelect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {
            $v->type_show=$wms_order_type_show[$v->type]??null;
            $v->button_info=$button_info;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    入库导入      /wms/library/import
     */
    public function import(Request $request,Change $change){
        $user_info          = $request->get('user_info');//接收中间件产生的参数1
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_library_order';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建入库';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        /** 接收数据*/
        $input                      =$request->all();
        $importurl                  =$request->input('importurl');
        $warehouse_id               =$request->input('warehouse_id');
        $company_id                 =$request->input('company_id');
        $file_id                    =$request->input('file_id');
        $voucher                    =$request->input('voucher');

	//dd($voucher);
        /****虚拟数据
        //$input['importurl']       =$importurl="uploads/2020-10-13/直接入库导入文件范本.xlsx";
        $input['warehouse_id']      =$warehouse_id='warehouse_202011191628080039423809';
        $input['company_id']        =$company_id='group_202011191635046508242166';
			***/
        $rules = [
            'warehouse_id' => 'required',
            'company_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'warehouse_id.required' => '请选择公司',
            'company_id.required' => '请选择业务公司',
            'importurl.required' => '请上传文件',
        ];

        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
            $res = Excel::toArray((new Import),$importurl);

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
                '商品编号' =>['Y','N','64','external_sku_id'],
                '商品名称' =>['Y','Y','255','good_name'],
                '商品英文名称' =>['N','Y','255','good_english_name'],
                '生产日期' =>['Y','Y','50','production_date'],
                '到期时间' =>['N','Y','50','expire_time'],
                /**
                 * 2022/1/10 修改 导入入库订单取消库位填写
                 *  '库位' =>['Y','Y','50','sign'],
                 * */

                '实际库存' =>['Y','Y','20','now_num'],
            ];
            $ret=arr_check($shuzu,$info_check);

            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];

			//DUMP($info);

            //检查仓库有没有
            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warehouse_id],
            ];
            $warehouse_info = WmsWarehouse::where($where_check)->select('self_id','warehouse_name','group_code','group_name')->first();
            /**
             * 2022/1/10 修改 导入入库订单取消库位填写
            if(empty($warehouse_info)){
                $msg['code'] = 304;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }
             * */
            $where_check2=[
                ['delete_flag','=','Y'],
                ['self_id','=',$company_id],
            ];
            $company_name = WmsGroup::where($where_check2)->value('company_name');

            if(empty($company_name)){
                $msg['code'] = 305;
                $msg['msg'] = '业务公司不存在';
                return $msg;
            }

            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
			$pull=[];
            $bulk=0;
            $weight=0;
			//	dd($info_wait);
            $a=2;
            $seld=generate_id('SID_');
            foreach($info_wait as $k => $v){
                $v['warehouse_sign_id'] = '';
                $where100=[
                    ['external_sku_id','=',$v['external_sku_id']],
                    ['company_id','=',$company_id],
                ];

                //查询商品是不是存在
                $goods_select=['self_id','external_sku_id','company_id','company_name','good_name','good_english_name','wms_target_unit','wms_scale','wms_unit','wms_spec',
                    'wms_length','wms_wide','wms_high','wms_weight','period_value','period'];
                $getGoods=ErpShopGoodsSku::where($where100)->select($goods_select)->first();
                //dd($getGoods);
                if(empty($getGoods)){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行商品不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                //查询库位是不是存在
                /**
                 * 2022/1/10 修改 导入入库订单取消库位填写
                 * $abc= explode('-',$v['sign']);
                $where2k=[];
                if(array_key_exists(0, $abc)){
                $where2k['area']=$abc[0];
                }
                if(array_key_exists(1, $abc)){
                $where2k['row']=$abc[1];
                }
                if(array_key_exists(2, $abc)){
                $where2k['column']=$abc[2];
                }
                if(array_key_exists(3, $abc)){
                $where2k['tier']=$abc[3];
                }
                if($where2k){
                $where2k['warehouse_id']=$warehouse_id;
                }

                $warehouse_select=['warehouse_id','warehouse_name','self_id','area_id','area','row','column','tier','group_code','group_name'];
                $getWmsWarehouse=WmsWarehouseSign::where($where2k)->select($warehouse_select)->first();
                 * */
                $where2k['self_id']=$v['warehouse_sign_id'];
                $warehouse_select=['warehouse_id','warehouse_name','self_id','area_id','area','row','column','tier','group_code','group_name'];
                $getWmsWarehouse=WmsWarehouseSign::where($where2k)->select($warehouse_select)->first();

                /**
                 * 2022/1/10 修改 导入入库订单取消库位填写
                if(empty($getWmsWarehouse)){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行库位不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                 * */
                /** 计算商品的有效期**/
                $expire_time=null;
                if($v['expire_time']){
                    $expire_time=$v['expire_time'];
                }else{
                    if($getGoods->period_value && $getGoods->period){
                        $abcccc='+'.$getGoods->period_value.' '.$getGoods->period;
                        $expire_time       =date('Y-m-d',strtotime($abcccc,strtotime($v['production_date'])));
                    }else{
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行数据无到期时间".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }
                }
                $list=[];
                /**现在可以开始做数据了**/
                if($cando == 'Y'){
                    $pull[]='';

                    $list["self_id"]            =generate_id('LSID_');
                    $list["order_id"]           =$seld;
                    $list["sku_id"]             =$getGoods->self_id;
                    $list["external_sku_id"]    =$getGoods->external_sku_id;
                    $list["company_id"]         =$getGoods->company_id;
                    $list["company_name"]       =$getGoods->company_name;
                    $list["good_name"]          =$getGoods->good_name;
                    $list["good_english_name"]  =$getGoods->good_english_name;
                    $list["good_target_unit"]   =$getGoods->wms_target_unit;
                    $list["good_scale"]         =$getGoods->wms_scale;
                    $list["good_unit"]          =$getGoods->wms_unit;
                    $list["good_lot"]           ='';
                    $list["company_name"]       =$getGoods->company_name;
                    $list["good_name"]          =$getGoods->good_name;
                    $list['spec']               =$getGoods->wms_spec;
                    $list["wms_length"]         =$getGoods->wms_length;
                    $list["wms_wide"]           =$getGoods->wms_wide;
                    $list["wms_high"]           =$getGoods->wms_high;
                    $list["wms_weight"]         =$getGoods->wms_weight;
                    $list["production_date"]    =$v['production_date'];
                    $list["expire_time"]        =$expire_time;
                    $list["good_info"]          =json_encode($getGoods,JSON_UNESCAPED_UNICODE);

                    $list['initial_num']        =$v['now_num'];
                    $list['now_num']            =$v['now_num'];
                    $list['storage_number']     =$v['now_num'];
                    $list['in_library_state']   ='normal';
                    $list["group_code"]         =$warehouse_info->group_code;
                    $list["group_name"]         =$warehouse_info->group_name;
                    $list['create_time']        =$now_time;
                    $list["update_time"]        =$now_time;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['bulk']               = $getGoods->wms_length*$getGoods->wms_wide*$getGoods->wms_high*(float)$v['now_num'];
                    $list['weight']             = $getGoods->wms_weight*$v['now_num'];
                    $bulk+=  $getGoods->wms_length*$getGoods->wms_wide*$getGoods->wms_high*$v['now_num'];
                    $weight+=  $getGoods->wms_weight*$v['now_num'];
                    $datalist[]=$list;


                }

                $a++;
            }



            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }

            $count=count($datalist);
			$pull=array_unique($pull);
            $pull_count=count($pull);

            ///做一个大订单
            $data['self_id']                =$seld;
            $data['create_user_id']         = $user_info->admin_id;
            $data['create_user_name']       = $user_info->name;
            $data['create_time']            =$now_time;
            $data["update_time"]            =$now_time;
            $data["grounding_status"]       ='N';
            $data["group_code"]             =$warehouse_info->group_code;
            $data["group_name"]             =$warehouse_info->group_name;
            $data["warehouse_id"]           =$warehouse_info->self_id;
            $data["warehouse_name"]         =$warehouse_info->warehouse_name;
            $data['count']                  =$count;
            $data['type']                   ='preentry';
            $data['company_id']             =$company_id;
            $data["company_name"]           =$company_name;
			$data["pull_count"]             =$pull_count;
            $data['file_id']                =$file_id;
	        $data['voucher']                =img_for($voucher,'in');
            $data['bulk']                   =$bulk;
            $data['weight']                 =$weight;
            $data['order_status']           = 'S';
            $operationing->table_id=$seld;
            $operationing->new_info=$data;

            DB::beginTransaction();
            try {
                $id=WmsLibraryOrder::insert($data);
                if($id) {
                    $new_list = array_chunk($datalist, 1000);
                    foreach ($new_list as $value) {
                        WmsLibrarySige::insert($value);
                    }
//                if($data["grounding_status"]=='Y'){
                    $change->change($datalist, 'preentry');
//                }
                    $msg['code'] = 200;
                    /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                    $msg['msg'] = '操作成功，您一共导入' . $count . '条数据';

                }
                DB::commit();
                return $msg;
            }catch(\Exception $e){
                DB::rollBack();
                $msg['code']=307;
                $msg['msg']='操作失败';
                return $msg;
            }
        }else{
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

    /***    入库待列表      /wms/library/libraryCheck
     */
    public function libraryCheck(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $wms_order_type      = config('wms.wms_order_type');
        $wms_order_type_show  =array_column($wms_order_type,'name','key');
    //抓取他需要待审核的数据多少
        $select=['self_id','grounding_status','group_name','warehouse_name','company_name','create_user_name','count','create_time','type','purchase','operator','accepted'];
        $where=[
            ['delete_flag','=','Y'],
            ['grounding_status','=', 'N'],
        ];

        switch ($group_info['group_id']){
            case 'all':
                $data['info']=WmsLibraryOrder::where($where)->select($select)->orderBy('create_time','desc')->get(); //总的数据量
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['info']=WmsLibraryOrder::where($where)->select($select)->orderBy('create_time','desc')->get(); //总的数据量
                break;

            case 'more':
                $data['info']=WmsLibraryOrder::where($where)->select($select)->whereIn('group_code',$group_info['group_code'])->orderBy('create_time','desc')->get(); //总的数据量
                break;
        }

        foreach ($data['info'] as $k=>$v) {
            $v->type_show=$wms_order_type_show[$v->type];
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    审核入库详情      /wms/library/getLibrary
     */
    public function getLibrary(Request $request){
        $self_id=$request->input('self_id');
        //$self_id='SID_202011201200214819529790';

        $where=[
            ['delete_flag','=','Y'],
            ['grounding_status','=', 'N'],
            ['self_id','=', $self_id],
        ];
        $sigeSelect=['order_id','external_sku_id','good_name','spec','production_date','expire_time','now_num','area','row','column','tier','good_unit','good_target_unit','good_scale'];
        $select=['self_id','warehouse_name','company_name','type','voucher','purchase','operator','accepted'];
        $data['info']=WmsLibraryOrder::with(['wmsLibrarySige' => function($query)use($sigeSelect){
            $query->select($sigeSelect);
        }])->where($where)
         ->select($select)->first();

	//dd($libraryOrder->voucher);
        $data['info']->voucher=img_for($data['info']->voucher,'more');
        if($data['info']){
            foreach ($data['info']->wmsLibrarySige as $k=>$v) {
                $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
                $v->good_describe =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);

            }


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="该订单不存在或已被审核完毕";
            return $msg;
        }
    }

    /***    审核入库操作      /wms/library/checkStatus
     */
    public function checkStatus(Request $request,Change $change,WmsMoney $money){
        $operationing           = $request->get('operationing');//接收中间件产生的参数
        $user_info              = $request->get('user_info');//接收中间件产生的参数
        $now_time               =date('Y-m-d H:i:s',time());
        $self_id                =$request->input('self_id');
        $status                 =$request->input('status')??'Y';
        $table_name             ='wms_library_order';
        $operationing->access_cause='审核入库';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;


//DUMP($self_id);DUMP($status);
        //$self_id='SID_202011211409592172693936';
        $where=[
            ['delete_flag','=','Y'],
            ['grounding_status','=', 'N'],
            ['self_id','=', $self_id],
        ];

        $old_info=WmsLibraryOrder::where($where)->first();




		//DUMP($where);
//DD($old_info);
        if($old_info){
			$where_pack2=[
				['delete_flag','=','Y'],
				['self_id','=', $old_info->company_id],
			];
			$company_select=['self_id','company_name',
				'preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];

			$company_info = WmsGroup::where($where_pack2)->select($company_select)->first();


            if($status == 'Y'){
                $data['grounding_status'] = $status;
            }else{
                $data['delete_flag'] = $status;
            }

            $data['check_user_id']=$user_info->admin_id;
            $data['check_user_name']=$user_info->name;
            $data['check_time']=$now_time;
            $id=WmsLibraryOrder::where($where)->update($data);


            $operationing->table_id=$self_id;
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){
                if($status == 'Y'){
                    $list['grounding_status'] = $status;
                    $where_list['order_id']=$self_id;
                    $where_list["delete_flag"]='Y';

                    $datalist= WmsLibrarySige::where($where_list)->get()->toArray();

                    WmsLibrarySige::where($where_list)->update($list);

                    //这里要触发调用仓库变化
                    $change->change($datalist,'preentry');
                    $money->moneyCompute($old_info,$datalist,$now_time,$company_info,$user_info,'in');


                }
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
                return $msg;
            }

        }else{
            $msg['code']=300;
            $msg['msg']="该订单不存在或已被审核完毕";
            return $msg;
        }
    }

    /***    手工入库操作      /wms/library/addLibrary
     */
    public function addLibrary(Request $request,Change $change,WmsMoney $money){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $user_info          = $request->get('user_info');                //接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='wms_library_order';

        $operationing->access_cause='入库';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        $input=$request->all();
        /** 接收数据*/
        $warehouse_id       =$request->input('warehouse_id');//仓库ID
        $library_sige       = json_decode($request->input('library_sige'), true);//产品信息
        $purchase           = $request->input('purchase');//采购商/供应商
        $operator           = $request->input('operator');//经办人
        $accepted           = $request->input('accepted');//验收人
        $voucher            = json_decode($request->input('voucher'),true);//凭证

        /*** 虚拟数据
        $input['group_code']=$group_code='1234';
        $input['warehouse_id']=$warehouse_id='warehouse_20221215135058787296124';
        $input['purchase']=$purchase='12345';
        $input['operator']=$operator='1234';
        $input['accepted']=$accepted='123';
        $input['library_sige']=$library_sige=[
            '0'=>[
                'sku_id'=>'sku_202212171207283772516693',
                'now_num'=>'30',
                'can_use'=>'Y',
                'good_remark'=>'Y',
            ]
         ];
         **/
        $rules=[
            'group_code'=>'required',
            'warehouse_id'=>'required',
            'library_sige'=>'required',
        ];
        $message=[
            'group_code.required'=>'请填写公司',
            'warehouse_id.required'=>'请填写仓库',
            'library_sige.required'=>'请选择商品',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /** 开始检查其他数据对不对    **/
            $where_pack=[
                ['delete_flag','=','Y'],
                ['self_id','=', $warehouse_id],
            ];

            $warehouse_info = WmsWarehouse::where($where_pack)->select('warehouse_name','group_code','group_name')->first();
            if(empty($warehouse_info)){
                $msg['code'] = 304;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }

            /** 开始制作数据了*/
            $datalist=[];       //初始化数组为空
            $seld=generate_id('SID_');

            foreach($library_sige as $k => $v){
                $where100['self_id']=$v['sku_id'];
                //查询商品是不是存在
                $goods_select=['self_id','external_sku_id','good_name','good_english_name','wms_target_unit','wms_scale','wms_unit','wms_spec',
                    'period','period_value'];

                $getGoods=ErpShopGoodsSku::where($where100)->select($goods_select)->first();
				$list=[];

				$list["self_id"]            =generate_id('LSID_');
				$list["order_id"]           =$seld;
				$list["sku_id"]             =$getGoods->self_id;
				$list["external_sku_id"]    =$getGoods->external_sku_id;
				$list["good_name"]          =$getGoods->good_name;
				$list["good_english_name"]  =$getGoods->good_english_name;
				$list["good_target_unit"]   =$getGoods->wms_target_unit;
				$list["good_scale"]         =$getGoods->wms_scale;
				$list["good_unit"]          =$getGoods->wms_unit;
				$list["good_info"]          =json_encode($getGoods,JSON_UNESCAPED_UNICODE);
				$list['spec']               =$getGoods->wms_spec;
				$list['initial_num']        =$v['now_num'];
				$list['now_num']            =$v['now_num'];
				$list['storage_number']     =$v['now_num'];
				$list["group_code"]         =$user_info->group_code;
				$list["group_name"]         =$user_info->group_name;
				$list['create_time']        =$now_time;
				$list["update_time"]        =$now_time;
				$list["create_user_id"]     =$user_info->admin_id;
				$list["create_user_name"]   =$user_info->name;
				$list["grounding_status"]   ='Y';
				$list["good_remark"]        =$v['good_remark'];
				$list["use_flag"]           ='N';
				$datalist[]=$list;

                /**保存费用**/
//                $money['pay_type']           = 'fuel';
//                $money['money']              = $v['now_num']*$v['price'];
//                $money['pay_state']          = 'Y';
//                $money['process_state']      = 'Y';
//                $moneyList[] = $money;
            }

            $count=count($datalist);

            $data['self_id']            =$seld;
            $data['create_time']        =$now_time;
            $data["update_time"]        =$now_time;
            $data["grounding_status"]   ='Y';
            $data["group_code"]         =$warehouse_info->group_code;
            $data["group_name"]         =$warehouse_info->group_name;
            $data["create_user_id"]     =$user_info->admin_id;
            $data["create_user_name"]   =$user_info->name;
            $data["warehouse_id"]       =$warehouse_id;
            $data["warehouse_name"]     =$warehouse_info->warehouse_name;
            $data['count']              =$count;
            $data['type']               ='preentry';

            $data['check_time']         =$now_time;
            $data['voucher']            =img_for($voucher,'in');
            $data['order_status']       = 'W';
            $data['purchase']           =$purchase;
            $data['operator']           =$operator;
            $data['accepted']           =$accepted;

            $id=WmsLibraryOrder::insert($data);
//            TmsMoney::insert($moneyList);
            $operationing->table_id=$data['self_id'];
            $operationing->old_info=null;
            $operationing->new_info=$data;

            if($id){
                WmsLibrarySige::insert($datalist);
                $change->change($datalist,'preentry');

                $msg['code']=200;
                $msg['msg']='操作成功';

                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }
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

    /***    入库详情     /wms/library/details
     */
    public function  details(Request $request,Details $details){
        $wms_order_type      = config('wms.wms_order_type');
        $wms_order_type_show  =array_column($wms_order_type,'name','key');
        $in_store_status  =array_column(config('wms.in_store_status'),'name','key');
        $self_id=$request->input('self_id');

        //$self_id='SID_2020120415135553691290';

        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','grounding_status','order_status','type','create_user_name','create_time','group_name','check_time','grounding_status','count',
            'purchase','operator','accepted','voucher','type','warehouse_id','warehouse_name'];

		$WmsLibrarySigeSelect=[
            'self_id','grounding_status','in_library_state','grounding_type','good_remark','good_lot','order_id','external_sku_id','good_name','spec',
            'production_date','expire_time','initial_num as now_num','good_unit','good_target_unit','good_scale','can_use', 'delete_flag'
		];

        $info=WmsLibraryOrder::with(['wmsLibrarySige' => function($query)use($WmsLibrarySigeSelect) {
            $query->where('delete_flag','Y');
		$query->select($WmsLibrarySigeSelect);
		}])->where($where)
		->select($select)->first();


        if($info){
            $info->type_show=$wms_order_type_show[$info->type];

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            foreach ($info->wmsLibrarySige as $k => $v){
                if ($v->area && $v->row && $v->column){
                    $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
                }


                $v->good_describe =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);
                $v->in_library_state_show = $in_store_status[$v->in_library_state]??null;
            }

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
//dd($msg);

            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }

    /**
     * 入库审核
     * 修改order_status 入库订单状态 W
     * */
    public function wait_library(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_order';

        $operationing->access_cause     ='修改入库状态';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $self_id = $request->input('self_id');
        $order_status = $request->input('order_status');

//        $sign_id = $input('sign_id');//数组
        //第一步，验证数据
        $rules=[
            'self_id'=>'required',
            'order_status'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择入库订单',
            'order_status.required'=>'请选择要做的操作',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $data['update_time'] = $now_time;
            $data['order_status'] = $order_status;
            $update['use_flag'] = 'Y';
            $update['update_time'] = $now_time;
            DB::beginTransaction();
            try{
                $id =  WmsLibraryOrder::whereIn('self_id',explode(',',$self_id))->update($data);
                $a = WmsLibraryChange::whereIn('order_id',explode(',',$self_id))->update($update);
                $b = WmsLibrarySige::whereIn('order_id',explode(',',$self_id))->update($update);
                DB::commit();
                $msg['code'] = 200;
                $msg['msg'] = '操作成功';
                return $msg;
            }catch(\Exception $e){
               DB::rollBack();
                $msg['code'] = 301;
                $msg['msg'] = '操作失败！';
                return $msg;
            }
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
     * 修改入库明细里商品信息
     * */
    public function editSku(Request $request,Change $change){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_sige';

        $operationing->access_cause     ='添加/修改入库商品信息';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $self_id          = $request->input('self_id');
        $good_target_unit = $request->input('good_target_unit');
        $now_num          = $request->input('now_num');
        $good_lot         = $request->input('good_lot');
        $order_id         = $request->input('order_id');
        $external_sku_id  = $request->input('external_sku_id');
        $production_date  = $request->input('production_date');
        $expire_time      = $request->input('expire_time');
        $name             = $request->input('name');
        $good_remark      = $request->input('good_remark');
        $in_library_state   = $request->input('in_library_state');
        $sku_id           = $request->input('sku_id');
        $rules = [

        ];
        $message = [

        ];
        $validator = Validator::make($input,$rules,$message);
        if($validator->passes()){
            $data['good_target_unit'] = $good_target_unit;
            $data['good_lot'] = $good_lot;
            $data['now_num'] = $now_num;
            $data['initial_num'] = $now_num;


            $wheres['self_id'] = $self_id;
            $old_info=WmsLibrarySige::where($wheres)->first();

            if($old_info){
                //dd(1111);
                $data['update_time']=$now_time;
                $res = WmsLibrarySige::where('self_id',$self_id)->update($data);
                $result =  WmsLibraryChange::where('order_id',$order_id)->where('external_sku_id',$external_sku_id)->update($data);
                $operationing->access_cause='修改货物信息';
                $operationing->operation_type='update';


            }else{

                $where100['self_id']=$sku_id;
                //查询商品是不是存在
                $goods_select=['self_id','external_sku_id','company_id','company_name','good_name','good_english_name','wms_target_unit','wms_scale','wms_unit','wms_spec',
                    'wms_length','wms_wide','wms_high','wms_weight','period','period_value','group_code'];
                //dump($goods_select);

                $getGoods=ErpShopGoodsSku::where($where100)->select($goods_select)->first();
                $data["self_id"]            =generate_id('LSID_');
                $data["order_id"]           =$order_id;
                $data["sku_id"]             =$getGoods->self_id;
                $data["external_sku_id"]    =$getGoods->external_sku_id;
                $data["company_id"]         =$getGoods->company_id;
                $data["company_name"]       =$getGoods->company_name;
                $data["good_name"]          =$getGoods->good_name;
                $data["good_english_name"]  =$getGoods->good_english_name;
                $data["good_target_unit"]   =$getGoods->wms_target_unit;
                $data["good_scale"]         =$getGoods->wms_scale;
                $data["good_unit"]          =$getGoods->wms_unit;
                $data["wms_length"]         =$getGoods->wms_length;
                $data["wms_wide"]           =$getGoods->wms_wide;
                $data["wms_high"]           =$getGoods->wms_high;
                $data["wms_weight"]         =$getGoods->wms_weight;
                $data["good_info"]          =json_encode($getGoods,JSON_UNESCAPED_UNICODE);

                $data["production_date"]    =$production_date;
                $data["expire_time"]        =$expire_time;
                $data['spec']               =$getGoods->wms_spec;
                $data['storage_number']     =$now_num;

                $data["group_code"]         =$getGoods->group_code;
                $data["group_name"]         =$getGoods->group_name;

                $data['create_time']        =$now_time;
                $data["update_time"]        =$now_time;
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $name;
                $data["grounding_status"]   ='N';
                $data["good_remark"]        =$good_remark;
                $data["in_library_state"]     =$in_library_state;

                $data['bulk']               = $getGoods->wms_length*$getGoods->wms_wide*$getGoods->wms_high*$now_num;
                $data['weight']             = $getGoods->wms_weight*$now_num;
                $dataList[] = $data;
                $res = WmsLibrarySige::insert($data);
                $change->change($dataList,'preentry');
            }


            if ($res){
                $msg['code'] = 200;
                $msg['msg'] = '修改成功';
                return $msg;
            }else{
                $msg['code'] = 301;
                $msg['msg'] = '修改失败';
                return $msg;
            }

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
     * 上架
     * */
    public function grounding(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_sige';

        $operationing->access_cause     ='上架';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $order_id           = $request->input('order_id');
        $area_id = $request->input('area_id');
        $sign_id = $request->input('sign_id');//
        $warehouse_id = $request->input('warehouse_id');//
        $warehouse_name = $request->input('warehouse_name');//
        $warehouse_sign_id = $request->input('warehouse_sign_id');//
        $grounding_type = $request->input('grounding_type');//

        //第一步，验证数据
        $rules=[
            'area_id'=>'required',
            'sign_id'=>'required',
        ];
        $message=[
            'area_id.required'=>'请选择入库订单',
            'sign_id.required'=>'请选择要做的操作',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $warehouse_sign_info = WmsWarehouseSign::where('self_id',$warehouse_sign_id)->first();
            $change['area'] = $data['area'] = $warehouse_sign_info->area;
            $change['row'] =$data['row']  = $warehouse_sign_info->row;
            $change['column'] = $data['column'] = $warehouse_sign_info->column;
            $change['tier'] = $data['tier'] = $warehouse_sign_info->tier;
            $change['update_time'] = $data['update_time'] = $now_time;
            $change['warehouse_id'] = $data['warehouse_id'] = $warehouse_id;
            $change['warehouse_name'] = $data['warehouse_name'] = $warehouse_name;
            $change['warehouse_sign_id'] = $data['warehouse_sign_id'] = $warehouse_sign_id;
            $data['area_id'] = $area_id;
            $data['grounding_status'] = 'Y';
            $data['grounding_type'] = $grounding_type;
            DB::beginTransaction();
            try {
                $id = WmsLibrarySige::whereIn('self_id',json_decode($sign_id,true))->update($data);
                foreach (json_decode($sign_id,true) as $key => $value){
                    WmsLibraryChange::where('order_id',$order_id)->where('library_sige_id',$value)->update($change);
                }
                DB::commit();
                $msg['code'] = 200;
                $msg['msg'] = '操作成功';
                return $msg;
            }catch(\Exception $e){
                DB::rollBack();
                $msg['code'] = 301;
                $msg['msg'] = '操作失败！';
                return $msg;
            }
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
     *入库未完成删除
     * */
    public function delSku(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_sige';

        $operationing->access_cause     ='上架';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $sige_id = $request->input('sige_id');//列表数据self_id
        $order_id = $request->input('order_id');//入库订单self_id
        $external_sku_id = $request->input('external_sku_id');//商品编号

        $rules=[
            'sige_id'=>'required',
        ];
        $message=[
            'sige_id.required'=>'请选择入库订单',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $show = 'Y';
            $data['delete_flag'] = 'N';
            $data['update_time'] = $now_time;

            $id = WmsLibrarySige::where('self_id',$sige_id)->update($data);

            $order_list = WmsLibrarySige::where('order_id',$order_id)->where('delete_flag','Y')->get()->toArray();
            if(count($order_list) > 0){

            }else{
                WmsLibraryOrder::where('self_id',$order_id)->update($data);
                WmsLibraryChange::where('order_id',$order_id)->where('external_sku_id',$external_sku_id)->update($data);
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

    /**
     * 未完成 已完成头部list
     * */
    public function libraryNlist(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数

        $data['page_info']      =config('page.listrows');
//        $data['button_info']    =$request->get('anniu');


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /**
     * 预约入库删除
     * */
    public function delLibraryOrder(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_order';

        $operationing->access_cause     ='预约入库删除';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='delete';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $self_id = $request->input('self_id');//列表数据self_id

        $rules=[
            'self_id'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择入库订单',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $data['delete_flag'] = 'N';
            $data['update_time'] = $now_time;
            DB::beginTransaction();
            try {
                $id = WmsLibraryOrder::where('self_id',$self_id)->update($data);

                WmsLibrarySige::where('order_id',$self_id)->update($data);
                WmsLibraryChange::where('order_id',$self_id)->update($data);
                DB::commit();
                $msg['code'] = 200;
                $msg['msg'] = '操作成功！';
                return $msg;
            }catch (\Exception $e){
                DB::rollBack();
                $msg['code'] = 301;
                $msg['msg'] = '操作失败！';
                return $msg;
            }
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
     * 冻结库内商品
     * */
    public function freeLibrarySku(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_order';

        $operationing->access_cause     ='预约入库删除';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='delete';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $self_id = $request->input('self_id');//列表数据self_id
        $can_use = $request->input('can_use');//

        $rules=[
            'self_id'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择入库订单',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $data['can_use'] = $can_use;
            $data['update_time'] = $now_time;
            $id = WmsLibrarySige::whereIn('self_id',explode(',',$self_id))->update($data);

            if($id){
                $msg['code'] = 200;
                $msg['msg'] = '操作成功！';
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

    /**
     * 取消上架
     * */
    public function cancelGrounding(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_library_sige';

        $operationing->access_cause     ='取消上架';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='delete';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              = $request->all();
        $sign_id = $request->input('sign_id');//
        //第一步，验证数据
        $rules=[
            'sign_id'=>'required',
        ];
        $message=[
            'sign_id.required'=>'请选择要做的操作',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $data['area'] = '';
            $data['row']  = '';
            $data['column'] = '';
            $data['tier'] = 1;
            $data['update_time'] = $now_time;
            $data['warehouse_id'] = '';
            $data['warehouse_name'] = '';
            $data['warehouse_sign_id'] = '';
            $data['area_id'] = '';
            $data['grounding_status'] = 'N';
            $id = WmsLibrarySige::whereIn('self_id',explode(',',$sign_id))->update($data);
            if($id){
                $msg['code'] = 200;
                $msg['msg'] = '操作成功';
                return $msg;
            }else{
                $msg['code'] = 301;
                $msg['msg'] = '操作失败！';
                return $msg;
            }

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

//    /***获取产品库存**/  wms/library/getLibrarySige
    public function getLibrarySige(Request $request){
        /** 接收数据*/
        $sku_id       =$request->input('sku_id');
        /*** 虚拟数据**/
        //$sku_id='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['can_use','=','Y'],
            ['sku_id','=',$sku_id],
            ['now_num','>',0],
        ];

        $data['info']=WmsLibrarySige::where($where)->sum('now_num');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }


}
?>
