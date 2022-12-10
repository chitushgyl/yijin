<?php
namespace App\Http\Admin\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\StatusController as Status;
use App\Models\School\SchoolHardware;
use App\Models\School\SchoolInfo;
use App\Models\School\SchoolCarInfo;


class MacController  extends CommonController
{
    /***    商户权限头部      /company/mac/macList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function macList(Request $request)
    {
        //引入配置文件
        $data['page_info'] = config('page.listrows');
        $data['button_info'] = $request->get('anniu');

        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;

        //dd($msg);
        return $msg;

    }

    /***    商户权限头部      /company/mac/macPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function macPage(Request $request)
    {
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
//dd($group_info);
        /**接收数据*/
        $num            = $request->input('num') ?? 10;
        $page           = $request->input('page') ?? 1;
        $mac_address    = $request->input('mac_address');

        $listrows = $num;
        $firstrow = ($page - 1) * $listrows;
        $search = [
			['type' => '=', 'name' => 'mac_address', 'value' => $mac_address],
            ['type' => '=', 'name' => 'delete_flag', 'value' => 'Y'],
        ];

        $where = get_list_where($search);

        $select=['self_id','mac_address','use_group_name','car_id','car_brand','deploy_user_name','deploy_update_time','url','tel_number','type'];
        switch ($group_info['group_id']) {
            case 'all':
                $data['total'] = SchoolHardware::where($where)->count(); //总的数据量
                $data['items'] = SchoolHardware::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'one':
                
                $data['total'] = SchoolHardware::where($where)->count(); //总的数据量
                $data['items'] = SchoolHardware::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['total'] = SchoolHardware::where($where)->count(); //总的数据量
                $data['items'] = SchoolHardware::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;
        }

        foreach ($data['items'] as $k => $v) {
			switch($v->type){
				case 'Android':
					$v->type='安卓设备';
				break;
				default:
					$v->type='硬件设备';
				break;
			}
			
			
			
			
            $v->button_info = $button_info;
        }
        $msg['code'] = 200;
        $msg['msg'] = "数据拉取成功";
        $msg['data'] = $data;
        //dd($msg);
        return $msg;


    }


    /***    硬件设备禁用/启用     /company/mac/macUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function macUseFlag(Request $request,Status $status){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='school_hardware';
        $medol_name='SchoolHardware';
        $self_id=$request->input('self_id');
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


    /***    硬件设备删除     /company/mac/macDeleteFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function macDeleteFlag(Request $request,Status $status)
    {
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='school_hardware';
        $medol_name='SchoolHardware';
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

    /***    获取线路     /company/mac/getPath
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function getPath(Request $request)
    {
        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数

        /**接收数据*/
        $self_id = $request->input('self_id');

        //$self_id= 'mac_202005222120512869323984';
        $where_hardware['self_id'] = $self_id;
        $data['mac_info'] = SchoolHardware::where($where_hardware)->select('mac_address', 'use_group_name', 'car_id',
		'car_brand', 'deploy_user_name', 'deploy_update_time','url','tel_number')->first();
//dump($where_hardware);
//dd($data);
        if ($data['mac_info']) {
            //拉取可配置的公司线路数据
            $where=[
                ['delete_flag','=','Y'],
            ];

            $select=['self_id', 'car_brand', 'car_number', 'group_name'];

            switch ($group_info['group_id']) {
                case 'all':
                    $data['items'] = SchoolCarInfo::where($where)
                        ->orderBy('create_time', 'desc')
                        ->select($select)->get();
                    break;

                case 'one':
                    $where[] = ['group_code', '=', $group_info['group_code']];
                    $data['items'] = SchoolCarInfo::where($where)
                        ->orderBy('create_time', 'desc')
                        ->select($select)->get();


                    break;

                case 'more':
                    $data['items'] = SchoolCarInfo::where($where)->whereIn('group_code', $group_info['group_code'])
                        ->orderBy('create_time', 'desc')
                        ->select($select)->get();

                    break;
            }

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

    /***    绑定车辆     /company/mac/binPath
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function binPath(Request $request){

        $operationing = $request->get('operationing');//接收中间件产生的参数
        $now_time=date('Y-m-d H:i:s',time());
        $table_name='school_hardware';
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        /**接收数据*/
        $self_id            = $request->input('self_id');                      //设备的ID
        $car_id             = $request->input('car_id');
        $url                = $request->input('url');
        $tel_number         = $request->input('tel_number');
        /*** 虚拟数据
        $self_id = 'mac_202005222120512869323984';
        $car_id = 'car_202008181046058402576195';
**/

        $where['self_id'] = $car_id;
        $where['delete_flag'] = 'Y';
        $car_info = SchoolCarInfo::where($where)->select('self_id', 'car_brand', 'car_number','group_code', 'group_name')->first();

        $where2['self_id']          = $self_id;
        $where2['delete_flag']      = 'Y';
        $mac_info = SchoolHardware::where($where2)->first();



        $operationing->access_cause     ='绑定车辆';
        $operationing->table            =$table_name;
        $operationing->table_id         =$self_id;
        $operationing->now_time         =$now_time;
        $operationing->old_info         =$mac_info;
        $operationing->new_info         =null;
        $operationing->operation_type   ='update';


        if ($car_info && $mac_info) {
            //清除掉之前已有的车辆绑定关系
            $wehrer['car_id'] = $car_id;
            $wehrer['delete_flag'] = 'Y';

            $data2['car_id'] = null;
            $data2['car_brand'] = null;
            $data2['use_group_code'] = null;
            $data2['use_group_name'] = null;
            $data2['deploy_user_id'] = $user_info->admin_id;
            $data2['deploy_user_name'] = $user_info->name;
            $data2['update_time'] = $now_time;
            SchoolHardware::where($wehrer)->update($data2);

            //做数据出来
            $data['car_id'] = $car_info->self_id;
            $data['car_brand'] = $car_info->car_number;
            $data['use_group_code'] = $car_info->group_code;
            $data['use_group_name'] = $car_info->group_name;
            $data['url'] = $url;
            $data['tel_number'] = $tel_number;
            $data['update_time'] = $now_time;
            $data['deploy_user_id'] = $user_info->admin_id;
            $data['deploy_user_name'] = $user_info->name;

            $id = SchoolHardware::where($where2)->update($data);

            $operationing->new_info=$data;

            if ($id) {
				//把这个carid对应的其他的东西清除掉
                $msg['code'] = 200;
                $msg['msg'] = "绑定成功";
                return $msg;
            } else {
                $msg['code'] = 302;
                $msg['msg'] = "绑定失败";
                return $msg;
            }

        } else {
            $msg['code'] = 301;
            $msg['msg'] = "没有查询到数据";
            return $msg;
        }

    }



}
?>
