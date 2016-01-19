<?php

require_once '../bootstrap.php';

class RecipeParser_Parser_HungrycouplenyccomTest extends PHPUnit_Framework_TestCase {

    public function test_crispy_potato_and_hummus_balls() {
        $path = "data/hungrycouplenyc_com_hungry_couple_crispy_potato_and_hummus_curl.html";
        $url = "http://www.hungrycouplenyc.com/2016/01/crispy-potato-and-hummus-balls.html";

        $doc = RecipeParser_Text::getDomDocument(file_get_contents($path));
        $recipe = RecipeParser::parse($doc, $url);
        if (isset($_SERVER['VERBOSE'])) print_r($recipe);
        
        $this->assertEquals("Crispy Potato and Hummus Balls", $recipe->title);
        $this->assertEquals(2, count($recipe->ingredients));
        $this->assertEquals("Breading", $recipe->ingredients[1]['name']);
        $this->assertEquals(5, count($recipe->ingredients[0]['list']));
        $this->assertEquals(3, count($recipe->ingredients[1]['list']));
        $this->assertEquals("1/4 Cup Sabra Classic Hummus", $recipe->ingredients[0]['list'][1]);
        $this->assertEquals("Hungry Couple", $recipe->credits);
    }
}
