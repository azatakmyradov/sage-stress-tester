<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class SOAP {

    /*
     * Guzzle Client
     */
    protected Client $client;

    /*
     * SOAP wsdl address
     */
    protected string $wsdl;

    /*
     * SOAP authentication
     */
    protected array $auth;

    /*
     * SOAP context
     */
    protected array $context;

    public function __construct($wsdl, $auth, $context = ['lang' => 'ENG', 'poolAlias' => 'SEED']) {
        $this->client = new Client([
            'auth' => [$auth['login'], $auth['password']]
        ]);

        $this->auth = $auth;
        $this->context = $context;
        $this->wsdl = $wsdl;
    }

    /*
     * Handle request
     *
     * @param $method Soap method
     * @param $action Soap action
     * @param $params Soap params
     *
     * @return Response
     */
    public function request($method, $action, $params) {
        $request = $this->getRequest($method, $action, $params);

        return $this->client->sendAsync($request)->wait();
    }

    /*
     * Create SOAP request
     *
     * @param $method Soap method
     * @param $action Soap action
     * @param $params Soap params
     *
     * @return Request
     */
    public function getRequest($method, $action, $params): Request
    {
        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => $method
        ];

        $params = (string) json_encode($params);

        $body = "<soapenv:Envelope xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns:xsd='http://www.w3.org/2001/XMLSchema' xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:wss='http://www.adonix.com/WSS'>
                    <soapenv:Header/>
                    <soapenv:Body>
                        <wss:run soapenv:encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'>
                            <callContext xsi:type='wss:CAdxCallContext'>
                                <codeLang xsi:type='xsd:string'>{$this->context['lang']}</codeLang>
                                <poolAlias xsi:type='xsd:string'>{$this->context['poolAlias']}</poolAlias>
                                <poolId xsi:type='xsd:string'></poolId>
                                <requestConfig xsi:type='xsd:string'>adxwss.beautify=true&adxwss.optreturn=JSON</requestConfig>
                            </callContext>
                            <publicName xsi:type='xsd:string'>{$action}</publicName>
                            <inputXml xsi:type='xsd:string'>{$params}</inputXml>
                            </wss:run>
                        </soapenv:Body>
                    </soapenv:Envelope>";

        return new Request('POST', $this->wsdl, $headers, $body);
    }

    public function __call($method, $args) {
        return $this->request($method, ...$args);
    }

    public function getGuzzleClient(): Client
    {
        return $this->client;
    }

    public static function setUp($config): SOAP
    {
        return new static($config['wsdl'], [
            'login' => $config['auth']['login'],
            'password' => $config['auth']['password']
        ]);
    }

}