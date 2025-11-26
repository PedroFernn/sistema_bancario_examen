<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bancario - Retiros</title>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: #e0e0e0;
        color: #222;
    }

    .container {
        max-width: 900px;
        margin: 30px auto;
        background: #fff;
        border: 2px solid #000;
        padding: 25px;
    }

    .header {
        text-align: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #000;
        padding-bottom: 15px;
    }

    .header h1 {
        font-size: 26px;
        font-weight: bold;
    }

    .alert {
        padding: 12px;
        margin-bottom: 15px;
        border: 2px solid #000;
        font-size: 15px;
    }

    .alert-success {
        background: #c5e6c5;
        border-color: #1b5e20;
    }

    .alert-error {
        background: #e6b5b8;
        border-color: #7f0000;
    }

    .warning-box {
        background: #fff0b3;
        border: 2px solid #a67c00;
        padding: 12px;
        margin-bottom: 18px;
    }

    form {
        background: #f2f2f2;
        border: 2px solid #000;
        padding: 18px;
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 14px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    input, select, textarea {
        width: 100%;
        padding: 8px;
        border: 2px solid #000;
        font-size: 15px;
        background: #fff;
        color: #000;
    }

    button {
        padding: 10px 18px;
        background: #000;
        color: white;
        border: 2px solid #000;
        cursor: pointer;
        font-size: 15px;
        font-weight: bold;
        text-transform: uppercase;
    }

    button:hover {
        background: #333;
    }

    .info-cuenta, .detalles-retiro {
        background: #f2f2f2;
        border: 2px solid #000;
        padding: 15px;
        margin-bottom: 18px;
    }

    .historial {
        background: #f2f2f2;
        border: 2px solid #000;
        padding: 15px;
        margin-bottom: 25px;
    }

    .historial h3 {
        margin-bottom: 12px;
        text-transform: uppercase;
        font-weight: bold;
    }

    .transaccion-item {
        border: 2px solid #000;
        padding: 12px;
        margin-bottom: 12px;
        background: #fff;
    }

    .menu-link {
        text-align: center;
        margin-top: 15px;
    }

    .menu-link a {
        display: inline-block;
        margin: 0 10px;
        color: #000;
        text-decoration: none;
        padding: 8px 14px;
        border: 2px solid #000;
        font-weight: bold;
        text-transform: uppercase;
    }

    .menu-link a:hover {
        background: #000;
        color: #fff;
    }

    .comision-info {
        margin-top: 6px;
        font-size: 14px;
        font-weight: bold;
        color: #000;
    }
</style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏧 Sistema Bancario</h1>
            <p>Realizar Retiro</p>
        </div>

        <div class="content">

            <?php
            // --- PHP ORIGINAL (SIN CAMBIOS EN FUNCIONALIDAD) ---
            $host = 'localhost';
            $dbname = 'banco_sistema';
            $username = 'root';
            $password = '';

            $conn = new mysqli($host, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("<div class='alert alert-error'>Error de conexión: " . $conn->connect_error . "</div>");
            }

            $conn->set_charset("utf8mb4");

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['realizar_retiro'])) {
                $numero_cuenta = $_POST['numero_cuenta'];
                $monto = $_POST['monto'];
                $descripcion = $_POST['descripcion'];

                $stmt = $conn->prepare("CALL sp_realizar_retiro(?, ?, ?, @resultado, @mensaje)");
                $stmt->bind_param("sds", $numero_cuenta, $monto, $descripcion);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    
                    $result = $conn->query("SELECT @resultado AS resultado, @mensaje AS mensaje");
                    $row = $result->fetch_assoc();
                    
                    if ($row['resultado'] == 1) {
                        echo "<div class='alert alert-success'>
                                <strong>✓ Retiro Exitoso</strong><br>" . 
                                htmlspecialchars($row['mensaje']) . 
                             "</div>";

                        $comision = $monto * 0.01;
                        $total_deducido = $monto + $comision;

                        echo "<div class='detalles-retiro'>
                                <h3>📊 Detalles de la Transacción</h3>
                                <p><strong>Monto Retirado:</strong> $" . number_format($monto, 2) . "</p>
                                <p><strong>Comisión (1%):</strong> $" . number_format($comision, 2) . "</p>
                                <p><strong>Total Deducido:</strong> $" . number_format($total_deducido, 2) . "</p>
                              </div>";

                    } else {
                        echo "<div class='alert alert-error'>
                                <strong>✗ No es posible realizar el retiro</strong><br>" . 
                                htmlspecialchars($row['mensaje']) . 
                             "</div>";
                    }
                }
            }

            $query = "SELECT c.numero_cuenta, CONCAT(ch.nombre, ' ', ch.apellido) AS titular, 
                      c.saldo, c.tipo_cuenta
                      FROM cuentas c
                      INNER JOIN cuenta_habientes ch 
                      ON c.id_cuenta_habiente = ch.id_cuenta_habiente
                      WHERE c.estado = 'activa'
                      ORDER BY c.numero_cuenta";

            $cuentas = $conn->query($query);

            $cuenta_seleccionada = $_POST['numero_cuenta'] ?? '';

            if (!empty($cuenta_seleccionada)) {
                $info_query = "SELECT c.numero_cuenta, CONCAT(ch.nombre, ' ', ch.apellido) AS titular, 
                              c.saldo, c.tipo_cuenta
                              FROM cuentas c
                              INNER JOIN cuenta_habientes ch 
                              ON c.id_cuenta_habiente = ch.id_cuenta_habiente
                              WHERE c.numero_cuenta = ?";

                $stmt = $conn->prepare($info_query);
                $stmt->bind_param("s", $cuenta_seleccionada);
                $stmt->execute();
                $info_result = $stmt->get_result();
                
                if ($info = $info_result->fetch_assoc()) {
                    echo "<div class='info-cuenta'>
                            <p><strong>Titular:</strong> " . htmlspecialchars($info['titular']) . "</p>
                            <p><strong>Número de Cuenta:</strong> " . htmlspecialchars($info['numero_cuenta']) . "</p>
                            <p><strong>Tipo:</strong> " . ucfirst($info['tipo_cuenta']) . "</p>
                            <p><strong>Saldo Disponible:</strong> $" . number_format($info['saldo'], 2) . "</p>
                          </div>";
                }

                $stmt->close();
            }
            ?>

            <div class="warning-box">
                ⚠️ <strong>Importante:</strong> Se aplicará una comisión del 1% sobre el monto del retiro.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="numero_cuenta">Seleccionar Cuenta:</label>
                    <select name="numero_cuenta" id="numero_cuenta" required onchange="this.form.submit()">
                        <option value="">-- Seleccione una cuenta --</option>
                        <?php
                        $cuentas->data_seek(0);
                        while ($cuenta = $cuentas->fetch_assoc()) {
                            $selected = ($cuenta_seleccionada == $cuenta['numero_cuenta']) ? 'selected' : '';
                            echo "<option value='" . $cuenta['numero_cuenta'] . "' $selected>";
                            echo $cuenta['numero_cuenta'] . " - " . $cuenta['titular'] . 
                                 " (Saldo: $" . number_format($cuenta['saldo'], 2) . ")";
                            echo "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="monto">Monto a Retirar ($):</label>
                    <input type="number" name="monto" id="monto" step="0.01" min="0.01" 
                           placeholder="Ingrese el monto" required oninput="calcularComision()">
                    <div class="comision-info" id="comisionInfo" style="display:none;">
                        Comisión: <strong id="comisionMonto">$0.00</strong> |  
                        Total a deducir: <strong id="totalDeducir">$0.00</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción (opcional):</label>
                    <textarea name="descripcion" rows="3" placeholder="Ej: Retiro en cajero automático"></textarea>
                </div>

                <button type="submit" name="realizar_retiro">💸 Realizar Retiro</button>
            </form>

            <?php
            if (!empty($cuenta_seleccionada)) {
                $historial_query = "SELECT tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion, fecha_transaccion
                                   FROM transacciones
                                   WHERE id_cuenta = (SELECT id_cuenta FROM cuentas WHERE numero_cuenta = ?)
                                   ORDER BY fecha_transaccion DESC
                                   LIMIT 5";

                $stmt = $conn->prepare($historial_query);
                $stmt->bind_param("s", $cuenta_seleccionada);
                $stmt->execute();

                $historial = $stmt->get_result();

                if ($historial->num_rows > 0) {
                    echo "<div class='historial'>
                            <h3>📋 Últimas Transacciones</h3>";

                    while ($trans = $historial->fetch_assoc()) {
                        $icono = ($trans['tipo_transaccion'] == 'deposito') ? "💰" :
                                 (($trans['tipo_transaccion'] == 'retiro') ? "💸" : "📊");

                        echo "<div class='transaccion-item'>
                                <p><strong>$icono " . ucfirst($trans['tipo_transaccion']) . "</strong></p>
                                <p>Monto: $" . number_format($trans['monto'], 2) . "</p>
                                <p>Saldo: $" . number_format($trans['saldo_anterior'], 2) .
                                " → $" . number_format($trans['saldo_nuevo'], 2) . "</p>";

                        if (!empty($trans['descripcion'])) {
                            echo "<p>Detalle: " . htmlspecialchars($trans['descripcion']) . "</p>";
                        }

                        echo "<p style='font-size:12px;color:#666;'>" .
                            date('d/m/Y H:i:s', strtotime($trans['fecha_transaccion'])) . "</p>
                              </div>";
                    }

                    echo "</div>";
                }
                $stmt->close();
            }

            $conn->close();
            ?>

            <div class="menu-link">
                <a href="deposito.php">💰 Depósitos</a>
                <a href="index.php">🏠 Menú Principal</a>
            </div>

        </div>
    </div>

    <script>
    function calcularComision() {
        const monto = parseFloat(document.getElementById('monto').value) || 0;
        const comision = monto * 0.01;
        const total = monto + comision;

        if (monto > 0) {
            document.getElementById('comisionInfo').style.display = 'block';
            document.getElementById('comisionMonto').textContent = '$' + comision.toFixed(2);
            document.getElementById('totalDeducir').textContent = '$' + total.toFixed(2);
        } else {
            document.getElementById('comisionInfo').style.display = 'none';
        }
    }
    </script>

</body>
</html>
