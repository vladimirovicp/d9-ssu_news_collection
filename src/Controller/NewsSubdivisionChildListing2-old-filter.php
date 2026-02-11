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
    private $search_term;
    private $heading;
    private $next_to_date;
    private $filter;
    private $tag;
    private $keywords;
    private $top_news;
    private $divisions;
    private $participant; // Участник события
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

            // Сохраняем ID страницы для использования в фильтрации
            $this->main_subdivision_id = $node_id;
            // dpm($this->main_subdivision_id);
        }



        $total_count = $this->get_count();
        $this->total_pages = ceil($total_count / $this->output_pages);

        if ($this->total_pages == 0) {
            throw new NotFoundHttpException('Страница не найдена');
        }

        if (($this->page > $this->total_pages) || ($this->page <= 0)) {
            $this->page = 1;
        }

        // dpm($this->page);

        $news_id = $this->listNews();

        $content['news'] = $this->createNewsCard($news_id);
        $content['filter'] = \Drupal::formBuilder()->getForm('\Drupal\news_collection\Form\NewsFilter');
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
        $begin = $this->page * $this->output_pages - 16;

        if ($this->filter) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', $content_type)  // Указываем тип материала.
                ->condition('status', 1);  // Опубликованные ноды.
                
            // Фильтрация по главному подразделению
            if ($this->main_subdivision_id) {
                $query->condition('field_news_link.target_id', $this->main_subdivision_id, '=');
            }

            if ($this->search_term) {
                $query->condition('title', '%' . $this->search_term . '%', 'LIKE');
            }
            if ($this->heading) {
                $id_term = $this->get_terma_name_to_id($this->heading, 'heading');
                $query->condition('field_heading.target_id', $id_term, '=');
            }

            if ($this->next_to_date) {

                $date_parts = explode(' - ', $this->next_to_date);
                $start_date = $date_parts[0];

                if (!empty($date_parts[1])) {
                    $end_date = $date_parts[1];
                } else {
                    $end_date = $start_date;
                }


                $start_timestamp = strtotime($start_date);
                $end_timestamp = strtotime($end_date);

                $start_year = date('Y', $start_timestamp);
                $start_month = date('m', $start_timestamp);
                $start_day = date('d', $start_timestamp);

                $end_year = date('Y', $end_timestamp);
                $end_month = date('m', $end_timestamp);
                $end_day = date('d', $end_timestamp);

                $start_date_formate = sprintf('%04d-%02d-%02dT00:00:00', $start_year, $start_month, $start_day);
                $end_date_formate = sprintf('%04d-%02d-%02dT23:59:59', $end_year, $end_month, $end_day);

                $query->condition('field_publ_date', [$start_date_formate, $end_date_formate], 'BETWEEN');
            }

            if ($this->top_news) {
                $query->condition('field_news_main_flag', 1);
            }


          if ($this->participant) {
            $query->condition('field_news_participant.target_id', $this->participant, '=');
          }



            if ($this->tag) {
                $id_term = $this->get_terma_name_to_id($this->tag, 'news_tags');
                $query->condition('field_news_tag.target_id', $id_term, '=');
            }

            if ($this->keywords) {
                $id_term = $this->get_terma_name_to_id($this->keywords, 'news_keywords');
                $query->condition('field_news_keywords.target_id', $id_term, '=');
            }
            $query->sort('field_publ_date', 'DESC') // Сортировка по полю дата публикации
                ->range($begin, $this->output_pages);  // Ограничение на последние 15 записей.

        } else {
            $query = \Drupal::entityQuery('node')
                ->condition('type', $content_type)  // Указываем тип материала.
                ->condition('status', 1);  // Опубликованные ноды.
                
            // Фильтрация по главному подразделению
            if ($this->main_subdivision_id) {
                $query->condition('field_news_link.target_id', $this->main_subdivision_id, '=');
            } 
            
            $query->sort('field_publ_date', 'DESC') // Сортировка по полю дата публикации
                ->range($begin, $this->output_pages);  // Ограничение на последние 15 записей.
        }

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
        $headings = []; // Рубрика | field_heading
        $news_announce = null; // Анонс | field_news_announce
        $news_img_url = null;
        $news_img_alt = null;
        $url = $node->toUrl();
        $publ_date = null;
        $data_top_img = null; // Данные для слайдера в шапке новости

        $main_news = false; // Главная новость


        if ($node->hasField('field_heading') && !$node->get('field_heading')->isEmpty()) {
            $heading_ids = $node->get('field_heading')->getValue();

            foreach ($heading_ids as $term_id) {
                $term = Term::load($term_id['target_id']);
                if ($term_id['target_id']) {
                    $headings[] = $term->getName();
                }
            }
        }

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
            'headings' => $headings,
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



        if ($this->search_term) {
            $result['search'] = $this->search_term;
        }

        if ($this->heading) {
            $result['heading'] = $this->heading;
        }

        if ($this->next_to_date) {
            $result['next_to_date'] = $this->next_to_date;
        }

        if ($this->top_news){
            $result['top_news'] = $this->top_news;
        }

        if ($this->tag){
            $result['tag'] = $this->tag;
        }

        if($this->keywords){
           $result['keywords'] = $this->keywords;
        }

        if($this->divisions){
           $result['divisions'] = $this->divisions;
        }

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

        $this->page = isset($query_params['page']) ? $query_params['page'] : null;
        $this->search_term = isset($query_params['search']) ? $query_params['search'] : null;
        $this->heading = isset($query_params['heading']) ? $query_params['heading'] : null;
        $this->next_to_date = isset($query_params['next_to_date']) ? $query_params['next_to_date'] : null;
        $this->top_news = isset($query_params['top_news']) ? $query_params['top_news'] : null;
        $this->divisions = isset($query_params['divisions']) ? $query_params['divisions'] : null;
        $this->participant = isset($query_params['participant']) ? $query_params['participant'] : null;
        $this->tag = isset($query_params['tag']) ? $query_params['tag'] : null;
        $this->keywords = isset($query_params['keywords']) ? $query_params['keywords'] : null;




        if ($this->search_term || $this->heading || $this->next_to_date || $this->top_news || $this->tag || $this->keywords || $this->divisions || $this->participant) {
            $this->filter = true;
        } else {
            $this->filter = false;
        }

        if (!$this->filter) {
            $this->page = isset($query_params['page']) ? $query_params['page'] : 1;
        }

        $content_type = 'news';
        if ($this->filter) {
            $total_query = \Drupal::entityQuery('node')
                ->condition('type', $content_type)  // Указываем тип материала.
                ->condition('status', 1);  // Опубликованные ноды.
                
            // Фильтрация по главному подразделению
            if ($this->main_subdivision_id) {
                $total_query->condition('field_news_link.target_id', $this->main_subdivision_id, '=');
            } elseif (!$this->divisions) {
                $total_query->notExists('field_news_link');
            }

            if ($this->top_news) {
                $total_query->condition('field_news_main_flag', 1);
            }

            // if ($this->divisions) {
              //$total_query->condition('field_news_participant_division.target_id', $this->divisions, '=');


                // $group = $total_query->orConditionGroup();
                // $group->condition('field_news_participant_division.target_id', $this->divisions, '=');
                // $group->condition('field_news_link.target_id', $this->divisions, '=');

                // $total_query->condition($group);
            // }

            if ($this->participant) {
                $total_query->condition('field_news_participant.target_id', $this->participant, '=');
            }


            if ($this->search_term) {
                $total_query->condition('title', '%' . $this->search_term . '%', 'LIKE');
            }

            if ($this->heading) {
                $id_term = $this->get_terma_name_to_id($this->heading, 'heading');
                $total_query->condition('field_heading.target_id', $id_term, '=');
            }



            if ($this->next_to_date) {
                $date_parts = explode(' - ', $this->next_to_date);
                $start_date = $date_parts[0];

                if (!empty($date_parts[1])) {
                    $end_date = $date_parts[1];
                } else {
                    $end_date = $start_date;
                }

                $start_timestamp = strtotime($start_date);
                $end_timestamp = strtotime($end_date);

                $start_year = date('Y', $start_timestamp);
                $start_month = date('m', $start_timestamp);
                $start_day = date('d', $start_timestamp);

                $end_year = date('Y', $end_timestamp);
                $end_month = date('m', $end_timestamp);
                $end_day = date('d', $end_timestamp);

                $start_date_formate = sprintf('%04d-%02d-%02dT00:00:00', $start_year, $start_month, $start_day);
                $end_date_formate = sprintf('%04d-%02d-%02dT23:59:59', $end_year, $end_month, $end_day);

                $total_query->condition('field_publ_date', [$start_date_formate, $end_date_formate], 'BETWEEN');
            }


            if ($this->tag) {
                $id_term = $this->get_terma_name_to_id($this->tag, 'news_tags');
                $total_query->condition('field_news_tag.target_id', $id_term, '=');
            }

            if ($this->keywords) {
                $id_term = $this->get_terma_name_to_id($this->keywords, 'news_keywords');
                $total_query->condition('field_news_keywords.target_id', $id_term, '=');
            }

            $total_query->count();
            $total_count = $total_query->execute();

        } else {
            $total_query = \Drupal::entityQuery('node')
                ->condition('type', $content_type)  // Указываем тип материала.
                ->condition('status', 1);  // Опубликованные ноды.
                
            // Фильтрация по главному подразделению
            if ($this->main_subdivision_id) {
                $total_query->condition('field_news_link.target_id', $this->main_subdivision_id, '=');
            } else {
                $total_query->notExists('field_news_link');
            }
            
            $total_count = $total_query->count()->execute();
        }

        return $total_count;

    }
    //поис id термина по имени
    public function get_terma_name_to_id($term_name, $vocabulary = 'heading')
    {

        // Словарь, в котором находится термин
        //$vocabulary = 'heading';

        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
            'name' => $term_name,
            'vid' => $vocabulary,
        ]);

        $term_id = null;

        if (!empty($terms)) {
            // Получаем ID первого найденного термина
            $term = reset($terms);
            $term_id = $term->id();
            // Выводим или используем $term_id по мере необходимости
        }

        return $term_id;

    }


}
