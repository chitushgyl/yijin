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

Route::group([
    "middleware"=>['loginCheck','group'],
], function(){
    /******商品模块*******/
    Route::group([
        'prefix' => 'goods','namespace'  => 'Goods',
    ], function(){
//        /**商品工业分类1**/
//        Route::any('/classify/classifyList', 'ClassifyController@classifyList');                        //工业分类列表
//        Route::any('/classify/classifyPage', 'ClassifyController@classifyPage');                        //工业分类分页1
//        Route::any('/classify/createClassify', 'ClassifyController@createClassify');                    //新建分类
//        Route::any('/classify/classifyDetails', 'ClassifyController@classifyDetails');
//		Route::any('/classify/getClassify', 'ClassifyController@getClassify');
//
//        Route::group([
//            "middleware"=>['daily'],
//        ], function(){
//            Route::any('/classify/addClassify', 'ClassifyController@addClassify');                          //添加分类
//            Route::any('/classify/classifyUseFlag', 'ClassifyController@classifyUseFlag');                      //  启用，禁用
//            Route::any('/classify/classifyDelFlag', 'ClassifyController@classifyDelFlag');                      //  删除
//        });

        /**商品运费管理**/
        Route::any('/freight/freightList', 'FreightController@freightList');
        Route::any('/freight/freightPage', 'FreightController@freightPage');                        //快递运费分页数据
        Route::any('/freight/details', 'FreightController@details');
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/freight/addFreight', 'FreightController@addFreight');                          //  信息进入
            Route::any('/freight/freightUseFlag', 'FreightController@freightUseFlag');                  //  启用，禁用
        });


//        /**商品服务设置**/
//        Route::any('/serve/serveList', 'ServeController@serveList');//商品服务列表
//        Route::any('/serve/servePage', 'ServeController@servePage');//商品服务数据加载
//        Route::any('/serve/serveClassifyPage', 'ServeController@serveClassifyPage');//商品服务分类数据加载
//
//        Route::any('/serve/create_serve_classify', 'ServeController@create_serve_classify')->name('create_serve_classify'); //拉取商品服务分类数据
//        Route::any('/serve/add_serve_classify', 'ServeController@add_serve_classify')->name('add_serve_classify');          //  服务分类添加
//        Route::any('/serve/serveClassifyUseFlag', 'ServeController@serveClassifyUseFlag');                                  //  服务分类禁启用
//        Route::any('/serve/serveClassifyDelFlag', 'ServeController@serveClassifyDelFlag');                                  //  服务分类删除
//
//
//        Route::any('/serve/create_serve', 'ServeController@create_serve')->name('create_serve');//拉取商品服务数据
//        Route::any('/serve/add_serve', 'ServeController@add_serve')->name('add_serve'); //  服务添加
//        Route::any('/serve/serveUseFlag', 'ServeController@serveUseFlag'); //  服务启用禁用
//        Route::any('/serve/serveDelFlag', 'ServeController@serveDelFlag'); //  服务的删除

        /**基础商品**/
        Route::any('/goods/goodsList', 'GoodsController@goodsList');                                        //基础商品列表
        Route::any('/goods/goodsPage', 'GoodsController@goodsPage');                                        //基础商品数据加载
        Route::any('/goods/createGoods', 'GoodsController@createGoods');                                    //商品添加页面
        Route::any('/goods/details', 'GoodsController@details');
        Route::any('/goods/getGoodsPage', 'GoodsController@getGoodsPage');

        Route::any('/goods/addGoods', 'GoodsController@addGoods');                                          //商品添加进入数据库

        Route::group([
            "middleware"=>['daily'],
        ], function(){

            Route::any('/goods/skuDeleteFlag', 'GoodsController@skuDeleteFlag');                                //SKU删除
            Route::any('/goods/goodsDeleteFlag', 'GoodsController@goodsDeleteFlag');                            //商品删除
            Route::any('/goods/goodsStatusFlag', 'GoodsController@goodsStatusFlag');                            //商品上下架
        });


    });

    /******首页设置*******/
    Route::group([
        'prefix' => 'pages','namespace'  => 'Pages',
    ], function(){
        /**模板设置中心**/
        Route::any('/template/templateList', 'TemplateController@templateList');                    //模板设置列表
        Route::any('/template/templatePage', 'TemplateController@templatePage');                    //模板设置列表分页
        Route::any('/template/createHomeConfig', 'TemplateController@createHomeConfig');            //门店模板设置
        Route::any('/template/createHomeConfigData', 'TemplateController@createHomeConfigData');    //模板内容配置
//        Route::any('/template/getData', 'TemplateController@getData');                              //模板类型数据查询

        //Route::group([
        //    "middleware"=>['daily'],
        //], function(){
            Route::any('/template/addHomeConfig', 'TemplateController@addHomeConfig');                  //门店模板设置进入数据库
            Route::any('/template/configDelFlag', 'TemplateController@configDelFlag');                  //门店模板的删除
            Route::any('/template/configDataDelFlag', 'TemplateController@configDataDelFlag');          //模板内容删除
            Route::any('/template/addHomeConfigData', 'TemplateController@addHomeConfigData');    		//模板内容添加到数据库
        //});


        Route::any('/template/createRelevance', 'TemplateController@createRelevance');
        Route::any('/template/addRelevance', 'TemplateController@addRelevance');
        Route::any('/template/relevanceDataDelFlag', 'TemplateController@relevanceDataDelFlag');


        /**关键字设置**/
        Route::any('/keyword/keywordList', 'KeywordController@keywordList');                                        //关键字列表
        Route::any('/keyword/keywordPage', 'KeywordController@keywordPage');                                        //关键字数据分页加载
        Route::any('/keyword/createKeyword', 'KeywordController@createKeyword');                                    //关键字增改操作

        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/keyword/addKeyword', 'KeywordController@addKeyword');                                                    //创建关键字分类及关键字以及头部搜索入库添加入库
            Route::any('/keyword/kwUseFlag', 'KeywordController@kwUseFlag');                                            //关键字启禁用
            Route::any('/keyword/kwDelFlag', 'KeywordController@kwDelFlag');                                            //关键字删除
            Route::any('/keyword/sortKeyword', 'KeywordController@sortKeyword');                                                  //关键字排序
        });


        /**门店自定义分类管理**/
        Route::any('/catalog/catalogList', 'CatalogController@catalogList');                            //门店目录列表
        Route::any('/catalog/catalogPage', 'CatalogController@catalogPage');                            //门店目录分页
        Route::any('/catalog/createCatalog', 'CatalogController@createCatalog');                        //自定义分类数据操作

        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/catalog/addCatalog', 'CatalogController@addCatalog');                              //自定义分类数据添加进入数据库
            Route::any('/catalog/catalogUseFlag', 'CatalogController@catalogUseFlag');                      //分类禁启用
            Route::any('/catalog/catalogDelFlag', 'CatalogController@catalogDelFlag');                      //分类删除
        });

        /**自定义分类 中商品的处理**/
        Route::any('/good/goodList', 'GoodController@goodList');
        Route::any('/good/goodPage', 'GoodController@goodPage');

        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/good/goodUseFlag', 'GoodController@goodUseFlag');                          //分类禁启用
            Route::any('/good/goodDelFlag', 'GoodController@goodDelFlag');                          //分类删除
        });


    });

    /******客户信息*******/
    Route::group([
        'prefix' => 'user','namespace'  => 'User',
    ], function(){
        /**客户信息**/
        Route::any('/user/userList', 'UserController@userList');
        Route::any('/user/userPage', 'UserController@userPage');
        Route::any('/user/walletPage', 'UserController@walletPage');
		Route::any('/user/userCouponList', 'UserController@userCouponList');
        Route::any('/user/userCouponPage', 'UserController@userCouponPage');
        Route::any('/user/addUserCoupon', 'UserController@addUserCoupon');
	Route::any('/user/details', 'UserController@details');                  //客户信息详情
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/user/addWallet', 'UserController@addWallet');
			Route::any('/user/addUser', 'UserController@addUser');
        });

        /**提现信息**/
		
        Route::any('/extract/extractList', 'ExtractController@extractList');
        Route::any('/extract/extractPage', 'ExtractController@extractPage');
		Route::any('/extract/createExtract', 'ExtractController@createExtract');
        Route::any('/extract/addExtract', 'ExtractController@addExtract');
        Route::any('/extract/details', 'ExtractController@details');                  //提现详情
        /**客户合并信息**/
        Route::any('/combine/combine_list', 'CombineController@combine_list'); //拉取菜单
        Route::any('/combine/combine_page', 'CombineController@combine_page');



    });


    /******营销中心*******/
    Route::group([
        'prefix' => 'marketing','namespace'  => 'Marketing',
    ], function(){
        /**优惠券**/
        Route::any('/coupon/couponList', 'CouponController@couponList');                    //优惠券列表
        Route::any('/coupon/couponPage', 'CouponController@couponPage');                    //优惠券分页数据
        Route::any('/coupon/createCoupon', 'CouponController@createCoupon');                //优惠券数据操作
        Route::any('/coupon/couponSearchGoods', 'CouponController@couponSearchGoods');      //配置商品查询
        Route::any('/coupon/details', 'CouponController@details');
		
		
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/coupon/addCoupon', 'CouponController@addCoupon');                      //优惠券数据提交
            Route::any('/coupon/couponUseFlag', 'CouponController@couponUseFlag');              //优惠券启用禁用
            Route::any('/coupon/couponDelFlag', 'CouponController@couponDelFlag');              //优惠券删除
        });



        /**活动模板管理**/
        Route::any('/activity/activity_list', 'ActivityController@activity_list');
        Route::any('/activity/activity_page', 'ActivityController@activity_page');
    //    Route::any('/dispatch/dispatchSystem_systemCreate', 'DispatchSystemController@dispatchSystem_systemCreate')->name('systemCreate');
        /**活动显示配置**/
        Route::any('/show/show_list', 'ShowController@show_list');
        Route::any('/show/show_page', 'ShowController@show_page');
    //    Route::any('/dispatch/dispatchSystem_systemCreate', 'DispatchSystemController@dispatchSystem_systemCreate')->name('systemCreate')
        /**用户积分获得设置**/
        Route::any('/integral/integral_list', 'IntegralController@integral_list');
        Route::any('/integral/integral_page', 'IntegralController@integral_page');
    //    Route::any('/dispatch/dispatchSystem_systemCreate', 'DispatchSystemController@dispatchSystem_systemCreate')->name('systemCreate');

    });

    /******订单中心*******/
    Route::group([
        'prefix' => 'order','namespace'  => 'Order',
    ], function(){
        /**全部订单**/
        Route::any('/order/orderList', 'OrderController@orderList');
        Route::any('/order/orderPage', 'OrderController@orderPage');
		Route::any('/order/createOrder', 'OrderController@createOrder');                                    //创建订单
        Route::any('/order/addOrder', 'OrderController@addOrder');                                          //订单入库11
        Route::any('/order/createDeliver', 'OrderController@createDeliver');                                    //创建发货
        Route::any('/order/details', 'OrderController@details');                                       //订单详情
        Route::group([
           // "middleware"=>['daily'],
        ], function(){
            Route::any('/order/addDeliver', 'OrderController@addDeliver');                                          //发货处理
        });

        /**订单导出**/
        Route::any('/export/orderList', 'ExportController@orderList');                                            //已导出订单信息头部
        Route::any('/export/orderPage', 'ExportController@orderPage');                                            //已导出订单信息分页

        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/export/export', 'ExportController@export');                                                  //导出数据
            Route::any('/export/addDeliver', 'ExportController@addDeliver');                                          //导入数据进行批量发货处理
        });



    });

    
    /******客户行为分析*******/
    Route::group([
        'prefix' => 'analyze','namespace'  => 'Analyze',
    ], function(){
        /**用户关键字搜索**/
        Route::any('/kw/kwList', 'KwController@kwList');
        Route::any('/kw/kwPage', 'KwController@kwPage');
        /**用户收藏及浏览商品**/
        Route::any('/track/trackList', 'TrackController@trackList');
        Route::any('/track/trackPage', 'TrackController@trackPage');
        /**用户访问页面统计**/
        Route::any('/visit/visitList', 'VisitController@visitList');
        Route::any('/visit/visitPage', 'VisitController@visitPage');
        /**用户位置分析中心**/
        Route::any('/position/positionList', 'PositionController@positionList');
        Route::any('/position/positionPage', 'PositionController@positionPage');
        /**用户购物车分析**/
        Route::any('/cart/cartList', 'CartController@cartList');
        Route::any('/cart/cartPage', 'CartController@cartPage');
    });



});
