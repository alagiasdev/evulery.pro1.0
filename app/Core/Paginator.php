<?php

namespace App\Core;

class Paginator
{
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;
    private string $baseUrl;

    public function __construct(int $total, int $perPage, int $currentPage, string $baseUrl)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
        $this->totalPages = (int)ceil($this->total / $this->perPage);
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
        $this->baseUrl = $baseUrl;
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function limit(): int
    {
        return $this->perPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function totalPages(): int
    {
        return $this->totalPages;
    }

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    /**
     * Build URL for a specific page, preserving existing query params.
     */
    public function url(int $page): string
    {
        $sep = str_contains($this->baseUrl, '?') ? '&' : '?';
        return $this->baseUrl . $sep . 'page=' . $page;
    }

    /**
     * Generate pagination data for views.
     * Returns array with prev, next, pages (with gaps).
     */
    public function links(): array
    {
        if (!$this->hasPages()) {
            return [];
        }

        $pages = [];
        $current = $this->currentPage;
        $last = $this->totalPages;

        // Always show: 1, current-1, current, current+1, last
        // With '...' gaps where needed
        $range = [];
        $range[] = 1;

        for ($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++) {
            $range[] = $i;
        }

        if ($last > 1) {
            $range[] = $last;
        }

        $range = array_unique($range);
        sort($range);

        $prev = null;
        foreach ($range as $p) {
            if ($prev !== null && $p - $prev > 1) {
                $pages[] = ['type' => 'gap'];
            }
            $pages[] = [
                'type'    => 'page',
                'number'  => $p,
                'url'     => $this->url($p),
                'active'  => $p === $current,
            ];
            $prev = $p;
        }

        return [
            'prev'    => $current > 1 ? $this->url($current - 1) : null,
            'next'    => $current < $last ? $this->url($current + 1) : null,
            'pages'   => $pages,
            'current' => $current,
            'total'   => $last,
            'from'    => $this->offset() + 1,
            'to'      => min($this->offset() + $this->perPage, $this->total),
            'totalItems' => $this->total,
        ];
    }
}