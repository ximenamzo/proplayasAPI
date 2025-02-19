# **📌 README.md**
## **🌊 ProPlayas API**
API REST para la plataforma de ProPlayas.

---

## **🚀 Requisitos previos**
Asegúrate de tener instalado en tu sistema:
- **Docker** 🐳
- **Docker Compose** ⚙️

---

## **🛠 Instalación en otra máquina**
### **1️⃣ Clonar el repositorio**
```bash
git clone THIS_REPO_GITHUB.git
cd proplayasAPI
```

### **2️⃣ Levantar los contenedores con Docker**
```bash
docker-compose up -d --build
```
✅ Esto **crea y levanta** los contenedores de PHP, Nginx y MySQL.

### **3️⃣ Acceder al contenedor PHP**
```bash
docker exec -it proplayas_php bash
```
📌 **Desde aquí se ejecutarán todos los comandos de Laravel.**

### **4️⃣ Instalar las dependencias de Laravel**
```bash
composer install
```

### **5️⃣ Configurar las variables de entorno**
```bash
cp .env.example .env
```
Luego, edita el archivo **.env** si es necesario.

### **6️⃣ Generar la clave de Laravel**
```bash
php artisan key:generate
```

### **7️⃣ Ejecutar migraciones y poblar la base de datos**
```bash
php artisan migrate --seed
```
📌 Esto **creará las tablas** y **agregará los roles básicos** (`admin`, `node_leader`, `member`).

### **8️⃣ Limpiar cachés y reiniciar Laravel (opcional)**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
docker-compose restart app
```
✅ Esto **evita errores de caché** y asegura que los cambios se reflejen.

### **9️⃣ Probar la API**
📌 **Para verificar que Laravel está funcionando correctamente:**
```bash
curl -X GET "http://localhost:8080/api/test"
```
✅ Debe devolver: `{"message": "API is working!"}`

---

## **🐳 Comandos útiles de Docker**
📌 **Detener los contenedores:**
```bash
docker-compose down
```

📌 **Reconstruir la imagen de PHP con composer preinstalado:**
```bash
docker-compose build app
```

📌 **Ver los contenedores activos:**
```bash
docker ps
```

📌 **Si hay advertencia de contenedores huérfanos:**
```bash
docker-compose up -d --remove-orphans
```

📌 **Si el puerto 8080 está en uso:**
```bash
sudo lsof -i :8080
sudo kill -9 <PID>
```
(O detener el servicio en conflicto, por ejemplo: `sudo systemctl stop jenkins.service`)

---

## **🔑 Autenticación y Pruebas de API**
📌 **Crear un usuario:**
```bash
curl -X POST "http://localhost:8080/api/register" \
     -H "Content-Type: application/json" \
     -d '{"name":"Ximena", "email":"xime@example.com", "password":"12345678", "role":"admin"}'
```

📌 **Iniciar sesión y obtener token:**
```bash
curl -X POST "http://localhost:8080/api/login" \
     -H "Content-Type: application/json" \
     -d '{"email":"xime@example.com", "password":"12345678"}'
```
✅ **Guarda el token** que devuelve esta petición.

📌 **Acceder al Dashboard del Admin (requiere token):**
```bash
curl -X GET "http://localhost:8080/api/admin-dashboard" \
     -H "Authorization: Bearer TU_TOKEN_AQUI"
```

📌 **Cerrar sesión (requiere token):**
```bash
curl -X POST "http://localhost:8080/api/logout" \
     -H "Authorization: Bearer TU_TOKEN_AQUI"
```

---
