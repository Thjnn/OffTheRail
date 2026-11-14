<?php

// Thông báo validate tiếng Việt cho các luật đang dùng. Luật không có ở đây rơi về tiếng Anh (fallback).
return [
    'accepted' => ':Attribute cần được chấp nhận.',
    'array' => ':Attribute không hợp lệ.',
    'boolean' => ':Attribute không hợp lệ.',
    'date' => ':Attribute không phải ngày hợp lệ.',
    'digits' => ':Attribute phải gồm :digits chữ số.',
    'distinct' => ':Attribute bị trùng.',
    'email' => ':Attribute chưa đúng định dạng email.',
    'enum' => ':Attribute không hợp lệ.',
    'exists' => ':Attribute không tồn tại.',
    'file' => ':Attribute phải là một tệp.',
    'gt' => [
        'numeric' => ':Attribute phải lớn hơn :value.',
    ],
    'image' => ':Attribute phải là ảnh.',
    'in' => ':Attribute không hợp lệ.',
    'integer' => ':Attribute phải là số nguyên.',
    'max' => [
        'array' => ':Attribute tối đa :max mục.',
        'file' => ':Attribute tối đa :max KB.',
        'numeric' => ':Attribute tối đa :max.',
        'string' => ':Attribute tối đa :max ký tự.',
    ],
    'mimes' => ':Attribute phải có định dạng: :values.',
    'min' => [
        'array' => ':Attribute cần ít nhất :min mục.',
        'numeric' => ':Attribute tối thiểu :min.',
        'string' => ':Attribute cần ít nhất :min ký tự.',
    ],
    'numeric' => ':Attribute phải là số.',
    'regex' => ':Attribute không đúng định dạng.',
    'required' => 'Cần nhập :attribute.',
    'required_with' => 'Cần nhập :attribute.',
    'string' => ':Attribute không hợp lệ.',
    'unique' => ':Attribute đã được dùng.',
    'uploaded' => 'Tải :attribute lên không thành công.',
    'url' => ':Attribute chưa đúng định dạng đường dẫn.',

    'attributes' => [
        'name' => 'tên',
        'email' => 'email',
        'password' => 'mật khẩu',
        'price' => 'giá',
        'slug' => 'đường dẫn',
        'category_id' => 'danh mục',
    ],
];
