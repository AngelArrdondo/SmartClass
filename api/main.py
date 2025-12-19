from fastapi import FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import mysql.connector
import bcrypt
import logging

# Configuración de logs
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="SmartClass API")

# --- CORS ---
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- MODELOS ---
class LoginRequest(BaseModel):
    email: str
    password: str

# --- CONFIGURACIÓN BD ---
db_config = {
    'host': 'localhost',
    'port': 3307,
    'user': 'root',
    'password': '', 
    'database': 'SmartClass'
}

def get_db_connection():
    try:
        conn = mysql.connector.connect(**db_config)
        return conn
    except mysql.connector.Error as err:
        logger.error(f"Error conectando a BD: {err}")
        return None

# --- ENDPOINT 1: LOGIN MULTI-ROL ---
@app.post("/api/login")
def login(login_data: LoginRequest):
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Error de conexión a la BD")

    cursor = conn.cursor(dictionary=True)

    try:
        # 1. Buscar usuario activo
        query = """
            SELECT u.id, u.password_hash, u.nombre, u.apellido_paterno, r.nombre as rol 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.email = %s AND u.is_active = 1
        """
        cursor.execute(query, (login_data.email,))
        user = cursor.fetchone()

        if not user:
            raise HTTPException(status_code=401, detail="Usuario no encontrado")

        # 2. Verificar contraseña
        hashed_password_db = user['password_hash'].encode('utf-8')
        input_password_bytes = login_data.password.encode('utf-8')

        if not bcrypt.checkpw(input_password_bytes, hashed_password_db):
            raise HTTPException(status_code=401, detail="Contraseña incorrecta")

        # 3. Lógica de Roles (Profesor vs Alumno)
        response_data = {
            "success": True,
            "message": f"Bienvenido {user['nombre']}",
            "user_id": user['id'],
            "nombre": f"{user['nombre']} {user['apellido_paterno']}",
            "rol": user['rol'],  # Importante: devolver el rol
            "perfil_id": None    # ID específico (alumno_id o profesor_id)
        }

        if user['rol'] in ['profesor', 'tutor']:
            # Buscar en tabla PROFESORES
            cursor.execute("SELECT id FROM profesores WHERE user_id = %s", (user['id'],))
            perfil = cursor.fetchone()
            if not perfil:
                 raise HTTPException(status_code=403, detail="Cuenta de profesor sin perfil asignado.")
            response_data['perfil_id'] = perfil['id']

        elif user['rol'] == 'alumno':
            # Buscar en tabla ALUMNOS
            cursor.execute("SELECT id, grupo_id FROM alumnos WHERE user_id = %s", (user['id'],))
            perfil = cursor.fetchone()
            if not perfil:
                 raise HTTPException(status_code=403, detail="Cuenta de alumno sin perfil asignado.")
            response_data['perfil_id'] = perfil['id']
            # Opcional: Podríamos devolver el grupo_id si lo necesitamos
            
        else:
            # Si es admin u otro rol no soportado en la app
            raise HTTPException(status_code=403, detail="Tu rol no tiene acceso a la App Móvil.")

        return response_data

    except HTTPException as he:
        raise he
    except Exception as e:
        logger.error(f"Error en login: {e}")
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        cursor.close()
        conn.close()

# --- ENDPOINT 2: HORARIO INTELIGENTE ---
@app.get("/api/horario")
def get_horario(
    id: int = Query(..., description="ID del Perfil (ProfesorID o AlumnoID)"),
    rol: str = Query(..., description="Rol del usuario ('profesor' o 'alumno')")
):
    conn = get_db_connection()
    if not conn:
        raise HTTPException(status_code=500, detail="Error de BD")

    cursor = conn.cursor(dictionary=True)

    try:
        # Base de la consulta
        # FIX: Se cambia JOIN a LEFT JOIN para profesores y p_user
        base_query = """
            SELECT 
                h.id as horario_id,
                h.dia_semana,
                TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio,
                TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin,
                m.nombre as materia,
                g.codigo as grupo,
                s.codigo as salon,
                s.ubicacion,
                p_user.nombre as nombre_profesor, 
                p_user.apellido_paterno as apellido_profesor
            FROM horarios h
            JOIN materias m ON h.materia_id = m.id
            JOIN grupos g ON h.grupo_id = g.id
            JOIN salones s ON h.salon_id = s.id
            JOIN ciclos_escolares c ON h.ciclo_id = c.id
            LEFT JOIN profesores p ON h.profesor_id = p.id 
            LEFT JOIN users p_user ON p.user_id = p_user.id
            WHERE c.activo = 1 
        """

        if rol in ['profesor', 'tutor']:
            # Lógica Profesor: Buscar clases donde ÉL enseña
            sql = base_query + " AND h.profesor_id = %s ORDER BY FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'), h.hora_inicio"
            cursor.execute(sql, (id,))

        elif rol == 'alumno':
            # Lógica Alumno: 
            # 1. Obtener el grupo del alumno
            cursor.execute("SELECT grupo_id FROM alumnos WHERE id = %s", (id,))
            alumno_data = cursor.fetchone()
            
            if not alumno_data or not alumno_data['grupo_id']:
                return {"success": True, "data": [], "message": "No tienes grupo asignado"}

            grupo_id = alumno_data['grupo_id']

            # 2. Buscar horarios de ese grupo
            sql = base_query + " AND h.grupo_id = %s ORDER BY FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'), h.hora_inicio"
            cursor.execute(sql, (grupo_id,))
        
        else:
            raise HTTPException(status_code=400, detail="Rol no válido")

        horario = cursor.fetchall()
        return {"success": True, "data": horario}

    except Exception as e:
        logger.error(f"Error horario: {e}")
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    import uvicorn
    # Recargar servidor automáticamente al guardar cambios
    uvicorn.run("app:app", host="0.0.0.0", port=5000, reload=True)