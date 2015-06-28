<?php

/**
* Plugin Name: Amazon Product Crawler
* Plugin URI: http://febin.me
* Author: Febin John James
* Description: Allows you to crawl amazon for a given list of ASIN and auto post it in your website.
* Version: 0.0.1
* License: GPLv2
*/

$GLOBALS['count']=0;
add_action('read_file_action','read_file',1,2);
add_action('crawl_product_action','crawl_product_details',1,3);


function crawl_product_details($ASIN_URL,$ASIN,$uniqid)
{
  // Crawl details from amazon

  try {

  $image1 = $image2 = $image3 = $image4 = $image5 = $image6 = $image7 = $image8 = $image9 = $image10 = '';
  $body = file_get_contents($ASIN_URL);


  $title = $brand = $list_price = $price = $you_save = $shipping = $category = $description = $feature = '';


  $doc = new DOMdocument();

  libxml_use_internal_errors(true);
  $doc->loadHTML($body);
  $docxpath = new DOMXpath($doc);


  $title = $doc->getElementById('productTitle');
  $brand = $doc->getElementById('brand');
  $price = $doc->getElementById('priceblock_ourprice');
  $you_save = $doc->getElementById('regularprice_savings');
  $feature = $doc->getElementById('feature-bullets');
  $available = FALSE;
  $shipping = "FREE";

  $availablity_element = $docxpath->query('//span[@class="a-size-medium a-color-success"]');
  foreach($availablity_element as $availablity)
  {
    $available = TRUE;
  }

  if($available == FALSE)
  {
    return;
  }

  if(isset($title->textContent))
  {
  $title = $title->textContent;
  }

  if(isset($brand->textContent))
  {
  $brand = $brand->textContent;
  }

  if(isset($price->textContent))
  {
  $price = $price->textContent;
  }


  if(isset($you_save->textContent))
  {
  $you_save = $you_save->textContent;
  }

  if(isset($feature->textContent))
  {
    $feature = $feature->textContent;
  }



  $image_element =$doc->getElementById('landingImage');
  $images = $image_element->getAttribute('data-a-dynamic-image');
  $regex = '/\b(https?|ftp|file|http):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
  preg_match_all($regex, $images, $matches);
  $urls = $matches[0];

  // go over all links
  $i=0;
  foreach($urls as $url) {
    if (strpos($url,'L.jpg') !== false) {
      $i++;
      ${'image'.$i} = $url;
      }
  }


  $list_prices = $docxpath->query('//td[@class="a-span12 a-color-secondary a-size-base a-text-strike"]');
  foreach($list_prices as $list_price)
  {
    $list_price = $list_price->textContent;
  }

  $shipping_prices = $docxpath->query('//span[@class="a-size-small a-color-secondary shipping3P"]');
  foreach($shipping_prices as $shipping_price)
  {
    $shipping_price = $shipping_price->textContent;
    $shipping_price = preg_replace('/[a-zA-Z]+/', '', $shipping_price);
    $shipping_price = str_replace('+','',$shipping_price);
     $shipping_price;
  }



  $category_element = $docxpath->query('//a[@class="nav-a nav-b"]');
  foreach($category_element as $category_meta)
  {
    $category = $category_meta->textContent;
  }

  $description_element = $doc->getElementsByTagName('meta');
  foreach ($description_element as $description_meta) {

        if($description_meta->getAttribute('name')==='description')
        {
         $description = $description_meta->getAttribute('content');
        }
  }
/*
  $Product = array(
  'ASIN' => $ASIN,
  'Title' => $title,
  'Brand' => $brand,
  'List Price' => $list_price,
  'Price' => $price,
  'You Save' => $you_save,
  'Shipping' => $shipping,
  'Category' => $category,
  'Description' => $description,
  'Feature' => $feature,
  'Image1' => $image1,
  'Image2' => $image2,
  'Image3' => $image3,
  'Image4' => $image4,
  'Image5' => $image5,
  'Image6' => $image6,
  'Image7' => $image7,
  'Image8' => $image8,
  'Image9' => $image9,
  'Image10' => $image10
);*/

  $product = array($ASIN,$title,$brand,$list_price,$price,$you_save,$shipping,$category,$description,$feature,$image1,$image2,$image3,$image4,$image5,$image6,$image7,$image8,$image9,$image10);
  $GLOBALS['count'] = $GLOBALS['count']  + 1;

  if($GLOBALS['count']%100==0)
  {
    sleep(5);
  }

  $upload_dir = wp_upload_dir();
  $fileOutput = $upload_dir['path']. '/' . $uniqid . '.csv';
  $fp = fopen($fileOutput, "a");
  fputcsv($fp,$product);
  fclose($fp);

  }
  catch (Exception $e)
  {
    print $e;
  }

}

add_action('admin_menu', 'aws_add_pages');

function aws_add_pages() {
    add_menu_page('Amazon Product Autoposter', 'ASIN Crawler', 'manage_options', 'aws-crawler', 'auto_post');
    add_plugins_page( 'CSV Reader', 'CSV Reader', 'None', 'upload', 'hello_world');
}



function auto_post() {
    handle_upload();
    ?>
    <div class="wrap">
        <h2>AWS Product Crawler</h2>
            <form method="post" enctype="multipart/form-data">
            <h3> Input ASIN (csv) </h3>
            <input type="file" id="asin_file" name='asin_file'/>
            <h3 > Email Id </h3>
            <input type="email" id="email" name='email'/>
            <br/>
             <?php submit_button('Upload') ?>
           </form>
    </div>
    <?php
}

function read_file($filepath,$email){

      $uniqid = uniqid();
      $upload_dir = wp_upload_dir();
      $fileOutput = $upload_dir['path']. '/' . $uniqid . '.csv';
      $fp = fopen( $fileOutput, "w" );
      $product_header = array('ASIN' ,'Title','Brand','List Price','Price','You Save','Shipping','Category','Description','Feature','Image1','Image2','Image3','Image4','Image5','Image6','Image7','Image8','Image9','Image10');
      fputcsv($fp,$product_header);
      fclose($fp);
      if (($handle = fopen($filepath, "r")) !== FALSE) {
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
              $row++;
              if($row == 1)
              {
                continue;
              }
              $asin_url =  $data[23];
              $asin =  $data[0];
              wp_schedule_single_event(time()+2,'crawl_product_action',array($asin_url,$asin,$uniqid));
            //  crawl_product_details($asin_url,$asin,$uniqid);
        }
      }


      wp_mail( $email, 'Your ASIN export is ready', 'Your asin file has been crawled fully. Here is the download link '. $upload_dir['base_url'] .'/'.$uniqid.'.csv' );
}


function handle_upload(){

        $GLOBALS['count'] = 0;
        if(isset($_FILES['asin_file'])){
                $file = $_FILES['asin_file'];
                $email = $_POST['email'];

                $upload_dir = wp_upload_dir();
                $uniqid = uniqid();

                copy($file['tmp_name'], $upload_dir['path']. '/ASIN' . $uniqid . '.csv');
                $filepath = $upload_dir['path']. '/ASIN' . $uniqid . '.csv';
              //  read_file($filepath,$email);
               wp_schedule_single_event(time()+5, 'read_file_action', array($filepath,$email));
                }
}



?>
