<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/7/22
 * Time: 17:08
 */
namespace App\Http\Admin\School;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;

class TestController extends CommonController{

    /**大屏数据展示    左侧数据统计栏（未大屏之前）
     * pathUrl => /test
     * @return mixed
     */
    public function test(Request $request){
		
		//DD(1121);
		//return true;
        $info=$request->input('info');
		//dd($info);
//        $info=[
//            'mac_id'=>'12311',
//            'cad_id'=>'456898',
//            'longitude'=>'121.317604',
//            'dimensionality'=>'31.189552',
//            'time'=>'2018-12-01 18:03:21',
//
//        ];
        if($info){
            $id=false;
			
            $data['messs']      =$info;
            $data['time']       =date('Y-m-d H:i:s',time());
			//dd($data);
            $id=DB::table('abc')->insert($data);

            if($id){
				return response()->json(['code'=>200,'msg'=>'成功'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
               // $msg['code']=200;
               // $msg['msg']="成功";
               // return $msg;
            }else{
				return response()->json(['code'=>300,'msg'=>'失败'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
                //$msg['code']=300;
               // $msg['msg']="失败";
               // return $msg;
            }

        }else{
			return response()->json(['code'=>301,'msg'=>'没有数据'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            //$msg['code']=301;
            //$msg['msg']="没有数据";
           // return $msg;
        }


    }
/**大屏数据展示    左侧数据统计栏（未大屏之前）
     * pathUrl => /test2
     * @return mixed
     */
    public function test2(Request $request){
		/**
		$id='6645';
		$info=DB::table('abc')->where('id','=',$id)->first();
		
		$eriu=json_decode(json_decode($info->messs));
		
		
		dump($eriu->mac_id);
		
		
		dump($eriu);
		
		dd($info);
		**/
		
        $mac_id			=$request->input('mac_id');
		$cad_id			=$request->input('cad_id');
		$longitude		=$request->input('longitude');
		$dimensionality	=$request->input('dimensionality');
		$time			=$request->input('time');
		//$cad_id='1211';
		//dd($info);
        $info=[
            'mac_id'=>$mac_id,
            'cad_id'=>$cad_id,
            'longitude'=>$longitude,
            'dimensionality'=>$dimensionality,
            'time'=>$time,

        ];
        if($cad_id){
            $id=false;
			
            $data['messs']      =json_encode($info,JSON_UNESCAPED_UNICODE);
            $data['time']       =date('Y-m-d H:i:s',time());
			//dd($data);
            $id=DB::table('abc')->insert($data);

            if($id){
				return response()->json(['code'=>200,'msg'=>'成功'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
               // $msg['code']=200;
               // $msg['msg']="成功";
               // return $msg;
            }else{
				return response()->json(['code'=>300,'msg'=>'失败'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
                //$msg['code']=300;
               // $msg['msg']="失败";
               // return $msg; 
            }

        }else{
			return response()->json(['code'=>301,'msg'=>'没有数据'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            //$msg['code']=301;
           // $msg['msg']="没有数据";
           // return $msg;
        }


    }
	
	
	/**大屏数据展示    左侧数据统计栏（未大屏之前）
     * pathUrl => /test3
     * @return mixed
     */
    public function test3(Request $request){
		
		$id='6678';
		$info=DB::table('abc')->where('id','=',$id)->first();
		
		$eriu=json_decode($info->messs);
		
		
		
		
		
		
		//dump(json_decode($eriu));
		
		
		dump($eriu);
		
		dd($info);
	}

}