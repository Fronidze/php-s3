<?php

namespace App\Helpers;

use App\Entities\CredentialsEntity;
use App\Entities\ProfileEntity;
use App\Exceptions\Custom\RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RequestConfig
{
    const DEFAULT_CONFIG_PATH = '/etc/signer/config.yml';
    private CredentialsEntity $credentials;
    private string $signVersion;

    public function __construct(
        private readonly Request $request
    ) {
        $config = $this->readConfigFile();
        $this->credentials = $this->parseConfig($config);

        $signVersion = Arr::get($config,'version', 'v4');
        if (in_array($signVersion, ['v2', 'v4']) === false) {
            $signVersion = 'v4';
        }
        $this->signVersion = $signVersion;
    }

    public function getSignVersion(): string {
        return $this->signVersion;
    }

    private function parseConfig(
        array $content
    ): CredentialsEntity {

        $profileEntity = null;
        $headerProfile = $this->getProfile();
        if ($headerProfile !== null) {
            $profiles = Arr::get($content, 'profiles', []);
            foreach ($profiles as $profile) {
                if (Arr::get($profile, 'name') === $headerProfile) {
                    $profileEntity = new ProfileEntity($profile);
                }
            }
        } else {
            throw new RuntimeException('Header X-Profile is required');
        }

        if ($profileEntity === null) {
            throw new RuntimeException("Config file does not find profile: ${$headerProfile}");
        }

        return (new CredentialsEntity())
            ->setEndpointUrl($profileEntity?->endpointUrl ?? null)
            ->setVersionAuth(Arr::get($content, 'version', $this->getSignVersion()))
            ->setAccessKey($profileEntity?->accessKey ?? null)
            ->setSecretKey($profileEntity?->secretKey ?? null)
            ->setRegion($profileEntity?->region)
        ;
    }

    private function readConfigFile(
        ?string $path = null
    ): array {
        $path = $path ?? self::DEFAULT_CONFIG_PATH;
        if (file_exists($path) === false) {
            throw new RuntimeException("File {$path} does not exists");
        }

        $config = yaml_parse_file($path);
        if ($config === false) {
            throw new RuntimeException("Parse config failed");
        }

        if (is_array($config) === false) {
            throw new RuntimeException('Config content is not array');
        }

        return $config;
    }

    public function getProfile(): ?string
    {
        return $this->request->header('x-profile');
    }

    public function getAccessKey(): ?string
    {
        return $this->credentials->getAccessKey();
    }

    public function getSecretKey(): ?string
    {
        return $this->credentials->getSecretKey();
    }

    public function getEndpointUrl(): ?string
    {
        return $this->credentials->getEndpointUrl();
    }

    public function getRegion(): ?string {
        return $this->credentials->getRegion();
    }

}
