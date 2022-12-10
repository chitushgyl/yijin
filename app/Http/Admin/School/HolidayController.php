<?php
namespace App\Http\Admin\School;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\School\SchoolHoliday;
use App\Models\School\SchoolHolidayPerson;
use Illuminate\Http\Request;
use App\Models\Group\SystemGroup;
//use App\Services\OSS;


use App\Http\Controllers\FileController as File;

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
        $holidayType		=$request->input('holiday_type');//请假类型
		$group_name         =$request->input('group_name');//学校名称
        $class_name         =$request->input('class_name');//班级
		$person_name        =$request->input('person_name');//学生姓名
        $grade_name         =$request->input('grade_name');//年级
		$holiday_date         =$request->input('holiday_date');//请假时间


        $search=[
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
            ['type'=>'like','name'=>'class_name','value'=>$class_name],
            ['type'=>'like','name'=>'person_name','value'=>$person_name],
            ['type'=>'like','name'=>'holiday_type','value'=>$holidayType],
			['type'=>'like','name'=>'grade_name','value'=>$grade_name],
			['type'=>'=','name'=>'holiday_date','value'=>$holiday_date],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);
		
		$select=['self_id','holiday_id','person_name','grade_name','class_name','holiday_date',
                'group_code','group_name','create_time','use_flag','holiday_type',
            'cancel_time','cancel_user_name','cancel_token_img','cancel_reason'];

        $schoolHolidaySelect=['self_id','reason','create_user_name','create_token_img'];
		switch ($group_info['group_id']){
            case 'all':
                $data['total']=SchoolHolidayPerson::where($where)->count();
				$data['items']=SchoolHolidayPerson::with(['schoolHoliday' => function ($query)use($schoolHolidaySelect) {
						$query->select($schoolHolidaySelect);
					}])->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
					->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SchoolHolidayPerson::where($where)->count();
                $data['items']=SchoolHolidayPerson::with(['schoolHoliday' => function ($query)use($schoolHolidaySelect) {
                    $query->select($schoolHolidaySelect);
                }])->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SchoolHolidayPerson::where($where)->whereIn('group_code',$group_info['group_code'])->count();
                $data['items']=SchoolHolidayPerson::with(['schoolHoliday' => function ($query)use($schoolHolidaySelect) {
                    $query->select($schoolHolidaySelect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
		
		
		//dump($data['items']->toArray())1;

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

    /***    老师取消请假      /school/holidy/cancelHolidy
     *      前端传递必须参数：group_code
     *      前端传递非必须参数：日期区间
     */
    public function cancelHolidy(Request $request){
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());

        /**接收数据*/
        $self_id        =$request->input('self_id');
        $cancel_reason  =$request->input('cancel_reason');

        /**虚拟数据***/
        //$self_id        =$input['self_id']='h_202007241920244602792794';
        //$cancel_reason  =$input['cancel_reason']='实际到校了，爸爸送过来的';
        $input=$request->input();
        $rules=[
            'self_id'=>'required',
            'cancel_reason'=>'required',
        ];
        $message=[
            'self_id.required'=>'请假ID不能为空',
            'cancel_reason.required'=>'取消请假原因必须填写',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $holiday_where=[
                ['self_id','=',$self_id],
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];

            $old_info=SchoolHolidayPerson::where($holiday_where)->first();
            if($old_info){
                /***  做一套数据出来  看看  **/
                $data['use_flag']               ='N';
                $data['cancel_user_id']         =$user_info->admin_id;
                $data['cancel_time']            =$now_time;
                $data['cancel_user_name']       =$user_info->name;
                $data['update_time']            =$now_time;
                $data['cancel_reason']          =$cancel_reason;

                $where['self_id']       =$self_id;
                $id=SchoolHolidayPerson::where($where)->update($data);

                if($id){
                    $msg['code']=200;
                    $msg['msg']='操作成功';
                    return $msg;
                }else{
                    $msg['code']=301;
                    $msg['msg']='操作失败';
                    return $msg;
                }
            }else{
                $msg['code']=302;
                $msg['msg']='未查询该请假信息';
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



    /***    请假导出数据      /school/holidy/holidayExcel
     *      前端传递必须参数：group_code
     *      前端传递非必须参数：日期区间
     */
    public function holidayExcel(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
		$input          =$request->all();
		//dump($input);
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $start         	=$request->input('start');                  //开始时间
        $end            =$request->input('end');                    //结束时间
        /** 虚拟数据*/
        //$group_code     =$input['group_code']   ='group_202006221103544823194960';
        //$start          =$input['start']        ='2020-08-26';
        //$end            =$input['end']          ='2020-11-11';

        $rules=[
            'group_code'=>'required',
            'start'=>'required',
            'end'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择公司',
            'start.required'=>'开始时间不能为空',
            'end.required'=>'结束时间不能为空',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            /** 第二步效验   开始时间和结束时间不能相差3个月以上，也就是最多可以拉取3个月的数据    假设3个月最多为92 天***/
            $second1 = strtotime($start);
            $second2 = strtotime($end);
            $tian=($second2 - $second1) / 86400;
            if($tian>92){
                $msg['code']=301;
                $msg['msg']="最多导出3个月，请您重新选择";
                return $msg;
            }


            /** 下面开始执行导出逻辑**/
            $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');
            $search=[
                ['type'=>'>=','name'=>'holiday_date','value'=>$start],
                ['type'=>'<=','name'=>'holiday_date','value'=>$end],
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];

            $where=get_list_where($search);

            $schoolHoliday=SchoolHolidayPerson::where($where)
                ->select('self_id','holiday_id','person_name','grade_name','class_name',
                    'group_code','group_name','create_time','use_flag','holiday_type','holiday_date',
                    'cancel_time','cancel_user_name','cancel_reason')
                ->get();
            if($schoolHoliday){
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
                    "holiday_cancel"=>'是否取消请假',
                ]];


                /** 现在根据查询到的数据去做一个导出的数据**/
                $data_execl=[];
                foreach ($schoolHoliday as $k=>$v){
                    switch ($v->holiday_type){
                        case 'UP':
                            if($v->use_flag == 'N'){
                                $v->holiday_type='上学(已取消)';
                            }else{
                                $v->holiday_type='上学';
                            }

                            break;
                        case 'DOWN':
                            if($v->use_flag == 'N'){
                                $v->holiday_type='放学(已取消)';
                            }else{
                                $v->holiday_type='上学';
                            }
                            break;
                    }

                    $data_execl[$k]['id']             =($k+1);//id
                    $data_execl[$k]['person_name']    =$v->person_name;               //学生姓名
                    $data_execl[$k]['grade_name']     =$v->grade_name;                //年级
                    $data_execl[$k]['class_name']     =$v->class_name;                //班级
                    $data_execl[$k]['group_name']     =$v->group_name;                //学校名称
                    $data_execl[$k]['holiday_date']   =$v->holiday_date;              //请假日期
                    $data_execl[$k]['create_time']    =$v->create_time;               //请假提交时间
                    $data_execl[$k]['text_type']      =$v->holiday_type;              //上/放学状态
                    if($v->use_flag == 'N'){
                        $data_execl[$k]['holiday_cancel'] =$v->cancel_user_name.$v->cancel_time.'：'.$v->cancel_reason;                      //是否取消请假
                    }else{
                        $data_execl[$k]['holiday_cancel'] =null;                      //是否取消请假
                    }

                }

                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time,$start,$end);

                return $msg;

            }else{
                $msg['code']=302;
                $msg['msg']="您选择的时间段没有数据可以导出";
                return $msg;
            }

        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code']=305;
            return $msg;
        }

    }


}