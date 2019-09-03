<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;





use app\component\model\TokenModel;

class TokenLogic  extends BaseLogic
{

    public function saveInfo($data)
    {
        $data['expires_in'] = time() + $data['expires_in'];//有效期实为7200秒
        $model = new TokenModel();
        $model->save($data);
        return $data['token'];
    }

}
