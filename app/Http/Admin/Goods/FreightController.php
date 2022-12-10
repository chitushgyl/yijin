<?php
namespace App\Http\Admin\Goods;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController as Status;
use App\Models\Shop\ShopFreight;
use App\Models\SysAddressAll;
use App\Http\Controllers\DetailsController as Details;

class FreightController extends CommonController{
    /***    运费信息头部      /goods/freight/freightList
     */
    public function  freightList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }

    /***    运费信息分页      /goods/freight/freightPage
     */
    public function freightPage(Request $request){
        /** 接收中间件参数**/
        $user_info = $request->get('user_info');//接收中间件产生的参数
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数
//dd($user_info);
        //如果没有数据则把所有的数据添加进去呗，检测自己本身是不是
        $check_where=[
            ['delete_flag','=','Y'],
            ['group_code','=',$user_info->group_code],
        ];

        $inffw=ShopFreight::where($check_where)->value('self_id');
        if(empty($inffw)){
            $now_time       =date('Y-m-d H:i:s',time());
            //执行把数据丢进去
            //将运费的东西给到这个商户
            $whereAddressAll=[
                ['delete_flag','=','Y'],
                ['code_parent','=','0'],
            ];
            $select_address=['self_id','code_name','code_parent','type_flag'];
            $info=SysAddressAll::where($whereAddressAll)->select($select_address)->get();
            $list=[];
            foreach ($info as $k=>$v) {
                $freight_data['self_id']            =generate_id('freight_');
                $freight_data['freight_type']       =$v->type_flag;
                $freight_data['code_id']            =$v->self_id;
                $freight_data['code_name']          =$v->code_name;
                $freight_data['postage_flag']       ='Y';
                $freight_data['use_flag']           ='Y';
                $freight_data['freight']            =0;
                $freight_data['free']               =0;
                $freight_data['create_user_id']     =$user_info->admin_id;
                $freight_data["create_user_name"]   =$user_info->name;
                $freight_data['create_time']        =$freight_data['update_time']=$now_time;
                $freight_data['group_code']         =$user_info->group_code;
                $freight_data['group_name']         =$user_info->group_name;

                $list[]=$freight_data;
            }

            ShopFreight::insert($list);
        }

        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;
        $use_flag   =$request->input('use_flag');

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'all','name'=>'use_flag','value'=>$use_flag],
        ];

        $where=get_list_where($search);
        $select=['self_id','code_name','free','freight','postage_flag','use_flag','freight','group_name'];

        $user_track_where2=[
            ['delete_flag','=','Y'],
        ];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=ShopFreight::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ShopFreight::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=ShopFreight::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->count(); //总的数据量
                $data['items']=ShopFreight::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='N';
                break;

            case 'more':
                $data['total']=ShopFreight::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=ShopFreight::wherehas('systemGroup',function($query)use($user_track_where2){
                    $query->where($user_track_where2);
                })->where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                $data['group_show']='Y';
                break;
        }

        foreach ($data['items'] as $k=>$v) {
            /** 处理下运费的关系*/
            $v->show=freight_do($v);

            $v->button_info=$button_info;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
         //dd($data['items']->toArray());
        return $msg;
    }


    /***    运费插入数据库      /goods/freight/addFreight
     */

    public function addFreight(Request $request){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
        $table_name     ='shop_freight';
        $now_time       =date('Y-m-d H:i:s',time());

        $operationing->access_cause='修改运费';
        $operationing->operation_type='update';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;


        //$user_info = $request->get('user_info');//接收中间件产生的参数
        /** 接收数据*/
        $self_id        =$request->input('self_id');
        $postage_flag   =$request->input('postage_flag');
        $freight        =$request->input('freight');
        $free           =$request->input('free');
        $use_flag       =$request->input('use_flag');

        $input          =$request->all();

        /**虚拟数据
        $self_id        ='freight_202009241412250875644471';
        $postage_flag   ='Y';
        $freight        ='124';
        $free           ='12';
        $use_flag       ='Y';
         ***/


        $rules=[
            'self_id'=>'required',
        ];
        $message=[
            'self_id.required'=>'运费ID必须有',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){

            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];

            $old_info=ShopFreight::where($where)->first();

            /**开始做数据**/
            $data['postage_flag']       =$postage_flag;
            $data['freight']            =intval($freight*100);
            $data['free']               =intval($free*100);
            $data['use_flag']           =$use_flag;
            $data['update_time']        =$now_time;
//dump($data);
            $id=ShopFreight::where($where)->update($data);

            $operationing->table_id     =$self_id;
            $operationing->old_info     =$old_info;
            $operationing->new_info     =$data;


            if($id){

                $show=freight_do((object)$data);

               // dd($show);
                $msg['code']=200;
                $msg['msg']='修改成功';
                $msg['data']=$show;
                return $msg;
            }else{
                $msg['code']=301;
                $msg['msg']='修改失败';
                return $msg;
            }

        }else{
            $erro=$validator->errors()->all();
            $msg['msg']=null;
            foreach ($erro as $k=>$v) {
                $kk=$k+1;
                $msg['msg'].=$kk.'：'.$v.'</br>';
            }
            $msg['code']=300;
            return $msg;
        }

    }

    /***    运费启禁用      /goods/freight/freightUseFlag
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function freightUseFlag(Request $request,Status $status){
        $now_time=date('Y-m-d H:i:s',time());
        $operationing = $request->get('operationing');//接收中间件产生的参数
        $table_name='shop_freight';
        $medol_name='ShopFreight';
        $self_id=$request->input('self_id');
        $flag='useFlag';
        //$self_id='freight_202009241412250875644471';

        $status_info=$status->changeFlag($table_name,$medol_name,$self_id,$flag,$now_time);

        $operationing->access_cause='启用/禁用';
        $operationing->table=$table_name;
        $operationing->table_id=$self_id;
        $operationing->now_time=$now_time;
        $operationing->old_info=$status_info['old_info'];
        $operationing->new_info=$status_info['new_info'];
        $operationing->operation_type=$flag;

        if($status_info['code'] == 200){
            $where=[
                ['delete_flag','=','Y'],
                ['self_id','=',$self_id],
            ];
            $select=['self_id','code_name','free','freight','postage_flag','use_flag','freight','group_name'];
            $old_info=ShopFreight::where($where)->select($select)->first();
            if($old_info){
                $show=freight_do($old_info);
                $msg['code']=$status_info['code'];
                $msg['msg']=$status_info['msg'];
                $msg['data']=$show;
                return $msg;
            }else{
                $msg['code']=300;
                $msg['msg']='没有查询到数据';
                return $msg;
            }

        }else{
            $msg['code']=$status_info['code'];
            $msg['msg']=$status_info['msg'];
            return $msg;
        }

    }

    /***    仓库详情     /goods/freight/details
     */
    public function  details(Request $request,Details $details){
        $self_id=$request->input('self_id');
        $table_name='shop_freight';
        $select=['self_id','group_code','group_name','use_flag','create_user_name','create_time',
            'freight_type','code_id','code_name','freight','free','postage_flag'];
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
