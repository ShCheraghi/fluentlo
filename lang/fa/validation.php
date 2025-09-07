<?php


return [
    'required' => 'فیلد :attribute الزامی است',
    'string' => 'فیلد :attribute باید متن باشد',
    'email' => 'فیلد :attribute باید یک آدرس ایمیل معتبر باشد',
    'min' => [
        'string' => 'فیلد :attribute باید حداقل :min کاراکتر باشد',
    ],
    'max' => [
        'string' => 'فیلد :attribute نباید بیشتر از :max کاراکتر باشد',
    ],
    'unique' => ':attribute قبلاً استفاده شده است',
    'confirmed' => 'تأیید :attribute مطابقت ندارد',
    'in' => ':attribute انتخاب شده نامعتبر است',
    'same' => 'فیلدهای :attribute و :other باید مطابقت داشته باشند',
    'version_format' => 'فرمت :attribute باید مانند 1.2.3 یا 1.2.3.4 و امکان -beta داشته باشد.',
    'attributes' => [
        'name' => 'نام',
        'email' => 'آدرس ایمیل',
        'password' => 'رمز عبور',
        'password_confirmation' => 'تأیید رمز عبور',
        'c_password' => 'تأیید رمز عبور',
        'locale' => 'زبان',
        'platform' => 'پلتفرم',
        'track' => 'کانال',
        'current_version' => 'نسخه فعلی',
        'build_number' => 'شماره بیلد',
        'device_id' => 'شناسه دستگاه',
        'current_password' => 'رمز عبور فعلی',
        'token' => 'توکن بازنشانی',
    ],

    'onboarding' => [
        'title_required' => 'فیلد عنوان الزامی است.',
        'title_max' => 'عنوان نمی‌تواند بیشتر از 255 کاراکتر باشد.',
        'subtitle_max' => 'زیرعنوان نمی‌تواند بیشتر از 255 کاراکتر باشد.',
        'description_max' => 'توضیحات نمی‌تواند بیشتر از 1000 کاراکتر باشد.',
        'image_required' => 'فیلد آدرس تصویر الزامی است.',
        'background_color_required' => 'فیلد رنگ پس‌زمینه الزامی است.',
        'text_color_required' => 'فیلد رنگ متن الزامی است.',
        'button_color_required' => 'فیلد رنگ دکمه الزامی است.',
        'invalid_hex_color' => 'رنگ باید یک کد رنگ HEX معتبر باشد (مثل #FF0000).',
        'order_required' => 'فیلد ترتیب الزامی است.',
        'order_integer' => 'ترتیب باید یک عدد صحیح باشد.',
        'order_min' => 'ترتیب باید حداقل 0 باشد.',
    ],
];
