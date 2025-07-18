<?php
function renderPagination($currentPage, $totalPages, $queryStr = '', $maxPagesToShow = 20)
{
    if ($totalPages <= 1) return '';

    $output = '<ul class="pagination-list" data-current-page="' . $currentPage . '" data-total-pages="' . $totalPages . '">';

    $device = $_GET['device'] ?? '';

    
    if ($device === 'tablet') {
        $startPage = 1;
        $endPage = min($maxPagesToShow, $totalPages);
    } else {
        $half = floor($maxPagesToShow / 2);
        if ($totalPages > $maxPagesToShow) {
            if ($currentPage <= $half) {
                $startPage = 1;
                $endPage = $maxPagesToShow;
            } elseif ($currentPage >= $totalPages - $half) {
                $startPage = $totalPages - $maxPagesToShow + 1;
                $endPage = $totalPages;
            } else {
                $startPage = $currentPage - $half;
                $endPage = $startPage + $maxPagesToShow - 1;
            }
        } else {
            $startPage = 1;
            $endPage = $totalPages;
        }
    }

    
    if ($currentPage > 1) {
        $output .= '<li class="prev"><a class="page-link" href="?page=' . ($currentPage - 1) . '&' . $queryStr . '">&laquo;</a></li>';
    } else {
        $output .= '<li class="prev disabled"><span>&laquo;</span></li>';
    }

    
    if ($startPage > 1) {
        $output .= '<li><a class="page-link" href="?page=1&' . $queryStr . '">1</a></li>';
        if ($startPage > 2) {
            $output .= '<li class="ellipsis"><span>...</span></li>';
        }
    }

    
    for ($i = $startPage; $i <= $endPage && $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? ' class="active"' : '';
        $output .= '<li' . $active . '><a class="page-link" href="?page=' . $i . '&' . $queryStr . '">' . $i . '</a></li>';
    }

    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $output .= '<li class="ellipsis"><span>...</span></li>';
        }
        $output .= '<li><a class="page-link" href="?page=' . $totalPages . '&' . $queryStr . '">' . $totalPages . '</a></li>';
    }

    
    if ($currentPage < $totalPages) {
        $output .= '<li class="next"><a class="page-link" href="?page=' . ($currentPage + 1) . '&' . $queryStr . '">&raquo;</a></li>';
    } else {
        $output .= '<li class="next disabled"><span>&raquo;</span></li>';
    }

    $output .= '</ul>';

    
    $paginationInfo = htmlspecialchars("$currentPage / $totalPages");

    return '<div class="pagination-wrapper"><span class="pagination-info">' . $paginationInfo . '</span>' . $output . '</div>';
}
?>
