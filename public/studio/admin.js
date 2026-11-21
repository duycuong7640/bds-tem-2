const menu = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-scrim]');
const mobile = matchMedia('(max-width: 780px)');
let collapsed = false;
try {
    collapsed = localStorage.getItem('cms-sidebar') === 'collapsed';
} catch {}
let opened = false;

function syncSidebar() {
    document.body.classList.toggle('sidebar-collapsed', collapsed && !mobile.matches);
    document.body.classList.toggle('sidebar-open', opened && mobile.matches);
    menu?.setAttribute('aria-expanded', String(mobile.matches ? opened : !collapsed));
    backdrop.hidden = !(opened && mobile.matches);
}
function closeSidebar() {
    opened = false;
    syncSidebar();
    menu?.focus();
}
if (menu) {
    syncSidebar();
    mobile.addEventListener('change', () => {
        opened = false;
        syncSidebar();
    });
    menu.addEventListener('click', () => {
        if (mobile.matches) {
            opened = !opened;
        } else {
            collapsed = !collapsed;
            try {
                localStorage.setItem('cms-sidebar', collapsed ? 'collapsed' : 'expanded');
            } catch {}
        }
        syncSidebar();
    });
    backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && opened) closeSidebar();
    });
}

document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!confirm(form.dataset.confirm)) event.preventDefault();
    });
});

const picker = document.querySelector('[data-file-picker]');
let selectImage;
if (picker) {
    const frame = picker.querySelector('iframe');
    window.cmsChooseImage = callback => {
        selectImage = callback;
        frame.src = picker.dataset.url;
        picker.showModal();
    };
    picker.addEventListener('close', () => {
        selectImage = undefined;
        frame.removeAttribute('src');
    });
    addEventListener('message', event => {
        if (!picker.open || event.origin !== location.origin || event.source !== frame.contentWindow) return;
        const { type, url } = event.data ?? {};
        if (type !== 'cms:select-image' || typeof url !== 'string') return;
        const image = URL.parse(url, location.origin);
        if (!image || image.origin !== location.origin || !/^\/storage\/media\/library\/(?:[\w-]+\/)*[\w-]+\.(?:jpg|jpeg|png|gif|webp)$/i.test(image.pathname) || !/^https?:$/.test(image.protocol)) return;
        const apply = selectImage;
        picker.close();
        apply?.(image.pathname);
    });
}

document.addEventListener('click', event => {
    const button = event.target.closest('[data-pick-image], [data-remove-image]');
    const field = button?.closest('[data-image-field]');
    if (!field) return;

    const input = field.querySelector('[data-image-url]');
    const preview = field.querySelector('[data-image-preview]');
    const image = preview.querySelector('img');
    const update = url => {
        input.value = url;
        preview.hidden = !url;
        if (url) image.src = url;
        else image.removeAttribute('src');
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    if (button.hasAttribute('data-remove-image')) {
        update('');
        field.querySelector('[data-pick-image]')?.focus();
    } else {
        window.cmsChooseImage?.(update);
    }
});

const slugField = document.querySelector('[data-slug-field]');
const postTitle = slugField && document.querySelector('#field-name');
let customSlug = false;
slugField?.addEventListener('input', () => { customSlug = true; });
postTitle?.addEventListener('input', () => {
    if (customSlug) return;
    const slug = postTitle.value.toLowerCase().replace(/đ/g, 'd').normalize('NFD')
        .replace(/\p{M}/gu, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    slugField.placeholder = slug.slice(0, 180).replace(/-+$/, '') || 'Tự tạo khi để trống';
});

const publication = document.querySelector('[data-publication]');
if (publication) {
    const dateTime = publication.querySelector('[name="publication_at"]');
    const mode = publication.querySelector('[name="publication_time"]');
    const shortcuts = [...publication.querySelectorAll('[data-publication-choice]')];
    function markSelected() {
        const hour = dateTime.value.slice(11, 16);
        for (const button of shortcuts) {
            const choice = button.dataset.publicationChoice;
            button.setAttribute('aria-pressed', String(choice === (mode.value === 'now' ? 'now' : hour)));
        }
    }
    dateTime.addEventListener('input', () => {
        mode.value = 'custom';
        markSelected();
    });
    for (const button of shortcuts) {
        button.addEventListener('click', () => {
            const choice = button.dataset.publicationChoice;
            const current = new Intl.DateTimeFormat('sv-SE', {
                timeZone: 'Asia/Ho_Chi_Minh', dateStyle: 'short', timeStyle: 'short',
            }).format(new Date()).replace(' ', 'T');
            dateTime.value = choice === 'now' ? current : (dateTime.value || current).slice(0, 10) + 'T' + choice;
            mode.value = choice === 'now' ? 'now' : 'custom';
            markSelected();
        });
    }
    markSelected();
}

const campaignTrack = document.querySelector('.admin-campaign-track');
if (campaignTrack) {
    const viewport = campaignTrack.parentElement;
    const group = campaignTrack.firstElementChild;
    const copies = [];

    function updateCampaignLoop() {
        const distance = group.getBoundingClientRect().width;
        if (!distance || !viewport.clientWidth) {
            return;
        }

        const requiredCopies = Math.ceil(viewport.clientWidth / distance);
        while (copies.length < requiredCopies) {
            const copy = group.cloneNode(true);
            copy.dataset.campaignCopy = '';
            copy.setAttribute('aria-hidden', 'true');
            copy.querySelectorAll('a').forEach(link => { link.tabIndex = -1; });
            campaignTrack.append(copy);
            copies.push(copy);
        }
        while (copies.length > requiredCopies) {
            copies.pop().remove();
        }

        campaignTrack.style.setProperty('--campaign-distance', `-${distance}px`);
        campaignTrack.style.setProperty('--campaign-duration', `${distance / 55}s`);
        viewport.dataset.campaignLoop = '';
    }

    const campaignResize = new ResizeObserver(updateCampaignLoop);
    campaignResize.observe(viewport);
    campaignResize.observe(group);
    updateCampaignLoop();
}

document.querySelector('[data-campaign-pause]')?.addEventListener('click', event => {
    const button = event.currentTarget;
    const paused = button.getAttribute('aria-pressed') !== 'true';
    button.setAttribute('aria-pressed', String(paused));
    button.setAttribute('aria-label', paused ? 'Tiếp tục dòng quảng cáo' : 'Tạm dừng dòng quảng cáo');
});
