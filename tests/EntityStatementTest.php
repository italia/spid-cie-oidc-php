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

        $es = new EntityStatement("eyJ0eXAiOiJlbnRpdHktc3RhdGVtZW50K2p3dCIsImFsZyI6IlJTMjU2Iiwia2lkIjoiNmE3ZmNjZmQ0ZjY3ZDY1ZjdjOTBlOTAyZWU1OWJhYTljZjUyYTA2NzU2YWEzYzgyYzQyOTVhZWQ1ZTM2YmU4NSJ9.eyJpc3MiOiJodHRwczpcL1wvZGV2LmxpbmZhYm94Lml0XC9vaWRjXC8iLCJzdWIiOiJodHRwczpcL1wvZGV2LmxpbmZhYm94Lml0XC9vaWRjXC8iLCJpYXQiOjE2NDk2NzY3ODQsImV4cCI6MTY4MTIxMjc4NCwiandrcyI6eyJrZXlzIjpbeyJraWQiOiI2YTdmY2NmZDRmNjdkNjVmN2M5MGU5MDJlZTU5YmFhOWNmNTJhMDY3NTZhYTNjODJjNDI5NWFlZDVlMzZiZTg1Iiwia3R5IjoiUlNBIiwibiI6IjFtMjJSWHdac2gzVWNVaVhxLXotVFpSWTdTY240a1JMalR3eFgwbWsyQkZ3Zi1uRGVxVktfam9aYXhPSlJqSWo5R1dMalVGb0JldnpvS2RadzNGWnlVWEZjanhfZHJLZk5sbHpCWUxCSGU2dzlZblV5MnlQdEZFemRzckQ4ZFNvOExacThvZGw5SjdjNWM3V1FyUUdFeDVUWjdGTXNxUk9FY3RiWVkyOTEyc3BPWUxIMmU4eG1xWVVLSkVHWFk1SlhualVWWGNvU1BxTkgzVDBLQ0hNYWpkczRZWktNblBQaDBrdFRuNGE0dDNibUM0dHpIR2RDWXpXc0VObjFfNDV6X3o2OVJFX1N5bFZaRXV2ampwcFFyMUpLaXZGc1kyUjNjQlVpYTRGeTZCRkFQTzVwV3RjbEtIeXYwRnlELWpZcUJTb2hCQmEyLTNDaXI1S3JxSlVNYU1kTEN3VnlRaXRTZ1phNTNRVTFlVE9Zb0otNXkxVTVGRUY0WkMyNGo1b3d5RXk5WHZ6WGxIWGt1VGliVHBsOFBwbHM3endUTnV3RXZ5ME1lVmFBQ3NiMkFTYkVRYUFaWnZ5WEY3bmZVQmx6YUN0SHAzZnA1M2xoLW5OU2xIQW12LXphaGZqVUk1ZldUU0E3aU95ZWhKWEhQR20zelFwbklpUnpHVW15clVEIiwiZSI6IkFRQUIiLCJ4NWMiOiJNSUlFMFRDQ0F6bWdBd0lCQWdJVVk1MzkzYUJjYVVDTXBzWEZIdUZZS2VkMUF2a3dEUVlKS29aSWh2Y05BUUVMQlFBd2daVXhIakFjQmdOVkJBb01GVTVoYldVZ2IyWWdVbVZzZVdsdVp5QlFZWEowZVRFZU1Cd0dBMVVFQXd3VlRtRnRaU0J2WmlCU1pXeDVhVzVuSUZCaGNuUjVNUm93R0FZRFZRUlREQkZvZEhSd2N6b3ZMMnh2WTJGc2FHOXpkREVWTUJNR0ExVUVZUXdNVUVFNlNWUXRZMTlpTlRFNU1Rc3dDUVlEVlFRR0V3SkpWREVUTUJFR0ExVUVCd3dLUTJGdGNHOWlZWE56YnpBZUZ3MHlNakF6TWpJeE56TTNOVGRhRncweU5EQXpNakV4TnpNM05UZGFNSUdWTVI0d0hBWURWUVFLREJWT1lXMWxJRzltSUZKbGJIbHBibWNnVUdGeWRIa3hIakFjQmdOVkJBTU1GVTVoYldVZ2IyWWdVbVZzZVdsdVp5QlFZWEowZVRFYU1CZ0dBMVVFVXd3UmFIUjBjSE02THk5c2IyTmhiR2h2YzNReEZUQVRCZ05WQkdFTURGQkJPa2xVTFdOZllqVXhPVEVMTUFrR0ExVUVCaE1DU1ZReEV6QVJCZ05WQkFjTUNrTmhiWEJ2WW1GemMyOHdnZ0dpTUEwR0NTcUdTSWIzRFFFQkFRVUFBNElCandBd2dnR0tBb0lCZ1FEV2JiWkZmQm15SGRSeFNKZXI3UDVObEZqdEp5ZmlSRXVOUERGZlNhVFlFWEJcLzZjTjZwVXIrT2hsckU0bEdNaVAwWll1TlFXZ0Y2XC9PZ3AxbkRjVm5KUmNWeVBIOTJzcDgyV1hNRmdzRWQ3ckQxaWRUTGJJKzBVVE4yeXNQeDFLand0bXJ5aDJYMG50emx6dFpDdEFZVEhsTm5zVXl5cEU0UnkxdGhqYjNYYXlrNWdzZlo3ekdhcGhRb2tRWmRqa2xlZU5SVmR5aEkrbzBmZFBRb0ljeHFOMnpoaGtveWM4K0hTUzFPZmhyaTNkdVlMaTNNY1owSmpOYXdRMmZYXC9qblBcL1ByMUVUOUxLVlZrUzYrT09tbEN2VWtxSzhXeGpaSGR3RlNKcmdYTG9FVUE4N21sYTF5VW9mS1wvUVhJUDZOaW9GS2lFRUZyYjdjS0t2a3F1b2xReG94MHNMQlhKQ0sxS0Jscm5kQlRWNU01aWduN25MVlRrVVFYaGtMYmlQbWpESVRMMWVcL05lVWRlUzVPSnRPbVh3K21XenZQQk0yN0FTXC9MUXg1Vm9BS3h2WUJKc1JCb0JsbVwvSmNYdWQ5UUdYTm9LMGVuZCtubmVXSDZjMUtVY0NhXC83TnFGK05Ramw5Wk5JRHVJN0o2RWxjYzhhYmZOQ21jaUpITVpTYkt0UU1DQXdFQUFhTVhNQlV3RXdZRFZSMGdCQXd3Q2pBSUJnWXJUQkFFQWdFd0RRWUpLb1pJaHZjTkFRRUxCUUFEZ2dHQkFDWVNKYVJIVDJvbGl2WFhCOTNZTEhHOGZOdVcxSWRDem1YU1NXc2x2OXVzcnBzZnBCQlwvakZ5SUtYUkZYbWZTZVNPQWRoQTh2YzN1WWhYNHpjWlB5NFRVVG9IOWs5RmoxV2M2NlhMcUk4VHBRT0dON1g5YjhIK3VhR1pwR0hwZkswV3BBb3ZuUmF0eDRsdlwvU1JkUHpCY3pITTh2NU9kM25EZm1CWk4rQVlKWVwva3FLNUhYeldrUldySnJyTndcLzhxYlhreGlFSStlUFF0Y0c5aU9RUkZcLzlBUnNQMmhpUGtxTnFMTmM5ZldaVVJtMUVRV1wvMUJXdjJ5RHRsYUcxZHhrU3dWOEY3aG9kT2RSNlp3VXNMTThVWjRtQndmQlZNWlRqS3RrVkRtdzRFaHV6cUxqeHluUk5MZW10elFXbXJqRXJzMUk4R2Y5aHFpNnJsdDAxcGl3OHRObXY2Q3BueE91ZEhGQWlqRHlcL3MyRjVzUnhtTkZZcDlsVkVRbGxibXlOY3lwSmFaOEptcjBHQjVqQWc4N01XTFwvYldoQzF6cmFidUl1bmZTVWhZWGZWKzgxZmRHdVFWaEtha0N2SEpLWkMweU5CT1wvcVB5ZG5Wc2NQQjNjcEFoY1F5T2JLcFI5em9CcjFLaERvVG9JV3ZJdGRTdHZvTUU1eWQyaG9qZVc4SWc9PSIsIng1dCI6IlZ3aDZfX1hTN05qZGo1UF9xYXJGd1VRN0ZNWSIsIng1dCMyNTYiOiJweEFValV5OWVBRzlYdFFEdERRdk41OUlPOGtJSFJZX09QNFFjb3NHSk1FIiwidXNlIjoic2lnIn1dfSwiYXV0aG9yaXR5X2hpbnRzIjpbImh0dHA6XC9cL3NwaWQtY2llLW9pZGMtdGVzdC5saW5mYXNlcnZpY2UuaXQ6ODAwMFwvIl0sInRydXN0X21hcmtzIjpbXSwibWV0YWRhdGEiOnsib3BlbmlkX3JlbHlpbmdfcGFydHkiOnsiYXBwbGljYXRpb25fdHlwZSI6IndlYiIsImNsaWVudF9yZWdpc3RyYXRpb25fdHlwZXMiOlsiYXV0b21hdGljIl0sImNsaWVudF9uYW1lIjoiUmVseWluZyBQYXJ0eSBQSFAiLCJjb250YWN0cyI6WyJycEBleGFtcGxlLml0Il0sImdyYW50X3R5cGVzIjpbImF1dGhvcml6YXRpb25fY29kZSJdLCJqd2tzIjp7ImtleXMiOlt7ImtpZCI6IjZhN2ZjY2ZkNGY2N2Q2NWY3YzkwZTkwMmVlNTliYWE5Y2Y1MmEwNjc1NmFhM2M4MmM0Mjk1YWVkNWUzNmJlODUiLCJrdHkiOiJSU0EiLCJuIjoiMW0yMlJYd1pzaDNVY1VpWHEtei1UWlJZN1NjbjRrUkxqVHd4WDBtazJCRndmLW5EZXFWS19qb1pheE9KUmpJajlHV0xqVUZvQmV2em9LZFp3M0ZaeVVYRmNqeF9kcktmTmxsekJZTEJIZTZ3OVluVXkyeVB0RkV6ZHNyRDhkU284TFpxOG9kbDlKN2M1YzdXUXJRR0V4NVRaN0ZNc3FST0VjdGJZWTI5MTJzcE9ZTEgyZTh4bXFZVUtKRUdYWTVKWG5qVVZYY29TUHFOSDNUMEtDSE1hamRzNFlaS01uUFBoMGt0VG40YTR0M2JtQzR0ekhHZENZeldzRU5uMV80NXpfejY5UkVfU3lsVlpFdXZqanBwUXIxSktpdkZzWTJSM2NCVWlhNEZ5NkJGQVBPNXBXdGNsS0h5djBGeUQtallxQlNvaEJCYTItM0NpcjVLcnFKVU1hTWRMQ3dWeVFpdFNnWmE1M1FVMWVUT1lvSi01eTFVNUZFRjRaQzI0ajVvd3lFeTlYdnpYbEhYa3VUaWJUcGw4UHBsczd6d1ROdXdFdnkwTWVWYUFDc2IyQVNiRVFhQVpadnlYRjduZlVCbHphQ3RIcDNmcDUzbGgtbk5TbEhBbXYtemFoZmpVSTVmV1RTQTdpT3llaEpYSFBHbTN6UXBuSWlSekdVbXlyVUQiLCJlIjoiQVFBQiIsIng1YyI6Ik1JSUUwVENDQXptZ0F3SUJBZ0lVWTUzOTNhQmNhVUNNcHNYRkh1RllLZWQxQXZrd0RRWUpLb1pJaHZjTkFRRUxCUUF3Z1pVeEhqQWNCZ05WQkFvTUZVNWhiV1VnYjJZZ1VtVnNlV2x1WnlCUVlYSjBlVEVlTUJ3R0ExVUVBd3dWVG1GdFpTQnZaaUJTWld4NWFXNW5JRkJoY25SNU1Sb3dHQVlEVlFSVERCRm9kSFJ3Y3pvdkwyeHZZMkZzYUc5emRERVZNQk1HQTFVRVlRd01VRUU2U1ZRdFkxOWlOVEU1TVFzd0NRWURWUVFHRXdKSlZERVRNQkVHQTFVRUJ3d0tRMkZ0Y0c5aVlYTnpiekFlRncweU1qQXpNakl4TnpNM05UZGFGdzB5TkRBek1qRXhOek0zTlRkYU1JR1ZNUjR3SEFZRFZRUUtEQlZPWVcxbElHOW1JRkpsYkhscGJtY2dVR0Z5ZEhreEhqQWNCZ05WQkFNTUZVNWhiV1VnYjJZZ1VtVnNlV2x1WnlCUVlYSjBlVEVhTUJnR0ExVUVVd3dSYUhSMGNITTZMeTlzYjJOaGJHaHZjM1F4RlRBVEJnTlZCR0VNREZCQk9rbFVMV05mWWpVeE9URUxNQWtHQTFVRUJoTUNTVlF4RXpBUkJnTlZCQWNNQ2tOaGJYQnZZbUZ6YzI4d2dnR2lNQTBHQ1NxR1NJYjNEUUVCQVFVQUE0SUJqd0F3Z2dHS0FvSUJnUURXYmJaRmZCbXlIZFJ4U0plcjdQNU5sRmp0SnlmaVJFdU5QREZmU2FUWUVYQlwvNmNONnBVcitPaGxyRTRsR01pUDBaWXVOUVdnRjZcL09ncDFuRGNWbkpSY1Z5UEg5MnNwODJXWE1GZ3NFZDdyRDFpZFRMYkkrMFVUTjJ5c1B4MUtqd3RtcnloMlgwbnR6bHp0WkN0QVlUSGxObnNVeXlwRTRSeTF0aGpiM1hheWs1Z3NmWjd6R2FwaFFva1FaZGprbGVlTlJWZHloSStvMGZkUFFvSWN4cU4yemhoa295YzgrSFNTMU9maHJpM2R1WUxpM01jWjBKak5hd1EyZlhcL2puUFwvUHIxRVQ5TEtWVmtTNitPT21sQ3ZVa3FLOFd4alpIZHdGU0pyZ1hMb0VVQTg3bWxhMXlVb2ZLXC9RWElQNk5pb0ZLaUVFRnJiN2NLS3ZrcXVvbFF4b3gwc0xCWEpDSzFLQmxybmRCVFY1TTVpZ243bkxWVGtVUVhoa0xiaVBtakRJVEwxZVwvTmVVZGVTNU9KdE9tWHcrbVd6dlBCTTI3QVNcL0xReDVWb0FLeHZZQkpzUkJvQmxtXC9KY1h1ZDlRR1hOb0swZW5kK25uZVdINmMxS1VjQ2FcLzdOcUYrTlFqbDlaTklEdUk3SjZFbGNjOGFiZk5DbWNpSkhNWlNiS3RRTUNBd0VBQWFNWE1CVXdFd1lEVlIwZ0JBd3dDakFJQmdZclRCQUVBZ0V3RFFZSktvWklodmNOQVFFTEJRQURnZ0dCQUNZU0phUkhUMm9saXZYWEI5M1lMSEc4Zk51VzFJZEN6bVhTU1dzbHY5dXNycHNmcEJCXC9qRnlJS1hSRlhtZlNlU09BZGhBOHZjM3VZaFg0emNaUHk0VFVUb0g5azlGajFXYzY2WExxSThUcFFPR043WDliOEgrdWFHWnBHSHBmSzBXcEFvdm5SYXR4NGx2XC9TUmRQekJjekhNOHY1T2QzbkRmbUJaTitBWUpZXC9rcUs1SFh6V2tSV3JKcnJOd1wvOHFiWGt4aUVJK2VQUXRjRzlpT1FSRlwvOUFSc1AyaGlQa3FOcUxOYzlmV1pVUm0xRVFXXC8xQld2MnlEdGxhRzFkeGtTd1Y4Rjdob2RPZFI2WndVc0xNOFVaNG1Cd2ZCVk1aVGpLdGtWRG13NEVodXpxTGp4eW5STkxlbXR6UVdtcmpFcnMxSThHZjlocWk2cmx0MDFwaXc4dE5tdjZDcG54T3VkSEZBaWpEeVwvczJGNXNSeG1ORllwOWxWRVFsbGJteU5jeXBKYVo4Sm1yMEdCNWpBZzg3TVdMXC9iV2hDMXpyYWJ1SXVuZlNVaFlYZlYrODFmZEd1UVZoS2FrQ3ZISktaQzB5TkJPXC9xUHlkblZzY1BCM2NwQWhjUXlPYktwUjl6b0JyMUtoRG9Ub0lXdkl0ZFN0dm9NRTV5ZDJob2plVzhJZz09IiwieDV0IjoiVndoNl9fWFM3TmpkajVQX3FhckZ3VVE3Rk1ZIiwieDV0IzI1NiI6InB4QVVqVXk5ZUFHOVh0UUR0RFF2TjU5SU84a0lIUllfT1A0UWNvc0dKTUUiLCJ1c2UiOiJzaWcifV19LCJyZWRpcmVjdF91cmlzIjpbImh0dHBzOlwvXC9kZXYubGluZmFib3guaXRcL29pZGNcL1wvb2lkY1wvcmVkaXJlY3QiXSwicmVzcG9uc2VfdHlwZXMiOlsiY29kZSJdLCJzdWJqZWN0X3R5cGUiOiJwYWlyd2lzZSJ9fX0.jjek4zIEbW5JgCBmX5tQ2RKM1kRpyMd6BaIdrPVGnWVk-aCfm5izkAp8gN5anXDK5xBczbYTWscwpzo0I1Lc1xz8aolRe_bH_Qf0Z9nY1bbM9jlk85qTz98QYa-kIxhOX2h13IFtxC8_PBJALv7CGc5XnYvyYbUYaVzyKDL9WtH0br2h5OeZw_Zn1sFNgPi6c_vMh1gNIV-9brkjOVwFReY95KRZG36EaMH5rse-lP877fGIHprNq-nSqcnU3eiItd7wxqEEwS9pCGT6B4hzqC_6wB1HU0ZHS-j6JP_0Nq2aB2sHb3HZp8HV9XU18aovZFIs7sM3mEFlOlaDFa_bSbJmxES9wDBxfNNFB2d8GoyWAhpwvkRjp6Y3ngAwTr_-rmHqAorbzep37O4PDYTbd1TnXNgHN7QanCJGB9y03rKIEfFI3m-26g4-IeUm8-__vwF3aL05KzsCmzv0W27u0m9cK04-bti1bwJ_ShQMwMHSrjihZDUUroswlVsmdsUO", "http://iss");
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
