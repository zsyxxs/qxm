<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


function code($text,$logo = ''){
    //二维码图片保存路径
    $saveName = 'public/uploads/qrcode/qrcode_'. time() . ".png";

    vendor("phpqrcode.phpqrcode");

//二维码图片保存路径(若不生成文件则设置为false)
    $filename = ROOT_PATH .$saveName;
//二维码容错率，默认L
    $level = "L";
//二维码图片每个黑点的像素，默认4
    $size = '10';
//二维码边框的间距，默认2
    $padding = 2;
//保存二维码图片并显示出来，$filename必须传递文件路径
    $saveandprint = true;

//生成二维码图片
    $QRcode = new \QRcode();
    $QRcode->png($text,$filename,$level,$size,$padding,$saveandprint);

//二维码logo
    $QR = imagecreatefromstring(file_get_contents($filename));
    if(!empty($logo)){
        //二维码中间加入logo
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, 1, 1);
        imagepng($QR,$filename);
        imagedestroy($QR);
    }

    $saveName = str_replace('public/','',$saveName);
    return $saveName;
}

//阶乘
function factorial($n){
    return array_product(range(1, $n));
}

//阶乘的组合
function getFactorial($arr, $list=array()) {
    shuffle($arr);
    $a = $arr;
    $a = implode(",", $a);
    if($list){
        if(!in_array($a, $list)){
            $list[] = $a;
        }
    }else{
        $list[] = $a;
    }
    $n = count($arr);
    $num = count($list);
    if(factorial($n)>$num) return getFactorial($arr, $list);
    sort($list);
    return $list;
}

//aes cbc 128 解密
function aesEn($str){
    $aes = new \encrypt\AesEncrypt('1234567890abcdef');
    return $aes->encrypt($str);
}

//aes cbc 128 解密
function aesDe($str){
    $aes = new \encrypt\AesEncrypt('1234567890abcdef');
    return $aes->decrypt($str);
}


