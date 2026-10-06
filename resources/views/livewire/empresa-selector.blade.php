<div class="d-flex align-items-center gap-2">
    <label for="empresa-actual" class="small text-body-secondary mb-0 d-none d-md-inline">
        
        Empresa
    </label>
    <select id="empresa-actual" class="form-select form-select-sm" style="min-width: 12rem;"
        wire:model.live="empresaId" aria-label="Empresa activa">
        <option value="">Todas las empresas</option>
        @foreach ($companies as $company)
        <option value="{{ $company->id }}">{{ $company->name }}</option>
        @endforeach
    </select>
</div>
