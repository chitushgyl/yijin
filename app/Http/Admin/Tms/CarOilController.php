<?php
namespace App\Http\Admin\Tms;
use App\Http\Controllers\FileController as File;
use App\Models\Tms\CarCount;
use App\Models\Tms\CarDanger;
use App\Models\Tms\CarOil;
use App\Models\Tms\TmsMoney;
use App\Models\Tms\TmsOil;
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

class CarOilController extends CommonController{

    /***    加油记录列表头部      /tms/carOil/carList
     */
    public function  carList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/加油明细导入.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    加油记录分页      /tms/carOil/carPage
     */
    public function carPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $car_number     =$request->input('car_number');
        $ic_number     =$request->input('ic_number');
        $start_time     =$request->input('start_time');
        $end_time     =$request->input('end_time');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'ic_number','value'=>$ic_number],
            ['type'=>'>=','name'=>'add_time','value'=>$start_time],
            ['type'=>'<=','name'=>'add_time','value'=>$end_time],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_number','car_id','add_time','ic_number','number','price','total_money','remark','create_time','update_time','delete_flag','group_code',
            'create_user_id','create_user_name','use_flag'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=CarOil::where($where)->count(); //总的数据量
                $data['items']=CarOil::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['price']=CarOil::where($where)->sum('total_money');
                $data['number']=CarOil::where($where)->sum('number');
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=CarOil::where($where)->count(); //总的数据量
                $data['items']=CarOil::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['price']=CarOil::where($where)->sum('total_money');
                $data['number']=CarOil::where($where)->sum('number');
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=CarOil::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=CarOil::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')->orderBy('self_id','desc')
                    ->select($select)->get();
                $data['price']=CarOil::where($where)->whereIn('group_code',$group_info['group_code'])->sum('total_money');
                $data['number']=CarOil::where($where)->whereIn('group_code',$group_info['group_code'])->sum('number');
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



    /***    新建车辆      /tms/carOil/createCar
     */
    public function createCar(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select = ['self_id','car_number','car_id','add_time','ic_number','number','price','total_money','remark','create_time','update_time','delete_flag','group_code',
            'create_user_id','create_user_name'];
        $data['info']=CarOil::where($where)->select($select)->first();
        $total_num = TmsOil::where('use_flag','Y')->where('delete_flag','Y')->sum('num');
        $total_price = TmsOil::where('use_flag','Y')->where('delete_flag','Y')->sum('total_price');
        if ($total_num){
            $data['price'] = round($total_price/$total_num,2);
        }

        if ($data['info']){

        }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;


    }


    /***    新建车辆加油数据提交      /tms/carOil/addCar
     */
    public function addCar(Request $request){
        $money_type     =array_column(config('tms.money_type'),'name','key');
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
        $add_time           =$request->input('add_time');//加油时间
        $ic_number          =$request->input('ic_number');//IC卡号
        $number             =$request->input('number');// 加油升数
        $price              =$request->input('price');//油单价
        $total_money        =$request->input('total_money');//加油总价
        $remark             =$request->input('remark');//备注

        $rules=[
            'car_number'=>'required',
        ];
        $message=[
            'car_number.required'=>'车牌号必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $id = generate_id('oil_');
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $total_number = TmsOil::where('group_code',$group_code)->where('use_flag','Y')->where('delete_flag','Y')->where('state','Y')->sum('num');
            $total_out_number = CarOil::where('group_code',$group_code)->where('use_flag','Y')->where('delete_flag','Y')->sum('number');
            $total_jie_number = $total_number -$total_out_number;
            if ($total_jie_number<$number){
                $msg['code'] = 302;
                $msg['msg'] = '库存不足';
                return $msg;
            }

            $data['car_number']        =$car_number;
            $data['car_id']            =$car_id;
            $data['add_time']          =$add_time;
            $data['ic_number']         =$ic_number;
            $data['number']            =$number;
            $data['price']             =$price;
            $data['total_money']       =$total_money;
            $data['remark']            =$remark;

            /**保存费用**/
            $money['pay_type']           = 'fuel';
            $money['money']              = $total_money;
            $money['pay_state']          = 'Y';
            $money['car_id']             = $car_id;
            $money['car_number']         = $car_number;
            if($self_id){
                $money['order_id']         = $self_id;
            }else{
                $money['order_id']         = $id;
            }
            $money['process_state']      = 'Y';
            $money['type_state']         = 'out';

            $wheres['self_id'] = $self_id;
            $old_info=CarOil::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=CarOil::where($wheres)->update($data);
                TmsMoney::where('order_id',$self_id)->update($money);
                $operationing->access_cause='修改加油记录';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =$id;
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $money['self_id']            = generate_id('money_');
                $money['group_code']         = $group_code;
                $money['group_name']         = $group_name;
                $money['create_user_id']     = $user_info->admin_id;
                $money['create_user_name']   = $user_info->name;
                $money['create_time']        =$money['update_time']=$add_time;

                $id=CarOil::insert($data);
                TmsMoney::insert($money);
                $operationing->access_cause='新建加油记录';
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



    /***    车辆禁用/启用      /tms/carOil/carUseFlag
     */
    public function carUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='car_oil';
        $medol_name='CarOil';
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

    /***    车辆删除      /tms/carOil/carDelFlag
     */
    public function carDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='car_oil';
        $medol_name='CarOil';
        $self_id=$request->input('self_id');
        $flag='delFlag';
//        $self_id='car_202012242220439016797353';
        $old_info = CarOil::whereIn('self_id',explode(',',$self_id))->select('use_flag','self_id','delete_flag','group_code')->get();
        $data['delete_flag']='N';
        $data['update_time']=$now_time;
//        dd($old_info);
        $id=CarOil::whereIn('self_id',explode(',',$self_id))->update($data);
        if ($id){
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
        }else{
            $msg['code']=301;
            $msg['msg']="删除失败";

        }
        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$data;
        $operationing->operation_type=$flag;

        $msg['code']=$msg['code'];
        $msg['msg']=$msg['msg'];
        $msg['data']=(object)$data;

        return $msg;
    }

    /***    拿去车辆数据     /tms/car/getCar
     */
    public function  getCar(Request $request){
        $group_code=$request->input('group_code');
        $car_number=$request->input('car_number');
//        $input['group_code'] =  $group_code = '1234';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
        ];

        $where=get_list_where($search);
        $select = ['self_id','car_number'];
        $data['info']=TmsCar::where($where)->select($select)->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    加油记录导入     /tms/carOil/import
     */
    public function import(Request $request){
        $table_name         ='car_oil';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入加油记录';
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

        /****虚拟数据
        $input['importurl']     =$importurl="uploads/import/TMS车辆导入文件范本.xlsx";
        $input['group_code']       =$group_code='1234';
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
            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }
//            dd($info_check);
            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                'IC卡卡号' =>['Y','Y','10','ic_number'],
                '车牌号' =>['N','Y','20','car_number'],
                '会员名称' =>['Y','Y','20','car_number'],
//                '总价' =>['N','Y','30','total_money'],
                '加注量' =>['N','Y','30','number'],
                '单价' =>['N','Y','30','price'],
                '交易时间' =>['Y','Y','50','add_time'],
                '地址' =>['N','Y','200','address'],
            ];
            $ret=arr_check($shuzu,$info_check);
//            dd($ret);
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

//             dd($info);

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;
            $moneylist =[];
            $number = 0;
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
//                if (!check_carnumber($v['car_number'])) {
//                    if($abcd<$errorNum){
//                        $strs .= '数据中的第'.$a."行车牌号错误！".'</br>';
//                        $cando='N';
//                        $abcd++;
//                    }
//                }

                $list=[];
                $money=[];
                if($cando =='Y'){
                    $total_num = TmsOil::where('use_flag','Y')->where('delete_flag','Y')->where('group_code',$group_code)->sum('num');
                    $total_price = TmsOil::where('use_flag','Y')->where('delete_flag','Y')->where('group_code',$group_code)->sum('total_price');
                    if ($total_num){
                        $price = round($total_price/$total_num,2);
                    }
                    $list['self_id']            = generate_id('oil_');
                    $list['car_number']         = $v['car_number'];
                    $list['ic_number']          = $v['ic_number'];
                    $list['number']             = $v['number'];
                    $list['price']              = $price;
                    $list['total_money']        = $price*$v['number'];
                    $list['add_time']           = $v['add_time'];
                    $list['address']            = $v['address'];

                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;

                    $datalist[]=$list;

                     $money['pay_type']           = 'fuel';
                     $money['money']              = $price*$v['number'];
                     $money['pay_state']          = 'Y';
//                     $money['car_id']             = $car_id;
                     $money['car_number']         = $v['car_number'];
                     $money['process_state']      = 'Y';
                     $money['type_state']         = 'out';
                     $money['self_id']            = generate_id('money_');
                     $money['group_code']         = $info->group_code;
                     $money['group_name']         = $info->group_name;
                     $money['create_user_id']     = $user_info->admin_id;
                     $money['create_user_name']   = $user_info->name;
                     $money['create_time']        =$money['update_time']=$v['add_time'];

                    $moneylist[]=$money;

                    $number += $v['number'];

                }

                $a++;
            }
            $total_number = TmsOil::where('group_code',$info->group_code)->where('use_flag','Y')->where('delete_flag','Y')->where('state','Y')->sum('num');
            $total_out_number = CarOil::where('group_code',$info->group_code)->where('use_flag','Y')->where('delete_flag','Y')->sum('number');
            $total_jie_number = $total_number -$total_out_number;
            if ($total_jie_number<$number){
                $msg['code'] = 302;
                $msg['msg'] = '库存不足';
                return $msg;
            }
            $operationing->old_info=null;
            $operationing->new_info=(object)$datalist;

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= CarOil::insert($datalist);
            TmsMoney::insert($moneylist);
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

    /***    车辆详情     /tms/carOil/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='tms_car';
        $select=['self_id','car_number','car_id','add_time','ic_number','number','price','total_money','remark','create_time','update_time','delete_flag','group_code',
            'create_user_id','create_user_name'];

        // $self_id='car_202012291341297595587871';
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

    /***    车辆导出     /tms/car/execl
     */
    public function execl(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
//        $group_code  =$input['group_code']   ='group_202012251449437824125582';

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

            $select=['self_id','car_number','car_possess','weight','volam','control','car_type_name','contacts','tel','remark'];
            $info=TmsCar::where($where)->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "car_number"=>'车牌号',
                    "car_type_name"=>'车辆类型',
                    "car_possess"=>'属性',
                    "control"=>'温控',
                    "weight"=>'承重(kg)',
                    "volam"=>'体积(立方)',
                    "contacts"=>'联系人',
                    "tel"=>'联系电话',
                    "remark"=>'备注'
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                $tms_control_type = array_column(config('tms.tms_control_type'),'name','key');
                $tms_car_possess_type = array_column(config('tms.tms_car_possess_type'),'name','key');

                foreach ($info as $k=>$v){
                    $list=[];

                    $list['id']=($k+1);
                    $list['car_number']=$v->car_number;
                    $list['car_type_name']=$v->car_type_name;
                    $control = '';
                    $car_possess = '';
                    if (!empty($tms_control_type[$v['control']])) {
                        $control = $tms_control_type[$v['control']];
                    }

                    if (!empty($tms_car_possess_type[$v['car_possess']])) {
                        $car_possess = $tms_car_possess_type[$v['car_possess']];
                    }
                    $list['car_possess']=$car_possess;
                    $list['control']    =$control;
                    $list['weight']     =$v->weight;
                    $list['volam']      =$v->volam;
                    $list['contacts']   =$v->contacts;
                    $list['tel']        =$v->tel;
                    $list['remark']     =$v->remark;

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



    /**
     *          /tms/carOil/oilList
     * */
    public function  oilList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='车辆';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/TMS车辆导入文件范本.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***
     * 入库列表  /tms/carOil/oilPage
     **/
    public function oilPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $start_time     =$request->input('start_time');
        $end_time       =$request->input('end_time');
        $suppliere       =$request->input('suppliere');
        $state          =$request->input('state');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'suppliere','value'=>$suppliere],
            ['type'=>'>=','name'=>'create_time','value'=>$start_time],
            ['type'=>'<=','name'=>'create_time','value'=>$end_time],
            ['type'=>'=','name'=>'state','value'=>$state],
        ];

        $where=get_list_where($search);

        $select=['self_id','num','price','total_price','enter_time','create_time','update_time','delete_flag','group_code','suppliere','operator',
            'create_user_id','create_user_name','use_flag','state'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsOil::where($where)->count(); //总的数据量
                $data['items']=TmsOil::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['num']=TmsOil::where($where)->sum('num');
                $data['price']=TmsOil::where($where)->sum('total_price');
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsOil::where($where)->count(); //总的数据量
                $data['items']=TmsOil::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['num']=TmsOil::where($where)->sum('num');
                $data['price']=TmsOil::where($where)->sum('total_price');
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsOil::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsOil::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['num']=TmsOil::where($where)->whereIn('group_code',$group_info['group_code'])->sum('num');
                $data['price']=TmsOil::where($where)->whereIn('group_code',$group_info['group_code'])->sum('total_price');
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

    /**
     * 油入库  /tms/carOil/addOil
     ***/
    public function addOil(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_oil';
        $operationing->access_cause     ='创建/修改入库';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $enter_time         =$request->input('enter_time');//入库日期
        $num                =$request->input('num');// 加油升数
        $price              =$request->input('price');//油单价
        $suppliere          =$request->input('suppliere');//供应商
        $operator           =$request->input('operator');//经手人
        $total_price        =$request->input('total_price');//加油总价


        $rules=[
            'num'=>'required',
            'price'=>'required',
        ];
        $message=[
            'num.required'=>'升数必须填写',
            'price.required'=>'单价必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {

            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            if(empty($group_name)){
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            $data['num']               =$num;
            $data['price']             =$price;
            $data['total_price']       =$total_price;
            $data['suppliere']         =$suppliere;
            $data['operator']          =$operator;
            $data['enter_time']        =$enter_time;

            $wheres['self_id'] = $self_id;
            $old_info=TmsOil::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsOil::where($wheres)->update($data);
                $operationing->access_cause='修改加油记录';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('oil_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=TmsOil::insert($data);

                $operationing->access_cause='新建加油记录';
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
     * 油库入库审核
     * */
    public function updateOilState(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_oil';

        $operationing->access_cause     ='创建/修改入库状态';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');

        $rules=[
            'self_id'=>'required',

        ];
        $message=[
            'self_id.required'=>'请选择审核条目！',
        ];
        $validator=Validator::make($input,$rules,$message);

        //操作的表
        if($validator->passes()){
            $wheres['self_id'] = $self_id;
            $old_info=TmsOil::whereIn('self_id',explode(',',$self_id))->get();
            foreach ($old_info as $k => $v){
                if($v->state == 'Y'){
                    $msg['code'] = 303;
                    $msg['msg'] = "入库已审核，不可修改！";
                    return $msg;
                }
            }


            $data['state'] = 'Y';
            $data['update_time']   = $now_time;
            $id = TmsOil::whereIn('self_id',explode(',',$self_id))->update($data);

            $operationing->access_cause='费用作废';
            $operationing->operation_type='create';
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
     * 油库库存
     * */
    public function depotList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    public function depotPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $user_info    = $request->get('user_info');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $warehouse_name      =$request->input('warehouse_name');
        $good_name           =$request->input('good_name');
        $start_time          =$request->input('start_time');
        $end_time            =$request->input('end_time');
        $listrows            =$num;
        $firstrow            =($page-1)*$listrows;

        if ($start_time) {
            $start_time = $start_time.' 00:00:00';
        }else{
            $msg['code']=300;
            $msg['msg']='请选择开始时间';
            return $msg;
        }
        if ($end_time) {
            $end_time = $end_time.' 23:59:59';
        }else{
            $msg['code']=300;
            $msg['msg']='请选择结束时间';
            return $msg;
        }
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'state','value'=>'Y'],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'>=','name'=>'enter_time','value'=>$start_time],
            ['type'=>'<=','name'=>'enter_time','value'=>$end_time],


        ];

        $where=get_list_where($search);

        $select=['self_id','num','price','total_price','enter_time','create_time','update_time','delete_flag','group_code','suppliere','operator',
            'create_user_id','create_user_name','use_flag','state'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsOil::where($where)->count(); //总的数据量
                $data['items']=TmsOil::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsOil::where($where)->count(); //总的数据量
                $data['items']=TmsOil::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsOil::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsOil::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }

        $res['initial_num'] = 0;
        $res['in_num'] = 0;
        $res['out_num'] = 0;
        $res['jie_num'] = 0;
        foreach ($data['items'] as $k=>$v) {
            //统计本期内入库总量
            $res['in_num'] += $v->num;
        }
        $res['initial_num'] = TmsOil::where('state','Y')->where('group_code',$group_code)->where('enter_time','<',$start_time)->sum('num');
        $res['out_num'] = CarOil::where('group_code',$group_code)->where('use_flag','Y')->where('delete_flag','Y')->sum('number');
        $res['jie_num'] = round(($res['initial_num'] + $res['in_num'] - $res['out_num']),2);
        $res['group_code'] = $user_info->group_code;
        $res['group_name'] = $user_info->group_name;
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$res;
//        dd($msg);
        return $msg;
    }



}
?>
