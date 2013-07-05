<?php

/**
* 
*/
class Controller
{
    protected $request;
    protected $response;
    protected $app;

    public function __construct($request, $response, $app)
    {
        $db_conf = Config::get('db');
        ORM::configure($db_conf['dsn']);
        ORM::configure($db_conf['conf']);
        
        $response->cur_user = $request->session('se_user_id') ? User::find_one($_SESSION['se_user_id']) : null;

        $this->request   = $request;
        $this->response  = $response;
        $this->app       = $app;

        $this->response->layout(APP_ROOT.'view/layout/master.phtml');

    }

    protected function render($tpl, $data = array())
    {
        $this->response->render(APP_ROOT.'view/'.(isset(static::$view_root) ? static::$view_root : '')."$tpl.phtml", $data);
    }
}
