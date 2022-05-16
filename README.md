# WooCommerce Najniža cijena u zadnjih 30 dana

Prema izmjenama zakona o zaštiti potrošača koje nastupaju na snagu 28.5.2022. prilikom akcija je potrebno iskazivati najnižu cijenu u zadnjih 30 dana za određeni proizvod.

Postoje i tumačenje da postoji izuzeće za prodaju na daljinu, ali još uvijek nije potvrđeno.

**Ranije su web trgovine za vrijeme akcija prikazivale:**

- Redovnu cijneu
- Sniženu cijenu

**Sada trebaju prikazivati:**

- Najnižu cijenu u zadnjih 30 dana
- Sniženu cijenu


## Kako radi plugin?

Prilikom svake promjene cijene zapisuje se aktualna cijena u zasebnu tablicu zajedno s trajanjem od-do. Prilikom prikaza cijene za proizvode na akciji, dohvaća se najniža cijena u zadnjih 30 dana.

**Važno:** plugin ne zna za povijest cijena pa će se povijest izmjena početi stvarati nakon prvih updateova. Kao najnižu cijenu u zadnjih 30 dana, ukoliko ne postoji niti jedna druga cijena, uzima se redovna cijena.

Plugin funkcionira za:

- Jednostavne proizvode
- Varijabilne proizvode

## Prikaz na listingu

![alt text](https://api.applause.hr/lowest-price/listing.png "Listing")

## Prikaz na single proizvodu
![alt text](https://api.applause.hr/lowest-price/single.png "Single")

## Bugovi

Ukoliko naiđete na bugove, javite na hello@applause.hr.

Testovi su rađeni na clean instalaciji:

- WordPress 5.9.3
- WooCommerce 6.5.1
- Storefront 4.1.0
- PHP 7.4 i 8.0

ali ne bi trebalo biti problema niti s drugim verzijama i temama.

Ukoliko koristite specifične pluginove poput Subscriptiona ili customizirane teme, postoji mogućnost da ćete trebati prilagoditi plugin.
