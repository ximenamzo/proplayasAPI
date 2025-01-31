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

git clone <THIS_REPO_URL> \
cd proplayasAPI \
docker-compose build app  \
docker-compose up -d  # Levantar la API \
docker exec -it proplayas_php bash  # Acceder al contenedor \
php artisan migrate  # Ejecutar migraciones



## Probar API

curl -X POST "http://localhost:8080/api/register" \
     -H "Content-Type: application/json" \
     -d '{"name":"Ximena", "email":"xime@example.com", "password":"12345678"}'

curl -X POST "http://localhost:8080/api/login" \
     -H "Content-Type: application/json" \
     -d '{"email":"xime@example.com", "password":"12345678"}'

curl -X GET "http://localhost:8080/api/admin-dashboard" \
     -H "Authorization: Bearer TU_TOKEN_AQUI"

