<?php
class SmsIr
{
    private string $apiKey;
    private int $templateId;
    private string $parameterName;

    public function __construct(string $apiKey, int $templateId, string $parameterName = 'CODE')
    {
        $this->apiKey = $apiKey;
        $this->templateId = $templateId;
        $this->parameterName = $parameterName;
    }

    public function sendVerifyCode(string $mobile, string $code): array
    {
        $payload = [
            'mobile' => $mobile,
            'templateId' => $this->templateId,
            'parameters' => [
                [
                    'name' => $this->parameterName,
                    'value' => $code
                ]
            ]
        ];

        $ch = curl_init('https://api.sms.ir/v1/send/verify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return [
                'ok' => false,
                'status' => $status,
                'message' => 'خطا در اتصال به سرویس پیامک: ' . $error,
                'raw' => null,
            ];
        }

        $json = json_decode((string)$body, true);

        $success = $status >= 200 && $status < 300;
        if (is_array($json)) {
            if (isset($json['status'])) {
                $success = $success && ((int)$json['status'] === 1 || (string)$json['status'] === '1');
            } elseif (isset($json['data'])) {
                $success = $success && true;
            }
        }

        return [
            'ok' => $success,
            'status' => $status,
            'message' => $success ? 'کد تایید ارسال شد.' : 'ارسال پیامک انجام نشد.',
            'raw' => $json ?: $body,
        ];
    }
}
