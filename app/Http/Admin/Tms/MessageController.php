<?php
namespace App\Http\Admin\Tms;

use App\Http\Controllers\CommonController;
use App\Models\Tms\TmsMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;

class MessageController extends CommonController{

    /***    滚动消息列表      /tms/message/messageList
     */
    public function  messageList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='滚动消息';

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }
    /**
     * 滚动消息列表 /tms/message/messagePage
     * */
    public function messagePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $tms_order_type           =array_column(config('tms.tms_order_type'),'name','key');
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $type           =$request->input('type');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'type','value'=>$type],
        ];


        $where=get_list_where($search);

        $select=['self_id','content','use_flag','delete_flag','create_time','update_time','type','sort','group_code','group_name'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=TmsMessage::where($where)->count(); //总的数据量
                $data['items']=TmsMessage::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('sort', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=TmsMessage::where($where)->count(); //总的数据量
                $data['items']=TmsMessage::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('sort', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=TmsMessage::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=TmsMessage::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('sort', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
            $v->type_show = $tms_order_type[$v->type]?? null;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /**
     * 添加滚动消息  /tms/message/createMessage
     * */
    public function createMessage(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
//        $self_id = 'car_20210313180835367958101';
        $tms_order_type           =array_column(config('tms.tms_order_type'),'name','key');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','content','use_flag','delete_flag','create_time','update_time','type','sort','group_code','group_name'];
        $data['info']=TmsMessage::where($where)->select($select)->first();
        if ($data['info']){
            $data['info']->type_show = $tms_order_type[$data['info']->type]?? null;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($msg);
        return $msg;
    }

    /**
     *  添加/编辑滚动消息   /tms/message/addMessage
     * */
    public function addMessage(Request $request){
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='tms_message';

        $operationing->access_cause     ='创建/修改滚动消息';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';

        $input              =$request->all();
        //dd($input);
        /** 接收数据*/
        $self_id           =$request->input('self_id');
        $type              =$request->input('type');
        $content           =$request->input('content');
        $sort              =$request->input('sort');
        $group_code        =$request->input('group_code');
        $group_name        =$request->input('group_name');
        /*** 虚拟数据
        $input['push_content']       =$push_content ='4米2厢车';
         **/
        $rules=[
            'content'=>'required',
        ];
        $message=[
            'content.required'=>'请填写内容',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $data['type']          = $type;
            $data['content']       = $content;
            $data['sort']          = $sort;

            $wheres['self_id'] = $self_id;
            $old_info=TmsMessage::where($wheres)->first();

            if($old_info){
                $data['update_time']=$now_time;
                $id=TmsMessage::where($wheres)->update($data);

                $operationing->access_cause='修改滚动消息';
                $operationing->operation_type='update';
            }else{
                $data['self_id']            =generate_id('msg_');
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;
                $data['create_time']        =$data['update_time']=$now_time;

                $id=TmsMessage::insert($data);
                $operationing->access_cause='新建滚动消息';
                $operationing->operation_type='create';
            }
            $operationing->table_id=$self_id;
            $operationing->old_info=null;
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
                $msg['msg'].=$kk.'：'.$v;
            }
            return $msg;
        }
    }

    /**
     * 启用/禁用滚动消息 /tms/message/messageUseFlag
     * */
    public function messageUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_message';
        $medol_name='TmsMessage';
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


    /**
     * 删除滚动消息 /tms/message/messageDelFlag
     * */
    public function messageDelFlag(Request $request){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='tms_message';
        $self_id=$request->input('self_id');
        $flag='delete_flag';
//        $self_id='company_2021030315204691392271';
        $old_info = TmsMessage::where('self_id',$self_id)->select('group_code','group_name','use_flag','delete_flag','update_time')->first();
        $update['delete_flag'] = 'N';
        $update['update_time'] = $now_time;
        $id = TmsMessage::where('self_id',$self_id)->update($update);
//        dd($id);
//        $status_info=$status->changeFlag($table_name,$self_id,$flag,$now_time);
        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=(object)$update;
        $operationing->operation_type=$flag;
        if($id){
            $msg['code']=200;
            $msg['msg']='删除成功！';
            $msg['data']=(object)$update;
        }else{
            $msg['code']=300;
            $msg['msg']='删除失败！';
        }

        return $msg;

    }
}
