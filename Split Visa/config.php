<?php
/**
 * Arquivo de configuração para carregar variáveis de ambiente
 * Este arquivo deve ser incluído antes de usar as variáveis
 */

// Função para carregar variáveis do arquivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Processa linhas no formato CHAVE=VALOR
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove aspas se existirem
            $value = trim($value, '"\'');
            
            // Define como variável de ambiente se não existir
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    return true;
}

// Carrega o arquivo .env do diretório atual
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
} else {
    // Fallback: tenta carregar do diretório pai (útil se config.php estiver em subdiretório)
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        loadEnv($envFile);
    }
}

// Função auxiliar para obter variável de ambiente com valor padrão
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }
    return $value;
}

