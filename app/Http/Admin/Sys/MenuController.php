<?php
namespace App\Http\Admin\Sys;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\StatusController;
use App\Models\Group\SystemGroupAuthority;
use App\Models\Group\SystemGroup;


class MenuController  extends CommonController{

    /***    默认权限     /sys/menu/menuList
     */
    public function  menuList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    默认权限分页      /sys/menu/menuPage
     */
    public function menuPage(Request $request){
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数
        $business_type  =config('page.business_type');
        $business_type  =array_column($business_type,'name','key');

        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;

        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],

        ];

        $where=get_list_where($search);
        $select=['self_id','create_user_name','create_time','group_name','business_type','cms_show','type','business_type'];

        switch ($group_info['group_id']){
            case 'all':
                $data['total']=SystemGroupAuthority::where($where)->count(); //总的数据量
                $data['items']=SystemGroupAuthority::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'one':
                $where[]=['group_code','=',$group_info['group_code']];
                $data['total']=SystemGroupAuthority::where($where)->count(); //总的数据量
                $data['items']=SystemGroupAuthority::where($where)
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;

            case 'more':
                $data['total']=SystemGroupAuthority::where($where)->whereIn('group_code',$group_info['group_code'])->count(); //总的数据量
                $data['items']=SystemGroupAuthority::where($where)->whereIn('group_code',$group_info['group_code'])
                    ->offset($firstrow)->limit($listrows)->orderBy('create_time', 'desc')
                    ->select($select)->get();
                break;
        }


        foreach($data['items'] as $k => $v){
            switch ($v->type){
                case 'system':
                    $v->type='公司权限';
                    break;
                case '':
                    $v->type='职务权限';
                    break;
                default:
                    $v->type='无用权限';
                    break;
            }
            $v->business_type_show=$business_type[$v->business_type];

            $v->button_info=$button_info;

        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        //dd($msg);
        return $msg;
    }

    /***    创建默认权限      /sys/menu/createMenu
     */
    public function createMenu(Request $request){



        dd(111);
    }

    /***    创建套餐入库      /sys/menu/addMenu
     */
    public function addMenu(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='system_group_authority';

        $operationing->access_cause='新建/修改商户套餐';
        $operationing->operation_type='create';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        $user_info = $request->get('user_info');//接收中间件产生的参数
        $input=$request->all();

        /** 接收数据*/
        $self_id            =$request->input('self_id');
        $group_code         =$request->input('group_code');
        $package_name       =$request->input('package_name');
        $menu_id            =$request->input('menu_id');
        $home_config_info   =$request->input('home_config_info');
        $price_info         =$request->input('price_info');


        /*** 虚拟数据**/
        //$input['self_id']=$self_id='group_202006192050422191941931';
        $input['group_code']=$group_code='1234';
        $input['package_name']=$package_name='至尊版';
        $input['menu_id']=$menu_id=['227','228','434','458','232','233'];
        $input['home_config_info']=$home_config_info=['search','lunbo','wulan','home_menu'];
        $input['price_info']=$price_info=[
            '0'=>[
               // 'self_id'=>'sku_202007011336328692892904',
                'package_price'=>'599.00',
                'measure'=>'year',
                'measure_number'=>'2',
            ],
            '1'=>[
               // 'self_id'=>'sku_202007011336328692892904',
                'package_price'=>'69.00',
                'measure'=>'month',
                'measure_number'=>'4',
            ],
        ];

        $rules=[
            'group_code'=>'required',
            'package_name'=>'required',
            'price_info'=>'required',
        ];
        $message=[
            'group_code.required'=>'请填写公司名称',
            'package_name.required'=>'请填写套餐名称',
            'price_info.required'=>'套餐价格必须有一条',
        ];

        $validator=Validator::make($input,$rules,$message);
        if($validator->passes()){
            //效验price_info的有效性
            $rulesssss=['package_price'=>'套餐价格','measure'=>'套餐周期','measure_number'=>'套餐时长'];

            $rule=array_keys($rulesssss);
            $rule_count=count($rule);
            $msg['msg']=null;
            $cando='Y';
            $abcs=1;

            foreach($price_info as $k => $v){
                $art222=array_keys($v);
                //取一个交集出来，然后比较长度
                $result=array_intersect($rule,$art222);
                $result_count=count($result);
                if($rule_count != $result_count){
                    //说明缺少参数
                    $msg['code']=302;
                    $msg['msg']='模板数组缺少必要参数';
                    //dd($msg);
                    return $msg;
                }


                foreach($rulesssss as $kk => $vv){
                    if($v[$kk]){
                        if(in_array($kk,['package_price','measure_number'])){
                            if($v[$kk]<0){
                                $cando='N';
                                $msg['msg'].=$abcs.": ".$vv." 必须大于0\r\n";
                                $abcs++;
                            }
                        }
                    }else{
                        $cando='N';
                        $msg['msg'].=$abcs.": ".$vv." 缺失\r\n";
                        $abcs++;
                    }
                }
            }

            if($cando=='N'){
                $msg['code']=600;
                //dd($msg);
                return $msg;
            }

            //效验price_info的有效性  结束

            /** 开始制作数据了*/
            $data['package_name']=$package_name;
            $data['menu_id']=json_encode($menu_id);
            $data['home_config_info']=json_encode($home_config_info);

            $wheres2['self_id'] = $self_id;
            $wheres2['delete_flag'] = "Y";
            $old_info=SystemGroupAuthority::where($wheres2)->first();


            if($old_info){
                $operationing->access_cause='修改商户套餐';
                $operationing->operation_type='update';


                $data['update_time'] = $now_time;
                $id= SystemGroupAuthority::where($wheres2)->update($data);


            }else{
                $operationing->access_cause='新建商户套餐';
                $operationing->operation_type='create';

                $group_name=SystemGroup::where('group_code','=',$group_code)->value('group_name');

                //说明是新增
//                    dd($input);
                $data['group_code']=$group_code;
                $data['group_name']=$group_name;
                $data['create_user_id'] = $user_info->admin_id;
                $data['create_user_name'] = $user_info->name;
                $data['self_id'] = generate_id('sga_');
                $data['update_time'] = $data['create_time'] = $now_time;
                $id = SystemGroupAuthority::insert($data);

            }

            $operationing->table_id=$self_id?$self_id:$data['self_id'];
            $operationing->old_info=$old_info;
            $operationing->new_info=$data;



dump($data);
            dd($input);










        }else{
            //前端用户验证没有通过
            $erro=$validator->errors()->all();
            $msg['code']=301;
            $msg['msg']=null;
            foreach ($erro as $k => $v){
                $kk=$k+1;
                $msg['msg'].=$kk.":".$v."\r\n";
            }
            //dd($msg);
            return $msg;
        }
    }


}
?>
