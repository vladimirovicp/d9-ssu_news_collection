<?php

namespace Drupal\news_collection\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class NewsSubdivisionChildListing2 extends ControllerBase
{
    private $page;
    private $total_pages;
    private $output_pages = 16; // колычество выводимых новостей
    private $page_filter;

    private $main_subdivision_id; // ID главного подразделения

    private $subdivision; // Параметр подразделения из URL
    private $subdivision_child; // Параметр дочернего подразделения из URL
    private $subdivision_child2;

    public function view($subdivision, $subdivision_child, $subdivision_child2)
    {
        // Сохраняем параметры подразделения для использования в пагинации
        $this->subdivision = $subdivision;
        $this->subdivision_child = $subdivision_child;
        $this->subdivision_child2 = $subdivision_child2;
        
        // Проверка существования страницы по пути /struktura/{$subdivision}/{$subdivision_child}
        $path_alias = '/struktura/' . $subdivision . '/' . $subdivision_child . '/' . $subdivision_child2;
        $path_alias_manager = \Drupal::service('path_alias.manager');
        $internal_path = $path_alias_manager->getPathByAlias($path_alias);
        
        //Если внутренний путь совпадает с алиасом, значит страница не найдена
        if ($internal_path === $path_alias) {
            throw new NotFoundHttpException('Страница не найдена');
        }
        
        // Дополнительная проверка: убеждаемся, что это валидная нода
        if (preg_match('/^\/node\/(\d+)$/', $internal_path, $matches)) {
            $node_id = $matches[1];
            $node = Node::load($node_id);
            if (!$node || !$node->isPublished()) {
                throw new NotFoundHttpException('Страница не найдена');
            }
            $this->main_subdivision_id = $node_id;
        }



        $total_count = $this->get_count();
        $this->total_pages = ceil($total_count / $this->output_pages);

        if ($this->total_pages == 0) {
            throw new NotFoundHttpException('Страница не найдена');
        }

        if (($this->page > $this->total_pages) || ($this->page <= 0)) {
            $this->page = 1;
        }

        $news_id = $this->listNews();

        $content['news'] = $this->createNewsCard($news_id);
        $pagination = $this->getPaginationLinks();

        return [
            '#theme' => 'news-subdivision-child-listing2',
            '#content' => $content,
            '#pagination' => $pagination,
            '#attached' => [
                'library' => [
                    'news_collection/ssu-news-assets',
                ]
            ],
        ];

    }

    public function listNews()
    {
        $content_type = 'news';
        $begin = $this->page * $this->output_pages - $this->output_pages;


        $query = \Drupal::entityQuery('node')
            ->condition('type', $content_type)  // Указываем тип материала.
            ->condition('status', 1)  // Опубликованные ноды.
            ->condition('field_news_link.target_id', $this->main_subdivision_id, '=')
            ->sort('field_publ_date', 'DESC')
            ->range($begin, $this->output_pages);  // Ограничение на последние 15 записей.






        $nids = $query->execute();

        if (!empty($nids)) {
            return $nids;
        }
        return [];
    }

    public function getNewsData($id)
    {
        $node = Node::load($id);

        $title = $node->getTitle();
        $news_announce = null; // Анонс | field_news_announce
        $news_img_url = null;
        $news_img_alt = null;
        $url = $node->toUrl();
        $publ_date = null;
        $data_top_img = null; // Данные для слайдера в шапке новости

        $main_news = false; // Главная новость


        if ($node->hasField('field_news_announce') && !$node->get('field_news_announce')->isEmpty()) {
            $news_announce = $node->get('field_news_announce')->value;
        }


        // Получаем видео для слайдера
        if ($node->hasField('field_video_v_shapke_novosti') && !$node->get('field_video_v_shapke_novosti')->isEmpty()) {

            $link_video_data = $node->get('field_video_v_shapke_novosti')->getValue();
            $link_video_id = $link_video_data[0]['target_id'];
        }

        if ($node->hasField('field_news_header_slider') && !$node->get('field_news_header_slider')->isEmpty()) {

            $news_header_slider_ids = $node->get('field_news_header_slider')->getValue();

            $news_header_slider_id = $news_header_slider_ids[0]['target_id'];
            $media = \Drupal\media\Entity\Media::load($news_header_slider_id);

            if ($media && $media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
                $media_image_field = $media->get('field_media_image');
                $file_id = $media_image_field->target_id;
                $file = \Drupal\file\Entity\File::load($file_id);
                if ($file) {
                    $style = ImageStyle::load('416xauto');
                    $styled_image_url = $style->buildUrl($file->getFileUri());
                    $news_img_url = $styled_image_url;
                }

                $alt = $media_image_field->get(0)->get('alt')->getString();
                if ($alt) {
                    $news_img_alt = $alt;
                } else {
                    $news_img_alt = $title;
                }

            }

            $data_top_img = array_map(function ($data) {
                $target_id = $data['target_id'];
                $media = \Drupal\media\Entity\Media::load($target_id);
                if ($media && $media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
                    $media_image_field = $media->get('field_media_image');
                    $file_id = $media_image_field->target_id;
                    $file = \Drupal\file\Entity\File::load($file_id);
                    if ($file) {
                        $style = ImageStyle::load('416xauto');
                        $styled_image_url = $style->buildUrl($file->getFileUri());
                        $news_img_url = $styled_image_url;
                    }

                    $alt = $media_image_field->get(0)->get('alt')->getString();
                    if ($alt) {
                        $news_img_alt = $alt;
                    } else {
                        $news_img_alt = '';
                    }
                }

                return [
                    'url' => $news_img_url,
                    'alt' => $news_img_alt,
                    'type' => 'img',
                ];
            }, $news_header_slider_ids);
        }



        if ($node->hasField('field_publ_date')) {
            $date_value = $node->get('field_publ_date')->value;
            $site_timezone = \Drupal::config('system.date')->get('timezone.default');
            $datetime = new DrupalDateTime($date_value, new \DateTimeZone('UTC'));
            $datetime->setTimezone(new \DateTimeZone($site_timezone));
            $formatted_date = $datetime->format('d.m.Y / H:i');
            $publ_date = $formatted_date;
        }

        //Главная новость
        if ($node->hasField('field_news_main_flag') && !$node->get('field_news_main_flag')->isEmpty()) {
            $main_news = $node->get('field_news_main_flag')->value;
        }

        return [
            'id' => $id,
            'main_news' => $main_news,
            'title' => $title,
            'news_announce' => $news_announce,
            'img_top' => $data_top_img,
            'img_url' => $news_img_url,
            'img_alt' => $news_img_alt,
            'url' => $url,
            'date' => $publ_date
        ];
    }

    public function createNewsCard($news_id)
    {

        $newsCard = [];
        if (!empty($news_id)) {
            foreach ($news_id as $id) {
                $content = $this->getNewsData($id);

                $newsCard[] = [
                    '#theme' => 'news-card',
                    '#content' => $content,
                ];
            }
        }
        return $newsCard;
    }

    public function getPaginationLinks()
    {
        $display_previous = true;
        $display_previous_to_2 = true;
        $display_next = true;
        $display_next_to_2 = true;

        if ($this->page == 1) {
            $display_previous = false;
        } elseif ($this->page == $this->total_pages) {
            $display_next = false;
        }

        $next = $this->page + 1;
        $next_to_2 = $next + 1;

        $previous = $this->page - 1;
        $previous_to_2 = $this->page - 2;

        if ($this->total_pages <= $this->total_pages) {
            $total_pages = $this->total_pages;
        } else {
            $total_pages = $this->total_pages;
        }

        if ($total_pages <= 1) {
            $display_next = false;
        }

        if ($total_pages <= $next_to_2) {
            $display_next_to_2 = false;
        }

        if ($total_pages - 2 != $previous_to_2) {
            $display_previous_to_2 = false;
        }


        $result = [
            'display_next' => $display_next,
            'display_next_2' => $display_next_to_2,
            'display_previous' => $display_previous,
            'display_previous_2' => $display_previous_to_2,
            'current_page' => $this->page,
            'next' => $next,
            'next2' => $next_to_2,
            'previous' => $previous,
            'previous2' => $previous_to_2,
            'total_pages' => $total_pages,
        ];

        // Добавляем параметры подразделения для использования в ссылках пагинации
        if($this->subdivision){
           $result['subdivision'] = $this->subdivision;
        }

        // Добавляем параметр дочернего подразделения для использования в ссылках пагинации
        if($this->subdivision_child){
           $result['subdivision_child'] = $this->subdivision_child;
        }

        if($this->subdivision_child2){
            $result['subdivision_child2'] = $this->subdivision_child2;
         }
        return $result;
    }

    public function get_count()
    {
        $query_params = \Drupal::request()->query->all();
        $this->page = isset($query_params['page']) ? $query_params['page'] : 1;
        $content_type = 'news';
        $total_query = \Drupal::entityQuery('node')
            ->condition('type', $content_type)  // Указываем тип материала.
            ->condition('status', 1)  // Опубликованные ноды.
            ->condition('field_news_link.target_id', $this->main_subdivision_id, '=');
        $total_count = $total_query->count()->execute();
        return $total_count;
    }


}
