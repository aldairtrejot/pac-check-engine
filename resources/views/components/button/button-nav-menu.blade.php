<li class="nav-item">
    <a class="nav-link {{ Request::is($active . '*') ? 'active' : '' }}" href="{{ route($route) }}">
        <div
            class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="{{ $icon }}"></i>
        </div>
        <span class="nav-link-text ms-1">{{ $title }}</span>
    </a>
</li>
