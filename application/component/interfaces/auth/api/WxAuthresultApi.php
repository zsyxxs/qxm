<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/21
 * Time: 19:31
 */

namespace app\component\interfaces\auth\api;



use app\api\helper\ApiReturn;
use app\component\logic\UserLogic;

class WxAuthresultApi extends WxAuth
{

    /**
     * 请求获取code
     * @return array|string
     */
    public function oauth()
    {
        $config = [
            'appid' => $this->appId,
            'redirect_uri' => urlEncode ($this->redirectUri),
            'response_type' => $this->responseType,
            'scope' => $this->scope,
            'state' => $this->state,
        ];
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        $result = (new WxAuthvalueApi())->config($url,$config);
        return ApiReturn::success('成功',$result);
    }

    /**
     * @param $data
     * @return array|bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userInfo($data)
    {
        $config = [
            'appid' => $this->appId,
            'secret' => $this->appsecret,
            'code' => $data['code'],
            'grant_type' => $this->grantType
        ];
        //获取access_token
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
        $result = (new WxAuthvalueApi())->get_result_get($url,$config);
        $result = json_decode($result,true);
        if( isset($result['errcode']) && $result['errcode'] == '40029'){
            return ApiReturn::error($result['errmsg']);
        }
        //拉去用户信息
        $config = [
            'access_token' => $result['access_token'],
            'openid' => $result['openid'],
            'lang' => 'zh_CN'
        ];
        $url = 'https://api.weixin.qq.com/sns/userinfo?';
        $result = (new WxAuthvalueApi())->get_result_get($url,$config);
        $result = json_decode($result,true);
        if( isset($result['errcode']) && $result['errcode'] == '40003'){
            return ApiReturn::error($result['errmsg']);
        }

        //将用户信息注册到用户表中
        $data = [
            'openid' => $result['openid'],
            'username' => $result['nickname'],
            'province' => $result['province'],
            'city' => $result['city'],
            'country' => $result['country'],
            'logo' => $result['headimgurl'],
            'sex' => $result['sex'] //1：男性 2：女性 0：未知
        ];
        if(isset($result['unionid'])){
            $data['unionid'] = $result['unionid'];
        }
        $res = (new UserLogic())->register($data);

        return $res;
    }


























}
