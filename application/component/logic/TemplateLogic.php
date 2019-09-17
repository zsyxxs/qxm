<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;


use app\api\helper\ApiReturn;
use app\component\interfaces\weixin\api\WxresultApi;
use app\component\model\BannersModel;
use think\Db;

class TemplateLogic  extends BaseLogic
{
    protected $task_template_id = 'icG5vz61YQj6BL7_5OWuJNp5_9Ox967Nb6qbUudca3A';
    protected $task_first = '你收到一条牵线任务，请48小时内完成。';
    protected $task_deacription = '加好友一对一“发送教程”；指导学生提炼总结300字自我介绍并录音；完成教学后学生会给你相应评价后领红包。';

    protected $dynamic_template_id = 'OybNotWPGiUCTwL586aWJclP2WkExIpMJQNiQD-L7Gs';
    protected $dynamic_first = '恭喜上头条，你的自我介绍语音被人点赞啦！';
    protected $dynamic_deacription = '已为你点赞，若有人灭灯则下架喵圈。';

    /**
     * 发送任务模板消息
     * @param $uid
     * @param $url
     * @param $appid
     * @return array
     */
    public function sendTaskTemplate($uid,$url,$appid)
    {
        $first = $this->task_first;
        $template_id = $this->task_template_id;
        $description = $this->task_deacription;
        $userInfo = (new UserLogic())->getInfo(['id' => $uid],false,'id,username,p_id,openid,unionid,flag_like');
        $parentInfo = (new UserLogic())->getInfo(['id'=>$userInfo['p_id']],false,'id,username,openid,unionid');

        $flags_like = explode(',',$userInfo['flag_like']);
        $flags = (new FlagsLogic())->getLists(['id'=>array('in',$flags_like)],false,'id,title');
        $title = [];
        foreach ($flags as $k => $v){
            $title[] = $v['title'];
        }
        $title = implode("  ", $title);
        $taskInfo = (new TaskLogic())->getInfo(['p_id'=>$parentInfo['id'],'uid'=>$userInfo['id']],false,'id,create_time,update_time');

        $keyword2 = '《帮助  '.$userInfo['username'].'  完成自我介绍语音》';
        $res = $this->getData($parentInfo['openid'],$template_id,$url,$appid,$first,$taskInfo['create_time'],$keyword2,$title,$description);
        return $res;
    }

    /**
     * 发送动态点赞模板消息
     * @param $uid
     * @param $url
     * @param $appid
     * @param $point_uid
     * @param $t_id
     * @return array
     */
    public function sendDynamicTemplate($uid,$url,$appid,$point_uid,$t_id)
    {
        $first = $this->dynamic_first;
        $template_id = $this->dynamic_template_id;

        $pointInfo = (new UserLogic())->getInfo(['id'=>$point_uid],false,'id,username');
        $description = $pointInfo['username'].$this->dynamic_deacription;

        $userInfo = (new UserLogic())->getInfo(['id' => $uid],false,'id,username,p_id,openid,unionid,flag_like');

        $taskInfo = (new TaskLogic())->getInfo(['id'=>$t_id],false,'id,create_time,update_time');


        $keyword1 = '自我介绍语音';
        $keyword2 = $taskInfo['update_time'];
        $keyword3 = '牵线喵喵圈';

        $res = $this->getData($userInfo['openid'],$template_id,$url,$appid,$first,$keyword1,$keyword2,$keyword3,$description);
        return $res;
    }

    /**
     * 给游客发送模板消息
     * @param $a_uid
     * @param $url
     * @param $appid
     * @param $username
     * @param $parentname
     * @param $types 默认1 语音 2 文字
     * @return array
     */
    public function sendAssessTemplate($a_uid,$url,$appid,$username,$parentname,$types=1)
    {
        $first = '现在有人想认识你哦';
        $template_id = 'H_Aa7eIwfPPf9U4Fqu_phq4cbOk89EEKYSx-frBsDuQ';

        $description = '进入主页点击围观，认真听完TA的自我介绍，可点赞或灭灯哦';

        //游客
        $userInfo = (new UserLogic())->getInfo(['id' => $a_uid],false,'id,username,p_id,openid,unionid,flag_like');

        $keyword1 = $username.'的自我介绍';
//        $keyword2 = '牵线喵语音自我介绍培训班';
//        $keyword3 = $parentname;


        $res = $this->getData($userInfo['openid'],$template_id,$url,$appid,$first,$keyword1,$description);
        return $res;
    }

    public function getData($openid,$template_id,$url,$appid,$first = '',$keyword1 = '',$keyword2 = '',$keyword3 = '',$keyword4 = '')
    {
        $data = [
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => $url,
            'appid' => $appid,
//            'pagepath' => '',
            'data' => [
                "first" => [
                    'value' => $first,
                    'color' => '#173177'
                ],
                'keyword1' => [
                    'value' =>$keyword1,
                    "color" => "#173177"
                ],
                'keyword2' => [
                    'value' => $keyword2,
                    "color" => "#173177"
                ],
                'keyword3' => [
                    'value' => $keyword3,
                    "color" => "#173177"
                ],
                'keyword4' => [
                    'value' => $keyword4,
                    "color" => "#173177"
                ]
            ]
        ];

        $res = (new WxresultApi())->sendTemplate($data);
        if($res['errcode'] == '0' && $res['errmsg'] == 'ok'){
            return ApiReturn::success('success');
        }else{
            return ApiReturn::error('fail');
        }
    }

}