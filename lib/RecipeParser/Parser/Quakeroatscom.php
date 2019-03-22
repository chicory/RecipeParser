<?php

class RecipeParser_Parser_Quakeroatscom {

    static public function parse(DOMDocument $doc, $url) {

        $recipe = new RecipeParser_Recipe();
        $xpath = new DOMXPath($doc);

        // Recipe Name
        $nodes = $xpath->query('//span[@id="cphStaticMaster_cpContent1_C001_lblRecipeNameDesktop"]');
        if ($nodes->length) {
            $recipe->title = trim($nodes->item(0)->nodeValue);
        }

        /** Recipe Image structure
         *
         * <div id="cphStaticMaster_cpContent1_C001_divWithoutVideo" class="container-fluid trpl-cklt"
         * style="background-image:url('/images/default-source/RecipeModule/banana-nut-overnight-oats-desktop');position:relative;">
         */
        // Recipe Image
        $nodes = $xpath->query('//div[@id="cphStaticMaster_cpContent1_C001_divWithoutVideo"]');
        if ($nodes->length) {
            $recipe_style = $nodes->item(0)->getAttribute('style');
            $re = '/(?<=background-image:url\(\')(.*)(?=\')/m';
            preg_match($re, $recipe_style, $match, PREG_OFFSET_CAPTURE, 0);
            if ($match) {
                if (strpos( $url, 'stage.quakeroats.com' ) !== false) {
                    $recipe->photo_url = trim('https://stage.quakeroats.com' . $match[0][0]);
                } else {
                    $recipe->photo_url = trim('https://www.quakeroats.com' . $match[0][0]);
                }
            }
        }

        // Servings
        $nodes = $xpath->query('//span[@id="cphStaticMaster_cpContent1_C001_lblServings"]');
        if ($nodes->length) {
            $recipe->yield = trim($nodes->item(0)->nodeValue);
        }

        // Ingredients
        $nodes = $xpath->query('//span[@class="ingrd-txts"]/ul/li');
        foreach ($nodes as $node) {

                $line = trim($node->nodeValue);
                $recipe->appendIngredient($line);
        }

        return $recipe;
    }

}

?>
