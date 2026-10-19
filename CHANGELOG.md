# Changelog

## 0.3.1

- Die Seite für die Schnellerfassung ist nur noch für angemeldete Benutzer: Besucher werden zur WordPress-Anmeldung geleitet und danach zurück. Die Seite ist für Suchmaschinen gesperrt (noindex).

## 0.3.0

- Neu: Reiter im Editor. Die Event-Angaben stehen direkt unter dem Titel, je ein Reiter für Wann, Wo, Online, Veranstalter, Tickets und Anzeige. Weniger Scrollen.
- Neu: Reiter "Anzeige" mit zwei Schaltern pro Event: "Bild auf der Event-Seite zeigen" und "Karte anzeigen (OpenStreetMap)".
- Neu: Die Karte braucht keine Koordinaten mehr von Hand. Die Position wird beim Speichern aus Ort und Adresse ermittelt (OpenStreetMap).
- Ohne Karte entfällt auch der Knopf "Karte öffnen".

## 0.2.0

- Neu: klarere Struktur. Felder in Gruppen (Wann, Wo, Online, Veranstalter, Tickets und Preis) im Editor und im Handy-Formular, "Weitere Angaben" einklappbar.
- Neu: Orte und Veranstalter als wiederverwendbare Einträge, mit Link zur Karte (OpenStreetMap) und optionaler eingebetteter Karte.
- Neu: Online-Link für Hybrid- und Online-Events.
- Neu: Events duplizieren (Admin-Liste und Handy).
- Neu: "Zum Kalender hinzufügen" (.ics und Google Kalender) und Schema-Markup für Google.
- Neu: Filter für Besucher mit `[mlab_events filters="yes"]`: Suche, Kategorie, Datum.
- Neu: "Meine Events" auf der Handy-Seite: ansehen, ändern, duplizieren, löschen.
- Neu: Admin-Liste mit Ansichten "Bevorstehend" und "Vergangene" und Spalte "Wo".
- Neu: CSV-Export unter Events > Export.
- Neu: Migration vom WP Event Manager unter Events > Migration, mit Vorabprüfung.
- Neu: Einstellungen in Bereiche gegliedert, Option für die eingebettete Karte.
- Neu: Kartenstil für Liste (mit Bild und Kategorien), Event-Seite und Formulare.

## 0.1.0

- Erste Version: Events mit Kategorien und Schlagwörtern, Erfassung im Backend, Event-Liste per Shortcode, Event-Details auf der Event-Seite.
- Schnellerfassung per Handy auf der Seite "Event erfassen", mit Login und Foto.
- Deutsche Übersetzung (de_CH).
