<?php

namespace SPID_CIE_OIDC_PHP\Federation;

use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;

class EntityListingEndpoint
{

    private $config;

    /**
     *  creates a new EntityListingEndpoint instance
     *
     * @param array $config base configuration
     * @throws Exception
     * @return EntityListingEndpoint
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function process()
    {
        $list = array();
   
        // TODO
        // implement filter by entity_type, trust_marked, trust_mark_id
        
        foreach($this->config as $k => $v) {
            $list[] = $v['client_id'];
        }

        $mediaType = 'application/json';
        header('Content-Type: ' . $mediaType);
        echo json_encode($list);

        return $list;
    }
}