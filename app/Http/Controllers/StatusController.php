<?php
/**
* 此控制器主要作用启用禁用，删除等公用方法
*
*/
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class StatusController extends Controller{
    /***    改变状态，主要用于启用/禁用，删除     changeFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function changeFlag($table_name,$medol_name,$self_id,$flag,$now_time){
        $where['self_id']=$self_id;
        $select=['use_flag','delete_flag', 'update_time', 'group_code', 'group_name'];

        $old_info = DB::table($table_name)->where($where)->select($select)->first();
        if($old_info){
            switch ($flag){
                case 'useFlag':
                    $data['update_time']=$now_time;
                    if($old_info->use_flag=='Y'){
                        $data['use_flag']='N';
                    }else{
                        $data['use_flag']='Y';
                    }
                    break;

                case 'delFlag':
                    $data['delete_flag']='N';
                    $data['update_time']=$now_time;
                    break;
            }
            $id=DB::table($table_name)->where($where)->update($data);
            $return_data['old_info']=$old_info;
            $return_data['new_info']=(object)$data;
            //dd($data);
            if($id){
                $return_data['code']=200;
                $return_data['msg']="操作成功";
                return $return_data;
            }else{
                $return_data['code']=301;
                $return_data['msg']="操作失败";
                return $return_data;
            }

        }else{
            $return_data['code']=300;
            $return_data['msg']="没有查询到数据";
            $return_data['old_info']=null;
            $return_data['new_info']=null;
            return $return_data;
        }

     }




}
