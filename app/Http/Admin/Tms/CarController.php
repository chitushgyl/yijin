<?php
namespace App\Http\Admin\Tms;
use App\Http\Controllers\FileController as File;
use App\Models\Tms\CarCount;
use App\Models\Tms\CarDanger;
use App\Models\Tms\CarOil;
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

class CarController extends CommonController{

    /***    车辆列表头部      /tms/car/carList
     */
    public function  carList(Request $request){
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

    /***    车辆分页      /tms/car/carPage
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
        $carframe_num   =$request->input('carframe_num');
        $car_type       =$request->input('car_type');
        $type           =$request->input('type');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'like','name'=>'carframe_num','value'=>$carframe_num],
            ['type'=>'=','name'=>'car_type','value'=>$car_type],
            ['type'=>'=','name'=>'type','value'=>$type],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_number','car_type','carframe_num','crock_medium','crock_medium','license_date','medallion_date','remark','weight','volume','insure','tank_validity',
            'license','medallion','payment_state','insure_price','create_time','update_time','use_flag','delete_flag','compulsory_end','commercial_end','carrier_end','compulsory','commercial','carrier',
        'medallion_num','curb_weight','all_weight','medallion_change','license_begin','production_date','scrap_date','business_scope','goods','medallion_change_end',
        'design_code','operation_date','tank_num','tank_type','car_color','car_brand','car_model','type',
            'car_made','engine_num','fuel_type','displacement_power','maker','turn_view','tread','trye_num','steel_plate','wheel_base','axles_num','outline',
            'car_size','car_user','gps_flag','bussiness_license','license_plate','engine_model','sgs_cert','sgs_date','inspect_annually'];
        $select1 = ['self_id','parame_name','type'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsCar::where($where)->count(); //总的数据量
                $data['items']=TmsCar::with(['TmsCarType' => function($query) use($select1,$type){
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsCar::where($where)->count(); //总的数据量
                $data['items']=TmsCar::with(['TmsCarType' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsCar::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsCar::with(['TmsCarType' => function($query) use($select1){
                    $query->select($select1);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
            $v->medallion     =img_for($v->medallion,'no_json');
            $v->license       =img_for($v->license,'no_json');
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }



    /***    新建车辆      /tms/car/createCar
     */
    public function createCar(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select = ['self_id','car_number','car_type','carframe_num','crock_medium','crock_medium','license_date','medallion_date','remark','weight','volume','insure','tank_validity',
            'license','medallion','payment_state','insure_price','compulsory','commercial','carrier','compulsory_end','commercial_end','carrier_end',
            'medallion_num','curb_weight','all_weight','medallion_change','license_begin','production_date','scrap_date','business_scope','goods','car_model',
            'design_code','operation_date','tank_num','tank_type','registr_cert','carrier_cert','tank_cert','medallion_change_end','car_color','car_brand',
            'car_made','engine_num','fuel_type','displacement_power','maker','turn_view','tread','trye_num','steel_plate','wheel_base','axles_num','outline',
            'car_size','car_user','gps_flag','bussiness_license','license_plate','engine_model','license_back','medallion_back','registr_date','medallion_begin',
            'license_start','compulsory_cert','commercial_cert','registr_cert_date','carrier_insurer','carrier_insurer_num','carrier_baoe','carrier_zrx','carrier_zr','carrier_good',
            'compulsory_insurer','compulsory_num','compulsory_sc','compulsory_yl','compulsory_property','commercial_insurer','commercial_num','commercial_tz','commercial_zr','commercial_driver','vessel_tax',
            'commercial_user','car_unit','type','goods_type','sgs_cert','sgs_date','pass_cert','nameplate','inspect_annually'];
        $select1 = ['self_id','parame_name','type'];
        $data['info']= TmsCar::with(['TmsCarType' => function($query) use($select1){
            $query->select($select1);
        }])->where('self_id',$self_id)->select($select)->first();

        if ($data['info']){
            $data['info']->medallion     =img_for($data['info']->medallion,'no_json');
            $data['info']->license       =img_for($data['info']->license,'no_json');
            $data['info']->registr_cert  =img_for($data['info']->registr_cert,'no_json');
            $data['info']->carrier_cert  =img_for($data['info']->carrier_cert,'no_json');
            $data['info']->tank_cert     =img_for($data['info']->tank_cert,'no_json');
            $data['info']->nameplate     =img_for($data['info']->nameplate,'no_json');
            $data['info']->pass_cert     =img_for($data['info']->pass_cert,'no_json');
            $data['info']->compulsory_cert     =img_for($data['info']->compulsory_cert,'no_json');
            $data['info']->commercial_cert     =img_for($data['info']->commercial_cert,'no_json');
            $data['info']->license_back     =img_for($data['info']->license_back,'no_json');
            $data['info']->medallion_back     =img_for($data['info']->medallion_back,'no_json');
            $data['info']->sgs_cert     =img_for($data['info']->sgs_cert,'no_json');
            $data['info']->type_show     = $data['info']->TmsCarType->parame_name;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;


    }


    /***    新建车辆数据提交      /tms/car/addCar
     */
    public function addCar(Request $request){
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
        $car_type           =$request->input('car_type');//车型
        $type               =$request->input('type');//车型
        $carframe_num       =$request->input('carframe_num');//车架号
        $crock_medium       =$request->input('crock_medium');//罐体介质
        $license_date       =$request->input('license_date');// 行驶证到期时间
        $medallion_date     =$request->input('medallion_date');//运输证到期时间
        $remark             =$request->input('remark');//备注
        $weight             =$request->input('weight');//核载吨位
        $volume             =$request->input('volume');//罐体容积
        $insure             =$request->input('insure');//保险
        $tank_validity      =$request->input('tank_validity');//罐检有效期
        $license            =$request->input('license');//行驶证
        $medallion          =$request->input('medallion');//运输证
        $insure_price       =$request->input('insure_price');//保险价格
        $compulsory         =$request->input('compulsory');//交强险
        $compulsory_end     =$request->input('compulsory_end');//交强险
        $commercial         =$request->input('commercial');//商业险
        $commercial_end     =$request->input('commercial_end');//商业险
        $carrier            =$request->input('carrier');//承运险
        $carrier_end        =$request->input('carrier_end');//承运险
        $medallion_num      =$request->input('medallion_num');//运输证号
        $curb_weight        =$request->input('curb_weight');//整备质量
        $all_weight         =$request->input('all_weight');//总质量
        $medallion_change   =$request->input('medallion_change');//运输证换证日期
        $license_begin      =$request->input('license_begin');//行驶证注册日期
        $production_date    =$request->input('production_date');//出厂日期
        $scrap_date         =$request->input('scrap_date');//强制报废日期
        $business_scope     =$request->input('business_scope');//经营范围
        $goods              =$request->input('goods');//产品名称  危货/普货
        $goods_type         =$request->input('goods_type');//产品名称  危货/普货
        $design_code        =$request->input('design_code');//设计代码
        $operation_date     =$request->input('operation_date');//投运日期
        $tank_num           =$request->input('tank_num');//罐体编号
        $tank_type          =$request->input('tank_type');//罐体类型
        $registr_cert       =$request->input('registr_cert');//登记证书
        $carrier_cert       =$request->input('carrier_cert');//承运险
        $tank_cert          =$request->input('tank_cert');//罐检
        $nameplate          =$request->input('nameplate');//铭牌
        $pass_cert          =$request->input('pass_cert');//合格证
        $medallion_change_end          =$request->input('medallion_change_end');//运输证换成有效期截止
        $car_color          =$request->input('car_color');//车身颜色
        $car_brand          =$request->input('car_brand');//车辆品牌
        $car_model          =$request->input('car_model');//车身型号
        $car_made           =$request->input('car_made');//国产/进口
        $engine_num         =$request->input('engine_num');//发动机号
        $engine_model       =$request->input('engine_model');//发动机型号
        $fuel_type          =$request->input('fuel_type');//燃料种类
        $displacement_power =$request->input('displacement_power');//排量/功率
        $maker              =$request->input('maker');//制造厂名称
        $turn_view          =$request->input('turn_view');//转向形式
        $tread              =$request->input('tread');//前后轮距
        $trye_num           =$request->input('trye_num');//轮胎数/规格
        $steel_plate        =$request->input('steel_plate');//钢板弹簧片数
        $wheel_base         =$request->input('wheel_base');//轴距
        $axles_num          =$request->input('axles_num');//车轴数
        $outline            =$request->input('outline');//车辆外廓尺寸
        $car_size           =$request->input('car_size');//货厢内部尺寸
        $car_user           =$request->input('car_user');//驾驶室载客
        $gps_flag           =$request->input('gps_flag');//卫星定位安装情况
        $bussiness_license  =$request->input('bussiness_license');//经营许可证号
        $license_plate      =$request->input('license_plate');//车牌颜色
        $license_back       =$request->input('license_back');//行驶证背面
        $medallion_back     =$request->input('medallion_back');//运输证背面
        $registr_date       =$request->input('registr_date');//机动车注册登记日期
        $medallion_begin    =$request->input('medallion_begin');//运输证发证日期
        $license_start      =$request->input('license_start');//行驶证发证日期
        $compulsory_cert    =$request->input('compulsory_cert');//交强险
        $commercial_cert    =$request->input('commercial_cert');//商业险
        $registr_cert_date  =$request->input('registr_cert_date');//登记证书发证日期
        $carrier_insurer    =$request->input('carrier_insurer');//保险公司
        $carrier_insurer_num=$request->input('carrier_insurer_num');//承运险保险单号
        $carrier_baoe       =$request->input('carrier_baoe');//人身伤亡每人保额（万元）
        $carrier_zrx        =$request->input('carrier_zrx');//第三者责任险
        $carrier_zr         =$request->input('carrier_zr');//每人人身伤亡责任（万元）
        $carrier_good       =$request->input('carrier_good');//货物责任保险（万元）
        $compulsory_insurer =$request->input('compulsory_insurer');//交强险保险公司
        $compulsory_num     =$request->input('compulsory_num');//交强险保险保单
        $compulsory_sc      =$request->input('compulsory_sc');//交强险死亡伤残赔偿
        $compulsory_yl      =$request->input('compulsory_yl');//医疗费用赔偿
        $compulsory_property=$request->input('compulsory_property');//交强险财产损失赔偿
        $vessel_tax         =$request->input('vessel_tax');//车船税
        $commercial_insurer =$request->input('commercial_insurer');//商业险保险单位
        $commercial_num     =$request->input('commercial_num');//商业险保险单号
        $commercial_tz      =$request->input('commercial_tz');//特种车损失险（万元）
        $commercial_zr      =$request->input('commercial_zr');//商业险第三者责任险（万元）
        $commercial_driver  =$request->input('commercial_driver');//车上司机责任险（万元）
        $commercial_user    =$request->input('commercial_user');//车上乘客责任险（万元）
        $car_unit           =$request->input('car_unit');//规格
        $sgs_cert           =$request->input('sgs_cert');//SGS证书
        $sgs_date           =$request->input('sgs_date');//SGS有效期截止
        $inspect_annually   =$request->input('inspect_annually');//年检


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

            if($self_id){
                $name_where=[
                    ['self_id','!=',$self_id],
                    ['car_number','=',$car_number],
                    ['delete_flag','=','Y'],
                    ['group_code','=',$group_code]
                ];
            }else{
                $name_where=[
                    ['car_number','=',$car_number],
                    ['delete_flag','=','Y'],
                    ['group_code','=',$group_code]
                ];
            }

            $carnumber = TmsCar::where($name_where)->count();

            if ($carnumber>0){
                $msg['code'] = 308;
                $msg['msg'] = '车牌号已存在，请重新填写';
                return $msg;
            }

            $data['car_number']        =$car_number;
            $data['car_type']          =$car_type;
            $data['carframe_num']      =$carframe_num;
            $data['crock_medium']      =$crock_medium;
            $data['license_date']      =$license_date;
            $data['medallion_date']    =$medallion_date;
            $data['remark']            =$remark;
            $data['volume']            =$volume;
            $data['insure']            =$insure;
            $data['tank_validity']     =$tank_validity;
            $data['weight']            =$weight;
            $data['license']           =img_for($license,'one_in');
            $data['medallion']         =img_for($medallion,'one_in');
//            $data['payment_state']     =$payment_state;
            $data['insure_price']      =$insure_price;
            $data['compulsory']        =$compulsory;
            $data['compulsory_end']    =$compulsory_end;
            $data['commercial']        =$commercial;
            $data['commercial_end']    =$commercial_end;
            $data['carrier']           =$carrier;
            $data['carrier_end']       =$carrier_end;
            $data['medallion_num']     =$medallion_num;
            $data['curb_weight']       =$curb_weight;
            $data['all_weight']        =$all_weight;
            $data['medallion_change']  =$medallion_change;
            $data['license_begin']     =$license_begin;
            $data['production_date']   =$production_date;
            $data['scrap_date']        =$scrap_date;
            $data['business_scope']    =$business_scope;
            $data['goods']             =$goods;
            $data['goods_type']        =$goods_type;
            $data['design_code']       =$design_code;
            $data['operation_date']    =$operation_date;
            $data['tank_num']          =$tank_num;
            $data['tank_type']         =$tank_type;
            $data['vessel_tax']         =$vessel_tax;

            $data['medallion_change_end']         =$medallion_change_end;
            $data['registr_cert']      =img_for($registr_cert,'one_in');
            $data['carrier_cert']      =img_for($carrier_cert,'one_in');
            $data['tank_cert']         =img_for($tank_cert,'one_in');
            $data['nameplate']         =img_for($nameplate,'one_in');
            $data['pass_cert']         =img_for($pass_cert,'one_in');
            $data['car_color']         =$car_color;
            $data['car_brand']         =$car_brand;
            $data['car_model']         =$car_model;
            $data['car_made']          =$car_made;
            $data['engine_num']        =$engine_num;
            $data['engine_model']      =$engine_model;
            $data['fuel_type']         =$fuel_type;
            $data['displacement_power']=$displacement_power;
            $data['maker']             =$maker;
            $data['turn_view']         =$turn_view;
            $data['tread']             =$tread;
            $data['trye_num']          =$trye_num;
            $data['steel_plate']       =$steel_plate;
            $data['wheel_base']        =$wheel_base;
            $data['axles_num']         =$axles_num;
            $data['outline']           =$outline;
            $data['car_size']          =$car_size;
            $data['car_user']          =$car_user;
            $data['gps_flag']          =$gps_flag;
            $data['bussiness_license'] =$bussiness_license;
            $data['license_plate']     =$license_plate;
            $data['license_back']      =img_for($license_back,'one_in');
            $data['medallion_back']    =img_for($medallion_back,'one_in');
            $data['registr_date']      =$registr_date;
            $data['medallion_begin']   =$medallion_begin;
            $data['license_start']     =$license_start;
            $data['compulsory_cert']   =img_for($compulsory_cert,'one_in');
            $data['commercial_cert']   =img_for($commercial_cert,'one_in');
            $data['registr_cert_date'] =$registr_cert_date;
            $data['carrier_insurer']   =$carrier_insurer;
            $data['carrier_insurer_num']=$carrier_insurer_num;
            $data['carrier_baoe']       =$carrier_baoe;
            $data['carrier_zrx']        =$carrier_zrx;
            $data['carrier_zr']         =$carrier_zr;
            $data['carrier_good']       =$carrier_good;
            $data['compulsory_insurer'] =$compulsory_insurer;
            $data['compulsory_num']     =$compulsory_num;
            $data['compulsory_sc']      =$compulsory_sc;
            $data['compulsory_yl']      =$compulsory_yl;
            $data['compulsory_property']=$compulsory_property;
            $data['commercial_insurer'] =$commercial_insurer;
            $data['commercial_num']     =$commercial_num;
            $data['commercial_tz']      =$commercial_tz;
            $data['commercial_zr']      =$commercial_zr;
            $data['commercial_driver']  =$commercial_driver;
            $data['commercial_user']    =$commercial_user;
            $data['car_unit']           =$car_unit;
            $data['sgs_cert']           =img_for($sgs_cert,'one_in');
            $data['sgs_date']           =$sgs_date;
            $data['inspect_annually']           =$inspect_annually;
            $wheres['self_id'] = $self_id;
            $old_info=TmsCar::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsCar::where($wheres)->update($data);

                $operationing->access_cause='修改车辆';
                $operationing->operation_type='update';

            }else{
                $data['self_id']            =generate_id('car_');
                $data['type']              =$type;
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=TmsCar::insert($data);
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



    /***    车辆禁用/启用      /tms/car/carUseFlag
     */
    public function carUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_car';
        $medol_name='TmsCar';
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

    /***    车辆删除      /tms/car/carDelFlag
     */
    public function carDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_car';
        $medol_name='TmsCar';
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

    /***    拿去车辆数据     /tms/car/getCar
     */
    public function  getCar(Request $request){
        $group_code=$request->input('group_code');
        $car_number=$request->input('car_number');
        $type      =$request->input('type');
//        $input['group_code'] =  $group_code = '1234';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'type','value'=>$type],
        ];

        $where=get_list_where($search);
        $select = ['self_id','car_number','car_brand','group_code'];
        $data['info']=TmsCar::where($where)->select($select)->get();

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
        $select=['self_id','car_number','car_type','carframe_num','crock_medium','crock_medium','license_date','medallion_date','remark','weight','volume','insure','tank_validity',
            'license','medallion','payment_state','insure_price','compulsory_end','commercial_end','carrier_end','compulsory','commercial','carrier','medallion_num','curb_weight','all_weight','medallion_change','license_begin','production_date','scrap_date','business_scope','goods',
            'design_code','operation_date','tank_num','tank_type','registr_cert','carrier_cert','tank_cert','medallion_change_end','nameplate','pass_cert','car_color','car_brand',
            'car_made','engine_num','fuel_type','displacement_power','maker','turn_view','tread','trye_num','steel_plate','wheel_base','axles_num','outline','car_model',
            'car_size','car_user','gps_flag','bussiness_license','license_plate','engine_model','license_back','medallion_back','registr_date','medallion_begin',
            'license_start','compulsory_cert','commercial_cert','registr_cert_date','carrier_insurer','carrier_insurer_num','carrier_baoe','carrier_zrx','carrier_zr','carrier_good',
            'compulsory_insurer','compulsory_num','compulsory_sc','compulsory_yl','compulsory_property','commercial_insurer','commercial_num','commercial_tz','commercial_zr','commercial_driver','vessel_tax',
            'commercial_user','car_unit','goods_type','sgs_cert','sgs_date','inspect_annually'];
        $select1 = ['self_id','parame_name'];


        $info= TmsCar::with(['TmsCarType' => function($query) use($select1){
            $query->select($select1);
        }])->where('self_id',$self_id)->select($select)->first();

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/
            $info->medallion     =img_for($info->medallion,'no_json');
            $info->license       =img_for($info->license,'no_json');
            $info->registr_cert  =img_for($info->registr_cert,'no_json');
            $info->carrier_cert  =img_for($info->carrier_cert,'no_json');
            $info->tank_cert     =img_for($info->tank_cert,'no_json');
            $info->nameplate     =img_for($info->nameplate,'no_json');
            $info->pass_cert     =img_for($info->pass_cert,'no_json');
            $info->compulsory_cert     =img_for($info->compulsory_cert,'no_json');
            $info->commercial_cert     =img_for($info->commercial_cert,'no_json');
            $info->license_back     =img_for($info->license_back,'no_json');
            $info->medallion_back     =img_for($info->medallion_back,'no_json');
            $info->sgs_cert     =img_for($info->sgs_cert,'no_json');
            $info->type_show     = $info->TmsCarType->parame_name;
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

    /***    车辆导出     /tms/car/excel
     */
    public function excel(Request $request,File $file){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $user_info  = $request->get('user_info');//接收中间件产生的参数
//        $table_name = 'tms_car';
        $now_time   =date('Y-m-d H:i:s',time());
//        $operationing->access_cause     ='车辆导出';
//        $operationing->table            =$table_name;
//        $operationing->operation_type   ='create';
//        $operationing->now_time         =$now_time;
//        $operationing->type             ='excel';
//        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $ids     =$request->input('ids');
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
                'license','medallion','payment_state','insure_price','compulsory_end','commercial_end','carrier_end','compulsory','commercial','carrier','medallion_num','curb_weight','all_weight','medallion_change','license_begin','production_date','scrap_date','business_scope','goods',
                'design_code','operation_date','tank_num','tank_type','registr_cert','carrier_cert','tank_cert','medallion_change_end','nameplate','pass_cert','car_color','car_brand',
                'car_made','engine_num','fuel_type','displacement_power','maker','turn_view','tread','trye_num','steel_plate','wheel_base','axles_num','outline','car_model',
                'car_size','car_user','gps_flag','bussiness_license','license_plate','engine_model','license_back','medallion_back','registr_date','medallion_begin',
                'license_start','compulsory_cert','commercial_cert','registr_cert_date','carrier_insurer','carrier_insurer_num','carrier_baoe','carrier_zrx','carrier_zr','carrier_good',
                'compulsory_insurer','compulsory_num','compulsory_sc','compulsory_yl','compulsory_property','commercial_insurer','commercial_num','commercial_tz','commercial_zr','commercial_driver','vessel_tax',
                'commercial_user','car_unit','goods_type','sgs_cert','sgs_date'];
            $select1 = ['self_id','parame_name'];
            $info=TmsCar::with(['tmsCarType' => function($query) use($select1){
                $query->select($select1);
            }])->where($where)->whereIn('self_id',explode(',',$ids))->orderBy('create_time', 'desc')->select($select)->get();
//dd($info);
            if($info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "type"=>'类型',
                    "car_number"=>'车牌号',
                    "car_type"=>'车型',
                    "crock_medium"=>'罐体介质',
                    "volume"=>'罐体容积',
                    "tank_validity"=>'罐检到期日期',
                    "weight"=>'核载吨位',
                    "medallion_num"=>'运输证号',
                    "curb_weight"=>'整备质量',
                    "all_weight"=>'总质量',
                    "medallion_change"=>'运输证到期时间',
                    "license_begin"=>'行驶证注册日期',
                    "license_date"=>'行驶证到期日期',
                    "production_date"=>'出厂日期',
                    "scrap_date"=>'强制报废日期',
                    "business_scope"=>'经营范围',
                    "goods_type"=>'使用性质',
                    "design_code"=>'设计代码',
                    "operation_date"=>'投运日期',
                    "tank_num"=>'罐体编号',
                    "tank_type"=>'罐体类型',
                    "medallion_change_end"=>'运输证换证有效期截止',
                    "car_color"=>'车身颜色',
                    "car_brand"=>'车辆品牌',
                    "car_model"=>'车身型号',
                    "car_made"=>'国产/进口',
                    "engine_num"=>'发动机号',
                    "engine_model"=>'发动机型号',
                    "fuel_type"=>'燃料种类',
                    "displacement_power"=>'排量/功率',
                    "maker"=>'制造厂名称',
                    "turn_view"=>'转向形式',
                    "tread"=>'前后轮距',
                    "trye_num"=>'轮胎数',
                    "car_unit"=>'轮胎规格',
                    "goods"=>'产品名称',
                    "steel_plate"=>'钢板弹簧片数',
                    "wheel_base"=>'轴距',
                    "axles_num"=>'车轴数',
                    "outline"=>'车辆外廓尺寸',
                    "car_size"=>'货厢内部尺寸',
                    "car_user"=>'驾驶室载客',
                    "gps_flag"=>'卫星定位安装情况',
                    "bussiness_license"=>'经营许可证号',
                    "license_plate"=>'车牌颜色',
                    "remark"=>'备注',

                    "registr_date"=>'机动车注册登记日期',
                    "medallion_begin"=>'运输证发证日期',
                    "license_start"=>'行驶证发证日期',
                    "registr_cert_date"=>'登记证书发证日期',
                    "sgs_date"=>'SGS有效期截止',

                    "compulsory"=>'交强险保费',
                    "compulsory_insurer"=>'交强险保险公司',
                    "compulsory_num"=>'交强险保险保单',
                    "compulsory_sc"=>'交强险死亡伤残赔偿',
                    "compulsory_yl"=>'医疗费用赔偿',
                    "compulsory_property"=>'交强险财产损失赔偿',
                    "compulsory_end"=>'交强险有效期截止',
                    "vessel_tax"=>'车船税',

                    "commercial"=>'商业险保费',
                    "commercial_insurer"=>'商业险保险单位',
                    "commercial_num"=>'商业险保险单号',
                    "commercial_tz"=>'特种车损失险（万元）',
                    "commercial_zr"=>'商业险第三者责任险（万元）',
                    "commercial_driver"=>'车上司机责任险（万元）',
                    "commercial_user"=>'车上乘客责任险（万元）',
                    "commercial_end"=>'商业险有效期截止',

                    "carrier"=>'承运险保费',
                    "carrier_insurer"=>'承运险保险单位',
                    "carrier_insurer_num"=>'承运险保险单号',
                    "carrier_baoe"=>'人身伤亡每人保额（万元）',
                    "carrier_zrx"=>'第三者责任险',
                    "carrier_zr"=>'每人人身伤亡责任（万元）',
                    "carrier_good"=>'货物责任保险（万元）',
                    "carrier_end"=>'承运险有效期截止',

                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];

                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    if($v['type'] == 'tractor'){
                        $list['type']               = '牵引车';
                    }else{
                        $list['type']               = '挂车';
                    }
                    $list['car_number']         = $v['car_number'];
                    $list['car_type']           = $v->tmsCarType->parame_name;
                    $list['crock_medium']       = $v['crock_medium'];
                    $list['volume']             = $v['volume'];
                    $list['tank_validity']      = $v['tank_validity'];
                    $list['weight']             = $v['weight'];
                    $list['medallion_num']      = $v['medallion_num']."\t";
                    $list['curb_weight']        = $v['curb_weight'] ;
                    $list['all_weight']         = $v['all_weight'];
                    $list['medallion_change']   = $v['medallion_change'];
                    $list['license_begin']      = $v['license_begin'];
                    $list['license_date']       = $v['license_date'];
                    $list['production_date']    = $v['production_date'];
                    $list['scrap_date']         = $v['scrap_date'];
                    $list['business_scope']     = $v['business_scope'];
                    $list['goods_type']         = $v['goods_type'];
                    $list['design_code']        = $v['design_code'];
                    $list['operation_date']     = $v['operation_date'];
                    $list['tank_num']           = $v['tank_num'];
                    $list['tank_type']          = $v['tank_type'];
                    $list['medallion_change_end'] = $v['medallion_change_end'];
                    $list['car_color']          = $v['car_color'];
                    $list['car_brand']          = $v['car_brand'];
                    $list['car_model']          = $v['car_model'];
                    $list['car_made']           = $v['car_made'];
                    $list['engine_num']         = $v['engine_num'];
                    $list['engine_model']       = $v['engine_model'];
                    $list['fuel_type']          = $v['fuel_type'];
                    $list['displacement_power'] = $v['displacement_power'];
                    $list['maker']              = $v['maker'];
                    $list['turn_view']          = $v['turn_view'];
                    $list['tread']              = $v['tread'];
                    $list['trye_num']           = $v['trye_num'];
                    $list['car_unit']           = $v['car_unit'];
                    $list['goods']              = $v['goods'];
                    $list['steel_plate']        = $v['steel_plate'];
                    $list['wheel_base']         = $v['wheel_base'];
                    $list['axles_num']          = $v['axles_num'];
                    $list['outline']            = $v['outline'];
                    $list['car_size']           = $v['car_size'];
                    $list['car_user']           = $v['car_user'];
                    if($v['gps_flag'] == 'Y'){
                        $list['gps_flag']           = '是';
                    }else{
                        $list['gps_flag']           = '否';
                    }
                    $list['bussiness_license']  = $v['bussiness_license'];
                    $list['license_plate']      = $v['license_plate'];
                    $list['remark']             = $v['remark'];
                    $list['registr_date']       = $v['registr_date'];
                    $list['medallion_begin']    = $v['medallion_begin'];
                    $list['license_start']      = $v['license_start'];
                    $list['registr_cert_date']  = $v['registr_cert_date'];
                    $list['sgs_date']           = $v['sgs_date'];
                    $list['compulsory']         = $v['compulsory'];
                    $list['compulsory_end']     = $v['compulsory_end'];
                    $list['commercial']         = $v['commercial'];
                    $list['commercial_end']     = $v['commercial_end'];
                    $list['carrier']            = $v['carrier'];
                    $list['carrier_end']        = $v['carrier_end'];
                    $list['remark']             = $v['remark'];
                    $list['compulsory']         = $v['compulsory'];
                    $list['compulsory_insurer'] = $v['compulsory_insurer'];
                    $list['compulsory_num']     = $v['compulsory_num'];
                    $list['compulsory_sc']      = $v['compulsory_sc'];
                    $list['compulsory_yl']      = $v['compulsory_yl'];
                    $list['compulsory_property']= $v['compulsory_property'];
                    $list['compulsory_end']     = $v['compulsory_end'];
                    $list['vessel_tax']         = $v['vessel_tax'];
                    $list['commercial']         = $v['commercial'];
                    $list['commercial_insurer'] = $v['commercial_insurer'];
                    $list['commercial_num']     = $v['commercial_num'];
                    $list['commercial_tz']      = $v['commercial_tz'];
                    $list['commercial_zr']      = $v['commercial_zr'];
                    $list['commercial_driver']  = $v['commercial_driver'];
                    $list['commercial_user']    = $v['commercial_user'];
                    $list['commercial_end']     = $v['commercial_end'];
                    $list['carrier']            = $v['carrier'];
                    $list['carrier_insurer']    = $v['carrier_insurer'];
                    $list['carrier_insurer_num']= $v['carrier_insurer_num'];
                    $list['carrier_baoe']       = $v['carrier_baoe'];
                    $list['carrier_zrx']        = $v['carrier_zrx'];
                    $list['carrier_zr']         = $v['carrier_zr'];
                    $list['carrier_good']       = $v['carrier_good'];
                    $list['carrier_end']        = $v['carrier_end'];
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


    /***    车辆保险导出     /tms/car/insurExecl
     */
    public function insurExecl(Request $request,File $file){
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

            $select=['self_id','car_number','car_type','type','carrier_insurer','carrier_insurer_num','carrier','carrier_end','compulsory_insurer','compulsory_num','compulsory','compulsory_end','commercial_insurer','commercial_num','commercial','commercial_end','vessel_tax'];
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
                    "type"=>'车型',
                    "carrier_insurer"=>'承运险保险公司',
                    "carrier_insurer_num"=>'承运险保险单号',
                    "carrier"=>'承运险保费',
                    "carrier_end"=>'承运险到期时间',
                    "compulsory_insurer"=>'交强险保险公司',
                    "compulsory_num"=>'交强险保险单号',
                    "compulsory"=>'交强险保费',
                    "vessel_tax"=>'交强险车船税',
                    "compulsory_end"=>'交强险到期时间',
                    "commercial_insurer"=>'商业险保险公司',
                    "commercial_num"=>'商业险保险单号',
                    "commercial"=>'商业险保费',
                    "commercial_end"=>'商业险到期时间',
                    "remark"=>'备注'
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];

                foreach ($info as $k=>$v){
                    $list=[];
                    $list['id']=($k+1);
                    $list['car_number']          = $v['car_number'];
                    if($v['type'] == 'tractor'){
                         $list['type']           = '牵引车';
                    }else{
                         $list['type']           = '挂车';
                    }
                    $list['carrier_insurer']     = $v['carrier_insurer'];
                    $list['carrier_insurer_num'] = $v['carrier_insurer_num'];
                    $list['carrier']             = $v['carrier'];
                    $list['carrier_end']         = $v['carrier_end'];
                    $list['compulsory_insurer']  = $v['compulsory_insurer'];
                    $list['compulsory_num']      = $v['compulsory_num'] ;
                    $list['compulsory']          = $v['compulsory'];
                    $list['vessel_tax']          = $v['vessel_tax'];
                    $list['compulsory_end']      = $v['compulsory_end'];
                    $list['commercial_insurer']  = $v['commercial_insurer'];
                    $list['commercial_num']      = $v['commercial_num'];
                    $list['commercial']          = $v['commercial'];
                    $list['commercial_end']      = $v['commercial_end'];
                    $list['remark']              = $v['remark'];
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
     *设置油耗预警值
     * */
    public function setParam(Request $request){
        $group_code=$request->input('group_code');
        $threshold=$request->input('threshold');

//        $input['group_code'] =  $group_code = '1234';
        $data['threshold'] =  $threshold;
        $data['update_time'] = date('Y-m-d H:i:s',time());
        $id = SystemGroup::where('self_id',$group_code)->update($data);

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        return $msg;
    }

    /**
     * 月油耗 列表
     * */
    public function  countList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $data['user_info']    =$request->get('user_info');

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

    /***    车辆分页      /tms/car/countPage
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
        $car_number     =$request->input('car_number');
        $car_id         =$request->input('car_id');
        $month          =$request->input('month');
//        $end_time       =$request->input('end_time');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'car_id','value'=>$car_id],
            ['type'=>'=','name'=>'month','value'=>$month],
//            ['type'=>'<','name'=>'create_time','value'=>$end_time],
        ];

        $where=get_list_where($search);

        $select=['self_id','car_id','car_number','month','month_kilo','month_fat','create_time','update_time','use_flag','delete_flag','group_code','total_oil'];
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


    /***    月油耗      /tms/car/createCount
     */
    public function createCount(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select = ['self_id','car_number','car_id','month','month_kilo','month_fat','use_flag','delete_flag','group_code','group_name','create_time','total_oil'];

        $data['info']= CarCount::where('self_id',$self_id)->select($select)->first();

        if ($data['info']){

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;

    }

    /**
     * 获取本月加油量 tms/car/getCarOil
     * */
    public function getCarOil(Request $request){
        $group_code=$request->input('group_code');
        $car_number=$request->input('car_number');
        $start_time    =$request->input('start_time');
        $end_time      =$request->input('end_time');
//        $input['group_code'] =  $group_code = '1234';
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'car_number','value'=>$car_number],
            ['type'=>'>=','name'=>'add_time','value'=>$start_time],
            ['type'=>'<=','name'=>'add_time','value'=>$end_time],
        ];

        $where=get_list_where($search);
        $select = ['self_id','car_number','number','group_code','use_flag','delete_flag'];
        $data['info']=CarOil::where($where)->select($select)->sum('number');

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
        $total_oil          =$request->input('total_oil');// 月油耗

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
            $data['total_oil']       =$total_oil;

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


    /**
     * 月油耗记录删除
     * */
    public function oilDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='car_count';
        $medol_name='CarCount';
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
