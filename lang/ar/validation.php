<?php

/**
 * Arabic validation messages. (No lang/ folder shipped by default, which made
 * Laravel render raw keys like "validation.required".)
 */
return [
    'accepted' => 'يجب قبول :attribute.',
    'active_url' => ':attribute ليس رابطًا صحيحًا.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'string' => 'يجب أن يكون عدد أحرف :attribute بين :min و :max.',
        'array' => 'يجب أن يحتوي :attribute بين :min و :max عنصرًا.',
    ],
    'boolean' => 'قيمة :attribute يجب أن تكون صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'date' => ':attribute ليس تاريخًا صحيحًا.',
    'date_equals' => 'يجب أن يكون :attribute تاريخًا يساوي :date.',
    'different' => 'يجب أن يختلف :attribute عن :other.',
    'digits' => 'يجب أن يكون :attribute :digits رقمًا.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'exists' => 'القيمة المحددة لـ :attribute غير موجودة.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المحددة لـ :attribute غير صحيحة.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'max' => [
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'file' => 'يجب ألا يكون حجم :attribute أكبر من :max كيلوبايت.',
        'string' => 'يجب ألا يزيد عدد أحرف :attribute عن :max.',
        'array' => 'يجب ألا يحتوي :attribute أكثر من :max عنصرًا.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'min' => [
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'string' => 'يجب ألا يقل عدد أحرف :attribute عن :min.',
        'array' => 'يجب أن يحتوي :attribute على الأقل :min عنصرًا.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'present' => 'يجب توفير :attribute.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other يساوي :value.',
    'required_with' => 'حقل :attribute مطلوب عند توفر :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'unique' => ':attribute مستخدم من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url' => 'صيغة :attribute غير صحيحة.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'title' => 'العنوان',
        'description' => 'الوصف',
        'department_id' => 'القسم',
        'priority_id' => 'الأولوية',
        'location_id' => 'الموقع',
        'quantity' => 'الكمية',
        'reason' => 'السبب',
        'note' => 'الملاحظة',
        'body' => 'النص',
        'file' => 'الملف',
        'reason_code' => 'سبب الإيقاف',
        'technician_id' => 'الفني',
        'unit_price' => 'سعر الوحدة',
        'fulfillment_type' => 'نوع الشراء',
        'justification' => 'المبرّر',
        'role' => 'الدور',
    ],
];
