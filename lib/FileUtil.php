<?php

class FileUtil {

    public static function tempFilenameFromUrl($url) {
        $hostname = parse_url($url, PHP_URL_HOST);
        $hostname = str_replace(".", "_", $hostname);
        $basename = "onetsp_{$hostname}_" . substr(md5($url), 0, 8);
        $filename = sys_get_temp_dir() . "/" . $basename;
        return $filename;
    }

    /**
     * Performs a cURL request to download and return the page HTML
     * @param $url the source URL to download from
     * @return mixed source html from response
     */
    public static function downloadPage($url) {
        $user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // added CURLOPT_ENCODING "gzip,deflate" in order to address sites with compression
        curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        // Disable SSL Verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $proxy = 'socks5h://chicory:ck3sHks3a2@proxy.chicoryapp.com:1080';

        // Publishers that require IP whitelisting; routing request through Chicory Proxy
        $proxy = 'socks5h://chicory:ck3sHks3a2@proxy.chicoryapp.com:1080';
        $whitelisted_domains = array(
            'quakeroats.com',
            'landolakes.com',
            'thekitchn.com',
            'barcart.com',
            'parade.com',
            'barcartstage.wpengine.com'
        );
        foreach ($whitelisted_domains as $domain) {
            if (strpos( $url, $domain ) !== false) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
            }
        }

        // add age check cookie
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: is-legal-age=1"));

        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

    public static function downloadRecipeWithCache($url) {
        $cache_ttl = 86400 * 3;

        // Target filename
        $filename = FileUtil::tempFilenameFromUrl($url);

        // Only fetch 1x per day
        if (file_exists($filename)
            && filesize($filename) > 0
            && (time() - filemtime($filename) < $cache_ttl)
        ) {
            error_log("Found file in cache: $filename");
            $html = file_get_contents($filename);

        } else {
            // Fetch and cleanup the HTML
            error_log("Downloading recipe from url: $url");

            $html = FileUtil::downloadPage($url);
            $html = RecipeParser_Text::forceUTF8($html);
            $html = RecipeParser_Text::cleanupClippedRecipeHtml($html);

            // Append some notes to the HTML
            $comments = RecipeParser_Text::getRecipeMetadataComment($url, "curl");
            $html = $comments . "\n\n" . $html;

            error_log("Saving recipe to file $filename");
            file_put_contents($filename, $html);
        }

        return $html;
    }

}
