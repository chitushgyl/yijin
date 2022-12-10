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

/******ADMIN后台过来的数据操作redis操作   暂时保留，估计后面也不用了*******/
//Route::group([
//    'prefix' => 'web','namespace'  => 'School',
//], function(){
//    /**redis操作*/
//	Route::any('/set_group_info', 'WebController@set_group_info');                   //将公司信息进入reids
//    Route::any('/set_mac_info', 'WebController@set_mac_info');                       //将mac地址配置的线路进入reids
//    Route::any('/set_user_info', 'WebController@set_user_info');                     //操作用户信息进入reids
//    Route::any('/set_path_info', 'WebController@set_path_info');                     //线路信息进入redis
//});
/******自动发车*******/
Route::any('/school/self', 'School\SelfController@self');
/******获取经纬度 *******/
Route::any('/school/long_lat', 'School\LongLatController@longLat');
/******临时使用做模版数据推送*******/
Route::any('/school/sendCartData', 'School\TempDataController@sendCartData');

/******异常消息定时推送 *******/
Route::any('/school/error', 'School\ErrorController@getErrorInfo');

/******首页模块*******/
Route::group([
    'prefix' => 'index','namespace'  => 'School',

], function(){
    /**首页拉取数据显示部分*/
    Route::group([
        "middleware"=>['frontCheck','userCheck','personCheck'],
    ], function(){
        Route::any('/index', 'IndexController@index');                          //   用户鉴别身份的
        Route::any('/patriarch', 'IndexController@patriarch');                  //   家长数据中心
        Route::any('/care', 'IndexController@care');                            //   照管数据中心
        Route::any('/teacher', 'IndexController@teacher');                      //   老师数据中心
        Route::any('/invite', 'IndexController@invite');                        //    照管员邀请码注册
        Route::any('/capture', 'IndexController@capture');                      //   小程序抓点修正线路数据
    });
});





///******人脸识别*******/
//Route::group([
//    'prefix' => 'face',"middleware"=>['miniCheck'],'namespace'  => 'School',
//
//], function(){
//    /**  人脸部分相关工作*/
//    Route::any('/get_child', 'FaceController@get_child');               //人脸识别家长拉取孩子
//  //  Route::any('/faceUpload', 'FaceController@faceUpload');             //图片上传
//    Route::any('/inSQL', 'FaceController@inSQL');                       //oss图片地址入reg表
//});


/******个人中心的学生信息板块*******/
Route::group([
    'prefix' => 'student',"middleware"=>['frontCheck','userCheck','holdCheck','personCheck'],'namespace'  => 'School',

], function(){
    /**  学生信息处理部分*/
    Route::any('/add_child', 'StudentController@add_child');                      //添加学生和自己的关系进入数据库
    Route::any('/get_child', 'StudentController@get_child');                //添加学生和自己的关系进入数据库

});

/******学生请假*******/
Route::group([
    'prefix' => 'holidy',"middleware"=>['frontCheck','userCheck','holdCheck','personCheck'],'namespace'  => 'School',

], function(){
    /**学生请假处理部分*/
    Route::any('/holidy_detail', 'HolidyController@holidy_detail');                             //学生请假详情
    Route::any('/holidy_cancel', 'HolidyController@holidy_cancel');                 			//取消请假

    Route::any('/patriarch_get_holidy', 'HolidyController@patriarch_get_holidy');               //家长拿去学生可请假的时间和该时间段中学生已请假的数据
    Route::any('/patriarch_add_holidy', 'HolidyController@patriarch_add_holidy');               //家长给学生请假
});


/******运输过程管理       *******/
Route::group([
    'prefix' => 'trips',"middleware"=>['frontCheck','userCheck','personCheck'],'namespace'  => 'School',

], function(){
    /**   包括发车，读取家长经纬度，车辆实时信息板块     到站处理等信息     */
    Route::any('/trips', 'TripsController@trips');                                              //   照管发车
    Route::any('/carriage', 'TripsController@carriage');                                         //   拉取车辆实时信息
    Route::any('/ride_status', 'TripsController@ride_status');                                   //   【手动到站】
    Route::any('/change_student_status', 'TripsController@change_student_status');               //   改变学生乘用状态
    Route::any('/path_loglat', 'TripsController@path_loglat');									 // 家长拉取校车实时经纬度
	
});



/******mac线路信息*******/
Route::group([
    'prefix' => 'mac','namespace'  => 'School',

], function(){
    Route::any('/setMac_info', 'MacController@setMac_info');                //mac进入数据库
    Route::any('/getMac_info', 'MacController@getMac_info');                //mac线路信息
    Route::any('/get_loglat', 'MacController@get_loglat');                  //接收安卓端的实时经纬度
    Route::any('/change_status', 'MacController@change_status');            //接收安卓人脸识别后返回的id，修改学生上下车状态

});

