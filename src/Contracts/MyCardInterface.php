<?php

namespace VisionShadow\MyCard\Contracts;

/**
 * MyCard对外提供服务的API
 */
interface MyCardInterface
{
    /**
     * [getAuthCode 获取支付授权码]
     * @doc 3.1
     * @datetime 2018-05-02T11:39:43+0800
     * @return   [type]                   [description]
     */
    public function getAuthCode($fac_trade_seq, $trade_type, $customer_id, $payment_type, $product_name, $amount, $currency_code);

    /**
     * [getWebUrlByAuthCode 将授权码传至安卓SDK/MyCard网站, 并开始进行交易]
     * @doc 3.2
     * @datetime 2018-05-02T11:45:28+0800
     * @return   [type]                   [description]
     */
    public function getWebUrlByAuthCode($auth_code);

    /**
     * [query 查询MyCard交易结果]
     * @doc 3.3
     * @datetime 2018-05-02T11:38:48+0800
     * @return   [type]                   [description]
     */
    public function query($auth_code);

    /**
     * [confirm 确认MyCard交易并进行请款]
     * @doc 3.4
     * @datetime 2018-05-02T11:38:32+0800
     * @return   [type]                   [description]
     */
    public function confirm($auth_code);


    /**
     * [notify 补储流程, MyCard主动通知]
     * @doc 3.6
     * @datetime 2018-05-02T11:46:51+0800
     * @return   [type]                   [description]
     */
    public function notify();


    /**
     * [diffOrder 差异报表]
     * @doc 3.7
     * @datetime 2018-05-02T11:49:15+0800
     * @return   [type]                   [description]
     */
    public function diffOrder();
}
