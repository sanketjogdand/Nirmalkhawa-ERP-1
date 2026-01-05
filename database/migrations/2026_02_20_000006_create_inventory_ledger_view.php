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
        DB::statement('DROP VIEW IF EXISTS inventory_ledger_view');

        DB::statement(<<<'SQL'
CREATE VIEW inventory_ledger_view AS
SELECT
    g.grn_date AS txn_date,
    gl.product_id,
    CAST(COALESCE(gl.received_qty, 0) AS DECIMAL(15, 3)) AS qty_in,
    CAST(0 AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(gl.uom, '') AS uom,
    'GRN_IN' AS txn_type,
    'grn_lines' AS ref_table,
    gl.id AS ref_id,
    COALESCE(gl.created_at, g.created_at) AS created_at,
    gl.remarks
FROM grn_lines gl
JOIN grns g ON g.id = gl.grn_id
WHERE g.deleted_at IS NULL
  AND gl.deleted_at IS NULL
UNION ALL
SELECT
    pb.date AS txn_date,
    pb.output_product_id AS product_id,
    CAST(COALESCE(pb.actual_output_qty, 0) AS DECIMAL(15, 3)) AS qty_in,
    CAST(0 AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(pb.output_uom, '') AS uom,
    'PRODUCTION_OUT_IN' AS txn_type,
    'production_batches' AS ref_table,
    pb.id AS ref_id,
    pb.created_at AS created_at,
    pb.remarks
FROM production_batches pb
WHERE pb.deleted_at IS NULL
UNION ALL
SELECT
    u.date AS txn_date,
    u.product_id,
    CAST(COALESCE(u.total_bulk_qty, 0) AS DECIMAL(15, 3)) AS qty_in,
    CAST(0 AS DECIMAL(15, 3)) AS qty_out,
    '' AS uom,
    'UNPACK_BULK_IN' AS txn_type,
    'unpackings' AS ref_table,
    u.id AS ref_id,
    u.created_at AS created_at,
    u.remarks
FROM unpackings u
UNION ALL
SELECT
    sa.adjustment_date AS txn_date,
    sal.product_id,
    CAST(COALESCE(sal.qty, 0) AS DECIMAL(15, 3)) AS qty_in,
    CAST(0 AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(sal.uom, '') AS uom,
    'ADJUSTMENT_IN' AS txn_type,
    'stock_adjustment_lines' AS ref_table,
    sal.id AS ref_id,
    COALESCE(sal.created_at, sa.created_at) AS created_at,
    COALESCE(sal.remarks, sa.reason) AS remarks
FROM stock_adjustment_lines sal
JOIN stock_adjustments sa ON sa.id = sal.stock_adjustment_id
WHERE sa.deleted_at IS NULL
  AND sal.deleted_at IS NULL
  AND sal.direction = 'IN'
UNION ALL
SELECT
    mi.date AS txn_date,
    p.id AS product_id,
    CAST(COALESCE(mi.qty_ltr, 0) AS DECIMAL(15, 3)) AS qty_in,
    CAST(0 AS DECIMAL(15, 3)) AS qty_out,
    'LTR' AS uom,
    'MILK_INTAKE_IN' AS txn_type,
    'milk_intakes' AS ref_table,
    mi.id AS ref_id,
    mi.created_at AS created_at,
    CONCAT('Center:', mi.center_id, ' Shift:', mi.shift) AS remarks
FROM milk_intakes mi
LEFT JOIN products p ON p.code = CASE
    WHEN mi.milk_type = 'CM' THEN 'RAW-CM'
    WHEN mi.milk_type = 'BM' THEN 'RAW-BM'
    ELSE 'RAW-MIX'
END
WHERE mi.deleted_at IS NULL
UNION ALL
SELECT
    pb.date AS txn_date,
    pi.material_product_id AS product_id,
    CAST(0 AS DECIMAL(15, 3)) AS qty_in,
    CAST(COALESCE(pi.actual_qty_used, 0) AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(pi.uom, '') AS uom,
    'PRODUCTION_CONSUMPTION_OUT' AS txn_type,
    'production_inputs' AS ref_table,
    pi.id AS ref_id,
    COALESCE(pi.created_at, pb.created_at) AS created_at,
    NULL AS remarks
FROM production_inputs pi
JOIN production_batches pb ON pb.id = pi.production_batch_id
WHERE pb.deleted_at IS NULL
  AND pi.deleted_at IS NULL
  AND pi.actual_qty_used IS NOT NULL
  AND pi.actual_qty_used > 0
UNION ALL
SELECT
    mc.consumption_date AS txn_date,
    mcl.product_id,
    CAST(0 AS DECIMAL(15, 3)) AS qty_in,
    CAST(COALESCE(mcl.qty, 0) AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(mcl.uom, '') AS uom,
    'MATERIAL_CONSUMPTION_OUT' AS txn_type,
    'material_consumption_lines' AS ref_table,
    mcl.id AS ref_id,
    COALESCE(mcl.created_at, mc.created_at) AS created_at,
    COALESCE(mcl.remarks, mc.consumption_type) AS remarks
FROM material_consumption_lines mcl
JOIN material_consumptions mc ON mc.id = mcl.material_consumption_id
WHERE mc.deleted_at IS NULL
  AND mcl.deleted_at IS NULL
UNION ALL
SELECT
    p.date AS txn_date,
    p.product_id,
    CAST(0 AS DECIMAL(15, 3)) AS qty_in,
    CAST(COALESCE(p.total_bulk_qty, 0) AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(prod.uom, '') AS uom,
    'PACK_BULK_OUT' AS txn_type,
    'packings' AS ref_table,
    p.id AS ref_id,
    p.created_at AS created_at,
    p.remarks
FROM packings p
LEFT JOIN products prod ON prod.id = p.product_id
UNION ALL
SELECT
    p.date AS txn_date,
    pmu.material_product_id AS product_id,
    CAST(0 AS DECIMAL(15, 3)) AS qty_in,
    CAST(COALESCE(pmu.qty_used, 0) AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(pmu.uom, '') AS uom,
    'PACK_MATERIAL_OUT' AS txn_type,
    'packing_material_usages' AS ref_table,
    pmu.id AS ref_id,
    COALESCE(pmu.created_at, p.created_at) AS created_at,
    pmu.remarks
FROM packing_material_usages pmu
JOIN packings p ON p.id = pmu.packing_id
WHERE pmu.deleted_at IS NULL
UNION ALL
SELECT
    d.dispatch_date AS txn_date,
    dl.product_id,
    CAST(0 AS DECIMAL(15, 3)) AS qty_in,
    CAST(CASE WHEN dl.sale_mode = 'BULK' THEN COALESCE(dl.computed_total_qty, 0) ELSE 0 END AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(dl.uom, dl.pack_uom, '') AS uom,
    'DISPATCH_OUT' AS txn_type,
    'dispatch_lines' AS ref_table,
    dl.id AS ref_id,
    COALESCE(dl.created_at, d.created_at) AS created_at,
    NULL AS remarks
FROM dispatch_lines dl
JOIN dispatches d ON d.id = dl.dispatch_id
WHERE d.deleted_at IS NULL
  AND dl.deleted_at IS NULL
  AND d.status = 'POSTED'
  AND dl.sale_mode = 'BULK'
UNION ALL
SELECT
    sa.adjustment_date AS txn_date,
    sal.product_id,
    CAST(0 AS DECIMAL(15, 3)) AS qty_in,
    CAST(COALESCE(sal.qty, 0) AS DECIMAL(15, 3)) AS qty_out,
    COALESCE(sal.uom, '') AS uom,
    'ADJUSTMENT_OUT' AS txn_type,
    'stock_adjustment_lines' AS ref_table,
    sal.id AS ref_id,
    COALESCE(sal.created_at, sa.created_at) AS created_at,
    COALESCE(sal.remarks, sa.reason) AS remarks
FROM stock_adjustment_lines sal
JOIN stock_adjustments sa ON sa.id = sal.stock_adjustment_id
WHERE sa.deleted_at IS NULL
  AND sal.deleted_at IS NULL
  AND sal.direction = 'OUT';
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS inventory_ledger_view');
    }
};
