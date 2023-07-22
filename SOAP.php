<?php

class SOAP
{
    public $client = null;

    public $callContext = null;

    /**
     * @throws SoapFault
     */
    public function __construct($wsdl, $options)
    {
        $this->client = new SoapClient($wsdl, [
            'login' => $options['login'],
            'password' => $options['password']
        ]);

        $this->callContext = [
            'codeLang' => $options['codeLang'] ?? 'ENG',
            'poolAlias' => $options['poolAlias'],
            'requestConfig' => $options['requestConfig'] ?? 'adxwss.beautify=true&adxwss.optreturn=JSON'
        ];
    }

    /**
     * @throws SoapFault
     */
    public function request($action, $method, ...$args)
    {
        return $this->client->$action($this->callContext, ...$method, ...$args);
    }
    
    public function __call($method, $args)
    {
        return $this->request($method, $args);
    }

    public function createParams($params)
    {
        $fields = [];

        foreach ($params as $field => $value) {
            $fields[] = "<FLD NAME=\"{$field}\">{$value}</FLD>";
        }

        $fields = implode('', $fields);

        $xml = "<PARAM>$fields</PARAM>";

        return <<<EOD
        $xml
        EOD;
    }

    public static function setUp() {

    }

}