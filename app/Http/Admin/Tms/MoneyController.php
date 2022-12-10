<?php
namespace App\Http\Admin\Tms;
use App\Models\Tms\TmsOrderCost;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\DetailsController as Details;

use App\Models\Tms\TmsOrderMoney;

class MoneyController extends CommonController{

    /***    费用头部      /tms/money/moneyList
     */
    public function  moneyList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    费用分页      /tms/money/moneyPage
     */
    public function moneyPage(Request $request){
        $tms_money_type    =array_column(config('tms.tms_money_type'),'name','key');
        $money_type    =array_column(config('tms.money_type'),'name','key');
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'!=','name'=>'settle_flag','value'=>'Y'],
            ['type'=>'all','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'shouk_group_code','value'=>$group_code],
        ];

        $search1=[
            ['type'=>'!=','name'=>'settle_flag','value'=>'Y'],
            ['type'=>'all','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'fk_group_code','value'=>$group_code],
        ];
        $where=get_list_where($search);
        $where1=get_list_where($search1);

//        $where=[
//            ['settle_flag','=','N'],
//            ['delete_flag','=','Y'],
//            ['shouk_group_code','=',$group_code]
//        ];
//
//        $where1 = [
//            ['settle_flag','=','N'],
//            ['delete_flag','=','Y'],
//            ['fk_group_code','=',$group_code]
//        ];
        $user_where = [
            ['delete_flag','=','Y'],
        ];
//        dump($group_info['group_id']);

        $data['info'] = [];
        $select = ['self_id','order_id','dispatch_id','carriage_id','driver_id','shouk_group_code','shouk_company_id','shouk_total_user_id','shouk_type','fk_group_code','fk_company_id',
            'fk_total_user_id','fk_type','ZIJ_group_code','ZIJ_company_id','ZIJ_total_user_id','create_time','update_time','use_flag','delete_flag','money','money_type',
            'settle_flag','settle_id','type','shouk_driver_id'
        ];
        $select1 = ['self_id','type','company_name'];
        $select2 = ['self_id','tel','token_name','total_user_id'];
        $select3 = ['self_id','car_number','contacts','tel'];
        $select4 = ['self_id','group_name','tel'];

        switch ($group_info['group_id']){
            case 'all':

                $data['total']=TmsOrderCost::where($where)->count(); //总的数据量
                $data['items'] = TmsOrderCost::with(['tmsGroup' => function($query)use($select1){
                    $query->select($select1);
                }])
                    ->with(['tmsCompany' => function($query)use($select1){
                        $query->select($select1);
                    }])
                    ->with(['userReg' => function($query)use($select2){
                        $query->select($select2);
                    }])
                    ->with(['regUser' => function($query)use($select2,$user_where){
                        $query->where($user_where);
                        $query->select($select2);
                    }])
                    ->with(['tmsDriver' => function($query)use($select3){
                        $query->select($select3);
                    }])
                    ->with(['tmsGroupCompany' => function($query)use($select4){
                        $query->select($select4);
                    }])
                    ->with(['tmsCompanyGroup' => function($query)use($select4){
                        $query->select($select4);
                    }])->where($where)->orWhere($where1)->offset($firstrow)->limit($listrows)->select($select)->orderBy('update_time','DESC')->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['shouk_group_code','=',$group_info['group_code']];
                $where1[]=['fk_group_code','=',$group_info['group_code']];
                $data['total']=TmsOrderCost::where($where)->count(); //总的数据量
                $data['items'] = TmsOrderCost::with(['tmsGroup' => function($query)use($select1){
                    $query->select($select1);
                }])
                    ->with(['tmsCompany' => function($query)use($select1){
                        $query->select($select1);
                    }])
                    ->with(['userReg' => function($query)use($select2){
                        $query->select($select2);
                    }])
                    ->with(['regUser' => function($query)use($select2,$user_where){
                        $query->where($user_where);
                        $query->select($select2);
                    }])
                    ->with(['tmsDriver' => function($query)use($select3){
                        $query->select($select3);
                    }])
                    ->with(['tmsGroupCompany' => function($query)use($select4){
                        $query->select($select4);
                    }])
                    ->with(['tmsCompanyGroup' => function($query)use($select4){
                        $query->select($select4);
                    }])
                    ->offset($firstrow)->limit($listrows)->where($where)->orWhere($where1)->select($select)->orderBy('update_time','DESC')->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsOrderCost::where($where)->count(); //总的数据量
                $data['items'] = TmsOrderCost::with(['tmsGroup' => function($query)use($select1){
                    $query->select($select1);
                }])
                    ->with(['tmsCompany' => function($query)use($select1){
                        $query->select($select1);
                    }])
                    ->with(['userReg' => function($query)use($select2,$user_where){
                        $query->where($user_where);
                        $query->select($select2);
                    }])
                    ->with(['regUser' => function($query)use($select2,$user_where){
                        $query->where($user_where);
                        $query->select($select2);
                    }])
                    ->with(['tmsDriver' => function($query)use($select3){
                        $query->select($select3);
                    }])
                    ->with(['tmsGroupCompany' => function($query)use($select4){
                        $query->select($select4);
                    }])
                    ->with(['tmsCompanyGroup' => function($query)use($select4){
                        $query->select($select4);
                    }])->where($where)->orWhere($where1)->offset($firstrow)->limit($listrows)->select($select)->orderBy('update_time','DESC')->get();
                $data['group_show']='Y';
                break;
        }
//        dd($data['items']->toArray());
        foreach ($data['items'] as $k => $v){
            $v->button_info = $button_info;
            $v->money = number_format($v->money/100, 2);
            $v->money_show=$money_type[$v->type]??null;
            $v->money_type_show=$tms_money_type[$v->money_type]??null;
//            if ($v->type == 'in'){
//                if ($v->fk_type == 'COMPANY'){
//                    if ($v->tmsGroup){
//                        $v->object_show = $v->tmsGroup->company_name;
//                    }
//                }elseif($v->fk_type == 'USER'){
//                    if ($v->userReg){
//                        $v->object_show = $v->userReg->token_name ?? $v->userReg->tel;
//                    }
//                }
//            }else{
//                if ($v->shouk_type == 'COMPANY'){
//                    $v->object_show = $v->tmsCompany->company_name;
//                }elseif($v->shouk_type == 'USER'){
//                    $v->object_show = $v->userReg->token_name ?? $v->userReg->tel;
//                }elseif($v->shouk_type == 'DRIVER'){
//                    $v->object_show = $v->tmsDriver->contacts;
//                }
//            }
            switch ($v->fk_type){
                case 'COMPANY':
                    $v->payment_show = $v->tmsGroup->company_name;
                    break;
                case 'USER':
                    $v->payment_show = $v->regUser->token_name ?? $v->regUser->tel;
                    break;
                case 'PLATFORM':
                    $v->payment_show = '赤途';
                    break;
                case 'GROUP_CODE':
                    if ($v->tmsGroupCompany){
                        $v->payment_show =  $v->tmsGroupCompany->group_name;
                    }else{
                        $v->payment_show =  '';
                    }

                    break;
            }
            switch ($v->shouk_type){
                case 'COMPANY':
                    $v->receiver_show = $v->tmsCompany->company_name;
                    break;
                case 'USER':
                    $v->receiver_show = $v->userReg->token_name ?? $v->userReg->tel;
                    break;
                case 'PLATFORM':
                    $v->receiver_show = '赤途';
                    break;
                case 'GROUP_CODE':
                    $v->receiver_show = $v->tmsCompanyGroup->group_name;
                    break;
                case 'DRIVER':
                    $v->receiver_show = $v->tmsDriver->contacts;
                    break;
            }


            if ($v->settle_flag == 'Y'){
                $v->settle_show = '未结算';
            }else{
                $v->settle_show = '已结算';
            }
        }
//        dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }




    /***    费用详情     /tms/money/details
     */
    public function details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='tms_order_cost';
//        $self_id='order_money_202105181007216848591872';
        $select=['self_id','order_id','dispatch_id','carriage_id','driver_id','shouk_group_code','shouk_company_id','shouk_total_user_id','shouk_type','fk_group_code','fk_company_id',
            'fk_total_user_id','fk_type','ZIJ_group_code','ZIJ_company_id','ZIJ_total_user_id','create_time','update_time','use_flag','delete_flag','money','money_type',
            'settle_flag','settle_id','type','shouk_driver_id'];
        $select1 = ['self_id','type','company_name'];
        $select2 = ['self_id','tel','token_name','total_user_id'];
        $select3 = ['self_id','car_number','contacts','tel'];
        $select4 = ['self_id','group_name','tel'];
        $user_where = [
            ['delete_flag','=','Y'],
        ];
        $info= TmsOrderCost::with(['tmsGroup' => function($query)use($select1){
            $query->select($select1);
        }])
            ->with(['tmsCompany' => function($query)use($select1){
                $query->select($select1);
            }])
            ->with(['userReg' => function($query)use($select2,$user_where){
                $query->where($user_where);
                $query->select($select2);
            }])
            ->with(['regUser' => function($query)use($select2,$user_where){
                $query->where($user_where);
                $query->select($select2);
            }])
            ->with(['tmsDriver' => function($query)use($select3){
                $query->select($select3);
            }])
            ->with(['tmsGroupCompany' => function($query)use($select4){
                $query->select($select4);
            }])
            ->with(['tmsCompanyGroup' => function($query)use($select4){
                $query->select($select4);
            }])->where('self_id',$self_id)->select($select)->orderBy('update_time','DESC')->first();
//        dd($info->toArray());
        if($info){
            $info->money = number_format($info->money/100, 2);
            $info->money_show=$money_type[$info->type]??null;
            $info->money_type_show=$tms_money_type[$info->money_type]??null;
//            if ($info->type == 'in'){
//                if ($info->fk_type == 'COMPANY'){
//                    if ($info->tmsGroup){
//                        $info->object_show = $info->tmsGroup->company_name;
//                    }
//                }elseif($info->fk_type == 'USER'){
//                    if ($info->userReg){
//                        $info->object_show = $v->userReg->token_name ?? $info->userReg->tel;
//                    }
//                }
//            }else{
//                if ($info->shouk_type == 'COMPANY'){
//                    $info->object_show = $info->tmsCompany->company_name;
//                }elseif($info->shouk_type == 'USER'){
//                    $info->object_show = $v->userReg->token_name ?? $info->userReg->tel;
//                }elseif($info->shouk_type == 'DRIVER'){
//                    $info->object_show = $info->tmsDriver->contacts;
//                }
//            }
            switch ($info->fk_type){
                case 'COMPANY':
                    $info->payment_show = $info->tmsGroup->company_name;
                    break;
                case 'USER':
                    $info->payment_show = $info->regUser->token_name ?? $info->regUser->tel;
                    break;
                case 'PLATFORM':
                    $info->payment_show = '赤途';
                    break;
                case 'GROUP_CODE':
                    $info->payment_show =  $info->tmsGroupCompany->group_name;
                    break;
            }
            switch ($info->shouk_type){
                case 'COMPANY':
                    $info->receiver_show = $info->tmsCompany->company_name;
                    break;
                case 'USER':
                    $info->receiver_show = $info->userReg->token_name ?? $info->userReg->tel;
                    break;
                case 'PLATFORM':
                    $info->receiver_show = '赤途';
                    break;
                case 'GROUP_CODE':
                    $info->receiver_show = $info->tmsCompanyGroup->group_name;
                    break;
                case 'DRIVER':
                    $info->receiver_show = $info->tmsDriver->contacts;
                    break;
            }
//            dd($info->toArray());

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$info;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }




}
?>
