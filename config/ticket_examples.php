<?php

/*
|--------------------------------------------------------------------------
| Ticket example hints (per department type)
|--------------------------------------------------------------------------
|
| Central, editable source for the dynamic "example problem" hint shown in the
| mobile new-ticket form. Keyed by Department::TYPES. The meta endpoint attaches
| the matching example to each department; unknown types fall back to 'default'.
| Edit here to change the guidance shown to requesters — no code change needed.
|
*/

return [
    'it'          => 'مثال: الحاسب لا يعمل، لا يوجد اتصال بالشبكة/الإنترنت، أو طابعة معطّلة.',
    'electrical'  => 'مثال: انقطاع كهرباء عن المكتب، قاطع يفصل باستمرار، أو إنارة لا تعمل.',
    'mechanical'  => 'مثال: صوت غير طبيعي من المعدة، تسرّب زيت، أو مضخة متوقّفة.',
    'hvac'        => 'مثال: المكيّف لا يبرّد، تسرّب مياه من الوحدة، أو ضوضاء عالية.',
    'plumbing'    => 'مثال: تسرّب مياه، انسداد في الصرف، أو صنبور لا يُغلق.',
    'civil'       => 'مثال: تشقّق في جدار، باب/نافذة لا تُغلق، أو بلاط مكسور.',
    'safety'      => 'مثال: طفاية حريق منتهية، مخرج طوارئ مغلق، أو لافتة سلامة مفقودة.',
    'maintenance' => 'مثال: عطل عام يحتاج صيانة — صِف المشكلة وموقعها بدقة.',
    'general'     => 'مثال: اوصف المشكلة بوضوح مع تحديد الموقع والوقت إن أمكن.',

    'default'     => 'مثال: اوصف المشكلة بوضوح، وحدّد موقعها بدقة.',
];
