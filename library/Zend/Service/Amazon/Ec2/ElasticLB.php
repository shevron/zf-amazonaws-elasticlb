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
    
    /**
     * Get information about one, a set of or all running load balancers
     *  
     * This method may be called with a single load balancer name passed in as
     * a string, or a set or load balancer names passed as an array of strings. 
     * In both cases, information about the requested load balancers will be 
     * returned as an array.
     * 
     * If no parameter is passed, information will be returned on all running
     * load balancers owned by the user in the current EC2 region.
     * 
     * @param  null|string|array $lbnames
     * @return array 
     */
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
        $this->_checkExpectedResponseType($response, 'DescribeLoadBalancersResponse');
        
        $xpath = $response->getXPath();
        $return = array();
        $lbDescriptions = $xpath->evaluate('//ec2:LoadBalancerDescriptions/ec2:member');
        
        // Iterate over all returned load balancers
        foreach($lbDescriptions as $lbDescNode) {
            
            // Extract basic parameters
            $lbDesc = array(
                'LoadBalancerName' => $xpath->evaluate('string(ec2:LoadBalancerName/text())', $lbDescNode),  
                'CreatedTime'      => $xpath->evaluate('string(ec2:CreatedTime/text())', $lbDescNode),
                'DNSName'          => $xpath->evaluate('string(ec2:DNSName/text())', $lbDescNode),
            );
            
            // Extract availability zones
            $data = array();
            foreach($xpath->evaluate('ec2:AvailabilityZones/ec2:member', $lbDescNode) as $dataNode) {
                $data[] = $dataNode->nodeValue;
            }
            $lbDesc['AvailabilityZones'] = $data;
             
            // Extract instances
            $data = array();
            foreach($xpath->evaluate('ec2:Instances/ec2:member/ec2:InstanceId', $lbDescNode) as $dataNode) {
                $data[] = $dataNode->nodeValue;
            }
            $lbDesc['Instances'] = $data;
            
            // Extract health check info
            $lbDesc['HealthCheck'] = array(
                'Interval'           => (int) $xpath->evaluate('string(ec2:HealthCheck/ec2:Interval)', $lbDescNode),
                'Target'             => $xpath->evaluate('string(ec2:HealthCheck/ec2:Target)', $lbDescNode),
                'HealthyThreshold'   => (int) $xpath->evaluate('string(ec2:HealthCheck/ec2:HealthyThreshold)', $lbDescNode),
                'Timeout'            => (int) $xpath->evaluate('string(ec2:HealthCheck/ec2:Timeout)', $lbDescNode),
                'UnhealthyThreshold' => (int) $xpath->evaluate('string(ec2:HealthCheck/ec2:UnhealthyThreshold)', $lbDescNode),
            );
            
            // Extract listener desciprion
            $data = array();
            foreach($xpath->evaluate('ec2:ListenerDescriptions/ec2:member', $lbDescNode) as $dataNode) {
                $data[] = array(
                    'PolicyNames' => array(),
                    'Listener'    => new Zend_Service_Amazon_Ec2_ElasticLB_Listener(
                        (int) $xpath->evaluate('string(ec2:Listener/ec2:LoadBalancerPort)', $dataNode),
                        (int) $xpath->evaluate('string(ec2:Listener/ec2:InstancePort)', $dataNode),
                              $xpath->evaluate('string(ec2:Listener/ec2:Protocol)', $dataNode)
                    )
                );
            }
            $lbDesc['ListenerDescriptions'] = $data;
            
            // FIXME: Implement Policies extraction
            $lbDesc['Policies'] = array();
                
            $return[$lbDesc['LoadBalancerName']] = $lbDesc;
        }
        
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
    
    /**
     * Register one or more EC2 machine instances with a load balancer
     * 
     * This method accepts one Instance ID as a string or multiple instance IDs
     * as an array of strings. 
     * 
     * The list of machines registered with the load balancer will be returned.
     * 
     * @param  string       $name      Load balancer name
     * @param  string|array $instances Instance ID(s) to register
     * @return array
     */
    public function registerInstances($name, $instances)
    {
        return $this->_doRegDeregInstancesCall(
            'RegisterInstancesWithLoadBalancer',
            'RegisterInstancesWithLoadBalancerResponse',
            $name,
            $instances
        );
    }

    /**
     * Remove one or more EC2 machine instances from a load balancer pool
     * 
     * This method accepts one Instance ID as a string or multiple instance IDs
     * as an array of strings. 
     * 
     * The list of machines registered with the load balancer will be returned.
     * 
     * @param  string       $name      Load balancer name
     * @param  string|array $instances Instance ID(s) to register
     * @return array
     */
    public function deregisterInstances($name, $instances)
    {
        return $this->_doRegDeregInstancesCall(
            'DeregisterInstancesFromLoadBalancer',
            'DeregisterInstancesFromLoadBalancerResponse',
            $name,
            $instances
        );
    }
    
    /**
     * Enable one or more availability zones for a load balancer
     * 
     * All zones must be in the same region as the load balancer. 
     * 
     * Will return the new list of zones enabled for the load balancer.
     * 
     * @param  string       $name
     * @param  string|array $zones
     * @return array
     */
    public function enableAvailabilityZones($name, $zones)
    {
        return $this->_doEnableDisableZones(
            'EnableAvailabilityZonesForLoadBalancer',
            'EnableAvailabilityZonesForLoadBalancerResponse',
            $name,
            $zones
        );
    }
    
    /**
     * Disable one or more availability zones for a load balancer
     * 
     * All zones must be in the same region as the load balancer. 
     * 
     * Will return the list of zones still enabled for the load balancer.
     * 
     * @param  string       $name
     * @param  string|array $zones
     * @return array
     */
    public function disableAvailabilityZones($name, $zones)
    {
        return $this->_doEnableDisableZones(
            'DisableAvailabilityZonesForLoadBalancer',
            'DisableAvailabilityZonesForLoadBalancerResponse',
            $name,
            $zones
        );
    }
    
    /**
     * Execute an enableAvailabilityZones or disableAvailabilityZones API call
     * 
     * This method is here to avoid code duplication, since both method calls 
     * take very similar parameters, and return a very similar response
     * 
     * @param  string       $action
     * @param  string       $expResponse
     * @param  string       $name
     * @param  string|array $zones
     * @return array
     */
    private function _doEnableDisableZones($action, $expResponse, $name, $zones)
    {
        // Validate load balancer name
        if (! $this->_validateLbName($name)) {                   
            $message = "Invalid load balancer name: '$name'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $params = array(
            'Action'           => $action,
            'LoadBalancerName' => $name
        );
        
        // Validate and set availability zone(s)
        $invalid = false;
        if (is_array($zones) && ! empty($zones)) {
            $i = 1;
            foreach($zones as $az) {
                if (! $this->_validateZone($az)) {
                    $invalid = true;
                    break;
                }
                $params['AvailabilityZones.member.' . $i++] = $az;
            }
        } elseif (is_string($zones)) {
            if ($this->_validateZone($zones)) {
                $params['AvailabilityZones.member.1'] = $zones;    
            } else {
                $invalid = true;
            }
        } else {
            $invalid = true;
        }
        
        if ($invalid) {
            if (isset($az)) $zones = $az;
            $message = "Invalid availability zone '$zones' for region '$this->_region'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $response = $this->sendRequest($params);
        $this->_checkExpectedResponseType($response, $expResponse);

        $zones = array();
        $xpath = $response->getXpath();
        foreach($xpath->evaluate('//ec2:AvailabilityZones/ec2:member') as $zone) {
            $zones[] = $zone->nodeValue;
        }
        
        return array('AvailabilityZones' => $zones);
    }
    
    /**
     * Execute a registerInstances or deregisterInstances API call
     * 
     * This method is here to avoid code duplication, given that both method 
     * calls take very similar parameters, and return a very similar response
     * 
     * @param  string       $action      Action name
     * @param  string       $expResponse Expeected response type
     * @param  string       $name        Load balancer name
     * @param  string|array $instances   Instance ID(s) to register
     * @throws Zend_Service_Amazon_Ec2_Exception
     * @return array
     */
    private function _doRegDeregInstancesCall($action, $expResponse, $name, $instances)
    {
        // Validate load balancer name
        if (! $this->_validateLbName($name)) {                   
            $message = "Invalid load balancer name: '$name'";
            throw new Zend_Service_Amazon_Ec2_Exception($message);
        }
        
        $params = array(
            'Action'           => $action,
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
        $this->_checkExpectedResponseType($response, $expResponse);
        
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