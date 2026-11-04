<?php


namespace App\Support;

use Illuminate\Support\Facades\Auth;

class TenantContext
{
    protected ?int $tenantId = null;

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function id(): ?int
    {
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        if (Auth::hasUser()) {
            return Auth::user()->tenant_id;
        }

        return session('tenant_id');
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }
}

