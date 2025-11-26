<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bancario - Menú Principal</title>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: #f2f2f2;
        color: #333;
    }

    .container {
        width: 900px;
        margin: 25px auto;
        background: #fff;
        padding: 25px;
        border: 1px solid #444;   /* bordes más duros */
    }

    .header {
        text-align: center;
        border-bottom: 2px solid #444;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .header h1 {
        font-size: 26px;
        margin-bottom: 8px;
        font-weight: bold;
    }

    /* --- Menú --- */
    .menu-grid {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .menu-card {
        flex: 1;
        text-align: center;
        text-decoration: none;
        color: #111;
        padding: 18px;
        border: 2px solid #333;
        background: #f5f5f5;
    }

    .menu-card:hover {
        background: #ddd;        /* cambio duro sin suavidad */
        border-color: #000;
    }

    .menu-card .icon {
        font-size: 40px;
        margin-bottom: 10px;
    }

    .menu-card h3 {
        font-size: 18px;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .menu-card p {
        font-size: 14px;
        color: #444;
    }

    /* --- Estadísticas --- */
    .stats-section {
        margin-top: 30px;
    }

    .stats-section h2 {
        margin-bottom: 10px;
        font-size: 20px;
        border-left: 6px solid #222;   /* borde fuerte */
        padding-left: 10px;
        font-weight: bold;
    }

    .stats-grid {
        display: flex;
        gap: 20px;
        margin-top: 15px;
    }

    .stat-card {
        flex: 1;
        background: #f5f5f5;
        border: 2px solid #333;
        padding: 15px;
        text-align: center;
    }

    .stat-card h4 {
        font-size: 15px;
        color: #222;
        margin-bottom: 8px;
        font-weight: bold;
    }

    .stat-card .value {
        font-size: 22px;
        font-weight: bold;
        color: #000;
    }

    /* --- Lista de cuentas --- */
    .cuentas-list {
        margin-top: 20px;
        border-top: 2px solid #444;
        padding-top: 15px;
    }

    .cuenta-item {
        display: flex;
        justify-content: space-between;
        padding: 12px;
        border: 2px solid #333;
        margin-bottom: 10px;
        background: #fff;
    }

    .cuenta-info h4 {
        font-size: 16px;
        margin-bottom: 3px;
        font-weight: bold;
    }

    .cuenta-info p {
        font-size: 14px;
        color: #444;
    }

    .cuenta-saldo {
        font-size: 18px;
        font-weight: bold;
        color: #000;
        padding-left: 10px;
    }
</style>

</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Sistema Bancario</h1>
            <p>Gestión de Cuentas y Transacciones</p>
        </div>
        
        <div class="content">

            <div class="menu-grid">
                <a href="deposito.php" class="menu-card">
                    <div class="icon">💰</div>
                    <h3>Depósitos</h3>
                    <p>Realizar depósitos a cuentas bancarias</p>
                </a>
                
                <a href="retiro.php" class="menu-card">
                    <div class="icon">💸</div>
                    <h3>Retiros</h3>
                    <p>Realizar retiros con comisión del 1%</p>
                </a>
            </div>

            <?php
            $host = 'localhost';
            $dbname = 'banco_sistema';
            $username = 'root';
            $password = '';

            $conn = new mysqli($host, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("<div style='color: red;'>Error de conexión: " . $conn->connect_error . "</div>");
            }

            $conn->set_charset("utf8mb4");

            $total_cuentas = $conn->query("SELECT COUNT(*) as total FROM cuentas WHERE estado = 'activa'")->fetch_assoc()['total'];
            $total_saldo = $conn->query("SELECT SUM(saldo) as total FROM cuentas WHERE estado = 'activa'")->fetch_assoc()['total'];
            $total_transacciones = $conn->query("SELECT COUNT(*) as total FROM transacciones")->fetch_assoc()['total'];
            ?>

            <div class="stats-section">
                <h2>📊 Estadísticas del Sistema</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>Cuentas Activas</h4>
                        <div class="value"><?php echo $total_cuentas; ?></div>
                    </div>
                    <div class="stat-card">
                        <h4>Saldo Total</h4>
                        <div class="value">$<?php echo number_format($total_saldo, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h4>Transacciones</h4>
                        <div class="value"><?php echo $total_transacciones; ?></div>
                    </div>
                </div>
            </div>

            <div class="stats-section">
                <h2>👥 Cuentas Bancarias</h2>
                <div class="cuentas-list">
                    <?php
                    $query = "SELECT c.numero_cuenta, CONCAT(ch.nombre, ' ', ch.apellido) AS titular, 
                              c.saldo, c.tipo_cuenta
                              FROM cuentas c
                              INNER JOIN cuenta_habientes ch ON c.id_cuenta_habiente = ch.id_cuenta_habiente
                              WHERE c.estado = 'activa'
                              ORDER BY c.numero_cuenta";
                    $cuentas = $conn->query($query);

                    if ($cuentas->num_rows > 0) {
                        while($cuenta = $cuentas->fetch_assoc()) {
                            echo "<div class='cuenta-item'>";
                            echo "<div class='cuenta-info'>";
                            echo "<h4>" . htmlspecialchars($cuenta['titular']) . "</h4>";
                            echo "<p>Cuenta: " . htmlspecialchars($cuenta['numero_cuenta']) . " | ";
                            echo "Tipo: " . ucfirst($cuenta['tipo_cuenta']) . "</p>";
                            echo "</div>";
                            echo "<div class='cuenta-saldo'>$" . number_format($cuenta['saldo'], 2) . "</div>";
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='cuenta-item'>No hay cuentas registradas</div>";
                    }

                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
