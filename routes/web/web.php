<?php
/******只供登录使用*******/
use Illuminate\Support\Facades\Route;

/******只供登录使用*******/
Route::get('/login/login', 'Admin\LoginController@login');
Route::any('/login/loginOn', 'Admin\LoginController@loginOn');
Route::any('/login/loginOut', 'Admin\LoginController@loginOut');
Route::any('/up/upload_img', 'Admin\UpController@upload_img');			//上传EXECL文件
Route::any('/up/upload_image', 'Admin\UpController@upload_image');			//上传EXECL文件
Route::any('/up/uploadImg', 'Admin\UpController@uploadImg');			//上传EXECL文件
/******公用文件触发器*******/
Route::group([
    "middleware"=>['loginCheck','group'],'prefix' => '','namespace'  => 'Admin',
], function(){
    /**文件上传*/
    Route::group([
        "middleware"=>['daily'],
    ], function(){
        Route::any('/up/img', 'UpController@img');				//上传图片
        Route::any('/up/execl', 'UpController@execl');			//上传EXECL文件

    });
});



Route::group([
    "middleware"=>['loginCheck','group'],
], function(){
    /******首页模块，需要抓取菜单*******/
    Route::group([
        'prefix' => 'admin','namespace'  => 'Admin',
    ], function(){
        /**首页**/
        Route::any('/index', 'IndexController@index');                                      //首页，主要是拉取菜单

        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/changePwd', 'IndexController@changePwd');                           //修改密码
        });
    });

    /******首页数据展示页面*******/
    Route::group([
        'prefix' => 'indexs','namespace'  => 'Indexs',
    ], function(){
        /**首页数据展示**/
        Route::any('/index', 'IndexsController@index');
    });

    /******商户模块1*******/
    Route::group([
        'prefix' => 'company','namespace'  => 'Company',
    ], function(){
        /**商户设定1**/
        Route::any('/company/getGroup', 'CompanyController@getGroup');                              //抓取可选择的公司
        Route::any('/company/companyList', 'CompanyController@companyList');                        //商户列表
        Route::any('/company/companyPage', 'CompanyController@companyPage');                        //商户分页数据加载
        Route::any('/company/createCompany', 'CompanyController@createCompany');                    //新建商户
        Route::any('/company/createCompanyPay', 'CompanyController@createCompanyPay');              //新建商户支付体系
        Route::any('/company/createCompanyShare', 'CompanyController@createCompanyShare');          //新建商户分享
        Route::any('/company/details', 'CompanyController@details');                  //商户详情
		Route::any('/company/addCompany', 'CompanyController@addCompany');
        Route::group([
            "middleware"=>['daily'],
        ], function(){
			                    //新建商户数据提交
            Route::any('/company/companyUseFlag', 'CompanyController@companyUseFlag');              //商户禁启用
            Route::any('/company/companyDelFlag', 'CompanyController@companyDelFlag');              //商户删除

            Route::any('/company/addCompanyPay', 'CompanyController@addCompanyPay');                //新建商户支付体系数据提交
            Route::any('/company/addCompanyShare', 'CompanyController@addCompanyShare');            //新建商户分享数据提交
        });


        //Route::any('/company/create_company_tax', 'CompanyController@create_company_tax');          //新建商户发票系统
        //Route::any('/company/add_company_tax', 'CompanyController@add_company_tax');                //新建商户发票系统数据提交

        /**商户权限设定**/
        Route::any('/administrate/administrateList', 'AdministrateController@administrateList');            //商户权限列表
        Route::any('/administrate/administratePage', 'AdministrateController@administratePage');            //商户分页数据加载
        Route::any('/administrate/createAdministrate', 'AdministrateController@createAdministrate');        //修改商户权限
        Route::any('/administrate/details', 'AdministrateController@details');      //权限详情
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/administrate/addAdministrate', 'AdministrateController@addAdministrate');  //商户权限数据提交
        });

        /**硬件设备管理**/
        Route::any('/mac/macList', 'MacController@macList');
        Route::any('/mac/macPage', 'MacController@macPage');
        Route::any('/mac/getPath', 'MacController@getPath');                                        //拉取线路
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/mac/macUseFlag', 'MacController@macUseFlag');                              //硬件设备禁启用
            Route::any('/mac/macDeleteFlag', 'MacController@macDeleteFlag');                        //硬件设备删除
            Route::any('/mac/binPath', 'MacController@binPath');                                    //绑定线路
        });


        /**公司申请审核**/
        Route::any('/apply/applyList', 'ApplyController@applyList');
        Route::any('/apply/applyPage', 'ApplyController@applyPage');
        Route::any('/apply/createApply', 'ApplyController@createApply');                            //拿取公司审核的数据
        Route::any('/apply/details', 'ApplyController@details');      //公司审核详情
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/apply/addApply', 'ApplyController@addApply');                              //公司审核的操作
        });



        /*** 差异化设置中心*/
        Route::any('/basic/basicList', 'BasicController@basicList');                                    //基础设置头部
        Route::any('/basic/basicPage', 'BasicController@basicPage');                                    //基础设置分页数据
        Route::any('/basic/createBasic', 'BasicController@createBasic');                                    //基础设置分页数据
		Route::any('/basic/details', 'BasicController@details');      //差异化详情
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/basic/addBasic', 'BasicController@addBasic');                                    //基础设置分页数据
        });


    });

    /******员工模块*******/
    Route::group([
        'prefix' => 'staff','namespace'  => 'Staff',
    ], function(){
        /**权限管理**/
        Route::any('/authority/authorityList', 'AuthorityController@authorityList');                //权限管理列表
        Route::any('/authority/authorityPage', 'AuthorityController@authorityPage');                //权限管理分页数据加载
        Route::any('/authority/createAuthority', 'AuthorityController@createAuthority');            //新建权限管理
        Route::any('/authority/details', 'AuthorityController@details');          //权限详情
		Route::any('/authority/getAuthority', 'AuthorityController@getAuthority');                  //获取权限
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/authority/addAuthority', 'AuthorityController@addAuthority');              //新建权限数据提交
            Route::any('/authority/authorityUseFlag', 'AuthorityController@authorityUseFlag');      //权限禁启用
        });


        /**部门列表**/
        Route::any('/section/sectionList', 'SectionController@sectionList');//部门列表
        Route::any('/section/sectionPage', 'SectionController@sectionPage');//部门分页数据加载
        Route::any('/section/createSection', 'SectionController@createSection'); //部门添加页面
        Route::any('/section/addSection', 'SectionController@addSection'); //部门添加进入数据库
		Route::any('/section/getAddSection', 'SectionController@getAddSection'); //获取部门
        Route::any('/section/sectionUseFlag', 'SectionController@sectionUseFlag'); //部门禁启用
        Route::any('/section/sectionDelFlag', 'SectionController@sectionDelFlag'); //部门删除

        /**员工列表**/
        Route::any('/staff/staffList', 'StaffController@staffList');                                //员工列表
        Route::any('/staff/staffPage', 'StaffController@staffPage');                                //员工分页数据加载
        Route::any('/staff/createStaff', 'StaffController@createStaff');                            //员工添加剂修改页面
        Route::any('/staff/details', 'StaffController@details');                          //员工详情


        Route::group([
            "middleware"=>['daily'],
        ], function(){
			Route::any('/staff/import', 'StaffController@import');
            Route::any('/staff/staffUseFlag', 'StaffController@staffUseFlag');                       //员工禁启用
            Route::any('/staff/staffDelFlag', 'StaffController@staffDelFlag');                       //员工删除
            Route::any('/staff/addStaff', 'StaffController@addStaff');                               //员工添加进入数据库
            Route::any('/staff/passwordRest', 'StaffController@passwordRest');                       //员工密码初始化
        });

    });

    /******系统设置模块*******/
    Route::group([
        'prefix' => 'sys','namespace'  => 'Sys',
    ], function(){
        /**菜单设置**/
        Route::any('/menu/menuList', 'MenuController@menuList');                //权限管理列表
        Route::any('/menu/menuPage', 'MenuController@menuPage');                //权限管理分页数据加载
        Route::any('/menu/createMenu', 'MenuController@createMenu');            //新建权限管理
//        Route::any('/menu/authorityDetails', 'MenuController@authorityDetails');          //权限详情
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/menu/addMenu', 'MenuController@addMenu');              //新建权限数据提交
            Route::any('/menu/menuUseFlag', 'MenuController@menuUseFlag');      //权限禁启用
        });

        /**默认权限管理**/
        Route::any('/authority/authorityList', 'AuthorityController@authorityList');
        Route::any('/authority/authorityPage', 'AuthorityController@authorityPage');
        Route::any('/authority/createAuthority', 'AuthorityController@createAuthority');
//        Route::any('/menu/authorityDetails', 'MenuController@authorityDetails');
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/authority/addAuthority', 'AuthorityController@addAuthority');
            Route::any('/authority/authorityUseFlag', 'AuthorityController@authorityUseFlag');
            Route::any('/authority/authorityDelFlag', 'AuthorityController@authorityDelFlag');
        });

        /**APP版本控制**/
        Route::any('/versions/versionsList', 'VersionsController@versionsList');
        Route::any('/versions/versionsPage', 'VersionsController@versionsPage');

        /**套餐价格数据**/
        Route::any('/package/packageList', 'PackageController@packageList');
        Route::any('/package/packagePage', 'PackageController@packagePage');
        Route::any('/package/createPackage', 'PackageController@createPackage');                                    //创建套餐
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/package/addPackage', 'PackageController@addPackage');                                          //创建套餐入库
        });

		Route::any('/address/address', 'AdderssController@address');//获取tms地址

    });

    /******微信模板消息推送*******/
    Route::group([
        'prefix' => 'message','namespace'  => 'Message',
    ], function(){
        /**微信模板中心**/
        Route::any('/message/messageList', 'MessageController@messageList');                        //微信模板推送消息列表
        Route::any('/message/messagePage', 'MessageController@messagePage');                        //微信模板推送消息列表
        Route::any('/message/createMessage', 'MessageController@createMessage');                    //新建微信模板
        Route::any('//message/details', 'MessageController@details');          //微信模板详情
        Route::group([
            "middleware"=>['daily'],
        ], function(){
            Route::any('/message/addMessage', 'MessageController@addMessage');                      //新建微信模板数据提交
            Route::any('/message/messageUseFlag', 'MessageController@messageUseFlag');              //微信模板禁启用
            Route::any('/message/messageDelFlag', 'MessageController@messageDelFlag');              //微信模板删除
        });

        /*** 推送管理*/
        Route::any('/push/pushList', 'PushController@pushList');
        Route::any('/push/pushPage', 'PushController@pushPage');
        Route::any('/push/pushExcel', 'PushController@pushExcel');
        Route::any('/push/pushDetails', 'PushController@pushDetails');
        Route::any('/push/createPush', 'PushController@createPush');
        Route::any('/push/addPush', 'PushController@addPush');
    });

    /******日志模块*******/
    Route::group([
        'prefix' => 'history','namespace'  => 'History',
    ], function(){
        /**操作记录**/
        Route::any('/history/historyList', 'HistoryController@historyList');                        //操作记录
        Route::any('/history/historyPage', 'HistoryController@historyPage');                        //操作记录分页数据
        Route::any('/history/details', 'HistoryController@details');                  //操作记录详情
        /**登录记录**/
        Route::any('/login/loginList', 'LoginController@loginList');                                //操作记录
        Route::any('/login/loginPage', 'LoginController@loginPage');                                //操作记录分页数据

    });





});

Route::group([
    'prefix' => 'pay',
//        "middleware"=>['loginCheck','group'],
    'namespace'  => 'Pay',
], function(){
    Route::any('/pay/alipay_deposit', 'PayController@alipay_deposit');
    Route::any('/pay/deposit_notify', 'PayController@deposit_notify');
    Route::any('/pay/onlineAlipay', 'PayController@onlineAlipay');
    Route::any('/pay/bulkAlipay', 'PayController@bulkAlipay');
    Route::any('/pay/bulkAlipay_notify', 'PayController@bulkAlipay_notify');
});
Route::group([
    'prefix' => 'crondtab','namespace'  => 'Crondtab',
], function(){
Route::any('/crondtab/userReword', 'CrondtabController@userReword'); //定时下线订单
Route::any('/crondtab/updateDiplasic', 'CrondtabController@updateDiplasic'); //定时下线订单
Route::any('/crondtab/updateUserEntry', 'CrondtabController@updateUserEntry'); //定时下线订单
Route::any('/crondtab/countSalary', 'CrondtabController@countSalary'); //定时下线订单


});














