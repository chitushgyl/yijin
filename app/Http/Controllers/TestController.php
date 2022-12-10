<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\SysAddressAll;
use App\Models\Shop\ShopFreight;
use App\Models\SysAddress;
use App\Http\Controllers\NewsController as News;
class TestController extends Controller{

    /***    消息系统测试单元    /test/news_test
     */
    public function  news_test(Request $request,News $news){
        $now_time           =date('Y-m-d H:i:s',time());
        /*** 虚拟数据**/
        $push_type          ='alone';
        $message_title      ='消息标题';
        $message_content    ='消息主体';
        $push_id            =[
            '0'=>[
                'total_user_id'=>'123456',
                'tel'=>'15000661376',
                'token_id'=>'o-QhFwfq1JXqj-Q0vmtiNJaDgEwI',
                'token_name'=>'疍瑆',
                'person_name'=>'疍瑆',
            ],
            '1'=>[
                'total_user_id'=>'12345655',
                'tel'=>'13764502539',
                'token_id'=>'o-QhFwfq1JXqj-Q0vmtiNJaDgEwI',
                'token_name'=>'疍瑆',
                'person_name'=>'疍瑆',
            ],
        ];


        $message_flag           ='N';
        $tel_message_flag       ='Y';
        $wx_message_flag        ='Y';

        //$data=[];

        if($message_flag == 'Y'){
            $news->news($push_type,$message_title,$message_content,$push_id,$now_time);
        }

        if($tel_message_flag == 'Y'){
            $news->tel_push($push_type,$message_title,$message_content,$push_id,$now_time);
        }

        if($wx_message_flag == 'Y'){
            $news->wx_push($push_type,$message_title,$message_content,$push_id=null,$now_time);
        }


        $data=[
            'message_title'=>'消息标题',
            'message_content'=>'消息主体',
            'message_flag'=>'Y',
            'wx_message_flag'=>'Y',
            'wx_template_id'=>'good_202007011336328472133661',
            'wx_push'=>[
                '0'=>[
                    'token_id'=>'o-QhFwfq1JXqj-Q0vmtiNJaDgEwI',
                    'token_name'=>'疍瑆',
                    'person_name'=>'疍瑆',
                ],
            ],
            'tel_message_flag'=>'Y',
            'tel'=>['15000661376','13764502539'],
        ];


        //$news->news($data);


        dd($data);

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        return $msg;




    }





}
