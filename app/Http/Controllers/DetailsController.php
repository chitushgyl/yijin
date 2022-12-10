<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Log\LogRecordChange;


class DetailsController extends Controller{
    /***    专门用来抓取数据详情的
     */
    public function details($self_id,$table_name,$select){
        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];
		//dd($table_name);
        $info=DB::table($table_name)->where($where)->select($select)->first();

        return $info;

    }

    /***    专门用来抓取数据详情操作记录数据
     */
    public function change($self_id,$log_num){

        $log_where=[
            ['table_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];
        $select=['self_id','access_cause','result','create_time','create_user_name'];
        $log_data=LogRecordChange::where($log_where)->select($select)->limit($log_num)->orderBy('create_time', 'desc')->get();

        return $log_data;


    }




}
