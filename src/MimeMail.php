<?php

namespace MimecastMail;

class MimeMail
{
    private $baseUrl = 'https://eu-api.mimecast.com';
    private $uri = '/api/email/send-email';
    private $requestId;
    private $accessKey;
    private $secretKey;
    private $appId;
    private $appKey;

    /**
     * Instantiate a new instances
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $appId
     * @param string $appKey
     */
    public function __construct(
        string $accessKey,
        string $secretKey,
        string $appId,
        string $appKey
    ) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->requestId = $this->generateRequestId();
    }

    /**
     * Generate GUID request ID
     *
     * @param bool $trim
     * @return string
     */
    public function generateRequestId(bool $trim = true): string
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true) {
                return trim(com_create_guid(), '{}');
            } else {
                return com_create_guid();
            }
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10

            return vsprintf(
                '%s%s-%s-%s-%s-%s%s%s',
                str_split(bin2hex($data), 4)
            );
        }
    }

    /**
     * Generate DataToSign
     *
     * @param string $timestamp
     * @return string
     */
    public function generateDataToSign(string $timestamp): string
    {
        return $timestamp .
            ':' .
            $this->requestId .
            ':' .
            $this->uri .
            ':' .
            $this->appKey;
    }

    /**
     * Generate the HMAC SHA1 of base64 decoded secret key
     */
    public function generateHash(string $dataToSign): string
    {
        $secret = base64_decode($this->secretKey);
        $hash = hash_hmac('sha1', $dataToSign, $secret, $raw_output = true);

        return base64_encode($hash);
    }

    /**
     * Generate Headers to be used for cUrl request
     *
     * @param string $timestamp
     * @param string $dataToSign
     * @return string[]
     */
    public function generateHeaders(
        string $timestamp,
        string $dataToSign
    ): array {
        $auth =
            'MC ' . $this->accessKey . ':' . $this->generateHash($dataToSign);

        return [
            'Authorization: ' . $auth,
            'x-mc-app-id: ' . $this->appId,
            'x-mc-date: ' . $timestamp,
            'x-mc-req-id: ' . $this->requestId,
            'Content-Type: ' . 'application/json',
        ];
    }

    /**
     * Make the curl request to the sendMail endpoint
     * Take post as array of body fields according to mimecast docs
     *
     * @param array $post
     * @return bool|string
     */
    public function makeRequest(array $post): string
    {
        //set the timestamp for the request
        $timestamp = gmdate(DATE_RFC2822);
        //generate dataToSign using timestamp
        $dataToSign = $this->generateDataToSign($timestamp);
        //generate headers using timestamp and dataToSign
        $headers = $this->generateHeaders($timestamp, $dataToSign);

        //make curl request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $this->uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response = curl_exec($ch);

        return $response;
    }
}
