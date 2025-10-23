<?php

return [
    'settings' => [
        // Đặt đường dẫn cache cho HTMLPurifier
        'Cache.SerializerPath' => storage_path('app/purifier'),
        // Bạn có thể thêm các tuỳ chọn khác tại đây
        'HTML.Doctype' => 'HTML 4.01 Transitional',
        'Attr.EnableID' => true,
        'AutoFormat.RemoveEmpty' => false,
    ],
];
