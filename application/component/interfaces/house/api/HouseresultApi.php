<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 14:30
 */

namespace app\component\interfaces\house\api;


class HouseresultApi extends HouseUrl
{
    /**
     * 房屋怕评估登录接口
     * @return bool|mixed
     */
    public function login()
    {
        $name = $this->userName;
        $pwd = $this->password;
        $url = $this->loginUrl;
        $data = '{"UserName": "'.$name.'","Password": "'.$pwd.'"}';
        $headers[]  =  "Content-Type:application/json";
        $result = (new HousevalueApi())->get_result_post($url,$data,$headers);
        return json_decode($result,true);
    }

    /**
     * 获取全部省份接口
     * @param $token
     * @return mixed
     */
    public function provinces($token)
    {
        $url = $this->provincesUrl;
//        $headers[]  = "Authorization: Basic ". $token;
        $headers[]  = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }

    /**
     * 获取城市接口
     * @param $token
     * @param string $id
     * @return mixed
     */
    public function citys($token,$id='')
    {
        $url = $this->citysUrl;
        if($id){
            $params = ['id'=>$id];
            $url = $this->getUrl($url,$params);
        }
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }

    /**
     * 获取行政区接口
     * @param $token
     * @param $id
     * @return mixed|string
     */
    public function areas($token,$id)
    {
        if(empty($id)){
            return '请填写城市id';
        }
        $params = [
            'id' => $id
        ];
        $url = $this->areasUrl;
        $url = $this->getUrl($url,$params);
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }

    /**
     * 获取楼盘列表接口
     * @param $token
     * @param $city
     * @param $name
     * @return mixed|string
     */
    public function construction($token,$city,$name)
    {
        if(empty($city) || empty($name)){
            return '缺少参数';
        }
        $params = [
            'city' => $city,
            'name' => urlencode($name),
        ];

        $url = $this->constructionUrl;
        $url = $this->getUrl($url,$params);
//        $headers[] = 'charset=utf-8';
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }

    /**
     * 根据楼盘id获取楼盘详情接口
     * @param $token
     * @param $coind
     * @return mixed|string
     */
    public function construtionViewInfoById($token,$coind)
    {
        if(empty($coind)){
            return '缺少参数';
        }
        $params = [
            'coind' => $coind
        ];
        $url = $this->construtionViewInfoByIdUrl;
        $url = $this->getUrl($url,$params);
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }

    /**
     * 根据楼盘id获取楼栋列表接口
     * @param $token
     * @param $id
     * @return mixed|string
     */
    public function build($token,$id)
    {
        if(empty($id)){
            return '缺少参数';
        }
        $params = ['id'=>$id];
        $url = $this->buildUrl;
        $url = $this->getUrl($url,$params);
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }

    public function house($token,$id)
    {
        if(empty($id)){
            return '缺少参数';
        }
        $params = ['id'=>$id];
        $url = $this->houseUrl;
        $url = $this->getUrl($url,$params);
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_get($url,$headers);
        return $result;
    }


    /**
     * 自动评估接口
     * @param $token
     * @param $divisionId
     * @param $buildArea
     * @param $caseId
     * @return bool|mixed
     */
    public function estateEvaluation($token,$data)
    {
        $params = json_encode($data);
        $url = $this->estateEvaluationUrl;
        $headers[]  =  "Content-Type:application/json;charset=utf-8";
        $headers[] = $this->getHeader($token);
        $result = (new HousevalueApi())->get_result_post($url,$params,$headers);
        return $result;
    }


}