<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Shop\ShopFreight;
use App\Models\SysAddress;
class NewsController extends Controller{
    /*** 站内信触发
     *    站内信触发，需要，一个是push_type   是全员的还是单体消息
     *
     ***/
    public function  news($push_type,$message_title,$message_content,$push_id=null,$now_time){

        if($push_type == 'all'){
            $data['self_id']            =generate_id('message_');
            $data['type']               ='news';
            $data['push_type']          =$push_type;
            $data['message_title']      =$message_title;
            $data['message_content']    =$message_content;
            $data['create_time']        =$now_time;
            $data['update_time']        =$now_time;
            Message::insert($data);
        }else{
            $data=[];
            foreach ($push_id as $k => $v){
                $list['self_id']            =generate_id('message_');
                $list['type']               ='news';
                $list['push_type']          =$push_type;
                $list['message_title']      =$message_title;
                $list['message_content']    =$message_content;
                $list['create_time']        =$now_time;
                $list['update_time']        =$now_time;
                $list['total_user_id']      =$v['total_user_id'];
                $data[]=$list;
            }
            //dd($data);
            Message::insert($data);
        }

    }

    /***    微信推送
     */
    public function wx_push($push_type,$message_title,$message_content,$push_id=null,$now_time){
        DUMP($push_type);
        DUMP($message_title);
        DUMP($message_content);
        DUMP($push_id);
        DUMP($now_time);

DD(12121);


    }

    /***    短信推送
     */
    public function tel_push($push_type,$message_title,$message_content,$push_id=null,$now_time){
        $data=[];
        foreach ($push_id as $k => $v){
            $list['self_id']            =generate_id('message_');
            $list['type']               ='tel';
            $list['push_type']          =$push_type;
            $list['message_title']      =$message_title;
            $list['message_content']    =$message_content;
            $list['create_time']        =$now_time;
            $list['update_time']        =$now_time;
            $list['tel']      =$v['tel'];
            $data[]=$list;
        }
        //dd($data);
        Message::insert($data);

        /*** 执行发送消息出去**/


    }




}
