<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/19
 * Time: 13:50
 */
namespace App\Models\School;
use  Illuminate\Database\Eloquent\Model;
class SchoolMessage extends Model{
    public $timestamps = false;
    protected $table = 'school_message';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
}