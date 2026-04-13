<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api_response - Standardized JSON response helper for REST API
 *
 * Provides consistent response format and CORS headers.
 */
class Api_response {

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Set CORS headers for API responses
     *
     * Call this in the controller constructor to enable CORS for all methods.
     */
    public function set_cors_headers()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Send a success JSON response
     *
     * @param mixed  $data    Response data (array or object)
     * @param string $message Success message
     * @param int    $code    HTTP status code (default 200)
     */
    public function success($data = null, $message = 'OK', $code = 200)
    {
        $response = array(
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        );

        $this->_output($response, $code);
    }

    /**
     * Send an error JSON response
     *
     * @param string $message Error message
     * @param int    $code    HTTP status code (default 400)
     * @param mixed  $errors  Optional additional error details
     */
    public function error($message = 'Bad Request', $code = 400, $errors = null)
    {
        $response = array(
            'status'  => 'error',
            'message' => $message
        );

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->_output($response, $code);
    }

    /**
     * Output JSON response using CI output class
     *
     * @param array $data Response data
     * @param int   $code HTTP status code
     */
    private function _output($data, $code)
    {
        $this->CI->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->_display();
        exit;
    }
}
