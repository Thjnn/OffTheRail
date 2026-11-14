<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Category;
use App\Support\SizeChart;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SizeChartEditor extends Component
{
    use OwnerOnly;

    public ?int $categoryId = null;

    public string $text = '';

    public bool $saved = false;

    public function mount(): void
    {
        $first = Category::orderBy('position')->orderBy('id')->first();
        $this->categoryId = $first?->id;
        $this->text = SizeChart::format($first?->size_chart);
    }

    public function updatedCategoryId(): void
    {
        $this->text = SizeChart::format(Category::find($this->categoryId)?->size_chart);
        $this->saved = false;
    }

    public function save(): void
    {
        $this->validate([
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'text' => ['nullable', 'string', 'max:3000'],
        ], [], ['categoryId' => 'danh mục', 'text' => 'bảng size']);

        Category::whereKey($this->categoryId)->first()->update(['size_chart' => SizeChart::parse($this->text)]);
        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.admin.size-chart-editor', ['categories' => Category::orderBy('position')->get(['id', 'name'])]);
    }
}
