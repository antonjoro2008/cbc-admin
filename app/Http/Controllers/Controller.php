<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

abstract class Controller
{
    const API_BASE_URL = "https://mdarasa.com:8443/api/v1";

    protected function callMDarasaAPIPostWithToken($data, $url)
    {
        $encodedData = json_encode($data);

        $httpRequest = curl_init(self::API_BASE_URL . $url);

        curl_setopt($httpRequest, CURLOPT_POST, true);
        curl_setopt($httpRequest, CURLOPT_POSTFIELDS, $encodedData);
        curl_setopt($httpRequest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($httpRequest, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt(
            $httpRequest,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Authorization: Bearer' . Session::get('token')
            )
        );
        curl_setopt($httpRequest, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($httpRequest, CURLOPT_SSL_VERIFYPEER, false);
        $results = curl_exec($httpRequest);
        $decodedResults = json_decode($results);
        curl_close($httpRequest);

        return $decodedResults;

    }

    protected function callMDarasaAPIPostWithoutToken($data, $url)
    {
        $encodedData = json_encode($data);

        $httpRequest = curl_init(self::API_BASE_URL . $url);
        Log::info("Calling " . self::API_BASE_URL . $url . " with payload  $encodedData");

        curl_setopt($httpRequest, CURLOPT_POST, true);
        curl_setopt($httpRequest, CURLOPT_POSTFIELDS, $encodedData);
        curl_setopt($httpRequest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($httpRequest, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($httpRequest, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($httpRequest, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($httpRequest, CURLOPT_SSL_VERIFYPEER, false);
        $results = curl_exec($httpRequest);
        $decodedResults = json_decode($results);
        curl_close($httpRequest);

        return $decodedResults;
    }
}