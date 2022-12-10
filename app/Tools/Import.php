<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/18
 * Time: 14:43
 */
namespace App\Tools;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
class Import implements ToArray
{
//    private $rows = 0;
    public function Array(Array $rows)
    {
//        ++$this->rows;
        return $rows;
    }

//
//
//    public function model(array $row)
//    {
//
//
//        return new SchoolInfo([
//            'self_id' => $row[0],
//        ]);
//    }
//
//    public function getRowCount(): int
//    {
//        return $this->rows;
//    }
}