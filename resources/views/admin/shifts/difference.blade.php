@if ($value === null)
    <span class="text-a-muted">—</span>
@elseif ($value < 0)
    <span class="text-a-down">{{ \App\Support\Money::vnd($value) }}</span>
@elseif ($value > 0)
    <span class="text-a-up">+{{ \App\Support\Money::vnd($value) }}</span>
@else
    <span>0đ</span>
@endif
