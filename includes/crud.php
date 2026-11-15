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

/**
 * Consistent color classes for planting statuses.
 */
function statusColor(string $status): string
{
    return match (strtolower(trim($status))) {
        'harvested' => 'bg-green-100 text-green-700',
        'growing'   => 'bg-blue-100 text-blue-700',
        'failed'    => 'bg-red-100 text-red-700',
        default     => 'bg-gray-100 text-gray-700',
    };
}

/**
 * Consistent color classes for quality grades.
 */
function gradeColor(string $grade): string
{
    return match (strtolower(trim($grade))) {
        'grade a' => 'bg-green-100 text-green-700',
        'grade b' => 'bg-blue-100 text-blue-700',
        'grade c' => 'bg-amber-100 text-amber-700',
        default   => 'bg-gray-100 text-gray-700',
    };
}

/**
 * Hex colors for chart datasets, matching statusColor/gradeColor.
 */
function statusChartColors(): array
{
    return ['#10b981', '#3b82f6', '#ef4444', '#f59e0b'];
}

function gradeChartColors(): array
{
    return ['#10b981', '#3b82f6', '#f59e0b'];
}

/**
 * Strictly validate a Y-m-d date string.
 * Returns true only for real, possible calendar dates
 * (rejects inputs like 2026-02-31).
 */
function isValidDate(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

/**
 * Whitelist a sort column from user input; fall back to $default.
 */
function whitelistSort(string $sort, array $allowed, string $default): string
{
    return in_array($sort, $allowed, true) ? $sort : $default;
}

/**
 * Allow only asc or desc (case-insensitive); fall back to asc.
 */
function whitelistDir(string $dir): string
{
    return strtolower(trim($dir)) === 'desc' ? 'desc' : 'asc';
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
            e($baseUrl . '&page=' . ($page - 1)) .
            '" class="px-3 py-1 border rounded hover:bg-gray-100">Previous</a>';
    }
    if ($page < $pages) {
        $html .=
            '<a href="' .
            e($baseUrl . '&page=' . ($page + 1)) .
            '" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a>';
    }
    $html .= '</div></div>';
    return $html;
}
