<?php

$images = [
    'folder_name' => 'media',
    'startup_view' => 'grid',
    'max_size' => 5120,
    'thumb' => false,
    'valid_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
];

return [
    'use_package_routes' => false,
    'allow_private_folder' => false,
    'allow_shared_folder' => true,
    'shared_folder_name' => 'library',
    'folder_categories' => ['image' => $images, 'file' => $images],
    'should_validate_size' => true,
    'rename_file' => true,
    'alphanumeric_directory' => true,
    'disallowed_extensions' => ['php', 'phtml', 'html', 'svg', 'js', 'phar'],
];
