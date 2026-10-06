<?php

namespace App\Support;

use App\Models\Company;

class EmpresaActual
{
    private const SESSION_KEY = 'empresa_actual_id';

    public static function id(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id === null ? null : (int) $id;
    }

    public static function set(?int $companyId): void
    {
        if ($companyId === null) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        $existe = Company::query()->whereKey($companyId)->where('is_active', true)->exists();

        if ($existe) {
            session([self::SESSION_KEY => $companyId]);
        } else {
            session()->forget(self::SESSION_KEY);
        }
    }
}
