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
        // �����ӳٵ�ʱ�䣬delay() �����Ĳ������������֮��ִ��,ʹ�����Զ��� 
        $this->delay($delay);
    }

    /**
     * Execute the job. 
     *  �����д������Ӷ�����ȡ������ʱ������� handle() ����
     * @return void
     */
    public function handle(){
		
        // �ж϶�Ӧ�Ķ����Ƿ��Ѿ���֧��
        // ����Ѿ�֧������Ҫ�رն�����ֱ���˳� 
        if ($this->workingMessage['push_status'] == 'Y') {
            return;
        }
		
		$data['push_status']='Y';
		$data['update_time']=date('Y-m-d H:i:s',time());
		
		$where['push_type']=$this->workingMessage['push_type'];
		DB::table('school_working_message')->where($where)->update($data);
    }
}