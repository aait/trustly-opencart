<?php
if (!defined('DIR_APPLICATION')) {
    die();
}

$_['text_title'] = 'Betala med Trustly';
$_['text_message_payment_credited'] = 'En Trustly betalning på %s %s har blivit krediterad till den här ordern på %s, Trustly order-id %s.';
$_['text_message_order_totals_not_match'] = 'Trustly betalningen för den här ordern på %s i %s %s matchar inte totala ordern på %s %s.';
$_['text_message_payment_debited'] = 'En Trustly betalning på %s har dragits till den här ordern %s';
$_['text_message_payment_canceled'] = 'Betalningen avbröts på Trustly av %s';
$_['text_message_payment_pending'] = 'En Trustly betalning på %s %s har startats för den här ordern på %s, Trustly order-id %s.';
$_['text_message_payment_pending_notification'] = 'Väntar på notifikation från Trustly. Var god och inspektera ordern senare.';
$_['text_order_orders_processed'] = 'Din order är betald!';
$_['text_order_orders_pending'] = 'Din order är mottagen och kommer behandlas så fort betalningen är konfirmerad.';
$_['text_success_customer'] = '<p>Du kan se din orderhistorik på <a href="%s">min sida</a> klicka på <a href="%s">historik</a>.</p><p>Om ditt köp har en nedladdningsbar produkt kan du gå till <a href="%s">Nedladdningar</a> för att se dem.</p><p>Om du har några frågor, vänligen kontakta <a href="%s">Kundtjänst</a>.</p><p>Tack för att du handlar hos oss!</p>';
$_['text_success_guest'] = '<p>Om du har några frågor, vänligen kontakta <a href="%s">Kundtjänst</a>.</p><p>Tack för att du handlar hos oss!</p>';
$_['text_error_title'] = 'Trustly betalnings fel';
$_['text_error_description'] = 'Ett fel uppstod under betalningen.';
$_['text_error_link'] = 'Tillbaka till kassan';
$_['error_invalid_order'] = 'Ogiltig order';
$_['error_unknown'] = 'Ett okänt fel uppstod';
$_['error_no_payment_url'] = 'Ett fel uppstod vid kommunikationen med Trustlys web service: Ingen betalnings urls togs emot.';
$_['error_payment_failed'] = 'Betalningen misslyckades';
$_['error_order_create'] = 'Kunde inte starta betalningen';
$_['error_trustly'] = 'Ett problem har uppstått: %s';
$_['error_message_payment_amount_invalid'] = 'Betalningen för den här ordern med beloppet %s av %s %s matchar inte totalbeloppet %s %s.';
$_['error_wrong_payment_amount'] = 'Din betalning kommer att kontrolleras manuellt. Vår kundservice återkommer.';