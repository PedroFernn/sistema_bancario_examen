-- Aplica una comisión del 1% después de cada retiro
DELIMITER $$

DROP TRIGGER IF EXISTS trg_aplicar_comision_retiro$$

CREATE TRIGGER trg_aplicar_comision_retiro
AFTER INSERT ON transacciones
FOR EACH ROW
BEGIN
    DECLARE v_comision DECIMAL(15,2);
    DECLARE v_saldo_actual DECIMAL(15,2);
    DECLARE v_nuevo_saldo DECIMAL(15,2);
    
    -- Solo aplicar la comisión si la transacción es un retiro
    IF NEW.tipo_transaccion = 'retiro' THEN
        
        -- Calcular la comisión del 1%
        SET v_comision = NEW.monto * 0.01;
        
        -- Obtener el saldo actual de la cuenta
        SELECT saldo INTO v_saldo_actual
        FROM cuentas
        WHERE id_cuenta = NEW.id_cuenta;
        
        -- Calcular el nuevo saldo después de la comisión
        SET v_nuevo_saldo = v_saldo_actual - v_comision;
        
        -- Actualizar el saldo de la cuenta descontando la comisión
        UPDATE cuentas
        SET saldo = v_nuevo_saldo
        WHERE id_cuenta = NEW.id_cuenta;
        
        -- Registrar la transacción de comisión
        INSERT INTO transacciones
            (id_cuenta, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion)
        VALUES
            (NEW.id_cuenta, 'comision', v_comision, v_saldo_actual, v_nuevo_saldo, 
             CONCAT('Comisión 1% por retiro de $', FORMAT(NEW.monto, 2)));
    END IF;
    
END$$

DELIMITER ;

-- =====================================================
-- PROCEDIMIENTO ALMACENADO: sp_realizar_retiro
-- Permite realizar retiros validando el saldo disponible
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_realizar_retiro$$

CREATE PROCEDURE sp_realizar_retiro(
    IN p_numero_cuenta VARCHAR(20),
    IN p_monto DECIMAL(15,2),
    IN p_descripcion VARCHAR(255),
    OUT p_resultado INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_id_cuenta INT;
    DECLARE v_saldo_actual DECIMAL(15,2);
    DECLARE v_nuevo_saldo DECIMAL(15,2);
    DECLARE v_comision DECIMAL(15,2);
    DECLARE v_total_deduccion DECIMAL(15,2);
    
    -- Manejador de errores
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_resultado = 0;
        SET p_mensaje = 'Error: No se pudo realizar el retiro';
        ROLLBACK;
    END;
    
    -- Iniciar transacción
    START TRANSACTION;
    
    -- Validar que el monto sea positivo
    IF p_monto <= 0 THEN
        SET p_resultado = 0;
        SET p_mensaje = 'Error: El monto debe ser mayor a cero';
        ROLLBACK;
    ELSE
        -- Buscar la cuenta y obtener el saldo actual
        SELECT id_cuenta, saldo 
        INTO v_id_cuenta, v_saldo_actual
        FROM cuentas
        WHERE numero_cuenta = p_numero_cuenta 
        AND estado = 'activa'
        FOR UPDATE;
        
        -- Verificar si la cuenta existe
        IF v_id_cuenta IS NULL THEN
            SET p_resultado = 0;
            SET p_mensaje = 'Error: La cuenta no existe o está inactiva';
            ROLLBACK;
        ELSE
            -- Calcular la comisión del 1%
            SET v_comision = p_monto * 0.01;
            SET v_total_deduccion = p_monto + v_comision;
            
            -- Validar que hay suficiente saldo para el retiro y la comisión
            IF v_saldo_actual < v_total_deduccion THEN
                SET p_resultado = 0;
                SET p_mensaje = CONCAT('Error: Saldo insuficiente. Saldo disponible: $', 
                                      FORMAT(v_saldo_actual, 2), 
                                      '. Se requiere: $', FORMAT(v_total_deduccion, 2),
                                      ' (Retiro: $', FORMAT(p_monto, 2), 
                                      ' + Comisión 1%: $', FORMAT(v_comision, 2), ')');
                ROLLBACK;
            ELSE
                -- Calcular el nuevo saldo después del retiro (sin comisión, el trigger la aplica)
                SET v_nuevo_saldo = v_saldo_actual - p_monto;
                
                -- Actualizar el saldo de la cuenta
                UPDATE cuentas 
                SET saldo = v_nuevo_saldo
                WHERE id_cuenta = v_id_cuenta;
                
                -- Registrar la transacción de retiro
                -- El trigger se encargará de aplicar la comisión automáticamente
                INSERT INTO transacciones 
                    (id_cuenta, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion)
                VALUES 
                    (v_id_cuenta, 'retiro', p_monto, v_saldo_actual, v_nuevo_saldo, p_descripcion);
                
                -- Confirmar transacción exitosa
                SET p_resultado = 1;
                SET p_mensaje = CONCAT('Retiro exitoso. Monto retirado: $', FORMAT(p_monto, 2),
                                      '. Comisión aplicada: $', FORMAT(v_comision, 2),
                                      '. Nuevo saldo: $', FORMAT(v_nuevo_saldo - v_comision, 2));
                COMMIT;
            END IF;
        END IF;
    END IF;
    
END$$

DELIMITER ;

-- =====================================================
-- PRUEBAS DEL TRIGGER Y PROCEDIMIENTO DE RETIRO
-- =====================================================

-- Ver saldo inicial de la cuenta 1000000001
SELECT numero_cuenta, saldo FROM cuentas WHERE numero_cuenta = '1000000001';

-- Prueba 1: Retiro exitoso de $200 (se debe aplicar comisión de $2)
CALL sp_realizar_retiro('1000000001', 200.00, 'Retiro en cajero automático', @resultado, @mensaje);
SELECT @resultado AS resultado, @mensaje AS mensaje;

-- Verificar el saldo después del retiro y la comisión
SELECT numero_cuenta, saldo FROM cuentas WHERE numero_cuenta = '1000000001';

-- Ver las transacciones generadas (retiro + comisión)
SELECT 
    tipo_transaccion,
    monto,
    saldo_anterior,
    saldo_nuevo,
    descripcion,
    fecha_transaccion
FROM transacciones
WHERE id_cuenta = (SELECT id_cuenta FROM cuentas WHERE numero_cuenta = '1000000001')
ORDER BY id_transaccion DESC
LIMIT 2;

-- Prueba 2: Retiro que excede el saldo (debe fallar)
CALL sp_realizar_retiro('1000000002', 2000.00, 'Retiro excesivo', @resultado, @mensaje);
SELECT @resultado AS resultado, @mensaje AS mensaje;

-- Prueba 3: Retiro casi al límite del saldo
-- Primero verificamos el saldo de la cuenta 1000000003
SELECT numero_cuenta, saldo FROM cuentas WHERE numero_cuenta = '1000000003';

-- Intentar retirar $990 (con comisión de $9.90, total $999.90)
CALL sp_realizar_retiro('1000000003', 990.00, 'Retiro al límite', @resultado, @mensaje);
SELECT @resultado AS resultado, @mensaje AS mensaje;

-- Verificar saldo final
SELECT numero_cuenta, saldo FROM cuentas WHERE numero_cuenta = '1000000003';

-- Ver todas las transacciones
SELECT 
    c.numero_cuenta,
    t.tipo_transaccion,
    t.monto,
    t.saldo_anterior,
    t.saldo_nuevo,
    t.descripcion,
    t.fecha_transaccion
FROM transacciones t
INNER JOIN cuentas c ON t.id_cuenta = c.id_cuenta
ORDER BY t.fecha_transaccion DESC;