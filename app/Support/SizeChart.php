<?php

namespace App\Support;

/**
 * Bảng size dạng chữ để chủ shop sửa trong một ô: dòng đầu là tiêu đề, mỗi dòng sau là một hàng,
 * các ô cách nhau bởi dấu |. Lưu vào categories.size_chart dạng {columns, rows}.
 */
class SizeChart
{
    /** @return array{columns: list<string>, rows: list<list<string>>}|null null khi không có nội dung */
    public static function parse(string $text): ?array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $text)),
            fn (string $line) => $line !== '',
        ));

        if ($lines === []) {
            return null;
        }

        $split = fn (string $line) => array_map('trim', explode('|', $line));
        $columns = $split(array_shift($lines));
        $width = count($columns);

        $rows = array_map(
            fn (string $line) => array_slice(array_pad($split($line), $width, ''), 0, $width),
            $lines,
        );

        return ['columns' => $columns, 'rows' => $rows];
    }

    public static function format(?array $chart): string
    {
        if (empty($chart['columns'])) {
            return '';
        }

        return collect([$chart['columns'], ...($chart['rows'] ?? [])])
            ->map(fn (array $cells) => implode(' | ', $cells))
            ->implode("\n");
    }
}
