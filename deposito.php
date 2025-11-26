<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Bancario - Depósitos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #e6e6e6;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border: 1px solid #999;
        }

        .header {
            text-align: left;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 4px;
            color: #222;
            font-weight: bold;
        }

        .header p {
            color: #444;
            font-size: 15px;
        }

        .info-cuenta {
            background: #f2f2f2;
            padding: 15px;
            border: 1px solid #888;
            margin-bottom: 20px;
        }

        .info-cuenta p {
            margin-bottom: 6px;
            color: #222;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #222;
        }

        select, input, textarea {
            width: 100%;
            padding: 10px;
            font-size: 15px;
            border: 1px solid #666;
            background: #fff;
        }

        textarea {
            resize: none;
        }

        button {
            background: #222;
            color: white;
            border: 1px solid #000;
            padding: 12px 18px;
            cursor: pointer;
            font-size: 16px;
            letter-spacing: 0.5px;
        }

        button:hover {
            background: #444;
        }

        /* Alertas rígidas */
        .alert {
            padding: 12px;
            border: 1px solid #333;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d9f2d9;
            color: #1b5e20;
        }

        .alert-error {
            background: #f8d7da;
            color: #7a1116;
        }

        .menu-link {
            margin-top: 25px;
            text-align: left;
        }

        .menu-link a {
            display: inline-block;
            padding: 10px 14px;
            background: #111;
            color: white;
            border: 1px solid #000;
            text-decoration: none;
            font-size: 14px;
        }

        .menu-link a:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Sistema Bancario</h1>
            <p>Realizar Depósito</p>
        </div>

        <div class="content">
            <?php
            // Configuración de la base de datos
            $host = 'localhost';
            $dbname = 'banco_sistema';
            $username = 'root';
            $password = '';

            $conn = new mysqli($host, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("<div class='alert alert-error'>Error de conexión: " . $conn->connect_error . "</div>");
            }

            $conn->set_charset("utf8mb4");

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $numero_cuenta = $_POST['numero_cuenta'];
                $monto = $_POST['monto'];
                $descripcion = $_POST['descripcion'];

                $stmt = $conn->prepare("CALL sp_realizar_deposito(?, ?, ?, @resultado, @mensaje)");
                $stmt->bind_param("sds", $numero_cuenta, $monto, $descripcion);

                if ($stmt->execute()) {
                    $stmt->close();
                    $result = $conn->query("SELECT @resultado AS resultado, @mensaje AS mensaje");
                    $row = $result->fetch_assoc();
                    
                    if ($row['resultado'] == 1) {
                        echo "<div class='alert alert-success'><strong>✓ Éxito:</strong> " . htmlspecialchars($row['mensaje']) . "</div>";
                    } else {
                        echo "<div class='alert alert-error'><strong>✗ Error:</strong> " . htmlspecialchars($row['mensaje']) . "</div>";
                    }
                } else {
                    echo "<div class='alert alert-error'>Error al ejecutar el procedimiento</div>";
                }
            }

            $query = "SELECT c.numero_cuenta, CONCAT(ch.nombre, ' ', ch.apellido) AS titular, 
                      c.saldo, c.tipo_cuenta
                      FROM cuentas c
                      INNER JOIN cuenta_habientes ch ON c.id_cuenta_habiente = ch.id_cuenta_habiente
                      WHERE c.estado = 'activa'
                      ORDER BY c.numero_cuenta";

            $cuentas = $conn->query($query);

            if (isset($_POST['numero_cuenta']) && !empty($_POST['numero_cuenta'])) {
                $num_cuenta = $_POST['numero_cuenta'];
                $info_query = "SELECT c.numero_cuenta, CONCAT(ch.nombre, ' ', ch.apellido) AS titular, 
                              c.saldo, c.tipo_cuenta
                              FROM cuentas c
                              INNER JOIN cuenta_habientes ch ON c.id_cuenta_habiente = ch.id_cuenta_habiente
                              WHERE c.numero_cuenta = ?";
                $stmt = $conn->prepare($info_query);
                $stmt->bind_param("s", $num_cuenta);
                $stmt->execute();
                $info_result = $stmt->get_result();

                if ($info = $info_result->fetch_assoc()) {
                    echo "<div class='info-cuenta'>";
                    echo "<p><strong>Titular:</strong> " . htmlspecialchars($info['titular']) . "</p>";
                    echo "<p><strong>Número de Cuenta:</strong> " . htmlspecialchars($info['numero_cuenta']) . "</p>";
                    echo "<p><strong>Tipo:</strong> " . ucfirst($info['tipo_cuenta']) . "</p>";
                    echo "<p><strong>Saldo Actual:</strong> $" . number_format($info['saldo'], 2) . "</p>";
                    echo "</div>";
                }

                $stmt->close();
            }
            ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="numero_cuenta">Seleccionar Cuenta:</label>
                    <select name="numero_cuenta" id="numero_cuenta" required onchange="this.form.submit()">
                        <option value="">-- Seleccione una cuenta --</option>
                        <?php
                        if ($cuentas->num_rows > 0) {
                            $cuentas->data_seek(0);
                            while($cuenta = $cuentas->fetch_assoc()) {
                                $selected = (isset($_POST['numero_cuenta']) && $_POST['numero_cuenta'] == $cuenta['numero_cuenta']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($cuenta['numero_cuenta']) . "' $selected>";
                                echo htmlspecialchars($cuenta['numero_cuenta']) . " - " . htmlspecialchars($cuenta['titular']);
                                echo " (Saldo: $" . number_format($cuenta['saldo'], 2) . ")";
                                echo "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="monto">Monto a Depositar ($):</label>
                    <input type="number" name="monto" id="monto" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción (opcional):</label>
                    <textarea name="descripcion" id="descripcion" rows="3"></textarea>
                </div>

                <button type="submit">💵 Realizar Depósito</button>
            </form>

            <div class="menu-link">
                <a href="index.php">← Volver al Menú Principal</a>
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
