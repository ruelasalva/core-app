<?php
namespace Fuel\Tasks;

/**
 * TASK SEEDSUPPLIERIMPORTHELP
 *
 * Crea o actualiza el articulo de ayuda de importacion de proveedores.
 *
 * Uso:
 * php oil refine seedsupplierimporthelp
 */
class Seedsupplierimporthelp
{
    public function run()
    {
        try {
            if (!\DBUtil::table_exists('core_knowledge_articles')) {
                throw new \RuntimeException('Falta la tabla core_knowledge_articles.');
            }

            $article = $this->article();
            $existing = \DB::select('id')
                ->from('core_knowledge_articles')
                ->where('code', '=', $article['code'])
                ->execute()
                ->current();

            $data = [
                'code' => $article['code'],
                'title' => $article['title'],
                'category' => $article['category'],
                'summary' => $article['summary'],
                'content' => $article['content'],
                'sort_order' => (int) $article['sort_order'],
                'active' => 1,
                'updated_at' => time(),
            ];

            if ($existing) {
                \DB::update('core_knowledge_articles')
                    ->set($data)
                    ->where('id', '=', (int) $existing['id'])
                    ->execute();
                \Cli::write('Artículo de ayuda actualizado: '.$article['title']);
            } else {
                $data['created_at'] = time();
                \DB::insert('core_knowledge_articles')->set($data)->execute();
                \Cli::write('Artículo de ayuda creado: '.$article['title']);
            }

            \Log::info('Ayuda de importacion de proveedores sembrada.');
        } catch (\Exception $e) {
            \Cli::write('Error sembrando ayuda de importación de proveedores: '.$e->getMessage());
            \Log::error('Seedsupplierimporthelp: '.$e->getMessage());
        }
    }

    protected function article()
    {
        return [
            'code' => 'importacion-de-proveedores',
            'title' => 'Importación de proveedores',
            'category' => 'Comercial',
            'summary' => 'Explica cómo cargar catálogos de proveedores en staging sin crear productos reales.',
            'sort_order' => 80,
            'content' => '<h3>Objetivo</h3><p>La importación de proveedores permite cargar catálogos externos en una tabla temporal para revisión. En esta fase no crea productos, no modifica precios, no descarga imágenes y no afecta inventario.</p><h3>Flujo recomendado</h3><ol><li>Descarga la plantilla CSV desde Comercio &gt; Importación de proveedores.</li><li>Llena las columnas SKU, modelo, nombre, marca, categoría, descripción, compatibilidad, precio, moneda, stock, imagen y URL de origen.</li><li>Ejecuta primero dry-run: <code>php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=1</code>.</li><li>Revisa totales, advertencias y errores.</li><li>Si el archivo es correcto, guarda staging con: <code>php oil refine supplierimportcsv --file=PATH --provider=CODE --dry-run=0</code>.</li><li>Revisa las filas en la pantalla administrativa antes de futuras fases de mapeo o creación de productos.</li></ol><h3>Reglas de seguridad</h3><ul><li>El staging no toca productos reales.</li><li>El staging no actualiza precios reales.</li><li>El staging no modifica inventario.</li><li>Las imágenes solo se guardan como URL; no se descargan en esta fase.</li><li>Los duplicados se detectan por source_hash y se omiten.</li></ul><h3>Columnas CSV</h3><p><code>sku, model, name, brand, category, description, compatibility, price, currency, stock, image_url, source_url</code></p>',
        ];
    }
}
