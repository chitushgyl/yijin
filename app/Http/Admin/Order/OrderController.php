<?php
namespace App\Http\Admin\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopOrderList;
use App\Models\Shop\ShopOrderCarriage;
use App\Models\Shop\ShopOrderEvaluate;
use App\Models\Shop\ErpShopGoodsSku;
use App\Models\User\UserTotal;
use App\Models\User\UserCapital;
use App\Models\User\UserWallet;
use App\Http\Controllers\ComputeController as Compute;

class OrderController  extends CommonController{
    /***    订单信息头部      /order/order/orderList
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
    /***    订单信息分页      /order/order/orderPage
     */
	public function orderPage(Request $request){
        $pay_status     =config('shop.pay_status');

        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
	//dd($group_info);
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $group_name     =$request->input('group_name');
        $order_sn       =$request->input('order_sn');
        $pay_order_sn   =$request->input('pay_order_sn');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        // dump($button_info);
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'order_ok_flag','value'=>'Y'],
			['type'=>'like','name'=>'group_name','value'=>$group_name],
			['type'=>'like','name'=>'self_id','value'=>$order_sn],
			['type'=>'like','name'=>'pay_order_sn','value'=>$pay_order_sn],
        ];
        $where=get_list_where($search);
        $select=['self_id','pay_order_sn','pay_status','order_type','money_goods','create_time','group_name','show_group_name','gather_name','gather_tel','gather_address'];

        $shopOrderListSelect=['order_sn','good_title','good_img','price','number','real_nubmer'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ShopOrder::where($where)->count(); //总的数据量

                $data['items']=ShopOrder::with(['shopOrderList' => function($query)use($shopOrderListSelect) {
                    $query->select($shopOrderListSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();

                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ShopOrder::where($where)->count(); //总的数据量

                $data['items']=ShopOrder::with(['shopOrderList' => function($query)use($shopOrderListSelect) {
                    $query->select($shopOrderListSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();


                break;

            case 'more':

                $data['total']=ShopOrder::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ShopOrder::with(['shopOrderList' => function($query)use($shopOrderListSelect) {
                    $query->select($shopOrderListSelect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                break;
        }


        /** 做一个数组，看那个是可以有发货的按钮的**/
        $rule=['1','5','6','8'];                    //无法发货的！！！

        foreach ($data['items'] as $k=>$v) {

            foreach ($v->shopOrderList as $kk=>$vv) {
                $vv->prices=number_format($vv->price/100,2);

                $vv->good_img=img_for($vv->good_img,'one');
            }

            $v->pay_status_color=$pay_status[$v->pay_status-1]['pay_status_color'];
            $v->pay_status_text=$pay_status[$v->pay_status-1]['pay_status_text'];

            $button_info2=[];

            foreach ($button_info as $kk => $vv){
                if(in_array($v->pay_status,$rule) && $vv->id=='267'){

                }else{
                    $button_info2[]=$vv;
                }
            }
            $v->button_info=$button_info2;

        }

        //dd($data['items']->toArray());
        //dd($data['items']);


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        // dd($msg);
        return $msg;
	}

    /***    发货处理      /order/order/createDeliver
     */
    public function  createDeliver(Request $request){
        //拿一个可选择的物流公司出来
        $data['deliver_company']=config('shop.deliver_company');

        /**接收数据*/
        $self_id        =$request->input('self_id');

       // $self_id='O202005112129041318137996';

        $where=[
            ['order_sn','=',$self_id],
        ];
        $select=['self_id', 'good_title','good_img', 'number','real_nubmer'];

        $data['shop_order_list_info']=ShopOrderList::where($where)->select($select)->get();

        if($data['shop_order_list_info']){
            foreach ($data['shop_order_list_info'] as $k => $v){

                $v->good_img=img_for($v->good_img,'one');

                //还可以发货的数量
                $v->can_do_number=$v->number - $v->real_nubmer;
                if($v->can_do_number < 0){
                    $v->can_do_number=0;
                }
                $v->text_reason=null;
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

    /***    发货进入数据库      /order/order/addDeliver
     */
    public function  addDeliver(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数

        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='shop_order_carriage';

        $operationing->access_cause         ='发货';
        $operationing->table                =$table_name;
        $operationing->now_time             =$now_time;
        $operationing->old_info             =null;
        $operationing->operation_type       ='create';


        /** 接收中间件参数**/
        $user_info                  = $request->get('user_info');//接收中间件产生的参数

        /**接收数据*/
        $order_sn                   =$request->input('order_sn');
        $deliver_company_id         =$request->input('deliver_company_id');
        $deliver_sn                 =$request->input('deliver_sn');
        $do_arr                     =json_decode($request->input('do_arr'),true)				;                      //要操作的发货的数据

        //假设传递过来一个订单号
        $input=$request->all();

        /*** 虚拟数据   
        $input['order_sn']=$order_sn='O202010231845212137514382';
        $input['deliver_company_id']=$deliver_company_id='2';
        $input['deliver_sn']=$deliver_sn='212121212';
        $input['do_arr']=$do_arr=[
            '0'=>['self_id'=>'list_202010231845212137611818',
                'can_do_num'=>'2',
                'text_reason'=>'2',],
            ];
**/
        $rules=[
            'order_sn'=>'required',
            'deliver_company_id'=>'required',
            'deliver_sn'=>'required',
            'do_arr'=>'required',
        ];
        $message=[
            'order_sn.required'=>'请填写订单号',
            'deliver_company_id.required'=>'请选择运输公司',
            'deliver_sn.required'=>'请输入运输单号',
            'do_arr.required'=>'请输入发货信息',
        ];

//dd($do_arr);

        $validator=Validator::make($input,$rules,$message);


        if($validator->passes()){
            $where=[
                ['order_sn','=',$order_sn],
            ];

            //抓取old_info
            $shop_order_list=ShopOrderList::where($where)
                ->select('self_id','total_user_id','order_sn','pay_order_sn','number','real_nubmer','good_title')->get();
            //dump($do_arr);
            //dump($shop_order_list->toArray());
            if($shop_order_list){
                $deliver_company_config=config('shop.deliver_company');
                $deliver_company=$deliver_company_config[$deliver_company_id-1]['name'];
                //dump($deliver_company);
                //第一步，做一个运输数据信息
		//dd($shop_order_list);
                $data['self_id']            =generate_id('carriage_');
                $data['total_user_id']      =$shop_order_list[0]->total_user_id;
                $data['pay_order_sn']       =$shop_order_list[0]->pay_order_sn;
                $data['order_sn']           =$shop_order_list[0]->order_sn;
                $data['deliver_type']       ='send';
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['create_time']        =$data['update_time']       =$now_time;
                $data['deliver_company_id'] =$deliver_company_id;
                $data['deliver_company']    =$deliver_company;
                $data['deliver_company_sn'] =$deliver_sn;
                $data['info']               =json_encode($shop_order_list,JSON_UNESCAPED_UNICODE);
                $id=ShopOrderCarriage::insert($data);
                $operationing->table_id     =$data['self_id'];
                $operationing->new_info     =$data;


                /** 第二步，把新的发货数量追加进入实发数量信息中***/
                foreach ($do_arr as $k => $v){
                    $whererer['self_id']=$v['self_id'];                         //这个是条件
                    $data_list['real_nubmer']=$shop_order_list[$k]->real_nubmer + $v['can_do_num'];
                    $data_list['update_time']=$now_time;
                    ShopOrderList::where($whererer)->update($data_list);      //修改商户订单的状态

                }
                /***第三步， 处理shop_order状态，如果所有的发货数量都》那个数量，并且发货状态是2则发货****/
                $pay_where=[
                    ['self_id','=',$order_sn],
                ];
                $order_info=ShopOrder::where($pay_where)->select('self_id','pay_status')->get()->toArray();
                $data_order['pay_status']   ='3';
                $data_order['update_time']  =$now_time;

                ShopOrder::where($pay_where)->update($data_order);      //修改商户订单的状态

                //dd($order_info);


                if($id){
                    $msg['code']=200;
                    $msg['msg']='发货成功';
                    return $msg;
                }else{
                    $msg['code']=302;
                    $msg['msg']='发货失败';
                    return $msg;
                }

            }else{
                $msg['code']=301;
                $msg['msg']="没有查询到数据";
                return $msg;
            }

        }else{
            //前端用户验证没有通过1
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }

            //dd($msg);

            return $msg;
        }


    }


    /***    订单详情      /order/order/orderDetails
     */

    public function  orderDetails(Request $request){
        /**接收数据*/
        $order_sn=$request->input('self_id');

        //$order_sn='O202010231845212137514382';
        $where['a.self_id']=$order_sn;
        $data['order_info']=DB::table('shop_order as a')
            ->join('pay as b',function($join){
                $join->on('a.pay_order_sn','=','b.self_id');
            }, null,null,'left')
            ->join('user_reg as c',function($join){
                $join->on('a.total_user_id','=','c.total_user_id');
            }, null,null,'left')
            ->where($where)
            ->select(
                'a.self_id as order_sn',
                'a.pay_order_sn',
                'a.pay_status',
                'a.create_time',
                'a.group_name',
                'a.money_goods',
                'a.money_serves',
                'a.money_freight',
                'a.discounts_single_total',
                'a.discounts_all_total',
                'a.discounts_activity_total',
                'a.create_time',
                'a.gather_address_id',
                'a.gather_name',
                'a.gather_tel',
                'a.gather_address',
                'a.gather_star_time',
                'a.gather_end_time',
                'b.show_group_name',
                'b.hosturl',
                'b.pay_way',
                'b.pay_time',
                'b.pay_mode',
                'b.pay_message',
                'b.show_group_name',
                'c.token_img',
                'c.tel',
                'c.token_name'
            )
            ->first();



        if($data['order_info']){
            $where_list['a.order_sn']=$order_sn;
            $data['shop_order_list'] =DB::table('shop_order_list as a')
                ->join('user_coupon as b',function($join){
                    $join->on('a.coupon_id','=','b.self_id');
                }, null,null,'left')
                ->where($where_list)
                ->select(
                    'a.price',
                    'a.number',
                    'a.good_title',
                    'a.good_img',
                    'a.good_info',
                    'a.serve_info',
                    'a.remarks',
                    'a.coupon_id',
                    'a.discounts_single',
                    'a.real_nubmer',
                    'b.coupon_title'
                )
                ->get()->toArray();
            foreach ($data['shop_order_list'] as $k => $v){
                $v->price=number_format($v->price/100,2);           //单价
                $v->discounts_single_show=number_format($v->discounts_single/100,2);           //单品优惠券优惠金额

                $v->good_img=img_for($v->good_img,'one');
            }


            //下面开始查询这个订单的发货信息，投诉信息等元素
//            use App\Models\Shop\ShopOrderCarriage;
//            use App\Models\Shop\ShopOrderEvaluate;
            $where_chu=[
                ['order_sn','=',$order_sn],
                ['delete_flag','=','Y'],
            ];
            $select_carriage=['self_id','create_user_name','deliver_type','deliver_company','deliver_company_sn','info','create_time'];
            $data['carriage'] =ShopOrderCarriage::where($where_chu)->select($select_carriage)->get();

            $select_carriage=['self_id','create_user_name','good_star_level','complain','create_time'];
            $data['evaluate'] =ShopOrderEvaluate::where($where_chu)->select($select_carriage)->get();


            //dd($data);


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
//            dd($msg);
            return $msg;


//            dump($shop_order_list);
//            dd($order_info);

        }else{
                $msg['code']=300;
                $msg['msg']="没有查询到数据";
                return $msg;
            }






    }

    /***    创建订单      /order/order/createOrder
     */
    public function  createOrder(Request $request){
        /** 查询下这个商品目前的价格区间*/
        $good_id		='good_202010261916293512632330';
        $sku_where=[
            ['good_id','=',$good_id],
        ];
        $sku=ErpShopGoodsSku::where($sku_where)->select('cost_price','sell_number')->orderBy('cost_price','asc')->get();

        $cando='Y';
        $data['good_id']=$good_id;
        $data['price_show']=null;
        $data['price']=null;         //当前价格


        foreach ($sku as $k => $v){
            if($cando == 'Y'){
                $list_count_where=[
                    ['good_id','=',$good_id],
                    ['price','=',$v->cost_price],
                ];
                $count=DB::table('shop_order_list')->where($list_count_where)->distinct('total_user_id')->count();

                if($count < $v->sell_number){
                    $data['price_show']=number_format($v->cost_price/100, 2);
                    $data['price']=$v->cost_price;         //当前价格
                    $cando = 'N';
                }

            }
        }
        if($data['price']){
            $msg['code']=200;
            $msg['msg']='数据拉取成功';
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']='已售罄';
            return $msg;
        }

    }

    /***    订单入库      /order/order/addOrder
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  addOrder(Request $request,Compute $compute){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='shop_order';

        $operationing->access_cause     ='创建报单';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();
	//dd($input);
        /** 接收数据*/
        $tel              =$request->input('tel');
        $good_id          =$request->input('good_id');
        $price            =$request->input('price');
        $number           =$request->input('number');

        /*** 虚拟数据
        //$input['tel']        =$tel='123';
        $input['good_id']    =$good_id='good_202010261916293512632330';
        $input['price']      =$price='100000';
        $input['number']     =$number='100';
**/

        $rules=[
            'tel'=>'required',
            'good_id'=>'required',
            'price'=>'required',
            'number'=>'required',
        ];
        $message=[
            'tel.required'=>'用户手机号码不能为空',
            'good_id.required'=>'商品必须选择',
            'price.required'=>'价格必须',
            'number.required'=>'数量必须',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //通过这个用户去拿他姓名以及其他的元素
            $where=[
                ['tel','=',$tel],
            ];

            $select=['self_id','tel','true_name','grade_id','father_user_id1','father_user_id2','father_user_id3','father_user_id4','father_user_id5','father_user_id6','father_user_id7','father_user_id8'];
            $selectCapital=['total_user_id','performance','performance_share','share','leiji5','leiji10','leiji20','leiji'];
            //把他的所有的上级抓出来看看
            $user=UserTotal::with(['userCapital' => function($query) use($selectCapital){
                $query->select($selectCapital);
            }])->where($where)->select($select)->first();

            if(empty($user)){
                $msg['code']=301;
                $msg['msg']='手机号码无用户，请去用户中心创建';
                return $msg;
            }

            //dump($user->toArray());
            $good_where=[
                ['self_id','=',$good_id],
            ];
            $selectGood=['good_title','group_code','group_name','good_name','thum_image_url','good_type'];
            $good_info=DB::table('erp_shop_goods')->where($good_where)->select($selectGood)->first();

            if(empty($good_info)){
                $msg['code']=302;
                $msg['msg']='查询不到商品';
                return $msg;
            }


            //dd($good_info);


            //去生成订单
            //做一个支付单号过来，就是客户显示的订单号
            $pay_order_sn=generate_id('O');

            $order['gather_address_id']='001';
            $order['gather_name']=$user->true_name;// 收货人姓名
            $order['gather_tel']=$tel;
            $order['gather_address']='自提';
            $order['self_id']=generate_id('O');      			//商户订单号
            $order['total_user_id']=$user->self_id;
            $order['pay_order_sn']=$pay_order_sn;
            $order['pay_status']='2'; 							//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
            $order['logistics_status']='1';						//物流状态(1,待拣货，2拣货中，3待配送，4配送中，5已完成，6退货中，7已退货)
            $order['order_type']='share';					//团购group，单独购买alone，赠送give,积分integral，批发wholesale,购物车cart，扫码购scan，股份share
            $order['create_time']=$order['update_time']=$now_time;
            $order['group_code']=$good_info->group_code;
            $order['group_name']=$good_info->group_name;
            $order['show_group_code']=$good_info->group_code;			//那个门店发起的
            $order['show_group_name']=$good_info->group_name;			//那个门店发起的
            $order['money_goods']=$price*$number;				//商品总价值
            $order['money_serves']='0';				//服务总计
            $order['discounts_single_total']='0';	//单品总计
            $order['discounts_all_total']='0';		//全场总计
            $order['discounts_activity_total']='0';	//活动总计

            $id=DB::table('shop_order')->insert($order);

            $order_list['total_user_id']=$user->self_id;
            $order_list['self_id']=generate_id('list_');
            $order_list['order_sn']=$order['self_id'];
            $order_list['pay_order_sn']=$pay_order_sn;
            $order_list['pay_status']='2'; 							//订单状态，1未支付2已支付(待收货）3配送中,4已完成,5取消订单,6已退款,7已评价的订单,8已关闭,9已送达
            $order_list['price']=$price;//单价
            $order_list['number']=$number;//数量
            $order_list['good_id']=$good_id;//商品id
            $order_list['good_title']=$good_info->good_title;							//商品的标题
            $order_list['good_name']=$good_info->good_name;							//商品的名称
            $order_list['good_img']=$good_info->thum_image_url;						//商品的图片
            $order_list['good_type']=$good_info->good_type;							//商品类型，用于订单中解开good_info
            $order_list['group_code']=$good_info->group_code;
            $order_list['group_name']=$good_info->group_name;
            $order_list['create_time']=$order_list['update_time']=$now_time;

            DB::table('shop_order_list')->insert($order_list);

            if($id){
                /** 现在开始计算触发奖励的事情**/
                /***  第一步，奖励给他自己股份，按照购买数量*1000，同时1,5,10,20 分别为0,1000,3000,7000    累计购买也做完了
                 *    第二步，奖励给他的上级个人奖，1为10%，2为5%
                 *    第三步，发起团队奖励，
                 *    第四步，处理他的上级是不是可以升级的问题，只算他的上级
                 **/
                if($number>=20){
                    $ewai=7000;
                }else if($number>=10){
                    $ewai=3000;
                }else if($number>=5){
                    $ewai=1000;
                }else{
                    $ewai=0;
                }

                $abc=$user->userCapital->leiji+$number;
                if($abc>=20){
                    $ewai2=$user->userCapital->leiji20+$user->userCapital->leiji10+$user->userCapital->leiji5;
                    $capital_data['leiji20']=0;
                    $capital_data['leiji10']=0;
                    $capital_data['leiji5']=0;
                }else if($abc>=10){
                    $ewai2=$user->userCapital->leiji10+$user->userCapital->leiji5;
                    $capital_data['leiji10']=0;
                    $capital_data['leiji5']=0;
                }else if($abc>=5){
                    $ewai2=$user->userCapital->leiji5;
                    $capital_data['leiji5']=0;
                }else{
                    $ewai2=0;
                }

				$yeji=$price*$number;

               // dump($user->toArray());
                $capital_where['total_user_id']=$user->self_id;
                $capital_data['share']=$user->userCapital->share+$number*1000+$ewai+$ewai2;
				$capital_data['performance']=$user->userCapital->performance+$yeji;
				$capital_data['performance_share']=$user->userCapital->performance_share+$number;
                $capital_data['update_time']=$now_time;
                $capital_data['leiji']=$user->userCapital->leiji+$number;



                UserCapital::where($capital_where)->update($capital_data);
                /** 做资金的流水，属性为股份**/

                $data['self_id']        =generate_id('wallet_');
                $data['total_user_id']  =$user->self_id;
                $data['capital_type']   ='share';
                $data['produce_type']   ='IN';
                $data['produce_cause']  ='订单购买';
                $data['create_time']    =$now_time;
                $data['money']          =$number*1000;
                $data['order_sn']       =$user->self_id;
                $data['now_money']      =$user->userCapital->share+$number*1000;
                $data['now_money_md']   =get_md5($user->userCapital->share+$number*1000);
                $data['ip']             =$request->getClientIp();
                $data['wallet_status']  ='SU';
                UserWallet::insert($data);

                if($ewai>0){
                    $data['self_id']        =generate_id('wallet_');
                    $data['total_user_id']  =$user->self_id;
                    $data['capital_type']   ='share';
                    $data['produce_type']   ='IN';
                    $data['produce_cause']  ='订单赠送';
                    $data['create_time']    =$now_time;
                    $data['money']          =$ewai;
                    $data['order_sn']       =$user->self_id;
                    $data['now_money']      =$user->userCapital->share+$number*1000+$ewai;
                    $data['now_money_md']   =get_md5($data['now_money']);
                    $data['ip']             =$request->getClientIp();
                    $data['wallet_status']  ='SU';
                    UserWallet::insert($data);
                }

                if($ewai2>0){
                    $data['self_id']        =generate_id('wallet_');
                    $data['total_user_id']  =$user->self_id;
                    $data['capital_type']   ='share';
                    $data['produce_type']   ='IN';
                    $data['produce_cause']  ='额外奖励';
                    $data['create_time']    =$now_time;
                    $data['money']          =$ewai2;
                    $data['order_sn']       =$user->self_id;
                    $data['now_money']      =$user->userCapital->share+$number*1000+$ewai+$ewai2;
                    $data['now_money_md']   =get_md5($data['now_money']);
                    $data['ip']             =$request->getClientIp();
                    $data['wallet_status']  ='SU';
                    UserWallet::insert($data);
                }



                /**第二步，开始计算个人奖励，同时把业绩算给他的上级，做业绩统计***/
                

                for($i=1;$i<9;$i++){
                        $fwfhiwf='father_user_id'.$i;
                        if($user->$fwfhiwf){
                            $compute->personCompute($yeji,$user->$fwfhiwf,$i,$now_time,$number);
                        }
                }

                /** 发起团队奖励**/
                /** 先看这个买单的人是什么级别，碰到比他高级别的人才可以享受业绩的待遇*/
                //$egtheuiy=10;

                $abcd=[4,3,3];

                for($i=1;$i<9;$i++){
                    $total=array_sum($abcd);
                    //dump($egtheuiy);
                    if($total>0){
                        $fwfhiwf='father_user_id'.$i;
                        if($user->$fwfhiwf){
                            $abcd=$compute->groupCompute($yeji,$user->$fwfhiwf,$user->grade_id,$now_time,$abcd);

                        }
                    }
                }


                /** 计算他的上上级能不能升级*/
                for($i=1;$i<9;$i++){
                    //dump($egtheuiy);
                    $fwfhiwf='father_user_id'.$i;
                    if($user->$fwfhiwf){
                        $compute->gradeCompute($user->$fwfhiwf,$now_time);
                    }
                }


//                if($user->father_user_id2){
//					//dump(21221);
//                    $compute->gradeCompute($user->father_user_id2,$now_time);
//                }

                //DD(12112);

                $msg['code']=200;
                $msg['msg']='操作成功';
                return $msg;

            }else{
                $msg['code']=303;
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


}
?>
