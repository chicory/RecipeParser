<?php

class RecipeParser_Parser_MicrodataJsonLd {

    static public function parse(DOMDocument $doc, $url) {
        $recipe = new RecipeParser_Recipe();
        $xpath = new DOMXPath($doc);

        $data = self::findRecipeJSON($xpath);

        // Bail if no Recipe JSON-LD in the markup
        if (!$data) {
            return $recipe;
        }
        
        // Parse elements:
        
        // Title
        if (property_exists($data, "name")) {
            $name = $data->name;
            $recipe->title = $name ? RecipeParser_Text::formatTitle($name) : null;
        }
    
        // Summary
        if (property_exists($data, "description")) {
            $description = $data->description;
            $recipe->description = $description ? RecipeParser_Text::formatAsParagraphs($description) : null;
        }
    
        // Times
        if (property_exists($data, "prepTime")) {
            $prepTime = $data->prepTime;
            $recipe->time['prep'] = $prepTime ? RecipeParser_Text::formatISO_8601($prepTime) : null;
        }
        if (property_exists($data, "cookTime")) {
            $cookTime = $data->cookTime;
            $recipe->time['cook'] = $cookTime ? RecipeParser_Text::formatISO_8601($cookTime) : null;
        }
        if (property_exists($data, "totalTime")) {
            $totalTime = $data->totalTime;
            $recipe->time['total'] = $totalTime ? RecipeParser_Text::formatISO_8601($totalTime) : null;
        }
    
        // Yield
        if (property_exists($data, "recipeYield")) {
            $recipeYield = $data->recipeYield;
            $recipe->yield = $recipeYield ? RecipeParser_Text::formatAsParagraphs($recipeYield) : null;
        }
    
        // Ingredients
        $ingredients = property_exists($data, "recipeIngredient") ? $data->recipeIngredient : false;
        if (!$ingredients) {
            $ingredients = property_exists($data, "ingredients") ? $data->ingredients : [];
        }
        foreach ($ingredients as $ingredient) {
            $ingredient = RecipeParser_Text::formatAsOneLine($ingredient);
            if (empty($ingredient)) {
                continue;
            }
            if (strlen($ingredient) > 150) {
                // probably a mistake, like a run-on of existing ingredients?
                continue;
            }
            if (RecipeParser_Text::matchSectionName($ingredient)) {
                $ingredient = RecipeParser_Text::formatSectionName($ingredient);
                $recipe->addIngredientsSection($ingredient);
            } else {
                $recipe->appendIngredient($ingredient);
            }
        }
    
        // Photo
        if (property_exists($data, "image")) {
            $image = $data->image;

            // Grab first image if array was provided
            if (is_array($image)) {
                $image = array_values($image)[0];
            }

            // Extract url if image object was provided
            if (is_object($image)) {
                $image = $image->url;

            }
            $recipe->photo_url = RecipeParser_Text::relativeToAbsolute($image, $url);
        }
    
        // Credits
        if (property_exists($data, "author")) {
            $author = $data->author;

            // Grab first author if array was provided
            if (is_array($author)) {
                $author = array_values($author)[0];
            }

            // Extract name if author object was provided
            if (is_object($author)) {
                $author = $author->name;

            }

            $recipe->credits = RecipeParser_Text::formatCredits($author);
        }
        
        return $recipe;
    }

    static public function findRecipeJSON($xpath) {
        $scripts = $xpath->query('//script[@type="application/ld+json"]');

        foreach ($scripts as $script) {
            $jsons = trim($script->nodeValue);
            $jsons = RecipeParser_Text::cleanJson($jsons);
            $jsons = json_decode($jsons);

            if (is_array($jsons)) {
                foreach ($jsons as $json) {
                    if (
                        property_exists($json, "@context")
                        && stripos($json->{'@context'}, "schema.org") !== false
                        && property_exists($json, "@type")
                        && stripos($json->{'@type'}, "Recipe") !== false
                    ) {
                        return $json;
                    }
                }
            }
        }
    }

}
