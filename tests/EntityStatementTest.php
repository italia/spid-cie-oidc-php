<?php

use PHPUnit\Framework\TestCase as TestCase;
use SPID_CIE_OIDC_PHP\Federation\EntityStatement;

/**
 * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement
 */
class EntityStatementTest extends TestCase
{
    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::makeFromConfig
     * @runInSeparateProcess
     */
    public function test_makeFromConfig()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        $config = $config['rp_proxy_clients']['default'];
        $metadata = EntityStatement::makeFromConfig($config);
        $this->assertNotEmpty($metadata, "EntityStatement cannot be empty");

        $es = new EntityStatement("eyJ0eXAiOiJlbnRpdHktc3RhdGVtZW50K2p3dCIsImFsZyI6IkVTMjU2Iiwia2lkIjoiMjE1ZTNkMWM5YTcxZTkxYzFhNWY0YmQ1N2Y1YTRjZjgifQ.eyJpc3MiOiJodHRwczovL2Rldi5saW5mYWJveC5pdC9vaWRjLyIsInN1YiI6Imh0dHBzOi8vZGV2LmxpbmZhYm94Lml0L29pZGMvIiwiaWF0IjoxNjQ5Njc2Nzg0LCJleHAiOjI2ODEyMTI3ODQsImp3a3MiOnsia2V5cyI6W3sia2lkIjoiNmE3ZmNjZmQ0ZjY3ZDY1ZjdjOTBlOTAyZWU1OWJhYTljZjUyYTA2NzU2YWEzYzgyYzQyOTVhZWQ1ZTM2YmU4NSIsImt0eSI6IlJTQSIsIm4iOiIxbTIyUlh3WnNoM1VjVWlYcS16LVRaUlk3U2NuNGtSTGpUd3hYMG1rMkJGd2YtbkRlcVZLX2pvWmF4T0pSaklqOUdXTGpVRm9CZXZ6b0tkWnczRlp5VVhGY2p4X2RyS2ZObGx6QllMQkhlNnc5WW5VeTJ5UHRGRXpkc3JEOGRTbzhMWnE4b2RsOUo3YzVjN1dRclFHRXg1VFo3Rk1zcVJPRWN0YllZMjkxMnNwT1lMSDJlOHhtcVlVS0pFR1hZNUpYbmpVVlhjb1NQcU5IM1QwS0NITWFqZHM0WVpLTW5QUGgwa3RUbjRhNHQzYm1DNHR6SEdkQ1l6V3NFTm4xXzQ1el96NjlSRV9TeWxWWkV1dmpqcHBRcjFKS2l2RnNZMlIzY0JVaWE0Rnk2QkZBUE81cFd0Y2xLSHl2MEZ5RC1qWXFCU29oQkJhMi0zQ2lyNUtycUpVTWFNZExDd1Z5UWl0U2daYTUzUVUxZVRPWW9KLTV5MVU1RkVGNFpDMjRqNW93eUV5OVh2elhsSFhrdVRpYlRwbDhQcGxzN3p3VE51d0V2eTBNZVZhQUNzYjJBU2JFUWFBWlp2eVhGN25mVUJsemFDdEhwM2ZwNTNsaC1uTlNsSEFtdi16YWhmalVJNWZXVFNBN2lPeWVoSlhIUEdtM3pRcG5JaVJ6R1VteXJVRCIsImUiOiJBUUFCIiwieDVjIjoiTUlJRTBUQ0NBem1nQXdJQkFnSVVZNTM5M2FCY2FVQ01wc1hGSHVGWUtlZDFBdmt3RFFZSktvWklodmNOQVFFTEJRQXdnWlV4SGpBY0JnTlZCQW9NRlU1aGJXVWdiMllnVW1Wc2VXbHVaeUJRWVhKMGVURWVNQndHQTFVRUF3d1ZUbUZ0WlNCdlppQlNaV3g1YVc1bklGQmhjblI1TVJvd0dBWURWUVJUREJGb2RIUndjem92TDJ4dlkyRnNhRzl6ZERFVk1CTUdBMVVFWVF3TVVFRTZTVlF0WTE5aU5URTVNUXN3Q1FZRFZRUUdFd0pKVkRFVE1CRUdBMVVFQnd3S1EyRnRjRzlpWVhOemJ6QWVGdzB5TWpBek1qSXhOek0zTlRkYUZ3MHlOREF6TWpFeE56TTNOVGRhTUlHVk1SNHdIQVlEVlFRS0RCVk9ZVzFsSUc5bUlGSmxiSGxwYm1jZ1VHRnlkSGt4SGpBY0JnTlZCQU1NRlU1aGJXVWdiMllnVW1Wc2VXbHVaeUJRWVhKMGVURWFNQmdHQTFVRVV3d1JhSFIwY0hNNkx5OXNiMk5oYkdodmMzUXhGVEFUQmdOVkJHRU1ERkJCT2tsVUxXTmZZalV4T1RFTE1Ba0dBMVVFQmhNQ1NWUXhFekFSQmdOVkJBY01Da05oYlhCdlltRnpjMjh3Z2dHaU1BMEdDU3FHU0liM0RRRUJBUVVBQTRJQmp3QXdnZ0dLQW9JQmdRRFdiYlpGZkJteUhkUnhTSmVyN1A1TmxGanRKeWZpUkV1TlBERmZTYVRZRVhCLzZjTjZwVXIrT2hsckU0bEdNaVAwWll1TlFXZ0Y2L09ncDFuRGNWbkpSY1Z5UEg5MnNwODJXWE1GZ3NFZDdyRDFpZFRMYkkrMFVUTjJ5c1B4MUtqd3RtcnloMlgwbnR6bHp0WkN0QVlUSGxObnNVeXlwRTRSeTF0aGpiM1hheWs1Z3NmWjd6R2FwaFFva1FaZGprbGVlTlJWZHloSStvMGZkUFFvSWN4cU4yemhoa295YzgrSFNTMU9maHJpM2R1WUxpM01jWjBKak5hd1EyZlgvam5QL1ByMUVUOUxLVlZrUzYrT09tbEN2VWtxSzhXeGpaSGR3RlNKcmdYTG9FVUE4N21sYTF5VW9mSy9RWElQNk5pb0ZLaUVFRnJiN2NLS3ZrcXVvbFF4b3gwc0xCWEpDSzFLQmxybmRCVFY1TTVpZ243bkxWVGtVUVhoa0xiaVBtakRJVEwxZS9OZVVkZVM1T0p0T21YdyttV3p2UEJNMjdBUy9MUXg1Vm9BS3h2WUJKc1JCb0JsbS9KY1h1ZDlRR1hOb0swZW5kK25uZVdINmMxS1VjQ2EvN05xRitOUWpsOVpOSUR1STdKNkVsY2M4YWJmTkNtY2lKSE1aU2JLdFFNQ0F3RUFBYU1YTUJVd0V3WURWUjBnQkF3d0NqQUlCZ1lyVEJBRUFnRXdEUVlKS29aSWh2Y05BUUVMQlFBRGdnR0JBQ1lTSmFSSFQyb2xpdlhYQjkzWUxIRzhmTnVXMUlkQ3ptWFNTV3Nsdjl1c3Jwc2ZwQkIvakZ5SUtYUkZYbWZTZVNPQWRoQTh2YzN1WWhYNHpjWlB5NFRVVG9IOWs5RmoxV2M2NlhMcUk4VHBRT0dON1g5YjhIK3VhR1pwR0hwZkswV3BBb3ZuUmF0eDRsdi9TUmRQekJjekhNOHY1T2QzbkRmbUJaTitBWUpZL2txSzVIWHpXa1JXckpyck53LzhxYlhreGlFSStlUFF0Y0c5aU9RUkYvOUFSc1AyaGlQa3FOcUxOYzlmV1pVUm0xRVFXLzFCV3YyeUR0bGFHMWR4a1N3VjhGN2hvZE9kUjZad1VzTE04VVo0bUJ3ZkJWTVpUakt0a1ZEbXc0RWh1enFManh5blJOTGVtdHpRV21yakVyczFJOEdmOWhxaTZybHQwMXBpdzh0Tm12NkNwbnhPdWRIRkFpakR5L3MyRjVzUnhtTkZZcDlsVkVRbGxibXlOY3lwSmFaOEptcjBHQjVqQWc4N01XTC9iV2hDMXpyYWJ1SXVuZlNVaFlYZlYrODFmZEd1UVZoS2FrQ3ZISktaQzB5TkJPL3FQeWRuVnNjUEIzY3BBaGNReU9iS3BSOXpvQnIxS2hEb1RvSVd2SXRkU3R2b01FNXlkMmhvamVXOElnPT0iLCJ4NXQiOiJWd2g2X19YUzdOamRqNVBfcWFyRndVUTdGTVkiLCJ4NXQjMjU2IjoicHhBVWpVeTllQUc5WHRRRHREUXZONTlJTzhrSUhSWV9PUDRRY29zR0pNRSIsInVzZSI6InNpZyJ9XX0sImF1dGhvcml0eV9oaW50cyI6WyJodHRwOi8vc3BpZC1jaWUtb2lkYy10ZXN0LmxpbmZhc2VydmljZS5pdDo4MDAwLyJdLCJ0cnVzdF9tYXJrcyI6W10sIm1ldGFkYXRhIjp7Im9wZW5pZF9yZWx5aW5nX3BhcnR5Ijp7ImFwcGxpY2F0aW9uX3R5cGUiOiJ3ZWIiLCJjbGllbnRfcmVnaXN0cmF0aW9uX3R5cGVzIjpbImF1dG9tYXRpYyJdLCJjbGllbnRfbmFtZSI6IlJlbHlpbmcgUGFydHkgUEhQIiwiY29udGFjdHMiOlsicnBAZXhhbXBsZS5pdCJdLCJncmFudF90eXBlcyI6WyJhdXRob3JpemF0aW9uX2NvZGUiXSwiandrcyI6eyJrZXlzIjpbeyJraWQiOiI2YTdmY2NmZDRmNjdkNjVmN2M5MGU5MDJlZTU5YmFhOWNmNTJhMDY3NTZhYTNjODJjNDI5NWFlZDVlMzZiZTg1Iiwia3R5IjoiUlNBIiwibiI6IjFtMjJSWHdac2gzVWNVaVhxLXotVFpSWTdTY240a1JMalR3eFgwbWsyQkZ3Zi1uRGVxVktfam9aYXhPSlJqSWo5R1dMalVGb0JldnpvS2RadzNGWnlVWEZjanhfZHJLZk5sbHpCWUxCSGU2dzlZblV5MnlQdEZFemRzckQ4ZFNvOExacThvZGw5SjdjNWM3V1FyUUdFeDVUWjdGTXNxUk9FY3RiWVkyOTEyc3BPWUxIMmU4eG1xWVVLSkVHWFk1SlhualVWWGNvU1BxTkgzVDBLQ0hNYWpkczRZWktNblBQaDBrdFRuNGE0dDNibUM0dHpIR2RDWXpXc0VObjFfNDV6X3o2OVJFX1N5bFZaRXV2ampwcFFyMUpLaXZGc1kyUjNjQlVpYTRGeTZCRkFQTzVwV3RjbEtIeXYwRnlELWpZcUJTb2hCQmEyLTNDaXI1S3JxSlVNYU1kTEN3VnlRaXRTZ1phNTNRVTFlVE9Zb0otNXkxVTVGRUY0WkMyNGo1b3d5RXk5WHZ6WGxIWGt1VGliVHBsOFBwbHM3endUTnV3RXZ5ME1lVmFBQ3NiMkFTYkVRYUFaWnZ5WEY3bmZVQmx6YUN0SHAzZnA1M2xoLW5OU2xIQW12LXphaGZqVUk1ZldUU0E3aU95ZWhKWEhQR20zelFwbklpUnpHVW15clVEIiwiZSI6IkFRQUIiLCJ4NWMiOiJNSUlFMFRDQ0F6bWdBd0lCQWdJVVk1MzkzYUJjYVVDTXBzWEZIdUZZS2VkMUF2a3dEUVlKS29aSWh2Y05BUUVMQlFBd2daVXhIakFjQmdOVkJBb01GVTVoYldVZ2IyWWdVbVZzZVdsdVp5QlFZWEowZVRFZU1Cd0dBMVVFQXd3VlRtRnRaU0J2WmlCU1pXeDVhVzVuSUZCaGNuUjVNUm93R0FZRFZRUlREQkZvZEhSd2N6b3ZMMnh2WTJGc2FHOXpkREVWTUJNR0ExVUVZUXdNVUVFNlNWUXRZMTlpTlRFNU1Rc3dDUVlEVlFRR0V3SkpWREVUTUJFR0ExVUVCd3dLUTJGdGNHOWlZWE56YnpBZUZ3MHlNakF6TWpJeE56TTNOVGRhRncweU5EQXpNakV4TnpNM05UZGFNSUdWTVI0d0hBWURWUVFLREJWT1lXMWxJRzltSUZKbGJIbHBibWNnVUdGeWRIa3hIakFjQmdOVkJBTU1GVTVoYldVZ2IyWWdVbVZzZVdsdVp5QlFZWEowZVRFYU1CZ0dBMVVFVXd3UmFIUjBjSE02THk5c2IyTmhiR2h2YzNReEZUQVRCZ05WQkdFTURGQkJPa2xVTFdOZllqVXhPVEVMTUFrR0ExVUVCaE1DU1ZReEV6QVJCZ05WQkFjTUNrTmhiWEJ2WW1GemMyOHdnZ0dpTUEwR0NTcUdTSWIzRFFFQkFRVUFBNElCandBd2dnR0tBb0lCZ1FEV2JiWkZmQm15SGRSeFNKZXI3UDVObEZqdEp5ZmlSRXVOUERGZlNhVFlFWEIvNmNONnBVcitPaGxyRTRsR01pUDBaWXVOUVdnRjYvT2dwMW5EY1ZuSlJjVnlQSDkyc3A4MldYTUZnc0VkN3JEMWlkVExiSSswVVROMnlzUHgxS2p3dG1yeWgyWDBudHpsenRaQ3RBWVRIbE5uc1V5eXBFNFJ5MXRoamIzWGF5azVnc2ZaN3pHYXBoUW9rUVpkamtsZWVOUlZkeWhJK28wZmRQUW9JY3hxTjJ6aGhrb3ljOCtIU1MxT2ZocmkzZHVZTGkzTWNaMEpqTmF3UTJmWC9qblAvUHIxRVQ5TEtWVmtTNitPT21sQ3ZVa3FLOFd4alpIZHdGU0pyZ1hMb0VVQTg3bWxhMXlVb2ZLL1FYSVA2TmlvRktpRUVGcmI3Y0tLdmtxdW9sUXhveDBzTEJYSkNLMUtCbHJuZEJUVjVNNWlnbjduTFZUa1VRWGhrTGJpUG1qRElUTDFlL05lVWRlUzVPSnRPbVh3K21XenZQQk0yN0FTL0xReDVWb0FLeHZZQkpzUkJvQmxtL0pjWHVkOVFHWE5vSzBlbmQrbm5lV0g2YzFLVWNDYS83TnFGK05Ramw5Wk5JRHVJN0o2RWxjYzhhYmZOQ21jaUpITVpTYkt0UU1DQXdFQUFhTVhNQlV3RXdZRFZSMGdCQXd3Q2pBSUJnWXJUQkFFQWdFd0RRWUpLb1pJaHZjTkFRRUxCUUFEZ2dHQkFDWVNKYVJIVDJvbGl2WFhCOTNZTEhHOGZOdVcxSWRDem1YU1NXc2x2OXVzcnBzZnBCQi9qRnlJS1hSRlhtZlNlU09BZGhBOHZjM3VZaFg0emNaUHk0VFVUb0g5azlGajFXYzY2WExxSThUcFFPR043WDliOEgrdWFHWnBHSHBmSzBXcEFvdm5SYXR4NGx2L1NSZFB6QmN6SE04djVPZDNuRGZtQlpOK0FZSlkva3FLNUhYeldrUldySnJyTncvOHFiWGt4aUVJK2VQUXRjRzlpT1FSRi85QVJzUDJoaVBrcU5xTE5jOWZXWlVSbTFFUVcvMUJXdjJ5RHRsYUcxZHhrU3dWOEY3aG9kT2RSNlp3VXNMTThVWjRtQndmQlZNWlRqS3RrVkRtdzRFaHV6cUxqeHluUk5MZW10elFXbXJqRXJzMUk4R2Y5aHFpNnJsdDAxcGl3OHRObXY2Q3BueE91ZEhGQWlqRHkvczJGNXNSeG1ORllwOWxWRVFsbGJteU5jeXBKYVo4Sm1yMEdCNWpBZzg3TVdML2JXaEMxenJhYnVJdW5mU1VoWVhmVis4MWZkR3VRVmhLYWtDdkhKS1pDMHlOQk8vcVB5ZG5Wc2NQQjNjcEFoY1F5T2JLcFI5em9CcjFLaERvVG9JV3ZJdGRTdHZvTUU1eWQyaG9qZVc4SWc9PSIsIng1dCI6IlZ3aDZfX1hTN05qZGo1UF9xYXJGd1VRN0ZNWSIsIng1dCMyNTYiOiJweEFValV5OWVBRzlYdFFEdERRdk41OUlPOGtJSFJZX09QNFFjb3NHSk1FIiwidXNlIjoic2lnIn1dfSwicmVkaXJlY3RfdXJpcyI6WyJodHRwczovL2Rldi5saW5mYWJveC5pdC9vaWRjLy9vaWRjL3JlZGlyZWN0Il0sInJlc3BvbnNlX3R5cGVzIjpbImNvZGUiXSwic3ViamVjdF90eXBlIjoicGFpcndpc2UifX19.Ox69VXJWVgXPhTyj4TVEG65csfhSf9TSJ3TSwlKPnIcVVPak7uz46jb_jRR9bx2r-CX8b5c7ldaaN1-2FfQcAg", "http://iss");
        $this->assertNotNull($es);
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierValue
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierValue()
    {
        $es = new EntityStatement(null, "https://iss");
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise",
                       "public"
                   )
               )
           )
        )));
        $es->initFromObject($config);

        $this->assertEquals($config, $es->getPayload());

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierValue');

        $method->invokeArgs($es, array(
           'openid_provider',
           'subject_types_supported',
           array('pairwise')
        ));

        $new_config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise"
                   )
               )
           )
        )));

        $this->assertNotEquals($config, $es->getPayload());
        $this->assertEquals($new_config, $es->getPayload());
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierAdd
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierAdd()
    {
        $es = new EntityStatement(null, "https://iss");
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise"
                   )
               )
           )
        )));
        $es->initFromObject($config);

        $this->assertEquals($config, $es->getPayload());

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierAdd');

        $method->invokeArgs($es, array(
           'openid_provider',
           'subject_types_supported',
           array('public')
        ));

        $new_config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "subject_types_supported" => array(
                       "pairwise", "public"
                   )
               )
           )
        )));

        $this->assertNotEquals($config, $es->getPayload());
        $this->assertEquals($new_config, $es->getPayload());

        $method->invokeArgs($es, array(
            'openid_provider',
            'subject_types_supported',
            'pairwise'
         ));

         $new_config = json_decode(json_encode(array(
             "iss" => "https://iss",
            "metadata" => array(
                "openid_provider" => array(
                    "subject_types_supported" => "pairwise"
                )
            )
         )));

         $this->assertNotEquals($config, $es->getPayload());
         $this->assertEquals($new_config, $es->getPayload());
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierDefault
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierDefault()
    {
        $es = new EntityStatement(null, "https://iss");
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "logo_uri" => null,
                   "organization_name" => "Organization Name",
                   "op_policy_uri" => ""
               )
           )
        )));
        $es->initFromObject($config);

        $this->assertEquals($config, $es->getPayload());

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierDefault');

        $method->invokeArgs($es, array(
           'openid_provider',
           'logo_uri',
           'https://logo_default'
        ));

        $method->invokeArgs($es, array(
           'openid_provider',
           'organization_name',
           'The organization name should not be overwrited'
        ));

        $method->invokeArgs($es, array(
           'openid_provider',
           'op_policy_uri',
           'https://policy_default'
        ));

        $new_config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "logo_uri" => "https://logo_default",
                   "organization_name" => "Organization Name",
                   "op_policy_uri" => "https://policy_default"
               )
           )
        )));

        $this->assertNotEquals($config, $es->getPayload());
        $this->assertEquals($new_config, $es->getPayload());
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierOneOf
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierOneOf()
    {
        $config1 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "id_token_signing_alg" => "ES384"
               )
           )
        )));

        $config2 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "id_token_signing_alg" => "ES256"
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierOneOf');

        $es1 = new EntityStatement(null, "https://iss");
        $es1->initFromObject($config1);
        $this->assertEquals($config1, $es1->getPayload());

        try {
            $method->invokeArgs($es1, array(
                'openid_provider',
                'id_token_signing_alg',
                ['ES256', 'ES384']
            ));
        } catch (\Exception $e) {
            $this->fail("Must not be throw exception");
        }

        $es2 = new EntityStatement(null, "https://iss");
        $es2->initFromObject($config2);
        $this->assertEquals($config2, $es2->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (id_token_signing_alg must be one of [\"ES384\",\"ES512\"])");

        $method->invokeArgs($es2, array(
           'openid_provider',
           'id_token_signing_alg',
           ['ES384', 'ES512']
        ));
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierSubsetOf
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierSubsetOf()
    {
        $config1 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $config2 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "RS256",
                       "RS384",
                       "RS512",
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierSubsetOf');

        $es1 = new EntityStatement(null, "https://iss");
        $es1->initFromObject($config1);
        $this->assertEquals($config1, $es1->getPayload());

        try {
            $method->invokeArgs($es1, array(
                'openid_provider',
                'token_endpoint_auth_signing_alg_values_supported',
                ["RS512", "ES256", "ES384", "ES512"]
            ));
        } catch (\Exception $e) {
            $this->fail("Must not be throw exception");
        }

        $es2 = new EntityStatement(null, "https://iss");
        $es2->initFromObject($config2);
        $this->assertEquals($config2, $es2->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (token_endpoint_auth_signing_alg_values_supported must be subset of [\"RS512\",\"ES256\",\"ES384\",\"ES512\"])");

        $method->invokeArgs($es2, array(
           'openid_provider',
           'token_endpoint_auth_signing_alg_values_supported',
           ["RS512", "ES256", "ES384", "ES512"]
        ));
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierSupersetOf
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierSupersetOf()
    {
        $config1 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "RS256",
                       "RS384",
                       "RS512",
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $config2 = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "token_endpoint_auth_signing_alg_values_supported" => array(
                       "ES256",
                       "ES384",
                       "ES512"
                   )
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierSupersetOf');

        $es1 = new EntityStatement(null, "https://iss");
        $es1->initFromObject($config1);
        $this->assertEquals($config1, $es1->getPayload());

        try {
            $method->invokeArgs($es1, array(
                'openid_provider',
                'token_endpoint_auth_signing_alg_values_supported',
                ["RS512", "ES256", "ES384", "ES512"]
            ));
        } catch (\Exception $e) {
            $this->fail("Must not be throw exception");
        }

        $es2 = new EntityStatement(null, "https://iss");
        $es2->initFromObject($config2);
        $this->assertEquals($config2, $es2->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (token_endpoint_auth_signing_alg_values_supported must be superset of [\"RS512\",\"ES256\",\"ES384\",\"ES512\"])");

        $method->invokeArgs($es2, array(
           'openid_provider',
           'token_endpoint_auth_signing_alg_values_supported',
           ["RS512", "ES256", "ES384", "ES512"]
        ));
    }

    /**
     * @covers SPID_CIE_OIDC_PHP\Federation\EntityStatement::applyPolicyModifierEssential
     * @runInSeparateProcess
     */
    public function test_applyPolicyModifierEssential()
    {
        $config = json_decode(json_encode(array(
            "iss" => "https://iss",
           "metadata" => array(
               "openid_provider" => array(
                   "authorization_endpoint" => ""
               )
           )
        )));

        $method = $this->getPrivateMethod('EntityStatement', 'applyPolicyModifierEssential');

        $es = new EntityStatement(null, "https://iss");
        $es->initFromObject($config);
        $this->assertEquals($config, $es->getPayload());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed trust policy (authorization_endpoint must have a value)");

        $method->invokeArgs($es, array(
           'openid_provider',
           'authorization_endpoint',
           true
        ));
    }



    /**
     * getPrivateMethod
     *
     * @param string $className
     * @param string $methodName
     * @return ReflectionMethod
     */
    public function getPrivateMethod(string $className, string $methodName)
    {
        $reflector = new ReflectionClass('\\SPID_CIE_OIDC_PHP\\Federation\\' . $className);
        $method = $reflector->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
