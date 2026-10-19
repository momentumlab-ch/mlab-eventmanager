=== MomentumLab Event Manager ===
Contributors: momentumlab
Tags: events, event manager, calendar, event list, frontend submission
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight event management: create, list and manage events in the backend, on the website and quickly on a phone.

== Description ==

* Events as their own content type with start, end, location, address, organizer, price and ticket link.
* Event categories and tags.
* Event list with the shortcode `[mlab_events]`, archive pages show upcoming events in date order.
* Date, location and ticket link appear automatically on the event page.
* Quick entry on the phone: the page "Add event" is created on activation. Log in once, fill in one form, take a photo, save.
* Users who may publish posts publish directly. All other users submit events for review.
* Updates via the regular WordPress updater.

== Shortcodes ==

`[mlab_events show="upcoming" category="concert" limit="10"]`

* `show`: `upcoming` (default), `past` or `all`
* `category`: category slug, several separated by commas
* `limit`: events per page, default from the settings

`[mlab_event_form]` shows the quick entry form. Guests see a login form.

== Customising ==

Templates can be overridden in the theme: copy a file from `templates/` to `<theme>/mlab-eventmanager/`.

Colours are CSS custom properties, e.g. `--mlab-em-accent`.

= Filters =

* `mlab_em_event_fields`: add, remove or change event fields. New fields appear in the editor, the REST API and, with `'quick' => true`, in the phone form.
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
* `mlab_em_list_item_after( $post_id )`, `mlab_em_details_after( $post_id )`, `mlab_em_form_fields_after()`: add output in the templates.

== Changelog ==

= 0.1.0 =
* Erste Version: Events mit Kategorien und Schlagwörtern, Erfassung im Backend, Event-Liste per Shortcode, Event-Details auf der Event-Seite.
* Schnellerfassung per Handy auf der Seite "Event erfassen", mit Login und Foto.
* Deutsche Übersetzung (de_CH).
