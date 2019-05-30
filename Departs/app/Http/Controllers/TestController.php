<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class TestController extends Controller
{

    public function notice() {

        $contents = file_get_contents('https://s3-ap-northeast-1.amazonaws.com/depart-rss/Departs.json');

        $newsArray = json_decode($contents);
        $title = $newsArray[0]->title;
        $description = strip_tags($newsArray[0]->description);
        $url = $newsArray[0]->url;

        $serviceAccount = ServiceAccount::fromJsonFile(app_path('Http/Controllers/') . 'depart-ios-firebase.json');
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();
        $messaging = $firebase->getMessaging();


        $notification = Notification::create()
            ->withTitle($title)
            ->withBody($description);

        $topic = 'all';
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification($notification)
            ->withData(['url' => $url]);

        $messaging->send($message);
    }

    public function index() {

        $this->departsTab();
        $this->scTab();
        $this->eventChecker();
        $this->apparelTab();
        $this->foodsTab();
        $this->ryusuutab();
        $this->cosmeticsTab();
        $this->interiorTab();
    }

    private function departsTab() {
        $urls = [
            'https://news.google.com/rss/search?q=%E7%99%BE%E8%B2%A8%E5%BA%97&hl=ja&gl=JP&ceid=JP:ja', //百貨店
            'https://news.google.com/rss/search?q=%E4%B8%89%E8%B6%8A%E4%BC%8A%E5%8B%A2%E4%B8%B9%20%E3%81%9D%E3%81%94%E3%81%86%E8%A5%BF%E6%AD%A6%20%E5%A4%A7%E4%B8%B8%E6%9D%BE%E5%9D%82%E5%B1%8B%20%E9%AB%98%E5%B3%B6%E5%B1%8B&hl=ja&gl=JP&ceid=JP:ja', // leading departs
        ];

        $item_array = [];
        foreach ($urls as $url) {
            $item_array = array_merge($item_array, $this->googleNews($url));
        }

        $item_array = $this->sotrByDate($item_array);
        $item_array = array_slice($item_array, 0, 100);
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Departs.json', $json,'public');
    }

    private function scTab() {
        $urls = [
            'https://news.google.com/rss/search?q=%E3%82%B7%E3%83%A7%E3%83%83%E3%83%94%E3%83%B3%E3%82%B0%E3%82%BB%E3%83%B3%E3%82%BF%E3%83%BC&hl=ja&gl=JP&ceid=JP:ja', // SC
        ];
        $item_array = [];
        foreach ($urls as $url) {
            $item_array = array_merge($item_array, $this->googleNews($url));
        }

        $item_array = $this->sotrByDate($item_array);
        $item_array = array_slice($item_array, 0, 100);
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('SC.json', $json,'public');
    }

    private function cosmeticsTab() {
        $urls = [
            'https://news.google.com/rss/search?q=%E5%8C%96%E7%B2%A7%E5%93%81&hl=ja&gl=JP&ceid=JP:ja', // 化粧品
        ];
        $item_array = [];
        foreach ($urls as $url) {
            $item_array = array_merge($item_array, $this->googleNews($url));
        }

        $item_array = $this->sotrByDate($item_array);
        $item_array = array_slice($item_array, 0, 100);
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Cosmetics.json', $json,'public');
    }

    private function interiorTab() {
        $urls = [
            'https://news.google.com/rss/search?q=%E3%82%A4%E3%83%B3%E3%83%86%E3%83%AA%E3%82%A2&hl=ja&gl=JP&ceid=JP:ja', // インテリア
        ];
        $item_array = [];
        foreach ($urls as $url) {
            $item_array = array_merge($item_array, $this->googleNews($url));
        }

        $item_array = $this->sotrByDate($item_array);
        $item_array = array_slice($item_array, 0, 100);
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Interior.json', $json,'public');
    }

    private function apparelTab() {
        $item_array = [];
//        $item_array = array_merge($item_array, $this->wwd());
        $item_array = array_merge($item_array, $this->senken());
        $item_array = array_merge($item_array, $this->topseller());
        $item_array = array_merge($item_array, $this->apparelWeb());
        $item_array = array_merge($item_array, $this->fashionSnap());
        $item_array = $this->sotrByDate($item_array);

        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Apparel.json', $json, 'public');
    }

    private function foodsTab() {
        $urls = [
            'https://news.google.com/rss/search?q=%E3%83%87%E3%83%91%E5%9C%B0%E4%B8%8B&hl=ja&gl=JP&ceid=JP:ja',
            'https://news.google.com/rss/search?q=%E9%A3%9F%E5%93%81&hl=ja&gl=JP&ceid=JP:ja',
        ];

        $item_array = [];
        foreach ($urls as $url) {
            $item_array = array_merge($item_array, $this->googleNews($url));
        }

        $item_array = array_merge($item_array, $this->shokuhin());

        $item_array = $this->sotrByDate($item_array);
        $item_array = array_slice($item_array, 0, 100);
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Foods.json', $json,'public');
    }

    private function ryusuutab() {
        $item_array = [];
        $item_array = array_merge($item_array, $this->ryutsuu());
        $item_array = array_merge($item_array, $this->shogyokai());
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('Ryutsuu.json', $json, 'public');
    }

    private function ryutsuu() {
        $xml = simplexml_load_file('https://www.ryutsuu.biz/feed');
        $items = $xml->channel->item;

        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = "";
            $article['image_url'] = "";
            $item_array[] = $article;
        }

        return $item_array;
    }

    private function shogyokai() {
        $xml = simplexml_load_file('http://shogyokai.jp/list/feed/rss');
        $items = $xml->channel->item;
        $nameSpaces = $xml->getNamespaces(true);

        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = "商業界オンライン";
            $article['image_url'] = (string)$item->children($nameSpaces['media'])->thumbnail->attributes()->url;
            $item_array[] = $article;
        }

        return $item_array;
    }

    private function wwd() {

        $xml = simplexml_load_file('https://www.wwdjapan.com/feed');
        $items = $xml->channel->item;
        $nameSpaces = $xml->getNamespaces(true);

        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = (string)$item->children($nameSpaces['dc'])->creator;

            $doc = new \DOMDocument();
            $doc->loadHTML((string)$item->description);
            $imageTags = $doc->getElementsByTagName('img');
            foreach($imageTags as $tag) {
                $article['image_url'] = $tag->getAttribute('src');
            }
            $item_array[] = $article;
        }
        return $item_array;
    }

    private function shokuhin() {
        $xml = simplexml_load_file('https://www.shokuhin.net/feed/');
        $items = $xml->channel->item;

        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = "";
            $article['image_url'] = "";
            $item_array[] = $article;
        }
        return $item_array;
    }

    private function senken() {
        $xml = simplexml_load_file('https://senken.co.jp/posts/feed.xml');
        $items = $xml->channel->item;

        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = (string)$item->category; // sourceがないのでカテゴリーをいれておく
            if (isset($item->enclosure)) {
                $url = (string)$item->enclosure->attributes()->url;
                $article['image_url'] = $url;
            } else {
                $article['image_url'] = "";
            }
            $item_array[] = $article;
        }
        return $item_array;
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

    private function n() {
        $note_urls = [
            'https://note.mu/rosha_jp/rss',
            'https://note.mu/minami_mitsuhiro/rss',
            'https://note.mu/kimiyashouten/rss',
            'https://note.mu/alex_t_m/rss',
            'https://note.mu/yosuke_kataoka/rss',
            'https://note.mu/qzqrnl/rss',
            'https://note.mu/fukaji/rss',
            'https://note.mu/hayakawagomi/rss',
        ];

        foreach ($note_urls as $url) {
            $xml = simplexml_load_file($url);

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
        }


        foreach ((array) $item_array as $key => $value) {
            $sort[$key] = $value['date'];
        }
        array_multisort($sort, SORT_DESC, $item_array);
        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    private function topseller() {

        $xml = simplexml_load_file('https://topseller.style/feed');

        $items = $xml->channel->item;
        $item_array = [];
        $nameSpaces = $xml->getNamespaces(true);
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['description'] = (string)$item->description;
            $article['source'] = (string)$item->children($nameSpaces['dc'])->creator;
            $article['image_url'] = "";
            $item_array[] = $article;
        }

        return $item_array;
    }

    private function googleNews($url) {
        $xml = simplexml_load_file($url);
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
                $article['image_url'] = $url;
            } else {
                $article['image_url'] = "";
            }
            $item_array[] = $article;
        }
        return $item_array;
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

    private function eventChecker() {
        $xml = simplexml_load_file('https://event-checker.blog.so-net.ne.jp/index.xml');

        $items = $xml->channel->item;
        $item_array = [];
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['description'] = (string)$item->description;
            $article['source'] = (string)$item->author;

            $pattern = '/<img.*?src\s*=\s*[\"|\'](.*?)[\"|\'].*?>/i';
            preg_match($pattern, $article['description'], $images);
            if (isset($images[1])) {
                $article['image_url'] = $images[1];
            }  else {
                $article['image_url'] = "";
            }
            $item_array[] = $article;
        }

        $json = json_encode($item_array, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        Storage::disk('s3')->put('EventChecker.json', $json,'public');
    }

    private function apparelWeb() {
        $xml = simplexml_load_file('https://apparel-web.com/news/feed');

        $items = $xml->channel->item;
        $nameSpaces = $xml->getNamespaces(true);
        $item_array = [];
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = "ApparelWeb";

            $pattern = '/<img.*?src\s*=\s*[\"|\'](.*?)[\"|\'].*?>/i';
            preg_match_all($pattern, (string)$item->children($nameSpaces['content'])->encoded, $images);
            $article['image_url'] = "";
            if (isset($images[1])) {
                foreach ($images[1] as $image)
                    if (! empty($image)) {
                        $article['image_url'] = $image;
                        break;
                    }
            }
            $item_array[] = $article;
        }
        return $item_array;
    }

    private function fashionSnap() {

        $xml = simplexml_load_file("https://www.fashionsnap.com/rss.xml");
        $items = $xml->channel->item;
        $nameSpaces = $xml->getNamespaces(true);
        $item_array = [];
        foreach($items as $item) {
            $article = [];
            $article['title'] = (string)$item->title;
            $article['url'] =(string) $item->link;
            $article['guid'] = (string)$item->guid;
            $article['date'] = strtotime((string)$item->pubDate);
            $article['source'] = "FASHIONSNAP.COM";

            $pattern = '/<img.*?src\s*=\s*[\"|\'](.*?)[\"|\'].*?>/i';
            preg_match_all($pattern, (string)$item->description, $images);
            $article['image_url'] = "";
            if (isset($images[1])) {
                foreach ($images[1] as $image)
                    if (! empty($image)) {
                        $article['image_url'] = $image;
                        break;
                    }
            }
            $item_array[] = $article;
        }

        return $item_array;
    }

    private function sotrByDate($item_array) {
        foreach ((array) $item_array as $key => $value) {
            $sort[$key] = $value['date'];
        }
        array_multisort($sort, SORT_DESC, $item_array);
        return $item_array;
    }
}
