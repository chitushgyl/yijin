<?php
//需要在app.php中aliases中'MacAddress' => App\Services\MacAddress::class,
namespace App\Services;
use App\Models\Group\SystemGroupShare;
class School{
    /**
     * 图片转化
     * @param $group_code
     * @param $group_name
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function defaultShare($group_code,$group_name){
        $where['group_code']=$group_code;
        $where['delete_flag']='Y';
        $small=SystemGroupShare::where($where)->first();
        $default_share=config('aliyun.group');
        if($small){
            foreach ($default_share as $k => $v){
                if($small->$k){
                    if(strpos($k, 'img') !== false){
                        $default_share[$k]=img_for($small->$k,'one');
                    }else{
                        $default_share[$k]=$small->$k;
                    }
                }
            }
        }
        $default_share['group_name']=$group_name;
        return  $default_share;
    }
}
