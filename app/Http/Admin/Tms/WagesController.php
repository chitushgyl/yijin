<?php
namespace App\Http\Admin\Tms;
use App\Models\Tms\TmsWages;
use App\Models\Tms\TmsWares;
use App\Models\Tms\TmsOrder;
use App\Models\Tms\DriverCommission;
use App\Models\Group\SystemUser;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\FileController as File;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsLine;



class WagesController extends CommonController{
    /***    商品列表头部      /tms/wages/wagesList
     */
    public function  wagesList(Request $request){

        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='商品';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/商品导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($data['button_info']->toArray());
        return $msg;
    }
    /***    商品分页     /tms/wages/wagesPage
     */
    public function wagesPage(Request $request){
        $user_type    =array_column(config('tms.user_type'),'name','key');
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
//dd($button_info);
        /**接收数据11*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $start_time     =$request->input('start_time').'-01 00:00:00';
        $end_time       =$request->input('end_time').'-';
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
        ];
        $search1=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'>=','name'=>'send_time','value'=>$start_time],
            ['type'=>'<=','name'=>'send_time','value'=>$end_time],
        ];
        $where=get_list_where($search);
        $where1=get_list_where($search1);

        $select=['self_id','user_id','user_name','position','date','base_pay','reward','reward_back','ti_money','remark','total_money','group_code','group_name','use_flag','delete_flag','create_time','update_time'];
        $select1=['self_id','driver_id','car_number','send_time','order_weight','upload_weight','send_id','send_name','gather_id','gather_name','good_name','group_code','delete_flag','use_flag'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsWages::where($where)->count(); //总的数据量
                $data['items']=TmsWages::with(['tmsOrder' => function($query) use($select1,$where1){
                    $query->select($select1);
                    $query->where($where1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsWages::where($where)->count(); //总的数据量
                $data['items']=TmsWages::with(['tmsOrder' => function($query) use($select1,$where1){
                    $query->select($select1);
                    $query->where($where1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsWages::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsWages::with(['tmsOrder' => function($query) use($select1,$where1){
                    $query->select($select1);
                    $query->where($where1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
            $v->position               =$user_type[$v->position]??null;
        }
        //exit;
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    新建商品      /tms/wages/createWages
     */
    public function createWages(Request $request){
        $data['type'] = config('tms.wares_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['info']=TmsWages::where($where)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    新建商品入库      /tms/wages/addWages
     */
    public function addWages(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_wares';

        $operationing->access_cause     ='创建/修改货物';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id             =$request->input('self_id');
        $user_id             =$request->input('user_id');//员工ID
        $user_name           =$request->input('user_name');//员工名称
        $position            =$request->input('position');// 职位
        $date                =$request->input('date');// 月份
        $base_pay            =$request->input('base_pay');//基本工资
        $reward              =$request->input('reward');//奖励
        $reward_back         =$request->input('reward_back');// 奖金返还
        $ti_money            =$request->input('ti_money');//提成
        $remark              =$request->input('remark');//备注
        $total_money         =$request->input('total_money');//合计

        $rules=[

        ];
        $message=[

        ];
        $validator=Validator::make($input,$rules,$message);
        //操作的表
        if($validator->passes()){
            $where_goods=[
                ['delete_flag','=','Y'],
                ['use_flag','=','Y'],
                ['self_id','=',$user_info->group_code],
            ];

            $info2 = SystemGroup::where($where_goods)->select('self_id','group_code','group_name')->first();

            $data['user_id']          = $user_id;
            $data['user_name']        = $user_name;
            $data['position']         = $position;
            $data['date']             = $date;
            $data['base_pay']         = $base_pay;
            $data['reward']           = $reward;
            $data['reward_back']      = $reward_back;
            $data['ti_money']         = $ti_money;
            $data['remark']           = $remark;
            $data['total_money']      = $base_pay + $reward + $reward_back + $ti_money;

            $wheres['self_id'] = $self_id;
            $old_info=TmsWages::where($wheres)->first();

            if($old_info){
                //dd(1111);
                $data['update_time']=$now_time;
                $id=TmsWages::where($wheres)->update($data);

                $operationing->access_cause='修改货物';
                $operationing->operation_type='update';

            }else{

                $data['self_id']=generate_id('wages_');		//优惠券表ID
                $data['group_code'] = $info2->group_code;
                $data['group_name'] = $info2->group_name;
                $data['create_user_id']=$user_info->admin_id;
                $data['create_user_name']=$user_info->name;
                $data['create_time']=$data['update_time']=$now_time;
                $id=TmsWages::insert($data);
                $operationing->access_cause='新建货物';
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
            return $msg;
        }


    }

    /***    商品禁用/启用      /tms/wages/wagesUseFlag
     */
    public function wagesUseFlag(Request $request,Status $status){

        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_wages';
        $medol_name='TmsWages';
        $self_id=$request->input('self_id');
        $flag='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

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

    /***    商品删除      /tms/wages/wagesDelFlag
     */
    public function waresDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_wares';
        $medol_name='TmsWares';
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

    /***    商品导入     /tms/wares/import
     */
    public function import(Request $request){
        $period  =array_column(config('wms.period'),'key','name');
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='erp_shop_goods_sku';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建商品';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');

        $file_id            =$request->input('file_id');

        /****虚拟数据
        $input['importurl']    =$importurl="uploads/2020-10-13/商品导入文件范本.xlsx";
        $input['company_id']   =$company_id='group_202011181550202905767384';
         ***/
        $rules = [

            'importurl' => 'required',
        ];
        $message = [
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
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
                '产品编号' =>['Y','N','64','external_sku_id'],
                '产品名称' =>['Y','Y','255','good_name'],
                '产品类型' =>['Y','Y','255','type'],
                '单位' =>['Y','Y','10','wms_unit'],
                '规格' =>['N','Y','50','wms_spec'],
                '单价' =>['N','Y','50','sale_price'],

            ];

            $ret=arr_check($shuzu,$info_check);

            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];

            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){

                $where['delete_flag'] = 'Y';
                $where['external_sku_id']=$v['external_sku_id'];
                $where['type']='wms';
                $good_info = ErpShopGoodsSku::where($where)->value('external_sku_id');

                if($good_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行商品编号已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                if ($v['type'] =='办公'){
                    $type = 'office';
                }else{
                    $type = 'car';
                }
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('sku_');
                    $list['external_sku_id']    = $v['external_sku_id'];
                    $list['good_name']          = $v['good_name'];
                    $list['wms_unit']           = $v['wms_unit'];
                    $list['wms_spec']           = $v['wms_spec'];
                    $list['type']               = 'wms';
                    $list['sale_price']         = $v['sale_price'];
                    $list['good_type']          = $type;
                    $list['group_code']         = $user_info->group_code;
                    $list['group_name']         = $user_info->group_name;
                    $list['create_user_id']     =$user_info->admin_id;
                    $list['create_user_name']   =$user_info->name;
                    $list['create_time']        =$list['update_time']=date('Y-m-d H:i:s',time());
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }

                $a++;
            }

            $operationing->new_info=$datalist;
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= TmsWares::insert($datalist);

            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }


        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }

    }

    /***    根据业务公司ID拉取商品      /tms/wares/getWares
     */
    public function getWares(Request $request){
        /** 接收数据*/
        $company_id       =$request->input('group_code');

        /*** 虚拟数据**/
        //$warehouse_id='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['group_code','=',$company_id],
        ];

        $data['info']=TmsWares::where($where)->select('self_id','wares_name','un_num','type','group_code','group_name','use_flag','delete_flag')->get();
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;


    }
    /***    商品导出     /tms/wares/execl
     */
    public function execl(Request $request,File $file){
        $period  =array_column(config('wms.period'),'name','key');
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        //$group_code  =$input['group_code']   ='group_202011201701272916308975';
        //dd($group_code);
        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择公司',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /** 下面开始执行导出逻辑**/
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            //查询条件
            $search=[
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];
            $where=get_list_where($search);

            $select=['self_id','wares_name','un_num','type','group_code','group_name','use_flag','delete_flag','create_time','update_time'];
            $info=TmsWares::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "external_sku_id"=>'产品编号',
                    "good_name"=>'产品名称',
                    "type"=>'产品类型',
                    "wms_unit"=>'单位',
                    "wms_spec"=>'规格',
                    "sale_price"=>'单价',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];
                    if ($v->type == 'office'){
                        $type = '办公';
                    }else{
                        $type = '车用';
                    }
                    $list['id']=($k+1);
                    $list['external_sku_id']=$v->external_sku_id;
                    $list['good_name']=$v->good_name;
                    $list['type']=$type;
                    $list['wms_unit']=$v->wms_unit;
                    $list['wms_spec']=$v->wms_spec;
                    $list['sale_price']=$v->sale_price;

                    if($v->wms_scale && $v->wms_target_unit){
                        $list['good_zhuanhua']='1'.$v->wms_target_unit.'='.$v->wms_scale.$v->wms_unit;

                    }else{
                        $list['good_zhuanhua']=null;
                    }

                    $data_execl[]=$list;
                }
                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);

                //dd($msg);
                return $msg;

            }else{
                $msg['code']=301;
                $msg['msg']="没有数据可以导出";
                return $msg;
            }
        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code']=300;
            return $msg;
        }

    }

    /***    商品详情     /tms/wares/details
     */
    public function  details(Request $request,Details $details){

        $self_id=$request->input('self_id');
        $table_name='tms_wages';
        $select=['self_id','user_id','user_name','position','date','base_pay','reward','reward_back','ti_money','remark','total_money','group_code','group_name','use_flag','delete_flag','create_time','update_time'];
        //$self_id='group_202009282038310201863384';
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
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

    //提成
    public function commissionList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='跟单';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/跟单导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($data['button_info']->toArray());
        return $msg;
    }

    public function commissionPage(Request $request){
        $user_type    =array_column(config('tms.user_type'),'name','key');
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
//dd($button_info);
        /**接收数据11*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $driver_id      =$request->input('driver_id');
        $user_name      =$request->input('user_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

    
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'driver_id','value'=>$driver_id],
            ['type'=>'>=','name'=>'leave_time','value'=>$start_time.' 00:00:00'],
            ['type'=>'<=','name'=>'leave_time','value'=>$start_time.' 23:59:59'],
        ];
       
        $where=get_list_where($search);

        $select=['self_id','driver_id','driver_name','leave_time','use_flag','delete_flag','group_code','group_name','money','order_id','create_time','update_time'
    ];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DriverCommission::where($where)->count(); //总的数据量
                $data['items']=DriverCommission::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('leave_time', 'asc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=DriverCommission::where($where)->count(); //总的数据量
                $data['items']=DriverCommission::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('leave_time', 'asc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=DriverCommission::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=DriverCommission::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('leave_time', 'asc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    public function getCommissionOrder(Request $request){

    }

    public function getWages(Request $request){
        $user_type    =array_column(config('tms.user_type'),'name','key');
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $user_info  = $request->get('user_info');//接收中间件产生的参数
//dd($button_info);
        /**接收数据11*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $start_time     =$request->input('start_time')??'2023-04-01';
        $end_time       =$request->input('end_time')??'2023-04-15';
        $driver_id      =$request->input('driver_id');
        $user_name      =$request->input('user_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

    
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'driver_id','value'=>$driver_id],
            ['type'=>'>=','name'=>'leave_time','value'=>$start_time.' 00:00:00'],
            ['type'=>'<=','name'=>'leave_time','value'=>$start_time.' 23:59:59'],
        ];
       
        $where=get_list_where($search);

        $search1=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'driver_id','value'=>$driver_id],
        ];
       
        $where1=get_list_where($search1);
        $select3 =['self_id','name','salary'];
        $select=['self_id','driver_id','user_name','escort','escort_name','car_number','send_time','order_weight','upload_weight','send_id','send_name','gather_id','gather_name','good_name','group_code','delete_flag','use_flag','leave_time','pay_id'];
        $select1=['self_id','send_id','send_name','gather_id','gather_name','delete_flag','create_time','kilo_num','num','group_code','group_name','use_flag','car_num','line_list','pay_type','once_price','base_pay'];
        $select2=['self_id','pay_id','send_name','gather_name','leave_time'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemUser::where($where)->count(); //总的数据量
                $data['items']=SystemUser::with(['tmsOrder' => function($query) use($where,$select1,$select){
                    $query->where($where);
                    $query->select($select);
                    $query->orderBy('leave_time','desc');
                }])->with(['driverCommission' => function($query) use($select1,$where){
                    $query->where($where);
                }])
                ->where($where1)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select3)
                    ->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemUser::where($where)->count(); //总的数据量
                $data['items']=SystemUser::with(['tmsOrder' => function($query) use($where,$select1,$select){
                    $query->where($where);
                    $query->select($select);
                    $query->orderBy('leave_time','desc');
                }])->with(['driverCommission' => function($query) use($select1,$where){
                    $query->where($where);
                }])
                ->where($where1)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select3)
                    ->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemUser::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemUser::with(['tmsOrder' => function($query) use($where,$select1,$select){
                    $query->where($where);
                    $query->select($select);
                    $query->orderBy('leave_time','desc');
                }])->with(['driverCommission' => function($query) use($select1,$where){
                    $query->where($where);
                }])
                ->where($where1)
                ->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select3)
                    ->get();
                $data['group_show']='Y';
                break;
        }
        $date = getDateFromRange($start_time,$end_time);
        
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }



}
?>

