<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Cobrador\MisLotes;
use App\Livewire\Cobrador\Ruta;
use App\Models\Cartera;
use App\Models\Cobro;
use App\Models\Company;
use App\Models\Deudacartera;
use App\Models\Gestion;
use App\Models\Lote;
use App\Models\Recibo;
use App\Models\User;
use App\Services\ReciboService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CobradorRutaTest extends TestCase
{
    use RefreshDatabase;

    private Lote $lote;

    private User $cobrador;

    private Deudacartera $deudaA1;

    private Deudacartera $deudaA2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $cartera = Cartera::create(['company_id' => Company::factory()->create()->id]);
        $this->cobrador = $this->usuario('Cobrador', 'cobrador@test.com');

        $this->lote = Lote::create([
            'cartera_id' => $cartera->id,
            'company_id' => $cartera->company_id,
            'user_id' => $this->cobrador->id,
            'created_by' => $this->usuario('JefeVentas', 'jefe@test.com')->id,
            'codigo' => 'LOTE-TEST-0001',
            'nombre' => 'Lote móvil',
            'estado' => 'asignado',
            'fecha_asignacion' => today(),
            'total_deudores' => 2,
            'total_deudas' => 3,
        ]);

        $this->deudaA1 = $this->agregar($cartera, '100', 'A-1', 500, 1);
        $this->deudaA2 = $this->agregar($cartera, '100', 'A-2', 300, 1);
        $this->agregar($cartera, '200', 'B-1', 100, 2);
    }

    public function test_mis_lotes_solo_muestra_los_lotes_del_cobrador(): void
    {
        $this->actingAs($this->usuario('Cobrador', 'otro@test.com'));

        Livewire::test(MisLotes::class)->assertDontSee('Lote móvil');

        $this->actingAs($this->cobrador);

        Livewire::test(MisLotes::class)->assertSee('Lote móvil');
    }

    public function test_un_cobrador_no_puede_abrir_el_lote_de_otro(): void
    {
        $this->actingAs($this->usuario('Cobrador', 'otro@test.com'));

        Livewire::test(Ruta::class, ['lote' => $this->lote])->assertForbidden();
    }

    public function test_un_lote_cerrado_no_se_puede_gestionar(): void
    {
        $this->lote->update(['estado' => 'cerrado']);
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])->assertForbidden();
    }

    public function test_el_inicio_del_cobrador_redirige_a_sus_lotes(): void
    {
        $this->actingAs($this->cobrador)
            ->get(route('home'))
            ->assertRedirect(route('cobrador.lotes'));
    }

    public function test_la_ruta_lista_los_deudores_en_orden_y_filtra_pendientes(): void
    {
        $this->actingAs($this->cobrador);

        $componente = Livewire::test(Ruta::class, ['lote' => $this->lote]);

        $this->assertSame(['100', '200'], $componente->get('lista')->pluck('codigo')->all());

        $this->lote->deudas()->updateExistingPivot($this->deudaA1->id, ['estado_gestion' => 'visitado']);
        $componente->set('vista', 'pendientes');

        $this->assertSame(['200'], Livewire::test(Ruta::class, ['lote' => $this->lote])->get('lista')->pluck('codigo')->all());
    }

    public function test_guardar_gestion_registra_y_actualiza_el_estado_de_todas_las_deudas(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100')
            ->set('tipo', 'llamada')
            ->set('resultado', 'no_contesta')
            ->call('guardarGestion')
            ->assertHasNoErrors()
            ->assertSet('codigoDeudor', null);

        $this->assertDatabaseHas('gestiones', ['lote_id' => $this->lote->id, 'codigo_cliente' => '100', 'resultado' => 'no_contesta', 'tipo' => 'llamada']);
        $this->assertSame(2, $this->lote->deudas()->wherePivot('estado_gestion', 'no_contactado')->count());
    }

    public function test_la_promesa_de_pago_exige_fecha_y_monto(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100')
            ->set('resultado', 'promesa_pago')
            ->call('guardarGestion')
            ->assertHasErrors(['fechaPromesa', 'montoPrometido']);

        $this->assertDatabaseCount('gestiones', 0);
    }

    public function test_registrar_cobro_parcial_y_total(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->set("montos.{$this->deudaA1->id}", '200')
            ->set("cobrar.{$this->deudaA2->id}", true)
            ->set("montos.{$this->deudaA2->id}", '300')
            ->set('formaPago', 'transferencia')
            ->set('referencia', 'TRX-123')
            ->call('revisarCobro')
            ->assertSet('confirmandoCobro', true)
            ->call('registrarCobro')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cobros', 2);
        $this->assertEquals(500, (float) Cobro::sum('monto'));
        $this->assertDatabaseHas('cobros', ['deudacartera_id' => $this->deudaA1->id, 'estado' => 'registrado', 'forma_pago' => 'transferencia', 'user_id' => $this->cobrador->id]);

        $parcial = $this->lote->deudas()->where('deudacarteras.id', $this->deudaA1->id)->first()->pivot;
        $total = $this->lote->deudas()->where('deudacarteras.id', $this->deudaA2->id)->first()->pivot;
        $this->assertSame('visitado', $parcial->estado_gestion);
        $this->assertEquals(200, (float) $parcial->monto_recuperado);
        $this->assertSame('pagado', $total->estado_gestion);
        $this->assertDatabaseHas('gestiones', ['lote_id' => $this->lote->id, 'resultado' => 'pago']);
    }

    public function test_no_se_registra_el_cobro_sin_confirmar_primero(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->call('registrarCobro')
            ->assertSet('confirmandoCobro', true);

        $this->assertDatabaseCount('cobros', 0);
        $this->assertDatabaseCount('recibos', 0);
    }

    public function test_al_volver_a_revisar_se_cancela_la_confirmacion(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->call('revisarCobro')
            ->call('volverACobro')
            ->assertSet('confirmandoCobro', false)
            ->call('registrarCobro')
            ->assertSet('confirmandoCobro', true);

        $this->assertDatabaseCount('cobros', 0);
    }

    public function test_confirmar_el_cobro_emite_un_recibo_con_numero_y_token(): void
    {
        $this->actingAs($this->cobrador);

        $componente = Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->set("montos.{$this->deudaA1->id}", '200')
            ->set("cobrar.{$this->deudaA2->id}", true)
            ->set("montos.{$this->deudaA2->id}", '100')
            ->call('revisarCobro')
            ->call('registrarCobro');

        $recibo = Recibo::with('cobros')->firstOrFail();

        $componente->assertSet('reciboId', $recibo->id)->assertSet('codigoDeudor', null);

        $this->assertMatchesRegularExpression('/^REC-\d{4}-\d{6}$/', $recibo->numero);
        $this->assertSame(40, strlen($recibo->token));
        $this->assertEquals(300, (float) $recibo->monto_total);
        $this->assertSame('Cliente 100', $recibo->cliente);
        $this->assertSame($this->cobrador->name, $recibo->cobrador);
        $this->assertCount(2, $recibo->cobros);
    }

    public function test_el_cobrador_solo_puede_ver_sus_propios_recibos(): void
    {
        $this->actingAs($this->cobrador);

        $recibo = $this->emitirRecibo();

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('verRecibo', $recibo->id)
            ->assertSet('reciboId', $recibo->id)
            ->assertSee($recibo->numero)
            ->call('cerrarRecibo')
            ->assertSet('reciboId', null);

        $recibo->update(['user_id' => $this->usuario('Cobrador', 'otro@test.com')->id]);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('verRecibo', $recibo->id)
            ->assertSet('reciboId', null);
    }

    public function test_el_recibo_publico_se_ve_sin_iniciar_sesion(): void
    {
        $recibo = $this->emitirRecibo();

        $this->get(route('recibos.publico', $recibo->token))
            ->assertOk()
            ->assertSee($recibo->numero)
            ->assertSee('Cliente 100')
            ->assertSee('Doc. A-1')
            ->assertSee('Pago registrado, pendiente de validación');
    }

    public function test_el_ticket_de_58mm_incluye_el_qr_con_la_url_publica(): void
    {
        $recibo = $this->emitirRecibo();

        $this->get(route('recibos.ticket', $recibo->token))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('58mm', false)
            ->assertSee($recibo->urlPublica());
    }

    public function test_un_token_inexistente_devuelve_404(): void
    {
        $this->get(route('recibos.publico', 'no-existe'))->assertNotFound();
    }

    public function test_no_se_registra_el_cobro_con_monto_mayor_al_pendiente_ni_en_la_revision(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->set("montos.{$this->deudaA1->id}", '9999')
            ->call('revisarCobro')
            ->assertHasErrors("montos.{$this->deudaA1->id}")
            ->assertSet('confirmandoCobro', false);
    }

    public function test_no_se_puede_cobrar_mas_que_el_saldo_pendiente(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->set("montos.{$this->deudaA1->id}", '9999')
            ->call('registrarCobro')
            ->assertHasErrors("montos.{$this->deudaA1->id}");

        $this->assertDatabaseCount('cobros', 0);
    }

    public function test_pagos_que_no_son_en_efectivo_exigen_referencia(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->set('formaPago', 'qr')
            ->call('registrarCobro')
            ->assertHasErrors('referencia');

        $this->assertDatabaseCount('cobros', 0);
    }

    public function test_exige_marcar_al_menos_una_deuda(): void
    {
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->call('registrarCobro')
            ->assertHasErrors('cobrar');
    }

    public function test_sin_permiso_de_cobrar_no_se_registra_el_cobro(): void
    {
        $this->cobrador->revokePermissionTo('cobrador.cobrar');
        $this->cobrador->roles()->detach();
        $this->cobrador->givePermissionTo(['cobrador.access', 'cobrador.gestionar']);
        $this->actingAs($this->cobrador);

        Livewire::test(Ruta::class, ['lote' => $this->lote])
            ->call('abrirDeudor', '100', 'cobro')
            ->set("cobrar.{$this->deudaA1->id}", true)
            ->call('registrarCobro');

        $this->assertDatabaseCount('cobros', 0);
        $this->assertSame(0, Gestion::count());
    }

    private function emitirRecibo(): Recibo
    {
        $gestion = Gestion::create([
            'lote_id' => $this->lote->id,
            'codigo_cliente' => '100',
            'user_id' => $this->cobrador->id,
            'tipo' => 'visita',
            'resultado' => 'pago',
        ]);

        $cobro = Cobro::create([
            'lote_id' => $this->lote->id,
            'deudacartera_id' => $this->deudaA1->id,
            'gestion_id' => $gestion->id,
            'user_id' => $this->cobrador->id,
            'monto' => 200,
            'fecha_cobro' => today(),
            'forma_pago' => 'efectivo',
        ]);

        return ReciboService::emitir($this->lote, $gestion, collect([$cobro]), 'Cliente 100');
    }

    private function agregar(Cartera $cartera, string $codigo, string $numDoc, float $saldo, int $orden): Deudacartera
    {
        $deuda = Deudacartera::create([
            'cartera_id' => $cartera->id,
            'num_doc' => $numDoc,
            'codigo_cliente' => $codigo,
            'cliente' => "Cliente {$codigo}",
            'importe' => $saldo,
            'saldo' => $saldo,
        ]);

        $this->lote->deudas()->attach($deuda->id, ['codigo_cliente' => $codigo, 'orden_visita' => $orden]);

        return $deuda;
    }

    private function usuario(string $rol, string $email): User
    {
        $usuario = User::query()->where('email', $email)->first()
            ?? User::factory()->create(['email' => $email, 'is_active' => true]);

        Role::findOrCreate($rol, 'web');
        $usuario->assignRole($rol);

        return $usuario;
    }
}
