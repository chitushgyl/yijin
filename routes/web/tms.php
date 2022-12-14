<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/29
 * Time: 13:23
 */

/******WMS系统*******/
Route::group([
    'prefix' => 'tms',
    "middleware"=>['loginCheck','group'],
    'namespace'  => 'Tms',
], function(){


    /**TMS车辆类型管理**/
    Route::any('/type/typeList', 'TypeController@typeList');
    Route::any('/type/typePage', 'TypeController@typePage');
    Route::any('/type/createType','TypeController@createType');
    Route::any('/type/details', 'TypeController@details');
    Route::any('/type/getType','TypeController@getType');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
    Route::any('/type/addType','TypeController@addType');
    Route::any('/type/typeUseFlag', 'TypeController@typeUseFlag');
    Route::any('/type/typeDelFlag', 'TypeController@typeDelFlag');
    Route::any('/type/import', 'TypeController@import');
    });

    /**TMS车辆管理**/
    Route::any('/car/carList', 'CarController@carList');
    Route::any('/car/carPage', 'CarController@carPage');
    Route::any('/car/createCar','CarController@createCar');
    Route::any('/car/details', 'CarController@details');
    Route::any('/car/getCar','CarController@getCar');
    Route::any('/car/addCar','CarController@addCar');
    Route::any('/car/carUseFlag', 'CarController@carUseFlag');
    Route::any('/car/carDelFlag', 'CarController@carDelFlag');
    Route::any('/car/import', 'CarController@import');
    Route::any('/car/execl', 'CarController@execl');
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/car/addCar','CarController@addCar');
        Route::any('/car/carUseFlag', 'CarController@carUseFlag');
        Route::any('/car/carDelFlag', 'CarController@carDelFlag');
        Route::any('/car/import', 'CarController@import');
        Route::any('/car/execl', 'CarController@execl');
    });

    /**TMS车辆业务公司管理**/
    Route::any('/group/groupList', 'GroupController@groupList');
    Route::any('/group/groupPage', 'GroupController@groupPage');
    Route::any('/group/createGroup','GroupController@createGroup');
    Route::any('/group/details', 'GroupController@details');
    Route::any('/group/getCompany','GroupController@getCompany');
    Route::any('/group/getGroup','GroupController@getGroup');

    Route::any('/customer/customerList', 'CustomerController@customerList');
    Route::any('/customer/customerPage', 'CustomerController@customerPage');

    Route::any('/driver/driverList', 'DriverController@driverList');
    Route::any('/driver/driverPage', 'DriverController@driverPage');
    Route::any('/driver/addDriver', 'DriverController@addDriver');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
    Route::any('/group/addGroup','GroupController@addGroup');
    Route::any('/group/groupUseFlag', 'GroupController@groupUseFlag');
    Route::any('/group/groupDelFlag', 'GroupController@groupDelFlag');
//    Route::any('/group/import', 'GroupController@import');
//    Route::any('/group/execl', 'GroupController@execl');
    });

    Route::any('/group/import', 'GroupController@import');
    Route::any('/group/execl', 'GroupController@execl');
    /**TMS联系人管理**/
    Route::any('/contacts/contactsList', 'ContactsController@contactsList');
    Route::any('/contacts/contactsPage', 'ContactsController@contactsPage');
    Route::any('/contacts/createContacts','ContactsController@createContacts');
    Route::any('/contacts/details', 'ContactsController@details');
    Route::any('/contacts/getContacts','ContactsController@getContacts');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
    Route::any('/contacts/addContacts','ContactsController@addContacts');
    Route::any('/contacts/contactsUseFlag', 'ContactsController@contactsUseFlag');
    Route::any('/contacts/contactsDelFlag', 'ContactsController@contactsDelFlag');
    Route::any('/contacts/import', 'ContactsController@import');
    Route::any('/contacts/execl', 'ContactsController@execl');
    });


    /**TMS地址管理**/
    Route::any('/address/addressList', 'AddressController@addressList');
    Route::any('/address/addressPage', 'AddressController@addressPage');
    Route::any('/address/createAddress','AddressController@createAddress');
    Route::any('/address/details', 'AddressController@details');
    Route::any('/address/getAddress','AddressController@getAddress');
    Route::any('/address/execl', 'AddressController@execl');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
    Route::any('/address/addAddress','AddressController@addAddress');
    Route::any('/address/addressUseFlag', 'AddressController@addressUseFlag');
    Route::any('/address/addressDelFlag', 'AddressController@addressDelFlag');
    Route::any('/address/import', 'AddressController@import');
    });


    /**TMS订单管理**/
    Route::any('/order/orderList', 'OrderController@orderList');
    Route::any('/order/orderPage', 'OrderController@orderPage');
    Route::any('/order/createOrder','OrderController@createOrder');
    Route::any('/order/details', 'OrderController@details');
    Route::any('/order/getOrder','OrderController@getOrder');
    Route::any('/order/addOrder','OrderController@addOrder');
    Route::any('/order/orderUseFlag', 'OrderController@orderUseFlag');
    Route::any('/order/orderDelFlag', 'OrderController@orderDelFlag');
	Route::any('/order/addOrder','OrderController@addOrder');
	Route::any('/order/orderCancel','OrderController@orderCancel');
	Route::any('/order/orderDone','OrderController@orderDone');
	Route::any('/order/add_order','OrderController@add_order');
	Route::any('/order/addUserFreeRide','OrderController@addUserFreeRide');
	Route::any('/order/pickOrder','OrderController@pickOrder');
	Route::any('/order/upOrder','OrderController@upOrder');
	Route::any('/order/uploadReceipt','OrderController@uploadReceipt');
	Route::any('/order/dispatchOrder','OrderController@dispatchOrder');
	Route::any('/order/getOrderLog','OrderController@getOrderLog');
	Route::any('/order/import','OrderController@import');
	Route::any('/order/excel','OrderController@excel');

    Route::group([
        "middleware"=>['daily'],
    ], function(){

        Route::any('/order/orderUseFlag', 'OrderController@orderUseFlag');
        Route::any('/order/orderDelFlag', 'OrderController@orderDelFlag');
    });

    /**TMS调度管理**/
    Route::any('/dispatch/dispatchList', 'DispatchController@dispatchList');
    Route::any('/dispatch/dispatchPage', 'DispatchController@dispatchPage');
    Route::any('/dispatch/createDispatch','DispatchController@createDispatch');
    Route::any('/dispatch/dispatchOrder','DispatchController@dispatchOrder');
    Route::any('/dispatch/addDispatch','DispatchController@addDispatch');
    Route::any('/dispatch/details', 'DispatchController@details');
    Route::any('/dispatch/getDispatch','DispatchController@getDispatch');
    Route::any('/dispatch/dispatchCancel','DispatchController@dispatchCancel');
    Route::any('/dispatch/carriageDone','DispatchController@carriageDone');
    Route::any('/dispatch/uploadReceipt','DispatchController@uploadReceipt');

    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/dispatch/online','DispatchController@online');
        Route::any('/dispatch/unline','DispatchController@unline');
    });

    /**TMS费用管理**/
    Route::any('/money/moneyList', 'MoneyController@moneyList');
    Route::any('/money/moneyPage', 'MoneyController@moneyPage');
	Route::any('/money/details', 'MoneyController@details');
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });

    /**TMS费用管理**/
    Route::any('/settle/settleList', 'SettleController@settleList');
    Route::any('/settle/settlePage', 'SettleController@settlePage');
    Route::any('/settle/createSettle','SettleController@createSettle');
    Route::any('/settle/addSettle','SettleController@addSettle');
    Route::any('/settle/createGathering','SettleController@createGathering');
    Route::any('/settle/addGathering','SettleController@addGathering');
    Route::any('/settle/details', 'SettleController@details');
    Route::any('/settle/payment', 'SettleController@payment');
    Route::any('/settle/updateSettle', 'SettleController@updateSettle');
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });

    /** TMS开票**/
    Route::any('/bill/billList','BillController@billList');
    Route::any('/bill/billPage','BillController@billPage');
    Route::any('/bill/createBill','BillController@createBill');
    Route::any('/bill/addBill','BillController@addBill');
    Route::any('/bill/billDelFlag','BillController@billDelFlag');
    Route::any('/bill/details','BillController@details');
    Route::any('/bill/orderList','BillController@orderList');
    Route::any('/bill/order_list','BillController@order_list');
    Route::any('/bill/commonBillList','BillController@commonBillList');
    Route::any('/bill/commonBillPage','BillController@commonBillPage');
    Route::any('/bill/createCommonBill','BillController@createCommonBill');
    Route::any('/bill/addCommonBill','BillController@addCommonBill');
    Route::any('/bill/useCommonBill','BillController@useCommonBill');
    Route::any('/bill/delCommonBill','BillController@delCommonBill');
    Route::any('/bill/billDetails','BillController@billDetails');
    Route::any('/bill/billTitleList','BillController@billTitleList');
    Route::any('/bill/billTitlePage','BillController@billTitlePage');
    Route::any('/bill/billSuccess','BillController@billSuccess');
    Route::any('/bill/createReceipt','BillController@createReceipt');
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });

    /**月公里数，月油耗***/
    Route::any('/car/countPage','CarController@countPage');
    Route::any('/car/addCount','CarController@addCount');

    /**出险记录**/
    Route::any('/car/dangerPage','CarController@dangerPage');
    Route::any('/car/addDanger','CarController@addDanger');

    /**员工管理**/
    Route::any('/user/userList','UserController@userList');
    Route::any('/user/userPage','UserController@userPage');
    Route::any('/user/createUser','UserController@createUser');
    Route::any('/user/addUser','UserController@addUser');
    Route::any('/user/userFlag','UserController@userFlag');
    Route::any('/user/userDelFlag','UserController@userDelFlag');
    Route::any('/user/getUser','UserController@getUser');
    Route::any('/user/details','UserController@details');
    Route::any('/user/import','UserController@import');
    Route::any('/user/execl','UserController@execl');
    Route::any('/user/printUser','UserController@printUser');

    /**员工奖惩记录**/
    Route::any('/userReward/userRewardList','UserRewardController@userRewardList');
    Route::any('/userReward/userRewardPage','UserRewardController@userRewardPage');
    Route::any('/userReward/createUserReward','UserRewardController@createUserReward');
    Route::any('/userReward/addUserReward','UserRewardController@addUserReward');
    Route::any('/userReward/userRewardFlag','UserRewardController@userRewardFlag');
    Route::any('/userReward/userRewardDelFlag','UserRewardController@userRewardDelFlag');
    Route::any('/userReward/details','UserRewardController@details');

    /**车辆维修记录***/
    Route::any('/carService/serviceList','CarServiceController@serviceList');
    Route::any('/carService/servicePage','CarServiceController@servicePage');
    Route::any('/carService/createService','CarServiceController@createService');
    Route::any('/carService/addService','CarServiceController@addService');
    Route::any('/carService/serviceUseFlag','CarServiceController@serviceUseFlag');
    Route::any('/carService/serviceDelFlag','CarServiceController@serviceDelFlag');
    Route::any('/carService/details','CarServiceController@details');
    Route::any('/carService/import','CarServiceController@import');

    /**车辆加油记录***/
    Route::any('/carOil/carList','CarOilController@carList');
    Route::any('/carOil/carPage','CarOilController@carPage');
    Route::any('/carOil/createCar','CarOilController@createCar');
    Route::any('/carOil/addCar','CarOilController@addCar');
    Route::any('/carOil/carUseFlag','CarOilController@carUseFlag');
    Route::any('/carOil/carDelFlag','CarOilController@carDelFlag');
    Route::any('/carOil/import','CarOilController@import');

    /**车辆过路费***/
    Route::any('/roadToll/roadList','RoadTollController@roadList');
    Route::any('/roadToll/roadPage','RoadTollController@roadPage');
    Route::any('/roadToll/createRoad','RoadTollController@createRoad');
    Route::any('/roadToll/addRoad','RoadTollController@addRoad');
    Route::any('/roadToll/roadUseFlag','RoadTollController@roadUseFlag');
    Route::any('/roadToll/roadDelFlag','RoadTollController@roadDelFlag');
    Route::any('/roadToll/import','RoadTollController@import');

    /**车辆事故记录***/
    Route::any('/accident/accidentList','AccidentController@accidentList');
    Route::any('/accident/accidentPage','AccidentController@accidentPage');
    Route::any('/accident/createAccident','AccidentController@createAccident');
    Route::any('/accident/addAccident','AccidentController@addAccident');
    Route::any('/accident/accidentUseFlag','AccidentController@accidentUseFlag');
    Route::any('/accident/accidentDelFlag','AccidentController@accidentDelFlag');
    Route::any('/accident/import','AccidentController@import');

    /***考核记录**/
    Route::any('/examine/examineList','ExamineController@examineList');
    Route::any('/examine/examinePage','ExamineController@examinePage');
    Route::any('/examine/createExamine','ExamineController@createExamine');
    Route::any('/examine/addExamine','ExamineController@addExamine');
    Route::any('/examine/examineUseFlag','ExamineController@examineUseFlag');
    Route::any('/examine/examineDelFlag','ExamineController@examineDelFlag');
    Route::any('/examine/import','ExamineController@import');

});


