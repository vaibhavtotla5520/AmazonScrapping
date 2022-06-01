    <?php
    class AmazonReaderV1 {
        public $_data = array();

        public function getStringBetween($string, $start, $end) {
            $string = " " . $string;
            $ini = strpos($string, $start);
            if ($ini == 0) {
                return "";
            }
            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;
            return substr($string, $ini, $len);
        }

        public function setProductDetail($html) {
            $this->dom = str_get_html($html);

            $sku_raw = $this->dom->find("div[id=fast-track] input", 0);
            $sku = '';
            if ($sku_raw) {
                $sku = $sku_raw->value;
            }
            $this->_data['sku'] = $sku;

            $title_raw = $this->dom->find("span[id=productTitle]", 0);
            $title = '';
            if ($title_raw) {
                $title = trim($title_raw->plaintext);
            }
            $this->_data['title'] = $title;

            $image = '';
            $image_raw = $this->dom->find("#altImages ul li img");
            foreach ($image_raw as $image_el) {
                if (strpos($image_el->src, 'US40')) {
                    $image = str_replace('US40', 'SX522', $image_el->src);
                    $this->_data['image'][] = $image;
                }
            }

            $price = '';
            $price_el = $this->dom->find(".a-price .a-offscreen", 0);
            if ($price_el) {
                $price = $price_el->plaintext;
            }
            $this->_data['price'] = $price;

            $specifications_raw = $this->dom->find("table[class=a-spacing-micro] tr");
            foreach ($specifications_raw as $specs) {
                $specs_name_raw = $specs->find("td", 0);
                $specs_value_raw = $specs_name_raw->next_sibling();
                $specs_name = '';
                if ($specs_name_raw) {
                    $specs_name = trim($specs_name_raw->plaintext);
                }
                $specs_value = '';
                if ($specs_value_raw) {
                    $specs_value = trim($specs_value_raw->plaintext);
                }
                $brand = '';
                if ($specs_name == 'Brand') {
                    $brand = $specs_value;
                    $this->_data['brand'] = $brand;
                }
                $this->_data['specifications'][$specs_name] = $specs_value;
            }

            $features = '';
            $features_raw = $this->dom->find("#feature-bullets ul li");
            foreach ($features_raw as $features_el) {
                if ($features_el->class != 'aok-hidden') {
                    $features_raw = $features_el->find(".a-list-item", 0);
                    if ($features_raw) {
                        $features = $features_raw->plaintext;
                        $this->_data['features'][] = $features;
                    }
                }
            }
            
            $variation = $this->dom->find("#twister_feature_div .a-form-label", 0);
            if ($variation) {
                $variation_type = str_replace(':', '', trim($variation->plaintext));

                $variation_images = $this->dom->find("#imageBlockVariations_feature_div script[type=text/javascript]");

                foreach ($variation_images as $jsonData) {
                    $variation_json_str = $jsonData->innertext();
                }
                $variation_data = $this->getStringBetween($variation_json_str, "var obj = jQuery.parseJSON('", "');");
                $variation_img_data = json_decode($variation_data, true);
                // $this->_data['variations'] = $variation_img_data['colorImages']['Gray'];

                $variation_name_raw = $this->dom->find("#twister_feature_div .twisterImages");
                foreach ($variation_name_raw as $variation_name) {

                    $variation_value = '';
                    $variation_value_raw = $variation_name->find("img", 0);
                    if ($variation_value_raw) {
                        $variation_value = $variation_value_raw->alt;
                    }

                    $variation_price = '';
                    $variation_price = trim($variation_name->plaintext);

                    $this->_data['variations_type'][$variation_type][$variation_value] = array(
                        "price" => $variation_price,
                        "images" => $variation_img_data['colorImages']['Gray'],
                    );
                }
            }

            $description = '';
            $description_raw = $this->dom->find("#aplus3p_feature_div p");
            foreach ($description_raw as $description_el) {
                $description = trim($description_el->plaintext);
                $this->_data['description'][] = $description;
            }

            $description_img = '';
            $description_img_raw = $this->dom->find("#aplus3p_feature_div img");
            foreach ($description_img_raw as $description_img_el) {
                $description_img = $description_img_el->src;
                if (!strpos($description_img, '.gif')) {
                    $this->_data['description_img'][] = $description_img;
                }
            }


            $information_raw = $this->dom->find("#productDetails_feature_div table tr");
            foreach ($information_raw as $information_el) {
                $heading = trim($information_el->find("th", 0)->plaintext);
                $value = trim($information_el->find("td", 0)->plaintext);
                $this->_data['information'][$heading] = $value;
            }

            $related_products = '';
            $related_products_raw = $this->dom->find("#sp_detail2 ol li");
            foreach ($related_products_raw as $related_products_el) {

                $image = str_replace(',160_', '', $related_products_el->find("img", 0)->src);
                $title = $related_products_el->find("a[class=a-link-normal]", 0)->title;
                $url = $related_products_el->find("a[class=a-link-normal]", 0)->href;
                $price = $related_products_el->find("span[class=a-color-price]", 0)->plaintext;

                $sku_el_ = $related_products_el->find("div", 0)->id;
                $product_sku_ = explode("_", $sku_el_);
                $product_sku_ = end($product_sku_);

                $related_products = array(
                    "title" => $title,
                    "image" => $image,
                    "url" => $url,
                    "price" => $price,
                    "sku" => $product_sku_
                );

                $this->_data['related_products'][] = $related_products;
            }

            $sponsered_products = '';
            $sponsered_products_raw = $this->dom->find("#sp_detail_thematic-highly_rated ol li");
            if ($sponsered_products_raw) {
                foreach ($sponsered_products_raw as $sponsered_products_el) {

                    $image = str_replace(',160_', '', $sponsered_products_el->find("img", 0)->src);
                    $title = $sponsered_products_el->find("a[class=a-link-normal]", 0)->title;
                    $url = $sponsered_products_el->find("a[class=a-link-normal]", 0)->href;
                    $price = $sponsered_products_el->find("span[class=a-color-price]", 0)->plaintext;

                    $sku_el = $sponsered_products_el->find("div", 0)->id;
                    $product_sku = explode("_", $sku_el);
                    $product_sku = end($product_sku);

                    $sponsered_products = array(
                        "title" => $title,
                        "image" => $image,
                        "url" => $url,
                        "price" => $price,
                        "sku" => $product_sku
                    );

                    $this->_data['sponsered_products'][] = $sponsered_products;
                }
            }

            return $this->_data;
        }
    }
