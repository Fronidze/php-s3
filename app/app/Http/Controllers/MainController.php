<?php

namespace App\Http\Controllers;

use App\Helpers\RequestConfig;
use App\Helpers\RequestSigner;
use App\Helpers\RequestSignerVersionTwo;
use Illuminate\Http\Request;

class MainController extends Controller
{

    public function proxy(
        Request $request
    ) {
        try {

            $isDebug = false;
            $config = new RequestConfig($request);

            $excludeHeaders = [
                'x-profile',
                'host',
                'x-real-ip',
                'user-agent',
            ];
            $headers = [];

            foreach ($request->headers as $key => $value) {
                $key = strtolower($key);

                if ($key === 'x-debug') {
                    $isDebug = true;
                    continue;
                }

                if (str_starts_with($key, 'x-forwarded') === true) {
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
                $request->getRequestUri(),
                headers: $headers,
                body: $request->getContent()
            );

            if ($config->getSignVersion() === 'v2') {
                $signer = new RequestSignerVersionTwo(
                    request: $request,
                    region: $config->getRegion(),
                    accessKey: $config->getAccessKey(),
                    secretKey: $config->getSecretKey(),
                    isDebug: $isDebug
                );
            } else {
                $signer = new RequestSigner(
                    request: $request,
                    region: $config->getRegion(),
                    accessKey: $config->getAccessKey(),
                    secretKey: $config->getSecretKey(),
                    isDebug: $isDebug
                );
            }

            $request = $signer->signRequest();
            $client = new \GuzzleHttp\Client(['base_uri' => sprintf('http://%s', $config->getEndpointUrl())]);
            try {
                $response = $client->send($request, ['debug' => false]);
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
        return response("Sometimes i think here will be response");
    }
}
