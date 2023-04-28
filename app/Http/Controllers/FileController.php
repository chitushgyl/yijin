<?php
/**
 * 此控制器主要作用为图片上传到阿里云服务器的设置功能
 *
 */
namespace App\Http\Controllers;
use QrCode;
use App\Services\OSS;
use App\Tools\Export;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\FileWarehouse;

class FileController extends Controller{
    /***    图片生成功能
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function createImage($browse_type,$group_code,$group_name,$user_info,$now_time){
        /** 先做一个本地文件**/
        $url_siru='http://'.config('page.platform.front_name').'/?group_code=';
        $msgggg=$url_siru.$group_code;

        $format='png';
        $file_name     =$group_code.'.'.$format;
        //$tmppath        =date('Y-m-d',time()).'/'.$file_name.'.'.$format;

        //这个是本地保存文件
        $storage_path = 'uploads/'.date('Y-m-d',time());//上传文件保存的路径
        $pathurl = $storage_path.'/'.$file_name;
        if (!file_exists($storage_path)) {
            mkdir($storage_path, 0777, true);
        }
        $imgPath=QrCode::format('png')->size(300)->generate($msgggg,$pathurl);
        /** 以上为生成了一个本地文件***/

        /** 现在上传到OSS上面去**/
        $storage_path2 = 'images/'.date('Y-m-d',time());//上传文件保存的路径
        $pathName = $storage_path2.'/'.$file_name;
        $filepath=$this->oss_do($pathName,$pathurl);

        /*** 做一组数据变量***/
        $info['type']='group_ewm';
        $info['group_code']=$group_code;
        $info['group_name']=$group_name;
        $info['browse_type']=$browse_type;
        $info['operation_type']='IN';


        $data_file=$this->file_do($info,$pathName,$user_info,$now_time);

        return $filepath;
    }



    /***    图片上传功能
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function images($pic,$browse_type,$user_info,$now_time){
//        dd($pic);
        if($pic){
            if ($pic->isValid()) {
                //括号里面的是必须加的哦,如果括号里面的不加上的话，下面的方法也无法调用的
                $name=$pic->getClientOriginalName();//得到图片名；
                $ext=$pic->getClientOriginalExtension();//获取文件的扩展名
                $extensions = ["png", "jpg", "gif","image","jpeg","pdf"];

                $size = $pic->getSize();
                if(!in_array(strtolower($ext),$extensions)) {//限制上传文件的类型
                    $msg['code'] = 302;
                    $msg['msg'] = '只能上传 png | jpg | gif | pdf格式的图片';
                    return $msg;
                }else{
                    if($size > 50*1024*1024){
                        $msg['code'] = 303;
                        $msg['msg'] = '上传图片不能超过2M';
                        return $msg;
                    }

                    $storage_path = 'images/'.date('Y-m-d',time());//上传文件保存的路径
                    //获取上传图片的临时地址
                    $pathurl = $pic->getRealPath();

                    //生成文件名
                    $file_name =md5(uniqid($name)).'.'.$ext;
                    //拼接上传的文件夹路径(按照日期格式1810/17/xxxx.jpg)
                    $pathName = $storage_path.'/'.$file_name;

                    $filepath=$this->oss_do($pathName,$pathurl);


                    //获取上传图片的大小
                    $url=getimagesize($filepath);
                    $width=$url[0];
                    $height=$url[1];


                    $info['type']			='img';
                    $info['group_code']	=$user_info->group_code;
                    $info['group_name']	=$user_info->group_name;
                    $info['browse_type']	=$browse_type;
                    $info['operation_type']='IN';

                    $data_file=$this->file_do($info,$pathName,$user_info,$now_time);
                    $data['url']=$filepath;
                    $data['width']=$width;
                    $data['height']=$height;
                    $data['data_file']=$data_file;

                    $msg['code'] = 200;
                    $msg['msg'] = '上传图片成功';
                    $msg['data'] = $data;


                    return $msg;

                    //
                }
            }else{
                $msg['code'] = 301;
                $msg['msg'] = '上传的图片无效';
                return $msg;
            }
        }else{
            $msg['code'] = 304;
            $msg['msg'] = '上传的图片无效';

            return $msg;
        }
    }

    /**
     * 前端上传图片
     * */
    public function image($pic){
        //dd($user_info);
		//dump($pic);
        if($pic){
            if ($pic->isValid()) {
                //括号里面的是必须加的哦,如果括号里面的不加上的话，下面的方法也无法调用的
                $name=$pic->getClientOriginalName();//得到图片名；
                $ext=$pic->getClientOriginalExtension();//获取文件的扩展名
                $extensions = ["png", "jpg", "gif","image","jpeg"];

                $size = $pic->getSize();
                if(!in_array(strtolower($ext),$extensions)) {//限制上传文件的类型
                    $msg['code'] = 302;
                    $msg['msg'] = '只能上传 png | jpg | gif格式的图片';
                    return $msg;
                }else{
                    if($size > 2*1024*1024){
                        $msg['code'] = 303;
                        $msg['msg'] = '上传图片不能超过2M';
                        return $msg;
                    }

                    $storage_path = 'images/'.date('Y-m-d',time());//上传文件保存的路径
                    //获取上传图片的临时地址
                    $pathurl = $pic->getRealPath();

                    //生成文件名
                    $file_name =md5(uniqid($name)).'.'.$ext;
                    //拼接上传的文件夹路径(按照日期格式1810/17/xxxx.jpg)
                    $pathName = $storage_path.'/'.$file_name;

                    $filepath=$this->oss_do($pathName,$pathurl);


                    //获取上传图片的大小
                    $url=getimagesize($filepath);
                    $width=$url[0];
                    $height=$url[1];

                    $data['url']=$filepath;
                    $data['width']=$width;
                    $data['height']=$height;


                    $msg['code'] = 200;
                    $msg['msg'] = '上传图片成功';
                    $msg['data'] = $data;


                    return $msg;

                    //
                }
            }else{
                $msg['code'] = 301;
                $msg['msg'] = '上传的图片无效';
                return $msg;
            }
        }else{
            $msg['code'] = 304;
            //$msg['msg'] = '上传的图片无效';
			$msg['msg'] = $pic;

            return $msg;
        }
    }


    public function img($pic){
        //dd($user_info);
        //dump($pic);
        if($pic){
            if ($pic->isValid()) {
                //括号里面的是必须加的哦,如果括号里面的不加上的话，下面的方法也无法调用的
                $name=$pic->getClientOriginalName();//得到图片名；
                $ext=$pic->getClientOriginalExtension();//获取文件的扩展名
                $extensions = ["png", "jpg", "gif","image","jpeg"];

                $size = $pic->getSize();
                if(!in_array(strtolower($ext),$extensions)) {//限制上传文件的类型
                    $msg['code'] = 302;
                    $msg['msg'] = '只能上传 png | jpg | gif格式的图片';
                    return $msg;
                }else{
                    if($size > 2*1024*1024){
                        $msg['code'] = 303;
                        $msg['msg'] = '上传图片不能超过2M';
                        return $msg;
                    }

                    $storage_path = 'images/'.date('Y-m-d',time());//上传文件保存的路径
                    //获取上传图片的临时地址
                    $pathurl = $pic->getRealPath();

                    //生成文件名
                    $file_name =md5(uniqid($name)).'.'.$ext;
                    //拼接上传的文件夹路径(按照日期格式1810/17/xxxx.jpg)
                    $pathName = $storage_path.'/'.$file_name;

                    $filepath=$this->oss_do($pathName,$pathurl);


                    //获取上传图片的大小
                    $url=getimagesize($filepath);
                    $width=$url[0];
                    $height=$url[1];

                    $data['url']=$filepath;
                    $data['width']=$width;
                    $data['height']=$height;


                    $msg['code'] = 200;
                    $msg['msg'] = '上传图片成功';
                    $msg['data'] = $data;


                    return $msg;

                    //
                }
            }else{
                $msg['code'] = 301;
                $msg['msg'] = '上传的图片无效';
                return $msg;
            }
        }else{
            $msg['code'] = 304;
            //$msg['msg'] = '上传的图片无效';
            $msg['msg'] = $pic;

            return $msg;
        }
    }
    /**
     * 多图上传
     * */
    public function up_image($pic){
        if($pic){
            $data_img = [];
            foreach ($pic as $key => $value){
                if ((object)$value->isValid()) {
                    //括号里面的是必须加的哦,如果括号里面的不加上的话，下面的方法也无法调用的
                    $name=$value->getClientOriginalName();//得到图片名；
                    $ext=$value->getClientOriginalExtension();//获取文件的扩展名
                    $extensions = ["png", "jpg", "gif","image","jpeg"];

                    $size = $value->getSize();
                    if(!in_array(strtolower($ext),$extensions)) {//限制上传文件的类型
                        $msg['code'] = 302;
                        $msg['msg'] = '只能上传 png | jpg | gif格式的图片';
                        return $msg;
                    }else{
                        if($size > 2*1024*1024){
                            $msg['code'] = 303;
                            $msg['msg'] = '上传图片不能超过2M';
                            return $msg;
                        }

                        $storage_path = 'images/'.date('Y-m-d',time());//上传文件保存的路径
                        //获取上传图片的临时地址
                        $pathurl = $value->getRealPath();

                        //生成文件名
                        $file_name =md5(uniqid($name)).'.'.$ext;
                        //拼接上传的文件夹路径(按照日期格式1810/17/xxxx.jpg)
                        $pathName = $storage_path.'/'.$file_name;

                        $filepath=$this->oss_do($pathName,$pathurl);

                        //获取上传图片的大小
                        $url=getimagesize($filepath);
                        $width=$url[0];
                        $height=$url[1];

                        $data['url']=$filepath;
                        $data['width']=$width;
                        $data['height']=$height;

                        $data_img[] = $data;
                    }
                }else{
                    $msg['code'] = 301;
                    $msg['msg'] = '上传的图片无效';
                    return $msg;
                }
            }
            $msg['code'] = 200;
            $msg['msg'] = '上传图片成功';
            $msg['data'] = $data_img;
            return $msg;

        }else{
            $msg['code'] = 304;
            $msg['msg'] = '上传的图片无效';
            return $msg;
        }
    }

    /***    execl导入功能      /file/import
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function import($pic,$browse_type,$user_info,$now_time){
        if($pic) {
            if ($pic->isValid()) {
                $name = $pic->getClientOriginalName();            //得到图片名；11
                $ext = $pic->getClientOriginalExtension();        //获取文件的扩展名
                $extensions = ["csv", "xlsx", "xls"];
                $size = $pic->getSize();
                if (!in_array(strtolower($ext), $extensions)) {//限制上传文件的类型
                    $msg['code'] = 302;
                    $msg['msg'] = '文件格式不正确';
                    return $msg;
                }
                if ($size > 2 * 1024 * 1024) {
                    $msg['code'] = 303;
                    $msg['msg'] = '上传文件不能超过2M';
                    return $msg;
                }

                $storage_path = 'execl/' . date('Y-m-d', time());//上传文件保存的路径

                //获取上传图片的临时地址
                $pathurl = $pic->getRealPath();

                //生成文件名
                $file_name = md5(uniqid($name)) . '.' . $ext;

                $pathName = $storage_path . '/' . $file_name;

                //上传到阿里云OSS
                $filepath=$this->oss_do($pathName,$pathurl);

                $storage_path2 = 'uploads/' . date('Y-m-d', time());//上传文件保存的路径
                //拼接上传的文件夹路径(按照日期格式1810/17/xxxx.jpg)
                $pic->move($storage_path2, $file_name);

                $pathurl = $storage_path2 . '/' . $file_name;


                $info['type']			='xlsx';
                $info['group_code']		=$user_info->group_code;
                $info['group_name']		=$user_info->group_name;
                $info['browse_type']	=$browse_type;
                $info['operation_type']	='IN';

                $data_file=$this->file_do($info,$pathName,$user_info,$now_time);
				$data['self_id']=$data_file['self_id'];
                $data['url']=$filepath;
                $data['pathurl']=$pathurl;
                $data['data_file']=$data_file;

                $msg['code'] = 200;
                $msg['msg'] = '上传成功';
                $msg['data'] = $data;

                return $msg;
            } else {
                $msg['code'] = 301;
                $msg['msg'] = '请上传文件';
                return $msg;
            }
        }else{
            $msg['code'] = 304;
            $msg['msg'] = '上传的图片无效';

            return $msg;
        }
    }

    /***    execl导出功能      /file/export
     *      前端传递必须参数：
     *      前端传递非必须参数：
     */
    public function export($cellData,$cellDataTitle,$group_code,$group_name,$browse_type,$user_info,$where,$now_time,$start=null,$end=null){
        /****   传递变量过来，一个是数据，一个是头部
         *      $cellData  是数据
         *      $cellDataTitle   是头部文件
         ****/
        $format='xlsx';
        $file_name     =generate_id('execl_');
        $tmppath        =date('Y-m-d',time()).'/'.$file_name.'.'.$format;
        $bucket_name    = config('aliyun.oss.bucket');
        $pathName       = 'execl/'.date('Y-m-d',time()).'/'.$file_name.'.'.$format;//上传文件保存的路径
        $store          =Excel::store(new Export($cellDataTitle,$cellData),$tmppath);


        if($store){
            $pathurl='uploads/'.$tmppath;
            //上传到阿里云OSS
            $filepath=$this->oss_do($pathName,$pathurl);
            /*** 做一组数据变量***/
            $info['type']=$format;
            $info['group_code']=$group_code;
            $info['group_name']=$group_name;
            $info['browse_type']=$browse_type;
            $info['operation_type']='OUT';


            $data_file=$this->file_do($info,$pathName,$user_info,$now_time,$where,$start,$end);
			$data['self_id']=$data_file['self_id'];
            $data['url']=$filepath;
            $data['data_file']=$data_file;
//            dd($data);
            $msg['code'] = 200;
            $msg['msg'] = '导出成功';
            $msg['data'] = $data;
            return $msg;
        }else{
            $msg['code'] = 300;
            $msg['msg'] = '文件生成失败';
            return $msg;
        }


    }
    /***    做一个文件上传阿里云的接口，把文件放在阿里云上面去
     */
    public function oss_do($pathName,$pathurl){
        $bucket_name = config('aliyun.oss.bucket');
        OSS::publicUpload($bucket_name, $pathName, $pathurl);
        $filepath = OSS::getPublicObjectURL($bucket_name, $pathName);

        if(strpos($filepath,"https") === false){
            $filepath='https'.substr($filepath,4);
        }
        return $filepath;
    }

    /***    启用一个文件管理系统，这个时候需要的工作是把上传或者导出的文件放在一个数据库表中进行储存
     */
    public function file_do($info,$pathName,$user_info,$now_time,$where=null,$start=null,$end=null){
        /**  做一个文件仓库出来1**/
		//dd($user_info);
        $data_file['self_id']		    =generate_id('file_');
        $data_file['type']		        =$info['type'];
        $data_file['oss_prefix']	    =config('aliyun.oss.url');
        $data_file['url']	            =$pathName;
        $data_file['group_code']		=$info['group_code'];
        $data_file['group_name']		=$info['group_name'];
        $data_file['create_user_id']	=$user_info->admin_id;
        $data_file['create_user_name']	=$user_info->name;
        $data_file['create_time']	    =$now_time;
        $data_file['update_time']		=$now_time;
        $data_file['operation_type']	=$info['operation_type'];
        $data_file['start_time']	    =$start;
        $data_file['end_time']	        =$end;
        $data_file['browse_type']		=$info['browse_type'];
        if($where){
            $data_file['condition_info']	=json_encode($where,JSON_UNESCAPED_UNICODE);
        }

        FileWarehouse::insert($data_file);

        return $data_file;

    }

}
