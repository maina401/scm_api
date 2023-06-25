<?php

namespace Leaf\Controllers;

use AllowDynamicProperties;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Leaf\Helpers\ApiException;

use Leaf\Helpers\Validator;
use Leaf\Models\JWT;
use Leaf\Models\User;
use Leaf\Services\AuthService;

/**
 * @package : Api Controller
 * @version : 4.0
 * @developed by : Moffat
 */
#[AllowDynamicProperties]
class APIController
{
    public $uri;
    public $requestMethod;
    public $requestData;
    private $secret = null;
    private $ttl = 1800;//30 minutes
    /**
     * @var AuthService
     */
    private AuthService $authentication_service;
    private $session;

    public function __construct()
    {
        // load repositories
        // Check if caching is enabled (not in dev mode)
        $useCaching = _env('APP_ENV')== 'production';

        // Load repositories from cache or scan the directory
        $repositories =  $this->scanRepositoriesDirectory();//$useCaching ? $this->loadRepositoriesFromCache() :

        // Bind repository methods to this class
        foreach ($repositories as $repository) {
            $repositoryName = "Leaf\\Repositories\\" . str_replace(".php", "", $repository);
            $repo = new $repositoryName();

            foreach (get_class_methods($repo) as $method) {
                // Bind as class method
                $this->{$method} = function () use ($repo, $method) {
                    return call_user_func_array([$repo, $method], func_get_args());
                };
            }
        }
        return $this;
    }

    private function scanRepositoriesDirectory(): false|array
    {
        $repositories = scandir(__DIR__ . "/../Repositories");
        return array_filter($repositories, function ($repository) {
            return $repository != "." && $repository != ".." && str_ends_with($repository, ".php");
        });
    }

    private function loadRepositoriesFromCache()
    {
        $repositories = Cache::get('repositories');

        if (!$repositories) {
            $repositories = $this->scanRepositoriesDirectory();
            Cache::put('repositories', $repositories, now()->addDay());
        }

        return $repositories;
    }

    public function processor(): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->uri = explode('/', $uri);
        $this->requestMethod = $_SERVER["REQUEST_METHOD"];
        $this->requestData = (object)request()->body();
        $this->authentication_service = new AuthService();
        $this->session = session();
        $this->secret = _env("APP_KEY");
        $this->processRequest();
    }


    /**
     * @throws Exception
     */
    public function login(): array
    {

        Validator::make($this->requestData, [
            'client_id' => 'required|exists:users,email#phone#id',
            'client_secret' => 'required',
        ]);
        $merchant_key = $this->requestData->client_id;
        $merchant_secret = substr(base64_decode($this->requestData->client_secret), 6);// remove the first 6 characters
        // username is okey lets check the password now
        $jwt = $this->authentication_service->merchant_auth($merchant_key, $merchant_secret);
        if ($jwt->id) {
            $user = $jwt->user;
            if (isset($user)) {
                $token = $this->getSignedJWTForUser($user, $jwt);
            } else {
                throw new Exception("User Not Found");
            }
        } else {
            throw new Exception("User is inactive. Log In Denied");
        }

        return $this->respondWith(json_encode(['status' => '01', "access_token" => $token, "refresh_token" => $token, "expires" => $this->ttl]), 200);
    }
    //refresh token
    /**
     * @throws Exception
     */
    public function refresh(): array
    {
        Validator::make($this->requestData, [
            'refresh_token' => 'required',
            'client_id' => 'required',
        ]);
        $token = $this->refreshJWT($this->requestData);
        return ['status' => '01', "access_token" => $token, "refresh_token" => $token, "expires" => $this->ttl];
    }
    //register

    /**
     * @throws Exception
     */
    public function register(): array
    {
        return $this->respondWith(json_encode(['status' => '01', "message" => "Registration Successful", "data" => $this->authentication_service->register($this->requestData)->toArray()]), 200);
    }


    private function processRequest(): void
    {
        switch ($this->requestMethod) {
            case 'POST' || 'OPTIONS' || 'GET':
                try {
                    if (!isset($this->requestData->resource)) {
                        throw new Exception("Parameter resource is required");
                    }
                    //check if resource exists, as a method in this class or in the loaded repositories
                    if (!method_exists($this, $this->requestData->resource) && !property_exists($this, $this->requestData->resource)) {
                        throw new Exception("The requested resource (" . $this->requestData->resource . ") is unavailable");
                    }
                    $unguarded = ['login', 'register'];

                    if (in_array($this->requestData->resource,$unguarded)) {//Validate token if request not log in
                        $response = call_user_func(array($this, $this->requestData->resource));
                    } elseif ($this->validate_request()) {//Will Throw Exception
                        if (method_exists($this, $this->requestData->resource)) {
                            $response = call_user_func(array($this, $this->requestData->resource));
                        } elseif (property_exists($this, $this->requestData->resource)) {
                            //$this->{$this->requestData->resource} is closure. call its __invoke method
                            $response = $this->{$this->requestData->resource}->__invoke($this->requestData);
                        } else {
                            throw new Exception("The required resource (" . $this->requestData->resource . ") is unavailable");
                        }

                        if (is_array($response)) {
                            $response = $this->respondWith(json_encode($response), 200);
                        } elseif (is_object($response)) {
                            $response = $this->respondWith(json_encode($response->toArray()), 200);
                        } else {
                            $response = $this->respondWith(json_encode($response), 200);
                        }

                    } else {
                        throw new Exception("Access denied. Invalid or expired token!");
                    }

                } catch (ApiException $exception) {
                    $response = $this->unprocessableEntityResponse($exception->errors());
                }catch (ModelNotFoundException){
                    $response = $this->unprocessableEntityResponse("Resource not found");
                }
                catch (Exception $exception) {
                    $response = $this->unprocessableEntityResponse($exception->getMessage());
                }
                break;
            default:
                $response = $this->notFoundResponse();
                break;
        }
        header($response['status_code_header']);
        $this->session->destroy();
        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function unprocessableEntityResponse($message): array
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode([
            'error' => $message ?? 'Invalid input',
        ]);
        return $response;
    }

    private function notFoundResponse(): array
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }

    private function respondWith($json, int $status): array
    {
        $response['status_code_header'] = match ($status) {
            200 => 'HTTP/1.1 200 OK',
            default => 'HTTP/1.1 404 Not Found',
        };
        $response['body'] = $json;
        return $response;
    }

    public function getSignedJWTForUser($user, JWT $jwt): string
    {
        $issuedAtTime = time();
        $tokenTimeToLive = $this->ttl;
        $tokenExpiration = $issuedAtTime + $tokenTimeToLive;
        $payload = [
            'user' => $user->id,
            'login_credential' => $jwt->id,
            'iat' => $issuedAtTime,
            'exp' => $tokenExpiration,
        ];
        //update jwt
        $token = $this->generate_jwt($issuedAtTime, $payload, $this->secret);
        $jwt->update(['payload' => json_encode($payload), 'token' => $token]);
        return $token;
    }

    /**
     * @throws
     */
    public function validate_request(): bool
    {
        try {
            $bearer = $_SERVER["HTTP_AUTHORIZATION"];
            if (empty($bearer)) {
                $bearer = $_SERVER["HTTP_BEARER"];
            } else {
                $bearer = str_replace("Bearer ", "", $bearer);
            }
            if (empty($bearer)) {
                $bearer = $this->requestData->token;
            }
        } catch (Exception) {
            throw new ApiException("Access denied. Invalid or expired token!");
        }

        return $this->is_jwt_valid($bearer);
    }

    /**
     * @throws Exception
     */
    public function is_jwt_valid($jwt): bool
    {
        if (empty($jwt)) {
            return false;
        }

        // split the jwt
        $tokenParts = explode('.', $jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = json_decode(base64_decode($tokenParts[1]));
        $signature_provided = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = $payload->exp;
        $is_token_expired = ($expiration - time()) < 0;

        // build a signature based on the header and payload using the secret
        $base64_url_header = $this->base64url_encode($header);
        $base64_url_payload = $this->base64url_encode(json_encode($payload));
        $signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, $this->secret, true);
        $base64_url_signature = $this->base64url_encode($signature);

        // verify it matches the signature provided in the jwt
        $is_signature_valid = ($base64_url_signature === $signature_provided);

        if ($is_token_expired || !$is_signature_valid) {
            return false;
        } else {
            // retrieve user
            try{
                $jwt = JWT::findOrFail($payload->login_credential);
                $user = User::findOrFail($payload->user);
            }catch (Exception) {
                throw new ApiException("Access denied. Invalid or expired token!");
            }
            $auth = $this->authentication_service->merchant_validation($user, $jwt);
            if ($auth) {
                $this->authentication_service->setUser($user, $jwt);
                return true;
            } else {
                return false;
            }
        }
    }

    public function base64url_encode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function generate_jwt($headers, $payload, $secret = 'secret'): string
    {
        $headers_encoded = $this->base64url_encode(json_encode($headers));

        $payload_encoded = $this->base64url_encode(json_encode($payload));

        $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", $secret, true);
        $signature_encoded = $this->base64url_encode($signature);

        return "$headers_encoded.$payload_encoded.$signature_encoded";
    }

    /**
     * @throws ApiException
     */
    private function refreshJWT($data): string
    {
        $issuedAtTime = time();
        $tokenTimeToLive = $this->ttl;
        $tokenExpiration = $issuedAtTime + $tokenTimeToLive;
        $jwt_id = session()->get('auth_user');
        $jwt = JWT::find($jwt_id);
        if (!$jwt) {
            throw new ApiException("Access denied. Invalid or expired token!");
        }
        $payload = [
            'user' => $jwt->user,
            'login_credential' => $jwt->id,
            'iat' => $issuedAtTime,
            'exp' => $tokenExpiration,
        ];
        //update jwt
        $token = $this->generate_jwt($issuedAtTime, $payload, $this->secret);
        $jwt->update(['payload' => json_encode($payload), 'token' => $token]);
        return $token;
    }
}
