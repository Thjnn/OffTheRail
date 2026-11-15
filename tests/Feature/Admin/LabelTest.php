<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_form_defaults_to_stock(): void
    {
        $v = $this->variant(['stock' => 3, 'barcode' => '893000000555']);

        $this->actingAs(User::factory()->create(), 'admin')->get("/admin/san-pham/{$v->product_id}/in-tem")
            ->assertOk()
            ->assertSee('893000000555')
            ->assertSee('name="copies['.$v->id.']"', false)
            ->assertSee('value="3"', false);
    }

    public function test_print_page_repeats_labels_and_ignores_foreign_variants(): void
    {
        $v = $this->variant(['stock' => 1, 'barcode' => '893000000556']);
        $other = $this->variant(['barcode' => '893000000999']);

        $html = $this->actingAs(User::factory()->create(), 'admin')
            ->get("/admin/san-pham/{$v->product_id}/in-tem?copies[{$v->id}]=2&copies[{$other->id}]=5&copies[999999]=1")
            ->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-barcode="893000000556"'));
        $this->assertSame(0, substr_count($html, 'data-barcode="893000000999"'));
    }

    public function test_too_many_labels_rejected(): void
    {
        $v = $this->variant();

        $this->actingAs(User::factory()->create(), 'admin')
            ->get("/admin/san-pham/{$v->product_id}/in-tem?copies[{$v->id}]=2001")
            ->assertSessionHasErrors('copies');
    }

    public function test_huge_copy_count_rejected_without_building_labels(): void
    {
        $v = $this->variant();

        $this->actingAs(User::factory()->create(), 'admin')
            ->get("/admin/san-pham/{$v->product_id}/in-tem?copies[{$v->id}]=999999999")
            ->assertSessionHasErrors('copies');
    }

    public function test_requires_admin_login(): void
    {
        $v = $this->variant();

        $this->get("/admin/san-pham/{$v->product_id}/in-tem")->assertRedirect('/admin/dang-nhap');
    }
}
