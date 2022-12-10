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
use App\Models\Wms\WmsWarehouseSign;
use App\Models\Wms\WmsWarehouseArea;
class SignController extends CommonController{
    /***    库位列表      /wms/sign/signList
     */
    public function  signList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');
        $abc='库位';
        $data['import_info']    =[
            'import_text'=>'下载'.$abc.'导入示例文件',
            'import_color'=>'#FC5854',
            'import_url'=>config('aliyun.oss.url').'execl/2020-07-02/库位导入文件范本.xlsx',
        ];
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    库位分页      /wms/sign/signPage
     */
    public function signPage(Request $request){
        /** 接收中间件参数**/
        $group_info     = $request->get('group_info');//接收中间件产生的参数
        $button_info    = $request->get('anniu');//接收中间件产生的参数1111

        /**接收数据*/
        $num            =$request->input('num')??10;
        $page           =$request->input('page')??1;
        $use_flag       =$request->input('use_flag');
        $group_code     =$request->input('group_code');
        $area           =$request->input('area');
        $warehouse_id   =$request->input('warehouse_id');
        $listrows       =$num;
        $firstrow       =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
            ['type'=>'=','name'=>'group_code','value'=>$group_code],
            ['type'=>'like','name'=>'area','value'=>$area],
            ['type'=>'=','name'=>'warehouse_id','value'=>$warehouse_id],
        ];

        $where=get_list_where($search);

        $select=['self_id','warehouse_name','group_name','area','row','column','tier','use_flag'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=WmsWarehouseSign::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouseSign::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=WmsWarehouseSign::where($where)->count(); //总的数据量
                $data['items']=WmsWarehouseSign::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=WmsWarehouseSign::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=WmsWarehouseSign::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('self_id','desc')->orderBy('update_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }


        foreach ($data['items'] as $k=>$v) {
            $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
            $v->button_info=$button_info;

        }

       //dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;



    }

    /***    创建库区      /wms/sign/createSign
     */
    public function createSign(Request $request){





        dd(1211);

    }


    /***    创建库区      /wms/sign/addSign
     */
    public function addSign(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ='wms_warehouse_sign';

        $operationing->access_cause     ='创建/修改库区';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input              =$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $area_id            =$request->input('area_id');
        $row_left            =$request->input('row_left');
        $row_right           =$request->input('row_right');
        $tier_left            =$request->input('tier_left');
        $tier_right           =$request->input('tier_right');
        $column_left            =$request->input('column_left');
        $column_right           =$request->input('column_right');

        /*** 虚拟数据
//        $input['self_id']           =$self_id='good_202007011336328472133661';
//        $input['area_id']           =$area_id='area_202011151042442192785461';
        $input['row_left']              =$row_left='1';
        $input['row_right']              =$row_right='1';
        $input['tier_left']              =$tier_left='1';
        $input['tier_right']              =$tier_right='4';
        $input['column_left']              =$column_left='1';
        $input['column_right']              =$column_right='12';
* **/

//		DD($input);
        $rules=[
            'area_id'=>'required',
        ];
        $message=[
            'area_id.required'=>'请选择所属库区',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()) {
            /** 第二步效验，left的值必须小于right 的 值*/
            if($row_left > $row_right){
                $msg['code']=301;
                $msg['msg']='库位排位1必须小于或等于库位排位2';
                return $msg;
            }

            if($tier_left > $tier_right){
                $msg['code']=302;
                $msg['msg']='库位层1必须小于或等于库位层2';
                return $msg;
            }

            if($column_left > $column_right){
                $msg['code']=303;
                $msg['msg']='库位列位1必须小于或等于库位列位2';
                return $msg;
            }





            //现在要根据这个做笛卡尔积,先得到几个数组
            $row        =$this->squares($row_left,$row_right);
            $tier       =$this->squares($tier_left,$tier_right);
            $column     =$this->squares($column_left,$column_right);

            //初始化一些变量
            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数

            foreach ($row as $k => $v){
				foreach ($column as $kk => $vv){
					foreach ($tier as $kkk=>$vvv){
                        $sign['row']=$v;
                        $sign['column']=$vv;
                        $sign['tier']=$vvv;
                        $datalist[]=$sign;
					}
				}
            }
            $select_WmsWarehouseArea=['area','warehouse_id','warehouse_name','group_code','group_name','warm_id'];
            $info=WmsWarehouseArea::where('self_id','=',$area_id)->select($select_WmsWarehouseArea)->first();


            foreach ($datalist as $k => $v){
                $where=[
                    ['row','=',$v['row']],
                    ['tier','=',$v['tier']],
                    ['column','=',$v['column']],
                    ['delete_flag','=','Y'],
                    ['area_id','=',$area_id],
                ];

                $exists=WmsWarehouseSign::where($where)->exists();
                if($exists){
                    $cando='N';
                    if($abcd<$errorNum){
                        $strs.='第'.$v['row'].'排，第'.$v['column'].'列，第'.$v['tier'].'层的'.'库位已存在'.'</br>';
                        $abcd++;
                    }

                   // dump($v);
                    //dd($strs);
                    //break;
                }

                $datalist[$k]['self_id']				=generate_id('sign_');
                $datalist[$k]['area_id']				=$area_id;
                $datalist[$k]['area']					=$info->area;
                $datalist[$k]['warehouse_id']			=$info->warehouse_id;
                $datalist[$k]['warehouse_name']			=$info->warehouse_name;
                $datalist[$k]['group_code']				=$info->group_code;
                $datalist[$k]['group_name']				=$info->group_name;
                $datalist[$k]['warm_id']				=$info->warm_id;
                $datalist[$k]['create_user_id']     	=$user_info->admin_id;
                $datalist[$k]['create_user_name']   	=$user_info->name;
                $datalist[$k]['create_time']			=$datalist[$k]['update_time']=$now_time;



            }
            if($cando=='Y'){
                $new_list = array_chunk($datalist,1000);
//                dd($new_list);
                foreach ($new_list as $value){
                    $id=WmsWarehouseSign::insert($value);
                }
                if($id){
                    $msg['code'] = 200;
                    $msg['msg'] = "操作成功,创建了".count($datalist).'个库位';
                    //dd($msg);
                    return $msg;
                }else{
                    $msg['code'] = 302;
                    $msg['msg'] = "操作失败";
                    return $msg;
                }

            }else{
                $msg['code'] = 302;
                $msg['msg'] = $strs;
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

    /**XXX**/
    public function squares($start,$stop) {
        for ($i=$start;$i<=$stop;$i++) {
            $d[]=$i;
        }
        return $d;
    }



    /***    库位禁用      /wms/sign/signUseFlag
     */
    public function signUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warehouse_sign';
        $medol_name='wmsWarehouseSign';
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

    /***    库位删除      /wms/sign/signDelFlag
     */
    public function signDelFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='wms_warehouse_sign';
        $medol_name='wmsWarehouseSign';
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

    /***    库位导入     /wms/sign/import
     */

    public function import(Request $request){
        $user_info          = $request->get('user_info');//接收中间件产生的参数
        $now_time           = date('Y-m-d H:i:s', time());
        $table_name         ='wms_warehouse_sign';
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $operationing->access_cause     ='导入创建库位';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        $operationing->type             ='import';
        /** 接收数据*/
        $input              =$request->all();
        $importurl          =$request->input('importurl');
        $area_id            =$request->input('area_id');
        $file_id            =$request->input('file_id');
        /****虚拟数据
        $input['importurl']     =$importurl="uploads/2020-10-13/库位导入文件范本.xlsx";
        $input['area_id']       =$area_id='area_202011191023492569805606';
         ***/
        $rules = [
            'area_id' => 'required',
            'importurl' => 'required',
        ];
        $message = [
            'area_id.required' => '请选择区域',
            'importurl.required' => '请上传文件',
        ];
        $validator = Validator::make($input, $rules, $message);
        if ($validator->passes()) {
            /**发起二次效验，1效验文件是不是存在， 2效验文件中是不是有数据 3,本身数据是不是重复！！！* */
            if(!file_exists($importurl)){
                $msg['code'] = 301;
                $msg['msg'] = '文件不存在';
                return $msg;
            }

            $res = Excel::toArray((new Import),$importurl);

            $info_check=[];
            if(array_key_exists('0', $res)){
                $info_check=$res[0];
            }
            /**  定义一个数组，需要的数据和必须填写的项目
            键 是EXECL顶部文字，
             * 第一个位置是不是必填项目    Y为必填，N为不必须，
             * 第二个位置是不是允许重复，  Y为允许重复，N为不允许重复
             * 第三个位置为长度判断
             * 第四个位置为数据库的对应字段
             */

            $shuzu=[
                '排' =>['Y','Y','10','row'],
                '列' =>['Y','Y','10','column'],
                '楼层' =>['Y','Y','10','tier'],
            ];
            $ret=arr_check($shuzu,$info_check);

            if($ret['cando'] == 'N'){
                $msg['code'] = 304;
                $msg['msg'] = $ret['msg'];
                return $msg;
            }
            $info_wait=$ret['new_array'];

            $signs=[];
            foreach ($info_wait as $k => $v){
                $signs[][]=$v['row'].$v['column'].$v['tier'];
            }



            $where_check=[
                ['delete_flag','=','Y'],
                ['self_id','=',$area_id],
            ];
            $select_WmsWarehouseArea=['self_id','area','group_name','group_code','warehouse_name','warehouse_id','warm_id'];
            $info = WmsWarehouseArea::where($where_check)->select($select_WmsWarehouseArea)->first();
           // DUMP($area_info);
            if(empty($info)){
                $msg['code'] = 302;
                $msg['msg'] = '库区不存在';
                return $msg;
            }


            /** 二次效验结束**/

            $datalist=[];       //初始化数组为空
            $cando='Y';         //错误数据的标记
            $strs='';           //错误提示的信息拼接  当有错误信息的时候，将$cando设定为N，就是不允许执行数据库操作
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $a=2;

            /** 现在开始处理$car***/
            foreach($info_wait as $k => $v){
                $where['delete_flag'] = 'Y';
                $where['row']=$v['row'];
                $where['column']=$v['column'];
                $where['tier']=$v['tier'];

                $where['area_id']=$area_id;
                $good_info = WmsWarehouseSign::where($where)->value('self_id');

                if($good_info){
                    if($abcd<$errorNum){
                        $strs .= '数据中的第'.$a."行库区编号已存在".'</br>';
                        $cando='N';
                        $abcd++;
                    }
                }

                $list=[];
                if($cando =='Y'){
                    $list['self_id']            =generate_id('sign_');
                    $list['row']                = $v['row'];
                    $list['column']             = $v['column'];
                    $list['tier']               = $v['tier'];
                    $list['warehouse_id']       = $info->warehouse_id;
                    $list['warehouse_name']     = $info->warehouse_name;
                    $list['area_id']            = $info->self_id;
                    $list['warm_id']            = $info->warm_id;
                    $list['area']               = $info->area;
                    $list['group_code']         = $info->group_code;
                    $list['group_name']         = $info->group_name;
                    $list['create_user_id']     =$user_info->admin_id;
                    $list['create_user_name']   =$user_info->name;
                    $list['create_time']        =$list['update_time']=$now_time;
                    $list['file_id']            =$file_id;
                    $datalist[]=$list;
                }


                $a++;
            }

            $operationing->new_info=$datalist;
            if($cando == 'N'){
                $msg['code'] = 305;
                $msg['msg'] = $strs;
                return $msg;
            }
            $count=count($datalist);


            //dd($datalist);
            $id= WmsWarehouseSign::insert($datalist);

            if($id){
                $msg['code']=200;
                /** 告诉用户，你一共导入了多少条数据，其中比如插入了多少条，修改了多少条！！！*/
                $msg['msg']='操作成功，您一共导入'.$count.'条数据';

                return $msg;
            }else{
                $msg['code']=301;
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

    /***    根据仓库ID拉取库位创建库区      /wms/sign/getSign
     */
    public function getSign(Request $request){
        /** 接收数据*/
        $warehouse_id        =$request->input('warehouse_id');

        /*** 虚拟数据**/
        //$warehouse_id='ware_202006012159456407842832';

        $where=[
            ['delete_flag','=','Y'],
            ['use_flag','=','Y'],
            ['warehouse_id','=',$warehouse_id],
        ];

        //dd($where);
        $data['info']=WmsWarehouseSign::where($where)->select('self_id','area_id','area','row','column','tier')->get();
        foreach ($data['info'] as $k=>$v) {
            $v->sign=$v->area.'-'.$v->row.'-'.$v->column.'-'.$v->tier;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;


    }


    /***    库位详情     /wms/sign/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='wms_warehouse_sign';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'area','row','column','tier','warehouse_name'];
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
