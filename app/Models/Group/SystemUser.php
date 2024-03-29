<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/4/21
 * Time: 23:13
 */
namespace App\Models\Group;
use  Illuminate\Database\Eloquent\Model;

class SystemUser extends Model{
    //软删除
    //   use SoftDeletes;
    //模型的连接名称（单独连接其他数据库）
    //protected $connection = 'connection-name';

    //指定数据库表 如果表名后带s则不需要 不带则需要
    protected $table = 'system_user';

    //指定主键字段 默认为id
    //protected $primaryKey = 'id';

    //删除时间字段定义 自定义命名
    //const DELETED_AT = 'updated_at';
    //创建时间字段定义 自定义命名
    const CREATED_AT = 'create_time';
    //更新时间字段定义 自定义命名
    const UPDATED_AT = 'update_time';

    //是否被自动维护时间戳（自动更新created_at/updated_at字段）
    public $timestamps = false;

    //模型的日期字段的存储格式，具体指参考下方：php 时间字母含义
    //protected $dateFormat = 'U';

    //自增ID默认为true
    //public $incrementing=false;

    //默认分页数量
    //protected $perPage = 15;

    //隐藏字段
    //protected $hidden = ['id'];

    protected $fillable = [
        'id'
    ];

    protected $guarded=[
        // 'id'
        //写进去的字段不被注入
    ];

    public function SystemSection(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasOne('App\Models\Group\SystemSection','self_id','department');
    }

    public function tmsOrder(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasMany('App\Models\Tms\TmsOrder','driver_id','self_id');
    }

    public function driverCommission(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasMany('App\Models\Tms\DriverCommission','driver_id','self_id');
    }

    public function userExamine(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasMany('App\Models\User\UserExamine','user_id','self_id');
    }

    public function awardRemind(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasMany('App\Models\Tms\AwardRemind','user_id','self_id');
    }
    public function userReward(){
        //参数：关联模型名称，外键，主键
        //如果主键是id可以省略
        return $this->hasMany('App\Models\User\UserReward','user_id','self_id');
    }






}
