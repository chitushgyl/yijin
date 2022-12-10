<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SendController as Send;
class MessageController extends Controller{

    /**
     * 短信发送控制器     message/send
     * 前端传递必须参数：user_token    tel
     *前端传递非必须参数：
     *
     * 回调结果：200  发送成功
     *          301  手机号格式不正确
     *          302  内部错误，数据库操作未成功
     *          303  阿里云发送失败
     *          100  授权失败
     *
     *回调数据：  无
     */
    public function send(Request $request,Send $send){
        /** 接收数据*/
        $tel=$request->tel;//电话

        /** 虚拟一下数据来做下操作*/
        //$tel='15000661376';

//dd($user_info);
        if($tel){
            $aliyun     = config('aliyun.aliyun');      //短信配置参数
            //必须包含：accessKeyId，accessKeySecret，SignName    可以通过数据库的控制或者配置文件控制来实现

            //做一个分类变量来实现发送的是什么短信，方便短信发送控制器根据流程做相应的转化
            /*** 这个位置就是要触发短信推送的具体内容***/
//            $templateCode   ='SMS_205246188';
            $templateCode   ='SMS_196658029';
            $send_type      ='verify';
            $code           =rand(1111,9999);
            $smsData        = [
                'code'=>$code,
            ];

            /*** 只要修改上面部分的内容就可以触发短信，如果有多条短信需要发送，目前需要将上面的 $smsData元素 放在循环体中***/

            //所使用的模板若有变量 在这里填入变量的值
            $info=$send->send($tel,$aliyun,$templateCode,$send_type,$smsData);

            $msg['code']    =$info['status'];
            $msg['msg']     =$info['msg'];
            return $msg;

        }else{
            $msg['code']=303;
            $msg['msg']="请填写手机号码";
            return $msg;
        }
    }

    /**
     * 短信发送控制器     message/message_send
     * 前端传递必须参数：user_token    tel
     *前端传递非必须参数：
     *
     * 回调结果：200  发送成功
     *          301  手机号格式不正确
     *          302  内部错误，数据库操作未成功
     *          303  阿里云发送失败
     *          100  授权失败
     *
     *回调数据：  无
     */


    public function message_send(Request $request,Send $send){
        $tel='15000661376';
        $aliyun     = config('aliyun.aliyun');      //短信配置参数
        $templateCode   ='SMS_210765192';
        switch ($templateCode){
            case 'SMS_210765192':
                $send_type      ='qita';
                $name           ='张三';
                $smsData        = [
                    'name'=>$name,
                ];

                break;
        }



        $info=$send->send($tel,$aliyun,$templateCode,$send_type,$smsData);
        $msg['code']    =$info['status'];
        $msg['msg']     =$info['msg'];
        return $msg;
    }

}
?>
