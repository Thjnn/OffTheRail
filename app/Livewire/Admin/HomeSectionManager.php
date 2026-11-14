<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\HomeSection;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class HomeSectionManager extends Component
{
    use OwnerOnly;

    public const TYPE_LABELS = [
        'new_arrivals' => 'Hàng mới',
        'categories' => 'Mua theo loại',
        'featured' => 'Bán chạy',
        'story' => 'Câu chuyện ngắn',
    ];

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public string $newType = 'new_arrivals';

    public function mount(): void
    {
        $this->loadRows();
    }

    public function save(int $id): void
    {
        $section = HomeSection::findOrFail($id);
        $p = "rows.$id";

        $rules = ["$p.title" => ['nullable', 'string', 'max:120']];

        if (in_array($section->type, ['new_arrivals', 'featured'], true)) {
            $rules["$p.limit"] = ['required', 'integer', 'min:1', 'max:24'];
        }

        if ($section->type === 'story') {
            $rules += [
                "$p.eyebrow" => ['nullable', 'string', 'max:60'],
                "$p.body" => ['nullable', 'string', 'max:1000'],
                "$p.link" => BannerManager::LINK_RULE,
                "$p.link_label" => ['nullable', 'string', 'max:40'],
            ];
        }

        $this->validate($rules, ["$p.link.regex" => 'Link phải bắt đầu bằng / (trang trong shop) hoặc https://'],
            ["$p.limit" => 'số sản phẩm', "$p.link" => 'link']);

        $row = $this->rows[$id];
        $config = match ($section->type) {
            'new_arrivals', 'featured' => ['limit' => (int) $row['limit']],
            'story' => array_map(fn ($v) => trim((string) $v), array_intersect_key($row, array_flip(['eyebrow', 'body', 'link', 'link_label']))),
            default => $section->config,
        };

        $section->update(['title' => trim((string) $row['title']) ?: null, 'config' => $config]);
    }

    public function toggle(int $id): void
    {
        $section = HomeSection::findOrFail($id);
        $section->update(['is_active' => ! $section->is_active]);
    }

    public function sort(int $id, int $position): void
    {
        $ids = HomeSection::orderBy('position')->orderBy('id')->pluck('id')->all();
        abort_unless(in_array($id, $ids, true), 404);
        $ids = array_values(array_diff($ids, [$id]));
        array_splice($ids, max(0, min($position, count($ids))), 0, [$id]);

        foreach ($ids as $i => $sectionId) {
            HomeSection::whereKey($sectionId)->update(['position' => $i]);
        }
    }

    public function add(): void
    {
        $this->validate(['newType' => ['required', Rule::in(HomeSection::TYPES)]], [], ['newType' => 'loại khối']);

        HomeSection::create([
            'type' => $this->newType,
            'title' => self::TYPE_LABELS[$this->newType],
            'config' => in_array($this->newType, ['new_arrivals', 'featured'], true) ? ['limit' => 8] : [],
            'position' => (int) HomeSection::max('position') + 1,
        ]);

        $this->loadRows();
    }

    public function delete(int $id): void
    {
        HomeSection::findOrFail($id)->delete();
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->rows = HomeSection::all()->mapWithKeys(fn (HomeSection $s) => [$s->id => [
            'title' => (string) $s->title,
            'limit' => $s->option('limit', 8),
            'eyebrow' => (string) $s->option('eyebrow'),
            'body' => (string) $s->option('body'),
            'link' => (string) $s->option('link'),
            'link_label' => (string) $s->option('link_label'),
        ]])->all();
    }

    public function render(): View
    {
        return view('livewire.admin.home-section-manager', [
            'sections' => HomeSection::orderBy('position')->orderBy('id')->get(),
        ]);
    }
}
