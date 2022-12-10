<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User\UserTelCheck;

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;

class SendController extends Controller{

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
    public function send($tel,$aliyun,$templateCode,$send_type,$smsData){
        //dd(1111);
//        $tel=$request->tel;//电话
        //$tel=15000661376;//电话
        if(empty($tel) && !$this->isphone($tel)){
            $msg['status'] = 301;
            $msg['message'] = "手机号格式不正确";
            return $msg;
        }
        /*** 以下位置为阿里云的短信配置元素 **/
        require_once  './app/Tools/sms/vendor/autoload.php';    //此处为你放置API的路径
        Config::load();             //加载区域结点配置
        $accessKeyId        = $aliyun['accessKeyId'];
        $accessKeySecret    = $aliyun['accessKeySecret'];
        $SignName    = $aliyun['SignName'];
//        $templateCode = config('aliyun.aliyun.templateCode');   //短信模板ID

        //短信API产品名（短信产品名固定，无需修改）
        $product = "Dysmsapi";
        //短信API产品域名（接口地址固定，无需修改）
        $domain = "dysmsapi.aliyuncs.com";
        //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）
        $region = "cn-hangzhou";
        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
        // dump($profile);exit;
        // 增加服务结点
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
        // 初始化AcsClient用于发起请求
        $acsClient = new DefaultAcsClient($profile);
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置短信接收号码
        $request->setPhoneNumbers($tel);

        // 必填，设置签名名称 填写和阿里云配置短信的模板签名一样
        $request->setSignName($SignName);
        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);
        /*** 阿里云的短信配置元素结束 现在是单独处理元素的地方 **/

        /**这里是处理变量的地方***/

//        dump($smsData);
        //选填-假如模板中存在变量需要替换则为必填(JSON格式),友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        $request->setTemplateParam(json_encode($smsData));


        //发起访问请求
        $acsResponse = $acsClient -> getAcsResponse($request);
//        dump($acsResponse);

        //返回请求结果
        $result = json_decode(json_encode($acsResponse), true);
        $resp = $result['Code'];

        //dd($result);

        //$send_type='verify';
//        $user_id=null;
        $msg=$this->sendMsgResult($resp,$tel,$send_type,$templateCode,$smsData);
        return $msg;

    }


    //验证手机号是否正确
    private function isphone($tel){
        if (!is_numeric($tel)) {
            return false;
        }
        return preg_match("/^1[34578]{1}\d{9}$/", $tel)??false;
    }

    /**
     * 验证手机号是否发送成功  前端用ajax，发送成功则提示倒计时，如50秒后可以重新发送
     * @param  [json] $resp  [发送结果]
     * @param  [type] $tel [手机号]
     * @param  [type] $code  [验证码]
     * @return [type]        [description]
     */
    private function sendMsgResult($resp,$tel,$send_type,$templateCode,$smsData){
//        dd($resp);
        if ($resp == "OK") {
//            dd(3333);
            $data['self_id']=generate_id('check_');
            $data['send_type']=$send_type;
            $data['tel']=$tel;
            $data['create_time']=date('Y-m-d H:i:s',time());
            $data['send_day']=date('Y-m-d',time());
            $data['template_code']=$templateCode;

            switch ($send_type){
                case 'verify':
                    $data['message']=$smsData['code'];
                    break;
                default :
                    $data['message']=json_encode($smsData,JSON_UNESCAPED_UNICODE);
                    break;

            }

            $idd=UserTelCheck::insert($data);
            if($idd){
                $msg['status']=200;
                $msg['msg']="发送成功";
            }else{
                $msg['status']=302;
                $msg['msg']="内部错误";
            }
        }else{
            $msg['status']=303;
            $msg['msg']="阿里云发送失败";
        }
//        dd($msg);
        return $msg;
    }


}
?>
