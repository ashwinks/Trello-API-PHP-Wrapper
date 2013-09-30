<?php

	namespace Trello;
	
	class Client {
	    
	    private $_api_key;
	    private $_api_secret;
	    private $_access_token;
	    private $_api_url = 'https://trello.com/1';
	    private $_debug_info; 
	    private $_raw_response;
	    private $_curl_handle;
	    
	    public function __construct($api_key, $secret = null, $access_token = null){
	        
	        if (empty($api_key)){
	            throw new \InvalidArgumentException('Invalid API key');
	        }
	        
	        $this->_api_key = trim($api_key);
	        
	        if (!empty($secret)){
	            $this->_api_secret = trim($secret);
	        }
	        
	        if (!empty($access_token)){
	            $this->_access_token = trim($access_token);
	        }
	        
	    }
	    
	    public function getAuthorizationUrl($application_name, $return_url, array $scope = array('read'), $expiration = "30days", $callback_method = 'fragment'){
	        
	        $valid_expirations = array('1hour', '1day', '30days', 'never');
	        $valid_scopes = array('read', 'write', 'account');
	        $valid_callback_methods = array('postMessage', 'fragment');
	        
	        if (!in_array($expiration, $valid_expirations)){
	            throw new \InvalidArgumentException("Invalid expiration {$expiration}. Valid expiration parameters are " . print_r($valid_expirations, true));
	        }
	        
	        foreach ($scope as $v){
	            if (!in_array($v, $valid_scopes)){
	                throw new \InvalidArgumentException("Invalid scope {$v}. Valid data scopes are " . print_r($valid_scopes, true));
	            }
	        }
	        
	        if (!in_array($callback_method, $valid_callback_methods)){
	            throw new \InvalidArgumentException("Invalid callback method {$callback_method}. Valid callback methods are " . print_r($valid_callback_methods, true));
	        }
	        
	        $scope = implode(',', $scope);
	        
	        return $this->getApiBaseUrl() . "/authorize?callback_method={$callback_method}&return_url={$return_url}&scope={$scope}&expiration={$expiration}&name={$application_name}&key=" . $this->getApiKey();
	        
	    }
	    
	    public function setAccessToken($token){
	        
	        $this->_access_token = trim($token);
	        
	        return $this;
	        
	    }
	    
	    public function getApiBaseUrl(){
	        
	        return $this->_api_url;
	        
	    }
	    
	    public function setApiBaseUrl($url){
	        
	        $this->_api_url = rtrim($url, ' /');
	        
	        return $this;
	        
	    }
	    
	    public function getBoard($id){
	        
	        $obj = new \Trello\Model\Board($this);
	        $obj->setId($id);
	        
	        return $obj->get();
	        
	    }
	    
	    public function getCard($id){
	        
	        $obj = new \Trello\Model\Card($this);
	        $obj->setId($id);
	        
	        return $obj->get();
	        
	    }
	    
	    public function getAction($id){
	        
	        $obj = new \Trello\Model\Action($this);
	        $obj->setId($id);
	        
	        return $obj->get();
	        
	    }

	    public function getAccessToken(){
	        
	        return $this->_access_token;
	        
	    }
	    
	    public function getApiSecret(){
	        
	        return $this->_api_secret;
	        
	    }

	    public function getApiKey(){
	        
	        return $this->_api_key;
	        
	    }
	    
	    /**
	     * 
	     * @param unknown_type $path
	     * @return Ambigous <multitype:, mixed>
	     */
	    public function get($path, array $payload = array()){
	        
	        return $this->_makeRequest($path, $payload);
	        
	    }
	    
	    public function post($path, array $payload = array(), array $headers = array()){
	        
	        return $this->_makeRequest($path, $payload, 'POST', $headers);
	        
	    }
	    
	    public function put($path, array $payload = array(), array $headers = array()){
	        
	        return $this->_makeRequest($path, $payload, 'PUT', $headers);
	        
	    }
	    
	    public function delete($path){
	        
	        return $this->_makeRequest($path, array(), 'DELETE');
	        
	    }

	    /**
	     * Make a CURL request
	     * 
	     * @param string $url
	     * @param array $payload
	     * @param string $method
	     * @param array $headers
	     * @param array $curl_options
	     * @throws \RuntimeException
	     * @return array
	     */
	    protected function _makeRequest($url, array $payload = array(), $method = 'GET', array $headers = array(), array $curl_options = array()){

	        $url = $this->getApiBaseUrl() . '/' . $url . '?key=' . $this->getApiKey();
	        if ($this->getAccessToken()){
	            $url .= '&token=' . $this->getAccessToken();
	        }
	        
	        $ch = $this->_getCurlHandle();
	        $method = strtoupper($method);

	        $options = array(
	        	CURLOPT_CUSTOMREQUEST => $method,
	        	CURLOPT_RETURNTRANSFER => true,
	        	CURLOPT_URL => $url,
	        	CURLOPT_HTTPHEADER => $headers,
	        	CURLOPT_SSL_VERIFYPEER => false,
	        	CURLOPT_FOLLOWLOCATION => true
	        );
	        
	        if ($method === 'GET'){
	            
	            if (!empty($payload)){
	                $options[CURLOPT_URL] = $options[CURLOPT_URL] . http_build_query($payload);
	            }
	            
	        }else if (!empty($payload)){
	            
	            $options[CURLOPT_POST] = true;
	            $options[CURLOPT_POSTFIELDS] = http_build_query($payload);
	            $headers[] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);
	            $options[CURLOPT_HTTPHEADER] = $headers;
	            
	        }
	        
	        if (!empty($curl_options)){
	            $options = array_merge($options, $curl_options);
	        }

	        curl_setopt_array($ch, $options);
	        $this->_raw_response = curl_exec($ch);
	        $this->_debug_info = curl_getinfo($ch);

	        if ($this->_raw_response === false){
	            throw new \RuntimeException('Request Error: ' . curl_error($ch));
	        }
	        
	        if ($this->_debug_info['http_code'] < 200 || $this->_debug_info['http_code'] >= 400){
	            throw new \RuntimeException('API Request failed - Response: ' . $this->_raw_response, $this->_debug_info['http_code']);
	        }
	        
	        $response = json_decode($this->_raw_response, true);
	        if (!$response || !is_array($response)){
	            throw new \RuntimeException('Could not decode response JSON - Response: ' . $this->_raw_response, $this->_debug_info['http_code']);
	        }
	        
	        return $response;
	        
	    }
	    
	    public function getRawResponse(){
	        
	        return $this->_raw_response;
	        
	    }

	    protected function _getCurlHandle(){
	    
	    	if (!$this->_curl_handle){
	    		$this->_curl_handle = curl_init();
	    	}
	    		
	    	return $this->_curl_handle;
	    
	    }
	    
	    public function __destruct(){
	    
	    	if ($this->_curl_handle){
	    		curl_close($this->_curl_handle);
	    	}
	    
	    }
	}