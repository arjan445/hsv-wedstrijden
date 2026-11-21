# HSV Wedstrijden (plugin) – v1.0.0

Agenda, wedstrijden, startlijsten en uitslagen voor HSV De Paardenvriend. Staat los van het thema: bij een themawissel blijft alle inhoud bewaard.

## Datamodel

| Onderdeel | Wat | Adres |
|---|---|---|
| **Wedstrijd** (`hsv_wedstrijd`) | Evenement in de agenda: datum(s), tijd, voor wie, locatie, sluitdatum, inschrijflink of -mail, live-uitslagen, flyer (= uitgelichte afbeelding) | `/agenda/`, `/agenda/<naam>/` |
| **Startlijst** (`hsv_startlijst`) | Hoort bij één wedstrijd. Bestand (PDF/Excel), externe link, tekst/tabel, meerdere afbeeldingen | `/startlijsten/<naam>/` |
| **Uitslag** (`hsv_uitslag`) | Zelfde opbouw als een startlijst | `/uitslagen/` (archief per jaar), `/uitslagen/<naam>/` |
| **Discipline** | Springen, Dressuur, Clubactiviteit; met kleur | filter in de agenda |
| **Soort** | Oefenwedstrijd, KNHS-wedstrijd, Kampioenschap, Training; met kleur (gaat voor discipline) | filter in de agenda |

Een wedstrijd kan **meerdere** startlijsten en uitslagen hebben (bijv. "Zaterdag – paarden" en "Zondag – pony's"). Volgorde via *Volgorde* (Pagina-attributen).

## Werkwijze voor het bestuur

1. **Agenda → Nieuwe wedstrijd**: titel, gegevens, discipline + soort, eventueel flyer. Publiceren.
2. Uitslag of startlijst bekend? Open de wedstrijd en klik rechts op **+ Uitslag** of **+ Startlijst**. De koppeling en een titel staan al klaar; voeg een bestand, link en/of foto's toe.
3. De wedstrijdpagina toont alles automatisch. Na de einddatum verhuist de wedstrijd naar het uitslagenarchief.

Knoppen en status gaan vanzelf: *Inschrijving open* → *Inschrijving gesloten* (na sluitdatum) → *Nu bezig* + *Live uitslagen* (tijdens de wedstrijd) → *Uitslagen*.

## Agenda op de site

- Lijst- en maandweergave, filters: `/agenda/?weergave=maand&maand=2026-11&discipline=springen&soort=knhs-wedstrijd`
- iCal-abonnement voor leden: `/agenda/?ical=1`, per wedstrijd: `<wedstrijd-url>?ical=1`
- Schema.org `SportsEvent` op wedstrijdpagina's (Google)

## Shortcodes

- `[hsv_agenda aantal="5" discipline="springen" soort="oefenwedstrijd"]` – komende wedstrijden
- `[hsv_uitslagen aantal="3"]` – laatste wedstrijden met uitslag

## Voor het thema

- Templates overschrijven: kopieer een bestand uit `templates/` naar `<thema>/hsv-wedstrijden/`.
- Helpers: `hsv_get_wedstrijden()`, `hsv_wedstrijd()`, `hsv_get_lists()`, `hsv_list_item()`, `hsv_template()`, `hsv_ical_url()`.
- Filters: `hsv_default_location` (standaardlocatie), `hsv_agenda_intro` (tekst boven de agenda). Actie: `hsv_wedstrijd_sidebar`.
- Opmaak: CSS-variabelen op `.hsv` (`--hsv-primary`, `--hsv-accent`, `--hsv-font-ui`, …).
