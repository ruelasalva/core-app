<?php
namespace Fuel\Tasks;

/**
 * TASK TONERSDISCOVERY
 *
 * Descubre estructura publica de catalogo. No guarda datos.
 *
 * Uso:
 * php oil refine tonersdiscovery
 * php oil refine tonersdiscovery --limit-pages=20 --delay-ms=500
 * php oil refine tonersdiscovery --mode=sitemap --limit-products=20
 */
class Tonersdiscovery
{
    public function run()
    {
        $options = $this->options();

        try {
            $adapter = new \Service_Core_SupplierImport_Adapters_TonersParaImpresoras();
            if ((string) \Arr::get($options, 'mode', 'html') === 'sitemap') {
                $result = $adapter->discover_sitemap($options);
                $this->print_sitemap_result($result);
            } else {
                $result = $adapter->discover($options);
                $this->print_result($result);
            }
        } catch (\Exception $e) {
            \Cli::write('[ERROR] '.$e->getMessage());
            \Log::error('Fallo tonersdiscovery: '.$e->getMessage());
        }
    }

    protected function print_result(array $result)
    {
        \Cli::write('');
        \Cli::write('Discovery TonersParaImpresoras');
        \Cli::write('-------------------------------');
        \Cli::write('Sitio: '.(string) \Arr::get($result, 'site', ''));
        \Cli::write('Paginas visitadas: '.(int) \Arr::get($result, 'pages_visited', 0));
        \Cli::write('');
        \Cli::write('Totales:');
        \Cli::write(' - Categorias encontradas: '.(int) \Arr::get($result, 'total_categories', 0));
        \Cli::write(' - URLs de producto detectadas: '.(int) \Arr::get($result, 'total_product_urls', 0));
        \Cli::write(' - URLs de imagen detectadas: '.(int) \Arr::get($result, 'total_image_urls', 0));
        \Cli::write(' - Nombres de producto detectados: '.(int) \Arr::get($result, 'total_product_names', 0));

        $this->print_sample('Muestras de categorias', (array) \Arr::get($result, 'sample_categories', []));
        $this->print_sample('Muestras de URLs de producto', (array) \Arr::get($result, 'sample_product_urls', []));
        $this->print_sample('Muestras de imagenes', (array) \Arr::get($result, 'sample_image_urls', []));
        $this->print_sample('Muestras de nombres', (array) \Arr::get($result, 'sample_product_names', []));
        $this->print_sample('Muestras de paginacion', (array) \Arr::get($result, 'sample_pagination_urls', []));

        $warnings = (array) \Arr::get($result, 'warnings', []);
        if (!empty($warnings)) {
            \Cli::write('');
            \Cli::write('Advertencias:');
            foreach ($warnings as $warning) {
                \Cli::write(' - '.$warning);
            }
        }

        \Cli::write('');
        \Cli::write('No se guardaron datos. No se inserto staging. No se modificaron productos, precios, inventario ni imagenes.');
    }

    protected function print_sitemap_result(array $result)
    {
        \Cli::write('');
        \Cli::write('Discovery TonersParaImpresoras');
        \Cli::write('-------------------------------');
        \Cli::write('Modo: sitemap');
        \Cli::write('Sitemap: '.(string) \Arr::get($result, 'sitemap_url', ''));
        \Cli::write('Sitemaps procesados: '.(int) \Arr::get($result, 'sitemaps_processed', 0));
        \Cli::write('');
        \Cli::write('Totales:');
        \Cli::write(' - URLs sitemap: '.(int) \Arr::get($result, 'total_sitemap_urls', 0));
        \Cli::write(' - URLs de producto: '.(int) \Arr::get($result, 'total_product_urls', 0));

        $this->print_sample('Muestras de URLs de producto', (array) \Arr::get($result, 'sample_product_urls', []));
        $this->print_lastmod_sample((array) \Arr::get($result, 'sample_product_lastmods', []));

        $warnings = (array) \Arr::get($result, 'warnings', []);
        if (!empty($warnings)) {
            \Cli::write('');
            \Cli::write('Advertencias:');
            foreach ($warnings as $warning) {
                \Cli::write(' - '.$warning);
            }
        }

        \Cli::write('');
        \Cli::write('No se guardaron datos. No se inserto staging. No se modificaron productos, precios, inventario ni imagenes.');
    }

    protected function print_lastmod_sample(array $items)
    {
        \Cli::write('');
        \Cli::write('Muestras de lastmod:');
        if (empty($items)) {
            \Cli::write(' - Sin lastmod detectado.');
            return;
        }

        foreach (array_slice($items, 0, 10) as $item) {
            \Cli::write(' - '.(string) \Arr::get($item, 'url', '').' | lastmod: '.((string) \Arr::get($item, 'lastmod', '') ?: 'Sin fecha'));
        }
    }

    protected function print_sample($title, array $items)
    {
        \Cli::write('');
        \Cli::write($title.':');
        if (empty($items)) {
            \Cli::write(' - Sin datos detectados.');
            return;
        }

        foreach (array_slice($items, 0, 10) as $item) {
            \Cli::write(' - '.$item);
        }
    }

    protected function options()
    {
        $options = [
            'mode' => 'html',
            'limit_pages' => 20,
            'delay_ms' => 500,
            'limit_products' => 20,
        ];

        $argv = isset($_SERVER['argv']) ? (array) $_SERVER['argv'] : [];
        foreach ($argv as $arg) {
            if (strpos($arg, '--') !== 0) {
                continue;
            }

            $arg = substr($arg, 2);
            $parts = explode('=', $arg, 2);
            $key = str_replace('-', '_', trim((string) $parts[0]));
            $value = isset($parts[1]) ? trim((string) $parts[1]) : '';

            if ($key === 'mode') {
                $mode = strtolower($value);
                $options['mode'] = in_array($mode, ['html', 'sitemap'], true) ? $mode : 'html';
            } elseif ($key === 'limit_pages') {
                $options['limit_pages'] = (int) $value;
            } elseif ($key === 'delay_ms') {
                $options['delay_ms'] = (int) $value;
            } elseif ($key === 'limit_products') {
                $options['limit_products'] = (int) $value;
            }
        }

        return $options;
    }
}
