=== MomentumLab Event Manager ===
Contributors: momentumlab
Tags: events, event manager, calendar, event list, frontend submission
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight event management: create, list and manage events in the backend, on the website and quickly on a phone.

== Description ==

* Events as their own content type with start, end, venue, organizer, online link, price and ticket link, in clear groups.
* Venues and organizers as reusable entries. Map link and optional embedded map (OpenStreetMap).
* Event categories and tags.
* Event list with the shortcode `[mlab_events]`, optional search, category and date filter for visitors. Archive pages show upcoming events in date order.
* Date, place and links appear automatically on the event page, with "Add to calendar" (.ics and Google Calendar).
* Structured data for Google (schema.org Event).
* Quick entry on the phone: the page "Add event" is created on activation. Log in once, fill in one form, take a photo, save.
* "My events": change, duplicate and delete your own events on the phone.
* Duplicate events in the admin list. Upcoming and Past views. CSV export.
* Users who may publish posts publish directly. All other users submit events for review.
* Migration from WP Event Manager: events, venues, organizers, categories and images.
* Updates via the regular WordPress updater.

== Shortcodes ==

`[mlab_events show="upcoming" category="concert" limit="10"]`

* `show`: `upcoming` (default), `past` or `all`
* `category`: category slug, several separated by commas
* `limit`: events per page, default from the settings
* `filters`: `yes` shows search, category and date filters above the list

`[mlab_event_form]` shows the quick entry form and "My events". Guests see a login form.

== Migration from WP Event Manager ==

Open Events > Migration. "Check first" shows what would be copied and changes nothing. "Copy events now" copies up to 50 events per run, so run it again for more. Your WP Event Manager data is not changed. Events that were copied are skipped on the next run.

Copied: title, text, image, status, author, dates (converted to the website timezone), place, venue, organizer, price, ticket link, categories (as categories) and types (as tags). Not copied: registration e-mail addresses, online flag without link, second and further organizers, unfinished submissions. Events without a readable start date are skipped and listed.

== Customising ==

Templates can be overridden in the theme: copy a file from `templates/` to `<theme>/mlab-eventmanager/`.

Colours are CSS custom properties, e.g. `--mlab-em-accent`.

= Filters =

* `mlab_em_event_fields`: add, remove or change event fields. New fields appear in the editor, the REST API and on the event page. Optional keys: `required`, `default`, `quick` (phone form), `group` (section in the editor and the form), `column` (admin list), `button` (url fields as button), `details` (false hides the field on the event page).
* `mlab_em_sanitize_value`, `mlab_em_input_type`: sanitizing and input type for custom field types.
* `mlab_em_field_groups`: sections of the event fields.
* `mlab_em_linked_fields`: fields of venues and organizers.
* `mlab_em_place`: place of an event (name, address, coordinates).
* `mlab_em_schema`: structured data of an event.
* `mlab_em_export_columns`, `mlab_em_export_delimiter`, `mlab_em_export_capability`: CSV export.
* `mlab_em_own_events_args`: query of "My events".
* `mlab_em_settings_fields`: add settings.
* `mlab_em_post_type_args`, `mlab_em_taxonomy_args`: change the post type and taxonomies.
* `mlab_em_list_query_args`: change the list query.
* `mlab_em_archive_upcoming_only`: return false to show past events on archive pages.
* `mlab_em_template_path`: load a template from somewhere else.
* `mlab_em_details_rows`: rows on the event page.
* `mlab_em_date_range`: formatted date range.
* `mlab_em_show_details`: return false to hide the details box.
* `mlab_em_default_start`: default start in the phone form (format `Y-m-d H:i`).
* `mlab_em_submit_post_status`: status of events from the phone form.

= Actions =

* `mlab_em_event_saved( $post_id, $values )`: after event fields were saved.
* `mlab_em_event_submitted( $post_id, $input )`: after an event was added via the phone form.
* `mlab_em_event_updated( $post_id, $input )`, `mlab_em_event_duplicated( $new_id, $source_id )`, `mlab_em_event_migrated( $post_id, $source_id )`: after an event was changed, duplicated or migrated.
* `mlab_em_list_item_after( $post_id )`, `mlab_em_details_after( $post_id )`, `mlab_em_form_fields_after()`: add output in the templates.

== Changelog ==

= 0.4.0 =
* Bessere Event-Daten für die Google-Eventsuche: Preis und Währung aus dem Feld «Preis» (Zahl oder «Eintritt frei», ohne Preis wird die Beschreibung auf «Eintritt frei» geprüft), Angebot mit Link zu Tickets oder zur Event-Seite, Veranstalter fällt auf die Website zurück, Ort hat immer eine Adresse, Bild in mehreren Grössen. Filter mlab_em_currency (Standard CHF) und mlab_em_schema_organizer.

= 0.3.1 =
* Die Seite für die Schnellerfassung ist nur noch für angemeldete Benutzer: Besucher werden zur WordPress-Anmeldung geleitet und danach zurück. Die Seite ist für Suchmaschinen gesperrt (noindex).

= 0.3.0 =
* Neu: Reiter im Editor. Die Event-Angaben stehen direkt unter dem Titel, je ein Reiter für Wann, Wo, Online, Veranstalter, Tickets und Anzeige. Weniger Scrollen.
* Neu: Reiter "Anzeige" mit zwei Schaltern pro Event: "Bild auf der Event-Seite zeigen" und "Karte anzeigen (OpenStreetMap)".
* Neu: Die Karte braucht keine Koordinaten mehr von Hand. Die Position wird beim Speichern aus Ort und Adresse ermittelt (OpenStreetMap).
* Ohne Karte entfällt auch der Knopf "Karte öffnen".

= 0.2.0 =
* Neu: klarere Struktur. Felder in Gruppen (Wann, Wo, Online, Veranstalter, Tickets und Preis) im Editor und im Handy-Formular, "Weitere Angaben" einklappbar.
* Neu: Orte und Veranstalter als wiederverwendbare Einträge, mit Link zur Karte (OpenStreetMap) und optionaler eingebetteter Karte.
* Neu: Online-Link für Hybrid- und Online-Events.
* Neu: Events duplizieren (Admin-Liste und Handy).
* Neu: "Zum Kalender hinzufügen" (.ics und Google Kalender) und Schema-Markup für Google.
* Neu: Filter für Besucher mit `[mlab_events filters="yes"]`: Suche, Kategorie, Datum.
* Neu: "Meine Events" auf der Handy-Seite: ansehen, ändern, duplizieren, löschen.
* Neu: Admin-Liste mit Ansichten "Bevorstehend" und "Vergangene" und Spalte "Wo".
* Neu: CSV-Export unter Events > Export.
* Neu: Migration vom WP Event Manager unter Events > Migration, mit Vorabprüfung.
* Neu: Einstellungen in Bereiche gegliedert, Option für die eingebettete Karte.
* Neu: Kartenstil für Liste (mit Bild und Kategorien), Event-Seite und Formulare.

= 0.1.0 =
* Erste Version: Events mit Kategorien und Schlagwörtern, Erfassung im Backend, Event-Liste per Shortcode, Event-Details auf der Event-Seite.
* Schnellerfassung per Handy auf der Seite "Event erfassen", mit Login und Foto.
* Deutsche Übersetzung (de_CH).
