<?php

namespace VisionShadow\MyCard;

use VisionShadow\MyCard\Traits\HasHttpRequest;
use VisionShadow\MyCard\Contracts\MyCardInterface;

use VisionShadow\MyCard\Exceptions\AuthCodeException;
use VisionShadow\MyCard\Exceptions\ConfirmException;
use VisionShadow\MyCard\Exceptions\QueryException;
use VisionShadow\MyCard\Exceptions\VerifyCardAndPasswordException;


class MyCard implements MyCardInterface
{
    use HasHttpRequest;

    //厂商秘钥
    protected $factory_key = '';

    //service_id
    protected $fac_service_id = '';

    //是否沙箱
    protected $is_sandbox = false;

    /**
     * 获取授权码的
     * 正式地址 ENDPOINT_AUTH
     * 沙箱地址 SANDBOX_ENDPOINT_AUTH
     */
    const ENDPOINT_AUTH = 'https://b2b.mycard520.com.tw/MyBillingPay/api/AuthGlobal';
    const SANDBOX_ENDPOINT_AUTH = 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/AuthGlobal';

    /**
     * 获取MyCard的跳转地址
     * 正式地址 ENDPOINT_REDIRECT
     * 沙箱地址 SANDBOX_ENDPOINT_REDIRECT
     */
    const ENDPOINT_REDIRECT = 'https://www.mycard520.com.tw/MyCardPay/';
    const SANDBOX_ENDPOINT_REDIRECT = 'https://test.mycard520.com.tw/MyCardPay/';

    /**
     * MyCard的卡号认证地址
     * 正式地址
     * 沙箱地址
     */
    const ENDPOINT_CARD = 'https://b2b.mycard520.com.tw/MyBillingPay/api/IngamePay';
    const SANDBOX_ENDPOINT_CARD = 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/IngamePay';

     /**
     * MyCard查询订单的地址
     * 正式地址
     * 沙箱地址
     */
    const ENDPOINT_QUERY = 'https://b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery';
    const SANDBOX_ENDPOINT_QUERY = 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/TradeQuery';

    /**
     * MyCard确认请款地址
     * DOC 3.4 確認 MyCard 交易，並進行請款
     */
    const SANDBOX_ENDPOINT_PAYMENT_CONFIRM = 'https://test.b2b.mycard520.com.tw/MyBillingPay/api/PaymentConfirm';
    const ENDPOINT_PAYMENT_CONFIRM = 'https://b2b.mycard520.com.tw/MyBillingPay/api/PaymentConfirm';

    /**
     * 初始化
     */
    public function __construct($fac_service_id, $factory_key, $is_sandbox)
    {
        $this->fac_service_id = $fac_service_id;
        $this->factory_key = $factory_key;
        $this->is_sandbox = (bool)$is_sandbox;
    }

    /**
     * [getAuthCode 获取支付授权码]
     * @doc 3.1
     * @datetime 2018-05-02T11:39:43+0800
     * @return   [type]                   [description]
     */
    public function getAuthCode($fac_trade_seq, $trade_type, $customer_id, $payment_type, $product_name, $amount, $currency_code='TWD')
    {
        // $this->is_sandbox = true;
        $fac_service_id = $this->fac_service_id;
        $factory_key = $this->factory_key;

        $parameter = [
            'FacServiceId' => $fac_service_id, //廠商服務代碼 由 MyCard 編列 测试环境参数Ginhi
            'FacTradeSeq' => $fac_trade_seq, //廠商交易序號 廠商自訂，每筆訂單編號不得重 覆，為訂單資料 key 值
            'TradeType' => $trade_type, //※交易模式 1:Android SDK (手遊適用) 2:WEB
            // 'ServerId' => '', //伺服器代號 用戶在廠商端的伺服器編號 不可輸入中文 僅允許 0-9a-zA-Z._- 非必填
            'CustomerId' => $customer_id, //會員代號 用戶在廠商端的會員唯一識別 編號僅允許 0-9a-zA-Z._-
            'PaymentType' => $payment_type, // 此參數非必填，參數為空時將依 交易金額(Amount)和幣別 (Currency)判斷可用的付費方式 呈現給用戶選擇 INGAME/Billing/COSTPOINT
            // 'ItemCode' => '', // 此參數非必填，參數為空時將依 交易金額(Amount)和幣別 (Currency)判斷可用的付費方式 呈現給用戶選擇
            'ProductName' => $product_name, //產品名稱 用戶購買的產品名稱 中文字及全型符號一個字算兩 個字元
            'Amount' => ($this->is_sandbox===true) ? '150': $amount, //交易金額 可以為整數，若有小數點最多 2 位
            'Currency' => $currency_code, //TWD/HKD/USD
            'SandBoxMode' => ($this->is_sandbox===true) ? "true" : "false", //※是否為測試環境 true/false string
        ];

        $sort_list = ['FacServiceId', 'FacTradeSeq', 'TradeType', 'ServerId', 'CustomerId', 'PaymentType', 'ItemCode', 'ProductName', 'Amount', 'Currency', 'SandBoxMode'];

        $pre_hash_value = '';
        foreach ($sort_list as $key => $key_name) {
            if (isset($parameter[$key_name])) {
                if ($parameter[$key_name] !== urlencode($parameter[$key_name])) {
                    $pre_hash_value = $pre_hash_value . strtolower(urlencode($parameter[$key_name]));
                } else {
                    $pre_hash_value = $pre_hash_value . $parameter[$key_name];
                }
            }
        }
        $pre_hash_value = $pre_hash_value . $factory_key;
        $hash = hash('sha256', $pre_hash_value);
        $parameter['Hash'] = $hash;

        if (false === $this->is_sandbox) {
            $url = self::ENDPOINT_AUTH;
        } else {
            $url = self::SANDBOX_ENDPOINT_AUTH;
        }

        $response = $this->get($url, $parameter);

        //string 1为成功, 其他为失败
        if ($response['ReturnCode'] !== '1') {
            throw new AuthCodeException($response['ReturnMsg'], 200, $response);
        }

        return $response;

    }

    /**
     * [getWebUrlByAuthCode 将授权码传至安卓SDK/MyCard网站, 并开始进行交易]
     * @doc 3.2
     * @datetime 2018-05-02T11:45:28+0800
     * @return   [type]                   [description]
     */
    public function getWebUrlByAuthCode($auth_code)
    {
        if (empty($auth_code)) {
            throw new AuthCodeException("auth_code为空", 200, null);
        }

        if (false === $this->is_sandbox) {
            $url = self::ENDPOINT_REDIRECT;
        } else {
            $url = self::SANDBOX_ENDPOINT_REDIRECT;
        }

        $params = [
            'AuthCode' => $auth_code,
        ];

        return $url . '?' . http_build_query($params);
    }

    /**
     * [query 查询MyCard交易结果]
     * @doc 3.3
     * @datetime 2018-05-02T11:38:48+0800
     * @return   [type]                   [description]
     */
    public function query($auth_code)
    {
        if (empty($auth_code)) {
            throw new AuthCodeException("auth_code为空", 200, null);
        }

        $parameter = [
            'AuthCode' => $auth_code,
        ];

        if (false === $this->is_sandbox) {
            $url = self::ENDPOINT_QUERY . '?' . http_build_query($parameter);
        } else {
            $url = self::SANDBOX_ENDPOINT_QUERY . '?' . http_build_query($parameter);
        }

        $response = $this->get($url);

        if ($response['ReturnCode'] !== '1') {
            throw new QueryException($response['ReturnMsg'], 200, $response);
        }

        return $response;
    }

    /**
     * [confirm 确认MyCard交易并进行请款]
     * @doc 3.4
     * @datetime 2018-05-02T11:38:32+0800
     * @return   [type]                   [description]
     */
    public function confirm($auth_code)
    {
        if (empty($auth_code)) {
            throw new AuthCodeException("auth_code为空", 200, null);
        }

        $parameter = [
            'AuthCode' => $auth_code,
        ];

        if (false === $this->is_sandbox) {
            $url = self::ENDPOINT_PAYMENT_CONFIRM . '?' . http_build_query($parameter);
        } else {
            $url = self::SANDBOX_ENDPOINT_PAYMENT_CONFIRM . '?' . http_build_query($parameter);
        }

        $response = $this->get($url);

        if ($response['ReturnCode'] ===  "1") {
            return $response;
        } else {
            throw new QueryException($response['ReturnMsg'], 200, $response);
        }
    }

    /**
     * [verifyCardAndPassword 卡号+密码验证]
     * @doc 3.4
     * @datetime 2018-05-02T11:38:32+0800
     * @return   [type]                   [description]
     */
    public function verifyCardAndPassword($auth_code, $card_id, $card_pw)
    {
        //VerifyCardAndPasswordException
        if (empty($auth_code)) {
            throw new VerifyCardAndPasswordException("auth_code为空", 200, null);
        }

        if (empty($card_id)) {
            throw new VerifyCardAndPasswordException("卡号为空", 200, null);
        }

        if (empty($card_pw)) {
            throw new VerifyCardAndPasswordException("密码为空", 200, null);
        }


        $parameter = [
            'AuthCode' => $auth_code,
            'CardID' => $card_id,
            'CardPW' => $card_pw,
        ];

        $factory_key = $this->factory_key;

        $sort_list = ['AuthCode', 'CardID', 'CardPW'];

        $pre_hash_value = '';
        foreach ($sort_list as $key => $key_name) {
            if (isset($parameter[$key_name])) {
                if ($parameter[$key_name] !== urlencode($parameter[$key_name])) {
                    $pre_hash_value = $pre_hash_value . strtolower(urlencode($parameter[$key_name]));
                } else {
                    $pre_hash_value = $pre_hash_value . $parameter[$key_name];
                }
            }
        }
        $pre_hash_value = $pre_hash_value . $factory_key;
        $hash = hash('sha256', $pre_hash_value);
        $parameter['Hash'] = $hash;

        if (false === $this->is_sandbox) {
            $url = self::ENDPOINT_CARD . '?' . http_build_query($parameter);
        } else {
            $url = self::SANDBOX_ENDPOINT_CARD . '?' . http_build_query($parameter);
        }

        $response = $this->get($url, $parameter);

        //string 1为成功, 其他为失败
        if ($response['ReturnCode'] !== '1') {
            throw new AuthCodeException($response['ReturnMsg'], 200, $response);
        }

        return $response;
    }


    /**
     * [notify 补储流程, MyCard主动通知]
     * @doc 3.6
     * @datetime 2018-05-02T11:46:51+0800
     * @return   [type]                   [description]
     */
    public function notify()
    {

    }


    /**
     * [diffOrder 差异报表]
     * @doc 3.7
     * @datetime 2018-05-02T11:49:15+0800
     * @return   [type]                   [description]
     */
    public function diffOrder()
    {

    }
}


//ReturnCode=1&ReturnMsg=%25e7%25b6%25b2%25e7%25ab%2599%25e5%2585%25a7%25e5%25ae%25b9%25e5%2595%258f%25e9%25a1%258c%25e8%25ab%258b%25e6%25b4%25bd%25e7%25b6%25b2%25e7%25ab%2599%25e5%25ae%25a2%25e6%259c%258d%25ef%25bc%258c%25e8%258b%25a5%25e7%2582%25ba%25e4%25ba%25a4%25e6%2598%2593%25e5%2595%258f%25e9%25a1%258c%25e8%25ab%258b%25e6%2592%25a5%25e6%2589%2593%2802%2926510754%25e2%2580%25a7&PayResult=3&FacTradeSeq=2018050224472370125&PaymentType=INGAME&Amount=150.00&Currency=TWD&MyCardTradeNo=MCARCJ000086&MyCardType=2&PromoCode=A0000&SerialId=&Hash=93ddae9298a3c7f966165ea455a3272e8fea47a958b0348d9ec37106df66c3bd&submit1=%E5%A6%82%E6%9E%9C%E6%82%A8%E7%9A%84%E7%80%8F%E8%A6%BD%E5%99%A8%E6%B2%92%E6%9C%89%E8%87%AA%E5%8B%95%E8%B7%B3%E8%BD%89%EF%BC%8C%E8%AB%8B%E9%BB%9E%E9%81%B8%E9%80%99%E8%A3%A1