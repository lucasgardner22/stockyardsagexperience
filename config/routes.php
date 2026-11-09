<?php

return [
    'whats-happening' => ['template' => 'pages/news/whats-happening'],
    'events' => ['template' => 'pages/visit/events'],
    'events/<slug:[^\/]+>.ics' => ['template' => 'pages/visit/_eventIcs'],
];