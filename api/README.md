# Gestao de Propostas API

API REST versionada em `/api/v1` para gestao de propostas. Este projeto roda localmente com SQLite para evitar dependencia externa.

## Requisitos

- PHP 8.2+
- Composer

## Setup rapido

1. `composer install`
2. `php spark migrate`
3. `php spark db:seed DatabaseSeeder`
4. `php spark serve`

O SQLite fica em `writable/database.sqlite` (criado automaticamente no primeiro acesso/migrate).

## Padrao de erros

As respostas de erro seguem o padrao do CodeIgniter ResponseTrait:

- `status`
- `error`
- `messages`

## Idempotencia

Obrigatoria nos endpoints:

- `POST /api/v1/propostas`
- `POST /api/v1/propostas/{id}/submit`

Enviar o header `Idempotency-Key`. A mesma chave e payload devolvem a resposta original. Chave reutilizada com payload diferente retorna `409`.

## Concorrencia otimista

As atualizacoes usam `versao`:

- `PATCH /api/v1/propostas/{id}`
- `POST /api/v1/propostas/{id}/submit`
- `POST /api/v1/propostas/{id}/approve`
- `POST /api/v1/propostas/{id}/reject`
- `POST /api/v1/propostas/{id}/cancel`
- `DELETE /api/v1/propostas/{id}`

Se a versao enviada divergir da atual, retorna `409`.

## Endpoints

- `POST /api/v1/clientes`
- `GET /api/v1/clientes/{id}`
- `POST /api/v1/propostas`
- `PATCH /api/v1/propostas/{id}`
- `POST /api/v1/propostas/{id}/submit`
- `POST /api/v1/propostas/{id}/approve`
- `POST /api/v1/propostas/{id}/reject`
- `POST /api/v1/propostas/{id}/cancel`
- `GET /api/v1/propostas/{id}`
- `GET /api/v1/propostas`
- `GET /api/v1/propostas/{id}/auditoria`
- `DELETE /api/v1/propostas/{id}`

## Busca avancada

`GET /api/v1/propostas` aceita filtros e paginacao:

- `status`, `origem`, `cliente_id`, `produto`
- `min_valor`, `max_valor`
- `created_from`, `created_to` (Y-m-d)
- `sort` (created_at, valor_mensal, status, versao)
- `direction` (asc, desc)
- `page`, `per_page`

## Testes

`vendor/bin/phpunit`

Os testes cobrem transicoes de status, idempotencia, conflito de versao e busca com paginacao.

## Swagger/OpenAPI

O Swagger UI fica em `/docs` e usa o arquivo `app/Docs/openapi.json`.

Para (re)gerar a especificacao automaticamente com `swagger-php`:

`php spark openapi:generate`
