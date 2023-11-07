<?php

namespace App\Http\Controllers;

use App\Exceptions\Custom\RuntimeException;
use App\Helpers\RequestConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MainController extends Controller
{

    public function proxy(
        Request $request
    ) {
        try {
//            $config = new RequestConfig($request);

            $accessKey = null;
            $secretKey = null;
            $region = 'ru-msk';
            $isAuthVersion4 = true;
            $isDebug = false;

            $excludeHeaders = [];
            $headers = [];

            foreach ($request->headers as $key => $value) {
                $key = strtolower($key);

                if (preg_match("|^x-config|i", $key)) {
                    if ($key === 'x-config-authversion') {
                        $authVersion = current($value);
                        $isAuthVersion4 = $authVersion !== 'v2';
                        continue;
                    }

                    if ($key === 'x-config-accesskey') {
                        $accessKey = current($value);
                        continue;
                    }

                    if ($key === 'x-config-secretkey') {
                        $secretKey = current($value);
                        continue;
                    }

                    if ($key === 'x-config-region') {
                        $region = current($value);
                        continue;
                    }

                    continue;
                }

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

            $host = $request->header('host');
            ksort($headers);
            $request = new \GuzzleHttp\Psr7\Request($request->getMethod(), $request->getUri(), headers: $headers, body: $request->getContent());

            $signer = $isAuthVersion4 ?
                new \App\Helpers\RequestSigner($request, $region, $accessKey, $secretKey) :
                new \App\Helpers\RequestSignerVersionTwo($request, $region, $accessKey, $secretKey);

            $request = $signer->signRequest();

            $client = new \GuzzleHttp\Client(['base_uri' => sprintf('http://%s', $host)]);
            try {
                //$response = $client->send($request, ['debug' => $isDebug, 'proxy' => '100.96.162.109:9090']);
                $response = $client->send($request, ['debug' => $isDebug]);
                return response($response->getBody()->getContents(), $response->getStatusCode(), $response->getHeaders());
            } catch (\GuzzleHttp\Exception\RequestException $exception) {
                $response = $exception->getResponse();
                echo $response->getBody()->getContents();
            }
        } catch (Throwable $exception) {
            return response(sprintf(
                "Exception [%s] in line: %s \n\nmessage: %s",
                $exception::class,
                $exception->getLine(),
                $exception->getMessage()
            ));
        }
    }
}
