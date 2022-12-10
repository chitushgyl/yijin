<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/7/4
 * Time: 16:26
 */
namespace App\Facades;
use Illuminate\Support\Facades\Facade;
class Map extends Facade{
    public static function getFacadeAccessor(){
        return 'Map';
    }
}
