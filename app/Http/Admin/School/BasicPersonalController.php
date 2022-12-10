<?php
namespace App\Http\Admin\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\School\SchoolPersonInfo;
use App\Models\School\SchoolPersonRelation;
use App\Models\Group\SystemGroup;
use App\Tools\Import;
use App\Http\Controllers\FileController as File;
class BasicPersonalController extends CommonController{
    /***    人员基础信息头部      /school/basic_personal/basicPersonList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function basicPersonList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='人员基础信息';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/人员基础信息导入模板.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    人员基础信息分页     /school/basic_personal/basicPersonPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function basicPersonPage(Request $request){
        /** 读取配置文件信息**/
        $person_type_show   =config('school.person_type');
        $person_type_show   =array_column($person_type_show,'name','key');

        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数
        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $actual_name        =$request->input('actual_name');
        $identity_card      =$request->input('identity_card');



        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'like','name'=>'identity_card','value'=>$identity_card],
            ['type'=>'like','name'=>'actual_name','value'=>$actual_name],
        ];
        $where=get_list_where($search);;
        $select=['self_id','create_user_name','create_time','use_flag','actual_name',
            'english_name','sex','identity_card'];

        $data['total']=SchoolPersonInfo::where($where)->count(); //总的数据量
        $data['items']=SchoolPersonInfo::where($where)->select($select)->get();
        foreach ($data['items'] as $k=>$v) {
            $v->button_info=$button_info;
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;

    }

    /***    excel表格导入     /school/basic_personal/import
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function import(Request $request,File $file){

//        dd(1234);
        $table_name         ='school_person_info';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建人员基础信息';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数1


        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $file_id            =$request->input('file_id');

        /****虚拟数据***/
        // $input['importurl']    =$importurl="uploads/2021-01-18/6e5d0d9badb207a7864bdb502c97d1b2.xlsx"; //excel数据重复的=>N
        // $input['importurl']    =$importurl="uploads/2021-01-18/32aa36bf238e4b39cf1a1e79d60e700c.xlsx"; //excel数据必填字段缺失的=>N
       // $input['importurl']    =$importurl="uploads/2021-01-18/986f68703b0a5e6d190271b02389bd99.xlsx"; //excel数据非必填字段缺失的=>Y
         // $input['importurl']    =$importurl="uploads/2021-01-18/db34547d2ef66982c8aaec3399027ece.xlsx";
        $input['importurl']    =$importurl;
        $rules = [
            'importurl' => 'required',
        ];
        $message = [
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

            //2
            $res = Excel::toArray((new Import),$importurl);
            $info_check=[];

            /**获取sheet0的数据**/
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
                '中文名称' =>['Y','Y','255','actual_name'],
                '英文名称' =>['N','Y','255','english_name'],
                '身份证号' =>['Y','N','20','identity_card'],
                '性别'     =>['N','Y','10','sex'],
            ];
            $ret=arr_check($shuzu,$info_check);
            //dd($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];
            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            foreach ($info_wait as $k=>$v){
                $where = [
                    'delete_flag'=>'Y',
                    'identity_card'=>$v['identity_card']
                ];
                $is_identity_card = SchoolPersonInfo::where($where)->value('identity_card');
                if($is_identity_card){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行身份证存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }
                $list=[];
                if($cando =='Y'){

                    $list['self_id']            =generate_id('info_');
                    $list['actual_name']        = $v['actual_name'];
                    $list['identity_card']      = $v['identity_card'];
                    $list['english_name']       = $v['english_name'];
                    $list['sex']                = $v['sex'];

                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;

                    $datalist[]=$list;
                }

                $a++;
            }
            $operationing->old_info=null;
            $operationing->new_info=(object)$datalist;

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                //dd($msg);
                return $msg;
            }

            $count=count($datalist);
            $id= SchoolPersonInfo::insert($datalist);

            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';
//                dd($msg);
                return $msg;
            }else{
                $msg['code']=307;
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



    /***    编辑人员基础信息     /school/basic_personal/createBasicPerson
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createBasicPerson(Request $request){

        $data['person_type']   =config('school.person_type');

        $self_id            =$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','actual_name', 'english_name','sex','identity_card'];
        $data['info']=SchoolPersonInfo::where($where)->select($select)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
		return $msg;
    }

    /***    提交人员基础信息      /school/basic_personal/addBasicPerson
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addBasicPerson(Request $request){

        /** 接收中间件参数*/
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $user_info          = $request->get('user_info');//接收中间件产生的参数

        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='school_person_info';

        $operationing->access_cause         ='新建/修改人员';
        $operationing->operation_type       ='create';
        $operationing->table                =$table_name;
        $operationing->now_time             =$now_time;
        $input=$request->all();

       // dd($input);

        /** 接收数据*/
        $self_id                =$request->input('self_id');
        $actual_name            =$request->input('actual_name');
        $english_name           =$request->input('english_name');
        $sex                    =$request->input('sex');
        $identity_card            =$request->input('identity_card');

        /*** 虚拟数据
        $input['self_id']           =$self_id='';
        $input['actual_name']       =$actual_name='张三';
        $input['english_name']      =$english_name='zhangsan';
        $input['sex']               =$sex='男';
        $input['identity_card']      =$identity_card='0001';
         ***/
        $rules=[
            'actual_name'=>'required',
            'identity_card'=>'required',
        ];
        $message=[
            'identity_card.required'=>'请填写真实姓名',
            'identity_card.required'=>'请填写身份证号',
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

				$self_id2=SchoolPersonInfo::where($where)->value('self_id');
//				dd($self_id2);
				if($self_id2){
					$msg['code'] = 301;
					$msg['msg'] = '身份证号码重复';
					return $msg;
				}
            }

            //现在开始做数据层面
            $data['actual_name']    = $actual_name;
            $data['identity_card']  = trim($identity_card);
            $data['english_name']   = $english_name;
            $data['sex']            = $sex;

            $where2['self_id'] = $self_id;
            $old_info=SchoolPersonInfo::where($where2)->first();
//            dd($old_info);
            if($old_info){
                //说明是修改
                $data['update_time'] =$now_time;
                $id=SchoolPersonInfo::where($where2)->update($data);

                $operationing->access_cause='修改人员基础信息';
                $operationing->operation_type='update';
            }else{

                $data['self_id']= generate_id('info_');
                $data['create_user_id'] =$user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['create_time'] =$data['update_time'] =$now_time;
                $id=SchoolPersonInfo::insert($data);
                $operationing->access_cause='新建人员基础信息';
                $operationing->operation_type='create';
            }
            $operationing->table_id=$self_id?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;
            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功';
                $msg['data']=(object)$data;
//                dd($msg);
                return $msg;
            }else{
                $msg['code']=303;
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
