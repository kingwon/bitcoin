<?php


/**
* 财务帐号
*/
class Account extends Model
{
    public static function add($args)
    {
        $account = self::orm()->create();
        $account->set($args);
        $rs = $account->save();
        if (!$rs) {
            ORM::get_db()->rollback();
            throw new Exception('创建账户失败', 1);
        }
        $log = self::log_orm()->create();
        $log->money = 0;
        $log->account_id = $account->id;
        $log->remain = 0;
        $log->is_affect = 1;
        $log->set_expr('created', 'NOW()');
        $log->set_expr('affected', 'NOW()');
        $rs = $log->save();
        if (!$rs) {
            ORM::get_db()->rollback();
            throw new Exception('创建账户记录失败', 1);
        }
        return true;
    }

    private static function _transfer_money(Account $from, Account $to, $money)
    {
        $from->decr($money);
        $to->incr($money);
    }

    public static function log_orm()
    {
        return ORM::for_table('account_log');
    }

    // 可用的btc/cny，余额减去挂单量
    public function get_free()
    {
        $remain = $this->remain();
        $occupy = Trade::orm_to()
            ->select_expr($map[$this->type])
            ->where('user_id', $this->user()->id)
            ->find_one();
        return $remain + $occupy;
    }

    // btc/cny 挂单量
    public function get_occupy()
    {
        $map = array(
            'btc' => 'sum(`quantity`)',
            'cny' => 'sum(`quantity` * `price`)',
        );
        $typeMap = array(
            'btc' => 'sell',
            'cny' => 'buy',
        );
        $occupy = Trade::orm_to()
            ->select_expr($map[$this->type])
            ->where('type', $typeMap[$this->type])
            ->where('user_id', $this->user()->id)
            ->find_one();
        return $occupy;
    }

    // 余额
    public function remain()
    {
        $remain = self::log_orm()
            ->select('remain')
            ->where('account_id', $this->id)
            ->where('is_affect', 1)
            ->order_by_desc('id')
            ->find_one();
        return $remain;
    }

    public function incease($n)
    {
        if (!ORM::get_db()->inTransaction()) {
            ORM::get_db()->beginTransation();
        }
        try {
            $this->incr($n);
        } catch (Exception $e) {
            ORM::get_db()->rollback();
            return '增加钱数失败';
        }
        ORM::get_db()->commit();
        return true;
    }

    public function incr($n)
    {
        return get_add_log(-1, $n, 1);
    }

    public function decrease($n)
    {
        if (!ORM::get_db()->inTransaction()) {
            ORM::get_db()->beginTransation();
        }
        try {
            $this->decr($n);
        } catch (Exception $e) {
            ORM::get_db()->rollback();
            return '扣款失败';
        }
        ORM::get_db()->commit();
        return true;
    }

    public function decr($n)
    {
        return get_add_log(-1, $n, 1);
    }

    private function get_add_log($type, $n, $is_affect)
    {
        $remain = $this->remain();
        $log = self::log_orm()->create();
        $log->money = $n;
        $log->account_id = $this->id;
        $log->remain = $type > 0 ? $remain + $n : $remain - $n;
        $log->is_affect = $is_affect; // 是否立即生效
        $log->set_expr('created', 'NOW()');
        $rs = $log->save();
        if (!$rs) {
            throw new Exception("add account log", 1);
        }
        return $log->id;
    }

    // 充值
    public function recharge($n)
    {
        return $this->get_add_log(+1, $n, 0);
    }

    public function affect($id)
    {
        $log = self::log_orm()->find_one($id);
        $log->is_affect = 1;
        return $log->save();
    }
}

