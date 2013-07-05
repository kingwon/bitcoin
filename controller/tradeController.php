<?php

/**
* 交易
*/
class tradeController extends Controller
{
    public static $view_root = 'trade/';

    public function newViewAction()
    {
        $types = Trade::field('type');
        $this->response->types = array_combine($types, $types);
        $this->render('new');
    }

    public function newAction()
    {
        $args = $this->request->params(array('type', 'price', 'quantity'));
        $trade = Trade::create($args);
        if ($trade) {
            die('ok');
        } else {
            die('fail');
        }
    }

    public function btc_testAction()
    {
        $bitcoin = new Bitcoin(array(
            'username' => 'bitcoinrpc',
            'password' => 'AD1UXYhUW5FEE63dBoJrEvxv6RMeh6BgQMDJxuAjmf65',
        ));
        echo "<pre>\n";
        print_r($bitcoin->getinfo()); echo "\n";
        print_r($bitcoin->new_address());
        echo "</pre>";
    }
}
