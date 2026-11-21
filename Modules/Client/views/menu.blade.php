@if($items)
    <ul @if($nested ?? false) class="submenu" @endif>
        @foreach($items as $menuItem)
            <li>
                @if($menuItem['url'])
                    <a href="{{ $menuItem['url'] }}">{{ $menuItem['name'] }}@if($menuItem['children'] && !($nested ?? false)) <i class="fas fa-chevron-down caret" aria-hidden="true"></i>@endif</a>
                @else
                    <span>{{ $menuItem['name'] }}</span>
                @endif
                @if($menuItem['children'])
                    @include('client::menu', ['items' => $menuItem['children'], 'nested' => true])
                @endif
            </li>
        @endforeach
    </ul>
@endif
