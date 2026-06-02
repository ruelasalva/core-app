<?php

/**
 * SERVICE CORE SUPPLIERIMPORT ADAPTERS TONERSPARAIMPRESORAS
 *
 * Descubre estructura publica del catalogo sin guardar datos.
 * No crea staging, productos, precios, inventario ni imagenes.
 */
class Service_Core_SupplierImport_Adapters_TonersParaImpresoras
{
    protected $base_url = 'https://catalogo.tonersparaimpresoras.com/';
    protected $host = 'catalogo.tonersparaimpresoras.com';
    protected $user_agent = 'CORE-APP ERP Supplier Discovery';
    protected $warnings = [];
    protected $visited = [];
    protected $robots_rules = [];

    public function discover(array $options = [])
    {
        $limit_pages = max(1, min(100, (int) \Arr::get($options, 'limit_pages', 20)));
        $delay_ms = max(0, min(5000, (int) \Arr::get($options, 'delay_ms', 500)));

        $this->warnings = [];
        $this->visited = [];
        $this->robots_rules = $this->load_robots_rules();

        $queue = [$this->base_url];
        $categories = [];
        $product_urls = [];
        $image_urls = [];
        $product_names = [];
        $pagination_urls = [];

        while (!empty($queue) && count($this->visited) < $limit_pages) {
            $url = array_shift($queue);
            $url = $this->normalize_url($url);

            if ($url === '' || isset($this->visited[$url]) || !$this->same_host($url)) {
                continue;
            }

            if (!$this->allowed_by_robots($url)) {
                $this->warnings[] = 'URL bloqueada por robots.txt: '.$url;
                continue;
            }

            $this->visited[$url] = true;
            if ($delay_ms > 0 && count($this->visited) > 1) {
                usleep($delay_ms * 1000);
            }

            try {
                $html = $this->fetch_html($url);
            } catch (\Exception $e) {
                $this->warnings[] = $url.': '.$e->getMessage();
                continue;
            }

            $page = $this->parse_html($html, $url);
            $categories = $this->merge_unique($categories, $page['categories']);
            $product_urls = $this->merge_unique($product_urls, $page['product_urls']);
            $image_urls = $this->merge_unique($image_urls, $page['image_urls']);
            $product_names = $this->merge_unique($product_names, $page['product_names']);
            $pagination_urls = $this->merge_unique($pagination_urls, $page['pagination_urls']);

            $candidates = array_merge($page['categories'], $page['pagination_urls'], array_slice($page['product_urls'], 0, 5));
            foreach ($candidates as $candidate) {
                $candidate = $this->normalize_url($candidate, $url);
                if ($candidate !== '' && $this->same_host($candidate) && !isset($this->visited[$candidate]) && count($queue) < $limit_pages * 2) {
                    $queue[] = $candidate;
                }
            }
        }

        if (empty($product_urls) && empty($product_names)) {
            $this->warnings[] = 'El sitio parece depender de JavaScript; se requerira estrategia headless o endpoint autorizado.';
        }

        \Log::info('Discovery TonersParaImpresoras paginas='.count($this->visited).' categorias='.count($categories).' productos='.count($product_urls).' imagenes='.count($image_urls));

        return [
            'site' => $this->base_url,
            'pages_visited' => count($this->visited),
            'total_categories' => count($categories),
            'total_product_urls' => count($product_urls),
            'total_image_urls' => count($image_urls),
            'total_product_names' => count($product_names),
            'sample_categories' => array_slice(array_values($categories), 0, 10),
            'sample_product_urls' => array_slice(array_values($product_urls), 0, 10),
            'sample_image_urls' => array_slice(array_values($image_urls), 0, 10),
            'sample_product_names' => array_slice(array_values($product_names), 0, 10),
            'sample_pagination_urls' => array_slice(array_values($pagination_urls), 0, 10),
            'warnings' => array_values(array_unique($this->warnings)),
        ];
    }

    public function discover_sitemap(array $options = [])
    {
        $limit_products = max(1, min(5000, (int) \Arr::get($options, 'limit_products', 20)));
        $limit_sitemaps = max(1, min(20, (int) \Arr::get($options, 'limit_sitemaps', 5)));
        $sitemap_url = 'https://'.$this->host.'/sitemap.xml';

        $this->warnings = [];
        $this->visited = [];
        $this->robots_rules = $this->load_robots_rules();

        if (!$this->allowed_by_robots($sitemap_url)) {
            $this->warnings[] = 'Sitemap bloqueado por robots.txt: '.$sitemap_url;
            return $this->empty_sitemap_result($sitemap_url);
        }

        $all_urls = [];
        $product_urls = [];
        $product_lastmods = [];
        $sitemaps_processed = 0;

        try {
            $xml = $this->fetch_text($sitemap_url);
            $parsed = $this->parse_sitemap_xml($xml);
        } catch (\Exception $e) {
            $this->warnings[] = 'No se pudo leer sitemap.xml: '.$e->getMessage();
            return $this->empty_sitemap_result($sitemap_url);
        }

        if ($parsed['type'] === 'sitemapindex') {
            foreach (array_slice($parsed['sitemaps'], 0, $limit_sitemaps) as $child_sitemap) {
                $child_url = $this->normalize_url((string) \Arr::get($child_sitemap, 'loc', ''), $sitemap_url);
                if ($child_url === '' || !$this->same_host($child_url) || !$this->allowed_by_robots($child_url)) {
                    continue;
                }

                try {
                    $child_xml = $this->fetch_text($child_url);
                    $child = $this->parse_sitemap_xml($child_xml);
                    $sitemaps_processed++;
                    $this->collect_sitemap_urls($child['urls'], $all_urls, $product_urls, $product_lastmods, $limit_products);
                } catch (\Exception $e) {
                    $this->warnings[] = 'No se pudo leer sub-sitemap '.$child_url.': '.$e->getMessage();
                }

                if (count($product_urls) >= $limit_products) {
                    break;
                }
            }
        } else {
            $sitemaps_processed = 1;
            $this->collect_sitemap_urls($parsed['urls'], $all_urls, $product_urls, $product_lastmods, $limit_products);
        }

        \Log::info('Discovery sitemap TonersParaImpresoras sitemaps='.$sitemaps_processed.' urls='.count($all_urls).' productos='.count($product_urls));

        return [
            'site' => $this->base_url,
            'mode' => 'sitemap',
            'sitemap_url' => $sitemap_url,
            'sitemaps_processed' => $sitemaps_processed,
            'total_sitemap_urls' => count($all_urls),
            'total_product_urls' => count($product_urls),
            'sample_product_urls' => array_slice(array_values($product_urls), 0, 10),
            'sample_product_lastmods' => array_slice($product_lastmods, 0, 10),
            'warnings' => array_values(array_unique($this->warnings)),
        ];
    }

    protected function empty_sitemap_result($sitemap_url)
    {
        return [
            'site' => $this->base_url,
            'mode' => 'sitemap',
            'sitemap_url' => $sitemap_url,
            'sitemaps_processed' => 0,
            'total_sitemap_urls' => 0,
            'total_product_urls' => 0,
            'sample_product_urls' => [],
            'sample_product_lastmods' => [],
            'warnings' => array_values(array_unique($this->warnings)),
        ];
    }

    protected function load_robots_rules()
    {
        $robots_url = 'https://'.$this->host.'/robots.txt';
        try {
            $content = $this->fetch_text($robots_url);
        } catch (\Exception $e) {
            $this->warnings[] = 'robots.txt no disponible: '.$e->getMessage();
            return [];
        }

        $rules = [];
        $applies = false;
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }

            list($key, $value) = array_map('trim', explode(':', $line, 2));
            $key = strtolower($key);
            if ($key === 'user-agent') {
                $agent = strtolower($value);
                $applies = $agent === '*' || strpos(strtolower($this->user_agent), $agent) !== false;
                continue;
            }

            if ($applies && $key === 'disallow' && $value !== '') {
                $rules[] = $value;
            }
        }

        return $rules;
    }

    protected function allowed_by_robots($url)
    {
        if (empty($this->robots_rules)) {
            return true;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        foreach ($this->robots_rules as $rule) {
            if ($rule !== '' && strpos($path, $rule) === 0) {
                return false;
            }
        }

        return true;
    }

    protected function fetch_html($url)
    {
        $content = $this->fetch_text($url);
        if (stripos($content, '<html') === false && stripos($content, '<!doctype') === false) {
            $this->warnings[] = 'Respuesta sin HTML reconocible: '.$url;
        }

        return $content;
    }

    protected function fetch_text($url)
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('La extension cURL no esta disponible.');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $content = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($content === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException('HTTP '.$status.($error ? ' '.$error : ''));
        }

        return (string) $content;
    }

    protected function parse_sitemap_xml($xml)
    {
        if (!class_exists('DOMDocument')) {
            throw new \RuntimeException('PHP DOMDocument no esta disponible para leer sitemap XML.');
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();

        if (!$loaded) {
            throw new \RuntimeException('XML de sitemap invalido.');
        }

        $root = strtolower($dom->documentElement ? $dom->documentElement->localName : '');
        if ($root === 'sitemapindex') {
            return [
                'type' => 'sitemapindex',
                'urls' => [],
                'sitemaps' => $this->extract_sitemap_nodes($dom, 'sitemap'),
            ];
        }

        return [
            'type' => 'urlset',
            'urls' => $this->extract_sitemap_nodes($dom, 'url'),
            'sitemaps' => [],
        ];
    }

    protected function extract_sitemap_nodes(\DOMDocument $dom, $tag)
    {
        $items = [];
        foreach ($dom->getElementsByTagName($tag) as $node) {
            $loc = '';
            $lastmod = '';
            foreach ($node->childNodes as $child) {
                $name = strtolower($child->localName);
                if ($name === 'loc') {
                    $loc = trim((string) $child->textContent);
                } elseif ($name === 'lastmod') {
                    $lastmod = trim((string) $child->textContent);
                }
            }

            if ($loc !== '') {
                $items[] = [
                    'loc' => $loc,
                    'lastmod' => $lastmod,
                ];
            }
        }

        return $items;
    }

    protected function collect_sitemap_urls(array $urls, array &$all_urls, array &$product_urls, array &$product_lastmods, $limit_products)
    {
        foreach ($urls as $item) {
            $url = $this->normalize_url((string) \Arr::get($item, 'loc', ''), $this->base_url);
            if ($url === '' || !$this->same_host($url)) {
                continue;
            }

            $all_urls[$url] = $url;
            if (strpos((string) parse_url($url, PHP_URL_PATH), '/producto/') !== false) {
                $product_urls[$url] = $url;
                $product_lastmods[] = [
                    'url' => $url,
                    'lastmod' => (string) \Arr::get($item, 'lastmod', ''),
                ];
            }

            if (count($product_urls) >= $limit_products) {
                break;
            }
        }
    }

    protected function parse_html($html, $base_url)
    {
        if (!class_exists('DOMDocument')) {
            throw new \RuntimeException('PHP DOMDocument no esta disponible.');
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $links = $this->extract_links($xpath, $base_url);
        $images = $this->extract_images($xpath, $base_url);
        $names = $this->extract_product_names($xpath);

        return [
            'categories' => $this->filter_links($links, 'category'),
            'product_urls' => $this->filter_links($links, 'product'),
            'pagination_urls' => $this->filter_links($links, 'pagination'),
            'image_urls' => $images,
            'product_names' => $names,
        ];
    }

    protected function extract_links(\DOMXPath $xpath, $base_url)
    {
        $links = [];
        foreach ($xpath->query('//a[@href]') as $node) {
            $href = $node->getAttribute('href');
            $url = $this->normalize_url($href, $base_url);
            if ($url !== '' && $this->same_host($url)) {
                $links[] = $url;
            }
        }

        return array_values(array_unique($links));
    }

    protected function extract_images(\DOMXPath $xpath, $base_url)
    {
        $images = [];
        foreach ($xpath->query('//img[@src]') as $node) {
            $src = $node->getAttribute('src');
            $url = $this->normalize_url($src, $base_url);
            if ($url !== '' && $this->same_host($url) && preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $url)) {
                $images[] = $url;
            }
        }

        return array_values(array_unique($images));
    }

    protected function extract_product_names(\DOMXPath $xpath)
    {
        $names = [];
        $queries = [
            '//*[contains(@class, "product")]//h1',
            '//*[contains(@class, "product")]//h2',
            '//*[contains(@class, "product")]//h3',
            '//*[contains(@class, "producto")]//h1',
            '//*[contains(@class, "producto")]//h2',
            '//*[contains(@class, "producto")]//h3',
            '//h1',
            '//h2',
            '//h3',
        ];

        foreach ($queries as $query) {
            foreach ($xpath->query($query) as $node) {
                $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                if ($text !== '' && strlen($text) >= 4 && strlen($text) <= 180) {
                    $names[] = $text;
                }
            }
            if (count($names) >= 20) {
                break;
            }
        }

        return array_values(array_unique($names));
    }

    protected function filter_links(array $links, $type)
    {
        $patterns = [
            'category' => '/(categoria|category|cat=|familia|marca|brand)/i',
            'product' => '/(producto|product|item|sku|modelo|toner|cartucho)/i',
            'pagination' => '/(page=|paged=|pagina|p=|\/page\/)/i',
        ];

        $items = [];
        foreach ($links as $link) {
            if (preg_match($patterns[$type], $link)) {
                $items[] = $link;
            }
        }

        return array_values(array_unique($items));
    }

    protected function normalize_url($url, $base_url = '')
    {
        $url = trim((string) $url);
        if ($url === '' || strpos($url, 'javascript:') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) {
            return '';
        }

        if (strpos($url, '//') === 0) {
            $url = 'https:'.$url;
        } elseif (strpos($url, '/') === 0) {
            $url = 'https://'.$this->host.$url;
        } elseif (!preg_match('/^https?:\/\//i', $url)) {
            $base = $base_url ?: $this->base_url;
            $url = rtrim(dirname($base), '/').'/'.$url;
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        $normalized = strtolower($parts['scheme']).'://'.$parts['host'];
        $normalized .= isset($parts['path']) ? $parts['path'] : '/';
        if (!empty($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        return $normalized;
    }

    protected function same_host($url)
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST)) === $this->host;
    }

    protected function merge_unique(array $a, array $b)
    {
        return array_values(array_unique(array_merge($a, $b)));
    }
}
