# proplayasAPI
API REST for ProPlayas Org website

## Docker

$ docker-compose down \
	: para detener los contenedores

$ docker-compose build app \
	: para contruir la imagen personalizada con composer preinstalado

$ docker-compose up -d \
	: para crear o iniciar los contenedores (configuracion en el .yml)

$ docker ps \
	: para ver los contenedores activos


! en caso de que tengan la advertencia WARN[0000] Found orphan containers ([proplayasapi-database-1]) for this project. \
$ docker-compose up -d --remove-orphans

! en caso de que tengan el error "Error response from daemon: driver failed programming external connectivity on endpoint... Error starting userland proxy: listen tcp4 0.0.0.0:8080: bind: address already in use" \
$ sudo lsof -i :8080 \
	: para ver el servicio o proceso que ocupa dicho puerto \
$ sudo kill -9 <PID> \
	: para matarlo/detenerlo, o si conocen el nombre del servicio yo recomiendo, por ejemplo... \
$ sudo systemctl stop jenkins.service



## PHP y Laravel

$ docker exec -it proplayas_php bash \
	: para entrar al contenedor


## Instrucciones para instalar en otra máquina

1️⃣ Clonar el repositorio
```bash
$ git clone THIS_REPO_GITHUB.git
$ cd proplayasAPI
```

2️⃣ Levantar el entorno con Docker
```bash
$ docker-compose up -d
``` 

3️⃣ Acceder al contenedor PHP
```bash
$ docker exec -it proplayas_php bash
``` 

4️⃣ Instalar las dependencias de Laravel
```bash
$ composer install
``` 

5️⃣ Copiar el archivo de configuración .env
```bash
$ cp .env.example .env
```

6️⃣ Generar la clave de Laravel
```bash
$ php artisan key:generate
``` 

7️⃣ Ejecutar las migraciones y poblar la base de datos
```bash
$ php artisan migrate --seed
```


8️⃣ Limpiar cachés y reiniciar Laravel (opcional, si ven errores)
```bash
$ php artisan cache:clear
$ php artisan config:clear
$ php artisan route:clear
$ docker-compose restart app
``` \

📌 Después de esto, podrán probar la API en http://localhost:8080/api/test y hacer login. 🎯



## Probar API

curl -X POST "http://localhost:8080/api/register" \
     -H "Content-Type: application/json" \
     -d '{"name":"Ximena", "email":"xime@example.com", "password":"12345678"}'

curl -X POST "http://localhost:8080/api/login" \
     -H "Content-Type: application/json" \
     -d '{"email":"xime@example.com", "password":"12345678"}'

curl -X GET "http://localhost:8080/api/admin-dashboard" \
     -H "Authorization: Bearer TU_TOKEN_AQUI"

