<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/7/29
 * Time: 15:42
 */
namespace App\Http\Api\Timer;
use App\Http\Controllers\Controller;
use App\Models\School\SchoolPath;
use App\Http\Controllers\RedisController as RedisServer;
use App\Http\Api\School\TempDataController as TempData;
use App\Http\Controllers\PushController as Push;
//use Illuminate\Support\Facades\Redis;
//use Illuminate\Support\Facades\Log;
//use DB;
class TickController extends Controller{
	protected $prefix='car_';
	
    public function timerTick(TempData $tempData,Push $push,RedisServer $redisServer){
		$time=time()-8000;
		$date=date('Y-m-d',$time);
		$star_time=date('H:i:s',$time-60*60);
		$end_time=date('H:i:s',$time);
		
		$where['delete_flag']='Y';
		$schoolPath=SchoolPath::where($where)
			->whereTime('over_time','>',$star_time)
			->whereTime('over_time','<',$end_time)
			->select('self_id','path_name','site_type','come_time','over_time')
			->get();
		if($schoolPath && $schoolPath->count()>0){
			foreach ($schoolPath as $k=>$v){
				//获取缓存中线路数据
				$carriageId=$this->prefix.$v->self_id.$v->site_type.$date;
				$jsonRedis=$redisServer->get($carriageId,'carriage');
				if($jsonRedis){
					$carriage_info=json_decode($jsonRedis); 
					//判断线路状态是否发车
					if($carriage_info->carriage_status == 2){
						// $tempData->sendCartData($push,$carriage_info,'abnormal');
						
						dump($carriage_info);
						
						
					}
				}
			}
		}
       
	  
	   dump($star_time);
	    dump($end_time);
        dd(11);  
		
    }
}