# Osallistuminen TonysTheme-teeman kehitykseen

Kiitos kiinnostuksestasi osallistua TonysTheme-teeman kehitykseen! Tässä dokumentissa kerrotaan, miten voit osallistua projektiin.

## Kehitysympäristö

### Vaatimukset

- PHP 7.4 tai uudempi
- Composer
- Node.js ja npm
- WordPress-kehitysympäristö
- Git

### Asennus

1. Fork-projektista oma kopio GitHubissa
2. Kloonaa forkattu repositorio:
```bash
git clone https://github.com/sinun-username/tonys-theme.git
```
3. Asenna riippuvuudet:
```bash
composer install
npm install
```
4. Luo feature branch:
```bash
git checkout -b feature/ominaisuuden-nimi
```

## Koodaustyyli

Noudatamme WordPress Coding Standards -ohjeistusta:

- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

### Tarkistukset

Ennen pull requestin tekemistä:

1. Aja koodityylitarkistukset:
```bash
composer lint
```

2. Aja yksikkötestit:
```bash
composer test
```

3. Aja Lighthouse-testit:
```bash
npm run lighthouse
```

## Pull Requestit

1. Varmista että koodisi on testattu
2. Päivitä dokumentaatio tarvittaessa
3. Kirjoita selkeä kuvaus muutoksista
4. Viittaa mahdolliseen GitHub issueen

### PR:n rakenne

```markdown
## Kuvaus
Lyhyt kuvaus muutoksista

## Muutokset
- Muutos 1
- Muutos 2
- Muutos 3

## Testaus
Miten muutokset on testattu?

## Liittyvät issut
Fixes #123
```

## Testaus

### Yksikkötestit

- Kirjoita testit uusille ominaisuuksille
- Päivitä olemassa olevia testejä tarvittaessa
- Varmista että kaikki testit menevät läpi

### Suorituskykytestit

- Tarkista Lighthouse-pisteet
- Varmista että muutokset eivät hidasta sivustoa
- Optimoi resurssit tarvittaessa

## Dokumentaatio

- Päivitä PHPDoc-dokumentaatio PHP-koodille
- Päivitä JSDoc-dokumentaatio JavaScript-koodille
- Lisää käyttöohjeet README.md:hen tarvittaessa

## Versiointi

Käytämme [Semantic Versioning](https://semver.org/):ia:

- MAJOR.MINOR.PATCH
- Esim. 1.0.0

## Julkaisu

1. Päivitä version numero
2. Päivitä muutosloki
3. Tee pull request main-haaraan

## Tietoturva

Jos löydät tietoturvaongelman:

1. ÄLÄ raportoi sitä julkisesti
2. Lähetä sähköpostia: security@tony.fi
3. Odota vahvistusta

## Yhteisön ohjeet

- Ole kunnioittava muita kohtaan
- Kirjoita rakentavaa palautetta
- Auta muita kehittäjiä
- Seuraa WordPress-yhteisön ohjeita

## Lisenssi

Kaikki kontribuutiot julkaistaan GNU General Public License v2 tai uudemman alaisena.

## Yhteystiedot

- Sähköposti: dev@tony.fi
- Twitter: @tonydev
- GitHub: @tonydev

Kiitos osallistumisestasi!
