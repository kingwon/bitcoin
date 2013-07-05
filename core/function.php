<?php

function options($arr, $key = null)
{
    $options = array();
    foreach ($arr as $k => $value) {
        $sel = ($key == $k) ? ' selected' : '';
        $options[] = '<option value="'.$k.'"'.$sel.'>'.$value.'</option>';
    }
    return implode("\n", $options);
}

function _url($url, $query = array())
{
    $url = '/'.ltrim($url, '/');
    if ($query) {
        $url .= '?'.http_build_query($query);
    }
    return $url;
}
function _static_url($url, $query = array())
{
    return _url($url, $query);
}
