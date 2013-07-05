<?php

/**
 * 
 * @author picasso250
 */

class Model
{
    protected $_id;
    protected $_data;
    private static $_cache;

    // 新建一个 Object
    // 接受的参数是一个 id
    // 或者一个 ORM 对象
    public function __construct($id = null, $data = null)
    {
        if (is_numeric($id)) {
            $this->_id = $id;
        }
        $this->_data = $data;
    }

    /**
     * 吸收 $data 对象，返回 Model 对象
     */
    public static function for_data($data)
    {
        $cn = get_called_class();
        $m = new $cn($data->id, $data);
        return $m;
    }

    public static function orm()
    {
        return ORM::for_table(self::table_name());
    }

    public static function __callStatic($func, $args)
    {
        return call_user_func_array(array(ORM::for_table(self::table_name()), $func), $args);
    }

    public static function table_name()
    {
        if (isset(static::$table_name)) {
            return static::$table_name;
        }
        return self::camel_to_under_score(get_called_class());
    }

    public static function camel_to_under_score($str)
    {
        return ltrim(strtolower(preg_replace('/([A-Z])/', '_$1', $str)), '_');
    }

    public function under_score_to_camel_case($str)
    {
        return ucfirst(preg_replace_callback('/_[a-z]/', function($s){return strtoupper(trim($s, '_'));}, $str));
    }

    // 给予程序员通过访问这个对象的属性来取数据的能力
    public function __get($name) 
    {
        return $this->get($name);
    }

    // 给予程序员通过这个类的属性赋值来更新数据库的能力
    public function __set($prop, $value)
    {
        return $this->set($prop, $value);
    }

    // 给予程序员查看属性存在与否的能力
    public function __isset($prop)
    {
        return $this->offsetExists($key);
    }

    public function __call($func, $args)
    {
        // 转发方法
        $data_methods = array('get', 'offsetExists', 'save', 'set', 'set_expr');
        if (in_array($func, $data_methods)) {
            if (!$this->_data) {
                if (ORM::get_db()->inTransaction()) {
                    $this->_fetch_db_data();
                } else {
                    $this->_fetch_data();
                }
            }
            $ret = call_user_func_array(array($this->_data, $func), $args);
            // if (self::$_cache && $func == 'save') {
            //     $this->_del_cache_data();
            // }
            return $ret;
        }

        // 魔术方法
        // get object by foreign_key
        $table_name = $func;
        $class = self::under_score_to_camel_case($table_name);
        if (!class_exists($class)) {
            return;
        }
        $data = ORM::for_table($table_name)->find_one($this->_data->get($table_name.'_id'));
        return $class::for_data($data); // catch the exception?
    }

    // fetch data from db
    protected function _fetch_db_data()
    {$this->_data = self::orm()->find_one($this->_id);
        var_dump(self::orm()->get_last_query());
        if ($this->_data = self::orm()->find_one($this->_id)) {
            throw new Exception("no data $this->_id of ".get_called_class(), 1);
        }
    }

    protected function _fetch_data()
    {
        if (self::$_cache) {
            $this->_get_cache_data();
        } else {
            $this->_fetch_db_data();
        }
    }

    // fetch data from cache
    private function _get_cache_data()
    {
        $key = $this->_cache_key();
        $data = self::$_cache->get($key);
        if ($data) {
            // hit
            $this->_data = $data;
        } else {
            // not hit
            $this->_fetch_db_data();
            // self::$_cache->set($key, ??);
        }
    }

    // del data from cache
    private function _del_cache_data()
    {
        self::$_cache->del($this->_cache_key());
    }

    private static function _user_orm($args, &$page)
    {
        $page = array_merge($page, array('limit' => 20, 'offset' => 0));
        $orm = self::orm();
        foreach ($args as $key => $value) {
            $orm->where($key, $value);
        }
        $page['total'] = $orm->count();
        return $orm;
    }

    public static function field($name)
    {
        // 获取表的描述
        // 返回 option 的可能值
        // 但显然，这个函数能做的事情还有很多很多
        $struct = self::_fields();
        foreach ($struct as $e) {
            if ($e->Field == $name) {
                $arr = explode('(', $e->Type);
                $types = trim($arr[1], "')");
                $types = explode("','", $types);
                return $types;
            }
        }
    }

    // 获取表的描述
    private static function _fields()
    {
        $sql = 'DESCRIBE `'.static::table_name().'`';
        $struct = self::orm()->raw_query($sql)->find_many(); // it must be cached
        return $struct;
    }

}

