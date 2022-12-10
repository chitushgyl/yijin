<?php
namespace App\Http\Admin\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Models\School\SchoolPath;
use App\Models\School\SchoolInfo;
use App\Models\School\SchoolCarInfo;
use App\Models\School\SchoolPathway;
use App\Models\School\SchoolPathwayPerson;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroup;

use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;

use App\Http\Controllers\FileController as File;
class LineController  extends CommonController
{
    /***    线路信息头部      /school/line/lineList
     *      前端传递必须参数：
     *      前端传递非必须参数1111
     */
    //线路列表
    public function lineList(Request $request)
    {
        $data['page_info'] = config('page.listrows');
        $data['button_info'] = $request->get('anniu');

        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;

        // dd($msg);
        return $msg;
    }

    /***    线路信息分页      /school/line/linePage
     *      前端传递必须参数：
     *      前端传递非必须参数：1
     */
    public function linePage(Request $request)
    {
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数
//dd($button_info->toArray());
        /**接收数据*/
        $num = $request->input('num') ?? 10;
        $page = $request->input('page') ?? 1;

        $listrows = $num;
        $firstrow = ($page - 1) * $listrows;
        $defaultCarBrand= $request->input('defaultCarBrand') ;
		//dd($defaultCarBrand);
        $search = [
//            ['type'=>'like','name'=>'path_name','value'=>$request->input('pathName')],
            ['type'=>'=','name'=>'car_number','value'=>$defaultCarBrand],
            ['type' => '=', 'name' => 'delete_flag', 'value' => 'Y'],
			['type' => '=', 'name' => 'use_flag', 'value' => 'Y'],
        ];
        $where = get_list_where($search);
		//dd($where);
        $select = ['self_id', 'car_brand', 'car_number', 'group_name', 'remark'];

        $schoolPathSelect = ['self_id', 'site_type', 'path_name', 'default_car_id', 'default_car_brand',
            'default_driver_name', 'default_driver_tel', 'default_care_id', 'default_care_name',
            'default_care_tel', 'pathway_count','sort'];

        $schoolPathwaySelect = ['self_id', 'pathway_name', 'path_id', 'site_type', 'sort'];

        $schoolPathwayPersonSelect = ['self_id', 'pathway_id', 'person_name', 'class_name', 'grade_name'];
        switch ($group_info['group_id']) {
            case 'all':
                $data['total'] = SchoolCarInfo::where($where)->count(); //总的数据量1

                $data['items'] = SchoolCarInfo::with(['schoolPath' => function ($query) use ($schoolPathSelect, $schoolPathwaySelect, $schoolPathwayPersonSelect) {
                    $query->where('delete_flag', '=', 'Y');
                    $query->select($schoolPathSelect);
                    $query->withCount(['schoolPathwayPerson' => function ($query) {
                        $query->where('use_flag', '=', 'Y');
                        $query->where('delete_flag', '=', 'Y');
                    }]);
                    $query->with(['schoolPathway' => function ($query) use ($schoolPathwaySelect, $schoolPathwayPersonSelect) {
                        $query->where('delete_flag', '=', 'Y');
                        $query->select($schoolPathwaySelect);
                        $query->orderBy('sort', 'asc');
                        $query->withCount(['schoolPathwayPerson' => function ($query) {
                            $query->where('use_flag', '=', 'Y');
                            $query->where('delete_flag', '=', 'Y');
                        }]);
                        $query->with(['schoolPathwayPerson' => function ($query) use ($schoolPathwayPersonSelect) {
                            $query->where('use_flag', '=', 'Y');
                            $query->where('delete_flag', '=', 'Y');
                            $query->select($schoolPathwayPersonSelect);
                        }]);
                    }]);
                }])
                    ->where($where)
                    ->offset($firstrow)
                    ->limit($listrows)
                    ->orderBy('create_time', 'desc')
                    ->select($select)
                    ->get();
				$data['group_show']='Y';
                //dd($data['items']->toArray());
                break;

            case 'one':
                $where[] = ['group_code', '=', $group_info['group_code']];
                $data['total'] = SchoolCarInfo::where($where)->count(); //总的数据量1

                $data['items'] = SchoolCarInfo::with(['schoolPath' => function ($query) use ($schoolPathSelect, $schoolPathwaySelect, $schoolPathwayPersonSelect) {
                    $query->where('delete_flag', '=', 'Y');
                    $query->select($schoolPathSelect);
                    $query->withCount(['schoolPathwayPerson' => function ($query) {
                        $query->where('use_flag', '=', 'Y');
                        $query->where('delete_flag', '=', 'Y');
                    }]);
                    $query->with(['schoolPathway' => function ($query) use ($schoolPathwaySelect, $schoolPathwayPersonSelect) {
                        $query->where('delete_flag', '=', 'Y');
                        $query->select($schoolPathwaySelect);
                        $query->orderBy('sort', 'asc');
                        $query->withCount(['schoolPathwayPerson' => function ($query) {
                            $query->where('use_flag', '=', 'Y');
                            $query->where('delete_flag', '=', 'Y');
                        }]);
                        $query->with(['schoolPathwayPerson' => function ($query) use ($schoolPathwayPersonSelect) {
                            $query->where('use_flag', '=', 'Y');
                            $query->where('delete_flag', '=', 'Y');
                            $query->select($schoolPathwayPersonSelect);
                        }]);
                    }]);
                }])
                    ->where($where)
                    ->offset($firstrow)
                    ->limit($listrows)
                    ->select($select)
                    ->orderBy('create_time', 'desc')
                    ->get();
				$data['group_show']='N';
                break;

            case 'more':
                $data['total'] = SchoolCarInfo::where($where)->whereIn('group_code', $group_info['group_code'])->count(); //总的数据量

                $data['items'] = SchoolCarInfo::with(['schoolPath' => function ($query) use ($schoolPathSelect, $schoolPathwaySelect, $schoolPathwayPersonSelect) {
                    $query->where('delete_flag', '=', 'Y');
                    $query->select($schoolPathSelect);

                    $query->withCount(['schoolPathwayPerson' => function ($query) {
                        $query->where('use_flag', '=', 'Y');
                        $query->where('delete_flag', '=', 'Y');
                    }]);
                    $query->with(['schoolPathway' => function ($query) use ($schoolPathwaySelect, $schoolPathwayPersonSelect) {
                        $query->where('delete_flag', '=', 'Y');
                        $query->select($schoolPathwaySelect);
                        $query->orderBy('sort', 'asc');
                        $query->withCount(['schoolPathwayPerson' => function ($query) {
                            $query->where('use_flag', '=', 'Y');
                            $query->where('delete_flag', '=', 'Y');
                        }]);
                        $query->with(['schoolPathwayPerson' => function ($query) use ($schoolPathwayPersonSelect) {
                            $query->where('use_flag', '=', 'Y');
                            $query->where('delete_flag', '=', 'Y');
                            $query->select($schoolPathwayPersonSelect);
                        }]);
                    }]);
                }])
                    ->where($where)
                    ->whereIn('group_code', $group_info['group_code'])
                    ->offset($firstrow)
                    ->limit($listrows)
                    ->select($select)
                    ->orderBy('create_time', 'desc')
                    ->get();
					$data['group_show']='Y';
                break;
        }

        //dd($data['items']->toArray());
        $button_info1 = []; //新建线路
        $button_info2 = []; //已建线路上的按钮
        $button_info3 = [];//线路上学生配置按钮【现在已经可以不用了，放在button_info2上了】
//
        foreach ($button_info as $k => $v) {
            if ($v->id == '356') {
                $button_info1[] = $v;
            }
			if ($v->id == '503') {
                $button_info2[] = $v;
            }
            if ($v->id == '357') {
                $button_info2[] = $v;
            }
            if ($v->id == '358') {
				$button_info2[] = $v;
                //$button_info3[] = $v;
            }
        }

        foreach ($data['items'] as $k => $v) {
            $v->button_info = $button_info1;
            foreach ($v->schoolPath as $kk => $vv) {
                $vv->button_info = $button_info2;
                foreach ($vv->schoolPathway as $kkk => $vvv) {
                    $vvv->button_info = $button_info3;
                }
            }
        }


        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        //dd($msg);
        return $msg;


    }


    /***    新路及修改创建      /school/line/createLine
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createLine(Request $request)
    {
        /** 接收中间件参数1**/

        //$user_info = $request->get('user_info');//接收中间件产生的参数
        //$group_info = $request->get('group_info');//接收中间件产生的参数

        /**接收数据*/
        $school_car_id = $request->input('school_car_id');                    //车辆ID1
        $self_id = $request->input('self_id');                //创建修改线路传过来的ID 可选111
		
        /** 虚拟数据**/
        //$school_car_id='car_202009041115586369160334';
        //$self_id='path_20200824153952646938277611';

        //有self_id则是修改 没有则是创建11
        if ($self_id) {
            $wherePath['self_id'] = $self_id;
            $wherePath['delete_flag'] = 'Y';
            $data['path_info'] = SchoolPath::where($wherePath)
                ->select('self_id', 'path_name', 'default_car_id', 'default_car_brand', 'site_type',
                    'default_driver_id', 'default_driver_name', 'default_driver_tel', 'default_care_id', 'default_care_name', 'default_care_tel',
                    'group_code', 'start_flag', 'go_flag', 'go_time', 'end_flag', 'arrive_flag', 'go_flag2', 'go_time2', 'mini_flag','call_up_flag','call_down_flag','sort'
                )
                ->first();
				//dd($data['path_info']);
            $school_car_id = $data['path_info']->default_car_id;
        } else {
            $data['path_info'] = null;
        }


        //查询出  这个车的车牌号码之类的出来
        $where['use_flag'] = 'Y';
        $where['delete_flag'] = 'Y';
        $where['self_id'] = $school_car_id;
        $data['car_info'] = SchoolCarInfo::where($where)
            ->select('self_id', 'group_code', 'group_name', 'car_number', 'car_nuclear')
            ->first();
		
		//这里开始查询该车辆下已经创建了几条线路
         //有？？没有？？
        $data['has_path']=null; //初始化
        if($data['car_info']&& $data['car_info']->self_id){
            $where1=[
                ['default_car_id' ,'=',$data['car_info']->self_id],
                ['delete_flag' ,'=','Y'],
            ];
            $temp_has_path= SchoolPath::where($where1)
                ->select('self_id', 'path_name', 'site_type', 'sort')
                ->get()->toArray();
            if(count($temp_has_path)>0){
                $data['has_path']=$temp_has_path;
            }else{
                $data['has_path']=null;
            }
        }
		

        $data['school_car_id'] = $school_car_id;
        //查询司机
        $data['driver'] = SchoolCarInfo::wherehas('schoolInfo', function ($query) {
            $query->where('person_type', '=', 'driver');
            $query->where('use_flag', '=', 'Y');
            $query->where('delete_flag', '=', 'Y');
        })
            ->with(['schoolInfo' => function ($query) {
                $query->where('person_type', '=', 'driver');
                $query->where('use_flag', '=', 'Y');
                $query->where('delete_flag', '=', 'Y');
                $query->select('self_id', 'group_code', 'actual_name');
            }])
            ->where($where)
            ->select('self_id', 'group_code')
            ->first();
			//dd($where);
			//dd($data['driver']);

        //查询照管
        $data['care'] = SchoolCarInfo::wherehas('schoolInfo', function ($query) {
            $query->where('person_type', '=', 'care');
            $query->where('use_flag', '=', 'Y');
            $query->where('delete_flag', '=', 'Y');
        })
            ->with(['schoolInfo' => function ($query) {
                $query->where('person_type', '=', 'care');
                $query->where('use_flag', '=', 'Y');
                $query->where('delete_flag', '=', 'Y');
                $query->select('self_id', 'group_code', 'actual_name');
            }])
            ->where($where)
            ->select('self_id', 'group_code')
            ->first();


        //dd($data['path_info']);
        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        return $msg;
    }

    /***    新路及修改创建      /school/line/lineAdd       线路进入数据【进redis】
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function lineAdd(Request $request)
    {
        /** 接收中间件参数**/
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $now_time = date('Y-m-d H:i:s', time());
        $table_name = 'school_path';

        $operationing->access_cause = '新建/修改线路';
        $operationing->operation_type = 'create';
        $operationing->table = $table_name;
        $operationing->now_time = $now_time;
        /**接收数据*/
        $school_car_id = $request->input('school_car_id');                        //车辆的ID

        $self_id = $request->input('self_id');                            //线路的ID
        $group_code = $request->input('group_code');
        $path_name = $request->input('path_name');
        $site_type = $request->input('site_type');
        $default_driver_id = $request->input('default_driver_id');
        $default_care_id = $request->input('default_care_id');
        $start_flag = $request->input('start_flag');
        $go_flag = $request->input('go_flag');
        $go_time = $request->input('go_time');
        $end_flag = $request->input('end_flag');
        $arrive_flag = $request->input('arrive_flag');
        $mini_flag = $request->input('mini_flag');
        $call_up_flag = $request->input('call_up_flag');
        $call_down_flag = $request->input('call_down_flag');
		$sort = $request->input('sort');
        //$go_flag2 = $request->input('go_flag2');
        //$go_time2 = $request->input('go_time2');


        $input = $request->all();
        /*** 虚拟数据
         * $input['path_name']='212121212';
         * $input['school_car_id']='212121212';
         * $input['default_driver_id']='212121212';
         * $input['default_care_id']='212121212';
         * $self_id='path_202008291615147895680395';
         * $path_name='新世纪一号线';
         * $school_car_id='car_202008241538559962280560';
         * $default_driver_id='info_202008241130196842187849';
         * $default_care_id='info_202008241130196751796742';
         **/
        $rules = [
            'path_name' => 'required',
            'school_car_id' => 'required',
            'default_driver_id' => 'required',
            'default_care_id' => 'required',
        ];
        $message = [
            'path_name.required' => '线路名称不能为空',
            'school_car_id.required' => '车辆不能为空',
            'default_driver_id.required' => '司机不能为空',
            'default_care_id.required' => '照管员不能为空',
        ];

        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            $where_car['self_id'] = $school_car_id;
            $where_car['delete_flag'] = 'Y';
            $car_info = SchoolCarInfo::where($where_car)->select('self_id', 'car_number', 'group_code', 'group_name')->first();
            $data['default_car_id'] = $car_info->self_id;
            $data['default_car_brand'] = $car_info->car_number;


            $where_driver['delete_flag'] = 'Y';
            $where_driver['person_type'] = 'driver';
            $where_driver['self_id'] = $default_driver_id;
            $driver_info = SchoolInfo::where($where_driver)->select('self_id', 'actual_name', 'person_tel')->first();
            if ($driver_info) {
                $data['default_driver_id'] = $driver_info->self_id;
                $data['default_driver_name'] = $driver_info->actual_name;
                $data['default_driver_tel'] = $driver_info->person_tel;
            } else {
                $data['default_driver_id'] = null;
                $data['default_driver_name'] = null;
                $data['default_driver_tel'] = null;
            }

            $where_care['delete_flag'] = 'Y';
            $where_care['person_type'] = 'care';
            $where_care['self_id'] = $default_care_id;
            $care_info = SchoolInfo::where($where_care)->select('self_id', 'actual_name', 'person_tel')->first();
            if ($driver_info) {
                $data['default_care_id'] = $care_info->self_id;
                $data['default_care_name'] = $care_info->actual_name;
                $data['default_care_tel'] = $care_info->person_tel;
            } else {
                $data['default_care_id'] = null;
                $data['default_care_name'] = null;
                $data['default_care_tel'] = null;
            }

            $data['path_name'] = $path_name;
            $data['site_type'] = $site_type;
            $data['start_flag'] = $start_flag;
            $data['go_flag'] = $go_flag;
            $data['go_time'] = $go_time;
            $data['end_flag'] = $end_flag;
            $data['arrive_flag'] = $arrive_flag;
            $data['mini_flag'] = $mini_flag;
            $data['call_up_flag'] = $call_up_flag;
            $data['call_down_flag'] = $call_down_flag;
			$data['sort'] = $sort;
            //$data['go_flag2'] = $go_flag2;
           // $data['go_time2'] = $go_time2;
            //dd($data);

            $where_save['self_id'] = $self_id;
            $old_info = SchoolPath::where($where_save)->first();

            if ($old_info) {

                //说明是修改
                $data["update_time"] = $now_time;
                $where_updata['self_id'] = $self_id;

                $operationing->access_cause = '修改线路';
                $operationing->operation_type = 'update';
                $operationing->old_info = $old_info;

                $id = SchoolPath::where($where_save)->update($data);
                //dd($data);
            } else {
                $data['self_id'] = generate_id('path_');
                $data['create_time'] = $data['update_time'] = $now_time;
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['group_code'] = $car_info->group_code;
                $data['group_name'] = $car_info->group_name;

                $operationing->access_cause = '新增线路';
                $operationing->operation_type = 'create';
                $id = SchoolPath::insert($data);

            }

            $operationing->table_id = $self_id ? $self_id : $data['self_id'];
            $operationing->old_info = $old_info;
            $operationing->new_info = $data;

            if ($id) {
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            } else {
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
                return $msg;
            }
        } else {
            $erro = $validator->errors()->all();
            $msg['code'] = 300;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            return $msg;
        }
    }


    /***    配置上车途经点      /school/line/linePathway
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function linePathway(Request $request)
    {
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        /** 接收数据*/
        $self_id = $request->input('self_id');                //传递的是path_id

        //$self_id='path_202008291615147895680395';
        //第一步，就是查询出这个没有这个线，如果没有这个线，则直接要报错误了！！1
        $whereself_id['self_id'] = $self_id;
        $whereself_id['delete_flag'] = 'Y';
        $data['group_info'] = DB::table('school_path')
            ->where($whereself_id)
            ->select('group_code', 'path_name','site_type')->first();

        if ($data['group_info']) {
            $where['path_id'] = $self_id;
            $where['use_flag'] = 'Y';
            $where['delete_flag'] = 'Y';
            $data['info'] = DB::table('school_pathway')->where($where)->orderBy('sort', 'asc')
                ->select('self_id', 'pathway_name', 'pathway_address', 'longitude', 'dimensionality', 'distance', 'duration','is_compel_arriver')
                ->get()->toArray();
            if ($data['info']) {
                foreach ($data['info'] as $k => $v) {
                    $v->loglat = $v->longitude . '*' . $v->dimensionality;
                }
            } else {
                $data['info'] = null;
            }
            $msg['code'] = 200;
            $msg['msg'] = "数据拉取成功";
            $msg['data'] = $data;
            return $msg;
        } else {
            $msg['code'] = 301;
            $msg['msg'] = "查询不到数据";
        }
    }

    /***    配置途经点进入数据      /school/line/linePathwayAdd
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function linePathwayAdd(Request $request)
    {
        /** 接收中间件参数**/
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $now_time = date('Y-m-d H:i:s', time());


        $url_len = config('aliyun.oss.url_len');
        $table_name = 'school_pathway';

        $operationing->access_cause = '新建/修改站点配置';
        $operationing->table = $table_name;
        $operationing->now_time = $now_time;
        $operationing->operation_type = 'create';

        /**接收数据*/
        $path_id = $request->input('path_id');
        $arr = json_decode($request->input('arr'), true);

        $input = $request->all();
        //dd($input);
        /*** 虚拟数据
         * $input['path_id']=$path_id='path_202008291615147895680395';
         * $arr=[
         * '0'=>[
         * 'self_id'=>'pathway_202008291704130618470818',
         * 'pathway_name'=>'2121212',
         * 'pathway_address'=>'2121212',
         * 'longitude'=>'2121212',
         * 'distance'=>'2121212',
         * 'duration'=>'2121212',
         * 'dimensionality'=>'2121212',
         * 'loglat'=>'121.313133*31.19519'
         * ],
         * '1'=>[
         * 'self_id'=>'',
         * 'pathway_name'=>'2121212',
         * 'pathway_address'=>'2121212',
         * 'longitude'=>'2121212',
         * 'distance'=>'2121212',
         * 'duration'=>'2121212',
         * 'dimensionality'=>'2121212',
         * 'loglat'=>'121.313133*31.19519'
         * ],
         * ];
         **/

        $rules = [
            'path_id' => 'required',
        ];
        $message = [
            'path_id.required' => '线路名称不能为空',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            //获取传过来的途经点id
            $temp = [];
            foreach ($arr as $k => $v) {
                if ($v['self_id'] != '') {
                    array_push($temp, $v['self_id']);
                }
            }


            $where_all['path_id'] = $path_id;
            $where_all['delete_flag'] = 'Y';
            $self_all = DB::table('school_pathway')->where($where_all)->pluck('self_id')->toArray();
            $operationing->old_info = json_encode($self_all);//获取已经在数据库中的站点信息
            $temp2 = array_diff($self_all, $temp);
            $update['delete_flag'] = 'N';
            $update['update_time'] = $now_time;
            DB::table('school_pathway')->whereIn('self_id', $temp2)->update($update);

            //通过这个$path_id 去拿取一些必要的信息111
            $path_where['self_id'] = $path_id;
            $path_info = DB::table('school_path')->where($path_where)->select('group_code', 'group_name', 'path_name', 'english_path_name', 'site_type')->first();

            $path_wheress['path_id'] = $path_id;
            $path_wheress['delete_flag'] = 'Y';

            foreach ($arr as $k => $v) {
                $data = [];
                $data['path_id'] = $path_id;
                $data['path_name'] = $path_info->path_name;
                if ($v['longitude'] && $v['dimensionality']) {
                    $data['longitude'] = $v['longitude'];
                    $data['dimensionality'] = $v['dimensionality'];
                } else if ($v['loglat']) {
                    $tempsss = explode('*', $v['loglat']);
                    $data['longitude'] = $tempsss[0];
                    $data['dimensionality'] = $tempsss[1];
                } else {
                    $data['longitude'] = '';
                    $data['dimensionality'] = '';
                }
                $data['pathway_name'] = $v['pathway_name'];
                $data['pathway_address'] = $v['pathway_address'];
                $j = $k + 1;
                $data['sort'] = $j;
                $data['distance'] = $v['distance'] == '' ? 0 : $v['distance'];
                $data['duration'] = $v['duration'] == '' ? 0 : $v['duration'];
                $data['site_type'] = $path_info->site_type;
                if(array_key_exists('poly', $v)){
                    $data['poly'] =$v['poly'];
                };
                if(array_key_exists('is_compel_arriver', $v)){
                    $data['is_compel_arriver'] =$v['is_compel_arriver'];
                };

                if ($v['self_id'] == '') {
                    //这里是新建
                    $data['self_id'] = generate_id('pathway_');
                    $data['group_code'] = $path_info->group_code;
                    $data['group_name'] = $path_info->group_name;
                    $data['create_user_id'] = $user_info->admin_id;
                    $data['create_user_name'] = $user_info->name;
                    $data['create_time'] = $data['update_time'] = $now_time;
                    SchoolPathway::insert($data);
                    $datass['pathway_count'] = DB::table('school_pathway')->where($path_wheress)->count();
                    //dump(1111);

                    DB::table('school_path')->where($path_where)->update($datass);


                } else {
                    //这里是修改

                    $operationing->access_cause = '修改站点配置';
                    $operationing->operation_type = 'update';


                    $data['update_time'] = $now_time;
                    SchoolPathway::where('self_id', '=', $v['self_id'])->update($data);

                    $datass['pathway_count'] = DB::table('school_pathway')->where($path_wheress)->count();
                    DB::table('school_path')->where($path_where)->update($datass);
                }
            }
            $operationing->table_id = $path_id;
            $operationing->new_info = json_encode(DB::table('school_pathway')->where($where_all)->pluck('self_id')->toArray());
            $msg['code'] = 200;
            $msg['msg'] = "操作成功";
            return $msg;

        } else {
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


    /***    删除途经点进入数据      /school/line/linePathwayDelete      线路进入数据【进redis】
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function linePathwayDelete(Request $request)
    {
        $status = new StatusController;
        //$self_id='path202006051606581641869878';
        $now_time = date('Y-m-d H:i:s', time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $operationing->now_time=$now_time;
        $table_name = 'school_pathway';
        $medol_name = 'SchoolPathway';
        $self_id = $request->input('self_id');
        $flag = 'delFlag';

        $status_info = $status->changeFlag($table_name, $medol_name, $self_id, $flag, $now_time);

        if ($status_info['code'] == 200) {
            /*** 如果 =200  成功的话，现在开始做差异化业务逻辑
             * 第一步，是不是应该把这个站点上配置的小孩释放出来
             * 第二步，更新线路表中的站点数量
             * 第三步，是不是需要通过这个ID，拿到所有的站点信息，然后调用地图重新发起计算，循环存储进入数据库，时间和距离
             **/
            $where['self_id'] = $self_id;
            $info = DB::table('school_pathway')->where($where)->first();
            //第一步
            $where_stu['pathway_id'] = $info->self_id;  //途经点id
            $where_stu['pathway_type'] = $info->site_type;
            $where_stu['delete_flag'] = 'Y';
            $student_info = DB::table('school_pathway_person')->where($where_stu)->pluck('person_id')->toArray();
            $data['delete_flag'] = 'N';
            $data['update_time'] = date('Y-m-d H:i:s', time());
            if ($student_info) {
                DB::table('school_pathway_person')->where($where_stu)->whereIn('person_id', $student_info)->update($data);
            }

            // 第二步
            if ($info) {
                $where_way['path_id'] = $info->path_id;
                $where_way['delete_flag'] = 'Y';
                $datass['pathway_count'] = DB::table('school_pathway')->where($where_way)->count();
                $where_path['self_id'] = $info->path_id;
                DB::table('school_path')->where($where_path)->update($datass);
            }

            //第三步
            $where_dis['path_id'] = $info->path_id;
            $where_dis['delete_flag'] = 'Y';
            //查询该线路下所有正常站点
            $all_path = DB::table('school_pathway')->where($where_dis)->select('self_id', 'longitude', 'dimensionality')->get()->toArray();
            /**为什么要规定可用站点的数量大于1？因为只有两个站及以上才能计算距离**/
            if ($all_path && count($all_path) > 1) {
                for($i=0;$i<count($all_path);$i++) {
                    //重新调用接口计算剩下站点之间的距离
                    //删除后的第一个站点的时间和距离应该要重新覆盖为0、0
                    if ($i < count($all_path)-1) {
                        $temp_dis= $this->getDistance($all_path[$i]->longitude, $all_path[$i]->dimensionality, $all_path[$i + 1]->longitude, $all_path[$i + 1]->dimensionality);
                        $datas[$i]['distance']=$temp_dis['distance'];
                        $datas[$i]['duration']=$temp_dis['duration'];
                        $datas[$i]['self_id']=$all_path[$i+1]->self_id;

                    }
                }
                /**删除后的第一个站点的时间和距离应该要重新覆盖为0、0，所以做一个数组放在时间距离数据的首位**/
                $o_temp=[
                    'distance'=>'0',
                    'duration'=>'0',
                    'self_id'=>$all_path[0]->self_id
                ];
                array_unshift($datas,$o_temp);

                //新的时间和距离已经计算好了，可以将数据重新入库
                foreach($datas as $kk=>$vv){
                    $where_new=[
                        ['self_id','=',$vv['self_id']],
                        ['delete_flag','=','Y']
                    ];
                    $new['distance']=$vv['distance'];
                    $new['duration']=$vv['duration'];
                    DB::table('school_pathway')->where($where_new)->update($new);
                }

            }else{
                //删除的剩下一个站点后，该站点的时间和距离就是0了
                $where_new=[
                    ['self_id','=',$all_path[0]->self_id],
                    ['delete_flag','=','Y']
                ];
                $new['distance']=0;
                $new['duration']=0;
            }
            DB::table('school_pathway')->where($where_new)->update($new);

        }

        $operationing->access_cause = '删除';
        $operationing->table = $table_name;
        $operationing->table_id = $self_id;
        $operationing->now_time = $now_time;
        $operationing->old_info = $status_info['old_info'];
        $operationing->new_info = $status_info['new_info'];
        $operationing->operation_type = $flag;

        $msg['code'] = $status_info['code'];
        $msg['msg'] = $status_info['msg'];
        $msg['data'] = $status_info['new_info'];

        return $msg;

    }
	
	
	/***    获取该条线路上的所有可用站点      /school/line/pathwayStation
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function pathwayStation(Request $request)
    {

        /**接收数据*/
        $path_id = $request->input('path_id');
        // dd($self_id);
        //第一步，抓取当前线路的所有可用站点
        $where_station['path_id'] = $path_id;
        $where_station['delete_flag'] = 'Y';

        $data['school_pathway'] = SchoolPathway::where($where_station)
            ->select('self_id', 'pathway_name','sort')->orderBy('sort','asc')->get();

        //判断该线路是否已经配置站点了
        if ($data['school_pathway']->count() > 0) {
            $msg['code'] = 200;
            $msg['msg'] = "数据拉取成功";
            $msg['data'] = $data;
            return $msg;
        } else {
            $msg['code'] = 301;
            $msg['msg'] = "该线路未配置站点！";
            return $msg;
        }

    }

	

    /***    配置途经点学生      /school/line/pathwayStudent
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function pathwayStudent(Request $request)
    {
        /**接收数据*/
        $self_id = $request->input('self_id');
        // $self_id='pathway_2020082510142312569239991';
        //dump($self_id);111
        //第一步，抓取头部信息1
        $where_group['self_id'] = $self_id;
        $where_group['delete_flag'] = 'Y';

        $data['school_pathway'] = SchoolPathway::where($where_group)
            ->select('self_id', 'pathway_name', 'english_pathway_name', 'path_name', 'group_code', 'site_type')->first();


        //dump($data['school_pathway']);

        if ($data['school_pathway']->count() > 0) {

            //第三步，抓取所有的人的信息可配置人的信息
            $where_student['a.group_code'] = $data['school_pathway']->group_code;
            $where_student['a.person_type'] = 'student';
            $where_student['a.delete_flag'] = 'Y';


            $where_student2['b.pathway_type'] = $data['school_pathway']->site_type;
            $where_student2['b.delete_flag'] = 'Y';

            //dump($where_student);

            //dump($where_student2);

            //这里要加一个判断，因为->where('b.pathway_type','=','DOWN');   不能使用变量
            $data['school_info'] = DB::table('school_info as a')->where($where_student)
                ->join('school_pathway_person as b', function ($join) use ($where_student2) {
                    $join->on('a.self_id', '=', 'b.person_id')
                        ->where($where_student2);
                }, null, null, 'left')
                ->select('a.self_id as key', 'a.actual_name', 'a.english_name', 'a.grade_name', 'a.class_name', 'b.pathway_id', 'b.pathway_name')
                ->get()->toArray();

//dd($data['school_info']);

            $msg['code'] = 200;
            $msg['msg'] = "数据拉取成功";
            $msg['data'] = $data;


            return $msg;
        } else {
            $msg['code'] = 301;
            $msg['msg'] = "没有查询到数据";
            return $msg;
        }

    }

    /***    配置途经点学生入库      /school/line/pathwayStudentAdd
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

    public function pathwayStudentAdd(Request $request)
    {
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $now_time = date('Y-m-d H:i:s', time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $now_time = date('Y-m-d H:i:s', time());
        $table_name = 'school_pathway_person';
        $operationing->access_cause = '新增/修改站点学生配置';
        $operationing->table = $table_name;
        $operationing->now_time = $now_time;
        $operationing->operation_type = 'create';

        /**接收数据*/
        $pathway_id = $request->input('pathway_id');              //站点ID
        $students = $request->input('students') ?? '';              //站点ID
        $input = $request->all();

        /*** 虚拟数据
         * $input['pathway_id']=$pathway_id='pathway_202009061337268868445839';
         * $students=[
         * 'info_202005250944543707467764','info_202005250944543707237798'
         * ];**/

        $rules = [
            'pathway_id' => 'required',
        ];
        $message = [
            'pathway_id.required' => '线路不能为空',
        ];

        $validator = Validator::make($input, $rules, $message);

        if ($validator->passes()) {
            /*** 查询中这个线路中的所有需要的信息**/
            $path_where['self_id'] = $pathway_id;
            $pathway_info = DB::table('school_pathway')->where($path_where)->select('path_id', 'pathway_name', 'site_type', 'group_code', 'group_name')->first();


            $where_has['delete_flag'] = 'Y';
            $where_has['pathway_id'] = $pathway_id;
            $has_stu = DB::table('school_pathway_person')->where($where_has)->pluck('person_id')->toArray();

            $where_old['delete_flag'] = 'Y';
            $where_old['pathway_id'] = $pathway_id;

            /**获取该站点上已经配置过的学生**/
            $old_temp = DB::table('school_pathway_person')->where($where_old)->select('self_id', 'person_name')->get()->toArray();
            if ($old_temp) {
                $operationing->old_info = json_encode($old_temp);
            }

            if ($students && count($students) > 0) {
                /**  $temp2   新增的小孩？要操作，第一步，如果这个小孩在其他的站点上，是不是要修改成N
                 * 假设这个$temp2  =【1,2】  是不是需要作为条件去其他站点删掉？
                 *    DB::table('school_pathway_person')->where($where_hass)->whereIn('person_id',$temp2)->update($update);
                 *
                 **/
                $temp2 = array_diff($students, $has_stu);                       //新增的学生                 添加数据进去

                $temp3 = array_diff($has_stu, $students);                       //删除的学生                 修改成删除

                $update['delete_flag'] = 'N';
                $update['update_time'] = $now_time;
                $where_hass['pathway_type'] = $pathway_info->site_type;

                /**如果原来已经在线路上的学生在新的提交过程中没有被重新提交，则默认该学生被移除该线路了**/
                DB::table('school_pathway_person')->where($where_hass)->whereIn('person_id', $temp3)->update($update);


                //数组重合的就算了，新增的学生如果还在其他站点上，则需要删除他，因为人没有分身术
                DB::table('school_pathway_person')->where($where_hass)->whereIn('person_id', $temp2)->update($update);

                $data['pathway_id'] = $pathway_id;
                $data['path_id'] = $pathway_info->path_id;
                $data['pathway_name'] = $pathway_info->pathway_name;
                $data['group_code'] = $pathway_info->group_code;
                $data['group_name'] = $pathway_info->group_name;
                $data['create_time'] = $data['update_time'] = $now_time;
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['pathway_type'] = $pathway_info->site_type;


                foreach ($temp2 as $k => $v) {

                    $data['self_id'] = generate_id('person_');
                    $where22['self_id'] = $v;
                    $where22['delete_flag'] = "Y";

                    $std_info = DB::table("school_info")->where($where22)->select('self_id', 'actual_name', 'grade_name', 'class_name')->first();

                    $data['person_name'] = $std_info->actual_name;
                    $data['person_id'] = $std_info->self_id;
                    $data['grade_name'] = $std_info->grade_name;
                    $data['class_name'] = $std_info->class_name;


                    DB::table("school_pathway_person")->insert($data);
                }

                $new_temp = DB::table('school_pathway_person')->where($where_has)->select('self_id', 'person_name')->get()->toArray();

                if ($new_temp) {
                    $operationing->new_info = json_encode($new_temp);

                }
                $operationing->table_id = $pathway_id;
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
            } else {
                $update['delete_flag'] = 'N';
                $update['update_time'] = $now_time;

                $where_hass['delete_flag'] = 'Y';
                $where_hass['pathway_id'] = $pathway_id;
                $where_hass['pathway_type'] = $pathway_info->site_type;
                //dump($has_stu) ;
                DB::table('school_pathway_person')->where($where_hass)->update($update);
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
            }

            return $msg;
        } else {
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

    /***    获取指定线路的历史轨迹      /school/line/phistoryPathwayLin
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function historyPathwayLin(Request $request){
        $input=$request->all();
        $rules=[
            'path_id'=>'required',
            'site_type'=>'required',
            'time'=>'required',
        ];
        $message=[
            'path_id.required'=>'线路id不能为空',
            'site_type.required'=>'线路状态不能为空',
            'time.required'=>'时间不能为空',
        ];
        /**
         * 虚拟数据
         */
        $input['path_id']='path_202009041657438755373511';
        $input['ite_type']='UP';
        $input['time']='2020-09-10';
//        dd($input);
        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            /**
             * 第一步：通过path_id查线路轨迹，制作前端需要的数组形式
             * 第二步：通过path_id查询线路上可用的站点
             */
            //第一步
            $temp='car_'.$input['path_id'].$input['ite_type'].$input['time'];
            $wherePathWay['type']='car';
            $wherePathWay['carriage_id']=$temp;
            $line_temp=DB::table('school_carriage_json')->where($wherePathWay)->select('json')->get()->toArray();

            //第二步：
            $where_station['path_id']=$input['path_id'];
            $where_station['delete_flag']='Y';
            $msg['station']=DB::table('school_pathway')->where($where_station)->select('self_id','pathway_name','longitude','dimensionality')->get()->toArray();

//            $line=json_decode($line_temp,true);
            //开始制作返回的轨迹格式
            //初始化所需数据
            $data=[
                'name'=>$input['path_name'],
                'path'=>null,
            ];
            $msg['data']=null;
            //处理开始的地方
            if($line_temp && count($line_temp)>0){
                $temp3=[];
                foreach ($line_temp as $k=>$v){
                    $temp2=json_decode($v->json,true);
                    foreach ($temp2 as $kk=>$vv){
                        $temp3[]=[$vv['longitude'],$vv['latitude']];
                    }
                }
                $data['path']=$temp3;
                $msg['data']=$data;
            }
            if($data['path']){
                $msg['code']=200;
                $msg['msg']='成功';
            }else{
                $msg['code']=300;
                $msg['msg']='没有查询到轨迹！';
            }


        }else{
            $erro=$validator->errors()->all();
            $msg['code']=300;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
        }
//        dd($msg);
        return $msg;
    }


    /**
     * 计算时间和距离的共用方法
     * 参数为 起点经纬度和终点经纬度
     *
     */
    public function getDistance($longitude, $latitude, $enterLongitude, $enterLatitude, $key = '4e481c099d1871be2e8989497ab26e46')
    {
        $origin = $longitude . ',' . $latitude;               //这个是当前的位置经纬度
        $destination = $enterLongitude . ',' . $enterLatitude;
        $queryUrl = 'https://restapi.amap.com/v3/direction/driving?origin=' . $origin . '&destination=' . $destination . '&extensions=base&output=json&key=' . $key . '&strategy=10';
        $json = $this->httpGet($queryUrl);
        $back_temp = json_decode($json, true);
        $back['distance']=$back_temp['route']['paths'][0]['distance'];
        $back['duration']=$back_temp['route']['paths'][0]['duration'];
        return $back;
    }
    /**
     * GET 请求远程的链接
     * @param $url
     * @return mixed
     */
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

    /***    站点学生匹配批量导入      /school/line/studentImport
     */
    public function studentImport(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数1
        $now_time           = date('Y-m-d H:i:s', time());

        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $group_code         =$request->input('group_code');
		
		/****虚拟数据
        $input['importurl']    =$importurl="uploads/2020-09-23/4567.xlsx";
        $input['group_code']   =$group_code='group_202006221103544823194960';***/
		
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

            $info=[];           //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
			
			
			
            $where_pack['group_code'] = $group_code;
            $where_pack['delete_flag'] = 'Y';
            $group_name = SystemGroup::where($where_pack)->value('group_name');
			
			//dump($group_name);
			/**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
			if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }
			
			$res = Excel::toArray((new Import),$importurl);
			

			if(array_key_exists('0', $res)){
                $info=$res[0];
            }
			
			$ret['cando']='Y';
            $ret['msg']=null;
			
			array_shift($info);      //把数组的第一个项目去掉
            $info_check=array_filter(array_column($info,1));                        //获取二维数组中指定的值,并去掉空值
             //dump($info_check);
            $ret=array_number($info_check,1,'学籍号',$ret);
			
			if($ret['cando'] == 'N'){
                $msg['code'] = 303;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
			/** 二次效验结束**/
			
			/**现在看看如何制作数据了！！！！****/
            $select=['self_id','group_code','actual_name','english_name','grade_name','class_name','group_name'];
//            $path_old_info=SchoolPathway::where($where_pack)->select($select)->get();
            $a=2;
            foreach($info as $k => $v){
                if($v[1] && $v[2] && $v[3]){
                    //看看这个学生是不是有数据
                    $where_student=[
                        ['identity_card','=',$v[1]],
                        ['group_code','=',$group_code],
                        ['delete_flag','=','Y'],
                        ['person_type','=','student'],
                    ];
                    $school_info=SchoolInfo::where($where_student)->select($select)->first();
                    if($school_info){
                        //看看这个线路有没有
                        $where_pathway=[
                            ['pathway_name','=',$v[3]],
                            ['path_name','=',$v[2]],
                            ['group_code','=',$group_code],
                            ['delete_flag','=','Y'],
                        ];

                        $pathway_info=SchoolPathway::where($where_pathway)->select('self_id','path_id','site_type','pathway_name','path_name','english_pathway_name')->first();
                        if($pathway_info){
                            $info[$k]['person_id']                  =$school_info->self_id;
                            $info[$k]['actual_name']                =$school_info->actual_name;
                            $info[$k]['grade_name']                 =$school_info->grade_name;
                            $info[$k]['class_name']                 =$school_info->class_name;
                            $info[$k]['site_type']                  =$pathway_info->site_type;
                            $info[$k]['pathway_name']               =$pathway_info->pathway_name;
                            $info[$k]['english_pathway_name']       =$pathway_info->english_pathway_name;
                            $info[$k]['path_name']                  =$pathway_info->path_name;
                            $info[$k]['pathway_id']                 =$pathway_info->self_id;
                            $info[$k]['path_id']                    =$pathway_info->path_id;

                        }else{
                            if($abcd<$errorNum){
                                $strs .= '数据中的第'.$a."行查询不到线路及站点".'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }

                    }else{
                        if($abcd<$errorNum){
                            $strs .= '数据中的第'.$a."行查询不到学生".'</br>';
                            $cando='N';
                            $abcd++;
                        }
                    }

                }else{
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行数据不完整".'</br>';
                        $cando='N';
                        $abcd++;
                    }

                }
                $a++;
            }

            /** 下面对数据进行插入数据库操作执行11**/
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }

            $count=count($info);
            $update_count=0;
            $insert_count=0;

            foreach($info as $k => $v){
                //去查询一下这个用户是不是已经在一个站点上配置了，如果已经配置了，那么把用户这个人去掉
                $where_person=[
                    ['person_id','=',$v['person_id']],
                    ['pathway_type','=',$v['site_type']],
                ];

                $unpate['delete_flag']='N';
                $unpate['update_time'] = $now_time;
                $abc=SchoolPathwayPerson::where($where_person)->update($unpate);

                $data['self_id'] = generate_id('person_');
                $data['pathway_id'] = $v['pathway_id'];
                $data['path_id'] = $v['path_id'];
                $data['pathway_name'] = $v['pathway_name'];
                $data['english_pathway_name'] =$v['english_pathway_name'];
                $data['pathway_type'] = $v['site_type'];
                $data['person_id'] = $v['person_id'];
                $data['person_name'] = $v['actual_name'];
                $data['grade_name'] = $v['grade_name'];
                $data['class_name'] = $v['class_name'];
                $data['group_code'] = $group_code;
                $data['group_name'] = $group_name;
                $data['create_time'] = $data['update_time'] = $now_time;
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $user_info->name;

                $id=SchoolPathwayPerson::insert($data);

                if($abc){
                    $update_count++;
                }else{
                    $insert_count++;
                }



            }


            $id='1';
            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据，其中插入了'.$insert_count.'条，修改了'.$update_count.'条';

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
                $msg['msg'] .= $v . "\n";
            }
            $msg['code'] = 304;
            return $msg;
        }

    }

    /***    站点学生匹配批量导出      /school/line/lineExcel
     */
    public function lineExcel(Request $request,File $file){
        $user_info      = $request->get('user_info');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $input          =$request->all();
        //dump($input);
        /** 接收数据*/
        $group_code     =$request->input('group_code');

        /** 虚拟数据*/
        //$group_code     =$input['group_code']   ='group_202006161412449505577299';

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

            $search = [
                ['type'=> '=','name' => 'group_code','value'=>$group_code],
                ['type'=> '=','name' => 'delete_flag', 'value' => 'Y'],
            ];
            $where = get_list_where($search);

            $select = ['self_id', 'pathway_id', 'person_name', 'class_name', 'grade_name','group_name'];
            $schoolPath=['self_id', 'pathway_name', 'path_name', 'site_type','longitude','dimensionality'];
            $info= SchoolPathwayPerson::with(['schoolPathway' => function($query)use($schoolPath){
                $query->select($schoolPath);
            }])->where($where)
                ->orderBy('create_time', 'desc')
                ->select($select)
                ->get();


            //dd($info->toArray());


            if($info){
                //设置表头
                $row = [[
                    "group_name"=>'学校名称',
                    "path_name"=>'线路名称',
                    "site_type"=>'上学/放学',
                    "pathway_name"=>'站点名称',
                    "longitude"=>'经度',
                    "dimensionality"=>'维度',
                    "person_name"=>'学生姓名',
                    "class_name"=>'年级',
                    "grade_name"=>'班级',
                ]];

                /** 现在根据查询到的数据去做一个导出的数据**/

                $data_execl=[];
                foreach ($info as $k => $v){
                    $new=[];

                    $new['group_name']              =$group_name;
                    $new['path_name']               =$v->schoolPathway['path_name'];

                    if($v->schoolPathway['site_type'] == 'UP'){
                        $new['site_type']           ='上学';
                    }else{
                        $new['site_type']           ='放学';
                    }

                    $new['pathway_name']            =$v->schoolPathway['pathway_name'];
                    $new['longitude']               =$v->schoolPathway['longitude'];
                    $new['dimensionality']          =$v->schoolPathway['dimensionality'];
                    $new['person_name']             =$v->person_name;
                    $new['class_name']              =$v->class_name;
                    $new['grade_name']              =$v->grade_name;

                    $data_execl[]=$new;
                }

                /** 调用EXECL导出公用方法，将数据抛出来***/
                $browse_type=$request->path();
                $msg=$file->export($data_execl,$row,$group_code,$group_name,$browse_type,$user_info,$where,$now_time);

                return $msg;


            }else{
                $msg['code']=302;
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


