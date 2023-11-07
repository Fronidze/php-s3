<?php

namespace App\Http\Controllers;

use App\Exceptions\Custom\RuntimeException;
use App\Helpers\RequestConfig;
use App\Helpers\RequestSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MainController extends Controller
{

    public function proxy(
        Request $request
    ) {
        try {

            $config = new RequestConfig($request);
            $isDebug = false;

            $excludeHeaders = ['x-profile', 'host'];
            $headers = [];

            foreach ($request->headers as $key => $value) {
                $key = strtolower($key);

                if ($key === 'x-debug') {
                    $isDebug = true;
                    continue;
                }

                if (empty(current($value)) === true) {
                    continue;
                }

                if (in_array($key, $excludeHeaders) === false) {
                    $headers[$key] = preg_replace('/\s+/', ' ', implode(',', $value));
                }
            }

            $headers['host'] = $config->getEndpointUrl();
            ksort($headers);
            $request = new \GuzzleHttp\Psr7\Request(
                $request->getMethod(), 
                $request->getUri(), 
                headers: $headers, 
                body: $request->getContent()
            );
            echo "<pre>"; print_r($request->getHeaders()); echo "</pre>"; die("Debug Fronidze");
//            foreach ($request->getHeaders() as $key => $value) {
//                if ($key === 'host') {
//                    echo "<pre>"; print_r($value); echo "</pre>"; die("Debug Fronidze");
//                }
//            }
//            $signer = $isAuthVersion4 ?
//                new \App\Helpers\RequestSigner($request, $region, $accessKey, $secretKey) :
//                new \App\Helpers\RequestSignerVersionTwo($request, $region, $accessKey, $secretKey);

            $signer = new RequestSigner(
                request: $request,
                region: $config->getRegion(),
                accessKey: $config->getAccessKey(),
                secretKey: $config->getSecretKey()
            );

            $request = $signer->signRequest();
            $client = new \GuzzleHttp\Client(['base_uri' => sprintf('http://%s', $config->getEndpointUrl())]);
            try {
                $response = $client->send($request, ['debug' => true]);
                return response($response->getBody()->getContents(), $response->getStatusCode(), $response->getHeaders());
            } catch (\GuzzleHttp\Exception\RequestException $exception) {
                $response = $exception->getResponse();
                echo $response->getBody()->getContents();
            } catch (\Throwable $exception) {
                throw new \RuntimeException("Error with send response: " . $exception->getMessage(),0, $exception);
            }
        } catch (\Throwable $exception) {
            return response(sprintf(
                "Exception [%s] in line: %s \n\nmessage: %s",
                $exception::class,
                $exception->getLine(),
                $exception->getMessage()
            ));
        }
    }
}
