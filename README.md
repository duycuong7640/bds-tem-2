# Bản mẫu bất động sản LBTech

Dữ liệu tiếng Việt gồm menu, danh mục tin bán và cho thuê, chuyên mục tin tức, tin bất động sản, bài viết, slide, banner quảng cáo, bảng giá VIP và cấu hình hiển thị. Danh mục được lưu dưới dạng menu con. `WEBFX_TEMPLATE_SAMPLE=furniture` là tên chế độ đọc bản chụp dùng chung của WebFX.

## Chuẩn bị và cập nhật dữ liệu mẫu

Khởi động môi trường local và chạy migration.

```sh
docker compose up -d
docker compose exec -T app php artisan migrate
```

Nếu database local chưa có nội dung, chạy `docker compose exec -T app php artisan db:seed`. Lệnh dùng bản chụp sẵn có. Khi chưa có bản chụp, lệnh dùng dữ liệu bất động sản gốc để khởi tạo. Mở `/admin/login` ở cổng ứng dụng local để chỉnh nội dung và ảnh. Giữ cùng một database local làm nguồn cho các lần xuất để ID bản ghi nguồn ổn định giữa các bản build.

Sau khi chỉnh trong admin, xuất lại dữ liệu rồi chạy build. Có thể tăng `WEBFX_TEMPLATE_VERSION` trong `.env` trước khi build để tạo bản phát hành mới.

```sh
docker compose exec -T app php artisan webfx:export-sample
sh build-webfx.sh
```

Lệnh xuất ghi `database/demo-data/furniture.json` và chép ảnh upload được tham chiếu vào `database/demo-images/storage/`. Đường dẫn `/storage/…` trên website giữ nguyên. Ảnh từ URL ngoài tiếp tục dùng URL gốc. Khi ảnh local bị thiếu, lệnh liệt kê đường dẫn và giữ bản chụp trước đó. Sửa ảnh trong admin hoặc khôi phục tệp rồi xuất lại trước khi build.

Bản chụp dùng tin đã duyệt và giữ trạng thái hiển thị của tin. Tin mẫu được nạp với thời hạn mở để website mới hiển thị được sau ngày build. Bản chụp chỉ lấy cấu hình hiển thị công khai, gồm thông tin doanh nghiệp hiển thị trên website. Tài khoản, dữ liệu liên hệ gửi từ khách, thông tin riêng của người đăng, giao dịch ví, thông tin nhận chuyển khoản và thông tin xác thực được loại khỏi bản chụp.

Build tạo tag `webfx-bds-tem-2:v<VERSION>` theo cấu hình mặc định và ghi `release.json` để nhập vào WebFX. Lưu JSON và thư mục ảnh cùng mã nguồn để bản build sau có thể dùng lại bản chụp.

## Nạp vào website

Website mới nạp bản chụp khi `webfx-laravel-init` chạy migration và `webfx:bootstrap`. Với website đã cài, sau khi triển khai image mới và chạy migration, chạy lệnh sau trong container của website.

```sh
php artisan webfx:sample-sync --force
```

Lệnh bổ sung các bản ghi và tệp ảnh còn thiếu. Website ghi nhớ bản ghi đã đồng bộ để giữ chỉnh sửa trong admin kể cả khi đổi tiêu đề, slug hoặc URL. Dữ liệu từ website cài trước khi có bản chụp được nhận diện theo các trường sẵn có, như slug, mã tin hoặc tên gói.

## Chạy kiểm thử với MySQL

Bộ kiểm thử dùng MySQL 8.4 trong service `mysql-test`. Service lưu dữ liệu kiểm thử tạm thời trong bộ nhớ và dùng database riêng cho các lần chạy migration của PHPUnit.

```sh
docker compose --profile test up -d --wait mysql-test
docker compose --profile test run --rm app-test php artisan test
docker compose --profile test stop mysql-test
```
