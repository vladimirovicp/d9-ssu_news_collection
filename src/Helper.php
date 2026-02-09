<?php

namespace Drupal\news_collection;

class Helper
{
    public static function total_count($content_type, $search_term = null)
    {
        $total_count = \Drupal::entityQuery('node')
            ->condition('type', $content_type)  // Указываем тип материала.
            ->condition('status', 1)  // Опубликованные ноды.
            ->condition('field_news_main_flag', 1); // главная новость

        if ($search_term) {
            $total_count->condition('title', '%' . $search_term . '%', 'LIKE');
        }

        $total_count->count();

        return $total_count->execute();
    }

    public static function getPaginationLinks($page, $total_pages, $search = null)
    {
        $display_previous = true;
        $display_next = true;

        if ($page == 1) {
            $display_previous = false;
        } elseif ($page == $total_pages) {
            $display_next = false;
        }

        $next = $page + 1;
        $previous = $page - 1;

        if ($total_pages <= 1) {
            $display_next = false;
        }

        return [
            'display_next' => $display_next,
            'display_previous' => $display_previous,
            'current_page' => $page,
            'next' => $next,
            'previous' => $previous,
            'total_pages' => $total_pages,
            'search' => $search,
        ];


    }

    // Получаем изображения для шапки новости
}