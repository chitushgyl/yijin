<?php
namespace App\Http\Admin\Pages;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Models\Group\SystemGroup;
use App\Models\Shop\HomeConfig;
use App\Models\Shop\HomeConfigData;
use App\Models\Shop\HomeMenuRelevance;

class TemplateController  extends CommonController{
    /***    首页模板信息头部      /pages/template/templateList
     */
    public function  templateList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    首页模板信息分页      /pages/template/templatePage
     */
	public function templatePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??100;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];
        $where=get_list_where($search);

        $select=['group_code','group_name','use_flag','front_name','father_group_code'];
        $systemGroupSelect=['group_code','front_name'];
        $homeConfigSelect=['self_id','remark','type','use_flag','group_code','group_name','start_time','end_time'];

        $homeConfigWhere=[
            ['delete_flag','=','Y'],
        ];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::with(['systemGroup' => function($query)use($systemGroupSelect) {
                    $query->select($systemGroupSelect);
                }])->with(['homeConfig' => function($query)use($homeConfigSelect,$homeConfigWhere) {
                    $query->select($homeConfigSelect);
                    $query->where($homeConfigWhere);
                    $query->orderBy('sort','asc');
                }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];

                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::with(['systemGroup' => function($query)use($systemGroupSelect) {
                    $query->select($systemGroupSelect);
                }])->with(['homeConfig' => function($query)use($homeConfigSelect,$homeConfigWhere) {
                    $query->select($homeConfigSelect);
                    $query->where($homeConfigWhere);
                    $query->orderBy('sort','asc');
                }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroup::with(['systemGroup' => function($query)use($systemGroupSelect) {
                    $query->select($systemGroupSelect);
                }])->with(['homeConfig' => function($query)use($homeConfigSelect,$homeConfigWhere) {
                    $query->select($homeConfigSelect);
                    $query->where($homeConfigWhere);
                    $query->orderBy('sort','asc');
                }])
                    ->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        $button_info1=[];
        $button_info2=[];
        $button_info3=[];

        foreach ($button_info as $k => $v){
            if(in_array($v->id,[183,185])){
                $button_info1[]=$v;
            }
            if($v->id == '331'){
                $button_info2[]=$v;
            }
            if(in_array($v->id,[331,332,413])){
                $button_info3[]=$v;
            }
        }

        foreach ($data['items'] as $k=>$v) {

            foreach ($v->homeConfig as $kk => $vv){
                if($vv->start_time=='2018-11-30 00:00:00' && $vv->end_time=='2099-12-31 00:00:00'){
                    $vv->time_show='长期有效';
                }else{
                    $vv->time_show=$vv->start_time.'～'.$vv->end_time;
                }

                if($vv->type!='home_menu'){
                    $vv->button_info=$button_info2;
                }else{
                    $vv->button_info=$button_info3;
                }


            }

			$v->father_front_name='http://'.$v->systemGroup->front_name;
            $v->button_info=$button_info1;

        }

        //dd($data['items']->toArray());
       // dump($button_info1);dump($button_info2);dd($button_info3);
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        // dd($msg);
        return $msg;
	}

    /***    门店模板设置      /pages/template/createHomeConfig
     */
	public function createHomeConfig(Request $request){
        $data['home_template']      =config('shop.home_template');
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $self_id='1234';

        $where_group=[
            ['group_code','=',$self_id],
            ['delete_flag','=','Y'],
        ];

        $data['group_info']=SystemGroup::where($where_group)->select('group_code','group_name')->first();


//        dd($data['group_info']->toArray());

        if($data['group_info']){
            $where_has['delete_flag']='Y';
            $where_has['group_code']=$self_id;
            $data['config']=HomeConfig::where($where_has)->orderBy('sort','asc')
                ->select('self_id','template','remark','type','use_flag','start_time','end_time','ground_flag','ground_info')->get();
            foreach ($data['config'] as $k => $v){
                if($v->ground_flag == 'img'){
                    $v->ground_info=img_for($v->ground_info,'more');
                }

                if($v->start_time=='2018-11-30 00:00:00' && $v->end_time=='2099-12-31 00:00:00'){
                    $v->start_time=null;
                    $v->end_time=null;
                }


            }

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;

        }else{
            $msg['code']=301;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }

    /***    门店模板设置进入数据库      /pages/template/addHomeConfig
     */

    public function addHomeConfig(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='home_config';
        $now_time       =date('Y-m-d H:i:s',time());

        $operationing->access_cause     ='修改门店模板';
        $operationing->operation_type   ='update';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();

        /** 接收数据*/
        $self_id=$request->input('self_id');
        $config=$request->input('config');

        /*** 虚拟数据**/
        $input['self_id']=$self_id='1234';
        $input['config']=$config=[
            '0'=>[
                'self_id'=>'config_202007151942351065925894',
                'type'=>'lunbo',
                'remark'=>'我是备注',
                'use_flag'=>'N',
                'start_time'=>'2018-11-30 00:00:00',
                'end_time'=>'2099-12-31 00:00:00',
                'ground_flag'=>'color',
                'ground_info'=>'#154578',
            ],

            '1'=>[
                'type'=>'lunbo',
                'remark'=>'我是备注22222',
                'use_flag'=>'Y',
                'start_time'=>'',
                'end_time'=>'2099-12-31 00:00:00',
                'ground_flag'=>'color',
                'ground_info'=>'#154578',
            ],

            '2'=>[
                'type'=>'home_menu',
                'remark'=>'我是备注2222233232',
                'use_flag'=>'Y',
                'start_time'=>'2018-11-30 00:00:00',
                'end_time'=>'2099-12-31 00:00:00',
                'ground_flag'=>'no',
                'ground_info'=>'',
            ],
        ];



        $rules=[
            'self_id'=>'required',
            'config'=>'required',
        ];
        $message=[
            'self_id.required'=>'您未选择公司',
            'config.required'=>'您未设置任何模板',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $where_group=[
                ['group_code','=',$self_id],
                ['delete_flag','=','Y'],
            ];
            $group_info=SystemGroup::where($where_group)->select('group_code','group_name')->first();
            if(empty($group_info)){
                $msg['code']=301;
                $msg['message']='公司不存在';
                // dd($msg);
                return $msg;
            }

            /**二次效验开始，判断传递过来的数据里面的$v['type']=='home_menu'  在第几个位置，如果位置不在最后一个，则应该是个错误的数据***/
            //$art=array_values($arr);
            $count=count($config);

            //做一个数组，这个里面必须包含的元素
            $rule=['type','use_flag','remark','start_time','end_time','ground_flag','ground_info'];
            $rule_count=count($rule);

            foreach($config as $k => $v){
                $art222=array_keys($v);
                //取一个交集出来，然后比较长度
                $result=array_intersect($rule,$art222);
                $result_count=count($result);
                if($rule_count != $result_count){
                    //说明缺少参数
                    $msg['code']=302;
                    $msg['message']='模板数组缺少必要参数';
                    // dd($msg);
                    return $msg;
                }

//                //如果有二级菜单，那么二级菜单必须在最后一个位置
                $sort=$k+1;
                if($count>1 && $sort<$count && $v['type']=='home_menu'){
                    $msg['code']=303;
                    $msg['message']='二级菜单必须排在最后一位';
                    //dd($msg);
                    return $msg;
                }
            }
            /**二次效验结束***/


            $template=config('shop.home_template');
            $template_info  =array_column($template,'name','key');


            //处理一下arr的数据，然后有self_id  说明是修改，没有的说明是新增
            foreach ($config as $k => $v){
                //开始制作数据
                $data['type']           = $v['type'];
                $data['template']       = $template_info[$v['type']];
                $data['sort']           = $k + 1;
                $data['use_flag']       = $v['use_flag'];
                $data['remark']         = $v['remark'];
                $data['start_time']     = $v['start_time']?$v['start_time']:'2018-11-30 00:00:00';
                $data['end_time']       = $v['end_time']?$v['end_time']:'2099-12-31 00:00:00';

                $data['ground_flag'] = $v['ground_flag'];
                //做底图，或者是底色
                switch ($v['ground_flag']){
                    case 'color':
                        if($v['ground_info']){
                            $data['ground_info'] = $v['ground_info'];
                        }else{
                            $data['ground_flag'] = 'no';
                            $data['ground_flag'] = null;
                        }

                        break;

                    case 'img':
                        if($v['ground_info']){
                            $data['ground_info']=img_for($v['ground_info'],'in');
                        }else{
                            $data['ground_flag'] = 'no';
                            $data['ground_flag'] = null;
                        }
                        break;
                }

//                DUMP($data);
                if(array_key_exists('self_id', $v)){
                    //这里是修改
                    $data['update_time'] = $now_time;
                    $where4['self_id'] =  $v['self_id'];
                    //dump($where4['self_id']);
                    $id=HomeConfig::where($where4)->update($data);
                }else{
                    //这里是新增
                    $data['group_code'] = $group_info->group_code;
                    $data['group_name'] = $group_info->group_name;

                    $data['self_id'] = generate_id('config_');
                    $data['update_time'] = $data['create_time'] = $now_time;
                    $data['create_user_id'] = $user_info->admin_id;
                    $data['create_user_name'] = $user_info->name;
                    $id=HomeConfig::insert($data);

                }

            }
//            dd(1111);
            if($id){
                $msg['code'] = 200;
                $msg['msg'] = '设置成功';
                //dump($msg);
                return $msg;

            }else{
                $msg['code'] = 301;
                $msg['msg'] = '设置失败';
                //dump($msg);
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


    }


    /***    门店模板删除      /pages/template/configDelFlag
     */
    public function  configDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='home_config';
        $medol_name='HomeConfig';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;

    }


    /***    模板内容配置      /pages/template/createHomeConfigData
     */

    public function  createHomeConfigData(Request $request){
        /** 读取配置文件信息**/
        $data['jump_type']  =config('shop.jump');

        /** 接收数据*/
        $self_id=$request->input('self_id');
        $self_id='config_202101111032580068831637';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','group_name','type','template'];
        $where_data=[
            ['use_flag','=','Y'],
            ['delete_flag','=','Y'],
        ];
        $select_data=['self_id','config_id','jump_type','data_value','url','name'];
        $data['info']=HomeConfig::with(['homeConfigData' => function($query)use($where_data,$select_data){
            $query->where($where_data);
            $query->select($select_data);
            $query->orderBy('sort','asc');
        }])->where($where)
            ->select($select)
            ->first();

        if($data['info']){
            foreach ($data['info']->homeConfigData as $k => $v){
                $v->url=img_for($v->url,'more');
            }

            //dd($data['info']->toArray());
            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            //dd($msg);
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }







        $where['self_id']=$self_id;
        $where['delete_flag']='Y';

        if($data['config_title']){

            $where2=[
                ['config_id','=',$self_id],
                ['use_flag','=','Y'],
                ['delete_flag','=','Y'],
            ];

            $data['configData']=HomeConfigData::where($where2)->orderBy('sort','asc')
                ->select('self_id','jump_type','data_value','url','name')
                ->get();

            //对url  进行处理





        }else{
            $msg['code']=301;
            $msg['msg']="没有查询到数据";
            return $msg;
        }
    }


    /***    模板内容删除      /pages/template/configDataDelFlag
     */
    public function  configDataDelFlag(Request $request,Status $status ){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='home_config_data';
        $medol_name='HomeConfigData';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;


    }




    /***    模板数据设置中心添加到数据库      /pages/template/addHomeConfigData
     */

    public function addHomeConfigData(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数

        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='home_config_data';
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $operationing->access_cause     ='新建/修改模板数据';
        $operationing->operation_type   ='create';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $input=$request->all();


        /** 接收数据*/
        $self_id        =$request->input('self_id');
        $configData     =$request->input('configData');

        /*** 虚拟数据***/
        $input['self_id']=$self_id='config_202101111032580068831637';
        $input['configData']=$configData=[
            '0'=>[
                'self_id'=>'configdata202007221603397302971952',
                'jump_type'=>'good',
                'data_value'=>'good_202004231526162726909441',
                'url'=>[
                    '0'=>[
                        'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-14/6e26701f642f4b7912f2db9112e66577.png',
                        'width'=>'500',
                        'height'=>'100',
                    ],
                ],
                'name'=>'轮播1',
            ],

            '1'=>[
                'jump_type'=>'good',
                'data_value'=>'good_202004231548116645683161',
                'url'=>[
                    '0'=>[
                        'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-14/6e26701f642f4b7912f2db9112e66577.png',
                        'width'=>'500',
                        'height'=>'100',
                    ],
                ],
                'name'=>'轮播2',
            ],
        ];



        $rules=[
            'self_id'=>'required',
            'configData'=>'required',
        ];
        $message=[
            'self_id.required'=>'请选择模板',
            'configData.required'=>'配置数据不能为空',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            /**二次效验开始***/
            $where_config=[
                ['self_id','=',$self_id],
                ['delete_flag','=','Y'],
            ];
            $homeconfig=HomeConfig::where($where_config)->select('group_code','group_name','type')->first();
            if(empty($homeconfig)){
                $msg['code']=301;
                $msg['message']='模板不存在';
                // dd($msg);
                return $msg;
            }

            $rule=['jump_type','data_value','url','name'];
            $rule_count         =count($rule);
            //第二步，根据这里里面的类型去做数据效验
            $home_template      =config('shop.home_template');

            $template_info  =array_column($home_template,'must','key');
            $must=$template_info[$homeconfig->type]??null;

            $msg['msg']=null;
            $cando='Y';

            foreach($configData as $k => $v){
                $art222             =array_keys($v);
                $result             =array_intersect($rule,$art222);
                $result_count       =count($result);
                if($rule_count != $result_count){
                    //说明缺少参数11
                    $msg['code']=302;
                    $msg['msg'] ='模板数组缺少必要参数';
                    return $msg;
                }


                $abcs=$k+1;
                foreach ($must as $kk => $vv){
                    if(empty($v[$vv])){
                        $msg['code']=308;
                        $cando='N';
                        switch ($vv){
                            case 'url':
                                $msg['msg'].="，第".$abcs."条的图片地址缺失</br>";
                                break;
                            case 'name':
                                $msg['msg'].="，第".$abcs."条的文字显示缺失</br>";
                                break;
                        }
                    }
                }

            }

            if($cando=='N'){
                return $msg;
            }
            /**二次效验结束***/


            /**** 下面开始可以处理业务逻辑了**/
            foreach($configData as $k => $v){
                $data['config_id']=$self_id;
                $data['name']=$v['name'];
                $data['sort']=$k+1;
                $data['group_code']=$homeconfig->group_code;
                $data['group_name']=$homeconfig->group_name;
                $data['jump_type']=$v['jump_type'];
                $data['data_value']=trim($v['data_value']);
                $data['url'] =img_for($v['url'],'in');

                if (array_key_exists('self_id', $v)) {
                    //这个里面说明包含有self_id          执行修改操作
                    $data['update_time']=$now_time;
                    $where3['self_id']=$v['self_id'];
                    $id=HomeConfigData::where($where3)->update($data);
                }else{
                    $data['self_id']=generate_id('configdata_');
                    $data['create_time']=$data['update_time']=$now_time;
                    $data['create_user_id'] = $user_info->admin_id;
                    $data['create_user_name'] = $user_info->name;
                    $id=HomeConfigData::insert($data);
                }

            }

            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
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


    }

    /***    模板数据二级菜单商品      /pages/template/createRelevance
     */

    public function createRelevance(Request $request){
        $self_id=$request->input('self_id');
        /*** 虚拟数据**/
        $input['self_id']=$self_id='configdata_20210111111332617348868';
        $where_good=[
            ['delete_flag','=','Y'],
            ['config_data_id','=',$self_id],
        ];
        $select=['self_id','relevance_id'];
        $select2=['self_id'];
        $data['info']=HomeMenuRelevance::with(['erpShopGoods' => function($query)use($select2) {
            $query->select($select2);
        }])->where($where_good)->select($select)->get();


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;

    }

    /***    模板数据二级菜单商品      /pages/template/addRelevance
     */

    public function addRelevance(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='home_config';
        $now_time       =date('Y-m-d H:i:s',time());

        $operationing->access_cause     ='修改门店模板';
        $operationing->operation_type   ='update';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();

        /** 接收数据*/
        $self_id                =$request->input('self_id');
        $config_good            =$request->input('config_good');

        /*** 虚拟数据**/
        $input['self_id']=$self_id='configdata_20210111111332617348868';
        $input['config_good']=$config_good=['good_202011271604006585154553','good_202101081338184549989645'];


        $rules=[
            'self_id'=>'required',
            'config_good'=>'required',
        ];
        $message=[
            'self_id.required'=>'您未选择二级菜单',
            'config_good.required'=>'您未配置商品',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $where_config=[
                ['self_id','=',$self_id],
                ['delete_flag','=','Y'],
            ];
            $homeconfigData=HomeConfigData::where($where_config)->select('group_code','group_name','self_id','name')->first();
            if(empty($homeconfigData)){
                $msg['code']=301;
                $msg['message']='模板不存在';
                // dd($msg);
                return $msg;
            }

            foreach ($config_good as $k => $v){
                $where_good=[
                    ['relevance_id','=',$v],
                    ['delete_flag','=','Y'],
                    ['config_data_id','=',$self_id],
                ];
                $rself_id=HomeMenuRelevance::where($where_good)->value('self_id');


                $data['config_data_id']     =$homeconfigData->self_id;
                $data['name']               =$homeconfigData->name;
                $data['type']               ='good';
                $data['sort']               =$k+1;
                $data['relevance_id']       =$v;

                if($rself_id){
                    $data['update_time']=$now_time;

                    $id=HomeMenuRelevance::where($where_good)->update($data);

                }else{
                    $data['self_id']            =generate_id('relevance_');
                    $data['create_user_id']     =$user_info->admin_id;
                    $data['create_user_name']   =$user_info->name;
                    $data['create_time']        =$data['update_time']=$now_time;
                    $data["group_code"]         =$homeconfigData->group_code;
                    $data["group_name"]         =$homeconfigData->group_name;
                    $id=HomeMenuRelevance::insert($data);
                }
            }


            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
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

    }

    /***    模板数据二级菜单商品      /pages/template/relevanceDataDelFlag
     */

    public function relevanceDataDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='home_menu_relevance';
        $medol_name='homeMenuRelevance';
        $self_id=$request->input('self_id');
        $flag='delFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='删除';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        $msg['code']=$status_info['code'];
        $msg['msg']=$status_info['msg'];
        $msg['data']=$status_info['new_info'];

        return $msg;

    }

}
?>
