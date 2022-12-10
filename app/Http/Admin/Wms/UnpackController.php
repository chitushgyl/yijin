<?php
namespace App\Http\Admin\Wms;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;

class UnpackController extends CommonController{
    /***    包装列表      /wms/unpack/unpackList
     */
    public function  unpackList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    包装列表      /wms/unpack/unpackPage
     */
    public function unpackPage(Request $request){

        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
//            ['type'=>'like','name'=>'area','value'=>$input['area']],
//            ['type'=>'all','name'=>'group_code','value'=>$input['group_code']],
        ];

        $where=get_list_where($search);

        $select=['self_id'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('wms_uppack')->where($where)->count(); //总的数据量
                $data['items']=DB::table('wms_uppack')->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=DB::table('wms_uppack')->where($where)->count(); //总的数据量
                $data['items']=DB::table('wms_uppack')->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=DB::table('wms_uppack')->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=DB::table('wms_uppack')->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

//dd($data);
        foreach ($data['items'] as $k=>$v) {

            $v->button_info=$button_info;

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


    }


    /***    包装列表      /wms/unpack/unpackList
     */

    /**新建商户数据提交**/
    public function add_pack(Request $request){
//        //第一步，验证数据
        $input=Input::all();
        $table_name='wms_pack';
        $rules=[
            'pack'=>'required',
            'group_code'=>'required',
        ];
        $message=[
            'pack.required'=>'请填写包装名称',
            'group_code.required'=>'请选择所属公司',
        ];

        $validator=Validator::make($input,$rules,$message);

        //第二步，日志初始化
        $operationing['access_cause']=null;
        $operationing['browse_type']=$request->path();
        $operationing['table']=$table_name;
        $operationing['table_id']=null;
        $operationing['old_info']=null;
        $operationing['new_info']=null;
        $operationing['group_code']=$input['group_code'];
        $operationing['group_name']=$input['group_name'];
        $operationing['ip']=$request->getClientIp();
        $operationing['roll_back_flag']='N';                    //是否允许回滚
        $operationing['operation_type']=null;
        $operationing['admin_flag']='Y';                        //N为只有超级管理员可见
        $operationing['log_status']='FS';                        //初始化为失败
        $operationing['false_cause']=null;

        if($validator->passes()) {
            $where['group_code'] =$input['group_code'];
            $where['pack'] = $input['pack'];
//            $where['self_id'] = $input['self_id'];
            $info = DB::table($table_name)->where($where)->first();

            $data['pack'] = $input['pack'];
            $data['create_time'] = $data['update_time'] = date('Y-m-d H:i:s', time());
            $data['create_user_id']=session('cms_uu_id');
            $data["create_user_name"]=session('cms_name');
            if ($info) {
                $msg['code'] = 300;
                $msg['msg'] = '该包装已存在';
            }else {
                if ($input['self_id']) {
//                    dd(111);
                    $dataeee['self_id'] = $input['self_id'];
                    $dataeee['delete_flag'] = 'Y';
                    $old_info = DB::table($table_name)->where($dataeee)->update($data);
//                    dd($data);
                    $id = DB::table($table_name)->where($dataeee)->update($data);
                    if ($id) {

                        $operationing['old_info'] = (Object)$old_info;
                        $operationing['log_status'] = 'SU';
                        $operationing['new_info'] = (Object)$data;
                        $operationing['table_id'] = $input['self_id'];
                        $operationing['access_cause'] =$input['group_name'].'修改包装：'.$data['pack'];
                        $operationing['operation_type'] = 'update';

                        $msg['code'] = 200;
                        $msg['msg'] = '修改成功';
                    }

                } else {
//                    dd(2222);
                    $data['self_id'] = generate_id('pack_');
                    $data['group_code'] = $input['group_code'];
                    $data['group_name'] = $input['group_name'];
                    $id = DB::table($table_name)->insert($data);
                    if ($id) {
                        $operationing['log_status'] = 'SU';
                        $operationing['new_info'] = (Object)$data;
                        $operationing['table_id'] =$data['self_id'];
                        $operationing['access_cause'] =$input['group_name'].":".'新增包装：'.$data['pack'];
                        $operationing['operation_type'] = 'create';
                        $msg['code'] = 200;
                        $msg['msg'] = '新增成功';
                    }
                }
            }


        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $msg['msg'].=$v."\n";
                $operationing['false_cause'].=$v.'*';
            }
            $msg['code']=501;
            $operationing['new_info']=(object)$input;

        }

        //记录操作日志
//        dd($msg);
        operationing($operationing);
        return response()->json(['msg'=>$msg]);
    }

    /**
     * 入库查询业务往来公司
     */
    public function unpack_group(Request $request){
        $where['group_code']=$request->groupCode;
        $where['delete_flag']='Y';
        $group=DB::table('wms_group')->where($where)->select('self_id','company_name')->get();
        if(!$group->isEmpty()){
            return response()->json(['code'=>200,'msg'=>'成功','data'=>$group]);
        }else{
            return response()->json(['code'=>500,'msg'=>'您没有业务往来公司']);
        }
    }


    /**商品查询**/
    public function load_shop(Request $request){
        $input=Input::all();
        $where['group_code']=$input['group_code'];
        $where['company_id']=$input['business'];

        $where['delete_flag']='Y';
        $erp_shop_goods=DB::table('wms_goods_sku')->where($where)->select('self_id','good_name','group_code','group_name')->get()->toArray();

        return view('Wms.Unpack.load_shop',['erp_shop_goods'=>$erp_shop_goods]);
    }



    /**包装查询**/
    public function get_pack(Request $request){
        $where['self_id']=$request->input('idd');
        $info=DB::table('wms_pack')->where($where)->select('self_id','pack','use_flag','delete_flag','update_time','group_code','group_name')->first();

        if($info){
            $msg['code']=200;
            $msg['self_id']=$info->self_id;
            $msg['pack']=$info->pack;
            $msg['group_code']=$info->group_code;
        }else{
            $msg['code']=300;
            $msg['msg']='查询失败';
        }
        return response()->json(['msg'=>$msg]);
    }

    /***    包装列表      /wms/unpack/unpackList
     */
    public function pack_use_flag(Request $request){
//        dd($request->input('idd'));
        $data['update_time']=date('Y-m-d H:i:s',time());
        $where['self_id']=$request->input('idd');
        $info=DB::table('wms_pack')->where($where)->select('self_id','pack','use_flag','delete_flag','update_time','group_code','group_name')->first();
//        dd($info);

        if($info->use_flag=='Y'){
            $data['use_flag']='N';
            $msg=use_flag('N',3);
            $caozuo='禁用';
            $operationing['operation_type']='forbidden';
        }else if($info->use_flag=='N'){
            $data['use_flag']='Y';
            $msg=use_flag('Y',3);
            $caozuo='启用';
            $operationing['operation_type']='use_flag';
        }

        $id=DB::table('wms_pack')->where($where)->update($data);

        //如果是审核的状态，不能启用禁用

        if($id){
            //做日志文件
            $operationing['access_cause']=$info->group_name.'禁用包装：'.$info->pack;
            $operationing['browse_type']=$request->path();
            $operationing['table']='wms_pack';
            $operationing['table_id']=$request->input('idd');
            $operationing['new_info']=(object)$data;
            $operationing['old_info']=$info;
            $operationing['group_code']=$info->group_code;
            $operationing['group_name']=$info->group_name;
            $operationing['roll_back_flag']='Y';
            $operationing['admin_flag']='N';
            $operationing['ip']=$request->getClientIp();
            $operationing['log_status']='SU';
            $operationing['false_cause']=null;
            operationing($operationing);
            return response()->json(['st'=> true,'msg'=>$msg]);
        }else{
            return response()->json(['st'=> false,'msg'=>'禁用失败']);
        }

    }

    /***    包装列表      /wms/unpack/unpackList
     */
    public function pack_del_flag(Request $request){
//        dd(11111);
//        dd($request->all());
        $data['use_flag']='N';
        $data['delete_flag']='N';
        $data['update_time']=date('Y-m-d H:i:s',time());
        $where['self_id']=$request->input('idd');
        $info=DB::table('wms_pack')->where($where)->select('self_id','pack','use_flag','delete_flag','update_time','group_code','group_name')->first();
        $id=DB::table('wms_pack')->where($where)->update($data);
//        dd($id);
        if($id){
            //做日志文件
            $operationing['access_cause']=$info->group_name.'删除'.'包装：'.$info->pack;
            $operationing['browse_type']=$request->path();
            $operationing['table']='wms_warehouse_sign';
            $operationing['table_id']=$request->input('idd');
            $operationing['new_info']=(object)$data;
            $operationing['old_info']=$info;
            $operationing['group_code']=$info->group_code;
            $operationing['group_name']=$info->group_name;
            $operationing['roll_back_flag']='N';
            $operationing['operation_type']='delete';
            $operationing['admin_flag']='N';
            $operationing['ip']=$request->getClientIp();
            $operationing['log_status']='SU';
            $operationing['false_cause']=null;
            operationing($operationing);
            return response()->json(['st'=> true,'msg'=>'删除成功']);
        }else{
            return response()->json(['st'=> false,'msg'=>'删除失败']);
        }

    }


    /**拆包规则数据提交**/
    public function add_unpack(Request $request){
        //dd($request->all());
//        //第一步，验证数据
        $input=Input::all();
        //dd($input);
        $table_name='wms_uppack';
        $rules=[
            'group_code'=>'required',
            'from_sku_id'=>'required',
            'to_sku_id'=>'required',
        ];
        $message=[
            'group_code.required'=>'请选择公司',
            'from_sku_id.required'=>'请选择原商品',
            'to_sku_id.required'=>'请选择目标商品',
        ];

        $validator=Validator::make($input,$rules,$message);

        //第二步，日志初始化
        $operationing['access_cause']=null;
        $operationing['browse_type']=$request->path();
        $operationing['table']=$table_name;
        $operationing['table_id']=null;
        $operationing['old_info']=null;
        $operationing['new_info']=null;
        $operationing['group_code']=$input['group_code'];
        $operationing['group_name']=$input['group_name'];
        $operationing['ip']=$request->getClientIp();
        $operationing['roll_back_flag']='N';                    //是否允许回滚
        $operationing['operation_type']=null;
        $operationing['admin_flag']='Y';                        //N为只有超级管理员可见
        $operationing['log_status']='FS';                        //初始化为失败
        $operationing['false_cause']=null;

        if($validator->passes()) {
            $where['group_code'] =$input['group_code'];
            $where['from_sku_id'] =$input['from_sku_id'];
            $where['to_sku_id'] =$input['to_sku_id'];
            $where['delete_flag'] ='Y';
            $where['company_id'] =$input['business'];
            $info = DB::table($table_name)->where($where)->first();


            if ($info) {
                $msg['code'] = 300;
                $msg['msg'] = '该拆包规则已存在';
            }else {
                $data['self_id'] = generate_id('uppack_');
                $data['from_sku_id'] = $input['from_sku_id'];
                $data['to_sku_id'] = $input['to_sku_id'];
                $data['create_time'] = $data['update_time'] = date('Y-m-d H:i:s', time());
                $data['create_user_id']=session('cms_uu_id');
                $data["create_user_name"]=session('cms_name');
                $data['group_code'] = $input['group_code'];
                $data['group_name'] = $input['group_name'];
                $data['company_id'] = $input['business'];

                $where20['self_id']=$input['business'];
                $data['company_name'] = DB::table('wms_group')->where($where20)->value('company_name');
                $id = DB::table($table_name)->insert($data);
                if ($id) {
                    $operationing['log_status'] = 'SU';
                    $operationing['new_info'] = (Object)$data;
                    $operationing['table_id'] =$data['self_id'];
                    $operationing['access_cause'] =$input['group_name'].":".'新增拆包规则：'.$data['from_sku_id'];
                    $operationing['operation_type'] = 'create';
                    $msg['code'] = 200;
                    $msg['msg'] = '新增拆包规则成功';
                }
            }

        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $msg['msg'].=$v."\n";
                $operationing['false_cause'].=$v.'*';
            }
            $msg['code']=501;
            $operationing['new_info']=(object)$input;

        }

        //记录操作日志
//        dd($msg);
        operationing($operationing);
        return response()->json(['msg'=>$msg]);
    }

}
?>
