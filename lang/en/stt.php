<?php

return [
    // Success & errors
    'transcribe_success' => 'Audio transcribed successfully.',
    'transcribe_failed'  => 'Failed to transcribe audio.',
    'validation_error'   => 'The submitted data is invalid.',
    'upload_failed'      => 'Temporary file upload failed.',

    // Validation messages
    'validation' => [
        'audio_file_required' => 'The audio file field is required.',
        'audio_file_invalid'  => 'Invalid audio file.',
        'audio_file_format'   => 'Unsupported format. Allowed: wav, mp3, m4a, ogg, webm, flac.',
        'audio_file_size'     => 'The audio file must not exceed 25 MB.',
        'audio_url_required'  => 'The audio URL field is required.',
        'audio_url_invalid'   => 'The audio URL must be a valid URL.',
        'audio_url_format'    => 'The audio URL must end with a supported audio extension (wav, mp3, m4a, ogg, webm, flac).',
    ],
];
