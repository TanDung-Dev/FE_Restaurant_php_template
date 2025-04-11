<?php
class ApiService {
    private $apiUrl;
    private $token;

    public function __construct() {
        $this->apiUrl = API_BASE_URL; // Defined in config/api.php
        $this->token = isset($_SESSION['auth_token']) ? $_SESSION['auth_token'] : null;
    }

    // GET Request
    public function get($endpoint, $params = []) {
        return $this->request('GET', $endpoint, $params);
    }

    // POST Request
    public function post($endpoint, $data = []) {
        return $this->request('POST', $endpoint, $data);
    }

    // PUT Request
    public function put($endpoint, $data = []) {
        return $this->request('PUT', $endpoint, $data);
    }

    // DELETE Request
    public function delete($endpoint, $params = []) {
        return $this->request('DELETE', $endpoint, $params);
    }

    // Generic Request Method
    private function request($method, $endpoint, $data = []) {
        $url = $this->apiUrl . $endpoint;
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n" .
                            ($this->token ? "Authorization: Bearer {$this->token}\r\n" : ""),
                'method' => $method,
                'ignore_errors' => true
            ]
        ];
        
        if ($method == 'POST' || $method == 'PUT') {
            $options['http']['content'] = json_encode($data);
        } elseif (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        // Parse headers
        $responseCode = $this->parseHeaders($http_response_header);
        
        return [
            'status' => $responseCode,
            'data' => json_decode($response, true)
        ];
    }
    
    // Parse response headers to get status code
    private function parseHeaders($headers) {
        $status = 0;
        
        if (is_array($headers)) {
            $parts = explode(' ', $headers[0]);
            if (count($parts) > 1) {
                $status = intval($parts[1]);
            }
        }
        
        return $status;
    }
}