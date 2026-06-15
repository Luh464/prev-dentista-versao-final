<?php
// config/database.php

$host     = '127.0.0.1';   // usar IP em vez de 'localhost' no Windows/XAMPP
$port     = '3306';
$db_name  = 'clinica_prev_dentistas';
$username = 'root';
$password = '';             // padrão XAMPP: sem senha

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    $code = $e->getCode();
    $msg  = $e->getMessage();

    // Mensagens de erro mais claras para o desenvolvedor
    if ($code == 2002) {
        die("Erro de conexão: MySQL não encontrado em $host:$port. Verifique se o MySQL está rodando no XAMPP.");
    } elseif ($code == 1049) {
        die("Banco de dados '$db_name' não existe. Importe o SQL pelo phpMyAdmin primeiro.");
    } elseif ($code == 1045) {
        die("Usuário ou senha inválidos. Verifique as credenciais em config/database.php.");
    } else {
        die("Erro na conexão com o banco de dados [$code]: $msg");
    }
}
