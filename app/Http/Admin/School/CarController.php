<?php
namespace App\Http\Admin\School;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Group\SystemGroup;
use App\Models\School\SchoolCarInfo;

use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;

use App\Http\Controllers\FileController as File;

class CarController  extends CommonController{
    /***    车辆信息头部      /school/car/carList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  carList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

       // dd($msg);
        return $msg;
    }

    /***    车辆信息分页数据      /school/car/carPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
	public function carPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
		$car_number     =$request->input('car_number');
		$group_code     =$request->input('group_code');
        $car_possess    =$request->input('car_possess');
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
			['type'=>'=','name'=>'car_number','value'=>$car_number],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'car_possess','value'=>$car_possess],
        ];

        $where=get_list_where($search);

        //$data['table_tilte']=['group_name','car_number','car_possess','car_brand','car_nuclear','hardware','create_user_name','use_flag','button'];
        
        $select=['self_id','group_code','group_name','use_flag','car_brand','car_number','car_nuclear','car_possess','create_user_name','create_time','remark'];
        $selectSchoolHardware=['car_id','mac_address','deploy_user_name','deploy_update_time'];

        $where_systemgroup=['delete_flag'=>'Y'];
		
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SchoolCarInfo::wherehas('schoolCarSystem',function ($query)use($where_systemgroup){
                    $query->where($where_systemgroup);
                })
                ->where($where)->count();

                $data['items']=SchoolCarInfo::wherehas('schoolCarSystem',function ($query)use($where_systemgroup){
                    $query->where($where_systemgroup);
                })->with(['schoolHardware' => function($query)use($selectSchoolHardware){
                    $query->select($selectSchoolHardware);
                }]) ->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SchoolCarInfo::wherehas('schoolCarSystem',function ($query)use($where_systemgroup){
                    $query->where($where_systemgroup);
                })->where($where)->count(); //总的数据量

                $data['items']=SchoolCarInfo::wherehas('schoolCarSystem',function ($query)use($where_systemgroup){
                    $query->where($where_systemgroup);
                })->with(['schoolHardware' => function($query)use($selectSchoolHardware){
                    $query->select($selectSchoolHardware);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                //$data['table_tilte']=['car_number','car_possess','car_brand','car_nuclear','hardware','create_user_name','use_flag','button'];
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SchoolCarInfowherehas('schoolCarSystem',function ($query)use($where_systemgroup){
                    $query->where($where_systemgroup);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SchoolCarInfo::wherehas('schoolCarSystem',function ($query)use($where_systemgroup){
                    $query->where($where_systemgroup);
                })->with(['schoolHardware' => function($query)use($selectSchoolHardware){
                    $query->select($selectSchoolHardware);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
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
        //dd($msg);
        return $msg;

	}

    /***    创建数据操作      /school/car/createCar
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  createCar(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');

        $select=['self_id','group_code','group_name','use_flag','car_brand','car_number','car_nuclear','car_possess','img','remark'];

        $where1['self_id']=$self_id;

        $data['car_info']=SchoolCarInfo::where($where1)->select($select)->first();
			//dd($data['car_info']);
		if($data['car_info']){
            $data['car_info']->img=img_for($data['car_info']->img,'more');
		}

        $data['key_info']=[
            ['key'=>'img',
                'count'=>'5',
                'name'=>'车辆图片']
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;

    }

    /***    车辆数据操作      /school/car/addCar
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  addCar(Request $request){

		$user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing = $request->get('operationing');//接收中间件产生的参数

        $now_time   =date('Y-m-d H:i:s',time());
        $url_len    =config('aliyun.oss.url_len');
        $table_name ='school_car_info';

        $operationing->access_cause     ='创建/修改车辆';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';


        $input                          =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $car_brand          =$request->input('car_brand');
        $car_number         =$request->input('car_number');
        $car_nuclear        =$request->input('car_nuclear');
        $car_possess        =$request->input('car_possess');
        $img                =$request->input('img');
		$remark        		=$request->input('remark');


        /*** 虚拟数据**/
        $input['self_id']=$self_id='car_202101201545133032777518';
        $input['group_code']=$group_code='1234';
        $input['car_brand']=$car_brand='宇通-改';
        $input['car_number']=$car_number='苏F6988';
        $input['car_nuclear']=$car_nuclear='16';
        $input['car_possess']=$car_possess='自有';
        $input['remark']=$remark='备注';
        $input['img']=$img=[
                '0'=>[
                    'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2021-01-20/2c6483db79e92488ab5b7e84e0c8921e.pn',
                    'width'=>'120',
                    'height'=>'120',
                    ],
                ];


        $rules=[
            'group_code'=>'required',
            'car_number'=>'required',
        ];
        $message=[
            'group_code.required'=>'请选择学校',
            'car_number.required'=>'请填写车牌号码',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){



            //效验数据的有效性      车牌的唯一性
            if($self_id){
                $name_where=[
                    ['car_number','=',trim($car_number)],
                    ['self_id','!=',$self_id],
                    ['group_code','=',$group_code],
                ];

            }else{
                $name_where=[
                    ['car_number','=',trim($car_number)],
                    ['group_code','=',$group_code],
                ];
            }
            $name_count = SchoolCarInfo::where($name_where)->count();            //检查车牌是不是重复
            if($name_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '车牌重复';
                return $msg;
            }

            //开始制作数据
            $data['car_brand']		=$car_brand;
            $data['car_number']		=$car_number;
            $data['car_nuclear']	=$car_nuclear;
            $data['car_possess']	=$car_possess;
			$data['remark']			=$remark;


            if($img) {
                foreach ($img as $k => $v) {
                    $xq2[$k]['url'] = substr($v['url'], $url_len);
                    $xq2[$k]['width'] = $v['width'];
                    $xq2[$k]['height'] = $v['height'];
                }
                $data['img']=json_encode($xq2);//轮播图
            }else {
                $data['img'] = Null;
            }

            $wheres['self_id'] = $self_id;
            $old_info=SchoolCarInfo::where($wheres)->first();

            if($old_info){
                $data['update_time'] = $now_time;
                $id=SchoolCarInfo::where($wheres)->update($data);

            }else{
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['self_id'] = generate_id('car_');
                $data['update_time'] = $data['create_time'] = $now_time;
                $data['group_code']=$group_code;
                $data['group_name']= SystemGroup::where('self_id','=',$group_code)->value('group_name');
                $id=SchoolCarInfo::insert($data);
            }

            $operationing->table_id     =$self_id?$self_id:$data['self_id'];
            $operationing->old_info     =$old_info;
            $operationing->new_info     =$data;


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
            $msg['code']=301;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            //dd($msg);
            return $msg;
        }

    }

    /***    车辆禁启用      /school/car/carUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function carUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='school_car_info';
        $medol_name='schoolCarInfo';
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

    /***    车辆删除      /school/car/carDelFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function carDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='school_car_info';
        $medol_name='schoolCarInfo';
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

    /***    excel表格导入     /school/import
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function import(Request $request,File $file){
//        dd(123);
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='school_car_info';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入校车';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';


        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');

        /****虚拟数据***/
        $input['group_code']   =$group_code='group_202009060931404534426532';
        $input['file_id']     =$file_id='123456789';
        //$input['importurl']    =$importurl="uploads/2021-01-20/bb498c786e51dab0ed5eac4024b307d1.xlsx"; //必填字段缺失的校车导入文件
        //$input['importurl']    =$importurl="uploads/2021-01-20/8468169132b84b4628f29cae9361beeb.xlsx"; //必填字段重复的校车导入文件
        //$input['importurl']    =$importurl="uploads/2021-01-20/45492cd968b3daa109094ccdb090e48a.xlsx"; //必填字段超长的校车导入文件
        $input['importurl']    =$importurl="uploads/2021-01-20/641df678239904203f77da681b285f2b.xlsx"; //正常的校车导入文件

        $rules = [
            'group_code' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'group_code.required' => '请选择学校',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
            $res = Excel::toArray((new Import),$importurl);
            $info_check=[];
            //获取excel的sheet0文件
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
                '车牌号码' =>['Y','N','50','car_number'],
                '荷载人数' =>['N','Y','10','car_nuclear'],
            ];

            $ret=arr_check($shuzu,$info_check);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                dd($msg);
                return $msg;
            }
            $info_wait=$ret['new_array'];
            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code],
            ];
            $info = SystemGroup::where($where_check)->select('group_code','group_name')->first();

            if(empty($info)){
                $msg['code'] = 302;
                $msg['msg'] = '学校不存在';
                //dd($msg);
                return $msg;
            }
            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where = [
                    'delete_flag'=>'Y',
                    'car_number'=>$v['car_number']
                ];
                $is_car_number = SchoolCarInfo::where($where)->value('car_number');
                if($is_car_number){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行车牌号存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            = generate_id('car_');
                    $list['group_code']         = $group_code;
                    $list['group_name']         = $info->group_name
                    ;
                    $list['car_number']         = $v['car_number'];
                    $list['car_nuclear']        = $v['car_nuclear'];


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
                //dd($msg);
                return $msg;
            }
            $count=count($datalist);
            $id= SchoolCarInfo::insert($datalist);
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
            $msg['code'] = 304;
            return $msg;
        }


    }

    /***    excel表格导出数据     /school/car/carExcel
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function carExcel(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();

        /** 接收数据*/
        $group_code     =$request->input('group_code');

        /** 虚拟数据*/
        //$group_code     =$input['group_code']   ='group_202006221103544823194960';

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
            $search=[
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];

            $where=get_list_where($search);
            $select=['self_id','group_code','group_name','use_flag','car_brand','car_number','car_nuclear','car_possess','create_user_name','create_time','remark'];
            $selectSchoolHardware=['car_id','mac_address','deploy_user_name','deploy_update_time'];

            $car_info=SchoolCarInfo::with(['schoolHardware' => function($query)use($selectSchoolHardware){
                $query->select($selectSchoolHardware);
            }])->where($where)->orderBy('create_time', 'desc')
                ->select($select)->get();

            if($car_info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "group_name"=>'学校名称',
                    "car_number"=>'车牌号码',
                    "car_nuclear"=>'荷载人数',
                    "car_possess"=>'车辆性质',
                    "remark"=>'备注信息',
                    "mac_address"=>'绑定硬件设备号码',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($car_info as $k=>$v){
                    $data_execl[$k]['id']               =($k+1);//id
                    $data_execl[$k]['group_name']       =$v->group_name;
                    $data_execl[$k]['car_number']       =$v->car_number;
                    $data_execl[$k]['car_nuclear']      =$v->car_nuclear;
                    $data_execl[$k]['car_possess']      =$v->car_possess;
                    $data_execl[$k]['remark']           =$v->remark;

                    if($v->schoolHardware){
                        $data_execl[$k]['mac_address']    =$v->schoolHardware->mac_address;
                    }else{
                        $data_execl[$k]['mac_address']    =null;
                    }
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


    /***    详情     /school/car/carDetails
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function carDetails(Request $request,Details $details){
        $table_name='school_car_info';
        $select=['self_id','car_brand','car_number','car_nuclear','car_possess','group_name','img','remark','create_user_name','create_time','update_time','use_flag'];

        $self_id=$request->input('self_id');
        //$self_id='car_202009041113268902331821';
        $msg=$details->details($self_id,$table_name,$select);

        /** 如果需要对数据进行处理，请自行在下面对 $msg['data']  进行处理工作*/
        //dd($msg);
        return $msg;

    }

}
?>
