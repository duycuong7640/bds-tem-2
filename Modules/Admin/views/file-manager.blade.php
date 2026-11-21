{{-- Adapted from UniSharp Laravel Filemanager v2.15.1 (MIT). --}}
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=EDGE" />
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Chrome, Firefox OS and Opera -->
  <meta name="theme-color" content="#333844">
  <!-- Windows Phone -->
  <meta name="msapplication-navbutton-color" content="#333844">
  <!-- iOS Safari -->
  <meta name="apple-mobile-web-app-status-bar-style" content="#333844">

  <title>{{ trans('laravel-filemanager::lfm.title-page') }}</title>
  <link rel="shortcut icon" type="image/png" href="{{ asset('vendor/laravel-filemanager/img/72px color.png') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/filemanager/css/bootstrap.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/filemanager/css/icons.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/laravel-filemanager/css/dropzone.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/laravel-filemanager/css/mime-icons.min.css') }}">
  <style>{!! \File::get(base_path('vendor/unisharp/laravel-filemanager/public/css/lfm.css')) !!}</style>
  {{-- <link rel="stylesheet" href="{{ asset('/vendor/laravel-filemanager/css/lfm.css') }}"> --}}
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="{{ asset('assets/admin/filemanager/css/workspace.css') }}?v={{ filemtime(public_path('assets/admin/filemanager/css/workspace.css')) }}">
</head>
<body class="webfx-file-manager">
  <div id="tree" hidden></div>
  <nav class="d-none" id="nav" aria-hidden="true"><div id="nav-buttons"><ul></ul></div></nav>
  <main id="main">
      <div id="alerts"></div>
      <header class="lfm-heading">
        <div>
          <h1>Thư viện ảnh</h1>
          @unless (request()->boolean('picker'))<p>UniSharp File Manager</p>@endunless
        </div>
        <div class="lfm-controls">
          <button type="button" class="btn btn-outline-primary" id="lfm-create-folder" @unless(admin_can('media.edit')) hidden @endunless aria-controls="lfm-folder-form" aria-expanded="false">Tạo thư mục</button>
          <button type="button" class="btn btn-primary" id="webfx-upload" @unless(admin_can('media.edit')) hidden @endunless data-toggle="modal" data-target="#uploadModal">Tải ảnh lên</button>
          @unless (request()->boolean('picker') || request()->boolean('embedded'))
            <a class="btn btn-outline-secondary" href="{{ route('admin.index', 'banners') }}">Quản lý ảnh</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.home') }}">Về Admin</a>
          @endunless
        </div>
      </header>

      <div class="lfm-toolbar" aria-label="Tìm kiếm và hiển thị ảnh">
        <div class="lfm-search" role="search">
          <input type="search" id="keyword" maxlength="120" placeholder="Tên ảnh" aria-label="Tìm theo tên ảnh" class="form-control">
          <button type="button" id="keyword-button" class="btn btn-outline-primary">Tìm</button>
          <button type="button" id="keyword-reset-button" class="btn btn-outline-secondary">Xóa lọc</button>
        </div>
        <div class="lfm-controls">
          <select id="lfm-sort" class="form-control" aria-label="Sắp xếp">
            <option value="time">Mới nhất</option>
            <option value="alphabetic">Theo tên</option>
          </select>
          <button type="button" class="btn btn-outline-secondary" data-display="grid" aria-label="Dạng lưới" aria-pressed="true"><i class="fas fa-th-large" aria-hidden="true"></i></button>
          <button type="button" class="btn btn-outline-secondary" data-display="list" aria-label="Dạng danh sách" aria-pressed="false"><i class="fas fa-list-ul" aria-hidden="true"></i></button>
        </div>
      </div>

      <form id="lfm-folder-form" action="{{ route('admin.media.folder') }}" method="post" hidden>
        @csrf
        <label for="lfm-folder-name">Tên thư mục</label>
        <div class="lfm-controls">
          <input id="lfm-folder-name" name="name" class="form-control" maxlength="80" required>
          <button class="btn btn-primary" type="submit">Tạo</button>
          <button class="btn btn-outline-secondary" id="lfm-cancel-folder" type="button">Hủy</button>
        </div>
        <p id="lfm-folder-error" role="alert"></p>
      </form>
      <nav class="lfm-folder-path" aria-label="Thư mục hiện tại">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="lfm-parent-folder" hidden>← Thư mục cha</button>
        <strong id="lfm-current-folder">Thư viện ảnh</strong>
      </nav>
      <div id="lfm-folders" class="lfm-folder-grid"></div>

      <div id="empty" class="d-none">
        <i class="far fa-image" aria-hidden="true"></i>
        <strong>Thư mục chưa có ảnh</strong>
        <span>Tải ảnh mới lên hoặc thử từ khóa khác.</span>
      </div>

      <div id="content"></div>
      <div id="pagination"></div>

      <a id="item-template" class="d-none" tabindex="0" role="button">
        <div class="square"></div>

        <div class="info">
          <div class="item_name text-truncate"></div>
          <time class="text-muted font-weight-light text-truncate"></time>
        </div>
      </a>
  </main>
  <div id="fab" hidden></div>
  <nav class="lfm-selection fixed-bottom d-none" id="actions" aria-label="Ảnh đã chọn">
    <button type="button" class="btn btn-outline-secondary" id="cancel_selection">Bỏ chọn</button>
    @if(admin_can('media.delete'))
      <button type="button" class="btn btn-outline-danger" id="cms-delete-image">Xóa ảnh</button>
    @endif
    <button type="button" class="btn btn-primary" data-action="use" data-multiple="true">{{ request()->boolean('picker') ? 'Chèn ảnh đã chọn' : 'Mở ảnh' }}</button>
  </nav>

  <div class="modal" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="myModalLabel">{{ trans('laravel-filemanager::lfm.title-upload') }}</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aia-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('admin.media.upload') }}" role='form' id='uploadForm' name='uploadForm' method='post' enctype='multipart/form-data' class="dropzone">
            <div class="form-group" id="attachment">
              <div class="controls text-center">
                <div class="input-group w-100">
                  <a class="btn btn-primary w-100 text-white" id="upload-button">{{ trans('laravel-filemanager::lfm.message-choose') }}</a>
                </div>
              </div>
            </div>
            <input type='hidden' name='working_dir' id='working_dir'>
            <input type='hidden' name='type' id='type' value='image'>
            <input type='hidden' name='_token' value='{{csrf_token()}}'>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="notify" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
          <button type="button" class="btn btn-primary w-100" data-dismiss="modal" id="confirm-button-yes">{{ trans('laravel-filemanager::lfm.btn-confirm') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"></h4>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
          <button type="button" class="btn btn-primary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-confirm') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div id="carouselTemplate" class="d-none carousel slide bg-light" data-ride="carousel">
    <ol class="carousel-indicators">
      <li data-target="#previewCarousel" data-slide-to="0" class="active"></li>
    </ol>
    <div class="carousel-inner">
      <div class="carousel-item active">
        <a class="carousel-label"></a>
        <div class="carousel-image"></div>
      </div>
    </div>
    <a class="carousel-control-prev" href="#previewCarousel" role="button" data-slide="prev">
      <div class="carousel-control-background" aria-hidden="true">
        <i class="fas fa-chevron-left"></i>
      </div>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#previewCarousel" role="button" data-slide="next">
      <div class="carousel-control-background" aria-hidden="true">
        <i class="fas fa-chevron-right"></i>
      </div>
      <span class="sr-only">Next</span>
    </a>
  </div>

  <script src="{{ asset('assets/admin/filemanager/jquery.js') }}"></script>
  <script src="{{ asset('assets/admin/filemanager/bootstrap.js') }}"></script>
  <script src="{{ asset('vendor/laravel-filemanager/js/dropzone.min.js') }}"></script>
  <script nonce="{{ request()->attributes->get('csp_nonce') }}">
    var lang = @json(trans('laravel-filemanager::lfm'));
    var actions = [];

    var sortings = [];
  </script>
  <script nonce="{{ request()->attributes->get('csp_nonce') }}">{!! \File::get(base_path('vendor/unisharp/laravel-filemanager/public/js/script.js')) !!}</script>
  {{-- <script src="{{ asset('vendor/laravel-filemanager/js/script.js') }}"></script> --}}
  <script nonce="{{ request()->attributes->get('csp_nonce') }}">
    @unless(admin_can('media.edit'))
      $(function () { $(window).off('dragenter'); });
    @endunless
    const isPicker = @json(request()->boolean('picker'));
    use = function (items) {
      const item = items[0];
      if (!item || !item.is_file) return;
      if (isPicker && window.parent !== window) {
        window.parent.postMessage({ type: 'cms:select-image', url: item.url }, location.origin);
      } else {
        window.open(item.url, '_blank', 'noopener');
      }
    };
    createPagination = function ({current_page: current, total, per_page: perPage}) {
      const last = Math.ceil(total / perPage);
      if (last < 2) return;

      const list = $('<ul>', {class: 'pagination flex-wrap', role: 'navigation', 'aria-label': 'Phân trang ảnh'});
      const add = (page, label, disabled = false, active = false) => {
        const row = $('<li>', {class: 'page-item', 'data-num': page});
        const link = $('<' + (disabled || active ? 'span' : 'a') + '>', {class: 'page-link', text: label});
        if (active) link.attr('aria-current', 'page');
        if (disabled) link.attr('aria-disabled', 'true');
        else if (!active) link.attr('href', '#pagination');
        row.toggleClass('active', active).toggleClass('disabled', disabled).append(link).appendTo(list);
      };
      add(current - 1, '← Trước', current === 1);
      const pages = [...new Set([1, 2, current - 1, current, current + 1, last - 1, last])]
        .filter(page => page >= 1 && page <= last).sort((left, right) => left - right);
      let previous = 0;
      for (const page of pages) {
        if (page - previous > 1) {
          if (page - previous === 2) add(page - 1, page - 1);
          else add(0, '…', true);
        }
        add(page, page, false, page === current);
        previous = page;
      }
      add(current + 1, 'Sau →', current === last);
      $('#pagination').append(list);
    };
    const canDelete = @json(admin_can('media.delete'));
    function deleteMedia(button, endpoint, payload, prompt) {
      const workingDir = document.querySelector('#working_dir').value;
      confirm(prompt, async () => {
        button.disabled = true;
        try {
          const response = await fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json'},
            body: JSON.stringify({...payload, working_dir: workingDir})
          });
          if (!response.ok) throw new Error();
          loadFolders();
        } catch {
          notify('Không xóa được mục đã chọn. Vui lòng thử lại.');
        } finally {
          button.disabled = false;
        }
      });
    }
    document.querySelector('#cms-delete-image')?.addEventListener('click', event => {
      const items = getSelectedItems().filter(item => item.is_file).map(item => item.name);
      if (!items.length) return;
      deleteMedia(event.currentTarget, @json(route('admin.media.delete', absolute: false)), {items}, 'Xóa ảnh đã chọn khỏi thư viện?');
    });
    // Render upload errors as text even when a proxy returns an HTML error page.
    const uploadError = document.createElement('p');
    uploadError.className = 'text-danger mt-2';
    uploadError.setAttribute('role', 'alert');
    document.querySelector('#uploadForm').after(uploadError);
    sort_type = 'time';
    document.querySelector('#lfm-sort').addEventListener('change', event => {
      sort_type = event.target.value;
      loadItems();
    });
    document.querySelectorAll('[data-display]').forEach(button => button.addEventListener('click', () => {
      document.querySelectorAll('[data-display]').forEach(option => option.setAttribute('aria-pressed', String(option === button)));
    }));
    document.querySelector('#content').addEventListener('keydown', event => {
      const image = event.target.closest('a[data-id]');
      if (image && (event.key === ' ' || event.key === 'Enter')) {
        event.preventDefault();
        image.click();
      }
    });
    displayErrorResponse = function () {
      notify('Không thể tải thư viện ảnh. Vui lòng thử lại hoặc đăng nhập lại.');
    };
    {
      const folderForm = document.querySelector('#lfm-folder-form');
      const createFolder = document.querySelector('#lfm-create-folder');
      const folderName = document.querySelector('#lfm-folder-name');
      const folderError = document.querySelector('#lfm-folder-error');
      const folderGrid = document.querySelector('#lfm-folders');
      const parentFolder = document.querySelector('#lfm-parent-folder');
      const workingDirectory = document.querySelector('#working_dir');

      function showFolderForm(open) {
        folderForm.hidden = !open;
        createFolder.setAttribute('aria-expanded', String(open));
        folderError.textContent = '';
        if (open) folderName.focus();
        else createFolder.focus();
      }
      createFolder.addEventListener('click', () => showFolderForm(folderForm.hidden));
      document.querySelector('#lfm-cancel-folder').addEventListener('click', () => showFolderForm(false));
      parentFolder.addEventListener('click', () => goTo(parentFolder.dataset.path));

      $(document).ajaxSuccess((event, response, options) => {
        if (!options.url.startsWith(lfm_route + '/jsonitems')) return;
        const directory = JSON.parse(response.responseText);
        document.querySelector('#lfm-current-folder').textContent = directory.folder_name;
        parentFolder.hidden = !directory.parent_dir;
        parentFolder.dataset.path = directory.parent_dir ?? '';
        folderGrid.replaceChildren(...directory.folders.map(folder => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'btn btn-outline-primary lfm-folder';
          const icon = document.createElement('i');
          icon.className = 'fas fa-folder';
          icon.setAttribute('aria-hidden', 'true');
          item.append(icon, document.createTextNode(folder.name));
          item.addEventListener('click', () => goTo(folder.url));
          if (!canDelete) return item;

          const group = document.createElement('div');
          group.className = 'lfm-folder-actions';
          const remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'btn btn-outline-danger';
          remove.title = 'Xóa thư mục';
          remove.setAttribute('aria-label', 'Xóa thư mục ' + folder.name);
          const trash = document.createElement('i');
          trash.className = 'far fa-trash-alt';
          trash.setAttribute('aria-hidden', 'true');
          remove.append(trash);
          remove.addEventListener('click', () => {
            deleteMedia(remove, @json(route('admin.media.delete-folder', absolute: false)), {folder: folder.name}, 'Xóa thư mục này cùng toàn bộ ảnh và thư mục con?');
          });
          group.append(item, remove);
          return group;
        }));
      });

      folderForm.addEventListener('submit', async event => {
        event.preventDefault();
        const data = new FormData(folderForm);
        data.set('working_dir', workingDirectory.value);
        const submit = folderForm.querySelector('[type="submit"]');
        submit.disabled = true;
        folderError.textContent = '';
        try {
          const response = await fetch(folderForm.action, { method: 'POST', headers: { Accept: 'application/json' }, body: data });
          if (!response.ok || (await response.clone().text()) !== 'OK') {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.errors?.name?.[0] ?? 'Không tạo được thư mục. Vui lòng thử lại.');
          }
          folderForm.reset();
          showFolderForm(false);
          loadItems();
        } catch (error) {
          folderError.textContent = error.message;
        } finally {
          submit.disabled = false;
        }
      });

    }

    Dropzone.options.uploadForm = {
      paramName: "upload[]", // The name that will be used to transfer the file
      uploadMultiple: false,
      parallelUploads: 5,
      timeout:0,
      clickable: '#upload-button',
      dictDefaultMessage: lang['message-drop'],
      init: function() {
        this.on('error', (file, message) => {
          uploadError.textContent = typeof message === 'string' ? message : 'Không thể tải ảnh.';
        });
        this.on('success', function(file, response) {
          if (response === 'OK') {
            uploadError.textContent = '';
            if (isPicker) $('#uploadModal').modal('hide');
            loadFolders();
          } else {
            this.emit('error', file, Array.isArray(response) ? response.join('\n') : 'Không thể tải ảnh.');
          }
        });
      },
      acceptedFiles: "{{ implode(',', $helper->availableMimeTypes()) }}",
      maxFilesize: ({{ $helper->maxUploadSize() }} / 1000)
    }

  </script>
</body>
</html>
