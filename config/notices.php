<?php

return [
    'per_minute' => max(1, (int) env('NOTICE_MAIL_PER_MINUTE', 30)),
];
