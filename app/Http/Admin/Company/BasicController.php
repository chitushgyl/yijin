<?php
namespace App\Http\Admin\Company;
use App\Models\School\SchoolInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\RedisController as RedisServer;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroup;
use App\Models\School\SchoolBasics;

class BasicController  extends CommonController{
    /***    基础头部      /school/basic/basicList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  basicList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    基础信息分页      /school/basic/basicPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function basicPage(Request $request){
        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
        $group_name         = $request->get('groupName');//接收中间件产生的参数
        
        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');
        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
        ];
        $where=get_list_where($search);

        $select=['self_id','group_code','group_name','use_flag'];
        $schoolBasicsSelect=[
		'self_id',
		'self_id',
		'group_code',
		'group_name',
		'money_platform_flag',
		'money_cost_way',
		'money_refund_flag',
        'mini_flag',
        'shenqing',
		'auto_depart_flag',
        'auto_depart',
		'auto_depart_ahead',
		'auto_departlater',
		'arrive_skip_flag',
		'arrive_rail_type',
		'arrive_distance',
		'arrive_distance_distance',
		'arrive_distance_tolerance',
		'depart_push_care_flag',
		'depart_push_teacher_flag',
		'depart_push_patriarch_flag',
		'depart_push_patriarch',
		'arrive_push_care_flag',
		'arrive_push_teacher_flag',
		'arrive_push_patriarch_flag',
		'arrive_push_patriarch',
		'end_push_care_flag',
		'end_push_teacher_flag',
		'end_push_patriarch_flag',
		'end_push_patriarch',
		'go_push_care_flag',
		'go_push_teacher_flag',
		'go_push_patriarch_flag',
		'go_push_patriarch',
		'holiday_push_care_flag',
		'holiday_push_teacher_flag',
		'holiday_push_patriarch_flag',
		'holiday_push_patriarch'
		];

		//$schoolBasicsSelect=['group_code','cost_way'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroup::where($where)->count(); //总的数据量1
                $data['items']=SystemGroup::with(['schoolBasics' => function($query)use($schoolBasicsSelect) {
                    $query->select($schoolBasicsSelect);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroup::where($where)->count(); //总的数据量1
                $data['items']=SystemGroup::with(['schoolBasics' => function($query)use($schoolBasicsSelect) {
                    $query->select($schoolBasicsSelect);
                }]) ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroup::with(['schoolBasics' => function($query)use($schoolBasicsSelect) {
                    $query->select($schoolBasicsSelect);
                }]) ->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k => $v){
			if($v->schoolBasics){
                $v->schoolBasics->patriarch=json_decode($v->schoolBasics->patriarch);
            }

            $v->button_info = $button_info;
        }

        //dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }


    /***    基础拉取数据      /school/basic/createBasic
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  createBasic(Request $request){
        /** 接收数据*/
        $group_code=$request->input('self_id');
        $data['group_code'] =$group_code;
        //$group_code='1234';
//dd($group_code);
        $where=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
        ];
		
        $group_name=SystemGroup::where($where)->value('group_name');
		
		if(empty($group_name)){
			$msg['code']=301;
			$msg['msg']="获取不到数据";
			return $msg;
		}
        $data['basic_info']=SchoolBasics::where($where)->select(
		'self_id',
		'group_code',
		'group_name',
		'money_platform_flag',
		'money_cost_way',
		'money_refund_flag',
        'mini_flag',
        'shenqing',
		'auto_depart_flag',
        'auto_depart',
		'auto_depart_ahead',
		'auto_departlater',
		'arrive_skip_flag',
		'arrive_rail_type',
		'arrive_distance',
		'arrive_distance_distance',
		'arrive_distance_tolerance',
		'depart_push_care_flag',
		'depart_push_teacher_flag',
		'depart_push_patriarch_flag',
		'depart_push_patriarch',
		'arrive_push_care_flag',
		'arrive_push_teacher_flag',
		'arrive_push_patriarch_flag',
		'arrive_push_patriarch',
		'end_push_care_flag',
		'end_push_teacher_flag',
		'end_push_patriarch_flag',
		'end_push_patriarch',
		'go_push_care_flag',
		'go_push_teacher_flag',
		'go_push_patriarch_flag',
		'go_push_patriarch',
		'holiday_push_care_flag',
		'holiday_push_teacher_flag',
		'holiday_push_patriarch_flag',
		'holiday_push_patriarch'
		)->first();
		
		//dd($data['basic_info']);
		
		if($data['basic_info']){
			$data['basic_info']->depart_push_patriarch=json_decode($data['basic_info']->depart_push_patriarch,true);
			$data['basic_info']->arrive_push_patriarch=json_decode($data['basic_info']->arrive_push_patriarch,true);
			$data['basic_info']->end_push_patriarch=json_decode($data['basic_info']->end_push_patriarch,true);
			$data['basic_info']->go_push_patriarch=json_decode($data['basic_info']->go_push_patriarch,true);		
			$data['basic_info']->holiday_push_patriarch=json_decode($data['basic_info']->holiday_push_patriarch,true);				
			$data['basic_info']->auto_depart_ahead=$data['basic_info']->auto_depart_ahead/60;
			$data['basic_info']->auto_departlater=$data['basic_info']->auto_departlater/60;
			
		}
		
		//dd($data);
        $where2=[
            ['group_code','=',$group_code],
            ['person_type','=','teacher'],
            ['delete_flag','=','Y'],

        ];
        $data['teacher'] =SchoolInfo::has('userReg')
            ->with(['userReg'=>function($query){
                $query->where('reg_type','=','WEIXIN');
                $query->where('delete_flag','=','Y');
                $query->select('self_id','union_id','token_id','token_name');
            }])
            ->where($where2)
            ->select('union_id','grade_name','class_name','person_tel','actual_name','group_name')
            ->get();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;

    }


    /***    基础添加入库      /school/basic/addBasic
     *      前端传递必须参数：1
     *      前端传递非必须参数：
     */
    public function addBasic(Request $request,RedisServer $redisServer){
		//dd($request->input());
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $table_name         ='school_basics';
        $now_time           =date('Y-m-d H:i:s',time());

        $operationing->access_cause     ='新建/修改基础数据';
        $operationing->operation_type   ='create';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();
        //dd($input);
        /** 接收数据*/		
        $group_code                 =$request->input('group_code');
		
		/** 接收车费数据*/
		$money_platform_flag		=$request->input('money_platform_flag');  //是否平台代收车费
        $money_cost_way             =$request->input('money_cost_way');//付费方式
        $money_refund_flag          =$request->input('money_refund_flag');	//未坐校车是否退款	
        $mini_flag                  =$request->input('mini_flag');	//跳转到小程序
        $shenqing                   =$request->input('shenqing');   //校车申请采用模式
		
		/** 自动发车数据*/
		$auto_depart_flag           =$request->input('auto_depart_flag');//是否自动发车
        $auto_depart                =$request->input('auto_depart');	//自动发车电子围栏范围
        $auto_depart_ahead          =$request->input('auto_depart_ahead');//自动发车提前时间
        $auto_departlater           =$request->input('auto_departlater');//自动发车滞后时间

		
		/** 自动到站数据*/
        $arrive_skip_flag           =$request->input('arrive_skip_flag');//是否跳站
        $arrive_rail_type           =$request->input('arrive_rail_type');//到站围栏类型
        $arrive_distance            =$request->input('arrive_distance');//到站直线距离阈值
        $arrive_distance_distance   =$request->input('arrive_distance_distance');//到站进入容差距离
        $arrive_distance_tolerance  =$request->input('arrive_distance_tolerance');//到站容差次数
		
		/** 发车数据*/
        $depart_push_care_flag      =$request->input('depart_push_care_flag');//发车提醒是否推送给照管和司机
        $depart_push_teacher_flag   =$request->input('depart_push_teacher_flag');//发车提醒是否推送给老师
        $depart_push_patriarch_flag =$request->input('depart_push_patriarch_flag');//发车提醒是否推送给家长
        $depart_push_patriarch      =$request->input('depart_push_patriarch');//发车提醒推送给老师的集合
		
		/** 到站数据*/
        $arrive_push_care_flag      =$request->input('arrive_push_care_flag');//到站提醒是否推送给照管和司机
        $arrive_push_teacher_flag   =$request->input('arrive_push_teacher_flag');//到站提醒是否推送给老师
        $arrive_push_patriarch_flag =$request->input('arrive_push_patriarch_flag');	//	到站提醒是否推送给家长
        $arrive_push_patriarch      =$request->input('arrive_push_patriarch');//到站提醒推送给老师的集合
		
		/** 预约提醒数据*/
        $go_push_care_flag          =$request->input('go_push_care_flag');//预约提醒是否推送给照管和司机
        $go_push_teacher_flag       =$request->input('go_push_teacher_flag');//预约提醒是否推送给老师
        $go_push_patriarch_flag     =$request->input('go_push_patriarch_flag');//预约提醒是否推送给家长
		$go_push_patriarch          =$request->input('go_push_patriarch');//预约提醒推送给老师的集合
		
		/** 结束提醒数据*/
        $end_push_care_flag          =$request->input('end_push_care_flag');//结束提醒是否推送给照管和司机
        $end_push_teacher_flag       =$request->input('end_push_teacher_flag');//结束提醒是否推送给老师
        $end_push_patriarch_flag     =$request->input('end_push_patriarch_flag');//结束提醒是否推送给家长
		$end_push_patriarch          =$request->input('end_push_patriarch');//结束提醒推送给老师的集合
		
		/** 请假提醒数据*/	
        $holiday_push_care_flag          =$request->input('holiday_push_care_flag');//假是否推送给照管和司机
        $holiday_push_teacher_flag       =$request->input('holiday_push_teacher_flag');//请假是否推送给老师
        $holiday_push_patriarch_flag     =$request->input('holiday_push_patriarch_flag');//请假是否推送给家长
		$holiday_push_patriarch          =$request->input('holiday_push_patriarch');//请假推送给老师的集合

        /*** 虚拟数据
        $input['group_code']    =$group_code    ='group_20200907110014106649684';
        $money_cost_way      ='front';
        $money_refund_flag   ='Y';
        $mini_flag  ='Y';
        $shenqing  ='address';

        $depart_push_patriarch=[
//            [
//                'token_id'=>'oH6pmt8gZwxNxKwufPOVyuDWraH0',
//                'token_name'=>'疍瑆',
//            ],[
//                'token_id'=>'oH6pmt266h5qz7jKme5hgOkqrKPg',
//                'token_name'=>'吉安',
//            ]
        ];
*/

        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
        ];
		
        $validator=Validator::make($input,$rules,$message);
		//dd(1111);
        if($validator->passes()){
			$etheiu['group_code']=$group_code;
            $group_name=SystemGroup::where($etheiu)->value('group_name'); 
			//dd($data);
			if(empty($group_name)){
				$msg['code']=301;
				$msg['msg']="获取不到数据";
				return $msg;
			}
            //dd($input);
            /** 把数据先做好***/
			/** 车费数据***/
            $data['money_platform_flag']      		=$money_platform_flag;
            $data['money_cost_way']           		=$money_cost_way;
            $data['money_refund_flag']        		=$money_refund_flag;
            $data['mini_flag']                		=$mini_flag;
            $data['shenqing']           	  		=$shenqing;
			
			/** 自动发车数据***/
			$data['auto_depart_flag']         		=$auto_depart_flag;
            $data['auto_depart']           	  		=$auto_depart;
            $data['auto_depart_ahead']        		=$auto_depart_ahead*60;
            $data['auto_departlater']         		=$auto_departlater*60;
			/** 自动到站数据***/
            $data['arrive_skip_flag']         		=$arrive_skip_flag;
            $data['arrive_rail_type']      	  		=$arrive_rail_type;
            $data['arrive_distance']          		=$arrive_distance;
            $data['arrive_distance_distance']       =$arrive_distance_distance;
            $data['arrive_distance_tolerance']      =$arrive_distance_tolerance;
			/** 发车数据***/
            $data['depart_push_care_flag']          =$depart_push_care_flag;
			$data['depart_push_teacher_flag']       =$depart_push_teacher_flag;
            $data['depart_push_patriarch_flag']     =$depart_push_patriarch_flag;
			
            if(!is_null($depart_push_patriarch) && !empty($depart_push_patriarch)){
                $data['depart_push_patriarch']          =json_encode($depart_push_patriarch,JSON_UNESCAPED_UNICODE);
            }
	
			/** 到站数据***/
            $data['arrive_push_care_flag']          =$arrive_push_care_flag;
            $data['arrive_push_teacher_flag']       =$arrive_push_teacher_flag;	
            $data['arrive_push_patriarch_flag']     =$arrive_push_patriarch_flag;
			if(!is_null($arrive_push_patriarch) && !empty($arrive_push_patriarch)){
                $data['arrive_push_patriarch']          =json_encode($arrive_push_patriarch,JSON_UNESCAPED_UNICODE);
            }
			
			/** 结束数据***/
            $data['end_push_care_flag']          	=$end_push_care_flag;			
            $data['end_push_teacher_flag']          =$end_push_teacher_flag;	
            $data['end_push_patriarch_flag']        =$end_push_patriarch_flag;
			if(!is_null($end_push_patriarch) && !empty($end_push_patriarch)){
                $data['end_push_patriarch']          =json_encode($end_push_patriarch,JSON_UNESCAPED_UNICODE);
            }
			
			/** 预约数据***/
            $data['go_push_care_flag']          	=$go_push_care_flag;
            $data['go_push_teacher_flag']           =$go_push_teacher_flag;	
            $data['go_push_patriarch_flag']         =$go_push_patriarch_flag;
            if(!is_null($go_push_patriarch) && !empty($go_push_patriarch)) {
                $data['go_push_patriarch'] = json_encode($go_push_patriarch, JSON_UNESCAPED_UNICODE);
            }


			/** 请假数据***/
            $data['holiday_push_care_flag']         =$holiday_push_care_flag;
            $data['holiday_push_teacher_flag']      =$holiday_push_teacher_flag;	
            $data['holiday_push_patriarch_flag']    =$holiday_push_patriarch_flag;
            if( !is_null($holiday_push_patriarch) && !empty($holiday_push_patriarch)) {
                $data['holiday_push_patriarch'] = json_encode($holiday_push_patriarch, JSON_UNESCAPED_UNICODE);
            }
			
            $datt1['group_code']=$group_code;
			
            $datt1['delete_flag']='Y';
            $old_info=SchoolBasics::where($datt1)->first();


				if($old_info){
					//dd(11111);
					$operationing->access_cause     ='修改差异化数据';
					$operationing->operation_type   ='update';

					$data['update_time']=$now_time;
					$id=SchoolBasics::where($datt1)->update($data);
					//存储差异化到redis
					//dd(11111);
				}else{

					$operationing->access_cause     ='新建差异化数据';
					$operationing->operation_type   ='create';
					//通过group_code去拿取数据去


					$data['self_id']            =generate_id('basics_');
					$data['create_user_id']     = $user_info->admin_id;
					$data['create_user_name']   = $user_info->name;
					$data['create_time']        =$data['update_time']=$now_time;
					$data['group_code']         = $group_code;
					$data['group_name']         = $group_name;
					//dd($data);
					$id=SchoolBasics::insert($data);
					//dd(2222);
					
                    //存储差异化到redis
                    
				}				



            $operationing->table_id=$old_info?$old_info->self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){
				$redisServer->set($group_code,$data,'school_basics');
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
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



    /***    差异化详情     /company/basis/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='school_basics';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'warehouse_name','warehouse_address','warehouse_contacts','warehouse_tel'];
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
