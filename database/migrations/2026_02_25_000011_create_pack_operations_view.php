<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS pack_operations_view');

        DB::statement(<<<'SQL'
CREATE VIEW pack_operations_view AS
SELECT
    p.date AS txn_date,
    p.product_id,
    pi.pack_size_id,
    CAST(pi.pack_count AS SIGNED) AS pack_count_in,
    CAST(0 AS SIGNED) AS pack_count_out,
    pi.pack_qty_snapshot,
    pi.pack_uom,
    'PACK_IN' AS operation,
    'packing_items' AS ref_table,
    pi.id AS ref_id,
    COALESCE(pi.created_at, p.created_at) AS created_at,
    p.remarks
FROM packing_items pi
JOIN packings p ON p.id = pi.packing_id
UNION ALL
SELECT
    u.date AS txn_date,
    u.product_id,
    ui.pack_size_id,
    CAST(0 AS SIGNED) AS pack_count_in,
    CAST(ui.pack_count AS SIGNED) AS pack_count_out,
    ui.pack_qty_snapshot,
    ui.pack_uom,
    'UNPACK_OUT' AS operation,
    'unpacking_items' AS ref_table,
    ui.id AS ref_id,
    COALESCE(ui.created_at, u.created_at) AS created_at,
    u.remarks
FROM unpacking_items ui
JOIN unpackings u ON u.id = ui.unpacking_id
UNION ALL
SELECT
    d.dispatch_date AS txn_date,
    dl.product_id,
    dl.pack_size_id,
    CAST(0 AS SIGNED) AS pack_count_in,
    CAST(dl.pack_count AS SIGNED) AS pack_count_out,
    dl.pack_qty_snapshot,
    dl.pack_uom,
    'DISPATCH_PACK_OUT' AS operation,
    'dispatch_lines' AS ref_table,
    dl.id AS ref_id,
    COALESCE(dl.created_at, d.created_at) AS created_at,
    NULL AS remarks
FROM dispatch_lines dl
JOIN dispatches d ON d.id = dl.dispatch_id
WHERE d.deleted_at IS NULL
  AND dl.deleted_at IS NULL
  AND d.status = 'POSTED'
  AND dl.sale_mode = 'PACK';
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS pack_operations_view');
    }
};
