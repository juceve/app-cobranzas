<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Lotes\Show;
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

class LoteShowTest extends TestCase
{
    use RefreshDatabase;

    private Cartera $cartera;

    private Lote $lote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->cartera = Cartera::create(['company_id' => Company::factory()->create()->id]);

        $this->lote = Lote::create([
            'cartera_id' => $this->cartera->id,
            'company_id' => $this->cartera->company_id,
            'user_id' => $this->usuario('Cobrador', 'cobrador@test.com')->id,
            'created_by' => $this->usuario('JefeVentas', 'jefe@test.com')->id,
            'codigo' => 'LOTE-TEST-0001',
            'nombre' => 'Lote de prueba',
            'estado' => 'asignado',
            'fecha_asignacion' => now()->toDateString(),
            'total_deudores' => 2,
            'total_deudas' => 3,
        ]);

        $this->agregarDeuda('100', 'A-1', 500, 1);
        $this->agregarDeuda('100', 'A-2', 300, 1);
        $this->agregarDeuda('200', 'B-1', 100, 2);
    }

    public function test_el_jefe_ve_la_ruta_de_visitas_en_orden(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));

        $deudores = Livewire::test(Show::class, ['lote' => $this->lote])->get('deudores');

        $this->assertSame(['100', '200'], $deudores->pluck('codigo')->all());
        $this->assertSame(2, $deudores->first()['deudas']->count());
    }

    public function test_ver_deudor_abre_el_modal_solo_para_deudores_del_lote(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));

        Livewire::test(Show::class, ['lote' => $this->lote])
            ->call('verDeudor', '100')
            ->assertSet('codigoDeudor', '100')
            ->assertSee('Deudas (2)')
            ->call('verDeudor', '999')
            ->assertSet('codigoDeudor', null);
    }

    public function test_un_cobrador_no_ve_el_lote_de_otro(): void
    {
        $otro = $this->usuario('Cobrador', 'otro@test.com');
        $this->actingAs($otro);

        Livewire::test(Show::class, ['lote' => $this->lote])->assertForbidden();
    }

    public function test_el_cobrador_asignado_puede_ver_su_lote_pero_no_cancelarlo(): void
    {
        $cobrador = $this->usuario('Cobrador', 'cobrador@test.com');
        $this->actingAs($cobrador);

        $componente = Livewire::test(Show::class, ['lote' => $this->lote])->assertOk();

        $componente->set('motivo', 'No quiero este lote')->call('cancelar');

        $this->assertSame('asignado', $this->lote->fresh()->estado);
    }

    public function test_cancelar_libera_las_deudas_y_registra_motivo_y_usuario(): void
    {
        $jefe = $this->usuario('JefeVentas', 'jefe@test.com');
        $this->actingAs($jefe);

        Livewire::test(Show::class, ['lote' => $this->lote])
            ->set('motivo', 'Error al asignar')
            ->call('cancelar')
            ->assertHasNoErrors();

        $lote = $this->lote->fresh();
        $this->assertSame('cancelado', $lote->estado);
        $this->assertSame('Error al asignar', $lote->motivo_cierre);
        $this->assertSame($jefe->id, $lote->cerrado_por);
        $this->assertNotNull($lote->cerrado_en);

        $disponibles = Deudacartera::query()
            ->whereDoesntHave('lotes', fn ($q) => $q->whereIn('lotes.estado', Lote::ESTADOS_ABIERTOS))
            ->count();
        $this->assertSame(3, $disponibles);
    }

    public function test_cancelar_exige_motivo(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));

        Livewire::test(Show::class, ['lote' => $this->lote])
            ->set('motivo', '')
            ->call('cancelar')
            ->assertHasErrors('motivo');

        $this->assertSame('asignado', $this->lote->fresh()->estado);
    }

    public function test_cerrar_deja_el_lote_cerrado_y_no_se_puede_cerrar_dos_veces(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));

        $componente = Livewire::test(Show::class, ['lote' => $this->lote])->call('cerrar');

        $this->assertSame('cerrado', $this->lote->fresh()->estado);

        $primerCierre = $this->lote->fresh()->cerrado_en;
        $this->travel(5)->minutes();
        $componente->call('cancelar');

        $this->assertSame('cerrado', $this->lote->fresh()->estado);
        $this->assertTrue($primerCierre->equalTo($this->lote->fresh()->cerrado_en));
    }

    public function test_quitar_deudor_actualiza_totales_y_libera_sus_deudas(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));

        Livewire::test(Show::class, ['lote' => $this->lote])->call('quitarDeudor', '200');

        $lote = $this->lote->fresh();
        $this->assertSame(1, $lote->total_deudores);
        $this->assertSame(2, $lote->total_deudas);
        $this->assertEquals(800, (float) $lote->total_saldo);
        $this->assertDatabaseMissing('lote_deuda', ['lote_id' => $lote->id, 'codigo_cliente' => '200']);
    }

    public function test_no_se_puede_quitar_el_unico_deudor_ni_uno_con_gestion(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));

        $this->lote->deudas()->updateExistingPivot(
            Deudacartera::where('codigo_cliente', '200')->value('id'),
            ['estado_gestion' => 'visitado']
        );

        $componente = Livewire::test(Show::class, ['lote' => $this->lote]);
        $componente->call('quitarDeudor', '200');

        $this->assertDatabaseHas('lote_deuda', ['lote_id' => $this->lote->id, 'codigo_cliente' => '200']);

        $this->lote->deudas()->wherePivot('codigo_cliente', '200')->detach();
        $this->lote->update(['total_deudores' => 1]);

        Livewire::test(Show::class, ['lote' => $this->lote->fresh()])->call('quitarDeudor', '100');

        $this->assertDatabaseHas('lote_deuda', ['lote_id' => $this->lote->id, 'codigo_cliente' => '100']);
    }

    public function test_modificar_cambia_datos_y_reasigna_cobrador(): void
    {
        $this->actingAs($this->usuario('JefeVentas', 'jefe@test.com'));
        $nuevo = $this->usuario('Cobrador', 'nuevo@test.com');

        Livewire::test(Show::class, ['lote' => $this->lote])
            ->call('abrirEditar')
            ->set('nombre', 'Lote renombrado')
            ->set('cobradorId', $nuevo->id)
            ->call('guardarEdicion')
            ->assertHasNoErrors();

        $lote = $this->lote->fresh();
        $this->assertSame('Lote renombrado', $lote->nombre);
        $this->assertSame($nuevo->id, $lote->user_id);
    }

    public function test_no_se_puede_reasignar_a_alguien_sin_rol_cobrador(): void
    {
        $jefe = $this->usuario('JefeVentas', 'jefe@test.com');
        $this->actingAs($jefe);

        Livewire::test(Show::class, ['lote' => $this->lote])
            ->call('abrirEditar')
            ->set('cobradorId', $jefe->id)
            ->call('guardarEdicion')
            ->assertHasErrors('cobradorId');
    }

    private function agregarDeuda(string $codigo, string $numDoc, float $saldo, int $orden): void
    {
        $deuda = Deudacartera::create([
            'cartera_id' => $this->cartera->id,
            'num_doc' => $numDoc,
            'codigo_cliente' => $codigo,
            'cliente' => "Cliente {$codigo}",
            'importe' => $saldo,
            'saldo' => $saldo,
        ]);

        $this->lote->deudas()->attach($deuda->id, ['codigo_cliente' => $codigo, 'orden_visita' => $orden]);
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
