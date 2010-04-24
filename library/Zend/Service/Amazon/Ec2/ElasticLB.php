<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Ec2
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Instance.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/**
 * @see Zend_Service_Amazon_Ec2_Abstract
 */
require_once 'Zend/Service/Amazon/Ec2/Abstract.php';

/**
 * @see Zend_Service_Amazon_Ec2_ElasticLB_Response
 */
require_once 'Zend/Service/Amazon/Ec2/ElasticLB/Response.php';

/**
 * An Amazon EC2 interface that allows yout to create, configure and delete 
 * Amazon Elastic Load Balancing load balancers, to be used together with
 * Amazon EC2 machine instances. 
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Ec2
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Amazon_Ec2_ElasticLB extends Zend_Service_Amazon_Ec2_Abstract
{
    /**
     * List of valid availability zones per region
     * 
     * @var array
     */
    protected $_validZones = array(
        'eu-west-1' => array('eu-west-1a', 'eu-west-1b'),
        'us-east-1' => array('us-east-1a', 'us-east-1b', 'us-east-1c', 'us-east-1d'),
        'us-west-1' => array('us-west-1a', 'us-west-1b'),
    );
    
    /**
     * The HTTP query server
     */
    protected $_ec2Endpoint = 'amazonaws.com';

    /**
     * The API version to use
     */
    protected $_ec2ApiVersion = '2009-11-25';
    
    /**
     * Create Amazon Elastic Load Balancing client.
     *
     * @param  string $access_key       Override the default Access Key
     * @param  string $secret_key       Override the default Secret Key
     * @param  string $region           Sets the AWS Region
     * @return void
     */
    public function __construct($accessKey = null, $secretKey = null, $region = null)
    {
        parent::__construct($accessKey, $secretKey, $region);
        
        if (! $this->_region) {
            $message = "Region must be set in order to use EC2 ELB";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
    }
    
    public function describe($lbnames = null)
    {
        $params = array(
            'Action' => 'DescribeLoadBalancers'
        );
        
        // Validate and set instance(s)
        $invalid = false;
        if (is_array($lbnames) && ! empty($lbnames)) {
            $i = 1;
            foreach($lbnames as $name) {
                if (! $this->_validateLbName($name)) {
                    $invalid = true;
                    break;
                }
                $params['LoadBalancerNames.member.' . $i++] = $name;
            }
        } elseif (is_string($lbnames) && $this->_validateLbName($lbnames)) {
            $params['LoadBalancerNames.member.1'] = $lbnames;

        } elseif ($lbnames !== null) {
            $invalid = true;
        }
        
        if ($invalid) {
            if (isset($name)) $lbnames = $name;
            $message = "Invalid load balancer name: '$lbnames'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $response = $this->sendRequest($params);

        $xpath = $response->getXPath();

        $return = array(
        );
        
        /*
        TODO: Implement me!
         
        $return['snapshotId']   = $xpath->evaluate('string(//ec2:snapshotId/text())');
        $return['volumeId']     = $xpath->evaluate('string(//ec2:volumeId/text())');
        $return['status']       = $xpath->evaluate('string(//ec2:status/text())');
        $return['startTime']    = $xpath->evaluate('string(//ec2:startTime/text())');
        $return['progress']     = $xpath->evaluate('string(//ec2:progress/text())');
        */
        
        return $return;
    }
    
    /**
     * Create a new load balancer
     * 
     * The returned array will contain one member: 'DNSName' - containing
     * the public host name of the created load balacner.
     *  
     * This action is idempotent - if you try to create a load balancer with
     * same same name as an existing one (in the same region), it will succeed
     * but no new load balancer is created. Instead, you will get the DNS name
     * of the existing LB. 
     * 
     * @param string                                           $name
     * @param string|array                                     $zone
     * @param Zend_Service_Amazon_Ec2_ElasticLB_Listener|array $listeners
     * 
     * @throws Zend_Service_Amazon_Ec2_Exception
     * @return array
     */
    public function create($name, $zone, $listeners)
    {
        // Validate load balancer name
        if (! $this->_validateLbName($name)) {                   
            $message = "Invalid load balancer name: '$name'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $params = array(
            'Action'           => 'CreateLoadBalancer',
            'LoadBalancerName' => $name
        );
        
        // Validate and set availability zone(s)
        $invalid = false;
        if (is_array($zone) && ! empty($zone)) {
            $i = 1;
            foreach($zone as $az) {
                if (! $this->_validateZone($az)) {
                    $invalid = true;
                    break;
                }
                $params['AvailabilityZones.member.' . $i++] = $az;
            }
        } elseif (is_string($zone)) {
            if ($this->_validateZone($zone)) {
                $params['AvailabilityZones.member.1'] = $zone;    
            } else {
                $invalid = true;
            }
        } else {
            $invalid = true;
        }
        
        if ($invalid) {
            if (isset($az)) $zone = $az;
            $message = "Invalid availability zone '$zone' for region '$this->_region'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        // Validate and set listeners
        if (is_array($listeners) && ! empty($listeners)) {
            $i = 1;
            foreach($listeners as $listener) {
                if ($listener instanceof Zend_Service_Amazon_Ec2_ElasticLB_Listener) {
                    $params = array_merge($params, $listener->toParametersArray($i++));
                } else {
                    $invalid = true;
                }
            }
            
        } elseif ($listeners instanceof Zend_Service_Amazon_Ec2_ElasticLB_Listener) {
            $params = array_merge($params, $listeners->toParametersArray(1));
            
        } else {
            $invalid = true;
        }
        
        if ($invalid) {
            $message = "Invalid listener, expecting a Zend_Service_Amazon_Ec2_ElasticLB_Listener object";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $response = $this->sendRequest($params);
        $this->_checkExpectedResponseType($response, 'CreateLoadBalancerResponse');
        $xpath = $response->getXPath();

        $return = array(
            'DNSName' => $xpath->evaluate('string(//ec2:DNSName/text())')
        );
        
        return $return;
    }
    
    /**
     * Delete an existing load balancer
     * 
     * @param string $name
     * 
     * @throws Zend_Service_Amazon_Ec2_Exception
     * @return boolean TRUE on success
     */
    public function delete($name)
    {
        // Validate load balancer name
        if (! $this->_validateLbName($name)) {                   
            $message = "Invalid load balancer name: '$name'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $params = array(
            'Action'           => 'DeleteLoadBalancer',
            'LoadBalancerName' => $name
        );
        
        $response = $this->sendRequest($params);
        $this->_checkExpectedResponseType($response, 'DeleteLoadBalancerResponse');
        
        return true;
    }
    
    public function registerInstances($name, $instances)
    {
        // Validate load balancer name
        if (! $this->_validateLbName($name)) {                   
            $message = "Invalid load balancer name: '$name'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $params = array(
            'Action'           => 'RegisterInstancesWithLoadBalancer',
            'LoadBalancerName' => $name
        );
        
        // Validate and set instance(s)
        $invalid = false;
        if (is_array($instances) && ! empty($instances)) {
            $i = 1;
            foreach($instances as $instance) {
                if (! (is_string($instance) && trim($instance))) {
                    $invalid = true;
                    break;
                }
                $params['Instances.member.' . $i++ . '.InstanceId'] = trim($instance);
            }
        } elseif (is_string($instances) && trim($instances)) {
            $params['Instances.member.1.InstanceId'] = trim($instances);
                
        } else {
            $invalid = true;
        }
        
        if ($invalid) {
            if (isset($instance)) $instances = $instance;
            $message = "Invalid instance ID '$instances'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $response = $this->sendRequest($params);
        $this->_checkExpectedResponseType($response, 
            'RegisterInstancesWithLoadBalancerResponse');
        
        $return = array(
            'Instances' => array()
        );
        
        $xpath = $response->getXPath();
        $instanceIds = $xpath->evaluate('//ec2:Instances/ec2:member/ec2:InstanceId');
        foreach ($instanceIds as $iid) {
            $return['Instances'][] = $iid->nodeValue;
        }
        
        return $return;
    }

    /**
     * Check that a response is of the expected type
     * 
     * @param Zend_Service_Amazon_Ec2_ElasticLB_Response $response
     * @param string                                     $expectedType
     * 
     * @throws Zend_Service_Amazon_Ec2_Exception
     */
    protected function _checkExpectedResponseType(Zend_Service_Amazon_Ec2_ElasticLB_Response $response, 
        $expectedType) 
    {
        $gotType = $response->getDocument()->documentElement->localName;
        if ($gotType != $expectedType) {
            $message = "Unexpected response type: expected '$expectedType', got '$gotType'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
    }
    
    /**
     * Validate a load balancer name according to the EC2 requirements
     * 
     * @param  string $name
     * @return boolean
     */
    protected function _validateLbName($name)
    {
        return is_string($name) && 
               preg_match('/^[\p{L}0-9][\p{L}0-9-]{0,31}$(?<!-)/', $name);
    }
    
    /**
     * Validate a provided availability zone
     * 
     * @param  string $zone
     * @return boolean
     */
    protected function _validateZone($zone)
    {
        if (! in_array($zone, $this->_validZones[$this->_region])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sends a HTTP request to the AWS service using Zend_Http_Client
     *
     * @param  array $params List of parameters to send with the request
     * @return Zend_Service_Amazon_Ec2_Response
     * @throws Zend_Service_Amazon_Ec2_Exception
     */
    protected function sendRequest(array $params = array())
    {
        $url = 'https://elasticloadbalancing.' . $this->_getRegion() . $this->_ec2Endpoint . '/';

        try {
            /* @var $request Zend_Http_Client */
            $request = self::getHttpClient();
            $request->resetParameters();

            $request->setConfig(array(
                'timeout' => $this->_httpTimeout
            ));

            $request->setUri($url);
            $request->setMethod(Zend_Http_Client::POST);
            
            $params = $this->addRequiredParameters($params);    
            $request->setParameterPost($params);

            $httpResponse = $request->request();
            
        } catch (Zend_Http_Client_Exception $zhce) {
            $message = 'Error in request to AWS service: ' . $zhce->getMessage();
            throw new Zend_Service_Amazon_Ec2_Exception($message, $zhce->getCode(), $zhce);
        }
        
        $response = new Zend_Service_Amazon_Ec2_ElasticLB_Response($httpResponse, $this->_ec2ApiVersion);
        $this->_checkForErrors($response);

        return $response;
    }
    
    /**
     * Computes the RFC 2104-compliant HMAC signature for request parameters
     *
     * This implements the Amazon Web Services signature, as per the following
     * specification:
     *
     * 1. Sort all request parameters (including <tt>SignatureVersion</tt> and
     *    excluding <tt>Signature</tt>, the value of which is being created),
     *    ignoring case.
     *
     * 2. Iterate over the sorted list and append the parameter name (in its
     *    original case) and then its value. Do not URL-encode the parameter
     *    values before constructing this string. Do not use any separator
     *    characters when appending strings.
     *
     * @param array  $parameters the parameters for which to get the signature.
     * @param string $secretKey  the secret key to use to sign the parameters.
     *
     * @return string the signed data.
     */
    protected function signParameters(array $paramaters)
    {
        $data = "POST\n";
        $data .= $this->getHttpClient()->getUri()->getHost() . "\n";
        $data .= "/\n";

        uksort($paramaters, 'strcmp');
        unset($paramaters['Signature']);

        $arrData = array();
        foreach($paramaters as $key => $value) {
            $arrData[] = $key . '=' . str_replace("%7E", "~", rawurlencode($value));
        }

        $data .= implode('&', $arrData);

        require_once 'Zend/Crypt/Hmac.php';
        $hmac = Zend_Crypt_Hmac::compute($this->_getSecretKey(), 'SHA256', $data, Zend_Crypt_Hmac::BINARY);

        return base64_encode($hmac);
    }
    
    /**
     * Checks for errors responses from Amazon
     *
     * @param Zend_Service_Amazon_Ec2_ElasticLB_Response $response the response object to
     *                                                             check.
     *
     * @return void
     *
     * @throws Zend_Service_Amazon_Ec2_Exception if one or more errors are
     *         returned from Amazon.
     */
    protected function _checkForErrors(Zend_Service_Amazon_Ec2_ElasticLB_Response $response)
    {
        $xpath = $response->getXPath();
        
        $list  = $xpath->query('//ec2:Error');
        if ($list->length > 0) {
            $node    = $list->item(0);
            $code    = $xpath->evaluate('string(ec2:Code/text())', $node);
            $message = $xpath->evaluate('string(ec2:Message/text())', $node);
            throw new Zend_Service_Amazon_Ec2_Exception($message, 0, $code);
        }
    }
}