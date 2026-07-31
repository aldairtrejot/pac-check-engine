<li class="nav-item">
    <a class="nav-link cap-nav-link {{ Request::is($active . '*') ? 'active' : '' }}" href="{{ route($route) }}">
        <div
            class="icon icon-shape icon-sm shadow border-radius-md text-center me-2 d-flex align-items-center justify-content-center cap-nav-icon">
            <i class="{{ $icon }}"></i>
        </div>
        <span class="nav-link-text ms-1">{{ $title }}</span>
    </a>
</li>
