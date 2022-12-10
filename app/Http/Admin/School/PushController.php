<?php
namespace App\Http\Admin\School;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\WxMessageSend;
use Illuminate\Http\Request;
use App\Http\Controllers\FileController as File;
class PushController extends CommonController{
    /***    推送管理头部      /school/push/pushList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  pushList(Request $request){
        $data['page_info']          =config('page.listrows');
        $data['button_info']        =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        // dd($msg);
        return $msg;
    }


    /***    推送管理分页数据      /school/push/pushPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function pushPage(Request $request){
		$group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
        $num = $request->input('num') ?? 10;
        $page = $request->input('page') ?? 1;
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
		
		$search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];
        $where=get_list_where($search);
		
		$select=['self_id','app_id','date_time','count','create_time','create_user_name','template_type','group_name'];
			
		switch ($group_info['group_id']){
            case 'all':
                $data['total']=WxMessageSend::where($where)->count();
				$data['items']=WxMessageSend::where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
					->select($select)->get();


                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WxMessageSend::where($where)->count();
				$data['items']=WxMessageSend::where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
					->select($select)->get();


                break;

            case 'more':
                $data['total']=WxMessageSend::where($where)->whereIn('group_code',$group_info['group_code'])->count();
				$data['items']=WxMessageSend::where($where)->whereIn('group_code',$group_info['group_code'])
					->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
					->select($select)->get();


                break;
        }

        foreach ($data['items'] as $k => $v){
            $v->button_info = $button_info;
        }
        //dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
       
        return $msg;
    }


    /***    推送详情      /school/push/pushDetails
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function pushDetails(Request $request){
        /**接收数据*/
		
        $self_id        =$request->input('self_id');
		//dd($self_id);
        //$self_id='temp202009031411275657349302';

        $where=[
            ['self_id','=',$self_id],
        ];
        $select=['self_id','app_id','date_time','count','create_time','create_user_name','template_type','group_name','reg_info','message_info'];
        $data['items']=WxMessageSend::where($where)->select($select)->first();
		//dd($data);
        if($data['items']){
            $data['items']->reg_info =json_decode($data['items']->reg_info,true);
            $data['items']->message_info =json_decode($data['items']->message_info,true);
			//dd($data);
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;

        }else{
            $msg['code']=301;
            $msg['msg']="没有查询到数据";
            return $msg;

        }

    }






    /***    推送导出                      /school/push/pushExcel
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function pushExcel(Request $request,File $file){

        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());

        /** 接收数据*/
        $group_code     =$request->input('group_code');
        $start         	=$request->input('start');      //开始时间
        $end            =$request->input('end');        //结束时间

        $input          =$request->all();

        /** 虚拟数据*/
        $group_code     =$input['group_code']   ='group_202006221103544823194960';
        $start          =$input['start']        ='2020-08-26';
        $end            =$input['end']          ='2020-11-11';

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
                ['type'=>'>=','name'=>'date_time','value'=>$start],
                ['type'=>'<=','name'=>'date_time','value'=>$end],
                ['type'=>'=','name'=>'group_code','value'=>$group_code],
                ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ];

            $where=get_list_where($search);

            $wx_message_info=WxMessageSend::where($where)->select('group_name','template_type','date_time','count','create_user_name','create_time')->get();

            if($wx_message_info){
                //设置表头
                $row = [[
                    "group_name"=>'学校名称',
                    "template_type"=>'消息类型',
                    "date_time"=>'时间',
                    "count"=>'接收消息人数',
                    "create_user_name"=>'发送人',
                    "create_time"=>'发送时间',
                ]];

                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($wx_message_info,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time,$start,$end);
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
                $msg['msg'].=$v."\n";
            }
            $msg['code']=300;
            return $msg;
        }
    }

}