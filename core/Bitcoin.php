<?php

/**
 * 
 * @author picasso250
 */

require_once APP_ROOT.'lib/jsonRpcClient.php';

class Bitcoin
{
    private static $_default_config = array(
        'url' => 'localhost',
        'port' => 8332,
        'prefix' => 'xc_'
    );

    private $_config = array();
    private $_rpc;

    public function __construct($config) 
    {
        $this->_config = $config = array_merge($config, self::$_default_config);
        $url = "http://$config[username]:$config[password]@$config[url]:$config[port]/";
        $this->_rpc = new jsonRPCClient($url);
    }

    public function __call($method, $args)
    {
        return $this->_rpc->__call($method, $args);
    }

    public function new_address($prefix = null)
    {
        if (!$prefix) {
            $prefix = $this->_config['prefix'];
        }
        $name = $prefix.uniqid();
        return $this->_rpc->getnewaddress($name);
    }
}

