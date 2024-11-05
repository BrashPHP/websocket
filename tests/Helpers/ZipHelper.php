<?php

namespace Tests\Helpers;

use ZipArchive;

function createTempZipFile($identifier): string
{
    $zip = new ZipArchive();
    $filename = $identifier;
    $tmpDir = sys_get_temp_dir();
    $zipArchive = "{$tmpDir}/{$filename}";
    if ($zip->open($zipArchive, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) {
        exit("cannot open <$filename>\n");
    }

    $zip->addFromString("testfilephp.txt" . time(), "#1 This is a test string added as testfilephp.txt.\n");
    $zip->close();

    return $zipArchive;
}

function readTempZip(): bool|string{
    $fullLocation = createTempZipFile("test.zip");

    return file_get_contents($fullLocation);
}
