<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\SysAddressAll;
use App\Models\Shop\ShopFreight;
use App\Models\SysAddress;
class AddressController extends Controller{

    /***    拉取省市区     /address/address
     */
    public function  address(Request $request){
        //拿取地址的方式，all，children
        $type            =$request->input('type')??'all';
		//dd($type);
        $select=['id','name','level','parent_id'];
        //$type='children';
        switch ($type){
            case 'all':
                $data['info']=SysAddress::select($select)->get();
						//dd($data['info']);
                break;

            default:
                $where = [
                    ['level', '=', '1'],
                ];
                $data['info']=SysAddress::with(['children' => function($query)use($select) {
                    $query->select($select);
                    $query->with(['children' => function($query)use($select) {
                        $query->select($select);
                    }]);
                }])->where($where)->select($select)->get();
                break;


        }
//                dd($data['info']->toArray());
        $msg['code']=200;
        $msg['msg']="数据拉取成功";
        $msg['data']=$data;
        return $msg;





        /**拿取所有的地址，方便做搜索功能触发**/
//        $where=[
//            ['type_flag','=','express'],
//            ['delete_flag','=','Y'],
//        ];

        //dd($msg);

    }



    /***    初始化公司快递地址，在公司创建的地方用，在运费的板块要补充
     */
    public function createAddress($group_code,$group_name,$user_info,$now_time){
        $where=[
            ['level','=',1],
        ];

        $info=SysAddress::where($where)->select('id','name')->get();

        $data=[];
        foreach ($info as $k=>$v) {
            $freight_data['self_id']            =generate_id('freight_');
            $freight_data['code_id']            =$v->id;
            $freight_data['code_name']          =$v->name;
            $freight_data['postage_flag']       ='Y';
            $freight_data['use_flag']           ='Y';
            $freight_data['freight']            =0;
            $freight_data['free']               =0;
            $freight_data['create_user_id']     =$user_info->admin_id;
            $freight_data["create_user_name"]   =$user_info->name;
            $freight_data['create_time']        =$freight_data['update_time']=$now_time;
            $freight_data['group_code']         =$group_code;
            $freight_data['group_name']         =$group_name;
            $data[]=$freight_data;
        }
        ShopFreight::insert($data);

    }






}
