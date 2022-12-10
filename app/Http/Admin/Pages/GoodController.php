<?php
namespace App\Http\Admin\Pages;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Shop\ShopCatalogGood;


class GoodController  extends CommonController{
    /***    分类挂接商品头部       /pages/good/goodList
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  goodList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
  }

    /***    分类挂接商品分页      /pages/good/goodPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
	public function goodPage(Request $request){
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数

        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $thereclassify  =$request->input('thereclassify');
        $twoclassify    =$request->input('twoclassify');
        $oneclassify    =$request->input('oneclassify');
        $group_code     =$request->input('group_code');

        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'catalog_id','value'=>$thereclassify],
            ['type'=>'all','name'=>'parent_catalog_id','value'=>$twoclassify],
            ['type'=>'all','name'=>'parent_parent_catalog_id','value'=>$oneclassify],
            ['type'=>'all','name'=>'group_code','value'=>$group_code],
//            ['type'=>'=','name'=>'b.delete_flag','value'=>'Y'],
        ];

        $where=get_list_where($search);

        $erpShopGoodsWhere=[
            ['delete_flag','=','Y'],
        ];

        $select=['self_id','good_id','group_name','use_flag','parent_parent_catalog_name','parent_catalog_name','catalog_name','create_user_name','create_time'];
        $erpShopGoodsSelect=['self_id','good_name','good_status','commodity_number'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ShopCatalogGood::with(['erpShopGoods' => function($query)use($erpShopGoodsWhere) {
                    $query->where($erpShopGoodsWhere);
                }])->where($where)->count(); //总的数据量

                $data['items']=ShopCatalogGood::with(['erpShopGoods' => function($query)use($erpShopGoodsWhere,$erpShopGoodsSelect) {
                    $query->select($erpShopGoodsSelect);
                    $query->where($erpShopGoodsWhere);
                }])->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();


                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ShopCatalogGood::with(['erpShopGoods' => function($query)use($erpShopGoodsWhere) {
                    $query->where($erpShopGoodsWhere);
                }])->where($where)->count(); //总的数据量

                $data['items']=ShopCatalogGood::with(['erpShopGoods' => function($query)use($erpShopGoodsWhere,$erpShopGoodsSelect) {
                    $query->select($erpShopGoodsSelect);
                    $query->where($erpShopGoodsWhere);
                }])->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['total']=ShopCatalogGood::with(['erpShopGoods' => function($query)use($erpShopGoodsWhere) {
                    $query->where($erpShopGoodsWhere);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量

                $data['items']=ShopCatalogGood::with(['erpShopGoods' => function($query)use($erpShopGoodsWhere,$erpShopGoodsSelect) {
                    $query->select($erpShopGoodsSelect);
                    $query->where($erpShopGoodsWhere);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                break;
        }

        //dd($data['items']->toArray());
        foreach ($data['items'] as $k=>$v) {
//            $whereee['self_id']=$v->good_id;
//            $whereee['delete_flag']='Y';
////            dd($whereee);
//            $shop_goods=DB::table('erp_shop_goods')->where($whereee)->select('good_name','good_status','commodity_number')->first();
//
//            if($shop_goods){
//                $v->good_name=$shop_goods->good_name;
//                $v->commodity_number=$shop_goods->commodity_number;
//            }else{
//                $v->good_name=null;
//                $v->commodity_number=null;
//            }

            $v->button_info=$button_info;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
      // dd($msg);
        return $msg;


	}

    /***    分类商品启用禁用      /pages/good/goodUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function goodUseFlag(Request $request){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_catalog_good';
        $medol_name='ShopCatalogGood';
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

    /***    分类删除      /pages/good/goodDelFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function goodDelFlag(Request $request){
        $status=new StatusController;
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_catalog_good';
        $medol_name='ShopCatalogGood';
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
