<div>
    <form wire:submit="save" class="space-y-5">
        @error('variants')<div class="rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2 text-a-down" role="alert">{{ $message }}</div>@enderror
        @error('product')<div class="rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2 text-a-down" role="alert">{{ $message }}</div>@enderror

        <section class="a-card grid gap-4 p-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="a-label" for="name">Tên sản phẩm</label>
                <input id="name" wire:model="name" class="a-input" maxlength="150">
                @error('name')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="a-label" for="slug">Đường dẫn (để trống sẽ tự tạo từ tên)</label>
                <input id="slug" wire:model="slug" class="a-input font-mono" placeholder="ao-thun-may">
                @error('slug')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="a-label" for="category_id">Danh mục</label>
                <select id="category_id" wire:model="category_id" class="a-input">
                    <option value="">Chọn danh mục</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
                @error('category_id')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="a-label" for="price">Giá bán (đ)</label>
                <input id="price" type="number" min="0" step="1000" wire:model="price" class="a-input">
                @error('price')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="a-label" for="compare_at_price">Giá gốc gạch ngang (tuỳ chọn)</label>
                <input id="compare_at_price" type="number" min="0" step="1000" wire:model="compare_at_price" class="a-input">
                @error('compare_at_price')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="a-label" for="quote">Câu trích nghiêng (tuỳ chọn)</label>
                <input id="quote" wire:model="quote" class="a-input" maxlength="200">
                @error('quote')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="a-label" for="description">Mô tả</label>
                <textarea id="description" wire:model="description" rows="4" class="a-input" maxlength="5000"></textarea>
                @error('description')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="a-label" for="published_at">Ngày đăng (để tương lai thì chưa hiện trên web)</label>
                <input id="published_at" type="datetime-local" wire:model="published_at" class="a-input">
                @error('published_at')<p class="a-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end gap-5 pb-1.5">
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_active"> Đang bán</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_featured"> Nổi bật</label>
            </div>
        </section>

        <section class="a-card p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold">Màu</h2>
                <button type="button" wire:click="addColor" class="a-btn"><x-admin.icon name="plus" class="size-4" /> Thêm màu</button>
            </div>
            <div class="space-y-2">
                @forelse ($colors as $i => $color)
                    <div class="flex flex-wrap items-start gap-2" wire:key="color-{{ $i }}-{{ $color['id'] ?? 'new' }}">
                        <input type="color" wire:model.live="colors.{{ $i }}.hex" class="h-8 w-10 rounded border border-a-line" aria-label="Chọn màu">
                        <div>
                            <input wire:model.blur="colors.{{ $i }}.hex" class="a-input w-28 font-mono" aria-label="Mã màu">
                            @error("colors.$i.hex")<p class="a-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <input wire:model="colors.{{ $i }}.name" class="a-input w-48" placeholder="Tên màu, vd. Đen" aria-label="Tên màu">
                            @error("colors.$i.name")<p class="a-error">{{ $message }}</p>@enderror
                        </div>
                        <button type="button" wire:click="removeColor({{ $i }})" wire:confirm="Bỏ màu này cùng các biến thể của nó?" class="a-btn a-btn-danger" aria-label="Bỏ màu"><x-admin.icon name="trash" class="size-4" /></button>
                    </div>
                @empty
                    <p class="text-a-muted">Thêm ít nhất một màu để tạo biến thể.</p>
                @endforelse
            </div>
        </section>

        @if ($colors)
            <section class="a-card p-4">
                <h2 class="mb-1 font-semibold">Biến thể (màu × size)</h2>
                <p class="mb-3 text-a-muted">Bấm vào ô để bật/tắt. SKU và mã vạch để trống sẽ tự tạo.</p>

                <div class="mb-4 overflow-x-auto">
                    <table class="text-sm">
                        <thead><tr><th></th>@foreach ($sizes as $size)<th class="px-2 pb-1 font-medium">{{ $size }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($colors as $ci => $color)
                                <tr>
                                    <td class="pr-3"><span class="inline-block size-3 rounded-full align-middle" style="background: {{ preg_match('/^#[0-9A-Fa-f]{6}$/', $color['hex']) ? $color['hex'] : '#ccc' }}"></span> {{ $color['name'] ?: 'Màu '.($ci + 1) }}</td>
                                    @foreach ($sizes as $size)
                                        @php($on = collect($variants)->contains(fn ($v) => $v['color'] === $ci && $v['size'] === $size))
                                        <td class="p-1">
                                            <button type="button" wire:click="toggleCell({{ $ci }}, @js($size))" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                                class="size-8 rounded border {{ $on ? 'border-accent bg-accent text-white' : 'border-a-line bg-a-surface text-a-muted hover:border-accent' }}">{{ $on ? '✓' : '' }}</button>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($variants)
                    <div class="overflow-x-auto">
                        <table class="a-table">
                            <thead><tr><th>Màu / Size</th><th>SKU</th><th>Mã vạch</th><th>Giá riêng (đ)</th><th>Tồn kho</th></tr></thead>
                            <tbody>
                                @foreach ($variants as $i => $v)
                                    <tr wire:key="v-{{ $v['id'] ?? 'n' }}-{{ $v['color'] }}-{{ $v['size'] }}">
                                        <td class="whitespace-nowrap">{{ $colors[$v['color']]['name'] ?? '' }} / {{ $v['size'] }}
                                            @error("variants.$i.size")<p class="a-error">{{ $message }}</p>@enderror</td>
                                        <td><input wire:model="variants.{{ $i }}.sku" class="a-input w-36 font-mono" placeholder="tự tạo" aria-label="SKU">
                                            @error("variants.$i.sku")<p class="a-error">{{ $message }}</p>@enderror</td>
                                        <td><input wire:model="variants.{{ $i }}.barcode" class="a-input w-36 font-mono" placeholder="tự tạo" aria-label="Mã vạch">
                                            @error("variants.$i.barcode")<p class="a-error">{{ $message }}</p>@enderror</td>
                                        <td><input type="number" min="0" step="1000" wire:model="variants.{{ $i }}.price" class="a-input w-32" placeholder="theo giá chung" aria-label="Giá riêng">
                                            @error("variants.$i.price")<p class="a-error">{{ $message }}</p>@enderror</td>
                                        <td>
                                            @if ($v['id'])
                                                <span class="font-medium">{{ $v['current'] ?? 0 }}</span>
                                                <a href="{{ url('/admin/kho?q='.urlencode($v['sku'] ?? '')) }}" class="ml-1 text-xs text-accent hover:underline">Sửa ở Kho hàng</a>
                                            @else
                                                <input type="number" min="0" wire:model="variants.{{ $i }}.stock" class="a-input w-24" aria-label="Tồn ban đầu">
                                                @error("variants.$i.stock")<p class="a-error">{{ $message }}</p>@enderror
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        <div class="flex items-center gap-2">
            <button class="a-btn a-btn-primary" wire:loading.attr="disabled">Lưu sản phẩm</button>
            <a href="{{ url('/admin/san-pham') }}" class="a-btn">Huỷ</a>
            @if ($productId)
                <button type="button" wire:click="delete" wire:confirm="Xoá hẳn sản phẩm này? Không hoàn tác được." class="a-btn a-btn-danger ml-auto">
                    <x-admin.icon name="trash" class="size-4" /> Xoá sản phẩm
                </button>
            @endif
        </div>
    </form>
</div>
