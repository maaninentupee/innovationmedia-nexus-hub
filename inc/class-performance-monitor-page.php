<?php
/**
 * Suorituskyvyn monitoroinnin hallintasivu
 *
 * @package TonysTheme
 */

class TonysTheme_Performance_Monitor_Page {
    /**
     * Alusta hallintasivu
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_performance_page']);
    }

    /**
     * Lisää suorituskykysivu hallintapaneeliin
     */
    public static function add_performance_page() {
        add_theme_page(
            __('Suorituskyky', 'tonys-theme'),
            __('Suorituskyky', 'tonys-theme'),
            'manage_options',
            'tonys-theme-performance',
            [__CLASS__, 'render_performance_page']
        );
    }

    /**
     * Renderöi suorituskykysivu
     */
    public static function render_performance_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Hae metriikat
        $metrics = TonysTheme_Performance_Monitor::get_metrics();
        $averages = TonysTheme_Performance_Monitor::calculate_averages($metrics);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Keskiarvot -->
            <div class="card">
                <h2><?php esc_html_e('Keskimääräinen suorituskyky', 'tonys-theme'); ?></h2>
                <p>
                    <?php printf(
                        __('Latausaika: %s ms | Muistinkäyttö: %s | Tietokantakyselyt: %s', 'tonys-theme'),
                        '<strong>' . esc_html($averages['avg_duration']) . '</strong>',
                        '<strong>' . esc_html($averages['avg_memory']) . '</strong>',
                        '<strong>' . esc_html($averages['avg_queries']) . '</strong>'
                    ); ?>
                </p>
            </div>

            <!-- Viimeisimmät mittaukset -->
            <h2><?php esc_html_e('Viimeisimmät mittaukset', 'tonys-theme'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Aika', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('URL', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Latausaika', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Muistinkäyttö', 'tonys-theme'); ?></th>
                        <th><?php esc_html_e('Kyselyt', 'tonys-theme'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($metrics) as $metric): ?>
                        <tr>
                            <td><?php echo esc_html($metric['timestamp']); ?></td>
                            <td><?php echo esc_html($metric['url']); ?></td>
                            <td><?php 
                                echo isset($metric['metrics']['page_load']['duration']) 
                                    ? esc_html(round($metric['metrics']['page_load']['duration'] * 1000, 2)) . ' ms'
                                    : '-';
                            ?></td>
                            <td><?php 
                                echo isset($metric['metrics']['page_load']['peak_memory'])
                                    ? esc_html(size_format($metric['metrics']['page_load']['peak_memory']))
                                    : '-';
                            ?></td>
                            <td><?php 
                                echo isset($metric['metrics']['page_load']['db_queries'])
                                    ? esc_html($metric['metrics']['page_load']['db_queries'])
                                    : '-';
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Ohjeita -->
            <div class="card">
                <h3><?php esc_html_e('Suorituskyvyn parantaminen', 'tonys-theme'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Pidä latausaika alle 2 sekunnissa (2000 ms)', 'tonys-theme'); ?></li>
                    <li><?php esc_html_e('Minimoi tietokantakyselyiden määrä välimuistin avulla', 'tonys-theme'); ?></li>
                    <li><?php esc_html_e('Optimoi kuvat ja käytä lazy loading -ominaisuutta', 'tonys-theme'); ?></li>
                    <li><?php esc_html_e('Hyödynnä selaimen välimuistia ja CDN-palveluita', 'tonys-theme'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// Alusta suorituskyvyn monitorointisivu
TonysTheme_Performance_Monitor_Page::init();
