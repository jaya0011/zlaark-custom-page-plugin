<?php
namespace Custom_Page_Builder;

/**
 * Class Polylang_Integrator
 * 
 * Handles integration with Polylang, WPML, and other translation plugins for multi-language support.
 * Automatically detects the current language and provides fallback to English if page doesn't exist.
 */
class Polylang_Integrator {

    /**
     * Fallback language code (English)
     */
    const FALLBACK_LANGUAGE = 'en';

    /**
     * Check if Polylang is active
     *
     * @return bool
     */
    public static function is_polylang_active() {
        return function_exists('pll_languages_list') && function_exists('pll_default_language');
    }

    /**
     * Check if WPML is active
     *
     * @return bool
     */
    public static function is_wpml_active() {
        return defined('ICL_SITEPRESS_VERSION') && function_exists('icl_get_languages');
    }

    /**
     * Check if any translation plugin is active
     *
     * @return bool
     */
    public static function is_active() {
        return self::is_polylang_active() || self::is_wpml_active();
    }

    /**
     * Get the default/fallback language - Always English
     *
     * @return string Language code (always 'en')
     */
    public static function get_default_language() {
        // Always return English as the default language
        return self::FALLBACK_LANGUAGE;
    }

    /**
     * Get all configured languages
     *
     * @return array Array of language objects with 'slug', 'name', 'flag' properties
     */
    public static function get_languages() {
        $languages = [];

        // Try Polylang
        if (self::is_polylang_active()) {
            $pll_languages = pll_languages_list(['fields' => false]);
            
            foreach ($pll_languages as $lang) {
                $languages[] = [
                    'slug' => $lang->slug,
                    'name' => $lang->name,
                    'flag' => $lang->flag_url ?? '',
                    'locale' => $lang->locale ?? ''
                ];
            }
            return $languages;
        }

        // Try WPML
        if (self::is_wpml_active()) {
            $wpml_languages = icl_get_languages('skip_missing=0');
            
            foreach ($wpml_languages as $code => $lang) {
                $languages[] = [
                    'slug' => $code,
                    'name' => $lang['native_name'] ?? $lang['translated_name'] ?? $code,
                    'flag' => $lang['country_flag_url'] ?? '',
                    'locale' => $lang['default_locale'] ?? ''
                ];
            }
            return $languages;
        }

        return $languages;
    }

    /**
     * Get available languages for a specific page (languages that have this page)
     *
     * @param string $slug Page slug
     * @return array Array of language codes that have this page
     */
    public static function get_available_languages_for_page($slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';

        $languages = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT language FROM $table_name WHERE slug = %s AND status = 'published'",
            $slug
        ));

        return $languages ?: [];
    }

    /**
     * Check if a page exists in a specific language
     *
     * @param string $slug Page slug
     * @param string $lang Language code
     * @return bool
     */
    public static function page_exists_in_language($slug, $lang) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE slug = %s AND language = %s AND status = 'published'",
            $slug,
            $lang
        ));

        return intval($exists) > 0;
    }

    /**
     * Get current language from multiple sources with automatic detection
     *
     * Priority:
     * 1. Explicit 'lang' parameter in request
     * 2. Polylang current language
     * 3. WPML current language
     * 4. URL language prefix detection
     * 5. Browser Accept-Language header
     * 6. Default/fallback language (English)
     *
     * @param \WP_REST_Request|null $request
     * @return string Language code
     */
    public static function get_current_language($request = null) {
        // 1. Check if lang parameter is provided in request
        if ($request && isset($request['lang'])) {
            $lang = sanitize_text_field($request['lang']);
            error_log('CPB Language: Using explicit lang parameter: ' . $lang);
            return $lang;
        }

        // 2. Check Polylang current language
        if (self::is_polylang_active() && function_exists('pll_current_language')) {
            $current = pll_current_language();
            if ($current) {
                error_log('CPB Language: Using Polylang current language: ' . $current);
                return $current;
            }
        }

        // 3. Check WPML current language
        if (self::is_wpml_active()) {
            $current = apply_filters('wpml_current_language', null);
            if ($current && $current !== 'all') {
                error_log('CPB Language: Using WPML current language: ' . $current);
                return $current;
            }
        }

        // 4. Try to detect from URL (common patterns)
        $detected = self::detect_language_from_url();
        if ($detected) {
            error_log('CPB Language: Detected from URL: ' . $detected);
            return $detected;
        }

        // 5. Try browser Accept-Language header (for API requests)
        $browser_lang = self::detect_language_from_browser();
        if ($browser_lang) {
            error_log('CPB Language: Detected from browser: ' . $browser_lang);
            return $browser_lang;
        }

        // 6. Default to English
        $default = self::get_default_language();
        error_log('CPB Language: Using default language: ' . $default);
        return $default;
    }

    /**
     * Get the language for a page with automatic fallback
     *
     * This is the main method to use when fetching pages.
     * It will return the requested language if the page exists,
     * otherwise fall back to English.
     *
     * @param string $slug Page slug
     * @param \WP_REST_Request|null $request
     * @return array ['language' => string, 'is_fallback' => bool]
     */
    public static function get_language_with_fallback($slug, $request = null) {
        // Get the requested/detected language
        $requested_lang = self::get_current_language($request);
        
        // Check if page exists in requested language
        if (self::page_exists_in_language($slug, $requested_lang)) {
            return [
                'language' => $requested_lang,
                'is_fallback' => false,
                'requested_language' => $requested_lang
            ];
        }

        // Page doesn't exist in requested language - ALWAYS try English fallback first
        if (self::page_exists_in_language($slug, self::FALLBACK_LANGUAGE)) {
            error_log("CPB Language: Page '$slug' not found in '$requested_lang', falling back to English");
            return [
                'language' => self::FALLBACK_LANGUAGE,
                'is_fallback' => true,
                'requested_language' => $requested_lang
            ];
        }

        // If English doesn't exist either, try any available language for this page
        $available = self::get_available_languages_for_page($slug);
        if (!empty($available)) {
            // Prefer English if it's in the list
            if (in_array(self::FALLBACK_LANGUAGE, $available)) {
                return [
                    'language' => self::FALLBACK_LANGUAGE,
                    'is_fallback' => true,
                    'requested_language' => $requested_lang
                ];
            }
            // Otherwise use the first available language
            $fallback = $available[0];
            error_log("CPB Language: Page '$slug' using first available language: $fallback");
            return [
                'language' => $fallback,
                'is_fallback' => true,
                'requested_language' => $requested_lang
            ];
        }

        // No page found in any language - return English as default (will show 404)
        return [
            'language' => self::FALLBACK_LANGUAGE,
            'is_fallback' => false,
            'requested_language' => $requested_lang,
            'page_not_found' => true
        ];
    }

    /**
     * Detect language from URL patterns, cookies, and headers
     *
     * Supports common URL structures:
     * - /en/page-slug (Polylang, WPML)
     * - /page-slug?lang=en (query parameter)
     * - example.com/en/ (path prefix)
     * - en.example.com (subdomain)
     * - Cookies from translation plugins
     * - Custom header X-Language
     *
     * @return string|null Language code or null if not detected
     */
    public static function detect_language_from_url() {
        // 1. Check query parameter (highest priority for API calls)
        if (isset($_GET['lang'])) {
            $lang = sanitize_text_field($_GET['lang']);
            if (self::is_valid_language_code($lang)) {
                return self::normalize_language_code($lang);
            }
        }

        // 2. Check custom header (for React/frontend API calls)
        if (isset($_SERVER['HTTP_X_LANGUAGE'])) {
            $lang = sanitize_text_field($_SERVER['HTTP_X_LANGUAGE']);
            if (self::is_valid_language_code($lang)) {
                return self::normalize_language_code($lang);
            }
        }

        // 3. Check Polylang cookie
        if (isset($_COOKIE['pll_language'])) {
            $lang = sanitize_text_field($_COOKIE['pll_language']);
            if (self::is_valid_language_code($lang)) {
                return self::normalize_language_code($lang);
            }
        }

        // 4. Check WPML cookie
        if (isset($_COOKIE['wp-wpml_current_language'])) {
            $lang = sanitize_text_field($_COOKIE['wp-wpml_current_language']);
            if (self::is_valid_language_code($lang)) {
                return self::normalize_language_code($lang);
            }
        }

        // 5. Check TranslatePress cookie
        if (isset($_COOKIE['trp_language'])) {
            $lang = sanitize_text_field($_COOKIE['trp_language']);
            if (self::is_valid_language_code($lang)) {
                return self::normalize_language_code($lang);
            }
        }

        // 6. Check URL path for language prefix
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($request_uri, PHP_URL_PATH);
        
        if ($path) {
            // Match /en/ or /en-us/ or /en_US/ at the start of the path
            if (preg_match('#^/([a-z]{2}(?:[-_][a-z]{2})?)(?:/|$)#i', $path, $matches)) {
                $lang = strtolower($matches[1]);
                if (self::is_valid_language_code($lang)) {
                    return self::normalize_language_code($lang);
                }
            }
        }

        // 7. Check HTTP Referer for language (useful for API calls from frontend)
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $referer_path = parse_url($referer, PHP_URL_PATH);
            if ($referer_path && preg_match('#^/([a-z]{2}(?:[-_][a-z]{2})?)(?:/|$)#i', $referer_path, $matches)) {
                $lang = strtolower($matches[1]);
                if (self::is_valid_language_code($lang)) {
                    return self::normalize_language_code($lang);
                }
            }
            // Also check referer query string
            $referer_query = parse_url($referer, PHP_URL_QUERY);
            if ($referer_query) {
                parse_str($referer_query, $query_params);
                if (isset($query_params['lang']) && self::is_valid_language_code($query_params['lang'])) {
                    return self::normalize_language_code($query_params['lang']);
                }
            }
        }

        // 8. Check subdomain for language
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        if ($host && preg_match('#^([a-z]{2})\.#i', $host, $matches)) {
            $lang = strtolower($matches[1]);
            if (self::is_valid_language_code($lang)) {
                return self::normalize_language_code($lang);
            }
        }

        return null;
    }

    /**
     * Normalize language code to standard format
     * Converts en_US, en-US to just 'en'
     *
     * @param string $code Language code
     * @return string Normalized language code
     */
    public static function normalize_language_code($code) {
        $code = strtolower(trim($code));
        
        // Map common language codes to their base
        $language_map = [
            'en-us' => 'en',
            'en_us' => 'en',
            'en-gb' => 'en',
            'en_gb' => 'en',
            'fr-fr' => 'fr',
            'fr_fr' => 'fr',
            'de-de' => 'de',
            'de_de' => 'de',
            'it-it' => 'it',
            'it_it' => 'it',
            'ja-jp' => 'ja',
            'ja_jp' => 'ja',
            'nl-nl' => 'nl',
            'nl_nl' => 'nl',
            'es-es' => 'es',
            'es_es' => 'es',
            'pt-br' => 'pt',
            'pt_br' => 'pt',
            'zh-cn' => 'zh',
            'zh_cn' => 'zh',
        ];
        
        if (isset($language_map[$code])) {
            return $language_map[$code];
        }
        
        // If code contains separator, take only first part
        if (strpos($code, '-') !== false) {
            return substr($code, 0, 2);
        }
        if (strpos($code, '_') !== false) {
            return substr($code, 0, 2);
        }
        
        return $code;
    }

    /**
     * Detect language from browser Accept-Language header
     *
     * @return string|null Language code or null if not detected
     */
    public static function detect_language_from_browser() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // Parse Accept-Language header (e.g., "en-US,en;q=0.9,es;q=0.8")
        $languages = [];
        foreach (explode(',', $accept_lang) as $part) {
            $part = trim($part);
            $q = 1.0;
            
            if (strpos($part, ';q=') !== false) {
                list($part, $q) = explode(';q=', $part);
                $q = floatval($q);
            }
            
            // Get the language code (first 2 characters)
            $lang = strtolower(substr(trim($part), 0, 2));
            
            if (self::is_valid_language_code($lang)) {
                $languages[$lang] = $q;
            }
        }

        // Sort by quality and return the best match
        if (!empty($languages)) {
            arsort($languages);
            return array_key_first($languages);
        }

        return null;
    }

    /**
     * Check if a language code is valid (basic validation)
     *
     * @param string $code Language code
     * @return bool
     */
    public static function is_valid_language_code($code) {
        // Basic validation: 2-5 character language code
        return preg_match('/^[a-z]{2}(-[a-z]{2})?$/i', $code);
    }

    /**
     * Get translation ID for a page in a specific language
     *
     * @param int $page_id Original page ID
     * @param string $lang Target language code
     * @return int|null Translation page ID or null if not found
     */
    public static function get_translation_id($page_id, $lang) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';

        // Get the translation group of the original page
        $original = $wpdb->get_row($wpdb->prepare(
            "SELECT translation_group, slug FROM $table_name WHERE id = %d",
            $page_id
        ));

        if (!$original) {
            return null;
        }

        // If no translation group, this page is the source
        $translation_group = $original->translation_group ?: $page_id;

        // Find the translation in the target language
        $translation = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE (translation_group = %d OR id = %d) AND language = %s",
            $translation_group,
            $translation_group,
            $lang
        ));

        return $translation ? intval($translation) : null;
    }

    /**
     * Get all translations for a page
     *
     * @param int $page_id Page ID
     * @return array Array of translations with language => id mapping
     */
    public static function get_translations($page_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';

        // Get the current page
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT id, language, translation_group, slug FROM $table_name WHERE id = %d",
            $page_id
        ));

        if (!$page) {
            return [];
        }

        // Determine the translation group
        $translation_group = $page->translation_group ?: $page->id;

        // Get all pages in this translation group
        $translations = $wpdb->get_results($wpdb->prepare(
            "SELECT id, language, title FROM $table_name WHERE translation_group = %d OR id = %d",
            $translation_group,
            $translation_group
        ));

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->language] = [
                'id' => intval($translation->id),
                'title' => $translation->title
            ];
        }

        return $result;
    }

    /**
     * Link a page as a translation of another page
     *
     * @param int $page_id The translation page ID
     * @param int $original_id The original page ID
     * @return bool Success
     */
    public static function link_translation($page_id, $original_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_pages';

        // Get the original page's translation group
        $original = $wpdb->get_row($wpdb->prepare(
            "SELECT id, translation_group FROM $table_name WHERE id = %d",
            $original_id
        ));

        if (!$original) {
            return false;
        }

        // The translation group is either the existing group or the original's ID
        $translation_group = $original->translation_group ?: $original->id;

        // Update the translation to be part of this group
        $result = $wpdb->update(
            $table_name,
            ['translation_group' => $translation_group],
            ['id' => $page_id]
        );

        return $result !== false;
    }
}
