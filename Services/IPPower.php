<?php

require_once 'Services/IPPower/Exception.php';
require_once 'HTTP/Request2.php';

class Services_IPPower
{
    const OUTLET_ONE   = 'P60';
    const OUTLET_TWO   = 'P61';
    const OUTLET_THREE = 'P62';
    const OUTLET_FOUR  = 'P63';

    const STATE_ON  = 1;
    const STATE_OFF = 0;

    const LOG_EMERG   = 0; /* System is unusable */
    const LOG_ALERT   = 1; /* Immediate action required */
    const LOG_CRIT    = 2; /* Critical conditions */
    const LOG_ERR     = 3; /* Error conditions */
    const LOG_WARNING = 4; /* Warning conditions */
    const LOG_NOTICE  = 5; /* Normal but significant */
    const LOG_INFO    = 6; /* Informational */
    const LOG_DEBUG   = 7; /* Debug-level messages */

    protected $_http = null;
    protected $_log  = null;
    protected $_host = '192.168.10.100';
    protected $_user = 'admin';
    protected $_pass = '12345678';
    protected $_path = '/Set.cmd';

    protected $_allowedOptions = array(
        'host',
        'user',
        'pass'
    );

    protected $_availableOutlets = array(
        self::OUTLET_ONE,
        self::OUTLET_TWO,
        self::OUTLET_THREE,
        self::OUTLET_FOUR
    );

    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    public function setOption($name, $value)
    {
        if (!in_array($name, $this->_allowedOptions)) {
            throw new Services_IPPower_Exception('Invalid option: ' . $name);
        }

        $protectedName          = '_' . $name;
        $this->{$protectedName} = $value;

        $this->log(
            'Set option ' . $name . ' to a value of ' . $value,
            self::LOG_DEBUG
        );
    }

    public function accept($object)
    {
        if ($object instanceof Log) {
            $this->_log = $object;
            return;
        } else if ($object instanceof HTTP_Request2) {
            $this->_http = $object;
            return;
        }

        throw new Services_IPPower_Exception('Invalid argument to ' . __METHOD__);
    }

    public function getHttp()
    {
        if ($this->_http === null) {
            $this->_http = new HTTP_Request2();
            $this->log(
                'No instance of HTTP_Request2, set, creating',
                self::LOG_DEBUG
            );
        }

        return $this->_http;
    }

    public function log($message, $priority = self::LOG_DEBUG)
    {
        if ($this->_log === null) {
            return;
        }

        return $this->_log->log($message, $priority);
    }

    public function setPowerMulti(array $outlets)
    {
        $params   = array();
        $expected = array();
        foreach ($outlets as $outlet => $state) {
            if (!in_array($outlet, $this->_availableOutlets)) {
                throw new Services_IPPower_Exception('Invalid outlet: ' . $outlet);
            }

            if ($state !== self::STATE_ON && $state !== self::STATE_OFF) {
                throw new Services_IPPower_Exception('Invalid state: ' . $state);
            }

            $params[]          = $outlet . '=' . $state;
            $expected[$outlet] = $state;
        }

        $http = $this->getHttp();
        $this->log('Setting auth: ' . $this->_user . ':' . $this->_pass, self::LOG_DEBUG);
        $url = $this->_formatUrl('SetPower', $params);
        $http->setAuth($this->_user, $this->_pass);
        $http->setUrl($url);
        $this->log('Sending request: ' . $url, self::LOG_DEBUG);

        try {
            $response = $http->send();
        } catch (HTTP_Request2_Exception $e) {
            throw new Services_IPPower_Exception(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if ($response->getStatus() != '200') {
            throw new Services_IPPower_Exception(
                'Non-200 response code: ' . $response->getStatus()
            );
        }

        $body = $response->getBody();

        $this->log('Response: ' . $body);

        $parsed = $this->_parseResponse($body);
        $status = true;

        foreach ($expected as $key => $value) {
            if (!isset($parsed[$key]) || $parsed[$key] != $value) {
                var_dump($parsed);
                $this->log('Unexpected value for key ' . $key . ': ' . $value);
                $status = false;
            }
        }

        return $status;
    }

    protected function _parseResponse($response)
    {
        if (strstr($response, 'HTTPCMD_') !== false) {
            throw new Services_IPPower_Exception(
                'Error response: ' . $response
            );
        }

        preg_match('!^<html>(.*)</html>!', $response, $matches);
        $exploded = explode(',', $matches[1]);
        $pairs = array();
        foreach ($exploded as $pair) {
            list ($key, $value) = explode('=', $pair);
            $pairs[$key] = $value;
        }

        return $pairs;
    }

    protected function _formatUrl($cmd, $params)
    {
        array_unshift($params, 'CMD' . '=' . $cmd);
        return 'http://' . $this->_host . $this->_path . '?' . implode('+', $params);
    }

    public function setPower($outlet, $state)
    {
        return $this->setPowerMulti(array($outlet => $state));
    }

    // TODO
    public function getPower($outlet = '')
    {
        return 1;
    }
}
