<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/7/2
 * Time: 15:23
 */

namespace app\api\helper;


class ApiReturn
{
    /**
     * @param $msg
     * @param $data
     * @return array
     */
    public static function success($msg, $data = null,$count = 0)
    {
        return [
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'count' => $count
        ];
    }

    /**
     * @param $msg
     * @param int $code
     * @return array
     */
    public static function error($msg, $code = -1)
    {
        return [
            'code' => $code,
            'msg' => $msg
        ];
    }

    /**
     * @param $msg
     * @param int $code
     *
     */
    public static function throws($msg,$code=-1) {
        header('Content-Type:application/json; charset=utf-8;');
        $json =  json_encode((object)['msg'=>$msg,'code'=>$code]);
        exit($json);
    }

    /**
     * 数组验证，去除空键值
     * @param $arr
     * @param bool $trim
     * @return array|bool
     */
    public static  function array_remove_empty(&$arr, $trim = true) {
        if (!is_array($arr)) return false;
        foreach($arr as $key => $value){
            if (is_array($value)) {
                self::array_remove_empty($arr[$key]);
            } else {
                //$value = ($trim == true) ? trim($value) : $value;
                if ($value == "") {
                    unset($arr[$key]);
                } else {
                    $arr[$key] = $value;
                }
            }
        }
        return $arr;
    }
}