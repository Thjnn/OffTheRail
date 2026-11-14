<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AboutEditor extends Component
{
    use OwnerOnly;

    public string $body = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->body = (string) shop('about_body');
    }

    public function save(ShopSettings $settings): void
    {
        $this->validate(['body' => ['nullable', 'string', 'max:5000']], [], ['body' => 'nội dung']);
        $settings->set('about_body', trim($this->body));
        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.admin.about-editor');
    }
}
