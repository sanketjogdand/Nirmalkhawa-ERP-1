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
    sl.id AS id,
    DATE(sl.txn_datetime) AS txn_date,
    sl.txn_datetime,
    sl.product_id,
    CASE WHEN sl.is_increase = 1 THEN sl.qty ELSE 0 END AS qty_in,
    CASE WHEN sl.is_increase = 0 THEN sl.qty ELSE 0 END AS qty_out,
    sl.uom,
    sl.txn_type,
    'stock_ledgers' AS ref_table,
    sl.id AS ref_id,
    sl.remarks,
    sl.created_at,
    sl.updated_at
FROM stock_ledgers sl
UNION ALL
SELECT
    sal.id AS id,
    sa.adjustment_date AS txn_date,
    CAST(sa.adjustment_date AS DATETIME) AS txn_datetime,
    sal.product_id,
    CASE WHEN sal.direction = 'IN' THEN sal.qty ELSE 0 END AS qty_in,
    CASE WHEN sal.direction = 'OUT' THEN sal.qty ELSE 0 END AS qty_out,
    sal.uom,
    CASE WHEN sal.direction = 'IN' THEN 'ADJUSTMENT_IN' ELSE 'ADJUSTMENT_OUT' END AS txn_type,
    'stock_adjustment_lines' AS ref_table,
    sal.id AS ref_id,
    COALESCE(sal.remarks, sa.reason) AS remarks,
    sal.created_at,
    sal.updated_at
FROM stock_adjustment_lines sal
JOIN stock_adjustments sa ON sa.id = sal.stock_adjustment_id
WHERE sa.deleted_at IS NULL
  AND sal.deleted_at IS NULL;
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
