# HSV Wedstrijden (plugin) – v1.4.0

Agenda (wedstrijden, trainingen, vergaderingen), bezetting van locaties, startlijsten en uitslagen voor HSV De Paardenvriend. Staat los van het thema: bij een themawissel blijft alle inhoud bewaard.

## Datamodel

| Onderdeel | Wat | Adres |
|---|---|---|
| **Agenda-item** (`hsv_wedstrijd`) | Wedstrijd, training, vergadering of activiteit: datum(s), begin-/eindtijd, voor wie, externe locatie, sluitdatum, inschrijflink of -mail, live-uitslagen, flyer (= uitgelichte afbeelding), vinkje *Intern* | `/agenda/`, `/agenda/<naam>/` |
| **Locatie** | Grote hal, Buitenparcours, Kantine (uit te breiden, met volgorde). Een item kan meerdere locaties bezetten | `/agenda/?weergave=bezetting` |
| **Startlijst** (`hsv_startlijst`) | Hoort bij één wedstrijd. Bestand (PDF/Excel), externe link, tekst/tabel, meerdere afbeeldingen | `/startlijsten/<naam>/` |
| **Uitslag** (`hsv_uitslag`) | Zelfde opbouw als een startlijst | `/uitslagen/` (archief per jaar), `/uitslagen/<naam>/` |
| **Discipline** | Springen, Dressuur, Clubactiviteit; met kleur | filter in de agenda |
| **Soort** | Oefenwedstrijd, KNHS-wedstrijd, Kampioenschap, Training, Vergadering; met kleur (gaat voor discipline) en vinkje *Telt als wedstrijd* (homepage, uitslagen) | filter in de agenda |

Een wedstrijd kan **meerdere** startlijsten en uitslagen hebben (bijv. "Zaterdag – paarden" en "Zondag – pony's"). Volgorde via *Volgorde* (Pagina-attributen).

## Locaties en bezetting

- Kies bij een agenda-item rechts de **locatie(s)** en vul **begin- en eindtijd** in. Zonder tijd is de locatie de hele dag bezet.
- Staat er op dat moment al iets op dezelfde locatie, dan toont het blok **Bezetting locaties** meteen een waarschuwing (ook live tijdens het invullen). In het overzicht staat bij zo'n item "dubbel geboekt".
- **Intern** (bijv. bestuursvergadering): niet in de openbare agenda, iCal of sitemap en de pagina geeft een 404, maar in het bezettingsoverzicht staat de locatie als **Bezet**. Ingelogde beheerders zien de titel.
- Weekoverzicht per locatie: `/agenda/?weergave=bezetting&week=2026-11-23`.

## Werkwijze voor het bestuur

1. **Agenda → Nieuwe wedstrijd**: titel, gegevens, discipline + soort, eventueel flyer. Publiceren.
2. Uitslag of startlijst bekend? Open de wedstrijd en klik rechts op **+ Uitslag** of **+ Startlijst**. De koppeling en een titel staan al klaar; voeg een bestand, link en/of foto's toe.
3. De wedstrijdpagina toont alles automatisch. Na de einddatum verhuist de wedstrijd naar het uitslagenarchief.

Knoppen en status gaan vanzelf: *Inschrijving open* → *Inschrijving gesloten* (na sluitdatum) → *Nu bezig* + *Live uitslagen* (tijdens de wedstrijd) → *Uitslagen*.

## Permalinks met datum

Agenda-items krijgen automatisch de begindatum in hun adres: `/agenda/springclinic-2026-11-23/` (twee op dezelfde dag: `…-2`). Verandert de datum of titel, dan verhuist het adres mee en stuurt WordPress het oude adres door. Een zelf ingevulde slug die niet op de titel lijkt, blijft staan. Bij de update naar 1.4.0 krijgen bestaande items eenmalig zo'n adres.

## Agenda op de site

- Standaard de **maandweergave** met [FullCalendar](https://fullcalendar.io) 6 (MIT, lokaal meegeleverd in `assets/vendor/`), net als de LAT-app. Activiteiten staan als blokjes met titel in de dagvakken, ook op mobiel; meerdaagse wedstrijden lopen door. Tik/klik op een dag (op mobiel ook op een blokje) voor het dagoverzicht onder de kalender. Zonder JavaScript valt hij terug op een HTML-maandweergave.
- Data via REST: `/wp-json/hsv/v1/agenda?start=2026-11-01&end=2026-12-01&discipline=&soort=&locatie=` (alleen openbare items).
- Standaardweergave aanpassen met filter `hsv_default_view` (`maand`, `lijst`, `bezetting`); FullCalendar uitzetten met `add_filter( 'hsv_use_fullcalendar', '__return_false' )`.
- Lijst-, maand- en bezettingsweergave, filters: `/agenda/?weergave=maand&maand=2026-11&discipline=springen&soort=knhs-wedstrijd&locatie=grote-hal`
- iCal-abonnement voor leden: `/agenda/?ical=1`, per wedstrijd: `<wedstrijd-url>?ical=1`
- Schema.org `SportsEvent` op wedstrijdpagina's (Google)

## Shortcodes

- `[hsv_agenda aantal="5" discipline="springen" soort="oefenwedstrijd" locatie="grote-hal" wedstrijden="ja"]` – komende agenda-items (`wedstrijden="ja"`: alleen wedstrijden)
- `[hsv_uitslagen aantal="3"]` – laatste wedstrijden met uitslag

## Voor het thema

- Templates overschrijven: kopieer een bestand uit `templates/` naar `<thema>/hsv-wedstrijden/`.
- Helpers: `hsv_get_wedstrijden()` (o.a. `only_wedstrijden`, `locatie`, `intern`), `hsv_get_range()`, `hsv_conflicts()`, `hsv_get_locaties()`, `hsv_wedstrijd()`, `hsv_get_lists()`, `hsv_list_item()`, `hsv_template()`, `hsv_ical_url()`.
- Filters: `hsv_default_location` (standaardlocatie), `hsv_agenda_intro` (tekst boven de agenda). Actie: `hsv_wedstrijd_sidebar`.
- Opmaak: CSS-variabelen op `.hsv` (`--hsv-primary`, `--hsv-accent`, `--hsv-font-ui`, …).
