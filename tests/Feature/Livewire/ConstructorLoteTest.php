<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Lotes\ConstructorLote;
use App\Models\Cartera;
use App\Models\Company;
use App\Models\Deudacartera;
use App\Models\Lote;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConstructorLoteTest extends TestCase
{
    use RefreshDatabase;

    private Cartera $cartera;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->cartera = Cartera::create(['company_id' => Company::factory()->create()->id]);
    }

    public function test_lista_un_registro_por_deudor_con_sus_totales(): void
    {
        $this->deuda('100', 'A-1', 500, 'VENCIDAS');
        $this->deuda('100', 'A-2', 300, 'VIGENTES');
        $this->deuda('200', 'B-1', 100, 'VIGENTES');

        $this->actingAs($this->jefeVentas());

        $deudores = Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])->get('deudores');

        $this->assertCount(2, $deudores);
        $primero = $deudores->firstWhere('codigo_cliente', '100');
        $this->assertSame(2, (int) $primero->total_deudas);
        $this->assertEquals(800, (float) $primero->total_saldo);
        $this->assertEquals(500, (float) $primero->saldo_vencido);
    }

    public function test_excluye_deudas_ausentes_sin_saldo_y_en_lote_activo(): void
    {
        $this->deuda('100', 'A-1', 500);
        $this->deuda('200', 'B-1', 500, ausente: true);
        $this->deuda('300', 'C-1', 0);
        $enLote = $this->deuda('400', 'D-1', 500);

        $lote = $this->loteAsignado();
        $lote->deudas()->attach($enLote->id, ['codigo_cliente' => '400']);

        $this->actingAs($this->jefeVentas());

        $codigos = Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->get('deudores')->pluck('codigo_cliente')->all();

        $this->assertSame(['100'], $codigos);
    }

    public function test_sugiere_los_deudores_mas_cercanos_a_la_semilla(): void
    {
        $this->deuda('100', 'A-1', 900, 'VENCIDAS', -17.78, -63.18);
        $this->deuda('200', 'B-1', 100, 'VENCIDAS', -17.79, -63.19);
        $this->deuda('300', 'C-1', 100, 'VENCIDAS', -17.50, -63.50);
        $this->deuda('400', 'D-1', 100, 'VENCIDAS', -16.50, -68.15);

        $this->actingAs($this->jefeVentas());

        $componente = Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->set('tamanoSugerido', 2)
            ->call('sugerirLote');

        $this->assertEqualsCanonicalizing(['100', '200'], $componente->get('seleccionados'));
    }

    public function test_la_sugerencia_respeta_el_radio_maximo(): void
    {
        $this->deuda('100', 'A-1', 900, 'VENCIDAS', -17.78, -63.18);
        $this->deuda('200', 'B-1', 100, 'VENCIDAS', -17.79, -63.19);
        $this->deuda('300', 'C-1', 100, 'VENCIDAS', -17.50, -63.50);

        $this->actingAs($this->jefeVentas());

        $componente = Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->set('tamanoSugerido', 10)
            ->set('radioMaxKm', 10)
            ->call('sugerirLote');

        $this->assertEqualsCanonicalizing(['100', '200'], $componente->get('seleccionados'));
    }

    public function test_los_filtros_solo_se_aplican_al_pulsar_filtrar(): void
    {
        $this->deuda('100', 'A-1', 500, 'VENCIDAS');
        $this->deuda('200', 'B-1', 100, 'VIGENTES');

        $this->actingAs($this->jefeVentas());

        $componente = Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->set('f.soloVencidas', true);

        $this->assertCount(2, $componente->get('deudores'));

        $componente->call('filtrar');

        $this->assertSame(['100'], $componente->get('deudores')->pluck('codigo_cliente')->all());
    }

    public function test_ignora_coordenadas_invalidas(): void
    {
        $this->deuda('100', 'A-1', 500, 'VENCIDAS', 0, 0);

        $this->actingAs($this->jefeVentas());

        $deudor = Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])->get('deudores')->first();

        $this->assertNull($deudor->lat);
    }

    public function test_crea_lote_con_todas_las_deudas_del_deudor_y_orden_de_visita(): void
    {
        $this->deuda('100', 'A-1', 500, 'VENCIDAS', -17.78, -63.18);
        $this->deuda('100', 'A-2', 300, 'VIGENTES', -17.78, -63.18);
        $this->deuda('200', 'B-1', 100, 'VENCIDAS', -17.79, -63.19);

        $cobrador = $this->cobrador();
        $this->actingAs($this->jefeVentas());

        Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->set('seleccionados', ['100', '200'])
            ->set('nombreLote', 'Lote Centro')
            ->set('cobradorId', $cobrador->id)
            ->set('fechaAsignacion', now()->toDateString())
            ->call('crearLote')
            ->assertHasNoErrors();

        $lote = Lote::firstOrFail();
        $this->assertSame(2, $lote->total_deudores);
        $this->assertSame(3, $lote->total_deudas);
        $this->assertEquals(900, (float) $lote->total_saldo);
        $this->assertSame('asignado', $lote->estado);

        $ordenes = $lote->deudas->groupBy('pivot.codigo_cliente')->map(fn ($g) => $g->pluck('pivot.orden_visita')->unique()->all());
        $this->assertSame([1], $ordenes['100']);
        $this->assertSame([2], $ordenes['200']);
    }

    public function test_no_permite_asignar_a_un_usuario_sin_rol_cobrador(): void
    {
        $this->deuda('100', 'A-1', 500);
        $this->actingAs($this->jefeVentas());

        Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->set('seleccionados', ['100'])
            ->set('nombreLote', 'Lote')
            ->set('cobradorId', $this->jefeVentas()->id)
            ->set('fechaAsignacion', now()->toDateString())
            ->call('crearLote')
            ->assertHasErrors('cobradorId');

        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_un_cobrador_no_puede_abrir_el_constructor(): void
    {
        $this->actingAs($this->cobrador());

        Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])->assertForbidden();
    }

    public function test_sin_permiso_de_asignar_no_se_puede_crear_el_lote(): void
    {
        $this->deuda('100', 'A-1', 500);
        $sinAsignar = User::factory()->create(['is_active' => true]);
        $sinAsignar->givePermissionTo('lotes.create');
        $this->actingAs($sinAsignar);

        Livewire::test(ConstructorLote::class, ['cartera' => $this->cartera])
            ->set('seleccionados', ['100'])
            ->set('nombreLote', 'Lote')
            ->set('cobradorId', $this->cobrador()->id)
            ->set('fechaAsignacion', now()->toDateString())
            ->call('crearLote');

        $this->assertDatabaseCount('lotes', 0);
    }

    private function deuda(string $codigo, string $numDoc, float $saldo, string $rango = 'VIGENTES', ?float $lat = null, ?float $lon = null, bool $ausente = false): Deudacartera
    {
        return Deudacartera::create([
            'cartera_id' => $this->cartera->id,
            'num_doc' => $numDoc,
            'codigo_cliente' => $codigo,
            'cliente' => "Cliente {$codigo}",
            'importe' => $saldo,
            'saldo' => $saldo,
            'rango' => $rango,
            'antiguedad' => $rango === 'VENCIDAS' ? 120 : 10,
            'coordenada_y' => $lat,
            'coordenada_x' => $lon,
            'ausente' => $ausente,
        ]);
    }

    private function loteAsignado(): Lote
    {
        return Lote::create([
            'cartera_id' => $this->cartera->id,
            'company_id' => $this->cartera->company_id,
            'user_id' => $this->cobrador()->id,
            'created_by' => $this->jefeVentas()->id,
            'codigo' => 'LOTE-TEST-0001',
            'nombre' => 'Lote existente',
            'estado' => 'asignado',
        ]);
    }

    private function jefeVentas(): User
    {
        return $this->usuarioConRol('JefeVentas', 'jefe@test.com');
    }

    private function cobrador(): User
    {
        return $this->usuarioConRol('Cobrador', 'cobrador@test.com');
    }

    private function usuarioConRol(string $rol, string $email): User
    {
        $usuario = User::query()->where('email', $email)->first()
            ?? User::factory()->create(['email' => $email, 'is_active' => true]);

        Role::findOrCreate($rol, 'web');
        $usuario->assignRole($rol);

        return $usuario;
    }
}
