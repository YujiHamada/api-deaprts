<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function index() {
        $this->note();
        $this->googleNews();
        $this->blogos();
    }

    private function note() {
        $xml = simplexml_load_file('https://note.mu/hashtag/%E7%99%BE%E8%B2%A8%E5%BA%97/rss');

        $nameSpaces = $xml->getNamespaces(true);
        $items = $xml->channel->item;
        
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = (string)$item->children($nameSpaces['note'])->creatorName;
            $article['image_url'] = (string)$item->children($nameSpaces['note'])->creatorImage;
            $item_array[] = $article;
        }
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Note.json', $json,'public');
    }

    private function googleNews() {
        $xml = simplexml_load_file('https://news.google.com/rss/search?q=%E7%99%BE%E8%B2%A8%E5%BA%97&hl=ja&gl=JP&ceid=JP:ja');

        $nameSpaces = $xml->getNamespaces(true);
        $items = $xml->channel->item;
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['description'] = (string)$item->description;
            $article['source'] = (string)$item->source;

            if (isset($item->children($nameSpaces['media'])->content)) {

                $url = (string)$item->children($nameSpaces['media'])->content->attributes()->url;
                $item = json_decode(json_encode($item), true);
                $article['image_url'] = $url;
            } else {
                $article['image_url'] = "";
            }
            $item_array[] = $article;
        }

        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('GoogleNews.json', $json,'public');
    }

    private function blogos() {
        $xml = simplexml_load_file('https://blogos.com/feed/tag/%E7%99%BE%E8%B2%A8%E5%BA%97/');

        $items = $xml->channel->item;
        $item_array = [];
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['description'] = (string)$item->description;
            $article['source'] = (string)$item->source;
            $article['image_url'] = "";
            $item_array[] = $article;
        }

        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Blogos.json', $json,'public');
    }
}
