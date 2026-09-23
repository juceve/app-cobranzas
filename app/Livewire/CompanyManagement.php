<?php

namespace App\Livewire;

use App\Models\Cartera;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyManagement extends Component
{
    use WithPagination;

    public ?int $editingCompanyId = null;

    public string $name = '';

    public string $nit = '';

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $contactPerson = null;

    public ?string $contactPhone = null;

    public string $search = '';

    public bool $showCompanyModal = false;

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->ensurePermission('companies.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createCompany(): void
    {
        $this->ensurePermission('companies.create');

        $this->resetForm();
        $this->showCompanyModal = true;
    }

    public function editCompany(int $companyId): void
    {
        $this->ensurePermission('companies.update');

        $company = Company::findOrFail($companyId);

        $this->editingCompanyId = $company->id;
        $this->name = $company->name;
        $this->nit = $company->nit;
        $this->phone = $company->phone;
        $this->address = $company->address;
        $this->contactPerson = $company->contact_person;
        $this->contactPhone = $company->contact_phone;
        $this->showCompanyModal = true;
        $this->resetValidation();
    }

    public function saveCompany(): void
    {
        $this->ensurePermission($this->editingCompanyId === null ? 'companies.create' : 'companies.update');

        $validated = $this->validate();
        $attributes = [
            'name' => $validated['name'],
            'nit' => $validated['nit'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'contact_person' => $validated['contactPerson'],
            'contact_phone' => $validated['contactPhone'],
        ];

        if ($this->editingCompanyId === null) {
            $company = Company::create($attributes);
            if ($company) {
                $cartera = Cartera::create([
                    'company_id' => $company->id,                    
                ]);
            }
            $message = 'La empresa fue creada correctamente.';
        } else {
            Company::findOrFail($this->editingCompanyId)->update($attributes);
            $message = 'La empresa fue actualizada correctamente.';
        }

        $this->resetForm();
        $this->dispatch('company-notification', icon: 'success', message: $message);
    }

    #[On('disable-company')]
    public function disableCompany(int $companyId): void
    {
        $this->ensurePermission('companies.disable');

        Company::findOrFail($companyId)->update(['is_active' => false]);

        $this->dispatch('company-notification', icon: 'success', message: 'La empresa fue deshabilitada correctamente.');
    }

    #[On('enable-company')]
    public function enableCompany(int $companyId): void
    {
        $this->ensurePermission('companies.enable');

        Company::findOrFail($companyId)->update(['is_active' => true]);

        $this->dispatch('company-notification', icon: 'success', message: 'La empresa fue habilitada correctamente.');
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $companies = Company::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'ilike', "%{$this->search}%")
                        ->orWhere('nit', 'ilike', "%{$this->search}%")
                        ->orWhere('contact_person', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.company-management', ['companies' => $companies]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nit' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'nit')->ignore($this->editingCompanyId),
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'contactPerson' => ['nullable', 'string', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingCompanyId',
            'name',
            'nit',
            'phone',
            'address',
            'contactPerson',
            'contactPhone',
            'showCompanyModal',
        ]);
        $this->resetValidation();
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless($this->currentUser()->can($permission), 403);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
