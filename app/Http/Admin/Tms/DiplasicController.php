<?php
namespace App\Http\Admin\Tms;
use App\Models\Tms\TmsDiplasic;
use App\Models\Tms\TmsTrye;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsCarType;


class DiplasicController extends CommonController{

    /***    车辆类型列表头部      /tms/diplasic/diplasicList
     */
    public function  diplasicList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆类型';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/TMS车辆类型导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    车辆类型分页      /tms/diplasic/diplasicPage
     */
    public function diplasicPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_number');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','production_date','input_date','service','tips','service_now','service_plan','create_user_name','create_time','group_code','use_flag'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsDiplasic::where($where)->count(); //总的数据量
                $data['items']=TmsDiplasic::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsDiplasic::where($where)->count(); //总的数据量
                $data['items']=TmsDiplasic::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsDiplasic::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsDiplasic::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {

            $v->button_info=$button_info;

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    新建车辆类型    /tms/diplasic/createDiplasic
     */
    public function createDiplasic(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','car_id','car_number','production_date','input_date','service','tips','service_now','service_plan','create_user_name','create_time','group_code','use_flag'];
        $data['info']=TmsDiplasic::where($where)->select($select)->first();
        if($data['info']){

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    车辆类型数据提交      /tms/diplasic/addDiplasic
     */
    public function addDiplasic(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_trye';

        $operationing->access_cause     ='创建/修改车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $car_id             =$request->input('car_id');//车牌号
        $car_number         =$request->input('car_number');//车牌号
        $production_date    =$request->input('production_date');//出厂日期
        $input_date         =$request->input('input_date');//投入日期
        $service            =$request->input('service');//维护周期
        $service_now        =$request->input('service_now');//维护日期
        $service_plan       =$request->input('service_plan');//计划维护日期
        $tips               =$request->input('tips');//提示

        $rules=[
            'car_number'=>'required',
        ];
        $message=[
            'car_number.required'=>'请填写车牌号',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $data['car_id']               =$car_id;
            $data['car_number']           =$car_number;
            $data['production_date']      =$production_date;
            $data['input_date']           =$input_date;
            $data['service']              =$service;
            $data['service_now']          =$service_now;
            $data['service_plan']         =$service_plan;
            $data['tips']                 =$tips;
            $wheres['self_id']            = $self_id;
            $old_info=TmsDiplasic::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsDiplasic::where($wheres)->update($data);
                $operationing->access_cause='修改二级维护';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('type_');
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $user_info->group_code;
                $data['group_name']         = $user_info->group_name;
                $id=TmsDiplasic::insert($data);
                $operationing->access_cause='新建二级维护';
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

    /***    车辆类型禁用/启用      /tms/diplasic/diplasicUseFlag
     */
    public function diplasicUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_diplasic';
        $medol_name='TmsDiplasic';
        $self_id=$request->input('self_id');
        $flag='useFlag';
//        $self_id='car_202012242220439016797353';

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

    /***    二次维护删除     /tms/diplasic/diplasicDelFlag
     */
    public function diplasicDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_diplasic';
        $medol_name='TmsDiplasic';
        $self_id=$request->input('self_id');
        $flag='delFlag';
//        $self_id='car_202012242220439016797353';

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


    /***    车辆类型导入     /tms/diplasic/import
     */
    public function import(Request $request){
        $table_name         ='tms_car_type';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建车辆类型';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         ='1234';
        $file_id            =$request->input('file_id');
        //dd($input);
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/import/TMS车辆类型导入文件范本.xlsx";
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
            if (!file_exists($importurl)) {
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }

            $res = Excel::toArray((new Import),$importurl);
            //dump($res);
            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '车牌号码' =>['Y','N','64','parame_name'],
                '车辆出厂日期' =>['Y','N','6','allvolume'],
                '车辆投入运行日期' =>['Y','N','10','allweight'],
                '车辆维护周期' =>['Y','N','64','dimensions'],
                '出车最低价(元)' =>['Y','Y','10','low_price'],
                '价格(元/公里)' =>['Y','Y','10','costkm_price'],
                '市内包车价(元)' =>['N','Y','10','chartered_price'],
                '装货费' =>['Y','Y','10','pickup_price'],
                '卸货费' =>['Y','Y','10','unload_price'],
                '多点提货费' =>['N','Y','10','morepickup_price'],
            ];
            $ret=arr_check($shuzu,$info_check);

            // dump($ret);
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

            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['parame_name','=',$v['parame_name']],
                ];

                $cartype = TmsCarType::where($where)->value('self_id');

                if($cartype){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆类型已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('type_');
                    $list['parame_name']        = $v['parame_name'];
                    $list['allweight']          = $v['allweight'];
                    $list['allvolume']          = $v['allvolume'];
                    $list['dimensions']         = $v['dimensions'];
                    $list['costkm_price']       = $v['costkm_price'] ? ($v['costkm_price'] - 0 * 100) : 0;
                    $list['low_price']          = $v['low_price'] ? ($v['low_price'] - 0 * 100) : 0;
                    $list['chartered_price']    = $v['chartered_price'] ? ($v['chartered_price'] - 0 * 100) : 0;
                    $list['pickup_price']       = $v['pickup_price'] ? ($v['pickup_price'] - 0 * 100) : 0;
                    $list['unload_price']       = $v['unload_price'] ? ($v['unload_price'] - 0 * 100) : 0;
                    $list['morepickup_price']   = $v['morepickup_price'] ? ($v['morepickup_price'] - 0 * 100) : 0;
                    $list['group_code']         = $group_code;
                    $list['group_name']         = '平台方';
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }

                $a++;
            }


            $operationing->new_info=$datalist;

            //dump($operationing);

            // dd($datalist);

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= TmsCarType::insert($datalist);




            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=307;
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

    /***    二级维护详情     /tms/diplasic/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='tms_diplasic';
        $select=['self_id','car_id','car_number','production_date','input_date','service','tips','service_now','service_plan','create_user_name','create_time','group_code','use_flag'];
        // $self_id='type_202012290957581187464100';
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
