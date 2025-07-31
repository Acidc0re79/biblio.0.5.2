<?php
// Archivo COMPLETO Y ACTUALIZADO: /utils/api_clients/GeminiApiClient.php

class GeminiApiClient {
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=';

    public function __construct(string $apiKey) {
        if (empty($apiKey)) {
            throw new Exception("La clave de API de Gemini no puede estar vacía.");
        }
        $this->apiKey = $apiKey;
    }

    /**
     * Envía un prompt a la API de Gemini, respetando un timeout dinámico.
     * @param string $prompt El texto a enviar.
     * @param int $timeout El tiempo máximo de espera en segundos.
     * @return array La respuesta de la API decodificada.
     * @throws Exception Si la llamada a la API falla.
     */
    public function sendPrompt(string $prompt, int $timeout = 60): array { // ✅ Se añade el parámetro de timeout
        $url = $this->apiUrl . $this->apiKey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // ✅ LÍNEA CLAVE: Se establece el timeout dinámicamente para la llamada completa.
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // ... el resto del manejo de errores se mantiene igual ...
        if ($response === false) {
            // El error 28 de cURL es específicamente un timeout.
            if (strpos($error, 'timed out') !== false) {
                 throw new Exception("La API de Gemini superó el tiempo de espera de {$timeout} segundos.");
            }
            throw new Exception("Error en cURL al contactar la API de Gemini: " . $error);
        }

        $responseData = json_decode($response, true);

        if ($httpCode !== 200 || !$responseData || isset($responseData['error'])) {
            $errorMessage = $responseData['error']['message'] ?? 'Error desconocido en la respuesta de la API.';
            log_system_event("Fallo en API Gemini", ['http_code' => $httpCode, 'response_body' => $response], 'api');
            throw new Exception("La API de Gemini devolvió un error (HTTP $httpCode): " . $errorMessage);
        }
        
        return $responseData;
    }
}