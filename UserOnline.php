<?php

class UserOnline{
    public $redisObj = null;
    public $status = [0,1];
    public function __construct()
    {
        $this->redisObj = new Redis();
        $this->redisObj->connect('127.0.0.1', 6379);

    }

    /**
     * 记录用户登录上下线时间
     * @return bool
     */
    public function getOnlineStatus(){
//        获取参数
        $uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
        $status = isset($_GET['status']) ? ( in_array($_GET['status'],$this->status) ? intval($_GET['status']) : FALSE ) : FALSE;
        
//        组装键名
        $key = date('H',time());
        $key = $key.'-'.($key+1);

//        验证参数
        if (!$uid || $status === FALSE){
            return false;
        }

//        查询缓存是否有记录
        $isSave = $this->redisObj->hGet($key,$uid);

//        如果没缓存
        if (!$isSave){
            if ($status == 1){
//                status=1，记录上线时间、默认次数为0
                $data = [
                    'online_time0'=>time(),
                    'times'=>0
                ];
            }else{
//                status=0时，记录上线时间为整点，下线时间为now，更改times为1，代表一次完整上下线
                $data = [
                    'online_time0'=>strtotime(date('Y-m-d H:00:00')),
                    'offline_time0' => time(),
                    'times'=>1
                ];
            }

        }else{
//            有缓存，将json转为数组
            $data = json_decode($isSave,true);
            
//            获取times值
            $times = isset($data['times']) ? $data['times'] : false;

//            如果times值为0
            if (!$times){
//                有缓存，times值为0时，代表已登录，status=1时不做任何操作
//                status=0时，添加下线时间，更改times值
                if ($status == 0){
                    $data['offline_time0'] = time();
                    $data['times'] = 1;
                }

            }else{
//                status=1、记录第times次上线时间
                if ($status == 1){
                    if (!isset($data['online_time'.$times])){
                        $data['online_time'.$times] = time();
                    }
                }else{
//                    status=0、存在第times次上线时间则添加下线时间，否则更新下线时间
                    if (isset($data['online_time'.$times])){
//                        记录第times次下线时间，times+1
                        $data['offline_time'.$times] = time();
                        $data['times'] = $times+1;
                    }else{
//                        更新下线时间
                        $data['offline_time'.($times-1)] = time();
                    }
                }
            }
            
        }

//        更新redis数据
        $result = $this->redisObj->hSet($key,$uid,json_encode($data));
        if (!$result){
            return false;
        }
        return true;

    }
}

(new UserOnline())->getOnlineStatus();