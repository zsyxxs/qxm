<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/15
 * Time: 10:06
 */
namespace app\component\logic;

use app\api\helper\ApiReturn;
use app\component\model\AgentModel;
use think\Db;

class BaseLogic
{
    protected $model;

    public function __construct($initModel = true )
    {
        if($initModel) {
            $className = str_replace("Logic","",get_class($this));
            $className = str_replace("logic","model",$className);
            $className .= "Model";
            if(class_exists($className)) {
                $this->model = new $className;
            }

        }
    }



    /**
     * 求和统计
     * @param $map
     * @param $field
     * @return mixed
     */
    public function sum($map,$field)
    {
        return $this->model->where($map)->sum($field);
    }

    /**
     * 数量统计
     * @param $map
     * @param bool $field
     * @return int|string
     */
    public function count($map, $field = false)
    {
        if ($field === false) {
            $result = $this->model->where($map)->count();
        } else {
            $result = $this->model->where($map)->count($field);
        }
        return $result;
    }

    public function get_token($data)
    {
        $data = [
            'phone' => $data['phone'],
            'password' => md5($data['password']),
            'timestamp' => time()
        ];
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k.$v;
        }
        $token =  strtoupper(MD5($str));
        return $token;

    }

    /**
     * 生成订单编号
     * @return string
     */
    public function order_num()
    {
//        $order_num = 'qxm'.$this->getRandomString(6).date('YmdHis',time());
        $order_num = 'qxm'.date('Ymd').substr(time(),-5).substr(microtime(),2,5).sprintf('%02d',mt_rand(1000,9999));
        return $order_num;
    }

    function getRandomString($len, $chars=null)
    {
        if (is_null($chars)){
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * 生成普通邀请码
     * @param $uid
     * @return string
     */
    public function createCode($uid)
    {
        $code = 'qxm'.rand(1000,9999).$uid;
        return $code;

    }

    /**
     * 生成免费验证码
     * @param $uid
     * @return string
     */
    public function createFreeCode($uid)
    {
        $code = 'qxmf'.rand(1000,9999).$uid;
        return $code;
    }


    /**
     * 检验传递的参数与要求的参数是否一致
     * 检验数据是否为空
     * 检验每个参数的值是否为空
     * @param $field
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkFields($field,$data)
    {
        if(empty($data)){
            return ApiReturn::error('数据为空');
        }
        $newField = [];
        foreach ($data as $k => $v){
            if($v === '' && in_array($k,$field)){
                return ApiReturn::error($k.'参数值为空');//参数为空
            }
            array_push($newField,$k);
        }

        if(array_diff($field,$newField)) {
            return ApiReturn::error('缺少参数');//缺少参数
        }else{
            if(isset($data['token']) && isset($data['uid'])){
                //判断登录用户token是否正确
                $token = Db::table('user')->where(['id' =>$data['uid'] ,'token' => $data['token']])->find();
                if(empty($token)){
                    return ApiReturn::error('登录token错误');
                }
            }
            return ENABLE;
        }
    }


    /**
     * 新增一条记录或更新数据
     * @param $data
     * @return mixed
     */
    public function save($data,$where = [])
    {
        if(!empty($where)){
            //更新
            return $this->getModel()->allowfield(true)->save($data,$where);
        }
        return $this->getModel()->allowfield(true)->save($data);
    }

    /**
     * 批量添加数据
     * @param $data
     * @return mixed
     */
    public function saveAll($data)
    {
        return $this->getModel()->saveAll($data);
    }

    public function getInsertId($data)
    {
        $model = $this->getModel();
        $model->allowfield(true)->save($data);
        return $model->id;
    }

    /**
     * 根据条件删除
     * @param $where
     * @return mixed
     */
    public function delete($where)
    {
        return $this->getModel()->where($where)->delete();
    }

    /**
     * 根据条件获取单条数据
     * @param $map
     * @param bool $order
     * @param bool $field
     * @return mixed
     */
    public function getInfo($map = [], $order = false, $field = false)
    {
        $query = $this->model;

        if (false !== $order) {
            $query = $query->order($order);
        }

        if (false !== $field) {
            $query = $query->field($field);
        }

        if(!empty($map)){
            $query = $query->where($map);
        }

        $result = $query->find();

        return $result;
    }

    /**
     * 根据条件获取多条数据(不分页)
     * @param $map
     * @param bool $order
     * @param bool $field
     * @return mixed
     */
    public function getLists($map = [], $order = false, $field = false)
    {
        $query = $this->model;

        if (false !== $order) {
            $query = $query->order($order);
        }

        if (false !== $field) {
            $query = $query->field($field);
        }

        if(!empty($map)){
            $query = $query->where($map);
        }

        $result = $query->select();

        return $result;
    }

    /**
     * 根据条件获取多条数据(不分页)
     * @param $map
     * @param bool $order
     * @param bool $field
     * @return mixed
     */
    public function column($field, $map = [])
    {
        $query = $this->model;

        if(!empty($map)) {
            $query = $query -> where($map);
        }

        $result = $query ->column($field);
        return $result;
    }

    /**
     * 根据条件分页(不带HTML)
     * @param array $map
     * @param bool $orderBy
     * @param bool $field
     * @param int $pageNo
     * @param int $pagesize
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function queryPage($map =[] , $order = false , $field = false,$pageNo =1 ,$pagesize = 20)
    {
        $query = $this->model;

        if (false !== $order) {
            $query = $query->order($order);
        }

        if (false !== $field) {
            $query = $query->field($field);
        }

        if(!empty($map)) {
            $query = $query -> where($map);
        }

        $offset = $this->getOffset($pageNo,$pagesize);
        $result = $query ->limit($offset,$pagesize)->select();

        return $result;

    }

    /**
     * 根据条件分页查询，带HTML后台页面
     * @param array $map
     * @param bool $order
     * @param bool $field
     * @param int $pagesize
     * @param int $pageNo
     * @return array
     */
    public function queryPageHtml($map = [], $order = false , $field = false ,$pagesize = 20,$pageNo =1)
    {
        $query = $this->model;

        if (false !== $order) {
            $query = $query->order($order);
        }

        if (false !== $field) {
            $query = $query->field($field);
        }

        if(!empty($map)) {
            $query = $query -> where($map);
        }

        $list = $query->paginate($pagesize);
        $page = $list->render();
        $count = $this->getModel()->where($map)->count();

        return ['list'=>$list,'count'=>$count,'page'=>$page];
    }



    /**
     * 数字类型字段有效
     * @param $map array 条件
     * @param $field string 更改字段
     * @param float|int $cnt float 增加的值
     * @return integer 返回影响记录数 或 错误信息
     * @throws Exception
     */
    public function setInc($map, $field, $cnt = 1)
    {

        return $this->model->where($map)->setInc($field, $cnt);
    }

    /**
     * 数字类型字段有效,不允许小于0,维护字段最小为0,金额等敏感类型不适用
     * @param $map array 条件
     * @param $field string 更改字段
     * @param $cnt int 减少的值
     * @return integer 返回影响记录数 或 错误信息
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setDecCantZero($map, $field, $cnt = 1)
    {
        $result = $this->model->where($map)->find()->toArray();

        if (!empty($result) && isset($result[$field])) {
            $fieldValue = $result[$field];
            if ($fieldValue - $cnt < 0) $cnt = $fieldValue;
        }

        return $this->setDec($map, $field, $cnt);

    }

    /**
     * 数字类型字段有效
     * @param $map array 条件
     * @param $field string 更改字段
     * @param $cnt int 减少的值 减少的值
     * @return integer|string 返回影响记录数 或 错误信息
     * @throws Exception
     */
    public function setDec($map, $field, $cnt = 1)
    {
        return $this->model->where($map)->setDec($field, $cnt);
    }



    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getOffset($pageNo,$pagesize)
    {
        $page = ($pageNo -1 )>0 ? ($pageNo -1) : 0;
        $offset = $page * $pagesize;
        return $offset;
    }


    public function getImgUrl($query)
    {
        $url = config('webUrl.apiUrl');
        foreach ($query as $k=>$v){
            $str = substr($v['primary_file_uri'],1);
            $query[$k]['img_url'] = $url.$str;
        }
        return $query;
    }

}