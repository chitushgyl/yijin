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
use App\Models\Wms\WmsShop;
use App\Models\Wms\WmsGroup;
class ShopController extends CommonController{
    /***    门店列表      /wms/shop/shopList
     */
    public function  shopList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='门店';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/门店导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    门店分页      /wms/shop/shopPage
     */
    public function shopPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $address        =$request->input('address');
        $warehouse_name =$request->input('warehouse_name');
        $tel        =$request->input('tel');
        $contacts    =$request->input('contacts');
        $line_code    =$request->input('line_code');
        $contacts_code    =$request->input('contacts_code');
        $external_id    =$request->input('external_id');
        $name    =$request->input('name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'address','value'=>$address],
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'like','name'=>'tel','value'=>$tel],
            ['type'=>'like','name'=>'contacts','value'=>$contacts],
            ['type'=>'like','name'=>'line_code','value'=>$line_code],
            ['type'=>'like','name'=>'contacts_code','value'=>$contacts_code],
            ['type'=>'like','name'=>'external_id','value'=>$external_id],
            ['type'=>'like','name'=>'name','value'=>$name],
        ];

        $where=get_list_where($search);

        $select=['self_id','external_id','name','contacts','address','tel','group_name','company_name','use_flag','city','line_code','contacts_code'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsShop::where($where)->count(); //总的数据量
                $data['items']=WmsShop::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsShop::where($where)->count(); //总的数据量
                $data['items']=WmsShop::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsShop::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsShop::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
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

    /***    门店创建      /wms/shop/createShop
     */
    public function createShop(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        //$self_id='coupon_20200928155425956582308';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['info']=WmsShop::where($where)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    门店创建进入数据库      /wms/shop/addShop
     */
    public function addShop(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_shop';

        $operationing->access_cause     ='创建/修改门店';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $external_id        =$request->input('external_id');
        $name       		=$request->input('name');
        $contacts       	=$request->input('contacts');
        $address       		=$request->input('address');
        $tel       			=$request->input('tel');
        $longitude      	=$request->input('longitude');
        $dimensionality     =$request->input('dimensionality');
        $company_id      	=$request->input('company_id');
        $line_code      	=$request->input('line_code');
        $contacts_code      =$request->input('contacts_code');

        /*** 虚拟数据
        $input['self_id']           =$self_id='good_202007011336328472133661';
        $input['external_id']      =$external_id='group_202006040950004008768595';
        $input['name']              =$name='常温';
        $input['contacts']              =$contacts='12';
        $input['address']              =$address='15';
        $input['tel']              =$tel='常温';
        $input['longitude']              =$longitude='12';
        $input['dimensionality']              =$dimensionality='15';
        $input['company_id']              =$company_id='15';
        $input['line_code']              =$line_code='15';
        $input['contacts_code']              =$contacts_code='15';
***/

        //第一步，验证数据
        $rules=[
            'name'=>'required',
            'company_id'=>'required',
        ];
        $message=[
            'name.required'=>'请填写门店名称',
            'company_id.required'=>'请选择所属公司',
        ];
        $validator=Validator::make($input,$rules,$message);


        if($validator->passes()) {

            if($self_id){
                $name_where=[
                    ['external_id','=',trim($external_id)],
                    ['self_id','!=',$self_id],
                    ['company_id','=',$company_id],
                    ['delete_flag','=','Y'],
                ];
            }else{
                $name_where=[
                    ['external_id','=',trim($external_id)],
                    ['company_id','=',$company_id],
                    ['delete_flag','=','Y'],
                ];
            }
            $name_count = WmsShop::where($name_where)->count();            //检查名字是不是重复11

            if($name_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '公司编号重复';
                return $msg;
            }

            $data['external_id'] = trim($external_id);
            $data['name'] = $name;
            $data['contacts'] = $contacts;
            $data['address'] = $address;
            $data['tel'] = $tel;
            $data['longitude'] = $longitude;
            $data['dimensionality'] = $dimensionality;
            $data['company_id'] = $company_id;
            $data['line_code'] = $line_code;
            $data['contacts_code'] = $contacts_code;

            $where2['self_id'] = $self_id;
            $old_info=WmsShop::where($where2)->first();

            if($old_info){
                $data['update_time'] =$now_time;
                $id=WmsShop::where($where2)->update($data);

                $operationing->access_cause='修改门店';
                $operationing->operation_type='update';
            }else{
                $wehre222['self_id']=$company_id;
                $info = WmsGroup::where($wehre222)->select('company_name','group_code','group_name')->first();

                $data['self_id']= generate_id('shop_');
                $data['create_user_id'] =$user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['group_code'] =$info->group_code;
                $data['group_name'] =$info->group_name;
                $data['company_id'] =$company_id;
                $data['company_name'] =$info->company_name;
                $data['create_time'] =$data['update_time'] =$now_time;
                $id=WmsShop::insert($data);

                $operationing->access_cause='新建门店';
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

    /***    门店禁用      /wms/shop/shopUseFlag
     */
    public function shopUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_shop';
        $medol_name='wmsShop';
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

    /***    门店删除      /wms/shop/shopDelFlag
     */
    public function shopDelFlag(Request $request,Status $status){

        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_shop';
        $medol_name='wmsShop';
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


    /***    门店导入     /wms/shop/import
     */
    public function import(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_shop';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建门店';
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
        $input['importurl']    =$importurl="uploads/2020-10-13/门店导入文件范本.xlsx";
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
                '门店编号' =>['Y','Y','64','external_id'],
                '门店名称' =>['Y','Y','255','name'],
                '线路号' =>['N','Y','255','line_code'],
                '门店地址' =>['N','Y','255','address'],
                '联系人' =>['N','Y','50','contacts'],
                '收货人编号' =>['N','Y','50','contacts_code'],
                '联系电话' =>['N','Y','50','tel'],
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
            $info = WmsGroup::where($where_check)->select('company_name','group_name','group_code')->first();
//dump($company_info);
            if(empty($info)){
                $msg['code'] = 302;
                $msg['msg'] = '业务公司不存在';
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
                    ['external_id','=',$v['external_id']],
                    ['company_id','=',$company_id],
                ];

//                $good_info = WmsShop::where($where)->value('external_id');

//                if($good_info){
//                    if($abcd<$errorNum){
//                        $strs .= '数据中的第'.$a."行门店编号已存在".'</br>';
//                        $cando='N';
//                        $abcd++;
//                    }
//                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('shop_');
                    $list['external_id']        = $v['external_id'];
                    $list['name']               = $v['name'];
                    $list['line_code']          = $v['line_code'];
                    $list['address']            = $v['address'];
                    $list['contacts']           = $v['contacts'];
                    $list['tel']                = $v['tel'];
                    $list['contacts_code']      = $v['contacts_code'];
                    $list['company_id']         = $company_id;
                    $list['company_name']       = $info->company_name;
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
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);

            //dd($datalist);
            $id= WmsShop::insert($datalist);

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

    /***    门店导出     /wms/shop/execl
     */
    public function execl(Request $request,File $file){
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
            $select=['self_id','company_name','use_flag','group_name','name','contacts','address','tel','line_code','contacts_code'];
            $info=WmsShop::where($where)->orderBy('create_time', 'desc')->select($select)->get();

            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "group_name"=>'所属公司',
                    "company_name"=>'业务往来公司',
                    "name"=>'门店名称',
                    "line_code"=>'线路号',
                    "contacts"=>'联系人',
                    "tel"=>'联系电话',
                    "contacts_code"=>'收货人编号',
                    "address"=>'门店地址',
                    "use_flag"=>'状态',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['company_name']=$v->company_name;
                    $list['group_name']=$v->group_name;
                    $list['name']=$v->name;
                    $list['line_code']=$v->line_code;
                    $list['contacts']=$v->contacts;
                    $list['tel']=$v->tel;
                    $list['contacts_code']=$v->contacts_code;
                    $list['address']=$v->address;

                    if($v->use_flag == 'Y'){
                        $list['use_flag']='使用中';
                    }else{
                        $list['use_flag']='禁止使用';
                    }

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

    /***    门店详情     /wms/shop/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='wms_shop';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'external_id','name','contacts','address','tel','company_name','line_code','contacts_code'];

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

    /***    获取包装      /wms/shop/getShop
     */
    public function getShop(Request $request){
        /** 接收数据*/
		//$input            =$request->all();
        //$group_code       =$request->input('group_code');
		$company_id       =$request->input('company_id');
        /*** 虚拟数据**/
        //$group_code='ware_202006012159456407842832';
		//dump($group_code);dd($company_id);
        $where=[
            ['delete_flag','=','Y'],
            //['use_flag','=','Y'],
            //['group_code','=',$group_code],
			['company_id','=',$company_id],
        ];

        //dd($where);
        $data['info']=WmsShop::where($where)->select('self_id','name','external_id')->get();
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;

    }

}
?>
