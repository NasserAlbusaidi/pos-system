<?php

return [
    'enabled' => (bool) env('PRINTNODE_ENABLED', false),
    'api_key' => env('PRINTNODE_API_KEY'),
    'endpoint' => env('PRINTNODE_ENDPOINT', 'https://api.printnode.com'),
    'default_printer_id' => env('PRINTNODE_PRINTER_ID'),
];
