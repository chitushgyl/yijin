<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/******车辆司机模块*******/
Route::group([
    'prefix' => 'school',"middleware"=>['loginCheck','group'],'namespace'  => 'School',
], function(){
    /*** 车辆设置中心*/
    Route::any('/car/carList', 'CarController@carList');                                      //车辆信息头部
    Route::any('/car/carPage', 'CarController@carPage');                                      //车辆信息分页数据
    Route::any('/car/createCar', 'CarController@createCar');                                  //创建车辆
	Route::any('/car/import', 'CarController@import');                                  //车辆导入
    Route::any('/car/carExcel', 'CarController@carExcel');                                    //车辆导出
    Route::any('/car/carDetails', 'CarController@carDetails');                                //车辆详情
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/car/addCar', 'CarController@addCar');                                    //车辆添加进去数据库
        Route::any('/car/carUseFlag', 'CarController@carUseFlag');                                    //车辆的启用禁用
        Route::any('/car/carDelFlag', 'CarController@carDelFlag');                                    //车辆删除
    });
	
	/***人员基础信息配置***/
    Route::any('/basic_personal/basicPersonList', 'BasicPersonalController@basicPersonList');        //人员基础信息列表
    Route::any('/basic_personal/basicPersonPage', 'BasicPersonalController@basicPersonPage');        //人员基础信息分页
    Route::any('/basic_personal/createBasicPerson', 'BasicPersonalController@createBasicPerson');    //人员基础信息编辑
    Route::any('/basic_personal/addBasicPerson', 'BasicPersonalController@addBasicPerson');          //人员基础信息添加
    Route::any('/basic_personal/import', 'BasicPersonalController@import');    //人员基础信息导入
	
    /*** 人员设置中心*/
    Route::any('/person/personList', 'PersonController@personList');                            //校车人员信息
    Route::any('/person/personPage', 'PersonController@personPage');                            //校车信息分页
    Route::any('/person/createPerson', 'PersonController@createPerson');                       //修改人员
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/person/addPerson', 'PersonController@addPerson');                              //新增人员

    });
    Route::any('/person/import', 'PersonController@import');                          //一键导入
    Route::any('/person/personExcel', 'PersonController@personExcel');                          //人员一键导出

    /*** 线路设置中心*/
    Route::any('/line/lineList', 'LineController@lineList');
    Route::any('/line/linePage', 'LineController@linePage');
    Route::any('/line/getInfo', 'LineController@getInfo');                                    //拿到可以管理的公司

    Route::any('/line/createLine', 'LineController@createLine');                                    //添加线路
	Route::any('/line/pathwayStation', 'LineController@pathwayStation');                            //获取该线路上所有可用站点
    Route::any('/line/linePathway', 'LineController@linePathway');                                  //配置线路站点(获取已配置的站点数据)
    Route::any('/line/pathwayStudent', 'LineController@pathwayStudent');                            //配置途径点学生(点击图标配置学生)
    Route::any('/line/lineExcel', 'LineController@lineExcel');                            			//配置途径点学生(点击图标配置学生)
    Route::any('/line/studentImport', 'LineController@studentImport');                              //配置途径点学生(点击图标配置学生)
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/line/lineAdd', 'LineController@lineAdd');
        Route::any('/line/linePathwayAdd', 'LineController@linePathwayAdd');                            //配置线路站点进数据(站点数据提交)
        Route::any('/line/linePathwayDelete', 'LineController@linePathwayDelete');                      //删除途经点(删除站点)
        Route::any('/line/pathwayStudentAdd', 'LineController@pathwayStudentAdd');                      //途经点学生进数据（学生数据进库）
    });

    /*** 收费管理*/
    Route::any('/cost/costList', 'CostController@costList');
    Route::any('/cost/costPage', 'CostController@costPage');
    Route::any('/cost/createCost', 'CostController@createCost');
    Route::any('/cost/addCost', 'CostController@addCost');


    /*** 请假管理*/
    Route::any('/holidy/holidyList', 'HolidayController@holidyList');
    Route::any('/holidy/holidyPage', 'HolidayController@holidyPage');
    Route::any('/holidy/cancelHolidy', 'HolidayController@cancelHolidy');
    Route::any('/holidy/holidayExcel', 'HolidayController@holidayExcel');
	
	/*** 点名管理*/
    Route::any('/call/callList', 'CallController@callList'); //点名列表
    Route::any('/call/callPage', 'CallController@callPage');//点名分页
    Route::any('/call/callDetails', 'CallController@callDetails');//点名详情
    Route::any('/call/callData', 'CallController@callData');
    Route::any('/call/callExcel', 'CallController@callExcel'); //点名导出

});


/******申请校车乘坐*******/
Route::group([
    'prefix' => 'school',"middleware"=>['loginCheck','group'],'namespace'  => 'School',
], function(){
    /*** 申请校车乘坐*/
    Route::any('/apply/applyList', 'ApplyController@applyList');                            //校车信息头部
    Route::any('/apply/applyPage', 'ApplyController@applyPage');                            //校车信息分页
    Route::any('/apply/excelImport', 'ApplyController@excelImport');                        //一键导入学生申请


});


/******实时  及 历史 数据运行***/
Route::group([
    'prefix' => 'school',"middleware"=>['loginCheck','group'],'namespace'  => 'School',
], function(){
    /******线路信息路由*******/
    Route::any('/history/historyInfo', 'HistroyController@historyInfo');                      //获取饼状图和线路的数据信息
    Route::any('/history/getAddress', 'HistroyController@getAddress');                      //获取线路的历史数据

    /******实时运行***/
    Route::any('/real/real_line', 'RealController@real_line');
    Route::any('/real/real_info', 'RealController@real_info');
    Route::any('/real/real_pathway', 'RealController@real_pathway');
    Route::any('/real/real_count', 'RealController@real_count');                    //获取线路的历史数据
});


Route::any('test', 'School\TestController@test');
Route::any('test2', 'School\TestController@test2');
Route::any('test3', 'School\TestController@test3');








