<?php
use Adepc\Schedule;

/**
 * An event's date in big type: the day over month and year, a text such as
 * "Date to be announced", or its rhythm ("Every Sunday").
 * @var array $event
 */
?>
<?php if ($event['date_mode'] === 'date' && $event['event_date']): $d = Schedule::eventDate($event['event_date']); ?>
<p class="display leading-[0.85]"><span class="block text-display-m"><?= e($d['day']) ?></span><span class="block text-h2"><?= e(t('date.monthYear', ['month' => $d['month'], 'year' => $d['year']])) ?></span></p>
<?php elseif ($event['date_mode'] === 'text'): ?>
<p class="display text-h2 leading-none"><?= e(L($event, 'date_text')) ?></p>
<?php else: ?>
<p class="display text-h2 leading-none"><?= e(L($event, 'recurrence')) ?></p>
<?php endif ?>
