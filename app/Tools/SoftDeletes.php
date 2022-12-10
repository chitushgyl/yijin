<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/9
 * Time: 16:31
 */
namespace App\Tools;
trait SoftDeletes
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
        //print_r(666);exit;
    }
    /**
     * Get the name of the "deleted at" column.
     * 返回软删除的标记字段
     * @return string
     */
    public function getDeletedAtColumn()
    {
       // print_r(555);exit;
        return 'is_deleted';
    }
    /**
     * Get the fully qualified "deleted at" column.
     * 获取删除字段名称，qualifyColumn会只能补充表的名称
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        print_r(444);exit;
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        print_r(333);exit;
        return $this->runSoftDelete();
    }
    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        print_r(222);exit;
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());
        $columns = [$this->getDeletedAtColumn() => 1];
        if ($query->update($columns)) {
            $this->syncOriginal();
        }
    }
}