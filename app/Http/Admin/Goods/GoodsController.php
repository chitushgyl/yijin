<?php
namespace App\Http\Admin\Goods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\StatusController as Status;
use App\Models\Shop\ErpShopGoods;
use App\Models\SysAttributeInfo;
use App\Models\Shop\ErpShopGoodsSku;
use App\Models\Group\SystemGroup;
use App\Http\Controllers\DetailsController as Details;
class GoodsController extends CommonController{
    /***    商品信息头部      /goods/goods/goodsList
     */
    public function  goodsList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $data['good_type']      =config('shop.good_type');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;


        //dd($msg);
        return $msg;

    }

    /***    商品信息分页      /goods/goods/goodsPage
     */
	public function  goodsPage(Request $request){
        /** 接收中间件参数**/
        $good_type_show  =config('shop.good_type');
        $good_type_show  =array_column($good_type_show,'name','key');

        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num                    =$request->input('num')??10;
        $page                   =$request->input('page')??1;
        $group_name             =$request->input('group_name');
        $parent_classify_name   =$request->input('parent_classify_name');
		$classify_name          =$request->input('classify_name');
        $good_name              =$request->input('good_name');
        $good_type              =$request->input('good_type');
        $good_status            =$request->input('good_status');

        $listrows               =$num;
        $firstrow               =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
			['type'=>'=','name'=>'group_name','value'=>$group_name],
            ['type'=>'like','name'=>'parent_classify_name','value'=>$parent_classify_name],
			['type'=>'like','name'=>'classify_name','value'=>$classify_name],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'=','name'=>'good_type','value'=>$good_type],
            ['type'=>'=','name'=>'good_status','value'=>$good_status],

        ];

        $where=get_list_where($search);
        $select=['self_id','good_title','good_type','classify_name','parent_classify_name','create_user_name','create_time','commodity_number','good_info',
            'good_status','thum_image_url','group_name','sell_start_time','sell_end_time'];

        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach($data['items'] as $k => $v){

            if($v->sell_start_time=='2018-11-30 00:00:00' && $v->sell_end_time=='2099-12-31 00:00:00'){
                $v->sell_time_show='长期有效';
            }else{
                $v->sell_time_show=$v->sell_start_time.'～'.$v->sell_end_time;
            }

            $v->good_info=json_decode($v->good_info,true);


            //商品属性1
            $v->good_type_show=$good_type_show[$v->good_type]??null;


            $v->thum_image_url=img_for($v->thum_image_url,'one');

            $v->button_info=$button_info;

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($data['items']->toArray());
        return $msg;

	}



    /***    新建/修改商品      /goods/goods/createGoods
     */
    public function createGoods(Request $request){
        /** 读取配置文件信息**/
        $data['good_status']    =config('shop.good_status');
		$data['good_type']      =config('shop.good_type');

        /** 接收数据*/
        $self_id                =$request->input('self_id');
        $self_id='good_202101091805128604759572';
        $data['goods_info']=null;

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','good_type','classify_id','good_name','good_title','good_describe', 'group_code','good_info',
            'good_status','commissioned_flag','thum_image_url','scroll_image_url','details_image_url',
            'sku_flag','check_flag','jump_flag','price_show_flag','label_info','sell_start_time','sell_end_time','cart_flag',
            'label_flag','label_image_url','label_text','label_start_time','label_end_time'];

        $selectSku=['self_id','good_id','good_name','sale_price','cost_price','commissioned_price','sell_number','good_status','sort','integral_scale','serve'];
        $data['goods_info']=ErpShopGoods::with(['erpShopGoodsSku' => function($query)use($selectSku) {
            $query->select($selectSku);
            $query->where('delete_flag','=','Y');
        }])->where($where)
            ->select($select)->first();

        if($data['goods_info']){

            $data['goods_info']->thum_image_url     =img_for($data['goods_info']->thum_image_url,'more');
            $data['goods_info']->scroll_image_url   =img_for($data['goods_info']->scroll_image_url,'more');
            $data['goods_info']->details_image_url  =img_for($data['goods_info']->details_image_url,'more');
            $data['goods_info']->good_info          =json_decode($data['goods_info']->good_info);

            foreach($data['goods_info']->erpShopGoodsSku as $k=>$v){
                $v->sale_price=$v->sale_price/100;
                $v->cost_price=$v->cost_price/100;
                $v->commissioned_price=$v->commissioned_price/100;
            }
            //dd($data['goods_info']);
            //$data['goods_info']->label_info=json_decode($data['goods_info']->label_info);
            $data['goods_info']->good_info=json_decode($data['goods_info']->good_info);

            $data['goods_info']->label_image_url=img_for($data['goods_info']->label_image_url,'more');

        }



        //dd($data['goods_info']->toArray());
		$data['key_info']=[
            ['key'=>'thum_image_url',
                'count'=>'1',
                'name'=>'微缩图'],
            ['key'=>'scroll_image_url',
                'count'=>'5',
                'name'=>'商品轮播图'],
            ['key'=>'details_image_url',
                'count'=>'12',
                'name'=>'商品详情图'],
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    商品数据提交      /goods/goods/addGoods
     */
    public function addGoods(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='erp_shop_goods';

        $operationing->access_cause='新建/修改商品';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;


        $user_info          = $request->get('user_info');                //接收中间件产生的参数
        $input=$request->all();
		
        /** 接收数据*/
		//dd($input);
        $self_id            =$request->input('self_id')??'Null';
        $group_code         =$request->input('group_code');
        $good_name          =$request->input('good_name');
        $good_title         =$request->input('good_title');
        $good_type          =$request->input('good_type')??'o2o';
        $classify_id        =$request->input('classify_id');
        $good_describe      =$request->input('good_describe');
        $good_status        =$request->input('good_status');
        $sell_start_time    =$request->input('sell_start_time');
        $sell_end_time      =$request->input('sell_end_time');
        $thum_image_url     =$request->input('thum_image_url');
        $scroll_image_url   =$request->input('scroll_image_url');
        $details_image_url  =$request->input('details_image_url');

        //标签信息
        $label_flag         =$request->input('label_flag');
        $label_text         =$request->input('label_text');
        $label_image_url    =$request->input('label_image_url');
        $label_start_time   =$request->input('label_start_time');
        $label_end_time     =$request->input('label_end_time');

        $price_show_flag    =$request->input('price_show_flag')??'A';
        $cart_flag          =$request->input('cart_flag')??'all';
        $commissioned_flag  =$request->input('commissioned_flag')??'Y';
        $jump_flag          =$request->input('jump_flag')??'Y';
        $check_flag         =$request->input('check_flag')??'Y';
        $sku_flag           =$request->input('sku_flag')??'Y';
        $erp_shop_goods_sku =$request->input('erp_shop_goods_sku');

		//商品差异化中心
        $good_info          =$request->input('good_info');

		//dump($thum_image_url);
		//dump($scroll_image_url);
		//dump($details_image_url);
        /*** 虚拟数据**/
//        $input['self_id']=$self_id='good_202007011336328472133661';
        //$input['group_code']=$group_code='1234';
        //$input['good_name']=$good_name='212121212';
        //$input['good_title']=$good_title='212121212';
        //$input['classify_id']=$classify_id='212121212';
        //$input['good_describe']=$good_describe='212121212';
        //$input['thum_image_url']=$thum_image_url22='212121212';
        //$input['scroll_image_url']=$scroll_image_url22='212121212';
        //$input['details_image_url']=$details_image_url22='212121212';

        //$input['good_status']=$good_status='Y';
        //$input['erp_shop_goods_sku']=$erp_shop_goods_sku=[
        //    '0'=>[
        ////        'self_id'=>'sku_202007011336328692892904',
        //        'good_name'=>'产品1',
        //        'cost_price'=>'15.38',
        //        'commissioned_price'=>'20.99',
        //        'sale_price'=>'21.99',
        //        'thum_image_url'=>'',
        //    ],

         //   '1'=>[
        //        'good_name'=>'产品2',
         //       'cost_price'=>'155.38',
        //        'commissioned_price'=>'165.99',
        //        'sale_price'=>'215.99',
         //       'thum_image_url'=>'',
        //    ],
       // ];

        //处理标签的时间
        $rules=[
            'good_name'=>'required',
            'good_title'=>'required',
            'thum_image_url'=>'required',
			'scroll_image_url'=>'required',
			'details_image_url'=>'required',
			'erp_shop_goods_sku'=>'required',
        ];
        $message=[
            'good_name.required'=>'请填写商品名称',
            'good_title.required'=>'请填写商品标题',
			'thum_image_url.required'=>'缩略图必须有一张',
			'scroll_image_url.required'=>'轮播图必须有一张',
			'details_image_url.required'=>'详情图必须有一张',
            'erp_shop_goods_sku.required'=>'SKU信息必须有',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //效验SKU的有效性
            //做一个数组，SKU里面这个里面必须包含的元素
			$rulesssss=['good_name'=>'商品名称','cost_price'=>'成本价格','commissioned_price'=>'代售价格','sale_price'=>'销售价格'];

			$rule=array_keys($rulesssss);
            $rule_count=count($rule);

            $msg['msg']=null;
            $cando='Y';
            $abcs=1;

            foreach($erp_shop_goods_sku as $k => $v){
                $art222=array_keys($v);
                //取一个交集出来，然后比较长度
                $result=array_intersect($rule,$art222);
                $result_count=count($result);
                if($rule_count != $result_count){
                    //说明缺少参数
                    $msg['code']=302;
                    $msg['msg']='模板数组缺少必要参数';
                    //dd($msg);
                    return $msg;
                }

				foreach($rulesssss as $kk => $vv){
                    if($v[$kk]){
                        if(in_array($kk,['cost_price','commissioned_price','sale_price'])){
                            if($v[$kk]<0){
                                $cando='N';
                                $msg['msg'].=$abcs.": ".$vv." 必须大于0</br>";
                                $abcs++;
                            }
                        }
                    }else{
                        $cando='N';
                        $msg['msg'].=$abcs.": ".$vv." 缺失</br>";
                        $abcs++;
                    }
				}

                //还要判断    销售价格 》代售价格   成本价格不管
                if($v['commissioned_price'] > $v['sale_price']){
                    $cando='N';
                    $msg['msg'].=$abcs.": 销售价格必须大于代售价格</br>";
                    $abcs++;
                }

            }


			if($cando=='N'){
				$msg['code']=600;
				return $msg;
			}

		/** 开始制作数据了*/
//dd(2121);
            $data['good_type']          =$good_type;
            $data['good_name']          =$good_name;
            $data['good_title']         =$good_title;
            $whereClassify=[
                ['delete_flag','=','Y'],
                ['self_id','=',$classify_id],
            ];
            $selsec_shop_classify=['self_id','type','parent_name','parent_id','name'];
            $catalog=SysAttributeInfo::where($whereClassify)->select($selsec_shop_classify)->first();
			//dd(1111);
            if($catalog){
                $data['parent_classify_name']       =$catalog->name;               //大分类名称
                $data['parent_classify_id']         =$catalog->parent_id;                   //大分类id
                $data['classify_id']                =$catalog->self_id;                                     //小分类id
                $data['classify_name']              =$catalog->name;                             //小分类名称
            }

            $data['good_describe']		=$good_describe;                      //商品描述
            $data['good_status']		=$good_status;
            $data['search_label']		=$good_name.'*'.$good_title;            //搜索标签

            //商品售卖时间
            $data['sell_start_time']=$sell_start_time??'2018-11-30 00:00:00';
            $data['sell_end_time']=$sell_end_time??'2099-12-31 00:00:00';

            //微缩图
            $data['thum_image_url'] = img_for($thum_image_url,'in');
            $data['scroll_image_url'] = img_for($scroll_image_url,'in');
            $data['details_image_url'] = img_for($details_image_url,'in');

			//dd($label_info);
            /**处理一下标签 **/
            $data['label_flag']         =$label_flag;
            $data['label_image_url']    = img_for($label_image_url,'in');
            $data['label_text']         =$label_text;
            $data['label_start_time']   =$label_start_time??'2018-11-30 00:00:00';
            $data['label_end_time']     =$label_end_time??'2099-12-31 00:00:00';

            $data['good_info']              =json_encode($good_info);
            $data['price_show_flag']        =$price_show_flag;
            $data['cart_flag']              =$cart_flag;
            $data['sku_flag']               =$sku_flag;
            $data['check_flag']             =$check_flag;
            $data['jump_flag']              =$jump_flag;

			//dd($data);
            /** 制作差异化的事情**/
			//dd($data);

            /** 下面开始做sku的数据**/
            foreach ($erp_shop_goods_sku as $k => $v){
                $sku2[$k]['sale_price']                 = intval($v['sale_price'] * 100);
                $sku2[$k]['commissioned_price']         = intval($v['commissioned_price'] * 100);
                $sku2[$k]['cost_price']                 = intval($v['cost_price'] * 100);
                $sku2[$k]['good_name']                  = $v['good_name'];
                $sku2[$k]['sort']                       =($k+1);
                $sku2[$k]['integral_scale']             = '0';
                $sku2[$k]['sell_number']                = $v['sell_number'];
                $sku2[$k]['sell_start_time']            ='2018-11-30 00:00:00';
                $sku2[$k]['sell_end_time']              ='2099-12-31 00:00:00';
                $sku2[$k]['good_status']                ='Y';
                if (array_key_exists('self_id', $v)) {
                    //这个里面说明包含有self_id
                    $sku2[$k]['self_id']            =$v['self_id'];
                    $sku2[$k]['flag']               ='update';
                }else{
                    $sku2[$k]['self_id']            =generate_id('sku_');
                    $sku2[$k]['flag']               ='insert';
                }

                //把所有的sku价格放在一个数组内
                $wrkui[]=$sku2[$k]['sale_price'];

            }


            $data['min_price']          = min($wrkui);
            $data['max_price']          = max($wrkui);


            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $old_info=ErpShopGoods::where($where)->first();

            $id=false;
            /** 下面开始处理数据进入数据库***/
			if ($old_info) {
				$operationing->access_cause='修改商品';
				$operationing->operation_type='update';

				//修改商品
				$data['update_time'] = $now_time;
				$id = ErpShopGoods::where($where)->update($data);

				foreach ($sku2 as $k => $v) {

					if($v['flag'] == 'insert'){
						//新增的SKU
						$sku_insert = $v;
						$sku_insert['create_user_id']       = $user_info->admin_id;
						$sku_insert['create_user_name']     = $user_info->name;
						$sku_insert['self_id']              = generate_id('sku_');
						$sku_insert['update_time']          = $sku_insert['create_time'] = $now_time;
						$sku_insert['good_id']              = $self_id;
						$sku_insert['group_code']           =$old_info->group_code;
						$sku_insert['group_name']           =$old_info->group_name;
						unset($sku_insert['flag']);
						ErpShopGoodsSku::insert($sku_insert);

					}else{
						//修改的SKU1
						$sku_update = $v;
						$sku_update['update_time']          = $now_time;
						unset($sku_update['flag']);

						$sku_where['self_id']=$v['self_id'];
						ErpShopGoodsSku::where($sku_where)->update($sku_update);

					}

				}

                }else {
                    $group_name=SystemGroup::where('group_code','=',$group_code)->value('group_name');

                    $data['group_code']                 =$group_code;
                    $data['group_name']                 =$group_name;
                    $data['create_user_id']             = $user_info->admin_id;
                    $data['create_user_name']           = $user_info->name;
                    $data['self_id']                    = generate_id('good_');
                    $data['update_time']                = $data['create_time'] = $now_time;
					$id = ErpShopGoods::insert($data);

					//处理SKU2的信息进入数据库
					foreach ($sku2 as $k => $v) {
						$datt = $v;
						$datt['create_user_id'] = $user_info->admin_id;
						$datt['create_user_name'] = $user_info->name;
						$datt['self_id'] = generate_id('sku_');
						$datt['update_time'] = $datt['create_time'] = $now_time;
						$datt['good_id'] = $data['self_id'];
						$datt['group_code']=$group_code;
						$datt['group_name']=$group_name;
						unset($datt['flag']);

						//dump($datt);
						ErpShopGoodsSku::insert($datt);

					}
                }
			//dd($data['self_id']);
			//dd($self_id);
            $operationing->table_id=$old_info?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;


            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
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
        //记录操作日志
        //dd($msg);


    }

    /***    商品删除      /goods/goods/skuDeleteFlag
     */

    public function skuDeleteFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='erp_shop_goods_sku';
        $medol_name='ErpShopGoodsSku';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='sku_202010231057303898768541';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }

    /***    商品上下架      /goods/goods/goodsStatusFlag
     */
    public function goodsStatusFlag(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数

        $now_time=date('Y-m-d H:i:s',time());
        $table_name='erp_shop_goods';

        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where['self_id']=$self_id;
        $select_goods=['good_status', 'update_time', 'group_code', 'group_name'];
        $old_info=ErpShopGoods::where($where)->select($select_goods)->first();

        $operationing->access_cause='状态改变';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=null;
        $operationing->operation_type='status';

        if($old_info){
            $data['update_time']=$now_time;
            if($old_info->good_status=='Y'){
                $data['good_status']='N';
            }else{
                $data['good_status']='Y';
            }
            $id=ErpShopGoods::where($where)->update($data);

            if($id){
                $msg['code']=200;
                $msg['msg']="操作成功";
                $msg['data']=(object)$data;
                $operationing->new_info=$data;
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']="操作失败";
                return $msg;
            }

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }

    /***    商品删除      /goods/goods/goodsDeleteFlag
     */

    public function goodsDeleteFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='erp_shop_goods';
        $medol_name='ErpShopGoods';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }



    /***    商品详情     /goods/goods/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
	//$data['good_status']    =config('shop.good_status');
	//$data['good_type']      =config('shop.good_type');

        /** 接收数据*/
        $self_id                =$request->input('self_id');
        //$self_id='goods202001091642050179519887';
        $data=null;
	    $goods_info=null;
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','good_type','classify_id','good_name','good_title','good_describe', 'group_code','good_info',
            'good_status','commissioned_flag','thum_image_url','scroll_image_url','details_image_url',
            'sku_flag','check_flag','jump_flag','price_show_flag','label_info','sell_start_time','sell_end_time','cart_flag'];

        $selectSku=['self_id','good_id','good_name','sale_price','cost_price','commissioned_price','sell_number','good_status','sort','integral_scale','serve'];
        $goods_info=ErpShopGoods::with(['erpShopGoodsSku' => function($query)use($selectSku) {
            $query->select($selectSku);
            $query->where('delete_flag','=','Y');
        }])->where($where)
            ->select($select)->first();
        if($goods_info){

            $goods_info->thum_image_url     =img_for($goods_info->thum_image_url,'more');
            $goods_info->scroll_image_url   =img_for($goods_info->scroll_image_url,'more');
            $goods_info->details_image_url  =img_for($goods_info->details_image_url,'more');
            $goods_info->good_info          =json_decode($goods_info->good_info);

            foreach($goods_info->erpShopGoodsSku as $k=>$v){
                $v->sale_price=$v->sale_price/100;
                $v->cost_price=$v->cost_price/100;
                $v->commissioned_price=$v->commissioned_price/100;
            }
            //dd($goods_info);
            $goods_info->label_info=json_decode($goods_info->label_info);
            //$data['goods_info']->good_info=json_decode($goods_info->good_info);

            if($goods_info->label_info){
                if($goods_info->label_info->label_flag=='img'){
                    $goods_info->label_info->label_image_url=img_for($goods_info->label_info->label_image_url,'more');
                }
            }else{
                $goods_info->label_info=null;
            }

        }



        //dd($data['goods_info']->toArray());
	//$data['key_info']=[
        //    ['key'=>'thum_image_url',
        //        'count'=>'1',
        //        'name'=>'微缩图'],
        //    ['key'=>'scroll_image_url',
        //        'count'=>'5',
        //        'name'=>'商品轮播图'],
        //    ['key'=>'details_image_url',
        //        'count'=>'12',
       //         'name'=>'商品详情图'],
       // ];


	//dd($goods_info);
        if($goods_info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/


            $data['info']=$goods_info;
            $log_flag='Y';
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
		dump($msg);exit;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }


    /***    抓取商品分页     /goods/goods/getGoodsPage
     */
    public function  getGoodsPage(Request $request){
        /** 接收中间件参数**/
        $good_type_show  =config('shop.good_type');
        $good_type_show  =array_column($good_type_show,'name','key');

        $group_info = $request->get('group_info');//接收中间件产生的参数

        /**接收数据*/
        $num                    =$request->input('num')??10;
        $page                   =$request->input('page')??1;
        $group_name             =$request->input('group_name');
        $parent_classify_name   =$request->input('parent_classify_name');
        $classify_name          =$request->input('classify_name');
        $good_name              =$request->input('good_name');
        $good_type              =$request->input('good_type');
        $good_status            =$request->input('good_status');

        $listrows               =$num;
        $firstrow               =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_name','value'=>$group_name],
            ['type'=>'like','name'=>'parent_classify_name','value'=>$parent_classify_name],
            ['type'=>'like','name'=>'classify_name','value'=>$classify_name],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'=','name'=>'good_type','value'=>$good_type],
            ['type'=>'=','name'=>'good_status','value'=>$good_status],

        ];

        $where=get_list_where($search);
        $select=['self_id','good_title','good_type','classify_name','parent_classify_name','commodity_number','good_info',
            'good_status','thum_image_url','group_name','sell_start_time','sell_end_time'];

        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ErpShopGoods::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach($data['items'] as $k => $v){

            if($v->sell_start_time=='2018-11-30 00:00:00' && $v->sell_end_time=='2099-12-31 00:00:00'){
                $v->sell_time_show='长期有效';
            }else{
                $v->sell_time_show=$v->sell_start_time.'～'.$v->sell_end_time;
            }

            $v->good_info=json_decode($v->good_info,true);


            //商品属性1
            $v->good_type_show=$good_type_show[$v->good_type]??null;

            $v->thum_image_url=img_for($v->thum_image_url,'one');


        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        dd($data['items']->toArray());
        return $msg;
    }




}
?>
