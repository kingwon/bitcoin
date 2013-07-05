<?php

/**
* 首页
*/
class indexController extends Controller
{
    public static $view_root = 'index/';

    public function indexAction()
    {
        $this->render('index');
    }
}
