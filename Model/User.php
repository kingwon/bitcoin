<?php

/**
* 用户类
*/
class User extends Model
{
    public static function add($args)
    {
        ORM::get_db()->beginTransaction();

        $user = self::orm()->create();
        $args['password'] = md5($args['password']);
        $args['money_pass'] = md5($args['money_pass']);
        $user->set($args);
        $user->set_expr('created', 'NOW()');
        $user->set_expr('last_login', 'NOW()');
        if (!$user->save()) {
            ORM::get_db()->rollback();
            throw new Exception('创建用户失败', 1);
        }

        // 创建账户
        try {
            foreach (array('btc', 'cny') as $type) {
                $account = Account::add(array(
                    'type' => $type,
                    'user_id' => $user->id,
                ));
            }
        } catch (Exception $e) {
            ORM::get_db()->rollback();
            throw new Exception('创建账户失败', 2);
        }
        ORM::get_db()->commit();

        return $user;
    }

    public static function hasName($name)
    {
        $user = self::orm()->where('username', $name)->find_one();
        return $user;
    }

    public static function check($name, $password)
    {
        $user = self::orm()
                ->where('username', $name)
                ->where_raw('password=md5(?)', array($password))
                ->find_one();
        return self::for_data($user);
    }

    public function changePassword($old_pass, $new_pass)
    {
        if (self::check($this->username, $old_pass)) {
            $this->password = md5($new_pass);
            return $this->save();
        }
        return false;
    }

    public function edit($key, $value)
    {
        $this->set($key, $value);
        return $this->save();
    }

    public function changeMoneyPass($old_pass, $new_pass)
    {
        if ($this->money_pass == md5($old_pass)) {
            $this->money_pass = md5($new_pass);
            return $this->save();
        }
        return false;
    }

    public function login($request, $response)
    {
        $response->session('se_user_id', $this->id);
        $this->ip = $request->ip();
        $this->set_expr('last_login', 'NOW()');
        $this->save();
    }

    public function logout()
    {
        $_SESSION['se_user_id'] = 0;
    }

    // get the current user who has logined in
    public static function current($request)
    {
        if ($request->session('se_user_id')) {
            return new self($_SESSION['se_user_id']);
        } else {
            return false;
        }
    }

    /**
     * 得到用户的挂单
     */
    public function posts($num)
    {
        return Trade::getList(array(
            'user_id' => $this->id,
        ));
    }

    public function account($type)
    {
        // we can cache it
        return Account::orm()->where('user_id', $this->id)->find_one();
    }

    public function get_trade_log($num)
    {
        return Trade::get_log($num, $this->id);
    }
}
