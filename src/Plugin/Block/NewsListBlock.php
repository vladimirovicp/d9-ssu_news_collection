<?php

namespace Drupal\news_collection\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\news_collection\Controller\NewsCollectionListing;



/**
 * Class NewsListBlock
 *
 * @package Drupal\news_collection\Plugin\Block
 * @Block(
 *   id="ssu_news_list",
 *   admin_label="Новости главная",
 *   category="news"
 * )
 */

class NewsListBlock extends BlockBase
{
    public function build()
    {
        $controller = new NewsCollectionListing();

        return $controller->view();
    }
}