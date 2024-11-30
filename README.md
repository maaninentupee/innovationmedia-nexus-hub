# InnovationMedia Nexus Hub WordPress Theme

Moderni ja suorituskykyinen WordPress-teema InnovationMedia Nexus Hub -sivustolle.

## Ominaisuudet

### Suorituskyky
- Automaattinen kuvien optimointi ja WebP-muunnos
- Kriittisen CSS:n erottelu
- HTTP/2 Server Push -tuki
- Service Worker offline-tuelle
- Välimuistin hallinta staattisille resursseille

### Käyttökokemus
- Responsiivinen suunnittelu
- Mukautettu mobiilivalikko
- Reaaliaikainen lomakkeiden validointi
- Latausanimaatiot
- Monikielisyystuki

### Tietoturva
- Two-Factor Authentication (2FA) -tuki
- Vahva salasanan tarkistus
- CSRF-suojaus
- Rate limiting lomakkeille
- Automaattinen lokien hallinta ja arkistointi

### Affiliate-ominaisuudet
- Mukautettu affiliate-tuotteiden hallinta
- Automaattinen affiliate-linkkien käsittely
- Schema.org -merkinnät tuotteille

## Asennus

1. Lataa teema WordPress-asennuksesi `wp-content/themes`-kansioon
2. Aktivoi teema WordPress-hallintapaneelista
3. Asenna tarvittavat lisäosat:
   - Google Authenticator (2FA-tuki)
   - WP Super Cache (välimuistin hallinta)

## Konfigurointi

### Two-Factor Authentication
1. Siirry käyttäjäprofiiliin
2. Aktivoi 2FA skannaamalla QR-koodi
3. Vahvista aktivointi syöttämällä koodi

### Suorituskykyoptimointien käyttöönotto
1. Siirry Asetukset > Suorituskyky
2. Valitse käytettävät optimoinnit:
   - Kuvien automaattinen optimointi
   - Kriittisen CSS:n generointi
   - Service Worker -tuki

### Affiliate-ominaisuudet
1. Siirry Artikkelit > Affiliate-tuotteet
2. Lisää uusi tuote täyttämällä tarvittavat tiedot
3. Käytä shortcodea `[product_rating id="123"]` näyttääksesi tuotteen arvostelun

## Kehitys

### Vaatimukset
- PHP 7.4 tai uudempi
- WordPress 5.9 tai uudempi
- Node.js ja npm (front-end-kehitykseen)

### Kehitysympäristön pystytys
1. Kloonaa repo: `git clone [repo-url]`
2. Asenna riippuvuudet: `composer install && npm install`
3. Käynnistä kehitysympäristö: `npm run dev`

### Testaus
Suorita testit komennolla: `composer test`

## Tuki ja ylläpito

### Lokien hallinta
- Lokit tallennetaan kansioon `wp-content/logs/tonys-theme/`
- Lokit arkistoidaan automaattisesti 30 päivän välein
- Kriittiset virheet lähetetään sähköpostilla ylläpitäjälle

### Suorituskykytestit
1. Suorita Lighthouse-testi: `npm run lighthouse`
2. Tarkista tulokset kansiosta `tests/lighthouse-results/`

## Lisenssi

GNU General Public License v2 tai uudempi

## Tekijät

Kehittäjä: Tony
Versio: 1.0.0
