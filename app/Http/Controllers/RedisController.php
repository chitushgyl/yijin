<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

// use Illuminate\Support\Facades\Schema;

class RedisController extends Controller{
    /**单变量传递写入redis*/
    public function setex($user_token,$self_id,$table,$time){
		//dump($user_token);dump($self_id);dump($table);dd($time);
        $token_redis = Redis::connection($table);
        $token_redis->setex($user_token,$time,$self_id);
    }


    public function get($id,$table){
        $redis = Redis::connection($table);
        $info=$redis->get($id);
        return $info;
    }


    public function set($key,$value,$table){
        if (empty($key) || empty($value)) {
            return false;
        }
		//dump($key);dump($value);dd($table);
        // 如果传入的是数组，那么就编码下
        $value = is_array($value) ? json_encode($value,JSON_UNESCAPED_UNICODE):$value;
        $redis = Redis::connection($table);
        // 不设置过期时间
        return $redis->set($key, $value);
    }

    /**
     * 删除指定缓存
     * @param string $key 缓存标识
     * @return int 返回删除个数
     * */
    public function del($key,$table)
    {
        if (empty($key)) {
            return false;
        }
        $redis = Redis::connection($table);
        return $redis->del($key);
    }


    /**
     * 添加集合
     * @param $key
     * @param $value
     * @param $table
     * @return bool|int
     */
    public function sadd($key,$value,$table)
    {
        if (empty($key) || empty($value)) {
            return false;
        }
        $redis = Redis::connection($table);
        return $redis->sadd($key,$value);
    }

    /**
     * 查询集合的个数
     * @param $key
     * @param $table
     * @return bool|int
     */
    public function scard($key,$table)
    {
        if (empty($key)) {
            return false;
        }
        $redis = Redis::connection($table);
        return $redis->scard($key);
    }

    /**
     * 查询是否在集合中
     * @param $key
     * @param $value
     * @param $table
     * @return bool|int
     */
    public function sismember($key,$value,$table){
        if (empty($key) || empty($value)) {
            return false;
        }
        $redis = Redis::connection($table);
        return $redis->sismember($key,$value);
    }

    /**
     * 获取集合中所有的元素
     * @param $key
     * @param $table
     * @return array|bool
     */
    public function smembers($key,$table){
        if (empty($key)) {
            return false;
        }
        $redis = Redis::connection($table);
        return $redis->smembers($key);
    }

}
?>
