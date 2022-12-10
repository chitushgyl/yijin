<?php
namespace App\Http\Controllers;
use App\Models\SysAddress;
use App\Models\Tms\TmsAddress;
use App\Models\Tms\TmsAddressContact;
use App\Models\Tms\TmsCar;
use App\Models\Tms\TmsContacts;
class TmsController extends Controller{
    /***    TMS中地址抓取及地址处理板块
     */
    public function address($address_id,$qu,$address,$group_info,$user_info,$now_time){
        $info=null;
        $select_address=['self_id','sheng','sheng_name','shi','shi_name','qu','qu_name','particular','address','longitude','dimensionality'];
        if($address_id){
            $where_address=[
                ['delete_flag','=','Y'],
                ['self_id','=',$address_id],
            ];
            $info = TmsAddress::where($where_address)->select($select_address)->first();

        }else{
            if($qu && $address  && $group_info){

                $where_address=[
                    ['id','=',$qu],
                ];
                $selectMenu=['id','name','parent_id'];
                $address_info=SysAddress::with(['sysAddress' => function($query)use($selectMenu) {
                    $query->select($selectMenu);
                    $query->with(['sysAddress' => function($query)use($selectMenu) {
                        $query->select($selectMenu);
                    }]);
                }])->where($where_address)->select($selectMenu)->first();
                /* 获取经纬度*/
                $result = bd_location(2,$address_info->sysAddress->sysAddress->name,$address_info->sysAddress->name,$address_info->name,$address);
                $where_address_check=[
                    ['sheng','=',$address_info->sysAddress->sysAddress->id],
                    ['shi','=',$address_info->sysAddress->id],
                    ['qu','=',$address_info->id],
                    ['address','=',$address],
                    ['group_code','=',$group_info->group_code],
                ];

                $info = TmsAddress::where($where_address_check)->select($select_address)->first();

                if(empty($info)){
                    $data['self_id']            =generate_id('address_');
                    $data['sheng']              =$address_info->sysAddress->sysAddress->id;
                    $data['sheng_name']         =$address_info->sysAddress->sysAddress->name;
                    $data['shi']                =$address_info->sysAddress->id;
                    $data['shi_name']           =$address_info->sysAddress->name;
                    $data['qu']                 =$address_info->id;
                    $data['qu_name']            =$address_info->name;
                    $data['address']            =$address;
                    $data['longitude']          =$result['lng'];
                    $data['dimensionality']     =$result['lat'];
                    $data['group_code']         =$group_info->group_code;
                    $data['group_name']         =$group_info->group_name;
                    $data['create_user_id']     =$user_info->admin_id;
                    $data['create_user_name']   =$user_info->name;
                    $data['create_time']     = $data['update_time'] = $now_time;
                    TmsAddress::insert($data);
                    $info=(object)$data;
                }
            }
        }
        return $info;

    }

    public function address_contact($address_id,$qu,$address,$contacts,$tel,$group_info,$user_info,$now_time){
        $info=null;
        $select_address=['self_id','sheng','sheng_name','shi','shi_name','qu','qu_name','particular','address','contacts','tel','longitude','dimensionality'];
        if($address_id){
            $where_address=[
                ['delete_flag','=','Y'],
                ['self_id','=',$address_id],
            ];
            $info = TmsAddressContact::where($where_address)->select($select_address)->first();

        }else{
            if($qu && $address){

                $where_address=[
                    ['id','=',$qu],
                ];
                $selectMenu=['id','name','parent_id'];
                $address_info=SysAddress::with(['sysAddress' => function($query)use($selectMenu) {
                    $query->select($selectMenu);
                    $query->with(['sysAddress' => function($query)use($selectMenu) {
                        $query->select($selectMenu);
                    }]);
                }])->where($where_address)->select($selectMenu)->first();
                /* 获取经纬度*/
                $result = bd_location(2,$address_info->sysAddress->sysAddress->name,$address_info->sysAddress->name,$address_info->name,$address);

                if ($group_info){
                    $where_address_check=[
                        ['sheng','=',$address_info->sysAddress->sysAddress->id],
                        ['shi','=',$address_info->sysAddress->id],
                        ['qu','=',$address_info->id],
                        ['address','=',$address],
                        ['group_code','=',$group_info->group_code],
                    ];
                }else{
                    $where_address_check=[
                        ['sheng','=',$address_info->sysAddress->sysAddress->id],
                        ['shi','=',$address_info->sysAddress->id],
                        ['qu','=',$address_info->id],
                        ['address','=',$address],
                        ['total_user_id','=',$user_info->total_user_id],
                    ];
                }
                $info = TmsAddressContact::where($where_address_check)->select($select_address)->first();
                if(empty($info)){
                    $data['self_id']            =generate_id('address_');
                    $data['sheng']              =$address_info->sysAddress->sysAddress->id;
                    $data['sheng_name']         =$address_info->sysAddress->sysAddress->name;
                    $data['shi']                =$address_info->sysAddress->id;
                    $data['shi_name']           =$address_info->sysAddress->name;
                    $data['qu']                 =$address_info->id;
                    $data['qu_name']            =$address_info->name;
                    $data['address']            =$address;
                    $data['contacts']           =$contacts;
                    $data['tel']                =$tel;
                    $data['longitude']          =$result['lng'];
                    $data['dimensionality']     =$result['lat'];
                    if ($group_info){
                        $data['group_code']         =$group_info->group_code;
                        $data['group_name']         =$group_info->group_name;
                        $data['create_user_id']     =$user_info->admin_id;
                        $data['create_user_name']   =$user_info->name;
                    }else{
                        $data['total_user_id']   =$user_info->total_user_id;
                    }

                    $data['create_time']     = $data['update_time'] = $now_time;
                    TmsAddressContact::insert($data);
                    $info=(object)$data;
                }
            }
        }
        return $info;

    }

    /***    TMS中联系人抓取及联系人处理板块
     */
    public function contacts($contacts_id,$contacts,$tel,$group_info,$user_info,$now_time){
        $info=null;
        if($contacts_id){
            $select_contacts=['self_id','contacts','tel'];
            $where_contacts=[
                ['delete_flag','=','Y'],
                ['self_id','=',$contacts_id],
            ];

            $info = TmsContacts::where($where_contacts)->select($select_contacts)->first();
        }else{
            if($contacts && $tel && $group_info){
                $data['self_id']            =generate_id('contacts_');
                $data['contacts']           = $contacts;
                $data['tel']                = $tel;
                $data['group_code']         =$group_info->group_code;
                $data['group_name']         =$group_info->group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']     = $data['update_time'] = $now_time;
                TmsContacts::insert($data);

                $info=(object)$data;
            }

        }
        return $info;
    }


    /***    TMS中联系人抓取及联系人处理板块
     */
    public function get_car($car_id,$car_number,$type,$group_info,$user_info,$now_time){
        $info=null;
        if($car_id){
            $select_car=['self_id','car_number','car_possess'];
            $where_car=[
                ['delete_flag','=','Y'],
                ['self_id','=',$car_id],
            ];
            $info = TmsCar::where($where_car)->select($select_car)->first();

        }else{
            if($car_number && $type && $group_info){
                $data['self_id']            =generate_id('car_');
                $data['car_number']           = $car_number;
                $data['car_possess']                = $type;
                $data['group_code']         =$group_info->group_code;
                $data['group_name']         =$group_info->group_name;
                $data['create_user_id']     =$user_info->admin_id;
                $data['create_user_name']   =$user_info->name;
                $data['create_time']     = $data['update_time'] = $now_time;
                TmsCar::insert($data);

                $info=(object)$data;
            }

        }

        return $info;
    }

    /*
     * 获取省市区ID
     * */
    public function get_district($pro,$city,$area,$address,$contacts,$tel,$group_info,$type,$user_info,$now_time,$abcd,$a){
        $info = null;
        $where_address=[
            ['name','=',$area],
            ['level','=',3],
        ];

        $where_address2=[
            ['name','=',$city],
            ['level','=',2],
        ];
        $where_address3=[
            ['name','=',$pro],
            ['level','=',1],
        ];


        $selectMenu=['id','name','parent_id'];

        $address_info=SysAddress::with(['address' => function($query)use($selectMenu,$where_address2,$where_address) {
            $query->select($selectMenu);
            $query->where($where_address2);
            $query->with(['address' => function($query)use($selectMenu,$where_address) {
                $query->select($selectMenu);
                $query->where($where_address);
            }]);
        }])->where($where_address3)->select($selectMenu)->first();
//        dd($address_info->toArray());
        $datalist=[];       //初始化数组为空
        $cando='Y';         //错误数据的标o
        $strs = '';
//        $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
        $errorNum=50;       //控制错误数据的条数
//        $a=2;
        if($address_info == null){
            $strs .= '数据中的第'.$a."行".$type."省不存在".'</br>';
            $cando='N';
            $abcd++;
        }else{
            if($address_info->address == null){
                $strs .= '数据中的第'.$a."行".$type."市不存在".'</br>';
                $cando='N';
                $abcd++;
            }else{
                if($address_info->address->address ==null){
                    $strs .= '数据中的第'.$a."行".$type."区不存在".'</br>';
                    $cando='N';
                    $abcd++;
                }
            }
        }
        if ($cando == 'Y'){
//            $result =  bd_location(2,$pro,$city,$area,$address);
//            $data['self_id']            =generate_id('address_');
            $data['self_id']            ='';
            $data['sheng']              =$address_info->id;
            $data['sheng_name']         =$address_info->name;
            $data['shi']                =$address_info->address->id;
            $data['shi_name']           =$address_info->address->name;
            $data['qu']                 =$address_info->address->address->id;
            $data['qu_name']            =$address_info->address->address->name;
            $data['address']            =$address;
            $data['contacts']           =$contacts;
            $data['tel']                =$tel;
//            $data['longitude']          =$result['lng'];
//            $data['dimensionality']     =$result['lat'];
//            $data['group_code']         =$group_info->group_code;
//            $data['group_name']         =$group_info->group_name;
//            $data['create_user_id']     =$user_info->admin_id;
//            $data['create_user_name']   =$user_info->name;
//            $data['create_time']     = $data['update_time'] = $now_time;
//            TmsAddressContact::insert($data);
            $info=(object)$data;
            $info->code = 200;
        }else{
            $data['code'] = 306;
            $data['msg'] = $strs;
            $info=(object)$data;

        }
        return $info;

    }

    /***    TMS中地址抓取及地址处理板块  total_user_id
     */
    public function address_api($address_id,$qu,$address,$contacts,$tel,$user_info,$now_time,$qu_name,$shi_name){
        $info = null;
        $select_address = ['self_id','sheng','sheng_name','shi','shi_name','qu','qu_name','particular','address','longitude','dimensionality'];
        if($address_id) {
            $where_address = [
                ['self_id','=',$address_id],
            ];
            $info = TmsAddressContact::where($where_address)->select($select_address)->first();
        } else {
            $selectMenu = ['id','name','parent_id'];
            if ($qu_name && $shi_name) {
                $where_shi = [
                    ['name','=',$shi_name],
                    ['level','=',2],
                ];
                $shi_info = SysAddress::where($where_shi)->select($selectMenu)->first();
                if ($shi_info) {
                    $where_qu = [
                        ['name','=',$qu_name],
                        ['level','=',3],
                        ['parent_id','=',$shi_info->id],

                    ];
                    $qu_info = SysAddress::where($where_qu)->select($selectMenu)->first();
                    if ($qu_info) {
                        $where_address_name = [
                            ['id','=',$qu_info->id]
                        ];
                        $address_info = SysAddress::with(['sysAddress' => function($query)use($selectMenu) {
                            $query->select($selectMenu);
                            $query->with(['sysAddress' => function($query)use($selectMenu) {
                                $query->select($selectMenu);
                            }]);
                        }])->where($where_address_name)->select($selectMenu)->first();

                        /* 获取经纬度*/
                        $result = bd_location(2,$address_info->sysAddress->sysAddress->name,$address_info->sysAddress->name,$address_info->name,$address);
                        $where_address_check = [
                            ['sheng','=',$address_info->sysAddress->sysAddress->id],
                            ['shi','=',$address_info->sysAddress->id],
                            ['qu','=',$address_info->id],
                            ['address','=',$address],
                            ['create_user_id','=',$user_info->total_user_id],
                        ];
                        $info = TmsAddressContact::where($where_address_check)->select($select_address)->first();
                        if(empty($info)){
                            $data['self_id']          = generate_id('address_');
                            $data['sheng']            = $address_info->sysAddress->sysAddress->id;
                            $data['sheng_name']       = $address_info->sysAddress->sysAddress->name;
                            $data['shi']              = $address_info->sysAddress->id;
                            $data['shi_name']         = $address_info->sysAddress->name;
                            $data['qu']               = $address_info->id;
                            $data['qu_name']          = $address_info->name;
                            $data['address']          = $address;
                            $data['contacts']         = $contacts;
                            $data['tel']              = $tel;
                            $data['longitude']        = $result['lng'];
                            $data['dimensionality']   = $result['lat'];
                            $data['group_code']       = '';
                            $data['group_name']       = '';
//                            $data['create_user_id']   = $user_info->total_user_id;
//                            $data['create_user_name'] = $user_info->token_name;
                            $data['create_time']      = $data['update_time'] = $now_time;
                            TmsAddressContact::insert($data);
                            $info = (object)$data;
                        }
                    }
                }
            }

            if($qu && $address  && $user_info && !$info) {
                $where_address = [
                    ['id','=',$qu],
                ];

                $address_info = SysAddress::with(['sysAddress' => function($query)use($selectMenu) {
                    $query->select($selectMenu);
                    $query->with(['sysAddress' => function($query)use($selectMenu) {
                        $query->select($selectMenu);
                    }]);
                }])->where($where_address)->select($selectMenu)->first();
                /* 获取经纬度*/
                $result = bd_location(2,$address_info->sysAddress->sysAddress->name,$address_info->sysAddress->name,$address_info->name,$address);
                $where_address_check = [
                    ['sheng','=',$address_info->sysAddress->sysAddress->id],
                    ['shi','=',$address_info->sysAddress->id],
                    ['qu','=',$address_info->id],
                    ['address','=',$address],
                    ['create_user_id','=',$user_info->total_user_id],
                ];

                $info = TmsAddressContact::where($where_address_check)->select($select_address)->first();

                if(empty($info)){
                    $data['self_id']          = generate_id('address_');
                    $data['sheng']            = $address_info->sysAddress->sysAddress->id;
                    $data['sheng_name']       = $address_info->sysAddress->sysAddress->name;
                    $data['shi']              = $address_info->sysAddress->id;
                    $data['shi_name']         = $address_info->sysAddress->name;
                    $data['qu']               = $address_info->id;
                    $data['qu_name']          = $address_info->name;
                    $data['address']          = $address;
                    $data['contacts']          = $contacts;
                    $data['tel']              = $tel;
                    $data['longitude']        = $result['lng'];
                    $data['dimensionality']   = $result['lat'];
                    $data['group_code']       = '';
                    $data['group_name']       = '';
//                    $data['create_user_id']   = $user_info->total_user_id;
//                    $data['create_user_name'] = $user_info->token_name;
                    $data['create_time']      = $data['update_time'] = $now_time;
                    TmsAddressContact::insert($data);
                    $info = (object)$data;
                }
            }
        }
        return $info;
    }

    /*
    **    TMS中联系人抓取及联系人处理板块 total_user_id
    */
    public function contacts_api($contacts_id,$contacts,$tel,$group_info,$user_info,$now_time){
        $info = null;
        if($contacts_id) {
            $select_contacts = ['self_id','contacts','tel'];
            $where_contacts = [
                ['delete_flag','=','Y'],
                ['self_id','=',$contacts_id],
            ];

            $info = TmsContacts::where($where_contacts)->select($select_contacts)->first();
        } else {
            if($contacts && $tel && $user_info) {
                $select_contacts = ['self_id','contacts','tel'];
                $where_contacts = [
                    ['delete_flag','=','Y'],
                    ['contacts','=',$contacts],
                    ['tel','=',$tel],
                    ['create_user_id','=',$user_info->total_user_id],
                ];

                $info = TmsContacts::where($where_contacts)->select($select_contacts)->first();
                if (empty($info)) {
                    $data['self_id']          = generate_id('contacts_');
                    $data['contacts']         = $contacts;
                    $data['tel']              = $tel;
                    $data['group_code']       = '';
                    $data['group_name']       = '';
                    $data['create_user_id']   = $user_info->total_user_id;
                    $data['create_user_name'] = $user_info->token_name;
                    $data['create_time']      = $data['update_time'] = $now_time;
                    TmsContacts::insert($data);
                    $info = (object)$data;
                }
            }
        }
        return $info;
    }



}
