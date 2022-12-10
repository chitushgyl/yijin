<?php
namespace App\Http\Admin\History;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Models\Log\LogRecordChange;

class HistoryController extends CommonController{
    /***    操作历史信息头部      /history/history/historyList
     */
    public function  historyList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');
        $data['operation_info']=config('page.operation_type');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;


    }

    /***    操作历史信息分页      /history/history/historyPage
     */
    public function  historyPage(Request $request){
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num=$request->input('num')??10;
        $page=$request->input('page')??1;
        $group_name=$request->input('wx_template_id');
        $operation_type=$request->input('operation_type');
        $table=$request->input('table');
        $listrows=$num;
        $firstrow=($page-1)*$listrows;


        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
            ['type'=>'all','name'=>'operation_type','value'=>$operation_type],
            ['type'=>'all','name'=>'table','value'=>$table],
        ];

        $where=get_list_where($search);

        //如果不是超级管理员，则需要加一个条件
        if($user_info->authority_id!='10'){
            $where[] = ['admin_flag','=','Y'];
        }

        $select=['self_id','access_cause','browse_type','table','table_id','create_user_name','create_time','group_name','operation_type','log_status','result'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=LogRecordChange::where($where)->count(); //总的数据量
                $data['items']=LogRecordChange::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=LogRecordChange::where($where)->count(); //总的数据量
                $data['items']=LogRecordChange::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=LogRecordChange::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=LogRecordChange::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v){
            $v->button_info=$button_info;
        }

        //dd($data);

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


    }

    /***    操作详情      /history/history/details
     */
    public function details(Request $request){
        /**接收数据*/
        $self_id            =$request->input('self_id');
//        $self_id            ='change_202008012214353739141129';
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];

        $data['info']=LogRecordChange::where($where)->first();


        $old_info=json_decode($data['info']->old_info, true);
        $new_info=json_decode($data['info']->new_info, true);


         //取   $old_info  和  $new_info  key的合集
        if($old_info){
            $old_info_key=array_keys($old_info);
        }else{
            $old_info_key=[];
        }

        if($new_info){
            $new_info_key=array_keys($new_info);
        }else{
            $new_info_key=[];
        }

        $intersection = $old_info_key + $new_info_key;

        $data['details']=[];
        foreach ($intersection as $k => $v){
            $data['details'][$k]['name']=$v;
            if(array_key_exists($v,$old_info)){
                $data['details'][$k]['old']=$old_info[$v];
            }else{
                $data['details'][$k]['old']=null;
            }

            if(array_key_exists($v,$new_info)){
                $data['details'][$k]['new']=$new_info[$v];
            }else{
                $data['details'][$k]['new']=null;
            }

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        return $msg;
    }

}
?>
