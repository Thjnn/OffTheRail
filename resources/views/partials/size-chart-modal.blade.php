{{-- Bảng size của danh mục. Mở bằng event `open-size-chart` phát từ bộ chọn biến thể. --}}
<div x-data="{ open: false }" x-show="open" x-cloak @open-size-chart.window="open = true" @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 grid place-items-end sm:place-items-center" role="dialog" aria-modal="true" aria-label="Bảng size {{ $categoryName }}">
    <div class="absolute inset-0 bg-black/50" x-show="open" x-transition.opacity @click="open = false"></div>

    <div class="relative max-h-[85vh] w-full overflow-auto rounded-t-[22px] bg-surface p-6 shadow-lift sm:max-w-xl sm:rounded-card" x-show="open" x-transition>
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="font-serif text-2xl">Bảng size · {{ $categoryName }}</h2>
            <button type="button" class="icon-btn text-xl" @click="open = false" aria-label="Đóng bảng size">×</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs text-muted">
                        @foreach ($chart['columns'] ?? [] as $column)
                            <th class="py-2 pr-4 font-semibold whitespace-nowrap">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($chart['rows'] as $row)
                        <tr>
                            @foreach ((array) $row as $cell)
                                <td class="py-2.5 pr-4 whitespace-nowrap {{ $loop->first ? 'font-semibold' : '' }}">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-xs text-muted">Số đo có thể lệch 1–2 cm. Phân vân giữa hai size? Nhắn shop để được tư vấn.</p>
    </div>
</div>
