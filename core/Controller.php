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
        $this->request   = $request;
        $this->response  = $response;
        $this->app       = $app;

        $this->response->layout(APP_ROOT.'view/layout/master.phtml');

        $db_conf = Config::get('db');
        ORM::configure($db_conf['dsn']);
        ORM::configure($db_conf['conf']);
    }

    protected function render($tpl, $data = array())
    {
        $this->response->render(APP_ROOT.'view/'.(isset(static::$view_root) ? static::$view_root : '')."$tpl.phtml", $data);
    }
}
