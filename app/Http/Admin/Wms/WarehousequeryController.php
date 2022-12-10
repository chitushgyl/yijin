<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use App\Models\Wms\WmsWarehouse;
use App\Models\Wms\WmsWarehouseArea;

class WarehousequeryController extends CommonController{

    /***   库位统计      /wms/warehousequery/warehousequeryList
     */
    public function  warehousequeryList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    库位统计分页      /wms/warehousequery/warehousequeryPage
     */
    public function warehousequeryPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $warm_name      =$request->input('warm_name');
        $min_warm       =$request->input('min_warm');
        $max_warm      =$request->input('max_warm');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
//            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
//            ['type'=>'=','name'=>'group_code','value'=>$group_code],
//            ['type'=>'like','name'=>'warm_name','value'=>$warm_name],
//            ['type'=>'>=','name'=>'min_warm','value'=>$min_warm],
//            ['type'=>'<=','name'=>'max_warm','value'=>$max_warm],
        ];

        $where=get_list_where($search);
        $select=['self_id','warehouse_name','city','warehouse_contacts','warehouse_tel','warehouse_address'];

        $Signselect=['warehouse_id','self_id'];
        $wmsLibrarySigeSelect=['warehouse_sign_id','self_id','now_num'];
//        dd($select);
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsWarehouse::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouse::with(['wmsWarehouseSign' => function($query)use($Signselect,$wmsLibrarySigeSelect){
                    $query->where('delete_flag','=','Y');
                    $query->select($Signselect);
                    $query->with(['wmsLibrarySige' => function($query)use($wmsLibrarySigeSelect){
                        $query->where('delete_flag','=','Y');
                        $query->where('now_num','>','0');
                        $query->select($wmsLibrarySigeSelect);
                    }]);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsWarehouse::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouse::with(['wmsWarehouseSign' => function($query)use($Signselect,$wmsLibrarySigeSelect){
                    $query->where('delete_flag','=','Y');
                    $query->select($Signselect);
                    $query->with(['wmsLibrarySige' => function($query)use($wmsLibrarySigeSelect){
                        $query->where('delete_flag','=','Y');
                        $query->where('now_num','>','0');
                        $query->select($wmsLibrarySigeSelect);
                    }]);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsWarehouse::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouse::with(['wmsWarehouseSign' => function($query)use($Signselect,$wmsLibrarySigeSelect){
                    $query->where('delete_flag','=','Y');
                    $query->select($Signselect);
                    $query->with(['wmsLibrarySige' => function($query)use($wmsLibrarySigeSelect){
                        $query->where('delete_flag','=','Y');
                        $query->where('now_num','>','0');
                        $query->select($wmsLibrarySigeSelect);
                    }]);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();

                $data['group_show']='Y';
                break;
        }


//        dump($data['items']->toArray());


        foreach ($data['items'] as $k=>$v) {
            //定义3个变量
            $v->count=0;
            $v->use_count=0;
            $v->leisure_count=0;
            if($v->wmsWarehouseSign){
                $v->count=$v->wmsWarehouseSign->count();
                foreach ($v->wmsWarehouseSign as $kk => $vv){
                    if($vv->wmsLibrarySige){
                        $v->use_count++;
                    }
                }
                $v->leisure_count=$v->count-$v->use_count;
                if($v->count == 0){
                    $v->use_rate='未使用';
                }else{
                    $v->use_rate=number_format($v->use_count/$v->count*100,0).'%';
                }
//                dump($v->count);

            }

            $v->button_info=$button_info;




        }
//        dd($data['items']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

/***    库位统计分页      /wms/warehousequery/details
     */
    public function details(Request $request){
        $self_id=$request->input('self_id');
        //$self_id='warehouse_202012181606196518756137';

        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','warehouse_name','city','warehouse_contacts','warehouse_tel','warehouse_address'];
        $data['warehouse_info']=WmsWarehouse::where($where)->select($select)->first();

        if($data['warehouse_info']){
            $data['warehouse_info']->count=0;
            $data['warehouse_info']->use_count=0;
            $data['warehouse_info']->leisure_count=0;
            $data['warehouse_info']->maintain=0;

            $where2=[
                ['delete_flag','=','Y'],
                ['warehouse_id','=',$self_id],
            ];
            $Areaselect=['warehouse_id','self_id','area'];
            $Signselect=['warehouse_id','self_id','area_id','use_flag','area','row','column','tier'];
            $wmsLibrarySigeSelect=['warehouse_sign_id','self_id','now_num'];

            $data['area_info']=WmsWarehouseArea::with(['wmsWarehouseSign' => function($query)use($Signselect,$wmsLibrarySigeSelect){
                $query->where('delete_flag','=','Y');
                $query->select($Signselect);
                $query->with(['wmsLibrarySige' => function($query)use($Signselect,$wmsLibrarySigeSelect){
                    $query->where('delete_flag','=','Y');
                    $query->where('now_num','>','0');
                    $query->select($wmsLibrarySigeSelect);
                }]);
            }])->where($where2)->orderBy('create_time', 'desc')
                ->select($Areaselect)->get();

            foreach ($data['area_info'] as $k => $v){
                $v->count=0;
                $v->maintain=0;
                $v->use_count=0;
                $v->leisure_count=0;

                if($v->wmsWarehouseSign){
                    //DUMP(111);
                    foreach ($v->wmsWarehouseSign as $kk => $vv){
                        $vv->sign=$vv->area.'-'.$vv->row.'-'.$vv->column.'-'.$vv->tier;
                          if($vv->use_flag == 'N'){
                              $vv->status = 'N';
                              $v->count++;
                              $v->maintain++;
                              $data['warehouse_info']->count++;
                              $data['warehouse_info']->maintain++;

                          }else{
                             if($vv->wmsLibrarySige){
                                 $vv->status = 'Y';
                                 $v->count++;
                                 $v->use_count++;
                                 $data['warehouse_info']->count++;
                                 $data['warehouse_info']->use_count++;

                             }else{
                                 $vv->status = 'K';
                                 $v->count++;
                                 $v->leisure_count++;
                                 $data['warehouse_info']->count++;
                                 $data['warehouse_info']->leisure_count++;

                             }
                          }
                    }
                }

                if($v->count > 0){
                    $v->use_rate=number_format($v->use_count/$v->count*100,0).'%';
                }else{
                    $v->use_rate='未使用';
                }
            }

            if($data['warehouse_info']->count > 0){
                $data['warehouse_info']->use_rate=number_format($data['warehouse_info']->use_count/$data['warehouse_info']->count*100,0).'%';
            }else{
                $data['warehouse_info']->use_rate='未使用';
            }

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            //dd($msg);
            return $msg;

        }else{
            $msg['code']=300;
            $msg['msg']="拉取不到数据";
            return $msg;

        }


    }


}
?>
