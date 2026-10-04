<?php
declare(strict_types=1);

namespace Adepc\Admin;

/**
 * What the admin can edit, declared once. Every screen (lists, forms, pages,
 * settings) is generated from these definitions. Labels are admin.* keys.
 *
 * Field: [column/name, type, options...]
 *   types: text, textarea, l10n (fr/en pair; 'multiline'), email, url, time, date,
 *          number, decimal, select ('options' => [value => label key] or 'source'),
 *          checkbox, media ('kind' => image|video), church, churches, slug, lines, password
 *   options: required, help, default_flag (column cleared when the value changes),
 *            show_if => [field, [values]], max
 */
final class Schema
{
    public static function resources(): array
    {
        return [
            'churches' => [
                'table' => 'churches',
                'sortable' => true,
                'label' => 'admin.nav.churches',
                'singular' => 'admin.res.church',
                'columns' => ['name', 'city_fr', 'published'],
                'fields' => [
                    ['name', 'text', 'required' => true, 'label' => 'admin.f.churchName'],
                    ['slug', 'slug', 'required' => true, 'label' => 'admin.f.slug', 'help' => 'admin.h.churchSlug'],
                    ['city', 'l10n', 'required' => true, 'label' => 'admin.f.city'],
                    ['region', 'text', 'required' => true, 'max' => 8, 'label' => 'admin.f.region', 'help' => 'admin.h.region'],
                    ['street', 'text', 'required' => true, 'label' => 'admin.f.street'],
                    ['locality', 'text', 'required' => true, 'label' => 'admin.f.locality'],
                    ['postal_code', 'text', 'required' => true, 'label' => 'admin.f.postalCode'],
                    ['lat', 'decimal', 'label' => 'admin.f.lat', 'help' => 'admin.h.coords'],
                    ['lng', 'decimal', 'label' => 'admin.f.lng'],
                    ['phone', 'text', 'label' => 'admin.f.phone', 'help' => 'admin.h.hiddenIfEmpty'],
                    ['email', 'email', 'label' => 'admin.f.email', 'help' => 'admin.h.hiddenIfEmpty'],
                    ['pastor', 'l10n', 'label' => 'admin.f.pastor', 'default_flag' => 'pastor_is_default', 'help' => 'admin.h.pastor'],
                    ['summary', 'l10n', 'multiline' => true, 'label' => 'admin.f.summary'],
                    ['intro', 'l10n', 'multiline' => true, 'label' => 'admin.f.intro'],
                    ['about_title', 'l10n', 'label' => 'admin.f.aboutTitle'],
                    ['hero_media_id', 'media', 'kind' => 'image', 'required' => true, 'label' => 'admin.f.heroPhoto'],
                    ['band_focus', 'text', 'label' => 'admin.f.bandFocus', 'help' => 'admin.h.focus'],
                    ['services', 'services', 'label' => 'admin.f.services'],
                    ['published', 'checkbox', 'label' => 'admin.f.published'],
                ],
            ],
            'events' => [
                'table' => 'events',
                'sortable' => true,
                'label' => 'admin.nav.events',
                'singular' => 'admin.res.event',
                'columns' => ['title_fr', 'kind', 'published'],
                'fields' => [
                    ['kind', 'select', 'options' => ['featured' => 'admin.o.featured', 'recurring' => 'admin.o.recurring'], 'label' => 'admin.f.kind'],
                    ['title', 'l10n', 'required' => true, 'label' => 'admin.f.title'],
                    ['description', 'l10n', 'multiline' => true, 'required' => true, 'label' => 'admin.f.description'],
                    ['date_mode', 'select', 'options' => ['none' => 'admin.o.dateNone', 'date' => 'admin.o.dateDate', 'text' => 'admin.o.dateText'], 'label' => 'admin.f.dateMode', 'default_flag' => 'date_is_default'],
                    ['event_date', 'date', 'label' => 'admin.f.date', 'show_if' => ['date_mode', ['date']], 'default_flag' => 'date_is_default'],
                    ['date_text', 'l10n', 'label' => 'admin.f.dateText', 'show_if' => ['date_mode', ['text']], 'default_flag' => 'date_is_default'],
                    ['recurrence', 'l10n', 'label' => 'admin.f.recurrence', 'show_if' => ['date_mode', ['none']]],
                    ['time_mode', 'select', 'options' => ['none' => 'admin.o.timeNone', 'time' => 'admin.o.timeTime', 'text' => 'admin.o.timeText'], 'label' => 'admin.f.timeMode', 'default_flag' => 'time_is_default'],
                    ['event_time', 'time', 'label' => 'admin.f.time', 'show_if' => ['time_mode', ['time']], 'default_flag' => 'time_is_default'],
                    ['time_text', 'l10n', 'label' => 'admin.f.timeText', 'show_if' => ['time_mode', ['text']], 'default_flag' => 'time_is_default'],
                    ['venue', 'l10n', 'label' => 'admin.f.venue', 'help' => 'admin.h.hiddenIfEmpty', 'default_flag' => 'venue_is_default'],
                    ['registration_url', 'url', 'label' => 'admin.f.registration', 'help' => 'admin.h.registration'],
                    ['people', 'lines', 'label' => 'admin.f.people', 'help' => 'admin.h.people'],
                    ['all_churches', 'checkbox', 'label' => 'admin.f.allChurches'],
                    ['churches', 'churches', 'label' => 'admin.f.churches', 'show_if' => ['all_churches', ['0']]],
                    ['show_sunday_times', 'checkbox', 'label' => 'admin.f.showSundayTimes'],
                    ['media_id', 'media', 'kind' => 'image', 'required' => true, 'label' => 'admin.f.photo'],
                    ['published', 'checkbox', 'label' => 'admin.f.published'],
                ],
            ],
            'videos' => [
                'table' => 'videos',
                'sortable' => true,
                'label' => 'admin.nav.videos',
                'singular' => 'admin.res.video',
                'columns' => ['title_fr', 'category_id', 'published'],
                'fields' => [
                    ['title', 'l10n', 'required' => true, 'label' => 'admin.f.title'],
                    ['category_id', 'select', 'source' => 'categories', 'required' => true, 'label' => 'admin.f.category'],
                    ['church_id', 'church', 'label' => 'admin.f.church'],
                    ['date_label', 'text', 'label' => 'admin.f.dateLabel', 'help' => 'admin.h.dateLabel'],
                    ['duration_seconds', 'number', 'label' => 'admin.f.duration'],
                    ['poster_media_id', 'media', 'kind' => 'image', 'label' => 'admin.f.poster'],
                    ['source_kind', 'select', 'options' => ['file' => 'admin.o.sourceFile', 'youtube' => 'admin.o.sourceYoutube'], 'label' => 'admin.f.source'],
                    ['mp4_media_id', 'media', 'kind' => 'video', 'label' => 'admin.f.mp4', 'show_if' => ['source_kind', ['file']]],
                    ['webm_media_id', 'media', 'kind' => 'video', 'label' => 'admin.f.webm', 'show_if' => ['source_kind', ['file']]],
                    ['youtube_id', 'text', 'label' => 'admin.f.youtubeId', 'help' => 'admin.h.youtubeId', 'show_if' => ['source_kind', ['youtube']]],
                    ['published', 'checkbox', 'label' => 'admin.f.published'],
                ],
            ],
            'stories' => [
                'table' => 'stories',
                'sortable' => true,
                'label' => 'admin.nav.stories',
                'singular' => 'admin.res.story',
                'columns' => ['name', 'published'],
                'fields' => [
                    ['quote', 'l10n', 'multiline' => true, 'required' => true, 'label' => 'admin.f.quote'],
                    ['name', 'text', 'required' => true, 'label' => 'admin.f.personName'],
                    ['detail', 'l10n', 'label' => 'admin.f.detail'],
                    ['church_id', 'church', 'label' => 'admin.f.church'],
                    ['media_id', 'media', 'kind' => 'image', 'required' => true, 'label' => 'admin.f.photo'],
                    ['consent', 'checkbox', 'label' => 'admin.f.consent', 'help' => 'admin.h.consent'],
                    ['confirm_note', 'textarea', 'label' => 'admin.f.confirmNote', 'help' => 'admin.h.confirmNote'],
                    ['published', 'checkbox', 'label' => 'admin.f.published'],
                ],
            ],
            'categories' => [
                'table' => 'video_categories',
                'sortable' => true,
                'label' => 'admin.nav.categories',
                'singular' => 'admin.res.category',
                'columns' => ['name_fr', 'slug', 'visible'],
                'fields' => [
                    ['name', 'l10n', 'required' => true, 'label' => 'admin.f.name'],
                    ['slug', 'slug', 'required' => true, 'label' => 'admin.f.anchor', 'help' => 'admin.h.anchor'],
                    ['role', 'select', 'options' => ['' => 'admin.o.roleNone', 'messages' => 'admin.o.roleMessages', 'live' => 'admin.o.roleLive'], 'label' => 'admin.f.role', 'help' => 'admin.h.role'],
                    ['empty', 'l10n', 'multiline' => true, 'label' => 'admin.f.emptyText'],
                    ['visible', 'checkbox', 'label' => 'admin.f.visible'],
                ],
            ],
            'purposes' => [
                'table' => 'contact_purposes',
                'sortable' => true,
                'label' => 'admin.nav.purposes',
                'singular' => 'admin.res.purpose',
                'columns' => ['label_fr', 'pkey', 'visible'],
                'fields' => [
                    ['label', 'l10n', 'required' => true, 'label' => 'admin.f.label'],
                    ['pkey', 'slug', 'required' => true, 'label' => 'admin.f.key', 'help' => 'admin.h.purposeKey'],
                    ['is_prayer', 'checkbox', 'label' => 'admin.f.isPrayer', 'help' => 'admin.h.isPrayer'],
                    ['visible', 'checkbox', 'label' => 'admin.f.visible'],
                ],
            ],
            'redirects' => [
                'table' => 'redirects',
                'sortable' => false,
                'label' => 'admin.nav.redirects',
                'singular' => 'admin.res.redirect',
                'columns' => ['from_path', 'to_path', 'status'],
                'fields' => [
                    ['from_path', 'text', 'required' => true, 'label' => 'admin.f.fromPath', 'help' => 'admin.h.path'],
                    ['to_path', 'text', 'required' => true, 'label' => 'admin.f.toPath'],
                    ['status', 'select', 'options' => ['308' => 'admin.o.permanent', '307' => 'admin.o.temporary'], 'label' => 'admin.f.status'],
                ],
            ],
        ];
    }

    /** Repeating items inside pages. */
    public static function lists(): array
    {
        $title = ['title', 'l10n', 'required' => true, 'label' => 'admin.f.text'];
        $body = ['body', 'l10n', 'multiline' => true, 'label' => 'admin.f.body'];
        return [
            'home.hero.lines' => ['label' => 'admin.list.heroLines', 'fields' => [$title]],
            'home.mission.rows' => ['label' => 'admin.list.missionRows', 'fields' => [$title, $body]],
            'home.pillars' => ['label' => 'admin.list.pillars', 'fields' => [$title, $body, ['media_id', 'media', 'kind' => 'image', 'label' => 'admin.f.photo']]],
            'about.heritage' => ['label' => 'admin.list.heritage', 'fields' => [['short', 'l10n', 'required' => true, 'label' => 'admin.f.shortName'], $title]],
            'about.glance' => ['label' => 'admin.list.glance', 'fields' => [
                $title,
                ['value_source', 'select', 'options' => ['churches' => 'admin.o.countChurches', 'cities' => 'admin.o.countCities', 'provinces' => 'admin.o.countProvinces', 'manual' => 'admin.o.manual'], 'label' => 'admin.f.figure'],
                ['value', 'text', 'label' => 'admin.f.value', 'show_if' => ['value_source', ['manual']]],
            ]],
            'about.mission.banner' => ['label' => 'admin.list.banner', 'fields' => [$title]],
            'about.strategic' => ['label' => 'admin.list.strategic', 'fields' => [$title, $body]],
            'about.values' => ['label' => 'admin.list.values', 'fields' => [$title]],
            'about.beliefs' => ['label' => 'admin.list.beliefs', 'fields' => [$title, $body]],
            'social' => ['label' => 'admin.list.social', 'fields' => [['title', 'l10n', 'required' => true, 'label' => 'admin.f.label'], ['url', 'url', 'required' => true, 'label' => 'admin.f.url']]],
        ];
    }

    /** Image and video slots. */
    public static function slots(): array
    {
        return [
            'home.hero.poster' => ['image', 'admin.slot.heroPoster', true],
            'home.hero.mp4' => ['video', 'admin.slot.heroMp4', false],
            'home.hero.webm' => ['video', 'admin.slot.heroWebm', false],
            'home.film.poster' => ['image', 'admin.slot.filmPoster', true],
            'home.stories.1' => ['image', 'admin.slot.stories1', true],
            'home.stories.2' => ['image', 'admin.slot.stories2', true],
            'home.stories.3' => ['image', 'admin.slot.stories3', true],
            'home.stories.4' => ['image', 'admin.slot.stories4', true],
            'about.hero' => ['image', 'admin.slot.pageHero', true],
            'about.story' => ['image', 'admin.slot.aboutStory', true],
            'churches.hero' => ['image', 'admin.slot.pageHero', true],
            'stories.hero' => ['image', 'admin.slot.pageHero', true],
            'give.hero' => ['image', 'admin.slot.pageHero', true],
            'pastor.photo' => ['image', 'admin.slot.pastorPhoto', true],
            'brand.emblem' => ['image', 'admin.slot.emblem', false],
            'brand.logo' => ['image', 'admin.slot.logo', false],
            'brand.icon' => ['image', 'admin.slot.icon', false],
            'brand.apple_icon' => ['image', 'admin.slot.appleIcon', false],
            'brand.share' => ['image', 'admin.slot.share', false],
        ];
    }

    /**
     * Pages and their sections, in the order they appear on the site.
     * Each section: label, translation key prefixes, lists, slots, settings.
     */
    public static function pages(): array
    {
        return [
            'home' => ['label' => 'admin.page.home', 'sections' => [
                ['admin.sec.hero', ['home.hero.'], ['home.hero.lines'], ['home.hero.poster', 'home.hero.mp4', 'home.hero.webm']],
                ['admin.sec.thisSunday', ['home.sunday.'], [], []],
                ['admin.sec.churchBand', ['home.churches.'], [], []],
                ['admin.sec.film', ['home.film.'], [], ['home.film.poster'], ['home.film.video_id']],
                ['admin.sec.mission', ['home.mission.'], ['home.mission.rows'], []],
                ['admin.sec.pillars', ['home.pillars.'], ['home.pillars'], []],
                ['admin.sec.pastor', ['home.pastor.'], [], ['pastor.photo'], ['pastor.name', 'pastor.role']],
                ['admin.sec.events', ['home.events.'], [], []],
                ['admin.sec.storiesStrip', ['home.stories.'], [], ['home.stories.1', 'home.stories.2', 'home.stories.3', 'home.stories.4']],
                ['admin.sec.watch', ['home.watch.'], [], []],
                ['admin.sec.testimonial', ['home.testimonial.'], [], []],
                ['admin.sec.give', ['home.give.', 'giving.'], [], []],
            ]],
            'churches' => ['label' => 'admin.page.churches', 'sections' => [
                ['admin.sec.hero', ['churches.hero.'], [], ['churches.hero']],
                ['admin.sec.map', ['churches.map.'], [], []],
                ['admin.sec.directory', ['churches.directory.'], [], []],
                ['admin.sec.churchPage', ['churches.page.'], [], []],
            ]],
            'events' => ['label' => 'admin.page.events', 'sections' => [
                ['admin.sec.hero', ['events.hero.'], [], []],
                ['admin.sec.list', ['events.'], [], []],
            ]],
            'watch' => ['label' => 'admin.page.watch', 'sections' => [
                ['admin.sec.hero', ['watch.hero.'], [], []],
                ['admin.sec.list', ['watch.'], [], []],
            ]],
            'about' => ['label' => 'admin.page.about', 'sections' => [
                ['admin.sec.hero', ['about.hero.'], [], ['about.hero']],
                ['admin.sec.who', ['about.who.'], [], []],
                ['admin.sec.heritage', ['about.heritage.'], ['about.heritage'], []],
                ['admin.sec.glance', [], ['about.glance'], []],
                ['admin.sec.story', ['about.story.'], [], ['about.story']],
                ['admin.sec.mission', ['about.mission.'], ['about.mission.banner'], []],
                ['admin.sec.vision', ['about.vision.'], [], []],
                ['admin.sec.strategic', ['about.strategic.'], ['about.strategic'], []],
                ['admin.sec.values', ['about.values.'], ['about.values'], []],
                ['admin.sec.beliefs', ['about.beliefs.'], ['about.beliefs'], []],
                ['admin.sec.cta', ['about.cta.'], [], []],
            ]],
            'stories' => ['label' => 'admin.page.stories', 'sections' => [
                ['admin.sec.hero', ['stories.hero.'], [], ['stories.hero']],
                ['admin.sec.list', ['stories.'], [], []],
            ]],
            'give' => ['label' => 'admin.page.give', 'sections' => [
                ['admin.sec.hero', ['give.hero.'], [], ['give.hero']],
                ['admin.sec.list', ['give.'], [], []],
            ]],
            'contact' => ['label' => 'admin.page.contact', 'sections' => [
                ['admin.sec.hero', ['contact.hero.'], [], []],
                ['admin.sec.form', ['contact.form.'], [], []],
                ['admin.sec.list', ['contact.'], [], []],
            ]],
            'privacy' => ['label' => 'admin.page.privacy', 'sections' => [
                ['admin.sec.hero', ['privacy.hero.'], [], []],
                ['admin.sec.list', ['privacy.'], [], [], ['privacy.policy', 'privacy.officer']],
            ]],
            'common' => ['label' => 'admin.page.common', 'sections' => [
                ['admin.sec.nav', ['nav.'], [], ['brand.emblem']],
                ['admin.sec.footer', ['footer.'], ['social'], ['brand.logo']],
                ['admin.sec.common', ['common.', 'consent.'], [], []],
                ['admin.sec.notFound', ['notFound.'], [], []],
                ['admin.sec.formats', ['format.', 'meta.titleTemplate'], [], []],
                ['admin.sec.dates', ['date.'], [], []],
                ['admin.sec.brand', [], [], ['brand.icon', 'brand.apple_icon', 'brand.share']],
            ]],
        ];
    }

    /** Settings screens: group → fields (keys of the settings table). */
    public static function settings(): array
    {
        return [
            'organization' => ['label' => 'admin.set.organization', 'fields' => [
                ['site.short_name', 'text', 'required' => true, 'label' => 'admin.f.shortName'],
                ['org.name', 'l10n', 'required' => true, 'label' => 'admin.f.orgName'],
                ['org.email', 'email', 'required' => true, 'label' => 'admin.f.email'],
                ['org.phone', 'text', 'label' => 'admin.f.phone', 'help' => 'admin.h.hiddenIfEmpty'],
                ['hq.label', 'l10n', 'label' => 'admin.f.hqLabel'],
                ['hq.building', 'text', 'label' => 'admin.f.hqBuilding'],
                ['hq.street', 'text', 'label' => 'admin.f.street'],
                ['hq.locality', 'text', 'label' => 'admin.f.locality', 'help' => 'admin.h.hqLocality'],
                ['hq.postal_code', 'text', 'label' => 'admin.f.postalCode'],
                ['hq.city', 'text', 'label' => 'admin.f.hqCity', 'help' => 'admin.h.structured'],
                ['hq.region', 'text', 'label' => 'admin.f.region'],
                ['org.country', 'text', 'label' => 'admin.f.country'],
                ['motto.text', 'l10n', 'multiline' => true, 'label' => 'admin.f.motto'],
                ['motto.ref', 'l10n', 'label' => 'admin.f.mottoRef'],
            ]],
            'pastor' => ['label' => 'admin.set.pastor', 'fields' => [
                ['pastor.name', 'l10n', 'label' => 'admin.f.pastorName'],
                ['pastor.role', 'l10n', 'label' => 'admin.f.pastorRole'],
            ]],
            'giving' => ['label' => 'admin.set.giving', 'fields' => [
                ['giving.interac.email', 'email', 'label' => 'admin.f.interacEmail'],
                ['giving.paypal.client_id', 'text', 'label' => 'admin.f.paypalClient'],
                ['giving.paypal.button_id', 'text', 'label' => 'admin.f.paypalButton'],
                ['giving.paypal.currency', 'text', 'label' => 'admin.f.currency'],
                ['giving.paypal.sdk_url', 'text', 'label' => 'admin.f.paypalSdk', 'help' => 'admin.h.advanced'],
            ]],
            'email' => ['label' => 'admin.set.email', 'fields' => [
                ['mail.transport', 'select', 'options' => ['none' => 'admin.o.mailNone', 'smtp' => 'admin.o.mailSmtp', 'mail' => 'admin.o.mailPhp'], 'label' => 'admin.f.transport', 'help' => 'admin.h.transport'],
                ['mail.smtp_host', 'text', 'label' => 'admin.f.smtpHost', 'show_if' => ['mail.transport', ['smtp']]],
                ['mail.smtp_port', 'number', 'label' => 'admin.f.smtpPort', 'show_if' => ['mail.transport', ['smtp']]],
                ['mail.smtp_secure', 'select', 'options' => ['ssl' => 'admin.o.ssl', 'tls' => 'admin.o.tls', 'none' => 'admin.o.noEncryption'], 'label' => 'admin.f.smtpSecure', 'show_if' => ['mail.transport', ['smtp']]],
                ['mail.smtp_user', 'text', 'label' => 'admin.f.smtpUser', 'show_if' => ['mail.transport', ['smtp']]],
                ['mail.smtp_pass', 'password', 'label' => 'admin.f.smtpPass', 'help' => 'admin.h.keepPassword', 'show_if' => ['mail.transport', ['smtp']]],
                ['mail.from_address', 'email', 'label' => 'admin.f.fromAddress'],
                ['mail.from_name', 'text', 'label' => 'admin.f.fromName'],
                ['mail.to', 'text', 'label' => 'admin.f.mailTo'],
                ['mail.to_pastor', 'text', 'label' => 'admin.f.mailToPastor', 'help' => 'admin.h.mailToPastor'],
                ['contact.min_seconds', 'number', 'label' => 'admin.f.minSeconds', 'help' => 'admin.h.minSeconds'],
            ]],
            'integrations' => ['label' => 'admin.set.integrations', 'fields' => [
                ['youtube.channel_id', 'text', 'label' => 'admin.f.youtubeChannel', 'help' => 'admin.h.youtubeChannel'],
                ['youtube.live_pattern', 'text', 'label' => 'admin.f.livePattern', 'help' => 'admin.h.livePattern'],
                ['youtube.limit', 'number', 'label' => 'admin.f.youtubeLimit'],
                ['maps.mapbox_token', 'text', 'label' => 'admin.f.mapboxToken', 'help' => 'admin.h.mapboxToken'],
                ['analytics.ga_id', 'text', 'label' => 'admin.f.gaId', 'help' => 'admin.h.gaId'],
                ['schedule.timezone', 'text', 'label' => 'admin.f.timezone'],
                ['phone.country_code', 'text', 'label' => 'admin.f.countryCode'],
                ['maps.directions_url', 'text', 'label' => 'admin.f.directionsUrl', 'help' => 'admin.h.advanced'],
                ['maps.mapbox_style', 'text', 'label' => 'admin.f.mapboxStyle', 'help' => 'admin.h.advanced'],
                ['maps.mapbox_js', 'text', 'label' => 'admin.f.mapboxJs', 'help' => 'admin.h.advanced'],
                ['maps.mapbox_css', 'text', 'label' => 'admin.f.mapboxCss', 'help' => 'admin.h.advanced'],
                ['youtube.feed_url', 'text', 'label' => 'admin.f.youtubeFeed', 'help' => 'admin.h.advanced'],
                ['youtube.embed_url', 'text', 'label' => 'admin.f.youtubeEmbed', 'help' => 'admin.h.advanced'],
                ['youtube.thumb_url', 'text', 'label' => 'admin.f.youtubeThumb', 'help' => 'admin.h.advanced'],
            ]],
            'seo' => ['label' => 'admin.set.seo', 'fields' => [
                ['site.url', 'url', 'required' => true, 'label' => 'admin.f.siteUrl', 'help' => 'admin.h.siteUrl'],
                ['seo.robots_disallow', 'textarea', 'label' => 'admin.f.robots', 'help' => 'admin.h.robots'],
                ['site.theme_color', 'text', 'label' => 'admin.f.themeColor'],
            ]],
            'privacy' => ['label' => 'admin.set.privacy', 'fields' => [
                ['privacy.policy', 'l10n', 'multiline' => true, 'label' => 'admin.f.privacyPolicy', 'help' => 'admin.h.paragraphs'],
                ['privacy.officer', 'l10n', 'multiline' => true, 'label' => 'admin.f.privacyOfficer'],
            ]],
        ];
    }

    /** Settings that also appear inside page screens. */
    public static function settingField(string $key): ?array
    {
        foreach (self::settings() as $group) {
            foreach ($group['fields'] as $f) {
                if ($f[0] === $key) {
                    return $f;
                }
            }
        }
        if ($key === 'home.film.video_id') {
            return ['home.film.video_id', 'select', 'source' => 'videos', 'label' => 'admin.f.homeFilm'];
        }
        return null;
    }
}
