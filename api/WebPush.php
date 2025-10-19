<?php
/**
 * Web Push 送信用のシンプルなライブラリ
 * ペイロードなし（空）で通知を送信する実装
 */

class WebPush {
    private $vapidPublicKey;
    private $vapidPrivateKey;
    private $vapidSubject;

    public function __construct($publicKey, $privateKey, $subject) {
        $this->vapidPublicKey = $publicKey;
        $this->vapidPrivateKey = $privateKey;
        $this->vapidSubject = $subject;
    }

    /**
     * プッシュ通知を送信（ペイロードなし）
     */
    public function sendNotification($subscription, $payload = null) {
        $subscriptionData = json_decode($subscription, true);

        if (!isset($subscriptionData['endpoint'])) {
            throw new Exception('Invalid subscription: missing endpoint');
        }

        $endpoint = $subscriptionData['endpoint'];

        // VAPID認証ヘッダーを生成
        $vapidHeaders = $this->getVapidHeaders($endpoint);

        // HTTPリクエストを送信（ペイロードなし）
        return $this->sendRequest($endpoint, $vapidHeaders);
    }

    /**
     * VAPID認証ヘッダーを生成
     */
    private function getVapidHeaders($endpoint) {
        $url = parse_url($endpoint);
        $audience = $url['scheme'] . '://' . $url['host'];

        if (isset($url['port'])) {
            $audience .= ':' . $url['port'];
        }

        // JWT ヘッダー
        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];

        // JWT ペイロード
        $payload = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12時間
            'sub' => $this->vapidSubject
        ];

        // JWTを生成（ES256署名）
        $jwt = $this->generateJWT($header, $payload);

        return [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey
        ];
    }

    /**
     * ES256署名を使用してJWTを生成
     */
    private function generateJWT($header, $payload) {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $dataToSign = $headerEncoded . '.' . $payloadEncoded;

        // VAPID秘密鍵をデコード
        $privateKeyRaw = $this->base64UrlDecode($this->vapidPrivateKey);

        // ECDSAの秘密鍵を準備（P-256曲線）
        // 秘密鍵をPEM形式に変換
        $privateKeyPEM = $this->createECPrivateKeyPEM($privateKeyRaw);

        // ES256署名を生成
        $signature = '';
        if (openssl_sign($dataToSign, $signature, $privateKeyPEM, OPENSSL_ALGO_SHA256)) {
            // DER形式の署名をrawフォーマット（r || s）に変換
            $signature = $this->derToRaw($signature);
            $signatureEncoded = $this->base64UrlEncode($signature);
            return $dataToSign . '.' . $signatureEncoded;
        } else {
            throw new Exception('Failed to sign JWT');
        }
    }

    /**
     * 秘密鍵バイトからPEM形式を生成
     */
    private function createECPrivateKeyPEM($privateKeyBytes) {
        // 公開鍵も必要なので、VAPID公開鍵から取得
        $publicKeyBytes = $this->base64UrlDecode($this->vapidPublicKey);

        // SEC1 ECPrivateKey構造を作成
        $der = $this->createECPrivateKeyDER($privateKeyBytes, $publicKeyBytes);

        // PEM形式に変換
        $pem = "-----BEGIN EC PRIVATE KEY-----\n";
        $pem .= chunk_split(base64_encode($der), 64, "\n");
        $pem .= "-----END EC PRIVATE KEY-----\n";

        return $pem;
    }

    /**
     * EC秘密鍵のDER形式を作成
     */
    private function createECPrivateKeyDER($privateKey, $publicKey) {
        // ECPrivateKey ::= SEQUENCE {
        //   version INTEGER { ecPrivkeyVer1(1) },
        //   privateKey OCTET STRING,
        //   parameters [0] ECParameters {{ NamedCurve }} OPTIONAL,
        //   publicKey [1] BIT STRING OPTIONAL
        // }

        $version = "\x02\x01\x01"; // INTEGER 1

        // privateKey (32 bytes)
        $privateKeyOctet = "\x04\x20" . $privateKey; // OCTET STRING

        // OID for prime256v1 (1.2.840.10045.3.1.7)
        $oid = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $parameters = "\xa0\x0a" . $oid; // [0] EXPLICIT

        // publicKey (65 bytes: 0x04 + x + y)
        $publicKeyBits = "\x03\x42\x00" . $publicKey; // BIT STRING
        $publicKeyTag = "\xa1\x44" . $publicKeyBits; // [1] EXPLICIT

        $contents = $version . $privateKeyOctet . $parameters . $publicKeyTag;

        // SEQUENCE
        $length = strlen($contents);
        if ($length < 128) {
            $der = "\x30" . chr($length) . $contents;
        } else {
            $der = "\x30\x81" . chr($length) . $contents;
        }

        return $der;
    }

    /**
     * DER形式のECDSA署名をraw形式に変換
     */
    private function derToRaw($der) {
        // DER: SEQUENCE { r INTEGER, s INTEGER }
        $offset = 0;

        // SEQUENCE tag
        if (ord($der[$offset++]) !== 0x30) {
            throw new Exception('Invalid DER signature');
        }

        // SEQUENCE length
        $offset++; // skip length

        // r INTEGER
        if (ord($der[$offset++]) !== 0x02) {
            throw new Exception('Invalid DER signature');
        }
        $rLength = ord($der[$offset++]);
        $r = substr($der, $offset, $rLength);
        $offset += $rLength;

        // s INTEGER
        if (ord($der[$offset++]) !== 0x02) {
            throw new Exception('Invalid DER signature');
        }
        $sLength = ord($der[$offset++]);
        $s = substr($der, $offset, $sLength);

        // Remove leading zero bytes if present
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Pad to 32 bytes
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /**
     * HTTPリクエストを送信
     */
    private function sendRequest($endpoint, $vapidHeaders) {
        $ch = curl_init($endpoint);

        $headers = [
            'TTL: 86400',
            'Content-Length: 0'
        ];

        foreach ($vapidHeaders as $key => $value) {
            $headers[] = "$key: $value";
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '', // 空のペイロード
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_VERBOSE => true
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // ログ出力
        error_log("Push notification response: HTTP $statusCode");
        error_log("Push endpoint: $endpoint");

        if ($error) {
            error_log("cURL error: $error");
            throw new Exception("cURL error: $error");
        }

        if ($statusCode >= 400) {
            error_log("Push notification failed: $response");
            throw new Exception("Push notification failed with status $statusCode: $response");
        }

        return [
            'success' => true,
            'statusCode' => $statusCode,
            'response' => $response
        ];
    }

    /**
     * Base64 URL-safe エンコード
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe デコード
     */
    private function base64UrlDecode($data) {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
