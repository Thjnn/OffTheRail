<div x-data="{ filters: false }" class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-10">
    {{-- Bộ lọc: ngăn kéo trượt trên điện thoại, cột trái trên máy tính --}}
    <div x-show="filters" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/45 lg:hidden" @click="filters = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 w-[84%] max-w-[340px] -translate-x-full overflow-y-auto bg-surface p-6 transition duration-300 lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0"
        :class="filters && '!translate-x-0'" aria-label="Bộ lọc sản phẩm" @keydown.escape.window="filters = false">
        <div class="mb-5 flex items-center justify-between lg:hidden">
            <p class="font-serif text-xl">Bộ lọc</p>
            <button type="button" class="icon-btn text-xl" @click="filters = false" aria-label="Đóng bộ lọc">×</button>
        </div>

        <div class="space-y-7 text-sm">
            <div>
                <p class="eyebrow mb-3">Danh mục</p>
                <div class="flex flex-col items-start gap-2">
                    <button type="button" wire:click="$set('danhMuc', '')" class="{{ $danhMuc === '' ? 'font-semibold text-accent' : 'hover:text-accent' }}">Tất cả</button>
                    @foreach ($categories as $category)
                        <button type="button" wire:click="$set('danhMuc', @js($category->slug))"
                            class="{{ $danhMuc === $category->slug ? 'font-semibold text-accent' : 'hover:text-accent' }}">{{ $category->name }}</button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="eyebrow mb-3">Size còn hàng</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($sizes as $option)
                        <button type="button" wire:click="$set('size', @js($size === $option ? '' : $option))" aria-pressed="{{ $size === $option ? 'true' : 'false' }}"
                            class="min-w-10 rounded-chip border px-2.5 py-1.5 text-xs {{ $size === $option ? 'border-ink bg-ink text-bg' : 'border-line bg-surface hover:border-accent' }}">{{ $option }}</button>
                    @endforeach
                </div>
            </div>

            @if ($colors->isNotEmpty())
                <div>
                    <p class="eyebrow mb-3">Màu</p>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($colors as $color)
                            <button type="button" wire:click="$set('mau', @js($mau === $color->name ? '' : $color->name))"
                                title="{{ $color->name }}" aria-label="{{ $color->name }}" aria-pressed="{{ $mau === $color->name ? 'true' : 'false' }}"
                                class="size-7 rounded-full border-2 border-surface ring-1 {{ $mau === $color->name ? 'ring-2 ring-accent' : 'ring-line' }}"
                                style="background: {{ $color->hex }}"></button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <p class="eyebrow mb-3">Khoảng giá (đ)</p>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" step="10000" inputmode="numeric" placeholder="Từ" aria-label="Giá từ" wire:model.live.debounce.500ms="giaTu"
                        class="w-full rounded-chip border border-line bg-surface px-3 py-2 outline-none focus:border-accent">
                    <span class="text-muted">–</span>
                    <input type="number" min="0" step="10000" inputmode="numeric" placeholder="Đến" aria-label="Giá đến" wire:model.live.debounce.500ms="giaDen"
                        class="w-full rounded-chip border border-line bg-surface px-3 py-2 outline-none focus:border-accent">
                </div>
            </div>

            @if ($hasFilters)
                <button type="button" wire:click="clearFilters" class="font-semibold underline underline-offset-4 hover:text-accent">Xoá bộ lọc</button>
            @endif
        </div>

        <button type="button" class="btn btn-primary mt-8 w-full lg:hidden" @click="filters = false">Xem {{ $products->total() }} sản phẩm</button>
    </aside>

    <div>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-outline !px-4 !py-2 lg:hidden" @click="filters = true">Bộ lọc</button>
                <p class="text-sm text-muted" aria-live="polite">{{ $products->total() }} sản phẩm{{ $q !== '' ? ' cho “'.$q.'”' : '' }}</p>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <span class="sr-only">Sắp xếp</span>
                <select wire:model.live="sapXep" class="rounded-full border border-line bg-surface px-4 py-2 outline-none focus:border-accent">
                    @foreach (\App\Livewire\ProductList::SORTS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div wire:loading.class="opacity-50" class="transition-opacity">
            @if ($products->isEmpty())
                <div class="rounded-card bg-surface-2 px-6 py-16 text-center">
                    <p class="font-serif text-2xl">Chưa có sản phẩm phù hợp</p>
                    <p class="mt-2 text-sm text-muted">Thử bỏ bớt bộ lọc hoặc tìm với từ khoá khác.</p>
                    @if ($hasFilters)
                        <button type="button" wire:click="clearFilters" class="btn btn-outline mt-5">Xoá bộ lọc</button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:gap-x-6" data-stagger>
                    @foreach ($products as $product)
                        <div wire:key="product-{{ $product->id }}">
                            @include('partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>

                <div class="mt-10">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
