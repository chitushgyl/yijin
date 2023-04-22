<?php
namespace App\Http\Admin\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\Group\SystemGroupAuthority;
use App\Models\Group\SystemGroup;
use App\Models\Group\SystemGroupShare;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\FileController as File;
use App\Http\Controllers\CompanyController as Company;
use App\Http\Controllers\DetailsController as Details;

class CompanyController extends CommonController{

    /***    公司信息头部      /company/company/companyList
     */
    public function  companyList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;

    }
    /***    公司信息分页数据      /company/company/companyPage
     */
    public function companyPage(Request $request){
        /** 读取配置文件信息**/
        $business_type  =config('page.business_type');
        $business_type  =array_column($business_type,'name','key');
        $tms_company_type   =array_column(config('tms.tms_company_type'),'name','key');
        /** 接收中间件参数**/
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

//DD($user_info);
        //      dump($img_url);
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $name           =$request->input('name');
        $group_code           =$request->input('group_code');
        $group_name     =$request->input('group_name');
        $tel            =$request->input('tel');


        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'like','name'=>'name','value'=>$name],
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
            ['type'=>'like','name'=>'tel','value'=>$tel],
        ];

        $where=get_list_where($search);

        $select=['self_id','group_code','group_name','name','leader_phone','tel','address',
            'business_type','use_flag','create_user_name','create_time',
            'domain_name','front_name','company_image_url','expire_time','father_group_code','group_qr_code','user_number','company_type'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['total']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;
        }


        //dd($data);

        foreach ($data['items'] as $k=>$v) {
            if($v->father_group_code==$v->group_code){
                $v->pay_app_id='自己收款';
            }else{
                $v->pay_app_id='平台代收';
            }

            $v->group_qr_code=img_for($v->group_qr_code,'no_json');
            $v->company_image_url_show=img_for($v->company_image_url,'one');
            $v->company_type_show = $tms_company_type[$v->company_type]??null;
            if($v->expire_time == '2099-12-31 00:00:00'){
                $v->expire_time='长期有效';
            }
            //公司的业务类型
            $v->business_type_show=$business_type[$v->business_type]??null;

            /** 如果权限  ==10 则 把所有的按钮全部放出来*/
            if($user_info->authority_id == '10' ){
                $v->button_info=$button_info;
            }else{
                if($v->group_code !='1234'){
                    $v->button_info=$button_info;
                }
            }
        }

        $data['ewm_show']='N';
//        dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    新建商户      /company/company/createCompany
     */
    public function createCompany(Request $request){
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $data['business']   =config('page.business_type');
        $data['company_type']   =config('tms.tms_company_type');
        $self_id       =$request->input('self_id');


        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $select=['self_id','business_type','group_name','company_image_url','name','leader_phone','tel','address','longitude','dimensionality','floating_flag','user_number','company_type'];
        $data['info']=SystemGroup::where($where)->select($select)->first();


        if($data['info']){
            $data['info']->company_image_url=img_for($data['info']->company_image_url,'more');
        }

        $data['user_number_flag']='N';
        if($user_info->group_code=='1234'){
            //说明是平台超级管理员
            $data['user_number_flag']='Y';
        }


        //dd($user_info->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//        dd($data['group_info']->toArray());

        return $msg;

    }

    /***    新建商户数据提交      /company/company/addCompany
     */
    public function addCompany(Request $request,File $file,Company $company){
        /** 接收中间件参数*/
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $user_info          = $request->get('user_info');//接收中间件产生的参数

        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='system_group';

        $operationing->access_cause         ='新建/修改商户';
        $operationing->operation_type       ='create';
        $operationing->table                =$table_name;
        $operationing->now_time             =$now_time;
        //dump($user_info);
        //第一步，验证数据
        $input=$request->all();

        /** 接收数据*/
        $self_id                =$request->input('self_id');
        $group_name             =$request->input('group_name');
        $name                   =$request->input('name');
        $leader_phone           =$request->input('leader_phone');
        $tel                    =$request->input('tel');
        $business_type          =$request->input('business_type')??'SHOP';
        $company_image_url      =$request->input('company_image_url');
        $address                =$request->input('address');
        $longitude              =$request->input('longitude');
        $dimensionality         =$request->input('dimensionality');
        $city_code              =$request->input('city_code');
        $city                   =$request->input('city');
        $floating_flag          =$request->input('floating_flag');
        $user_number            ='9999';
        $company_type            =$request->input('company_type');
        // $input['group_name']    =$group_name='4878787878';


        $rules=[
            'group_name'=>'required',
        ];
        $message=[
            'group_name.required'=>'请填写公司名称',
        ];
        if($self_id){
            $name_where=[
                ['self_id','!=',$self_id],
                ['group_name','=',$group_name],
                ['delete_flag','=','Y'],
            ];
        }else{
            $name_where=[
                ['group_name','=',$group_name],
                ['delete_flag','=','Y'],
            ];
        }
		//dump($name_where);
        $group = SystemGroup::where($name_where)->count();
		//dump($group);
		
        if ($group>0){
            $msg['code'] = 308;
            $msg['msg'] = '公司名称不能重复！';
            return $msg;
        }
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            //验证通过，这里处理数据

            //第三步，处理数据
            $data['group_name']         =$group_name;
            $data['name']               =$name;
            $data['leader_phone']       =$leader_phone;
            $data['tel']                =$tel;
            $data['business_type']      =$business_type;
            $data['floating_flag']      =$floating_flag;
            $data['longitude']          =$longitude;
            $data['dimensionality']     =$dimensionality;
            if($longitude){
                $data['map_type']       ='高德';
            }
            $data['city_code']          =$city_code;
            $data['city']               =$city;
            $data['address']            =$address;
            $data['company_type']       =$company_type;

            $data['company_image_url']=img_for($company_image_url,'in');

            //dump($data);
            //初步数据数据完成，已经做好了进入数据库的准备工作1

            $where_save['self_id']=$self_id;
            $old_info=SystemGroup::where($where_save)->first();

            //第四步，开始进行数据库操作1
            if($old_info){
                $operationing->access_cause         ='修改商户';
                $operationing->operation_type       ='update';

                $data['update_time']=$now_time;
                $id=SystemGroup::where($where_save)->update($data);


            }else{
                /** 查询出所有的预配置权限，然后把预配置权限给与这个公司**/
                $where_business=[
                    ['use_flag','=','Y'],
                    ['delete_flag','=','Y'],
                    ['business_type','=',$business_type],
                ];
                $select=['self_id','authority_name','menu_id','leaf_id','type','cms_show'];
                $authority_info=SystemGroupAuthority::where($where_business)->orderBy('create_time', 'desc')->select($select)->get();
                $authority_list=[];
                if($authority_info){
                    foreach ($authority_info as $k => $v){
                        if($v->type == 'system'){
                            $data['menu_id']       =$v->menu_id;
                            $data['leaf_id']       =$v->leaf_id;
                            $data['cms_show']      =$v->cms_show;

                        }else{
                            $authority_list[]=$v;
                        }
                    }

                }

                //说明是新增1
                $group_code                 =generate_id('group_');
                $data['self_id']            =$data['group_code']=$data['group_id']=$group_code;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_id_show']      =$group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['father_group_code']  =$user_info->group_code;
                $data['binding_group_code'] =$user_info->group_code;
                $data['user_number'] 		=$user_number;
                if($user_info->group_code == '1234'){
                    $data['group_group_code'] =$group_code;
                }else{
                    $data['group_group_code'] =$user_info->group_code;
                }




                /***生成一个二维码出来    上传到阿里云OSS上面去  执行createImage！！！！！！！**/
                $browse_type=$request->path();
                $filepath=$file->createImage($browse_type,$group_code,$group_name,$user_info,$now_time);

                if($filepath){
                    $data['group_qr_code']=img_for($filepath,'one_in');
                }

                $id=SystemGroup::insert($data);
                $company->create($business_type,$user_info,$group_code,$group_name,$now_time,$authority_list);
                $operationing->access_cause         ='新建商户';
                $operationing->operation_type       ='create';

            }

            $operationing->table_id=$self_id?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){


                $msg['code']=200;
                $msg['msg']='操作成功';
                return $msg;
            }else{
                $msg['code']=302;
                $msg['msg']='操作失败';
                return $msg;
            }


        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;

            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }
        //记录操作日志

        //dd($msg);


    }

    /***    公司禁用/启用      /company/company/companyUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

    public function companyUseFlag(Request $request,Status $status){
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='system_group';
        $medol_name     ='SystemGroup';
        $self_id        =$request->input('self_id');
        $flag           ='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause     ='启用/禁用';
        $operationing->table            =$table_name;
        $operationing->table_id         =$self_id;
        $operationing->now_time         =$now_time;
        $operationing->old_info         =$status_info['old_info'];
        $operationing->new_info         =$status_info['new_info'];
        $operationing->operation_type   =$flag;

        $msg['code']        =$status_info['code'];
        $msg['msg']         =$status_info['msg'];
        $msg['data']        =$status_info['new_info'];

        return $msg;
    }

    /***    公司删除      /company/company/companyDelFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function companyDelFlag(Request $request,Status $status){
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='system_group';
        $medol_name     ='SystemGroup';
        $self_id        =$request->input('self_id');
        $flag           ='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']        =$status_info['code'];
        $msg['msg']         =$status_info['msg'];
        $msg['data']        =$status_info['new_info'];

        return $msg;

    }

    /***    创建商户的支付信息1      /company/company/createCompanyPay
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createCompanyPay(Request $request){
        //dd($request->all());
        //完成支付需要什么东西！！！！！！
        ////=======【基本信息设置】=====================================
        //	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
        //	const APPID = 'wx1fd362b2a776fb66';
        //	//受理商ID，身份标识
        //	const MCHID = '1573122291';
        //	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
        //	const KEY = '89c383df1bd197f2d398b7a0f07db030';
        //	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
        //	const APPSECRET = 'f9046bc0b3c25a6f9b2f96242d5db77f';
        //
        //	//=======【JSAPI路径设置】===================================
        //	//获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
        //	const JS_API_CALL_URL = 'http://love.quanchengzanzhu.com/index.php/home/wxpay/pay';
        //
        //	//=======【证书路径设置】=====================================
        //	//证书路径,注意应该填写绝对路径
        //	//const SSLCERT_PATH = '/xxx/xxx/xxxx/WxPayPubHelper/cacert/apiclient_cert.pem';
        //	//const SSLKEY_PATH = '/xxx/xxx/xxxx/WxPayPubHelper/cacert/apiclient_key.pem';
        //	const SSLCERT_PATH = '/home/wwwroot/yghome.zhaodaolee.com/Public/payprove/apiclient_cert.pem';
        //	const SSLKEY_PATH = '/home/wwwroot/yghome.zhaodaolee.com/Public/payprove/apiclient_key.pem';
        //
        //	//=======【异步通知url设置】===================================
        //	//异步通知url，商户根据实际开发过程设定
        //	const NOTIFY_URL = 'http://love.quanchengzanzhu.com/index.php/home/wxpay/notify';

        $self_id       =$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $old_info=SystemGroup::where($where)->select('father_group_code','group_code','wx_pay_info')->first();
        //dump($where);dd($old_info);
        if($old_info){
            if($old_info->father_group_code ==$old_info->group_code){
                $old_info->gathering_flag='G';
            }else{
                $old_info->gathering_flag='P';
            }

            $data['gathering_flag']=$old_info->gathering_flag;
            if($old_info->gathering_flag =='G'){
                $data['wx_pay_info']=json_decode($old_info->wx_pay_info,true);
            }else{
                $data['wx_pay_info']=null;
            }


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }


    /***    创建商户的支付信息进入数据库      /company/company/addCompanyPay
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

    public function addCompanyPay(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $table_name         ='system_group';
        $now_time           =date('Y-m-d H:i:s',time());
        /** 接收中间件参数**/
        /// dd($request->all());
        /**接收数据*/
        $self_id            =$request->input('self_id');
        $app_id             =$request->input('app_id');
        $secret             =$request->input('secret');
        $pay_app_id         =$request->input('pay_app_id');
        $mch_id             =$request->input('mch_id');
        $key                =$request->input('key');
        $wx_name            =$request->input('wx_name');
        /**     注意一下，这里应该还有2个文件是发起退款的！！！！    **/

        $gathering_flag     =$request->input('gathering_flag')??'P';


        /*** 虚拟数据
        $self_id='group_202005261028554377477209';
        $app_id='wxc1426c690c07ef8f';
        $secret='b752d798e7476d32212c991a835563bb';
        $pay_app_id='wxc1426c690c07ef8f';
        $mch_id='1519218071';
        $key='89c383df1bd197f2d398b7a0f07db030';
        $wx_name='山溪小镇';
        $gathering_flag='G';
         **/

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','wx_pay_info','group_code'];
        //dd($where);
        $old_info=SystemGroup::where($where)->select($select)->first();
//dump($old_info);
        $operationing->access_cause         ='修改支付信息';
        $operationing->table                =$table_name;
        $operationing->table_id             =$self_id;
        $operationing->now_time             =$now_time;


//dump($old_info);
        if($old_info){
            $data['wx_name']    =$wx_name;
            $data['app_id']     =$app_id;
            $data['secret']     =$secret;
            $data['pay_app_id'] =$pay_app_id;
            $data['mch_id']     =$mch_id;
            $data['key']        =$key;
//dump($data);
            if($gathering_flag == 'P'){
                $up_data['wx_pay_info']         =null;
                $up_data['update_time']         =$now_time;
                $up_data['father_group_code']   ='1234';
            }else{
                $up_data['wx_pay_info']         =json_encode($data,JSON_UNESCAPED_UNICODE);
                $up_data['update_time']         =$now_time;
                $up_data['father_group_code']   =$old_info->group_code;
            }

            //dd($up_data);
            $operationing->old_info             =$old_info;
            $operationing->new_info             =$up_data;
            $operationing->operation_type       ='update';

            $id=SystemGroup::where($where)->update($up_data);

            if($id){
                $msg['code']=200;
                $msg['msg']="修改成功";
                return $msg;
            }else{
                $msg['code']=302;
                $msg['msg']="修改失败";
                return $msg;
            }
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }


    /***    创建商户的分享     /company/company/createCompanyShare
     *      前端传递必须参数：1
     *      前端传递非必须参数：
     */
    public function createCompanyShare(Request $request){

        $self_id       =$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$self_id],
        ];

        //dd($where);
//         $where['group_code']='1234';
        //拿取用户的信息过来
        $select=['share_img','share_title','share_content','start_img','end_img','care_flag','care_tel_flag','school_img',
            'path_img','care_img','identity','location_flag','fen_flag','location_img','fen_img','move_img','icon_img'];

        $data['share_info']=SystemGroupShare::where($where)->select($select)->first();

        if($data['share_info']){
            $data['share_info']->share_img      =img_for($data['share_info']->share_img,'more');
            $data['share_info']->start_img      =img_for($data['share_info']->start_img,'more');
            $data['share_info']->end_img        =img_for($data['share_info']->end_img,'more');
            $data['share_info']->school_img     =img_for($data['share_info']->school_img,'more');
            $data['share_info']->path_img       =img_for($data['share_info']->path_img,'more');
            $data['share_info']->care_img       =img_for($data['share_info']->care_img,'more');
            $data['share_info']->location_img   =img_for($data['share_info']->location_img,'more');
            $data['share_info']->fen_img        =img_for($data['share_info']->fen_img,'more');
            $data['share_info']->move_img       =img_for($data['share_info']->move_img,'more');
            $data['share_info']->icon_img       =img_for($data['share_info']->icon_img,'more');
        }

//        dd($data['share_info']->toArray());

        $data['text_info']=[
            ['key'=>'share_title',
                'flag'=>'input',
                'name'=>'分享标题'],

            ['key'=>'share_content',
                'flag'=>'input',
                'name'=>'分享内容'],
            /***
            ['key'=>'identity',
            'flag'=>'input',
            'name'=>'照管昵称'],

            ['key'=>'fen_flag',
            'flag'=>'select',
            'name'=>'分享图标'],

            ['key'=>'care_flag',
            'flag'=>'select',
            'name'=>'照管显示'],

            ['key'=>'care_tel_flag',
            'flag'=>'select',
            'name'=>'照管电话'],

            ['key'=>'location_flag',
            'flag'=>'select',
            'name'=>'定位图标'],
             */
        ];

        $data['key_info']=[
            ['key'=>'share_img',
                'count'=>'1',
                'name'=>'分享图片'],

            /***
            ['key'=>'start_img',
            'count'=>'1',
            'name'=>'开机动画'],
            ['key'=>'end_img',
            'count'=>'1',
            'name'=>'结束动画'],
            ['key'=>'school_img',
            'count'=>'1',
            'name'=>'学校图片'],
            ['key'=>'path_img',
            'count'=>'1',
            'name'=>'线路图片'],
            ['key'=>'care_img',
            'count'=>'1',
            'name'=>'照管图片'],

            ['key'=>'move_img',
            'count'=>'1',
            'name'=>'移动图片'],
            ['key'=>'icon_img',
            'count'=>'1',
            'name'=>'站点ICON'],

            ['key'=>'location_img',
            'count'=>'1',
            'name'=>'位置图片'],
            ['key'=>'fen_img',
            'count'=>'1',
            'name'=>'分享图片'],
             * */
        ];

        $msg['code']=200;
        $msg['data']=$data;
        $msg['msg']='获取数据成功';
//        dd($msg);
        return $msg;

    }


    /***    创建商户的11分享进入数据库    /company/company/addCompanyShare
     *      前端传递必须参数：
     *      前端传递非必须参数：
     *      !!!!!!注意，这里有个比较严重的问题，是前端页面然后没有传图片，会导致数据库中的图片清理不掉
     */

    public function addCompanyShare(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='system_group_share';
        /** 接收中间件参数**/
        $user_info          = $request->get('user_info');//接收中间件产生的参数

        /**接收数据*/
        $self_id            =$request->input('self_id');
        $share_title        =$request->input('share_title');
        $share_content      =$request->input('share_content');
        $care_flag          =$request->input('care_flag');
        $care_tel_flag      =$request->input('care_tel_flag');
        $identity           =$request->input('identity');
        $location_flag      =$request->input('location_flag');
        $fen_flag           =$request->input('fen_flag');
        $img_info           =$request->input('img_info');


        //dd($img_info);
        /*** 虚拟数据
        $self_id='group_202008181245478532163499';
        $identity='yy21121y';
        $img_info=[
        ['key'=>'share_img',
        'count'=>'1',
        'name'=>'分享图片',
        'images'=>[
        '0'=>[
        'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-28/54ad0b3b5bfc740765f8b85e6e8253ac.png',
        'height'=>'100',
        'width'=>'750',
        ],
        ],
        ],

        ['key'=>'path_img',
        'count'=>'1',
        'name'=>'分享图片',
        'images'=>[
        '0'=>[
        'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-28/54ad0b3b5bfc740765f8b85e6e8253ac.png',
        'height'=>'100',
        'width'=>'750',
        ],
        ],
        ],


        ];
         **/
        $operationing->access_cause     ='商户分享';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;
        $operationing->new_info         =null;
        $operationing->operation_type   ='update';

        $where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$self_id],
        ];

        $group_self_id=SystemGroup::where($where)->value('self_id');

        if($group_self_id){
            $old_info=SystemGroupShare::where($where)->first();

        }else{
            $msg['code']=300;
            $msg['msg']="缺少必要参数";
            return $msg;

        }

        $data['share_title']        =$share_title;
        $data['share_content']      =$share_content;
        $data['care_flag']          =$care_flag;
        $data['care_tel_flag']      =$care_tel_flag;
        $data['identity']           =$identity;
        $data['location_flag']      =$location_flag;
        $data['fen_flag']           =$fen_flag;

        if($img_info){
            foreach($img_info as $k => $v){
                if(array_key_exists('images', $v)){
                    $data[$v['key']]=img_for($v['images'],'in');
                }
            }
        }


        if($old_info){
            $data['update_time']        =$now_time;
            $id=SystemGroupShare::where($where)->update($data);
            $operationing->table_id     =$old_info->self_id;
            $operationing->old_info     =$old_info;
        }else{
            $data['self_id']            =generate_id('share_');
            $data['group_code']         =$self_id;
            $data['group_name']         =SystemGroup::where('group_code','=',$self_id)->value('group_name');
            $data['create_time']        =$data['update_time']=$now_time;
            $data['create_user_id']     =$user_info->admin_id;
            $data['create_user_name']   =$user_info->name;
            $id							=SystemGroupShare::insert($data);
            $operationing->table_id     =$data['self_id'];

        }

        $operationing->new_info=$data;

        if($id){
            $msg['code']=200;
            $msg['msg']="处理成功";
            return $msg;
        }else{
            $msg['code']=302;
            $msg['msg']="处理失败";
            return $msg;
        }
    }

    /***    详情     /company/company/details
     */
    public function details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='system_group';
        $select=['self_id','group_code','group_name','name','leader_phone','tel','address',
            'business_type','use_flag','create_user_name','create_time',
            'domain_name','front_name','company_image_url','expire_time','father_group_code','group_qr_code','user_number','company_type'];
        $info=$details->details($self_id,$table_name,$select);
        $tms_company_type   =array_column(config('tms.tms_company_type'),'name','key');
        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/

            $info->company_type_show = $tms_company_type[$info->company_type]??null;
            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);

            }


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }

    /***    专门用来抓取可选择的公司数据      /company/company/getGroup
     */
    public function getGroup(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $business_type  = $request->input('business_type');//接收中间件产生的参数


        //再拉取一下可以选择的公司
        if($business_type){
            $where=[
                ['delete_flag','=','Y'],
                ['business_type','=',$business_type],
            ];
        }else{
            $where=[
                ['delete_flag','=','Y'],
            ];
        }

        $select=['self_id','group_name','group_code'];
        switch ($group_info['group_id']){
            case 'all':
                $data['items']=SystemGroup::where($where)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['items']=SystemGroup::where($where)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['items']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;
        }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

}
?>
