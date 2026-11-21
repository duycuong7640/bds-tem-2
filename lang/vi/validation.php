<?php

return [
    'required' => 'Vui lòng nhập hoặc chọn :attribute.',
    'required_if' => 'Vui lòng nhập hoặc chọn :attribute.',
    'string' => ':Attribute phải là văn bản.',
    'email' => ':Attribute không đúng định dạng email.',
    'integer' => ':Attribute phải là số nguyên.',
    'boolean' => ':Attribute không hợp lệ.',
    'in' => 'Giá trị đã chọn cho :attribute không hợp lệ.',
    'exists' => 'Giá trị đã chọn cho :attribute không tồn tại.',
    'unique' => ':Attribute đã được sử dụng.',
    'date_format' => ':Attribute có định dạng không hợp lệ.',
    'min' => [
        'numeric' => ':Attribute phải lớn hơn hoặc bằng :min.',
        'string' => ':Attribute phải có ít nhất :min ký tự.',
    ],
    'max' => [
        'numeric' => ':Attribute phải nhỏ hơn hoặc bằng :max.',
        'string' => ':Attribute không được vượt quá :max ký tự.',
        'file' => ':Attribute không được vượt quá :max KB.',
    ],
];
