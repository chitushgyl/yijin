<?php
namespace App\Http\Admin\School;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\School\SchoolCost;
use App\Models\Group\SystemGroup;

class CostController  extends CommonController{
 /***    收费头部      /school/cost/costList
 *      前端传递必须参数：
 *      前端传递非必须参数：
 */
    public function  costList(Request $request){
        $data['page_info']      =config('page.listrows');
        $data['button_info']    =$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;

        //dd($msg);
        return $msg;
    }
    /***    收费分页      /school/cost/costPage
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
	public function costPage(Request $request){
        $cost_type          =config('school.cost_type');
        $cost_type          =array_column($cost_type,'name','key');

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

        $select=['self_id','group_name','use_flag','create_user_name','create_time','cost_cycle','cost_number','cost_way','refund_flag','refund_money','default_flag'];


        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SchoolCost::where($where)->count(); //总的数据量
                $data['items']=SchoolCost::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SchoolCost::where($where)->count(); //总的数据量
                $data['items']=SchoolCost::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();

                break;

            case 'more':
                $data['total']=SchoolCost::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SchoolCost::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
                    ->select($select)->get();
                break;
        }


//        dd($data['items']);
        foreach ($data['items'] as $k => $v){

            $v->cost_number=number_format($v->cost_number/100,2);
            $v->refund_money=number_format($v->refund_money/100,2);

            $v->cost_cycle=$cost_type[$v->cost_cycle]??null;

            $v->button_info = $button_info;
        }

        //dd($data['items']->toArray());

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;


	}


    /***    收费拉取数据      /school/cost/createCost
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function  createCost(Request $request){
        $data['business_type']  =config('school.cost_type');
        /** 接收数据*/
        $self_id=$request->input('self_id');

        $where=[
            ['self_id','=',$self_id],
            ['delete_flag','=','Y'],
        ];

        $data['cost_info']=SchoolCost::where($where)->select('self_id','group_name','group_code','use_flag','create_user_name','create_time','cost_cycle',
            'cost_number','cost_way','refund_flag','refund_money','default_flag')->first();
        if($data['cost_info']){
            $data['cost_info']->cost_number=$data['cost_info']->cost_number/100;
            $data['cost_info']->refund_money=$data['cost_info']->refund_money/100;
        }

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;

    }


    /***    基础添加入库      /school/cost/addCost
     *      前端传递必须参数：1
     *      前端传递非必须参数：
     */
    public function addCost(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $table_name         ='school_basics';
        $now_time           =date('Y-m-d H:i:s',time());

        $operationing->access_cause     ='新建/修改收费';
        $operationing->operation_type   ='create';
        $operationing->table            =$table_name;
        $operationing->now_time         =$now_time;

        $user_info                      = $request->get('user_info');//接收中间件产生的参数
        $input                          =$request->all();

        /** 接收数据*/
        $self_id                    =$request->input('self_id');
        $group_code                 =$request->input('group_code');
        $cost_cycle                 =$request->input('cost_cycle');
        $cost_number                =$request->input('cost_number');
        $cost_way                   =$request->input('cost_way');
        $refund_flag                =$request->input('refund_flag');
        $refund_money               =$request->input('refund_money');
        $default_flag               =$request->input('default_flag');


        /*** 虚拟数据*/
        //$input['group_code']    =$group_code    ='1234';
        //$input['cost_cycle']    =$cost_cycle   ='month';
        //$input['cost_number']   =$cost_number   ='1212';
        //$input['cost_way']      =$cost_way      ='front';
        //$input['refund_flag']   =$refund_flag   ='Y';
        //$input['refund_money']  =$refund_money  ='457';
        //$input['default_flag']  =$default_flag  ='Y';

        //dump($input);
        $rules=[
            'group_code'=>'required',
        ];
        $message=[
            'group_code.required'=>'必须选择一个公司',
        ];

        $validator=Validator::make($input,$rules,$message);

        if($validator->passes()){
            //dd($input);
            /** 把数据先做好***/
            $data['cost_cycle']=$cost_cycle;
            $data['cost_number']=$cost_number*100;
            $data['cost_way']=$cost_way;
            $data['refund_flag']=$refund_flag;
            $data['refund_money']=$refund_money*100;
            $data['default_flag']=$default_flag;

            $datt1['self_id']=$self_id;
            $datt1['delete_flag']='Y';

            $old_info=SchoolCost::where($datt1)->first();

            if($default_flag == 'Y'){
                //如果是默认收费，那么就把其他的默认收费全部修改成非默认
                $datt122['group_code']      =$group_code;
                $datt122['default_flag']    ='Y';
                $datt122['delete_flag']     ='Y';
                $datt12221212121['default_flag']    ='N';
                $datt12221212121['update_time']     =$now_time;
                SchoolCost::where($datt122)->update($datt12221212121);
            }

            if($old_info){
                $operationing->access_cause     ='修改收费信息';
                $operationing->operation_type   ='update';

                $data['update_time']=$now_time;
                $id=SchoolCost::where($datt1)->update($data);

            }else{
                $operationing->access_cause     ='新建收费信息';
                $operationing->operation_type   ='create';

                //通过group_code去拿取数据去
                $etheiu['group_code']=$group_code;
                $group_name=SystemGroup::where($etheiu)->value('group_name');

                $data['self_id']            =generate_id('cost_');
                $data['create_user_id']     = $user_info->admin_id;
                $data['create_user_name']   = $user_info->name;
                $data['create_time']        =$data['update_time']=$now_time;
                $data['group_code']         = $group_code;
                $data['group_name']         = $group_name;

                $id=SchoolCost::insert($data);

            }

            $operationing->table_id=$old_info?$old_info->self_id:$data['self_id'];
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

}
?>
