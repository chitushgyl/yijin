<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/19
 * Time: 13:50
 */
namespace App\Models\School;
use  Illuminate\Database\Eloquent\Model;
class SchoolHoliday extends Model{
    public $timestamps = false;
    protected $table = 'school_holiday';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public function schoolPathwayPerson(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasOne('App\Models\School\SchoolPathwayPerson','person_id','person_id');
    }

    //一对多
    public function SchoolHolidayPerson(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasMany('App\Models\School\SchoolHolidayPerson','holiday_id','self_id');
    }
}