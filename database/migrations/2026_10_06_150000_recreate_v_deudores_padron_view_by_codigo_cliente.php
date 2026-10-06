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
    u.rut_id,
    u.codigo_cliente,
    u.cliente,
    u.cli_lugar,
    u.ciu_nombre,
    u.direccion,
    u.coordenada_x,
    u.coordenada_y,
    u.telefono,
    u.ent_nombre_jefe_vendedor,
    u.ent_nombre_supervisor,
    u.ent_nombre_vendedor,
    a.primera_aparicion,
    a.ultima_aparicion,
    a.carteras_distintas,
    a.direcciones_distintas
FROM (
    SELECT
        rut_id, codigo_cliente, cliente, cli_lugar, ciu_nombre, direccion,
        coordenada_x, coordenada_y, telefono,
        ent_nombre_jefe_vendedor, ent_nombre_supervisor, ent_nombre_vendedor,
        ROW_NUMBER() OVER (
            PARTITION BY codigo_cliente
            ORDER BY (fecha_carga IS NULL), fecha_carga DESC, id DESC
        ) AS rn
    FROM deudacarteras
    WHERE codigo_cliente IS NOT NULL AND codigo_cliente <> ''
) u
JOIN (
    SELECT
        codigo_cliente,
        MIN(fecha_carga) AS primera_aparicion,
        MAX(fecha_carga) AS ultima_aparicion,
        COUNT(DISTINCT cartera_id) AS carteras_distintas,
        COUNT(DISTINCT direccion) AS direcciones_distintas
    FROM deudacarteras
    WHERE codigo_cliente IS NOT NULL AND codigo_cliente <> ''
    GROUP BY codigo_cliente
) a ON a.codigo_cliente = u.codigo_cliente
WHERE u.rn = 1
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_deudores_padron');

        DB::statement(<<<'SQL'
CREATE VIEW v_deudores_padron AS
SELECT
    d.rut_id, d.codigo_cliente, d.cliente, d.cli_lugar, d.ciu_nombre, d.direccion,
    d.coordenada_x, d.coordenada_y, d.telefono,
    d.ent_nombre_jefe_vendedor, d.ent_nombre_supervisor, d.ent_nombre_vendedor,
    MIN(d.fecha_carga) AS primera_aparicion,
    MAX(d.fecha_carga) AS ultima_aparicion,
    COUNT(DISTINCT d.cartera_id) AS carteras_distintas
FROM deudacarteras d
WHERE d.rut_id IS NOT NULL AND d.rut_id <> ''
GROUP BY
    d.rut_id, d.codigo_cliente, d.cliente, d.cli_lugar, d.ciu_nombre, d.direccion,
    d.coordenada_x, d.coordenada_y, d.telefono,
    d.ent_nombre_jefe_vendedor, d.ent_nombre_supervisor, d.ent_nombre_vendedor
SQL);
    }
};
