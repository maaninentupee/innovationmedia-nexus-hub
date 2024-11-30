module.exports = {
    extends: 'lighthouse:default',
    settings: {
        // Suorita testit mobiilina ja työpöytänä
        emulatedFormFactor: 'mobile',
        
        // Testaa seuraavat kategoriat
        onlyCategories: [
            'performance',
            'accessibility',
            'best-practices',
            'seo',
            'pwa'
        ],
        
        // Aseta suorituskyvyn raja-arvot
        scores: {
            performance: 90,
            accessibility: 90,
            'best-practices': 90,
            seo: 90,
            pwa: 90
        },
        
        // Määritä throttling
        throttling: {
            rttMs: 150,
            throughputKbps: 1638.4,
            cpuSlowdownMultiplier: 4
        },
        
        // Määritä viewport
        screenEmulation: {
            mobile: true,
            width: 360,
            height: 640,
            deviceScaleFactor: 2,
            disabled: false
        }
    }
};
