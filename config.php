<?php
/**
 * Archivo de Configuración de Base de Datos
 * config.php
 * 
 * Este archivo contiene la configuración de conexión a la base de datos
 * y funciones auxiliares para el sistema bancario.
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'banco_sistema');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Función para obtener conexión a la base de datos
 * @return mysqli|null Conexión a la base de datos o null en caso de error
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Error de conexión: " . $conn->connect_error);
        return null;
    }
    
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

/**
 * Función para cerrar la conexión a la base de datos
 * @param mysqli $conn Conexión a cerrar
 */
function closeDBConnection($conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}

/**
 * Función para sanitizar datos de entrada
 * @param string $data Dato a sanitizar
 * @return string Dato sanitizado
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Función para formatear moneda
 * @param float $amount Cantidad a formatear
 * @return string Cantidad formateada
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Función para formatear fecha
 * @param string $date Fecha a formatear
 * @return string Fecha formateada
 */
function formatDate($date) {
    return date('d/m/Y H:i:s', strtotime($date));
}

// Configuración de zona horaria
date_default_timezone_set('America/Managua');

// Configuración de errores (en producción cambiar a 0)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>