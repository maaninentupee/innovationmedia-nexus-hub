<?php
/**
 * Teeman suorituskyvyn monitorointi
 *
 * @package TonysTheme
 */

class TonysTheme_Performance_Monitor {
    /**
     * Suorituskykymittarit
     */
    private static $metrics = [];
    
    /**
     * Alusta monitorointi
     */
    public static function init() {
        // Aloita sivun latauksen mittaus
        add_action('template_redirect', [__CLASS__, 'start_page_load_tracking']);
        
        // Mittaa sivun latauksen loppu
        add_action('shutdown', [__CLASS__, 'end_page_load_tracking']);
        
        // Seuraa muistin käyttöä
        add_action('template_redirect', [__CLASS__, 'track_memory_usage']);
        
        // Seuraa tietokantakyselyitä
        add_action('template_redirect', [__CLASS__, 'track_database_queries']);
        
        // Lisää admin-palkkin suorituskykyindikaattori
        add_action('admin_bar_menu', [__CLASS__, 'add_performance_indicator'], 999);
        
        // Tallenna metriikat
        add_action('shutdown', [__CLASS__, 'save_metrics']);
    }

    /**
     * Aloita sivun latauksen mittaus
     */
    public static function start_page_load_tracking() {
        self::$metrics['page_load'] = [
            'start' => microtime(true),
            'peak_memory' => 0,
            'db_queries' => 0
        ];
    }

    /**
     * Lopeta sivun latauksen mittaus
     */
    public static function end_page_load_tracking() {
        if (isset(self::$metrics['page_load']['start'])) {
            self::$metrics['page_load']['duration'] = microtime(true) - self::$metrics['page_load']['start'];
        }
    }

    /**
     * Seuraa muistin käyttöä
     */
    public static function track_memory_usage() {
        self::$metrics['page_load']['peak_memory'] = memory_get_peak_usage(true);
    }

    /**
     * Seuraa tietokantakyselyitä
     */
    public static function track_database_queries() {
        global $wpdb;
        
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            add_action('shutdown', function() use ($wpdb) {
                self::$metrics['page_load']['db_queries'] = count($wpdb->queries);
                self::$metrics['queries'] = $wpdb->queries;
            });
        }
    }

    /**
     * Lisää suorituskykyindikaattori admin-palkkiin
     */
    public static function add_performance_indicator($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Odota että metriikat on kerätty
        if (!isset(self::$metrics['page_load']['duration'])) {
            return;
        }

        $duration = round(self::$metrics['page_load']['duration'] * 1000, 2); // ms
        $memory = size_format(self::$metrics['page_load']['peak_memory'], 2);
        $queries = isset(self::$metrics['page_load']['db_queries']) 
            ? self::$metrics['page_load']['db_queries'] 
            : '?';

        $wp_admin_bar->add_node([
            'id'    => 'performance-metrics',
            'title' => sprintf(
                __('Lataus: %sms | Muisti: %s | Kyselyt: %s', 'tonys-theme'),
                $duration,
                $memory,
                $queries
            ),
            'href'  => admin_url('themes.php?page=tonys-theme-performance')
        ]);
    }

    /**
     * Tallenna metriikat
     */
    public static function save_metrics() {
        if (empty(self::$metrics)) {
            return;
        }

        $metrics_history = get_option('tonys_theme_performance_metrics', []);
        
        // Säilytä vain viimeisimmät 100 mittausta
        if (count($metrics_history) >= 100) {
            array_shift($metrics_history);
        }
        
        // Lisää uusi mittaus
        $metrics_history[] = [
            'timestamp' => current_time('mysql'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'metrics' => self::$metrics
        ];
        
        update_option('tonys_theme_performance_metrics', $metrics_history);
    }

    /**
     * Hae suorituskykymetriikat
     */
    public static function get_metrics($limit = 100) {
        return get_option('tonys_theme_performance_metrics', []);
    }

    /**
     * Laske keskimääräiset metriikat
     */
    public static function calculate_averages($metrics) {
        if (empty($metrics)) {
            return [];
        }

        $totals = [
            'duration' => 0,
            'memory' => 0,
            'queries' => 0
        ];
        
        $count = count($metrics);
        
        foreach ($metrics as $metric) {
            if (isset($metric['metrics']['page_load'])) {
                $totals['duration'] += $metric['metrics']['page_load']['duration'] ?? 0;
                $totals['memory'] += $metric['metrics']['page_load']['peak_memory'] ?? 0;
                $totals['queries'] += $metric['metrics']['page_load']['db_queries'] ?? 0;
            }
        }
        
        return [
            'avg_duration' => round(($totals['duration'] / $count) * 1000, 2), // ms
            'avg_memory' => size_format($totals['memory'] / $count, 2),
            'avg_queries' => round($totals['queries'] / $count, 1)
        ];
    }
}

// Alusta suorituskyvyn monitorointi
TonysTheme_Performance_Monitor::init();
