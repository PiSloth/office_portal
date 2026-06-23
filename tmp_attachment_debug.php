<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$attachments = App\Models\Attachment::all();
if ($attachments->isEmpty()) {
    echo "NO_RECORDS\n";
    exit(0);
}

foreach ($attachments as $attachment) {
    $filePath = $attachment->file_path;
    $publicPath = storage_path('app/public/' . $filePath);
    $existsPublic = file_exists($publicPath) ? 'YES' : 'NO';
    $storagePath = storage_path($filePath);
    $existsStorage = file_exists($storagePath) ? 'YES' : 'NO';
    $url = Illuminate\Support\Facades\Storage::disk('public')->url($filePath);

    echo "id={$attachment->id} file_name={$attachment->file_name} file_path={$filePath} exists_public={$existsPublic} exists_storage={$existsStorage} url={$url}\n";
}
