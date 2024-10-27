<?php
require_once "vendor/autoload.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

// Globally accessible session data (initialized as empty)
$JWT_SESSION_DATA = [];
$key = $_SERVER["JWT_SECRET"];

function handleJWT() {
    global $JWT_SESSION_DATA, $key;
    // Get the Authorization header
    $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;

    // Check if the Authorization header is present and starts with "Bearer "
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];

        try {
            // Verify the JWT and decode it
            JWT::$leeway = 60; // Allow for 60 seconds of clock skew
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            $decoded_array = (array)$decoded;

            // Verify origin (ensure the 'aud' claim matches the current origin)
            $origin = parse_url($_SERVER["HTTP_REFERER"] ?? ""); // Use HTTP_REFERER or other method to get origin
            if (!isset($origin["host"]) || $decoded_array["aud"] !== $origin["host"]) {
                throw new Exception("Invalid token origin."); // Or handle differently (e.g., log, return specific error)
            }

            // Verify 'nbf' claim if present
            if (isset($decoded_array['nbf']) && $decoded_array['nbf'] > time()) {
                 throw new BeforeValidException("Token is not yet valid.");
            }

            $JWT_SESSION_DATA = $decoded_array;

            return $decoded_array;  // Return decoded data if successful

        } catch (ExpiredException $e) {
             // Token has expired - handle refresh token or redirect to login
              echo jsonResponse(401, "Token expired. Please refresh.");
              exit;
        } catch (SignatureInvalidException $e) {
            echo jsonResponse(401, "Invalid token signature.");
            exit;
        } catch (BeforeValidException $e){
            echo jsonResponse(401, "Token is not yet valid.");
            exit;
        } catch (Exception $e) {
             // Other JWT-related errors
            echo jsonResponse(401, "Invalid token. Reason: " . $e->getMessage());
            exit;
        }
    } else {
        // No token provided. Handle guest users or redirect to login as needed.
        // ... your logic for guest users ...
        return null;  // or an empty array or other appropriate value for guest users
    }
}


function createJWT($userData, $refreshToken = false) {
    global $key;
    $issuedAt = time();
    $expire = $issuedAt + ( $refreshToken ?  7 * 24 * 60 * 60 : 600); // Refresh token lasts for 7 days
    $origin = parse_url($_SERVER["HTTP_REFERER"] ?? "");

    $payload = [
        'iss' => $_SERVER["HTTP_HOST"],
        'aud' => $origin["host"],
        'iat' => $issuedAt,
        'nbf' => $issuedAt,
        'exp' => $expire,
    ];


    // Add user data to the payload
    $payload = array_merge($payload, $userData);

    $jwt = JWT::encode($payload, $key, 'HS256');
    return $jwt;
}

handleJWT();

// Print the session data for debugging
// error_log("JWT_SESSION_DATA: " . print_r($JWT_SESSION_DATA, true));

?>