<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 11:11
 */
namespace App\Http\Middleware;
use Closure;
use App\Models\SystemMenuNew;
class Group{
    /**
     *  这个中间件主要任务是控制用户是否可以使用按钮和页面的权限，如果客户无权访问页面，则返回402不让客户进行操作
     *  附带的作用是创建一个操作历史的初始化
     */
    public function handle($request, Closure $next, $guard = null){

        $user_info = $request->get('user_info');//接收中间件产生的参数

        $cando='N';
        $paths=$request->path();

        //把LOAD的全部开放掉
        $domain = strpos($paths, 'load');
        if($domain !== false){
            $cando='Y';
        }

        if($cando=='N'){
            //先做一个公用的模块
            //$rule=get_rule($user_info->menu_id);

            $where_menu['delete_flag']='Y';
            if($user_info->menu_id== 'all'){
                $menu_infos=SystemMenuNew::where($where_menu)->select('check_url')->get();
            }else{
                $menu_id=explode('*', $user_info->menu_id);
                $menu_infos=SystemMenuNew::where($where_menu)->whereIn('id',$menu_id)->select('check_url')->get();
            }

            $qiaoinfo='index*zx*changePwd*indexs/index_v1';
            foreach ($menu_infos as $k => $v){
                if($v->check_url){
                    $qiaoinfo.='*'.$v->check_url;
                }
            }
            $rule=explode('*', $qiaoinfo);

            //$paths  有可能带参数，所以要取前3个
            $paths_infoss=explode('/', $paths);
            $paths_infossssss=[];
            foreach ($paths_infoss as $k => $v){
                if($k<3){
                    $paths_infossssss[]=$v;
                }
            }
            $paths_check = implode("/", $paths_infossssss);  //数组转字符串

            if(in_array($paths_check,$rule) ){
                $cando='Y';
            }
        }



//        dd($user_info);
        $cando='Y';
        if($cando=='Y'){
            //拿去页面的按钮权限
            $anniu=[];
            $anniu_where['url']=$request->path();
            $anniu_where['type']='BUT';
            $anniu_where['delete_flag']='Y';
            $select=['id','img','name','jump_url','use_type','color','but_type','flag','show_flag','an_name'];

            if($user_info->menu_id!='all'){
                if($user_info->menu_id){
                    $anniu_where['admin_flag']='N';
                    //如果有，则拆分数据
                    $anniu_id=explode('*', $user_info->menu_id);
    //                dd($anniu_id);
                    $anniu=SystemMenuNew::where($anniu_where)->whereIn('id',$anniu_id)->orderBy('sort', 'asc')->select($select)->get();
                }else{
                    $anniu=null;
                }

            }else{
                //如果是超级管理员，则所有的按钮全部拿到
                $anniu=SystemMenuNew::where($anniu_where)->orderBy('sort', 'asc')->select($select)->get();
                foreach ($anniu as $key =>$value){
                    if ($value->an_name){
                        $value->name = $value->an_name;
                    }
                }
            }


			//dd($user_info);


            //做一个日志的初始化参数，方便传递到后置中间件
            $operationing['access_cause']=null;
            $operationing['table']=null;
            $operationing['table_id']=null;
            $operationing['old_info']=null;
            $operationing['new_info']=null;
            $operationing['operation_type']=null;
            //dd($anniu);
            $mid_params = ['anniu'=>$anniu,'operationing'=>(object)$operationing];

            $request->attributes->add($mid_params);//(暂存数据)参数
            return $next($request);
        }else{
            $msg['code']=402;
            $msg['msg']="您没有操作权限";
            echo json_encode($msg);
            exit;

        }
    }
}
?>
