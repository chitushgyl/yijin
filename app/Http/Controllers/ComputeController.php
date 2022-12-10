<?php
namespace App\Http\Controllers;
use App\Models\User\UserTotal;
use App\Models\User\UserCapital;
use App\Models\User\UserWallet;
use App\Models\Shop\ShopOrder;

class ComputeController extends Controller{
    /***    计算用户等级变化*/
    public function gradeCompute($user_id,$now_time){
        $cando= 'N';
        $abcccc=[2,3,4,7,9];
        //先通过这个用户ID拿去他的等级和他是不是购买过商品
        $where34333=[
            ['total_user_id','=',$user_id],
        ];
        $idd=ShopOrder::where($where34333)->whereIn('pay_status',$abcccc)->value('self_id');

        if($idd){
            $cando= 'W';
        }
        //$cando= 'W';
        //dump($cando);

        if($cando== 'W'){
            $where34=[
                ['self_id','=',$user_id],
            ];
            $grade_id=UserTotal::where($where34)->value('grade_id');

            $shuzu=['2','3','4'];
//dump($grade_id);

            if(in_array($grade_id,$shuzu)){
                $where222=[
                    ['total_user_id','=',$user_id],
                ];
                $zji=UserCapital::where($where222)->value('performance_share');

                $where=[
                    ['father_user_id1','=',$user_id],
                ];
                //查出他所有的下面的人的份数
                $select=['self_id'];

                $userCapitalSelect=['total_user_id','performance_share'];
                $recommend=UserTotal::with(['userCapital' => function($query)use($userCapitalSelect) {
                    $query->select($userCapitalSelect);
                }])->where($where)->select($select)->get();


                //dump($recommend->toArray());
                //$recommend=[];
                $geiut=[];
                foreach ($recommend as $k => $v){
                    $geiut[]=$v->userCapital->performance_share;
                }

                //计算大 小区业绩
                $total=array_sum($geiut);
                $max=$recommend?max($geiut):0;
                $other=$total-$max;

               // dump($total);dump($other);

                switch ($grade_id){
                    case '2':
                        /**当a总业绩超过100份，小区业绩应大于30份，该帐户普升为代理商**/
                        if($zji >=100 && $other >=30){
                            //升级！！！！
                            $cando= 'Y';
                        }
                        break;
                    case '3':
                        /**当a总业绩超过100份，小区业绩应大于30份，该帐户普升为代理商**/
                        if($zji >=300 && $other >=100){
                            //升级！！！！
                            $cando= 'Y';
                        }
                        break;
                    case '4':
                        /**当a总业绩超过100份，小区业绩应大于30份，该帐户普升为代理商**/
                        if($zji >=800 && $other >=300){
                            //升级！！！！
                            $cando= 'Y';
                        }

                        break;
                }

            }

        }


        if($cando== 'Y'){
            $data['grade_id']=$grade_id+1;
            $data['update_time']=$now_time;

            $total_where=[
                ['self_id','=',$user_id],
            ];
            UserTotal::where($total_where)->update($data);
        }



    }

    /***    计算个人佣金
     *      触发订单的时候弄的数据
     */
    public function personCompute($yeji,$user_id,$type,$now_time,$number){

        $cando= 'N';
        $abcccc=[2,3,4,7,9];
        //先通过这个用户ID拿去他的等级和他是不是购买过商品
        $where34333=[
            ['total_user_id','=',$user_id],
        ];
        $idd=ShopOrder::where($where34333)->whereIn('pay_status',$abcccc)->value('self_id');

        if($idd){
            $cando= 'W';
        }

        $cando= 'W';
        $bili=0;
        switch ($type){
            case '1':
                if($cando== 'W'){
                    $bili=0.12;
                }else{
                    $bili=0.05;
                }

                break;
            case '2':
                $bili=0.08;
                break;
        }


            $dedao=$yeji*$bili;

            $capital_where=[
                ['total_user_id','=',$user_id],
            ];
            $anc=UserCapital::where($capital_where)->select('money','performance','share','money_copy','performance_share')->first();

            ///$capital_where_up['total_user_id']=$user_id;

            $capital_data['money']=$anc->money+$dedao;
            $capital_data['performance']=$anc->performance+$yeji;
            $capital_data['performance_share']=$anc->performance_share+$number;
            $capital_data['update_time']=$now_time;

            //处理一下满5进一
            $money_copy=$anc->money_copy+$dedao;
            $share_can_do='N';
            if($money_copy>=5000000){
                $share_can_do='Y';
                $capital_data['share']=$anc->share+1000;
                $capital_data['money_copy']=$money_copy-5000000;
            }else{
                $capital_data['money_copy']=$money_copy;
            }


            //dump($capital_data);


            UserCapital::where($capital_where)->update($capital_data);
            /**满5送1000的流水*/
            if($share_can_do == 'Y'){
                $data['self_id']        =generate_id('wallet_');
                $data['total_user_id']  =$user_id;
                $data['capital_type']   ='share';
                $data['produce_type']   ='IN';
                $data['produce_cause']  ='满50000奖励';
                $data['create_time']    =$data['update_time']    =$now_time;
                $data['money']          ='1000';
                $data['order_sn']       =$user_id;
                $data['now_money']      =$capital_data['share'];
                $data['now_money_md']   =get_md5($capital_data['share']);
                $data['wallet_status']  ='SU';
                UserWallet::insert($data);
            }

            /*** 这个位置是做推荐奖励现金的流水**/
        if($dedao>0){
            $data['self_id']        =generate_id('wallet_');
            $data['total_user_id']  =$user_id;
            $data['capital_type']   ='wallet';
            $data['produce_type']   ='IN';
            $data['produce_cause']  ='推荐奖励';
            $data['create_time']    =$data['update_time']    =$now_time;
            $data['money']          =$dedao;
            $data['order_sn']       =$user_id;
            $data['now_money']      =$capital_data['money'];
            $data['now_money_md']   =get_md5($capital_data['money']);
            $data['wallet_status']  ='SU';
            UserWallet::insert($data);
        }

        //这个是业绩金额流水的部分
        $data['self_id']        =generate_id('wallet_');
        $data['total_user_id']  =$user_id;
        $data['capital_type']   ='performance';
        $data['produce_type']   ='IN';
        $data['produce_cause']  ='业绩';
        $data['create_time']    =$data['update_time']    =$now_time;
        $data['money']          =$yeji;
        $data['order_sn']       =$user_id;
        $data['now_money']      =$capital_data['performance'];
        $data['now_money_md']   =get_md5($capital_data['performance']);
        $data['wallet_status']  ='SU';
        UserWallet::insert($data);

        //这个是业绩份数流水的部分
        $data['self_id']        =generate_id('wallet_');
        $data['total_user_id']  =$user_id;
        $data['capital_type']   ='performance_share';
        $data['produce_type']   ='IN';
        $data['produce_cause']  ='业绩份数';
        $data['create_time']    =$data['update_time']    =$now_time;
        $data['money']          =$number;
        $data['order_sn']       =$user_id;
        $data['now_money']      =$capital_data['performance_share'];
        $data['now_money_md']   =get_md5($capital_data['performance_share']);
        $data['wallet_status']  ='SU';
        UserWallet::insert($data);


//            if($type == 1){
//
//            }


















        /** 做资金的流水，属性为股份1**/






    }


    /***    计算佣金以及其他的东西,团队奖励的部分
     *
     */
    public function groupCompute($yeji,$user_id,$grade_id,$now_time,$abcd){
        $where=[
            ['self_id','=',$user_id],
        ];
       // $egtheuiy=10;
        //把他的所有的上级抓出来看看
        $user=UserTotal::with(['userCapital' => function($query) {
            $query->select('total_user_id','money','share','money_copy');
        }])->where($where)->select('self_id','grade_id')->first();
        //$user->grade_id=3;
        if($user->grade_id > $grade_id){
            //比他级别高才可以触发
            switch ($user->grade_id){
                case '3':
                    $bili=$abcd[0];
                    $abcd[0]=0;
                    break;
                case '4':
                    $bili=$abcd[0]+$abcd[1];
                    $abcd[0]=0;
                    $abcd[1]=0;
                    break;
                case '5':
                    $bili=$abcd[0]+$abcd[1]+$abcd[2];
                    $abcd[0]=0;
                    $abcd[1]=0;
                    $abcd[2]=0;
                    break;
            }

            if($bili>0){
                $dedao=$yeji*$bili/100;
                $capital_where_up['total_user_id']=$user_id;
                $capital_data['money']=$user->userCapital->money+$dedao;
                $capital_data['update_time']=$now_time;
                //处理一下满5进一
                $money_copy=$user->userCapital->money_copy+$dedao;
                $share_can_do='N';
                if($money_copy>=5000000){
                    $share_can_do='Y';
                    $capital_data['share']=$user->userCapital->share+1000;
                    $capital_data['money_copy']=$money_copy-5000000;
                }else{
                    $capital_data['money_copy']=$money_copy;
                }

                UserCapital::where($capital_where_up)->update($capital_data);

                $data['self_id']        =generate_id('wallet_');
                $data['total_user_id']  =$user_id;
                $data['capital_type']   ='wallet';
                $data['produce_type']   ='IN';
                $data['produce_cause']  ='推荐奖励';
                $data['create_time']    =$data['update_time']    =$now_time;
                $data['money']          =$dedao;
                $data['order_sn']       =$user_id;
                $data['now_money']      =$capital_data['money'];
                $data['now_money_md']   =get_md5($capital_data['money']);
                $data['wallet_status']  ='SU';
                UserWallet::insert($data);

                if($share_can_do == 'Y'){
                    $data['self_id']        =generate_id('wallet_');
                    $data['total_user_id']  =$user_id;
                    $data['capital_type']   ='share';
                    $data['produce_type']   ='IN';
                    $data['produce_cause']  ='推荐奖励';
                    $data['create_time']    =$data['update_time']    =$now_time;
                    $data['money']          ='1000';
                    $data['order_sn']       =$user_id;
                    $data['now_money']      =$capital_data['share'];
                    $data['now_money_md']   =get_md5($capital_data['share']);
                    $data['wallet_status']  ='SU';
                    UserWallet::insert($data);
                }
            }

        }

        return $abcd;
    }






}
