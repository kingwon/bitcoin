<?php

/**
* 消息
*/
class Message extends Model
{
    public static function add($args)
    {
        $msg = self::orm()->create();
        $msg->set($args);
        $msg->set_expr('created', 'NOW()');
        $msg->save();
    }

    public function mark_as_read()
    {
        $this->set('has_read', 1);
        $this->save();
    }
}
