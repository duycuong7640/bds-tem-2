#!/usr/bin/env python3
"""Build a customer ZIP with a fixed issuer and ionCube-enforced PHP files."""

import base64
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile
from urllib.parse import urlsplit
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).resolve().parent.parent


def required(name):
    value = os.environ.get(name, "")
    if not value:
        raise SystemExit(f"Thiếu biến {name}.")
    return value


def main():
    if len(sys.argv) != 2:
        raise SystemExit("Dùng: python3 scripts/package-licensed.py /đường/dẫn/ten-mau.zip")

    encoder = shutil.which(os.environ.get("IONCUBE_ENCODER", "ioncube_encoder"))
    if not encoder:
        raise SystemExit("Cần ionCube Encoder Pro/Cerberus và đường dẫn IONCUBE_ENCODER. Chưa tạo ZIP.")
    passphrase = required("IONCUBE_LICENSE_PASSPHRASE")
    issuer = urlsplit(required("LICENSE_ISSUER"))
    public_key = required("LICENSE_PUBLIC_KEY")
    template = required("WEBFX_TEMPLATE_SLUG")
    if (issuer.scheme != "https" or not issuer.hostname or issuer.username or issuer.password
            or not re.fullmatch(r"[a-zA-Z0-9.-]+(?::[0-9]+)?", issuer.netloc)
            or issuer.path not in ("", "/") or issuer.query or issuer.fragment):
        raise SystemExit("LICENSE_ISSUER phải là origin HTTPS của WebFX.")
    try:
        valid_key = len(base64.b64decode(public_key, validate=True)) == 32
    except ValueError:
        valid_key = False
    if not valid_key or not re.fullmatch(r"(?=.{1,58}$)[a-z0-9]+(?:-[a-z0-9]+)*", template):
        raise SystemExit("Khóa công khai Ed25519 hoặc mã mẫu không hợp lệ.")

    output = Path(sys.argv[1]).resolve()
    if output.exists():
        raise SystemExit("Tệp đầu ra đã tồn tại. Chọn tên khác.")
    for asset in ("resources/editor/build/editor.js", "public/studio/editor.css"):
        if not (ROOT / asset).is_file():
            raise SystemExit("Chạy npm ci và npm run build trước khi đóng gói.")

    with tempfile.TemporaryDirectory(prefix="webfx-license-") as scratch:
        scratch = Path(scratch)
        package = scratch / "website"
        package.mkdir()
        folders = ("app", "bootstrap", "config", "database", "lang", "Modules", "public", "resources", "routes")
        ignore = shutil.ignore_patterns(
            ".env", ".env.*", ".DS_Store", "*.sqlite*", "*.log",
            "LocalAdminSeeder.php", "storage", "vendor", "cache", "node_modules",
        )
        for folder in folders:
            shutil.copytree(ROOT / folder, package / folder, ignore=ignore, symlinks=True)
        for name in ("artisan", "composer.json", "composer.lock"):
            shutil.copy2(ROOT / name, package)
        for folder in ("bootstrap/cache", "storage/app/public", "storage/app/private",
                       "storage/framework/cache/data", "storage/framework/sessions",
                       "storage/framework/views", "storage/logs"):
            (package / folder).mkdir(parents=True, exist_ok=True)
        source_env = (ROOT / ".env.example").read_text().replace("APP_ENV=local", "APP_ENV=production")
        (package / ".env.example").write_text("\n".join(
            line for line in source_env.splitlines()
            if not line.startswith(("LICENSE_REQUIRED=", "LICENSE_ISSUER=", "LICENSE_PUBLIC_KEY="))
        ) + "\n")
        gate = package / "app/Services/LicenseGate.php"
        s = gate.read_text()
        for expr, value in {
            "config('license.issuer')": f"'https://{issuer.netloc}'",
            "config('license.public_key')": f"'{public_key}'",
            "config('license.template')": f"'{template}'",
        }.items():
            if expr not in s:
                raise SystemExit("Không tìm thấy cấu hình cố định cho lõi license.")
            s = s.replace(expr, value)
        gate.write_text(s)
        middleware = package / "app/Http/Middleware/RequireWebsiteLicense.php"
        middleware.write_text(middleware.read_text().replace("config('license.required') && ", ""))
        seeder = package / "database/seeders/DatabaseSeeder.php"
        contents = seeder.read_text()
        contents = re.sub(r"        if \(app\(\)->environment\('local'\).*?\n        }\n", "", contents, flags=re.S)
        if "LocalAdminSeeder" in contents:
            raise SystemExit("Không thể loại seeder tài khoản local khỏi bản bàn giao.")
        seeder.write_text(contents)

        files = [package / name for name in ("bootstrap/app.php", "public/index.php", "config/license.php")]
        for folder in ("app", "Modules", "routes"):
            files.extend(path for path in (package / folder).rglob("*.php") if not path.name.endswith(".blade.php"))
        for path in files:
            encoded = scratch / "encoded.php"
            args = [encoder, "--with-license", "webfx-runtime.lic", "--passphrase", passphrase, str(path), "-o", str(encoded)]
            result = subprocess.run(args, capture_output=True)
            if result.returncode or not encoded.is_file() or encoded.read_bytes() == path.read_bytes():
                raise SystemExit(f"Encoder lỗi tại {path.relative_to(package)}. Chưa tạo ZIP.")
            encoded.replace(path)

        archive_path = scratch / "customer.zip"
        with ZipFile(archive_path, "w", ZIP_DEFLATED) as archive:
            for entry in sorted(package.rglob("*")):
                if entry.is_symlink():
                    raise SystemExit(f"Tệp liên kết không được đóng gói: {entry.relative_to(package)}")
                archive.write(entry, entry.relative_to(package))
        with output.open("xb") as destination, archive_path.open("rb") as source:
            shutil.copyfileobj(source, destination)

    print("Đã tạo ZIP. Hosting cần ionCube Loader và webfx-runtime.lic hợp lệ.")


if __name__ == "__main__":
    main()
