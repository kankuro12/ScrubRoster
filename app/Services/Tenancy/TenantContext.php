<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function slug(): ?string
    {
        return $this->tenant?->slug;
    }

    public function prefix(): ?string
    {
        return $this->slug();
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function check(): bool
    {
        return $this->hasTenant();
    }
}
