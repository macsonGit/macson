<?php

namespace Custom\SermepaBundle\Client\Authentication;

use JMS\Payment\CoreBundle\BrowserKit\Request;


class TokenAuthenticationStrategy implements AuthenticationStrategyInterface
{
    protected $username;
    protected $terminal;

    public function __construct($username, $terminal)
    {
        $this->username = $username;
        $this->password = $terminal;
    }

    public function authenticate(Request $request)
    {
        $request->request->set('Ds_Merchant_Terminal', $this->password);
        $request->request->set('Ds_Merchant_MerchantCode', $this->username);
    }

    public function getApiEndpoint($isDebug)
    {
        if ($isDebug) {
            return 'https://sis-t.redsys.es:25443/sis/realizarPago';
        }
        else {
            return 'https://sis.sermepa.es/sis/realizarPago';
        }
    }
}
