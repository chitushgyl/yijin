<?php
namespace App\Http\Controllers;
class PushController extends Controller{

    /**          可以使用这个测试   /message/send/addMessage
     *
     *回调数据：  无
     */
    public function send($wx_config,$templateId,$reg_info,$data,$url,$miniprogram){
        /****说明：
         * $wx_config    指使用哪个公号来进行推送，条件是微信公号的配置授权必须在这个服务器访问地址中，需要到公号后台进行配置
         * $templateId  指模板的推送ID，这个值是在微信公号中去申请推送的时候给出的，
         *              拿到之后，需要通过后台的模板管理（wx_message）中配置参数，参数变量请保持和申请一致不然推送出空数据出来
         * $reg_info    指这个消息推送给那些人，包含了token_id  和  token_name   记住这里传递过来必须是数组，因为要循环推送给相关的人
         * $data        指推送的具体消息，主要用于推送的消息内容，和$templateId 的配置变量进行了对比，如果不一致，会导致空推送的发生
         * $url         指推送的时候的跳转地址，和$miniprogram   想配合，有权重关系
         *$miniprogram  指推送的时候跳转到小程序，小程序必须和公号进行过绑定关系，不然推不出来，
         *               $url和$miniprogram 如果都有值的时候取小程序，如果不需要跳转则两个值都为空
         ****/

        $appid      =$wx_config['app_id'];
        $secret     =$wx_config['secret'];
        $urls       = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$secret";
        $json       = $this->httpGet($urls);

        //dd($json);
        $res        = json_decode($json,true);
        $token      = $res['access_token'];
        $url1       = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;

        foreach ($reg_info as $k=>$v){
             //如果  $differentiation  有的话，可能要在这里做差异化
             //根据 $differentiation  做差异化处理了！！！！ 补充元素

            foreach ($data as $kk => $vv){
                if(array_key_exists($kk, $v)){
                    if(array_key_exists('text', $v[$kk])){
                        $data[$kk]['value'].=$v[$kk]['text'];
                    }
                    if(array_key_exists('color', $v[$kk])){
                        $data[$kk]['color']=$v[$kk]['color'];
                    }
                }
            }
            //dump($data);
            $template =[
                "touser" =>$v['token_id'],
                "template_id" =>$templateId,
                "url" =>$url,
                "miniprogram"=>$miniprogram,
                "data" =>$data,
            ];
            //dump($template);
            $this->https_request($url1,json_encode($template));
        }

    }


    private function https_request($url,$data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

}
?>
