<?php

namespace SPID_CIE_OIDC_PHP\Federation;

use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;

const DEFAULT_EXPIRATION_TIME = 30;

class FetchEntityStatementEndpoint
{

    private $sa_config;
    private $rp_config;
    private $rps_config;    // all rp configurations

    /**
     *  creates a new FetchEntityStatementEndpoint instance
     *
     * @param array $sa_config      sa configuration
     * @param array $rp_config      relying parties configurations
     * @throws Exception
     * @return FetchEntityStatementEndpoint
     */
    public function __construct(array $sa_config, array $rps_config)
    {
        $this->sa_config = $sa_config;
        $this->rp_config = null;
        $this->rps_config = $rps_config;
    }

    // TODO merge into lib/federation/EntityStatement
    public function process()
    {
        try {
            $iss = $_GET['iss'] ?? null;
            $sub = $_GET['sub'] ?? null;
            $output = $_GET['output'] ?? null;
   
            if($iss!=null && $iss!=$this->sa_config['client_id']) {
                throw new \Exception('iss MUST be equal to: ' . $this->iss);
            }

            if($sub==null) {
                $this->rp_config = $this->sa_config;

            } else {
                foreach($this->rps_config as $rp_config) {
                    if($rp_config['client_id'] == $sub) {
                        $this->rp_config = $rp_config;
                        break;
                    }
                }

                if($this->rp_config==null) throw new \Exception("sub not found");
            }

            $entity_statement = EntityStatement::makeRPEntityStatementFromConfig($this->sa_config, $this->rp_config, $output=='json'); 
        
            $mediaType = $output=='json'? 'application/json' : 'application/entity-statement+jwt';
            header('Content-Type: ' . $mediaType);
            echo $entity_statement;

            return $entity_statement;

        } catch(\Exception $e) {
            // API error
            http_response_code(400);
            echo "ERROR: " . $e->getMessage();
        }
    }
}