<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_deudores_padron');

        DB::statement(<<<'SQL'
CREATE VIEW v_deudores_padron AS
SELECT
    -- Identificación
    d.rut_id,
    d.codigo_cliente,
    d.cliente,

    -- Ubicación
    d.cli_lugar,
    d.ciu_nombre,
    d.direccion,
    d.coordenada_x,
    d.coordenada_y,

    -- Contacto
    d.telefono,

    -- Comercial
    d.ent_nombre_jefe_vendedor,
    d.ent_nombre_supervisor,
    d.ent_nombre_vendedor,

    -- Metadata de aparición
    MIN(d.fecha_carga) AS primera_aparicion,
    MAX(d.fecha_carga) AS ultima_aparicion,
    COUNT(DISTINCT d.cartera_id) AS carteras_distintas

FROM deudacarteras d
WHERE d.rut_id IS NOT NULL
  AND d.rut_id <> ''
GROUP BY
    d.rut_id,
    d.codigo_cliente,
    d.cliente,
    d.cli_lugar,
    d.ciu_nombre,
    d.direccion,
    d.coordenada_x,
    d.coordenada_y,
    d.telefono,
    d.ent_nombre_jefe_vendedor,
    d.ent_nombre_supervisor,
    d.ent_nombre_vendedor
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_deudores_padron');
    }
};