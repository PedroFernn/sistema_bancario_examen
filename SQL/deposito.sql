-- Permite realizar depósitos en una cuenta bancaria
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_realizar_deposito$$

CREATE PROCEDURE sp_realizar_deposito(
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
    
    -- Manejador de errores
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_resultado = 0;
        SET p_mensaje = 'Error: No se pudo realizar el depósito';
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
            -- Calcular el nuevo saldo
            SET v_nuevo_saldo = v_saldo_actual + p_monto;
            
            -- Actualizar el saldo de la cuenta
            UPDATE cuentas 
            SET saldo = v_nuevo_saldo
            WHERE id_cuenta = v_id_cuenta;
            
            -- Registrar la transacción
            INSERT INTO transacciones 
                (id_cuenta, tipo_transaccion, monto, saldo_anterior, saldo_nuevo, descripcion)
            VALUES 
                (v_id_cuenta, 'deposito', p_monto, v_saldo_actual, v_nuevo_saldo, p_descripcion);
            
            -- Confirmar transacción exitosa
            SET p_resultado = 1;
            SET p_mensaje = CONCAT('Depósito exitoso. Nuevo saldo: $', FORMAT(v_nuevo_saldo, 2));
            COMMIT;
        END IF;
    END IF;
    
END$$

DELIMITER ;

-- PRUEBAS DEL PROCEDIMIENTO ALMACENADO

-- Prueba 1: Depósito exitoso de $500
CALL sp_realizar_deposito('1000000001', 500.00, 'Depósito en efectivo', @resultado, @mensaje);
SELECT @resultado AS resultado, @mensaje AS mensaje;

-- Verificar el saldo después del depósito
SELECT numero_cuenta, saldo FROM cuentas WHERE numero_cuenta = '1000000001';

-- Prueba 2: Depósito con monto negativo (debe fallar)
CALL sp_realizar_deposito('1000000002', -100.00, 'Depósito inválido', @resultado, @mensaje);
SELECT @resultado AS resultado, @mensaje AS mensaje;

-- Prueba 3: Depósito en cuenta inexistente (debe fallar)
CALL sp_realizar_deposito('9999999999', 200.00, 'Depósito a cuenta inexistente', @resultado, @mensaje);
SELECT @resultado AS resultado, @mensaje AS mensaje;

-- Ver historial de transacciones
SELECT 
    t.id_transaccion,
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