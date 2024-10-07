<?php

namespace Tests\Datasets;

dataset(name: 'bytesInFramesDataProvider', dataset: [

    // Success
    [34815, 0, 135],
    [34815, 1, 255],
    ['_7P!gij', 0, 95],
    ['_7P!gij', 1, 55],
    ['_7P!gij', 6, 106],
    // Failure
    [-10, 1],
    ['gdgdf_7P)', 10],
    [128, 1],
]);