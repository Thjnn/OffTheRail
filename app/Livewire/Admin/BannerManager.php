<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BannerManager extends Component
{
    use OwnerOnly, WithFileUploads;

    /** Link nội bộ (/...) hoặc https://; chặn javascript:, //host... */
    public const LINK_RULE = ['nullable', 'string', 'max:255', 'regex:#^(/(?!/)|https://)#'];

    public $image = null;

    public string $title = '';

    public string $subtitle = '';

    public string $link = '';

    public string $link_label = '';

    /** @var array<int, array{title: string, subtitle: string, link: string, link_label: string}> */
    public array $rows = [];

    /** @var array<int, mixed> ảnh thay thế theo id banner */
    public array $replacements = [];

    public function mount(): void
    {
        $this->loadRows();
    }

    public function create(): void
    {
        $data = $this->validate([
            'image' => ['required', ...ProductMedia::IMAGE_RULES],
            'title' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'link' => self::LINK_RULE,
            'link_label' => ['nullable', 'string', 'max:40'],
        ], ['link.regex' => 'Link phải bắt đầu bằng / (trang trong shop) hoặc https://'], ['image' => 'ảnh', 'title' => 'tiêu đề', 'link' => 'link']);

        Banner::create([
            'image' => $this->image->store('banners', 'public'),
            'title' => $data['title'] ?: null,
            'subtitle' => $data['subtitle'] ?: null,
            'link' => $data['link'] ?: null,
            'link_label' => $data['link_label'] ?: null,
            'position' => (int) Banner::max('position') + 1,
        ]);

        $this->reset('image', 'title', 'subtitle', 'link', 'link_label');
        $this->loadRows();
    }

    public function update(int $id): void
    {
        $banner = Banner::findOrFail($id);
        $this->validate([
            "rows.$id.title" => ['nullable', 'string', 'max:120'],
            "rows.$id.subtitle" => ['nullable', 'string', 'max:200'],
            "rows.$id.link" => self::LINK_RULE,
            "rows.$id.link_label" => ['nullable', 'string', 'max:40'],
            "replacements.$id" => ['nullable', ...ProductMedia::IMAGE_RULES],
        ], ["rows.$id.link.regex" => 'Link phải bắt đầu bằng / (trang trong shop) hoặc https://'], ["rows.$id.link" => 'link']);

        $data = array_map(fn ($v) => trim((string) $v) ?: null, $this->rows[$id]);

        if (isset($this->replacements[$id])) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $this->replacements[$id]->store('banners', 'public');
            unset($this->replacements[$id]);
        }

        $banner->update($data);
        $this->loadRows();
    }

    public function toggle(int $id): void
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => ! $banner->is_active]);
    }

    public function sort(int $id, int $position): void
    {
        $ids = Banner::orderBy('position')->orderBy('id')->pluck('id')->all();
        abort_unless(in_array($id, $ids, true), 404);
        $ids = array_values(array_diff($ids, [$id]));
        array_splice($ids, max(0, min($position, count($ids))), 0, [$id]);

        foreach ($ids as $i => $bannerId) {
            Banner::whereKey($bannerId)->update(['position' => $i]);
        }
    }

    public function delete(int $id): void
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();
        Storage::disk('public')->delete($banner->image);
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->rows = Banner::all()->mapWithKeys(fn (Banner $b) => [$b->id => [
            'title' => (string) $b->title, 'subtitle' => (string) $b->subtitle,
            'link' => (string) $b->link, 'link_label' => (string) $b->link_label,
        ]])->all();
    }

    public function render(): View
    {
        return view('livewire.admin.banner-manager', ['banners' => Banner::orderBy('position')->orderBy('id')->get()]);
    }
}
