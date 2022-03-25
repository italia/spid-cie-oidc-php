<?php

namespace SPID_CIE_OIDC_PHP\Core;

class Logger
{
    public function __construct($config = null)
    {
        if ($config == null) {
            $config = (object)[];
        }
        $this->config = $config;
    }

    public function log($tag, $value, $object = null, $priority = LOG_NOTICE)
    {
        $message = "[" . $_SERVER['REMOTE_ADDR'] . "][" . $tag . "] - " . $value;
        if ($object != null) {
            $message .= "(" . json_encode($object) . ")";
        }
        return error_log($message);
    }
}
