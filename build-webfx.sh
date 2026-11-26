#!/bin/sh
set -eu
cd "$(dirname "$0")"

if [ ! -f .env ]; then
    printf '%s\n' 'Thiếu .env. Hãy copy .env.example thành .env và chỉnh tên mẫu.' >&2
    exit 1
fi

# Read only the public settings used to build the template.
read_setting() {
    awk -v key="$1" '
        $0 ~ "^[[:space:]]*" key "[[:space:]]*=" {
            line = substr($0, index($0, "=") + 1)
            sub(/^[[:space:]]+/, "", line)
            sub(/[[:space:]]+$/, "", line)
            quote = substr(line, 1, 1)
            if (quote == "\"" || quote == sprintf("%c", 39)) {
                line = substr(line, 2)
                sub(quote "[[:space:]]*(#.*)?$", "", line)
            } else {
                sub(/[[:space:]]+#.*$/, "", line)
            }
            value = line
        }
        END { print value }
    ' .env
}

name=$(read_setting APP_NAME)
slug=$(read_setting WEBFX_TEMPLATE_SLUG)
version=$(read_setting WEBFX_TEMPLATE_VERSION)
sample=$(read_setting WEBFX_TEMPLATE_SAMPLE)
sample=${sample:-furniture}
issuer=$(read_setting LICENSE_ISSUER)

if [ -z "$name" ]; then
    printf '%s\n' 'Cần APP_NAME trong .env.' >&2
    exit 1
fi
if ! printf '%s\n' "$slug" | grep -Eq '^[a-z0-9]([a-z0-9-]{0,56}[a-z0-9])?$'; then
    printf '%s\n' 'WEBFX_TEMPLATE_SLUG chỉ dùng chữ thường không dấu, số và dấu gạch ngang, tối đa 58 ký tự.' >&2
    exit 1
fi
if ! printf '%s\n' "$version" | grep -Eq '^[1-9][0-9]{0,8}$'; then
    printf '%s\n' 'WEBFX_TEMPLATE_VERSION phải là số nguyên dương, ví dụ 1.' >&2
    exit 1
fi
case "$sample" in
    furniture)
        if [ ! -s database/demo-data/furniture.json ]; then
            printf '%s\n' 'Thiếu bản chụp. Chạy docker compose exec -T app php artisan webfx:export-sample rồi build lại.' >&2
            exit 1
        fi
        ;;
    real-estate|empty) ;;
    *) printf '%s\n' 'WEBFX_TEMPLATE_SAMPLE phải là furniture, real-estate hoặc empty.' >&2; exit 1 ;;
esac

tag="webfx-$slug:v$version"
release="$slug-laravel-v$version"
docker build -f Dockerfile.webfx -t "$tag" \
    --build-arg "WEBFX_TEMPLATE_NAME=$name" \
    --build-arg "WEBFX_TEMPLATE_SAMPLE=$sample" \
    --build-arg "LICENSE_ISSUER=$issuer" .
image=$(docker image inspect "$tag" --format '{{.Id}}')

release_file=$(mktemp ./.release.XXXXXX)
trap 'test ! -e "$release_file" || rm "$release_file"' EXIT
cat > "$release_file" <<JSON
{
  "$release": {
    "runtime": "laravel",
    "image": "$image",
    "port": 80,
    "storage_path": "/var/www/html/storage",
    "init": ["/usr/local/bin/webfx-laravel-init"],
    "health_path": "/up",
    "admin_path": "/admin/login",
    "admin_login": "email"
  }
}
JSON
mv "$release_file" release.json
printf '\nMã mẫu: %s\nMã bản phát hành: %s\nImage: %s\nĐã tạo release.json để nhập vào WebFX.\n' "$slug" "$release" "$image"
