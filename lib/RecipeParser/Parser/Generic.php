<?php

class RecipeParser_Parser_Generic {

    static public function parse(DOMDocument $doc, $url) {

//        $doc = new DOMDocument();
//        libxml_use_internal_errors(true);
//        $doc->loadHTMLFile($url);
//        libxml_clear_errors();

        $recipe = new RecipeParser_Recipe();
        $xpath = new DOMXPath($doc);

        //$html = $doc->saveHTML();
        $elements = $xpath->query("//h2");
        foreach ($elements as $element) {
            print "element";
            print_r($element);
//            foreach ($element->attributes as $attribute) {
//                print "attribute";
//                print_r($attribute);
//            }
        }

        return $recipe;
    }

}

?>
