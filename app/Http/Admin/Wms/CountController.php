<?php
namespace App\Http\Admin\Wms;
use App\Http\Controllers\FileController as File;
use App\Models\Group\SystemGroup;
use App\Models\Group\SystemUser;
use App\Models\Wms\WmsLibraryChange;
use App\Models\Wms\WmsLibrarySige;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use App\Models\Shop\ErpShopGoodsSku;
use Illuminate\Support\Facades\Validator;

class CountController extends CommonController{

    /***   商品统计      /wms/count/countList
     */
    public function  countList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    商品统计分页      /wms/count/countPage
     */
    public function countPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $warehouse_name      =$request->input('warehouse_name');
        $external_sku_id     =$request->input('external_sku_id');
        $good_name           =$request->input('good_name');
        $start_time          =$request->input('start_time');
        $end_time            =$request->input('end_time');
        $listrows            =$num;
        $firstrow            =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'type','value'=>'WMS'],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'external_sku_id','value'=>$external_sku_id],

        ];

        $search1=[
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'>=','name'=>'now_num','value'=>0],
            ['type'=>'>','name'=>'entry_time','value'=>$start_time],
            ['type'=>'<=','name'=>'entry_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $where1 = get_list_where($search1);
        $select=['self_id','good_name','good_english_name','wms_unit','wms_target_unit','wms_scale','wms_spec',
            'group_name','use_flag','external_sku_id'];

        $Signselect=['sku_id','production_date','expire_time','can_use','create_time','warehouse_name','area','row','column','tier','storage_number','initial_num','now_num','warehouse_sign_id','entry_time'];
//        dd($select);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
//                    $query->where('now_num','>','0');
                    $query->select($Signselect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ErpShopGoodsSku::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }


//        dump($data['items']->toArray());
        $count3=0;
        $count4=0;
        foreach ($data['items'] as $k=>$v) {
            $v->count=0;
            $v->count1=0;
            $v->count2=0;

            foreach ($v->wmsLibrarySige as $kk=>$vv) {

                $vv->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $vv->now_num);
                $v->count +=$vv->now_num;
                $v->count1 +=$vv->storage_number;
                $v->count2 +=$vv->initial_num;
            }
            $count = WmsLibrarySige::where('entry_time','<=',$start_time)->where('sku_id',$v->self_id)->sum('initial_num');
            $v->count4 = $count??$count3;
            $v->count3 = ($v->count1-$v->count)??$count4;
            $v->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $v->count);
            $v->button_info=$button_info;
        }
//        dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    public function count(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $warehouse_name      =$request->input('warehouse_name');
        $external_sku_id     =$request->input('external_sku_id');
        $good_name           =$request->input('good_name');
        $start_time          =$request->input('start_time');
        $end_time            =$request->input('end_time');
        $listrows            =$num;
        $firstrow            =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'type','value'=>'WMS'],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'external_sku_id','value'=>$external_sku_id],


        ];

        $search1=[
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'>','name'=>'now_num','value'=>0],
            ['type'=>'>=','name'=>'inout_time','value'=>$start_time],
            ['type'=>'<','name'=>'inout_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $where1 = get_list_where($search1);
        $select=['self_id','good_name','good_english_name','wms_unit','wms_target_unit','wms_scale','wms_spec',
            'group_name','use_flag','external_sku_id'];

        $Signselect=['sku_id','inout_time','type','produce_time','expire_time','can_use','create_time','warehouse_name','area','row','column','tier','now_num','warehouse_sign_id'];
//        dd($select);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibraryChange' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
//                    $query->where('now_num','>','0');
//                    $query->select($Signselect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibraryChange' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
//                    $query->select($Signselect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ErpShopGoodsSku::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibraryChange' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
//                    $query->select($Signselect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }


//        dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {
            $v->count=0;
            $v->in_count=0;
            $v->out_count=0;
            $v->initial_count=0;
            $v->jie_count=0;
            foreach ($v->wmsLibraryChange as $kk=>$vv) {
                if ($vv->type == 'preentry'){
                    $v->in_count += $vv->now_num;
                }else{
                    $v->out_count += $vv->change_num;
                }
                $v->initial_count += $vv->initial_num;

                $vv->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $vv->now_num);
                $v->count +=$vv->now_num;
            }
            $v->jie_count = $v->in_count - $v->out_count;
            $v->initial_count = WmsLibraryChange::where('inout_time','<=',$start_time)->where('sku_id',$v->self_id)->sum('initial_num');
            $v->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $v->count);
            $v->button_info=$button_info;
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /**
     * 导出 wms/count/excel
     * */
    public function excel(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $ids            =$request->input('ids');
        // $group_code  =$input['group_code']   ='1234';
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

            $search1=[
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
                ['type'=>'>','name'=>'now_num','value'=>0],
            ];

            $where1 = get_list_where($search1);
            $select=['self_id','good_name','good_english_name','wms_unit','wms_target_unit','wms_scale','wms_spec',
                'group_name','use_flag','external_sku_id','sale_price'];

            $Signselect=['sku_id','production_date','expire_time','can_use','warehouse_name','now_num','warehouse_sign_id'];
            $info=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                $query->where($where1);
                $query->select($Signselect);
            }])->where($where)
                ->orderBy('create_time', 'desc')
                ->select($select)->get();
//dd($info);
            foreach ($info as $k=>$v) {
                $v->count=0;
                foreach ($v->wmsLibrarySige as $kk=>$vv) {

                    $vv->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $vv->now_num);
                    $v->count +=$vv->now_num;
                }
                $v->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $v->count);

            }
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "good_name"=>'产品名称',
                    "external_sku_id"=>'产品编号',
                    "wms_unit"=>'单位',
                    "wms_spec"=>'规格',
                    "sale_price"=>'单价',
                    "count"=>'库存',
                ]];
                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['good_name']=$v->good_name;
                    $list['external_sku_id']=$v->external_sku_id;
                    $list['wms_unit']=$v->wms_unit;
                    $list['wms_spec']=$v->wms_spec;
                    $list['sale_price']=$v->sale_price;
                    $list['count']=$v->count;


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




}
?>
