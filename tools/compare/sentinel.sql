-- Marks every piece of public content with "§" (see sentinel.sh).
UPDATE translations SET value = CONCAT('§', value) WHERE tkey NOT LIKE 'admin.%' AND value <> '';
UPDATE settings SET value = CONCAT('§', value) WHERE value <> '' AND (skey LIKE 'org.name.%' OR skey LIKE 'hq.%' OR skey LIKE 'motto.%' OR skey LIKE 'pastor.%' OR skey LIKE 'privacy.%'
  OR skey IN ('site.short_name', 'org.email', 'org.phone', 'giving.interac.email'));
UPDATE locales SET name = CONCAT('§', name), short_label = CONCAT('§', short_label);
UPDATE menu_items SET label_fr = CONCAT('§', label_fr), label_en = CONCAT('§', label_en);
UPDATE list_items SET title_fr = CONCAT('§', title_fr), title_en = CONCAT('§', title_en) WHERE title_fr IS NOT NULL;
UPDATE list_items SET short_fr = CONCAT('§', short_fr), short_en = CONCAT('§', short_en) WHERE short_fr IS NOT NULL;
UPDATE list_items SET body_fr = CONCAT('§', body_fr), body_en = CONCAT('§', body_en) WHERE body_fr IS NOT NULL;
UPDATE list_items SET value = CONCAT('§', value) WHERE value IS NOT NULL;
UPDATE churches SET name = CONCAT('§', name), city_fr = CONCAT('§', city_fr), city_en = CONCAT('§', city_en), region = CONCAT('§', region),
  street = CONCAT('§', street), locality = CONCAT('§', locality), postal_code = CONCAT('§', postal_code),
  phone = CONCAT('§', phone), email = CONCAT('§', email), pastor_fr = CONCAT('§', pastor_fr), pastor_en = CONCAT('§', pastor_en),
  summary_fr = CONCAT('§', summary_fr), summary_en = CONCAT('§', summary_en), intro_fr = CONCAT('§', intro_fr), intro_en = CONCAT('§', intro_en),
  about_title_fr = CONCAT('§', about_title_fr), about_title_en = CONCAT('§', about_title_en);
UPDATE church_services SET label_fr = CONCAT('§', label_fr), label_en = CONCAT('§', label_en);
UPDATE events SET title_fr = CONCAT('§', title_fr), title_en = CONCAT('§', title_en), description_fr = CONCAT('§', description_fr), description_en = CONCAT('§', description_en),
  date_text_fr = CONCAT('§', date_text_fr), date_text_en = CONCAT('§', date_text_en), recurrence_fr = CONCAT('§', recurrence_fr), recurrence_en = CONCAT('§', recurrence_en),
  time_text_fr = CONCAT('§', time_text_fr), time_text_en = CONCAT('§', time_text_en), venue_fr = CONCAT('§', venue_fr), venue_en = CONCAT('§', venue_en),
  people = REPLACE(CONCAT('§', people), '\n', '\n§');
UPDATE video_categories SET name_fr = CONCAT('§', name_fr), name_en = CONCAT('§', name_en), empty_fr = CONCAT('§', empty_fr), empty_en = CONCAT('§', empty_en);
UPDATE videos SET title_fr = CONCAT('§', title_fr), title_en = CONCAT('§', title_en), date_label = CONCAT('§', date_label);
UPDATE stories SET quote_fr = CONCAT('§', quote_fr), quote_en = CONCAT('§', quote_en), name = CONCAT('§', name), detail_fr = CONCAT('§', detail_fr), detail_en = CONCAT('§', detail_en);
UPDATE contact_purposes SET label_fr = CONCAT('§', label_fr), label_en = CONCAT('§', label_en);
UPDATE media SET alt_fr = CONCAT('§', alt_fr) WHERE alt_fr <> '';
UPDATE media SET alt_en = CONCAT('§', alt_en) WHERE alt_en <> '';
UPDATE settings SET value = REPLACE(value, '\n\n', '\n\n§') WHERE skey LIKE 'privacy.%';
