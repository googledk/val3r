<?php
class BaleBot
{
    private string $token;
    private string $chatId;
    private string $lastError = '';
    private $lastResponse = null;

    public function __construct(string $token, string $chatId = '')
    {
        $this->token = trim($token);
        $this->chatId = trim($chatId);
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    public function sendMessage(string $text, ?string $chatId = null): bool
    {
        $chatId = trim((string)($chatId ?: $this->chatId));

        if ($this->token === '' || $chatId === '' || $text === '') {
            $this->lastError = 'توکن، chat_id یا متن پیام خالی است.';
            return false;
        }

        $result = $this->request('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        if (!$result['ok']) {
            $this->lastError = $result['error'];
            return false;
        }

        return true;
    }

    public function getUpdates(): array
    {
        $result = $this->request('getUpdates', []);
        if (!$result['ok']) {
            $this->lastError = $result['error'];
            return [];
        }

        return is_array($result['json']['result'] ?? null) ? $result['json']['result'] : [];
    }

    private function request(string $method, array $payload): array
    {
        $this->lastError = '';
        $this->lastResponse = null;

        if ($this->token === '') {
            return ['ok' => false, 'error' => 'توکن خالی است.', 'json' => null];
        }

        $url = 'https://tapi.bale.ai/bot' . $this->token . '/' . $method;

        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ];

        if ($payload) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $curlError = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$body, true);
        $this->lastResponse = $json ?: $body;

        if ($errno) {
            return ['ok' => false, 'error' => 'خطای cURL: ' . $curlError, 'json' => $json];
        }

        if ($status < 200 || $status >= 300) {
            return ['ok' => false, 'error' => 'HTTP ' . $status . ' - ' . mb_substr((string)$body, 0, 300), 'json' => $json];
        }

        if (is_array($json) && array_key_exists('ok', $json) && !$json['ok']) {
            $desc = $json['description'] ?? $json['error_code'] ?? 'خطای نامشخص از بله';
            return ['ok' => false, 'error' => (string)$desc, 'json' => $json];
        }

        return ['ok' => true, 'error' => '', 'json' => $json];
    }
}
