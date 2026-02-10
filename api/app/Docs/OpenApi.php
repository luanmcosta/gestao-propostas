<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="Gestao de Propostas API",
 *     version="1.0.0",
 *     description="API REST para gestao de propostas."
 *   ),
 *   @OA\Server(url="http://localhost:8080")
 * )
 *
 * @OA\Tag(name="Clientes")
 * @OA\Tag(name="Propostas")
 * @OA\Tag(name="Auditoria")
 *
 * @OA\Schema(
 *   schema="ClienteInput",
 *   required={"nome","email","documento"},
 *   @OA\Property(property="nome", type="string"),
 *   @OA\Property(property="email", type="string", format="email"),
 *   @OA\Property(property="documento", type="string")
 * )
 *
 * @OA\Schema(
 *   schema="Cliente",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="nome", type="string"),
 *   @OA\Property(property="email", type="string"),
 *   @OA\Property(property="documento", type="string"),
 *   @OA\Property(property="created_at", type="string", nullable=true),
 *   @OA\Property(property="updated_at", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *   schema="PropostaInput",
 *   required={"cliente_id","produto","valor_mensal","origem"},
 *   @OA\Property(property="cliente_id", type="integer"),
 *   @OA\Property(property="produto", type="string"),
 *   @OA\Property(property="valor_mensal", type="number", format="float"),
 *   @OA\Property(property="origem", type="string", enum={"APP","SITE","API"})
 * )
 *
 * @OA\Schema(
 *   schema="PropostaUpdate",
 *   required={"versao"},
 *   @OA\Property(property="versao", type="integer"),
 *   @OA\Property(property="produto", type="string"),
 *   @OA\Property(property="valor_mensal", type="number", format="float"),
 *   @OA\Property(property="origem", type="string", enum={"APP","SITE","API"})
 * )
 *
 * @OA\Schema(
 *   schema="PropostaDelete",
 *   required={"versao"},
 *   @OA\Property(property="versao", type="integer")
 * )
 *
 * @OA\Schema(
 *   schema="PropostaVersion",
 *   required={"versao"},
 *   @OA\Property(property="versao", type="integer")
 * )
 *
 * @OA\Schema(
 *   schema="Proposta",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="cliente_id", type="integer"),
 *   @OA\Property(property="produto", type="string"),
 *   @OA\Property(property="valor_mensal", type="number", format="float"),
 *   @OA\Property(property="status", type="string", enum={"DRAFT","SUBMITTED","APPROVED","REJECTED","CANCELED"}),
 *   @OA\Property(property="origem", type="string", enum={"APP","SITE","API"}),
 *   @OA\Property(property="versao", type="integer"),
 *   @OA\Property(property="created_at", type="string", nullable=true),
 *   @OA\Property(property="updated_at", type="string", nullable=true),
 *   @OA\Property(property="deleted_at", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *   schema="PropostaList",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Proposta")),
 *   @OA\Property(
 *     property="meta",
 *     type="object",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="per_page", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="page_count", type="integer")
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="Auditoria",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="proposta_id", type="integer"),
 *   @OA\Property(property="actor", type="string"),
 *   @OA\Property(property="evento", type="string"),
 *   @OA\Property(property="payload", type="string", nullable=true),
 *   @OA\Property(property="created_at", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *   schema="AuditoriaList",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Auditoria")),
 *   @OA\Property(
 *     property="meta",
 *     type="object",
 *     @OA\Property(property="page", type="integer"),
 *     @OA\Property(property="per_page", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="page_count", type="integer")
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="ErrorResponse",
 *   @OA\Property(property="status", type="integer"),
 *   @OA\Property(property="error", type="integer"),
 *   @OA\Property(property="messages", type="object")
 * )
 *
 * @OA\Parameter(parameter="id", name="id", in="path", required=true, @OA\Schema(type="integer"))
 * @OA\Parameter(parameter="IdempotencyKey", name="Idempotency-Key", in="header", required=true, @OA\Schema(type="string"))
 * @OA\Parameter(parameter="status", name="status", in="query", @OA\Schema(type="string"))
 * @OA\Parameter(parameter="origem", name="origem", in="query", @OA\Schema(type="string"))
 * @OA\Parameter(parameter="cliente_id", name="cliente_id", in="query", @OA\Schema(type="integer"))
 * @OA\Parameter(parameter="produto", name="produto", in="query", @OA\Schema(type="string"))
 * @OA\Parameter(parameter="min_valor", name="min_valor", in="query", @OA\Schema(type="number", format="float"))
 * @OA\Parameter(parameter="max_valor", name="max_valor", in="query", @OA\Schema(type="number", format="float"))
 * @OA\Parameter(parameter="created_from", name="created_from", in="query", @OA\Schema(type="string", format="date"))
 * @OA\Parameter(parameter="created_to", name="created_to", in="query", @OA\Schema(type="string", format="date"))
 * @OA\Parameter(parameter="sort", name="sort", in="query", @OA\Schema(type="string"))
 * @OA\Parameter(parameter="direction", name="direction", in="query", @OA\Schema(type="string"))
 * @OA\Parameter(parameter="page", name="page", in="query", @OA\Schema(type="integer"))
 * @OA\Parameter(parameter="per_page", name="per_page", in="query", @OA\Schema(type="integer"))
 */
final class OpenApi
{
}
