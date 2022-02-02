<?php

namespace MimecastMail;

class MimeMail
{

  protected string $accessKey;
  protected string $secretKey;
  protected string $appId;
  protected string $appKey;
  private string $baseUrl = 'https://eu-api.mimecast.com';
  private string $uri = '/api/email/send-email';

    /**
     * Instantiate a new instances
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $appId
     * @param string $appKey
     */
    public function __construct(string $accessKey, string $secretKey, string $appId, string $appKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->appId = $appId;
        $this->appKey = $appKey;
    }

    /**
     * Generate GUID request ID
     * @param bool $trim
     * @return string
     */
    public function generateRequestId(bool $trim = true): string
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
    }

    /**
     * Generate current date
     *
     * @return false|string
     */
    public function generateTimeStamp(): string
    {
        return date(DATE_RFC2822);
    }

    /**
     * Generate DataToSign
     *
     * @return string
     */
    public function generateDataToSign(): string
    {
        $date = $this->generateTimeStamp();
        $requestId = $this->generateRequestId();
        $uri = $this->uri;
        $appKey = $this->appKey;

        return $date.':'.$requestId.':'.$uri.':'.$appKey;
    }

    /**
     * Generate the HMAC SHA1 of base64 decoded secret key
     */
    public function generateHash(): string
    {
        $secret = base64_decode($this->secretKey);
        $hash = hash_hmac('sha1', $this->generateDataToSign(), $secret);
        return base64_encode($hash);

    }

    /**
     * Generate Headers to be used for cUrl request
     *
     * @return string[]
     */
    public function generateHeaders(): array
    {
        $auth = 'EU' . $this->accessKey . ':' . $this->generateHash();

        return [
            "Authorization:" . $auth,
            "x-mc-app-id:" . $this->appId,
            "x-mc-date:" . $this->generateTimeStamp(),
            "x-mc-req-id:" . $this->generateRequestId(),
            "Content-Type: 'application/json'",
        ];
    }
}
