<?php
namespace Drupal\news_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NewsFilter extends FormBase
{
    public function getFormId()
    {
        return 'form_news_filter';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $query_params = \Drupal::request()->query->all();
        $params_search_term = isset($query_params['search']) ? $query_params['search'] : null;
        $params_heading = isset($query_params['heading']) ? $query_params['heading'] : null;
        $params_event_date = isset($query_params['next_to_date']) ? $query_params['next_to_date'] : null;
        $params_tag = isset($query_params['tag']) ? $query_params['tag'] : null;
        $params_keywords = isset($query_params['keywords']) ? $query_params['keywords'] : null;
        $top_news = isset($query_params['top_news']) ? $query_params['top_news'] : null;



        $form['filter_list'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['news-collection__filter-list']],
        ];

        $form['filter_list']['search_term'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Поиск по заголовкам')
        ];

        if (empty($params_search_term)) {
            $form['filter_list']['search_term']['#placeholder'] = $this->t('Введите слово или фразу');
        } else {
            $form['filter_list']['search_term']['#default_value'] = $params_search_term;
        }

        $form['filter_list']['heading'] = [
            '#type' => 'select',
            '#title' => t('Поиск по рубрикам'),
            '#options' => $this->taxonomy_options('heading', $params_heading, 'Выберите ключевое слово'),
            '#multiple' => FALSE,
            '#required' => FALSE,
        ];

        $form['filter_list']['event_date'] = [
            '#type' => 'textfield',
            '#title' => t('Поиск по дате'),
            '#required' => false,
            '#attributes' => ['class' => ['news-collection__air-datepicker']],
        ];

        if (empty($params_event_date)) {
            $form['filter_list']['event_date']['#placeholder'] = $this->t('Выберите диапазон дат');
        } else {
            $form['filter_list']['event_date']['#default_value'] = $params_event_date;
        }

        $form['filter_list']['tag'] = [
            '#type' => 'select',
            '#title' => t('Поиск по тегам'),
            '#options' => $this->taxonomy_options('news_tags', $params_tag, 'Выберите тег'),
            '#multiple' => FALSE,
            '#required' => FALSE,
        ];

        $form['filter_list']['keywords'] = [
            '#type' => 'select',
            '#title' => t('Поиск по ключевым словам'),
            '#options' => $this->taxonomy_options('news_keywords', $params_keywords, 'Выберите ключевое слово'),
            '#multiple' => FALSE,
            '#required' => FALSE,
        ];

          $form['filter_list']['top_news'] = [
            '#type' => 'checkbox',
            '#title' => t('Главные новости'),
            '#default_value' => (bool)$top_news, // Устанавливаем значение checked
          ];

//        $top_news = $form_state->getValue('format');
//        $format_bool = $format['заочная'] == '0' && $format['очная'] == '0' && $format['очно-заочная'] == '0' ? false : true;

        $form['btn_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['news-collection__filter-btns']],
        ];

        $form['btn_wrapper']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Применить фильтры'),
        ];

        $form['btn_wrapper']['reset_button'] = [
            '#type' => 'button',
            '#value' => $this->t('Очистить фильтр'),
            '#attributes' => [
                'onclick' => "const url = window.location.href.split('?')[0]; window.location.href = url; return false;",
            ],
        ];
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $search_term = $form_state->getValue('search_term');
        $heading = $form_state->getValue('heading');
        $event_date = $form_state->getValue('event_date');
        $tag = $form_state->getValue('tag');
        $keywords = $form_state->getValue('keywords');
        $top_news =  $form_state->getValue('top_news');

        $route_name = 'news_collection.listing';

        $result = [];

        if (!empty($search_term) || !empty($heading) || !empty($event_date) || !empty($tag) || !empty($keywords) || !empty($top_news)) {

            if (!empty($search_term)) {
                $result['search'] = $search_term;
            }
            if (!empty($heading)) {
                $result['heading'] = $heading;
            }

            if (!empty($event_date)) {
                $result['next_to_date'] = $event_date;
            }
            if (!empty($tag)) {
                $result['tag'] = $tag;
            }
            if (!empty($keywords)) {
                $result['keywords'] = $keywords;
            }

            if (!empty($top_news)) {
              $result['top_news'] = $top_news;
            }

            return $form_state->setRedirect($route_name, $result);
        } else {
            return $form_state->setRedirect($route_name);
        }
    }

    private function taxonomy_options($vocabulary, $params, $default_text)
    {
        $terms_heading = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
        $options = [];

        if (empty($params)) {
            $options[''] = $default_text;
        } else {
            $options[$params] = $params;
        }

        foreach ($terms_heading as $term) {
            if ($params !== $term->name) {
                $options[$term->name] = $term->name;
            }
        }

        return $options;
    }
}
