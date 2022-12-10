<?php
namespace App\Http\Admin\Staff;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Models\Group\SystemAdmin;
use App\Models\Group\SystemAuthority;
use App\Models\Group\SystemGroup;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Group\SystemSection;
class StaffController extends CommonController{
    /***    后台用户头部      /staff/staff/staffList
     */
    public function  staffList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $abc='员工';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/员工导入.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    后台用户分页      /staff/staff/staffPage
     */
    public function staffPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;
        $login      =$request->input('login');
        $name       =$request->input('name');
        $tel        =$request->input('tel');
        $use_flag   =$request->input('use_flag');

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'like','name'=>'login','value'=>$login],
            ['type'=>'!=','name'=>'login','value'=>'admin'],
            ['type'=>'like','name'=>'name','value'=>$name],
            ['type'=>'like','name'=>'tel','value'=>$tel],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];
        $where=get_list_where($search);

        $select=['self_id','login','name','tel','create_user_name','create_time','authority_name','authority_name','group_name','use_flag','section_id','section_name'];

        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemAdmin::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=SystemAdmin::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemAdmin::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=SystemAdmin::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemAdmin::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemAdmin::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach($data['items'] as $k => $v){
            $v->button_info=$button_info;
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    新建或修改员工      /staff/staff/createStaff
     */
    public function  createStaff(Request $request){
		$group_info = $request->get('group_info');//接收中间件产生的参数
        $self_id=$request->input('self_id');
        //$self_id='admin_202007141305178863182910';
        $where_admin=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['system_admin_info']=SystemAdmin::where($where_admin)->first();

        $where['delete_flag']='Y';
        $select=['self_id','authority_name','group_code','group_name'];
        if($data['system_admin_info']){

            $where[]=['group_code','=',$data['system_admin_info']->group_code];
            $data['items']=SystemAuthority::where($where)->select($select)->orderBy('create_time', 'desc')->get();

        }else{

			switch ($group_info['group_id']){
				case 'all':
					$data['items']=SystemAuthority::where($where)->orderBy('create_time', 'desc')->select($select)->get();
					break;

				case 'one':
					$where[]=['group_code','=',$group_info['group_code']];
				   $data['items']=SystemAuthority::where($where)->orderBy('create_time', 'desc')->select($select)->get();
					break;

				case 'more':
					$data['items']=SystemAuthority::where($where)->whereIn('group_code',$group_info['group_code'])->orderBy('create_time', 'desc')->select($select)->get();
					break;

			}

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }


    /***    员工插入数据库      /staff/staff/addStaff
     */
    public function addStaff(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='system_admin';

        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数

        $operationing->access_cause='添加/修改员工';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;


        /**接收数据*/
        $self_id        =$request->input('self_id');
        $login          =$request->input('login');
        $name           =$request->input('name');
        $tel            =$request->input('tel');
        $email          =$request->input('email');
        $authority_id   =$request->input('authority_id');
		$section_id   	=$request->input('section_id');
        $input=$request->all();


        /**虚拟数据
        $login=$input['login']='323233';
        $name=$input['name']='2232333232223232';
        $authority_id=$input['authority_id']='10';
        $authority_id=$input['authority_id']='authority_202101131117198147331775';
		***/
        $rules=[
            'login'=>'required',
            'name'=>'required',
            'authority_id'=>'required',
        ];
        $message=[
            'login.required'=>'登录账号不能为空',
            'name.required'=>'真实姓名不能为空',
            'authority_id.required'=>'权限不能为空',
        ];
        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //效验可操作性
            if($self_id){
                $name_where=[
                    ['login','=',$login],
                    ['self_id','!=',$self_id],
                    ['delete_flag','=','Y'],
                ];
            }else{
                $name_where=[
                    ['login','=',$login],
                    ['delete_flag','=','Y'],
                ];
            }

            $name_count = SystemAdmin::where($name_where)->count();            //检查名字是不是重复

            if($name_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '账号名称重复！';
                return $msg;
            }


            $where_authority=[
                ['delete_flag','=','Y'],
                ['self_id','=',$authority_id],
            ];
            $select=['self_id','authority_name','group_code','group_name'];
            $select2=['self_id','user_number','group_code','group_name','father_group_code'];
            $authority_info=SystemAuthority::with(['systemGroup' => function($query)use($select2) {
                $query->select($select2);
                $query->with(['systemGroup' => function($query)use($select2) {
                    $query->select($select2);
                }]);
            }])->where($where_authority)->select($select)->first();
            if(empty($authority_info)){
                $msg['code']=304;
                $msg['msg']='未查询到选择的权限';
                return $msg;
            }


            /** 做一个可开启用户的数量以及是不是可以创建账号***/
            if($authority_info->group_code != '1234'){
                if($authority_info->systemGroup->father_group_code =='1234'){
                    $user_number=$authority_info->systemGroup->user_number;
                    $grop_code=$authority_info->systemGroup->group_code;
                }else{
                    $user_number=$authority_info->systemGroup->systemGroup->user_number;
                    $grop_code=$authority_info->systemGroup->systemGroup->group_code;
                }

                /***查询出 $grop_code   下面  所有已有的用户数量，以及 下面子公司所有的数量，然后和$user_number  比大小，小于他才可以创建，不然不能创建***/

                $where_Admin=[
                    ['delete_flag','=','Y'],
                    ['group_code','=',$grop_code],
                ];
                $do_user_number=SystemAdmin::where($where_Admin)->count();

                $wrht=[
                    ['delete_flag','=','Y'],
                    ['father_group_code','=',$grop_code],
                ];
                $wrht_arr=SystemGroup::where($wrht)->pluck('group_code')->toArray();

                if($wrht_arr){

                    $where_Admin2=[
                        ['delete_flag','=','Y'],
                    ];
                    $xiaji_number=SystemAdmin::where($where_Admin2)->whereIn('group_code',$wrht_arr)->count();

                }else{
                    $xiaji_number=0;
                }

                //已开启的用户数量是
                $do_user_number=$do_user_number+$xiaji_number;
                if($do_user_number >= $user_number){
                    $msg['code']=306;
                    $msg['msg']='已超过可开启用户最大数量';
                    return $msg;
                }
            }


                /** 现在开始可以做数据了**/
                $data['login']              =$login;
                $data['name']               =$name;
                $data['tel']                =$tel;
                $data['email']              =$email;
                $data['group_code']         =$authority_info->group_code;
                $data['group_name']         =$authority_info->group_name;
                $data['authority_id']       =$authority_info->self_id;
                $data['authority_name']     =$authority_info->authority_name;
				$data['section_id']       	=$section_id;

				$wherrr=[
                        ['self_id','=',$section_id],
						['delete_flag','=','Y'],
                ];

				$data['section_name']       =SystemSection::where($wherrr)->value('section_name');
                $whereSystemAdmin['self_id']=$self_id;
                $old_info=SystemAdmin::where($whereSystemAdmin)->first();


                if($old_info){
                    if($old_info->login=='admin'){
                        $msg['code']=302;
                        $msg['msg']='admin账号不允许修改';
                        return $msg;
                    }else{
                        //执行修改操作
                        $data['update_time']=$now_time;
                        $id=SystemAdmin::where($whereSystemAdmin)->update($data);
                    }

                    $operationing->access_cause='修改员工';
                    $operationing->operation_type='update';

                }else{
                    $data['self_id']=generate_id('admin_');
                    $data['pwd']=get_md5(123456);
                    $data['create_time']=$data['update_time']=$now_time;
                    $data['create_user_id'] =$user_info->admin_id;
                    $data['create_user_name'] = $user_info->name;
                    $id=SystemAdmin::insert($data);

                    $operationing->access_cause='添加员工';
                    $operationing->operation_type='create';

                }


                $operationing->table_id=$self_id?$self_id:$data['self_id'];
                $operationing->old_info=$old_info;
                $operationing->new_info=$data;


                if($id){
                    $msg['code']=200;
                    $msg['msg']='操作成功';
                    return $msg;
                }else{
                    $msg['code']=303;
                    $msg['msg']='操作失败';
                    return $msg;
                }

        }else{
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


    /***    员工列表启禁用      /staff/staff/staffUseFlag
     */

    public function staffUseFlag(Request $request,Status $status){
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='system_admin';
        $medol_name     ='SystemAdmin';
        $self_id        =$request->input('self_id');
        $flag='useFlag';
        //$self_id='group_202007311841426065800243';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='启用/禁用';
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

    /***    员工删除      /staff/staff/staffDelFlag
     */

    public function staffDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='system_admin';
        $medol_name='SystemAdmin';
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

    /***    重置密码      /staff/staff/passwordRest
     */
    public function passwordRest(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数

        $now_time=date('Y-m-d H:i:s',time());
        $table_name='system_admin';

        /** 接收数据*/
        $self_id=$request->input('self_id');

        //$self_id='admin_202008120934017866687877';

        $where['self_id']=$self_id;
        $select_admin=['self_id','login','group_code','group_name','name','pwd','update_time'];
        $old_info=SystemAdmin::where($where)->select($select_admin)->first();


        $operationing->access_cause='重置密码';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$old_info;
        $operationing->new_info=null;
        $operationing->operation_type='update';

        if($old_info){
            $data['pwd']=get_md5(123456);
            $data['update_time']=$now_time;

            $id=SystemAdmin::where($where)->update($data);
            if($id){
                $msg['code']=200;
                $msg['msg']='密码重置成功';
                $operationing->new_info=$data;
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='密码重置失败';
                return $msg;
            }

        }else{
            $msg['code']=302;
            $msg['msg']='用户不存在';
            return $msg;
        }
    }

    /***    员工导入      /staff/staff/import
     */
    public function import(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $operationing       = $request->get('operationing');//接收中间件产生的参数

        $table_name         ='system_admin';
        $now_time           = date('Y-m-d H:i:s', time());
        $operationing->access_cause     ='导入创建后台账号';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');

        /****虚拟数据
        $input['importurl']    =$importurl="uploads/2020-11-24/员工导入.xlsx";
        $input['group_code']   =$group_code='1234';
         ***/
        $rules = [
            'group_code' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'group_code.required' => '请选择公司',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
            $res = Excel::toArray((new Import),$importurl);

            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }

            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
                '登录用户名' =>['Y','N','64','login'],
                '权限' =>['Y','Y','64','authority_name'],
                '名字' =>['Y','Y','64','name'],
                '密码' =>['N','Y','64','pwd'],
            ];
            $ret=arr_check($shuzu,$info_check);

            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }

            $info_wait=$ret['new_array'];

            $where_pack['group_code'] = $group_code;
            $where_pack['delete_flag'] = 'Y';
            $group_name = SystemGroup::where($where_pack)->value('group_name');

            if(empty($group_name)){
                $msg['code'] = 302;
                $msg['msg'] = '公司不存在';
                return $msg;
            }

            /** 二次效验结束**/
            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            foreach($info_wait as $k => $v){
                $list=[];
                //效验数据的有效性
                $where=[
                    ['delete_flag','=','Y'],
                    ['login','=',$v['login']],
                ];
                $staff_info = SystemAdmin::where($where)->value('group_code');
                if($staff_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行登录账号已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $where_authority=[
                    ['delete_flag','=','Y'],
                    ['authority_name','=',$v['authority_name']],
                    ['group_code','=',$group_code],
                ];
                $authority_info = SystemAuthority::where($where_authority)->select('self_id','authority_name')->first();
                if(empty($authority_info)){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行权限不存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }


                if($cando =='Y'){
                    $list['self_id']            =generate_id('admin_');
                    $list['login']              = $v['login'];
                    $pwd                        =$v['pwd']??'123456';
                    $list['pwd']                =get_md5($pwd);
                    $list['name']               = $v['name'];
                    $list['authority_id']       = $authority_info->self_id;
                    $list['authority_name']     = $authority_info->authority_name;
                    $list['group_code']         = $group_code;
                    $list['group_name']         = $group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }

                $a++;

            }

            $operationing->new_info=$datalist;
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }

            $count=count($datalist);
            $id= SystemAdmin::insert($datalist);

            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
                return $msg;
            }

        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }



    }


    /***    员工详情     /staff/staff/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='system_admin';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'login','name','tel','email','authority_id','authority_name','total_user_id','true_name','cms_show','menu_id','group_id','group_id_show'];
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/


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


}
?>
