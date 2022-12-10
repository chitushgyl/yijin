<?php

use Illuminate\Http\Request;

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


Route::any('/file/images', 'FileController@images');                         //图片上传方法
Route::any('/file/importFile', 'FileController@importFile');                 //EXECL上传方法
Route::any('/file/export', 'FileController@export');                         //EXECL上传方法

///******redis操作*******/
//Route::group([
//    'prefix' => 'redis','namespace'  => '',
//
//], function(){
//    /**redis操作*/
//    Route::any('/set_group_info', 'RedisController@set_group_info');                     //操作存储公司信息进入reids
//    Route::any('/set_mac_info', 'RedisController@set_mac_info');                     	 //操作用户信息进入reids
//    Route::any('/set_user_info', 'RedisController@set_user_info');                       //操作用户信息进入reids
//    Route::any('/set_path_info', 'RedisController@set_path_info');                       //线路信息进入redis
//    Route::any('/set_carriage_info', 'RedisController@set_carriage_info');               //运输信息进入redis
//    Route::any('/set_pvuv_info', 'RedisController@set_pvuv_info');                       //用户PVUV进入redis
//});

/******公用属性*******/
Route::group([
    "middleware"=>['loginCheck','group'],
], function(){
    /****/
    Route::any('/attributeList', 'AttributeController@attributeList');                        //共用属性列表
    Route::any('/attributePage', 'AttributeController@attributePage');                        //共用属性分页
    Route::any('/createAttribute', 'AttributeController@createAttribute');                    //新建共用属性
    Route::any('/attributeDetails', 'AttributeController@attributeDetails');                  //共用属性详情
    Route::any('/getAttribute', 'AttributeController@getAttribute');                          //获取共用属性
    Route::any('/addAttribute', 'AttributeController@addAttribute');                          //添加共用属性
    Route::any('/attributeUseFlag', 'AttributeController@attributeUseFlag');                      //  启用，禁用共用属性
    Route::any('/attributeDelFlag', 'AttributeController@attributeDelFlag');                      //  删除共用属性
    Route::group([
        "middleware"=>['daily'],
    ], function(){

    });



});

Route::group([
    'prefix' => 'message',
], function(){
    /*** 短信模块*/
    Route::any('/send', 'MessageController@send');                              //发送短信验证码
    Route::any('/checkSMSCode', 'MessageController@checkSMSCode');              //核对短信验证码
    Route::any('/message_send', 'MessageController@message_send');              //发送短信信息
});


Route::group([
    'prefix' => 'address',
], function(){
    /*** 短信模块*/
    Route::any('/address', 'AddressController@address');                              //发送短信验证码=
});

/******定时任务控制器*******/
Route::any('/tick', 'TickController@timerTick');

Route::any('/test/news_test', 'TestController@news_test');  