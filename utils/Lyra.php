<?php
// Archivo COMPLETO Y DEFINITIVO (V4.1) - Integrado con ia_modelos y Timeout Dinámico

require_once __DIR__ . '/api_clients/GeminiApiClient.php';

class Lyra {
    private PDO $pdo;
    private array $apiProvidersKeys;

    /**
     * Constructor de la clase Lyra.
     * @param PDO $pdo Una instancia activa de la conexión a la base de datos.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // Cargamos todas las claves de API definidas en init.php en un array asociativo.
        // Esto nos permite gestionar claves de múltiples proveedores de forma centralizada.
        $this->apiProvidersKeys = [
            'Google' => defined('GEMINI_API_KEYS') ? GEMINI_API_KEYS : [],
            'HuggingFace' => defined('HUGGINGFACE_API_KEY') ? HUGGINGFACE_API_KEY : []
            // En el futuro, podemos añadir más proveedores aquí.
        ];
    }

    // --- MÉTODOS DEL SISTEMA DE FAILSAFE ---

    /**
     * Incrementa el contador de errores de un modelo específico en la base de datos.
     * @param int $modelId El ID del modelo que ha fallado.
     */
    private function recordErrorForModel(int $modelId): void {
        $sql = "UPDATE ia_modelos SET error_count = error_count + 1, last_error_at = NOW() WHERE id_modelo = ?";
        $this->pdo->prepare($sql)->execute([$modelId]);
    }

    /**
     * Comprueba si un modelo ha superado el umbral de errores y lo desactiva si es necesario.
     * @param int $modelId El ID del modelo a comprobar.
     */
    private function checkForFailsafe(int $modelId): void {
        // Obtenemos el umbral máximo de errores desde la tabla de configuración.
        // Esto nos permite ajustarlo desde el panel de admin sin tocar el código.
        $stmt_config = $this->pdo->prepare("SELECT valor FROM ia_configuracion WHERE clave = 'max_consecutive_errors'");
        $stmt_config->execute();
        $maxErrors = $stmt_config->fetchColumn() ?: 5; // Usamos 5 como valor por defecto si no está configurado.

        // Obtenemos el contador de errores actual del modelo.
        $stmt = $this->pdo->prepare("SELECT error_count FROM ia_modelos WHERE id_modelo = ?");
        $stmt->execute([$modelId]);
        $currentErrors = $stmt->fetchColumn();

        if ($currentErrors >= $maxErrors) {
            // Si se alcanza el umbral, cambiamos el estado del modelo a 'error'.
            $this->pdo->prepare("UPDATE ia_modelos SET estado = 'error' WHERE id_modelo = ?")->execute([$modelId]);
            log_system_event("FAILSAFE ACTIVADO: Modelo de IA desactivado por errores recurrentes.", [
                'id_modelo' => $modelId,
                'error_count' => $currentErrors
            ], 'api');
        }
    }

    /**
     * Resetea el contador de errores de un modelo a 0 tras una llamada exitosa.
     * @param int $modelId El ID del modelo que ha funcionado correctamente.
     */
    private function resetErrorCountForModel(int $modelId): void {
        $this->pdo->prepare("UPDATE ia_modelos SET error_count = 0, last_error_at = NULL WHERE id_modelo = ?")->execute([$modelId]);
    }

    // --- ORQUESTACIÓN Y EJECUCIÓN ---

    /**
     * Orquestador central. Ejecuta la llamada a la API para un modelo específico.
     * @param string $finalPrompt El prompt completo a enviar.
     * @param array $modelData Un array asociativo con los datos del modelo (id_modelo, nombre_proveedor, timeout_seconds).
     * @return array La respuesta decodificada de la API.
     * @throws Exception Si todos los intentos fallan o no hay claves de API.
     */
    private function executeApiCall(string $finalPrompt, array $modelData): array {
        $providerName = $modelData['nombre_proveedor'];
        $modelId = $modelData['id_modelo'];
        $timeout = (int)$modelData['timeout_seconds'];

        $providerKeys = $this->apiProvidersKeys[$providerName] ?? [];
        if (empty($providerKeys)) {
            throw new Exception("No se encontraron claves de API para el proveedor: $providerName");
        }

        $maxTries = count($providerKeys);
        $currentKeyIndex = 0; // El índice se gestiona localmente para cada llamada.

        for ($i = 0; $i < $maxTries; $i++) {
            try {
                $apiKey = $providerKeys[$currentKeyIndex];
                
                // En el futuro, un patrón "Factory" seleccionaría el cliente de API correcto.
                // Por ahora, solo tenemos implementado el de Google.
                if ($providerName === 'Google') {
                    $client = new GeminiApiClient($apiKey);
                } else {
                    throw new Exception("Cliente de API no implementado para el proveedor: $providerName");
                }
                
                // Ejecutamos la llamada pasando el timeout dinámico.
                $response = $client->sendPrompt($finalPrompt, $timeout);
                
                // Si la llamada tuvo éxito, reseteamos su contador de errores y devolvemos la respuesta.
                $this->resetErrorCountForModel($modelId);
                return $response;

            } catch (Exception $e) {
                // Si la llamada falla, registramos el error y comprobamos el failsafe.
                $this->recordErrorForModel($modelId);
                $this->checkForFailsafe($modelId);
                
                // Rotamos al siguiente índice de clave para el próximo reintento.
                $currentKeyIndex = ($currentKeyIndex + 1) % $maxTries;
                
                // Si este era el último reintento, lanzamos una excepción definitiva.
                if ($i === $maxTries - 1) {
                    throw new Exception("Todos los intentos para el modelo ID $modelId fallaron. Último error: " . $e->getMessage());
                }
            }
        }
        // Este punto solo se alcanzaría si el bucle falla de forma inesperada.
        throw new Exception("No se pudo procesar la solicitud para el modelo ID $modelId.");
    }
    
    /**
     * Método genérico para procesar un prompt de usuario con una personalidad de Lyra.
     * @param string $userPrompt El prompt original del usuario.
     * @param string $personalityConfigKey La 'clave' de la personalidad en la tabla `ia_configuracion`.
     * @param string $modelType El 'tipo_modelo' a buscar en la tabla `ia_modelos` (ej: 'texto').
     * @return string La respuesta de texto procesada por la IA.
     * @throws Exception Si no hay modelos activos o falta configuración.
     */
    private function processWithPersonality(string $userPrompt, string $personalityConfigKey, string $modelType = 'texto'): string {
        // 1. Encontrar un modelo activo del tipo y proveedor correctos.
        $sql = "SELECT m.id_modelo, p.nombre_proveedor, m.timeout_seconds
                FROM ia_modelos m
                JOIN ia_proveedores p ON m.id_proveedor = p.id_proveedor
                WHERE m.tipo_modelo = :modelType AND m.estado = 'activo'
                ORDER BY RAND() LIMIT 1"; // Orden aleatorio para balancear la carga si hay varios.
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':modelType' => $modelType]);
        $modelData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$modelData) {
            throw new Exception("No hay modelos de tipo '$modelType' activos disponibles en este momento.");
        }

        // 2. Obtener el prompt del sistema para la personalidad desde la tabla de configuración.
        $stmt_prompt = $this->pdo->prepare("SELECT valor FROM ia_configuracion WHERE clave = ?");
        $stmt_prompt->execute([$personalityConfigKey]);
        $systemPrompt = $stmt_prompt->fetchColumn();
        if (!$systemPrompt) {
            throw new Exception("No se encontró la configuración para la personalidad: $personalityConfigKey");
        }
        
        $finalPrompt = $systemPrompt . "\n\n### PROMPT A PROCESAR:\n" . $userPrompt;
        
        // 3. Ejecutar la llamada a la API a través del orquestador.
        $response = $this->executeApiCall($finalPrompt, $modelData);
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? 'No se pudo obtener una respuesta de texto válida de la API.';
    }

    // --- MÉTODOS PÚBLICOS (La Interfaz de Lyra) ---

    /**
     * Usa la personalidad "Guardiana" para analizar un prompt en busca de contenido inapropiado.
     * @param string $userPrompt El prompt del usuario.
     * @return string El veredicto de la Guardiana.
     */
    public function analizarConGuardiana(string $userPrompt): string {
        return $this->processWithPersonality($userPrompt, 'lyra_guardiana_prompt', 'texto');
    }

    /**
     * Usa la personalidad "Creativa" para enriquecer y mejorar un prompt.
     * @param string $userPrompt El prompt del usuario.
     * @return string El prompt mejorado.
     */
    public function mejorarConCreativa(string $userPrompt): string {
        return $this->processWithPersonality($userPrompt, 'lyra_creativa_prompt', 'texto');
    }
}