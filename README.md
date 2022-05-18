# WooCommerce - Najniža cijena u zadnjih 30 dana

Prema izmjenama zakona o zaštiti potrošača koje nastupaju na snagu 28.5.2022. prilikom akcija je potrebno iskazivati najnižu cijenu u zadnjih 30 dana za određeni proizvod.

Postoje i tumačenje da postoji izuzeće za prodaju na daljinu, ali još uvijek nije potvrđeno.

**Ranije su web trgovine za vrijeme akcija prikazivale:**

- Redovnu cijenu
- Sniženu cijenu

**Sada trebaju prikazivati:**

- Najnižu cijenu u zadnjih 30 dana
- Sniženu cijenu

**Važno:** ovisni o promjenama cijene, može se dogoditi da prekrižena cijena (tj. najniža u zadnjih 30 dana) bude ista ili niža od aktualne. U slučaju pogrešno unešene cijene, potrebno je obrisati redak u wp_price_history tablici.


## Kako radi plugin?

Prilikom svake promjene cijene zapisuje se aktualna cijena u zasebnu tablicu zajedno s trajanjem od-do. Osim toga, prilikom promjene cijene u postmeta tablicu zapisuje se najniža cijena koja je vrijedila za taj proizvod u zadnjih 30 dana.

Prilikom prikaza cijene za proizvode na akciji, dohvaća se najniža cijena koja se primjenjivala tijekom razdoblja od 30 dana prije provedbe akcije.

**Važno:** plugin ne zna za povijest cijena pa će se povijest izmjena početi stvarati nakon što se proizvodi / varijante ažuriraju prvi put. Kao najnižu cijenu u zadnjih 30 dana, ukoliko ne postoji niti jedna druga cijena, uzima se redovna cijena.

Plugin funkcionira za:

- Jednostavne proizvode
- Varijabilne proizvode


## Defaultni prikaz

![alt text](https://api.applause.hr/lowest-price/01-regular-listing.png "Listing")

![alt text](https://api.applause.hr/lowest-price/02-regular-single.png "Single")

## Tekstualni prikaz

U wp-config.php dodati liniju:
```
define( 'WPLP_DISPLAY_TYPE', 'text' );
```

![alt text](https://api.applause.hr/lowest-price/03-text-listing.png "Listing")

![alt text](https://api.applause.hr/lowest-price/04-text-single.png "Single")

## Alternativni prikaz

U wp-config.php dodati liniju:
```
define( 'WPLP_DISPLAY_TYPE', 'alt' );
```

![alt text](https://api.applause.hr/lowest-price/05-alt-single.png "Single")

## Bugovi

Ukoliko naiđete na bugove, javite na hello@applause.hr.

Testovi su rađeni na clean instalaciji:

- WordPress 5.9.3
- WooCommerce 6.5.1
- Storefront 4.1.0
- PHP 7.4 i 8.0

ali ne bi trebalo biti problema niti s drugim verzijama i temama.

Također je testirana i promjena cijena putem crona. Utjecaj na performanse trebao bi biti zanemariv.

Ukoliko koristite specifične pluginove poput Subscriptiona ili customizirane teme, postoji mogućnost da ćete trebati prilagoditi plugin. Također, potrebno je pripaziti ukoliko cijene updateate izravno kroz bazu (npr. prilikom spajanja na vanjski ERP).
