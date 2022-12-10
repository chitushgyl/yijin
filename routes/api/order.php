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

Route::group([
    "middleware"=>['frontCheck','userCheck','holdCheck'],
], function(){

    /******用户订单中心*******/
    Route::group([
        'prefix' => 'order','namespace'  => 'Order',
    ], function(){
        /*** 用户订单中心*/
        Route::any('/add_order', 'OrderController@add_order');                                //我的订单分页
        Route::any('/order_page', 'OrderController@order_page');                                //我的订单分页
        Route::any('/order_detail', 'OrderController@order_detail');                            //我的订单详情
        Route::any('/order_receipt', 'OrderController@order_receipt');                          //确认收货

        Route::any('/get_order_detail', 'OrderController@get_order_detail');              	//修改价格及核销拉取数据


        Route::any('/order_changge_price_do', 'OrderController@order_changge_price_do');              //修改价格的操作进入数据库
        Route::any('/order_verification', 'OrderController@order_verification');                //订单核销
        Route::any('/order_complain', 'OrderController@order_complain');                        //投诉
        Route::any('/order_service', 'OrderController@order_service');                          //售后服务
        Route::any('/order_cancel', 'OrderController@order_cancel');                          //取消订单

    });


});


































