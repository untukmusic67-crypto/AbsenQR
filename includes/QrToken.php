<?php
// includes/QrToken.php

class QrToken {
    
    /**
     * Generate Token String
     */
    public static function generate($adminId, $expirySeconds = 120) {
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16)); // Random string 32 chars
        $expiry = $expirySeconds;

        // Payload: timestamp|expiry|nonce|admin_id
        $payload = implode('|', [$timestamp, $expiry, $nonce, $adminId]);

        // HMAC Signature
        $signature = hash_hmac('sha256', $payload, SECRET_KEY);

        // Encode URL Safe
        $token = self::base64UrlEncode($payload) . '.' . self::base64UrlEncode($signature);
        
        return $token;
    }

    /**
     * Verify Token
     * Return Array data jika valid, FALSE jika gagal
     */
    public static function verify($tokenString) {
        $parts = explode('.', $tokenString);
        if (count($parts) !== 2) return false;

        list($encPayload, $encSignature) = $parts;

        $payload = self::base64UrlDecode($encPayload);
        $providedSignature = self::base64UrlDecode($encSignature);

        if (!$payload || !$providedSignature) return false;

        // Cek Signature
        $expectedSignature = hash_hmac('sha256', $payload, SECRET_KEY);
        if (!hash_equals($expectedSignature, $providedSignature)) {
            return false;
        }

        // Parse Payload
        $data = explode('|', $payload);
        if (count($data) !== 4) return false;

        return [
            'timestamp' => (int)$data[0],
            'expiry'    => (int)$data[1],
            'nonce'     => $data[2],
            'admin_id'  => $data[3]
        ];
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?>