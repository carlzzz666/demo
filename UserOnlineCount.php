<?php


class UserOnlineCount
{
    public $redisObj = null;
    public $checkTime = 60;

    public function __construct()
    {
        $this->redisObj = new Redis();
        $this->redisObj->connect('127.0.0.1', 6379);
    }

    /**
     * 定时任务，每小时执行，统计上一小时在线超过一分钟人数
     * @return bool
     */
    public function getOnlineCount(){
        $key = date('H',time()) == 0 ? 24 : date('H',time());

        $key = ($key-1).'-'.$key;

        $data = $this->redisObj->hGetAll($key);
        $userCount = 0;

        foreach ($data as $k => &$v){
            $v = json_decode($v,true);
//            如果没有退出时间，添加退出时间为当前时间整点时间戳
            $count = count($v);
            if (($count%2) == 0){
                $v['offline_time'.$v['times']] = strtotime(date('Y-m-d H:00:00'));
                $v['times'] = $v['times']+1;
            }

            $sum = 0;
            for ($i=0;$i<$v['times'];$i++){

                $sum += ($v['offline_time'.$i])-($v['online_time'.$i]);

                if ($sum > $this->checkTime){
                    ++$userCount;
                    break;
                }

            }

        }

//        记录每小时在线超过一分钟人数
        $result = $this->redisObj->hset('onlineCount',$key,$userCount);
        if (!$result){
            return false;
        }
        return true;
    }

}

(new UserOnlineCount())->getOnlineCount();