<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Models\Wms\WmsLibrarySige;
use App\Models\Wms\WmsWarehouseSign;
use App\Http\Controllers\WmschangeController as Change;

class SearchController extends CommonController{
    /***    库位商品列表      /wms/search/searchList
     */
    public function  searchList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    库位商品分页      /wms/search/searchPage
     */
    public function searchPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $in_store_status  =array_column(config('wms.in_store_status'),'name','key');
        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');
        $good_name          =$request->input('good_name');
        $company_id       =$request->input('company_id');
        $warehouse_id     =$request->input('warehouse_id');
		$warehouse_sign_id     =$request->input('warehouse_sign_id');
        $can_use            =$request->input('can_use');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'>','name'=>'now_num','value'=>0],
            ['type'=>'all','name'=>'can_use','value'=>$can_use],
            ['type'=>'like','name'=>'company_id','value'=>$company_id],
            ['type'=>'like','name'=>'warehouse_id','value'=>$warehouse_id],
			['type'=>'like','name'=>'warehouse_sign_id','value'=>$warehouse_sign_id],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
        ];


        $where=get_list_where($search);

        //DUMP($where);
        $select=['self_id','group_name','warehouse_name','company_name','good_name','good_english_name','external_sku_id','spec','area','row','column','tier',
            'production_date','expire_time','now_num','can_use','good_unit','good_target_unit','good_scale','in_library_state'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsLibrarySige::where($where)->count(); //总的数据量
                $data['items']=WmsLibrarySige::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsLibrarySige::where($where)->count(); //总的数据量
                $data['items']=WmsLibrarySige::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsLibrarySige::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsLibrarySige::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach ($data['items'] as $k=>$v) {
            if ($v->area && $v->row && $v->column){
                $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
            }else{
                $v->sign = '';
            }

            $v->good_describe =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->now_num);
            $v->in_library_state_show =$in_store_status[$v->in_library_state]??null;
            $v->button_info=$button_info;

        }

       // dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    修改差异      /wms/search/mistakeRevise
     */
    public function mistakeRevise(Request $request,Change $change){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $input              =$request->all();
        $self_id            =$request->input('self_id');
        $num                =$request->input('num');

        $table_name         ='wms_library_sige';
		$operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause='修改差异';
        $operationing->operation_type='update';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;
		$operationing->type='add';

        /****虚拟数据
        $input['self_id']    =$self_id="LSID_202011281559375553276821";
        $input['num']        =$num='35';
         ***/
        $rules=[
            'self_id'=>'required',
            'num'=>'required|integer',
        ];
        $message=[
            'self_id.required'=>'请填写修改的条目',
            'num.required'=>'请填写数量',
            'num.integer'=>'请填写正确的数量',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $where_sku=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $select=['self_id','group_name','warehouse_id','warehouse_name','company_name','good_name','good_english_name','sku_id','spec',
                'warehouse_sign_id','area','row','column','tier','good_unit','good_target_unit','good_scale','good_info',
                'production_date','expire_time','now_num','can_use',
                'order_id','external_sku_id','company_id','group_code'];

            $old_info=WmsLibrarySige::where($where_sku)->select($select)->first();


            if($old_info){
                 if($old_info ->now_num ==  $num){
                     $msg['code'] = 303;
                     $msg['msg'] = "原数量和新数量一致，不需要修改差异";
                     return $msg;
                 }
            }

            //dd($old_info);
            $data['now_num']=$num;
            $data['update_time']=$now_time;

            $operationing->table_id=$self_id;
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            $id=WmsLibrarySige::where($where_sku)->update($data);



            if($id){
                $andd=$old_info->toArray();
                $andd['create_user_id']     =$user_info->admin_id;
                $andd['create_user_name']   =$user_info->name;
                $andd['create_time']        =$now_time;
                $andd["update_time"]        =$now_time;
                $andd["now_num_new"]        =$num;

                $abc[0]=$andd;
                //DUMP($abc);
                $change->change($abc,'change');

                //dd(11111);
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


    }

    /***    移库      /wms/search/createMove
     */
    public function createMove(Request $request){
	//dd($request->all());
        /** 通过库位查询，通过商品查询**/
        /**接收数据*/
        $warehouse_sign_id      =$request->input('warehouse_sign_id');
        $sku_id                 =$request->input('sku_id');

        //$sku_id                 ='good_202012011717297024689590';

        if($warehouse_sign_id || $sku_id){
            $search=[
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
                ['type'=>'=','name'=>'use_flag','value'=>'Y'],
                ['type'=>'>','name'=>'now_num','value'=>0],
                ['type'=>'=','name'=>'grounding_status','value'=>'Y'],
                ['type'=>'=','name'=>'warehouse_sign_id','value'=>$warehouse_sign_id],
                ['type'=>'=','name'=>'sku_id','value'=>$sku_id],
            ];
	    //dd($search);

            $where=get_list_where($search);
//dd($where);
            $select=['self_id','group_name','warehouse_name','company_name','good_name','good_english_name',
                'spec','area','row','column','tier','production_date','expire_time','now_num','can_use','good_unit','good_target_unit','good_scale'];

            $data['info']=WmsLibrarySige::where($where)->orderBy('create_time', 'desc')
                ->select($select)->get();
		    //dd($data);
            foreach ($data['info'] as $k=>$v) {
                $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;

            }

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            //dd($msg);
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']='必须有个库位或者有个商品';
            return $msg;
        }



    }
    /***    移库操作      /wms/search/addMove
     */
    public function addMove(Request $request,Change $change){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        /**分两种，第一种是整个库位移库all，第二个是单个商品移库alone**/
        /** 接收数据*/
        $input              =$request->all();
		//dd($input);
        $type               =$request->input('type')??'all';
        $old                =$request->input('old');
        $new                =$request->input('new');
        $sing_info          =$request->input('sing_info');

	    $table_name         ='wms_library_sige';
		$operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->operation_type='update';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;
		$operationing->type='move';


	//dd($sing_info);
        /****虚拟数据
        $input['type']       =$type="alone";
        $input['old']        =$old='sign_202011281506576358672865';
        $input['new']        =$new='sign_202011281552273801653954';

        $input['sing_info']=$sing_info=[
                    '0'=>[
                        'self_id'=>'LSID_202012011318491222263348',
                        'num'=>'10',
                        'new_sign_id'=>'sign_202011281552273771444464'
                    ],

                   '1'=>[
                       'self_id'=>'LSID_202012011318491222448264',
                       'num'=>'10',
                       'new_sign_id'=>'sign_202011281552273771444464'
                    ],
                 ];
	***/
        $old_change=[];
        $new_change=[];

        $select=['self_id','group_name','warehouse_id','warehouse_name','company_name','good_lot',
            'good_name','good_english_name','spec','sku_id','good_unit','good_target_unit','good_scale','good_info',
            'warehouse_sign_id','area','row','column','tier','production_date','expire_time','now_num','can_use',
            'order_id','external_sku_id','company_id','group_code','storage_number','wms_length','wms_wide','wms_high','wms_weight'];

        switch ($type){
            case 'all':
                //查询老库位有没有商品
                $where_sku=[
                    ['delete_flag','=','Y'],
                    ['now_num','>',0],
                    ['warehouse_sign_id','=',$old],
                ];

                $old_info=WmsLibrarySige::where($where_sku)->orderBy('create_time', 'desc')->select($select)->get();
	//dd($old_info->toArray());
                //DUMP($old_info);
                if(empty($old_info->toArray())){
                    $msg['code']=301;
                    $msg['msg']="该库位没有商品";
                    return $msg;
                }


                //DD(1111);
                //查询新库位是不是存在
                $where_sign=[
                    ['delete_flag','=','Y'],
                    ['self_id','=',$new],
                ];

                $select_sign=['self_id','area_id','area','row','column','tier'];
                $signinfo=WmsWarehouseSign::where($where_sign)->select($select_sign)->first();
                if(empty($signinfo)){
                    $msg['code']=302;
                    $msg['msg']="整托移库请选择库位";
                    return $msg;
                }

                foreach ($old_info as $k => $v){
                    $andd=$v->toArray();
                    $andd['create_user_id']     =$user_info->admin_id;
                    $andd['create_user_name']   =$user_info->name;
                    $andd['create_time']        =$now_time;
                    $andd["update_time"]        =$now_time;
                    $andd["now_num_new"]        =0;
                    $andd["good_target_unit"]   =$v->good_target_unit;
                    $andd["good_scale"]         =$v->good_scale;
                    $andd["good_unit"]          =$v->good_unit;
                    $andd["good_log"]          =$v->good_lot;

                    $old_change[]=$andd;
		    //dump($andd);
                    /***  做一个新的数组**/
                    $list["self_id"]            =generate_id('LSID_');
                    $list["order_id"]           =$v->order_id;
                    $list["sku_id"]             =$v->sku_id;
                    $list["external_sku_id"]    =$v->external_sku_id;
                    $list["company_id"]         =$v->company_id;
                    $list["company_name"]       =$v->company_name;
                    $list["good_name"]          =$v->good_name;
                    $list["good_english_name"]  =$v->good_english_name;
                    $list["good_target_unit"]   =$v->good_target_unit;
                    $list["good_scale"]         =$v->good_scale;
                    $list["good_unit"]          =$v->good_unit;
                    $list["good_unit"]          =$v->good_unit;
                    $list["good_lot"]          =$v->good_lot;
                    $list["grounding_status"]   ="Y";
                    $list["warehouse_id"]       =$v->warehouse_id;
                    $list["warehouse_name"]     =$v->warehouse_name;
                    $list['warehouse_sign_id']  =$signinfo->self_id;
                    $list['area_id']            =$signinfo->area_id;
                    $list['area']               =$signinfo->area;
                    $list['row']                =$signinfo->row;
                    $list['column']             =$signinfo->column;
                    $list['tier']               =$signinfo->tier;
                    $list["production_date"]    =$v->production_date;
                    $list["expire_time"]        =$v->expire_time;
                    $list['spec']               =$v->spec;
                    $list['initial_num']        =$v->now_num;
                    $list['now_num']            =$v->now_num;
                    $list['storage_number']     =$v->now_num;
                    $list['wms_length']         =$v->wms_length;
                    $list['wms_wide']           =$v->wms_wide;
                    $list['wms_high']           =$v->wms_high;
                    $list['wms_weight']         =$v->wms_weight;
                    $list["group_code"]         =$v->group_code;
                    $list["group_name"]         =$v->group_name;
                    $list['create_time']        =$now_time;
                    $list["update_time"]        =$now_time;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;

                    $new_change[]=$list;

                }

                $change_update['now_num']          =0;
                $change_update['update_time']      =$now_time;

				$operationing->access_cause='整托移库';
				$operationing->table_id=null;
				$operationing->old_info=$old_change;
				$operationing->new_info=$new_change;

                $id=WmsLibrarySige::where($where_sku)->update($change_update);
                WmsLibrarySige::insert($new_change);

                break;

            case 'alone':
                    $cando='Y';

                    $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
                    $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
                    $errorNum=50;       //控制错误数据的条数
					//dump($sing_info);
                    /**  第一步，检查是不是数据够不够*/
			//dd($sing_info);
					//dd($sing_info);

                    foreach ($sing_info as $k => $v){
                        $where_sku=[
                            ['delete_flag','=','Y'],
                            //['now_num','>=',$v['num']],
                            ['self_id','=',$v['self_id']],
                        ];
			//dump($where_sku);
                        $old_info=WmsLibrarySige::where($where_sku)->select($select)->first();
                        if(empty($old_info)){
                            if($abcd<$errorNum){
                                $a=$k+1;
                                $strs .= '数据中的第'.$a."行商品数量不足".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }else{
							if($v['num'] >$old_info->now_num){
								if($abcd<$errorNum){
									$a=$k+1;
									$strs .= '数据中的第'.$a."行商品数量不足".'</br>';
									$cando='N';
									$abcd++;
								}
							}
						}

						//dd($cando);

                        //查询新库位是不是存在
                        $where_sign=[
                            ['delete_flag','=','Y'],
                            ['self_id','=',$v['new_sign_id']],
                        ];

                        $select_sign=['self_id','area','area_id','row','column','tier'];
                        $signinfo=WmsWarehouseSign::where($where_sign)->select($select_sign)->first();

                        //dump($signinfo);exit;
                        if(empty($signinfo)){
                            if($abcd<$errorNum){
                                $a=$k+1;
                                $strs .= '数据中的第'.$a."行库位不存在".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }

                        if($cando=='Y'){
                            //开始做数据
                            $andd=$old_info->toArray();
                            $andd['create_user_id']     =$user_info->admin_id;
                            $andd['create_user_name']   =$user_info->name;
                            $andd['create_time']        =$now_time;
                            $andd["update_time"]        =$now_time;
                            $andd["now_num_new"]        =$old_info->now_num - $v['num'];
                    	    $andd["good_target_unit"]   =$old_info->good_target_unit;
                    	    $andd["good_scale"]         =$old_info->good_scale;
                   	        $andd["good_unit"]          =$old_info->good_unit;
                   	        $andd["good_lot"]          =$old_info->good_lot;

                            $old_change[]=$andd;
			    //dump($andd);
		    	            $list["grounding_status"]   ="Y";
                            $list["self_id"]            =generate_id('LSID_');
                            $list["order_id"]           =$old_info->order_id;
                            $list["sku_id"]             =$old_info->sku_id;
                            $list["external_sku_id"]    =$old_info->external_sku_id;
                            $list["company_id"]         =$old_info->company_id;
                            $list["company_name"]       =$old_info->company_name;
                            $list["good_name"]          =$old_info->good_name;
                            $list["good_english_name"]  =$old_info->good_english_name;
                    	    $list["good_target_unit"]   =$old_info->good_target_unit;
                    	    $list["good_scale"]         =$old_info->good_scale;
                   	        $list["good_unit"]          =$old_info->good_unit;
                   	        $list["good_lot"]          =$old_info->good_lot;
                            $list["good_info"]          =$old_info->good_info;
                            $list["warehouse_id"]       =$old_info->warehouse_id;
                            $list["warehouse_name"]     =$old_info->warehouse_name;
                            $list['warehouse_sign_id']  =$signinfo->self_id;
                            $list['area']               =$signinfo->area;
			                $list['area_id']            =$signinfo->area_id;
                            $list['row']                =$signinfo->row;
                            $list['column']             =$signinfo->column;
                            $list['tier']               =$signinfo->tier;
                            $list["production_date"]    =$old_info->production_date;
                            $list["expire_time"]        =$old_info->expire_time;
                            $list['spec']               =$old_info->spec;
                            $list['initial_num']        =$v['num'];
                            $list['now_num']            =$v['num'];
                            $list['storage_number']     =$v['num'];
                            $list['wms_length']         =$old_info->wms_length;
                            $list['wms_wide']           =$old_info->wms_wide;
                            $list['wms_high']           =$old_info->wms_high;
                            $list['wms_weight']         =$old_info->wms_weight;
                            $list["group_code"]         =$old_info->group_code;
                            $list["group_name"]         =$old_info->group_name;
                            $list['create_time']        =$now_time;
                            $list["update_time"]        =$now_time;
                            $list['create_user_id']     = $user_info->admin_id;
                            $list['create_user_name']   = $user_info->name;
                            $new_change[]=$list;
			    //dump($list);
                        }


                    }
		//dd(111);exit;
                    /** 上面的循环结束，然后开始做数据进入数据库**/
                   if($cando=='Y'){
                        foreach ($old_change as $k => $v){
                            $where=[
                                ['self_id','=',$v['self_id']],
                            ];
                            $data['now_num']            =$v['now_num_new'];
                            $data['update_time']        =$now_time;


                            $id=WmsLibrarySige::where($where)->update($data);

                        }
						$operationing->access_cause='商品移库';
						$operationing->table_id=null;
						$operationing->old_info=$old_change;
						$operationing->new_info=$new_change;
                       WmsLibrarySige::insert($new_change);
                   }else{
                       $msg['code'] = 305;
                       $msg['msg'] = $strs;
                       return $msg;
                   }
                break;


        }

            if($id){
                $change->change($old_change,'moveout');
                $change->change($new_change,'movein');

                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }

    }


    /***    库位商品详情     /wms/search/details
     */
    public function  details(Request $request,Details $details){
        $wms_order_type      = config('wms.wms_order_type');
        $wms_order_type_show  =array_column($wms_order_type,'name','key');

        $self_id=$request->input('self_id');

        //$self_id='SID_2020120415135553691290';

        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','grounding_status','type','company_name','create_user_name','create_time','group_name','check_user_name','check_time','grounding_status','count','voucher'];

		$WmsLibrarySigeSelect=[
		    'order_id',
            'external_sku_id','good_name','spec','production_date','expire_time','now_num','good_unit','good_target_unit','good_scale',
            'area','row','column','tier',
            'can_use'
		];

        $info=WmsLibraryOrder::with(['wmsLibrarySige' => function($query)use($WmsLibrarySigeSelect) {
		$query->select($WmsLibrarySigeSelect);
		}])->where($where)
		->select($select)->first();


        if($info){
            $info->type_show=$wms_order_type_show[$info->type];

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            foreach ($info->wmsLibrarySige as $k => $v){
                $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;

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




}
?>
