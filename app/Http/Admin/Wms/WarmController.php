<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsWarm;
use App\Models\Wms\WmsWarehouse;

class WarmController extends CommonController{

    /***    温区列表头部      /wms/warm/warmList
     */
    public function  warmList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    温区分页      /wms/warm/warmPage
     */
    public function warmPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $warm_name      =$request->input('warm_name');
        $min_warm       =$request->input('min_warm');
        $max_warm      =$request->input('max_warm');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'warm_name','value'=>$warm_name],
            ['type'=>'>=','name'=>'min_warm','value'=>$min_warm],
            ['type'=>'<=','name'=>'max_warm','value'=>$max_warm],
        ];

        $where=get_list_where($search);

        $select=['self_id','warm_name','group_code','group_name','warehouse_id','warehouse_name','create_user_id','create_user_name','create_time','use_flag','min_warm','max_warm'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsWarm::where($where)->count(); //总的数据量
                $data['items']=WmsWarm::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsWarm::where($where)->count(); //总的数据量
                $data['items']=WmsWarm::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsWarm::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsWarm::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

//dd($data);
        foreach ($data['items'] as $k=>$v) {
			$v->warm_name=warm($v->warm_name,$v->min_warm,$v->max_warm);
            $v->button_info=$button_info;

            }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    创建温区      /wms/warm/createWarm
     */
    public function createWarm(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        //$self_id='coupon_20200928155425956582308';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['info']=WmsWarm::where($where)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


    }
    /***    新建温区数据提交      /wms/warm/addWarm
     */
    public function addWarm(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_warm';

        $operationing->access_cause     ='创建/修改温区';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $warehouse_id        =$request->input('warehouse_id');
        $warm_name       =$request->input('warm_name');
        $min_warm       =$request->input('min_warm');
        $max_warm       =$request->input('max_warm');
        $control       =$request->input('control');

        /*** 虚拟数据*
        $input['self_id']           =$self_id='good_202007011336328472133661';
        $input['warehouse_id']      =$warehouse_id='ware_202006012159456407842832';
        $input['warm_name']              =$warm_name='常温';
        $input['min_warm']              =$min_warm='12';
        $input['max_warm']              =$max_warm='15';
         **/
        $rules=[
            'warehouse_id'=>'required',
            'warm_name'      =>'required',
        ];
        $message=[
            'warehouse_id.required'=>'请填选择所属仓库',
            'warm_name.required'=>'请填写库区',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $where_goods=[
                ['delete_flag','=','Y'],
                ['use_flag','=','Y'],
                ['self_id','=',$warehouse_id],
            ];

            $info2 = WmsWarehouse::where($where_goods)->select('warehouse_name','group_code','group_name')->first();
            if (empty($info2)) {
                $msg['code'] = 301;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }

            if($min_warm>$max_warm){
                $msg['code'] = 302;
                $msg['msg'] = '最小温度和最大温度不正确';
                return $msg;
            }

            $data['warm_name'] = $warm_name;
            $data['min_warm'] = $min_warm;
            $data['max_warm'] = $max_warm;
            $data['control'] = $control;


            $where2['self_id'] = $self_id;
            $old_info=WmsWarm::where($where2)->first();

            if($old_info){
				//dd(1111);
                $data['update_time']=$now_time;
                $id=WmsWarm::where($where2)->update($data);

                $operationing->access_cause='修改温区';
                $operationing->operation_type='update';


            }else{

                $data['self_id']=generate_id('warm_');		//优惠券表ID
                $data['warehouse_id'] = $warehouse_id;
                $data['warehouse_name'] = $info2->warehouse_name;
                $data['group_code'] = $info2->group_code;
                $data['group_name'] = $info2->group_name;
                $data['create_user_id']=$user_info->admin_id;
                $data['create_user_name']=$user_info->name;
                $data['create_time']=$data['update_time']=$now_time;
                $id=WmsWarm::insert($data);
                $operationing->access_cause='新建温区';
                $operationing->operation_type='create';

            }

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

            //dd($msg);
            return $msg;
        }




    }



    /***    温区禁用/启用      /wms/warm/warmUseFlag
     */
    public function warmUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warm';
        $medol_name='wmsWarm';
        $self_id=$request->input('self_id');
        $flag='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);
	//dd($status_info);
        $operationing->access_cause='启用/禁用';
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

    /***   温区删除      /wms/warm/warmDelFlag
     */
    public function warmDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warm';
        $medol_name='WmsWarm';

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

    /***    拿去温区数据     /wms/warm/getWarm
     */
    public function  getWarm(Request $request){
        /** 接收数据*/
        $warehouse_id        =$request->input('warehouse_id');

        /*** 虚拟数据**/
        //$warehouse_id='ware_202006012159456407842832';
        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['warehouse_id','=',$warehouse_id],
        ];
        $data['info']=WmsWarm::where($where)->select('self_id','warm_name','min_warm','max_warm')->get();

		foreach ($data['info'] as $k=>$v) {
			$v->warm_name=warm($v->warm_name,$v->min_warm,$v->max_warm);

            }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    温区详情     /wms/warm/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='wms_warm';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'min_warm','max_warm','warehouse_name','warm_name'];
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->warm_name=warm($info->warm_name,$info->min_warm,$info->max_warm);

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

}
?>
