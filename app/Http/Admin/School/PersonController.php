<?php
namespace App\Http\Admin\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\School\SchoolPersonInfo;
use App\Models\School\SchoolPersonInfoCurrent;
use App\Models\Group\SystemGroup;
use App\Tools\Import;
use App\Http\Controllers\FileController as File;
class PersonController extends CommonController{
    /***    校车信息头部      /school/person/personList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function personList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    校车信息分页     /school/person/personPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function personPage(Request $request){
        dd(1);
        /** 读取配置文件信息**/
        $person_type_show   =config('school.person_type');
        $person_type_show   =array_column($person_type_show,'name','key');


      //  dump($person_type_show);
        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
//dd($group_info);
        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $person_type        =$request->input('personType');
        $actual_name        =$request->input('actualName');
        $identity_card      =$request->input('identityCard');
        $group_code         =$request->input('group_code');
        $person_tel         =$request->input('personTel');
        $token_name         =$request->input('token_name');              //reg表的微信昵称查询

       // $token_name='雷';

        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'like','name'=>'person_tel','value'=>$person_tel],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'identity_card','value'=>$identity_card],
            ['type'=>'like','name'=>'actual_name','value'=>$actual_name],
            ['type'=>'all','name'=>'person_type','value'=>$person_type],
        ];
        $where=get_list_where($search);
        //dd($where);
        $select=['self_id','group_name','create_user_name','create_time','use_flag','actual_name',
            'english_name','person_tel','person_type','sex','identity_card','grade_name','class_name','id','total_user_id','union_id'];
        $userRegSearch=[
            ['type'=>'=','name'=>'reg_type','value'=>'WEIXIN'],
            ['type'=>'like','name'=>'token_name','value'=>$token_name],
        ];
        $userRegWhere=get_list_where($userRegSearch);
        $userRegSelect=['union_id','token_name','token_img'];

//        dump($where);
//        dump($userRegSearch);

        $choolPathwayPersonSelect=['person_id','pathway_name','path_id','pathway_type'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SchoolPersonInfo::where($where)->count(); //总的数据量
//has()方法是基于关联关系去过滤模型的查询结果，所以它的作用和where条件非常相似。如果你只使用has('post'),这表示你只想得到这个模型，这个模型的至少存在一个post的关联关系
                //whereHas()方法的原理基本和has()方法相同，但是他允许你自己添加对这个模型的过滤条件
                $data['items']=SchoolPersonInfo::with(['userReg' => function($query)use($userRegWhere,$userRegSelect){
                    $query->where($userRegWhere);
                    $query->select($userRegSelect);
                }])->with(['schoolPathwayPerson' => function($query)use($choolPathwayPersonSelect){
                    $query->select($choolPathwayPersonSelect);
                    $query->where('delete_flag','=','Y');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';

                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SchoolPersonInfo::where($where)->count(); //总的数据量
				//dd($where);
                $data['items']=SchoolPersonInfo::with(['userReg' => function($query)use($userRegWhere,$userRegSelect){
                    $query->where($userRegWhere);
                    $query->select($userRegSelect);
                }])->with(['schoolPathwayPerson' => function($query)use($choolPathwayPersonSelect){
                    $query->select($choolPathwayPersonSelect);
                    $query->where('delete_flag','=','Y');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';

                break;

            case 'more':
                $data['total']=SchoolPersonInfo::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
				$data['items']=SchoolPersonInfo::with(['userReg' => function($query)use($userRegWhere,$userRegSelect){
                    $query->where($userRegWhere);
                    $query->select($userRegSelect);
                }])->with(['schoolPathwayPerson' => function($query)use($choolPathwayPersonSelect){
                    $query->select($choolPathwayPersonSelect);
                    $query->where('delete_flag','=','Y');
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';

                break;
        }

        //dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {

			$v->person_type_show=$person_type_show[$v->person_type];

            $v->button_info=$button_info;
        }
        //dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;

    }

    /***    excel表格导入     /school/import
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function import(Request $request,File $file){
//        dd(12);
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='school_person_info_current';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='人员导入';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
        $file_id            =$request->input('file_id');

        /****虚拟数据***/
        $input['group_code']   =$group_code='group_202009060931404534426532';
        $input['file_id']     =$file_id='file_202101281138013747366443';
        $input['importurl']    =$importurl="uploads/2021-01-28/ef22a98e2929e0207dbcfadacbfe33a1.xlsx"; //正常的人员导入文件
        //$input['importurl']    =$importurl="uploads/2021-01-28/2bd3afc10ab3bbfa7ab4a80a1f023573.xlsx"; //不可重复数据的人员导入文件
        //$input['importurl']    =$importurl="uploads/2021-01-28/bfe3c360962b5bc45da208427ed3e22e.xlsx"; //不可缺失数据的人员导入文件
        //$input['importurl']    =$importurl="uploads/2021-01-28/4e745dcf727c05142dbf0454271eaace.xlsx"; //有基础表中不存在数据的人员导入文件

        $rules = [
            'group_code' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'group_code.required' => '请选择学校',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
            $res = Excel::toArray((new Import),$importurl);
            $info_check=[];
            //获取excel的sheet0文件
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
                '姓名' =>['Y','Y','255','actual_name'],
                '职业' =>['Y','Y','64','person_type'],
                '电话' =>['N','N','64','person_tel'],
                '身份证号' =>['Y','N','64','identity_card'],
                '年级' =>['N','Y','255','grade_name'],
                '班级' =>['N','Y','64','class_name'],
                '工号' =>['N','N','64','job_sn'],
                '班年级简写' =>['N','Y','64','abbr_class'],
            ];
            $ret=arr_check($shuzu,$info_check);
           // dd($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
               // dd($msg);
                return $msg;
            }

            $info_wait=$ret['new_array'];
//            dd($info_wait);
            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$group_code],
            ];
            $info = SystemGroup::where($where_check)->select('group_code','group_name')->first();
            if(empty($info)){
                $msg['code'] = 302;
                $msg['msg'] = '学校不存在';
                //dd($msg);
                return $msg;
            }

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            /** 第一步，联合基础数据表，获取完整的人员信息***/
            //获取身份类型数组
            $all_type=config('school.person_type');
            foreach($info_wait as $k => $v){

//                $info_wait[$k]['group_code']=$info->group_code;
//                $info_wait[$k]['group_name']=$info->group_name;

                //第一步，开始通过身份证号（或者学生的学籍号）去人员基础表中获取数据信息
                $where_basic_person=[
                    'delete_flag'=>'Y',
                    'identity_card'=>$v['identity_card']
                ];
                $temp=SchoolPersonInfo::where($where_basic_person)->select('self_id','sex','english_name')->first();
                if($temp){
                    //如果excel表格中的人在基础表中可以查到，则查出id和性别
                    $info_wait[$k]['person_id']=$temp->self_id;
                    $info_wait[$k]['sex']=$temp->sex;
                    $info_wait[$k]['english_name']=$temp->english_name;
                }else{
                    //如果excel表中的人，在基础表中没有查到，则将人员信息同时insert到人员基础表中
                    $info_wait[$k]['person_id']=generate_id('info_');
                    $info_wait[$k]['sex']=null;
                    $info_wait[$k]['english_name']=null;

                    //信息回填到人员基础表
                    $data['self_id']            =$info_wait[$k]['person_id'];
                    $data['actual_name']        =$info_wait[$k]['actual_name'];
                    $data['identity_card']      =$info_wait[$k]['identity_card'];
                    $data['create_user_id']     = $user_info->admin_id;
                    $data['create_user_name']   = $user_info->name;
                    $data['create_time']        =$data['update_time']=$now_time;
                    SchoolPersonInfo::insert($data);
                }
                //第二步，通过配置文件，转化各个职业类型为指定字段
                foreach($all_type as $m=>$n){
                   if($n['name'] == $v['person_type']){
                       $info_wait[$k]['person_type']=$n['key'];
                   }
                }
            }
            /** 第二步，处理数据，准备开始进库[因为上面刚新增的字段，在for循环中拿不到，所以需要重新开一个循环，便于获取所有字段]***/
            foreach($info_wait as $k => $v){
                //开始处理数据的可用性【重复数据剔除】
                //第一步：如果身份id存在，并且delete_flag是Y,表示这位是已经存在的，不可添加
                $where_current=[
                    'identity_card'=>$v['identity_card'],
                    'delete_flag'=>'Y',
                ];
                $is_person_current = SchoolPersonInfoCurrent::where($where_current)->value('identity_card');
                if($is_person_current){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行身份证存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $list=[];
                if($cando =='Y'){
                    $list['self_id']            = generate_id('current_');
                    $list['group_code']         = $group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     =$user_info->admin_id;
                    $list['create_user_name']   =$user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['use_flag']           ='Y';
                    $list['delete_flag']        ='Y';
                    $list['file_id']            =$file_id;

                    $list['person_type']        = $v['person_type'];
                    $list['person_id']          = $v['person_id'];
                    $list['actual_name']        = $v['actual_name'];
                    $list['english_name']       = $v['english_name'];
                    $list['person_tel']         = $v['person_tel'];
                    $list['sex']                = $v['sex'];
                    $list['identity_card']      = $v['identity_card'];
                    $list['grade_name']         = $v['grade_name'];
                    $list['class_name']         = $v['class_name'];
                    $list['abbr_class']         = $v['abbr_class'];
                    $list['job_sn']             = $v['job_sn'];
                    $datalist[]=$list;
                }


                $a++;
            }
            $operationing->new_info=$datalist;
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                //dd($msg);
                return $msg;
            }
            $count=count($datalist);
            $id= SchoolPersonInfoCurrent::insert($datalist);
            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';
               // dd($msg);
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='操作失败';
               // dd($msg);
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



    /***    创建员工信息     /school/person/createPerson
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createPerson(Request $request){
        $data['person_type']   =config('school.person_type');

        $self_id            =$request->input('self_id');
        //$self_id            ='info_202008311630007097669437';

        $where['self_id']   =$self_id;

        $data['group_info']=SchoolInfo::where($where)
            ->select('self_id','group_code','group_name','use_flag','actual_name',
                'english_name','person_tel','person_type','sex','identity_card','grade_name','class_name','id','union_id')
            ->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
		return $msg;
    }

    /***    创建员工信息     /school/person/addPerson
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addPerson(Request $request){
//        dd(12);
        /** 接收中间件参数*/
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $user_info          = $request->get('user_info');//接收中间件产生的参数

        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='school_person_info_current';

        $operationing->access_cause         ='新建/修改人员';
        $operationing->operation_type       ='create';
        $operationing->table                =$table_name;
        $operationing->now_time             =$now_time;
        $input=$request->all();

        /** 接收数据*/
        $self_id                =$request->input('self_id');
        $person_id              =$request->input('person_id');
        $group_code             =$request->input('group_code');
        $actual_name            =$request->input('actual_name');
        $english_name           =$request->input('english_name');
        $person_tel             =$request->input('person_tel');
        $person_type            =$request->input('person_type');
        $sex                    =$request->input('sex');
        $grade_name             =$request->input('grade_name');
        $class_name             =$request->input('class_name');
        $identity_card          =$request->input('identity_card');
        $leave_time             =$request->input('leave_time');
        $job_sn                 =$request->input('job_sn');
        $abbr_class             =$request->input('abbr_class');
        /*** 虚拟数据***/
        $input['self_id']           =$self_id;
        $input['person_id']         =$person_id;
        $input['group_code']        =$group_code='group_202009060931404534426532';
        $input['actual_name']       =$actual_name='真十';
        $input['english_name']      =$english_name='ten';
        $input['person_tel']        =$person_tel='15500000005';
        $input['person_type']       =$person_type='teacher';
        $input['sex']               =$sex='男';
        $input['grade_name']        =$grade_name='二年级';
        $input['class_name']        =$class_name='二班';
        $input['identity_card']     =$identity_card='009';
        $input['job_sn']            =$job_sn='103';
        $input['leave_time']        =$leave_time=null;
        $input['abbr_class']        =$abbr_class=202;

        $rules=[
            'group_code'=>'required',
            'actual_name'=>'required',
            'person_type'=>'required',
            'identity_card'=>'required',
        ];
        $message=[
            'group_code.required'=>'请选择所属学校',
            'actual_name.required'=>'请填写真实姓名',
            'person_type.required'=>'请选择类型',
            'identity_card.required'=>'请填写身份证',
        ];
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            //对数据进行处理   还需要效验什么？？  效验身份证号码唯一性
            if($identity_card){

				if($self_id){
					$where=[
						['identity_card','=',trim($identity_card)],
						['delete_flag','=','Y'],
						['self_id','!=',$self_id],
					];
				}else{
					$where=[
						['identity_card','=',trim($identity_card)],
						['delete_flag','=','Y'],
					];
				}
//                dd($where);
				$self_id2=SchoolPersonInfoCurrent::where($where)->value('self_id');
				if($self_id2){
					$msg['code'] = 301;
					$msg['msg'] = '身份证号码重复';
                    //dd($msg);
					return $msg;
				}
            }

            //现在开始做数据层面

            $data['actual_name']    = $actual_name;
            $data['identity_card']  = trim($identity_card);
            $data['english_name']   = $english_name;
            $data['person_tel']     = $person_tel;
            $data['person_type']    = $person_type;
            $data['sex']            = $sex;
            $data['grade_name']     = $grade_name;
            $data['class_name']     = $class_name;
            $data['abbr_class']     = $abbr_class;
            $data['job_sn']         = $job_sn;
            $data['leave_time']     = $leave_time;



            $where2['self_id'] = $self_id;
            $old_info=SchoolPersonInfoCurrent::where($where2)->first();
            if($old_info){
                //说明是修改
                $data['update_time'] =$now_time;
                $id=SchoolPersonInfoCurrent::where($where2)->update($data);

                $operationing->access_cause='修改人员信息';
                $operationing->operation_type='update';
            }else{
                $wehre222['self_id']=$group_code;
                $group_name = SystemGroup::where($wehre222)->value('group_name');

                //新增人员需要到基础数据中获取基础id【如果基础表中有人直接获取，否则新增一条数据】
                $where_basicPerson=[
                    'identity_card'=>$identity_card,
                    'delete_flag'=>'Y'
                ];
                $temp_person_id=SchoolPersonInfo::where($where_basicPerson)->value('self_id');
                if($temp_person_id){
                    $person_id=$temp_person_id;
                }else{
                    $person_id                   =generate_id('info_');
                    $data1['self_id']            =$person_id;
                    $data1['actual_name']        =$actual_name;
                    $data1['english_name']       =$english_name;
                    $data1['sex']                =$sex;
                    $data1['identity_card']      =$identity_card;
                    $data1['create_user_id']     =$user_info->admin_id;
                    $data1['create_user_name']   =$user_info->name;
                    $data1['create_time']        =$data1['update_time']=$now_time;
                    SchoolPersonInfo::insert($data1);
                }

                $data['self_id']= generate_id('current_');
                $data['person_id']    = $person_id;
                $data['create_user_id'] =$user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['group_code'] =$group_code;
                $data['group_name'] =$group_name;
                $data['create_time'] =$data['update_time'] =$now_time;
                $id=SchoolPersonInfoCurrent::insert($data);
                $operationing->access_cause='新建人员信息';
                $operationing->operation_type='create';
            }
            $operationing->table_id=$self_id?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;
            if($id){
                $msg['code']=200;

                $msg['msg']='操作成功';
                $msg['data']=(object)$data;
               // dd($msg);
                return $msg;
            }else{
                $msg['code']=303;
                $msg['msg']='操作失败';
               // dd($msg);
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
            //dd($msg);
            return $msg;
        }
    }


    /***    员工信息详情     /school/person/personDetails
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function personDetails(Request $request){
        /**接收数据*/
        $self_id            =$request->input('self_id');

        $self_id            ='info_20200925142436572154612';

        /** 根据这个用户的ID，去查询出来这个人的属性，家长，学生，老师，照管，司机，或者无身份
         *  然后根据这个身份方便做功能
         * 老师，要学校的班级，年级，以及班级年级下面的学生信息
         * 学生，要关联的家长，乘坐的线路等信息
         * 家长，关联的学生，以及学生乘坐的线路
         *
         **/
        $where['self_id']   =$self_id;
        $data['school_info']=SchoolInfo::where($where)
            ->select('self_id','group_code','group_name','use_flag','actual_name',
                'english_name','person_tel','person_type','sex','identity_card','grade_name','class_name','id','union_id')
            ->first();

        if($data['school_info']){
            switch ($data['school_info']->person_type){
                case 'student':

                    break;
            }

        }else{
            //如果没有，则查询不到任何信息
            $msg['code']=300;
            $msg['msg']='查询不到任何信息';
            return $msg;
        }
        dd($data['school_info']);
    }

/***    excel表格导出     /school/person/personExcel
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function personExcel(Request $request,File $file){
        $user_info  = $request->get('user_info');//接收中间件产生的参数
        $now_time   =date('Y-m-d H:i:s',time());
        $input      =$request->all();
        /** 接收数据*/
        $group_code     =$request->input('group_code');
        //dd($group_code);
        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择公司',
        ];
        $validator=Validator::make($input,$rules,$message);
          if($validator->passes()){
              /** 下面开始执行导出逻辑**/
              $group_name     =SystemGroup::where('group_code','=',$group_code)->value('group_name');

              //查询条件
              $search=[
                  ['type'=>'=','name'=>'group_code','value'=>$group_code],
                  ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
              ];
              $where=get_list_where($search);
              $select=['group_name','use_flag','actual_name','english_name','person_tel','person_type','identity_card','email','grade_name','class_name'];
              $person_info=SchoolInfo::where($where)->select($select)->get();
//              dd($person_info);
              if($person_info){
                  //设置表头
                  $row = [[
                      "id"=>'ID',
                      "group_name"=>'学校名称',
                      "person_type"=>'身份类型',
                      "actual_name"=>'姓名',
                      "english_name"=>'英文名称',
                      "person_tel"=>'电话',
                      "identity_card"=>'学籍号',
                      "email"=>'邮箱',
                      "grade_name"=>'年级',
                      "class_name"=>'班级',
                  ]];
                  /** 现在根据查询到的数据去做一个导出的数据**/
                  $data_execl=[];
                  foreach ($person_info as $k=>$v){
                      $data_execl[$k]['id']               =($k+1);//id
                      $data_execl[$k]['group_name']       =$v->group_name??null;
                      $temp_type='';
                      switch($v->person_type){
                          case 'care':
                              $temp_type='照管员';
                              break;
                          case 'driver':
                              $temp_type='司机';
                              break;
                          case 'teacher':
                              $temp_type='老师';
                              break;
                          case 'patriarch':
                              $temp_type='家长';
                              break;
                          case 'student':
                              $temp_type='学生';
                              break;
                      };
                      $data_execl[$k]['person_type']      =$temp_type??null;
                      $data_execl[$k]['actual_name']      =$v->actual_name??null;
                      $data_execl[$k]['english_name']     =$v->english_name??null;
                      $data_execl[$k]['person_tel']       =$v->person_tel??null;
                      $data_execl[$k]['identity_card']    =$v->identity_card??null;
                      $data_execl[$k]['email']            =$v->email??null;
                      $data_execl[$k]['grade_name']       =$v->grade_name??null;
                      $data_execl[$k]['class_name']       =$v->class_name??null;
                  }
//                  dd($data_execl);
                  /** 调用EXECL导出公用方法，将数据抛出来***/
                  $browse_type=$request->path();
                  $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);
                  return $msg;
              }else{
                  $msg['code']=301;
                  $msg['msg']="没有数据可以导出";
                  return $msg;
              }
          }else{
              $erro=$validator->errors()->all();
              $msg['msg']=null;
              foreach ($erro as $k=>$v) {
                  $kk=$k+1;
                  $msg['msg'].=$kk.'：'.$v.'</br>';
              }
              $msg['code']=300;
              return $msg;
          }
    }

}
?>
