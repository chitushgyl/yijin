<?php
namespace App\Http\Admin\Pay;

use App\Http\Controllers\CommonController;
use App\Models\Tms\TmsOrder;
use App\Models\Tms\TmsOrderCost;
use App\Models\Tms\TmsOrderDispatch;
use App\Models\Tms\TmsPayment;
use App\Models\User\UserCapital;
use App\Models\User\UserWallet;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EasyWeChat\Foundation\Application;


class PayController extends CommonController{

    /**
     * 支付宝充值 /pay/pay/alipay_deposit
     * */
    public function alipay_deposit(Request $request){
        $now_time      = date('Y-m-d H:i:s',time());
        $input         = $request->all();
        $price         = $request->input('price');
        $group_code     = $request->input('group_code');//接收中间件产生的参数
//        $price = 0.01;
        include_once base_path( '/vendor/alipay/pagepay/service/AlipayTradeService.php');
        include_once base_path('/vendor/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');
        $ordernumber = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $out_trade_no = trim($ordernumber);//商户订单号，商户网站订单系统中唯一订单号，必填
        $subject = trim('充值'); //订单名称，必填
        $total_amount = trim($price); //付款金额，必填
        $body = trim('余额充值');//商品描述，可空
        $timeExpress = '15m';
        $notify_url = 'https://ytapi.56cold.com/pay/pay/deposit_notify';//异步通知地址
        $return_url = "https://bms.56cold.com/tms/balance";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($group_code);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = config('tms.alipay_config');//引入配置文件参数
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = json_encode($aop->pagePay($payRequestBuilder, $return_url, $notify_url),JSON_UNESCAPED_UNICODE);
        //输出表单
        return $response;
//        var_dump($response);
    }

    /**
     * 支付宝充值回调
     * */
    public function deposit_notify(Request $request){
        include_once base_path( 'vendor/alipay/pagepay/service/AlipayTradeService.php');
        $now_time = date('Y-m-d H:i:s',time());
        $config = config('tms.alipay_config');
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($_POST);
        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            $userCapital = UserCapital::where('group_code','=',$_POST['passback_params'])->first();
            $flag = TmsPayment::where([['group_code','=',$_POST['passback_params']],['order_id','=',$_POST['out_trade_no']]])->first();
            if ($flag){
                echo 'success';
                return false;
            }
            $pay['order_id'] = $_POST['out_trade_no'];
            $pay['pay_number'] = $_POST['total_amount'] * 100;
            $pay['platformorderid'] = $_POST['trade_no'];
            $pay['create_time'] = $pay['update_time'] = $now_time;
//            $pay['payname'] = $_POST['buyer_logon_id'];
            $pay['paytype'] = 'ALIPAY';//
            $pay['pay_result'] = 'SU';//
            $pay['state'] = 'recharge';//支付状态
            $pay['self_id'] = generate_id('pay_');
            $pay['group_code'] = $_POST['passback_params'];
//            file_put_contents(base_path('/vendor/5555.txt'),$pay);
            TmsPayment::insert($pay);

            $capital['money'] = $userCapital->money + $_POST['total_amount']*100;
            $capital['update_time'] = $now_time;
            UserCapital::where('group_code','=',$_POST['passback_params'])->update($capital);

            $wallet['self_id'] = generate_id('wallet_');
            $wallet['produce_type'] = 'recharge';
            $wallet['capital_type'] = 'wallet';
            $wallet['create_time'] = $now_time;
            $wallet['update_time'] = $now_time;
            $wallet['money'] = $_POST['total_amount'] * 100;
            $wallet['now_money'] = $capital['money'];
            $wallet['now_money_md'] = get_md5($capital['money']);
            $wallet['wallet_status'] = 'SU';
            $wallet['group_code'] = $_POST['passback_params'];

            UserWallet::insert($wallet);
            echo 'success';
        } else {
            echo 'fail';
        }
    }

    /**
     * 支付宝上线订单支付 /pay/onlineAlipay
     * */
    public function onlineAlipay(Request $request){
        $user_info     = $request->get('user_info');//接收中间件产生的参数
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $now_time      = date('Y-m-d H:i:s',time());
        $input         = $request->all();
        $price         = $request->input('price');
        $self_id       = $request->input('self_id');
        $order = TmsOrderDispatch::where('self_id','=',$self_id)->select(['self_id','group_code','receiver_id'])->first();
        if ($order->receiver_id != $order->group_code){
            $msg['code'] = 301;
            $msg['msg']  = "接取的订单不能上线";
            return $msg;
        }
//        $price = 0.01;
        include_once base_path( '/vendor/alipay/pagepay/service/AlipayTradeService.php');
        include_once base_path('/vendor/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');

        $out_trade_no = trim($self_id);//商户订单号，商户网站订单系统中唯一订单号，必填
        $subject = trim('上线订单支付'); //订单名称，必填
        $total_amount = trim($price); //付款金额，必填
        $body = trim('订单支付');//商品描述，可空
        $timeExpress = '15m';
        $notify_url = 'https://ytapi.56cold.com/onlineAlipay_notify';//异步通知地址
        $return_url = "https://bms.56cold.com/tms/balance";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($user_info->group_code);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = config('tms.alipay_config');//引入配置文件参数
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
    }

    public function onlineAlipay_notify(){
        include_once base_path( 'vendor/alipay/pagepay/service/AlipayTradeService.php');
        $config = Yii::$app->params['configpay'];
        $alipaySevice = new AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($_POST);
        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            $now_time = date('Y-m-d H:i:s',time());
            $pay['dispatch_id'] = $_POST['out_trade_no'];
            $pay['pay_number'] = $_POST['total_amount'] * 100;
            $pay['platformorderid'] = $_POST['trade_no'];
            $pay['create_time'] = $pay['update_time'] = $now_time;
            $pay['payname'] = $_POST['buyer_logon_id'];
            $pay['paytype'] = 'ALIPAY';//
            $pay['pay_result'] = 'SU';//
            $pay['state'] = 'in';//支付状态
            $pay['self_id'] = generate_id('pay_');
//            file_put_contents(base_path('/vendor/alipay.txt'),$pay);
            $order = TmsOrderDispatch::where('self_id',$_POST['out_trade_no'])->select(['total_user_id','group_code','order_status','group_name','order_type'])->first();
            if ($order->order_status == 3){
                echo 'success';
                return false;
            }
            if ($order->total_user_id){
                $pay['total_user_id'] = $_POST['passback_params'];
            }else{
                $pay['group_code'] = $_POST['passback_params'];
                $pay['group_name'] = $order->group_name;
            }
            TmsPayment::insert($pay);

            $order_update['order_status'] = 2;
            $order_update['update_time'] = date('Y-m-d H:i:s',time());
            $order_update['pay_status']  = 'Y';
            $order_update['on_line_flag'] = 'Y';
            $order_update['dispatch_flag'] = 'N';
            $order_update['receiver_id'] = null;
            $id = TmsOrderDispatch::where('self_id',$_POST['out_trade_no'])->update($order_update);
            /**修改费用数据为可用**/
            $money['delete_flag']                = 'Y';
            $money['settle_flag']                = 'W';
            $tmsOrderCost = TmsOrderCost::where('order_id',$_POST['out_trade_no'])->select('self_id')->get();
            if ($tmsOrderCost){
                $money_list = array_column($tmsOrderCost->toArray(),'self_id');
                TmsOrderCost::whereIn('self_id',$money_list)->update($money);
            }

            if ($id){
                echo 'success';
            }else{
                echo 'fail';
            }
        }else{
            echo 'fail';
        }
    }


    /**
     * 货主公司零担支付 pay/pay/bulkAlipay
     * */
    public function bulkAlipay(Request $request){
//        $user_info     = $request->get('user_info');//接收中间件产生的参数

        $now_time      = date('Y-m-d H:i:s',time());
        $input         = $request->all();
        $price         = $request->input('price');
        $self_id       = $request->input('self_id');
        $group_code    = $request->input('group_code');
//        dd($user_info->group_code);
//        $price = 0.01;
//        $self_id = 'order_202105191510152166706423';
//        $group_code = 'group_20210517152951011628979';
        include_once base_path( '/vendor/alipay/pagepay/service/AlipayTradeService.php');
        include_once base_path('/vendor/alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');

        $out_trade_no = trim($self_id);//商户订单号，商户网站订单系统中唯一订单号，必填
        $subject = trim('零担订单支付宝支付'); //订单名称，必填
        $total_amount = trim($price); //付款金额，必填
        $body = trim('支付宝订单支付');//商品描述，可空
        $timeExpress = '15m';
        $notify_url = 'https://ytapi.56cold.com/pay/pay/bulkAlipay_notify';//异步通知地址
        $return_url = "https://bms.56cold.com/tms/order";//同步跳转
        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder;
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setPassBack_params($group_code);
        $payRequestBuilder->setTimeExpress($timeExpress);
        $config = config('tms.alipay_config');//引入配置文件参数
        $aop = new \AlipayTradeService($config);
        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $return_url, $notify_url);
        //输出表单
        var_dump($response);
    }

    public function bulkAlipay_notify(){
        include_once base_path( 'vendor/alipay/pagepay/service/AlipayTradeService.php');
        $now_time = date('Y-m-d H:i:s',time());
        $config = config('tms.alipay_config');
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST, true));
        $result = $alipaySevice->check($_POST);
        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            $now_time = date('Y-m-d H:i:s',time());
            $pay['order_id'] = $_POST['out_trade_no'];
            $pay['pay_number'] = $_POST['total_amount'] * 100;
            $pay['platformorderid'] = $_POST['trade_no'];
            $pay['create_time'] = $pay['update_time'] = $now_time;
//            $pay['payname'] = $_POST['buyer_logon_id'];
            $pay['paytype'] = 'ALIPAY';//
            $pay['pay_result'] = 'SU';//
            $pay['state'] = 'out';//支付状态
            $pay['self_id'] = generate_id('pay_');
            file_put_contents(base_path('/vendor/111.txt'),$pay);
            $order = TmsOrder::where('self_id','=',$_POST['out_trade_no'])->select(['total_user_id','group_code','order_status','group_name','order_type'])->first();
            file_put_contents(base_path('/vendor/11122.txt'),$order);
            file_put_contents(base_path('/vendor/111223333.txt'),$_POST['passback_params']);
            if ($order->order_status == 2){
                file_put_contents(base_path('/vendor/111223.txt'),123);
                echo 'success';
                return false;
            }
            if ($order->total_user_id){
                $pay['total_user_id'] = $_POST['passback_params'];
                $wallet['total_user_id'] = $_POST['passback_params'];
                $where = [
                    ['total_user_id','=',$_POST['passback_params']]
                ];
            }else{
                file_put_contents(base_path('/vendor/11122355.txt'),456);
                $pay['group_code'] = $_POST['passback_params'];
                $pay['group_name'] = $order->group_name;
                $wallet['group_code'] = $_POST['passback_params'];
                $wallet['group_name'] = $order->group_name;
                $where = [
                    ['group_code','=',$_POST['passback_params']]
                ];
                file_put_contents(base_path('/vendor/111223.txt'),$_POST['passback_params']);
            }
            file_put_contents(base_path('/vendor/111111.txt'),$pay);
            TmsPayment::insert($pay);
            $capital = UserCapital::where($where)->first();
            $wallet['self_id'] = generate_id('wallet_');
            $wallet['produce_type'] = 'out';
            $wallet['capital_type'] = 'wallet';
            $wallet['money'] = $_POST['total_amount'] * 100;
            $wallet['create_time'] = $now_time;
            $wallet['update_time'] = $now_time;
            $wallet['now_money'] = $capital->money;
            $wallet['now_money_md'] = get_md5($capital->money);
            $wallet['wallet_status'] = 'SU';
            UserWallet::insert($wallet);

            if ($order->order_type == 'line'){
                $order_update['order_status'] = 3;
            }else{
                $order_update['order_status'] = 2;
            }
            $order_update['update_time'] = date('Y-m-d H:i:s',time());
            $id = TmsOrder::where('self_id',$_POST['out_trade_no'])->update($order_update);
            /**修改费用数据为可用**/
            $money['delete_flag']                = 'Y';
            $money['settle_flag']                = 'W';
            $tmsOrderCost = TmsOrderCost::where('order_id',$_POST['out_trade_no'])->select('self_id')->get();
            if ($tmsOrderCost){
                $money_list = array_column($tmsOrderCost->toArray(),'self_id');
                TmsOrderCost::whereIn('self_id',$money_list)->update($money);
            }

            $tmsOrderDispatch = TmsOrderDispatch::where('order_id',$_POST['out_trade_no'])->select('self_id')->get();
            if ($tmsOrderDispatch){
                $dispatch_list = array_column($tmsOrderDispatch->toArray(),'self_id');
                $orderStatus = TmsOrderDispatch::whereIn('self_id',$dispatch_list)->update($order_update);
            }

            if ($id){
                echo 'success';
            }else{
                echo 'fail';
            }
        }else{
            echo 'fail';
        }
    }


}
?>
