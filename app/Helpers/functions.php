<?php
/**生成唯一的ID**/
function generate_id($kk){
    list($s1, $s2) = explode(' ', microtime());
    $float = explode ( ".", ((float)$s1 + (float)$s2) );
    if (!array_key_exists(1,$float)){
        $float[1] = mt_rand(1001,9999);
    }
    $a = mt_rand(100,999);
    $b = mt_rand(100,999);
    $time=$kk.date('YmdHis').$float[1].$a.$b;
    return $time;
}

/*** md5加密*/
function get_md5($money){
    $mon=md5(md5($money)."baoyan");
    return $mon;
}
/*** 图片的for循环处理1*/
function img_for($img,$type){
    $img_url        =config('aliyun.oss.url');
    $url_len        =config('aliyun.oss.url_len');
    //dump($url_len);
    //dd($img);
    $abccc=null;
    $xq=null;
    if($img){
        switch ($type){
            case 'no_json':
                $img=$img_url.$img;
                break;

            case 'one':
                $abccc=json_decode($img,true);
                $img=$abccc?$img_url.$abccc[0]['url']:null;
                break;

            case 'more':
                $abccc=json_decode($img,true);
                foreach ($abccc as $k => $v){
                    $abccc[$k]['url']=$img_url.$abccc[$k]['url'];
                }
                $img=$abccc;
                break;

            case 'in':
		//dump($type);
                foreach ($img as $k => $v) {
			//dd($v);
                    $xq[$k]['url']     = substr($v['url'], $url_len);
                    $xq[$k]['width']   = $v['width'];
                    $xq[$k]['height']  = $v['height'];
                }
		//dd($xq);
                $img=json_encode($xq);
                break;

			case 'one_in':
                $img=substr($img, $url_len);
                break;
        }
    }else{
		$img=null;
	}
    return $img;
}


/**做一个返回时间值**/
function date_time(){
    $datetime            =date('Y-m-d H:i:s',time());
    list($date,$time)    =explode(' ',$datetime);

    //上学时间段
    $upStartTime='06:30:00';
    $upEndTime='12:00:00';

    //放学时间段
    $downStartTime='15:20:00';
    $downEndTime='23:59:59';

    if($time>=$upStartTime &&  $time<=$upEndTime){
        $status='UP';
    }else if($time>=$downStartTime &&  $time<=$downEndTime){
        $status='DOWN';
    }else{
        $status='OUT';
    }

    return ['dateStatus'=>$status.$date,'status'=>$status,'date'=>$date];

}

/**处理微信模板返回信息**/
function wx_message_info($wx_message_info,$send_message){
    foreach ($wx_message_info as $k => $v){
        //判断这个$send_message中  有没有这个key值，如果有，则取这个值，如果没有，则设置为空
        if(array_key_exists($v->key, $send_message)){
            $data[$v->key]['value']=$send_message[$v->key];
        }else{
            $data[$v->key]['value']=null;
        }
    }
    return  $data;
}
/**效验数组是不是满足条件数据**/
function arr_check($shuzu,$arr){
    //定义一个回执
    $cando          ='Y';
    $msg            =null;
    $new_array      =[];
    if($arr){
        /**  效验数据顶部是不是完整***/
        $tital_check=array_filter($arr[0]);
        $check= array_flip($tital_check);
        $new=[];
        foreach ($shuzu as $k => $v){
            if(array_key_exists($k, $check)){
                $new[$check[$k]]['must']=$v[0];
                $new[$check[$k]]['repetition']=$v[1];
                $new[$check[$k]]['len']=$v[2];
                $new[$check[$k]]['field']=$v[3];
            }else{
                $cando='N';
                $msg .='数据中未包含'.$k.'</br>';
            }
        }
        /**  效验数据的必填以及重复，以及长度***/
        if($cando == 'Y'){
            $a=2;
            $abcd=0;            //初始化为0     当有错误则加1，页面显示的错误条数不能超过$errorNum 防止页面显示不全1
            $errorNum=50;       //控制错误数据的条数
            $list=[];
            array_shift($arr);      //把数组的第一个项目去掉

            foreach ($arr as $k => $v){
                dd(array_filter($v));
                if (empty($v)){
                    continue;
                }
                foreach ($v as $kk => $vv){
                    if(array_key_exists($kk, $new)){
                         //判断数据的必填
                        if($new[$kk]['must'] == 'Y'){
                            if(empty($v[$kk])){
                                if($abcd<$errorNum){
                                    $msg .= '数据中的第'.$a."行数据不完整".'</br>';
                                    $cando='N';
                                    $abcd++;
                                }
                            }
                        }
                        //判断重复性
                        if($new[$kk]['repetition'] == 'N'){
                            $list[$kk][]=$v[$kk];
                        }

                        //判断数据长度
                        if(strlen($v[$kk]) > $new[$kk]['len']){
                            $hg=$kk+1;
                            if($abcd<$errorNum){
                                $msg .= '数据中的第'.$a."行第".$hg.'列超过了'.$new[$kk]['len'].'长度'.'</br>';
                                $cando='N';
                                $abcd++;
                            }
                        }

                        /*** 判断结束   还回去一个要处理的数组**/
                        $new_list[$new[$kk]['field']]=$v[$kk];

                    }
                }
                $new_array[]=$new_list;
                $a++;
            }
        }
        /**  重复性的效验**/
//        dump($cando);
        if($cando == 'Y'){
            foreach ($list as $k => $v){
                $abcd=null;
                $abcd=array_count_values(array_filter($v));
                foreach($abcd as $kk => $vv){
                    if($vv>1){
                        $cando='N';
                        $msg .=$kk.' 重复了'.$vv."次".'</br>';
                    }
                }

            }
        }


    }else{
        $cando='N';
        $msg .=' 没有数据要进行处理'.'</br>';

    }

    $ret['cando']           =$cando;
    $ret['msg']             =$msg;
    $ret['new_array']       =$new_array;
    return $ret;

}
/**处理温区数据**/
function warm($warm_name,$min_warm,$max_warm){
     if($min_warm != NULL && $max_warm != NULL){
         $warm_name.='('.$min_warm.'℃～'.$max_warm.'℃)';
     }else{
         if($min_warm != NULL){
             $warm_name.='('.$min_warm.'℃)';
         }
         if($max_warm != NULL){
             $warm_name.='('.$max_warm.'℃)';
         }
     }

    return  $warm_name;
}


/**对运费的处理工1作**/
function freight_do($arr){
    /** 处理下运费的关系
     *  权重1，use_flag   优先，如果是N，说明这个区域不卖！！
     *  权重2，postage_flag  如果是Y，则说明这个区域包邮
     *  权重3，运费金额和减免都》0，才是收取运费
     *
     */
    if($arr->use_flag =='Y'){
        if($arr->postage_flag =='Y'){
            $show='该区域全部包邮';
        }else{
            if($arr->freight>0 &&  $arr->free>0){
                $show='该区域满'.number_format($arr->free/100,2).'元包邮，否则支付运费'.number_format($arr->freight/100,2);
            }else{
                $show='该区域全部包邮';
            }
        }
    }else{
        $show='该区域不支持购买';
    }

    return $show;
}


/**对二维数组的集合进行处理**/
function array_dispose($send,$type){
    $send_ok=[];
    switch ($type){
        case 'jiaoji':
            $new=[];
            $count=count($send);
            for($i =0;$i<$count;$i++){
                foreach ($send[$i] as $k => $v ){
                    //这里需要把$v转成数组，因为有可能过来的不是数组
                    if(is_array($v)){
                        $hiu[]=$v['token_id'];
                        $new[]=$v;
                    }else{
                        $hiu[]=$v->token_id;
                        $new[]=$v;
                    }
                }
            }
            //现在开始要去重复
            $array_f=array_unique($hiu);
            $array_f=array_keys($array_f);
            $new=json_decode(json_encode($new),true);
            //dd($new);
            foreach ($new as $k => $v){
                if(in_array($k,$array_f)){
                    $send_ok[]=$v;
                }
            }
            break;
    }
    return $send_ok;

}



function curlGet($url,$method,$post_data = 0){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($method == 'post') {
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
//        curl_setopt($ch, CURLOPT_HEADER, false);
    }elseif($method == 'get'){
        curl_setopt($ch,CURLOPT_HEADER,0);
    }

    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function putSubmit($url){
    $ch = curl_init(); //初始化CURL句柄
    curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
    curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT"); //设置请求方式
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


/***    用PHP实现对26个大写英文字母的取值
*       $number  为取的布长   $str  取什么？A为大写字母，a为小写字母，1为数字
 *      $law     为规律
 **/
function getCharactors($number,$str) {
    switch ($str){
        case 'A':
            $span = ord("A");
            break;
        case 'a':
            $span = ord("a");
            break;
        case '1':
            $span = ord("1");
            break;
    }

    for($i=1;$i<=$number;$i++){
        $result[]= chr($span);
        $span++;
    }
//    dd($result);
    return $result;
}




/**
 * 判断是否为微信客户端访问
 * @return boolean
 */
function is_wechat_client() {
    $useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
    if (strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false) {
        return false;
    }
    return true;
}

/****加密*/
function encode($string = '', $skey = 'alapasturedsdsfdccdfgvxxcv') {
    $strArr = str_split(base64_encode($string));
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key < $strCount && $strArr[$key].=$value;
    return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
 }

/***解密*/
function decode($string = '', $skey = 'alapasturedsdsfdccdfgvxxcv') {
    $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key <= $strCount  && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
    return base64_decode(join('', $strArr));
 }

/***对商品的标签进行处理工作*/
function label_do($label_flag,$label_start_time,$label_end_time , $now_time) {
    if($label_flag != 'N'){
        if($label_start_time>$now_time  || $now_time >$label_end_time){
            $label_flag='N';
        }
    }
    return $label_flag;
}


/***对WMS商品包装转换进行处理工作*/
function unit_do($good_unit , $good_target_unit, $good_scale, $num) {

//    dump($good_unit);dump($good_target_unit);dump($good_scale);dump($num);
    if($good_target_unit && $good_scale && $num){
        $qian=intval($num/$good_scale);
//    dump($qian);
        if($qian == 0){
            $text=$num.$good_unit;
        }else{
            $mo=$num%$good_scale;
            if($mo == 0){
                $text=$qian.$good_target_unit;
            }else{
                $text=$qian.$good_target_unit.$mo.$good_unit;

            }
        }

    }else{

        $text=$num.$good_unit;
    }

    return $text;


}

/*
     * 百度接口获取经纬度
     * $type 1市到市距离 2具体区域到具体区域距离
     * $city 市
     * $area 区具体位置
     * return 经纬度
     * */
function bd_location($type,$pro,$city,$area,$info){
//    $ak ="PaC1MWoU0dYwg1ZHB6IgKEFOhy3PIpvc";
    $ak = '27uKVv8Q5jQpeZb6Vxaei7RvZhjNa3Gg';
    if($type == 1){
        $address = $pro.$city;
    }else{
        $address = $pro.$city.$area.$info;
    }
    $url ="http://api.map.baidu.com/geocoding/v3/?callback=showLocation&output=json&address=".$address."&ak=".$ak;
    $showLocation = file_get_contents($url);
    $res = preg_match("/.*\((.*)\)/",$showLocation,$result);
    if ($res) {
        $res = json_decode($result[1],true);

        if($res['status'] == '0'){
            $finlly = $res['result']['location'];
            $finlly['lng'] = sprintf("%.6f",$finlly['lng']);
            $finlly['lat'] = sprintf("%.6f",$finlly['lat']);
        }else{
            $finlly = [];
        }
        return $finlly;
    } else {
        return [];
    }

}

/*
     * 获取元素在数组中的位置
     *
     * return 位置
     * */
function get_arr_location($arr,$one){
    if ($arr) {
        $l = -1;
        foreach($arr as $k => $v) {
            if ($one == $v) {
                $l = $k;
                break;
            }
        }
        return $l;

    } else {
        return -1;
    }
}

function check_carnumber($license){
    $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5,6}$/u";
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    } else {
        return false;
    }
}

/** 商城价格显示的问题**/
function price_show($price_show_flag,$min_price,$max_price){
    if($price_show_flag == 'A'){
        $price_show = number_format($min_price/100, 2);
    }else{
        if($min_price == $max_price){
            $price_show = number_format($min_price/100, 2);
        }else{
            $price_show = number_format($min_price/100, 2) .'-'.number_format($max_price/100, 2);
        }
    }
    return $price_show;
}

/*
* 根据经纬度获取行车距离
* */
function direction($lat1, $lng1, $lat2, $lng2){
    if(empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)){
        return '';
    }
    $ak ="27uKVv8Q5jQpeZb6Vxaei7RvZhjNa3Gg";
    $url = "http://api.map.baidu.com/direction/v2/driving?output=json&tactics=0&origin=".$lat1.",".$lng1."&destination=".$lat2.",".$lng2."&ak=".$ak;

    $renderOption = file_get_contents($url);
    $result = json_decode($renderOption,true);

    if ($result['status'] == '0') {
        $res['distance'] = $result['result']['routes'][0]['distance'];
        $res['duration'] = $result['result']['routes'][0]['duration'];
    }else{
        $res='';
    }

    return $res;
}

function get_word($s0){
    $fchar = ord($s0{0});
    if($fchar >= ord("A") and $fchar <= ord("z") )return strtoupper($s0{0});
    $s1 = iconv("UTF-8","gb2312", $s0);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $s0){$s = $s1;}else{$s = $s0;}
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "H";
    if($asc >= -17922 and $asc <= -17418) return "I";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return NULL;
}

function line_count_price($line,$number){
    if ($number<=$line->min_number){
        $send_price = $line->start_price/100; //配送费
    }else{
        $send_price = $line->start_price/100 + $line->unit_price/100*($number-$line->min_number);
        if ($send_price > $line->max_price/100){
            $send_price = $line->max_price/100;
        }
    }
//    else{
//        $send_price = $line->max_price/100;
//    }
    return $send_price;
}

/*
 * 生成32位随机字符串
 * */
function getStr(){
    $length = 32;
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len = strlen($str)-1;
    $randstr = '';
    for ($i=0;$i<$length;$i++) {
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

/**
 * 获取今天 本周 本月时间
 * */
function dateTime(){
    $now_date = date('Y-m-d');
    $start_time = $now_date.' 00:00:00';
    $end_time = $now_date.' 23:59:59';

    //本周
    $start_week = date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y')));
    $end_week = date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y')));

    //本月
    $start_month = date('Y-m-d H:i:s',mktime(0,0,0,date('m'),1,date('Y')));
    $end_month = date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('t'),date('Y')));

    $date['start_time']  = $start_time;
    $date['end_time']    = $end_time;
    $date['start_week']  = $start_week;
    $date['end_week']    = $end_week;
    $date['start_month'] = $start_month;
    $date['end_month']   = $end_month;

    return $date;
}


?>
