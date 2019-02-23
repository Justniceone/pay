# pay
小程序支付

  获取appid，appsecret，mch_id已经支付加密key,登录小程序后台确保小程序已经开通关联商户。否则申请下单会报系统错误
  
  基本使用
````
public function pay()
{
  $config = [
            //必填---------
            //小程序appid
            'appid' => '',
            //商户号
            'mch_id' => '',
            //支付回调地址
            'notify_url' => 'https:/xxxx/info',
            //下单用户openid
            'openid' => '',
            //支付32位密钥
            'key' => '',
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
        //申请下单只用一步
        $array = MiniPay::unifiedorder($config);
        return json_encode($array);
    }
        
       //回调通知
    public function info()
    {
        $post_xml = file_get_contents('php://input');
        //加密密钥
        $key = '';
        //验签
        $result = MiniPay::check($post_xml, $key);
        if ($result) {
            //处理逻辑
            echo 'success';die;
            MiniPay::return_success();
        }
        //todo 记录日志
        return json('验签失败');
    }

````
