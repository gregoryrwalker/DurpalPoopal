<?php 
if(!class_exists('element_gva_history')):
   class element_gva_history{
      public function render_form(){
         $fields = array(
            'type' => 'gsc_history',
            'title' => t('History Content'),
            'size' => 3,
            'fields' => array(
               array(
                  'id'     => 'title',
                  'type'      => 'text',
                  'title'  => t('Title For Admin'),
                   'admin'     => true
               ),
               array(
                  'id'        => 'style_text',
                  'type'      => 'select',
                  'title'     => t('Skin Text for box'),
                  'options'   => array(
                     'text-dark'   => 'Text dark',
                     'text-light'   => 'Text light'
                  ),
                  'std'       => 'text-dark'
               ),
               array(
                  'id'        => 'el_class',
                  'type'      => 'text',
                  'title'     => t('Extra class name'),
                  'desc'      => t('Style particular content element differently - add a class name and refer to it in custom CSS.'),
               ), 
               array(
                  'id'     => 'animate',
                  'type'      => 'select',
                  'title'  => ('Animation'),
                  'desc'  => t('Entrance animation for element'),
                  'options'   => gavias_content_builder_animate(),
                  'class'     => 'width-1-2'
               ),
               array(
                  'id'        => 'animate_delay',
                  'type'      => 'select',
                  'title'     => t('Animation Delay'),
                  'options'   => gavias_content_builder_delay_wow(),
                  'desc'      => '0 = default',
                  'class'     => 'width-1-2'
               )                
            )                                   
         );

         for($i=1; $i<=8; $i++){
            $fields['fields'][] = array(
               'id'     => "info_${i}",
               'type'   => 'info',
               'desc'   => "Information for item {$i}"
            );
            $fields['fields'][] = array(
               'id'        => "date_{$i}",
               'type'      => 'text',
               'title'     => t("Date {$i}")
            );
            $fields['fields'][] = array(
               'id'           => "content_{$i}",
               'type'         => 'textarea',
               'title'        => t("Content {$i}"),
               'desc'         => t('Shortcodes and HTML tags allowed.'),
               'shortcodes'   => 'on'
            );
            $fields['fields'][] = array(
               'id'        => "image_{$i}",
               'type'      => 'upload',
               'title'     => t("Image {$i}")
            );
         }
         return $fields;
      }

      public static function render_content( $attr = array(), $content = '' ){
         global $base_url;
         $default = array(
            'title'           => '',
            'style_text'    => 'text-dark',
            'el_class'        => '',
            'animate'         => '',
            'animate_delay'   => '',
         );

         for($i=1; $i<=10; $i++){
            $default["date_{$i}"] = '';
            $default["content_{$i}"] = '';
            $default["image_{$i}"] = '';
         }

         extract(gavias_merge_atts($default, $attr));

         $_id = gavias_content_builder_makeid();
         ob_start();
         ?>

            <div class="gsc-history <?php echo $style_text ?> <?php echo $el_class ?>" <?php print gavias_content_builder_print_animate_wow($animate, $animate_delay) ?>> 
               <div class="owl-carousel init-carousel-owl" data-items="6" data-items_lg="6" data-items_md="6" data-items_sm="4" data-items_xs="2" data-loop="1" data-speed="500" data-auto_play="0" data-interval="false" data-auto_play_speed="600" data-auto_play_timeout="10000" data-auto_play_hover="1" data-navigation="0" data-rewind_nav="0" data-pagination="1" data-mouse_drag="1" data-touch_drag="1">
                  <?php for($i=1; $i<=8; $i++){ ?>
                     <?php 
                        $date = "date_{$i}";
                        $content = "content_{$i}";
                        $image = "image_{$i}";
                     ?>
                     <?php if($$date){ ?>
                        <div class="item">
                           <div class="item-content">  

                              <?php if($$date){ ?>
                                 <div class="date"><?php print $$date ?></div>
                              <?php } ?>
                              <?php if($$image){?>
                                <div class="image"><img src="<?php echo ($base_url . $$image) ?>" alt="<?php print $$date ?>"/></div>
                              <?php }  ?>
                              <?php if($$content){ echo '<div class="desc">' . $$content . '</div>'; } ?>
                              
                           </div>
                        </div>
                     <?php } ?>    
                  <?php } ?>
               </div>   
            </div> 

         <?php return ob_get_clean();
      }
   }
 endif;  



