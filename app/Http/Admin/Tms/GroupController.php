<?php
namespace App\Http\Admin\Tms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\FileController as File;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsGroup;
use App\Models\Group\SystemGroup;

class GroupController extends CommonController{
    /***    业务公司列表      /tms/group/groupList
     */
    public function  groupList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='业务公司';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/客户导入.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    业务公司分页      /tms/group/groupPage
     */
    public function groupPage(Request $request){
        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
        $tms_group_type     =array_column(config('tms.company_type'),'name','key');
        $now_time       =date('Y-m-d H:i:s',time());
        $ago_time       =date('Y-m-d H:i:s',strtotime("$now_time-1 month+1 day"));
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $cost_type      =$request->input('cost_type');
        $type           =$request->input('type');
        $contacts       =$request->input('contacts');
        $tel            =$request->input('tel');
        $company_name   =$request->input('company_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'cost_type','value'=>$cost_type],
            ['type'=>'like','name'=>'type','value'=>$type],
            ['type'=>'like','name'=>'company_name','value'=>$company_name],
            ['type'=>'like','name'=>'contacts','value'=>$contacts],
            ['type'=>'like','name'=>'tel','value'=>$tel],
        ];

        $where=get_list_where($search);

        $select=['self_id','company_name','create_user_name','type','group_name','company_name','agreement_date','agreement',
            'cost_type','contacts','address','tel','use_flag','group_code','bank','bank_number','remark'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsGroup::where($where)->count(); //总的数据量
                $data['items']=TmsGroup::where($where)
                    ->offset($firstrow)->limit($listrows)->orderByRaw(DB::raw("CASE WHERE $ago_time <agreement_date< $now_time"))->orderBy('agreement_date', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsGroup::where($where)->count(); //总的数据量
                $data['items']=TmsGroup::where($where)
                    ->offset($firstrow)->limit($listrows)->orderByRaw(DB::raw("CASE WHERE $ago_time <agreement_date< $now_time"))->orderBy('agreement_date', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsGroup::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderByRaw(DB::raw("CASE WHERE $ago_time <agreement_date< $now_time"))->orderBy('agreement_date', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
//dump($wms_cost_type_show);

        foreach ($data['items'] as $k=>$v) {
//            $v->total_money = number_format($v->total_money/100, 2);
            $company_type = [];
            foreach (explode(',',$v->type) as $kk => $vv){
                $company_type[] = $tms_group_type[$vv]??null;
            }
			$v->type_show=implode('/',$company_type);
			$v->cost_type_show=$tms_cost_type[$v->cost_type]??null;
            $v->button_info=$button_info;
//
        }

        // dump($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;

    }

    /***    业务公司创建      /tms/group/createGroup
     */
    public function createGroup(Request $request){
        $data['type']    =config('tms.company_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'company_202101151011516789650525';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $data['info']=TmsGroup::where($where)->first();
        if ($data['info']){
            if ($data['info']->type != 'driver'){
            }
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;


    }

    /***    业务公司添加进入数据库      /tms/group/addGroup
     */
    public function addGroup(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_group';

        $operationing->access_cause     ='创建/修改业务公司';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $company_name       =$request->input('company_name');
        $group_code         =$request->input('group_code');
        $contacts           =$request->input('contacts');
        $tel       	        =$request->input('tel');
        $address            =$request->input('address');
        $type               =$request->input('type');
        $cost_type          =$request->input('cost_type');
        $bank               =$request->input('bank');
        $bank_number        =$request->input('bank_number');
        $tax_id             =$request->input('tax_id');
        $remark             =$request->input('remark');
        $agreement          =$request->input('agreement');
        $agreement_date     =$request->input('agreement_date');

        /*** 虚拟数据
        $input['self_id']           =$self_id='group_202006040950004008768595';
        $input['company_name']      =$company_name='A公司';
        $input['group_code']           =$group_code='1234';
        $input['contacts']             =$contacts     ='pull';
        $input['tel']              =$tel   ='152';
        $input['address']          =$address  ='pull';
        $input['type']             =$type  ='客户';
        $input['cost_type']        =$cost_type  ='月结';
***/
//        dd($input);
        $rules=[
            'company_name'=>'required',
        ];
        $message=[
            'company_name.required'=>'客户名称不能为空',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $data['company_name']               = $company_name;
            $data['contacts']                   = $contacts;
            $data['address']                    = $address;
            $data['tel']                        = $tel;
            $data['cost_type']      		    = $cost_type;
            $data['bank']      		            = $bank;
            $data['bank_number']      		    = $bank_number;
            $data['tax_id']      		        = $tax_id;
            $data['type']      		            = $type;
            $data['remark']      		        = $remark;
            $data['agreement']      		    = $agreement;
            $data['agreement_date']      	    = $agreement_date;
            $wheres['self_id'] = $self_id;
            $old_info=TmsGroup::where($wheres)->first();

            if($old_info){
                //dd(1111);
                $where_check = [
                   ['group_code','=',$group_code],
                   ['type','=',$type],
                   ['company_name','=',$company_name],
                   ['self_id','!=',$self_id],
                   ['delete_flag','=','Y'],

                ];
                $group_check=TmsGroup::where($where_check)->first();
                if ($group_check) {
                    $msg['code'] = 302;
                    $msg['msg'] = "公司名称已存在";
                    return $msg;
                }
                $data['update_time']=$now_time;
                $id=TmsGroup::where($wheres)->update($data);

                $operationing->access_cause='修改客户公司';
                $operationing->operation_type='update';
            }else{
                $where_check['group_code'] = $group_code;
                $where_check['type'] = $type;
                $where_check['company_name'] = $company_name;
                $group_check=TmsGroup::where($where_check)->first();
                if ($group_check) {
                    $msg['code'] = 302;
                    $msg['msg'] = "公司名称已存在";
                    return $msg;
                }

                $data['self_id']            =generate_id('company_');		//优惠券表ID
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=TmsGroup::insert($data);
                $operationing->access_cause='新建客户公司';
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

    /***    业务公司启用禁用      /tms/group/groupUseFlag
     */
    public function groupUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_group';
        $medol_name='TmsGroup';
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

    /***    业务公司删除      /tms/group/groupDelFlag
     */
    public function groupDelFlag(Request $request,Status $status){

        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_group';
        $self_id=$request->input('self_id');
        $flag='delete_flag';
//        $self_id='company_2021030315204691392271';
        $old_info = TmsGroup::where('self_id',$self_id)->select('group_code','group_name','use_flag','delete_flag','update_time')->first();
        $update['delete_flag'] = 'N';
        $update['update_time'] = $now_time;
        $id = TmsGroup::where('self_id',$self_id)->update($update);
//        dd($id);
//        $status_info=$status->changeFlag($table_name,$self_id,$flag,$now_time);
        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$update;
        $operationing->operation_type=$flag;
        if($id){
            $msg['code']=200;
            $msg['msg']='删除成功！';
            $msg['data']=(object)$update;
        }else{
            $msg['code']=300;
            $msg['msg']='删除失败！';
        }

        return $msg;


    }
    /***    业务公司获取     /tms/group/getCompany
     */
	public function getCompany(Request $request){
		$group_code=$request->input('group_code');
		$type=$request->input('type');
        $company_name=$request->input('company_name');
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'type','value'=>$type],
            ['type'=>'like','name'=>'company_name','value'=>$company_name],
        ];
        $where=get_list_where($search);
        $data['info']=TmsGroup::where($where)->get()->toArray();

	    $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
	}

    /***    业务公司获取     /tms/group/getGroup
     */
    public function getGroup(Request $request){
        $group_code=$request->input('group_code');
        $type=$request->input('type');
        $normal=$request->input('normal');

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'type','value'=>$type],
            ['type'=>'=','name'=>'normal','value'=>$normal]
        ];

        $where=get_list_where($search);
        $data['info']=TmsGroup::where($where)->get();
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    业务公司导入     /tms/group/import
     */
    public function import(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='tms_group';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建业务公司';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');
        $type               =$request->input('type');
        /***虚拟数据
        $input['importurl']    =$importurl="uploads/import/TMS业务公司导入文件范本.xlsx";
        $input['group_code']       =$group_code='1234';
        **/

        $rules = [
            'group_code' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'group_code.required' => '请选择所属公司',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            //发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！
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
             * 第四个位置为数据库的对应字段**/


            $shuzu=[
                '单位类型' =>['Y','N','100','type'],
                '公司名称' =>['Y','N','100','company_name'],
                '联系人' =>['Y','Y','50','contacts'],
                '联系电话' =>['Y','Y','50','tel'],
                '联系地址' =>['N','Y','50','address'],
                '税务登记号' =>['N','Y','50','tax_id'],
                '开户银行' =>['N','Y','50','bank'],
                '银行账号' =>['N','Y','50','bank_number'],
                '备注' =>['N','Y','50','remark'],
            ];
            $ret=arr_check($shuzu,$info_check);
            // dd($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];

            $where_check_code=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code]
            ];


            $info = SystemGroup::where($where_check_code)->select('group_name','group_code')->first();

            //dump($group_info);
            if(empty($info)){
                $msg['code'] = 302;
                $msg['msg'] = '所属公司不存在';
                return $msg;
            }


            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            // dd($tms_group_type);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['company_name','=',$v['company_name']],
                    ['group_code','=',$info->group_code],
                ];
                $company_info = TmsGroup::where($where)->value('company_name');

                if($company_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行业务公司已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                if ($v['type'] == '收货人'){
                    $type = 'take';
                }elseif($v['type'] == '装货人'){
                    $type = 'load';
                }elseif($v['type'] == '托运人'){
                    $type = 'check';
                }else{
                    $strs .= '数据中的第'.$a."行类型错误".'</br>';
                    $cando='N';
                    $abcd++;
                }


                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('company_');
                    $list['type']               = $type;
                    $list['company_name']       = $v['company_name'];
                    $list['contacts']           = $v['contacts'];
                    $list['address']            = $v['address'];
                    $list['tel']                = $v['tel'];
                    $list['tax_id']             = $v['tax_id'];
                    $list['bank']               = $v['bank'];
                    $list['bank_number']        = $v['bank_number'];
                    $list['remark']             = $v['remark'];
                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     =$user_info->admin_id;
                    $list['create_user_name']   =$user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }


                $a++;
            }


            $operationing->new_info=$datalist;
            // dd($strs);
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);

            //dd($datalist);
            $id= TmsGroup::insert($datalist);

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

    /***    业务公司导出     /tms/group/execl
     */
    public function execl(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $tms_group_type     =array_column(config('tms.company_type'),'name','key');
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
//        $group_code  =$input['group_code']   ='1234';
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
            $select=['self_id','company_name','type','group_name','contacts','address','tel','tax_id','remark',
                'bank','bank_number'];
            $info=TmsGroup::where($where)->orderBy('create_time', 'desc')->select($select)->get();

            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "type"=>'单位类型',
                    "company_name"=>'公司名称',
                    "contacts"=>'联系人',
                    "tel"=>'联系电话',
                    "address"=>'公司地址',
                    "tax_id"=>'税务登记号',
                    "bank"=>'开户银行',
                    "bank_number"=>'银行账号',
                    "remark"=>'备注',

                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];

                foreach ($info as $k=>$v){
                    $list=[];

                    $type = '';
                    $cost_type = '';
                    if (!empty($tms_group_type[$v['type']])) {
                        $type = $tms_group_type[$v['type']];
                    }

                    if (!empty($tms_cost_type[$v['cost_type']])) {
                        $cost_type = $tms_cost_type[$v['cost_type']];
                    }

                    $list['id']=($k+1);
                    $list['type']=$type;
                    $list['company_name']=$v->company_name;
                    $list['contacts']=$v->contacts;
                    $list['tel']=$v->tel;
                    $list['address']=$v->address;
                    $list['tax_id']=$v->tax_id;
                    $list['bank']=$v->bank;
                    $list['bank_number']=$v->bank_number;
                    $list['remark']=$v->remark;

                    $data_execl[]=$list;

                }
                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);
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

    /***    业务公司详情     /tms/group/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='tms_group';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'company_name','contacts','address','tel','remark','agreement_date','agreement',
            'type','cost_type'];
        // $self_id='company_202012291153523141320375';
        $info=$details->details($self_id,$table_name,$select);

        if($info){
            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $tms_group_type    =array_column(config('tms.tms_group_type'),'name','key');
            $tms_cost_type    =array_column(config('tms.tms_cost_type'),'name','key');

            $info->type_show=$tms_group_type[$info->type]??null;
            $info->cost_type_show=$tms_cost_type[$info->cost_type]??null;


            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);
            }

            // dd($data);
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
