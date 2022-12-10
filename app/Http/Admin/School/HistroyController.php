<?php
namespace App\Http\Admin\School;

use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;

use App\Models\School\SchoolCarInfo;
use App\Models\School\SchoolInfo;
use App\Models\School\SchoolPathwayPerson;
use App\Models\School\SchoolCarriageJson;
use App\Models\School\SchoolPathway;
use App\Models\School\SchoolPath;
use App\Models\Group\SystemGroup;

use DB;
class HistroyController  extends CommonController{
    private $prefix='car_';

    /**
     * 获取历史饼状图和线路的数据
     * pathUrl => /school/history/historyInfo
     * @param Request $request
     * @return mixed
     */
    public function  historyInfo(Request $request){
        /**接收数据*/
        $group_code             =$request->input('group_code');
        $site_type              =$request->input('site_type');

        $input= $request->all();

        /*** 虚拟数据**/
        $input['group_code']=$group_code='group_202006221103544823194960';
        $input['site_type']=$site_type ='DOWN';

        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){

            //获取学校的经纬度
            $whereGroup=[
                ['group_code','=',$group_code],
                ['delete_flag','=','Y'],
            ];
            $school_address=SystemGroup::where($whereGroup)->select('group_name','longitude','dimensionality','company_image_url')->first();

            if($school_address){
                /** 如果有数据，则抓取出来应该出来的数据**/
                $school_address->logo=img_for($school_address->company_image_url,'one');
                if(empty($school_address->logo)){
                    $school_address->logo='https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-09-10/8b208e0a36a88a219bb0ebce0a42416b.png';
                }

                /** 车辆数据统计**/
                $where_car=[
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
                ];
                $car_info=SchoolCarInfo::where($where_car)->pluck('use_flag')->toArray();
                $count=count($car_info);                                //全部车辆
                if($count>0){
                    $yCount=array_count_values($car_info)['Y'];             //正常运行车辆数量

                }else{
                    $yCount=0;
                }
                $nCount=$count-$yCount;                             //维修车辆数量

                $data['car_count']=[
                    ['value'=>$yCount,'name'=>'运营中'. $yCount.'/'.($count),
                        'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/eaf19afb25208fecc04d192e736e347f.png'],
                    ['value'=>$nCount,'name'=>'维修中'. $nCount.'/'.($count),
                        'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/4137e6086bcf2e1202885be34149b193.png']
                ];

                /** 工作人员数量获取**/
                $where_person=[
                    ['group_code','=',$group_code],
                    ['person_type','=','care'],
                    ['delete_flag','=','Y'],
                ];

                $pcount=SchoolInfo::where($where_person)->count();

                $data['person_count']=[
                    ['value'=>$pcount,'name'=>'工作中'. $pcount.'/'.$pcount,'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/cc3103aa07c80ead5e7f20ddcbb1f758.png'],
                    ['value'=>0,'name'=>'请假中'. '0'.'/'.$pcount,'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/c2dfa5b90d547abc596bcb6a75540155.png']
                ];

                /** 学生人员数量获取**/
                $where_student=[
                    ['group_code','=',$group_code],
                    ['person_type','=','student'],
                    ['delete_flag','=','Y'],
                ];
                $assign_student_count=SchoolInfo::where($where_student)->count();           //全部学生数量

                $where_student_yi=[
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
                ];
                //已配置小孩数量
                $yi_assign_student_count=SchoolPathwayPerson::where($where_student_yi)
                    ->select('person_id')
                    ->distinct('person_id')  //指定字段查询不重复的值
                    ->count();

                $no_assign_student_count=$assign_student_count-$yi_assign_student_count;        //未配置小孩数量


                $data['student_counts']=[
                    ['value'=>$assign_student_count,'name'=>'已分配'. $yi_assign_student_count.'/'.$assign_student_count,'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/f39c427ea9dd7ee2589294bf149dfa4b.png'],
                    ['value'=>$no_assign_student_count,'name'=>'未分配'. $no_assign_student_count.'/'.$assign_student_count,'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/3ea474dd14fd10ae91344c47eab31cdd.png']
                ];

                /** 线路信息**/
                $wherePath=[
                    ['group_code','=',$group_code],
                    ['site_type','=',$site_type],
                    ['use_flag','=','Y'],
                    ['delete_flag','=','Y'],
                ];

                $pathInfo=schoolPath::with(['schoolInfo'=>function($query){
                    $query->where('delete_flag','=','Y');
                    $query->select('person_tel','self_id');
                }])
                    ->where($wherePath)
                    ->select(
                        'self_id',
                        'path_name as name',
                        'use_flag',
                        'site_type',
                        'default_car_id',
                        'default_car_brand',
                        'default_driver_id',
                        'default_driver_name',
                        'default_care_id',
                        'default_care_tel',
                        'default_care_name'
                    )
                    ->get();

                if($pathInfo->count()>0){
                    foreach ($pathInfo as $k=>$v){
                        $v->img='https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-06-15/8f9480a23a938667bc698465c7d5adc6.png';
                        $wherePathWay['path_id']=$v->self_id;
                        $wherePathWay['delete_flag']='Y';
                        $wherePathWay['site_type']=$v->site_type;
                        $v->path=SchoolPathway::where($wherePathWay)->orderBy('sort','asc')->select('pathway_name as name','longitude','dimensionality')->get();
                        $temp=SchoolPathway::where($wherePathWay)->orderBy('sort','asc')->pluck('pathway_name')->toArray();
                        $v->path_show=implode('、',$temp);
                    }
                }

                foreach($pathInfo as $kk=>$vv){
                    if($vv->path && $vv->use_flag == 'Y'){
                        foreach ($vv->path as $m=>$n){
                            $n->lnglat=[$n->longitude,$n->dimensionality];
                        }
                    }
                }

                $data['pathInfo']=$pathInfo;
                $data['school_address']=$school_address;


                $msg['code']=200;
                $msg['msg']='数据拉取成功';
                $msg['data']=$data;
                return $msg;



            }else{
                //给的公司group无效，需要跳出
                $msg['code']=301;
                $msg['msg']='拉取不到数据';
                return $msg;

            }

        }else{
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
     *
     *
     */

    /**
     * 获取线路的历史数据
     * pathUrl => /school/history/getAddress
     * @param Request $request
     * @return mixed
     */
    public function getAddress(Request $request){
        /**接收数据*/
        $group_code             =$request->input('group_code');
        $site_type              =$request->input('site_type');
        $time                   =$request->input('time');
        
        $input= $request->all();

        /*** 虚拟数据**/
        $input['group_code']    =$group_code='group_202006221103544823194960';
        $input['site_type']     =$site_type ='DOWN';
        $input['time']          =$time ='2020-09-08';


        //虚拟数据
//        $input['group_code']='group_202006221103544823194960';
//        $input['site_type']='DOWN';
//        $input['date']='2020-09-08';

        $rules=[
            'group_code'=>'required',
            'site_type'=>'required',
            'time'=>'required',
        ];

        $message=[
            'group_code.required'=>'必须选择一个公司',
            'site_type.required'=>'必须选择上或放学状态',
            'time.required'=>'必须选择日期',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){

            $wherePathWay=[
                ['group_code','=',$group_code],
                ['site_type','=',$site_type],
                ['delete_flag','=','Y'],
            ];
            $schoolPath=SchoolPath::where($wherePathWay)->select('self_id','path_name as name','site_type')->get();
            //$schoolPath=null;
            if($schoolPath && $schoolPath->count()>0){
                foreach($schoolPath as $k=>$v){
                    $carriageJson=[];
                    $temp=$this->prefix.$v->self_id.$site_type.$time;
                    $whereJson['carriage_id']=$temp;
                    $whereJson['type']='car';
                    $carriageJson=SchoolCarriageJson::where($whereJson)->select('json')->get();

                    $fhuyuty=[]; //线路经纬度的集合数组
                    $schoolPath[$k]->path=null;
                    if($carriageJson && $carriageJson->count()>0){
                        foreach ($carriageJson as $kk => $vv){
                            $temp_long=json_decode($vv->json,true);
                            $temp3=[];
                            foreach ($temp_long as $m=>$n){
                                $temp3[]=[$n['longitude'],$n['latitude']];
                            }
                            $fhuyuty=array_merge_recursive($fhuyuty,$temp3);
                        }
                        $schoolPath[$k]->path=$fhuyuty;
                    }

                    // dump($temp);
                }

                $where['delete_flag']='Y';
                $where['group_code']=$group_code;
                $school_add=SystemGroup::where($where)->select('group_name','longitude','dimensionality')->first();
                if($school_add && $school_add->count() > 0){
                    $msg['data']=$school_add;
                    foreach ($schoolPath as $k=>$v){
                        if($v->path){
                            $msg['data']->guiji=$schoolPath;
                        }
                    }
                    $msg['code']=200;
                    $msg['msg']='成功';
                    return $msg;
                }else{
                    $msg['code']=302;
                    $msg['msg']='未查询到相应信息！';
                    return $msg;
                }
            }else{
                $msg['code']=301;
                $msg['msg']='未查询到相应信息！';
                return $msg;
            }

        }else{
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
}