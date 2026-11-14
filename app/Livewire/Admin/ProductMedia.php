<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Bộ ảnh (gắn theo màu, kéo thả thứ tự) và các khối ảnh ↔ chữ của một sản phẩm. */
class ProductMedia extends Component
{
    use OwnerOnly, WithFileUploads;

    public const IMAGE_RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];

    public Product $product;

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var list<array{id: ?int, body: string}> */
    public array $blocks = [];

    /** @var array<int, mixed> ảnh mới cho khối theo chỉ số */
    public array $blockImages = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadBlocks();
    }

    public function upload(): void
    {
        $this->validate([
            'uploads' => ['required', 'array', 'max:10'],
            'uploads.*' => self::IMAGE_RULES,
        ], [], ['uploads' => 'ảnh', 'uploads.*' => 'ảnh']);

        $position = (int) $this->product->images()->max('position') + 1;

        foreach ($this->uploads as $file) {
            $this->product->images()->create([
                'path' => $file->store('products/'.$this->product->id, 'public'),
                'position' => $position++,
            ]);
        }

        $this->uploads = [];
    }

    public function setColor(int $imageId, ?int $colorId): void
    {
        $image = $this->product->images()->findOrFail($imageId);
        $valid = $colorId === null || $this->product->colors()->whereKey($colorId)->exists();

        if ($valid) {
            $image->update(['product_color_id' => $colorId]);
        }
    }

    /** Gọi từ wire:sort: đưa ảnh $id tới vị trí $position (tính từ 0). */
    public function sortImages(int $id, int $position): void
    {
        $this->move($this->product->images(), $id, $position);
    }

    public function sortBlocks(int $id, int $position): void
    {
        $this->move($this->product->blocks(), $id, $position);
        $this->loadBlocks();
    }

    public function deleteImage(int $id): void
    {
        $image = $this->product->images()->findOrFail($id);
        $image->delete();
        Storage::disk('public')->delete($image->path);
    }

    public function addBlock(): void
    {
        $this->blocks[] = ['id' => null, 'body' => ''];
    }

    public function saveBlock(int $index): void
    {
        $row = $this->blocks[$index] ?? abort(404);
        $block = $row['id'] ? $this->product->blocks()->findOrFail($row['id']) : null;
        $hasImage = isset($this->blockImages[$index]) || $block?->image_path;

        $this->validate([
            "blocks.$index.body" => [$hasImage ? 'nullable' : 'required', 'string', 'max:2000'],
            "blockImages.$index" => ['nullable', ...self::IMAGE_RULES],
        ], ['required' => 'Khối cần có ảnh hoặc chữ.'], ["blocks.$index.body" => 'nội dung', "blockImages.$index" => 'ảnh']);

        $data = ['body' => trim($row['body']) ?: null];

        if (isset($this->blockImages[$index])) {
            $data['image_path'] = $this->blockImages[$index]->store('products/'.$this->product->id, 'public');

            if ($block?->image_path) {
                Storage::disk('public')->delete($block->image_path);
            }
        }

        if ($block) {
            $block->update($data);
        } else {
            $this->product->blocks()->create($data + ['position' => (int) $this->product->blocks()->max('position') + 1]);
        }

        unset($this->blockImages[$index]);
        $this->loadBlocks();
    }

    public function deleteBlock(int $id): void
    {
        $block = $this->product->blocks()->findOrFail($id);
        $block->delete();

        if ($block->image_path) {
            Storage::disk('public')->delete($block->image_path);
        }

        $this->loadBlocks();
    }

    private function move(HasMany $relation, int $id, int $position): void
    {
        $ids = $relation->pluck('id')->all();
        abort_unless(in_array($id, $ids, true), 404);

        $ids = array_values(array_diff($ids, [$id]));
        array_splice($ids, max(0, min($position, count($ids))), 0, [$id]);

        foreach ($ids as $i => $itemId) {
            $relation->getRelated()->newQuery()->whereKey($itemId)->update(['position' => $i]);
        }
    }

    private function loadBlocks(): void
    {
        $this->blocks = $this->product->blocks()->get()
            ->map(fn ($b) => ['id' => $b->id, 'body' => (string) $b->body])->all();
    }

    public function render(): View
    {
        return view('livewire.admin.product-media', [
            'images' => $this->product->images()->get(),
            'colors' => $this->product->colors()->get(),
            'blockModels' => $this->product->blocks()->get()->keyBy('id'),
        ]);
    }
}
