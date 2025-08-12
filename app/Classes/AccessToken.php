<?php
namespace App\Classes;

use GuzzleHttp\Client;

Class AccessToken{

    public static function getTokenFromSFA()
   {

    $http = new Client(['verify'=>false]);
    $response = $http->post(env('SFA_BASE_URL').'/oauth/token', [
        'form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => env('SFA_CLIENT_ID'),
            'client_secret' => env('SFA_CLIENT_SECRET'),
            'client_name' => env('SFA_CLIENT_NAME'),
        ],
    ]);

    $tokenDetails=json_decode((string) $response->getBody(), true);
    return $tokenDetails['access_token'];
   }
   public function saveToken()
   {
    dd(self::getTokenFromSFA()['expires_in']/3600/24);
   }
}
