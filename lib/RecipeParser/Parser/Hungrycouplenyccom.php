<?php

class RecipeParser_Parser_Hungrycouplenyccom {
    
    static public function parse(DOMDocument $doc, $url) {
        $recipe = RecipeParser_Parser_Microformat::parse($doc, $url);
        $xpath = new DOMXPath($doc);
        
        // Title
        $nodes = $xpath->query('.//*[@itemprop="name"]');
        if ($nodes->length) {
            $value = $nodes->item(0)->nodeValue;
            $value = RecipeParser_Text::formatTitle($value);
            $recipe->title = $value;
        }
        
        return $recipe;
    }
}
