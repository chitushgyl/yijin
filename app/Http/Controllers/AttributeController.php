<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController  as Status;
use App\Models\Group\SystemGroup;
use App\Models\AttributeInfo;
class AttributeController  extends Controller{
    /***    信息头部      /attributeList
     */
    public function  attributeList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
//dd(111);
        return $msg;
    }

    /***    分页      /attributePage
     */
    public function attributePage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数
        $type           = $request->get('type');//接收中间件产生的参数
        $type='classify';
        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $group_code     =$request->input('group_code');
        $name           =$request->input('name');
        $level          =$request->input('level');
        $use_flag       =$request->input('use_flag');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        // dump($button_info);
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'=','name'=>'name','value'=>$name],
            ['type'=>'=','name'=>'level','value'=>$level],
            ['type'=>'=','name'=>'type','value'=>$type],
        ];

        $where=get_list_where($search);
        $select=['self_id','type','name','parent_id','parent_name','level','sort','create_user_name','create_time','group_name','group_code','money','img_url','use_flag'];

        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=AttributeInfo::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=AttributeInfo::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=AttributeInfo::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=AttributeInfo::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=AttributeInfo::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=AttributeInfo::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k => $v){
            $v->button_info=$button_info;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }
    /***    创建     /createAttribute
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createAttribute(Request $request){
        /** 接收数据*/
        $self_id        =$request->input('self_id');
        $group_code     =$request->input('group_code');
        $type           = $request->get('type');//接收中间件产生的参数
        //$type='classify';
        //$self_id='attribute_20210105100716218834298';
        //$group_code='1234';
        // $data['classify_info']=null;

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','type','name','parent_id','parent_name','parent_name','level','sort','create_user_name','create_time','group_name','group_code','money','img_url','use_flag'];
        $data['info']=AttributeInfo::where($where)->select($select)->first();

        $where2=[
            ['delete_flag','=','Y'],
            ['type','=',$type],
            ['level','=',1],
            ['group_code','=',$group_code],
        ];
		
        $data['info2']=AttributeInfo::where($where2)->select($select)->get();

        //dump($data['info']);

        //dd($data['info2']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }

    /***    提交      /addAttribute
     */
    public function addAttribute(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='attribute_info';
        $now_time       =date('Y-m-d H:i:s',time());

        $user_info      = $request->get('user_info');//接收中间件产生的参数

        $operationing->access_cause='新建/修改工业分类';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;


        $input=$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $name               =$request->input('name');
        $type               =$request->input('type');
        $parent_id          =$request->input('parent_id');
        $level =$request->input('level');
        $money =$request->input('money');
        $img_url =$request->input('img_url');
        /*** 虚拟数据
        $input['self_id']=$self_id=null;
        $input['group_code']=$group_code='1234';
        $input['name']=$name='克';
        $input['type']=$type='spec';
        $input['level']=$level;
        $input['parent_id']=$parent_id;
        $input['money']=$money;
        $input['img_url']=$img_url;
			*/

            $rules=[
                'group_code'=>'required',
                'name'=>'required',
                'type'=>'required',
            ];
            $message=[
                'group_code.required'=>'所属公司不能为空',
                'name.required'=>'工业分类不能为空',
                'type.required'=>'类型不能为空',
            ];


        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){

            /** 第一步，判断是不是可以添加，如果是修改，则去掉本身，然后是新增，则不需要这个条件 **/
            if($self_id){
                $where_name=[
                    ['level','=',$level],
                    ['name','=',trim($name)],
                    ['self_id','=',$self_id],
                    ['type','=',$type],
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
					['parent_id','=',$parent_id],
										
                ];

            }else{
                $where_name=[
                    ['level','=',$level],
                    ['name','=',trim($name)],
                    ['type','=',$type],
                    ['group_code','=',$group_code],
                    ['delete_flag','=','Y'],
                ];
            }
            //dump($where_name);
            $name_count = AttributeInfo::where($where_name)->count();            //检查名字是不是重复
            //dd($name_count);
            if($name_count > 0){
                $msg['code'] = 301;
                $msg['msg'] = '分类名称重复';
                return $msg;
            }

            $where_group=[
                ['group_code','=',$group_code],
                ['delete_flag','=','Y'],
            ];
            $group_name = SystemGroup::where($where_group)->value('group_name');
            if(empty($group_name)){
                $msg['code']=305;
                $msg['msg']='没有找到所属公司';
                return $msg;
            }


            //dd($input);
            /** 现在开始可以做数据了**/

            if($type=='classify' && $level != '1'){
                $where_parent_classify=[
                    ['delete_flag','=','Y'],
                    ['self_id','=',$parent_id],
                ];
                $parent_name= AttributeInfo::where($where_parent_classify)->value('name');

                if(empty($parent_name)){
                    $msg['code']=304;
                    $msg['msg']='没有找到上级分类';
                    return $msg;
                }

                $data['parent_id'] = $parent_id;
                $data['parent_name'] = $parent_name;
            }
            //dd(22222);
            $data['level'] = $level;
            $data['delete_flag'] = 'Y';
            $data['name'] = trim($name);

            $data['parent_id'] = $parent_id??null;
            $data['parent_name'] = $parent_name??null;

            $data['money'] = $money*100;
            $data['img_url']=img_for($img_url,'in');


            $where2['self_id'] = $self_id;
            $old_info=AttributeInfo::where($where2)->first();



            if($old_info){
                //dd(1111);
                //说明是修改
                $data['update_time'] =$now_time;
                $id=AttributeInfo::where($where2)->update($data);

                $operationing->access_cause='修改公共属性';
                $operationing->operation_type='update';

            }else{
                //dd(22222);
                $data['self_id']            = generate_id('attribute_');
                $data['level']              = $level;
                $data['type']               = $type;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['group_code']         =$group_code;
                $data['group_name']         =$group_name;
                $data['create_time']        =$data['update_time'] =$now_time;

                //dd($data);

                $id=AttributeInfo::insert($data);
                $operationing->access_cause='新建公共属性';
                $operationing->operation_type='create';

            }

            $operationing->table_id=$old_info?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

            if($id){
                $msg['code']=200;
                $msg['msg']='操作成功';
                $msg['data']=(object)$data;
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

    /***    启禁用      /attributeUseFlag
     */
    public function attributeUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='attribute_info';
        $medol_name='ShopClassify';
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

    /***    删除      /attributeDelFlag
     */
    public function attributeDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='attribute_info';
        $medol_name='ShopClassify';
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

    /***   	拿取      /getAttribute
     */
    public function getAttribute(Request $request){
        $group_code             =$request->input('group_code');
		$type             		=$request->input('type');
        //$group_code='1234';
        //$type='classify';
        $where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$group_code],
            ['type','=',$type],
            //['level','=',1],
        ];
		
        $select=['self_id','type','name','parent_id','parent_name','parent_name','level','sort','create_user_name','create_time','group_name','money','img_url','use_flag'];
		
        $info=AttributeInfo::with(['children' => function($query)use($select){
            $query->select($select);
        }])->where($where)->select($select)->get();

        //dd($info->toArray());


        //dd($data['classify_info']);

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$info;
        //dd($msg);
        return $msg;
    }
}
?>
