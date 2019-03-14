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
