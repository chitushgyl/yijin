<?php
namespace App\Http\Admin\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CommonController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class ApplyController extends CommonController{
    /***    家长申请乘坐校车头部      /school/apply/applyList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function applyList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;

    }
    /***    家长申请乘坐校车分页     /school/apply/applyPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function applyPage(Request $request){
        /** 读取配置文件信息**/
        //$person_type_show=config('school.person_type');
        //dump($person_type_show);
        /** 接收中间件参数**/
        //$user_info = $request->get('user_info');//接收中间件产生的参数
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num=$request->input('num')??10;
        $page=$request->input('page')??1;
        $person_type=$request->input('person_type');
        $group_code=$request->input('group_code');

        $listrows=$num;
        $firstrow=($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'a.delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'a.person_type','value'=>$person_type],
            ['type'=>'all','name'=>'a.group_code','value'=>$group_code],
        ];
        $where=get_list_where($search);

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=DB::table('school_box as a')->where($where)->count(); //总的数据量

                $data['items']=DB::table('school_box as a')
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select('a.self_id','a.patriarch_tel','a.address','a.group_name','a.create_time','a.img_url')
                    ->get()->toArray();

                break;

            case 'one':
                $where[]=['a.group_code','=',$group_info['group_code']];
                $data['total']=DB::table('school_box as a')
                    ->where($where)->count(); //总的数据量

                $data['items']=DB::table('school_box as a')
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select('a.self_id','a.patriarch_tel','a.address','a.group_name','a.create_time','a.img_url')
                    ->get()->toArray();


                break;

            case 'more':
                $data['total']=DB::table('school_box as a')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])->count(); //总的数据量

                $data['items']=DB::table('school_box as a')
                    ->where($where)->whereIn('a.group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('a.create_time', 'desc')
                    ->select('a.self_id','a.patriarch_tel','a.address','a.group_name','a.create_time','a.img_url')
                    ->get()->toArray();

                break;
        }


        dd($data);

        foreach ($data['items'] as $k=>$v) {




            $v->caozuo=$button_info;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
       
        return $msg;

    }

    /***    excel表格导入     /school/person/excelImport
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function excelImport(Request $request){

        dd(111);



        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input = Input::all();

        /** 接收数据*/
        $importurl=$request->input('importurl');
        $group_code=$request->input('group_code');

        /*** 虚拟数据
        $input['importurl']=$importurl='uploads/2020-07-02/63bd7e98dcaeb641d0be0fba3e858592.xlsx';
        $input['group_code']=$group_code='1234';**/

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
            //查询该公司
            $where_pack['group_code'] = $group_code;
            $where_pack['delete_flag'] = 'Y';
            $group_name = DB::table('system_group')->where($where_pack)->value('group_name');

            $table_name = 'sys_file_warehouse';
            $now_time = date('Y-m-d H:i:s', time());

            $file_data['self_id'] = generate_id('file_');
            $file_data['type'] = 'EXCEL';
            $file_data['url'] = $importurl;
            $file_data['group_code'] = $group_code;
            $file_data['group_name'] = $group_name;
            $file_data['create_user_id'] = $user_info->admin_id;
            $file_data['create_user_name'] = $user_info->name;
            $file_data['create_time'] = $file_data['update_time'] = $now_time;

            DB::table($table_name)->insert($file_data);
            /*** 把文件先存储一下**/
           // dump($user_info); dump($importurl);
            /***现在开始处理业务逻辑**/
            $res = [];
            Excel::load($importurl,function ($reader) use ( &$res ){
                $res= $reader->all()->toArray();
            });

            /*** 拿取所有这个学校的数据**/
            $where_car['group_code'] = $group_code;
            $where_car['delete_flag'] = 'Y';
            $school_info = DB::table('school_info')->where($where_car)
                ->select('self_id','data_type', 'car_brand', 'car_nuclear', 'actual_name', 'person_tel', 'person_type', 'identity_card')->get()->toArray();
            //dump($school_info);
            //把这个文件做成几个数组
            $car=[];                                //车辆信息
            $driver=[];                             //司机信息
            $student=[];                            //学生信息
            $patriarch=[];                            //学生信息
            foreach ($school_info as $k => $v){
                if($v->data_type == 'car'){
                    $car[$v->car_brand]=$v;
                }

                if($v->person_type == 'care' || $v->person_type == 'driver' || $v->person_type == 'teacher' ){
                    $driver[$v->person_tel]=$v;
                }

                if($v->person_type == 'student' ){
                    $student[$v->identity_card]=$v;
                }

                if($v->person_type == 'patriarch' ){
                    $patriarch[$v->person_tel]=$v;
                }

            }

//            dump($driver);

            //dump($res);
            /*** 对所有的数据进行粗加工   flag=Y 的说明要入库 **/
            $info=[];
            try {
                foreach ($res as $k => $v){
                    foreach ($v as $kk => $vv){
                        switch ($k){
                            case '0':
                                $vv['data_type']='car';
                                if($vv['车牌号'] && $vv['荷载人数']){
                                    if (array_key_exists($vv['车牌号'],$car)){
                                        $vv['self_id']=$car[$vv['车牌号']]->self_id;
                                        $vv['flag'] = 'N';
                                    }else{
                                        $vv['self_id']=generate_id('info_');
                                        $vv['flag'] = 'Y';                                      //Y说明是要进行入库操作的
                                        $vv['person_type']=null;
                                    }
                                    $info[]=$vv;
                                }

                                break;
                            case '2':
                                $vv['data_type']='human';
                                $vv['person_type']='student';
                                if($vv['姓名'] && $vv['学籍号']){
                                    if (array_key_exists($vv['学籍号'],$student)){
                                        $vv['self_id']=$student[$vv['学籍号']]->self_id;
                                        $vv['flag'] = 'N';
                                    }else{
                                        $vv['self_id']=generate_id('info_');
                                        $vv['flag'] = 'Y';                                      //Y说明是要进行入库操作的

                                        /*** 这里需要处理下家长的身份，和关联关系**/
                                        if($vv['监护人'] && $vv['手机']){
                                            //如果有监护人，也有手机号码，则需要把这个数据和数据的家长身份做一个比较，如果有，则不做任何操作
                                            //如果没有，则需要添加加入info表，同时和这个学生建立直接关系
                                            if (array_key_exists($vv['手机'],$patriarch)){
                                                $vv['patriarch_id'] = $patriarch[$vv['手机']]->self_id;
												$vv['relation_person_name'] = $patriarch[$vv['手机']]->actual_name;
												$vv['relation_tel'] = $patriarch[$vv['手机']]->person_tel;
                                            }else{
                                                //没有则需要再info表中把这个人加上去
                                                $ghtet['self_id']=generate_id('info_');
                                                $ghtet['data_type']='human';
                                                $ghtet['actual_name']=$vv['监护人'];
                                                $ghtet['person_tel']=$vv['手机'];
                                                $ghtet['person_type']='patriarch';
                                                $ghtet['flag'] = 'Y';

                                                $info[]=$ghtet;

                                                $patriarch[$vv['手机']]=(object)$ghtet;
                                                $vv['patriarch_id'] = $ghtet['self_id'];
                                                $vv['relation_person_name'] = $ghtet['actual_name'];
                                                $vv['relation_tel'] = $ghtet['person_tel'];
                                            }

                                        }else{
                                            $vv['patriarch_id'] = null;
                                        }


                                    }
                                    $info[]=$vv;
                                }
                                break;
                            default:
                                $vv['data_type']='human';
                                if($vv['姓名'] && $vv['电话']){
                                    if (array_key_exists($vv['电话'],$driver)){
                                        $vv['self_id']=$driver[$vv['电话']]->self_id;
                                        $vv['flag'] = 'N';
                                    }else{
                                        $vv['self_id']=generate_id('info_');
                                        $vv['flag'] = 'Y';                                      //Y说明是要进行入库操作的

                                        if($vv['职务'] == '照管'){
                                            $vv['person_type'] = 'care';
                                        }else if($vv['职务'] == '司机'){
                                            $vv['person_type'] = 'driver';
                                        }else{
                                            $vv['person_type'] = 'teacher';
                                        }

                                    }
                                    $info[]=$vv;
                                }
                                break;
                        }
                    }
                }

            } catch (\Exception $e) {
                //dd($e);
                $msg['code'] = 303;
                $msg['msg'] = '请确保上传文件的类型和示例文件一致';
                return $msg;
            }


            //dump($patriarch);
            //dd($info);

            /*** 对所有的数据进行粗加工结束  **/
            if($info){
                $url_siru='http://qxapi.zhaodaolo.com/index.php/home/index/index/p_id/';                //这个是准备发送邮件的
                //拿到数据总条数
                $total_count = count($info);
                $count=0;
                foreach ($info as $k => $v) {
                    $data=null;
                    if($v['flag'] == 'Y'){
                        $data['email']=null;
                        $data['self_id']=$v['self_id'];
                        $data['data_type']=$v['data_type'];
                        $data['group_code']= $group_code;
                        $data['group_name']=$group_name;
                        $data['create_user_id']=$user_info->admin_id;
                        $data['create_user_name']=$user_info->name;
                        $data['create_time']=$data['update_time']=$now_time;
                        //做一个差异化，避免报错
                        if($v['data_type'] == 'car'){
                            $data['car_brand']=$v['车牌号'];
                            $data['car_nuclear']=$v['荷载人数'];

                        }else{

                            $data['person_type']=$v['person_type'];
                             switch ($v['person_type'])  {
                                 case 'student':
                                     $data['actual_name']=$v['姓名'];
                                     $data['grade_name']=$v['年级'];
                                     $data['class_name']=$v['班级'];
                                     $data['identity_card']=$v['学籍号'];
                                     $data['sex']=$v['性别'];

                                     //看看$vv['patriarch_id']   是不是为空，如果不为空，则建立关联关系出来，就好了     写入school_person_relation
                                 if($v['patriarch_id']){
                                     $relation['self_id']=generate_id('relation_');
                                     $relation['person_id']=$data['self_id'];
                                     $relation['person_name']=$data['actual_name'];
                                     $relation['relation_type']='direct';

                                     $relation['relation_person_id']=$v['patriarch_id'];
                                     $relation['relation_person_name']=$v['relation_person_name'];
                                     $relation['relation_tel']=$v['relation_tel'];

                                     $relation['group_code']= $group_code;
                                     $relation['group_name']=$group_name;
                                     $relation['create_user_id']=$user_info->admin_id;
                                     $relation['create_user_name']=$user_info->name;
                                     $relation['create_time']=$relation['update_time']=$now_time;

                                      DB::table('school_person_relation')->insert($relation);

                                 }

                                     break;
                                 case 'care':
                                     $data['actual_name']=$v['姓名'];
                                     $data['person_tel']=$v['电话'];
                                     break;
                                 case 'driver':
                                     $data['actual_name']=$v['姓名'];
                                     $data['person_tel']=$v['电话'];
                                     break;
                                 case 'teacher':
                                     $data['actual_name']=$v['姓名'];
                                     $data['email']=$v['邮箱'];
                                     $data['person_tel']=$v['电话'];
									 $data['grade_name']=$v['年级'];
                                     $data['class_name']=$v['班级'];
                                     break;
                                 case 'patriarch':
                                     $data['actual_name']=$v['actual_name'];
                                     $data['person_tel']=$v['person_tel'];
                                     break;

                             }
                        }

                        $id = DB::table('school_info')->insert($data);


//                        if($data['email']){
//                            $name = $group_name.'邀请您绑定'.$data['person_type'].'身份，请使用微信扫码，并授权登录';
//                            $msgggg=$url_siru.$data['self_id'];
//                            //$msgggg=$url_siru.'info_202006101609482328890889';
//
//                            $storage_path = 'uploads/'.date('Y-m-d',time());//上传文件保存的路径
//                            //获取上传图片的临时地址
//
//                            //生成文件名
//                            $names=$data['self_id'];
//                            $fileName =$names.'.'.'png';
//                            $pathurl = $storage_path.'/'.$fileName;
//
//                            $imgPath=QrCode::format('png')->size(300)->generate($msgggg,$pathurl);
//
//
//                            $to=$data['email'];
//                            $url=$_SERVER["HTTP_HOST"].'/'.$pathurl;
//                            //dd($url);
////                                    // Mail::send()的返回值为空，所以可以其他方法进行判断
//                            Mail::send('emails.test',['name'=>$name,'imgPath'=>$url],function($message)use($to){
//                                // Mail::send('emails.test',['name'=>$name,'imgPath'=>$imgPath],function($message)use($to){
//                                $message ->to($to)->subject('邀请绑定身份邮件');
//                            });
//                            // dd(Mail::failures());
//                        }


                        //dump($data);

                        $count++;
                    }else{
                        $id =null;
                    }

                }

            }else{
                $msg['code'] = 301;
                $msg['msg'] = '没有要处理的数据';
                return $msg;
            }

            /*** 处理信息完毕**/
            if ($id) {
                $msg['code'] = 200;
                $msg['msg'] = '数据导入成功'.$total_count . "条数据\n" .  '处理了' . $count . '条数据';
                return $msg;
            } else {
                $msg['code'] = 302;
                $msg['msg'] = '导入'.$total_count . "条数据\n" .  '因重复无数据可处理';
                return $msg;
            }


        }else{
            $erro = $validator->errors()->all();

            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $msg['msg'] .= $v . "\n";
            }
            $msg['code'] = 304;
            return $msg;
        }

    }


    public function  load_reg2(Request $request){
        $input=Input::all();
//        $where['tel']=$input['tel'];
//        dd($input);
        $map = [
            ['tel','=',$input['tel']],
//            ['token_name','like', '%' . $input['token_name'] . '%'],
//            ['reg_type','=','ALL'],
//            ['authority','>',0],
//            ['authority','<',2],
//              ['group','=',$input['group']],
        ];

        $where=filter_where($map);
//        dd($where);
        $user_info=DB::table('user_reg')->where($where)->select('self_id','tel','token_name','true_name','token_img')->get()->toArray();
//        dd($user_info);
        return view('School.Excel.load_reg',['user_info'=>$user_info]);
    }


    //职员绑定用户
    public function related(Request $request){
        $input=Input::all();
//        dd($input);
        $where['self_id']=$input['dat']['user_id'];
        $user_info=DB::table('user_reg')->where($where)->select('self_id','tel','token_name','true_name','token_img','person_id')->first();

        if($user_info){
            $datt['user_id']=$user_info->self_id;
//            $datt['nick_name']=$user_info->token_name;
            $datt['update_time']=date('Y-m-d H:i:s',time());
            $where11['self_id']=$input['dat']['self_id'];
//            dump($where11);
//            dd($datt);
            $id=DB::table('school_info')->where($where11)->update($datt);
            $school_person=DB::table('school_info')->where($where11)->select('self_id','person_type','person_tel','group_code')->first();


            $person_type=config('school.person_type');


            if(empty($user_info->person_id)){

                foreach ($person_type as $k => $v){
                    if($school_person->person_type==$k){
                        $school_person->person_type_show=$v;
                    }
                }
//                dd($school_person);

                //把默认角色加上
                $datt2222['true_name']=$user_info->true_name;
                $datt2222['person_id']=$school_person->self_id;
                $datt2222['person_name']=$school_person->person_type_show;
                $datt2222['person_type']=$school_person->person_type;
                $datt2222['update_time']=date('Y-m-d H:i:s',time());
                DB::table('user_reg')->where($where)->update($datt2222);

            }





//            dump($group_code);
//            dump($input['dat']['user_id']);


            //save_userInfo($input['dat']['user_id'],$school_person->group_code);

            $url = "https://busapi.zhaodaolo.com/web/set_user_info?user_id=".$input['dat']['user_id'];
            //dump($url);
            $json = $this->httpGet($url);

            //dd($json);

            $id=2;
            if($id){
                $msg['code']=200;
                $msg['msg']='用户绑定成功';
            }else{
                $msg['code']=300;
                $msg['msg']='用户绑定失败';
            }
        }else{
            $msg['code']=300;
            $msg['msg']='用户不存在';
        }
        return response()->json(['msg'=>$msg]);
    }


    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}
?>
