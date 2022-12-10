<?php
namespace App\Http\Admin\Message;
//use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\StatusController as Status;
use App\Models\WxMessage;

class MessageController  extends CommonController
{
    /***    微信模板推送消息列表      /message/message/messageList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function messageList(Request $request)
    {
        //引入配置文件
        $data['page_info']      = config('page.listrows');
        $data['button_info']    = $request->get('anniu');

        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;

        //dd($msg);
        return $msg;

    }

    /***    微信模板推送消息列表      /message/message/messagePage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function messagePage(Request $request)
    {
        /** 接收中间件参数**/
		$user_info          = $request->get('user_info');//接收中间件产生的参数
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数1

//dd($user_info);
        /**接收数据*/
        $num                = $request->input('num') ?? 10;
        $page               = $request->input('page') ?? 1;
        $wx_template_id     = $request->get('wx_template_id');
        $template_title     = $request->get('template_title');
        $use_flag           = $request->get('use_flag');

        $listrows           = $num;
        $firstrow           = ($page - 1) * $listrows;
        $search = [
            ['type' => '=', 'name' => 'delete_flag', 'value' => 'Y'],
			['type' => '=', 'name' => 'use_flag', 'value' => $use_flag],
			['type' => 'like', 'name' => 'wx_template_id', 'value' => $wx_template_id],
			['type' => 'like', 'name' => 'template_title', 'value' => $template_title],
        ];

        $where = get_list_where($search);
        $select=['self_id','wx_template_id','template_title','template_info','use_flag','create_user_name','create_time','background_flag','remark','lock_flag'];

        switch ($group_info['group_id']) {
            case 'all':
                $data['total'] = WxMessage::where($where)->count(); //总的数据量
                $data['items'] = WxMessage::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'one':

                $data['total'] = WxMessage::where($where)->count(); //总的数据量
                $data['items'] = WxMessage::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['total'] = WxMessage::where($where)->count(); //总的数据量
                $data['items'] = WxMessage::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;
        }

        foreach ($data['items'] as $k => $v) {
			if($v->lock_flag == 'N' || $user_info->authority_id == '10'){
				$v->button_info = $button_info;
			}
            
        }
        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        //dd($msg);
        return $msg;


    }


    /***    微信模板禁启用     /message/message/messageUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function messageUseFlag(Request $request,Status $status)
    {
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='wx_message';
        $medol_name     ='WxMessage';
        $self_id        =$request->input('self_id');
        $flag           ='useFlag';
//        $self_id='tm_202008170549322271308718';
//        dd($now_time);
        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause         ='启用/禁用';
        $operationing->table                =$table_name;
        $operationing->table_id             =$self_id;
        $operationing->now_time             =$now_time;
        $operationing->old_info             =$status_info['old_info'];
        $operationing->new_info             =$status_info['new_info'];
        $operationing->operation_type       =$flag;

//        dd($operationing);


        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;

    }


    /***    微信模板删除     /message/message/messageDelFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function messageDelFlag(Request $request,Status $status)
    {
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='wx_message';
        $medol_name     ='WxMessage';
        $self_id        =$request->input('self_id');
        $flag           ='delFlag';
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


    /***    新建微信模板     /message/message/createMessage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createMessage(Request $request)
    {
        /** 接收数据*/
        $self_id=$request->input('self_id');
        //$self_id = 'tm_2020072311483583638916771';
        $where1['self_id']=$self_id;
        $data['message_info']=WxMessage::where($where1)
            ->select('self_id','wx_template_id','template_title','template_info','background_flag','remark','lock_flag')->first();

        if($data['message_info']){
            $data['message_info']->template_info=json_decode($data['message_info']->template_info,true);
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;






    }

    /***    新建微信模板数据提交     /message/message/addMessage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addMessage(Request $request)
    {
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='wx_message';

        $operationing->access_cause     ='新建/修改微信模板';
        $operationing->operation_type   ='create';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        /** 接收中间件参数**/
        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        /**接收数据*/
        $self_id                        = $request->input('self_id');
        $wx_template_id                 = $request->input('wx_template_id');
        $template_title                 = $request->input('template_title');
        $template_info                  = $request->input('template_info');
		$background_flag                = $request->input('background_flag');
		$remark                			= $request->input('remark');
		$lock_flag                		= $request->input('lock_flag');
		
		
		
		//dump($template_info);		
        /*** 虚拟数据*
        $self_id = 'mac_202005222120512869323984';
        $wx_template_id = '9FMbXZpvmbEsl6_a4FLJkvwkILMZoEDCyhDdutRHts0';                //微信的公号中提供的
        $template_title = '校车运行提醒';                                                 //微信公号中的
        $template_info22 = [
            '0'=>[
                'title'=>'标题',
                'key'=>'first',
            ],
            '1'=>[
                'template_info'=>'学生姓名',
                'key'=>'keyword1',
            ],
            '2'=>[
                'title'=>'运行状态',
                'key'=>'keyword2',
            ],
            '3'=>[
                'title'=>'内容详情',
                'key'=>'keyword3',
            ],
            '4'=>[
              'title'=>'发送时间',
                'key'=>'keyword4',
            ],

            '5'=>[
                'title'=>'备注',
                'key'=>'remark',
            ],
        ];
        */
        //开始做数据
        $data['wx_template_id']         =$wx_template_id;
        $data['template_title']         =$template_title;
		$data['background_flag']        =$background_flag;
        $data['template_info']          =json_encode($template_info,JSON_UNESCAPED_UNICODE);
		$data['remark']         		=$remark;
		$data['lock_flag']        		=$lock_flag;
		
		//dd($data);
        $where['self_id']=$self_id;
        $old_info=WxMessage::where($where)->first();

        if($old_info){
            $data['create_time'] = $now_time;
            $id=WxMessage::where($where)->update($data);
            $operationing->access_cause='修改微信模板';
            $operationing->operation_type='update';

        }else{
            $data['create_user_id'] = $user_info->admin_id;
            $data['create_user_name'] = $user_info->name;
            $data['self_id'] = generate_id('tm_');
            $data['update_time'] = $data['create_time'] = $now_time;
            $id=WxMessage::insert($data);

            $operationing->access_cause='新建微信模板';
            $operationing->operation_type='create';

        }

        $operationing->table_id=$self_id?$self_id:$data['self_id'];
        $operationing->old_info=$old_info;
        $operationing->new_info=$data;


        if($id){
            $msg['code'] = 200;
            $msg['msg'] = "处理成功";
            return $msg;
        }else{
            $msg['code'] = 302;
            $msg['msg'] = "处理失败";
            return $msg;
        }


    }


}
?>
