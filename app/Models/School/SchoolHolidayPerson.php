<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/19
 * Time: 13:50
 */
namespace App\Models\School;
use  Illuminate\Database\Eloquent\Model;
class SchoolHolidayPerson extends Model{
    public $timestamps = false;
    protected $table = 'school_holiday_person';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';


    public function schoolHoliday(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasOne('App\Models\School\SchoolHoliday','self_id','holiday_id');
    }
}