<?php
namespace App\Http\Admin\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\FileController as File;

class UpController extends CommonController{

    /***    上传图片接口      /up/img
     */
    public function img(Request $request,File $file){
        $operationing   = $request->get('operationing');//接收中间件产生的参数
		dd($operationing);
        $user_info      = $request->get('user_info');//接收中间件产生的参数11
        $pic			=$request->file('inputfile');
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ="file_warehouse";

        $operationing->access_cause     ='上传图片';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
        dump($operationing);
        $browse_type=$request->path();
        $msg=$file->images($pic,$browse_type,$user_info,$now_time);

        if($msg['code']==200){
            $operationing->table_id=$msg['data']['data_file']['self_id'];
            $operationing->new_info=$msg['data']['data_file'];
            return $msg;
        }else{
            $operationing->table_id=null;
            $operationing->new_info=null;
            return $msg;

        }

    }

    /**
     * 前端上传图片 /up/upload_img
     * */
    public function upload_img(Request $request,File $file){
	//dd($request->all());
        $user_info      = $request->get('user_info');//接收中间件产生的参数11
        $pic			=$request->file('inputfile');
//        $pic0			=$request->file('inputfile0');
//        $pic1			=$request->file('inputfile1');
//        $msg['code'] = 555;
//        $msg['msg'] = '重新上传';
//        $msg['data'] = $pic;
//        $msg['arr']  = $pic0;
//        $msg['res']  = $pic1;
//        $msg['info'] = $_FILES;
//        return $msg;
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ="file_warehouse";

        $browse_type=$request->path();
        $msg=$file->image($pic);
        return $msg;

    }
    public function uploadImg(Request $request,File $file){
        //dd($request->all());
        $user_info      = $request->get('user_info');//接收中间件产生的参数11
        $pic			=$request->file('inputfile');
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ="file_warehouse";

        $browse_type=$request->path();
        $msg=$file->img($pic);
        return $msg;

    }
    /**
     * 多图上传
     * */
    public function upload_image(Request $request,File $file){
        $user_info      = $request->get('user_info');//接收中间件产生的参数11
        $pic			=$_FILES;
        $msg=$file->up_image($pic);
        return $msg;
    }

    /***    上传EXECL接口      /up/execl
     */
    public function execl(Request $request,File $file){

		$operationing   = $request->get('operationing');//接收中间件产生的参数
        //dd(212212121);
        $user_info      = $request->get('user_info');//接收中间件产生的参数11
        $pic			=$request->file('importFile');
        $now_time       =date('Y-m-d H:i:s',time());
        $table_name     ="file_warehouse";


        $operationing->access_cause     ='上传execl文件';
        $operationing->table            =$table_name;
        $operationing->operation_type   ='create';
        $operationing->now_time         =$now_time;
//dd($pic);
        $browse_type=$request->path();
        $msg=$file->import($pic,$browse_type,$user_info,$now_time);

        if($msg['code']==200){
            $operationing->table_id=$msg['data']['data_file']['self_id'];
            $operationing->new_info=$msg['data']['data_file'];
            return $msg;
        }else{
            $operationing->table_id=null;
            $operationing->new_info=null;
            return $msg;

        }
    }

}
?>
