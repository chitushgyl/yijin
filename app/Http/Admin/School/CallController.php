<?php
namespace App\Http\Admin\School;
use Illuminate\Http\Request;
use App\Models\School\SchoolPath;
use App\Models\School\SchoolPathwayPerson;
use App\Models\School\SchoolCarriage;
use App\Models\School\SchoolCarriageInventory;
use App\Models\School\SchoolBasics;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\Group\SystemGroup;
use DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\FileController as File;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class CallController extends CommonController{
    private $prefix='car_';

    /**
     * 列表
     * pathUrl => /school/call/callList
     * @param Request $request
     * @return mixed
     */
    public function callList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /**
     * 分页
     * path  =>  /school/call/callPage
     * @param Request $request
     * @return mixed
     */
    public function callPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数


        /**接收数据*/
        $num            =$request->input('num',10);
        $page           =$request->input('page',1);

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $group_code=$request->input('group_code');

		$data_time=$request->input('search_time');
		$status=$request->input('status');
        $search=[
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            // ['type'=>'=','name'=>'group_code','value'=>'group_202006161412449505577299'],
		    ['type'=>'=','name'=>'create_date','value'=>$data_time],
			['type'=>'=','name'=>'carriage_type','value'=>$status]
        ];
        $where=get_list_where($search);
       // dd($where);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SchoolCarriage::where($where)->count(); //总的数据量

                $data['items']=SchoolCarriage::where($where)
                ->offset($firstrow)->limit($listrows)->orderBy('create_date','desc')
                ->select('group_code','group_name','path_name','create_date','self_id as carriage_id','create_date  as data','count as path_riding','carriage_type as status','path_id','create_time','carriage_status','text_info')
                ->get()->toArray();
                $data['group_show']='Y';
                break;
            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SchoolCarriage::where($where)->count(); //总的数据量

                $data['items']=SchoolCarriage::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_date','desc')
                    ->select('group_code','group_name','create_date','path_name','self_id as carriage_id','create_date as data','count as path_riding','carriage_type  as status','path_id','create_time','carriage_status','text_info')
                    ->get()->toArray();    
                $data['group_show']='N';
                break;
            case 'more':
                $data['total']=SchoolCarriage::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量

                $data['items']=SchoolCarriage::where($where)
                    ->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_date','desc')
                    ->select('group_code','group_name','path_name','create_date','self_id as carriage_id','create_date  as data','count as path_riding','carriage_type  as status','path_id','create_time','carriage_status','text_info')
                    ->get()->toArray();  
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v){

			$data['items'][$k]['text_info']=json_decode($v['text_info'],true);
            $data['items'][$k]['button_info']=$button_info;
            switch ($v['status']){
                case 'DOWN':
                    $where2['use_flag']='Y';
                    $where2['delete_flag']='Y';
                    $where2['riding_type']='yes_riding';
                    $where2['group_code']=$v['group_code'];
                    $where2['carriage_id']=$v['carriage_id'];
                    $data['items'][$k]['yes_riding']=SchoolCarriageInventory::where($where2)->select('student_id')->distinct()->get()->count();//已上车（排除一个学生多次刷上车）
                    $data['items'][$k]['up_down_status']='放学上车点名';
					$data['items'][$k]['carriage_type']='放学';
                    break;
                case 'UP':
                    $where_basic=[
                        'group_code'=>$v['group_code'],
                        'delete_flag'=>'Y',
                    ];
                    $is_call_departure=SchoolBasics::where($where_basic)->value('is_call_departure');

                    $where2['use_flag']='Y';
                    $where2['delete_flag']='Y';
                    if($is_call_departure == 'Y'){
                        $where2['riding_type']='yes_riding';
                    }else{
                        $where2['riding_type']='up_riding';
                    }


                    $where2['group_code']=$v['group_code'];
                    $where2['carriage_id']=$v['carriage_id'];
                    $data['items'][$k]['yes_riding']=SchoolCarriageInventory::where($where2)->select('student_id')->distinct()->get()->count();//已下车（排除一个学生多次刷下车）
                    $data['items'][$k]['up_down_status']='上学下车点名';
					$data['items'][$k]['carriage_type']='上学';
                    break;
            }
			
			 switch ($v['carriage_status']){
                case '1':
                    $data['items'][$k]['new_carriage_status']='未发车';
                    break;
                case '2':
                    $data['items'][$k]['new_carriage_status']='运行中';
                    break;
				case '3':
                    $data['items'][$k]['new_carriage_status']='运行结束';
                    break;
            }

            $where3['use_flag']='Y';
            $where3['delete_flag']='Y';
            $where3['riding_type']='yes_holiday';
            $where3['group_code']=$v['group_code'];
            $where3['carriage_id']=$v['carriage_id'];
            $data['items'][$k]['yes_holiday']=SchoolCarriageInventory::where($where3)->count();//已请假

            $where4['use_flag']='Y';
            $where4['delete_flag']='Y';
            $where4['riding_type']='not_riding';
            $where4['group_code']=$v['group_code'];
            $where4['carriage_id']=$v['carriage_id'];
            $data['items'][$k]['not_riding']=SchoolCarriageInventory::where($where4)->count();//未上车 

            $where5['use_flag']='Y';
            $where5['delete_flag']='Y';
            $where5['riding_type']='join_riding';
            $where5['group_code']=$v['group_code'];
            $where5['carriage_id']=$v['carriage_id'];
            $data['items'][$k]['join_riding']=SchoolCarriageInventory::where($where5)->count();//已接走
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /**
     * 线路信息
     * pathUrl => /school/call/callDetails
     * @param Request $request
     * @return mixed
     */
    public function callDetails(Request $request){

        $group_code      =$request->input('group_code');
        $up_down         =$request->get('status');

        //虚拟数据
        //$group_code='group_202006161412449505577299';
//        $up_down='UP';
        $search=[
            ['type'=>'=','name'=>'site_type','value'=>$up_down],
            ['type'=>'=','name'=>'use_flag','value'=>'Y'],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
        ];
        $where=get_list_where($search);
        $schoolPath = SchoolPath::where($where)->get(['self_id','path_name','site_type']);


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$schoolPath;
        return $msg;
    }

    /**
     *  获取点名后的数据
     * pathUrl => /school/call/callData
     * @param Request $request
     * @return mixed
     */

    public function callData(Request $request){
//        $old='46af577d';
//        $arr=str_split($old,2);
//        $temp=$arr[3].$arr[2].$arr[1].$arr[0];
//        $new=hexdec($temp);
//        dd($new);




        $up_down            =$request->input('status');
        $carriage_id            =$request->input('carriage_id');

        //虚拟数据
//        $carriage_id='car_path_202009071659191214743947DOWN2021-01-11';
        $where['use_flag']='Y';
        $where['delete_flag']='Y';
        $where['carriage_id']=$carriage_id;
        $select2=['self_id','group_code','group_name','student_id','actual_name','grade_name','class_name'];
        $select=['student_id','status'];
        $select_all=['student_id','riding_type','create_time','come',];

        $where_all=[
            'delete_flag'=>'Y',
            'carriage_id'=>$carriage_id,
        ];

        $where_up=[
            'riding_type'=>'yes_riding',
        ];

        $where_down=[
            'riding_type'=>'up_riding',
        ];
        $where_holiday=[
            'riding_type'=>'yes_holiday',
        ];
        $where_takeaway=[
            'riding_type'=>'join_riding',
        ];

        $info=SchoolCarriageInventory::with(['studentInfo' => function ($query)use($select2) {
            $query->select($select2);
        }])->with(['up' => function ($query)use($select_all,$where_up,$where_all) {
            $query->where($where_up);
            $query->where($where_all);
            $query->select($select_all);
            $query->orderBy('create_time','desc');
        }])->with(['down' => function ($query)use($select_all,$where_down,$where_all) {
            $query->where($where_down);
            $query->where($where_all);
            $query->select($select_all);
            $query->orderBy('create_time','desc');
        }])->with(['holiday' => function ($query)use($select_all,$where_holiday,$where_all) {
            $query->where($where_holiday);
            $query->where($where_all);
            $query->select($select_all);
            $query->orderBy('create_time','desc');
        }])->with(['takeaway' => function ($query)use($select_all,$where_takeaway,$where_all) {
                $query->where($where_takeaway);
                $query->where($where_all);
                $query->select($select_all);
                $query->orderBy('create_time','desc');
            }])
        ->where($where)
            ->select($select)->distinct('student_id')
            ->get();

        if($info){
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$info;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有数据";
            return $msg;
        }
    }



    public function callData_备份(Request $request){
        // $date               =$request->input('date',date('Y-m-d',time()));
        //$group_code         =$request->input('group_code');
        //$path_id        =$request->input('path_id');
        $up_down            =$request->input('status');
        $carriage_id            =$request->input('carriage_id');


        //虚拟数据
        //$group_code='group_202006161412449505577299';
//        $up_down='DOWN';
        //$path_id='path_202009151623309257223566';
        //$date='2020-11-04';

        //$carriage_id=$this->prefix.$path_id.$up_down.$date;
        //$search=[
        //    ['type'=>'=','name'=>'use_flag','value'=>'Y'],
        //    ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        //    ['type'=>'=','name'=>'group_code','value'=>$group_code],
        //    ['type'=>'=','name'=>'carriage_id','value'=>$carriage_id],
        //];
        //$where=get_list_where($search);

        $where['use_flag']='Y';
        $where['delete_flag']='Y';
        $where['carriage_id']=$carriage_id;
        $temp_items1=SchoolCarriageInventory::where($where)->where('come','=','card')
            ->select('self_id','group_code','group_name','student_id','actual_name','grade_name','class_name','riding_type','create_time','create_user_name','come')
            ->get()->toArray();
        if(count($temp_items1)==0){
            $temp_items1=SchoolCarriageInventory::where($where)->where('come','=','paper')
                ->select('self_id','group_code','group_name','student_id','actual_name','grade_name','class_name','riding_type','create_time','create_user_name','come')
                ->get()->toArray();
        }
        $items=$temp_items1;
        switch ($up_down){
            case 'DOWN':
                $type='shang';
                break;
            case 'UP':
                $type='fang';
                break;
        }


        if($items){
            //$result['yes_riding']= [];
            // $result['not_riding']= [];
            // $result['dow_riding']= [];
            // $result['up_riding']= [];
            // $result['yes_holiday']= [];
            // foreach ($items as $key => $info) {
            // $result[$info['riding_type']][] = $info;
            // }
            foreach ($items as $key => $info) {
                switch($info['riding_type']){

                    case 'yes_riding':
                        $items[$key]['show_riding_type']='已上车';
                        break;
                    case 'not_riding':
                        $items[$key]['show_riding_type']='未上车';
                        break;
                    case 'dow_riding':
                        $items[$key]['show_riding_type']='未下车';
                        break;
                    case 'up_riding':
                        $items[$key]['show_riding_type']='已下车';
                        break;
                    case 'yes_holiday':
                        $items[$key]['show_riding_type']='已请假';
                        break;
                    case 'join_riding':
                        $items[$key]['show_riding_type']='接走';
                        break;
                    default:
                        $items[$key]['show_riding_type']='';
                        break;
                }
            }
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$items;
            $msg['type']=$type;
            //dd($msg);
            return $msg;

        }else{
            $msg['code']=200;
            $msg['msg']="没有数据";
            $msg['type']=$type;
            return $msg;
        }
    }

    /***    excel表格导出数据     /school/call/callExcel
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function callExcel(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();

        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $start_time     =$request->input('start_time');
        $end_time     =$request->input('end_time');

        /** 虚拟数据*/
        //$group_code     =$input['group_code']   ='group_202006221103544823194960';

        $rules=[
            'group_code'=>'required',
            'start_time'=>'required',
            'end_time'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择公司',
            'start_time.required'=>'请选择开始时间',
            'end_time.required'=>'请选择截止时间',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /** 下面开始执行导出逻辑**/
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            $search=[
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];

            //做时间判断，开始时间必须小于结束时间
            $pan=$start_time<=>$end_time;
            if($pan==1 ){
                $msg['code']=301;
                $msg['msg']="开始时间必须小于结束时间 ";
                return $msg;
            }
            $where=get_list_where($search);

            $select=['self_id','group_code','group_name','create_time','actual_name','grade_name','class_name','path_name','pathway_name','riding_type','status','create_user_name'];
            $call_info=DB::table('school_carriage_inventory')->where($where)->whereBetween('data',[$start_time,$end_time])
                ->select($select)->get();

            if($call_info){
                //设置表头
                $row = [[
                    "id"=>'ID',
                    "group_name"=>'学校名称',
                    "path_name"=>'线路名称',
                    "pathway_name"=>'站点名称',
                    "status"=>'点名状态',
                    "create_user_name"=>'点名老师',
                    "create_time"=>'点名时间',
                    "actual_name"=>'学生姓名',
                    "grade_name"=>'学生年级',
                    "class_name"=>'学生班级',
                    "riding_type"=>'学生状态',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($call_info as $k=>$v){

                    $data_execl[$k]['id']               =($k+1);//id
                    $data_execl[$k]['group_name']       =$v->group_name;
                    $data_execl[$k]['path_name']       =$v->path_name;
                    $data_execl[$k]['pathway_name']      =$v->pathway_name;
                    switch ($v->status){
                        case 'DOWN':
                            $data_execl[$k]['status']='放学上车点名';
                            break;
                        case 'UP':
                            $data_execl[$k]['status']='上学下车点名';
                            break;
                    }
                    $data_execl[$k]['create_user_name']      =$v->create_user_name;
                    $data_execl[$k]['create_time']           =$v->create_time;
                    $data_execl[$k]['actual_name']           =$v->actual_name;
                    $data_execl[$k]['grade_name']           =$v->grade_name;
                    $data_execl[$k]['class_name']           =$v->class_name;
                    switch($v->riding_type){
                        case 'yes_riding':
                            $data_execl[$k]['riding_type']='已上车';
                            break;
                        case 'not_riding':
                            $data_execl[$k]['riding_type']='未上车';
                            break;
                        case 'dow_riding':
                            $data_execl[$k]['riding_type']='未下车';
                            break;
                        case 'up_riding':
                            $data_execl[$k]['riding_type']='已下车';
                            break;
                        case 'yes_holiday':
                            $data_execl[$k]['riding_type']='已请假';
                            break;
						case 'join_riding':
                            $data_execl[$k]['riding_type']='接走';
                            break;
                        default:
                            $data_execl[$k]['riding_type']='';
                            break;
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


}