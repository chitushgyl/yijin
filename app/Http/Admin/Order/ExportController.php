<?php
namespace App\Http\Admin\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\FileController;
use Maatwebsite\Excel\Facades\Excel;

class ExportController  extends CommonController{
    /***    已导出订单信息头部      /order/export/orderList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  orderList(Request $request){

        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    已导出订单信息分页      /order/export/orderPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
	public function orderPage(Request $request){
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num=$request->input('num')??10;
        $page=$request->input('page')??1;

        $listrows=$num;
        $firstrow=($page-1)*$listrows;
        /// dump($input);
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
			['type'=>'=','name'=>'browse_type','value'=>'order/export/export'],
        ];
        $where=get_list_where($search);

        //dd($where);

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('sys_file_warehouse')
                    ->where($where)->count(); //总的数据量

                $data['items']=DB::table('sys_file_warehouse')
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select('self_id','type','url','group_name','create_user_name','create_time','operation_type','start_time','end_time')
                    ->get()->toArray();

                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=DB::table('sys_file_warehouse')
                    ->where($where)->count(); //总的数据量

                $data['items']=DB::table('sys_file_warehouse')
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select('self_id','type','url','group_name','create_user_name','create_time','operation_type','start_time','end_time')
                    ->get()->toArray();


                break;

            case 'more':
                $data['total']=DB::table('sys_file_warehouse')
                    ->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量

                $data['items']=DB::table('sys_file_warehouse')
                    ->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select('self_id','type','url','group_name','create_user_name','create_time','operation_type','start_time','end_time')
                    ->get()->toArray();

                break;
        }
        $img_url=config('aliyun.oss.url');

        foreach ($data['items'] as $k=>$v) {
			$v->url=$img_url.$v->url;

            $v->button_info=$button_info;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
	}

    /***    导出数据      /order/export/export
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  export(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $now_time=date('Y-m-d H:i:s',time());
        $url_len=config('aliyun.img.url_len');
        $table_name='sys_file_warehouse';

        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数

        /**接收查询条件数据*/
        $group_code=$request->input('group_code');
        $start_time=$request->input('start_time').' 00:00:00';
        $end_time=$request->input('end_time').' 23:59:59';

        $input=Input::all();

        /*** 虚拟数据
        $input['group_code']=$group_code='1234';
        $input['start_time']=$start_time='2018-11-30 00:00:00';
        $input['end_time']=$end_time='2099-12-31 00:00:00';*/

        //开始做效验
        $rules=[
            'group_code'=>'required',
            'start_time'=>'required',
            'end_time'=>'required',
        ];
        $message=[
            'group_code.required'=>'请填写公司',
            'start_time.required'=>'请填写开始时间',
            'end_time.required'=>'请填写结束时间',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //做一个条件出来
            $condition_where=[
                ['a.group_code','=',$group_code],
                ['a.pay_status','=',2],
                ['b.pay_time','>=',$start_time],
                ['b.pay_time','<',$end_time],
            ];

            $cellData=DB::table('shop_order_list as c')
                ->join('shop_order as a',function($join){
                    $join->on('a.self_id','=','c.order_sn');
                }, null,null,'left')
                ->join('shop_order_pay as b',function($join){
                    $join->on('b.self_id','=','c.pay_order_sn');
                }, null,null,'left')
                ->where($condition_where)->orderBy('a.create_time','desc')
                ->select('a.self_id','a.pay_order_sn','a.pay_status','a.order_type','a.group_name',
                            'a.money_goods','a.create_time','a.group_name','a.show_group_name',
                            'b.gather_name','b.gather_tel','b.gather_address',
                            'c.good_title','c.number','c.real_nubmer','c.self_id as list_id'
                )
                ->get()->toArray();
			if($cellData){
				$cellDataTitle = [
                'self_id'=>'订单号',
                'list_id'=>'小订单号',
                'pay_status'=>'订单状态',
                'gather_name'=>'收货人',
                'gather_tel'=>'收货电话',
                'gather_address'=>'收货地址',
                'good_title'=>'商品名称',
                'number'=>'商品数量',
                'real_nubmer'=>'已发数量',
                ];

				$this->execl = new FileController;

				$retu= $this->execl->export($cellData,$cellDataTitle);

				if($retu['code']==200){

					//导入成功后，写入文件管理系统
					$data['self_id']=generate_id('file_');
					$data['type']='EXECL';
					$data['url']= substr($retu['data']['url'],$url_len);                  ;
					$data['group_code']=$group_code;
					$data['group_name']=$cellData[0]->group_name;
					$data['create_user_id'] = $user_info->admin_id;
					$data['create_user_name'] = $user_info->name;
					$data['update_time'] = $data['create_time'] = $now_time;
					$data['start_time']=$start_time;
					$data['end_time'] = $end_time;
					$data['browse_type'] = $request->path();
					$data['operation_type'] = 'OUT';
					$data['condition_info'] = json_encode($condition_where);

					$id=DB::table($table_name)->insert($data);

					if($id){
						$msg['code']=200;
						$msg['msg']="导出成功";
						$msg['url']=$retu['data']['url'];

                        $operationing->access_cause='导出数据';
                        $operationing->table=$table_name;
                        $operationing->table_id=$data['self_id'];
                        $operationing->now_time=$now_time;
                        $operationing->old_info=null;
                        $operationing->new_info=$data;
                        $operationing->operation_type='export';


					   // dd($msg);
						return $msg;

					}else{

						$msg['code']=303;
						$msg['msg']="导出失败";
						return $msg;
					}
				}else{
					//导入失败
					$msg['code']=302;
					$msg['msg']="导出失败";
					return $msg;
				}
            }else{
                //导入失败
                $msg['code']=304;
                $msg['msg']="没有数据需要导出";
                return $msg;
            }

        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=301;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.":".$v."\r\n";
            }
            return $msg;
        }


    }

    /***    导入订单数据发货进入数据库      /order/export/addDeliver
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

     public function  addDeliver(Request $request){
         $operationing = $request->get('operationing');//接收中间件产生的参数
         $now_time=date('Y-m-d H:i:s',time());
//         $url_len=config('aliyun.img.url_len');
         $table_name='sys_file_warehouse';

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input = Input::all();

        /** 接收数据*/
        $importurl=$request->input('importurl');

        /*** 虚拟数据**/
        //$input['importurl']=$importurl='uploads/2020-07-02/execl_202007171745165224875532.xlsx';

        $rules = [
            'importurl' => 'required',
        ];
        $message = [
            'importurl.required' => '请上传文件',
        ];

        $validator = Validator::make($input, $rules, $message);

		$operationing->access_cause='批量发货';
		$operationing->table=$table_name;
		$operationing->table_id=null;
		$operationing->now_time=$now_time;
		$operationing->old_info=null;
		$operationing->new_info=null;
		$operationing->operation_type='create';
			
			
        if ($validator->passes()) {
            //处理业务逻辑
            /***现在开始处理业务逻辑**/
            $res = [];
            Excel::load($importurl,function ($reader) use ( &$res ){
                $res= $reader->all()->toArray();
            });

            //首先判断是不是可以进行发货处理，数据源是不是都可以使用
            //dump($res);
            /*** 对所有的数据进行粗加工   定义一个变量为cando=Y   如果碰到数据错误则为N **/
            try {
                $order_cando='Y';
                $order_err_info=null;
                $biaoji=1;
                foreach ($res as $k => $v){
                    $info[$k]['order_sn']=$v['订单号'];
                    $info[$k]['list_order_sn']=$v['子订单号'];
                    $info[$k]['deliver_sn']=$v['快递单号'];
                    $info[$k]['deliver_company']=$v['快递公司'];
                    $info[$k]['can_do_num']=intval($v['发货数量']);
                    //通过小订单号去拿取相关的信息，看看是不是状态都=2，如果有不是2个的，那就跳出去吧
                    $wherr['a.self_id']=$v['子订单号'];

                    $shop_order_list=DB::table('shop_order_list as a')
                        ->join('shop_order as b',function($join){
                            $join->on('a.order_sn','=','b.self_id');
                        }, null,null,'left')
                        ->where($wherr)
                        ->select(
                            'a.self_id',
                            'a.user_id',
                            'a.order_sn',
                            'a.pay_order_sn',
                            'a.number',
                            'a.real_nubmer',
                            'b.pay_status'
                        )->first();

                    if($shop_order_list){
                        if($shop_order_list->pay_status == 2){
                            //可以操作，还要判断发货数量是不是超过了数字

                            if( intval($v['发货数量'])+ $shop_order_list->real_nubmer > $shop_order_list->number){
                                $order_cando='N';
                                $order_err_info.=$biaoji.":小订单号".$v['小订单号']."发货数量超过了用户购买数量，请核实</br>";
                            }else{
                                //这里说明是可以处理的了
                                $info[$k]['user_id']=$shop_order_list->user_id;
                                $info[$k]['pay_order_sn']=$shop_order_list->pay_order_sn;
                                $info[$k]['number']=$shop_order_list->number;
                                $info[$k]['real_nubmer']=$shop_order_list->real_nubmer;
                            }
                        }else{
                            $order_cando='N';
                            $order_err_info.=$biaoji.":订单号".$v['订单号']."不是已支付的状态</br>";
                        }
                    }else{
                        $order_cando='N';
                        $order_err_info.=$biaoji.":小订单号".$v['子订单号']."查询不到信息</br>";
                    }

                    $biaoji++;
                }
            }catch (\Exception $e) {
                //dd($e);
                $msg['code'] = 303;
                $msg['msg'] = '请确保上传文件的类型和示例文件一致';
                return $msg;
            }


            if($order_cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $order_err_info;
                return $msg;
            }

            //以上为效验过程

            $info=[];


            if($info){
                $deliver_company_config=config('shop.deliver_company');
                $huy=[];
                foreach ($deliver_company_config as $k => $v){
                    $huy[$v['name']]=$v['key'];
                }

                //dump($info);
                $order=[];
                /***数据入库操作开始            事务开始的位置**/
                DB::beginTransaction();
                try{
                foreach ($info as $k => $v){

                    $order[$v['order_sn']]['user_id']=$v['user_id'];
                    $order[$v['order_sn']]['deliver_company']=$v['deliver_company'];
                    $order[$v['order_sn']]['deliver_company_id']=$huy[$v['deliver_company']];
                    $order[$v['order_sn']]['pay_order_sn']=$v['pay_order_sn'];
                    $order[$v['order_sn']]['deliver_sn']=$v['deliver_sn'];

                    //做发货的数据
                    $data_list['real_nubmer']=$v['real_nubmer'] + $v['can_do_num'];
                    $data_list['update_time']=$now_time;

                    $wherr['self_id']=$v['list_order_sn'];
                    DB::table('shop_order_list')->where($wherr)->update($data_list);
                }

                /*** 做一个发货的OA记录***/
                foreach ($order as $k => $v){
                    $data['self_id']=generate_id('oa_');
                    $data['user_id']=$v['user_id'];
                    $data['pay_order_sn']=$v['pay_order_sn'];
                    $data['order_sn']=$k;
                    $data['operation_type']='5';
                    $data['operation_total']='发货';
                    //$data['old_info']= json_encode($shop_order_list);
                    //$data['new_info']=json_encode($input);
                    $data['operating_way']='rear';
                    $data['operating_id']=$user_info->admin_id;
                    $data['operating_name']=$user_info->name;
                    $data['operating_time']=$now_time;
                    $data['create_time']=$data['update_time']=$now_time;
                    $data['deliver_company_id']=$v['deliver_company_id'];
                    $data['deliver_company']=$v['deliver_company'];
                    $data['deliver_company_sn']=$v['deliver_sn'];

                    DB::table('shop_order_oa')->insert($data);

                    $pay_where=[
                        ['pay_order_sn','=',$v['pay_order_sn']],
                    ];

                    $order_infos=DB::table('shop_order')->where($pay_where)
                        ->select(
                            'self_id',
                            'pay_status'
                        )->get()->toArray();

                    //这里是要更新进入数据库的数据
                    $data_order['pay_status']='3';
                    $data_order['update_time']=$now_time;

                    $cando='Y';             //初始化一个标记，如果是Y，则更新到pay表中去
                    foreach ($order_infos as $kk => $vv){
                        //dump($vv);
                        if($vv->self_id == $k){
                            $where_order['self_id']=$k;
                            DB::table('shop_order')->where($where_order)->update($data_order);      //修改商户订单的状态
                            $vv->pay_status=3;
                        }

                        if($vv->pay_status != 3){
                            $cando='N';
                        }
                    }

                    if($cando == 'Y'){
                        //则修改用户订单的状态
                        $where_pay_order['self_id']=$v['pay_order_sn'];
                        //dump($where_pay_order);
                        DB::table('shop_order_pay')->where($where_pay_order)->update($data_order);      //修改商户订单的状态
                    }

                    //dump($order_infos);

                }

                /***数据入库操作结束            事务结束的位置**/
                    DB::commit();

                    $msg['code'] = 200;
                    $msg['msg'] = '批量发货成功';
                    return $msg;

                }catch (\Exception $e) {
                    //接收异常处理并回滚
                    DB::rollBack();
                    $msg['code'] = 302;
                    $msg['msg'] = '操作失败';
                    return $msg;
                }


            }else{
                $msg['code'] = 301;
                $msg['msg'] = '没有要处理的数据';
                return $msg;
            }

        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 304;
            return $msg;
        }

    }

}
?>
