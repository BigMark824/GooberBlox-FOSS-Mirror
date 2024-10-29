<?php
namespace core\modules;
use core\conf;


class twig {
    private static function twig() {
        $loader = new \Twig\Loader\FilesystemLoader([__FWDIR__."/templates", __FWDIR__."/"]);
        $twig = new \Twig\Environment($loader, ['debug' => true,]);

        $twig->addGlobal('conf', new conf());

        $twig->addFilter(new \Twig\TwigFilter('img2proxy', function ($url, $w = "", $h = "") {
            return img2proxy($url, $w, $h);
        }));

        $twig->addFilter(new \Twig\TwigFilter('search', function ($haystack, $needle) {
            if(!isset($needle)) {
            array_search($needle, $haystack);
            return $haystack;
            } else {
            return $haystack;
            }
        }));

        $twig->addFilter(new \Twig\TwigFilter('tagsearch', function ($array, $search) {
            $tags = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $filtered = array_filter($array, function ($item) use ($tags) {
                if (is_array($item)) {
                    $item = implode(' ', $item);
                }
                foreach ($tags as $tag) {
                    if (preg_match('/'.$tag.'/i', $item)) {
                        return true;
                    }
                }
                return false;
            });
    
            return $filtered;
        }));
        $twig->addFilter(new \Twig\TwigFilter('shuffle', function ($array) {
            shuffle($array);
            return $array;
        }));
        $twig->addFilter(new \Twig\TwigFilter('json_decode', function ($string) {
            return json_decode($string, true);
        }));
        $twig->addFilter(new \Twig\TwigFilter('json_encode', function ($string) {
            return json_encode($string);
        }));
        $twig->addFunction(new \Twig\TwigFunction('file_get_contents', function ($url) {
            return file_get_contents($url);
        }));
        $twig->addFunction(new \Twig\TwigFunction('z_ws', function ($int = 1) {
            return str_repeat("​", $int);
        }));
        $twig->addFilter(new \Twig\TwigFilter('timeago', function ($int = 0) {
            return @timeago($int);
        }));
        $twig->addFilter(new \Twig\TwigFilter('preg_replace', function ($subject, $pattern, $replacement) {
            return preg_replace($pattern, $replacement, $subject);
        }));
        #$twig->addGlobal('translator', translation::translator());
        $twig->addGlobal('fwdir', __FWDIR__);
        $twig->addGlobal('transdir', __TRANSDIR__);
        $twig->addGlobal('cookie', $_COOKIE);
        $twig->addGlobal('session', $_SESSION ?? []);
        $twig->addGlobal('post', $_POST);
        $twig->addGlobal('get', $_GET);
        $twig->addGlobal('pagename', __PAGE__);
        $twig->addGlobal('url', __URL_NOQUERY__);
        $twig->addGlobal('domain', __DOMAIN__);

        $twig->addFunction(new \Twig\TwigFunction('header', function ($txt) {
        return header($txt);
        }));

        $twig->addFunction(new \Twig\TwigFunction('vardump', function ($obj) {
        return var_dump($obj);
        }));

        $twig->addFunction(new \Twig\TwigFunction('http_response_code', function ($int) {
        return http_response_code($int);
        }));

        $twig->addFunction(new \Twig\TwigFunction('http_response_codename', function ($int) {
        return http_response_codename($int);
        }));

        $twig->addFilter(new \Twig\TwigFilter('br2nl', function ($txt) {
            return preg_replace('#<br\s*/?>#i', PHP_EOL, $txt);
        }));
        
        $twig->addFilter(new \Twig\TwigFilter('ordinal', function ($int) {
            return ordinal($int);
        }));

        $twig->addFilter(new \Twig\TwigFilter('truncate', function ($text, $length, $ellipsis = "...") {
            return truncate($text, $length, $ellipsis);
        }));


        $twig->addFilter(new \Twig\TwigFilter('eval', function ($string, $data = []) use ($twig) {
            $template = $twig->createTemplate($string);
            return $template->render($data);
        }));
        return $twig;
    }

    public static function view($file, $data = []) {
        $twig = self::twig();

        if (!preg_match('/\.twig$/', $file)) {
        $file .= '.twig';
        }
        
        if(file_exists(__FWDIR__."/templates".$file)) {
        return "Template does not exist.";
        }        

        try {
        return $twig->render($file, $data);
        } catch(\Exception $er) {
        return $er."<br><br>you will be redirected in 3 seconds".header('refresh:3;url='.$_SERVER['HTTP_REFERER']);
        }
    }
}