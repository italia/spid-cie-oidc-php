<?php

namespace SPID_CIE_OIDC_PHP\Hooks;

class HookTest
{
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function run($data)
    {
        header("Content-Type: application/json");
        echo json_encode($data);
        die();
    }
}
