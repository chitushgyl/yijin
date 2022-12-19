<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use App\Models\Shop\ErpShopGoodsSku;

class CountController extends CommonController{

    /***   商品统计      /wms/count/countList
     */
    public function  countList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    商品统计分页      /wms/count/countPage
     */
    public function countPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');

        $warehouse_name      =$request->input('warehouse_name');
        $external_sku_id       =$request->input('external_sku_id');
        $good_name      =$request->input('good_name');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'type','value'=>'WMS'],
            ['type'=>'like','name'=>'good_name','value'=>$good_name],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'external_sku_id','value'=>$external_sku_id],

        ];

        $search1=[
            ['type'=>'like','name'=>'warehouse_name','value'=>$warehouse_name],
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'>','name'=>'now_num','value'=>0],
        ];

        $where=get_list_where($search);
        $where1 = get_list_where($search1);
        $select=['self_id','good_name','good_english_name','wms_unit','wms_target_unit','wms_scale','wms_spec',
            'group_name','use_flag','company_name','external_sku_id'];

        $Signselect=['sku_id','production_date','expire_time','can_use','warehouse_name','area','row','column','tier','now_num','warehouse_sign_id'];
//        dd($select);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
//                    $query->where('now_num','>','0');
                    $query->select($Signselect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ErpShopGoodsSku::where($where)->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ErpShopGoodsSku::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ErpShopGoodsSku::with(['wmsLibrarySige' => function($query)use($Signselect,$where1) {
                    $query->where($where1);
                    $query->select($Signselect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->select($select)
                    ->get();
                $data['group_show']='Y';
                break;
        }


//        dump($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {
            $v->count=0;
            foreach ($v->wmsLibrarySige as $kk=>$vv) {
                $vv->sign=$vv->area.'-'.$vv->row.'-'.$vv->column.'-'.$vv->tier;
                $vv->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $vv->now_num);
                $v->count +=$vv->now_num;
            }
            $v->good_describe =unit_do($v->wms_unit , $v->wms_target_unit, $v->wms_scale, $v->count);

        }
//        dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }




}
?>
