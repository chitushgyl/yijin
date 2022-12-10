<?php
namespace App\Http\Admin\User;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Models\User\UserCapital;
use App\Models\User\UserWallet;

class ExtractController  extends CommonController{
    /***    提现信息头部      /user/extract/extractList
     */
    public function  extractList(Request $request){
        $data['page_info']=config('page.listrows');
        $data['button_info']=$request->get('anniu');

        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;
    }

    /***    提现信息分页     /user/extract/extractPage
     */
	public function extractPage(Request $request){
        /** 接收中间件参数**/
        $group_info = $request->get('group_info');//接收中间件产生的参数
        $button_info = $request->get('anniu');//接收中间件产生的参数
	//dd($button_info);
        /**接收数据*/
        $num        =$request->input('num')??10;
        $page       =$request->input('page')??1;
        $tel        =$request->input('tel');
        $wx         =$request->input('wx');

        $listrows   =$num;
        $firstrow   =($page-1)*$listrows;
        $search=[
            ['type'=>'=','name'=>'delete_flag','value'=>'Y'],
            ['type'=>'=','name'=>'produce_type','value'=>'EXTRACT'],
            ['type'=>'like','name'=>'tel','value'=>$tel],
           // ['type'=>'like','name'=>'token_name','value'=>$wx],
        ];

        $where=get_list_where($search);
        $select=['self_id','total_user_id','capital_type','money','produce_cause','wallet_status','create_time','bank_name','card_holder','card_number',
            'serial_bank_name','serial_number','serial_money','serial_rate'];


        $data['total']=UserWallet::where($where)->count(); //总的数据量
        //dd($data);
        $data['items']=UserWallet::with(['userReg' => function($query) {
            $query->select('total_user_id','token_img','token_name','tel','self_id');
            $query->where('delete_flag','=','Y');
        }])
        ->where($where)->offset($firstrow)->limit($listrows)->orderBy('create_time','desc')
        ->select($select)
            ->get();

        //($data['items']);
        //dd($data['items']->toArray());
        foreach($data['items'] as $k => $v){
            $v->money               =number_format($v->money/100,2);
            $v->serial_money        =number_format($v->serial_money/100,2);
            $v->serial_rate         =$v->serial_rate/100;

		    $v->wallet_status_text=null;
		switch ($v->wallet_status){
            case 'SU':
			//提现成功
			$v->wallet_status_text='提现成功';
                break;
            case 'FS':
                //提现失败
			$v->wallet_status_text='提现失败';	
                break;
            case 'WAIT':
                //等待中
			$v->button_info=$button_info;	
			$v->wallet_status_text='等待中';	
                break;
        }
        }
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
       // dd($button_info);
        return $msg;
	}

    /***    创建提现     /user/extract/createExtract
     */
    public function createExtract(Request $request){
        $self_id=$request->input('self_id');
        //$self_id='wallet_202011161333427108779989';

        $where=[
            ['self_id','=',$self_id],
            ['produce_type','=','EXTRACT'],
            ['delete_flag','=','Y'],
            ['wallet_status','=','WAIT'],
        ];

        $select=['self_id','total_user_id','capital_type','money','produce_cause','wallet_status','create_time','bank_name','card_holder','card_number',
            'serial_bank_name','serial_number','serial_money','serial_rate'];

        $data=UserWallet::with(['userReg' => function($query) {
            $query->select('total_user_id','token_img','token_name','tel','self_id');
            $query->where('delete_flag','=','Y');
        }]) ->where($where)
            ->select($select)
            ->first();






        if($data){
            $serial_rate =6;
            $bili=1-$serial_rate/100;
            $money = $data->money*$bili;
            //dump($money);

            $data->money_show       =number_format($data->money/100,2);
            $data->serial_money     =number_format($money/100,2);
            $data->serial_rate      =$serial_rate/100;
            //dd($data->toArray());

            $msg['code']=200;
            $msg['msg']="数据拉取成功";
            $msg['data']=$data;
            return $msg;
        }else{
            $msg['code']=300;
            $msg['msg']="未查询到数据";
            return $msg;
        }

    }



    /***    创建提现入库     /user/extract/addExtract
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function addExtract(Request $request){
        $operationing       = $request->get('operationing');//接收中间件产生的参数
        $now_time           =date('Y-m-d H:i:s',time());
        $table_name         ='user_wallet';

        $operationing->access_cause='处理提现';
        $operationing->operation_type='update';
        $operationing->table=$table_name;
        $operationing->now_time=$now_time;

        $user_info          = $request->get('user_info');                //接收中间件产生的参数
		//dd($request->all());
        /*** 虚拟数据**/
        $self_id            =$request->input('self_id');
        $serial_bank_name   =$request->input('serial_bank_name');
        $serial_number      =$request->input('serial_number');
        $serial_money       =$request->input('serial_money');
        $status             =$request->input('status');
        $serial_rate        =6;

        $where=[
            ['self_id','=',$self_id],
            ['produce_type','=','EXTRACT'],
            ['delete_flag','=','Y'],
            ['wallet_status','=','WAIT'],
        ];

        $info=UserWallet::where($where)->select('total_user_id','money')->first();
		
		//dump($info);
        if(empty($info)){
            $msg['code']=300;
            $msg['msg']="未查询到数据";
            return $msg;
        }

        $id=false;
        /*** 有两种情况，一个是提现成功，一个是提现失败***/
        switch ($status){
            case 'Y':
                $data['wallet_status']      ='SU';
                $data['update_time']        =$now_time;
                $data['serial_bank_name']   =$serial_bank_name;
                $data['serial_number']      =$serial_number;
                $data['serial_money']       =$serial_money*100;
				DD($data);
                $data['serial_rate']        =$serial_rate;

                $id=UserWallet::where($where)->update($data);

                break;
            case 'N':
                //阻止提现
                $data['wallet_status']  ='FS';
                $data['update_time']    =$now_time;
                $id=UserWallet::where($where)->update($data);

                $where_capital=[
                    ['total_user_id','=',$info->total_user_id],
                ];

                //把金额返回去给与用户
                $money=UserCapital::where($where_capital)->value('money');

                $data_capital['money']  =$money+$info->money;
                $data_capital['update_time']    =$now_time;
                UserCapital::where($where_capital)->update($data_capital);

                break;

        }

        if($id){
            $msg['code']=200;
            $msg['msg']="处理成功";
            return $msg;
        }else{
            $msg['code']=301;
            $msg['msg']="处理失败";
            return $msg;

        }


    }


}
?>
