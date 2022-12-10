<?php
namespace App\Http\Admin\Wms;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Tools\Import;
use App\Http\Controllers\StatusController as Status;
use App\Http\Controllers\DetailsController as Details;
use App\Models\Wms\WmsWarehouseArea;
use App\Models\Wms\WmsWarehouse;
use App\Models\Wms\WmsWarm;


class AreaController extends CommonController{

    /***    库区列表头部      /wms/area/areaList
     */
    public function  areaList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $abc='库区';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/库区导入文件范本.xlsx',
        ];

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    库区分页      /wms/area/areaPage
     */
    public function areaPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $area           =$request->input('area');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'area','value'=>$area],
        ];


        $where=get_list_where($search);

        $select=['self_id','area','group_code','group_name','warehouse_id','warehouse_name','create_user_id','create_user_name','create_time','use_flag','warm_id'];
        $warmSelect=['self_id','warm_name','min_warm','max_warm'];
        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsWarehouseArea::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouseArea::with(['wmsWarm' => function($query)use($warmSelect) {
                    $query->select($warmSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsWarehouseArea::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouseArea::with(['wmsWarm' => function($query)use($warmSelect) {
                    $query->select($warmSelect);
                }])->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsWarehouseArea::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsWarehouseArea::with(['wmsWarm' => function($query)use($warmSelect) {
                    $query->select($warmSelect);
                }])->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }
        //dd($data['items']->toArray());

        foreach ($data['items'] as $k=>$v) {
            $v->warm_name=warm($v->wmsWarm->warm_name,$v->wmsWarm->min_warm,$v->wmsWarm->max_warm);
            $v->button_info=$button_info;

            }


        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }



    /***    新建库区      /wms/area/createArea
     */
    public function createArea(Request $request){
        /** 接收数据*/
        $self_id=$request->input('self_id');
        $where=[
            ['delete_flag','=','Y'],
            ['self_id','=',$self_id],
        ];
        $select=['self_id','area','group_code','warehouse_id','warehouse_name','use_flag','warm_id'];
        $data['info']=WmsWarehouseArea::where($where)->select($select)->first();

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


    }


    /***    新建库区数据提交      /wms/area/addArea
     */
    public function addArea(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_warehouse_area';

        $operationing->access_cause     ='创建/修改库区';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='add';
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $warehouse_id        =$request->input('warehouse_id');
        $warm_id            =$request->input('warm_id');
        $area       =$request->input('area');

        /*** 虚拟数据**/
        //$input['self_id']           =$self_id='good_202007011336328472133661';
        //$input['warehouse_id']      =$warehouse_id='ware_202006012159456407842832';
        //$input['warm_id']           =$warm_id='warm_202011151030076319521955';
        //$input['area']              =$area='211';

        $rules=[
            'warehouse_id'=>'required',
            'warm_id'=>'required',
            'area'      =>'required',
        ];
        $message=[
            'warehouse_id.required'=>'请填选择所属仓库',
            'warm_id.required'=>'请填选择温区',
            'area.required'=>'请选择库区',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()) {
            $where_warehouse=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warehouse_id],
            ];
            $select_WmsWarehouse=['warehouse_name','group_code','group_name'];
            $info2 = WmsWarehouse::where($where_warehouse)->select($select_WmsWarehouse)->first();
            if (empty($info2)) {
                $msg['code'] = 301;
                $msg['msg'] = '仓库不存在';
                return $msg;
            }


            $where_area=[
                ['delete_flag','=','Y'],
                ['area','=',$area],
                ['warehouse_id','=',$warehouse_id],
            ];
            $info = WmsWarehouseArea::where($where_area)->first();
            if ($info) {
                $msg['code'] = 302;
                $msg['msg'] = '该库区已存在';
                return $msg;
            }

            $data['area'] = $area;
            $data['warm_id'] = $warm_id;
            $data['warm_name'] = WmsWarm::where('self_id','=',$warm_id)->value('warm_name');


            $wheres['self_id'] = $self_id;
            $select_WmsWarehouseArea=['self_id','area','warehouse_id','warehouse_name','group_code','group_name','create_user_id','create_user_name','warm_id','warm_name'];
            $old_info=WmsWarehouseArea::where($wheres)->select($select_WmsWarehouseArea)->first();

            if($old_info){
				//dd(1111);
                $data['update_time']=$now_time;
                $id=WmsWarehouseArea::where($wheres)->update($data);

                $operationing->access_cause='修改库区';
                $operationing->operation_type='update';


            }else{

                $data['self_id']=generate_id('area_');		//优惠券表ID
                $data['warehouse_id'] = $warehouse_id;
                $data['warehouse_name'] = $info2->warehouse_name;
                $data['group_code'] = $info2->group_code;
                $data['group_name'] = $info2->group_name;
                $data['create_user_id']=$user_info->admin_id;
                $data['create_user_name']=$user_info->name;
                $data['create_time']=$data['update_time']=$now_time;
                $id=WmsWarehouseArea::insert($data);
                $operationing->access_cause='新建库区';
                $operationing->operation_type='create';

            }

			$operationing->table_id=$old_info?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;

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
            //前端用户验证没有通过
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



    /***    库位禁用/启用      /wms/area/areaUseFlag
     */
    public function areaUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warehouse_area';
        $medol_name='wmsWarehouseArea';
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

    /***    新建库区数据提交      /wms/area/areaDelFlag
     */
    public function areaDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warehouse_area';
        $medol_name='wmsWarehouseArea';
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

    /***    拿去库区数据     /wms/area/getArea
     */
    public function  getArea(Request $request){
        /** 接收数据*/
        $warehouse_id        =$request->input('warehouse_id');

        /*** 虚拟数据**/
        //$warehouse_id='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['warehouse_id','=',$warehouse_id],
        ];
        $select=['self_id','area','group_code','group_name','warehouse_id','warehouse_name','warm_id','warm_name'];
        $data['info']=WmsWarehouseArea::where($where)->select($select)->get();
		foreach ($data['info'] as $k=>$v) {
            $v->warm_name=warm($v->wmsWarm->warm_name,$v->wmsWarm->min_warm,$v->wmsWarm->max_warm);
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    库区导入     /wms/area/import
     */
    public function import(Request $request){
        $table_name         ='wms_warehouse_area';
        $now_time           = date('Y-m-d H:i:s', time());

        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建库区';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';

        $user_info          = $request->get('user_info');//接收中间件产生的参数


        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $warm_id            =$request->input('warm_id');
        $file_id            =$request->input('file_id');
	//dd($input);
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/2020-10-13/库区导入文件范本.xlsx";
        $input['warm_id']       =$warm_id='warm_202012171029290396683997';
         ***/
        $rules = [
            'warm_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'warm_id.required' => '请选择温区',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {

            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
            if (!file_exists($importurl)) {
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }

            $res = Excel::toArray((new Import),$importurl);
            //dump($res);
            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }

            //dump($info_check);

            /**  定义一个数组，需要的数据和必须填写的项目
             键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */
            $shuzu=[
               '库区' =>['Y','N','64','area'],
                ];
            $ret=arr_check($shuzu,$info_check);

            //dump($ret);
            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }

            $info_wait=$ret['new_array'];


            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$warm_id],
            ];
            $select_WmsWarm=['self_id','warm_name','warehouse_id','warehouse_name','group_code','group_name'];
            $info= WmsWarm::where($where_check)->select($select_WmsWarm)->first();
            if(empty($info)){
                $msg['code'] = 305;
                $msg['msg'] = '温区不存在';
                return $msg;
            }
            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            //dump($info_wait);
            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where=[
                    ['delete_flag','=','Y'],
                    ['area','=',$v['area']],
                    ['warehouse_id','=',$info->warehouse_id],
                ];

                $area_info = WmsWarehouseArea::where($where)->value('group_code');

                if($area_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行库区已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('area_');
                    $list['area']               = $v['area'];
                    $list['warehouse_id']       = $info->warehouse_id;
                    $list['warehouse_name']     = $info->warehouse_name;
                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     = $user_info->admin_id;
                    $list['create_user_name']   = $user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['warm_id']            = $info->self_id;
                    $list['warm_name']          = $info->warm_name;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }

                $a++;
            }


            $operationing->new_info=$datalist;

            //dump($operationing);

           // dd($datalist);

            if($cando == 'N'){
                $msg['code'] = 306;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);
            $id= WmsWarehouseArea::insert($datalist);




            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=307;
                $msg['msg']='操作失败';
                return $msg;
            }
        }else{
            $erro = $validator->errors()->all();
            $msg['msg'] = null;
            foreach ($erro as $k => $v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code'] = 300;
            return $msg;
        }


    }

    /***    库区详情     /wms/area/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='wms_warehouse_area';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'area','warehouse_name','warm_name'];
        //$self_id='group_202009282038310201863384';
        $info=$details->details($self_id,$table_name,$select);

        if($info){

            /** 如果需要对数据进行处理，请自行在下面对 $$info 进行处理工作*/


            $data['info']=$info;
            $log_flag='Y';
            $data['log_flag']=$log_flag;
            $log_num='10';
            $data['log_num']=$log_num;
            $data['log_data']=null;

            if($log_flag =='Y'){
                $data['log_data']=$details->change($self_id,$log_num);

            }


            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="没有查询到数据";
            return $msg;
        }

    }

}
?>
