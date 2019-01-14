<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function index() {

        // GoogleNews
        $xml = simplexml_load_file('https://news.google.com/rss/search?q=%E7%99%BE%E8%B2%A8%E5%BA%97&hl=ja&gl=JP&ceid=JP:ja');

        $nameSpaces = $xml->getNamespaces(true);
        $items = $xml->channel->item;
        foreach($items as $item) {
            if (isset($item->children($nameSpaces['media'])->content)) {

                $url = (string)$item->children($nameSpaces['media'])->content->attributes()->url;
                $item = json_decode(json_encode($item), true);
                $item['image_url'] = $url;
            }
            $item_array[] = $item;
        }

        $json = json_encode($item_array);
        Storage::disk('s3')->put('GoogleNews.json', $json,'public');

        // Blogos
        $xml = simplexml_load_file('https://blogos.com/feed/tag/%E7%99%BE%E8%B2%A8%E5%BA%97/');

        $items = $xml->channel->item;
        foreach($items as $item) {
            $item_array[] = $item;
        }
        $json = json_encode($item_array);

        Storage::disk('s3')->put('Blogos.json', $json,'public');

    }
}
