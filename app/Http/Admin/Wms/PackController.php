<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Models\Wms\WmsPack;
use App\Models\Group\SystemGroup;

class PackController extends CommonController{
    /***    包装列表      /wms/pack/packList
     */
    public function  packList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='包装';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/包装导入实例文件.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    包装列表      /wms/pack/packPage
     */
    public function packPage(Request $request){

        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        //dd($button_info);
        /**接收数据*/
        $num                    = $request->input('num') ?? 10;
        $page                   = $request->input('page') ?? 1;
        $use_flag               = $request->input('use_flag');
        $group_code             =$request->input('group_code');
        $listrows = $num;
        $firstrow = ($page - 1) * $listrows;

        $search = [
            ['type' => '=', 'name' => 'delete_flag', 'value' => 'Y'],
            ['type' => 'all', 'name' => 'use_flag', 'value' => $use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
        ];

        $where = get_list_where($search);

        $select = ['self_id','pack','use_flag','create_user_name','create_time','group_name','group_code'];

        switch ($group_info['group_id']) {
            case 'all':
                $data['total'] = WmsPack::where($where)->count(); //总的数据量
                $data['items'] = WmsPack::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show'] = 'Y';
                break;

            case 'one':
                $where[] = ['group_code', '=', $group_info['group_code']];
                $data['total'] = WmsPack::where($where)->count(); //总的数据量
                $data['items'] = WmsPack::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show'] = 'N';
                break;

            case 'more':
                $data['total'] = WmsPack::where($where)->whereIn('group_code', $group_info['group_code'])->count(); //总的数据量
                $data['items'] = WmsPack::where($where)->whereIn('group_code', $group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show'] = 'Y';
                break;
        }

        foreach ($data['items'] as $k => $v) {

            $v->button_info = $button_info;

        }
        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        //dd($msg);
        return $msg;



    }

    /***    包装列表      /wms/pack/addPack
     */
    public function addPack(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_pack';

        $operationing->access_cause     ='创建/修改包装';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $pack               =$request->input('pack');
        $group_code         =$request->input('group_code');

        /*** 虚拟数据
        $input['self_id']           =$self_id='good_202007011336328472133661';
        $input['group_code']        =$group_code='1234';
        $input['pack']              =$pack='优惠券名称';
         **/
        $rules=[
            'pack'=>'required',
            'group_code'=>'required',
        ];
        $message=[
            'pack.required'=>'请填写包装名称',
            'group_code.required'=>'请选择所属公司',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //包装不能重复
            if($self_id){
                $name_where=[
                    ['pack','=',trim($pack)],
                    ['self_id','!=',$self_id],
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
                ];
            }else{
                $name_where=[
                    ['pack','=',trim($pack)],
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
                ];
            }
            $name_count = WmsPack::where($name_where)->count();            //检查名字是不是重复

            if($name_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '包装名称重复';
                return $msg;
            }
            /** 现在开始可以做数据了**/

            $data['pack'] = trim($pack);

            $where2['self_id'] = $self_id;
            $select_WmsPack=['self_id','pack','group_code','group_name'];
            $old_info=WmsPack::where($where2)->select($select_WmsPack)->first();
            if($old_info){
                $data['update_time'] =$now_time;
                $id=WmsPack::where($where2)->update($data);

                $operationing->access_cause='修改包装';
                $operationing->operation_type='update';
            }else{
                $wehre222['self_id']=$group_code;
                $group_name = SystemGroup::where($wehre222)->value('group_name');

                $data['self_id']= generate_id('pack_');
                $data['create_user_id'] =$user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['group_code'] =$group_code;
                $data['group_name'] =$group_name;
                $data['create_time'] =$data['update_time'] =$now_time;
                $id=WmsPack::insert($data);

                $operationing->access_cause='新建包装';
                $operationing->operation_type='create';
            }

            $operationing->table_id=$old_info?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功';
                $msg['data']=(object)$data;
                return $msg;
            }else{
                $msg['code']=303;
                $msg['msg']='操作失败';
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


    /***    包装列表      /wms/pack/packUseFlag
     */
    public function packUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_pack';
        $medol_name='wmsPack';
        $self_id=$request->input('self_id');
        $flag='useFlag';
        //$self_id='catalog_202011141244216470211';

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

    /***    包装列表      /wms/pack/packDelFlag
     */
    public function packDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_pack';
        $medol_name='wmsPack';
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

    /***    包装导入     /wms/pack/import
     */
    public function import(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_pack';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建包装';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';


        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');
        /****虚拟数据*
        $input['importurl']    =$importurl="uploads/2020-10-13/库区导入文件范本.xlsx";
        $input['group_code']   =$group_code='1234';
         **/
        $rules = [
            'group_code' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'group_code.required' => '请选择公司',
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
                '包装名称' =>['Y','N','255','pack'],
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
                ['group_code','=',$group_code],
            ];

            $group_name = SystemGroup::where($where_check)->value('group_name');

            if(empty($group_name)){
                $msg['code'] = 302;
                $msg['msg'] = '公司不存在';
                return $msg;
            }
            /** 二次效验结束**/
            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;


            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                    $where=[
                        ['delete_flag','=','Y'],
                        ['pack','=',$v['pack']],
                        ['group_code','=',$group_code],
                    ];
                    $pack_info = WmsPack::where($where)->value('group_code');

                    if($pack_info){
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行包装已存在".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }

                    $list=[];
                    if($cando =='Y'){
                        $list['self_id']            =generate_id('pack_');
                        $list['pack']               = $v['pack'];
                        $list['group_code']         = $group_code;
                        $list['group_name']         = $group_name;
                        $list['create_user_id']     = $user_info->admin_id;
                        $list['create_user_name']   = $user_info->name;
                        $list['create_time']        =$list['update_time']=$now_time;
                        $list['file_id']            =$file_id;
                        $datalist[]=$list;
                    }


                $a++;


            }

//            dump($datalist);
            $operationing->new_info=$datalist;

            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= WmsPack::insert($datalist);

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

    /***    获取包装      /wms/pack/getPack
     */
    public function getPack(Request $request){
        /** 接收数据*/
        $group_code       =$request->input('group_code');

        /*** 虚拟数据**/
        //$group_code='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['group_code','=',$group_code],
        ];

        //dd($where);
        $data['info']=WmsPack::where($where)->select('pack')->get();
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;

    }


}
?>
