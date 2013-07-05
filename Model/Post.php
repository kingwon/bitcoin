<?php

/**
* 
*/
class Post extends Model
{
    /**
     * 新增一个挂单
     */
    public static function add($args)
    {
        ORM::get_db()->beginTransation();

        // 是否还可以挂单？
        try {
            call_user_func_array(array(get_class(), '_enough_to_'.$args['type']), array($args));
            self::_check_add($args);

            // 挂单结果：
            // 1. 挂单失败，
            // 2. 挂单成功
            // 2.1. 未触发自动撮合
            // 2.2. 触发自动撮合
            // 2.2.1 自动撮合成功之后，单子融掉，
            // 2.2.2 自动撮合成功之后，仍然留有单子
            $rs = $this->_add_first($args);

            // todo
            // 返回值意义
            // 最低位 挂单是否成功
            // 次低位 是否触发自动撮合
            // 自动撮合是否成功
            // 是否留有挂单
            // 挂单的数量

            // todo 通知用户

        } catch (Exception $e) {
            ORM::get_db()->rollback();
            return $e->getMessage();
        }
        ORM::get_db()->commit();
        return new self($to->id);
    }

    private static function _add_first($args)
    {
        // 是第一次挂单
        $post = self::orm()->create();
        $post->set($args);
        $post->set_expr('created', 'NOW()');

        $rs = self::_do_match(self::for_data($post));
        if ($rs > 0) {
            return $rs;
        } else {
            $post->save();
            return $post->id;
        }
    }

    /**
     * 添加一个单
     * 供自动撮合时调用
     */
    public static function auto_add($args)
    {
        // 首先看数量是否过小，过小就不予撮合
        $t = (object) $args;
        try {
            self::_check_add($t);
        } catch (Exception $e) {
            // 如果是别人的单，需要退款
            // 自己的单不需要退款(因为我们从未扣过钱，所以不用将余额退回用户账户)
            if ($t->user_id == User::current()->id) {
                return false; // ??
            } else {
                self::_back_money($t);
            }
        }

        self::_add_first($args);
    }

    private static function _do_match(Post $t)
    {
        if (self::find_match($t)) {
            # code...
        }
        Trade::auto_match($t);
        return 0; // 暂时屏蔽撮合交易系统
    }

    private static function _enough_to_sell($t)
    {
        // 卖单，检测账户的 BTC 数量
        $btc = User::current()->account('btc')->get_free();
        if ($btc < $t->quantity) {
            throw new Exception("您的 bitcoin 不够了，请充值", 1);
        }
    }
    private static function _enough_to_buy($t)
    {
        // 买单，检测账户的 人民币 数量
        $cny = User::current()->account('cny')->get_free();
        if ($cny < $t->quantity * $t->price) {
            throw new Exception("您的余额不足，请充值", 1);
        }
    }

    private static function _check_add($args)
    {
        $t = is_array($args) ? (object) $args : $args;
        $conf = Conf::instance();
        $minBtc = $conf->get('min_btc');
        if ($minBtc > $t->quantity) {
            throw new Exception('比特币数量过低，请大于 '.$minBtc;, 1);
        } else {
            return true;
        }
    }

    private static function _back_money(Post $t)
    {
        // 退回钱
        $user = $t->user();
        return call_user_func_array(
            array(get_class(), '_back_'.$t->type.'_money'), 
            array($user, $t)
        );
    }

    private static function _back_sell_money($user, $t)
    {
        // 卖单，还回去 btc
        $user->account('btc')->incr($t->quantity);
        // todo
        notify_user('u have money back');
    }
    private static function _back_buy_money($user, $t)
    {
        // 买单，还回去 cny
        $user->account('cny')->incr($t->quantity * $t->price);
        notify_user('u have money back');
    }

    // 撮合交易规则
    public static function find_match($t)
    {
        // 规则：找到优于或等于这个价格的
        $price = $t->price;
        return self::_match_orm($t->type, $price)
            ->order_by_desc('id')
            ->find_one();
    }

    private static function _match_orm($type, $price)
    {
        $type_table = array(
            'sell' => 'buy',
            'buy' => 'sell',
        );
        $than_table = array(
            'sell' => 'gte',
            'buy' => 'lte',
        );
        $sort_table = array(
            'sell' => 'desc',
            'buy' => 'asc',
        );
        return self::orm()
            ->where_not_equal('is_cancel', 1)
            ->where('type', $type_table[$type])
            ->{'where_'.$than_table[$type]}('price', $price)
            ->{'order_by_'.$sort_table[$type]}('price');
    }

    /**
     * 撤销单子
     */
    public function cancel()
    {
        ORM::get_db()->beginTransation();
        try {
            $this->_fetch_db_data();

            // 已经交易的不允许改变
            if ($this->_is_trade()) {
                throw new Exception("$this->id has been trade", 1);
            }

            // 更改自身的状态
            $this->is_cancel = 1;
            $this->set_expr('canceled', 'NOW()');
            $this->save();
        } catch (Exception $e) {
            ORM::get_db()->rollback();
            return $e->getMessage();
        }
        ORM::get_db()->commit();
        return true;
    }

    private function _is_trade()
    {
        return TradeLog::find_by_post($this);
    }

    public static function get_list($args, $page = array())
    {
        return self::_user_orm($args, $page)
            ->order_by_desc('id')
            ->find_many();
    }

    /**
     * 获取卖单，以在首页显示
     */
    public static function get_to_sell($num)
    {
        return self::_get_to($num)
            ->where_equal('type', 'sell')
            ->order_by_asc('price')
            ->find_many();
    }

    /**
     * 获取买单，以在首页显示
     */
    public static function get_to_buy($num)
    {
        return self::_get_to($num)
            ->where_equal('type', 'buy')
            ->order_by_desc('price')
            ->find_many();
    }

    private static function _get_to($num)
    {
        return self::orm()
            ->select_expr('*')
            ->select_expr('count(id)', 'num') // 单数
            ->select_expr('(`price`*`quantity`) as `value`') // 价值
            ->where_not_equal('is_cancel', 1)
            ->group_by('price')
            ->limit($num);
    }
}
