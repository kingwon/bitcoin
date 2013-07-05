<?php


/**
* Trade
* 
* 关于 事务的开启方式
* 永远在 public 方法里开启事务
* 如果有某个方法要复用，就将之转换为对应的无事务的 private 方法
*/
class Trade extends Model
{

    private static function _prepare_msg($user, $msg)
    {
        
    }


    private static function _notify_user($user, $msg)
    {
        // 消息类型
        // 1. 挂单成功？ 布尔值
        // 2. 退回金钱 钱类型，数值
        // 3. 撮合成功？ 布尔值 撮合总额

    }

    /** 
     * 自动撮合交易
     * @param Post $t 一个新挂单
     */
    public static function auto_match(Post $t)
    {
        // 撮合一个单
        // 后面剩余的，用递归 _add() 实现继续撮合
        $f = self::match($t);
        if ($f) {
            // 只要撮合交易系统撮合成功了，就进行自动交易
            // 价格取平均价格，数量取少的那一个
            $price = ($t->price + $f->price) / 2.0;
            $quantity = min($t->quantity, $f->quantity);

            $rt = $t->_auto_trade($f->user(), $price, $quantity);
            $rf = $f->_auto_trade($t->user(), $price, $quantity);
        }
    }

    // 自动匹配时调用这个交易方法
    // 只会被自动匹配的调用
    private function _auto_trade($user, $price, $quantity)
    {
        // 单子中的btc数量不等
        // 剩余的数量退回到账户中去
        if ($this->quantity != $quantity) {

            $args = $this->to_array();
            $args['quantity'] = abs($this->quantity - $quantity);
            unset($args['created']);
            Post::auto_add($args);

            $this->quantity = $quantity;
            $this->save();
        }
        $rs = $this->_trade($user);
        return $rs;
    }

    private static function _money_buy_btc_with_check($buyer, $seller, $money, $btc)
    {
        if ($buyer->account('cny')->remain() < $post->value) {
            throw new Exception("钱不够", 2);
        }
        if ($seller->account('btc')->remain() < $post->quantity) {
            throw new Exception("btc not enough", 1);
        }
        return self::_money_buy_btc($buyer, $seller, $money, $btc);
    }

    public static function do_trade(Post $post, User $user)
    {
        ORM::get_db()->beginTransation();
        try {
            $puser = $post->user();
            if ($post->type == 'sell') {
                self::_money_buy_btc_with_check($user, $puser, $post->value, $post->quantity);
            } elseif ($post->type == 'buy') {
                self::_money_buy_btc_with_check($puser, $user, $post->value, $post->quantity);
            }
        } catch (Exception $e) {
            ORM::get_db()->rollback();
            return $e->getMessage();
        }
        ORM::get_db()->commit();
        return true;
    }

    // 用钱买btc
    private static function _money_buy_btc($buyer, $seller, $money, $btc)
    {
        // 在这里启动扣费程序
        $conf = Conf::instance();
        $ratio = $conf->get('ratio');
        $min_money = $conf->get('min_money');
        $min_btc = $conf->get('min_btc');
        $m = max($money * $ratio, $min_money);
        $b = max($money * $ratio, $min_btc);


        // 钱
        $buyer->account('cny')->decr($money);
        $seller->account('cny')->incr($money-$m);

        // BTC
        $seller->account('btc')->decr($btc);
        $buyer->account('btc')->incr($btc-$b);
    }

    public static function get_list($args, $page = array())
    {
        return self::_user_orm($args, $page)
            ->order_by_desc('id')
            ->find_many();
    }

    /**
     * 得到最近的交易记录，供首页显示
     */
    public static function get_latest($num)
    {
        $orm = self::orm()
            ->order_by_desc('id')
            ->limit($num);
        if ($user_id) {
            $orm->where('user_id', $user_id);
        }
        return $orm->find_many();
    }

    public static function find_by_post(Post $post)
    {
        return self::orm()->where('post_id', $post->id)->find_one();
    }
}

