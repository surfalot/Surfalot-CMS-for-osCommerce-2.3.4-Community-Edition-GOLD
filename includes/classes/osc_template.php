<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class oscTemplate {
    var $_title;
    var $_blocks = array();
    var $_content = array();
    var $_grid_container_width = 12;
    var $_grid_content_width = BOOTSTRAP_CONTENT;
    var $_grid_column_width = 0; // deprecated
    var $_data = array();
    var $_sort_order = 0; // SurfCMS added for temporary storage between instantiating a block/content and adding it to template array().

    function oscTemplate() {
      $this->_title = TITLE;
    }

    function setGridContainerWidth($width) {
      $this->_grid_container_width = $width;
    }

    function getGridContainerWidth() {
      return $this->_grid_container_width;
    }

    function setGridContentWidth($width) {
      $this->_grid_content_width = $width;
    }

    function getGridContentWidth() {
      return $this->_grid_content_width;
    }

    function setGridColumnWidth($width) {
      $this->_grid_column_width = $width;
    }

    function getGridColumnWidth() {
      return (12 - BOOTSTRAP_CONTENT) / 2;
    }

    function setTitle($title) {
      $this->_title = $title;
    }

    function getTitle() {
      return $this->_title;
    }

    function addBlock($block, $group, $sort = null) {
      // BOC: SurfCMS
	  if ( !isset($sort) ) {
	    $sort = $this->_sort_order;
	  }
	  if ( !is_numeric($sort) ) $sort = 0;
	  for ($i=0; isset($this->_blocks[$group][((int)$sort+$i)]); $i++) {}
	  $this->_blocks[$group][((int)$sort+$i)] = $block;
      // EOC: SurfCMS
	  // $this->_blocks[$group][] = $block;  // SurfCMS  removed
    }

    function hasBlocks($group) {
      return (isset($this->_blocks[$group]) && !empty($this->_blocks[$group]));
    }

    function getBlocks($group) {
      if ($this->hasBlocks($group)) {
		ksort($this->_blocks[$group]); // SurfCMS added
        return implode("\n", $this->_blocks[$group]);
      }
    }

    function buildBlocks() {
      global $language;

      if ( defined('TEMPLATE_BLOCK_GROUPS') && tep_not_null(TEMPLATE_BLOCK_GROUPS) ) {
        $tbgroups_array = explode(';', TEMPLATE_BLOCK_GROUPS);

        foreach ($tbgroups_array as $group) {
          $module_key = 'MODULE_' . strtoupper($group) . '_INSTALLED';

          if ( defined($module_key) && tep_not_null(constant($module_key)) ) {
            $modules_array = explode(';', constant($module_key));

            foreach ( $modules_array as $module ) {
              $class = substr($module, 0, strrpos($module, '.'));

              if ( !class_exists($class) ) {
                if ( file_exists(DIR_WS_LANGUAGES . $language . '/modules/' . $group . '/' . $module) ) {
                  include(DIR_WS_LANGUAGES . $language . '/modules/' . $group . '/' . $module);
                }

                if ( file_exists(DIR_WS_MODULES . $group . '/' . $class . '.php') ) {
                  include(DIR_WS_MODULES . $group . '/' . $class . '.php');
                }
              }

              if ( class_exists($class) ) {
                $mb = new $class();

                if ( $mb->isEnabled() ) {
				  $this->_sort_order = (int)$mb->sort_order; // SurfCMS added
                  $mb->execute();
                }
              }
            }
          }
        }
      }
    }

    function addContent($content, $group, $sort = null) {
      // BOC: SurfCMS
	  if ( !isset($sort) ) {
	    $sort = $this->_sort_order;
	  }
	  if ( !is_numeric($sort) ) $sort = 0;
	  for ($i=0; isset($this->_content[$group][((int)$sort+$i)]); $i++) {}
	  $this->_content[$group][((int)$sort+$i)] = $content;
	  // EOC: SurfCMS
      // $this->_content[$group][] = $content;  // SurfCMS  removed
    }

    function hasContent($group) {
      return (isset($this->_content[$group]) && !empty($this->_content[$group]));
    }

    function getContent($group) {
      global $language;

      if ( !class_exists('tp_' . $group) && file_exists(DIR_WS_MODULES . 'pages/tp_' . $group . '.php') ) {
        include(DIR_WS_MODULES . 'pages/tp_' . $group . '.php');
      }

      if ( class_exists('tp_' . $group) ) {
        $template_page_class = 'tp_' . $group;
        $template_page = new $template_page_class();
        $template_page->prepare();
      }

      foreach ( $this->getContentModules($group) as $module ) {
        if ( !class_exists($module) ) {
          if ( file_exists(DIR_WS_MODULES . 'content/' . $group . '/' . $module . '.php') ) {
            if ( file_exists(DIR_WS_LANGUAGES . $language . '/modules/content/' . $group . '/' . $module . '.php') ) {
              include(DIR_WS_LANGUAGES . $language . '/modules/content/' . $group . '/' . $module . '.php');
            }

            include(DIR_WS_MODULES . 'content/' . $group . '/' . $module . '.php');
          }
        }

        if ( class_exists($module) ) {
          $mb = new $module();

          if ( $mb->isEnabled() ) {
			$this->_sort_order = (int)$mb->sort_order; // SurfCMS added
            $mb->execute();
          }
        }
      }

      if ( class_exists('tp_' . $group) ) {
        $template_page->build();
      }

      if ($this->hasContent($group)) {
		ksort ($this->_content[$group]); // SurfCMS added
        return implode("\n", $this->_content[$group]);
      }
    }

    function getContentModules($group) {
      $result = array();

      foreach ( explode(';', MODULE_CONTENT_INSTALLED) as $m ) {
        $module = explode('/', $m, 2);

        if ( $module[0] == $group ) {
          $result[] = $module[1];
        }
      }

      return $result;
    }
  }
?>
