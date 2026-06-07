<?php

return [
    // Maximum number of files accepted per upload request. The frontend
    // splits larger selections into batches of this size, so this value
    // should stay small enough to comfortably fit within PHP's
    // post_max_size / max_execution_time for a single request.
    'max_upload_files' => (int) env('SIGN_MAX_UPLOAD_FILES', 20),
];
