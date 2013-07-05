<?php

/**
* 挂单
*/
class postController extends Controller
{
    public static $view_root = 'post/';

    public function addSellViewAction()
    {
        $this->render('add');
    }

    public function addSellAction()
    {
        $args = $this->request->param('price', 'quantity');
        var_dump($args);
        $args['type'] = 'sell';
        $post = Post::add($args);
        die($post->id ? 'ok' : 'error');
    }

}
