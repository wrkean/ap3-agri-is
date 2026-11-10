<?php
/**
 * Shared CRUD helper utilities for the Agriculture Information System.
 */

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatMoney(float $v): string
{
    return '₱' . number_format($v, 2);
}

function badge(string $status, array $map = []): string
{
    $cls = $map[$status] ?? 'bg-gray-100 text-gray-700';
    return '<span class="status-badge ' . $cls . '">' . e($status) . '</span>';
}

function pagination(int $page, int $total, int $per = 10): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $per;
    return ['page' => $page, 'pages' => $pages, 'offset' => $offset, 'limit' => $per, 'total' => $total];
}

function renderPager(string $baseUrl, int $page, int $pages): string
{
    if ($pages <= 1) {
        return '';
    }
    $html =
        '<div class="flex justify-between items-center mt-4 text-sm"><span>Showing page ' .
        $page .
        ' of ' .
        $pages .
        '</span><div class="flex gap-2">';
    if ($page > 1) {
        $html .=
            '<a href="' .
            $baseUrl .
            '&page=' .
            ($page - 1) .
            '" class="px-3 py-1 border rounded hover:bg-gray-100">Previous</a>';
    }
    if ($page < $pages) {
        $html .=
            '<a href="' .
            $baseUrl .
            '&page=' .
            ($page + 1) .
            '" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a>';
    }
    $html .= '</div></div>';
    return $html;
}
