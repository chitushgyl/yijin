<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Models\Group\SystemGroup;
use App\Models\Wms\WmsCost;
use App\Http\Controllers\DetailsController as Details;
class CostController  extends CommonController{
    /***    费用列表      /wms/cost/costList
     */
    public function  costList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    费用分页      /wms/cost/costPage
     */
    public function costPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $wms_cost_type_show    =array_column(config('wms.wms_cost_type'),'name','key');
	//dd($wms_cost_type_show);
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
		$group_code     =$request->input('group_code');
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
			['type'=>'all','name'=>'group_code','value'=>$group_code],
        ];

        $where=get_list_where($search);
        $select=['self_id','group_code','group_name','use_flag'];

        $wmsCostSelect=['self_id','create_user_id','create_user_name','create_time',
            'group_code','group_name','preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];
	switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsCost::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::with(['wmsCost' => function($query)use($wmsCostSelect) {
                    $query->select($wmsCostSelect);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsCost::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::with(['wmsCost' => function($query)use($wmsCostSelect) {
                    $query->select($wmsCostSelect);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsCost::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroup::with(['wmsCost' => function($query)use($wmsCostSelect) {
                    $query->select($wmsCostSelect);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }




        //dump($data['items']->toArray());


            foreach ($data['items'] as $k=>$v) {

                if($v->wmsCost){
                    $v->preentry_price = number_format($v->wmsCost->preentry_price/100, 2);
                    $v->out_price = number_format($v->wmsCost->out_price/100, 2);
                    $v->storage_price = number_format($v->wmsCost->storage_price/100, 2);
                    $v->total_price = number_format($v->wmsCost->total_price/100, 2);

                    $v->preentry_type_show=$wms_cost_type_show[$v->wmsCost->preentry_type]??null;
                    $v->out_type_show=$wms_cost_type_show[$v->wmsCost->out_type]??null;
                    $v->storage_type_show=$wms_cost_type_show[$v->wmsCost->storage_type]??null;
                    $v->total_type_show=$wms_cost_type_show[$v->wmsCost->total_type]??null;

                }

                $v->button_info=$button_info;
            }


        //dd($data);
        //dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    拉取费用      /wms/cost/createCost
 */
    public function createCost(Request $request){
        $data['wms_cost_type_show']    =config('wms.wms_cost_type');

        $group_code       =$request->input('group_code');

        //$group_code    ='1234';

        $where=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
        ];


        $select=['self_id','group_code','group_name','use_flag'];
        $wmsCostSelect=['self_id','create_user_id','create_user_name','create_time',
            'group_code','group_name','preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];

        $data['info']=SystemGroup::with(['wmsCost' => function($query)use($wmsCostSelect) {
            $query->select($wmsCostSelect);
        }]) ->where($where)->select($select)->first();

        if($data['info']){
            if($data['info']->wmsCost){
                $data['info']->wmsCost->preentry_price=$data['info']->wmsCost->preentry_price/100;
                $data['info']->wmsCost->out_price=$data['info']->wmsCost->out_price/100;
                $data['info']->wmsCost->storage_price=$data['info']->wmsCost->storage_price/100;
                $data['info']->wmsCost->total_price=$data['info']->wmsCost->total_price/100;
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

    /***    费用入库      /wms/cost/addCost
     */
    public function addCost(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $table_name         ='wms_cost_rule';
        $now_time           =date('Y-m-d H:i:s',time());

        $operationing->access_cause     ='新建/修改基础费用';
        $operationing->operation_type   ='create';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();
        //dd($input);
        $group_code         =$request->input('group_code');
        $preentry_type      =$request->input('preentry_type');
        $preentry_price     =$request->input('preentry_price');
        $out_type       	=$request->input('out_type');
        $out_price          =$request->input('out_price');
        $storage_type       =$request->input('storage_type');
        $storage_price      =$request->input('storage_price');
        $total_type       	=$request->input('total_type');
        $total_price        =$request->input('total_price');

        /*** 虚拟数据
        $input['group_code']        =$group_code    ='1234';
        $input['preentry_type']     =$preentry_type     ='pull';
        $input['preentry_price']    =$preentry_price   ='152';
        $input['out_type']          =$out_type  ='pull';
        $input['out_price']         =$out_price  ='152';
        $input['storage_type']      =$storage_type  ='pull';
        $input['storage_price']     =$storage_price  ='152';
        $input['total_type']        =$total_type  ='pull';
        $input['total_price']       =$total_price  ='152';
         */

        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $etheiu['group_code']=$group_code;
            $group_name=SystemGroup::where($etheiu)->value('group_name');
            //dd($data);
            if(empty($group_name)){
                $msg['code']=301;
                $msg['msg']="获取不到数据";
                return $msg;
            }

            /** 把数据先做好***/
            $data['preentry_type']      		=$preentry_type;
            $data['preentry_price']           	=$preentry_price*100;
            $data['out_type']      		        =$out_type;
            $data['out_price']           	    =$out_price*100;
            $data['storage_type']      		    =$storage_type;
            $data['storage_price']           	=$storage_price*100;
            $data['total_type']      		    =$total_type;
            $data['total_price']           	    =$total_price*100;

            $where=[
                ['group_code','=',$group_code],
                ['delete_flag','=','Y'],
            ];

            $old_info=WmsCost::where($where)->first();

            if($old_info){
                //dd(11111);
                $operationing->access_cause     ='修改基础费用';
                $operationing->operation_type   ='update';
                $data['update_time']=$now_time;
                $id=WmsCost::where($where)->update($data);
            }else{

                $operationing->access_cause     ='新建差异化数据';
                $operationing->operation_type   ='create';
                //通过group_code去拿取数据去


                $data['self_id']            =generate_id('cost_');
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                //dd($data);
                $id=WmsCost::insert($data);
                //dd(2222);

                //存储差异化到redis

            }

            $operationing->table_id=$old_info?$old_info->self_id:$data['self_id'];
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

    /***    费用详情      /wms/cost/details
     */
    public function details(Request $request,Details $details){
        $group_code       =$request->input('group_code');
        $wms_cost_type_show    =array_column(config('wms.wms_cost_type'),'name','key');
        //$group_code='1234';
        $where=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','group_code','group_name','use_flag'];
        $wmsCostSelect=['self_id','create_user_id','create_user_name','create_time',
            'group_code','group_name','preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];

        $info=SystemGroup::with(['wmsCost' => function($query)use($wmsCostSelect) {
            $query->select($wmsCostSelect);
        }]) ->where($where)->select($select)->first();

        if($info){

            if($info->wmsCost){
                $info->wmsCost->preentry_price=$info->wmsCost->preentry_price/100;
                $info->wmsCost->out_price=$info->wmsCost->out_price/100;
                $info->wmsCost->storage_price=$info->wmsCost->storage_price/100;
                $info->wmsCost->total_price=$info->wmsCost->total_price/100;
                $info->preentry_type_show=$wms_cost_type_show[$info->wmsCost->preentry_type];
                $info->out_type_show=$wms_cost_type_show[$info->wmsCost->out_type];
                $info->storage_type_show=$wms_cost_type_show[$info->wmsCost->storage_type];
                $info->total_type_show=$wms_cost_type_show[$info->wmsCost->total_type];

            }

            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y' && $info->wmsCost){
                $data['log_data']=$details->change($info->wmsCost->self_id,$log_num);

            }

//dd($data);
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

    /***    抓取费用      /wms/cost/getCost
     */
    public function getCost(Request $request){
        $data['wms_cost_type_show']    =config('wms.wms_cost_type');
        $group_code       =$request->input('group_code');

       // $group_code='1234';

        $where=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','create_user_id','create_user_name','create_time',
            'group_code','group_name','preentry_type','preentry_price','out_type','out_price','storage_type','storage_price','total_type','total_price'];

        $data['info']=WmsCost::where($where)->select($select)->first();

        if($data['info']){
            $data['info']->preentry_price=$data['info']->preentry_price/100;
            $data['info']->out_price=$data['info']->out_price/100;
            $data['info']->storage_price=$data['info']->storage_price/100;
            $data['info']->total_price=$data['info']->total_price/100;

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($data);
        return $msg;

    }


}

