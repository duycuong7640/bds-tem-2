(() => {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const money = (value) => new Intl.NumberFormat('vi-VN').format(Math.max(0, Math.round(value))) + ' ₫';

    // Menu mobile
    const toggle = $('[data-nav-toggle]');
    const nav = $('[data-nav]');
    toggle?.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
    });

    // Xác nhận thao tác (xóa tin...)
    $$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    }));

    // Ô tìm kiếm: tab Bán / Cho thuê lọc danh mục và khoảng giá
    $$('[data-search]').forEach((form) => {
        const typeInput = $('[name="loai"]', form);
        const priceSelect = $('[name="gia"]', form);
        const ranges = JSON.parse(form.dataset.ranges || '{}');
        const apply = (type) => {
            typeInput.value = type;
            form.action = form.dataset[type === 'rent' ? 'rentAction' : 'saleAction'];
            $$('[data-tab]', form).forEach((tab) => tab.setAttribute('aria-selected', String(tab.dataset.tab === type)));
            $$('[name="danh-muc"] option[data-type]', form).forEach((option) => {
                option.hidden = option.dataset.type !== type;
                if (option.hidden && option.selected) option.parentElement.value = '';
            });
            if (priceSelect) {
                const current = priceSelect.value;
                priceSelect.innerHTML = '<option value="">Mức giá</option>' + Object.entries(ranges[type] || {})
                    .map(([value, label]) => `<option value="${value}"${value === current ? ' selected' : ''}>${label}</option>`).join('');
            }
        };
        $$('[data-tab]', form).forEach((tab) => tab.addEventListener('click', () => apply(tab.dataset.tab)));
        apply(typeInput.value || 'sale');
    });

    // Bộ lọc trang danh sách: đổi lựa chọn là gửi form
    $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form.submit()));

    // Album ảnh trang chi tiết
    $$('[data-gallery]').forEach((gallery) => {
        const main = $('[data-gallery-main]', gallery);
        const counter = $('[data-gallery-counter]', gallery);
        const thumbs = $$('[data-gallery-thumb]', gallery);
        let index = 0;
        const show = (next) => {
            if (!thumbs.length) return;
            index = (next + thumbs.length) % thumbs.length;
            main.src = thumbs[index].dataset.src;
            thumbs.forEach((thumb, i) => thumb.setAttribute('aria-current', String(i === index)));
            if (counter) counter.textContent = `${index + 1}/${thumbs.length}`;
            thumbs[index].scrollIntoView({ block: 'nearest', inline: 'nearest' });
        };
        thumbs.forEach((thumb, i) => thumb.addEventListener('click', () => show(i)));
        $('[data-gallery-prev]', gallery)?.addEventListener('click', () => show(index - 1));
        $('[data-gallery-next]', gallery)?.addEventListener('click', () => show(index + 1));
    });

    // Hiện số điện thoại
    $$('[data-phone]').forEach((button) => button.addEventListener('click', (event) => {
        if (button.dataset.revealed) return;
        event.preventDefault();
        button.dataset.revealed = '1';
        $('[data-phone-text]', button).textContent = button.dataset.phone;
        button.href = 'tel:' + button.dataset.phone.replace(/[^0-9+]/g, '');
    }));

    // Form đăng tin: tính phí theo gói × số ngày, so sánh số dư
    $$('[data-fee-form]').forEach((form) => {
        const balance = Number(form.dataset.balance || 0);
        const days = $('[name="days"]', form);
        const output = $('[data-fee-output]', form);
        const update = () => {
            const checked = $('[name="package_id"]:checked', form);
            if (!checked || !output) return;
            const price = Number(checked.dataset.price);
            const minDays = Number(checked.dataset.minDays || 1);
            $$('option', days).forEach((option) => { option.disabled = Number(option.value) < minDays; });
            if (Number(days.value) < minDays) {
                const firstAllowed = $$('option', days).find((option) => !option.disabled);
                if (firstAllowed) days.value = firstAllowed.value;
            }
            const total = price * Number(days.value || 0);
            $('[data-fee-package]', output).textContent = checked.dataset.name + ' – ' + money(price) + '/ngày';
            $('[data-fee-days]', output).textContent = days.value + ' ngày';
            $('[data-fee-total]', output).textContent = money(total);
            $('[data-fee-after]', output).textContent = money(balance - total);
            const short = total > balance;
            $('[data-fee-short]', output).hidden = !short;
            $('[data-fee-short-amount]', output).textContent = money(total - balance);
        };
        $$('[name="package_id"]', form).forEach((radio) => radio.addEventListener('change', update));
        days?.addEventListener('change', update);
        update();

        // Đơn vị giá "Thỏa thuận" thì khóa ô nhập giá
        const unit = $('[name="price_unit"]', form);
        const value = $('[name="price_value"]', form);
        const syncPrice = () => {
            const negotiable = unit.value === 'thoa-thuan';
            value.disabled = negotiable;
            if (negotiable) value.value = '';
        };
        unit?.addEventListener('change', syncPrice);
        if (unit) syncPrice();

        // Lọc danh mục và đơn vị giá theo hình thức
        const category = $('[name="category_id"]', form);
        const syncUnit = () => {
            const type = category.selectedOptions[0]?.dataset.type;
            const trieu = $('option[value="trieu"]', unit);
            const ty = $('option[value="ty"]', unit);
            if (trieu) trieu.textContent = type === 'rent' ? 'Triệu/tháng' : 'Triệu';
            if (ty) ty.hidden = type === 'rent';
            if (type === 'rent' && unit.value === 'ty') unit.value = 'trieu';
        };
        category?.addEventListener('change', syncUnit);
        if (category && unit) syncUnit();
    });

    // Xem trước ảnh tải lên
    $$('[data-upload]').forEach((zone) => {
        const input = $('input[type="file"]', zone);
        const preview = $(zone.dataset.upload);
        input.addEventListener('change', () => {
            preview.innerHTML = '';
            [...input.files].slice(0, 10).forEach((file) => {
                const figure = document.createElement('figure');
                const img = document.createElement('img');
                img.alt = file.name;
                img.src = URL.createObjectURL(file);
                figure.append(img);
                preview.append(figure);
            });
            const note = $('[data-upload-count]', zone);
            if (note) note.textContent = input.files.length ? `Đã chọn ${input.files.length} ảnh` : '';
        });
    });

    // Chọn nhanh số tiền nạp
    $$('[data-amount-presets]').forEach((group) => {
        const input = $(group.dataset.amountPresets);
        $$('button', group).forEach((button) => button.addEventListener('click', () => {
            input.value = button.dataset.value;
            $$('button', group).forEach((b) => b.classList.toggle('active', b === button));
            input.dispatchEvent(new Event('input'));
        }));
    });

    // Hiện/ẩn mật khẩu
    $$('[data-toggle-password]').forEach((button) => button.addEventListener('click', () => {
        const input = button.previousElementSibling;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        $('i', button).className = show ? 'far fa-eye-slash' : 'far fa-eye';
    }));

    // Cuộn tới lỗi đầu tiên sau khi gửi form
    const firstError = $('[aria-invalid="true"]');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => firstError.focus({ preventScroll: true }), 350);
    }

    // Về đầu trang
    const top = $('[data-back-top]');
    if (top) {
        const sync = () => { top.hidden = window.scrollY < 500; };
        window.addEventListener('scroll', sync, { passive: true });
        sync();
        top.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // Sao chép nội dung chuyển khoản
    $$('[data-copy]').forEach((button) => button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            const label = button.innerHTML;
            button.textContent = 'Đã chép';
            setTimeout(() => { button.innerHTML = label; }, 1500);
        } catch { /* clipboard bị chặn – bỏ qua */ }
    }));
})();
