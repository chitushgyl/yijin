<?php
namespace App\Http\Admin\School;
use App\Http\Controllers\CommonController;
use App\Models\School\SchoolHoliday;
use App\Models\School\SchoolHolidayPerson;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\OSS;
use App\Tools\Export;
class HolidayController extends CommonController{
    /***    请假头部      /school/holidy/holidyList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  holidyList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        // dd($msg);
        return $msg;
    }


    /***    请假分页数据      /school/holidy/holidyPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function holidyPage(Request $request){
		/** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
		
        $num            =$request->input('num',10);
        $page           =$request->input('page',1);
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        //$holidayType='DOWN';
        $holidayType		=$request->input('holiday_type');
		$group_name         =$request->input('group_name');
        $class_name         =$request->input('class_name');
		$person_name        =$request->input('person_name');
        $grade_name         =$request->input('grade_name');
		
        //$where["delete_flag"] ='Y';


        $search=[
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
            ['type'=>'like','name'=>'class_name','value'=>$class_name],
            ['type'=>'like','name'=>'person_name','value'=>$person_name],
            ['type'=>'like','name'=>'grade_name','value'=>$grade_name],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);
		
		$select=['self_id','holiday_id','person_name','grade_name','class_name',
                'group_code','group_name','create_time','use_flag','holiday_type',
            'cancel_time','cancel_user_name','cancel_token_img','cancel_reason'];

        $schoolHolidaySelect=['self_id','reason','create_user_name','create_token_img'];
		switch ($group_info['group_id']){
            case 'all':
                $data['total']=SchoolHolidayPerson::where($where)->count();
				$data['items']=SchoolHolidayPerson::with(['schoolHoliday' => function ($query)use($schoolHolidaySelect) {
						$query->select($schoolHolidaySelect);
					}])->where($where)
					->offset($firstrow)->limit($listrows)
					->select($select)
					->get();


                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SchoolHolidayPerson::where($where)->count();
                $data['items']=SchoolHolidayPerson::with(['schoolHoliday' => function ($query)use($schoolHolidaySelect) {
                    $query->select($schoolHolidaySelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)
                    ->select($select)
                    ->get();


                break;

            case 'more':
                $data['total']=SchoolHolidayPerson::where($where)->whereIn('group_code',$group_info['group_code'])->count();
                $data['items']=SchoolHolidayPerson::with(['schoolHoliday' => function ($query)use($schoolHolidaySelect) {
                    $query->select($schoolHolidaySelect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)
                    ->select($select)
                    ->get();
                break;
        }
		
		
		//dump($data['items']->toArray());

		foreach ($data['items'] as $k=>$v){
            switch ($v->holiday_type){
                case 'UP':
                    $v->holiday_type='上学';
                    break;
                case 'DOWN':
                    $v->holiday_type='放学';
                    break;
            }

            $v->button_info=$button_info;
        }

        //dd($data['items']->toArray());
			
			
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
       
        return $msg;
    }


    /***    请假导出数据      /school/holidy/holidayExcel
     *      前端传递必须参数：group_code
     *      前端传递非必须参数：日期区间
     */
    public function holidayExcel(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());

        /** 接收数据*/
        $group_code             =$request->input('group_code');
        $start_date             =$request->input('start_date');
        $end_date         	    =$request->input('end_date');

        /** 虚拟数据*/
        $group_code='group_202008201434351063505955';
        $start_date             ='2020-08-26';
        $end_date         	    ='2020-12-11';

        //$holidayType = null;
        $search=[
            ['type'=>'>=','name'=>'holiday_date','value'=>$start_date],
            ['type'=>'<=','name'=>'holiday_date','value'=>$end_date],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];

        $where=get_list_where($search);

        dump($where);
        $schoolHoliday=SchoolHolidayPerson::where($where)
            ->select('self_id','holiday_id','person_name','grade_name','class_name',
                'group_code','group_name','create_time','use_flag','holiday_type',
                'cancel_time','cancel_user_name','cancel_token_img','cancel_reason')
            ->get();


       // dd($schoolHoliday->toArray());



        $data=[];
        foreach ($schoolHoliday as $k=>$v){
            switch ($v->holiday_type){
                case 'UP':
                    $v->holiday_type='上学';
                    break;
                case 'DOWN':
                    $v->holiday_type='放学';
                    break;
            }

            $data[$k]['id']=($k+1);//id
            $data[$k]['person_name']=$v->person_name;//学生姓名
            $data[$k]['grade_name']=$v->grade_name;//年级
            $data[$k]['class_name']=$v->class_name;//班级
            $data[$k]['group_name']=$v->group_name;//学校名称
            $data[$k]['holiday_date']=$v->holiday_date;//请假日期
            $data[$k]['create_time']=$v->create_time;//请假提交时间
            $data[$k]['text_type']=$v->holiday_type;//上/放学状态
        }

        //设置表头
        $row = [[
            "id"=>'ID',
            "person_name"=>'学生姓名',
            "grade_name"=>'年级',
            "class_name"=>'班级',
            "group_name"=>'学校名称',
            "holiday_date"=>'请假日期',
            "create_time"=>'请假提交时间',
            "text_type"=>'状态',
        ]];

        //return Excel::download(new Export($row,$data),date('Y:m:d ') . '.xlsx');
        $format='xlsx';
        $table_name=generate_id('execl_');
        $tmppath=date('Y-m-d',time()).'/'.$table_name.'.'.$format;
        $bucket_name = config('aliyun.oss.bucket');
        $pathName = 'execl/'.date('Y-m-d',time()).'/'.$table_name.'.'.$format;//上传文件保存的路径
        $store=Excel::store(new Export($row,$data),$tmppath);
        if($store){
            $tmppath='uploads/'.$tmppath;
            OSS::publicUpload($bucket_name,$pathName,$tmppath);
            //获取上传图片的Url链接
            $filepath = OSS::getPublicObjectURL($bucket_name,$pathName);
            $msg['code'] = 200;
            $msg['msg'] = '导出成功';
            $msg['data'] = ['url' => $filepath];
            return $msg;
        }else{
            $msg['code'] = 300;
            $msg['msg'] = '文件生成失败';
            return $msg;
        }
    }


}