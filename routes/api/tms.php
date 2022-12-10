<?php

//use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// 用户端APP
Route::group([
    "middleware"=>['frontCheck','userCheck'],
], function(){
    /******线路数据*******/
    Route::group([
        'prefix' => 'api','namespace'  => 'Tms',
    ], function(){
    	/**TMS钱包 - 用户端、承运端**/

    	/**TMS线路列表 - 用户端**/
        Route::any('/line/lineList', 'LineController@lineList');
        Route::any('/line/linePage', 'LineController@linePage');
        Route::any('/line/details', 'LineController@details');
        Route::any('/line/count_price', 'LineController@count_price');
		/**TMS订单管理 - 用户端**/
		Route::any('/order/createOrder','OrderController@createOrder');

		/**TMS联系人管理 - 用户端**/
		Route::any('/contacts/createContacts','ContactsController@createContacts');


		/**TMS地址管理 - 用户端**/

		Route::any('/address/createAddress','AddressController@createAddress');

        Route::any('/address/get_address', 'AddressController@get_address');
        Route::any('/address/get_address_id', 'AddressController@get_address_id');
        Route::any('/address/get_all_address', 'AddressController@get_all_address');
		/**TMS车辆管理 - 承运端**/
		Route::any('/car/createCar','CarController@createCar');
		Route::any('/car/getCar','CarController@getCar');
        Route::any('/car/getType', 'CarController@getType');
        Route::any('/comment/getWord', 'CommentController@getWord');

        /** TMS接单订单管理 - 承运端 **/
        Route::any('/carriage/carriageOrderPage', 'CarriageController@carriageOrderPage');//3pl承运商订单列表
    });
    Route::group([
        'prefix' => 'api','namespace'  => 'Tms',
    ], function(){
    	/**TMS上线订单 - 承运端**/
    	Route::any('/carriage/orderOnline', 'CarriageController@orderOnline');//线上点单列表
    	Route::any('/carriage/onnlineDetails', 'CarriageController@onnlineDetails');//线上订单详情
    	Route::any('/carriage/orderTaking', 'CarriageController@orderTaking');//接单
    	Route::any('/carriage/details', 'CarriageController@details');//详情
    	Route::any('/carriage/carriageTake', 'CarriageController@carriageTake');//详情
    	Route::any('/carriage/dispatch_done', 'CarriageController@dispatch_done');//详情
    	Route::any('/carriage/up_receipt', 'CarriageController@up_receipt');//详情
    });

});

Route::group([
    'prefix' => 'api','namespace'  => 'Tms',
], function(){
    Route::any('/take/onlinePage','TakeController@onlinePage');
    Route::any('/line/linePage', 'LineController@linePage');
    Route::any('/order/count_price','OrderController@count_price');
    Route::any('/order/count_klio','OrderController@count_klio');
    Route::any('/order/cityVehical','OrderController@cityVehical');
    Route::any('/order/discount_price','OrderController@discount_price');
    Route::any('/order/get_distance','OrderController@get_distance');//顺风车列表
    Route::any('/message/messagePage','MessageController@messagePage');
});

Route::group([
    "middleware"=>['frontCheck','userCheck'],
], function(){
    Route::any('/alipay/alipay', 'Pay\AlipayController@alipay');
    Route::any('/alipay/wechat', 'Pay\AlipayController@wechat');
    Route::any('/alipay/appWechat', 'Pay\AlipayController@appWechat');
    Route::any('/alipay/appAlipay', 'Pay\AlipayController@appAlipay');
    Route::any('/alipay/online_alipay', 'Pay\AlipayController@online_alipay');
    Route::any('/alipay/online_wechat', 'Pay\AlipayController@online_wechat');
    Route::any('/alipay/paymentAlipay', 'Pay\AlipayController@paymentAlipay');
    Route::any('/alipay/paymentWechat', 'Pay\AlipayController@paymentWechat');
    Route::any('/alipay/driverWechat', 'Pay\AlipayController@driverWechat');
    Route::any('/alipay/routinePay', 'Pay\AlipayController@routinePay');
    Route::any('/alipay/nativePay', 'Pay\AlipayController@nativePay');
    Route::any('/alipay/qrcodeAlipay', 'Pay\AlipayController@qrcodeAlipay');
    Route::any('/alipay/qrcode', 'Pay\AlipayController@qrcode');
    Route::any('/alipay/getClientType', 'Pay\AlipayController@getClientType');
    Route::any('/alipay/queryWechat', 'Pay\AlipayController@queryWechat');
    Route::any('/alipay/queryPayment', 'Pay\AlipayController@queryPayment');
    Route::any('/alipay/balancePay', 'Pay\AlipayController@balancePay');
    Route::any('/alipay/walletPay', 'Pay\AlipayController@walletPay');
    Route::any('/alipay/depositAlipay', 'Pay\AlipayController@depositAlipay');
    Route::any('/alipay/depositWechat', 'Pay\AlipayController@depositWechat');
    Route::any('/alipay/routineDeposit', 'Pay\AlipayController@routineDeposit');
});
Route::any('/alipay/notify', 'Pay\AlipayController@notify');
Route::any('/alipay/wxpaynotify', 'Pay\AlipayController@wxpaynotify');
Route::any('/alipay/appWechat_notify', 'Pay\AlipayController@appWechat_notify');
Route::any('/alipay/appAlipay_notify', 'Pay\AlipayController@appAlipay_notify');
Route::any('/alipay/onlineApipay_notity', 'Pay\AlipayController@onlineApipay_notity');
Route::any('/alipay/onlineWechat_notify', 'Pay\AlipayController@onlineWechat_notify');
Route::any('/alipay/paymentAlipayNotify', 'Pay\AlipayController@paymentAlipayNotify');
Route::any('/alipay/paymentWechatNotify', 'Pay\AlipayController@paymentWechatNotify');
Route::any('/alipay/nativeNotify', 'Pay\AlipayController@nativeNotify');
Route::any('/alipay/qrcode_notify', 'Pay\AlipayController@qrcode_notify');
Route::any('/alipay/depositAlipayNotify', 'Pay\AlipayController@depositAlipayNotify');
Route::any('/alipay/depositWechatNotify', 'Pay\AlipayController@depositWechatNotify');


/**极速版支付***/
Route::group([
    "middleware"=>['frontCheck','userCheck'],
], function(){
    Route::any('/pay/fastOrderAlipay', 'Pay\PayController@fastOrderAlipay');
    Route::any('/pay/fastOrderBalancePay', 'Pay\PayController@fastOrderBalancePay');
    Route::any('/pay/wechat', 'Pay\PayController@wechat');
    Route::any('/pay/paymentBalancePay', 'Pay\PayController@paymentBalancePay');
    Route::any('/pay/nativePay', 'Pay\PayController@nativePay');
    Route::any('/pay/qrcodeAlipay', 'Pay\PayController@qrcodeAlipay');
});
Route::any('/pay/fastOrderAlipayNotify', 'Pay\PayController@fastOrderAlipayNotify');
Route::any('/pay/fastPaymentAlipayNotify', 'Pay\PayController@fastPaymentAlipayNotify');
Route::any('/pay/wechatNotify', 'Pay\PayController@wechatNotify');
Route::any('/pay/paymentWechatNotify', 'Pay\PayController@paymentWechatNotify');
Route::any('/pay/nativeNotify', 'Pay\PayController@nativeNotify');
Route::any('/pay/qrcodeNotify', 'Pay\PayController@qrcodeNotify');

Route::group([
    "middleware"=>['frontCheck','userCheck','holdCheck'],
], function(){
    /******线路数据*******/
    Route::group([
        'prefix' => 'api','namespace'  => 'Tms',
    ], function(){
        /**TMS钱包 - 用户端、承运端**/
        Route::any('/wallet/money', 'WalletController@money');
        Route::any('/wallet/money_info', 'WalletController@money_info');
        Route::any('/wallet/ti', 'WalletController@ti');
        Route::any('/wallet/createAccount', 'WalletController@createAccount');
        Route::any('/wallet/accountPage', 'WalletController@accountPage');
        Route::any('/wallet/accountAdd', 'WalletController@accountAdd');
        Route::any('/wallet/accountUseFlag', 'WalletController@accountUseFlag');
        Route::any('/wallet/accountDelFlag', 'WalletController@accountDelFlag');
        Route::any('/wallet/wallet_info', 'WalletController@wallet_info');
        Route::any('/wallet/withdraw_money', 'WalletController@withdraw_money');
        Route::any('/wallet/getAccount', 'WalletController@getAccount');
        Route::any('/wallet/get_wallet', 'WalletController@get_wallet');
        /**TMS线路列表 - 用户端**/

        /**TMS仓库列表 - 承运端**/
        Route::any('/warehouse/warehousePage', 'WarehouseController@warehousePage');
        Route::any('/warehouse/createWarehouse', 'WarehouseController@createWarehouse');
        Route::any('/warehouse/addWarehouse','WarehouseController@addWarehouse');
        Route::any('/warehouse/warehouseUseFlag','WarehouseController@warehouseUseFlag');
        Route::any('/warehouse/warehouseDelFlag','WarehouseController@warehouseDelFlag');
        Route::any('/warehouse/details','WarehouseController@details');
        /**TMS订单管理 - 用户端**/
        Route::any('/order/orderPage', 'OrderController@orderPage');
        Route::any('/order/details', 'OrderController@details');
        Route::any('/order/addOrder','OrderController@addOrder');
        Route::any('/order/orderUnline','OrderController@orderUnline');
        Route::any('/order/order_cancel','OrderController@order_cancel');
        Route::any('/order/order_done','OrderController@order_done');
        Route::any('/order/addFreeRide','OrderController@addFreeRide');//顺风车
        Route::any('/order/addUserFreeRide','OrderController@addUserFreeRide');//顺风车
        Route::any('/order/userFreeRideList','OrderController@userFreeRideList');//顺风车列表
        Route::any('/order/freeRideList','OrderController@freeRideList');//顺风车列表
        Route::any('/order/liftOrder','OrderController@liftOrder');//顺风车列表

        /*** 快捷下单***/
        Route::any('/fastOrder/addFastOrder','FastOrderController@addFastOrder');//
        Route::any('/fastOrder/fastOrderPage','FastOrderController@fastOrderPage');//
        Route::any('/fastOrder/orderDetails','FastOrderController@orderDetails');//
        Route::any('/fastOrder/fastOrderCancel','FastOrderController@fastOrderCancel');//
        Route::any('/fastOrder/fastOrderDone','FastOrderController@fastOrderDone');//
        Route::any('/fastOrder/predictPrice','FastOrderController@predictPrice');//
        Route::any('/fastOrder/getType','FastOrderController@getType');//
        Route::any('/fastOrder/count_klio','FastOrderController@count_klio');//
        Route::any('/fastOrder/count_price','FastOrderController@count_price');//

        /**TMS联系人管理 - 用户端**/
        Route::any('/contacts/contactsPage', 'ContactsController@contactsPage');
        Route::any('/contacts/addContacts','ContactsController@addContacts');
        Route::any('/contacts/details', 'ContactsController@details');
        Route::any('/contacts/contactsUseFlag','ContactsController@contactsUseFlag');
        Route::any('/contacts/contactsDelFlag','ContactsController@contactsDelFlag');

        /**TMS地址管理 - 用户端**/
        Route::any('/address/addressPage', 'AddressController@addressPage');
        Route::any('/address/addAddress','AddressController@addAddress');
        Route::any('/address/details', 'AddressController@details');
        Route::any('/address/addressDelFlag', 'AddressController@addressDelFlag');
        Route::any('/address/addressUseFlag', 'AddressController@addressUseFlag');
        Route::any('/address/get_city', 'AddressController@get_city');

        /**TMS车辆管理 - 承运端**/
        Route::any('/car/carList', 'CarController@carList');
        Route::any('/car/carPage', 'CarController@carPage');
        Route::any('/car/details', 'CarController@details');
        Route::any('/car/addCar','CarController@addCar');
        Route::any('/car/carUseFlag', 'CarController@carUseFlag');
        Route::any('/car/carDelFlag', 'CarController@carDelFlag');

        /** TMS用户接单列表**/
        Route::any('/take/orderPage','TakeController@orderPage');
        Route::any('/take/addTake','TakeController@addTake');
        Route::any('/take/details','TakeController@details');
        Route::any('/take/dispatch_order','TakeController@dispatch_order');
        Route::any('/take/dispatch_cancel','TakeController@dispatch_cancel');
        Route::any('/take/carriage_done','TakeController@carriage_done');
        Route::any('/take/upload_receipt','TakeController@upload_receipt');
        Route::any('/take/order_cancel','TakeController@order_cancel');
        Route::any('/take/liftOrder','TakeController@liftOrder');
        Route::any('/take/liftDispatch','TakeController@liftDispatch');
        Route::any('/take/takeOrder','TakeController@takeOrder');//接单列表
        Route::any('/take/fastOrderPage','TakeController@fastOrderPage');//已接订单列表
        Route::any('/take/takeOrderDetails','TakeController@takeOrderDetails');//接单订单详情
        Route::any('/take/addFastTakeOrder','TakeController@addFastTakeOrder');//接单订单详情
        Route::any('/take/takeOrderCancel','TakeController@takeOrderCancel');//接单订单详情
        Route::any('/take/fastOrderDone','TakeController@fastOrderDone');//接单订单详情
        Route::any('/take/addTakeFastOrder','TakeController@addTakeFastOrder');//接单订单详情
        Route::any('/take/fastCarriageCancel','TakeController@fastCarriageCancel');//接单订单详情
        Route::any('/take/dispatchUploadReceipt','TakeController@dispatchUploadReceipt');//接单订单详情

        /** TMS用户开票**/
        Route::any('/bill/order_list','BillController@order_list');
        Route::any('/bill/billPage','BillController@billPage');
        Route::any('/bill/createBill','BillController@createBill');
        Route::any('/bill/billAdd','BillController@billAdd');
        Route::any('/bill/delFlag','BillController@delFlag');
        Route::any('/bill/details','BillController@details');
        Route::any('/bill/orderList','BillController@orderList');
        Route::any('/bill/commonBillList','BillController@commonBillList');
        Route::any('/bill/commonBillPage','BillController@commonBillPage');
        Route::any('/bill/createCommonBill','BillController@createCommonBill');
        Route::any('/bill/addCommonBill','BillController@addCommonBill');
        Route::any('/bill/useCommonBill','BillController@useCommonBill');
        Route::any('/bill/delCommonBill','BillController@delCommonBill');
        Route::any('/bill/billDetails','BillController@billDetails');

        /** TMS评论 ***/
        Route::any('/discuss/discussPage','DiscussController@discussPage');
        Route::any('/discuss/createDiscuss','DiscussController@createDiscuss');
        Route::any('/discuss/addDiscuss','DiscussController@addDiscuss');
        Route::any('/discuss/delFlag','DiscussController@delFlag');
        Route::any('/discuss/billDetails','DiscussController@billDetails');


    });
    Route::group([
        'prefix' => 'api','namespace'  => 'Tms',
    ], function(){
        /**TMS上线订单 - 承运端**/
        Route::any('/carriage/orderOnline', 'CarriageController@orderOnline');//线上点单列表
        Route::any('/carriage/onnlineDetails', 'CarriageController@onnlineDetails');//线上订单详情
        Route::any('/carriage/orderTaking', 'CarriageController@orderTaking');//接单
        Route::any('/driver/driverOrderPage', 'DriverController@driverOrderPage');//3pl司机订单列表
        Route::any('/driver/details', 'DriverController@details');//3pl司机订单列表
        Route::any('/driver/orderDone', 'DriverController@orderDone');//3pl司机订单列表
        Route::any('/driver/upload_receipt', 'DriverController@upload_receipt');//3pl司机订单列表
    });

});

Route::group([
    'prefix' => 'api','namespace'  => 'Tms',
], function(){
    Route::any('/address/get_city', 'AddressController@get_city');
    Route::any('/order/orderList', 'OrderController@orderList');
    Route::any('/take/orderList', 'TakeController@orderList');//app接单列表头部
});

