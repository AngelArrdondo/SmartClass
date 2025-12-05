/* Token $secret_token = "UTEQ_ADMIN_2025" para crear un admin y de ahi crear los usuarios*/ 

-- ==========================================================
-- CREACIÓN DE LA BASE DE DATOS
-- ==========================================================
CREATE DATABASE IF NOT EXISTS SmartClass; -- Crea la BD si no existe
USE SmartClass; -- Selecciona la BD para empezar a crear tablas

-- ==========================================================
-- 1. TABLAS CATÁLOGOS Y USUARIOS BASE
-- ==========================================================

-- Tabla de Roles: Define quién es quién (Admin, Profesor, Alumno)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Identificador único del rol
    nombre VARCHAR(50) NOT NULL UNIQUE, -- Nombre del rol (ej. 'admin', 'profesor')
    descripcion VARCHAR(255) -- Breve descripción de permisos
);

-- Tabla Central de Usuarios: Todos inician sesión aquí
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID único global del usuario
    email VARCHAR(100) NOT NULL UNIQUE, -- Correo para login (único)
    password_hash VARCHAR(255) NOT NULL, -- Contraseña encriptada (nunca texto plano)
    nombre VARCHAR(100) NOT NULL, -- Nombre(s) del usuario
    apellido_paterno VARCHAR(100) NOT NULL, -- Primer apellido
    apellido_materno VARCHAR(100), -- Segundo apellido (opcional)
    telefono VARCHAR(20), -- Teléfono de contacto
    role_id INT NOT NULL, -- Relación con la tabla roles
    is_active BOOLEAN DEFAULT TRUE, -- 1=Activo, 0=Bloqueado
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de registro automático
    FOREIGN KEY (role_id) REFERENCES roles(id) -- Integridad referencial con roles
);

-- Tabla de Ciclos Escolares (NUEVO): Manejo de semestres/periodos
CREATE TABLE ciclos_escolares (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID del ciclo
    nombre VARCHAR(100) NOT NULL, -- Ej: "Enero-Junio 2025"
    fecha_inicio DATE NOT NULL, -- Cuándo arranca el semestre
    fecha_fin DATE NOT NULL, -- Cuándo termina
    activo BOOLEAN DEFAULT FALSE -- Solo un ciclo debería estar activo a la vez
);

-- Tabla de Salones: Espacios físicos
CREATE TABLE salones (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID del salón
    codigo VARCHAR(20) NOT NULL UNIQUE, -- Código visible (ej. A-101)
    ubicacion VARCHAR(100), -- Descripción de dónde está
    capacidad INT DEFAULT 30 -- Cuántos alumnos caben
);

-- Tabla de Materias: Catálogo de clases disponibles
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID de la materia
    codigo VARCHAR(20) NOT NULL UNIQUE, -- Clave interna (ej. MAT-101)
    nombre VARCHAR(100) NOT NULL, -- Nombre oficial (ej. Matemáticas I)
    creditos INT DEFAULT 0 -- Valor académico (opcional)
);

-- Tabla de Grupos: Conjuntos de alumnos (ej. 2º A)
CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID del grupo
    codigo VARCHAR(20) NOT NULL UNIQUE, -- Nombre corto (ej. 2A-2025)
    ciclo_id INT NOT NULL, -- Pertenece a un ciclo escolar específico
    grado INT NOT NULL, -- Semestre o año (ej. 2)
    turno ENUM('Matutino', 'Vespertino') NOT NULL, -- Turno de clases
    FOREIGN KEY (ciclo_id) REFERENCES ciclos_escolares(id) -- Enlace al ciclo
);

-- ==========================================================
-- 2. PERFILES ESPECÍFICOS (EXTENSIÓN DE USERS)
-- ==========================================================

-- Tabla Profesores: Datos extra para los docentes
CREATE TABLE profesores (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID tabla profesor
    user_id INT NOT NULL UNIQUE, -- Relación 1 a 1 con tabla users
    codigo_empleado VARCHAR(50) UNIQUE, -- Matrícula de empleado administrativo
    especialidad VARCHAR(100), -- Área de conocimiento
    FOREIGN KEY (user_id) REFERENCES users(id) -- Enlace fuerte a users
);

-- Tabla Alumnos: Datos extra para estudiantes
CREATE TABLE alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID tabla alumno
    user_id INT NOT NULL UNIQUE, -- Relación 1 a 1 con tabla users
    matricula VARCHAR(50) NOT NULL UNIQUE, -- Matrícula escolar oficial
    grupo_id INT, -- Grupo actual al que pertenece (puede ser NULL si no está inscrito)
    FOREIGN KEY (user_id) REFERENCES users(id), -- Enlace a users
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) -- Enlace al grupo actual
);

-- ==========================================================
-- 3. OPERACIÓN Y ASIGNACIONES
-- ==========================================================

-- Tabla Tutores (NUEVO): Asigna un profesor a un grupo completo
CREATE TABLE tutor_asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID de la asignación
    profesor_id INT NOT NULL, -- Qué profesor es el tutor
    grupo_id INT NOT NULL, -- Qué grupo va a vigilar
    FOREIGN KEY (profesor_id) REFERENCES profesores(id), -- Enlace a profesores
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) -- Enlace a grupos
);

-- Tabla Horarios: La agenda semanal (Estricta Lunes-Viernes)
CREATE TABLE horarios (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID del horario
    ciclo_id INT NOT NULL, -- Valido para este ciclo escolar
    grupo_id INT NOT NULL, -- Grupo que toma la clase
    materia_id INT NOT NULL, -- Materia que se imparte
    profesor_id INT NOT NULL, -- Profesor asignado
    salon_id INT NOT NULL, -- Aula asignada
    dia_semana ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes') NOT NULL, -- Restricción 1-5
    hora_inicio TIME NOT NULL, -- Hora entrada
    hora_fin TIME NOT NULL, -- Hora salida
    FOREIGN KEY (ciclo_id) REFERENCES ciclos_escolares(id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id),
    FOREIGN KEY (materia_id) REFERENCES materias(id),
    FOREIGN KEY (profesor_id) REFERENCES profesores(id),
    FOREIGN KEY (salon_id) REFERENCES salones(id)
);

-- Tabla Asistencias (FLEXIBLE): Soporta clases sin horario fijo
CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID del registro
    alumno_id INT NOT NULL, -- Alumno evaluado
    fecha DATE NOT NULL, -- Fecha real de la asistencia
    estado ENUM('Presente', 'Ausente', 'Retardo', 'Justificado') NOT NULL, -- Estados definidos
    
    -- Campos para flexibilidad (clases fuera de horario):
    horario_id INT NULL, -- Puede ser NULL si es clase extra/recuperación
    materia_id INT NOT NULL, -- Obligatorio para saber de qué clase fue (aunque horario sea null)
    grupo_id INT NOT NULL, -- Obligatorio para reporte grupal
    
    observaciones TEXT, -- Notas opcionales del profe
    registrado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Hora exacta del registro
    
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id),
    FOREIGN KEY (horario_id) REFERENCES horarios(id),
    FOREIGN KEY (materia_id) REFERENCES materias(id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id)
);

-- Tabla Notificaciones: Mensajes del sistema
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID
    titulo VARCHAR(100) NOT NULL, -- Asunto
    mensaje TEXT NOT NULL, -- Cuerpo del mensaje
    tipo ENUM('Aviso', 'CambioAula', 'Cancelacion', 'Urgente') DEFAULT 'Aviso', -- Categoría
    grupo_destina_id INT NULL, -- Si es NULL, es personal. Si tiene ID, es para todo el grupo
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación
    FOREIGN KEY (grupo_destina_id) REFERENCES grupos(id)
);

INSERT INTO roles (id, nombre, descripcion) VALUES
    (1, 'admin', 'Administrador del sistema'),
    (2, 'profesor', 'Docente'),
    (3, 'alumno', 'Estudiante');

ALTER TABLE materias ADD COLUMN descripcion TEXT AFTER creditos;

ALTER TABLE salones 
ADD COLUMN nombre VARCHAR(100) AFTER codigo,
ADD COLUMN recursos TEXT AFTER ubicacion,
ADD COLUMN observaciones TEXT AFTER recursos;