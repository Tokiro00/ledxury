<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JWT_lib - JSON Web Token library for CodeIgniter 3
 *
 * Pure PHP HS256 JWT implementation.
 * Token expiration: 7 days by default.
 *
 * IMPORTANT: Change the secret key in production!
 */
class JWT_lib {

    /**
     * Secret key for HS256 signing.
     * Override via $config['jwt_secret'] in application/config/secrets.php or config.php
     */
    private $secret_key;

    /**
     * Token expiration in seconds (7 days)
     */
    private $expiration = 604800;

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();

        // Try to load secret from config, fall back to default
        // IMPORTANT: Change this default key in production!
        $this->secret_key = $this->CI->config->item('jwt_secret')
            ? $this->CI->config->item('jwt_secret')
            : 'MAM_ERP_JWT_S3cr3t_K3y_Ch4ng3_Th1s_1n_Pr0duct10n!';
    }

    /**
     * Generate a JWT token from user data
     *
     * @param array $userData Associative array with user info (idUser, name, role, store)
     * @return string JWT token
     */
    public function generateToken($userData)
    {
        $payload = array(
            'sub'   => $userData['idUser'],
            'name'  => $userData['name'],
            'role'  => $userData['role'],
            'store' => isset($userData['store']) ? $userData['store'] : null,
            'iat'   => time(),
            'exp'   => time() + $this->expiration
        );

        return $this->encode($payload);
    }

    /**
     * Validate a JWT token and return the decoded payload
     *
     * @param string $token JWT token string
     * @return object|false Decoded payload object or false on failure
     */
    public function validateToken($token)
    {
        $payload = $this->decode($token);

        if ($payload === false) {
            return false;
        }

        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Encode a payload into a JWT token
     *
     * @param array $payload
     * @return string
     */
    public function encode($payload)
    {
        $header = array(
            'typ' => 'JWT',
            'alg' => 'HS256'
        );

        $segments = array();
        $segments[] = $this->base64url_encode(json_encode($header));
        $segments[] = $this->base64url_encode(json_encode($payload));

        $signing_input = implode('.', $segments);
        $signature = hash_hmac('sha256', $signing_input, $this->secret_key, true);
        $segments[] = $this->base64url_encode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode a JWT token
     *
     * @param string $token
     * @return object|false Decoded payload or false on invalid token
     */
    public function decode($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($header_b64, $payload_b64, $signature_b64) = $parts;

        // Verify signature
        $signing_input = $header_b64 . '.' . $payload_b64;
        $signature = $this->base64url_decode($signature_b64);
        $expected_signature = hash_hmac('sha256', $signing_input, $this->secret_key, true);

        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        // Decode payload
        $payload = json_decode($this->base64url_decode($payload_b64));

        if ($payload === null) {
            return false;
        }

        return $payload;
    }

    /**
     * Base64 URL-safe encode
     *
     * @param string $data
     * @return string
     */
    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe decode
     *
     * @param string $data
     * @return string
     */
    private function base64url_decode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
