<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use DB;
class WorkingMessage implements ShouldQueue{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	 protected $workingMessage;

    /**
     * Create a new job instance.
     * @return void
     */
	//public function __construct($workingMessage,$delay)php artisan queue:work --queue=WorkingMessage
    public function __construct($workingMessage,$delay){
        $this->workingMessage = $workingMessage;
        // 设置延迟的时间，delay() 方法的参数代表多少秒之后执行,使用属性定义 
        $this->delay($delay);
    }

    /**
     * Execute the job. 
     *  当队列处理器从队列中取出任务时，会调用 handle() 方法
     * @return void
     */
    public function handle(){
		
        // 判断对应的订单是否已经被支付
        // 如果已经支付则不需要关闭订单，直接退出 
        if ($this->workingMessage['push_status'] == 'Y') {
            return;
        }
		
		$data['push_status']='Y';
		$data['update_time']=date('Y-m-d H:i:s',time());
		
		$where['push_type']=$this->workingMessage['push_type'];
		DB::table('school_working_message')->where($where)->update($data);
    }
}