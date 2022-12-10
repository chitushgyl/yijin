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
    "middleware"=>['frontCheck','userCheck'],
], function(){
    /******分类显示*******/
    Route::group([
        'prefix' => 'shop','namespace'  => 'Shop',

    ], function(){
        /*** 分类显示*/
        Route::any('/category', 'CatalogController@category');                     //详细目录

        /*** 搜索显示*/
        Route::any('/serch', 'KwController@serch');                                 //搜索显示
        Route::any('/result', 'KwController@result');                               //搜索显示
        Route::any('/result_del', 'KwController@result_del');                       //搜索记录删除

        /*** 商品数据*/
        Route::any('/good_page', 'GoodController@good_page');                //商品分页加载数据，
        //首页二级菜单，搜索结果页，分类商品页面等
        Route::any('/good_details', 'GoodController@good_details');          //商品数据
        Route::any('/good_track', 'GoodController@good_track');              //商品收藏
        Route::any('/good_sku', 'GoodController@good_sku');                  //商品SKU拉取
        Route::any('/details', 'GoodController@details');          //商品数据

        /*** 商品数据*/
        Route::any('/group', 'GroupController@group');                                 //商品分页加载数据，

        /*** 购物车数据*/
        Route::any('/cart', 'CartController@cart');                                 //商品加入购物车
        Route::any('/add_cart', 'CartController@add_cart');                                 //商品加入购物车
        Route::any('/change_cart_number', 'CartController@change_cart_number');              //购物车中修改数量
        Route::any('/change_cart_check', 'CartController@change_cart_check');              //购物车中是否选中的修改


    });



});




































