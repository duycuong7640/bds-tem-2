<?php

return [
    'editor_license' => env('CKEDITOR_LICENSE_KEY', 'GPL'),
    'resources' => [
        'menus' => [
            'name' => 'Menu', 'icon' => 'sitemap', 'table' => 'menus',
            'fields' => ['name' => 'Tên menu', 'url' => 'Đường dẫn', 'parent_id' => 'Menu cha', 'content_type' => 'Loại danh mục', 'position' => 'Thứ tự', 'active' => 'Hiển thị'],
        ],
        'products' => [
            'name' => 'Tin đăng BĐS', 'icon' => 'building', 'table' => 'products',
            'fields' => [
                'name' => 'Tiêu đề tin', 'sku' => 'Mã tin', 'price' => 'Giá (₫)', 'image' => 'Ảnh đại diện', 'body' => 'Mô tả chi tiết', 'active' => 'Hiển thị',
                'listing_type' => 'Hình thức', 'status' => 'Trạng thái duyệt', 'reject_reason' => 'Lý do từ chối', 'package_id' => 'Gói tin',
                'days' => 'Số ngày đăng', 'expires_at' => 'Ngày hết hạn', 'province' => 'Tỉnh/Thành phố', 'district' => 'Quận/Huyện',
                'address' => 'Địa chỉ', 'area' => 'Diện tích (m²)', 'bedrooms' => 'Số phòng ngủ', 'toilets' => 'Số toilet', 'floors' => 'Số tầng',
                'direction' => 'Hướng nhà', 'legal' => 'Pháp lý', 'contact_name' => 'Tên liên hệ', 'contact_phone' => 'Điện thoại liên hệ',
                'contact_email' => 'Email liên hệ', 'gallery' => 'Album ảnh',
            ],
        ],
        'posts' => [
            'name' => 'Bài viết', 'icon' => 'file-alt', 'table' => 'posts',
            'fields' => ['name' => 'Tiêu đề', 'image' => 'Ảnh đại diện', 'body' => 'Nội dung', 'active' => 'Xuất bản'],
        ],
        'packages' => [
            'name' => 'Gói tin & bảng giá', 'icon' => 'crown', 'table' => 'listing_packages',
            'fields' => [
                'name' => 'Tên gói', 'badge' => 'Nhãn hiển thị', 'color' => 'Màu nhãn', 'price_per_day' => 'Giá / ngày (₫)',
                'priority' => 'Độ ưu tiên hiển thị', 'min_days' => 'Số ngày tối thiểu', 'description' => 'Mô tả quyền lợi',
                'position' => 'Thứ tự', 'active' => 'Đang bán',
            ],
        ],
        'banners' => [
            'name' => 'Slide & quảng cáo', 'icon' => 'images', 'table' => 'banners',
            'fields' => ['name' => 'Tên ảnh', 'placement' => 'Vị trí', 'image' => 'Hình ảnh', 'url' => 'Liên kết', 'position' => 'Thứ tự', 'active' => 'Hiển thị'],
        ],
        'contacts' => [
            'name' => 'Liên hệ', 'icon' => 'envelope', 'table' => 'contacts',
            'fields' => ['name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Điện thoại', 'message' => 'Nội dung', 'status' => 'Trạng thái'],
        ],
        'subscribers' => [
            'name' => 'Đăng ký email', 'icon' => 'paper-plane', 'table' => 'subscribers',
            'fields' => ['email' => 'Email', 'active' => 'Đang đăng ký'],
        ],
        'users' => [
            'name' => 'Tài khoản quản trị', 'icon' => 'users', 'table' => 'users',
            'fields' => ['name' => 'Họ tên', 'email' => 'Email', 'role' => 'Vai trò', 'password' => 'Mật khẩu', 'active' => 'Hoạt động'],
        ],
    ],
    'post_publication_times' => ['09:00', '15:00', '19:00'],
    'post_categories' => ['home' => 'Trang chủ', 'blog' => 'Blog', 'activities' => 'Hoạt động'],
    // Vai trò quản trị. Thành viên đăng tin ngoài website dùng vai trò "member" và không vào được trang quản trị.
    'roles' => ['admin' => 'Admin', 'manager' => 'Manager', 'editor' => 'Editor'],
    'member_role' => 'member',
    'permission_groups' => [
        'dashboard' => 'Dashboard',
        'menus' => 'Menu',
        'products' => 'Tin đăng BĐS',
        'posts' => 'Bài viết',
        'packages' => 'Gói tin & bảng giá',
        'members' => 'Thành viên',
        'topups' => 'Nạp tiền & giao dịch',
        'banners' => 'Slide & quảng cáo',
        'contacts' => 'Liên hệ',
        'subscribers' => 'Đăng ký email',
        'settings' => 'Cấu hình website',
        'media' => 'Thư viện ảnh',
    ],
    'default_permissions' => [
        'manager' => ['dashboard.view', 'menus.view', 'menus.edit', 'products.view', 'products.edit', 'posts.view', 'posts.edit', 'packages.view', 'packages.edit', 'members.view', 'members.edit', 'topups.view', 'topups.edit', 'banners.view', 'banners.edit', 'contacts.view', 'contacts.edit', 'subscribers.view', 'subscribers.edit', 'settings.view', 'settings.edit', 'media.view', 'media.edit'],
        'editor' => ['dashboard.view', 'products.view', 'products.edit', 'posts.view', 'posts.edit', 'media.view', 'media.edit'],
    ],

    // Cấu hình website: nhóm => [khóa => [nhãn, kiểu, quy tắc]]
    'settings' => [
        'site_name' => 'Tên website', 'tagline' => 'Mô tả ngắn', 'email' => 'Email liên hệ',
        'phone' => 'Hotline', 'address' => 'Địa chỉ', 'logo' => 'Logo', 'seo_title' => 'Tiêu đề SEO', 'seo_description' => 'Mô tả SEO',
    ],
    'setting_groups' => [
        'Thông tin doanh nghiệp' => [
            'company_name' => ['Tên công ty', 'text', 'nullable|string|max:180'],
            'zalo' => ['Số Zalo', 'text', 'nullable|string|max:20|regex:/^[0-9 .+]{8,20}$/'],
            'working_hours' => ['Giờ làm việc', 'text', 'nullable|string|max:120'],
            'tax_code' => ['Mã số thuế', 'text', 'nullable|string|max:30'],
            'footer_about' => ['Giới thiệu ngắn ở chân trang', 'textarea', 'nullable|string|max:600'],
        ],
        'Bản đồ & mạng xã hội' => [
            'map_embed' => ['Link nhúng Google Maps (src của iframe)', 'url', ['nullable', 'string', 'max:2000', 'url', 'starts_with:https://www.google.com/maps']],
            'facebook' => ['Facebook', 'url', 'nullable|url|max:255'],
            'youtube' => ['YouTube', 'url', 'nullable|url|max:255'],
            'tiktok' => ['TikTok', 'url', 'nullable|url|max:255'],
        ],
        'Thanh toán & đăng tin' => [
            'bank_name' => ['Ngân hàng nhận tiền', 'text', 'nullable|string|max:120'],
            'bank_account' => ['Số tài khoản', 'text', 'nullable|string|max:40|regex:/^[0-9 ]+$/'],
            'bank_owner' => ['Chủ tài khoản', 'text', 'nullable|string|max:120'],
            'bank_branch' => ['Chi nhánh', 'text', 'nullable|string|max:120'],
            'transfer_prefix' => ['Tiền tố nội dung chuyển khoản', 'text', 'nullable|string|max:20|alpha_dash'],
            'min_topup' => ['Số tiền nạp tối thiểu (₫)', 'number', 'sometimes|required|integer|min:10000|max:1000000000'],
            'signup_bonus' => ['Tặng khi đăng ký (₫)', 'number', 'sometimes|required|integer|min:0|max:100000000'],
            'auto_approve' => ['Tự duyệt tin của thành viên', 'select', 'sometimes|required|in:0,1'],
            'posting_rules' => ['Quy định đăng tin', 'textarea', 'nullable|string|max:3000'],
        ],
    ],

    'listing_types' => ['sale' => 'Bán', 'rent' => 'Cho thuê'],
    'listing_statuses' => ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối'],
    'listing_days' => [7, 10, 15, 30, 60, 90],
    'directions' => ['Đông', 'Tây', 'Nam', 'Bắc', 'Đông Bắc', 'Đông Nam', 'Tây Bắc', 'Tây Nam'],
    'legal_options' => ['Sổ đỏ/Sổ hồng', 'Hợp đồng mua bán', 'Đang chờ sổ', 'Giấy tờ khác'],
    'area_ranges' => ['0-30' => 'Dưới 30 m²', '30-50' => '30 - 50 m²', '50-80' => '50 - 80 m²', '80-100' => '80 - 100 m²', '100-200' => '100 - 200 m²', '200-500' => '200 - 500 m²', '500-0' => 'Trên 500 m²'],
    'price_ranges' => [
        'sale' => ['0-1000000000' => 'Dưới 1 tỷ', '1000000000-3000000000' => '1 - 3 tỷ', '3000000000-5000000000' => '3 - 5 tỷ', '5000000000-10000000000' => '5 - 10 tỷ', '10000000000-0' => 'Trên 10 tỷ'],
        'rent' => ['0-5000000' => 'Dưới 5 triệu', '5000000-10000000' => '5 - 10 triệu', '10000000-20000000' => '10 - 20 triệu', '20000000-50000000' => '20 - 50 triệu', '50000000-0' => 'Trên 50 triệu'],
    ],
    'wallet_types' => ['topup' => 'Nạp tiền', 'listing_fee' => 'Phí đăng tin', 'refund' => 'Hoàn tiền', 'bonus' => 'Thưởng', 'adjust' => 'Điều chỉnh'],
    'provinces' => [
        'Hà Nội', 'Hồ Chí Minh', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ', 'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
        'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước', 'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông',
        'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 'Hậu Giang', 'Hòa Bình',
        'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định',
        'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị',
        'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang',
        'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
    ],
];
