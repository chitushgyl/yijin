<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/7/22
 * Time: 17:08
 */
namespace App\Http\Admin\School;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\RedisController as RedisServer;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;


use App\Models\School\SchoolCarInfo;
use App\Models\School\SchoolInfo;
use App\Models\School\SchoolPath;
use App\Models\School\SchoolCarriage;
use App\Models\School\SchoolPathway;
class RealController extends CommonController{
    private $prefix='car_';
    private $postfix='ss';

    /**大屏数据展示    左侧数据统计栏（未大屏之前）
     * pathUrl => /school/real/real_line
     * @return mixed
     */
    public function real_line(Request $request){
        /** 接收数据*/
        $group_code         =$request->input('group_code');
        $dateStatus         =date_time();
        $input              =$request->all();
        /****虚拟数据***/
        //$input['group_code']=$group_code='group_202006221103544823194960';

        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //dump($dateStatus);

            //dump($group_code);
            //获取学校的经纬度
            $where_group=[
                ['group_code','=',$group_code],
                ['delete_flag','=','Y'],
            ];
            $data['school_address']=DB::table('system_group')->where($where_group)->select('group_name','longitude','dimensionality')->first();

            //校车数量获取
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


            //工作人员数量获取
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

            //学生数据获取        如何去拉，是不是可以考虑查询，主表是info，查线路表，只要线路表中有数据的，就是已安排的
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
            $yi_assign_student_count=DB::table('school_pathway_person')
                ->where($where_student_yi)
                ->select('person_id')
                ->distinct('person_id')  //指定字段查询不重复的值
                ->count();

            $no_assign_student_count=$assign_student_count-$yi_assign_student_count;        //未配置小孩数量


            $data['student_counts']=[
                ['value'=>$assign_student_count,'name'=>'已分配'. $yi_assign_student_count.'/'.$assign_student_count,'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/f39c427ea9dd7ee2589294bf149dfa4b.png'],
                ['value'=>$no_assign_student_count,'name'=>'未分配'. $no_assign_student_count.'/'.$assign_student_count,'icon'=>'image://https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-06/3ea474dd14fd10ae91344c47eab31cdd.png']
            ];



            //线路信息
            $where_path=[
                ['a.use_flag','=','Y'],
                ['a.delete_flag','=','Y'],
                ['a.group_code','=',$group_code],
                ['a.site_type','=',$dateStatus['status']],
            ];
            $data['path_info']=DB::table('school_path as a')
                ->join('school_info  as b',function($join){
                    $join->on('a.default_care_id','=','b.self_id');
                }, null,null,'left')
                ->where($where_path)
                ->select(
                    'a.self_id',
                    'a.path_name as name',
                    'a.use_flag',
                    'a.default_car_id',
                    'a.default_car_brand',
                    'a.default_driver_id',
                    'a.default_driver_name',
                    'a.default_care_id',
                    'a.default_care_tel',
                    'a.default_care_name',
                    'b.person_tel'
                )
                ->get()
                ->toArray();

            foreach ($data['path_info'] as $k=>$v){
                $v->status=null;
                $v->img='https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-06-15/8f9480a23a938667bc698465c7d5adc6.png';
            }


            //dd($data);


            $msg['code']=200;
            $msg['msg']="成功";
            $msg['data']=$data;
            return $msg;




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
     * 获取大屏的数据  地图中的汇点数据
     * pathUrl => /school/real/real_info
     * @return mixed
     */
    public function real_info(Request $request,RedisServer $redisServer){
        /** 接收数据*/
        $group_code             =$request->input('group_code');
        $dateStatus             =date_time();
        $now_time               =date('Y-m-d H:i:s',time());
        $input                  =$request->all();
        /****虚拟数据***/
        ///$input['group_code']    =$group_code='group_202006221103544823194960';

        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //dump($group_code);
            $data['show']='tongji';
            //dump($data);
            //抓取所有的车辆出来
            $where_car=[
                ['a.group_code','=',$group_code],
                ['a.delete_flag','=','Y'],
                ['a.use_flag','=','Y'],
            ];

            $data['car_info']=DB::table('school_car_info as a')
                ->join('school_hardware  as b',function($join){
                    $join->on('a.self_id','=','b.car_id');
                }, null,null,'left')
                ->where($where_car)
                ->select('a.self_id','b.mac_address')
                ->get();

            foreach ($data['car_info'] as $k => $v){
                $v->real_longitude  =null;
                $v->real_longitude  =null;
                $v->path_name       =null;
                $v->site_type       =null;
                $v->carriage_id     =null;
                //从redis中拉取线路信息出来，然后做大屏的展示
                if($v->mac_address){
                    $carriage_id=$redisServer->get($v->mac_address,'carriage');
                    if($carriage_id){
                        $path=$redisServer->get($carriage_id,'carriage');
                        if($path){
                            $abc=json_decode($path,true);
                            if($abc['carriage_status'] == 2 ){
                                $v->real_longitude  =$abc['real_longitude']??'';
                                $v->real_latitude   =$abc['real_latitude']??'';
                                $v->path_name       =$abc['path_name'];
                                $v->site_type       =$abc['status'];
                                $v->carriage_id     =$abc['carriage_id'];
                                $v->icon            ='https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-08-10/e8dd359bdd0937fab5df328a83141e28.png';
                                //只要有一个有运输的，其实就应该给到时线路
                                $data['show']='xianlu';
                            }
                            //dump($abc);
                        }
                    }
                }
            }

            $data['time']=date('H:i:s',time());

            $data['show']='tongji';
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;

        }else{
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;

            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.":".$v."\r\n";
            }
            return $msg;
        }
    }

    /**
     * 左侧线路详细信息板块
     * pathUrl => /school/real/real_pathway
     * @return mixed
     */
    public function real_pathway(Request $request,RedisServer $redisServer){
        $dateStatus         =date_time();
        /** 接收数据*/
        $path_id             =$request->input('path_id');

        /****虚拟数据***/
        //$path_id             ='path_20200906110504999594641';

        $where_path=[
            ['self_id','=',$path_id],
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
        ];

        $path_info=SchoolPath::where($where_path)->select('site_type','group_code')->first();
        if($path_info){
            $carriage_id            =$this->prefix.$path_id.$path_info->site_type.$dateStatus['date'];
            $carriage_info           =$redisServer->get($carriage_id,'carriage');

            $data['carriage_info']   =json_decode($carriage_info,true);

            //还要通过数据拉一个图标出来
            $where_group=[
                ['group_code','=',$path_info->group_code],
                ['delete_flag','=','Y'],
            ];

            $company_image_url=DB::table('system_group')->where($where_group)->value('company_image_url');
            $image_url          = img_for($company_image_url,'one');     //如果没有图片，则给一个默认的大鼻子校车的图标
            /** 如果这个公司没有设置logo，则使用大鼻子校车的logo**/
            $data['company_image_url']=$image_url??config('aliyun.group')['school_img'];


            $msg['code'] = 200;
            $msg['data'] = $data;
            $msg['msg'] = "数据拉取成功";
            return $msg;
        }else{
            $msg['code'] = 302;
            $msg['msg'] = "查询不到数据";
            return $msg;
        }

    }

    /**
     * 大屏 最后的统计数据展示
     * pathUrl => /school/real/real_count
     * @return mixed
     */
    public function real_count(Request $request,RedisServer $redisServer){

        $group_code             =$request->input('group_code');
        $datetime               = self::datetime();
        $status                 =$datetime['status'];
        $input                  =$request->all();
        /****虚拟数据*/
      //  $input['group_code']    =$group_code    ='group_202006221103544823194960';
//        $input['status']        =$status='UP';

        $rules=[
            'group_code'=>'required',
            //'status'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
           // 'status.required'=>'必须传递一个上学放学',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //还要通过数据拉一个图标出来
            $where_group=[
                ['group_code','=',$group_code],
                ['delete_flag','=','Y'],
            ];

            $company_image_url=DB::table('system_group')->where($where_group)->value('company_image_url');
            $company_image_url = img_for($company_image_url,'one');


            /** 安全行驶天数及里程***/
            $where4=[
                ['delete_flag','=','Y'],
                ['group_code','=',$group_code],
                ['carriage_status','>',1]
            ];
            $leftCount=SchoolCarriage::where($where4)->sum('count');
            $number=str_split($leftCount);


            $where5=[
                ['delete_flag','=','Y'],
                ['group_code','=',$group_code],
                ['carriage_status','>',1]
            ];
            $day=SchoolCarriage::where($where5)->groupBy('create_date')->pluck('create_date');
            $dayNumber=str_split($day->count());

            $data['allinfo']=[
                ['name'=>'安全行驶天数','num'=>$dayNumber],
                ['name'=>'共计承载人次','num'=>$number],
            ];

            /** 安全行驶天数及里程 结束***/


            /*** 单日数据统计
             *    1，查询出线路的总数量，如果是上午，则加条件，上午，如果是下午，则出全部的数据
             *      2，查询出数据库中  Carriage  表中，所有的学生及线路的统计，需要根据状态划分数据
             *
             **/
            if($status == 'UP'){
                $where2=[
                    ['use_flag','=','Y'],
                    ['delete_flag','=','Y'],
                    ['group_code','=',$group_code],
                    ['site_type','=',$status],
                ];
            }else{
                $where2=[
                    ['use_flag','=','Y'],
                    ['delete_flag','=','Y'],
                    ['group_code','=',$group_code],
                ];
            }

            $path_count=DB::table('school_path')->where($where2)->count();                  //单日车辆总数量


            //查询出运输数据中每个值是多少
            $carriage_where=[
                ['group_code','=',$group_code],
                ['create_date','=',$datetime['date']],
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];
//            dump($carriage_where);
            $carriage_number = DB::table('school_carriage')
                ->select(DB::raw('count(*) as carriage_status_count, carriage_status'))
                ->where($carriage_where)
                ->groupBy('carriage_status')
                ->get()->toArray();
            //看看1,2，3分别有多少
            $yichang=0;
            foreach ($carriage_number as $k => $v){
                if($v-> carriage_status == 1 || $v-> carriage_status == 2){
                    $yichang +=$v-> carriage_status_count;
                }
            }

            /***乘坐人数***/
            $schoolSumCount = DB::table('school_carriage')->where($carriage_where)->sum('count');


            $data['count']=[
                ['img'=>$company_image_url,'text'=>'当日线路趟次','num'=>$path_count],
                ['img'=>$company_image_url,'text'=>'当日承载人次','num'=>$schoolSumCount],
                ['img'=>$company_image_url,'text'=>'当日线路异常次数','num'=>$yichang]
            ];


            /** 线路是否异常的情况**/
            $where_path_info=[
                ['group_code','=',$group_code],
                ['site_type','=',$status],
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];

            $path_info=SchoolPath::with(['schoolPathway' => function($query) {
                $query->select('self_id','sort','pathway_name','path_id');
                $query->orderBy('sort','asc');
            }]) ->where($where_path_info)->select('self_id','path_name','group_name','site_type')->get();

            //dump($data['path_info']->school_pathway);


          // dump($path_info->toArray());
            $data2=[];
            foreach ($path_info as $k => $v){
                /**   定义几个变量        ,站点大于0的则需要发车，其他的不需要发车
                 *      线路方向      direction
                 * ，    异常站点，   error
                 *       行驶距离，  distance
                 *      运行时间，   duration
                 *      承载人数    student_count
                 */
                $data2[$k]['path_name']        =$v->path_name;
                $data2[$k]['direction']       ='无站点，不发车';
                $data2[$k]['error']           =null;
                $data2[$k]['distance']        =null;
                $data2[$k]['duration']        =null;
                $data2[$k]['student_count']   =null;
                //初始化一下
                $count=count($v->schoolPathway);
//dump($count);
                if($count>0){
                    $data2[$k]['direction']       =$v->schoolPathway[0]->pathway_name.'->'.$v->schoolPathway[$count-1]->pathway_name;
                    $path=$redisServer->get($this->prefix.$v->self_id.$status.$datetime['date'],'carriage');
    //dump($path);
                    if($path){
                        $pathInfo=json_decode($path);

                        switch ($pathInfo->carriage_status){
                            case '1':
                                $data2[$k]['error']           ='未发车';
                                $data2[$k]['distance']        =null;
                                $data2[$k]['duration']        =null;
                                $data2[$k]['student_count']   =null;
                                break;
                            case '2':
                                $data2[$k]['error']           ='第'.($pathInfo->next+1).'站 '.$pathInfo->school_pathway[$pathInfo->next]->pathway_name;
                                $data2[$k]['distance']        =$pathInfo->distance;
                                $data2[$k]['duration']        =$pathInfo->duration;
                                $data2[$k]['student_count']   =$pathInfo->student_count;
                                break;
                            case '3':
                                $data2[$k]['error']           =null;
                                $data2[$k]['distance']        =$pathInfo->distance;
                                $data2[$k]['duration']        =$pathInfo->duration;
                                $data2[$k]['student_count']   =$pathInfo->student_count;
                                break;
                        }
                    }else{
                        $data2[$k]['error']           ='未发车';
                        $data2[$k]['distance']        =null;
                        $data2[$k]['duration']        =null;
                        $data2[$k]['student_count']   =null;
                    }
                }
            }

            //dump($data2);
            $valies=[];
            foreach(array_chunk($data2, 8) as $key=>$values){
                $valies[$key]['info']=$values;
            }
            $data['path_info']=$valies;

            $msg['code'] = 200;
            $msg['msg'] = '成功获取';
            $msg['data']=$data;
            //dd($msg);
            return $msg;
            //dump($yichang);

           // dd($msg);
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





    private static function datetime(){
        $datetime=date('Y-m-d H:i:s',time());
        //$datetime='2020-09-06 10:01:00';
        list($date,$time)=explode(' ',$datetime);

        //上学时间段
        $upStartTime='00:01:00';
        $upEndTime='14:50:00';

        //放学时间段
        $downStartTime='14:55:00';
        $downEndTime='23:00:00';

        if($time>=$upStartTime &&  $time<=$upEndTime){
            $status='UP';
        }else if($time>=$downStartTime &&  $time<=$downEndTime){
            $status='DOWN';
        }else{
            $status='OUT';
        }

        return ['dateStatus'=>$status.$date,'status'=>$status,'date'=>$date];
    }
}