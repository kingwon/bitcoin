<?php

/**
* 用户账户
*/
class userController extends Controller
{
    public static $view_root = 'user/';

    public function loginViewAction()
    {
        $this->render('login');
    }

    public function loginAction()
    {
        $user = User::check($this->request->username, $this->request->password);
        if ($user) {
            $user->login($request->ip());
            $this->response->redirect('/');
        } else {
            die('wrong username or password');
        }
    }

    public function registerViewAction()
    {
        $this->render('register');
    }

    public function registerAction()
    {
        if (User::hasName($this->request->username)) {
            die("username: $this->request->username exists");
        }
        $args = $this->request->params(array(
            'username', 
            'password',
            'money_pass',
            'email',
        ));
        $args['role'] = 'user';
        $user = User::create($args);
        if ($user) {
            die('ok');
        } else {
            die('fail');
        }
    }

    public function myInfoAction()
    {
        $this->response->user = User::current();
        $this->render('my-info');
    }

    public function myMoneyAction()
    {
        foreach (array('btc', 'cny') as $type) {
            $account = User::current()->account($type);
            $this->{$type.'_remain'} = array(
                'free' => $account->get_free(),
                'occupy' => $account->get_occupy(),
                'total' => $account->remain(),
            );
        }
        $this->render('my-money');
    }

    public function editInfoAction()
    {
        $this->user = User::current();
        $this->render('edit');
    }

    public function changePasswordAction()
    {
        $rs = User::current()->changePassword($this->request->password, $this->request->new_pass);
        if ($rs) {
            die('ok');
        } else {
            die('fail');
        }
    }

    public function changeEmailAction()
    {
        $rs = User::current()->edit('email', $this->request->email);
        if ($rs) {
            die('ok');
        } else {
            die('fail');
        }
    }

    public function changeMoneyPassAction()
    {
        $rs = User::current()->changeMoneyPass($this->request->old_pass, $this->request->new_pass);
        if ($rs) {
            die('ok');
        } else {
            die('fail');
        }
    }

    public function tradeHistoryAction()
    {
        $logs = User::current()->get_trade_log();
        $this->render('history');
    }
}
