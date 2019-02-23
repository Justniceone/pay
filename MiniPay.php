<?php

namespace app\lib;

class MiniPay
{
    /**
     * @var array $config 支付配置
     */
    protected static $config;
    /**
     * @var string $unifiedorderurl 下单地址
     */
    protected static $unifiedorderurl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    protected static $required = ['appid', 'mch_id', 'notify_url', 'openid', 'key'];

    public static function unifiedorder(array $config)
    {
        if ($config) self::$config = $config;

        foreach (self::$required as $k => $v) {
            if (!array_key_exists($v, self::$config) || !self::$config[$v]) {
                throw new \Exception($v . '未设置');
            }
        }

        $post_xml = self::xml();
        $result_xml = self::request(self::$unifiedorderurl, $post_xml);
        $array = self::xml_to_array($result_xml);
        if ($array['return_code'] == 'FAIL') {
            $response['state'] = 0;
            $response['text'] = "错误";
            $response['return_code'] = $array['return_code'];
            $response['return_msg'] = $array['return_msg'];
            return $response;
        }
        if ($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS') {
            $time = time();
            $tmp = [];//临时数组用于签名
            $tmp['appId'] = self::$config['appid'];
            $tmp['nonceStr'] = self::$config['nonce_str'];
            $tmp['package'] = 'prepay_id=' . $array['prepay_id'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";

            $data['state'] = 200;
            $data['timeStamp'] = "$time";//时间戳
            $data['nonceStr'] = self::$config['nonce_str'];//随机字符串
            $data['signType'] = 'MD5';//签名算法，暂支持 MD5
            $data['package'] = 'prepay_id=' . $array['prepay_id'];//统一下单接口返回的 prepay_id 参数值，提交格式如：prepay_id=*
            $data['paySign'] = self::sign($tmp);//签名,具体签名方案参见微信公众号支付帮助文档;
            $data['out_trade_no'] = self::$config['out_trade_no'];
            return $data;
        }
    }

    public static function set(array $config)
    {
        self::$config = $config;
    }

    /**
     * 生成xml数据下单
     * @return string
     * @throws \Exception
     */
    protected static function xml()
    {
        //小程序appid
        $post['appid'] = self::$config['appid'];
        $post['body'] = isset(self::$config['body']) && self::$config['body'] ? self::$config['body'] : '测试支付-商品';
        $post['mch_id'] = self::$config['mch_id'];
        $post['nonce_str'] = isset(self::$config['nonce_str']) && self::$config['nonce_str'] ? self::$config['nonce_str'] : self::nonce_str();//随机字符串
        $post['notify_url'] = self::$config['notify_url'];
        $post['openid'] = self::$config['openid'];;
        $post['out_trade_no'] = isset(self::$config['out_trade_no']) && self::$config['out_trade_no'] ? self::$config['out_trade_no'] : self::out_trade_no();
        $post['spbill_create_ip'] = isset(self::$config['spbill_create_ip']) && self::$config['spbill_create_ip'] ? self::$config['spbill_create_ip'] : '127.0.0.1';//终端的ip
        $post['total_fee'] = isset(self::$config['total_fee']) && self::$config['total_fee'] ? self::$config['total_fee'] : 1;//总金额 
        $post['trade_type'] = 'JSAPI';

        $sign = self::sign($post);

        return '<xml>
             <appid>' . $post['appid'] . '</appid>
             <body>' . $post['body'] . '</body>
             <mch_id>' . $post['mch_id'] . '</mch_id>
             <nonce_str>' . $post['nonce_str'] . '</nonce_str>
             <notify_url>' . $post['notify_url'] . '</notify_url>
             <openid>' . $post['openid'] . '</openid>
             <out_trade_no>' . $post['out_trade_no'] . '</out_trade_no>
             <spbill_create_ip>' . $post['spbill_create_ip'] . '</spbill_create_ip>
             <total_fee>' . $post['total_fee'] . '</total_fee>
             <trade_type>' . $post['trade_type'] . '</trade_type>
             <sign>' . $sign . '</sign>
          </xml>';
    }

    /**
     * 生成签名
     * @param array $data 要发送的数据
     * @param null $skey 支付密钥
     * @return string
     * @throws \Exception
     */
    private static function sign(array $data, $skey = null)
    {
        $stringA = '';
        foreach ($data as $key => $value) {
            if (!$value) continue;
            if ($stringA) $stringA .= '&' . $key . "=" . $value;
            else $stringA = $key . "=" . $value;
        }
        //商户后台-账户中心-账户设置-API安全-API密钥设置32位key
        $wx_key = $skey ?: self::$config['key'];
        if (!$wx_key) throw new \Exception('支付key没有设置');
        $stringSignTemp = $stringA . '&key=' . $wx_key;
        return strtoupper(md5($stringSignTemp));
    }

    /**
     * 生成随机字符串
     * @return string
     */
    private static function nonce_str()
    {
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i = 0; $i < 32; $i++) {
            $result .= $str[rand(0, 48)];
        }
        self::$config['nonce_str'] = $result;
        return $result;
    }

    /**
     * 生成商户订单号
     * @return string
     */
    private static function out_trade_no()
    {
        $no = date('YmdHis') . mt_rand(100000, 999999);//20位
        self::$config['out_trade_no'] = $no;
        return $no;
    }

    /**
     * 发送post请求
     * @param $url
     * @param null $data
     * @param array $headers
     * @return bool|string
     */
    private static function request($url, $data = null, $headers = array())
    {
        $curl = curl_init();
        if (count($headers) >= 1) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * xml转数组
     * @param $xml
     * @return bool|mixed
     */
    public static function xml_to_array($xml)
    {
        if (!$xml) return false;
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /**
     * 返回success的xml数据
     */
    public static function return_success()
    {
        $return['return_code'] = 'SUCCESS';
        $return['return_msg'] = 'OK';
        $xml_post = '<xml>
                    <return_code>' . $return['return_code'] . '</return_code>
                    <return_msg>' . $return['return_msg'] . '</return_msg>
                    </xml>';
        echo $xml_post;
        exit;
    }

    /**
     * 验签
     * @param $xml
     * @param $key
     * @return bool
     * @throws \Exception
     */
    public static function check($xml, $key)
    {
        $post_data = self::xml_to_array($xml);
        ksort($post_data);// 对数据进行排序
        $post_sign = $post_data['sign'];
        //sign不参与签名需要unset掉
        unset($post_data['sign']);
        $sign = self::sign($post_data, $key);
        if ($sign != $post_sign) return false;
        return true;
    }

    public static function login($code, $appid, $appsecret)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid;
        $url = $url . "&secret=" . $appsecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $result = self::curl($url);
        $array = json_decode($result, true);
        if (isset($array['openid'])) return $array;
        throw new \Exception('获取用户openid失败，请检查appid和appsecret');
    }

    public function curl($url)
    {
        $info = curl_init();
        curl_setopt($info, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($info, CURLOPT_HEADER, 0);
        curl_setopt($info, CURLOPT_NOBODY, 0);
        curl_setopt($info, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info, CURLOPT_URL, $url);
        $output = curl_exec($info);
        curl_close($info);
        return $output;
    }
}