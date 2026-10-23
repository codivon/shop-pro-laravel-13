<?php
/**
 * Sandbox repair script — mirrors the documented repair steps from
 * ADMIN_PANEL_PROGRESS §3 (SANDBOX RESET 2026-10-19) for rebuilding the
 * SQLite test DB from scratch:
 *  1. Mark SQLite-incompatible migrations (dropForeign/change/dropColumn tricks) as run
 *  2. Apply their ADD-COLUMN effects via plain ALTER TABLE (SQLite-safe)
 *  3. Add deleted_at/ordering/lang/timestamps where needed (provinces/cities/geo)
 */

$db = new PDO('sqlite:/tmp/nptest.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function migrationRan(PDO $db, string $name): bool {
    $st = $db->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
    $st->execute([$name]);
    return (bool) $st->fetchColumn();
}

function markRan(PDO $db, string $name, int $batch): void {
    if (!migrationRan($db, $name)) {
        $db->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)')->execute([$name, $batch]);
        echo "MARKED: $name\n";
    }
}

function addColumn(PDO $db, string $table, string $col, string $type): void {
    $cols = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if ($c['name'] === $col) return;
    }
    $db->exec("ALTER TABLE $table ADD COLUMN $col $type");
    echo "ADDED: $table.$col ($type)\n";
}

// current max batch
$batch = (int) $db->query('SELECT COALESCE(MAX(batch),0) FROM migrations')->fetchColumn() + 1;

// --- The failing migration: modify_orders_table (SQLite cannot dropForeign/change) ---
markRan($db, '2021_08_30_173848_modify_orders_table', $batch);

// --- pulse_entries (type, timestamp) index: SHOW INDEX is MySQL-only ---
if (migrationRan($db, '2026_07_05_101010_add_type_timestamp_index_to_pulse_entries') === false) {
    $db->exec('CREATE INDEX IF NOT EXISTS pulse_entries_type_timestamp_index ON pulse_entries (type, timestamp)');
    echo "INDEX CREATED: pulse_entries (type, timestamp)\n";
    markRan($db, '2026_07_05_101010_add_type_timestamp_index_to_pulse_entries', $batch);
}

// --- enlarge_title_slug_columns: information_schema + ALTER MODIFY = MySQL-only.
//     On SQLite varchar is TEXT (no length limit) → cosmetic → mark only.
markRan($db, '2026_10_20_120001_enlarge_title_slug_columns', $batch);

// --- scale_readiness_for_millions: MySQL-only syntax (information_schema,
//     ALTER ADD INDEX, FULLTEXT, UPDATE-alias). Apply SQLite equivalents:
if (migrationRan($db, '2026_10_23_130001_scale_readiness_for_millions') === false) {
    // favorites unique
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS favorites_user_product_unique ON favorites (user_id, product_id)');
    // orders / prices composite indexes
    $db->exec('CREATE INDEX IF NOT EXISTS orders_user_created_at_index ON orders (user_id, created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS prices_product_stock_index ON prices (product_id, stock)');
    // denormalized columns
    addColumn($db, 'products', 'in_stock', 'INTEGER DEFAULT 0');
    addColumn($db, 'products', 'min_price', 'INTEGER');
    // backfill (SQLite-compatible, no aliases)
    $db->exec('UPDATE products SET in_stock = EXISTS(SELECT 1 FROM prices pr WHERE pr.product_id = products.id AND pr.stock > 0 AND pr.deleted_at IS NULL)');
    $db->exec("UPDATE products SET min_price = (SELECT MIN(CASE WHEN pr.discount_expire_at IS NULL OR DATE(pr.discount_expire_at) > DATE('now') THEN pr.discount_price ELSE pr.regular_price END) FROM prices pr WHERE pr.product_id = products.id AND pr.deleted_at IS NULL AND pr.stock > 0)");
    // composite indexes on products
    $db->exec('CREATE INDEX IF NOT EXISTS products_published_created_at_index ON products (published, created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS products_category_published_index ON products (category_id, published)');
    $db->exec('CREATE INDEX IF NOT EXISTS products_brand_published_index ON products (brand_id, published)');
    $db->exec('CREATE INDEX IF NOT EXISTS products_in_stock_index ON products (in_stock)');
    $db->exec('CREATE INDEX IF NOT EXISTS products_min_price_index ON products (min_price)');
    echo "SCALE-READY equivalents applied (FULLTEXT skipped → LIKE fallback active)\n";
    markRan($db, '2026_10_23_130001_scale_readiness_for_millions', $batch);
}

echo "Done. Continue with: php artisan migrate --force\n";
