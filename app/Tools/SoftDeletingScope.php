<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/9
 * Time: 16:29
 */
namespace App\Tools;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class SoftDeletingScope implements Scope
{
    /**
     * 把约束加到 Eloquent 查询构造中.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('is_deleted', '=', 0);
    }
}