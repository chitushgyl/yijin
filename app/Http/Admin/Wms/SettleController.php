<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsSettle;
use App\Models\Wms\WmsSettleList;
use App\Models\Wms\WmsMoney;



class SettleController extends CommonController{

    /***    结算管理列表头部      /wms/settle/settleList
     */
    public function  settleList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    结算管理分页      /wms/settle/settlePage
     */
    public function settlePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
		$company_id     =$request->input('company_id');
		$warehouse_id     =$request->input('warehouse_id');
        $area           =$request->input('area');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'company_id','value'=>$company_id],
			['type'=>'=','name'=>'warehouse_id','value'=>$warehouse_id],
        ];


        $where=get_list_where($search);

        $select=['self_id','group_name','warehouse_name','company_name','create_user_name','create_time','use_flag','receivable_money',
            'practical_money','already_money','gathering_flag'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsSettle::where($where)->count(); //总的数据量
                $data['items']=WmsSettle::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsSettle::where($where)->count(); //总的数据量
                $data['items']=WmsSettle::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsSettle::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsSettle::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
        //dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {


            if($v->gathering_flag == 'Y'){
                $v->gathering_show='已完成收款';
            }else{
                if($v->already_money > 0){
                    $v->gathering_show='部分收款';
                }else{
                    $v->gathering_show='未收款';
                }
            }

            $v->practical_money = number_format($v->practical_money/100, 2);
            $v->receivable_money = number_format($v->receivable_money/100, 2);
            $v->already_money = number_format($v->already_money/100, 2);
            $v->button_info=$button_info;


            }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    创建结算      /wms/settle/createSettle
     */
    public function  createSettle(Request $request){
        $company_id           =$request->input('company_id');
        //$company_id            ='company_202012151607338915634933';
        $where=[
            ['settle_flag','=','N'],
            ['delete_flag','=','Y'],
            ['company_id','=',$company_id],
        ];
        $select = ['self_id','type','time','group_name','warehouse_name','company_name','payee_affirm_flag','payment_affirm_flag','money'];

        $data['info']=WmsMoney::where($where)->select($select)->orderBy('create_time', 'desc')->get();

        foreach ($data['info'] as $k => $v){
            $v->money = number_format($v->money/100, 2);
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    创建结算      /wms/settle/addSettle
     */
    public function  addSettle(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_settle';

        $operationing->access_cause     ='创建结算';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $money_list         =$request->input('money_list');

        /*** 虚拟数据 **/
        //$money_list=['money_202012151609575958814335','money_202012151613572178704371'];
		//dd($money_list);

        $where=[
            ['settle_flag','=','N'],
            ['delete_flag','=','Y'],
        ];
        $select = ['self_id','type','time','group_code','group_name','warehouse_id','warehouse_name',
            'company_id','company_name','payee_affirm_flag','payment_affirm_flag','money','settle_flag'];

        $info=WmsMoney::where($where)->whereIn('self_id', $money_list)->select($select)->get();
        if($info){

            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=1;

            $money=0;
            foreach ($info as $k=> $v){
                if($v->settle_flag == 'Y'){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行已结算过了，请核查".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }else{
                    $money+=$v->money;
                }
                $a++;
            }

            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }

            $seld=generate_id('settle_');


            $data['self_id']            =$seld;
            $data["group_code"]         =$info[0]->group_code;
            $data["group_name"]         =$info[0]->group_name;
            $data["warehouse_id"]       =$info[0]->warehouse_id;
            $data["warehouse_name"]     =$info[0]->warehouse_name;
            $data['company_id']         =$info[0]->company_id;
            $data["company_name"]       =$info[0]->company_name;
            $data['create_user_id']     = $user_info->admin_id;
            $data['create_user_name']   = $user_info->name;
            $data['create_time']        =$now_time;
            $data["update_time"]        =$now_time;
            $data["receivable_money"]   =$money;
            $data['practical_money']    =$money;
            $data['already_money']      =0;


            $id=WmsSettle::insert($data);

            $operationing->table_id=$seld;
            $operationing->old_info=null;
            $operationing->new_info=$data;

            if($id){
                /**修改所有的为已结算***/
                $money_up['settle_flag']        ='Y';
                $money_up['settle_id']          =$seld;
                $money_up['update_time']        =$now_time;
                WmsMoney::where($where)->whereIn('self_id', $money_list)->update($money_up);
                $msg['code']=200;
                $msg['msg']="结算成功";
                //dd($msg);
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']="结算失败";
                //dd($msg);
                return $msg;
            }


        }else{
            $msg['code']=300;
            $msg['msg']="没有需要结算的数据";
            //dd($msg);
            return $msg;
        }
    }


    /***    创建收款条     /wms/settle/createGathering
     */
    public function createGathering(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');

        //$self_id     ='SID_202012211734446106150501';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','group_name','warehouse_name','company_name','use_flag','receivable_money','practical_money','already_money','gathering_flag'];
        $wmsSettleListSelect=['settle_id','money','voucher','serial_bank_name','serial_number'];
        $data['info']=WmsSettle::with(['wmsSettleList' => function($query)use($wmsSettleListSelect) {
            $query->select($wmsSettleListSelect);
        }]) ->where($where)->select($select)->first();
		//dd($data);
        if($data['info']){
            $data['info']->receivable_money=number_format($data['info']->receivable_money/100,2);
            $data['info']->practical_money=number_format($data['info']->practical_money/100,2);
            $data['info']->already_money=number_format($data['info']->already_money/100,2);
			//dd($data['info']->wmsSettleList);
            if($data['info']->wmsSettleList){
                foreach ($data['info']->wmsSettleList as $k => $v){
					//($v);
                    $v->money=number_format($v->money/100,2);
                    $v->voucher=img_for($v->voucher,'more');
                }
            }
            //dd($data['info']->toArray());

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
				return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="拉取不到数据";
            return $msg;
        }
    }


    /***    收款数据提交      /wms/settle/addGathering
     */
    public function addGathering(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_settle';

        $operationing->access_cause     ='创建/修改库区';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $money              =$request->input('money');
        $voucher            =$request->input('voucher');
        $serial_bank_name   =$request->input('serial_bank_name');
        $serial_number      =$request->input('serial_number');

        /*** 虚拟数据
        $input['self_id']           =$self_id='money_202012101742404594964738';
        $input['money']             =$money='2000';
        $input['voucher']           =$voucher=null;
        $input['serial_bank_name']  =$serial_bank_name='中国银行';
        $input['serial_number']     =$serial_number='211';
		**/

        $rules=[
            'self_id'=>'required',
            'money'=>'required',
        ];
        $message=[
            'self_id.required'=>'请填结算订单编号',
            'money.required'=>'请输入金额',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $select=['self_id','group_code','group_name','warehouse_id','warehouse_name','company_id','company_name','use_flag','receivable_money',
                'practical_money','already_money','gathering_flag'];
            $old_info=WmsSettle::where($where)->select($select)->first();

            if($old_info){
                $data['already_money']      =$old_info->already_money+$money*100;
                $data['update_time']        =$now_time;
                if($data['already_money']  >= $old_info->practical_money ){
                    $data['gathering_flag']='Y';
                }

                $list['self_id']            =generate_id('carriage_');
                $list['settle_id']          =$self_id;
                $list['group_code']         =$old_info->group_code;
                $list['group_name']         =$old_info->group_name;
                $list['warehouse_id']       =$old_info->warehouse_id;
                $list['warehouse_name']     =$old_info->warehouse_name;
                $list['company_id']         =$old_info->company_id;
                $list['company_name']       =$old_info->company_name;
                $list['create_user_id']     = $user_info->admin_id;
                $list['create_user_name']   = $user_info->name;
                $list['create_time']        =$list['update_time']       =$now_time;
                $list['money']              =$money*100;
                $list['voucher']            =img_for($voucher,'in');
                $list['serial_bank_name']   =$serial_bank_name;
                $list['serial_number']      =$serial_number;

                $id=WmsSettle::where($where)->update($data);


                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;

                if($id){
                    $msg['code'] = 200;

                    WmsSettleList::insert($list);
                    $msg['msg'] = "操作成功";
                    return $msg;
                }else{
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }

            }else{
                $msg['code']=301;
                $msg['msg']="拉取不到数据";
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


    /***    收款明细详情     /wms/settle/details
     */
    public function  details(Request $request,Details $details){
        $wms_money_type_show    =array_column(config('wms.wms_money_type'),'name','key');
        $self_id=$request->input('self_id');
//        $self_id='settle_202012231802435176737672';
        $table_name='wms_settle';
        $select=['self_id','group_name','warehouse_name','create_user_name','create_time','use_flag','company_name',
            'receivable_money','practical_money','already_money','gathering_flag'];

        $list_select=['self_id','money','voucher','serial_bank_name','serial_number','create_user_name','create_time','settle_id'];

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];


        $info=WmsSettle::with(['WmsSettleList' => function($query) use($list_select){
            $query->select($list_select);
        }])->where($where)->select($select)->first();

        if($info){

            foreach ($info->WmsSettleList as $k=>$v){
                $v->money                = number_format($v->money/100, 2);
            }

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->receivable_money=number_format($info->receivable_money/100, 2);
            $info->practical_money=number_format($info->practical_money/100, 2);
            $info->already_money=number_format($info->already_money/100, 2);
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

    /**
     * 修改金额  /wms/settle/updateMoney
     * */
    public function updateMoney(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_settle';

        $operationing->access_cause     ='创建/修改结算金额';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $money              =$request->input('money');

        /*** 虚拟数据
        $input['self_id']           =$self_id='money_202012101742404594964738';
        $input['money']             =$money='2000';
         **/

        $rules=[
            'self_id'=>'required',
            'money'=>'required',
        ];
        $message=[
            'self_id.required'=>'请填结算订单编号',
            'money.required'=>'请输入金额',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $select=['self_id','group_code','group_name','warehouse_id','warehouse_name','company_id','company_name','use_flag','receivable_money',
                'practical_money','already_money','gathering_flag'];
            $old_info=WmsSettle::where($where)->select($select)->first();

            if($old_info){
                $data['practical_money']      =$money*100;
                $data['update_time']        =$now_time;
                $id=WmsSettle::where($where)->update($data);
                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;
                if($id){
                    $msg['code']=200;
                    $msg['msg']="操作成功";
                    return $msg;
                }
            }else{
                $msg['code']=301;
                $msg['msg']="拉取不到数据";
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
