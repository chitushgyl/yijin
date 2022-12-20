<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsMoney;


class MoneyController extends CommonController{

    /***    费用明细列表头部      /wms/money/moneyList
     */
    public function  moneyList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    费用明细分页      /wms/money/moneyPage
     */
    public function moneyPage(Request $request){
        $wms_money_type_show    =array_column(config('wms.wms_money_type'),'name','key');
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
        $type           =$request->input('type');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
			['type'=>'=','name'=>'company_id','value'=>$company_id],
			['type'=>'=','name'=>'warehouse_id','value'=>$warehouse_id],
            ['type'=>'=','name'=>'type','value'=>$type],
        ];


        $where=get_list_where($search);

        $select=['self_id','type','group_code','group_name','warehouse_id','warehouse_name','time','create_user_name','create_time','use_flag','company_name','company_id',
                'payee_affirm_flag','payment_affirm_flag','settle_flag','settle_id','money'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsMoney::where($where)->count(); //总的数据量
                $data['items']=WmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsMoney::where($where)->count(); //总的数据量
                $data['items']=WmsMoney::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsMoney::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
        //dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {
            $v->money = number_format($v->money/100, 2);
            $v->total_show=$wms_money_type_show[$v->type]??null;
            $v->button_info=$button_info;

            }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }



    /***    费用明细详情     /wms/money/details
     */
    public function  details(Request $request,Details $details){
        $wms_money_type_show    =array_column(config('wms.wms_money_type'),'name','key');
        $self_id=$request->input('self_id');
        //$self_id='money_202012231738203885359374';
        $table_name='wms_money';
        $select=['self_id','type','group_name','warehouse_name','create_user_name','create_time','use_flag','company_name',
            'payee_affirm_flag','payee_user_name','payee_time','payment_affirm_flag','payment_user_name','payment_time','settle_flag','settle_id','money'];


        $list_select=['self_id','area','row','column','tier','external_sku_id','good_name','good_english_name','spec','good_unit','good_target_unit','good_scale','production_date','expire_time','num',
            'create_user_name','create_time','use_flag','money_id'];

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];


        $info=WmsMoney::with(['WmsMoneyList' => function($query) use($list_select){
            $query->select($list_select);
        }])->where($where)->select($select)->first();
        //DD($info->toArray());



        if($info){


			foreach ($info->WmsMoneyList as $k=>$v){
				$v->sign                =$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
				$v->good_describe      =unit_do($v->good_unit , $v->good_target_unit, $v->good_scale, $v->num);
			}


            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->total_show=$wms_money_type_show[$info->type];
            $info->money = number_format($info->money/100, 2);
            $data['info']=$info;

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

            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }



    /***    付款方确认    /wms/money/paymentCheck
     */
    public function  paymentCheck(Request $request){
        $self_id=$request->input('self_id');
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_money';

        $operationing->access_cause     ='付款方确认';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $old_info=WmsMoney::where($where)->first();

        if($old_info){

            $datt['payment_affirm_flag']='Y';
            $datt['payment_user_id']=$user_info->admin_id;
            $datt['payment_user_name']=$user_info->name;
            $datt['payment_time']=$now_time;


            $id=WmsMoney::where($where)->update($datt);
            if($id){

                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$datt;

                $msg['code'] = 200;
                $msg['msg'] = "付款方确认成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "付款方确认失败";
                return $msg;
            }
        }else{
            $msg['code']=301;
            $msg['msg']="拉取不到数据";
            return $msg;
        }

    }


    /***    收款方确认     /wms/money/payeeCheck
     */
    public function  payeeCheck(Request $request){
        $self_id=$request->input('self_id');
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_money';

        $operationing->access_cause     ='收款方确认';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $old_info=WmsMoney::where($where)->first();

        if($old_info){

            $datt['payee_affirm_flag']='Y';
            $datt['payee_user_id']=$user_info->admin_id;
            $datt['payee_user_name']=$user_info->name;
            $datt['payee_time']=$now_time;


            $id=WmsMoney::where($where)->update($datt);
            if($id){

                $operationing->table_id=$self_id;
                $operationing->old_info=$old_info;
                $operationing->new_info=$datt;

                $msg['code'] = 200;
                $msg['msg'] = "收款方确认成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "收款方确认失败";
                return $msg;
            }
        }else{
            $msg['code']=301;
            $msg['msg']="拉取不到数据";
            return $msg;
        }
    }

    /**
     * 添加费用  wms/money/addMoney
     * */
    public function addMoney(Request $request){

    }

}
?>
