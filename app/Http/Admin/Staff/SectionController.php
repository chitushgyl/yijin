<?php
namespace App\Http\Admin\Staff;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Tools\Import;
use App\Http\Requests;
use App\Http\Controllers\StatusController as Status;
use App\Models\Group\SystemGroup;
use App\Models\Group\SystemAuthority;
use App\Models\Group\SystemSection;


class SectionController extends CommonController{

    /***    部门列表      /staff/section/sectionList
     */

    public function  sectionList(Request $request){

        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    部门分页      /staff/section/sectionPage
     */
    public function sectionPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;
        $login      =$request->input('login');
        $name       =$request->input('name');
        $group_name       =$request->input('group_name');
        $section_name       =$request->input('section_name');
        $tel        =$request->input('tel');
        $use_flag   =$request->input('use_flag');

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'like','name'=>'group_name','value'=>$group_name],
            ['type'=>'like','name'=>'section_name','value'=>$section_name],

        ];
        $where=get_list_where($search);
        $select=['self_id','section_name','group_code','group_name','create_user_name','create_time','use_flag'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemSection::where($where)->count(); //总的数据量
                $data['items']=SystemSection::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemSection::where($where)->count(); //总的数据量
                $data['items']=SystemSection::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=SystemSection::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemSection::where($where)->whereIn('group_code',$group_info['group_code'])
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


    /***    部门添加      /staff/section/createSection
     */
    public function createSection(Request $request){
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $self_id=$request->input('self_id');

        $where['delete_flag']='Y';
        $select=['group_code','group_name'];

        switch ($group_info['group_id']){
            case 'all':
                $data['info']=SystemSection::where($where)->orderBy('create_time', 'desc')->select($select)->get();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['info']=SystemGroup::where($where)->orderBy('create_time', 'desc')->select($select)->get();
                break;

            case 'more':
                $data['info']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->orderBy('create_time', 'desc')->select($select)->get();
                break;

        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    部门添加进入数据库      /staff/section/addSection
     */
    public function addSection(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='system_section';

        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数

        $operationing->access_cause='添加/修改部门';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        /**接收数据*/
        $self_id        		=$request->input('self_id');
        $section_name          	=$request->input('section_name');
        $group_code           	=$request->input('group_code');

        $input=$request->all();
        /**虚拟数据
            $self_id=$input['self_id']='section_202012231640197157177543';
            $section_name=$input['section_name']='2232333232223232';
            $group_code=$input['group_code']='1234';
         ***/

        $rules=[
            'section_name'=>'required',
            'group_code'=>'required',
        ];
        $message=[
            'section_name.required'=>'部门名称不能为空',
            'group_code.required'=>'所属公司不能为空',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            $where_group=[
                ['group_code','=',$group_code],
            ];
            $group_name=SystemGroup::where($where_group)->value('group_name');

            if(empty($group_name)){
                $msg['code']=301;
                $msg['message']='公司不存在';
                return $msg;
            }

            $section_name=trim($section_name);
            if($self_id){
                $whereSection=[
                    ['delete_flag','=','Y'],
                    ['section_name','=',$section_name],
                    ['group_code','=',$group_code],
                    ['self_id','!=',$self_id],
                ];
            }else{
                $whereSection=[
                    ['delete_flag','=','Y'],
                    ['section_name','=',$section_name],
                    ['group_code','=',$group_code],
                ];
            }

            $count=SystemSection::where($whereSection)->count();
            if($count>0){
                $msg['code']=302;
                $msg['message']='部门名称已存在';
                return $msg;
            }

            $data['section_name']=$section_name;


            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];
            $old_info=SystemSection::where($where)->first();


            if($old_info){
                $data['update_time']=$now_time;
                $id=SystemSection::where($where)->update($data);

                $operationing->access_cause='修改部门';
                $operationing->operation_type='update';

            }else{
                $data['self_id']= generate_id('section_');
                $data['create_time']=$data['update_time']=$now_time;
                $data['create_user_id'] =$user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['group_code']=$group_code;
                $data['group_name']=$group_name;
                $id=SystemSection::insert($data);

                $operationing->access_cause='新增部门';
                $operationing->operation_type='update';
            }

//dump($id);
//dd($data);
            $operationing->table_id=$old_info?$self_id:$data['self_id'];
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


    /***    部门启用禁用      /staff/section/sectionUseFlag
     */
    public function sectionUseFlag(Request $request,Status $status){
        $now_time       =date('Y-m-d H:i:s',time());
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='system_section';
        $medol_name     ='SystemGroup';
        $self_id        =$request->input('self_id');
        $flag='useFlag';

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

    /***    部门删除      /staff/section/sectionDelFlag
     */
    public function sectionDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='system_section';
        $medol_name='SystemGroup';
        $self_id=$request->input('self_id');
        $flag='delFlag';
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

    /***    获取部门      /staff/section/getAddSection
     */

    public function getAddSection(Request $request,Status $status){
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $group_code=$request->input('group_code');
        //$group_code='group_202012251449437824125582';
        $whereSection=[
            ['delete_flag','=','Y'],
            ['group_code','=',$group_code],
        ];
        $select=['self_id','section_name','group_code','group_name'];
        $info=SystemSection::where($whereSection)->orderBy('create_time', 'desc')->select($select)->get();
        $msg['code']=200;
        $msg['msg']='拉取数据成功';
        $msg['data']=$info;
        return $msg;

    }
}
?>
