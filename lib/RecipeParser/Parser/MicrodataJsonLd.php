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
    
        // Prep Time
        try {
            if (property_exists($data, "prepTime")) {
                $prepTime = self::cleanDuration($data->prepTime);
                $recipe->time['prep'] = $prepTime ? RecipeParser_Text::formatISO_8601($prepTime) : null;
            }
        } catch (\Exception $e) {
            $recipe->time['prep'] = null;
        }

        // Cook Time
        try {
            if (property_exists($data, "cookTime")) {
                $cookTime = self::cleanDuration($data->cookTime);
                $recipe->time['cook'] = $cookTime ? RecipeParser_Text::formatISO_8601($cookTime) : null;
            }
        } catch (\Exception $e) {
            $recipe->time['cook'] = null;
        }

        // Total Time
        try {
            if (property_exists($data, "totalTime")) {
                $totalTime = self::cleanDuration($data->totalTime);
                $recipe->time['total'] = $totalTime ? RecipeParser_Text::formatISO_8601($totalTime) : null;
            }
        } catch (\Exception $e) {
            $recipe->time['total'] = null;
        }


        // Yield
        if (property_exists($data, "recipeYield")) {
            $recipeYield = $data->recipeYield;
            if (is_array($recipeYield) and !empty($recipeYield)) {
                $recipeYield = $recipeYield[0];
            }
            $recipe->yield = $recipeYield ? RecipeParser_Text::formatAsParagraphs($recipeYield) : null;
        }
    
        // Ingredients
        $ingredients = property_exists($data, "recipeIngredient") ? $data->recipeIngredient : false;
        if (!$ingredients) {
            $ingredients = property_exists($data, "ingredients") ? $data->ingredients : [];
        }
        foreach ($ingredients as $ingredient) {
            $ingredient = RecipeParser_Text::formatAsOneLine($ingredient);

            // decode html entities and strip html tags
            $ingredient = trim(strip_tags(html_entity_decode($ingredient)));

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
    static public function cleanDuration($str) {
        $str = preg_replace("/P0Y0M0DT/", "", $str);
        $str = preg_replace("/0H/", "", $str);
        $str = preg_replace("/0.000S/", "", $str);
        if ($str && strpos($str, 'PT') === false) {
            $str = "PT" . $str;
        }
        return $str;
    }
    static public function findRecipeJSON($xpath) {
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scripts as $script) {
            $jsons = trim($script->nodeValue);
            $jsons = RecipeParser_Text::cleanJson($jsons);

            # strip php's trailing commas
            # $jsons = preg_replace("/,(?!.*,)/", "", $jsons);
            # $jsons = preg_replace("/,\s*]/", "]", $jsons);

            $jsons = json_decode($jsons);
            // check for array format
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
            } else {
                // check for graph format
                if (property_exists($jsons, "@graph")) {
                    foreach ($jsons->{'@graph'} as $json) {
                        if (
                                (
                                    (
                                        // case outter object
                                        property_exists($jsons, "@context")
                                        && stripos($jsons->{'@context'}, "schema.org") !== false
                                    ) || (
                                        // case inner object
                                        property_exists($json, "@context")
                                        && stripos($json->{'@context'}, "schema.org") !== false
                                     )
                                ) && property_exists($json, "@type")
                        ) {
                            // support type arrays per https://json-ld.org/spec/latest/json-ld/#specifying-the-type
                            if (is_array($json->{'@type'})) {
                                foreach($json->{'@type'} as $type) {
                                    if(stripos($type, "Recipe") !== false) {
                                        return $json;
                                    }
                                }
                            } else {
                                if (stripos($json->{'@type'}, "Recipe") !== false) {
                                    return $json;
                                }
                            }
                        }
                    }
                }
                // check for single entry format
                if (
                    property_exists($jsons, "@context")
                    && stripos($jsons->{'@context'}, "schema.org") !== false
                    && property_exists($jsons, "@type")
                    && stripos($jsons->{'@type'}, "Recipe") !== false
                ) {
                    return $jsons;
                }
            }
        }
    }

}
