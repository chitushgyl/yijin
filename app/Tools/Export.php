<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/8/19
 * Time: 13:30
 */
namespace App\Tools;
use Maatwebsite\Excel\Concerns\FromCollection;
class Export implements FromCollection
{
    private $row;
    private $data;

    public function __construct($row,$data)
    {
        $this->row = $row;
        $this->data = $data;
    }

    public function collection()
    {
        $row = $this->row;
        $data = $this->data;

        //设置表头
        foreach ($row[0] as $key => $value) {
            $key_arr[] = $key;
        }

        //输入数据
        foreach ($data as $key => &$value) {
            $js = [];
            for ($i=0; $i < count($key_arr); $i++) {
                $js = array_merge($js,[ $key_arr[$i] => $value[ $key_arr[$i] ] ]);
            }
            array_push($row, $js);
            unset($val);
        }
        return collect($row);
    }
}