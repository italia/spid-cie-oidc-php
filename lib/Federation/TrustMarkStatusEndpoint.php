<?php

namespace SPID_CIE_OIDC_PHP\Federation;

use SPID_CIE_OIDC_PHP\Core\Util;
use SPID_CIE_OIDC_PHP\Core\JWT;
use SPID_CIE_OIDC_PHP\OIDC\RP\Database;

class TrustMarkStatusEndpoint
{

    private $config;

    /**
     *  creates a new TrustMarkStatusEndpoint instance
     *
     * @param array $config base configuration
     * @throws Exception
     * @return TrustMarkStatusEndpoint
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function process()
    {

        try {
            $sub = $_POST['sub'] ?? null;
            $id = $_POST['id'] ?? null;
            $iat = $_POST['iat'] ?? null ;
            $trust_mark = $_POST['trust_mark'] ?? null;
    
            $active = false;

            if ($sub && $id) {

                // search trust mark for sub id and sub
                $found = array();
                foreach($this->config as $client) {
                    foreach($client['trust_marks'] as $trust_mark_item) {
                        if($id == $trust_mark_item['id']) {
                            $trust_mark = $trust_mark_item['trust_mark'];
                            $trust_mark_payload = JWT::getJWSPayload($trust_mark);
                            if(JWT::isValid($trust_mark) && $sub == $trust_mark_payload->sub) $found[] = $trust_mark;
                        }
                    }
                }

                if(count($found)) {

                    // descending order to get the last exp item
                    $found = usort($found, function ($a, $b) {
                        return strcmp($b->iat, $a->iat);
                    });

                    if($iat) {
                        foreach($found as $trust_mark) {
                            if($found->iat == iat) {
                                $active = true;
                                break;
                            }
                        }
                    } else {
                        $active = true;
                    }
                }

            } else if ($trust_mark) {

                foreach($this->config as $client) {
                    foreach($client[trust_mark] as $trust_mark_item) {
                        if($trust_mark == $trust_mark_item['trust_mark']
                            && JWT::isValid($trust_mark_item['trust_mark'])) {

                            $active = true;
                        }
                    }
                }

            } else {
                throw new \Exception("sub and id, or trust_mark are mandatory");
            }

            $trust_mark_status = array(
                "active" => $active
            );

            $mediaType = 'application/json';
            header('Content-Type: ' . $mediaType);
            echo json_encode($trust_mark_status);

            return $trust_mark_status;

        } catch(\Exception $e) {
            // API error
            http_response_code(400);
            echo "ERROR: " . $e->getMessage();
        }
    }
}