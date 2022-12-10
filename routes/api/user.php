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
    /******用户个人中心*******/
    Route::group([
        'prefix' => 'user','namespace'  => 'User',
    ], function(){
        Route::any('/foot', 'UserController@foot');
        /******用户个人中心*******/
//        Route::group([
//            "middleware"=>['telCheck'],
//        ], function(){
            /******用户底部导航*******/

            Route::any('/owm', 'UserController@owm');  //用户个人中心
            Route::any('/newOwm', 'UserController@newOwm');  //用户个人中心
//        });
        Route::any('/get_identity', 'UserController@get_identity');                 //获取身份角色
        Route::any('/update_pwd', 'UserController@update_pwd');                     //修改密码
        Route::any('/get_version', 'UserController@get_version');                   //获取App版本号
        Route::any('/attestation', 'UserController@attestation');                   //企业认证
        Route::any('/details', 'UserController@details');                   //企业认证
        Route::any('/log_off', 'UserController@log_off');                   //企业认证
        Route::any('/getAdvertPop', 'UserController@getAdvertPop');
        Route::any('/getUserTel', 'UserController@getUserTel');
    });
});

Route::group([
    "middleware"=>['frontCheck','userCheck'],
], function(){
    /******用户个人中心*******/
    Route::group([
        'prefix' => 'driver','namespace'  => 'User',
    ], function(){
        /******用户个人中心*******/
        Route::any('/index', 'DriverController@index');                               //首页数据
        Route::any('/foot', 'DriverController@foot');                                 //底部导航
        //用户个人中心
        Route::any('/get_identity', 'DriverController@get_identity');                 //获取身份角色
        Route::any('/update_pwd', 'DriverController@update_pwd');                     //修改密码
        Route::any('/get_version', 'DriverController@get_version');                   //获取App版本号
        Route::any('/attestation', 'DriverController@attestation');                   //企业认证
        Route::any('/details', 'DriverController@details');                   //企业认证
        Route::group([
            "middleware"=>['telCheck'],
        ], function(){
            Route::any('/owm', 'DriverController@owm');
        });
    });
});


Route::group([
    "middleware"=>['frontCheck','userCheck','holdCheck'],
], function(){
    /******用户个人中心*******/
    Route::group([
        'prefix' => 'user','namespace'  => 'User',
    ], function(){
        /******用户个人中心*******/
        Route::any('/get_user', 'UserController@get_user');                         //赠送余额
        Route::any('/check_user', 'UserController@check_user');
        /******用户底部导航控制*******/
        Route::any('/create_foot', 'FootController@create_foot');
        Route::any('/add_foot', 'FootController@add_foot');

        /******绑定身份  tms切换用户*******/
        Route::any('/binding_page', 'BindingController@binding_page');
        Route::any('/create_binding', 'BindingController@create_binding');
        Route::any('/add_binding', 'BindingController@add_binding');
        Route::any('/del_binding', 'BindingController@del_binding');
        Route::any('/switchover', 'BindingController@switchover');


        /******绑定身份*******/
        Route::any('/binding_page', 'BindingController@binding_page');
        Route::any('/create_binding', 'BindingController@create_binding');
        Route::any('/add_binding', 'BindingController@add_binding');

        /** 我的团队*/
        Route::any('/my_group', 'GroupController@my_group');                            //我的团队
        Route::any('/my_group2', 'GroupController@my_group2');                            //我的团队2
        /** 钱包数据*/
        Route::any('/wallet_page', 'WalletController@wallet_page');                     //我的流水记录分页
        Route::any('/creat_withdraw', 'WalletController@creat_withdraw');               //我的提现创建
        Route::any('/add_withdraw', 'WalletController@add_withdraw');                   //我的提现进入数据库
        Route::any('/give_withdraw', 'WalletController@give_withdraw');                 //我的余额赠送

        /** 收藏数据*/
        Route::any('/track_page', 'TrackController@track_page');                       //我的收藏记录分页
        Route::any('/track_delete', 'TrackController@track_delete');                   //收藏记录的删除

        /** 优惠券数据*/
        Route::any('/coupon_page', 'CouponController@coupon_page');                     //我的优惠券记录分页
        Route::any('/exchange_coupon', 'CouponController@exchange_coupon');             //兑换优惠券

        Route::any('/handle_coupon_page', 'CouponController@handle_coupon_page');      //user_coupon_give  的分页数据
        Route::any('/send_coupon_detail', 'CouponController@send_coupon_detail');       //用户用于赠送、出售、红包的优惠券详情
        Route::any('/get_coupon', 'CouponController@get_coupon');                      //获取可以发送的优惠券列表数据
        Route::any('/add_user_coupon_give', 'CouponController@add_user_coupon_give');     //赠送类优惠券进 user_coupon_give库

        Route::any('/add_coupon', 'CouponController@add_coupon');                       //获得优惠券

        /*** 用户地址数据*/
        Route::any('/address_page', 'AddressController@address_page');                          //地址数据
        Route::any('/create_address', 'AddressController@create_address');                       //创建地址
        Route::any('/add_address',   'AddressController@add_address');                           //地址添加
        Route::any('/del_address', 'AddressController@del_address');                           //地址删除

        /*** 用户联系人数据*/
        Route::any('/contacts_page', 'ContactsController@contacts_page');
        Route::any('/create_contacts', 'ContactsController@create_contacts');
        Route::any('/add_contacts',   'ContactsController@add_contacts');
        Route::any('/del_contacts', 'ContactsController@del_contacts');

        /******用户交易密码 修改手机号*******/
        Route::any('/setPassword', 'SetController@setPassword');                     //交易密码

        /*** 用户银行卡数据*/
        Route::any('/bank_page', 'BankController@bank_page');                     //我的银行卡分页
        Route::any('/creat_bank', 'BankController@creat_bank');               //我的银行卡创建
        Route::any('/add_bank', 'BankController@add_bank');                   //我的银行卡进入数据库
        Route::any('/del_bank', 'BankController@del_bank');

        /*** 商户申请数据*/
        Route::any('/apply_page', 'ApplyController@apply_page');          				//申请的商户分页加载数据，
        Route::any('/create_apply', 'ApplyController@create_apply');    				//创建商户
        Route::any('/add_apply',   'ApplyController@add_apply');                        //商户申请进入数据库
        Route::any('/apply_detail',   'ApplyController@apply_detail');                  //商户申请的详情

    });


});


































