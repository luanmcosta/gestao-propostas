<?php

namespace App\Services;

class PropostaService
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELED = 'CANCELED';

    public const ORIGEM_APP = 'APP';
    public const ORIGEM_SITE = 'SITE';
    public const ORIGEM_API = 'API';

    public const EVENT_CREATED = 'CREATED';
    public const EVENT_UPDATED_FIELDS = 'UPDATED_FIELDS';
    public const EVENT_STATUS_CHANGED = 'STATUS_CHANGED';
    public const EVENT_DELETED_LOGICAL = 'DELETED_LOGICAL';

    public function canTransition(string $from, string $to): bool
    {
        $map = $this->transitionMap();
        if (! array_key_exists($from, $map)) {
            return false;
        }

        return in_array($to, $map[$from], true);
    }

    public function isFinal(string $status): bool
    {
        return in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELED], true);
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELED,
        ], true);
    }

    public function isValidOrigem(string $origem): bool
    {
        return in_array($origem, [self::ORIGEM_APP, self::ORIGEM_SITE, self::ORIGEM_API], true);
    }

    private function transitionMap(): array
    {
        return [
            self::STATUS_DRAFT => [self::STATUS_SUBMITTED, self::STATUS_CANCELED],
            self::STATUS_SUBMITTED => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELED],
            self::STATUS_APPROVED => [],
            self::STATUS_REJECTED => [],
            self::STATUS_CANCELED => [],
        ];
    }
}
