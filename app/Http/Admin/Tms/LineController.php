<?php
namespace App\Http\Admin\Tms;
use App\Http\Controllers\FileController as File;
use App\Models\Tms\CarCount;
use App\Models\Tms\CarDanger;
use App\Models\Tms\TmsLine;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Tms\TmsCar;
use App\Models\Group\SystemGroup;
use App\Models\Tms\TmsCarType;
use App\Models\Tms\TmsGroup;

class LineController extends CommonController{

    /***    专线      /tms/line/lineList
     */
    public function  lineList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/车辆导入.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    专线分页      /tms/line/linePage
     */
    public function linePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $send_name     =$request->input('send_name');
        $gather_name   =$request->input('gather_name');
        $pay_type       =$request->input('pay_type');
        
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'send_name','value'=>$send_name],
            ['type'=>'like','name'=>'gather_name','value'=>$gather_name],
            ['type'=>'=','name'=>'pay_type','value'=>$pay_type],
           
        ];

        $where=get_list_where($search);

        $select=['self_id','send_id','send_name','gather_id','gather_name','delete_flag','create_time','kilo_num','num','group_code','group_name','use_flag','car_num','line_list','pay_type','once_price','base_pay','car_number'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsLine::where($where)->count(); //总的数据量
                $data['items']=TmsLine::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsLine::where($where)->count(); //总的数据量
                $data['items']=TmsLine::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsLine::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsLine::where($where)->whereIn('group_code',$group_info['group_code'])
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



    /***    新建车辆      /tms/line/createLine
     */
    public function createLine(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';

        $select = ['self_id','send_id','send_name','gather_id','gather_name','delete_flag','create_time','kilo_num','num','group_code','group_name','use_flag','car_num','line_list','pay_type','once_price','base_pay','car_number'];

        $data['info']= TmsLine::where('self_id',$self_id)->select($select)->first();

        if ($data['info']){

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;


    }


    /***    新建车辆数据提交      /tms/line/addLine
     */
    public function addLine(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_car';
        $operationing->access_cause     ='创建/修改车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $send_id            =$request->input('send_id');
        $send_name          =$request->input('send_name');//装
        $gather_id          =$request->input('gather_id');
        $gather_name        =$request->input('gather_name');//卸
        $kilo_num           =$request->input('kilo_num');//里程
        $num                =$request->input('num');//编号
        $car_num            =$request->input('car_num');//车数
        $pay_type           =$request->input('pay_type');//结算方式
        $once_price         =$request->input('once_price');//每车奖励
        $base_pay           =$request->input('base_pay');//基本提成
        $car_number         =$request->input('car_number');//车牌号

        if($pay_type == 'B'){
            $rules=[
               'car_number'=>'required',
            ];
            $message=[
               'car_number.required'=>'请填写装车点',
            ];
        }else{
            $rules=[
               'send_name'=>'required',
               'gather_name'=>'required',
            ];
            $message=[
               'send_name.required'=>'请填写装车点',
               'gather_name.required'=>'请填写卸车点',
            ];
        }
        

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }
            if($self_id){

            }else{
                if($pay_type == 'A'){
                      $old_line = TmsLine::where('gather_name',$gather_name)->where('send_name',$send_name)->first();
            if($old_line){
                $msg['code'] = 301;
                $msg['msg'] = '该线路已存在！';
                return $msg;
            }
                }
              
            }
            

            $data['send_id']          =$send_id;
            $data['send_name']        =$send_name;
            $data['gather_id']        =$gather_id;
            $data['gather_name']      =$gather_name;
            $data['kilo_num']         =$kilo_num;
            $data['num']              =$num;
            $data['car_num']          =$car_num;
            $data['car_number']       =$car_number;
            $data['pay_type']         =$pay_type;
            $data['once_price']       =$once_price;
            $data['base_pay']         =$base_pay;
            $data['line_list']        =$send_name.','.$gather_name;

            $wheres['self_id'] = $self_id;
            $old_info=TmsLine::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsLine::where($wheres)->update($data);

                $operationing->access_cause='修改专线';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('line_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=TmsLine::insert($data);
                $operationing->access_cause='新建专线';
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



    /***    车辆禁用/启用      /tms/line/lineUseFlag
     */
    public function lineUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_line';
        $medol_name='TmsLine';
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

    /***    车辆删除      /tms/line/lineDelFlag
     */
    public function lineDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_line';
        $medol_name='TmsLine';
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

    /***    拿去车辆数据     /tms/line/getLine
     */
    public function  getLine(Request $request){
        $group_code=$request->input('group_code');

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
        ];

        $where=get_list_where($search);
        $select = ['self_id','send_id','send_name','gather_id','gather_name','delete_flag','create_time','kilo_num','num','group_code','group_name','use_flag'];
        $data['info']=TmsLine::where($where)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    车辆导入     /tms/car/import
     */
    public function import(Request $request){
        $table_name         ='tms_car';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');

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
                '车牌号' =>['Y','Y','10','car_number'],
                '车型' =>['Y','Y','20','car_type'],
                '车架号' =>['N','Y','64','carframe_num'],
                '罐体介质' =>['N','Y','16','crock_medium'],
                '罐体容积' =>['N','Y','64','volume'],
                '罐检到期日期' =>['N','Y','64','tank_validity'],
                '核载吨位' =>['N','Y','64','weight'],
                '行驶证到期日期' =>['Y','Y','64','license_date'],
                '运输证到期日期' =>['Y','Y','64','medallion_date'],
                '保险' =>['N','Y','64','insure'],
                '保险金额' =>['N','Y','64','insure_price'],
                '交强险购买时间' =>['N','Y','64','compulsory'],
                '交强险到期时间' =>['N','Y','64','compulsory_end'],
                '商业险购买时间' =>['N','Y','64','commercial'],
                '商业险到期时间' =>['N','Y','64','commercial_end'],
                '承运险购买时间' =>['N','Y','64','carrier'],
                '承运险到期时间' =>['N','Y','64','carrier_end'],
                '备注' =>['N','Y','64','remark'],
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
            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code],
            ];

            $info= SystemGroup::where($where_check)->select('self_id','group_code','group_name')->first();
            if(empty($info)){
                $msg['code'] = 305;
                $msg['msg'] = '所属公司不存在';
                return $msg;
            }

            // dd($info);

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                if (!check_carnumber($v['car_number'])) {
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号错误！".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $where = [
                    ['delete_flag','=','Y'],
                    ['group_code','=',$info->group_code],
                    ['car_number','=',$v['car_number']]
                ];

                $is_car_info = TmsCar::where($where)->value('group_code');

                if($is_car_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                if (!in_array($v['insure'],['有','无'])) {
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆属性：有或无！".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $where_car_type = [
                    ['delete_flag','=','Y'],
                    ['parame_name','=',$v['car_type']],
                    ['group_code','=',$group_code]
                ];
                $car_type = TmsCarType::where($where_car_type)->select('self_id','parame_name')->first();
                // dd($car_type);
                if(!$car_type){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车辆类型不存在！".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){

                    $list['self_id']            = generate_id('car_');
                    $list['car_number']         = $v['car_number'];
                    $list['car_type']           = $car_type->self_id;
                    $list['carframe_num']       = $v['carframe_num'];
                    $list['crock_medium']       = $v['crock_medium'];
                    if ($v['license_date']){
                        $list['license_date']       = gmdate('Y-m-d H:i:s', ($v['license_date'] - 25569) * 3600 * 24);
                    }else{
                        $list['license_date']       = '';
                    }
                    if ($v['medallion_date']){
                        $list['medallion_date']     = gmdate('Y-m-d H:i:s', ($v['medallion_date'] - 25569) * 3600 * 24);
                    }else{
                        $list['medallion_date']       = '';
                    }
                    $list['remark']             = $v['remark'];
                    $list['weight']             = $v['weight'];
                    $list['volume']             = $v['volume'];
                    $list['insure']             = $v['insure'];
                    $list['insure_price']       = $v['insure_price'];
                    if ($v['compulsory']){
                        $list['compulsory']     = gmdate('Y-m-d H:i:s', ($v['compulsory'] - 25569) * 3600 * 24);
                    }else{
                        $list['compulsory']       = null;
                    }
                    if ($v['commercial']){
                        $list['commercial']     = gmdate('Y-m-d H:i:s', ($v['commercial'] - 25569) * 3600 * 24);
                    }else{
                        $list['commercial']       = null;
                    }
                    if ($v['carrier']){
                        $list['carrier']     = gmdate('Y-m-d H:i:s', ($v['carrier'] - 25569) * 3600 * 24);
                    }else{
                        $list['carrier']       = null;
                    }
                    if ($v['compulsory_end']){
                        $list['compulsory_end']     = gmdate('Y-m-d H:i:s', ($v['compulsory_end'] - 25569) * 3600 * 24);
                    }else{
                        $list['compulsory_end']       = null;
                    }
                    if ($v['commercial_end']){
                        $list['commercial_end']     = gmdate('Y-m-d H:i:s', ($v['commercial_end'] - 25569) * 3600 * 24);
                    }else{
                        $list['commercial_end']       = null;
                    }
                    if ($v['carrier_end']){
                        $list['carrier_end']     = gmdate('Y-m-d H:i:s', ($v['carrier_end'] - 25569) * 3600 * 24);
                    }else{
                        $list['carrier_end']       = null;
                    }
                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        = $list['update_time']=$now_time;
                    $list['file_id']            = $file_id;

                    $datalist[]=$list;
                }

                $a++;
            }
            $operationing->old_info=null;
            $operationing->new_info=(object)$datalist;

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= TmsCar::insert($datalist);

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

    /***    车辆详情     /tms/car/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='tms_car';
        $select=['self_id','send_id','send_name','gather_id','gather_name','delete_flag','create_time','kilo_num','num','group_code','group_name','use_flag','car_num','line_list','pay_type','once_price','base_pay','car_number'];


        $info= TmsLine::where('self_id',$self_id)->select($select)->first();

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

    /***    车辆导出     /tms/car/execl
     */
    public function execl(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
//        $group_code  =$input['group_code']   ='group_202012251449437824125582';
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

            $select=['self_id','car_number','car_type','carframe_num','crock_medium','crock_medium','license_date','medallion_date','remark','weight','volume','insure','tank_validity',
                'license','medallion','payment_state','insure_price','create_time','update_time','use_flag','delete_flag','compulsory_end','commercial_end','carrier_end','compulsory','commercial','carrier'];
            $select1 = ['self_id','parame_name'];
            $info=TmsCar::with(['tmsCarType' => function($query) use($select1){
                $query->select($select1);
            }])->where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "car_number"=>'车牌号',
                    "car_type"=>'车型',
                    "carframe_num"=>'车架号',
                    "crock_medium"=>'罐体介质',
                    "volume"=>'罐体容积',
                    "tank_validity"=>'罐检到期日期',
                    "weight"=>'核载吨位',
                    "license_date"=>'行驶证到期日期',
                    "medallion_date"=>'运输证到期日期',
                    "insure"=>'保险',
                    "insure_price"=>'保险金额',
                    "compulsory"=>'交强险购买时间',
                    "compulsory_end"=>'交强险到期时间',
                    "commercial"=>'商业险购买时间',
                    "commercial_end"=>'商业险到期时间',
                    "carrier"=>'承运险购买时间',
                    "carrier_end"=>'承运险到期时间',
                    "remark"=>'备注'
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];


                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['car_number']         = $v['car_number'];
                    $list['car_type']           = $v->tmsCarType->parame_name;
                    $list['carframe_num']       = $v['carframe_num'];
                    $list['crock_medium']       = $v['crock_medium'];
                    $list['volume']             = $v['volume'];
                    $list['tank_validity']      = $v['tank_validity'];
                    $list['weight']             = $v['weight'];
                    $list['license_date']       = $v['license_date'] ;
                    $list['medallion_date']     = $v['medallion_date'];
                    $list['insure']             = $v['insure'];
                    $list['insure_price']       = $v['insure_price'];
                    $list['compulsory']         = $v['compulsory'];
                    $list['compulsory_end']     = $v['compulsory_end'];
                    $list['commercial']         = $v['commercial'];
                    $list['commercial_end']     = $v['commercial_end'];
                    $list['carrier']            = $v['carrier'];
                    $list['carrier_end']        = $v['carrier_end'];
                    $list['remark']             = $v['remark'];
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

    /***    车辆分页      /tms/car/countPage
     */
    public function countPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??5;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_number');
        $car_id         =$request->input('car_id');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'car_id','value'=>$car_id],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<','name'=>'create_time','value'=>$end_time],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','month','month_kilo','month_fat','create_time','update_time','use_flag','delete_flag','group_code'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=CarCount::where($where)->count(); //总的数据量
                $data['items']=CarCount::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=CarCount::where($where)->count(); //总的数据量
                $data['items']=CarCount::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=CarCount::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=CarCount::where($where)->whereIn('group_code',$group_info['group_code'])
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

    /***    新建车辆数据提交      /tms/car/addCount
     */
    public function addCount(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_car';
        $operationing->access_cause     ='创建/修改车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $car_number         =$request->input('car_number');//车牌号
        $car_id             =$request->input('car_id');//
        $month              =$request->input('month');//月份
        $month_kilo         =$request->input('month_kilo');//月公里数
        $month_fat          =$request->input('month_fat');// 月油耗

        $rules=[
            'car_number'=>'required',
        ];
        $message=[
            'car_number.required'=>'车牌号必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $data['car_number']      =$car_number;
            $data['car_id']          =$car_id;
            $data['month']           =$month;
            $data['month_kilo']      =$month_kilo;
            $data['month_fat']       =$month_fat;

            $wheres['self_id'] = $self_id;
            $old_info=CarCount::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=CarCount::where($wheres)->update($data);

                $operationing->access_cause='修改车辆';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            = generate_id('count_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['create_time']        = $data['update_time']=$now_time;

                $id=CarCount::insert($data);
                $operationing->access_cause='新建车辆';
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

    /***    车辆分页      /tms/car/dangerPage
     */
    public function dangerPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??5;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_number');
        $car_id         =$request->input('car_id');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'car_id','value'=>$car_id],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<','name'=>'create_time','value'=>$end_time],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','arise_time','price','create_time','update_time','use_flag','delete_flag','group_code'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=CarDanger::where($where)->count(); //总的数据量
                $data['items']=CarDanger::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=CarDanger::where($where)->count(); //总的数据量
                $data['items']=CarDanger::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=CarDanger::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=CarDanger::where($where)->whereIn('group_code',$group_info['group_code'])
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

    /***    新建车辆数据提交      /tms/car/addDanger
     */
    public function addDanger(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_car';
        $operationing->access_cause     ='创建/修改车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $car_number         =$request->input('car_number');//车牌号
        $car_id             =$request->input('car_id');//车型
        $arise_time         =$request->input('arise_time');//出险时间
        $price              =$request->input('price');//赔付金额
        $payment_state      =$request->input('payment_state');//理赔状态


        $rules=[
            'car_number'=>'required',
        ];
        $message=[
            'car_number.required'=>'车牌号必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $data['car_number']      =$car_number;
            $data['car_id']          =$car_id;
            $data['arise_time']      =$arise_time;
            $data['price']           =$price;
            $data['payment_state']   =$payment_state;
            $wheres['self_id'] = $self_id;
            $old_info=CarDanger::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=CarDanger::where($wheres)->update($data);

                $operationing->access_cause='修改车辆';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            = generate_id('danger_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['create_time']        = $data['update_time']=$now_time;

                $id=CarDanger::insert($data);
                $operationing->access_cause='新建车辆';
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

    /**
     * 查询车辆详情
     * */
    public function getCarView(Request $request){
        $group_code=$request->input('group_code');
        $car_number=$request->input('car_number');

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
        ];

        $where=get_list_where($search);
        $select = ['self_id','car_number','car_type','carframe_num','crock_medium','crock_medium','license_date','medallion_date','remark','weight','volume','insure','tank_validity',
            'license','medallion','payment_state','insure_price'];
        $select1 = ['self_id','car_number','car_id','add_time','ic_number','number','price','total_money','remark','create_time','update_time','delete_flag','group_code',
            'create_user_id','create_user_name'];
        $select2 = ['self_id','car_number','car_id','brand','kilo_num','service_time','reason','service_price','service_partne','service_partne','driver_name','contact','operator',
            'remark','create_time','update_time','use_flag','delete_flag','group_code','fittings','warranty_time','service_view'];
        $select3 = ['self_id','car_number','car_id','road_time','etc_number','road_price','address','create_time','update_time','delete_flag','group_code',
            'create_user_id','create_user_name'];
        $select4 = ['self_id','car_id','car_number','month','month_kilo','month_fat','create_time','update_time','use_flag','delete_flag','group_code'];
        $select5=['self_id','car_id','car_number','arise_time','price','create_time','update_time','use_flag','delete_flag','group_code'];
        $select6=[''];
        $select7=[''];

        $data['info']=TmsCar::with(['TmsCarType' => function($query) use($select7){
            $query->select($select7);
        }])
            ->with(['TmsCarType' => function($query) use($select7){
                $query->select($select7);
            }])
            ->where($where)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



}
?>
