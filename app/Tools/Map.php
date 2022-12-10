<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/18
 * Time: 14:43
 */
namespace App\Tools;
class Map {
    /**
     * 经纬度计算距离
     * @param $longitude
     * @param $latitude
     * @param $enterLongitude
     * @param $enterLatitude
     * @param string $key
     * @return mixed
     */
    public function getDistance($longitude,$latitude,$enterLongitude,$enterLatitude,$key='4e481c099d1871be2e8989497ab26e46'){
        $origin=$longitude.','.$latitude;               //这个是当前的位置经纬度
        $destination=$enterLongitude.','.$enterLatitude;
        $queryUrl='https://restapi.amap.com/v3/direction/driving?origin='.$origin.'&destination='.$destination.'&extensions=base&output=json&key='.$key.'&strategy=10';
        $json = $this->httpGet($queryUrl);
        $back=json_decode($json,true);
        return $back;
    }

    /**
     * GET 请求远程的链接
     * @param $url
     * @return mixed
     */
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /**
     * 判断经纬度的大小
     * @param $long
     * @param $lat
     * @param $longitude
     * @param $latitude
     * @return bool
     */
    public function getBoolean($long,$lat,$longitude,$latitude,$distance = 30){
        $point			=$this->getSquarePoint($long,$lat,$distance);
        $lng_start		=number_format($point['lng_start'],6);
        $lng_end		=number_format($point['lng_end'],6);
        $lat_start		=number_format($point['lat_start'],6);
        $lat_end		=number_format($point['lat_end'],6);
        if($lng_start < $longitude && $longitude <$lng_end && $lat_start < $latitude && $latitude < $lat_end){
            return true;
        }else{
            return false;
        }
    }


    /**
     *计算某个经纬度的周围某段距离的正方形的四个点
     *@param lng float 经度
     *@param lat float 纬度
     *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米1
     *@return array 正方形的四个点的经纬度坐标
     */
    private function getSquarePoint($lng, $lat, $distance){
        $earthRadius = 6371; //地球半径，平均半径为6371km
        $d_lng = 2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
        $d_lng = rad2deg($d_lng);
        $d_lat = $distance / $earthRadius;
        $d_lat = rad2deg($d_lat);
        return [
            'lng_start' => $lng - $d_lng,//经度开始
            'lng_end' => $lng + $d_lng, //经度结束
            'lat_start' => $lat - $d_lat,//纬度开始
            'lat_end' => $lat + $d_lat,//纬度结束
        ];
    }



}