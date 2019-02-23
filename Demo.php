<?php

namespace app\index\controller;

use app\lib\MiniPay;

class Demo extends Controller
{
	//下单
    public function pay()
    {
        $config = [
            //必填---------
            //小程序appid
            'appid' => 'wxf2095e9e90b61d19',
            //商户号
            'mch_id' => '1504272751',
            //支付回调地址
            'notify_url' => 'https://m.bingboo.cn/fuel/pay/info',
            //下单用户openid
            'openid' => 'oJm6p5QlKGB9HHbC1KSA1AL48uEo',
            //支付32位密钥
            'key' => 'ixXLggKHIwgSor9uBhjpSjrQeat0THYK',
            //--------------

            //非必填,如需修改则填入即可------------
            //需要登录必填
            'appsecret' => '',
            //充值描述
            'body' => '走一个-充值',
            //随机字符串32位以内
            'nonce_str' => '',
            //订单号32位以内
            'out_trade_no' => '',
            //下单ip
            'spbill_create_ip' => '',
            //付款金额单位分
            'total_fee' => 1
            //------------------
        ];
        //用户登录后可缓存openid
        $openid = input('openid');
        //登录获取
        $code = input('code');
        $data = MiniPay::login($code, $config['appid'], $config['appsecret']);
        $openid = $data['openid'];
        $config['openid'] = $openid;
        $array = MiniPay::unifiedorder($config);
        return json_encode($array);
    }

    //回调通知
    public function info()
    {
        $post_xml = file_get_contents('php://input');
        $key = 'ixXLggKHIwgSor9uBhjpSjrQeat0THYK';
        $result = MiniPay::check($post_xml, $key);
        if ($result) {
            //处理逻辑
            echo 'success';die;
            MiniPay::return_success();
        }
        return json('验签失败');
    }
}