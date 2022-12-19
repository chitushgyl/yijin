<?php
namespace App\Http\Admin\Wms;
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
use App\Models\Shop\ErpShopGoodsSku;
use App\Models\Wms\WmsGroup;

class GoodController extends CommonController{
    /***    商品列表头部      /wms/good/goodList
     */
    public function  goodList(Request $request){

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
    /***    商品分页     /wms/good/goodPage
     */
    public function goodPage(Request $request){
        $period  =array_column(config('wms.period'),'name','key');

        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
//dd($button_info);
        /**接收数据11*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $good_name      =$request->input('good_name');
        $company_name   =$request->input('company_name');
        $external_sku_id   =$request->input('external_sku_id');
        $good_type   =$request->input('good_type');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'type','value'=>'wms'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'like','name'=>'company_name','value'=>$company_name],
            ['type'=>'like','name'=>'external_sku_id','value'=>$external_sku_id],
            ['type'=>'=','name'=>'good_type','value'=>$good_type],
        ];

        $where=get_list_where($search);

        $select=['self_id','use_flag','good_name','good_english_name','external_sku_id','wms_unit','wms_target_unit','wms_scale','wms_spec',
            'wms_length','wms_wide','wms_high','wms_weight','wms_out_unit','company_name','group_name','period_value','period','sale_price','good_type'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ErpShopGoodsSku::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {
            if($v->period && $v->period_value){
				//dump($v);
                $v->period=$v->period_value.$period[$v->period];
            }else{
                $v->period=null;
            }

            if($v->wms_scale && $v->wms_target_unit){
                $v->zhuanhua='1'.$v->wms_target_unit.'='.$v->wms_scale.$v->wms_unit;
            }else{
                $v->zhuanhua=null;
            }


            $v->button_info=$button_info;

        }
		//exit;
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    新建商品      /wms/good/createGood
     */
    public function createGood(Request $request){
        $data['type'] = config('wms.good_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['info']=ErpShopGoodsSku::where($where)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    新建商品入库      /wms/good/addGood
     */
    public function addGood(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='erp_shop_goods_sku';

        $operationing->access_cause     ='创建/修改商品';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $external_sku_id    =$request->input('external_sku_id');//商品编号
        $good_name          =$request->input('good_name');//产品名称
        $wms_unit           =$request->input('wms_unit');//单位
        $wms_spec           =$request->input('wms_spec');//规格
        $sale_price         =$request->input('sale_price');//单价
        $type               =$request->input('type');//产品类型 //办公  车用


        $rules=[
            'external_sku_id'=>'required',
            'good_name'=>'required',
            'wms_unit'=>'required',
        ];
        $message=[
            'external_sku_id.required'=>'请输入商品编号',
            'good_name.required'=>'请填写商品名称',
            'wms_unit.required'=>'请填写入库单位',
        ];
        $validator=Validator::make($input,$rules,$message);

        //操作的表

        if($validator->passes()){
            if($self_id){
                $name_where=[
                    ['external_sku_id','=',trim($external_sku_id)],
                    ['self_id','!=',$self_id],
                    ['delete_flag','=','Y'],
                ];
            }else{
                $name_where=[
                    ['external_sku_id','=',trim($external_sku_id)],
                    ['delete_flag','=','Y'],
                ];
            }
            $name_count = ErpShopGoodsSku::where($name_where)->count();            //检查名字是不是重复

            if($name_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '产品编号重复';
                return $msg;
            }

            $where_goods=[
                ['delete_flag','=','Y'],
                ['use_flag','=','Y'],
                ['self_id','=',$user_info->group_code],
            ];

            $info2 = SystemGroup::where($where_goods)->select('self_id','group_code','group_name')->first();


            $data['external_sku_id']    = $external_sku_id;
            $data['good_name']          = $good_name;
            $data['wms_unit']           = $wms_unit;
            $data['wms_spec']           = $wms_spec;//规格
            $data['type']               = 'wms';
            $data['sale_price']         = $sale_price;
            $data['good_type']          = $type;

            $wheres['self_id'] = $self_id;
            $old_info=ErpShopGoodsSku::where($wheres)->first();

            if($old_info){
                //dd(1111);
                $data['update_time']=$now_time;
                $id=ErpShopGoodsSku::where($wheres)->update($data);

                $operationing->access_cause='修改商品';
                $operationing->operation_type='update';


            }else{

                $data['self_id']=generate_id('sku_');		//优惠券表ID
                $data['group_code'] = $info2->group_code;
                $data['group_name'] = $info2->group_name;
                $data['create_user_id']=$user_info->admin_id;
                $data['create_user_name']=$user_info->name;
                $data['create_time']=$data['update_time']=$now_time;
                $id=ErpShopGoodsSku::insert($data);
                $operationing->access_cause='新建商品';
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

    /***    商品禁用/启用      /wms/good/goodUseFlag
     */
    public function goodUseFlag(Request $request,Status $status){

        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='erp_shop_goods_sku';
        $medol_name='erpShopGoodsSku';
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

    /***    商品删除      /wms/good/goodDelFlag
     */
    public function goodDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='erp_shop_goods_sku';
        $medol_name='erpShopGoodsSku';
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

    /***    商品导入     /wms/good/import
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
        $company_id         =$request->input('company_id');
        $file_id            =$request->input('file_id');

        /****虚拟数据
        $input['importurl']    =$importurl="uploads/2020-10-13/商品导入文件范本.xlsx";
        $input['company_id']   =$company_id='group_202011181550202905767384';
		***/
        $rules = [
            'company_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'company_id.required' => '请选择业务公司',
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
                '规格' =>['N','Y','50','wms_spec'],
                '主计量单位' =>['Y','Y','10','wms_unit'],
            ];

            $ret=arr_check($shuzu,$info_check);

            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];


            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$company_id],
            ];


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
                $where['company_id']=$company_id;
                $where['type']='wms';
                $good_info = ErpShopGoodsSku::where($where)->value('external_sku_id');

                if($good_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行商品编号已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('sku_');
                    $list['external_sku_id']    = $v['external_sku_id'];
                    $list['good_name']          = $v['good_name'];
                    $list['wms_unit']           = $v['wms_unit'];
                    $list['wms_spec']           = $v['wms_spec'];
                    $list['type']               = 'wms';
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
            $id= ErpShopGoodsSku::insert($datalist);

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

    /***    根据业务公司ID拉取商品      /wms/good/getGood
     */
    public function getGood(Request $request){
        /** 接收数据*/
        $company_id       =$request->input('group_code');

        /*** 虚拟数据**/
        //$warehouse_id='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['type','=','wms'],
            ['group_code','=',$company_id],
        ];

        $data['info']=ErpShopGoodsSku::where($where)->select('self_id','sale_price','external_sku_id','good_name','wms_spec','wms_unit','group_code','group_name')->get();
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;


    }
    /***    商品导出     /wms/good/execl
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

            $select=['self_id','external_sku_id','company_name','good_english_name','use_flag','group_name','good_name','wms_unit','wms_target_unit','wms_scale',
                'wms_length','wms_wide','wms_high','wms_weight',
                'period_value','period'];
            $info=ErpShopGoodsSku::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "company_name"=>'业务往来公司',
                    "external_sku_id"=>'商品编号',
                    "good_name"=>'商品名称',
                    "good_english_name"=>'商品英文名称',
                    "wms_unit"=>'入库单位',
                    "good_zhuanhua"=>'商品包装换算',
                    "period"=>'商品有效期',
                    "use_flag"=>'状态',
                    "wms_length"=>'箱长（米）',
                    "wms_wide"=>'箱长（米）',
                    "wms_high"=>'箱长（米）',
                    "wms_weight"=>'箱重（KG）',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['company_name']=$v->company_name;
                    $list['good_english_name']=$v->good_english_name;
                    $list['external_sku_id']=$v->external_sku_id;
                    $list['good_name']=$v->good_name;
                    $list['wms_unit']=$v->wms_unit;

                    if($v->wms_scale && $v->wms_target_unit){
                        $list['good_zhuanhua']='1'.$v->wms_target_unit.'='.$v->wms_scale.$v->wms_unit;

                    }else{
                        $list['good_zhuanhua']=null;
                    }
                    $list['period']=$v->period_value.$period[$v->period];

                    if($v->use_flag == 'Y'){
                        $list['use_flag']='使用中';
                    }else{
                        $list['use_flag']='禁止使用';
                    }

                    $list['wms_length']=$v->wms_length;
                    $list['wms_wide']=$v->wms_wide;
                    $list['wms_high']=$v->wms_high;
                    $list['wms_weight']=$v->wms_weight;

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

    /***    商品详情     /wms/good/details
     */
    public function  details(Request $request,Details $details){

        $self_id=$request->input('self_id');
        $table_name='erp_shop_goods_sku';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time','sale_price','good_type',
            'good_name','good_english_name','external_sku_id','wms_unit','wms_target_unit','wms_scale','wms_spec','wms_length','wms_wide','wms_high','wms_weight','wms_out_unit','company_name','period_value','period'];
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



}
?>
