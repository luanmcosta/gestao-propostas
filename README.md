# Gestao de Propostas

API em CodeIgniter 4 com Docker Compose para desenvolvimento local.

Atalho rapido para o README da API: [`api/README.md`](api/README.md)

## Stack local

- API: PHP 8.2 + CodeIgniter 4 (`api`)
- MySQL: `mysql:8.4`
- Redis: `redis:7-alpine`
- Worker opcional para filas (profile `worker`)

## Pre-requisitos

- Docker
- Docker Compose

## Como rodar

1. Suba tudo com bootstrap (instala npm da raiz + npm da API + sobe Docker):

```bash
npm run dev:up
```

Alternativa (sem script):

```bash
npm install
npm --prefix api install
docker compose up --build
```

2. Acesse no navegador:

- API base: http://localhost:8080
- Swagger UI: http://localhost:8080/docs
- OpenAPI JSON: http://localhost:8080/docs/openapi.json

3. Para derrubar:

```bash
docker compose down
```

## Migrations e seed

- `composer install` e `php spark migrate` ja rodam automaticamente no startup do container `api`.
- Seed agora e manual (quando quiser popular dados de exemplo):

```bash
npm run seed:api
```

## Worker (opcional)

Para subir o container de worker junto do Redis/API:

```bash
docker compose --profile worker up --build
```

## Variaveis de ambiente importantes

As variaveis sao passadas pelo `docker-compose.yml` para o container `api`.

### Basicas

- `CI_ENVIRONMENT=development`
- `app_baseURL=http://localhost:8080/`
- `database_default_DBDriver=MySQLi`
- `database_default_hostname=mysql`
- `database_default_port=3306`
- `database_default_database=gestao_propostas`
- `database_default_username=gestao`
- `database_default_password=gestao`
- `cache_redis_host=redis`
- `cache_redis_port=6379`
- `REDIS_HOST=redis` (opcional para codigo custom)
- `REDIS_PORT=6379` (opcional para codigo custom)

### CodeIgniter (parametros comuns)

As chaves abaixo seguem o formato aceito pelo `api/env`:

- `app_baseURL=...` (ou `app.baseURL=...`)
- `database_default_hostname=...` (ou `database.default.hostname=...`)
- `database_default_database=...` (ou `database.default.database=...`)
- `database_default_username=...` (ou `database.default.username=...`)
- `database_default_password=...` (ou `database.default.password=...`)
- `database_default_DBDriver=MySQLi` (ou `database.default.DBDriver=MySQLi`)
- `database_default_port=3306` (ou `database.default.port=3306`)

## Exemplos de consulta (curl)

### 1) Criar cliente

```bash
curl -X POST http://localhost:8080/api/v1/clientes \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Maria Souza",
    "email": "maria@email.com",
    "documento": "12345678901"
  }'
```

### 2) Criar proposta (com idempotencia)

```bash
curl -X POST http://localhost:8080/api/v1/propostas \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: proposta-001" \
  -d '{
    "cliente_id": 1,
    "produto": "Plano Premium",
    "valor_mensal": 199.90,
    "origem": "SITE"
  }'
```

### 3) Listar propostas

```bash
curl "http://localhost:8080/api/v1/propostas?page=1&per_page=10&sort=created_at&direction=desc"
```

### 4) Buscar proposta por id

```bash
curl http://localhost:8080/api/v1/propostas/1
```
