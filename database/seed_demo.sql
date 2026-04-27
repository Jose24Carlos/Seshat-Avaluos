-- Opcional: valuador de prueba (contraseña literal: password)
-- Ejecutar una vez después de schema.sql. Cambiar credenciales en producción.

INSERT INTO usuarios (email, password_hash, nombre, telefono, rol) VALUES
('valuador@ejemplo.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Valuador Demo', NULL, 'valuador');
