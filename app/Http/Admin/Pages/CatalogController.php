<?php
namespace App\Http\Admin\Pages;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroup;
use App\Models\Shop\ShopCatalog;
use App\Models\Shop\ShopCatalogImg;

class CatalogController  extends CommonController{
    /***    自定义分类头部      /pages/catalog/catalogList
     */
    public function  catalogList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

       // dd($msg);
        return $msg;
    }

    /***    自定义分类分页数据      /pages/catalog/catalogPage
     */
	public function catalogPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
        ];

        $where=get_list_where($search);

        $shopCatalog_where=[
            ['level','=','1'],
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','group_code','group_name','use_flag'];
        $shopCatalogSelect=['group_code','self_id','catalog_name','use_flag','emphasis_flag','icon_url','start_time','end_time','sort','level'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::with(['shopCatalog' => function($query)use($shopCatalog_where,$shopCatalogSelect) {
                    $query->select($shopCatalogSelect);
                    $query->where($shopCatalog_where);
                    $query->orderBy('sort','asc');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroup::where($where)->count(); //总的数据量
                $data['items']=SystemGroup::with(['shopCatalog' => function($query)use($shopCatalog_where,$shopCatalogSelect) {
                    $query->select($shopCatalogSelect);
                    $query->where($shopCatalog_where);
                    $query->orderBy('sort','asc');
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['total']=SystemGroup::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroup::with(['shopCatalog' => function($query)use($shopCatalog_where,$shopCatalogSelect) {
                    $query->select($shopCatalogSelect);
                    $query->where($shopCatalog_where);
                    $query->orderBy('sort','asc');
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                break;
        }

        foreach ($data['items'] as $k=>$v) {
                foreach ($v->shopCatalog as $kk => $vv){
                    if($vv->start_time=='2018-11-30 00:00:00' && $vv->end_time=='2099-12-31 00:00:00'){
                        $vv->time_show='长期有效';
                    }else{
                        $vv->time_show=$vv->start_time.'～'.$vv->end_time;
                    }

                    $vv->icon_url=img_for($vv->icon_url,'one');
                }

            $v->button_info=$button_info;
        }

        //dd($data['items']->toArray());


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

	}

    /***    自定义分类数据操作      /pages/catalog/createCatalog
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  createCatalog(Request $request){



        dd(111);
        $group_code=$request->group_code;
        $wherer['group_code']=$group_code;
        $group_name=DB::table('system_group')->where($wherer)->value('group_name');
        $where['group_code']=$group_code;
        $where['delete_flag']='Y';
        $where['level']=1;
        $classifys=DB::table('shop_catalog')->where($where)->orderBy('sort', 'asc')->get()->toArray();

        $img_url=config('aliyun.oss.url');
        foreach ($classifys as $k=>$v){
            $v->icon_url=$img_url.$v->icon_url;
        }
        $back['group_code']=$group_code;
        $back['group_name']=$group_name;

        return view('App.Store.create_store',['classifys'=>$classifys,'back'=>$back]);
    }

    /***    自定义分类数据操作      /pages/catalog/addCatalog
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  addCatalog(Request $request){
        $operationing = $request->get('operationing');//接收中间件产生的参数

        $now_time   =date('Y-m-d H:i:s',time());
        $table_name ='shop_catalog';

        $operationing->access_cause     ='新建/修改分类';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;
        $operationing->operation_type   ='create';

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $catalog_name       =$request->input('catalog_name');
        $emphasis_flag      =$request->input('emphasis_flag');
        $level              =$request->input('level');
        $parent_catalog_id  =$request->input('parent_catalog_id');
        $icon_url           =$request->input('icon_url');
        $start_time         =$request->input('start_time');
        $end_time           =$request->input('end_time');
        $all_flag           =$request->input('all_flag');
        $sort_flag          =$request->input('sort_flag');
        $img                =$request->input('img');
        /*** 虚拟数据**/
        //$input['self_id']=$self_id='group_202007132106489285668739';
        $input['group_code']=$group_code='1234';
        $input['catalog_name']=$catalog_name='1如意苑';
        $input['emphasis_flag']=$emphasis_flag='#fff';
        $input['level']=$level='1';
        $input['parent_catalog_id']=$parent_catalog_id='catalog201912241722153683385567';
        $input['start_time']=$start_time='2018-11-30 00:00:00';
        $input['end_time']=$end_time='2099-12-31 00:00:00';
        $input['all_flag']=$all_flag='Y';
        $input['sort_flag']=$sort_flag='Y';
        $input['icon_url']=$icon_url=[
                '0'=>[
                    'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-14/6e26701f642f4b7912f2db9112e66577.png',
                    'width'=>'500',
                    'height'=>'100',
                    ],
                ];

        $input['img']=$img=[
            '0'=>[
                'url'=>'https://bloodcity.oss-cn-beijing.aliyuncs.com/images/2020-07-14/6e26701f642f4b7912f2db9112e66577.png',
                'width'=>'500',
                'height'=>'100',
                'jump_type'=>'good',
                'data_value'=>'good_202101091805128604759572',
            ],
        ];



        $rules=[
            'group_code'=>'required',
            'catalog_name'=>'required',
        ];
        $message=[
            'group_code.required'=>'请填写公司',
            'catalog_name.required'=>'请填写分类名称',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //效验数据的有效性


            //开始制作数据
            $data['catalog_name']=$catalog_name;
            $data['level']=$level;
            $data['emphasis_flag']=$emphasis_flag;
            $data['all_flag']=$all_flag;
            $data['sort_flag']=$sort_flag;
            $data['start_time']=$start_time;
            $data['end_time']=$end_time;

            if($parent_catalog_id){
                $data['parent_catalog_id']=$parent_catalog_id;
                $data['parent_catalog_name']=ShopCatalog::where('self_id','=',$parent_catalog_id)->value('catalog_name');
            }


            $data['icon_url'] = img_for($icon_url,'in');
            $wheres['self_id'] = $self_id;
            $old_info=ShopCatalog::where($wheres)->first();


           // dd($data);
            if($old_info){
                $data['update_time'] = $now_time;
                $id=ShopCatalog::where($wheres)->update($data);

            }else{

                $data['self_id']            = generate_id('catalog_');
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['update_time']        = $data['create_time'] = $now_time;
                $data['group_code']         =$group_code;

                //做一个排序的问题
                $eriui['group_code']=$group_code;
                $eriui['level']=$level;
                $eriui['delete_flag']='Y';
                $sort= ShopCatalog::where($eriui)->orderBy('sort','desc')->value('sort');
                $data['sort']=$sort+1;
                $data['group_name']= SystemGroup::where('self_id','=',$group_code)->value('group_name');

                $id=ShopCatalog::insert($data);
            }

            $table_id                   =$old_info?$self_id:$data['self_id'];
            $operationing->table_id     =$table_id;
            $operationing->old_info     =$old_info;
            $operationing->new_info     =$data;


            if($id){
                $shop_catalog_img=[];

                foreach ($img as $k => $v){
                    $list['self_id']            = generate_id('catalog_img_');
                    $list['catalog_id']         = $table_id;
                    $list['jump_type']          = $v['jump_type'];
                    $list['data_value']         = $v['data_value'];
                    $list['sort']               = $k+1;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['update_time']        = $list['create_time'] = $now_time;
                    $list['group_code']         =$group_code;
                    $abc[0]['url']      =$v['url'];
                    $abc[0]['width']    =$v['width'];
                    $abc[0]['height']   =$v['height'];
                    $list['url'] = img_for($abc,'in');
                    $shop_catalog_img[]=$list;
                }
                if($shop_catalog_img){
                    ShopCatalogImg::insert($shop_catalog_img);
                }


                $msg['code'] = 200;
                $msg['msg'] = "操作成功";
                return $msg;
            }else{
                $msg['code'] = 302;
                $msg['msg'] = "操作失败";
                return $msg;
            }

        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=301;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            //dd($msg);
            return $msg;
        }

    }

    /***    分类禁启用      /pages/catalog/catalogUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function catalogUseFlag(Request $request){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_catalog';
        $medol_name='ShopCatalog';
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

    /***    公司删除      /pages/catalog/catalogDelFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function classifyDelFlag(Request $request){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_catalog';
        $medol_name='ShopCatalog';
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








}
?>
