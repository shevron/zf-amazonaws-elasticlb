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
     * The HTTP query server
     */
    protected $_ec2Endpoint = 'amazonaws.com';

    /**
     * The API version to use
     */
    protected $_ec2ApiVersion = '2009-11-25';
    
    public function describe($lbnames = null)
    {
        $params = array();
        $params['Action']       = 'DescribeLoadBalancers';
        
        if (is_array($lbnames)) {
            $i = 1;
            foreach($lbnames as $name) {
                $params['LoadBalancerNames.member.' . $i++] = $name;
            }
            
        } elseif ($lbnames) {
            $params['LoadBalancerNames.member.1'] = (string) $lbnames;
        }

        $response = $this->sendRequest($params);

        $xpath = $response->getXPath();

        var_dump($response);
        
        $return = array(
        );/*
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
     * @param $name
     * @param $zone
     * @param $listeners
     */
    public function create($name, $zone, $listeners)
    {
        
    }
    
    public function delete()
    {
        
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