DROP DATABASE IF EXISTS banco_sistema;
CREATE DATABASE banco_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE banco_sistema;
-- cuentas 
CREATE TABLE cuenta_habientes (
    id_cuenta_habiente INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion VARCHAR(200),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
) ENGINE=InnoDB;

-- TABLA: cuentas
-- Almacena las cuentas bancarias de cada usuario
CREATE TABLE cuentas (
    id_cuenta INT PRIMARY KEY AUTO_INCREMENT,
    id_cuenta_habiente INT NOT NULL,
    numero_cuenta VARCHAR(20) UNIQUE NOT NULL,
    saldo DECIMAL(15,2) DEFAULT 1000.00,
    tipo_cuenta ENUM('ahorros', 'corriente') DEFAULT 'ahorros',
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activa', 'bloqueada', 'cerrada') DEFAULT 'activa',
    FOREIGN KEY (id_cuenta_habiente) REFERENCES cuenta_habientes(id_cuenta_habiente)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- TABLA: transacciones
-- Registra todas las operaciones realizadas
CREATE TABLE transacciones (
    id_transaccion INT PRIMARY KEY AUTO_INCREMENT,
    id_cuenta INT NOT NULL,
    tipo_transaccion ENUM('deposito', 'retiro', 'comision') NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    saldo_anterior DECIMAL(15,2) NOT NULL,
    saldo_nuevo DECIMAL(15,2) NOT NULL,
    descripcion VARCHAR(255),
    fecha_transaccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuenta) REFERENCES cuentas(id_cuenta)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- INSERCIÓN DE DATOS DE PRUEBA
-- 3 Cuenta habientes con saldo inicial de $1000

-- Cuenta habiente 1
INSERT INTO cuenta_habientes 
    (nombre, apellido, cedula, email, telefono, direccion) 
VALUES 
    ('Juan', 'Pérez García', '001-150684-1001A', 'juan.perez@email.com', '8888-1234', 'Managua, Nicaragua');

INSERT INTO cuentas 
    (id_cuenta_habiente, numero_cuenta, saldo, tipo_cuenta) 
VALUES 
    (LAST_INSERT_ID(), '1000000001', 1000.00, 'ahorros');

-- Cuenta habiente 2
INSERT INTO cuenta_habientes 
    (nombre, apellido, cedula, email, telefono, direccion) 
VALUES 
    ('María', 'López Martínez', '001-220590-1002B', 'maria.lopez@email.com', '8888-5678', 'León, Nicaragua');

INSERT INTO cuentas 
    (id_cuenta_habiente, numero_cuenta, saldo, tipo_cuenta) 
VALUES 
    (LAST_INSERT_ID(), '1000000002', 1000.00, 'corriente');

-- Cuenta habiente 3
INSERT INTO cuenta_habientes 
    (nombre, apellido, cedula, email, telefono, direccion) 
VALUES 
    ('Carlos', 'Rodríguez Hernández', '001-310788-1003C', 'carlos.rodriguez@email.com', '8888-9012', 'Granada, Nicaragua');

INSERT INTO cuentas 
    (id_cuenta_habiente, numero_cuenta, saldo, tipo_cuenta) 
VALUES 
    (LAST_INSERT_ID(), '1000000003', 1000.00, 'ahorros');

-- CONSULTAS DE VERIFICACIÓN

-- Verificar cuenta habientes insertados
SELECT * FROM cuenta_habientes;

-- Verificar cuentas creadas con saldo inicial
SELECT 
    c.numero_cuenta,
    CONCAT(ch.nombre, ' ', ch.apellido) AS titular,
    c.saldo,
    c.tipo_cuenta,
    c.estado,
    c.fecha_apertura
FROM cuentas c
INNER JOIN cuenta_habientes ch ON c.id_cuenta_habiente = ch.id_cuenta_habiente;

-- Consulta completa de cuentas y habientes
SELECT 
    ch.id_cuenta_habiente,
    ch.nombre,
    ch.apellido,
    ch.cedula,
    ch.email,
    ch.telefono,
    c.numero_cuenta,
    c.saldo,
    c.tipo_cuenta,
    c.estado AS estado_cuenta,
    c.fecha_apertura
FROM cuenta_habientes ch
LEFT JOIN cuentas c ON ch.id_cuenta_habiente = c.id_cuenta_habiente
ORDER BY ch.id_cuenta_habiente;