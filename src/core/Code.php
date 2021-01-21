<?php
/**
 *
 * Author HanSheng
 */
namespace longdaihai\tencenim\core;

class IM_Code_Code
{

    public static function getInfo(int $code):string
    {
        $str = '';
        switch ($code){
            case 10015:
                $str = '群主id有误或不存在';
                break;
        }
        return $str;
    }
}