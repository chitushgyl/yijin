<?php
namespace App\Http\Admin\Pages;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroup;
use App\Models\Shop\ShopKw;

class KeywordController  extends CommonController{
 /***    搜索信息头部      /pages/keyword/keywordList
 *      前端传递必须参数：
 *      前端传递非必须参数：
 */
    public function  keywordList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    搜索信息头部      /pages/keyword/keywordPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
	public function keywordPage(Request $request){
        /** 接收中间件参数**/
        $group_info         = $request->get('group_info');//接收中间件产生的参数
        $button_info        = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num                =$request->input('num')??10;
        $page               =$request->input('page')??1;
        $use_flag           =$request->input('use_flag');

        $listrows           =$num;
        $firstrow           =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];
        $where=get_list_where($search);

        $select=['group_code','group_name','use_flag','front_name','father_group_code','delete_flag'];
        $systemGroupSelect=['group_code','front_name'];
        $shopKwSelect=['group_code','self_id','kw_type','keyword_name','kw_classifys_name','kw_classifys_id','img','emphasis_flag','use_flag'];

        $shopKwWhere=[
            ['delete_flag','=','Y'],
        ];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroup::where($where)->count(); //总的数据量

                $data['items']=SystemGroup::with(['systemGroup' => function($query)use($systemGroupSelect) {
                    $query->select($systemGroupSelect);
                }])->with(['shopKw' => function($query)use($shopKwSelect,$shopKwWhere) {
                    $query->select($shopKwSelect);
                    $query->where($shopKwWhere);
                    $query->orderBy('sort','asc');
                }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroup::where($where)->count(); //总的数据量

                $data['items']=SystemGroup::with(['systemGroup' => function($query)use($systemGroupSelect) {
                    $query->select($systemGroupSelect);
                }])->with(['shopKw' => function($query)use($shopKwSelect,$shopKwWhere) {
                    $query->select($shopKwSelect);
                    $query->where($shopKwWhere);
                    $query->orderBy('sort','asc');
                }])
                    ->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();

                break;

            case 'more':
                $data['total']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量

                $data['items']=SystemGroup::with(['systemGroup' => function($query)use($systemGroupSelect) {
                    $query->select($systemGroupSelect);
                }])->with(['shopKw' => function($query)use($shopKwSelect,$shopKwWhere) {
                    $query->select($shopKwSelect);
                    $query->where($shopKwWhere);
                    $query->orderBy('sort','asc');
                }])
                    ->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();


                break;
        }




        foreach ($data['items'] as $k=>$v) {
            $v->head=null;
            $v->kw=null;
//            foreach ()
            //dump($v->shopKw);
            $classifys=[];
            $geu=0;
            foreach($v->shopKw as $kk => $vv){
                if($vv->kw_type == 'head'){
                    $v->head=$vv->keyword_name;
                }

                if($vv->kw_type == 'classifys'){
                    $classifys[$geu]=$vv->keyword_name.'--->';
                    foreach ($v->shopKw as $kkk => $vvv){
                        if($vv->self_id == $vvv->kw_classifys_id){
                            $classifys[$geu].=$vvv->keyword_name.'  ';
                        }
                    }
                    $geu++;
                }
            }
            $v->kw=$classifys;


			$v->father_front_name='http://'.$v->systemGroup->front_name;
            $v->button_info=$button_info;


        }

        //dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


	}


    /***    搜索拉取数据      /pages/keyword/createKeyword
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  createKeyword(Request $request){
        /** 接收数据*/
        $group_code=$request->input('group_code');

        //$group_code='1234';

        $where=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
        ];

        $data['kw_info']=ShopKw::where($where)->select('self_id','kw_type','keyword_name','kw_classifys_name','kw_classifys_id','img','emphasis_flag','use_flag')
            ->orderBy('sort','asc')->get();

        $where2=[
            ['group_code','=',$group_code],
            ['delete_flag','=','Y'],
			['kw_type','=','classifys'],
        ];			
			
			
		$data['kw_classifys']=ShopKw::where($where2)->select('self_id','kw_type','keyword_name','use_flag')
            ->orderBy('sort','asc')->get();
        $msg['code']=200;			
		$data['key_info']=[
            ['key'=>'icon',
                'count'=>'1',
                'name'=>'图片']
        ];
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }


    /***    创建关键字分类及关键字以及头部搜索入库添加入库      /pages/keyword/addKeyword
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addKeyword(Request $request){
        $operationing           = $request->get('operationing');//接收中间件产生的参数
        $table_name             ='shop_kw';
        $now_time               =date('Y-m-d H:i:s',time());

        $operationing->access_cause     ='新建/修改关键字';
        $operationing->operation_type   ='create';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();
		//dd($input);
        /** 接收数据*/
        $self_id                =$request->input('self_id');
        $group_code             =$request->input('group_code');
        $keyword_name           =$request->input('keyword_name');
        $start_time             =$request->input('start_time');
        $end_time               =$request->input('end_time');
        $kw_type                =$request->input('kw_type');
        $img                    =$request->input('img');
        $emphasis_flag          =$request->input('emphasis_flag');
        $kw_classifys_id        =$request->input('kw_classifys_id');



        /*** 虚拟数据*/
        $input['group_code']=$group_code='1234';
        $input['keyword_name']=$keyword_name='1212121221';
        $input['kw_type']=$kw_type='classifys';
        $input['img']=$img=[
                            '0'=>[
                                'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-14/6e26701f642f4b7912f2db9112e66577.png',
                                'width'=>'500',
                                'height'=>'100',
                            ],
                        ];
        $input['emphasis_flag']=$emphasis_flag='Y';
        $input['kw_classifys_id']=$kw_classifys_id='keyword_2020102110381841119440';



        $rules=[
            'group_code'=>'required',
            'keyword_name'=>'required',
            'kw_type'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
            'keyword_name.required'=>'热搜不能为空',
            'kw_type.required'=>'数据类型必须传递',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            $where_group=[
                ['delete_flag','=','Y'],
                ['group_code','=',$group_code],
            ];
            $select=['group_code','group_name'];
            $group_info = SystemGroup::where($where_group)->select($select)->first();
            if (empty($group_info)) {
                $msg['code'] = 301;
                $msg['msg'] = '公司不存在';
                return $msg;
            }


            if($kw_type=='keyword'){
                $where_classifys_name=[
                    ['use_flag','=','Y'],
                    ['delete_flag','=','Y'],
                    ['self_id','=',$kw_classifys_id],
                ];
                $kw_classifys_name=ShopKw::where($where_classifys_name)->value('keyword_name');

                if(empty($kw_classifys_name)){
                    $msg['code'] = 303;
                    $msg['msg'] = "所属分类不存在";
                    return $msg;
                }
            }


            $data['kw_type']                =$kw_type;
            $data['keyword_name']           =$keyword_name;
            $data['start_time']             =$start_time??'2018-11-30 00:00:00';
            $data['end_time']               =$end_time??'2099-12-31 00:00:00';
            $data['emphasis_flag']          =$emphasis_flag;
            $data['img']                    =img_for($img,'in');

            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $old_info=ShopKw::where($where)->first();

            if($old_info){
                //dd(1111);
                $operationing->access_cause     ='修改关键字';
                $operationing->operation_type   ='update';

                if($kw_type == $old_info->kw_type ){
                    $data['update_time']=$now_time;
                    $id=ShopKw::where($where)->update($data);
                }else{
                    $msg['code']=301;
                    $msg['msg']="修改时传递的类型参数不对";
                    return $msg;
                }

            }else{
                //dd(22222);
                $operationing->access_cause='新建关键字';
                $operationing->operation_type='create';

                //通过group_code去拿取数据去

                //做一个排序出来
                $where_sort=[
                    ['delete_flag','=','Y'],
                    ['kw_type','=',$kw_type],
                    ['group_code','=',$group_code],
                ];
                $sort=ShopKw::where($where_sort)->orderBy('sort','asc')->value('sort');
                $data['sort'] = $sort?$sort+1:1;
                $data['self_id']            =generate_id('keyword_');
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $group_info->group_code;
                $data['group_name']         = $group_info->group_name;
                //dd($data);
                $id=ShopKw::insert($data);
            }

            $operationing->table_id=$old_info?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;
            //dd($operationing);
            if($id){
                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
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


    /***    关键字启禁用      /pages/keyword/kwUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function kwUseFlag(Request $request){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_kw';
        $medol_name='ShopKw';
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


    /***    关键字删除      /pages/keyword/kwDelFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function kwDelFlag(Request $request){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_kw';
        $medol_name='ShopKw';
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


    /***    关键字排序     /pages/keyword/sortKeyword
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */

    //
    public function sortKeyword(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数

        /** 接收中间件参数**/
        $now_time=date('Y-m-d H:i:s',time());
        $table_name='shop_kw';
        /**接收数据*/
        $sort = $request->input('sort');

        $operationing->access_cause='排序';
        $operationing->table=$table_name;
//        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
//        $operationing->old_info=$old_info;
        $operationing->new_info=$sort;
        $operationing->operation_type='sort';


        /**接收数据
        $sort = [
            '0'=>[
                'self_id'=>'keyword_202007231139555746864961',
            ],
            '1'=>[
                'self_id'=>'keyword_202007231145025101586283',
            ],
            '2'=>[
                'self_id'=>'keyword_202007231145301647469730',
            ],
        ];*/

        if($sort){
            foreach($sort as $k => $v){
                $abc=$k+1;
                $kw['sort']=$abc;
                $kw['update_time']=$now_time;
                $where['self_id']=$v['self_id'];
                $where['delete_flag']='Y';

                $id=ShopKw::where($where)->update($kw);
            }

            if ($id) {
                $msg['code'] = 200;
                $msg['msg'] = "排序成功";
                return $msg;
            } else {
                $msg['code'] = 302;
                $msg['msg'] = "排序失败";
                return $msg;
            }

        }else{
            $msg['code'] = 301;
            $msg['msg'] = "缺少数据";
            return $msg;

        }

    }


}
?>
